#!/usr/bin/env bash
# install/redis.sh — Redis installation and configuration

setup_redis() {
    local redis_choice="${1:-}"
    if [[ -z "$redis_choice" ]]; then
        redis_choice=$(select_option "Redis:" "Install locally" "External Redis (skip install)")
    fi

    case "$redis_choice" in
        1) install_redis_local ;;
        2) configure_redis_external ;;
        *) fatal "Invalid Redis choice." ;;
    esac
}

install_redis_local() {
    step "Installing Redis"
    apt_install redis-server

    # Ensure Redis binds to localhost only
    if grep -q "^bind " /etc/redis/redis.conf 2>/dev/null; then
        sed -i 's/^bind .*/bind 127.0.0.1 ::1/' /etc/redis/redis.conf
    fi

    service_enable_start redis-server

    if command_exists redis-cli && redis-cli ping 2>/dev/null | grep -q PONG; then
        success "Redis is running and responding"
    else
        warn "Redis installed but not responding to ping"
    fi

    export REDIS_HOST="127.0.0.1"
    export REDIS_PORT="6379"
    export REDIS_PASSWORD=""
}

configure_redis_external() {
    step "Configuring external Redis"
    local host port password
    host=$(ask "Redis host" "127.0.0.1")
    port=$(ask "Redis port" "6379")
    password=$(ask_hidden "Redis password (leave empty for none)")

    export REDIS_HOST="$host"
    export REDIS_PORT="$port"
    export REDIS_PASSWORD="$password"

    success "External Redis configured: $host:$port"
}
