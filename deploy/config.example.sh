# Copy outside the document root, e.g. /home/CPANEL_USER/tikmos-deploy/config.sh.
# Owner only: chmod 600 config.sh. This is trusted shell configuration.
APP_ROOT='/home/CPANEL_USER/tikmos-deploy'
# Existing manually deployed checkout, read only; must be clean before cutover.
PROJECT_DIR='/home/CPANEL_USER/tikmos-source'
# Set the domain document root to this path manually in cPanel, if supported.
PUBLIC_DIR='/home/CPANEL_USER/tikmos-deploy/current/public'
REPOSITORY='TIKMOS/tikmos.com'
BRANCH='main'
PHP_BIN='/usr/local/bin/ea-php83'
# Absolute path to a Composer 2 PHAR, invoked with PHP_BIN. No install on server.
COMPOSER_PHAR='/home/CPANEL_USER/bin/composer.phar'
GIT_BIN='/usr/bin/git'
CURL_BIN='/usr/bin/curl'
HEALTH_URL='https://tikmos.com/up'
VERSION_URL='https://tikmos.com/deployment-version.txt'
ENABLED='false'
DRY_RUN='true'
# Set only after testing the real cPanel symlink, document root and cron account.
SYMLINK_CONFIRMED='false'
# Migrations default to refused. Approval binds a backup and migration to ONE SHA.
MIGRATION_APPROVED_SHA=''
BACKUP_CONFIRMED_SHA=''
DB_BACKUP_FILE=''
BACKUP_MAX_AGE_SECONDS=3600
MIN_FREE_MB=512
MAX_ARCHIVE_BYTES=268435456
MAX_EXPANDED_BYTES=1073741824
NETWORK_TIMEOUT=30
DEPLOY_TIMEOUT=600
LOCK_DRIVER='auto' # auto: flock if available, else mkdir; never auto-break stale locks
LOG_MAX_BYTES=1048576
# Derived private directories: APP_ROOT/{state,logs,downloads,releases,recovery,shared}.
# No token, password, API key or production .env belongs in this file/repository.
