<?php
/**
 * [acore_dev_info]: what the CMS needs and what's set up - plugins, ACore settings, the game server, your game
 * account, the shop and a test product for every ACore service. The "ACore Development" front page shows it.
 *
 * Passwords and host names are never shown, only whether they are filled in.
 */

defined('ABSPATH') || exit;

add_action('init', function () {
    add_shortcode('acore_dev_info', 'acore_theme_dev_info_shortcode');
});

/** One row: [label, status (ok | warn | bad | info), text, optional link url, optional link text]. */
function acore_theme_dev_row($label, $status, $text, $url = '', $linkText = '')
{
    return compact('label', 'status', 'text', 'url', 'linkText');
}

/** The ACore plugin's services, or null when the plugin isn't active / can't connect. */
function acore_theme_dev_services(&$error = null)
{
    if (!class_exists('ACore\Manager\ACoreServices')) {
        $error = __('The ACore WP Plugins plugin is not active.', 'acore-development');
        return null;
    }
    try {
        return \ACore\Manager\ACoreServices::I();
    } catch (\Throwable $e) {
        $error = $e->getMessage();
        return null;
    }
}

function acore_theme_dev_info_site()
{
    $rows   = [];
    $isDev  = acore_theme_is_dev();
    $rows[] = acore_theme_dev_row(__('Environment', 'acore-development'), 'info', wp_get_environment_type()
        . ($isDev ? '' : ' - ' . __('set DOCKER_ENVIRONMENT=development in .env to let every user use the test payment', 'acore-development')));
    foreach (acore_theme_required_plugins() as $slug => $plugin) {
        list($name, $file) = $plugin;
        $state = acore_theme_plugin_state($file);
        $error = acore_theme_plugin_error($slug);
        $text  = $state === 'active' ? __('active', 'acore-development')
            : ($state === 'installed' ? __('installed, activated on the next page load', 'acore-development')
            : ($error !== '' ? sprintf(__('could not be installed: %s (retried every 10 minutes, or run wp acore-dev setup)', 'acore-development'), $error)
            : __('not installed yet: installed on the next page load', 'acore-development')));
        $rows[] = acore_theme_dev_row($name, $state === 'active' ? 'ok' : ($state === 'installed' ? 'warn' : 'bad'), $text);
    }
    $acore = class_exists('ACore\Manager\ACoreServices');
    $rows[] = acore_theme_dev_row(__('ACore WP Plugins', 'acore-development'), $acore ? 'ok' : 'bad',
        $acore ? __('active', 'acore-development') : __('not active: the services need it', 'acore-development'),
        $acore ? '' : admin_url('plugins.php'), __('Plugins', 'acore-development'));
    $rows[] = acore_theme_dev_row(__('Permalinks', 'acore-development'), get_option('permalink_structure') ? 'ok' : 'warn',
        get_option('permalink_structure') ? __('pretty permalinks', 'acore-development') : __('plain: some pages work better with pretty permalinks', 'acore-development'),
        get_option('permalink_structure') ? '' : admin_url('options-permalink.php'), __('Settings', 'acore-development'));
    return $rows;
}

function acore_theme_dev_info_acore()
{
    if (!class_exists('ACore\Manager\Opts')) {
        return [acore_theme_dev_row(__('Settings', 'acore-development'), 'bad', __('the ACore WP Plugins plugin is not active', 'acore-development'))];
    }
    $opts     = \ACore\Manager\Opts::I();
    $settings = defined('ACORE_SLUG') ? admin_url('admin.php?page=' . ACORE_SLUG . '-settings') : '';
    $rows     = [];

    $groups = [
        __('SOAP', 'acore-development')                => ['acore_soap_host', 'acore_soap_port', 'acore_soap_user', 'acore_soap_pass'],
        __('Auth database', 'acore-development')       => ['acore_db_auth_host', 'acore_db_auth_name', 'acore_db_auth_user'],
        __('Characters database', 'acore-development') => ['acore_db_char_host', 'acore_db_char_name', 'acore_db_char_user'],
        __('World database', 'acore-development')      => ['acore_db_world_host', 'acore_db_world_name', 'acore_db_world_user'],
    ];
    foreach ($groups as $label => $keys) {
        $missing = [];
        foreach ($keys as $key) {
            if (trim((string) $opts->$key) === '') {
                $missing[] = str_replace('acore_', '', $key);
            }
        }
        $rows[] = acore_theme_dev_row($label, empty($missing) ? 'ok' : 'bad',
            empty($missing) ? __('filled in', 'acore-development') : sprintf(__('missing: %s', 'acore-development'), implode(', ', $missing)),
            empty($missing) ? '' : $settings, __('Realm Settings', 'acore-development'));
    }

    // the game server, through SOAP (cached for a minute)
    $server = get_transient('acore_dev_server_info');
    if ($server === false) {
        $error = null;
        $services = acore_theme_dev_services($error);
        if ($services === null) {
            $server = ['bad', (string) $error];
        } else {
            try {
                // ".server info" answers "AzerothCore rev. ... / Connected players ... / Server uptime ...";
                // a SOAP error comes back as its message
                $reply  = $services->getServerSoap()->serverInfo();
                $text   = $reply instanceof \Throwable ? $reply->getMessage() : trim((string) $reply);
                $server = preg_match('/uptime|players/i', $text)
                    ? ['ok', trim(strtok($text, "\n"))]
                    : ['bad', $text !== '' ? $text : __('empty reply', 'acore-development')];
            } catch (\Throwable $e) {
                $server = ['bad', $e->getMessage()];
            }
        }
        set_transient('acore_dev_server_info', $server, MINUTE_IN_SECONDS);
    }
    $rows[] = acore_theme_dev_row(__('Game server (SOAP)', 'acore-development'), $server[0],
        $server[0] === 'ok' ? sprintf(__('answers: %s', 'acore-development'), $server[1]) : sprintf(__('no answer: %s', 'acore-development'), $server[1]));
    return $rows;
}

