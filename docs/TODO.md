# ISIR MVP TODO

## Backend

### Core Services

- [ ] **GitHub Release Fetcher** — Service to fetch releases from GitHub API for a given `owner/repo`
    - Parse GitHub repo URL → extract owner/repo
    - Fetch releases via GitHub API (public, no auth required for public repos)
    - Map API response to `SourceItem` model
    - Handle rate limiting
    - Incremental sync using etag/last-modified

- [ ] **Digest Compiler** — Service to compile a digest from source items
    - Query source items within time window (period_start_at → period_end_at)
    - Group items by source
    - Render to Markdown
    - Create `DigestRun` record

- [ ] **AI Summarizer** — Service to generate summaries for source items
    - Support OpenAI and Anthropic providers
    - Accept custom prompt from digest settings
    - Create `DigestItemSummary` records
    - Handle API errors gracefully

- [ ] **Delivery Service** — Send compiled digests to destinations
    - Slack webhook delivery
    - Discord webhook delivery
    - Email delivery (via Laravel Mail)
    - Create `DeliveryAttempt` records with status

### Scheduled Jobs

- [ ] **Source Sync Job** — Periodically fetch new releases from all enabled sources
    - Run every 15-30 minutes
    - Only fetch sources that have at least one enabled digest
    - Update `last_fetched_at` and `fetch_state`

- [ ] **Digest Scheduler Job** — Check which digests are due and dispatch compilation
    - Run every minute
    - Check `frequency`, `send_time`, `send_day_of_week`, `timezone`
    - Dispatch `CompileDigestJob` for due digests

- [ ] **CompileDigestJob** — Compile and deliver a single digest
    - Create `DigestRun` with status=running
    - Gather source items for period
    - Generate AI summaries (if enabled)
    - Render Markdown content
    - Dispatch delivery to each destination
    - Update `DigestRun` status

### Controllers & Routes

- [x] **DigestController** — CRUD for digests
    - `index` — list user's digests
    - `create` — show create form
    - `store` — create digest + attach sources/destinations
    - `edit` — show edit form
    - `update` — update digest
    - `destroy` — delete digest
    - `toggle` — enable/disable digest

- [x] **DestinationController** — CRUD for destinations
    - `index` — list user's destinations
    - `create` — show create form
    - `store` — create destination
    - `edit` — show edit form
    - `update` — update destination
    - `destroy` — delete destination
    - `toggle` — enable/disable destination

- [ ] **SourceController** — Minimal, mostly internal
    - `store` — find-or-create source from GitHub URL (called when adding repo to digest)

- [ ] **DashboardController** — Dashboard data
    - Recent digest runs with status
    - Quick stats (releases tracked, active digests)

- [ ] **Settings Controllers**
    - `AiProviderController` — save/update AI provider settings (stored in user settings or separate table)

### Form Requests

- [x] `StoreDigestRequest` — validate digest creation
- [x] `UpdateDigestRequest` — validate digest update
- [x] `StoreDestinationRequest` — validate destination creation (type-specific rules, max 10 limit)
- [x] `UpdateDestinationRequest` — validate destination update
- [ ] `StoreAiProviderRequest` — validate AI provider settings

### Validation Rules

- [x] `SlackWebhookUrl` — validates Slack webhook URL format
- [x] `DiscordWebhookUrl` — validates Discord webhook URL format
- [x] `GitHubRepoUrl` — validates GitHub repo URL or owner/repo format

### Actions

- [x] `CreateDigest` — create digest with sources and destinations
- [x] `UpdateDigest` — update digest, sync sources/destinations
- [x] `DeleteDigest` — delete digest (detaches sources/destinations first)
- [x] `CreateDestination` — create a new destination
- [x] `UpdateDestination` — update a destination
- [x] `DeleteDestination` — delete a destination (detaches from digests first)
- [x] `FindOrCreateSource` — find existing source or create from GitHub URL
- [ ] `FetchGitHubReleases` — fetch releases for a source
- [ ] `CompileDigest` — compile digest run
- [ ] `SummarizeSourceItem` — generate AI summary for a source item
- [ ] `DeliverToSlack` — send digest to Slack webhook
- [ ] `DeliverToDiscord` — send digest to Discord webhook
- [ ] `DeliverToEmail` — send digest via email

### Policies

- [x] `DigestPolicy` — authorization for digest CRUD operations
- [x] `DestinationPolicy` — authorization for destination CRUD operations

---

## Frontend

### Pages

- [ ] **Dashboard** (`pages/dashboard.tsx`)
    - Recent digest runs list
    - Quick stats
    - Empty state → prompt to create digest

