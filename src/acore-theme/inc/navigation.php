<?php
/**
 * Header menu (header.php): the first N entries in the bar, the rest in a "More" dropdown, plus the dark / light toggle.
 */

defined('ABSPATH') || exit;

/**
 * The menu entries: the top level of the menu assigned to "Primary", or every published page when none is.
 * Each entry: [ 'title' => ..., 'url' => ... ].
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
        return $entries;
    }

    $pages = get_pages([
        'sort_column' => 'menu_order,post_title',
        'sort_order'  => 'ASC',
        'post_status' => 'publish',
        'parent'      => 0,
    ]);
    foreach ($pages as $page) {
        $entries[] = ['title' => get_the_title($page), 'url' => get_permalink($page)];
    }
    return $entries;
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
    <nav id="site-navigation" class="acore-nav" aria-label="<?php esc_attr_e('Primary navigation', 'acore-theme'); ?>">
        <ul class="acore-nav-list">
            <?php foreach ($visible as $entry) : ?>
                <li><?php acore_theme_nav_link($entry); ?></li>
            <?php endforeach; ?>

            <?php if ($more) : ?>
                <li class="acore-nav-more">
                    <button type="button" class="acore-nav-more-btn" aria-expanded="false" aria-controls="acore-nav-more-menu">
                        <?php esc_html_e('More', 'acore-theme'); ?> <span aria-hidden="true">&#9662;</span>
                    </button>
                    <ul id="acore-nav-more-menu" class="acore-nav-dropdown" hidden>
                        <?php foreach ($hidden as $entry) : ?>
                            <li><?php acore_theme_nav_link($entry); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>

        <button type="button" class="acore-mode-toggle" aria-label="<?php esc_attr_e('Switch between dark and light mode', 'acore-theme'); ?>">
            <span class="acore-mode-toggle-dark" aria-hidden="true">&#9728;&#65039; <?php esc_html_e('Light', 'acore-theme'); ?></span>
            <span class="acore-mode-toggle-light" aria-hidden="true">&#127769; <?php esc_html_e('Dark', 'acore-theme'); ?></span>
        </button>
    </nav>
    <?php
}
