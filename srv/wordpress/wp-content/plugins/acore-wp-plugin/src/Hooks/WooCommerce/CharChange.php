<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class CharChange extends \ACore\Lib\WpClass {

    private static $skuList = array(
        "char-change", // used with variations
        "char-change-name",
        "char-change-faction",
        "char-change-race",
        "char-change-customize",
        "char-restore-delete"
    );

    public static function init() {
        add_action('woocommerce_after_add_to_cart_quantity', self::sprefix() . 'before_add_to_cart_button');
        add_filter('woocommerce_add_cart_item_data', self::sprefix() . 'add_cart_item_data', 20, 3);
        add_filter('woocommerce_get_item_data', self::sprefix() . 'get_item_data', 20, 2);
        add_action('woocommerce_checkout_order_processed', self::sprefix() . 'checkout_order_processed', 20, 2);
        add_action('woocommerce_add_order_item_meta', self::sprefix() . 'add_order_item_meta', 1, 3);
        add_action('woocommerce_payment_complete', self::sprefix() . 'payment_complete');
    }

    // 1) LIST
    public static function before_add_to_cart_button() {
        global $product;
        if (!in_array($product->get_sku(), self::$skuList)) {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            FieldElements::charList($current_user->user_login, self::isDeletedSKU($product->get_sku()));
        }
    }

    // 3) SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        if (!in_array($product->get_sku(), self::$skuList)) {
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

    // 4) Render on checkout
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

            $char = $charRepo->findOneByGuid($charId);

            $charName = $char
            ? (self::isDeletedSKU($cart_item['acore_item_sku']) ? $char->getDeletedName() : $char->getName())
            : "The character <$charId> doesn't exist!";

            $custom_items[] = array("name" => 'Character', "value" => $charName);
        }
        return $custom_items;
    }

    // 5) ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        if (!in_array($values['acore_item_sku'], self::$skuList)) {
            return;
        }

        if (isset($values['acore_char_sel']) && isset($values["acore_item_sku"])) {
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
            \wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
        }
    }

    // 6) check before payment
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

    // 7)DO THE FINAL ACTION
    public static function payment_complete($order_id) {
        $WoWSrv = ACoreServices::I();
        $logs = new \WC_Logger();
        try {
            $order = new \WC_Order($order_id);
            $items = $order->get_items();

            $soap = $WoWSrv->getCharactersSoap();

            foreach ($items as $item) {
                if (isset($item["acore_item_sku"])) {
                    switch ($item["acore_item_sku"]) {
                        case "char-change-name":
                            $charName = $WoWSrv->getCharName($item["acore_char_sel"]);
                            if (!$charName) {
                                throw new \Exception("No character found, please check your selection.");
                            }
                            $res = $soap->changeName($charName);
                            if ($res instanceof \Exception) {
                                throw new \Exception("There was an error with character rename on $charName - " . $res->getMessage());
                            }
                            break;
                        case "char-change-faction":
                            $charName = $WoWSrv->getCharName($item["acore_char_sel"]);
                            if (!$charName) {
                                throw new \Exception("No character found, please check your selection.");
                            }
                            $res = $soap->changeFaction($charName);
                            if ($res instanceof \Exception) {
                                throw new \Exception("There was an error with character change faction on $charName - " . $res->getMessage());
                            }
                            break;
                        case "char-change-race":
                            $charName = $WoWSrv->getCharName($item["acore_char_sel"]);
                            if (!$charName) {
                                throw new \Exception("No character found, please check your selection.");
                            }
                            $res = $soap->changeRace($charName);
                            if ($res instanceof \Exception) {
                                throw new \Exception("There was an error with character change race on $charName - " . $res->getMessage());
                            }
                            break;
                        case "char-change-customize":
                            $charName = $WoWSrv->getCharName($item["acore_char_sel"]);
                            if (!$charName) {
                                throw new \Exception("No character found, please check your selection.");
                            }
                            $res = $soap->charCustomization($charName);
                            if ($res instanceof \Exception) {
                                throw new \Exception("There was an error with character customization on $charName - " . $res->getMessage());
                            }
                            break;
                        case "char-restore-delete":
                            $charName = $WoWSrv->getCharName($item["acore_char_sel"], true);
                            $guid = $item["acore_char_sel"];
                            self::charRestore($guid, $charName);
                            break;
                    }
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }

    private static function charRestore($guid, $charName) {
        $soap = ACoreServices::I()->getCharactersSoap();
        $query = "SELECT `guid`, `name` FROM `characters` WHERE `characters`.`name` = ?";
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $queryResult = $conn->executeQuery($query, [$charName]);
        $chars = $queryResult->fetchAllAssociative();

        $newName = false;

        if ($chars && count($chars) > 0) {
            $res = $soap->charRestore($guid, $charName . "èè");
            $newName = true;
        } else {
            $res = $soap->charRestore($guid);
        }

        if ($res instanceof \Exception) {
            throw new \Exception("There was an error with character restore delete on $charName - " . $res->getMessage());
        } else if ($newName) {
            $res = $soap->changeName($charName . "èè");
            if ($res instanceof \Exception) {
                throw new \Exception("There was an error renaming your character " . $res->getMessage());
            }
        }
    }

    private static function isDeletedSKU($sku) {
        return $sku === 'char-restore-delete';
    }

}

CharChange::init();
