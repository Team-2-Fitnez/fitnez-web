#!/bin/sh
# husky

# Created by husky
command -v git >/dev/null 2>&1 || { echo "git is required"; exit 1; }
cd "$(dirname "$0")/.."
