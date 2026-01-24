# ISIR

## Installation

### Docker Compose (Recommended)

```bash
curl -O https://raw.githubusercontent.com/yassinebenh/isir/main/docker-compose.yaml
docker compose up -d
```

Edit `docker-compose.yaml` to set your `APP_URL`.

### Docker

```bash
docker run -d \
  -p 8080:8080 \
  -e APP_URL=https://your-domain.com \
  -v isir_data:/var/www/html/storage \
  ghcr.io/yassinebenh/isir:latest
```

The app will be available at `http://localhost:8080`.
