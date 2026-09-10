#!/usr/bin/env bash
# install/controller.sh — Controller installation (PHP, Composer, Node.js, Laravel, frontend)

install_controller() {
    step "Installing Controller Dependencies"

    # PHP 8.3
    install_php

    # Composer
    install_composer

    # Node.js 22 LTS
    install_nodejs

    # Clone / copy application
    clone_application

    # Composer install
    step "Installing PHP Dependencies"
    cd "$LG_INSTALL_DIR"
    composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -5
    success "Composer dependencies installed"

    # Build frontend
    build_frontend
}

install_php() {
    step "Installing PHP 8.3"

    if [[ "$OS_ID" == "ubuntu" ]]; then
        apt_install software-properties-common
        add-apt-repository -y ppa:ondrej/php >/dev/null 2>&1
    elif [[ "$OS_ID" == "debian" ]]; then
        apt_install apt-transport-https lsb-release ca-certificates
        curl -sSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/php.gpg 2>/dev/null
        echo "deb [signed-by=/usr/share/keyrings/php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
            > /etc/apt/sources.list.d/php.list
    fi
    apt_update

    local php_pkgs=(
        php8.3-fpm php8.3-cli php8.3-common
        php8.3-pgsql php8.3-mysql php8.3-sqlite3
        php8.3-xml php8.3-curl php8.3-mbstring
        php8.3-zip php8.3-bcmath php8.3-tokenizer
        php8.3-dom php8.3-gd php8.3-intl
        php8.3-redis php8.3-readline
        unzip curl git
    )
    apt_install "${php_pkgs[@]}"

    service_enable_start php8.3-fpm
    success "PHP $(php -r 'echo PHP_VERSION;') installed"
}

install_composer() {
    if command_exists composer; then
        success "Composer already installed"
        return
    fi
    step "Installing Composer"
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer 2>&1 | tail -3
    success "Composer installed"
}

install_nodejs() {
    if command_exists node; then
        local node_major
        node_major=$(node -v 2>/dev/null | grep -oP 'v\K[0-9]+' || echo "0")
        if (( node_major >= 22 )); then
            success "Node.js $(node -v) already installed"
            return
        fi
    fi

    step "Installing Node.js 22 LTS"
    curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1
    apt_install nodejs
    success "Node.js $(node -v) installed"
}

clone_application() {
    step "Setting up Application"

    if [[ -d "$LG_INSTALL_DIR/.git" ]]; then
        info "Existing installation found at $LG_INSTALL_DIR"
        cd "$LG_INSTALL_DIR"
        git pull --ff-only 2>/dev/null || warn "Could not fast-forward. Using existing code."
    elif [[ -d "$LG_INSTALL_DIR/artisan" ]]; then
        info "Application files already present at $LG_INSTALL_DIR"
    else
        info "Cloning repository..."
        mkdir -p "$LG_INSTALL_DIR"
        git clone "$LG_REPO_URL" "$LG_INSTALL_DIR" 2>&1 | tail -3
        cd "$LG_INSTALL_DIR"
    fi

    # Monorepo: controller code is in controller/ subdirectory
    if [[ -d "$LG_INSTALL_DIR/controller" && -f "$LG_INSTALL_DIR/controller/artisan" ]]; then
        if [[ ! -f "$LG_INSTALL_DIR/artisan" ]]; then
            info "Moving controller files to install root..."
            # Copy controller directory contents (not the directory itself) to install root
            # Using rsync to avoid recursive nesting issues
            rsync -a "$LG_INSTALL_DIR/controller/" "$LG_INSTALL_DIR/" 2>/dev/null \
                || cp -a "$LG_INSTALL_DIR/controller/"* "$LG_INSTALL_DIR/" 2>/dev/null || true
            # Remove the controller subdirectory now that contents are at root
            rm -rf "$LG_INSTALL_DIR/controller"
            success "Controller files moved to $LG_INSTALL_DIR"
        fi
    fi

    success "Application source ready at $LG_INSTALL_DIR"
}

