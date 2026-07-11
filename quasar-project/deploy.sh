#!/bin/bash
set -e

# ── Fill these in once ────────────────────────────────────────────────────────
SSH_KEY="$HOME/.ssh/diet_journal_deploy"
SSH_USER="u466389499"
SSH_HOST="ftp.castoware.com"
REMOTE_DIR="/home/u466389499/castoware.com/diet-journal/public"        # the folder that serves index.html on the server
# ─────────────────────────────────────────────────────────────────────────────

echo "Building..."
yarn build

echo "Uploading..."
rsync -avz --checksum \
  -e "ssh -i $SSH_KEY -p 65002" \
  --exclude=".DS_Store" \
  dist/spa/ \
  "$SSH_USER@$SSH_HOST:$REMOTE_DIR/"

echo "Done."
