#!/bin/sh
set -e

# Ensure all upload directories are writable by www-data (Apache).
# This MUST run at container startup — Docker named volumes override
# any chown/chmod done during the image build step.
echo "[entrypoint] Setting upload directory permissions..."

# All subdirectories the PHP code writes to
mkdir -p \
    /var/www/html/public/uploads/incidents \
    /var/www/html/public/uploads/root_cause \
    /var/www/html/public/uploads/lessons_learned \
    /var/www/html/public/uploads/security \
    /var/www/html/public/uploads/fraud

chown -R www-data:www-data /var/www/html/public/uploads
chmod -R 775 /var/www/html/public/uploads

# Ensure logs directory is writable too
mkdir -p /var/www/html/config/logs
chown -R www-data:www-data /var/www/html/config/logs
chmod -R 775 /var/www/html/config/logs

echo "[entrypoint] Permissions set. Starting Apache..."

# Hand off to the official Apache entrypoint
exec docker-php-entrypoint apache2-foreground
