<?php
/**
 * For users who can edit products (administrators, shop managers): an "Edit" button on every product (shop cards,
 * the Store page, the product page, the info page) and a toolbar to the product list / a new product.
 */

defined('ABSPATH') || exit;

function acore_theme_can_edit_products()
{
    return is_user_logged_in() && current_user_can('edit_products');
}

/** "Edit" button to the product's edit screen, or '' when the user can't edit it. */
function acore_theme_product_edit_button($productId, $label = '')
{
    $productId = (int) $productId;
    if (!$productId || !is_user_logged_in() || !current_user_can('edit_product', $productId)) {
        return '';
    }
    $url = get_edit_post_link($productId, 'url');
    if (!$url) {
        return '';
    }
    $label = $label !== '' ? $label : __('Edit', 'acore-development');
    return sprintf(
        '<a href="%s" class="acore-edit-chip" title="%s"><span aria-hidden="true">&#9998;</span> %s</a>',
        esc_url($url),
        esc_attr(sprintf(__('Edit "%s"', 'acore-development'), get_the_title($productId))),
        esc_html($label)
    );
}

/** Links to the product list, a new product and the categories, or '' when the user can't edit products. */
function acore_theme_product_admin_toolbar()
{
    if (!acore_theme_can_edit_products()) {
        return '';
    }
    $links = [
        [admin_url('edit.php?post_type=product'), __('Manage products', 'acore-development'), '&#9776;'],
        [admin_url('post-new.php?post_type=product'), __('Add product', 'acore-development'), '+'],
    ];
    if (current_user_can('manage_product_terms')) {
        $links[] = [admin_url('edit-tags.php?taxonomy=product_cat&post_type=product'), __('Categories', 'acore-development'), '#'];
    }
    $html = '<div class="acore-admin-toolbar"><span class="acore-admin-toolbar-label">' . esc_html__('Shop admin', 'acore-development') . '</span>';
    foreach ($links as $link) {
        $html .= sprintf('<a href="%s" class="acore-admin-toolbar-btn"><span aria-hidden="true">%s</span> %s</a>', esc_url($link[0]), $link[2], esc_html($link[1]));
    }
    return $html . '</div>';
}

// shop / category pages: toolbar under the category tabs, "Edit" on each card (outside the card's link)
add_action('woocommerce_before_shop_loop', function () {
    echo acore_theme_product_admin_toolbar(); // escaped above
}, 6);

add_action('woocommerce_before_shop_loop_item', function () {
    global $product;
    if ($product) {
        echo acore_theme_product_edit_button($product->get_id()); // escaped above
    }
}, 5);

// product page: "Edit product" above the title
add_action('woocommerce_single_product_summary', function () {
    global $product;
    if ($product) {
        echo acore_theme_product_edit_button($product->get_id(), __('Edit product', 'acore-development')); // escaped above
    }
}, 4);
