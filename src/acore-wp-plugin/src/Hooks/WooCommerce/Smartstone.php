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
        // Smartstone SKUs are one-shot unlocks (per-character for cat 0/1, per-account
        // for cat 2/9), so the quantity selector is meaningless. Cap it at 1 on both
        // the product page and the cart row. Multiple gift lines for the same SKU are
        // still possible via unique_key in add_cart_item_data.
        add_filter('woocommerce_quantity_input_args', [__CLASS__, 'quantity_input_args'], 10, 2);
        add_filter('woocommerce_cart_item_quantity', [__CLASS__, 'cart_item_quantity'], 10, 3);
    }

    /**
     * Lock the product-page quantity input at 1 for smartstone SKUs.
     * When min == max, WC renders the input as plain text (no +/- buttons).
     */
    public static function quantity_input_args($args, $product) {
        if ($product && strpos((string) $product->get_sku(), 'smartstone') === 0) {
            $args['min_value']   = 1;
            $args['max_value']   = 1;
            $args['input_value'] = 1;
        }
        return $args;
    }

    /**
     * Lock the cart-row quantity at 1 for smartstone line items. Returning a
     * plain string here replaces WC's quantity input with non-editable text.
     */
    public static function cart_item_quantity($product_quantity, $cart_item_key, $cart_item) {
        if (!empty($cart_item['acore_item_sku'])
            && strpos((string) $cart_item['acore_item_sku'], 'smartstone') === 0) {
            return '1';
        }
        return $product_quantity;
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
        // and do not need a character selection. They optionally accept a recipient
        // character name (gifting) — validate it here if provided.
        if (self::isAccountWide($smartstone_category)) {
            $destName = isset($_REQUEST['acore_char_dest']) ? trim((string) $_REQUEST['acore_char_dest']) : '';
            if ($destName !== '') {
                $ACoreSrv      = ACoreServices::I();
                $charRepo      = $ACoreSrv->getCharactersRepo();
                $charBanRepo   = $ACoreSrv->getCharactersBannedRepo();
                $accBanRepo    = $ACoreSrv->getAccountBannedRepo();

                $char = $charRepo->findOneByName($destName);
                if (!$char) {
                    \wc_add_notice(__('Recipient character not found.', 'acore-wp-plugin'), 'error');
                    return false;
                }
                if ($charBanRepo->isActiveByGuid($char->getGuid())) {
                    \wc_add_notice(__('Recipient character is banned.', 'acore-wp-plugin'), 'error');
                    return false;
                }
                if ($accBanRepo->isActiveById($char->getAccount())) {
                    \wc_add_notice(__('Recipient account is banned.', 'acore-wp-plugin'), 'error');
                    return false;
                }
                // Self-gift (recipient char belongs to the buyer's own account) is
                // silently treated as a regular self-unlock; nothing to error on here.
            }
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

        // Account-wide unlocks (costumes, perks) don't need a self character
        // selector, but they support gifting to a character on another account.
        if (self::isAccountWide($smartstone_category)) {
            FieldElements::destCharacter(__('Gift to a character (optional — leave blank to unlock for your own account):', 'acore-wp-plugin'));
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

            // If a recipient character was supplied, resolve it to an account
            // name now so payment_complete can route the SOAP call there.
            // add_to_cart_validation has already vetted existence/ban state.
            $destName = isset($_REQUEST['acore_char_dest']) ? trim((string) $_REQUEST['acore_char_dest']) : '';
            if ($destName !== '') {
                $ACoreSrv = ACoreServices::I();
                $char = $ACoreSrv->getCharactersRepo()->findOneByName($destName);
                if ($char) {
                    $account = $ACoreSrv->getAccountRepo()->findOneById($char->getAccount());
                    $current_user = wp_get_current_user();
                    if ($account && $current_user
                        && strcasecmp($account->getUsername(), $current_user->user_login) !== 0) {
                        // Real gift: recipient belongs to a different account.
                        $cart_item_data['acore_gift_account']  = $account->getUsername();
                        $cart_item_data['acore_gift_charname'] = $char->getName();
                    }
                    // Self-gift falls through with no gift_* fields => self-unlock.
                }
            }
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
            if (isset($cart_item['acore_gift_account'])) {
                $giftCharname = isset($cart_item['acore_gift_charname'])
                    ? $cart_item['acore_gift_charname']
                    : '(recipient)';
                $custom_items[] = array("name" => 'Gift for', "value" => $giftCharname);
            } else {
                $custom_items[] = array("name" => 'Unlock for', "value" => 'Entire account');
            }
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

        // Gifting: persist the resolved recipient so payment_complete can route
        // the SOAP unlock to their account. Charname is informational (for admin
        // order view); only acore_gift_account is functional.
        if (self::isAccountWide($smartstone_category) && isset($values['acore_gift_account'])) {
            \wc_add_order_item_meta($item_id, "acore_gift_account", $values['acore_gift_account']);
            if (isset($values['acore_gift_charname'])) {
                \wc_add_order_item_meta($item_id, "acore_gift_charname", $values['acore_gift_charname']);
            }
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
                    // Gifted items route to the recipient's account; otherwise unlock
                    // for the buyer. Resolution + ban-check happened at add-to-cart time.
                    $target = !empty($item['acore_gift_account'])
                        ? $item['acore_gift_account']
                        : $accountName;
                    if (!$target) {
                        throw new \Exception("Smartstone account-wide unlock requires a buyer with a linked AC account; order $order_id has none.");
                    }
                    $res = $soap->addAccountVanity($target, $smartstone_category, $smartstone_id);
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