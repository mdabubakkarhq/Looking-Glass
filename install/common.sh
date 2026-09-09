#!/usr/bin/env bash
# common.sh — Shared functions for the Open Looking Glass installer
# Source this file from other install scripts.

set -euo pipefail

# ── Version ──────────────────────────────────────────────────────────────────
LG_VERSION="1.0.0"
LG_REPO_URL="https://github.com/openlookingglass/looking-glass.git"
LG_INSTALL_DIR="/var/www/looking-glass"
LG_AGENT_DIR="/opt/lg-agent"
LG_AGENT_BIN="/usr/local/bin/lg-agent"

# ── Colors ───────────────────────────────────────────────────────────────────
if [[ -t 1 ]]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
    BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'
    DIM='\033[2m'; NC='\033[0m'
else
    RED='' GREEN='' YELLOW='' BLUE='' CYAN='' BOLD='' DIM='' NC=''
fi

# ── Logging ──────────────────────────────────────────────────────────────────
LOG_FILE="/var/log/open-looking-glass-install.log"

_log_to_file() {
    local level="$1"; shift
    printf "[%s] [%s] %s\n" "$(date '+%Y-%m-%d %H:%M:%S')" "$level" "$*" \
        >> "$LOG_FILE" 2>/dev/null || true
}

info()    { printf "${BLUE}[INFO]${NC} %s\n" "$*"; _log_to_file "INFO" "$*"; }
success() { printf "${GREEN}[ OK ]${NC} %s\n" "$*"; _log_to_file "OK" "$*"; }
warn()    { printf "${YELLOW}[WARN]${NC} %s\n" "$*"; _log_to_file "WARN" "$*"; }
error()   { printf "${RED}[ERR]${NC}  %s\n" "$*" >&2; _log_to_file "ERROR" "$*"; }
fatal()   { error "$*"; exit 1; }
step()    { printf "\n${BOLD}${CYAN}── %s ──${NC}\n" "$*"; _log_to_file "STEP" "$*"; }

# ── Input helpers ────────────────────────────────────────────────────────────
ask() {
    local prompt="$1" default="${2:-}"
    local reply
    if [[ -n "$default" ]]; then
        printf "${BOLD}%s${NC} [${DIM}%s${NC}]: " "$prompt" "$default"
    else
        printf "${BOLD}%s${NC}: " "$prompt"
    fi
    read -r reply
    echo "${reply:-$default}"
}

ask_hidden() {
    local prompt="$1" reply
    printf "${BOLD}%s${NC}: " "$prompt"
    read -rs reply; echo ""
    echo "$reply"
}

confirm() {
    local prompt="${1:-Continue?}" default="${2:-y}" yn
    if [[ "$default" == "y" ]]; then
        printf "${BOLD}%s${NC} [${DIM}Y/n${NC}]: " "$prompt"
    else
        printf "${BOLD}%s${NC} [${DIM}y/N${NC}]: " "$prompt"
    fi
    read -r yn; yn="${yn:-$default}"
    [[ "$yn" =~ ^[Yy] ]]
}

select_option() {
    local prompt="$1"; shift
    local options=("$@")
    printf "${BOLD}%s${NC}\n" "$prompt"
    local i=1
    for opt in "${options[@]}"; do
        printf "  ${CYAN}[%d]${NC} %s\n" "$i" "$opt"
        ((i++))
    done
    local choice
    while true; do
        choice=$(ask "Select")
        if [[ "$choice" =~ ^[0-9]+$ ]] && (( choice >= 1 && choice <= ${#options[@]} )); then
            echo "$choice"; return
        fi
        warn "Invalid selection. Choose 1-${#options[@]}."
    done
}

# ── OS detection ─────────────────────────────────────────────────────────────
detect_os() {
    [[ -f /etc/os-release ]] || fatal "Cannot detect OS. Supported: Ubuntu 24.04, Debian 12."
    . /etc/os-release
    OS_ID="${ID:-unknown}"; OS_VERSION="${VERSION_ID:-unknown}"
    OS_CODENAME="${VERSION_CODENAME:-unknown}"; OS_NAME="${PRETTY_NAME:-unknown}"
}

validate_os() {
    detect_os
    case "$OS_ID" in
        ubuntu)
            [[ "$OS_VERSION" == "24.04" ]] || {
                warn "Ubuntu $OS_VERSION detected. Only 24.04 LTS officially supported."
                confirm "Continue anyway?" "n" || fatal "Cancelled."
            } ;;
        debian)
            [[ "$OS_VERSION" == "12" ]] || {
                warn "Debian $OS_VERSION detected. Only Debian 12 officially supported."
                confirm "Continue anyway?" "n" || fatal "Cancelled."
            } ;;
        *) fatal "Unsupported OS: $OS_NAME. Supported: Ubuntu 24.04, Debian 12." ;;
    esac
    success "OS validated: $OS_NAME"
}

require_root() {
    [[ $EUID -eq 0 ]] || fatal "Must be run as root. Use: sudo bash install.sh"
}

# ── Service helpers ──────────────────────────────────────────────────────────
service_is_active() { systemctl is-active --quiet "$1" 2>/dev/null; }

