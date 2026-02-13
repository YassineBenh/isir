# ISIR

[![GitHub Release](https://img.shields.io/github/v/release/yassinebenh/isir)](https://github.com/yassinebenh/isir/releases/latest)

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
            # AI_DEFAULT_PROVIDER: 'openai' # Optional: openai, anthropic, ollama, gemini, mistral, groq, deepseek, xai
            # OPENAI_API_KEY: ''            # Optional: required for openai provider
            # ANTHROPIC_API_KEY: ''         # Optional: required for anthropic provider
            # OLLAMA_BASE_URL: 'http://host.docker.internal:11434' # Optional: for ollama provider
        volumes:
            - isir_data:/var/www/html/storage

volumes:
    isir_data:
```

### Environment Variables

- `APP_URL` — Required. Set to your domain.
- `GITHUB_TOKEN` — Optional. Increases GitHub API rate limit from 60 to 5000 requests/hour. No special scopes needed. [Create a token here](https://github.com/settings/tokens).

### AI Summaries

To enable AI-generated summaries for your digests, configure these environment variables:

- `AI_DEFAULT_PROVIDER` — The default provider used by Laravel AI (defaults to `openai`).
- Provider API key env vars from Laravel AI (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`, `MISTRAL_API_KEY`, `GROQ_API_KEY`, `DEEPSEEK_API_KEY`, `XAI_API_KEY`).
- For local Ollama, set `AI_DEFAULT_PROVIDER=ollama` and optionally `OLLAMA_BASE_URL`.

At least one valid provider key (or a reachable Ollama URL) must be configured for AI summaries to work. When not configured, the AI summary feature is disabled in the UI.

### Email Notifications

To enable email delivery for your digests, configure the mail environment variables. ISIR uses Laravel's mail system, which supports multiple drivers including SMTP, Mailgun, Postmark, SES, and more.

For the full list of supported mail drivers and configuration options, see the [Laravel Mail documentation](https://laravel.com/docs/mail).

The app will be available at `http://localhost:8080`.

```bash
docker compose up -d
```

### Docker

```bash
docker run -d \
  -p 8080:8080 \
  -e APP_URL=https://your-domain.com \
  -e GITHUB_TOKEN=your_token_here \
  -e AI_DEFAULT_PROVIDER=openai \
  -e OPENAI_API_KEY=your_api_key_here \
  -v isir_data:/var/www/html/storage \
  ghcr.io/yassinebenh/isir:latest
```

See environment variables above. The app will be available at `http://localhost:8080`.
