#!/bin/sh

DB_PATH="$APP_BASE_DIR/storage/config/database.sqlite"

mkdir -p "$(dirname "$DB_PATH")"

if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    touch "$DB_PATH"
fi

exit 0
