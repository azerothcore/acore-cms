<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class SmartstoneVanity extends \ACore\Lib\WpClass {

    private static function getItemId($sku) {
        $parts = explode("_", $sku);

        if ($parts[0] != "smartstone" || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
            return false;
        }

        $smartstone_category = $parts[1];
        $smartstone_id = $parts[2];

        return [$smartstone_category, $smartstone_id];
    }

    public static function init() {
        add_action('woocommerce_after_add_to_cart_quantity', self::sprefix() . 'before_add_to_cart_button');
        add_filter('woocommerce_add_cart_item_data', self::sprefix() . 'add_cart_item_data', 20, 3);
        add_filter('woocommerce_get_item_data', self::sprefix() . 'get_item_data', 20, 2);
        add_action('woocommerce_checkout_order_processed', self::sprefix() . 'checkout_order_processed', 20, 2);
        add_action('woocommerce_add_order_item_meta', self::sprefix() . 'add_order_item_meta', 1, 3);
        add_action('woocommerce_payment_complete', self::sprefix() . 'payment_complete');
    }

    // LIST
    public static function before_add_to_cart_button() {
        global $product;
        [$smartstone_category, $smartstone_id] = self::getItemId($product->get_sku());
        if (!$smartstone_id) {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            FieldElements::charList($current_user->user_login);
        }
    }

    // SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        [$smartstone_category, $smartstone_id] = self::getItemId($product->get_sku());
        if (!$smartstone_id) {
            return $cart_item_data;
        }

        if (isset($_REQUEST['acore_char_sel'])) {
            $cart_item_data['acore_char_sel'] = $_REQUEST['acore_char_sel'];
            $cart_item_data['acore_item_sku'] = $product->get_sku();
            /* below statement make sure every add to cart action as unique line item */
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }

        return $cart_item_data;
    }

    // RENDER ON CHECKOUT
    public static function get_item_data($cart_data, $cart_item = null) {
        $custom_items = array();
        if (!empty($cart_data)) {
            $custom_items = $cart_data;
        }

        [$smartstone_category, $smartstone_id] = self::getItemId($cart_item["acore_item_sku"]);
        if (!$smartstone_id) {
            return $custom_items;
        }

        if (isset($cart_item['acore_char_sel'])) {
            $ACoreSrv = ACoreServices::I();
            $charRepo = $ACoreSrv->getCharactersRepo();

            $charId = $cart_item['acore_char_sel'];

            $char = $charRepo->findOneByGuid($charId);

            $charName = $char ? $char->getName() : "Character <$charId> doesn't exist!";

            // Add to this dictionary / array to show other catergories for vanity items.
            $categoryToText = array(
                0 => "Pet",
                1 => "Combat Pet",
                2 => "Costume",
            );

            $custom_items[] = array("name" => 'Character', "value" => $charName);
            $custom_items[] = array("name" => $categoryToText[$smartstone_category], "value" => $smartstone_id);
        }
        return $custom_items;
    }

    // ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        [$smartstone_category, $smartstone_id] = self::getItemId($values['acore_item_sku']);
        if (!$smartstone_id) {
            return;
        }

        if (isset($values['acore_char_sel']) && isset($values["acore_item_sku"])) {
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
            \wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
        }
    }

    // CHECK BEFORE PAYMENT
    public static function checkout_order_processed($order_id, $posted_data) {
        $order = new \WC_Order($order_id);
        $items = $order->get_items();

        $soap = ACoreServices::I()->getServerSoap();

        foreach ($items as $item) {
            if ($item["acore_item_sku"] && self::getItemId($item["acore_item_sku"])) {
                $res = $soap->serverInfo();
                if ($res instanceof \Exception) {
                    throw new \Exception(__('Sorry, the server seems to be offline, try again later!', 'acore-wp-plugin'));
                }
                return;
            }
        }
    }

    // 7)DO THE FINAL ACTION
    public static function payment_complete($order_id) {
        $WoWSrv = ACoreServices::I();
        $logs = new \WC_Logger();
        try {
            $order = new \WC_Order($order_id);
            $items = $order->get_items();

            $soap = $WoWSrv->getSmartstoneSoap();

            foreach ($items as $item) {
                if (isset($item["acore_item_sku"])) {
                    [$smartstone_category, $smartstone_id] = self::getItemId($item["acore_item_sku"]);

                    if ($smartstone_id) {
                        $charName = $WoWSrv->getCharName($item["acore_char_sel"]);

                        $res = $soap->addVanity($charName, $smartstone_category, $smartstone_id);

                        if ($res instanceof \Exception) {
                            throw $res;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }
}

SmartstoneVanity::init();