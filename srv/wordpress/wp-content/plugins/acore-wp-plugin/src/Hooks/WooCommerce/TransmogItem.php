<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class TransmogItem extends \Acore\Lib\WpClass {

    private static $skuList = array(
        "transmog-item"
    );

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

        if ($product->get_sku() != "transmog-item") {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            ?>
            <span>Please select the character you want to transmog gear on.</span>
            <?php
            FieldElements::charList($current_user->user_login);
            ?>
            <span>Gear selecter or something</span>
            <?php
        }
    }

    // SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);

        if ($product->get_sku() != "transmog-item") {
            return $cart_item_data;
        }

        if (isset($_REQUEST['acore_char_sel']) && isset($_REQUEST['acore_transmog_item'])) {
            $cart_item_data['acore_char_sel'] = $_REQUEST['acore_char_sel'];
            $cart_item_data['acore_transmog_item'] = $_REQUEST['acore_transmog_item'];
            $cart_item_data['acore_item_sku'] = $product->get_sku();
            /* below statement make sure every add to cart action as unique line item */
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }

        return $cart_item_data;
    }

    // Render on checkout
    public static function get_item_data($cart_data, $cart_item = null) {
        $custom_items = array();
        if (!empty($cart_data)) {
            $custom_items = $cart_data;
        }

        if (!in_array($cart_item['acore_item_sku'], self::$skuList)) {
            return $custom_items;
        }

        if (isset($cart_item['acore_char_sel'])) {
            $ACoreSrv = ACoreServices::I();
            $charRepo = $ACoreSrv->getCharactersRepo();

            $charId = $cart_item['acore_char_sel'];
            $itemId = $cart_item['acore_transmog_item'];

            $char = $charRepo->findOneByGuid($charId);

            $charName = $char ? $char->getName() : "The character <$charId> does not exist!";

            $custom_items[] = array("name" => 'Character', "value" => $charName);
            $custom_items[] = array("name" => 'ItemId', "value" => $itemId);
        }

        return $custom_items;
    }

    // ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        if ($values['acore_item_sku'] != "transmog-item") {
            return;
        }

        if (isset($values['acore_char_sel']) && isset($values["acore_item_sku"])) {
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
            \wc_add_order_item_meta($item_id, "acore_transmog_item", $values['acore_transmog_item']);
            \wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
        }
    }

    // CHECK BEFORE PAYMENT
    public static function checkout_order_processed($order_id, $posted_data) {
        $logs = new \WC_Logger();

        $order = new \WC_Order($order_id);
        $items = $order->get_items();

        $soap = ACoreServices::I()->getServerSoap();

        foreach ($items as $item) {
            if ($item["acore_item_sku"]) {
                if ($item["acore_item_sku"] == "transmog-item") {
                    $res = $soap->serverInfo();
                    if ($res instanceof \Exception) {
                        throw new \Exception(__('The server seems to be offline, try again later!', 'woocommerce'));
                    }
                    return;
                }
            }
        }
    }

    // DO THE FINAL ACTION
    public static function payment_complete($order_id) {
        $WoWSrv = ACoreServices::I();
        $logs = new \WC_Logger();
        try {
            $order = new \WC_Order($order_id);
            $items = $order->get_items();

            $soap = $WoWSrv->getGuildSoap();
            
            foreach ($items as $item) {
                if (isset($item["acore_item_sku"])) {
                    if ($item["acore_item_sku"] == "transmog-item") {
                        $itemId = $item["acore_restore_item_sel"];
                        $charGuid = $item["acore_char_sel"];

                        if (!$itemId) {
                            throw new \Exception("Exception here");
                        }

                        $ACoreSrv = ACoreServices::I();
                        $charRepo = $ACoreSrv->getCharactersRepo();
                        $char = $charRepo->findOneByGuid($charGuid);

                        if ($char) {
                            $data = array('item' => $itemId, 'cname' => $char->getName());
                          } else {
                              throw new \Exception("Select a character!");
                          }
                    }
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }

}

TransmogItem::init();