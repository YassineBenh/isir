# ISIR

**It's Short; I Read** — an open-source digest aggregator that monitors GitHub repositories for new releases and delivers scheduled summaries to Slack, Discord, or Email. Self-hostable and AI-ready.

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
            # GITHUB_TOKEN: ''  # Optional: Increases rate limit (60 → 5000 req/hr)
        volumes:
            - isir_data:/var/www/html/storage

volumes:
    isir_data:
```

- `APP_URL` — Required. Set to your domain.
- `GITHUB_TOKEN` — Optional. Increases GitHub API rate limit from 60 to 5000 requests/hour. No special scopes needed. [Create a token here](https://github.com/settings/tokens).

```bash
docker compose up -d
```

The app will be available at `http://localhost:8080`.

### Docker

```bash
docker run -d \
  -p 8080:8080 \
  -e APP_URL=https://your-domain.com \
  -e GITHUB_TOKEN=your_token_here `# Optional` \
  -v isir_data:/var/www/html/storage \
  ghcr.io/yassinebenh/isir:latest
```

See environment variables above. The app will be available at `http://localhost:8080`.
