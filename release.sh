#!/usr/bin/env bash
#
# Prepare a Phorum release: bump the version, tag it, and build distribution
# archives from the tag. Must be run from the main/master branch on a clean tree.
#
# Usage: ./release.sh X.Y.Z

set -euo pipefail

VERSION="${1:-}"

if [[ -z "$VERSION" ]]; then
    echo "Usage: $0 X.Y.Z" >&2
    exit 1
fi

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.]+)?$ ]]; then
    echo "Error: '$VERSION' is not a valid version (expected X.Y.Z or X.Y.Z-suffix)" >&2
    exit 1
fi

TAG="v$VERSION"

REPO_ROOT="$(git rev-parse --show-toplevel)"
cd "$REPO_ROOT"

BRANCH="$(git rev-parse --abbrev-ref HEAD)"
if [[ "$BRANCH" != "master" && "$BRANCH" != "main" ]]; then
    echo "Error: releases must be built from the 'main' or 'master' branch (currently on '$BRANCH')" >&2
    exit 1
fi

if [[ -n "$(git status --porcelain)" ]]; then
    echo "Error: working tree is not clean. Commit or stash your changes first." >&2
    exit 1
fi

echo "Fetching origin/$BRANCH..."
git fetch origin "$BRANCH"

if ! git merge-base --is-ancestor "origin/$BRANCH" HEAD; then
    echo "Error: local $BRANCH is behind or has diverged from origin/$BRANCH. Pull first." >&2
    exit 1
fi

if git rev-parse "$TAG" >/dev/null 2>&1; then
    echo "Error: tag '$TAG' already exists locally." >&2
    exit 1
fi

if [[ -n "$(git ls-remote --tags origin "$TAG")" ]]; then
    echo "Error: tag '$TAG' already exists on origin." >&2
    exit 1
fi

echo "Bumping version to $VERSION in common.php..."
perl -pi -e 's/define\( "PHORUM", "[^"]+" \);/define( "PHORUM", "'"$VERSION"'" );/' common.php

if ! grep -q "define( \"PHORUM\", \"$VERSION\" );" common.php; then
    echo "Error: failed to update the PHORUM version constant in common.php" >&2
    exit 1
fi

git add common.php
git commit -m "Version changed to $VERSION"
git tag -a "$TAG" -m "Release $VERSION"

echo "Building distribution archives..."
mkdir -p dist
ARCHIVE_BASE="phorum-$VERSION"

git archive --format=zip --prefix="$ARCHIVE_BASE/" -o "dist/$ARCHIVE_BASE.zip" "$TAG"
git archive --format=tar --prefix="$ARCHIVE_BASE/" "$TAG" | gzip -9 > "dist/$ARCHIVE_BASE.tar.gz"

COMMIT_SHA="$(git rev-parse --short HEAD)"

echo
echo "Release $VERSION prepared successfully:"
echo "  Commit: $COMMIT_SHA"
echo "  Tag:    $TAG"
echo "  Archives:"
ls -lh "dist/$ARCHIVE_BASE.zip" "dist/$ARCHIVE_BASE.tar.gz" | awk '{print "    " $NF " (" $5 ")"}'
echo
echo "Nothing has been pushed. To publish this release, run:"
echo "  git push origin $BRANCH"
echo "  git push origin $TAG"
