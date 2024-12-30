#!/bin/bash

# Function to check if a plugin is installed
is_plugin_installed() {
    wp plugin is-installed "$1" --allow-root
    return $?
}

# Function to install and activate a plugin if not already installed
install_and_activate_plugin() {
    local plugin_name="$1"
    local plugin_slug="$2"

    if ! is_plugin_installed "$plugin_slug"; then
        echo "Installing and activating plugin: $plugin_name..."
        wp plugin install "$plugin_slug" --activate --allow-root
    else
        echo "Plugin $plugin_name is already installed."
    fi
}

# Special handling for plugin activations that should only be done once
handle_plugin_activation_once() {
    local plugin_slug=$1
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