function acore_theme_dev_info_you()
{
    if (!is_user_logged_in()) {
        return [acore_theme_dev_row(__('Logged in', 'acore-development'), 'warn',
            __('no - the services use your WordPress login as the game account name', 'acore-development'), wp_login_url(get_permalink()), __('Log in', 'acore-development'))];
    }
    $user   = wp_get_current_user();
    $rows   = [acore_theme_dev_row(__('WordPress user', 'acore-development'), 'ok', $user->user_login . (current_user_can('manage_woocommerce') ? ' (' . __('administrator', 'acore-development') . ')' : ''))];
    $error  = null;
    $services = acore_theme_dev_services($error);
    if ($services === null) {
        $rows[] = acore_theme_dev_row(__('Game account', 'acore-development'), 'bad', (string) $error);
        return $rows;
    }
    try {
        $accountId = $services->getAcoreAccountIdByName($user->user_login);
        if (!$accountId) {
            $rows[] = acore_theme_dev_row(__('Game account', 'acore-development'), 'bad',
                sprintf(__('no game account named %s: create one with the same name (account create) to buy services', 'acore-development'), $user->user_login));
            return $rows;
        }
        $rows[] = acore_theme_dev_row(__('Game account', 'acore-development'), 'ok', sprintf('%s (ID: %d)', strtoupper($user->user_login), $accountId));
        $conn  = $services->getCharacterEm()->getConnection();
        $count = (int) $conn->executeQuery('SELECT COUNT(*) FROM `characters` WHERE `account` = ? AND `deleteDate` IS NULL', [$accountId])->fetchOne();
        $rows[] = acore_theme_dev_row(__('Characters', 'acore-development'), $count > 0 ? 'ok' : 'warn',
            $count > 0 ? sprintf(_n('%d character', '%d characters', $count, 'acore-development'), $count) : __('none: most services need a character', 'acore-development'));
    } catch (\Throwable $e) {
        $rows[] = acore_theme_dev_row(__('Game account', 'acore-development'), 'bad', sprintf(__('database error: %s', 'acore-development'), $e->getMessage()));
    }
    return $rows;
}

