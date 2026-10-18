#!/usr/bin/env bash
# Daily database backup. Run it from cron (see DEPLOY-GUIDE.md).
# The database password is NOT in this file. mysqldump reads it from ~/.my.cnf (chmod 600) of the user who runs it.
set -euo pipefail

DB_NAME="${DB_NAME:-mutamireen}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/mutamireen}"
KEEP_DAYS="${KEEP_DAYS:-30}"

mkdir -p "$BACKUP_DIR"
chmod 700 "$BACKUP_DIR"

FILE="$BACKUP_DIR/mutamireen-$(date +%F-%H%M).sql.gz"

mysqldump --single-transaction --routines --no-tablespaces "$DB_NAME" | gzip > "$FILE"
chmod 600 "$FILE"

# Delete backups older than KEEP_DAYS days.
find "$BACKUP_DIR" -name 'mutamireen-*.sql.gz' -mtime +"$KEEP_DAYS" -delete

echo "Backup written: $FILE"
