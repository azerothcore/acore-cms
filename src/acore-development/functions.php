<?php
/**
 * ACore Development - a standalone theme for testing AzerothCore CMS (no parent theme, no other dependencies).
 *
 * header.php / footer.php / index.php   the page layout; WooCommerce pages use WooCommerce's own templates
 *
 * inc/customizer.php   settings (Appearance > Customize > ACore Development)
 * inc/appearance.php   dark / light mode and the theme's styles
 * inc/navigation.php   header menu with the "More" dropdown and the mode toggle
 * inc/store.php        [acore_store] shortcode (store page with category tiles and search)
 * inc/shop.php         category tabs and badges on the WooCommerce shop pages
 * inc/dev/plugins.php  required plugins (WooCommerce, myCred, WP 2FA) installed / activated, WooCommerce pre-set
 * inc/dev/setup.php    the test shop: categories, a test product per service, Store / info pages (always)
 * inc/dev/info.php     [acore_dev_info]: what the CMS needs and what's set up (the front page)
 * inc/dev/test-gateway "ACore test payment": everyone in development, administrators otherwise
 * inc/dev/points.php   buy test AzerothCore Points (packs or a custom amount), [acore_points]
 */

defined('ABSPATH') || exit;

define('ACORE_THEME_VERSION', '1.0.0');
define('ACORE_THEME_DIR', get_stylesheet_directory());
define('ACORE_THEME_URI', get_stylesheet_directory_uri());

/**
 * True on development / local installs (WP_ENVIRONMENT_TYPE; the Docker setup sets it from ENVIRONMENT).
 * Then everyone can use the "ACore test payment"; otherwise only administrators can.
 */
function acore_theme_is_dev()
{
    return in_array(wp_get_environment_type(), ['development', 'local'], true);
}

add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-lightbox');
    register_nav_menus(['primary' => __('Primary menu', 'acore-development')]);
});

/** Version for a theme asset: its modification time, so browsers fetch it again whenever it changes. */
function acore_theme_asset_version($path)
{
    $mtime = @filemtime(ACORE_THEME_DIR . $path);
    return $mtime ? (string) $mtime : ACORE_THEME_VERSION;
}

/** Products without an image show the AzerothCore logo. */
add_filter('woocommerce_placeholder_img_src', function () {
    return ACORE_THEME_URI . '/assets/images/azerothcore-logo.png';
});
// WooCommerce's placeholder is an uploaded image (option woocommerce_placeholder_image) that skips the src filter
// above, so the whole <img> is replaced with the logo
add_filter('woocommerce_placeholder_img', function ($html, $size = 'woocommerce_thumbnail', $dimensions = []) {
    $width  = !empty($dimensions['width']) ? (int) $dimensions['width'] : '';
    $height = !empty($dimensions['height']) ? (int) $dimensions['height'] : '';
    return sprintf(
        '<img src="%s" alt="%s" class="woocommerce-placeholder wp-post-image acore-placeholder-logo"%s%s loading="lazy" decoding="async" />',
        esc_url(ACORE_THEME_URI . '/assets/images/azerothcore-logo.png'),
        esc_attr__('AzerothCore', 'acore-development'),
        $width ? ' width="' . $width . '"' : '',
        $height ? ' height="' . $height . '"' : ''
    );
}, 10, 3);

require ACORE_THEME_DIR . '/inc/customizer.php';
require ACORE_THEME_DIR . '/inc/appearance.php';
require ACORE_THEME_DIR . '/inc/navigation.php';
require ACORE_THEME_DIR . '/inc/store.php';
require ACORE_THEME_DIR . '/inc/shop.php';
require ACORE_THEME_DIR . '/inc/product-admin.php';

require ACORE_THEME_DIR . '/inc/dev/plugins.php';
require ACORE_THEME_DIR . '/inc/dev/test-gateway.php';
require ACORE_THEME_DIR . '/inc/dev/points.php';
require ACORE_THEME_DIR . '/inc/dev/setup.php';
require ACORE_THEME_DIR . '/inc/dev/info.php';
