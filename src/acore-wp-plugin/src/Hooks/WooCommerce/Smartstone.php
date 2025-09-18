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
        add_action('woocommerce_add_to_cart_validation', [__CLASS__, 'add_to_cart_validation'], 10, 5);
    }

     // Validator for add to cart from external sources (no character selected) or logged in required from "CartValidation")    
    public static function add_to_cart_validation($passed, $product_id, $quantity, $variation_id = null, $variations = null) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        $sku = $product->get_sku();
        if (strpos($sku, 'smartstone') !== 0) {
            return $passed;
        }

        $current_user = wp_get_current_user();
        if (!is_user_logged_in()) {
            \wc_add_notice(__('You must be logged in to buy it!', 'acore-wp-plugin'), 'error');
            return false;
        }

        $guid = intval($_REQUEST['acore_char_sel'] ?? 0);
        if ($guid === 0) {
            \wc_add_notice(__('No character selected. Please select a character and try again.', 'acore-wp-plugin'), 'error');
            return false;
        }

        return $passed;
    }

    // LIST
    public static function before_add_to_cart_button() {
        global $product;
        [$smartstone_category, $smartstone_id] = self::getItemId($product->get_sku());
        if (!$smartstone_id) {
            return;
        }

        FieldElements::get3dViewer();

        $current_user = wp_get_current_user();

        if ($current_user) {
            FieldElements::charList($current_user->user_login);
            ?>
            <br>
            <label for="acore_char_dest">Or send it as a present for:</label>
            <input type="text" id="acore_char_dest" class="acore_char_dest" name="acore_char_dest" placeholder="Character name..." maxlength="24" />
            <br>
            <label for="acore_msg_dest">Send a message (optional):</label>
            <textarea maxlength="200" id="acore_msg_dest" class="acore_msg_dest" name="acore_msg_dest"></textarea>
            <br>
            <br>
            <?php
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


        // Always require sender character
        if (isset($_REQUEST['acore_char_sel'])) {
            $cart_item_data['acore_char_sel'] = $_REQUEST['acore_char_sel'];
        }
        // Save recipient if present
        if (!empty($_REQUEST['acore_char_dest'])) {
            $cart_item_data['acore_char_dest'] = sanitize_text_field($_REQUEST['acore_char_dest']);
        }
        // Save message if present
        if (!empty($_REQUEST['acore_msg_dest'])) {
            $cart_item_data['acore_msg_dest'] = substr(sanitize_text_field($_REQUEST['acore_msg_dest']), 0, 200);
        }
        $cart_item_data['acore_item_sku'] = $product->get_sku();
        /* below statement make sure every add to cart action as unique line item */
        $cart_item_data['unique_key'] = md5(microtime() . rand());

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

        $ACoreSrv = ACoreServices::I();
        $charRepo = $ACoreSrv->getCharactersRepo();
        $categoryToText = array(
            0 => "Pet",
            1 => "Combat Pet",
            2 => "Costume",
        );

        $senderName = null;
        if (isset($cart_item['acore_char_sel'])) {
            $charId = $cart_item['acore_char_sel'];
            $char = $charRepo->findOneByGuid($charId);
            $senderName = $char ? $char->getName() : "Character <$charId> doesn't exist!";
        }

        if (!empty($cart_item['acore_char_dest'])) {
            // Gifted
            $custom_items[] = array("name" => 'Gifted by', "value" => $senderName);
            $custom_items[] = array("name" => 'Gifted to', "value" => esc_html($cart_item['acore_char_dest']));
        } elseif ($senderName) {
            $custom_items[] = array("name" => 'Character', "value" => $senderName);
        }
        $custom_items[] = array("name" => $categoryToText[$smartstone_category], "value" => $smartstone_id);
        if (!empty($cart_item['acore_msg_dest'])) {
            $custom_items[] = array("name" => 'Message', "value" => esc_html($cart_item['acore_msg_dest']));
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

        if (isset($values['acore_char_sel'])) {
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
        }
        if (!empty($values['acore_char_dest'])) {
            \wc_add_order_item_meta($item_id, "acore_char_dest", $values['acore_char_dest']);
        }
        if (!empty($values['acore_msg_dest'])) {
            \wc_add_order_item_meta($item_id, "acore_msg_dest", $values['acore_msg_dest']);
        }
        if (isset($values["acore_item_sku"])) {
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
                        // If gifting, use recipient, else use sender
                        $recipient = !empty($item["acore_char_dest"]) ? $item["acore_char_dest"] : $WoWSrv->getCharName($item["acore_char_sel"]);
                        $sender = $WoWSrv->getCharName($item["acore_char_sel"]);
                        $itemName = isset($item["name"]) ? $item["name"] : 'a Smartstone item';
                        // Try to get product name if possible
                        if (isset($item["acore_item_sku"])) {
                            $product = wc_get_product($item["acore_item_sku"]);
                            if ($product) {
                                $itemName = $product->get_name();
                            }
                        }
                        $titleMsg = sprintf(__('%s has sent you %s', 'acore-wp-plugin'), $sender, $itemName);
                        $userMsg = !empty($item["acore_msg_dest"]) ? $item["acore_msg_dest"] : '';
                        $finalMsg = $titleMsg;
                        if ($userMsg !== '') {
                            $finalMsg .= "\n\n" . $userMsg;
                        }
                        $res = $soap->addVanity($recipient, $smartstone_category, $smartstone_id, $finalMsg);
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