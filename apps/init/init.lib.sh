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

# Special handling for theme activations that should only be done once
handle_theme_activation_once() {
    local theme_slug="$1"
    local activation_flag="/usr/src/wordpress/.${theme_slug}.theme.activated"

    if [ -f "$activation_flag" ]; then
        echo "Theme $1 has already been activated; skipping."
        return
    fi

    if is_theme_installed "$theme_slug"; then
        # Check if theme is active
        if ! wp theme status "$theme_slug" --allow-root | grep -q 'Active'; then
            echo "Activating theme: $1..."
            wp theme activate "$theme_slug" --allow-root
            touch "$activation_flag"
        else
            echo "Theme $1 is already active."
            touch "$activation_flag"
        fi
    else
        echo "Theme $1 is not installed; skipping."
    fi
}

# Function to batch install and activate plugins
batch_install_and_activate_plugins() {
    local plugins_list=("${@}")
    local plugins_to_install=()
    local plugin_sources=()

    # Function for parallel check
    check_plugin_installed() {
        local plugin="$1"
        IFS='|' read -r plugin_name plugin_slug plugin_source <<< "$plugin"
        if [ -z "$plugin_source" ]; then
            plugin_source=$plugin_slug
        fi
        if ! is_plugin_installed "$plugin_slug"; then
            echo "$plugin"
        fi
    }

    if command -v xargs >/dev/null 2>&1; then
        export -f is_plugin_installed
        export -f check_plugin_installed
        mapfile -t not_installed < <(
            printf "%s\n" "${plugins_list[@]}" | xargs -P4 -I{} bash -c 'check_plugin_installed "$@"' _ {}
        )
    else
        echo "Warning: xargs not found, falling back to sequential check. Please install xargs for better performance."
        not_installed=()
        for plugin in "${plugins_list[@]}"; do
            IFS='|' read -r plugin_name plugin_slug plugin_source <<< "$plugin"
            if [ -z "$plugin_source" ]; then
                plugin_source=$plugin_slug
            fi
            if ! is_plugin_installed "$plugin_slug"; then
                not_installed+=("$plugin")
            fi
        done
    fi

    for plugin in "${not_installed[@]}"; do
        IFS='|' read -r plugin_name plugin_slug plugin_source <<< "$plugin"
        if [ -z "$plugin_source" ]; then
            plugin_source=$plugin_slug
        fi
        plugins_to_install+=("$plugin_slug")
        plugin_sources+=("$plugin_source")
        echo "Adding $plugin_name ($plugin_source) to batch installation..."
    done

    if [ ${#plugins_to_install[@]} -gt 0 ]; then
        echo "Installing plugins: ${plugin_sources[*]}..."
        for i in "${!plugins_to_install[@]}"; do
            wp plugin install "${plugin_sources[$i]}" --activate --allow-root || echo "Failed to install ${plugin_sources[$i]}"
        done
    else
        echo "All plugins are already installed."
    fi
}

# Function to batch install and activate themes
batch_install_and_activate_themes() {
    local themes_list=("${@}")
    local themes_to_install=()
    local theme_sources=()

    for theme in "${themes_list[@]}"; do
        IFS='|' read -r theme_name theme_slug theme_source <<< "$theme"
        if [ -z "$theme_source" ]; then
            theme_source=$theme_slug
        fi

        if ! is_theme_installed "$theme_slug"; then
            echo "Adding $theme_name ($theme_source) to batch installation..."
            themes_to_install+=("$theme_slug")
            theme_sources+=("$theme_source")
        else
            echo "Theme $theme_name is already installed. Skipping installation."
        fi
    done

    if [ ${#themes_to_install[@]} -gt 0 ]; then
        echo "Installing themes: ${theme_sources[*]}..."
        for i in "${!themes_to_install[@]}"; do
            wp theme install "${theme_sources[$i]}" --activate --allow-root || echo "Failed to install ${theme_sources[$i]}"
        done
    else
        echo "All themes are already installed."
    fi
}

