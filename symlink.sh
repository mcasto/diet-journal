#!/bin/bash

# Get current working directory
DOCROOT=$(pwd)

# Define target and link paths
TARGET="$DOCROOT/storage/app/public"
LINK="$DOCROOT/public/storage"

# Check if symlink already exists
if [ -e "$LINK" ]; then
    echo "Error: Symlink already exists at $LINK"
    exit 1
fi

# Check if target directory exists
if [ ! -d "$TARGET" ]; then
    echo "Error: Target directory $TARGET does not exist"
    exit 1
fi

# Create parent directory if needed
mkdir -p "$(dirname "$LINK")"

# Create the symlink
if ln -s "$TARGET" "$LINK"; then
    echo "Success: Created symlink from $TARGET to $LINK"
    exit 0
else
    echo "Error: Failed to create symlink"
    exit 1
fi
