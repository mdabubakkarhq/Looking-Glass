#!/usr/bin/env bash
# install/rollback.sh — Rollback the Looking Glass installation
#
# Usage:
#   sudo bash install/rollback.sh               # Rollback to previous git tag
#   sudo bash install/rollback.sh --db-only      # Database rollback only
#   sudo bash install/rollback.sh --steps=3      # Rollback 3 migration batches

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
[[ -f "$SCRIPT_DIR/common.sh" ]] && source "$SCRIPT_DIR/common.sh"

require_root

LG_INSTALL_DIR="${LG_INSTALL_DIR:-/var/www/looking-glass}"
DB_ONLY=false
STEPS=1

# Parse arguments
while [[ $# -gt 0 ]]; do
    case "$1" in
        --db-only)  DB_ONLY=true; shift ;;
        --steps=*)  STEPS="${1#*=}"; shift ;;
        --help|-h)
            echo "Usage: rollback.sh [--db-only] [--steps=N]"
            echo "  --db-only    Only rollback database migrations"
            echo "  --steps=N    Number of migration batches to rollback (default: 1)"
            exit 0 ;;
        *) warn "Unknown option: $1"; shift ;;
    esac
done

step "Rollback"

cd "$LG_INSTALL_DIR" || fatal "Install directory not found: $LG_INSTALL_DIR"

# ── Code Rollback (git-based) ────────────────────────────────────────────
if [[ "$DB_ONLY" == false ]]; then
    if [[ -d .git ]]; then
        CURRENT_COMMIT=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
        CURRENT_TAG=$(git describe --tags --abbrev=0 2>/dev/null || echo "none")

        info "Current version: $CURRENT_TAG ($CURRENT_COMMIT)"

        # Find the previous tag
        PREVIOUS_TAG=$(git describe --tags --abbrev=0 HEAD^ 2>/dev/null || echo "")

        if [[ -n "$PREVIOUS_TAG" ]]; then
            info "Rolling back to: $PREVIOUS_TAG"

            # Stash any local changes
            git stash --include-untracked 2>/dev/null || true

            # Checkout the previous tag
            git checkout "$PREVIOUS_TAG" 2>&1 | tail -3

            # Reinstall dependencies
            info "Reinstalling Composer dependencies..."
            composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -3

            success "Code rolled back to $PREVIOUS_TAG"
        else
            warn "No previous tag found. Attempting to revert last commit..."
            git revert HEAD --no-edit 2>&1 || warn "Could not revert commit"
        fi
    else
        warn "Not a git repository — skipping code rollback"
    fi
fi

# ── Database Rollback ────────────────────────────────────────────────────
step "Rolling back database migrations"

for ((i = 1; i <= STEPS; i++)); do
    info "Rolling back migration batch $i of $STEPS..."
    php artisan migrate:rollback --force --no-interaction 2>&1 | tail -5 || {
        error "Migration rollback failed at batch $i"
        break
    }
done

success "Database rollback completed ($STEPS batch(es))"

# ── Cache Clearing ───────────────────────────────────────────────────────
step "Clearing caches"

php artisan cache:clear 2>&1 | tail -1 || true
php artisan config:clear 2>&1 | tail -1 || true
php artisan route:clear 2>&1 | tail -1 || true
php artisan view:clear 2>&1 | tail -1 || true

success "Caches cleared"

# ── Re-cache Production Config ───────────────────────────────────────────
step "Re-caching production configuration"

php artisan config:cache 2>&1 | tail -1 || true
php artisan route:cache 2>&1 | tail -1 || true

# ── Restart Services ─────────────────────────────────────────────────────
step "Restarting services"

for svc in lg-worker lg-scheduler php8.3-fpm; do
    if systemctl is-active --quiet "$svc" 2>/dev/null; then
        systemctl restart "$svc" 2>/dev/null && success "$svc: restarted" || warn "$svc: restart failed"
    fi
done

# ── Permissions ──────────────────────────────────────────────────────────
chown -R www-data:www-data "$LG_INSTALL_DIR" 2>/dev/null || true
chmod -R 775 "$LG_INSTALL_DIR/storage" 2>/dev/null || true
chmod -R 775 "$LG_INSTALL_DIR/bootstrap/cache" 2>/dev/null || true

echo ""
printf "${GREEN}${BOLD}Rollback complete.${NC}\n\n"

if [[ -d .git ]]; then
    printf "  Version:   %s\n" "$(git describe --tags --abbrev=0 2>/dev/null || git rev-parse --short HEAD 2>/dev/null || echo 'unknown')"
fi
printf "  Migrations: rolled back %s batch(es)\n" "$STEPS"
printf "  Caches:    cleared and re-cached\n\n"