- [x] **Digests Index** (`pages/digests/index.tsx`)
    - Card grid of user's digests
    - Enable/disable toggle per card
    - "New Digest" button
    - Empty state

- [x] **Digest Create** (`pages/digests/create.tsx`)
    - Single-page form with sections
    - Name + schedule inputs
    - Repository URL paste + chip list
    - Destination selector (3 selects by type)
    - AI settings toggle

- [x] **Digest Edit** (`pages/digests/edit.tsx`)
    - Same form as create, pre-filled
    - Delete button

- [x] **Destinations Index** (`pages/destinations/index.tsx`)
    - Card/list of destinations
    - Enable/disable toggle
    - "Add Destination" button
    - Empty state

- [x] **Destination Create** (`pages/destinations/create.tsx`)
    - Type selector (Slack/Discord/Email)
    - Type-specific form fields

- [x] **Destination Edit** (`pages/destinations/edit.tsx`)
    - Same form as create, pre-filled
    - Delete button

- [ ] **Settings: AI Provider** (`pages/settings/ai-provider.tsx`)
    - Provider dropdown (OpenAI/Anthropic)
    - API key input
    - Model selector
    - Test connection button

### Components

- [x] `DigestCard` — card showing digest summary (name, schedule, repos count, destinations count, last run)
- [x] `DestinationCard` — card showing destination (name, type icon, target)
- [x] `RepoChip` — chip showing `owner/repo` with remove button
- [ ] `DestinationChip` — chip showing destination with remove button
- [x] `ScheduleSelector` — frequency + time + day (for weekly) + timezone
- [x] `DestinationForm` — shared form for creating/editing destinations
- [x] `DigestForm` — shared form for creating/editing digests
- [ ] `DestinationModal` — modal wrapper for `DestinationForm` (used in digest page)
- [x] `Empty` — using shadcn/ui empty component
- [ ] `StatusBadge` — badge for digest run status (pending/running/completed/failed)

### Navigation

- [x] Update `app-sidebar.tsx` — add Destinations nav item
- [x] Update `app-sidebar.tsx` — add Digests nav item
- [ ] Add settings subnav for AI Provider

### Types

- [x] `Destination` — TypeScript type for destination model
- [x] `DestinationType` — union type for destination types
- [x] `DestinationConfig` — TypeScript type for destination config
- [x] `Digest` — TypeScript type for digest model
- [x] `DigestFrequency` — union type for digest frequencies
- [x] `Source` — TypeScript type for source model
- [x] `DestinationsByType` — TypeScript type for destinations grouped by type

---

## Testing

### Feature Tests

- [x] `DigestControllerTest` — CRUD operations, attach/detach sources/destinations (28 tests)
- [x] `DestinationControllerTest` — CRUD operations (27 tests)
- [ ] `DashboardControllerTest` — dashboard data

### Unit Tests

- [ ] `FetchGitHubReleasesTest` — mock GitHub API, test parsing
- [ ] `CompileDigestTest` — test digest compilation logic
- [ ] `SummarizeSourceItemTest` — mock AI API, test summary generation
- [ ] `DeliverToSlackTest` — mock webhook, test delivery
- [ ] `DeliverToDiscordTest` — mock webhook, test delivery
- [ ] `DeliverToEmailTest` — test email sending

### Browser Tests (Pest 4)

- [ ] Digest creation flow — paste URL, add destination, save
- [ ] Destination creation flow
- [ ] Dashboard displays recent runs

---

## Configuration

- [x] Add `config/isir.php` — app-specific config
    - [x] Resource limits (digests/destinations per user)
    - [x] Curated timezone list
    - [ ] Default AI provider/model
    - [ ] GitHub API rate limit handling
    - [ ] Digest compilation settings

- [ ] Add environment variables
    - [x] `ISIR_MAX_DESTINATIONS_PER_USER`
    - [x] `ISIR_MAX_DIGESTS_PER_USER`
    - [ ] `ISIR_DEFAULT_AI_PROVIDER`
    - [ ] `ISIR_DEFAULT_AI_MODEL`

---

## Database

- [x] Migrations created
- [x] Models created
- [x] Factories created
- [ ] Seeders for demo data (optional)

---

## Future (Post-MVP)

- [ ] Digest preview feature
- [ ] Digest run history page
- [ ] GitHub repo search (alternative to paste)
- [ ] RSS feed source type
- [ ] YouTube channel source type
- [ ] Team/organization support
- [ ] Notification preferences
