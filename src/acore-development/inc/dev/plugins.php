<?php
/**
 * The plugins the CMS needs (the ones the ACore plugin lists as required): installed from wordpress.org when
 * missing and activated - for the whole network on multisite.
 *
 * Activating an installed plugin happens on any request; downloading one needs a user who may install plugins
 * (an administrator), WP-CLI (wp acore-dev setup) or switching to this theme.
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

    $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
    $result   = $upgrader->install('https://downloads.wordpress.org/plugin/' . rawurlencode($slug) . '.latest-stable.zip');
    if (is_wp_error($result) || !$result) {
        $log[] = "Plugin $slug could not be installed: " . (is_wp_error($result) ? $result->get_error_message() : 'download or file permissions failed');
        return false;
    }
    wp_clean_plugins_cache();
    $log[] = "Plugin installed: $slug";
    return array_key_exists($file, get_plugins());
}

/**
 * Installs ($allowInstall) and activates the required plugins. Returns true when all of them are active now
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
            if (!$allowInstall) {
                $log[] = "$name is not installed: an administrator opening the site (or wp acore-dev setup) installs it.";
                continue;
            }
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
    acore_theme_ensure_plugins($log, current_user_can('install_plugins'));
    if (!empty($log)) {
        error_log('ACore Development plugins: ' . implode(' | ', $log));
    }
});
