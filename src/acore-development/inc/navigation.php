<?php
/**
 * Header menu (header.php): the first N entries in the bar, the rest in a "More" dropdown, plus the dark / light toggle.
 */

defined('ABSPATH') || exit;

/**
 * The menu entries: the top level of the menu assigned to "Primary" (Appearance > Menus) when there is one,
 * otherwise the theme's own pages - the ACore Development info page, the Store and the WooCommerce shop - so pages
 * other plugins create (WP 2FA, ...) and WordPress's "Sample Page" don't fill the bar.
 * Each entry: [ 'title' => ..., 'url' => ... ]. Filter: acore_theme_nav_entries.
 */
function acore_theme_nav_entries()
{
    $entries   = [];
    $locations = get_nav_menu_locations();

    if (!empty($locations['primary'])) {
        $items = wp_get_nav_menu_items($locations['primary']);
        foreach ((array) $items as $item) {
            if ((int) $item->menu_item_parent === 0) {
                $entries[] = ['title' => $item->title, 'url' => $item->url];
            }
        }
        return apply_filters('acore_theme_nav_entries', $entries);
    }

    foreach (['acore-development', 'store'] as $slug) {
        $page = get_page_by_path($slug);
        if ($page && $page->post_status === 'publish') {
            $entries[] = ['title' => get_the_title($page), 'url' => get_permalink($page)];
        }
    }
    if (function_exists('wc_get_page_id') && wc_get_page_id('shop') > 0) {
        $entries[] = ['title' => get_the_title(wc_get_page_id('shop')), 'url' => get_permalink(wc_get_page_id('shop'))];
    }
    return apply_filters('acore_theme_nav_entries', $entries);
}

/** Log in / account / log out links for the header. */
function acore_theme_account_links()
{
    $here = is_singular() ? get_permalink() : home_url('/');
    echo '<div class="acore-account">';
    if (is_user_logged_in()) {
        $account = function_exists('wc_get_page_permalink') && wc_get_page_id('myaccount') > 0
            ? wc_get_page_permalink('myaccount')
            : get_edit_profile_url();
        printf('<a class="acore-account-name" href="%s">%s</a>', esc_url($account), esc_html(wp_get_current_user()->user_login));
        if (function_exists('acore_theme_points_available') && acore_theme_points_available()) {
            printf('<span class="acore-account-points">%s</span>', esc_html(acore_theme_points_format(acore_theme_points_balance())));
        }
        printf('<a class="acore-account-btn" href="%s">%s</a>', esc_url(wp_logout_url($here)), esc_html__('Log out', 'acore-development'));
    } else {
        if (get_option('users_can_register')) {
            printf('<a class="acore-account-name" href="%s">%s</a>', esc_url(wp_registration_url()), esc_html__('Register', 'acore-development'));
        }
        printf('<a class="acore-account-btn" href="%s">%s</a>', esc_url(wp_login_url($here)), esc_html__('Log in', 'acore-development'));
    }
    echo '</div>';
}

/** True when $url is the page being viewed (same host and path). */
function acore_theme_is_current_url($url)
{
    $requestPath = isset($_SERVER['REQUEST_URI']) ? (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH) : '';
    $linkHost    = (string) wp_parse_url($url, PHP_URL_HOST);
    $siteHost    = (string) wp_parse_url(home_url(), PHP_URL_HOST);
    if ($linkHost !== '' && strcasecmp($linkHost, $siteHost) !== 0) {
        return false;
    }
    return untrailingslashit((string) wp_parse_url($url, PHP_URL_PATH)) === untrailingslashit($requestPath);
}

function acore_theme_nav_link($entry)
{
    $current = acore_theme_is_current_url($entry['url']);
    printf(
        '<a href="%s"%s>%s</a>',
        esc_url($entry['url']),
        $current ? ' class="current" aria-current="page"' : '',
        esc_html($entry['title'])
    );
}

function acore_theme_primary_navigation()
{
    $entries = acore_theme_nav_entries();
    $max     = max(1, (int) acore_theme_mod('acore_nav_max_pages'));
    $visible = array_slice($entries, 0, $max);
    $hidden  = array_slice($entries, $max);
    $more    = !empty($hidden) || acore_theme_mod('acore_nav_always_more');
    ?>
    <nav id="site-navigation" class="acore-nav" aria-label="<?php esc_attr_e('Primary navigation', 'acore-development'); ?>">
        <ul class="acore-nav-list">
            <?php foreach ($visible as $entry) : ?>
                <li><?php acore_theme_nav_link($entry); ?></li>
            <?php endforeach; ?>

            <?php if ($more) : ?>
                <li class="acore-nav-more">
                    <button type="button" class="acore-nav-more-btn" aria-expanded="false" aria-controls="acore-nav-more-menu">
                        <?php esc_html_e('More', 'acore-development'); ?> <span aria-hidden="true">&#9662;</span>
                    </button>
                    <ul id="acore-nav-more-menu" class="acore-nav-dropdown" hidden>
                        <?php foreach ($hidden as $entry) : ?>
                            <li><?php acore_theme_nav_link($entry); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>

        <button type="button" class="acore-mode-toggle" aria-label="<?php esc_attr_e('Switch between dark and light mode', 'acore-development'); ?>">
            <span class="acore-mode-toggle-dark" aria-hidden="true">&#9728;&#65039; <?php esc_html_e('Light', 'acore-development'); ?></span>
            <span class="acore-mode-toggle-light" aria-hidden="true">&#127769; <?php esc_html_e('Dark', 'acore-development'); ?></span>
        </button>
    </nav>
    <?php
}
