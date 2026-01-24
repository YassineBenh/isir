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
            # AI_PROVIDER: ''   # Optional: anthropic, openai, ollama, gemini, mistral, groq
            # AI_MODEL: ''      # Optional: Model name (e.g., gpt-5-mini, claude-sonnet-4-20250514)
            # AI_API_KEY: ''    # Optional: API key for the AI provider
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

- `AI_PROVIDER` — The AI provider to use: `anthropic`, `openai`, `ollama`, `gemini`, `mistral`, or `groq`.
- `AI_MODEL` — The model name (e.g., `gpt-5-mini` for OpenAI, `claude-sonnet-4-20250514` for Anthropic).
- `AI_API_KEY` — Your API key for the chosen provider.

All three variables must be set for AI summaries to work. When not configured, the AI summary feature will be disabled in the UI.

### Email Notifications

To enable email delivery for your digests, configure the mail environment variables. ISIR uses Laravel's mail system, which supports multiple drivers including SMTP, Mailgun, Postmark, SES, and more.

For the full list of supported mail drivers and configuration options, see the [Laravel Mail documentation](https://laravel.com/docs/mail).

```bash
docker compose up -d
```

The app will be available at `http://localhost:8080`.

### Docker

```bash
docker run -d \
  -p 8080:8080 \
  -e APP_URL=https://your-domain.com \
  -e GITHUB_TOKEN=your_token_here \
  -e AI_PROVIDER=openai \
  -e AI_MODEL=gpt-5-mini \
  -e AI_API_KEY=your_api_key_here \
  -v isir_data:/var/www/html/storage \
  ghcr.io/yassinebenh/isir:latest
```

See environment variables above. The app will be available at `http://localhost:8080`.
