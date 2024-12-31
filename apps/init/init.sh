#!/bin/bash

# List of plugins to install
# Array semplice con nome e slug separati da "|"
plugins_install=(
    "WooCommerce|woocommerce"
    "WPGraphQL|wp-graphql"
    "WPGraphQL ACF|wpgraphql-acf"
    "myCred|mycred"
    "Advanced Custom Fields|advanced-custom-fields"
)

# List of plugins to activate only once
plugins_activate_only=(
    "ACore WP Plugins|acore-wp-plugins"
)

CURPATH="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

# Wait for the database to be available
echo "Waiting for database to be ready..."
until nc -z -v -w30 wp-db 3306; do
    echo "Waiting for database connection..."
    sleep 5
done
echo "Database is ready!"

# Execute the entrypoint script with the php-fpm command (use -v to only print the version and exit)
bash /usr/local/bin/docker-entrypoint.sh "php-fpm" "-v"

# Create the WordPress configuration file if it doesn't exist
if [ ! -f /var/www/html/wp-config.php ]; then
    echo "Creating wp-config.php..."
    wp config create --dbname=wordpress --dbuser=wordpress --dbpass=wordpress --dbhost=wp-db --allow-root
fi

# Perform the initial WordPress installation if it hasn't been installed yet
if ! wp core is-installed --allow-root; then
    echo "Installing WordPress..."
    wp core install --url="$WORDPRESS_URL" --title="$WORDPRESS_TITLE" --admin_user="$WORDPRESS_ADMIN_USER" --admin_password="$WORDPRESS_ADMIN_PASSWORD" --admin_email="$WORDPRESS_ADMIN_EMAIL"  --allow-root
fi

wp maintenance-mode activate --allow-root

# Install and activate plugins
echo "Installing and activating plugins..."

source "$CURPATH/init.lib.sh"

# Install and activate each plugin in the list
for plugin in "${plugins_install[@]}"; do
    # Dividi il nome e lo slug
    IFS='|' read -r plugin_name plugin_slug <<< "$plugin"
    
    echo "Installing $plugin_name ($plugin_slug)..."
    install_and_activate_plugin "$plugin_name" "$plugin_slug"
done

# Handle Acore WP Plugins activation
for plugin in "${plugins_activate_only[@]}"; do
    IFS='|' read -r plugin_name plugin_slug <<< "$plugin"
    
    echo "Activating $plugin_name ($plugin_slug) only once..."
    handle_plugin_activation_once "$plugin_slug"
done

# Correct permissions for non-root operations
chown -R www-data:www-data /run /var/www/html/

setfacl -R -m u:$DOCKER_USER_ID:rwx /var/www/html/
setfacl -R -d -m u:$DOCKER_USER_ID:rwx /var/www/html/

# Start a proxy from 127.0.0.1:6379 to the Redis container
socat TCP-LISTEN:6379,fork TCP:redis:6379 &

wp maintenance-mode deactivate --allow-root

exec "php-fpm"