service_enable_start() {
    local svc="$1"
    systemctl enable "$svc" >/dev/null 2>&1 || true
    systemctl start "$svc" >/dev/null 2>&1 || true
    if service_is_active "$svc"; then success "$svc: active"
    else warn "$svc: failed to start"; fi
}

service_restart() { systemctl restart "$1" >/dev/null 2>&1 || true; }

# ── Package helpers ──────────────────────────────────────────────────────────
apt_update()  { info "Updating package lists..."; apt-get update -qq >/dev/null 2>&1; }

apt_install() {
    info "Installing: $*"
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq "$@" >/dev/null 2>&1
}

# ── Misc helpers ─────────────────────────────────────────────────────────────
random_string() {
    local length="${1:-64}"
    openssl rand -hex "$((length / 2))" 2>/dev/null \
        || head -c "$length" /dev/urandom | base64 | tr -dc 'a-zA-Z0-9' | head -c "$length"
}

generate_recovery_token() { random_string 32; }

command_exists() { command -v "$1" >/dev/null 2>&1; }

wait_for_port() {
    local port="$1" timeout="${2:-30}" elapsed=0
    while ! ss -tlnp 2>/dev/null | grep -q ":${port} "; do
        sleep 1; ((elapsed++)); (( elapsed >= timeout )) && return 1
    done
    return 0
}

# ── Banner ───────────────────────────────────────────────────────────────────
print_banner() {
    printf "${CYAN}${BOLD}"
    cat << 'BANNER'
  ┌─────────────────────────────────────────────┐
  │     Open Looking Glass — Installer v1.0     │
  │                                             │
  │  Network diagnostics platform with          │
  │  multi-node distributed testing             │
  └─────────────────────────────────────────────┘
BANNER
    printf "${NC}\n"
}

# ── Health Check ───────────────────────────────────────────────────────────
run_health_check() {
    local install_type="${1:-1}"
    local failures=0

    step "Running Health Check"

    # Core infrastructure services
    for svc in nginx php8.3-fpm redis-server; do
        if service_is_active "$svc"; then
            success "$svc: running"
        else
            warn "$svc: not running"
            ((failures++))
        fi
    done

    # Laravel services
    for svc in lg-worker lg-scheduler; do
        if service_is_active "$svc"; then
            success "$svc: running"
        else
            warn "$svc: not running"
            ((failures++))
        fi
    done

    # Agent (full install only)
    if [[ "$install_type" == "1" ]]; then
        if service_is_active "lg-agent"; then
            success "lg-agent: running"
        else
            warn "lg-agent: not running"
            ((failures++))
        fi
    fi

    # Laravel application check
    if [[ -f "$LG_INSTALL_DIR/artisan" ]]; then
        cd "$LG_INSTALL_DIR"

        # Route list
        php artisan route:list --columns=method,uri >/dev/null 2>&1 \
            && success "Laravel routes: OK" \
            || { warn "Laravel route check: failed"; ((failures++)); }

        # Database connectivity
        php artisan db:show >/dev/null 2>&1 \
            && success "Database: connected" \
            || { warn "Database: connection failed"; ((failures++)); }

        # Cache connectivity (Redis)
        php artisan cache:clear >/dev/null 2>&1 \
            && success "Cache (Redis): OK" \
            || { warn "Cache: connection failed"; ((failures++)); }

        # HTTP endpoint check
        local port
        port=$(ss -tlnp 2>/dev/null | grep -oP '(?<=:)\d+(?= )' | grep -E '^(80|443|8080)$' | head -1)
        if [[ -n "$port" ]]; then
            local http_code
            http_code=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:${port}/api/v1/config" 2>/dev/null || echo "000")
            if [[ "$http_code" == "200" ]]; then
                success "API endpoint: responding (HTTP $http_code)"
            else
                warn "API endpoint: not responding (HTTP $http_code)"
                ((failures++))
            fi
        fi
    fi

    # Summary
    if [[ $failures -eq 0 ]]; then
        success "All health checks passed"
    else
        warn "$failures health check(s) failed — review above for details"
    fi

    return 0
}

# ── Post-install report ─────────────────────────────────────────────────────
print_install_report() {
    local domain="$1" protocol="${2:-https}" recovery_token="${3:-}"
    printf "\n${GREEN}${BOLD}Installation completed successfully.${NC}\n\n"
    printf "  Frontend:  ${BOLD}%s://%s${NC}\n" "$protocol" "$domain"
    printf "  Admin:     ${BOLD}%s://%s/admin${NC}\n" "$protocol" "$domain"
    printf "  API:       ${BOLD}%s://%s/api/v1${NC}\n" "$protocol" "$domain"
    printf "\n  ${BOLD}Services:${NC}\n"
    for svc in nginx php8.3-fpm redis-server lg-scheduler lg-worker lg-agent; do
        if service_is_active "$svc"; then
            printf "  ${GREEN}●${NC} %s: active\n" "$svc"
        else
            printf "  ${DIM}○${NC} %s: inactive\n" "$svc"
        fi
    done
    printf "\n"
    if [[ -n "$recovery_token" ]]; then
        printf "  ${YELLOW}${BOLD}Recovery token:${NC} %s\n" "$recovery_token"
        printf "  ${DIM}Save this securely. Used to reset the admin password.${NC}\n\n"
    fi
    printf "  ${DIM}Install log: %s${NC}\n\n" "$LOG_FILE"
}

