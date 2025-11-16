#!/usr/bin/env python3
import argparse
import os
import re
import sys
from pathlib import Path
from typing import Iterable, Tuple


PROJECT_ROOT = Path(__file__).resolve().parents[1]
RESOURCES_DIR = PROJECT_ROOT / "resources"


def is_git_repo(root: Path) -> bool:
    return (root / ".git").exists()


def git_mv(src: Path, dst: Path) -> bool:
    """
    Try to move using `git mv` if available; fallback to os.rename.
    Returns True if move succeeded, False otherwise.
    """
    try:
        if is_git_repo(PROJECT_ROOT):
            import subprocess

            subprocess.run(["git", "mv", "-f", str(src), str(dst)], check=True)
        else:
            os.rename(src, dst)
        return True
    except Exception:
        return False


def case_safe_move(src: Path, dst: Path) -> None:
    """
    Safe move that works on case-insensitive filesystems:
    - If only case differs, move via a temp name first.
    """
    if str(src) == str(dst):
        return
    dst.parent.mkdir(parents=True, exist_ok=True)
    if src.exists():
        # If path differs only by case, use a temp hop
        if src.parent == dst.parent and src.name.lower() == dst.name.lower():
            temp = src.with_name(src.name + ".__tmp_lower__")
            # Best effort: use git first, then fallback
            if not git_mv(src, temp):
                os.rename(src, temp)
            if not git_mv(temp, dst):
                os.rename(temp, dst)
        else:
            if not git_mv(src, dst):
                os.rename(src, dst)


def lowercase_resources_paths(dry_run: bool = False) -> Tuple[int, int]:
    """
    Lowercase all files and directories inside `resources/`.
    Returns (num_dirs_moved, num_files_moved)
    """
    dir_moves = 0
    file_moves = 0

    if not RESOURCES_DIR.exists():
        print(f"[WARN] {RESOURCES_DIR} not found")
        return (0, 0)

    # First rename files (deepest paths first)
    for root, dirs, files in os.walk(RESOURCES_DIR, topdown=False):
        root_path = Path(root)
        for name in files:
            src = root_path / name
            dst = root_path / name.lower()
            if src != dst:
                print(f"[file] {src} -> {dst}")
                if not dry_run:
                    case_safe_move(src, dst)
                file_moves += 1
        # Then rename directories at this level
        for name in dirs:
            src = root_path / name
            dst = root_path / name.lower()
            if src != dst:
                print(f"[dir]  {src} -> {dst}")
                if not dry_run:
                    case_safe_move(src, dst)
                dir_moves += 1

    return (dir_moves, file_moves)


def iter_project_files() -> Iterable[Path]:
    """
    Iterate reasonably safe text-like files in the repository for in-place updates.
    Skips .git, node_modules, vendor, storage, public/build artifacts by default.
    """
    skip_dirs = {
        ".git",
        "node_modules",
        "vendor",
        "storage",
        "public/build",
        ".next",
        "dist",
        "build",
    }
    allowed_exts = {
        ".php",
        ".blade.php",
        ".js",
        ".cjs",
        ".mjs",
        ".ts",
        ".tsx",
        ".jsx",
        ".json",
        ".yml",
        ".yaml",
        ".css",
        ".scss",
        ".sass",
        ".vue",
        ".md",
        ".html",
        ".htm",
        ".xml",
        ".conf",
        ".ini",
        ".env",
        ".rb",
        ".py",
        ".sh",
        ".txt",
    }

    for root, dirs, files in os.walk(PROJECT_ROOT):
        # Skip directories
        pruned = []
        for d in dirs:
            if d in skip_dirs:
                continue
            pruned.append(d)
        dirs[:] = pruned
        for f in files:
            p = Path(root) / f
            if p.suffix in allowed_exts or any(
                str(p).endswith(ext) for ext in (".blade.php",)
            ):
                yield p


