=== Ent Companion ===
Contributors: rootsandfruit
Tags: abilities, mcp, ai, agents, ent
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Ent Companion — abilities engine for WordPress MCP Adapter (snippets, preview, safe plugin updates).

== Description ==

Registers a least-privilege `ent-companion/*` ability surface for Cursor agents via MCP Adapter. Site-specific abilities are added through FluentSnippets or Code Snippets — not bundled in the plugin.

== Ability catalog ==

* ent-companion/ping
* ent-companion/list-plugins

When Public Post Preview is active:

* ent-companion/enable-public-preview
* ent-companion/get-public-preview-url

When FluentSnippets and/or Code Snippets is active:

* ent-companion/snippets-list
* ent-companion/snippets-get
* ent-companion/snippets-create
* ent-companion/snippets-update
* ent-companion/snippets-activate
* ent-companion/snippets-deactivate
* ent-companion/snippets-verify

When WP Rollback is active:

* ent-companion/plugin-update-safe

== Custom abilities via snippets ==

1. Create a snippet with `ent-companion/snippets-create` (tagged `ec-ability`, saved as draft).
2. Use `templates/ec-ability-snippet.example.php` and `ec_register_agent_abilities()` inside `wp_abilities_api_init`.
3. Activate with `ent-companion/snippets-activate` after review.
4. Call `ent-companion/snippets-verify` after update/activate.

== Dependencies ==

* WordPress 6.9+ (Abilities API)
* MCP Adapter plugin
* FluentSnippets and/or Code Snippets (snippet workflow)
* Public Post Preview (preview abilities; optional)
* WP Rollback (plugin-update-safe; optional)

== Installation ==

1. Copy the plugin folder to `wp-content/plugins/ent-companion/`
2. Activate via Plugins screen
3. Confirm `ent-companion/ping` appears in MCP discover

== Changelog ==

= 2.2.0 =
* Fix plugin-update-safe: resolve package URLs from update_plugins transient (same as wp-admin) instead of the full WordPress.org versions map.
* Fallback to plugins_api downloadlink, then downloads.wordpress.org URL for explicit target_version.

= 2.1.0 =
* Add `ent-companion/list-plugins` — installed plugin inventory with optional status/search filters and update metadata.

= 2.0.0 =
* Fork as Ent Companion (`ent-companion/*` namespace).
* Remove bundled content CRUD and Block MCP bridge.
* Add Code Snippets provider alongside FluentSnippets.
* GitHub repo: Roots-and-Fruit/ent-companion (Git Updater).
