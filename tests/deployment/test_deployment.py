"""Isolated deployment contracts and Bash failure simulations (no real network)."""
import copy
import hashlib
import importlib.util
import io
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tarfile
import tempfile
import time
import unittest

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / "deploy"))
import build_release

SHA = "a" * 40
OLD = "b" * 40
SCHEMA = "c" * 64
REPO = "TIKMOS/tikmos.com"
PHP = shutil.which("php")
BASH = os.environ.get("BASH_BIN", shutil.which("bash"))


def php_call(expression, data):
    program = 'require $argv[1]; $d=json_decode(stream_get_contents(STDIN),true); try {' + expression + ';} catch(Throwable $e){fwrite(STDERR,$e->getMessage());exit(1);}'
    return subprocess.run([PHP, "-r", program, str(ROOT / "deploy/verify.php")], input=json.dumps(data), text=True, capture_output=True)


class ProofTests(unittest.TestCase):
    def setUp(self):
        self.run = {"id": 123, "run_attempt": 2, "head_sha": SHA, "head_branch": "main", "event": "push", "path": ".github/workflows/ci.yml", "head_repository": {"full_name": REPO}, "status": "completed", "conclusion": "success"}
        self.job = {"total_count": 2, "jobs": [{"name": name, "status": "completed", "conclusion": "success"} for name in ("validate", "publish")]}
        self.tag = f"deploy-{SHA}-123-2"
        self.release = {"tag_name": self.tag, "target_commitish": SHA, "draft": False, "assets": [{"name": "tikmos.tar.gz", "state": "uploaded", "size": 100, "digest": "sha256:" + SCHEMA, "browser_download_url": f"https://github.com/{REPO}/releases/download/{self.tag}/tikmos.tar.gz"}]}

    def run_result(self, run):
        return php_call('deployRun($d,"' + REPO + '","main","' + SHA + '")', {"workflow_runs": [] if run is None else [run]})

    def test_exact_successful_run(self):
        self.assertEqual(self.run_result(self.run).returncode, 0)

    def test_incomplete_cancelled_failed_skipped_unknown_runs_refused(self):
        for status, conclusion in (("queued", None), ("in_progress", None), ("completed", "failure"), ("completed", "cancelled"), ("completed", "skipped"), ("completed", "neutral"), ("completed", None)):
            with self.subTest(status=status, conclusion=conclusion):
                run = dict(self.run, status=status, conclusion=conclusion)
                self.assertNotEqual(self.run_result(run).returncode, 0)
        self.assertNotEqual(self.run_result(None).returncode, 0)

    def test_other_sha_pr_fork_or_workflow_refused(self):
        for field, value in (("head_sha", OLD), ("event", "pull_request"), ("head_branch", "other"), ("path", "other.yml"), ("head_repository", {"full_name": "other/repo"})):
            with self.subTest(field=field):
                self.assertNotEqual(self.run_result(dict(self.run, **{field: value})).returncode, 0)

    def test_newer_pending_run_never_falls_back_to_older_green(self):
        result = php_call('deployRun($d,"' + REPO + '","main","' + SHA + '")', {"workflow_runs": [dict(self.run, status="in_progress", conclusion=None), self.run]})
        self.assertNotEqual(result.returncode, 0)

    def test_all_required_jobs_must_succeed(self):
        self.assertEqual(php_call("deployJobs($d)", self.job).returncode, 0)
        for change in ("skipped", "cancelled", "failure", None):
            jobs = copy.deepcopy(self.job)
            jobs["jobs"][1]["conclusion"] = change
            self.assertNotEqual(php_call("deployJobs($d)", jobs).returncode, 0)
        self.assertNotEqual(php_call("deployJobs($d)", {"total_count": 0, "jobs": []}).returncode, 0)

    def test_release_must_bind_sha_run_attempt_digest_and_public_url(self):
        expression = f'deployAsset($d,"{REPO}","{self.tag}","{SHA}")'
        self.assertEqual(php_call(expression, self.release).returncode, 0)
        for field, value in (("target_commitish", OLD), ("tag_name", f"deploy-{SHA}-123-1"), ("draft", True)):
            self.assertNotEqual(php_call(expression, dict(self.release, **{field: value})).returncode, 0)
        for field, value in (("digest", None), ("state", "starter"), ("browser_download_url", "https://example.test/asset")):
            changed = copy.deepcopy(self.release)
            changed["assets"][0][field] = value
            self.assertNotEqual(php_call(expression, changed).returncode, 0)


