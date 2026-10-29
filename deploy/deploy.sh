#!/usr/bin/env bash
set -Eeuo pipefail
CONTROL=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)
# shellcheck source=lib.sh
source "$CONTROL/lib.sh"
load_config
MODE=${1:-run}
[[ $# -le 1 ]] || die 'No target SHA argument accepted; GitHub validation is mandatory.'
case "$MODE" in diagnostic|dry-run|run|status) ;; *) die 'Use diagnostic, dry-run, run or status.' ;; esac
current_version
if [[ "$MODE" == status ]]; then
    printf 'enabled=%s dry_run=%s deployed=%s\n' "$ENABLED" "$DRY_RUN" "$CURRENT_SHA"
    for item in last-success last-failure last-validated; do
        [[ ! -f "$STATE/$item" ]] || { printf '%s: ' "$item"; cat "$STATE/$item"; }
    done
    exit 0
fi
if [[ "$MODE" == run && ( "$ENABLED" != true || "$DRY_RUN" == true ) ]]; then
    printf 'Automatic deployment disabled; use diagnostic or dry-run explicitly.\n'; exit 0
fi
if [[ "$MODE" == diagnostic ]]; then
    printf 'PHP, utilities and private paths: configured. current=%s\n' "$CURRENT_SHA"
    printf 'symlink_confirmed=%s enabled=%s dry_run=%s\n' "$SYMLINK_CONFIRMED" "$ENABLED" "$DRY_RUN"
    TARGET=$(head_sha)
    "$PHP_BIN" "$CONTROL/verify.php" github "$REPOSITORY" "$BRANCH" "$TARGET" "$NETWORK_TIMEOUT"
    printf 'Diagnostic only: no application, state, cache or database write.\n'
    exit 0
