<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class SmartstoneVanity extends \ACore\Lib\WpClass {

    /**
     * Service types (ActionType in mod-chromiecraft-smartstone src/Smartstone.h)
     * that the server's `.smartstone unlock account` command will accept.
     * These get unlocked account-wide and do NOT require a character selection.
     */
    const ACCOUNT_WIDE_CATEGORIES = [2 /* Costume */, 9 /* Perk */];

    private static function getItemId($sku) {
        if (!is_string($sku) || $sku === '') {
            return false;
        }
        $parts = explode("_", $sku);

        if (count($parts) < 3 || $parts[0] !== "smartstone" || !is_numeric($parts[1]) || !is_numeric($parts[2])) {
            return false;
        }

        $smartstone_category = (int) $parts[1];
        $smartstone_id = (int) $parts[2];

        return [$smartstone_category, $smartstone_id];
    }

    private static function isAccountWide($category) {
        return in_array((int) $category, self::ACCOUNT_WIDE_CATEGORIES, true);
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

        // Any product whose SKU claims to be a smartstone item requires the buyer to
        // be logged in, regardless of whether the rest of the SKU parses cleanly.
        // Fail closed on the login check before falling back to other validators.
        if (!is_user_logged_in()) {
            \wc_add_notice(__('You must be logged in to buy it!', 'acore-wp-plugin'), 'error');
            return false;
        }

        $parsed = self::getItemId($sku);
        if (!$parsed) {
            return $passed; // malformed SKU; let WooCommerce/other validators handle it
        }
        [$smartstone_category, $smartstone_id] = $parsed;

        // Account-wide services (costumes, perks) unlock via `.smartstone unlock account`
        // and do not need a character selection.
        if (self::isAccountWide($smartstone_category)) {
            return $passed;
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
        $parsed = self::getItemId($product->get_sku());
        if (!$parsed) {
            return;
        }
        [$smartstone_category, $smartstone_id] = $parsed;

        FieldElements::get3dViewer();

        // Account-wide unlocks (costumes, perks) don't need a character selector.
        if (self::isAccountWide($smartstone_category)) {
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
        $parsed = self::getItemId($product->get_sku());
        if (!$parsed) {
            return $cart_item_data;
        }
        [$smartstone_category, $smartstone_id] = $parsed;

        if (self::isAccountWide($smartstone_category)) {
            // No character selector for account-wide unlocks; still stash the SKU
            // and force a unique line so the cart treats it as its own row.
            $cart_item_data['acore_item_sku'] = $product->get_sku();
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        } else if (isset($_REQUEST['acore_char_sel'])) {
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

        if (empty($cart_item["acore_item_sku"])) {
            return $custom_items;
        }
        $parsed = self::getItemId($cart_item["acore_item_sku"]);
        if (!$parsed) {
            return $custom_items;
        }
        [$smartstone_category, $smartstone_id] = $parsed;

        // Indexes match mod-chromiecraft-smartstone's ActionType enum (src/Smartstone.h):
        //   0 = Companion, 1 = Pet, 2 = Costume, 7 = Vehicle, 8 = Mount, 9 = Perk
        $categoryToText = array(
            0 => "Pet",
            1 => "Combat Pet",
            2 => "Costume",
            9 => "Class Perk",
        );
        $label = isset($categoryToText[$smartstone_category])
            ? $categoryToText[$smartstone_category]
            : "Smartstone item";

        if (self::isAccountWide($smartstone_category)) {
            $custom_items[] = array("name" => 'Unlock for', "value" => 'Entire account');
            $custom_items[] = array("name" => $label, "value" => $smartstone_id);
        } else if (isset($cart_item['acore_char_sel'])) {
            $ACoreSrv = ACoreServices::I();
            $charRepo = $ACoreSrv->getCharactersRepo();

            $charId = $cart_item['acore_char_sel'];
            $char = $charRepo->findOneByGuid($charId);
            $charName = $char ? $char->getName() : "Character <$charId> doesn't exist!";

            $custom_items[] = array("name" => 'Character', "value" => $charName);
            $custom_items[] = array("name" => $label, "value" => $smartstone_id);
        }
        return $custom_items;
    }

    // ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        if (empty($values['acore_item_sku'])) {
            return;
        }
        $parsed = self::getItemId($values['acore_item_sku']);
        if (!$parsed) {
            return;
        }
        [$smartstone_category, $smartstone_id] = $parsed;

        \wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);

        // Character is only relevant for per-character unlocks. For account-wide
        // categories, the buyer's account is derived from the order customer in payment_complete.
        if (!self::isAccountWide($smartstone_category) && isset($values['acore_char_sel'])) {
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
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

            // Resolve buyer's AC account name once (needed for account-wide unlocks).
            $accountName = null;
            $customerId = $order->get_customer_id();
            if ($customerId) {
                $buyer = \get_user_by('id', $customerId);
                if ($buyer) {
                    $accountName = $buyer->user_login;
                }
            }

            foreach ($items as $item) {
                if (!isset($item["acore_item_sku"])) {
                    continue;
                }
                $parsed = self::getItemId($item["acore_item_sku"]);
                if (!$parsed) {
                    continue;
                }
                [$smartstone_category, $smartstone_id] = $parsed;

                if (self::isAccountWide($smartstone_category)) {
                    if (!$accountName) {
                        throw new \Exception("Smartstone account-wide unlock requires a buyer with a linked AC account; order $order_id has none.");
                    }
                    $res = $soap->addAccountVanity($accountName, $smartstone_category, $smartstone_id);
                } else {
                    if (empty($item["acore_char_sel"])) {
                        throw new \Exception("Smartstone per-character unlock missing acore_char_sel on item in order $order_id.");
                    }
                    $charName = $WoWSrv->getCharName($item["acore_char_sel"]);
                    $res = $soap->addVanity($charName, $smartstone_category, $smartstone_id);
                }

                if ($res instanceof \Exception) {
                    throw $res;
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }
}

SmartstoneVanity::init();