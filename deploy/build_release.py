"""CI only: export an allowlisted runtime tree, install locked prod vendors, pack it."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import tarfile
import tempfile

from ci_checks import ROOT, SECRET, assets

RUNTIME = {"app", "bootstrap", "config", "database", "lang", "public", "resources", "routes"}


def migrations(root):
    digest = hashlib.sha256()
    for directory in ("database/migrations", "database/settings"):
        for path in sorted((root / directory).glob("*.php")):
            digest.update(path.relative_to(root).as_posix().encode() + b"\0" + path.read_bytes())
    return digest.hexdigest()


def pack(stage, output):
    files = sorted(path for path in stage.rglob("*") if path.is_file())
    for path in files:
        name = path.relative_to(stage).as_posix()
        assert not path.is_symlink(), "Symlinks are not packaged"
        assert not any(part.startswith(".env") or part in {"auth.json", ".git", "node_modules"} for part in path.relative_to(stage).parts), "Sensitive file"
        assert not name.startswith(("storage/", "bootstrap/cache/", "public/storage/")) and name != "public/hot", "Runtime state"
        assert not SECRET.search(path.read_bytes()), "Possible secret in archive"
    with tarfile.open(output, "w:gz") as archive:
        for path in files:
            archive.add(path, arcname=path.relative_to(stage).as_posix(), recursive=False)


def main(output):
    sha = os.environ["GITHUB_SHA"]
    assert len(sha) == 40 and all(c in "0123456789abcdef" for c in sha)
    assert subprocess.check_output(["git", "rev-parse", "HEAD"], cwd=ROOT, text=True).strip() == sha
    output.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory(prefix="tikmos-release-") as temp:
        stage = Path(temp)
        tracked = subprocess.check_output(["git", "ls-files", "-z"], cwd=ROOT).decode().split("\0")
        for name in tracked:
            if not name or not (name.split("/")[0] in RUNTIME or name in {"artisan", "composer.json", "composer.lock"}):
                continue
            if name.startswith(("bootstrap/cache/", "public/build/", "public/storage/")) or name == "public/hot":
                continue
            source = ROOT / name
            assert source.is_file() and not source.is_symlink()
            target = stage / name
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(source, target)
        shutil.copytree(ROOT / "public/build", stage / "public/build")
        # No package scripts: no .env, key generation, migration or cached CI config.
        composer = shutil.which("composer")
        assert composer, "Composer executable required on the CI/build machine"
        subprocess.run([composer, "install", "--working-dir=" + str(stage), "--no-dev", "--prefer-dist", "--no-progress", "--no-interaction", "--no-scripts", "--no-plugins", "--optimize-autoloader"], check=True)
        shutil.rmtree(stage / "vendor/bin", ignore_errors=True)
        metadata = {"sha": sha, "run_id": int(os.environ["GITHUB_RUN_ID"]), "attempt": int(os.environ["GITHUB_RUN_ATTEMPT"]), "migrations": migrations(stage)}
        (stage / ".deploy-release.json").write_text(json.dumps(metadata) + "\n")
        (stage / "public/deployment-version.txt").write_text(sha + "\n")
        assets(stage)
        pack(stage, output / "tikmos.tar.gz")
        archive = output / "tikmos.tar.gz"
        subprocess.run(["php", str(ROOT / "deploy/verify.php"), "archive", str(archive), hashlib.sha256(archive.read_bytes()).hexdigest(), str(1024**3), sha, str(metadata["run_id"]), str(metadata["attempt"])], check=True)
        print("Production archive checked:", sha, archive.stat().st_size, "bytes")


if __name__ == "__main__":
    parser = argparse.ArgumentParser()
    parser.add_argument("--output", type=Path, required=True)
    main(parser.parse_args().output)
