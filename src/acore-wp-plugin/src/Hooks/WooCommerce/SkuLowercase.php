<?php

namespace ACore\Hooks\WooCommerce;

/**
 * ACore service SKUs are lowercase: the services compare them that way (smartstone_9_37, itemsend_6948,
 * char-change-race, ...). Saving a product lowercases an ACore SKU, and existing ones are converted once.
 * A product whose lowercase SKU is already taken by another product is left as it is (and reported).
 */
class SkuLowercase {

    /** SKU prefixes of the ACore services. Filter: acore_service_sku_prefixes. */
    public static function prefixes(): array {
        return apply_filters('acore_service_sku_prefixes', [
            'char-', 'name-unlock', 'itemsend', 'item-restoration', 'guild-rename', 'transmog-', 'smartstone',
            'carboncopy-tickets',
        ]);
    }

    public static function isServiceSku($sku): bool {
        $lower = strtolower((string) $sku);
        foreach (self::prefixes() as $prefix) {
            if ($prefix !== '' && strpos($lower, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    public static function init() {
        // after SmartstoneProductAdmin (priority 10), which may have just built the SKU
        add_action('woocommerce_admin_process_product_object', [__CLASS__, 'lowercaseOnSave'], 20);
        add_action('admin_init', [__CLASS__, 'convertExisting']);
    }

    public static function lowercaseOnSave($product) {
        $sku = (string) $product->get_sku('edit');
        if ($sku === '' || $sku === strtolower($sku) || !self::isServiceSku($sku)) {
            return;
        }
        try {
            $product->set_sku(strtolower($sku));
        } catch (\WC_Data_Exception $e) {
            \WC_Admin_Meta_Boxes::add_error(sprintf(
                __('The SKU %1$s could not be made lowercase (%2$s): the ACore services only recognise lowercase SKUs.', 'acore-wp-plugin'),
                $sku, $e->getMessage()
            ));
        }
    }

    /** Once per site: lowercases the ACore SKUs already stored. */
    public static function convertExisting() {
        if (get_option('acore_sku_lowercase_done') || !function_exists('wc_get_product') || !current_user_can('edit_products')) {
            return;
        }
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT pm.post_id, pm.meta_value FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type IN ('product', 'product_variation')
             WHERE pm.meta_key = '_sku' AND pm.meta_value <> '' AND CAST(pm.meta_value AS BINARY) <> CAST(LOWER(pm.meta_value) AS BINARY)"
        );
        $failed = [];
        foreach ((array) $rows as $row) {
            if (!self::isServiceSku($row->meta_value)) {
                continue;
            }
            $product = \wc_get_product((int) $row->post_id);
            if (!$product) {
                continue;
            }
            try {
                $product->set_sku(strtolower($row->meta_value));
                $product->save();
            } catch (\Throwable $e) {
                $failed[] = $row->meta_value . ' (#' . $row->post_id . ')';
            }
        }
        update_option('acore_sku_lowercase_done', 1);
        if (!empty($failed)) {
            update_option('acore_sku_lowercase_failed', $failed, false);
        }
    }
}

add_action('admin_notices', function () {
    $failed = get_option('acore_sku_lowercase_failed');
    if (empty($failed) || !current_user_can('edit_products')) {
        return;
    }
    echo '<div class="notice notice-warning"><p>' . esc_html(sprintf(
        __('These ACore SKUs could not be made lowercase because the lowercase SKU is already used by another product: %s. Fix them by hand; the services read SKUs case-insensitively meanwhile.', 'acore-wp-plugin'),
        implode(', ', (array) $failed)
    )) . '</p></div>';
});

SkuLowercase::init();
