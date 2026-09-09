#!/usr/bin/env bash
# install/ssl.sh — SSL/TLS certificate setup

setup_ssl() {
    local domain="$1"
    local ssl_choice="${2:-}"

    if [[ -z "$ssl_choice" ]]; then
        ssl_choice=$(select_option "SSL/TLS:" \
            "Let's Encrypt (free, automatic)" \
            "Existing certificate (provide paths)" \
            "No SSL / reverse proxy managed externally")
    fi

    case "$ssl_choice" in
        1) setup_letsencrypt "$domain" ;;
        2) setup_existing_cert "$domain" ;;
        3) info "Skipping SSL. Configure your reverse proxy to terminate TLS." ;;
        *) fatal "Invalid SSL choice." ;;
    esac
}

setup_letsencrypt() {
    local domain="$1"
    [[ "$domain" == "_" || "$domain" == "localhost" ]] && {
        warn "Cannot issue Let's Encrypt certificate for '$domain'."
        warn "Please set a real domain first. Skipping SSL."
        return
    }

    step "Setting up Let's Encrypt"
    apt_install certbot python3-certbot-nginx

    info "Requesting certificate for $domain..."
    if certbot --nginx -d "$domain" --non-interactive --agree-tos \
        --email "admin@${domain}" --redirect 2>&1; then
        success "Let's Encrypt certificate installed for $domain"

        # Set up auto-renewal cron
        if ! crontab -l 2>/dev/null | grep -q "certbot renew"; then
            (crontab -l 2>/dev/null; echo "0 3 * * * certbot renew --quiet --post-hook 'systemctl reload nginx'") | crontab -
            success "Auto-renewal cron job added"
        fi
    else
        warn "Let's Encrypt setup failed. You can retry later with: certbot --nginx -d $domain"
    fi
}

setup_existing_cert() {
    local domain="$1"
    local cert_path key_path

    cert_path=$(ask "Full path to certificate file (PEM)")
    key_path=$(ask "Full path to private key file (PEM)")

    [[ -f "$cert_path" ]] || fatal "Certificate file not found: $cert_path"
    [[ -f "$key_path" ]]  || fatal "Private key file not found: $key_path"

    # Update nginx config to use SSL
    local fpm_sock="/run/php/php8.3-fpm.sock"
    [[ -S "$fpm_sock" ]] || fpm_sock="/run/php/php8.2-fpm.sock"

    cat > /etc/nginx/sites-available/looking-glass << NGINX
server {
    listen 80;
    server_name ${domain};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ${domain};
    root ${LG_INSTALL_DIR}/public;
    index index.php index.html;
    charset utf-8;
    client_max_body_size 20M;

    ssl_certificate ${cert_path};
    ssl_certificate_key ${key_path};
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ ^/(api|admin|sanctum)/ {
        try_files \$uri /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass unix:${fpm_sock};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 120;
    }

    location ~ /\.(?!well-known).* { deny all; }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
}
NGINX

    nginx -t 2>&1 && systemctl restart nginx
    success "SSL configured with existing certificate"
}
