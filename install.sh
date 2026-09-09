#!/usr/bin/env bash
# ──────────────────────────────────────────────────────────────────────────────
# Open Looking Glass — One-Command Installer
#
# Usage:
#   curl -fsSL https://raw.githubusercontent.com/<ORG>/<REPO>/main/install.sh | sudo bash
#
# Options (passed after -- or via environment):
#   INSTALL_TYPE   1=full, 2=controller, 3=agent, 4=docker
#   DOMAIN         Domain name for NGINX
#   SSL_CHOICE     1=letsencrypt, 2=existing, 3=none
#   DB_CHOICE      1=postgresql, 2=mariadb, 3=sqlite
#   REDIS_CHOICE   1=local, 2=external
#   CONTROLLER_URL For agent-only: controller URL
#   REG_TOKEN      For agent-only: registration token
# ──────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# Determine script location
INSTALL_DIR="${INSTALL_DIR:-}"
if [[ -z "$INSTALL_DIR" ]]; then
    SCRIPT_SOURCE="${BASH_SOURCE[0]}"
    while [[ -L "$SCRIPT_SOURCE" ]]; do
        SCRIPT_DIR="$(cd "$(dirname "$SCRIPT_SOURCE")" && pwd)"
        SCRIPT_SOURCE="$(readlink "$SCRIPT_SOURCE")"
        [[ "$SCRIPT_SOURCE" != /* ]] && SCRIPT_SOURCE="$SCRIPT_DIR/$SCRIPT_SOURCE"
    done
    INSTALL_DIR="$(cd "$(dirname "$SCRIPT_SOURCE")" && pwd)"
fi

# Source common functions
if [[ -f "$INSTALL_DIR/install/common.sh" ]]; then
    source "$INSTALL_DIR/install/common.sh"
elif [[ -f "$INSTALL_DIR/common.sh" ]]; then
    source "$INSTALL_DIR/common.sh"
else
    echo "ERROR: Cannot find common.sh. Ensure install/ directory is alongside install.sh"
    exit 1
fi

# Source modules
for module in controller.sh database.sh redis.sh nginx.sh ssl.sh firewall.sh agent.sh; do
    if [[ -f "$INSTALL_DIR/install/$module" ]]; then
        source "$INSTALL_DIR/install/$module"
    elif [[ -f "$INSTALL_DIR/$module" ]]; then
        source "$INSTALL_DIR/$module"
    fi
done

# ── Parse CLI arguments ──────────────────────────────────────────────────────
parse_args() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            agent)          CLI_INSTALL_TYPE=3; shift ;;
            --controller=*) CLI_CONTROLLER_URL="${1#*=}"; shift ;;
            --token=*)      CLI_REG_TOKEN="${1#*=}"; shift ;;
            --domain=*)     CLI_DOMAIN="${1#*=}"; shift ;;
            --db=*)         CLI_DB_CHOICE="${1#*=}"; shift ;;
            --non-interactive) CLI_NON_INTERACTIVE=true; shift ;;
            --help|-h)      print_usage; exit 0 ;;
            *) warn "Unknown option: $1"; shift ;;
        esac
    done
}

print_usage() {
    cat << 'USAGE'
Open Looking Glass Installer

Usage:
  sudo bash install.sh                 Interactive full installation
  sudo bash install.sh agent           Agent-only installation
  sudo bash install.sh agent --controller=https://lg.example.com --token=TOKEN

Environment variables:
  INSTALL_TYPE     1=full, 2=controller, 3=agent, 4=docker
  DOMAIN           Domain name
  SSL_CHOICE       1=letsencrypt, 2=existing, 3=none
  DB_CHOICE        1=postgresql, 2=mariadb, 3=sqlite
  REDIS_CHOICE     1=local, 2=external
  CONTROLLER_URL   Controller URL (agent mode)
  REG_TOKEN        Registration token (agent mode)
USAGE
}

# ── Main ─────────────────────────────────────────────────────────────────────
main() {
    local CLI_INSTALL_TYPE="${INSTALL_TYPE:-}"
    local CLI_CONTROLLER_URL="${CONTROLLER_URL:-}"
    local CLI_REG_TOKEN="${REG_TOKEN:-}"
    local CLI_DOMAIN="${DOMAIN:-}"
    local CLI_DB_CHOICE="${DB_CHOICE:-}"

    parse_args "$@"

    require_root
    print_banner

    # Agent-only mode
    if [[ "${CLI_INSTALL_TYPE:-}" == "3" ]]; then
        local ctrl="${CLI_CONTROLLER_URL:-}" tok="${CLI_REG_TOKEN:-}"
        [[ -z "$ctrl" ]] && ctrl=$(ask "Controller URL")
        [[ -z "$tok" ]]  && tok=$(ask "Registration token")
        install_agent_standalone "$ctrl" "$tok"
        return
    fi

    # Interactive wizard
    local install_type="${CLI_INSTALL_TYPE:-}"
    if [[ -z "$install_type" ]]; then
        install_type=$(select_option "Installation type:" \
            "Full Controller + Local Agent" \
            "Controller Only" \
            "Remote Agent Only" \
            "Docker Compose (not yet available)")
    fi

    # Agent-only from wizard
    if [[ "$install_type" == "3" ]]; then
        local ctrl="${CLI_CONTROLLER_URL:-}" tok="${CLI_REG_TOKEN:-}"
        [[ -z "$ctrl" ]] && ctrl=$(ask "Controller URL")
        [[ -z "$tok" ]]  && tok=$(ask "Registration token")
        install_agent_standalone "$ctrl" "$tok"
        return
    fi

    # Docker placeholder
    if [[ "$install_type" == "4" ]]; then
        info "Docker Compose: docker compose up -d"
        info "See docker-compose.yml in the repository."
        return
    fi

    # ── Full / Controller installation ───────────────────────────────────────
    validate_os
    apt_update

    local domain="${CLI_DOMAIN:-}"
    [[ -z "$domain" ]] && domain=$(ask "Domain name (or IP, or '_')" "_")

    local ssl_choice="${SSL_CHOICE:-}"
    [[ -z "$ssl_choice" ]] && ssl_choice=$(select_option "SSL/TLS:" \
        "Let's Encrypt (automatic)" \
        "Existing certificate" \
        "No SSL (reverse proxy managed externally)")

    local protocol="http"
    [[ "$ssl_choice" =~ ^[12]$ ]] && protocol="https"
    export APP_URL="${protocol}://${domain}"

    local db_choice="${CLI_DB_CHOICE:-}"
    [[ -z "$db_choice" ]] && db_choice=$(select_option "Database engine:" \
        "PostgreSQL (recommended)" "MariaDB" "SQLite (development)")

    local redis_choice="${REDIS_CHOICE:-}"
    [[ -z "$redis_choice" ]] && redis_choice=$(select_option "Redis:" \
        "Install locally" "External Redis")

    echo ""
    info "Installation summary:"
    info "  Type:     $([ "$install_type" == "1" ] && echo "Full (Controller + Agent)" || echo "Controller Only")"
    info "  Domain:   $domain"
    info "  Database: $([ "$db_choice" == "1" ] && echo "PostgreSQL" || ([ "$db_choice" == "2" ] && echo "MariaDB" || echo "SQLite"))"
    info "  Redis:    $([ "$redis_choice" == "1" ] && echo "Local" || echo "External")"
    echo ""
    confirm "Proceed with installation?" || { info "Cancelled."; exit 0; }

    # ── Execute installation steps ───────────────────────────────────────────
    install_controller
    setup_database "$db_choice"
    setup_redis "$redis_choice"
    configure_laravel
    setup_nginx "$domain"
    setup_ssl "$domain" "$ssl_choice"
    setup_firewall
    setup_laravel_services

    # Agent (full install only)
    if [[ "$install_type" == "1" ]]; then
        install_agent
    fi

    # ── Post-install ─────────────────────────────────────────────────────────
    local recovery_token
    recovery_token=$(generate_recovery_token)

    # Store recovery token hash
    if [[ -f "$LG_INSTALL_DIR/artisan" ]]; then
        cd "$LG_INSTALL_DIR"
        php artisan tinker --execute="\\App\\Models\\Setting::updateOrCreate(['key' => 'recovery_token'], ['value' => hash('sha256', '$recovery_token'), 'group' => 'security', 'type' => 'string', 'public' => false]);" 2>/dev/null || true
    fi

    # Health check
    run_health_check "$install_type"

    echo ""
    print_install_report "$domain" "$protocol" "$recovery_token"
}

main "$@"

