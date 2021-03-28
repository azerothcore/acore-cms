<?php

namespace ACore;

use ACore\ACoreServices;

class WC_CarbonCopy extends \ACore\Lib\WpClass {

    private static $skuList = array(
        "carboncopy_tickets"
    );

    public static function init() {
        add_action('woocommerce_add_to_cart_validation', self::sprefix() . 'add_to_cart_validation', 10, 5);
        add_filter('woocommerce_add_cart_item_data', self::sprefix() . 'add_cart_item_data', 10, 3);
        add_action('woocommerce_checkout_order_processed', self::sprefix() . 'checkout_order_processed', 10, 2);
        add_action('woocommerce_add_order_item_meta', self::sprefix() . 'add_order_item_meta', 1, 3);
        add_action('woocommerce_payment_complete', self::sprefix() . 'payment_complete');
    }

    // VALIDATION
    // This code will do the validation for name-on-tshirt field.
    public static function add_to_cart_validation($flaq, $product_id, $quantity, $variation_id = null, $variations = null) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        if (!in_array($product->get_sku(), self::$skuList)) {
            return true;
        }

        $current_user = wp_get_current_user();

        if (!$current_user) {
            \wc_add_notice(__('You must be logged to buy it!', 'acore-wp-plugin'), 'error');
            return false;
        }

        return true;
    }

    // SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        if (!in_array($product->get_sku(), self::$skuList)) {
            return $cart_item_data;
        }

        $cart_item_data['acore_item_sku'] = $product->get_sku();
        /* below statement make sure every add to cart action as unique line item */
        $cart_item_data['unique_key'] = md5(microtime() . rand());

        return $cart_item_data;
    }

    // ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        if (!in_array($values['acore_item_sku'], self::$skuList)) {
            return;
        }

        if (isset($values["acore_item_sku"])) {
            \wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
        }
    }

    // check before payment
    public static function checkout_order_processed($order_id, $posted_data) {
        $logs = new \WC_Logger();

        $order = new \WC_Order($order_id);
        $items = $order->get_items();

        $soap = ACoreServices::I()->getServerSoap();

        foreach ($items as $item) {
            if ($item["acore_item_sku"]) {
                if (in_array($item["acore_item_sku"], self::$skuList)) {
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
        $logs = new \WC_Logger();
        try {
            $order = new \WC_Order($order_id);
            $items = $order->get_items();

            $soap = ACoreServices::I()->getAccountSoap();

            foreach ($items as $item) {
                if (isset($item["acore_item_sku"])) {
                    switch ($item["acore_item_sku"]) {
                        case "carboncopy_tickets":
                            $current_user = wp_get_current_user();

                            $res = $soap->addCCTickets($current_user->user_login, $item["qty"]);
                            if ($res instanceof \Exception) {
                                throw new \Exception("There was an error adding the ticket - " . $res->getMessage());
                            }
                            break;
                    }
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }
}

WC_CarbonCopy::init();
