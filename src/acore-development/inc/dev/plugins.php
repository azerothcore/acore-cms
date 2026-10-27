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

    if (get_transient('acore_theme_install_wait_' . $slug)) {
        return false;   // failed a few minutes ago: don't slow every page load down
    }
    set_transient('acore_theme_install_wait_' . $slug, 1, 10 * MINUTE_IN_SECONDS);

    if (get_filesystem_method() !== 'direct') {
        $message = 'WordPress can\'t write to wp-content/plugins (file permissions)';
    } else {
        // the latest release first; when it needs a newer WordPress / PHP, the newest older releases (a few tries)
        $packages = array_merge(
            ['latest' => 'https://downloads.wordpress.org/plugin/' . rawurlencode($slug) . '.latest-stable.zip'],
            acore_theme_older_plugin_packages($slug, 4)
        );
        $message = '';
        foreach ($packages as $version => $package) {
            $skin     = new Automatic_Upgrader_Skin();
            $upgrader = new Plugin_Upgrader($skin);
            $result   = $upgrader->install($package);
            if (!is_wp_error($result) && $result) {
                wp_clean_plugins_cache();
                delete_transient('acore_theme_install_wait_' . $slug);
                acore_theme_plugin_error($slug, null);
                $log[] = "Plugin installed: $slug" . ($version === 'latest' ? '' : " $version (the latest needs a newer WordPress / PHP)");
                return array_key_exists($file, get_plugins());
            }
            $message = is_wp_error($result) ? $result->get_error_message() : implode(' ', array_map('wp_strip_all_tags', (array) $skin->get_upgrade_messages()));
            // only a "requires WordPress / PHP x" refusal is worth trying an older release for
            if (!preg_match('/requires|required|version/i', $message)) {
                break;
            }
        }
    }
    acore_theme_plugin_error($slug, $message ?: 'download failed');
    $log[] = "Plugin $slug could not be installed: " . ($message ?: 'download failed');
    return false;
}

/**
 * Download links of a plugin's older stable releases, newest first: the newest patch of each of the $count previous
 * major.minor versions (e.g. 10.3.2, 10.2.4, ...). From the wordpress.org plugin API.
 */
function acore_theme_older_plugin_packages($slug, $count)
{
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    $info = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['versions' => true, 'sections' => false]]);
    if (is_wp_error($info) || empty($info->versions) || !is_array($info->versions)) {
        return [];
    }
    $latest  = isset($info->version) ? $info->version : '';
    $byMinor = [];
    foreach ($info->versions as $version => $link) {
        if (!preg_match('/^(\d+)\.(\d+)(\.\d+)*$/', (string) $version, $m) || version_compare($version, $latest, '>=')) {
            continue;   // trunk, betas, release candidates, the latest itself
        }
        $minor = $m[1] . '.' . $m[2];
        if (!isset($byMinor[$minor]) || version_compare($version, $byMinor[$minor][0], '>')) {
            $byMinor[$minor] = [$version, $link];
        }
    }
    uksort($byMinor, function ($a, $b) {
        return version_compare($b, $a);
    });
    $packages = [];
    $latestMinor = preg_match('/^(\d+\.\d+)/', $latest, $lm) ? $lm[1] : '';
    foreach ($byMinor as $minor => $release) {
        if ($minor === $latestMinor && count($byMinor) > 1) {
            // older patches of the latest minor usually need the same WordPress: start with the previous minors
            continue;
        }
        $packages[$release[0]] = $release[1];
        if (count($packages) >= $count) {
            break;
        }
    }
    return $packages;
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
