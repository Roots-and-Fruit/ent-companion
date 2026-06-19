#!/usr/bin/env python3
"""Build a Git Updater–compatible release zip with forward-slash paths only."""

from __future__ import annotations

import argparse
import re
import sys
import zipfile
from pathlib import Path

PLUGIN_SLUG = "rootsandfruit-abilities"
EXCLUDE_DIRS = {".git", ".cursor", "__pycache__", ".github"}
EXCLUDE_FILES = {".DS_Store", "Thumbs.db", ".gitignore", ".cursorignore", ".cursorindexingignore"}
EXCLUDE_DOC_PREFIXES = ("RELEASE-v", "AGENTS.md", "GITHUB.md")


def read_version(plugin_root: Path) -> str:
    main_file = plugin_root / f"{PLUGIN_SLUG}.php"
    text = main_file.read_text(encoding="utf-8")
    match = re.search(r"^\s*\*\s*Version:\s*([0-9.]+)\s*$", text, re.MULTILINE)
    if not match:
        raise SystemExit(f"Could not read Version from {main_file}")
    return match.group(1)


def should_include(path: Path, repo_root: Path) -> bool:
    rel = path.relative_to(repo_root)
    if any(part in EXCLUDE_DIRS for part in rel.parts):
        return False
    if path.name in EXCLUDE_FILES:
        return False
    if path.suffix == ".zip" and path.name.startswith("abilities-"):
        return False
    if path.name in EXCLUDE_DOC_PREFIXES or path.name.startswith("RELEASE-v"):
        return False
    return True


def build_zip(repo_root: Path, output: Path | None = None) -> Path:
    version = read_version(repo_root)
    out_path = output or (repo_root / f"abilities-{version}.zip")

    with zipfile.ZipFile(out_path, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        for file_path in sorted(repo_root.rglob("*")):
            if not file_path.is_file() or not should_include(file_path, repo_root):
                continue
            rel_parts = file_path.relative_to(repo_root).parts
            arcname = PLUGIN_SLUG + "/" + "/".join(rel_parts)
            zf.write(file_path, arcname)

    return out_path


def main() -> int:
    parser = argparse.ArgumentParser(description="Build abilities release zip for Git Updater.")
    parser.add_argument(
        "--root",
        type=Path,
        default=Path(__file__).resolve().parents[1],
        help="Plugin repository root",
    )
    parser.add_argument("--output", type=Path, default=None, help="Output zip path")
    args = parser.parse_args()

    out = build_zip(args.root, args.output)
    print(f"Wrote {out}")

    with zipfile.ZipFile(out, "r") as zf:
        names = zf.namelist()[:5]
        print("Sample entries:")
        for name in names:
            print(f"  {name}")
        if any("\\" in n for n in zf.namelist()):
            print("ERROR: zip contains backslashes in entry names", file=sys.stderr)
            return 1

    main_php = f"{PLUGIN_SLUG}/{PLUGIN_SLUG}.php"
    with zipfile.ZipFile(out, "r") as zf:
        if main_php not in zf.namelist():
            print(f"ERROR: missing required entry {main_php}", file=sys.stderr)
            return 1

    print(f"OK: {main_php} present; paths use forward slashes.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
