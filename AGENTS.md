# Roots & Fruit — abilities plugin

WordPress plugin exposing curated **`rootsandfruit/*`** abilities for Cursor MCP ops on [rootsandfruit.com](https://rootsandfruit.com).

**Sibling repo:** `../agent/` (MCP scripts, `.env`, ops docs). Open `../rootsandfruit.code-workspace` for both folders.

## Dev workflow

- Plugin code: this repo. Match existing PHP style.
- Abilities work: load **`rf-abilities-dev`** skill; API reference: **`wp-abilities-api`** skill.
- After registration changes: from `../agent/` run `test-wordpress-mcp-http.ps1` and `audit-mcp-abilities.ps1` (see `../agent/README.md`).

## Release

Git push does **not** update production. Tag + zip via GitHub release; Git Updater on server. See [`GITHUB.md`](GITHUB.md).

## Boundaries

**Ask first:** Production plugin deploy, GitHub releases, Git Updater config.

**Never:** Delete content via MCP (no delete abilities registered); commit secrets.

## Deep docs

| Topic | Path |
|-------|------|
| Agent ops index | `../agent/AGENTS.md` |
| MCP routing | `../agent/agent_docs/mcp-routing.md` |
| Plugin dev skill | `.cursor/skills/rf-abilities-dev/` |
