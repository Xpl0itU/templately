#!/bin/bash
set -euo pipefail

DB_HOST="${database_default_hostname:-mysql}"
DB_NAME="${database_default_database:-templately}"
DB_USER="${database_default_username:-admin}"
DB_PASS="${database_default_password:-}"
DB_DRIVER="${database_default_DBDriver:-MySQLi}"
DB_PORT="${database_default_port:-3306}"

# Generate .env file from Docker environment variables
cat > /var/www/html/.env <<EOF
#--------------------------------------------------------------------
# ENVIRONMENT
#--------------------------------------------------------------------

CI_ENVIRONMENT = ${CI_ENVIRONMENT:-development}

#--------------------------------------------------------------------
# APP
#--------------------------------------------------------------------

app.baseURL = 'http://localhost:8080'

#--------------------------------------------------------------------
# DATABASE
#--------------------------------------------------------------------

database.default.hostname = ${DB_HOST}
database.default.database = ${DB_NAME}
database.default.username = ${DB_USER}
database.default.password = ${DB_PASS}
database.default.DBDriver = ${DB_DRIVER}
database.default.port = ${DB_PORT}

EOF

echo ".env file generated with Docker environment variables"
cat /var/www/html/.env

# Ensure writable subdirectories exist
mkdir -p /var/www/html/writable/cache
mkdir -p /var/www/html/writable/logs
mkdir -p /var/www/html/writable/session
mkdir -p /var/www/html/writable/uploads
mkdir -p /var/www/html/writable/debugbar

# Set proper permissions
chown www-data:www-data /var/www/html/.env
chown -R www-data:www-data /var/www/html/writable
chmod -R 775 /var/www/html/writable

# Wait for MySQL to be ready
echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
MYSQL_READY=0
for i in {1..30}; do
    if mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" ${DB_PASS:+-p"${DB_PASS}"} --silent; then
        echo "MySQL is ready."
        MYSQL_READY=1
        break
    else
        echo "MySQL not ready yet (attempt ${i}/30)."
        sleep 2
    fi
done

if [ "${MYSQL_READY}" -ne 1 ]; then
    echo "Warning: Unable to confirm MySQL is ready after waiting. Continuing anyway."
fi

# Run migrations automatically (non-fatal on failure)
echo "Running database migrations..."
if ! php spark migrate --all; then
    echo "Warning: Migrations failed or are already up to date. Check logs for details." >&2
fi

# Start Apache
exec apache2-foreground