build_frontend() {
    step "Building Frontend"

    local frontend_dir="$LG_INSTALL_DIR/frontend"
    [[ -d "$frontend_dir" ]] || { warn "Frontend directory not found. Skipping build."; return; }

    cd "$frontend_dir"
    info "Installing npm dependencies..."
    npm ci --no-audit --no-fund 2>&1 | tail -3

    info "Building frontend assets..."
    npm run build 2>&1 | tail -5

    if [[ -d "$frontend_dir/dist" ]]; then
        [[ -f "$LG_INSTALL_DIR/public/index.php" ]] \
            && cp "$LG_INSTALL_DIR/public/index.php" /tmp/lg_index.php.bak

        cp -r "$frontend_dir/dist/"* "$LG_INSTALL_DIR/public/"

        [[ -f /tmp/lg_index.php.bak ]] && {
            cp /tmp/lg_index.php.bak "$LG_INSTALL_DIR/public/index.php"
            rm -f /tmp/lg_index.php.bak
        }
        success "Frontend built and deployed to public/"
    else
        warn "Frontend build output not found."
    fi
}

configure_laravel() {
    step "Configuring Laravel"

    cd "$LG_INSTALL_DIR"

    # Generate .env if missing
    if [[ ! -f .env ]]; then
        [[ -f .env.example ]] || fatal ".env.example not found."
        cp .env.example .env
    fi

    # Write .env values
    _update_env "APP_NAME" "\"Open Looking Glass\""
    _update_env "APP_ENV" "production"
    _update_env "APP_DEBUG" "false"
    _update_env "APP_URL" "${APP_URL:-http://localhost}"
    _update_env "DB_CONNECTION" "$DB_CONNECTION"

    if [[ "$DB_CONNECTION" != "sqlite" ]]; then
        _update_env "DB_HOST" "$DB_HOST"
        _update_env "DB_PORT" "$DB_PORT"
        _update_env "DB_DATABASE" "$DB_DATABASE"
        _update_env "DB_USERNAME" "$DB_USERNAME"
        _update_env "DB_PASSWORD" "$DB_PASSWORD"
    fi

    _update_env "SESSION_DRIVER" "redis"
    _update_env "CACHE_STORE" "redis"
    _update_env "QUEUE_CONNECTION" "redis"
    _update_env "REDIS_HOST" "${REDIS_HOST:-127.0.0.1}"
    _update_env "REDIS_PORT" "${REDIS_PORT:-6379}"
    [[ -n "${REDIS_PASSWORD:-}" ]] && _update_env "REDIS_PASSWORD" "$REDIS_PASSWORD"

    # Generate APP_KEY
    php artisan key:generate --no-interaction 2>&1

    # Migrate and seed
    info "Running database migrations..."
    php artisan migrate --force --no-interaction 2>&1 | tail -5

    info "Seeding database..."
    php artisan db:seed --force --no-interaction 2>&1 | tail -3 || warn "Seeding skipped"

    # Cache config and routes
    php artisan config:cache --no-interaction 2>&1
    php artisan route:cache --no-interaction 2>&1

    # Permissions
    chown -R www-data:www-data "$LG_INSTALL_DIR"
    chmod -R 755 "$LG_INSTALL_DIR"
    chmod -R 775 "$LG_INSTALL_DIR/storage" 2>/dev/null || true
    chmod -R 775 "$LG_INSTALL_DIR/bootstrap/cache" 2>/dev/null || true

    success "Laravel configured"
}

setup_laravel_services() {
    step "Setting up Laravel Services"

    cat > /etc/systemd/system/lg-worker.service << EOF
[Unit]
Description=Open Looking Glass Queue Worker
After=network.target redis.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=$LG_INSTALL_DIR
ExecStart=/usr/bin/php $LG_INSTALL_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    cat > /etc/systemd/system/lg-scheduler.service << EOF
[Unit]
Description=Open Looking Glass Scheduler

[Service]
User=www-data
Group=www-data
WorkingDirectory=$LG_INSTALL_DIR
ExecStart=/usr/bin/php $LG_INSTALL_DIR/artisan schedule:work
Restart=always
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
EOF

    systemctl daemon-reload
    service_enable_start lg-worker
    service_enable_start lg-scheduler
    success "Queue worker and scheduler services installed"
}

_update_env() {
    local key="$1" value="$2" env_file="$LG_INSTALL_DIR/.env"
    if grep -q "^${key}=" "$env_file" 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$env_file"
    else
        echo "${key}=${value}" >> "$env_file"
    fi
}
