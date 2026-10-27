<?php
/**
 * WooCommerce shop pages: category tabs above the product grid and a category badge on each product.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function () {
    if (function_exists('is_woocommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page())) {
        wp_enqueue_style('acore-shop', ACORE_THEME_URI . '/assets/css/shop.css', ['acore-development'], acore_theme_asset_version('/assets/css/shop.css'));
    }
}, 30);

// header.php already opens <main>: no second wrapper from WooCommerce, and no sidebar (this theme has none)
remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);

add_action('woocommerce_before_shop_loop', 'acore_theme_shop_category_tabs', 5);
add_action('woocommerce_after_shop_loop_item_title', 'acore_theme_shop_category_badge', 5);

/** Tabs for the top-level product categories ("All" first) on the shop and category pages. */
function acore_theme_shop_category_tabs()
{
    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'parent'     => 0,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
    if (is_wp_error($categories)) {
        return;
    }
    $categories = array_filter($categories, function ($term) {
        return $term->slug !== 'uncategorized';
    });
    if (empty($categories)) {
        return;
    }

    $current = is_product_category() ? get_queried_object() : null;
    $currentTop = '';
    if ($current instanceof WP_Term) {
        $ancestors  = get_ancestors($current->term_id, 'product_cat', 'taxonomy');
        $top        = empty($ancestors) ? $current : get_term(end($ancestors), 'product_cat');
        $currentTop = ($top && !is_wp_error($top)) ? $top->slug : '';
    }

    echo '<nav class="acore-shop-tabs" aria-label="' . esc_attr__('Product categories', 'acore-development') . '">';
    printf(
        '<a href="%s" class="acore-shop-tab%s">%s</a>',
        esc_url(wc_get_page_permalink('shop')),
        $currentTop === '' ? ' active' : '',
        esc_html__('All', 'acore-development')
    );
    foreach ($categories as $category) {
        $url = get_term_link($category);
        if (is_wp_error($url)) {
            continue;
        }
        printf(
            '<a href="%s" class="acore-shop-tab%s">%s</a>',
            esc_url($url),
            $currentTop === $category->slug ? ' active' : '',
            esc_html($category->name)
        );
    }
    echo '</nav>';
}

/** The product's top-level category as a small badge under its title. */
function acore_theme_shop_category_badge()
{
    global $product;
    if (!$product) {
        return;
    }
    foreach (acore_theme_product_top_categories($product->get_id()) as $slug) {
        if ($slug === 'uncategorized') {
            continue;
        }
        $term = get_term_by('slug', $slug, 'product_cat');
        if ($term) {
            printf('<a href="%s" class="acore-cat-badge">%s</a>', esc_url(get_term_link($term)), esc_html($term->name));
            return;
        }
    }
}
