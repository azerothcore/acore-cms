<?php
/**
 * ACore Theme - a small standalone theme for testing AzerothCore CMS (no parent theme, no other dependencies).
 *
 * header.php / footer.php / index.php   the page layout; WooCommerce pages use WooCommerce's own templates
 *
 * inc/customizer.php   settings (Appearance > Customize > ACore Theme)
 * inc/appearance.php   dark / light mode and the theme's styles
 * inc/navigation.php   header menu with the "More" dropdown and the mode toggle
 * inc/store.php        [acore_store] shortcode (store page with category tiles and search)
 * inc/shop.php         category tabs and badges on the WooCommerce shop pages
 * inc/dev/*            development only: test shop setup and the "ACore test payment" method
 */

defined('ABSPATH') || exit;

define('ACORE_THEME_VERSION', '1.0.0');
define('ACORE_THEME_DIR', get_stylesheet_directory());
define('ACORE_THEME_URI', get_stylesheet_directory_uri());

/**
 * True on development / local installs (WP_ENVIRONMENT_TYPE; the Docker setup sets it from ENVIRONMENT).
 * The test shop and the test payment method only exist then.
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
    register_nav_menus(['primary' => __('Primary menu', 'acore-theme')]);
});

require ACORE_THEME_DIR . '/inc/customizer.php';
require ACORE_THEME_DIR . '/inc/appearance.php';
require ACORE_THEME_DIR . '/inc/navigation.php';
require ACORE_THEME_DIR . '/inc/store.php';
require ACORE_THEME_DIR . '/inc/shop.php';

if (acore_theme_is_dev()) {
    require ACORE_THEME_DIR . '/inc/dev/test-gateway.php';
    require ACORE_THEME_DIR . '/inc/dev/setup.php';
}
