<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;
use ACore\Hooks\Various\ItemSku;

class ItemSend extends \ACore\Lib\WpClass {

    private static function getSkuItem($sku) {
        $parts = explode("_", $sku);

        if ($parts[0] != "itemsend" || !is_numeric($parts[1])) {
            return false;
        }

        $sku = new ItemSku();

        $sku->itemId = $parts[1];

        if (isset($parts[2]) && $parts[2] == "stack") {
            $sku->isStackable = true;
        }

        return $sku;
    }

    public static function init() {
        add_action('woocommerce_before_shop_loop_item_title', self::sprefix() . 'catalogue_list', 9999);
        add_filter('the_title', self::sprefix() . 'the_title', 20);
        add_action('woocommerce_after_add_to_cart_quantity', self::sprefix() . 'before_add_to_cart_button');
        add_filter('woocommerce_add_cart_item_data', self::sprefix() . 'add_cart_item_data', 20, 3);
        add_filter('woocommerce_get_item_data', self::sprefix() . 'get_item_data', 20, 2);
        add_action('woocommerce_checkout_order_processed', self::sprefix() . 'checkout_order_processed', 20, 2);
        add_action('woocommerce_add_order_item_meta', self::sprefix() . 'add_order_item_meta', 1, 3);
        add_action('woocommerce_payment_complete', self::sprefix() . 'payment_complete');
    }

    public static function catalogue_list() {
        global $product;
        $sku = self::getSkuItem($product->get_sku());
        if (!$sku) {
            return;
        }

        $link="https://wowgaming.altervista.org/aowow/?item=" . $sku->itemId;

        echo "<p><a href='$link' target='_blank'>Details</a></p>";
    }

    public static function the_title($title)/* : string | void */ {
        if (( \function_exists("\is_product") && \is_product() && \in_the_loop())) {
            global $product;
            $sku = self::getSkuItem($product->get_sku());
            if (!$sku) {
                return;
            }
            return "<a href='https://wowgaming.altervista.org/aowow/?item=" . $sku->itemId . "'>$title</a>";
        }

        // return the normal Title if conditions aren't met
        return $title;
    }

    // LIST
    public static function before_add_to_cart_button() {
        global $product;
        $sku = self::getSkuItem($product->get_sku());
        if (!$sku) {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            FieldElements::charList($current_user->user_login);
            ?>
            <br>
            <?php
            FieldElements::destCharacter(__("Or send it as a present for: ", 'acore-wp-plugin'));
            ?>
            <br>
            <label for="acore_msg_dest">Send a message (optional):</label>
            <textarea maxlength="200" id="acore_msg_dest" class="acore_msg_dest" name="acore_msg_dest"></textarea>
            <br>
            <br>
            <a target="_blank" href='https://wowgaming.altervista.org/aowow/?item=<?= $sku->itemId ?>'><?=__('Show details', 'acore-wp-plugin')?> </a>
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
        $sku = self::getSkuItem($product->get_sku());
        if (!$sku) {
            return $cart_item_data;
        }

        $charInfo = self::getCharInfo();

        $cart_item_data['acore_msg_dest'] = substr(sanitize_text_field($_REQUEST['acore_msg_dest']), 0, 200);
        $cart_item_data['acore_char_name'] = $charInfo["name"];
        $cart_item_data['acore_char_guid'] = $charInfo["guid"];
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

        $sku = self::getSkuItem($cart_item["acore_item_sku"]);
        if (!$sku) {
            return $custom_items;
        }

        if (isset($cart_item['acore_char_guid'])) {
            $ACoreSrv = ACoreServices::I();
            $charRepo = $ACoreSrv->getCharactersRepo();

            $charId = $cart_item['acore_char_guid'];

            $char = $charRepo->findOneByGuid($charId);

            $charName = $char ? $char->getName() : "Character <$charId> doesn't exist!";

            $custom_items[] = array("name" => 'Character', "value" => $charName);
            $custom_items[] = array("name" => 'Item', "value" => $sku->itemId);
            $custom_items[] = array("name" => 'Details', "value" => "<a target='_blank' href='https://wowgaming.altervista.org/aowow/?item=" . $sku->itemId . "'>Show details</a> ");
        }
        return $custom_items;
    }

    // ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        $sku = self::getSkuItem($values['acore_item_sku']);
        if (!$sku) {
            return;
        }

        wc_add_order_item_meta($item_id, "acore_msg_dest", $values['acore_msg_dest']);
        wc_add_order_item_meta($item_id, "acore_char_guid", $values['acore_char_guid']);
        wc_add_order_item_meta($item_id, "acore_char_name", $values['acore_char_name']);
        wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
    }

    // CHECK BEFORE PAYMENT
    public static function checkout_order_processed($order_id, $posted_data) {
        $logs = new \WC_Logger();

        $order = new \WC_Order($order_id);
        $items = $order->get_items();

        $soap = ACoreServices::I()->getServerSoap();

        foreach ($items as $item) {
            if ($item["acore_item_sku"]) {
                if (self::getSkuItem($item["acore_item_sku"])) {
                    $res = $soap->serverInfo();
                    if ($res instanceof \Exception) {
                        throw new \Exception(__('Sorry, the server seems to be offline, try again later!', 'acore-wp-plugin'));
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

            $soap = $WoWSrv->getGameMailSoap();

            foreach ($items as $item) {
                if (isset($item["acore_item_sku"])) {
                    $sku = self::getSkuItem($item["acore_item_sku"]);

                    if ($sku) {
                        $charName = $WoWSrv->getCharName($item["acore_char_guid"]);

                        $obj = "Shop Item";
                        $msg = $item["acore_msg_dest"];
                        if (empty($msg))
                            $msg = "This item has been sent from the Shop.";

                        $qty = $item["qty"];

                        $res = NULL;
                        if ($sku->isStackable) {
                            $res = $soap->sendItem($charName, $obj, $msg, $sku->itemId, $qty);
                            // todo: use a conf to switch with senditemAndBind()
                            // $res = $soap->sendItemAndBind($item["acore_char_guid"], $msg, $sku->itemId, $qty);
                        } else {
                            for ($i = 0; $i < $qty; $i++) {
                                $res = $soap->sendItem($charName, $obj, $msg, $sku->itemId, 1);
                                // $res = $soap->sendItemAndBind($item["acore_char_guid"], $msg, $sku->itemId, 1);
                            }
                        }

                        if ($res != "Mail sent to " . $charName) {
                            $logs->add("acore_log", print_r($res, true));
                        }

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

    private static function getCharInfo() {
        $WoWSrv = ACoreServices::I();
        $charRepo = $WoWSrv->getCharactersRepo();

        $guid = NULL;
        $name = "";
        if (isset($_REQUEST['acore_char_dest']) && $_REQUEST['acore_char_dest']) {
            $name = $_REQUEST['acore_char_dest'];
            if (!$name || $name == "") {
                throw new \Exception("No selected character");
            }
            $char = $charRepo->findOneByName($name);

            if (!$char) {
                throw new \Exception("No selected character");
            }

            $guid = $char->getGuid();
        } else {
            $guid = intval($_REQUEST['acore_char_sel']);
            if ($guid === 0) {
                throw new \Exception("No selected character");
            }
            $name = $WoWSrv->getCharName($guid);
        }

        return array("guid" => $guid, "name" => $name);
    }

}

ItemSend::init();
