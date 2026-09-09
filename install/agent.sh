#!/usr/bin/env bash
# install/agent.sh — Go agent build and installation

install_agent() {
    local controller_url="${1:-}"
    local reg_token="${2:-}"

    step "Installing LG Agent"

    install_go_if_needed

    # Get agent source
    if [[ -d "$LG_INSTALL_DIR/agent" ]]; then
        info "Using agent source from $LG_INSTALL_DIR/agent"
        cp -r "$LG_INSTALL_DIR/agent" "$LG_AGENT_DIR"
    else
        info "Cloning agent source..."
        mkdir -p "$LG_AGENT_DIR"
        git clone --depth 1 --filter=blob:none --sparse "$LG_REPO_URL" /tmp/lg-repo 2>/dev/null
        cd /tmp/lg-repo && git sparse-checkout set agent
        cp -r agent/* "$LG_AGENT_DIR/"
        rm -rf /tmp/lg-repo
    fi

    cd "$LG_AGENT_DIR"

    # Fix potential Windows backslash paths in source
    find . -depth -name '*\\*' 2>/dev/null | while IFS= read -r f; do
        local newf
        newf=$(echo "$f" | tr '\\' '/')
        mkdir -p "$(dirname "$newf")"
        [[ -d "$f" ]] && rmdir "$f" 2>/dev/null || true
        mv "$f" "$newf" 2>/dev/null || true
    done

    # Build agent
    info "Building agent..."
    if [[ -f go.mod ]]; then
        go mod download 2>/dev/null || true
        go build -ldflags "-X main.version=$LG_VERSION" -o "$LG_AGENT_BIN" ./cmd/agent 2>&1
    else
        fatal "Agent source not found in $LG_AGENT_DIR"
    fi

    chmod +x "$LG_AGENT_BIN"
    success "Agent built: $($LG_AGENT_BIN version 2>/dev/null || echo 'unknown version')"

    # Install systemd service
    cat > /etc/systemd/system/lg-agent.service << 'EOF'
[Unit]
Description=Open Looking Glass Agent
After=network.target

[Service]
Type=simple
ExecStart=/usr/local/bin/lg-agent serve
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal
SyslogIdentifier=lg-agent
NoNewPrivileges=yes
ProtectSystem=strict
ReadWritePaths=/tmp

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    service_enable_start lg-agent
    success "lg-agent service installed"

    # Register with controller if credentials provided
    if [[ -n "$controller_url" && -n "$reg_token" ]]; then
        info "Registering agent with controller..."
        "$LG_AGENT_BIN" register \
            --controller="$controller_url" \
            --token="$reg_token" 2>&1 || warn "Agent registration failed. Register manually later."
    fi
}

install_go_if_needed() {
    if command_exists go; then
        local go_version
        go_version=$(go version 2>/dev/null | grep -oP 'go\K[0-9]+\.[0-9]+' || echo "0.0")
        local go_major go_minor
        go_major=$(echo "$go_version" | cut -d. -f1)
        go_minor=$(echo "$go_version" | cut -d. -f2)
        if (( go_major > 1 || (go_major == 1 && go_minor >= 22) )); then
            success "Go $go_version already installed"
            return
        fi
    fi

    info "Installing Go 1.22..."
    cd /tmp
    curl -sLO https://go.dev/dl/go1.22.5.linux-amd64.tar.gz
    rm -rf /usr/local/go
    tar -C /usr/local -xzf go1.22.5.linux-amd64.tar.gz
    rm -f go1.22.5.linux-amd64.tar.gz
    export PATH=$PATH:/usr/local/go/bin
    success "Go $(go version | awk '{print $3}') installed"
}

# Standalone agent-only installation (for remote nodes)
install_agent_standalone() {
    local controller_url="$1"
    local reg_token="$2"

    require_root
    validate_os
    apt_update
    apt_install git curl

    install_agent "$controller_url" "$reg_token"

    printf "\n${GREEN}${BOLD}Agent installation complete.${NC}\n\n"
    printf "  Controller: %s\n" "$controller_url"
    printf "  Agent:      %s\n" "$($LG_AGENT_BIN version 2>/dev/null || echo 'installed')"
    printf "  Service:    lg-agent\n\n"
}
