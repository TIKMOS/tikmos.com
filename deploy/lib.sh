#!/usr/bin/env bash
# Trusted, manually installed control-plane scripts; never executed from a release.
set -Eeuo pipefail
umask 077
export LC_ALL=C GIT_TERMINAL_PROMPT=0
STEP=configuration TARGET=none START_SECONDS=$SECONDS LOCK_HELD=false SWITCHED=false

die() { printf 'REFUSED: %s\n' "$1" >&2; exit 1; }
load_config() {
    local config=${TIKMOS_DEPLOY_CONFIG:-"$HOME/tikmos-deploy/config.sh"} mode owner
    [[ -f "$config" && ! -L "$config" ]] || die 'Private configuration missing or symlinked.'
    mode=$(stat -c '%a' "$config"); owner=$(stat -c '%u' "$config")
    [[ "$owner" == "$(id -u)" && "$mode" == 600 ]] || die 'Configuration must belong to cron user and have mode 600.'
    # shellcheck source=/dev/null
    source "$config"
    for name in APP_ROOT PROJECT_DIR PUBLIC_DIR PHP_BIN COMPOSER_PHAR GIT_BIN CURL_BIN; do
        [[ ${!name:-} == /* && ${!name} != *CPANEL_USER* && ${!name} != *$'\n'* ]] || die "Configure absolute path: $name"
    done
    [[ -d "$APP_ROOT" && ! -L "$APP_ROOT" ]] || die 'APP_ROOT must be an existing private directory.'
    APP_ROOT=$(realpath -e -- "$APP_ROOT")
    [[ "$APP_ROOT" != / && "$APP_ROOT" != "$HOME" && "$APP_ROOT" != */public_html* ]] || die 'Unsafe APP_ROOT.'
    [[ "$PUBLIC_DIR" == "$APP_ROOT/current/public" ]] || die 'Only current/public document root is supported.'
    [[ "$REPOSITORY" =~ ^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$ && "$BRANCH" == main ]] || die 'Invalid public repository or unsupported production branch.'
    [[ "$HEALTH_URL" =~ ^https://[^/?#]+/up$ && "$VERSION_URL" == "${HEALTH_URL%/up}/deployment-version.txt" ]] || die 'Configure matching HTTPS health/version URLs.'
    for name in ENABLED DRY_RUN SYMLINK_CONFIRMED; do [[ ${!name} == true || ${!name} == false ]] || die "Invalid boolean: $name"; done
    for name in MIN_FREE_MB MAX_ARCHIVE_BYTES MAX_EXPANDED_BYTES NETWORK_TIMEOUT DEPLOY_TIMEOUT LOG_MAX_BYTES BACKUP_MAX_AGE_SECONDS; do
        [[ ${!name} =~ ^[1-9][0-9]*$ ]] || die "Invalid numeric limit: $name"
    done
    for name in "$PHP_BIN" "$GIT_BIN" "$CURL_BIN"; do [[ -x "$name" ]] || die 'Configured executable unavailable.'; done
    [[ -f "$COMPOSER_PHAR" ]] || die 'Composer PHAR unavailable.'
    for name in timeout tar realpath stat df ln mv; do command -v "$name" >/dev/null || die "Required utility missing: $name"; done
    "$PHP_BIN" -r 'exit(PHP_VERSION_ID >= 80300 && PHP_INT_SIZE === 8 && extension_loaded("curl") && extension_loaded("phar") ? 0 : 1);' || die 'PHP 8.3+, 64 bit, cURL and Phar required.'
    STATE="$APP_ROOT/state"; LOGS="$APP_ROOT/logs"; SHARED="$APP_ROOT/shared"
    RELEASES="$APP_ROOT/releases"; DOWNLOADS="$APP_ROOT/downloads"; RECOVERY="$APP_ROOT/recovery"
    for name in state logs shared releases downloads recovery; do
        [[ ! -L "$APP_ROOT/$name" ]] || die 'Private directory must not be symlinked.'
        [[ ! -e "$APP_ROOT/$name" || -d "$APP_ROOT/$name" ]] || die 'Private path is not a directory.'
    done
}
log() {
    local message="$(date -u +%FT%TZ) step=$STEP current=${CURRENT_SHA:-unknown} target=$TARGET $1"
    printf '%s\n' "$message"
    [[ -d ${LOGS:-/nonexistent} ]] || return 0
    if [[ -f "$LOGS/deploy.log" ]] && (( $(stat -c '%s' "$LOGS/deploy.log") >= LOG_MAX_BYTES )); then
        mv -f -- "$LOGS/deploy.log" "$LOGS/deploy.log.1"
    fi
    printf '%s\n' "$message" >> "$LOGS/deploy.log"
}
acquire_lock() {
    mkdir -p -- "$STATE" "$LOGS" "$RELEASES" "$DOWNLOADS" "$RECOVERY"
    if [[ "$LOCK_DRIVER" == flock || ( "$LOCK_DRIVER" == auto && $(command -v flock || true) ) ]]; then
        command -v flock >/dev/null || die 'flock unavailable.'
        exec 9>"$STATE/deploy.lock"
        flock -n 9 || { printf 'Another deployment check is running.\n'; exit 0; }
        LOCK_KIND=flock
    else
        [[ "$LOCK_DRIVER" == auto || "$LOCK_DRIVER" == mkdir ]] || die 'Invalid lock driver.'
        mkdir -- "$STATE/deploy.lock.d" 2>/dev/null || { printf 'Lock held; inspect it manually if stale.\n'; exit 0; }
        printf '%s\n' "$$" > "$STATE/deploy.lock.d/pid"
        LOCK_KIND=mkdir
    fi
    LOCK_HELD=true
}
atomic_text() { printf '%s\n' "$2" > "$1.tmp.$$"; mv -f -- "$1.tmp.$$" "$1"; }
on_exit() {
    local result=$?
    trap - EXIT
    if (( result != 0 )); then
        if [[ "$SWITCHED" == true ]]; then
            ln -s -- "$PREVIOUS" "$APP_ROOT/restore.$$" && mv -Tf -- "$APP_ROOT/restore.$$" "$APP_ROOT/current" || true
            log 'rollback=code-attempted database=not-restored'
            if health "$CURRENT_SHA"; then log 'rollback-health=passed'; else log 'rollback-health=FAILED manual-intervention-required'; fi
        fi
        log "result=FAILED duration=$((SECONDS-START_SECONDS))s"
        [[ "$LOCK_HELD" != true ]] || atomic_text "$STATE/last-failure" "$(date -u +%FT%TZ) step=$STEP target=$TARGET"
    fi
    if [[ "$LOCK_HELD" == true && "$LOCK_KIND" == mkdir ]]; then
        rm -f -- "$STATE/deploy.lock.d/pid"
        rmdir -- "$STATE/deploy.lock.d"
    fi
    exit "$result"
}
head_sha() {
    local line
    line=$(timeout "$NETWORK_TIMEOUT" "$GIT_BIN" -c credential.helper= ls-remote --exit-code "https://github.com/$REPOSITORY.git" "refs/heads/$BRANCH" 2>/dev/null) || die 'GitHub HTTPS unavailable; current release retained.'
    line=${line%%$'\t'*}
    [[ "$line" =~ ^[a-f0-9]{40}$ ]] || die 'Invalid remote SHA.'
    printf '%s\n' "$line"
}
current_version() {
    CURRENT_SHA=none
    if [[ -L "$APP_ROOT/current" ]]; then
        PREVIOUS=$(realpath -e -- "$APP_ROOT/current")
        [[ "$PREVIOUS" == "$RELEASES/"* ]] || die 'Current release is outside releases directory.'
        CURRENT_SHA=$("$PHP_BIN" "$CONTROL/verify.php" metadata "$PREVIOUS/.deploy-release.json" sha)
    elif [[ -e "$APP_ROOT/current" ]]; then die 'current must be a symlink; no in-place deployment supported.'; fi
}
run_step() {
    STEP=$1; shift
    log 'begin'
    # Raw command output may include production config/SQL; never publish it to cron logs.
    if ! timeout "$DEPLOY_TIMEOUT" "$@" >"$STATE/command-output.private" 2>&1; then
        log 'command=FAILED details=private-local-file'; return 1
    fi
    rm -f -- "$STATE/command-output.private"
    log 'passed'
}
health() {
    local sha=$1 version code
    code=$("$CURL_BIN" --silent --show-error --fail --proto '=https' --connect-timeout 10 --max-time "$NETWORK_TIMEOUT" -H 'Cache-Control: no-cache' -o /dev/null -w '%{http_code}' "$HEALTH_URL") || return 1
    [[ "$code" == 200 ]] || return 1
    version=$("$CURL_BIN" --silent --show-error --fail --proto '=https' --connect-timeout 10 --max-time "$NETWORK_TIMEOUT" -H 'Cache-Control: no-cache' "$VERSION_URL?sha=$sha") || return 1
    [[ "$version" == "$sha" ]]
}
