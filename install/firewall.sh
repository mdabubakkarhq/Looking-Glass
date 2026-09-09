#!/usr/bin/env bash
# install/firewall.sh — UFW firewall configuration

setup_firewall() {
    step "Configuring Firewall (UFW)"

    if ! command_exists ufw; then
        apt_install ufw
    fi

    # Ensure SSH is always allowed first to prevent lockout
    ufw allow OpenSSH >/dev/null 2>&1
    info "Allowed: SSH (port 22)"

    # HTTP and HTTPS
    ufw allow 80/tcp  >/dev/null 2>&1
    info "Allowed: HTTP (port 80)"
    ufw allow 443/tcp >/dev/null 2>&1
    info "Allowed: HTTPS (port 443)"

    # Enable UFW if not already active
    if ! ufw status | grep -q "Status: active"; then
        echo "y" | ufw enable >/dev/null 2>&1 || true
    fi

    success "Firewall configured"
    info "Run 'ufw status verbose' to review rules"
}
