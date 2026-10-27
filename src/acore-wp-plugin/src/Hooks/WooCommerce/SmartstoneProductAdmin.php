<?php

namespace ACore\Hooks\WooCommerce;

/**
 * SmartStone products on the Edit Product page (Product data > Inventory, under the SKU):
 * tick "SmartStone product", pick the service type and enter the id - the SKU field is greyed out and becomes
 * smartstone_<type>_<id>, the format SmartstoneVanity reads to run
 *   .smartstone unlock service <character> <type> <id> true   (one character)
 *   .smartstone unlock account <account> <type> <id> true     (account-wide types: the whole account)
 * Untick it and the SKU field is a normal SKU again. A product with a SmartStone SKU opens with it ticked.
 * The product is also put in the type's SmartStone sub-category when that category exists.
 */
class SmartstoneProductAdmin {

    /**
     * The service types: mod-chromiecraft-smartstone's ActionType values that `.smartstone unlock service` accepts
     * (cs_smartstone.cpp). Account-wide ones are the only ones `.smartstone unlock account` accepts.
     * type => [label, product category slug, account-wide]
     *
     * The one list for the SKU builder, the cart labels and the categories: when the mod gets a new type, add a line
     * here (or add it from a theme / plugin with the acore_smartstone_types filter).
     */
    public static function types(): array {
        return apply_filters('acore_smartstone_types', [
            0 => ['Companion', 'acore-smartstone-companion', false],
            1 => ['Combat pet', 'acore-smartstone-pet', false],
            2 => ['Costume', 'acore-smartstone-costume', true],
            6 => ['Aura', 'acore-smartstone-aura', false],
            7 => ['Vehicle', 'acore-smartstone-vehicle', false],
            9 => ['Class perk', 'acore-smartstone-perk', true],
        ]);
    }

    /** The label of a type ("Costume"), or null for an unknown one. */
    public static function typeLabel($type): ?string {
        $types = self::types();
        return isset($types[(int) $type]) ? $types[(int) $type][0] : null;
    }

    public static function init() {
        add_action('woocommerce_product_options_sku', [__CLASS__, 'renderFields']);
        add_action('woocommerce_admin_process_product_object', [__CLASS__, 'save']);
    }

    /** [type, id] from a smartstone_<type>_<id> SKU, or null. */
    public static function parseSku($sku) {
        if (is_string($sku) && preg_match('/^smartstone_(\d+)_(\d+)$/i', $sku, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }
        return null;
    }

    public static function renderFields() {
        global $product_object;
        $sku    = $product_object instanceof \WC_Product ? $product_object->get_sku('edit') : '';
        $parsed = self::parseSku($sku);
        \wp_nonce_field('acore_smartstone_product', 'acore_smartstone_nonce');
        ?>
        <div class="acore-smartstone-sku">
            <p class="form-field">
                <label for="acore_smartstone_enabled"><?= esc_html__('SmartStone product', 'acore-wp-plugin') ?></label>
                <input type="checkbox" class="checkbox" id="acore_smartstone_enabled" name="acore_smartstone_enabled" value="1" <?php checked($parsed !== null); ?>>
                <span class="description"><?= esc_html__('The SKU is built from the type and the ID below.', 'acore-wp-plugin') ?></span>
            </p>
            <p class="form-field acore-smartstone-field">
                <label for="acore_smartstone_type"><?= esc_html__('SmartStone type', 'acore-wp-plugin') ?></label>
                <select id="acore_smartstone_type" name="acore_smartstone_type">
                    <option value=""><?= esc_html__('Choose a type', 'acore-wp-plugin') ?></option>
                    <?php foreach (self::types() as $value => $info) : ?>
                        <option value="<?= (int) $value ?>" <?php selected($parsed ? $parsed[0] : '', $value); ?>>
                            <?= esc_html($value . ' - ' . $info[0] . ($info[2] ? ' (' . __('whole account', 'acore-wp-plugin') . ')' : '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="form-field acore-smartstone-field">
                <label for="acore_smartstone_id"><?= esc_html__('SmartStone ID', 'acore-wp-plugin') ?></label>
                <input type="number" class="short" id="acore_smartstone_id" name="acore_smartstone_id" min="1" step="1" value="<?= esc_attr($parsed ? $parsed[1] : '') ?>">
            </p>
        </div>
        <script>
            jQuery(function ($) {
                var enabled = $('#acore_smartstone_enabled');
                var type = $('#acore_smartstone_type');
                var id = $('#acore_smartstone_id');
                var sku = $('#_sku');
                var fields = $('.acore-smartstone-field');
                var typedSku = sku.val();

                function update() {
                    var on = enabled.is(':checked');
                    fields.toggle(on);
                    sku.prop('readonly', on).css('opacity', on ? 0.6 : '');
                    if (on) {
                        sku.val(type.val() !== '' && id.val() !== '' ? 'smartstone_' + type.val() + '_' + id.val() : '');
                    } else if (/^smartstone_\d+_\d+$/i.test(sku.val()) === false || sku.val() === '') {
                        sku.val(typedSku);
                    }
                }
                sku.on('input', function () {
                    if (!enabled.is(':checked')) {
                        typedSku = sku.val();
                    }
                });
                enabled.on('change', update);
                type.on('change', update);
                id.on('input', update);
                update();
            });
        </script>
        <?php
    }

    /** Builds the SKU (and adds the SmartStone sub-category) when the product is a SmartStone product. */
    public static function save($product) {
        if (!isset($_POST['acore_smartstone_nonce']) || !\wp_verify_nonce(\sanitize_key(\wp_unslash($_POST['acore_smartstone_nonce'])), 'acore_smartstone_product')) {
            return;
        }
        if (empty($_POST['acore_smartstone_enabled'])) {
            return;   // a normal product: WooCommerce saved the SKU field as typed
        }
        $type  = isset($_POST['acore_smartstone_type']) ? (string) \wp_unslash($_POST['acore_smartstone_type']) : '';
        $id    = isset($_POST['acore_smartstone_id']) ? (int) $_POST['acore_smartstone_id'] : 0;
        $types = self::types();
        if ($type === '' || !isset($types[(int) $type]) || $id < 1) {
            \WC_Admin_Meta_Boxes::add_error(__('SmartStone product: choose a type and enter an ID, the SKU was not changed.', 'acore-wp-plugin'));
            return;
        }

        $sku = 'smartstone_' . (int) $type . '_' . $id;
        try {
            $product->set_sku($sku);
        } catch (\WC_Data_Exception $e) {
            \WC_Admin_Meta_Boxes::add_error(sprintf(__('SmartStone product: the SKU %1$s could not be set (%2$s).', 'acore-wp-plugin'), $sku, $e->getMessage()));
            return;
        }

        $term = \get_term_by('slug', $types[(int) $type][1], 'product_cat');
        if ($term) {
            $ids = array_map('intval', $product->get_category_ids());
            if (!in_array((int) $term->term_id, $ids, true)) {
                $ids[] = (int) $term->term_id;
                $product->set_category_ids($ids);
            }
        }
    }
}

SmartstoneProductAdmin::init();
