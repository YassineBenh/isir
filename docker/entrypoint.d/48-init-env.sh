#!/bin/sh

CONFIG_DIR="$APP_BASE_DIR/storage/config"
ENV_FILE="$APP_BASE_DIR/.env"
KEY_FILE="$CONFIG_DIR/.app_key"

mkdir -p "$CONFIG_DIR"

# Copy .env.example.production → .env if not exists
if [ ! -f "$ENV_FILE" ]; then
    echo "Creating .env file from .env.example.production..."
    cp "$APP_BASE_DIR/.env.example.production" "$ENV_FILE"
fi

# Generate or restore APP_KEY
if [ -f "$KEY_FILE" ]; then
    echo "Restoring APP_KEY from backup..."
    APP_KEY="$(tr -d '\r\n' < "$KEY_FILE")"
else
    echo "Generating new APP_KEY..."
    APP_KEY="$(php artisan key:generate --show)"
    printf '%s\n' "$APP_KEY" > "$KEY_FILE"
fi

# Update APP_KEY in .env
ESCAPED_APP_KEY="$(printf '%s' "$APP_KEY" | sed -e 's/[&|]/\\&/g')"

if grep -q '^APP_KEY=' "$ENV_FILE"; then
    sed -i "s|^APP_KEY=.*|APP_KEY=$ESCAPED_APP_KEY|" "$ENV_FILE"
else
    printf '\nAPP_KEY=%s\n' "$APP_KEY" >> "$ENV_FILE"
fi

exit 0
