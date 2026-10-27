<?php
/**
 * The plugins the CMS needs (the ones the ACore plugin lists as required): installed from wordpress.org when
 * missing and activated - for the whole network on multisite.
 *
 * Happens on any page load (no user input is involved: the list below, from wordpress.org only). A failed download is
 * retried at most every 10 minutes; the reason is shown on the ACore Development page.
 */

defined('ABSPATH') || exit;

/** slug => [name, main file]. Filter: acore_theme_required_plugins. */
function acore_theme_required_plugins()
{
    return apply_filters('acore_theme_required_plugins', [
        'woocommerce' => ['WooCommerce', 'woocommerce/woocommerce.php'],
        'mycred'      => ['myCred', 'mycred/mycred.php'],
        'wp-2fa'      => ['WP 2FA', 'wp-2fa/wp-2fa.php'],
    ]);
}

/**
 * Pinned releases for older WordPress versions: slug => [ WordPress below this version => release to install ].
 * The latest release is installed when WordPress is new enough. From each release's readme ("Requires at least"):
 * WooCommerce 11.1+ needs WordPress 7.0, 10.8.x - 11.0.x need 6.9, 10.5.x - 10.7.0 need 6.8.
 * Filter: acore_theme_plugin_fallbacks.
 */
function acore_theme_plugin_fallbacks()
{
    return apply_filters('acore_theme_plugin_fallbacks', [
        'woocommerce' => [
            '6.9' => '10.7.0',   // WordPress 6.8.x
            '7.0' => '11.0.1',   // WordPress 6.9.x
        ],
    ]);
}

/** The release to install for this WordPress: a pinned version, or "latest". */
function acore_theme_plugin_version_for_wordpress($slug)
{
    $fallbacks = acore_theme_plugin_fallbacks();
    if (empty($fallbacks[$slug])) {
        return 'latest';
    }
    $wp = get_bloginfo('version');
    $below = $fallbacks[$slug];
    uksort($below, 'version_compare');
    foreach ($below as $wpBelow => $release) {
        if (version_compare($wp, $wpBelow, '<')) {
            return $release;
        }
    }
    return 'latest';
}

function acore_theme_load_plugin_admin()
{
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/** installed | active | missing, for a required plugin's main file. */
function acore_theme_plugin_state($file)
{
    acore_theme_load_plugin_admin();
    if (is_plugin_active($file) || (is_multisite() && is_plugin_active_for_network($file))) {
        return 'active';
    }
    return array_key_exists($file, get_plugins()) ? 'installed' : 'missing';
}

/** Downloads a plugin from wordpress.org. True when it is installed afterwards. */
function acore_theme_install_plugin($slug, $file, &$log)
{
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    if (!(defined('WP_CLI') && WP_CLI) && get_transient('acore_theme_install_wait_' . $slug)) {
        return false;   // failed a few minutes ago: don't slow every page load down (WP-CLI always tries)
    }
    set_transient('acore_theme_install_wait_' . $slug, 1, 10 * MINUTE_IN_SECONDS);

    if (function_exists('set_time_limit')) {
        @set_time_limit(300);   // a plugin download can take longer than PHP's default 30 seconds
    }

    if (get_filesystem_method() !== 'direct') {
        $message = 'WordPress can\'t write to wp-content/plugins (file permissions)';
    } else {
        // the latest release, or the pinned one for an older WordPress (acore_theme_plugin_fallbacks)
        $version  = acore_theme_plugin_version_for_wordpress($slug);
        $package  = 'https://downloads.wordpress.org/plugin/' . rawurlencode($slug)
            . ($version === 'latest' ? '.latest-stable.zip' : '.' . rawurlencode($version) . '.zip');
        $skin     = new Automatic_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader($skin);
        $result   = $upgrader->install($package);
        if (!is_wp_error($result) && $result) {
            wp_clean_plugins_cache();
            delete_transient('acore_theme_install_wait_' . $slug);
            acore_theme_plugin_error($slug, null);
            $log[] = "Plugin installed: $slug" . ($version === 'latest' ? '' : " $version (pinned for WordPress " . get_bloginfo('version') . ')');
            return array_key_exists($file, get_plugins());
        }
        $message = is_wp_error($result) ? $result->get_error_message() : implode(' ', array_map('wp_strip_all_tags', (array) $skin->get_upgrade_messages()));
    }
    acore_theme_plugin_error($slug, $message ?: 'download failed');
    $log[] = "Plugin $slug could not be installed: " . ($message ?: 'download failed');
    return false;
}

/** Remembers (or clears, with null) why a plugin couldn't be installed, for the info page. */
function acore_theme_plugin_error($slug, $message = false)
{
    $errors = get_option('acore_theme_plugin_errors', []);
    $errors = is_array($errors) ? $errors : [];
    if ($message === false) {
        return isset($errors[$slug]) ? $errors[$slug] : '';
    }
    if ($message === null) {
        unset($errors[$slug]);
    } else {
        $errors[$slug] = $message;
    }
    update_option('acore_theme_plugin_errors', $errors, false);
    return '';
}


/**
 * Installs and activates the required plugins ($allowInstall is kept for callers; installing always happens). Returns true when all of them are active now
 * (a plugin activated during this request is only loaded from the next one).
 */
function acore_theme_ensure_plugins(&$log, $allowInstall)
{
    $allActive = true;
    foreach (acore_theme_required_plugins() as $slug => $plugin) {
        list($name, $file) = $plugin;
        $state = acore_theme_plugin_state($file);
        if ($state === 'active') {
            continue;
        }
        $allActive = false;
        if ($state === 'missing') {
            if (!acore_theme_install_plugin($slug, $file, $log)) {
                continue;
            }
        }
        $result = activate_plugin($file, '', is_multisite());
        if (is_wp_error($result)) {
            $log[] = "$name could not be activated: " . $result->get_error_message();
        } else {
            $log[] = "$name activated" . (is_multisite() ? ' for the network' : '') . '.';
        }
    }
    return $allActive;
}

/** WooCommerce ready to use: no setup wizard, no "coming soon" page, accounts needed (the services use the login). */
function acore_theme_woocommerce_defaults(&$log)
{
    $defaults = [
        'woocommerce_coming_soon'                      => 'no',
        'woocommerce_store_pages_only'                 => 'no',
        'woocommerce_task_list_hidden'                 => 'yes',
        'woocommerce_extended_task_list_hidden'        => 'yes',
        'woocommerce_enable_guest_checkout'            => 'no',
        'woocommerce_enable_checkout_login_reminder'   => 'yes',
        'woocommerce_enable_signup_and_login_from_checkout' => 'no',
        'woocommerce_allow_tracking'                   => 'no',
        'woocommerce_show_marketplace_suggestions'     => 'no',
    ];
    foreach ($defaults as $option => $value) {
        if (get_option($option) !== $value) {
            update_option($option, $value);
        }
    }
    $profile = get_option('woocommerce_onboarding_profile', []);
    if (!is_array($profile) || empty($profile['skipped']) && empty($profile['completed'])) {
        update_option('woocommerce_onboarding_profile', array_merge(is_array($profile) ? $profile : [], ['skipped' => true]));
    }
    $log[] = 'WooCommerce settings: setup wizard skipped, store open (no "coming soon"), checkout needs an account.';
}

/** Switching to this theme installs what's missing right away (the admin is the one switching). */
add_action('after_switch_theme', function () {
    $log = [];
    acore_theme_ensure_plugins($log, true);
    if (!empty($log)) {
        error_log('ACore Development plugins: ' . implode(' | ', $log));
    }
});
