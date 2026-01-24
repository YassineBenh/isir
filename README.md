# ISIR

## Installation

### Docker Compose (Recommended)

```yaml
services:
    isir:
        image: ghcr.io/yassinebenh/isir:latest
        restart: unless-stopped
        ports:
            - '8080:8080'
        environment:
            APP_URL: 'https://your-domain.com'
        volumes:
            - isir_data:/var/www/html/storage

volumes:
    isir_data:
```

Set `APP_URL` to your domain.

```bash
docker compose up -d
```

The app will be available at `http://localhost:8080`.

### Docker

```bash
docker run -d \
  -p 8080:8080 \
  -e APP_URL=https://your-domain.com \
  -v isir_data:/var/www/html/storage \
  ghcr.io/yassinebenh/isir:latest
```

Set `APP_URL` to your domain. The app will be available at `http://localhost:8080`.
