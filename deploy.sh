#!/bin/bash
# =============================================
# COGOCRAFT — Webhook de déploiement
# À placer sur la VM : /var/www/cogocraft/deploy.sh
# =============================================
set -e

REPO="https://github.com/TON_USERNAME/cogocraft-site.git"
WEBROOT="/var/www/cogocraft"
TMPDIR="/tmp/cogocraft-deploy-$$"
BRANCH="main"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Déploiement déclenché"

# Clone en temp
git clone --depth=1 --branch "$BRANCH" "$REPO" "$TMPDIR"

# Synchro vers webroot (exclut .git et scripts)
rsync -a --delete \
  --exclude='.git' \
  --exclude='deploy.sh' \
  --exclude='webhook.php' \
  "$TMPDIR/" "$WEBROOT/"

rm -rf "$TMPDIR"

# Permissions
chown -R www-data:www-data "$WEBROOT/"
chmod -R 755 "$WEBROOT/"

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Déploiement terminé OK"
