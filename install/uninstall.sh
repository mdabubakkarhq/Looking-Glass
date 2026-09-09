#!/usr/bin/env bash
# install/uninstall.sh — Remove Open Looking Glass

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/common.sh"

main() {
    require_root
    print_banner

    warn "This will remove Open Looking Glass from this system."
    warn "Installed files, services, and NGINX config will be removed."
    echo ""

    # Database
    local remove_db=false
    if confirm "Also remove the database and database user?" "n"; then
        remove_db=true
    fi

    # Confirm
    echo ""
    confirm "Proceed with uninstallation?" "n" || { info "Cancelled."; exit 0; }

    step "Stopping services"
    for svc in lg-agent lg-worker lg-scheduler; do
        if systemctl list-unit-files | grep -q "$svc"; then
            systemctl stop "$svc" 2>/dev/null || true
            systemctl disable "$svc" 2>/dev/null || true
            rm -f "/etc/systemd/system/${svc}.service"
            success "Removed $svc"
        fi
    done
    systemctl daemon-reload

    step "Removing NGINX config"
    rm -f /etc/nginx/sites-available/looking-glass
    rm -f /etc/nginx/sites-enabled/looking-glass
    if [[ -f /etc/nginx/sites-enabled/default.bak ]]; then
        mv /etc/nginx/sites-enabled/default.bak /etc/nginx/sites-enabled/default 2>/dev/null || true
    fi
    nginx -t 2>&1 && systemctl restart nginx 2>/dev/null || true
    success "NGINX config removed"

    step "Removing application files"
    if [[ -d "$LG_INSTALL_DIR" ]]; then
        # Backup .env one last time
        [[ -f "$LG_INSTALL_DIR/.env" ]] && {
            local backup="/root/lg-env-backup-$(date +%Y%m%d%H%M%S)"
            cp "$LG_INSTALL_DIR/.env" "$backup"
            info ".env backed up to $backup"
        }
        rm -rf "$LG_INSTALL_DIR"
        success "Removed $LG_INSTALL_DIR"
    fi

    # Remove agent
    if [[ -f "$LG_AGENT_BIN" ]]; then
        rm -f "$LG_AGENT_BIN"
        rm -rf "$LG_AGENT_DIR"
        success "Removed agent"
    fi

    # Remove database
    if $remove_db; then
        step "Removing database"
        if command_exists mysql; then
            mysql -e "DROP DATABASE IF EXISTS lookingglass;" 2>/dev/null
            mysql -e "DROP USER IF EXISTS 'lg'@'localhost';" 2>/dev/null
            mysql -e "FLUSH PRIVILEGES;" 2>/dev/null
            success "MariaDB database removed"
        fi
        if command_exists psql; then
            sudo -u postgres psql -c "DROP DATABASE IF EXISTS looking_glass;" 2>/dev/null
            sudo -u postgres psql -c "DROP USER IF EXISTS looking_glass;" 2>/dev/null
            success "PostgreSQL database removed"
        fi
    fi

    # Remove log file
    rm -f "$LOG_FILE"

    echo ""
    success "Open Looking Glass has been uninstalled."
    if [[ -f /root/lg-env-backup-* ]]; then
        info "Your .env backup is in /root/"
    fi
}

main "$@"