function acore_theme_dev_info_shop()
{
    if (!class_exists('WooCommerce')) {
        return [acore_theme_dev_row(__('Shop', 'acore-development'), 'bad', __('WooCommerce is not active', 'acore-development'))];
    }
    $rows  = [];
    $store = get_page_by_path('store');
    $rows[] = acore_theme_dev_row(__('Store page', 'acore-development'), $store ? 'ok' : 'warn',
        $store ? __('ready', 'acore-development') : __('missing (wp acore-dev setup)', 'acore-development'),
        $store ? get_permalink($store) : '', __('Open', 'acore-development'));
    foreach (['cart' => 'woocommerce_cart', 'checkout' => 'woocommerce_checkout'] as $page => $shortcode) {
        $id      = wc_get_page_id($page);
        $content = $id > 0 ? (string) get_post_field('post_content', $id) : '';
        $classic = strpos($content, '[' . $shortcode) !== false;
        $rows[]  = acore_theme_dev_row(ucfirst($page), $classic ? 'ok' : 'warn',
            $classic ? __('classic (the services\' fields work)', 'acore-development') : __('block version: the services add their fields to the classic one (wp acore-dev setup --force)', 'acore-development'),
            $id > 0 ? get_permalink($id) : '', __('Open', 'acore-development'));
    }
    $currency = get_woocommerce_currency();
    $points   = $currency === 'MYC' || (function_exists('mycred_point_type_exists') && mycred_point_type_exists($currency));
    $rows[]   = acore_theme_dev_row(__('Prices in', 'acore-development'), $points ? 'ok' : 'warn',
        $points ? sprintf(__('%s (myCred points, paid from the buyer\'s balance)', 'acore-development'), html_entity_decode(get_woocommerce_currency_symbol()) ?: $currency)
            : sprintf(__('%s - money, not points (wp acore-dev setup --force switches to points)', 'acore-development'), $currency),
        admin_url('admin.php?page=wc-settings'), __('Settings', 'acore-development'));
    $gateways = WC()->payment_gateways() ? WC()->payment_gateways()->get_available_payment_gateways() : [];
    $names    = array_map(function ($gateway) {
        return wp_strip_all_tags($gateway->get_title());
    }, $gateways);
    $rows[] = acore_theme_dev_row(__('Payment methods you can use', 'acore-development'), isset($gateways['acore_test']) ? 'ok' : (empty($gateways) ? 'bad' : 'warn'),
        empty($names) ? __('none', 'acore-development') : implode(', ', $names),
        admin_url('admin.php?page=wc-settings&tab=checkout'), __('Payments', 'acore-development'));
    $rows[] = acore_theme_dev_row(__('ACore test payment', 'acore-development'), 'info',
        acore_theme_is_dev() ? __('everyone can use it (development)', 'acore-development') : __('only administrators can use it (not development)', 'acore-development'));
    return $rows;
}

function acore_theme_dev_info_section($title, $rows)
{
    $labels = ['ok' => __('OK', 'acore-development'), 'warn' => __('Check', 'acore-development'), 'bad' => __('Missing', 'acore-development'), 'info' => __('Info', 'acore-development')];
    ?>
    <section class="acore-dev-section">
        <h2><?php echo esc_html($title); ?></h2>
        <table class="acore-dev-table">
            <?php foreach ($rows as $row) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($row['label']); ?></th>
                <td><span class="acore-dev-status acore-dev-<?php echo esc_attr($row['status']); ?>"><?php echo esc_html($labels[$row['status']]); ?></span></td>
                <td>
                    <?php echo esc_html($row['text']); ?>
                    <?php if ($row['url']) : ?>
                        <a href="<?php echo esc_url($row['url']); ?>"><?php echo esc_html($row['linkText'] ?: __('Open', 'acore-development')); ?></a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </section>
    <?php
}

function acore_theme_dev_info_shortcode()
{
    $isAdmin = current_user_can('manage_options');
    ob_start();
    ?>
    <div class="acore-dev">
        <p class="acore-dev-intro"><?php esc_html_e('This site runs the ACore Development theme: everything needed to try the AzerothCore CMS features, and whether it is set up.', 'acore-development'); ?></p>

        <?php
        acore_theme_dev_info_section(__('Site', 'acore-development'), acore_theme_dev_info_site());
        if ($isAdmin) {
            acore_theme_dev_info_section(__('ACore settings and game server', 'acore-development'), acore_theme_dev_info_acore());
        }
        acore_theme_dev_info_section(__('You', 'acore-development'), acore_theme_dev_info_you());
        acore_theme_dev_info_section(__('Shop', 'acore-development'), acore_theme_dev_info_shop());
        ?>

        <?php if (class_exists('WooCommerce')) : ?>
        <section class="acore-dev-section">
            <h2><?php esc_html_e('Services and their test products', 'acore-development'); ?></h2>
            <table class="acore-dev-table acore-dev-services">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Test product', 'acore-development'); ?></th>
                        <th><?php esc_html_e('SKU', 'acore-development'); ?></th>
                        <th><?php esc_html_e('What it does / what you need', 'acore-development'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (acore_theme_dev_products() as $definition) :
                        list($sku, $name, , , $description) = $definition;
                        $productId = wc_get_product_id_by_sku($sku);
                    ?>
                    <tr>
                        <td>
                            <?php if ($productId) : ?>
                                <a href="<?php echo esc_url(get_permalink($productId)); ?>"><?php echo esc_html(get_the_title($productId)); ?></a>
                            <?php else : ?>
                                <span class="acore-dev-status acore-dev-bad"><?php esc_html_e('Missing', 'acore-development'); ?></span> <?php echo esc_html($name); ?>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html($sku); ?></code></td>
                        <td><?php echo esc_html($description); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
        <?php endif; ?>

        <?php if ($isAdmin) : ?>
        <p class="acore-dev-note"><?php esc_html_e('Re-create anything missing with: wp acore-dev setup (add --force to also restore the classic cart / checkout, this front page and the test payment).', 'acore-development'); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
