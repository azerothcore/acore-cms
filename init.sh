#!/usr/bin/env bash
set -e

# Ensure submodules are initialized and updated on pull
git config submodule.recurse true

# Initialize and update all submodules (including nested ones)
echo "Initializing and updating submodules..."
git submodule update --init --recursive