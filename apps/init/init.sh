#!/bin/bash

# List of plugins to install
declare -A plugins=(
    ["WooCommerce"]="woocommerce"
    ["WPGraphQL"]="wp-graphql"
    ["WPGraphQL ACF"]="wpgraphql-acf"
    ["myCred"]="mycred"
    ["Advanced Custome Fields"]="advanced-custom-fields"
)

# List of plugins to activate only once
declare -A plugins_once=(
    ["ACore WP Plugins"]="acore-wp-plugins"
)

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Wait for the database to be available
echo "Waiting for database to be ready..."
until nc -z -v -w30 wp-db 3306; do
    echo "Waiting for database connection..."
    sleep 5
done
echo "Database is ready!"

# Hand over control to the main container process
bash /usr/local/bin/docker-entrypoint.sh echo done

# Create the WordPress configuration file if it doesn't exist
if [ ! -f /var/www/html/wp-config.php ]; then
    echo "Creating wp-config.php..."
    wp config create --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --dbhost=wp-db --allow-root
fi

# Perform the initial WordPress installation if it hasn't been installed yet
if ! wp core is-installed --allow-root; then
    echo "Installing WordPress..."
    wp core install --url="http://172.23.51.120/" --title="ACore CMS" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.com" --allow-root
fi

# Install and activate plugins
echo "Installing and activating plugins..."

source "$CURPATH/init.lib.sh"

# Install and activate each plugin in the list
for plugin_name in "${!plugins[@]}"; do
    install_and_activate_plugin "$plugin_name" "${plugins[$plugin_name]}"
done

# Handle Acore WP Plugins activation
for plugin_name in "${!plugins_once[@]}"; do
    handle_plugin_activation_once "${plugins_once[$plugin_name]}"
done

# Correct permissions for non-root operations
chown -R www-data:www-data /run /var/www/html/

# Start a proxy from 127.0.0.1:6379 to the Redis container
socat TCP-LISTEN:6379,fork TCP:redis:6379 &

exec "php-fpm"
