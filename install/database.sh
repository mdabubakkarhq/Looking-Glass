#!/usr/bin/env bash
# install/database.sh — PostgreSQL or MariaDB installation
# Source common.sh before calling functions here.

setup_database() {
    local db_choice="${1:-}"
    if [[ -z "$db_choice" ]]; then
        db_choice=$(select_option "Database engine:" "PostgreSQL (recommended)" "MariaDB" "SQLite (development only)")
    fi

    case "$db_choice" in
        1) install_postgresql; DB_CONNECTION="pgsql" ;;
        2) install_mariadb;    DB_CONNECTION="mysql" ;;
        3) install_sqlite;     DB_CONNECTION="sqlite" ;;
        *) fatal "Invalid database choice." ;;
    esac
}

install_postgresql() {
    step "Installing PostgreSQL"
    apt_install postgresql postgresql-client

    service_enable_start postgresql

    local DB_NAME="looking_glass"
    local DB_USER="looking_glass"
    local DB_PASS
    DB_PASS=$(random_string 32)

    info "Creating database and user..."
    sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='$DB_USER'" | grep -q 1 \
        || sudo -u postgres psql -c "CREATE USER $DB_USER WITH PASSWORD '$DB_PASS';" >/dev/null 2>&1
    sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='$DB_NAME'" | grep -q 1 \
        || sudo -u postgres psql -c "CREATE DATABASE $DB_NAME OWNER $DB_USER;" >/dev/null 2>&1
    sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;" >/dev/null 2>&1

    success "PostgreSQL configured"
    info "Database: $DB_NAME | User: $DB_USER"

    # Export for .env generation
    export DB_HOST="127.0.0.1" DB_PORT="5432" DB_DATABASE="$DB_NAME"
    export DB_USERNAME="$DB_USER" DB_PASSWORD="$DB_PASS"
}

install_mariadb() {
    step "Installing MariaDB"
    apt_install mariadb-server mariadb-client

    service_enable_start mariadb

    local DB_NAME="lookingglass"
    local DB_USER="lg"
    local DB_PASS
    DB_PASS=$(random_string 32)

    info "Creating database and user..."
    mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`;" 2>/dev/null
    mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';" 2>/dev/null
    mysql -e "GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost';" 2>/dev/null
    mysql -e "FLUSH PRIVILEGES;" 2>/dev/null

    success "MariaDB configured"
    info "Database: $DB_NAME | User: $DB_USER"

    export DB_HOST="127.0.0.1" DB_PORT="3306" DB_DATABASE="$DB_NAME"
    export DB_USERNAME="$DB_USER" DB_PASSWORD="$DB_PASS"
}

install_sqlite() {
    step "Setting up SQLite"
    apt_install php8.3-sqlite3 sqlite3

    local DB_PATH="$LG_INSTALL_DIR/database/database.sqlite"
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"

    success "SQLite database created at $DB_PATH"
    export DB_HOST="" DB_PORT="" DB_DATABASE="$DB_PATH"
    export DB_USERNAME="" DB_PASSWORD=""
}
