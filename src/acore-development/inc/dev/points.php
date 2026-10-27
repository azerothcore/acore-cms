<?php
/**
 * Test points: add AzerothCore Points (myCred) for testing - 100 / 500 / 1,000 / 5,000 or a custom amount.
 * The buttons update the balance in place (AJAX); without JavaScript the form posts and comes back to the same page.
 * No money is involved: the points are added to your balance straight away, so you can pay for test products with
 * "Pay with AzerothCore Points".
 *
 * Who can: everyone in development (WP_ENVIRONMENT_TYPE development / local), administrators otherwise.
 * Shown at the top of the Store and Shop pages, and with [acore_points] anywhere.
 */

defined('ABSPATH') || exit;

define('ACORE_THEME_POINTS_MAX', 100000);   // largest single top-up

/** The fixed amounts. Filter: acore_theme_points_packs. */
function acore_theme_points_packs()
{
    return apply_filters('acore_theme_points_packs', [100, 500, 1000, 5000]);
}

function acore_theme_points_available()
{
    return function_exists('mycred_add') && function_exists('mycred_get_users_balance');
}

function acore_theme_points_can_buy()
{
    return acore_theme_points_available() && is_user_logged_in()
        && (acore_theme_is_dev() || current_user_can('manage_woocommerce'));
}

/** "1,000 AzerothCore Points" in the point type's own format. */
function acore_theme_points_format($amount)
{
    if (function_exists('mycred')) {
        $mycred = mycred();
        // format_creds() already adds the point type's prefix / suffix ("1,000 AzerothCore Points");
        // only add the name when the point type has neither
        $text = trim((string) $mycred->format_creds($amount));
        return (empty($mycred->before) && empty($mycred->after)) ? $text . ' ' . $mycred->plural() : $text;
    }
    return number_format_i18n($amount) . ' ' . ACORE_THEME_POINTS_PLURAL;
}

function acore_theme_points_balance($userId = 0)
{
    $userId = $userId ?: get_current_user_id();
    return acore_theme_points_available() && $userId ? (float) mycred_get_users_balance($userId) : 0;
}

/**
 * Adds test points to the current user. Returns [ok, message key]: added-<n>, invalid, denied or failed.
 */
function acore_theme_points_add_request($nonce, $amount)
{
    if (!acore_theme_points_can_buy() || !wp_verify_nonce($nonce, 'acore_points_buy')) {
        return [false, 'denied'];
    }
    $amount = (int) $amount;
    if ($amount < 1 || $amount > ACORE_THEME_POINTS_MAX) {
        return [false, 'invalid'];
    }
    $ok = mycred_add('acore_test_points', get_current_user_id(), $amount, 'Test points added on the ACore Development site', 0, '', MYCRED_DEFAULT_TYPE_KEY);
    return $ok ? [true, 'added-' . $amount] : [false, 'failed'];
}

/** The text shown for a result key. */
function acore_theme_points_message($key)
{
    if (strpos($key, 'added-') === 0) {
        return sprintf(__('Added %s to your balance.', 'acore-development'), acore_theme_points_format((int) substr($key, 6)));
    }
    if ($key === 'invalid') {
        return sprintf(__('Choose an amount between 1 and %s.', 'acore-development'), number_format_i18n(ACORE_THEME_POINTS_MAX));
    }
    return __('The points could not be added.', 'acore-development');
}

/** AJAX: add points and answer with the new balance, so the page doesn't reload. */
add_action('wp_ajax_acore_points_add', function () {
    list($ok, $key) = acore_theme_points_add_request(
        isset($_POST['_acore_points']) ? sanitize_key(wp_unslash($_POST['_acore_points'])) : '',
        isset($_POST['acore_points_amount']) ? $_POST['acore_points_amount'] : 0
    );
    $payload = [
        'message' => acore_theme_points_message($key),
        'balance' => acore_theme_points_format(acore_theme_points_balance()),
    ];
    $ok ? wp_send_json_success($payload) : wp_send_json_error($payload);
});

/** Without JavaScript: the form posts here, then goes back to the same page without resubmitting. */
add_action('template_redirect', function () {
    if (empty($_POST['acore_points_buy'])) {
        return;
    }
    $back = isset($_POST['acore_points_back']) ? esc_url_raw(wp_unslash($_POST['acore_points_back'])) : '';
    $back = wp_validate_redirect($back, wp_get_referer() ?: home_url('/'));
    list(, $key) = acore_theme_points_add_request(
        isset($_POST['_acore_points']) ? sanitize_key(wp_unslash($_POST['_acore_points'])) : '',
        isset($_POST['acore_points_amount']) ? $_POST['acore_points_amount'] : 0
    );
    wp_safe_redirect(add_query_arg('acore_points', $key, remove_query_arg('acore_points', $back)));
    exit;
});