fi
acquire_lock
trap on_exit EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
STEP=remote; TARGET=$(head_sha)
log 'check-start'
if [[ "$TARGET" == "$CURRENT_SHA" ]]; then log 'result=unchanged'; exit 0; fi
STEP=validation
proof=$("$PHP_BIN" "$CONTROL/verify.php" github "$REPOSITORY" "$BRANCH" "$TARGET" "$NETWORK_TIMEOUT")
IFS=$'\t' read -r SHA RUN_ID ATTEMPT DIGEST ASSET_URL ASSET_BYTES <<< "$proof"
[[ "$SHA" == "$TARGET" && "$RUN_ID" =~ ^[0-9]+$ && "$ATTEMPT" =~ ^[0-9]+$ && "$DIGEST" =~ ^[a-f0-9]{64}$ ]] || die 'Invalid validation proof.'
atomic_text "$STATE/last-validated" "$TARGET run=$RUN_ID attempt=$ATTEMPT"
log "validation=passed run=$RUN_ID attempt=$ATTEMPT"
if [[ "$MODE" == dry-run ]]; then log 'result=would-deploy application=unchanged'; exit 0; fi
STEP=preflight
[[ "$SYMLINK_CONFIRMED" == true && "$CURRENT_SHA" != none ]] || die 'Manual initial cutover and symlink validation required.'
[[ -d "$PROJECT_DIR/.git" || -f "$PROJECT_DIR/.git" ]] || die 'Existing source Git checkout unavailable.'
[[ -z $("$GIT_BIN" -C "$PROJECT_DIR" status --porcelain --untracked-files=normal) ]] || die 'Existing source checkout modified; preserve and review changes manually.'
[[ -f "$SHARED/.env" && ! -L "$SHARED/.env" && -d "$SHARED/storage" ]] || die 'Prepare persistent .env and storage manually.'
[[ $(stat -c '%a' "$SHARED/.env") == 600 ]] || die 'Persistent .env must have mode 600.'
[[ -w "$RELEASES" && -w "$SHARED/storage" && -w "$APP_ROOT" ]] || die 'Insufficient permissions.'
free=$(df -Pk -- "$APP_ROOT" | awk 'NR==2 {print $4}')
[[ "$free" =~ ^[0-9]+$ ]] && (( free >= MIN_FREE_MB * 1024 )) || die 'Insufficient free disk space.'
(( ASSET_BYTES <= MAX_ARCHIVE_BYTES )) || die 'Archive exceeds configured download limit.'
archive="$DOWNLOADS/$TARGET-$RUN_ID-$ATTEMPT.tar.gz"
release="$RELEASES/$TARGET-$RUN_ID-$ATTEMPT"
[[ ! -e "$release" ]] || die 'Release directory exists after previous attempt; inspect manually.'
STEP=download
"$CURL_BIN" --silent --show-error --fail --location --proto '=https' --proto-redir '=https' --connect-timeout 10 --max-time "$DEPLOY_TIMEOUT" --max-filesize "$MAX_ARCHIVE_BYTES" "$ASSET_URL" -o "$archive"
"$PHP_BIN" "$CONTROL/verify.php" archive "$archive" "$DIGEST" "$MAX_EXPANDED_BYTES" "$TARGET" "$RUN_ID" "$ATTEMPT"
mkdir -- "$release"
tar --extract --gzip --file "$archive" --directory "$release" --no-same-owner --no-same-permissions
STEP=persistence
ln -s -- "$SHARED/.env" "$release/.env"
ln -s -- "$SHARED/storage" "$release/storage"
ln -s -- "$SHARED/storage/app/public" "$release/public/storage"
mkdir -p -- "$release/bootstrap/cache/views"
# Only the new public tree is made readable; persistent uploads are never chmodded.
chmod 711 -- "$release"
find "$release/public" -type d -exec chmod 755 {} +
find "$release/public" -type f -exec chmod 644 {} +
export VIEW_COMPILED_PATH="$release/bootstrap/cache/views"
run_step composer-platform "$PHP_BIN" "$COMPOSER_PHAR" --working-dir="$release" check-platform-reqs --no-dev
run_step discover "$PHP_BIN" "$release/artisan" package:discover --no-interaction
run_step runtime "$PHP_BIN" "$CONTROL/verify.php" runtime "$release"
old_schema=$("$PHP_BIN" "$CONTROL/verify.php" metadata "$PREVIOUS/.deploy-release.json" migrations)
new_schema=$("$PHP_BIN" "$CONTROL/verify.php" metadata "$release/.deploy-release.json" migrations)
STEP=schema-check
pending=$("$PHP_BIN" "$CONTROL/verify.php" pending "$release")
[[ "$pending" == yes || "$pending" == no ]] || die 'Invalid pending migration result.'
if [[ "$old_schema" != "$new_schema" || "$pending" == yes ]]; then
    STEP=migration-approval
    [[ "$MIGRATION_APPROVED_SHA" == "$TARGET" && "$BACKUP_CONFIRMED_SHA" == "$TARGET" && -s "$DB_BACKUP_FILE" && ! -L "$DB_BACKUP_FILE" ]] || die 'Migration change requires exact-SHA approval and a recent private database backup.'
    backup=$(realpath -e -- "$DB_BACKUP_FILE")
    [[ "$backup" == "$RECOVERY/"* && $(stat -c '%a' "$backup") == 600 ]] || die 'Backup must be private under recovery/.'
    age=$(( $(date +%s) - $(stat -c '%Y' "$backup") ))
    (( age >= 0 && age <= BACKUP_MAX_AGE_SECONDS )) || die 'Backup too old.'
    run_step migrations "$PHP_BIN" "$release/artisan" migrate --force --no-interaction
fi
for cache in config route event view; do run_step "cache-$cache" "$PHP_BIN" "$release/artisan" "$cache:cache" --no-interaction; done
run_step ready "$PHP_BIN" "$CONTROL/verify.php" ready "$release"
STEP=head-recheck
[[ $(head_sha) == "$TARGET" ]] || die 'Newer main commit detected; retain current version.'
fresh_proof=$("$PHP_BIN" "$CONTROL/verify.php" github "$REPOSITORY" "$BRANCH" "$TARGET" "$NETWORK_TIMEOUT")
[[ "$fresh_proof" == "$proof" ]] || die 'CI run, attempt or archive changed during preparation.'
atomic_text "$RECOVERY/previous-$TARGET" "$PREVIOUS"
STEP=switch
ln -s -- "$release" "$APP_ROOT/next.$$"
mv -Tf -- "$APP_ROOT/next.$$" "$APP_ROOT/current"
SWITCHED=true
STEP=health
health "$TARGET" || die 'HTTP health/version check failed.'
# Cron drains short-lived workers; no daemon is installed or restarted here.
STEP=success
atomic_text "$STATE/last-success" "$(date -u +%FT%TZ) sha=$TARGET previous=$CURRENT_SHA duration=$((SECONDS-START_SECONDS))s"
SWITCHED=false
log "result=success health=passed duration=$((SECONDS-START_SECONDS))s"
