#!/bin/sh

DB_PATH="$APP_BASE_DIR/database/data/database.sqlite"

if [ ! -f "$DB_PATH" ]; then
    echo "Creating SQLite database file..."
    touch "$DB_PATH"
fi

exit 0
