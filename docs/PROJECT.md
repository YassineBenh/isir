# ISIR (It's Short, I Read) — Service Description

## Overview

ISIR is an open-source service (with an optional hosted, paid offering) that helps people stay on top of software releases without having to monitor dozens of repositories, changelogs, and notification streams. It connects to GitHub (initially GitHub-only) and produces concise, readable digests of new releases across a set of repositories the user chooses. Digests can be scheduled (daily or weekly) and delivered to the user through their preferred notification channels—Slack, Discord, or email—so release information shows up where they already work.

At its core, ISIR turns raw release data into a short, structured summary designed for fast scanning. Instead of forwarding full release notes or sending a separate alert for each repository, ISIR groups updates into a single digest per schedule, reducing noise while preserving the important details. Each digest can represent a different context (for example: "Work dependencies", "Personal projects", "Security-critical libraries", "Frontend stack"), and users can create and manage multiple digests with independent repository selections and schedules.

## What ISIR Does

### Monitors GitHub releases for selected repositories

- Users connect ISIR to GitHub and select repositories to follow
- ISIR periodically checks for new releases and gathers relevant release metadata (version, title, publish date, links, and release notes)

### Builds one digest from many updates

- For each digest schedule (daily/weekly), ISIR compiles all releases that happened since the previous run
- Updates are grouped per repository and ordered for readability, so users can quickly understand "what changed where" without clicking through multiple pages

### Summarizes release notes into short, readable content (AI-assisted)

ISIR can use an AI model to convert verbose release notes into an "executive summary" format:

- Key changes
- Breaking changes (when present/mentioned)
- Highlights that affect most users
- Actionable items (upgrade notes, migrations, deprecations), when available in the source text

In self-hosted mode, AI is powered by the user's own provider key (bring-your-own-key), keeping the open-source version functional without requiring a proprietary hosted AI component.

### Delivers digests via the user's chosen channels

- **Slack**: sends the digest to a channel or direct message (depending on configuration)
- **Discord**: posts the digest to a channel via webhook/bot integration
- **Email**: sends a formatted email digest
- The same digest can be sent to one or multiple channels based on user preferences

### Designed to minimize noise while increasing awareness

ISIR is specifically intended to replace fragmented release tracking (watching repos, GitHub notifications, RSS feeds, random Slack pings) with a single, predictable cadence. Users get fewer messages that are more useful: short, summarized, and contextual.

## The Problem ISIR Solves

Modern development teams and solo builders rely on a large number of open-source dependencies and upstream tools. Releases happen constantly, but most release notes are too long to read in real time and too important to ignore entirely. The result is a common failure mode:

- People mute notifications because they're too noisy
- Important changes get missed (breaking changes, security fixes, deprecations)
- Updates are delayed because nobody had time to triage release notes

ISIR addresses this by providing a digest that is:

- **Scheduled** (daily/weekly)
- **Curated by selection** (only the repos you care about)
- **Summarized** (short enough to read)
- **Delivered where you already are** (Slack/Discord/email)

## Typical User Experience

A user signs in, connects GitHub, and creates a digest called "Backend Stack". They add Laravel, Postgres tooling, queue drivers, and a few internal dependencies. They schedule it weekly. Separately, they create a "Security & Infra" digest for key libraries and infrastructure components, scheduled daily. ISIR runs automatically and posts concise summaries to a Slack channel and sends a backup email—each message containing only what changed since the last digest, with links to the official releases when deeper reading is needed.

## Positioning

ISIR sits between "raw release notifications" and "manual changelog reading":

- It is **not** a generic feed reader
- It is **not** another notification spam source
- It **is** a focused release-digest system optimized for software updates

Open source provides transparency, self-hosting, and bring-your-own-AI-key flexibility. The hosted paid version provides convenience (no infrastructure, simpler setup, managed delivery, and potentially premium features later), while keeping the core value proposition identical: "keep up with releases in a format that is actually readable."

## Technical Direction (High Level)

ISIR is built with **Laravel + Inertia + React**, aiming for a modern web-app UX with a straightforward deployment model for self-hosting, plus a scalable hosted option. The system revolves around:

- Repository tracking (GitHub)
- Digest configuration (multiple digests per user)
- Scheduled compilation (daily/weekly)
- Optional AI summarization (BYO provider key for self-hosted)
- Multi-channel notification delivery (Slack, Discord, email)

---

**Next Steps**: Transform this into a full MVP PRD with personas, user stories, digest schema, scheduling rules, summarization format, notification templates, admin/hosting considerations, and non-goals.
