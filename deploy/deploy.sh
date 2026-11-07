#!/bin/bash
set -e
DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$DIR"

echo "[$(date -Iseconds)] Pulling latest demo files..."
git pull origin main

MOODLE_DIR="/home/algoritam/public_html/moodle"
cp demo.php "$MOODLE_DIR/"
cp algoritam-demo.mbz "$MOODLE_DIR/"
cp algoritam-demo-sr.mbz "$MOODLE_DIR/"

echo "[$(date -Iseconds)] Running demo.php reset..."
/usr/bin/php "$MOODLE_DIR/demo.php" reset
echo "[$(date -Iseconds)] Algorithm demo deploy finished successfully!"
