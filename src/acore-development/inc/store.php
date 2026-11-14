<?php
/**
 * [acore_store]: a store page with category tiles, product cards and a search box.
 *
 * Attributes: category="slug,slug" (only those categories), per_page (-1 = all), orderby, order.
 * Clicking a top-level category shows its products (sub-categories included); "Back" returns to the tiles.
 */

defined('ABSPATH') || exit;

add_action('init', function () {
    add_shortcode('acore_store', 'acore_theme_store_shortcode');
});

/**
 * True when the product needs choices on its own page before it can go in the cart (a character, a name, ...),
 * so the store links to it instead of adding it directly. ACore services are recognised by their SKU.
 */
function acore_theme_product_needs_choices($product)
{
    if (!$product->is_type('simple')) {
        return true;
    }
    $prefixes = apply_filters('acore_theme_sku_prefixes_with_choices', [
        'char-', 'itemsend', 'item-restoration', 'guild-rename', 'transmog-', 'name-unlock', 'smartstone',
    ]);
    $sku = strtolower((string) $product->get_sku());   // service SKUs are case-insensitive
    foreach ($prefixes as $prefix) {
        if ($prefix !== '' && strpos($sku, strtolower($prefix)) === 0) {
            return true;
        }
    }
    return false;
}

/** The price as shown on the store: "1,000 ChromiePoints" with a price label (Customizer), otherwise WooCommerce's. */
function acore_theme_store_price_html($product)
{
    $label = trim((string) acore_theme_mod('acore_price_label'));
    if ($label === '') {
        $html = $product->get_price_html();
        return $html !== '' ? $html : esc_html__('Free', 'acore-development');
    }
    $price = $product->get_price();
    if ($price === '' || $price === null || (float) $price == 0) {
        return esc_html__('Free', 'acore-development');
    }
    return esc_html(number_format_i18n((float) $price) . ' ' . $label);
}

/** The top-level category slugs of a product (a sub-category counts for its top-level parent). */
function acore_theme_product_top_categories($productId)
{
    $slugs = [];
    $terms = get_the_terms($productId, 'product_cat');
    if (!$terms || is_wp_error($terms)) {
        return $slugs;
    }
    foreach ($terms as $term) {
        $ancestors = get_ancestors($term->term_id, 'product_cat', 'taxonomy');
        $top = empty($ancestors) ? $term : get_term(end($ancestors), 'product_cat');
        if ($top && !is_wp_error($top)) {
            $slugs[$top->slug] = true;
        }
    }
    return array_keys($slugs);
}

function acore_theme_store_shortcode($atts)
{
    if (!class_exists('WooCommerce')) {
        return '<div class="acore-store-notice">' . esc_html__('The store needs the WooCommerce plugin to be active.', 'acore-development') . '</div>';
    }

    $atts = shortcode_atts([
        'category' => '',
        'per_page' => -1,
        'orderby'  => 'menu_order',
        'order'    => 'ASC',
    ], $atts, 'acore_store');

    $onlyCategories = array_filter(array_map('sanitize_title', explode(',', $atts['category'])));

    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
    if (is_wp_error($categories)) {
        $categories = [];
    }
    $categories = array_values(array_filter($categories, function ($term) use ($onlyCategories) {
        if ($term->slug === 'uncategorized') {
            return false;
        }
        return empty($onlyCategories) || in_array($term->slug, $onlyCategories, true);
    }));

    $query = [
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $atts['per_page'],
        'orderby'        => sanitize_key($atts['orderby']),
        'order'          => strtoupper($atts['order']) === 'DESC' ? 'DESC' : 'ASC',
    ];
    if (!empty($onlyCategories)) {
        $query['tax_query'] = [[
            'taxonomy'         => 'product_cat',
            'field'            => 'slug',
            'terms'            => $onlyCategories,
            'include_children' => true,
        ]];
    }
    $products = [];
    foreach ((new WP_Query($query))->posts as $post) {
        $product = wc_get_product($post->ID);
        if ($product && $product->is_visible()) {
            $products[] = $product;
        }
    }

    // only tiles for categories that have a loaded product (per_page can leave some out)
    $loaded = [];
    foreach ($products as $product) {
        foreach (acore_theme_product_top_categories($product->get_id()) as $slug) {
            $loaded[$slug] = true;
        }
    }
    $categories = array_values(array_filter($categories, function ($term) use ($loaded) {
        return isset($loaded[$term->slug]);
    }));

    wp_enqueue_style('acore-store', ACORE_THEME_URI . '/assets/css/store.css', ['acore-development'], acore_theme_asset_version('/assets/css/store.css'));
    wp_enqueue_script('acore-store', ACORE_THEME_URI . '/assets/js/store.js', [], acore_theme_asset_version('/assets/js/store.js'), true);

    ob_start();
    include ACORE_THEME_DIR . '/template-parts/store.php';
    return ob_get_clean();
}
