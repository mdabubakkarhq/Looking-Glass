#!/usr/bin/env bash
# install/update.sh — Update Open Looking Glass to latest version

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common.sh"

main() {
    require_root
    print_banner
    info "Updating Open Looking Glass..."

    [[ -d "$LG_INSTALL_DIR" ]] || fatal "Installation not found at $LG_INSTALL_DIR"

    cd "$LG_INSTALL_DIR"

    # Backup .env
    step "Backing up configuration"
    local backup_dir="/tmp/lg-backup-$(date +%Y%m%d%H%M%S)"
    mkdir -p "$backup_dir"
    [[ -f .env ]] && cp .env "$backup_dir/"
    success "Backup saved to $backup_dir"

    # Pull latest code
    step "Updating source code"
    if [[ -d .git ]]; then
        git pull --ff-only 2>&1 || warn "Could not fast-forward. Resolve conflicts manually."
    else
        warn "Not a git repository. Manual update required."
    fi

    # Install PHP dependencies
    step "Updating PHP dependencies"
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5

    # Build frontend
    if [[ -d frontend ]]; then
        step "Rebuilding frontend"
        cd frontend
        npm ci --no-audit --no-fund 2>&1 | tail -3
        npm run build 2>&1 | tail -5

        if [[ -d dist ]]; then
            [[ -f "$LG_INSTALL_DIR/public/index.php" ]] \
                && cp "$LG_INSTALL_DIR/public/index.php" /tmp/lg_idx.bak
            cp -r dist/* "$LG_INSTALL_DIR/public/"
            [[ -f /tmp/lg_idx.bak ]] && cp /tmp/lg_idx.bak "$LG_INSTALL_DIR/public/index.php"
        fi
        cd "$LG_INSTALL_DIR"
    fi

    # Run migrations
    step "Running migrations"
    php artisan migrate --force --no-interaction 2>&1 | tail -5

    # Clear and rebuild caches
    step "Rebuilding caches"
    php artisan config:cache --no-interaction 2>&1
    php artisan route:cache --no-interaction 2>&1
    php artisan view:cache --no-interaction 2>&1 || true

    # Fix permissions
    chown -R www-data:www-data "$LG_INSTALL_DIR"
    chmod -R 755 "$LG_INSTALL_DIR"
    chmod -R 775 "$LG_INSTALL_DIR/storage" 2>/dev/null || true
    chmod -R 775 "$LG_INSTALL_DIR/bootstrap/cache" 2>/dev/null || true

    # Restart services
    step "Restarting services"
    for svc in php8.3-fpm nginx lg-worker lg-scheduler lg-agent; do
        if service_is_active "$svc"; then
            service_restart "$svc"
            success "$svc restarted"
        fi
    done

    success "Update complete!"
}

main "$@"