class ArchiveTests(unittest.TestCase):
    def test_pending_registry_on_isolated_sqlite_before_and_after_migration(self):
        with tempfile.TemporaryDirectory(prefix="tikmos-pending-") as tmp:
            state = Path(tmp)
            for directory in ("storage/framework/views", "storage/framework/cache/data", "storage/framework/sessions", "storage/logs", "cache"):
                (state / directory).mkdir(parents=True, exist_ok=True)
            expression = '''
            foreach (["APP_ENV"=>"testing","APP_KEY"=>"base64:".base64_encode(random_bytes(32)),"DB_CONNECTION"=>"sqlite","DB_DATABASE"=>":memory:","DB_URL"=>"","MAIL_MAILER"=>"array","CACHE_STORE"=>"array","SESSION_DRIVER"=>"array","QUEUE_CONNECTION"=>"sync","LARAVEL_STORAGE_PATH"=>$d[0]."/storage","VIEW_COMPILED_PATH"=>$d[0]."/storage/framework/views"] as $key=>$value) {$_ENV[$key]=$_SERVER[$key]=$value;putenv($key."=".$value);}
            foreach (["CONFIG","SERVICES","PACKAGES","ROUTES","EVENTS"] as $key) {$name="APP_".$key."_CACHE";$value=$d[0]."/cache/".$key.".php";$_ENV[$name]=$_SERVER[$name]=$value;putenv($name."=".$value);}
            require $d[1]."/vendor/autoload.php";
            $app=require $d[1]."/bootstrap/app.php";
            if (PHP_OS_FAMILY==="Windows") {$app->addAbsoluteCachePathPrefix(substr($d[0],0,2));}
            $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
            if (!deployPendingMigrations($app["migrator"],$app->databasePath("migrations"))) {throw new Exception("Expected pending migrations");}
            if (Illuminate\\Support\\Facades\\Artisan::call("migrate",["--force"=>true])!==0) {throw new Exception("Isolated migration failed");}
            if (deployPendingMigrations($app["migrator"],$app->databasePath("migrations"))) {throw new Exception("Unexpected pending migrations");}
            $app["db"]->table("migrations")->where("migration","2026_10_27_030100_add_delivery_pricing_to_orders")->delete();
            if (!deployPendingMigrations($app["migrator"],$app->databasePath("migrations"))) {throw new Exception("Missing migration not detected");}
            '''
            result = php_call(expression, [str(state), str(ROOT)])
            self.assertEqual(result.returncode, 0, result.stderr)

    def test_bad_hash_traversal_symlink_and_sensitive_payload_refused(self):
        with tempfile.TemporaryDirectory() as tmp:
            archive = Path(tmp) / "test.tar.gz"
            for name, kind in (("../escape.php", "file"), (".env", "file"), ("public/storage/a", "file"), ("app/link", "symlink")):
                with self.subTest(name=name):
                    with tarfile.open(archive, "w:gz") as tar:
                        item = tarfile.TarInfo(name)
                        if kind == "symlink":
                            item.type = tarfile.SYMTYPE
                            item.linkname = "/outside"
                            tar.addfile(item)
                        else:
                            item.size = 1
                            tar.addfile(item, io.BytesIO(b"x"))
                    digest = hashlib.sha256(archive.read_bytes()).hexdigest()
                    result = subprocess.run([PHP, str(ROOT / "deploy/verify.php"), "archive", str(archive), digest, "100000", SHA, "123", "2"], capture_output=True)
                    self.assertNotEqual(result.returncode, 0)

    def test_package_scan_refuses_secrets(self):
        with tempfile.TemporaryDirectory() as tmp:
            stage = Path(tmp) / "stage"
            stage.mkdir()
            (stage / ".env").write_text("fictitious")
            with self.assertRaises(AssertionError):
                build_release.pack(stage, Path(tmp) / "test.tar.gz")

    def test_package_scan_refuses_private_key_in_runtime_source(self):
        with tempfile.TemporaryDirectory() as tmp:
            stage = Path(tmp) / "stage"
            (stage / "app").mkdir(parents=True)
            (stage / "app/example.txt").write_text("-----BEGIN PRIVATE KEY-----\nFICTITIOUS\n")
            with self.assertRaises(AssertionError):
                build_release.pack(stage, Path(tmp) / "test.tar.gz")

    def test_migration_fingerprint_changes_on_content_and_settings(self):
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            (root / "database/settings").mkdir(parents=True)
            before = build_release.migrations(root)
            (root / "database/settings/new.php").write_text("<?php // fictitious")
            self.assertNotEqual(before, build_release.migrations(root))


class ShellTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(prefix="tikmos-deploy-test-")
        self.root = Path(self.temp.name)
        self.control = ROOT / "deploy"
        self.bin = self.root / "bin"
        self.bin.mkdir()
        self.app = self.root / "app"
        for name in ("shared/storage/app/public", "releases/old/public", "recovery", "source/.git"):
            (self.app / name).mkdir(parents=True, exist_ok=True)
        (self.app / "shared/.env").write_text("FICTITIOUS=1\n")
        (self.app / "shared/.env").chmod(0o600)
        (self.app / "releases/old/.deploy-release.json").write_text(json.dumps({"sha": OLD, "migrations": SCHEMA}))
        self.asset = self.root / "asset.tar.gz"
        stage = self.root / "fixture"
        stage.mkdir()
        files = {"artisan": "<?php", "composer.lock": "{}", "vendor/autoload.php": "<?php", "public/index.php": "<?php", "public/build/manifest.json": "{}", "public/deployment-version.txt": SHA + "\n", ".deploy-release.json": json.dumps({"sha": SHA, "migrations": SCHEMA, "run_id": 123, "attempt": 2})}
        for name, content in files.items():
            path = stage / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content)
        build_release.pack(stage, self.asset)
        self.digest = hashlib.sha256(self.asset.read_bytes()).hexdigest()
        self.calls = self.root / "calls"
        self.env = dict(os.environ, MSYS="winsymlinks:sys", FIXTURE_ASSET=str(self.asset), FIXTURE_DIGEST=self.digest, REAL_PHP=PHP, CALLS=str(self.calls), SHA=SHA, OLD=OLD, SCHEMA=SCHEMA)
        self.env["PATH"] = str(self.bin) + os.pathsep + self.env["PATH"]
        # MSYS stat reports synthetic Windows permissions: explicitly mock this
        # platform difference; Linux CI exercises real 0600 checks.
        if os.name == "nt":
            self.script("stat", 'if [[ "$1" == -c && "$2" == %a ]]; then echo 600; else /usr/bin/stat "$@"; fi')
        self.script("git-mock", 'if [[ " $* " == *" status "* ]]; then [[ ${FAIL_AT:-} != dirty ]] || echo " M app/file.php"; else [[ ${FAIL_AT:-} != network ]] || exit 28; printf "%s\\trefs/heads/main\\n" "${HEAD_SHA:-$SHA}"; fi')
        self.script("php-mock", '''echo "php $*" >> "$CALLS"
if [[ "$1" == -r ]]; then exit 0; fi
if [[ "$1" == *verify.php ]]; then
  case "$2" in
    github) [[ ${FAIL_AT:-} != unvalidated ]] || exit 1
      [[ ${SLOW:-} != yes ]] || sleep 2
      printf '%s\\t123\\t2\\t%s\\thttps://github.com/TIKMOS/tikmos.com/releases/download/test/tikmos.tar.gz\\t1000\\n' "$SHA" "$FIXTURE_DIGEST" ;;
    runtime|ready) [[ ${FAIL_AT:-} != runtime ]] ;;
    pending) printf '%s\\n' "${PENDING:-no}" ;;
    *) exec "$REAL_PHP" "$@" ;;
  esac
elif [[ "$1" == *composer.phar ]]; then [[ ${FAIL_AT:-} != composer ]]
elif [[ "$1" == */artisan ]]; then
  if [[ "$2" == migrate ]]; then [[ ${FAIL_AT:-} != migration ]]; else [[ ${FAIL_AT:-} != cache ]]; fi
else exit 1; fi''')
        self.script("curl-mock", '''echo "curl $*" >> "$CALLS"
if [[ " $* " == *" --location "* ]]; then
  while (( $# )); do if [[ "$1" == -o ]]; then cp "$FIXTURE_ASSET" "$2"; exit 0; fi; shift; done
elif [[ " $* " == *"/up "* || "${*: -1}" == */up ]]; then
  [[ ${FAIL_AT:-} != health ]] || exit 22
  printf 200
else printf '%s' "$SHA"; fi''')
        (self.bin / "composer.phar").write_text("fictitious")
        self.config = self.root / "config.sh"
        def posix(path):
            if os.name == "nt":
                return "/" + str(path)[0].lower() + str(path)[2:].replace("\\", "/")
            return str(path)
        self.posix = posix
        environment = self.root / "shell-environment.sh"
        environment.write_text("export PATH='" + posix(self.bin) + "':$PATH\n", newline="\n")
        self.env["BASH_ENV"] = posix(environment)
        config = (self.control / "config.example.sh").read_text()
        replacements = {"APP_ROOT": self.app, "PROJECT_DIR": self.app / "source", "PUBLIC_DIR": self.app / "current/public", "PHP_BIN": self.bin / "php-mock", "GIT_BIN": self.bin / "git-mock", "CURL_BIN": self.bin / "curl-mock", "COMPOSER_PHAR": self.bin / "composer.phar"}
        import re
        for name, path in replacements.items():
            config = re.sub(r"^" + name + r"=.*$", name + "='" + posix(path) + "'", config, flags=re.M)
        config = config.replace("ENABLED='false'", "ENABLED='true'").replace("DRY_RUN='true'", "DRY_RUN='false'").replace("SYMLINK_CONFIRMED='false'", "SYMLINK_CONFIRMED='true'").replace("LOCK_DRIVER='auto'", "LOCK_DRIVER='mkdir'")
        self.config.write_text(config, newline="\n")
        self.config.chmod(0o600)
        self.env["TIKMOS_DEPLOY_CONFIG"] = posix(self.config)
        self.bash('ln -s "$1/releases/old" "$1/current"', posix(self.app), check=True)

    def tearDown(self):
        # MSYS emulated links are files, not traversable by native Windows tools.
        if os.name == "nt":
            for root, dirs, files in os.walk(self.root):
                for name in files:
                    try:
                        (Path(root) / name).chmod(0o600)
                    except OSError:
                        pass
        self.temp.cleanup()

    def script(self, name, content):
        path = self.bin / name
        path.write_text("#!/usr/bin/env bash\nset -eu\n" + content + "\n", newline="\n")
        path.chmod(0o700)

    def bash(self, code, *args, check=False):
        return subprocess.run([BASH, "-c", code, "fixture", *args], env=self.env, text=True, capture_output=True, check=check)

    def run_deploy(self, mode="run", failure=None):
        if failure:
            self.env["FAIL_AT"] = failure
        return subprocess.run([BASH, str(self.control / "check-updates.sh"), mode], env=self.env, text=True, capture_output=True, timeout=40)

    def current(self):
        return self.bash('readlink "$1/current"', self.posix(self.app)).stdout.strip()

    def test_validated_commit_deploys_and_marks_success(self):
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stdout + result.stderr)
        self.assertIn(SHA, self.current())
        self.assertIn(SHA, (self.app / "state/last-success").read_text())
        self.assertEqual((self.app / "shared/.env").read_text(), "FICTITIOUS=1\n")

    def test_no_update_runs_no_composer_migration_or_cache(self):
        self.env["HEAD_SHA"] = OLD
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("unchanged", result.stdout)
        self.assertNotIn("composer", self.calls.read_text())

    def test_dry_run_does_not_change_application(self):
        result = self.run_deploy("dry-run")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("would-deploy", result.stdout)
        self.assertTrue(self.current().endswith("/old"))
        self.assertFalse((self.app / "state/last-success").exists())

    def test_disabled_by_default_does_not_contact_github(self):
        with self.config.open("a") as config:
            config.write("\nENABLED='false'\nDRY_RUN='true'\n")
        result = self.run_deploy()
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("disabled", result.stdout)
        self.assertFalse((self.app / "state").exists())
        self.assertNotIn(" github ", self.calls.read_text())

    def test_diagnostic_is_read_only(self):
        result = self.run_deploy("diagnostic")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertFalse((self.app / "state").exists())
        self.assertTrue(self.current().endswith("/old"))

    def test_new_main_before_switch_refused(self):
        self.script("git-mock", '''if [[ " $* " == *" status "* ]]; then exit 0; fi
if [[ -e "$CALLS.head" ]]; then printf '%s\\trefs/heads/main\\n' "$OLD"; else touch "$CALLS.head"; printf '%s\\trefs/heads/main\\n' "$SHA"; fi''')
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Newer main commit", result.stderr)
        self.assertTrue(self.current().endswith("/old"))

    def test_network_unvalidated_dirty_composer_and_health_failures(self):
        # Each subcase needs independent release/state directories.
        for failure in ("network", "unvalidated", "dirty", "composer", "health"):
            with self.subTest(failure=failure):
                if failure != "network":
                    self.tearDown()
                    self.setUp()
                result = self.run_deploy(failure=failure)
                self.assertNotEqual(result.returncode, 0, result.stdout)
                expected = {"network": "step=remote", "unvalidated": "step=validation", "dirty": "step=preflight", "composer": "step=composer-platform", "health": "step=health"}
                self.assertIn(expected[failure], result.stdout)
                self.assertTrue(self.current().endswith("/old"), result.stdout)
                self.assertFalse((self.app / "state/last-success").exists())

    def test_changed_migrations_require_explicit_approval(self):
        meta = self.app / "releases/old/.deploy-release.json"
        meta.write_text(json.dumps({"sha": OLD, "migrations": "d" * 64}))
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("exact-SHA approval", result.stderr)
        self.assertTrue(self.current().endswith("/old"))

    def test_unapplied_existing_migrations_require_approval_too(self):
        self.env["PENDING"] = "yes"
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("exact-SHA approval", result.stderr)
        self.assertTrue(self.current().endswith("/old"))

    def test_approved_migration_failure_keeps_old_code_without_db_rollback_claim(self):
        (self.app / "releases/old/.deploy-release.json").write_text(json.dumps({"sha": OLD, "migrations": "d" * 64}))
        backup = self.app / "recovery/database.sql"
        backup.write_text("-- fictitious backup\n")
        backup.chmod(0o600)
        with self.config.open("a") as config:
            config.write(f"\nMIGRATION_APPROVED_SHA='{SHA}'\nBACKUP_CONFIRMED_SHA='{SHA}'\nDB_BACKUP_FILE='{self.posix(backup)}'\n")
        result = self.run_deploy(failure="migration")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("step=migrations", result.stdout)
        self.assertTrue(self.current().endswith("/old"))

    def test_insufficient_disk_and_configuration_permissions_refused(self):
        with self.config.open("a") as config:
            config.write("\nMIN_FREE_MB=999999999\n")
        result = self.run_deploy()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Insufficient free disk", result.stderr)
        self.config.chmod(0o644)
        if os.name == "nt":
            self.script("stat", 'if [[ "$1" == -c && "$2" == %a ]]; then echo 644; else /usr/bin/stat "$@"; fi')
        result = self.run_deploy("diagnostic")
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("mode 600", result.stderr)

    def test_exclusive_lock_prevents_second_deployment(self):
        self.env["SLOW"] = "yes"
        first = subprocess.Popen([BASH, str(self.control / "check-updates.sh"), "dry-run"], env=self.env, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        deadline = time.monotonic() + 8
        while not (self.app / "state/deploy.lock.d/pid").exists() and time.monotonic() < deadline:
            time.sleep(0.03)
        second = self.run_deploy("dry-run")
        out, err = first.communicate(timeout=30)
        self.assertEqual(first.returncode, 0, out + err)
        self.assertEqual(second.returncode, 0, second.stderr)
        self.assertIn("Lock held", second.stdout)
        self.assertFalse((self.app / "state/deploy.lock.d").exists())

    @unittest.skipIf(os.name == "nt", "flock unavailable in local MSYS; exercised on Linux CI")
    def test_flock_driver(self):
        with self.config.open("a") as config:
            config.write("\nLOCK_DRIVER='flock'\n")
        result = self.run_deploy("dry-run")
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(self.current().endswith("/old"))

    def test_fresh_archive_and_metadata_are_verified(self):
        expression = 'deployArchive($d[0],$d[1],10000000,"' + SHA + '",123,2)'
        self.assertEqual(php_call(expression, [str(self.asset), self.digest]).returncode, 0)
        self.assertNotEqual(php_call(expression, [str(self.asset), "0" * 64]).returncode, 0)
        self.assertNotEqual(php_call(expression.replace(SHA, OLD), [str(self.asset), self.digest]).returncode, 0)

    def test_php_and_python_migration_fingerprints_match(self):
        result = php_call('echo deployMigrationFingerprint($d[0])', [str(ROOT)])
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stdout, build_release.migrations(ROOT))


class WorkflowTests(unittest.TestCase):
    def test_bash_syntax(self):
        for path in (ROOT / "deploy").glob("*.sh"):
            subprocess.run([BASH, "-n", str(path)], check=True)

    def test_workflow_contract(self):
        # YAML syntax additionally checked with actionlint in CI/local tooling.
        content = (ROOT / ".github/workflows/ci.yml").read_text()
        self.assertIn("needs: validate", content)
        self.assertIn("php vendor/bin/pest --compact", content)
        self.assertIn("contents: read", content)
        self.assertNotIn("continue-on-error", content)
        self.assertNotIn("pull_request_target", content)
        self.assertNotIn("--clobber", content)


if __name__ == "__main__":
    unittest.main()
