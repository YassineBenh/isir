# ISIR Interface Design

## Design Philosophy

ISIR's value is **reducing noise** — the interface embodies this principle.

- **Minimal**: Only show what's necessary
- **Focused**: One task at a time, clear hierarchy
- **Fast**: Create a digest in <60 seconds

---

## Information Architecture

```
Sidebar Navigation
├── Dashboard (overview, recent runs, quick stats)
├── Digests (list + CRUD)
├── Destinations (Slack/Discord/Email management)
└── Settings
    ├── Profile
    ├── Password
    └── AI Provider (API keys for self-hosted)
```

---

## Screens

### 1. Dashboard

The "at a glance" home screen.

**Content:**

- List of recent digest runs with status (sent/failed/pending)
- Quick stats: "12 releases tracked this week across 3 digests"
- Shortcuts to create digest or add destination

**Empty State:**

- Guide user to create first digest
- Example use cases: "Track your Laravel dependencies", "Monitor security-critical libs"

---

### 2. Digests Index

Card grid or list of all user digests.

**Each digest card shows:**

- Name
- Schedule badge (Daily/Weekly)
- Number of repos tracked
- Number of destinations
- Last run status + timestamp
- Enable/disable toggle (inline)

**Actions:**

- "New Digest" button (prominent)
- Click card → edit digest

---

### 3. Digest Create/Edit

**Single-page form with sections** (not a wizard).

Progressive disclosure: start simple, reveal complexity on demand.

```
┌─────────────────────────────────────────────────────┐
│ Digest Name: [________________________]             │
│ Schedule:    [Daily ▾]  at [9:00 AM ▾] [Timezone ▾] │
├─────────────────────────────────────────────────────┤
│ REPOSITORIES                                        │
│                                                     │
│ [Paste GitHub repo URL...                    ] [Add]│
│                                                     │
│ ┌─────────────────────────────────────────────────┐ │
│ │ laravel/framework                          [×] │ │
│ │ tailwindcss/tailwindcss                    [×] │ │
│ │ inertiajs/inertia-laravel                  [×] │ │
│ └─────────────────────────────────────────────────┘ │
├─────────────────────────────────────────────────────┤
│ DESTINATIONS                                        │
│                                                     │
│ ┌─────────────────────────────────────────────────┐ │
│ │ #releases (Slack)                          [×] │ │
│ └─────────────────────────────────────────────────┘ │
│                                                     │
│ [+ Add Destination]  ← opens modal to create/select │
├─────────────────────────────────────────────────────┤
│ AI SUMMARY (optional)                         [▾]   │
│                                                     │
│ [✓] Summarize release notes with AI                 │
│                                                     │
│ Custom prompt (optional):                           │
│ [_______________________________________________]   │
│                                                     │
└─────────────────────────────────────────────────────┘
│                              [Cancel]  [Save Digest]│
└─────────────────────────────────────────────────────┘
```

**Repository Selection:**

- Paste GitHub URL only (e.g., `https://github.com/laravel/framework`)
- Parse owner/repo from URL
- Validate URL format client-side
- Check repo exists on submit (or async)
- Show repo as chip/tag after adding

**Destination Selection:**

- Shows existing destinations as selectable chips
- "+ Add Destination" opens modal with destination creation form
- Modal uses same form component as Destinations page
- After creating in modal, destination is auto-selected for this digest

**AI Settings:**

- Collapsed by default (accordion)
- Toggle to enable/disable AI summarization
- Optional custom prompt textarea
- Provider configured globally in Settings (not per-digest)

---

### 4. Destinations Index

List/card view of all configured destinations.

**Destination Types:**

- Slack (webhook URL)
- Discord (webhook URL)
- Email (email address)

**Each destination card shows:**

- Name/label
- Type icon (Slack/Discord/Email)
- Target (channel name, email address)
- Enable/disable toggle
- Edit/delete actions

**Actions:**

- "Add Destination" button
- Click card → edit

---

### 5. Destination Create/Edit

**Form fields by type:**

**Slack:**

```
Name:        [#backend-releases____________]
Webhook URL: [https://hooks.slack.com/...___]
```

**Discord:**

```
Name:        [#releases___________________]
Webhook URL: [https://discord.com/api/webhooks/...]
```

**Email:**

```
Name:        [Personal Email______________]
Email:       [user@example.com____________]
```

**Shared form component** used both:

- On Destinations page (full page)
- In modal from Digest create/edit page

---

### 6. Settings Pages

#### AI Provider Settings

```
┌─────────────────────────────────────────────────────┐
│ AI PROVIDER                                         │
│                                                     │
│ Provider: [OpenAI ▾]                                │
│                                                     │
│ API Key:  [sk-...________________________] [Test]  │
│                                                     │
│ Model:    [gpt-4o-mini ▾]                           │
└─────────────────────────────────────────────────────┘
```

Supported providers:

- OpenAI
- Anthropic

---

## Empty States

### No Digests

```
┌─────────────────────────────────────────────────────┐
│                                                     │
│              📋 No digests yet                      │
│                                                     │
│  Create your first digest to start tracking         │
│  releases from your favorite repositories.          │
│                                                     │
│  Ideas:                                             │
│  • "Backend Stack" — Laravel, Postgres, Redis       │
│  • "Frontend Tools" — React, Vite, Tailwind         │
│  • "Security Critical" — Auth libs, infra tools     │
│                                                     │
│              [Create Your First Digest]             │
│                                                     │
└─────────────────────────────────────────────────────┘
```

### No Destinations

```
┌─────────────────────────────────────────────────────┐
│                                                     │
│              📬 No destinations yet                 │
│                                                     │
│  Add a destination to receive your digests.         │
│  You can send to Slack, Discord, or email.          │
│                                                     │
│              [Add Your First Destination]           │
│                                                     │
└─────────────────────────────────────────────────────┘
```

---

## Component Reuse

| Component          | Used In                                 |
| ------------------ | --------------------------------------- |
| `DestinationForm`  | Destinations page, Modal in Digest page |
| `DigestCard`       | Digests index, Dashboard (recent)       |
| `DestinationChip`  | Digest form, Destinations index         |
| `RepoChip`         | Digest form                             |
| `ScheduleSelector` | Digest form                             |
| `EmptyState`       | All list views                          |

---

## Interaction Patterns

### Adding a Repository

1. User pastes GitHub URL into input
2. Client validates URL format
3. On "Add" click, URL is parsed → `owner/repo` extracted
4. Repo chip appears in list
5. On form submit, backend validates repo exists via GitHub API

### Adding a Destination from Digest Page

1. User clicks "+ Add Destination"
2. Modal opens with `DestinationForm`
3. User fills form, clicks "Create"
4. Modal closes
5. New destination appears selected in digest form

### Toggling Digest Enable/Disable

- Inline toggle on digest card
- Immediate optimistic update
- Toast confirmation: "Digest paused" / "Digest enabled"

---

## Mobile Considerations

- Primary use: **reading digests** in Slack/Discord/email (not in app)
- Management UI should work on mobile but is secondary
- Sidebar collapses to hamburger menu (already supported)
- Forms stack vertically on mobile
- Cards become full-width list items

---

## Future Considerations (Not MVP)

- Digest preview before going live
- Digest run history with rendered output
- Repository search (GitHub API) as alternative to paste
- RSS feed support (different source type)
- YouTube channel support
- Team/organization features
- Notification preferences (mute, snooze)
