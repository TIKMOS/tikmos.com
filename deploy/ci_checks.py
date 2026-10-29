"""Small standard-library CI checks; no production environment is loaded."""
import json
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[1]
SECRET = re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----|gh[pousr]_[A-Za-z0-9]{30,}|AKIA[A-Z0-9]{16}")


def assets(root=ROOT):
    public = root / "public"
    manifest = json.loads((public / "build/manifest.json").read_text())
    assert "resources/css/app.css" in manifest and "resources/js/app.js" in manifest
    for entry in manifest.values():
        for name in [entry["file"], *entry.get("css", []), *entry.get("assets", [])]:
            path = (public / "build" / name).resolve()
            assert path.is_relative_to((public / "build").resolve()) and path.is_file(), name
    assert not (public / "hot").exists()
    for path in (public / "build").rglob("*"):
        if path.is_file():
            assert not SECRET.search(path.read_bytes()), "Possible secret in compiled assets"


def syntax():
    files = subprocess.check_output(["git", "ls-files", "-z"], cwd=ROOT).decode().split("\0")
    # New control-plane PHP must also be checked before the first commit.
    files = sorted(set(files + ["deploy/verify.php"]))
    for name in files:
        if name.endswith(".php") and not name.endswith(".blade.php"):
            subprocess.run([os.environ.get("PHP_BIN", "php"), "-l", str(ROOT / name)], check=True, stdout=subprocess.DEVNULL)
    print("PHP syntax: passed")


if __name__ == "__main__":
    {"assets": assets, "syntax": syntax}[sys.argv[1]]()
