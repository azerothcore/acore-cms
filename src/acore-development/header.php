<?php
/**
 * Page header: site name / logo, the menu, log in / account links (inc/navigation.php) and the cart link.
 */

defined('ABSPATH') || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="screen-reader-text acore-skip-link" href="#content"><?php esc_html_e('Skip to content', 'acore-development'); ?></a>

<header class="acore-header">
    <div class="acore-container acore-header-inner">
        <div class="acore-branding">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <a class="acore-site-title" href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a>
            <?php endif; ?>
        </div>

        <?php acore_theme_primary_navigation(); ?>

        <?php acore_theme_account_links(); ?>

        <?php if (function_exists('WC') && WC()->cart) :
            $count = WC()->cart->get_cart_contents_count();
        ?>
            <a class="acore-cart-link" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                <?php esc_html_e('Cart', 'acore-development'); ?>
                <span class="acore-cart-count"><?php echo esc_html(number_format_i18n($count)); ?></span>
            </a>
        <?php endif; ?>
    </div>
</header>

<main id="content" class="acore-container acore-main">
