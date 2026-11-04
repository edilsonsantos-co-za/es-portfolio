#!/bin/bash

# ES Portfolio Backup Manager
# Usage: ./backup-manager.sh [list|download|restore|cleanup]

PROJECT_DIR="/var/www/html"  # Update this path
BACKUP_DIR="$PROJECT_DIR/storage/app/backups"

case "$1" in
  "list")
    echo "Available backups:"
    ls -lah "$BACKUP_DIR"/*.sql 2>/dev/null || echo "No backups found"
    ;;
    
  "download")
    LATEST=$(ls -t "$BACKUP_DIR"/*.sql 2>/dev/null | head -1)
    if [ -n "$LATEST" ]; then
      cp "$LATEST" ~/latest_backup.sql
      echo "Latest backup copied to ~/latest_backup.sql"
      echo "File: $(basename $LATEST)"
    else
      echo "No backups found"
    fi
    ;;
    
  "restore")
    if [ -z "$2" ]; then
      echo "Usage: $0 restore <backup-file>"
      echo "Available files:"
      ls "$BACKUP_DIR"/*.sql 2>/dev/null || echo "No backups found"
      exit 1
    fi
    
    BACKUP_FILE="$BACKUP_DIR/$2"
    if [ -f "$BACKUP_FILE" ]; then
      echo "Restoring from: $BACKUP_FILE"
      # Add your MySQL restore command here
      echo "mysql -h\$DB_HOST -u\$DB_USERNAME -p\$DB_PASSWORD \$DB_DATABASE < $BACKUP_FILE"
    else
      echo "Backup file not found: $BACKUP_FILE"
    fi
    ;;
    
  "cleanup")
    echo "Cleaning up old backups (keeping last 5)..."
    find "$BACKUP_DIR" -name "*.sql" -type f | sort | head -n -5 | xargs rm -f
    echo "Cleanup complete"
    ;;
    
  *)
    echo "Usage: $0 [list|download|restore|cleanup]"
    echo "  list     - List all available backups"
    echo "  download - Copy latest backup to home directory"
    echo "  restore  - Restore from a specific backup file"
    echo "  cleanup  - Remove old backups (keep last 5)"
    ;;
esac