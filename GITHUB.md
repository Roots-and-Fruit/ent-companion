# GitHub release — Ent Companion

Canonical repo: [github.com/Roots-and-Fruit/ent-companion](https://github.com/Roots-and-Fruit/ent-companion)

## Repo layout

Plugin files live at the **repository root**. Git Updater expects:

```
ent-companion/
├── ent-companion.php   # main plugin file (GitHub Plugin URI headers)
├── readme.txt
├── includes/
└── templates/
```

## Git Updater (production)

Headers in `ent-companion.php`:

```
GitHub Plugin URI: https://github.com/Roots-and-Fruit/ent-companion
Primary Branch: main
Release Asset: true
```

After install, register in **Git Updater → Additions** if auto-discovery does not pick it up:

| Field | Value |
|-------|--------|
| Repository Type | `github_plugin` |
| Repository Slug | `ent-companion/ent-companion.php` |
| Repository URI | `https://github.com/Roots-and-Fruit/ent-companion` |
| Primary Branch | `main` |
| Release Asset | checked |

Release zip asset name: `ent-companion-<version>.zip` (e.g. `ent-companion-2.0.0.zip`) containing folder `ent-companion/`.

## Tagging a release

1. Bump `Version` and `ENT_COMPANION_VERSION` in `ent-companion.php` and `Stable tag` in `readme.txt`.
2. Commit and push to `main`.
3. Build zip with **forward-slash paths** (required on Linux):

```powershell
python bin/build-release-zip.py
```

Do **not** use PowerShell `Compress-Archive` — it can embed backslashes and break Git Updater on the server.

4. Create GitHub release tag `v2.0.0` with attached `ent-companion-<version>.zip`.

Verify before upload: zip must contain `ent-companion/ent-companion.php` (not `ent-companion\...`).

## Ent dev workspace

Open `ent-dev.code-workspace` in the Ent kit repo — includes `ent-kit`, `Ent-workspace-test`, and this plugin checkout.
