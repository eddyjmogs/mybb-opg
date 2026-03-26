#!/bin/bash
# Watches /templates/ for changes and syncs to DB automatically.
# Requirements: fswatch (brew install fswatch)
#
# Usage: ./watch_templates.sh

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

if ! command -v fswatch &> /dev/null; then
    echo "fswatch not found. Install it with: brew install fswatch"
    exit 1
fi

echo "Watching templates/ for changes... (Ctrl+C to stop)"

fswatch -o "$SCRIPT_DIR/templates/" | while read -r event; do
    echo "[$(date '+%H:%M:%S')] Change detected, syncing..."
    php "$SCRIPT_DIR/import_templates.php"
done
