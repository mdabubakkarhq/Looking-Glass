#!/usr/bin/env bash
# install/nginx.sh — NGINX configuration for Open Looking Glass

setup_nginx() {
    local domain="${1:-_}"
    step "Configuring NGINX"

    apt_install nginx

    # Determine PHP-FPM socket path
    local fpm_sock="/run/php/php8.3-fpm.sock"
    [[ -S "$fpm_sock" ]] || fpm_sock="/run/php/php8.2-fpm.sock"

    # Write site config
    cat > /etc/nginx/sites-available/looking-glass << NGINX
server {
    listen 80;
    server_name ${domain};
    root ${LG_INSTALL_DIR}/public;
    index index.php index.html;
    charset utf-8;
    client_max_body_size 20M;

    # SPA catch-all: serve index.html for non-file, non-API routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # API and admin routes go to Laravel
    location ~ ^/(api|admin|sanctum)/ {
        try_files \$uri /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:${fpm_sock};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Static assets with aggressive caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
NGINX

    ln -sf /etc/nginx/sites-available/looking-glass /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default

    nginx -t 2>&1 && systemctl restart nginx
    service_enable_start nginx

    success "NGINX configured for domain: $domain"
}
