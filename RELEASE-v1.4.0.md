# Roots & Fruit Abilities v1.4.0

WordPress plugin that registers a curated, least-privilege **agent ability surface** for Cursor and other MCP clients via the WordPress **Abilities API** and **MCP Adapter**. One HTTP MCP endpoint on your site; guardrails enforced in PHP, not prompts.

## Purpose

- Give AI agents **safe, structured tools** for content workflows on rootsandfruit.com (and other R&F WordPress sites)
- **Server-side permissions** aligned to WordPress capabilities (`edit_posts`, `unfiltered_html`, `update_plugins`, etc.)
- **No delete abilities** — agents can create, edit, and publish, but not trash or hard-delete via MCP
- **Conditional modules** — abilities register only when their dependency plugin is active

## Core abilities (always)

| Ability | Purpose |
|---------|---------|
| `rootsandfruit/ping` | Health check + plugin version |
| `rootsandfruit/list-posts` | List editable posts |
| `rootsandfruit/get-post` | Post metadata summary |
| `rootsandfruit/create-draft` | Create draft post |
| `rootsandfruit/update-post` | Update title/excerpt (not block body on block posts) |
| `rootsandfruit/publish-post` | Publish existing post |

## Preview module (Public Post Preview active)

| Ability | Purpose |
|---------|---------|
| `rootsandfruit/enable-public-preview` | Enable shareable draft preview link |
| `rootsandfruit/get-public-preview-url` | Read preview URL |

## Block editor bridge (Block MCP / gk-block-mcp active) — **new in 1.4.0**

Bridges GravityKit Block MCP **in-process** so Cursor uses **one MCP server**, not a second `@gravitykit/block-mcp` process.

| Ability | Purpose |
|---------|---------|
| `rootsandfruit/blocks-get-page` | Read structured block tree |
| `rootsandfruit/blocks-update` | Update one block by ref or flat_index |
| `rootsandfruit/blocks-mutate` | Structural ops (insert-child, replace-block, move, …) |
| `rootsandfruit/blocks-insert` | Insert blocks at a position |
| `rootsandfruit/blocks-create-page` | Create post/page with blocks array |
| `rootsandfruit/blocks-list-patterns` | List block patterns |

## Snippets module (FluentSnippets active)

Seven abilities for snippet CRUD, activate/deactivate, and runtime verify — requires `unfiltered_html` (admin MCP user).

## Plugin updates module (WP Rollback active)

| Ability | Purpose |
|---------|---------|
| `rootsandfruit/plugin-update-safe` | Composite: baseline → wordpress.org update → homepage smoke → auto-rollback on failure |

## Requirements

- WordPress **6.9+** (Abilities API)
- **MCP Adapter** plugin (active)
- Optional: Public Post Preview, **gk-block-mcp**, FluentSnippets, WP Rollback

## Git Updater

Plugin headers include `GitHub Plugin URI`, `Primary Branch: main`, and `Release Asset: true`. Install release zip or use Git Updater Additions with slug `rootsandfruit-abilities/rootsandfruit-abilities.php`.

## v1.4.0 changes

- Block MCP bridge module (six `rootsandfruit/blocks-*` abilities)
- `ping` reports `block_mcp_active`
- `update-post` rejects block editor body HTML; directs agents to `blocks-*`
- GitHub Plugin URI headers for automated updates