/** The panel: balance, the fixed amounts and a custom amount. */
function acore_theme_points_panel()
{
    if (!acore_theme_points_available()) {
        return '';
    }
    if (acore_theme_points_can_buy()) {
        wp_enqueue_script('acore-points', ACORE_THEME_URI . '/assets/js/points.js', [], acore_theme_asset_version('/assets/js/points.js'), true);
    }
    ob_start();
    $result = isset($_GET['acore_points']) ? sanitize_key(wp_unslash($_GET['acore_points'])) : '';
    ?>
    <div class="acore-points" data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <p class="acore-points-message <?php echo strpos($result, 'added-') === 0 ? 'acore-points-ok' : 'acore-points-error'; ?>" role="status" aria-live="polite"<?php if ($result === '') echo ' hidden'; ?>>
            <?php echo $result !== '' ? esc_html(acore_theme_points_message($result)) : ''; ?>
        </p>

        <div class="acore-points-row">
            <div class="acore-points-balance">
                <?php if (is_user_logged_in()) : ?>
                    <span class="acore-points-label"><?php esc_html_e('Your balance', 'acore-development'); ?></span>
                    <strong class="acore-points-amount"><?php echo esc_html(acore_theme_points_format(acore_theme_points_balance())); ?></strong>
                <?php else : ?>
                    <a href="<?php echo esc_url(wp_login_url(is_singular() ? get_permalink() : home_url('/'))); ?>"><?php esc_html_e('Log in', 'acore-development'); ?></a>
                    <?php esc_html_e('to see your balance and add points.', 'acore-development'); ?>
                <?php endif; ?>
            </div>

            <?php if (acore_theme_points_can_buy()) : ?>
            <form class="acore-points-buy" method="post">
                <?php wp_nonce_field('acore_points_buy', '_acore_points'); ?>
                <input type="hidden" name="acore_points_buy" value="1">
                <input type="hidden" name="acore_points_back" value="<?php echo esc_url(remove_query_arg('acore_points', home_url(add_query_arg([])))); ?>">
                <?php foreach (acore_theme_points_packs() as $pack) : ?>
                    <button type="submit" class="acore-points-pack" name="acore_points_amount" value="<?php echo esc_attr($pack); ?>">
                        <?php echo esc_html(sprintf(__('Add %s', 'acore-development'), number_format_i18n($pack))); ?>
                    </button>
                <?php endforeach; ?>
            </form>
            <form class="acore-points-buy acore-points-custom" method="post">
                <?php wp_nonce_field('acore_points_buy', '_acore_points'); ?>
                <input type="hidden" name="acore_points_buy" value="1">
                <input type="hidden" name="acore_points_back" value="<?php echo esc_url(remove_query_arg('acore_points', home_url(add_query_arg([])))); ?>">
                <input type="number" name="acore_points_amount" min="1" max="<?php echo esc_attr(ACORE_THEME_POINTS_MAX); ?>" step="1" placeholder="<?php esc_attr_e('Amount', 'acore-development'); ?>" aria-label="<?php esc_attr_e('Custom amount of points', 'acore-development'); ?>" required>
                <button type="submit" class="acore-points-pack"><?php esc_html_e('Add', 'acore-development'); ?></button>
            </form>
            <?php endif; ?>
        </div>

        <?php if (acore_theme_points_can_buy()) : ?>
            <p class="acore-points-note"><?php esc_html_e('Test points: no money is taken, they are added to your balance right away.', 'acore-development'); ?></p>
        <?php elseif (is_user_logged_in()) : ?>
            <p class="acore-points-note"><?php esc_html_e('Adding test points is available when the site runs in development (DOCKER_ENVIRONMENT=development), or for administrators. An administrator can also set your balance under Users.', 'acore-development'); ?></p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

add_action('init', function () {
    add_shortcode('acore_points', 'acore_theme_points_panel');
});

// at the top of the WooCommerce shop pages
add_action('woocommerce_before_shop_loop', function () {
    echo acore_theme_points_panel(); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped in the panel
}, 4);