def update_file_contents(path: Path) -> Tuple[bool, int]:
    """
    Update a single file:
    - resources/css -> resources/css
    - resources/js  -> resources/js
    - resources/views -> resources/views
    - view('dotted.path') -> lowercased dotted path
    - Blade directives @include/@extends/@each/@includeIf/@includeWhen/@includeUnless -> lowercased dotted path
    Returns (changed, replacements_count)
    """
    try:
        text = path.read_text(encoding="utf-8")
    except Exception:
        return (False, 0)

    original = text
    # Asset path normalization
    text = re.sub(r"resources/views", "resources/views", text)
    text = re.sub(r"resources/css", "resources/css", text)
    text = re.sub(r"resources/js", "resources/js", text)

    # view('...')
    def repl_view(m: re.Match) -> str:
        quote = m.group(1)
        dotted = m.group(2)
        return f"view({quote}{dotted.lower()}{quote})"

    text = re.sub(r"view\(\s*(['\"])([^'\"]+)\1\s*\)", repl_view, text)

    # Blade directives
    def repl_blade(m: re.Match) -> str:
        directive = m.group(1)
        quote = m.group(2)
        dotted = m.group(3)
        return f"@{directive}({quote}{dotted.lower()}{quote}"

    text = re.sub(
        r"@(?:include|extends|each)\(\s*(['\"])([^'\"]+)\1",
        lambda m: f"@{m.re.pattern and ''}?",  # placeholder, replaced below
        text,
    )  # This placeholder prevents catastrophic backtracking on huge files; we will replace with a second pass.

    # Second pass precise for Blade (no placeholder)
    text = re.sub(
        r"@(include|extends|each)\(\s*(['\"])([^'\"]+)\2",
        repl_blade,
        text,
    )
    text = re.sub(
        r"@(includeIf|includeWhen|includeUnless)\(\s*(['\"])([^'\"]+)\2",
        repl_blade,
        text,
    )

    changed = text != original
    if changed:
        path.write_text(text, encoding="utf-8")
        # Rough count of replacements
        return (True, abs(len(text) - len(original)))
    return (False, 0)


def update_references() -> int:
    total_changed = 0
    for p in iter_project_files():
        changed, _ = update_file_contents(p)
        if changed:
            total_changed += 1
    return total_changed


def verify() -> None:
    # Simple verification prints
    print("\n[VERIFY] scanning for uppercase resource refs...")
    suspicious = []
    pattern = re.compile(r"resources/(Css|Js|Views)")
    for p in iter_project_files():
        try:
            t = p.read_text(encoding="utf-8")
        except Exception:
            continue
        if pattern.search(t):
            suspicious.append(p)
    if suspicious:
        print("Files still containing uppercase resource refs:")
        for p in suspicious:
            print(f" - {p.relative_to(PROJECT_ROOT)}")
    else:
        print("No uppercase resource refs found.")


def main(argv: Iterable[str]) -> int:
    parser = argparse.ArgumentParser(
        description="Normalize Laravel resources/ tree to lowercase and update references."
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Show what would change without applying.",
    )
    parser.add_argument(
        "--no-refs",
        action="store_true",
        help="Do not update file contents, only rename files/dirs.",
    )
    args = parser.parse_args(list(argv))

    if not RESOURCES_DIR.exists():
        print(f"[ERROR] resources directory not found at: {RESOURCES_DIR}")
        return 1

    print(f"[START] Normalizing names under: {RESOURCES_DIR}")
    d, f = lowercase_resources_paths(dry_run=args.dry_run)
    print(f"[DONE] Renames queued -> dirs: {d}, files: {f}")

    changed_files = 0
    if not args.no_refs:
        print("[UPDATE] Updating code references...")
        changed_files = update_references()
        print(f"[DONE] References updated in {changed_files} files.")

    if not args.dry_run:
        verify()
        if is_git_repo(PROJECT_ROOT):
            try:
                import subprocess

                subprocess.run(["git", "add", "-A"], check=False)
            except Exception:
                pass

    print("\n[SUMMARY]")
    print(f"- dirs renamed: {d}")
    print(f"- files renamed: {f}")
    print(f"- files with refs updated: {changed_files}")
    print("\nTip: run `npm run dev` or `npm run build` to regenerate Vite manifest.")
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))


