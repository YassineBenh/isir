# ISIR Data Model

This document describes the data model for ISIR (It's Short, I Read), a release digest service.

## Design Decisions

1. **Sources are global** (not user-scoped) to avoid fetching the same item multiple times when multiple users track the same source.
2. **Extensible source types**: The `Source` model uses a `type` field to support GitHub repos initially, with RSS feeds, YouTube channels, etc. planned for the future.
3. **AI summaries are per-digest**: `DigestItemSummary` is scoped to a `(digest_id, source_item_id)` pair, allowing different digests to have different summary styles/prompts.
4. **Digest output is Markdown**: `DigestRun.rendered_content` stores a single Markdown string for simplicity.
5. **Authentication via Socialite**: No dedicated `connected_accounts` table; OAuth tokens are managed by Laravel Socialite.

## Entity Relationship Diagram

```
User
├── Digest (hasMany)
│   ├── Source (belongsToMany via digest_source)
│   ├── Destination (belongsToMany via digest_destination)
│   ├── DigestRun (hasMany)
│   │   ├── SourceItem (belongsToMany via digest_run_source_item)
│   │   └── DeliveryAttempt (hasMany)
│   └── DigestItemSummary (hasMany)
└── Destination (hasMany)

Source (global)
└── SourceItem (hasMany)
    └── DigestItemSummary (hasMany)
```

## Models

### Source

A thing the system monitors for updates (GitHub repo, RSS feed, YouTube channel, etc.).

| Column          | Type      | Description                                                  |
| --------------- | --------- | ------------------------------------------------------------ |
| id              | bigint    | Primary key                                                  |
| type            | string    | Source type: `github_repo`, `rss_feed`, `youtube_channel`    |
| canonical_key   | string    | Unique identifier: `github:owner/repo`, `rss:url`, etc.      |
| name            | string    | Display name                                                 |
| url             | string?   | Web URL for the source                                       |
| config          | json?     | Type-specific config (repo owner/name, feed URL, channel ID) |
| is_enabled      | boolean   | Whether to fetch updates                                     |
| last_fetched_at | datetime? | Last successful fetch time                                   |
| fetch_state     | json?     | Cursor, etag, last-modified for incremental sync             |
| last_error      | text?     | Last fetch error message                                     |

### SourceItem

An individual update from a source (GitHub release, RSS entry, video, etc.).

| Column       | Type      | Description                                          |
| ------------ | --------- | ---------------------------------------------------- |
| id           | bigint    | Primary key                                          |
| source_id    | bigint    | FK to sources                                        |
| external_id  | string    | Provider ID (release ID, RSS GUID, video ID)         |
| title        | string    | Item title                                           |
| url          | string?   | Direct link to the item                              |
| published_at | datetime? | When the item was published                          |
| raw_content  | longtext? | Full content (release notes, RSS body, etc.)         |
| metadata     | json?     | Type-specific data (tag name, prerelease flag, etc.) |

**Unique constraint**: `(source_id, external_id)`

### Digest

A user-configured digest context with schedule and preferences.

| Column                 | Type      | Description                                       |
| ---------------------- | --------- | ------------------------------------------------- |
| id                     | bigint    | Primary key                                       |
| user_id                | bigint    | FK to users                                       |
| name                   | string    | Digest name ("Backend Stack", "Security Updates") |
| frequency              | string    | `daily` or `weekly`                               |
| timezone               | string    | User's timezone for scheduling                    |
| send_time              | time      | Time of day to send                               |
| send_day_of_week       | tinyint?  | 0-6 (Sunday-Saturday) for weekly digests          |
| is_enabled             | boolean   | Whether to run this digest                        |
| last_successful_run_at | datetime? | Last successful run timestamp                     |
| ai_enabled             | boolean   | Whether to generate AI summaries                  |
| ai_prefs               | json?     | Custom AI settings (model, prompt, etc.)          |

### Destination

A delivery channel for digests (Slack, Discord, email).

| Column     | Type    | Description                                       |
| ---------- | ------- | ------------------------------------------------- |
| id         | bigint  | Primary key                                       |
| user_id    | bigint  | FK to users                                       |
| type       | string  | `slack`, `discord`, or `email`                    |
| name       | string  | Display name ("#dev-updates", "Work Email")       |
| config     | json    | Type-specific config (webhook URL, email address) |
| is_enabled | boolean | Whether to use this destination                   |

### DigestRun

A historical record of a scheduled digest compilation.

| Column           | Type      | Description                                 |
| ---------------- | --------- | ------------------------------------------- |
| id               | bigint    | Primary key                                 |
| digest_id        | bigint    | FK to digests                               |
| period_start_at  | datetime  | Start of the time window                    |
| period_end_at    | datetime  | End of the time window                      |
| status           | string    | `pending`, `running`, `completed`, `failed` |
| rendered_content | longtext? | Final Markdown output                       |
| started_at       | datetime? | When processing started                     |
| finished_at      | datetime? | When processing finished                    |
| error            | text?     | Error message if failed                     |

### DeliveryAttempt

A per-destination send status for a digest run.

| Column              | Type      | Description                                    |
| ------------------- | --------- | ---------------------------------------------- |
| id                  | bigint    | Primary key                                    |
| digest_run_id       | bigint    | FK to digest_runs                              |
| destination_id      | bigint    | FK to destinations                             |
| status              | string    | `pending`, `sent`, `failed`                    |
| sent_at             | datetime? | When successfully sent                         |
| provider_message_id | string?   | Slack ts, Discord message ID, email message-id |
| error               | text?     | Error message if failed                        |

### DigestItemSummary

An AI-generated summary for a source item within a specific digest context.

| Column           | Type    | Description                                              |
| ---------------- | ------- | -------------------------------------------------------- |
| id               | bigint  | Primary key                                              |
| digest_id        | bigint  | FK to digests                                            |
| source_item_id   | bigint  | FK to source_items                                       |
| summary_markdown | text?   | Human-readable summary                                   |
| summary_json     | json?   | Structured summary (key_changes, breaking_changes, etc.) |
| provider         | string? | AI provider (openai, anthropic)                          |
| model            | string? | Model used (gpt-4, claude-3-sonnet)                      |
| status           | string  | `pending`, `completed`, `failed`                         |
| error            | text?   | Error message if failed                                  |

**Unique constraint**: `(digest_id, source_item_id)`

## Pivot Tables

### digest_source

Links digests to the sources they track.

| Column    | Type   |
| --------- | ------ |
| digest_id | bigint |
| source_id | bigint |

### digest_destination

Links digests to their delivery destinations.

| Column         | Type   |
| -------------- | ------ |
| digest_id      | bigint |
| destination_id | bigint |

### digest_run_source_item

Records which source items were included in a specific digest run.

| Column         | Type   | Description         |
| -------------- | ------ | ------------------- |
| digest_run_id  | bigint |                     |
| source_item_id | bigint |                     |
| position       | int?   | Order in the digest |

## Future Considerations

- **Web changelog monitoring**: Would add a new source type with diff-based detection.
- **Per-destination formatting**: Could add `rendered_content` per destination type instead of single Markdown.
- **Team/organization support**: Would require scoping sources and potentially adding team-level permissions.
