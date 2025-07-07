#!/bin/bash

# Function to check if a plugin is installed
is_plugin_installed() {
    wp plugin is-installed "$1" --allow-root
    return $?
}

is_theme_installed() {
    wp theme is-installed "$1" --allow-root
    return $?
}

# Function to install and activate a plugin if not already installed
install_and_activate_plugin() {
    local plugin_name="$1"
    local plugin_source="$2"

    # Extract plugin slug from source for checking installation
    # For URLs and file paths, we need to get the actual plugin slug after installation
    local plugin_slug="$plugin_source"
    
    # If it's a URL or file path, we'll check after installation
    if [[ "$plugin_source" == http* ]] || [[ "$plugin_source" == /* ]]; then
        echo "Installing and activating plugin: $plugin_name from $plugin_source..."
        wp plugin install "$plugin_source" --activate --allow-root
    else
        # It's a WordPress repository slug
        if ! is_plugin_installed "$plugin_slug"; then
            echo "Installing and activating plugin: $plugin_name..."
            wp plugin install "$plugin_slug" --activate --allow-root
        else
            echo "Plugin $plugin_name is already installed."
        fi
    fi
}

install_and_activate_theme() {
    local theme_name="$1"
    local theme_source="$2"

    echo "Installing and activating theme: $theme_name from $theme_source..."

    if [[ "$theme_source" == http* ]] || [[ "$theme_source" == /* ]]; then
        # If it's a URL or file path, install it directly
        wp theme install "$theme_source" --activate --allow-root
    else
        # It's a WordPress repository slug
        if ! is_theme_installed "$theme_source"; then
            wp theme install "$theme_source" --activate --allow-root
        else
            echo "Theme $theme_name is already installed."
        fi
    fi
}

# Special handling for plugin activations that should only be done once
handle_plugin_activation_once() {
    local plugin_slug="$1"
    local activation_flag="/usr/src/wordpress/.${plugin_slug}.activated"

    if [ -f "$activation_flag" ]; then
        echo "Plugin $1 has already been activated; skipping."
        return
    fi

    if is_plugin_installed "$plugin_slug"; then
        if ! wp plugin is-active "$plugin_slug" --allow-root; then
            echo "Activating plugin: $1..."
            wp plugin activate "$plugin_slug" --allow-root
            # Mark as activated
            touch "$activation_flag"
        else
            echo "Plugin $1 is already active."
            # Mark as activated if not marked already
            touch "$activation_flag"
        fi
    else
        echo "Plugin $1 is not installed; skipping."
    fi
}

