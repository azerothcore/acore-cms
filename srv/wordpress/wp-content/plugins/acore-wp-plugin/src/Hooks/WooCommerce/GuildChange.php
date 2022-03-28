<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class GuildChange extends \ACore\Lib\WpClass {

    private static $skuList = array(
        "guild-rename"
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
        if ($product->get_sku() != "guild-rename") {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            ?>
            <span>Please select the guild master of the guild whose name you wish to change.</span>
            <?php
            FieldElements::charList($current_user->user_login);
            ?>
            <label for="acore_new_guild_name">Please enter a new guild name:</label>
            <input type="text" maxlength="24" id="acore_new_guild_name" class="acore_new_guild_name" name="acore_new_guild_name" style="width: 300px;">
            <?php
        }
    }

    // 3) SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);

        if ($product->get_sku() != "guild-rename") {
            return $cart_item_data;
        }

        if (isset($_REQUEST['acore_char_sel']) && isset($_REQUEST['acore_new_guild_name'])) {
            $cart_item_data['acore_char_sel'] = $_REQUEST['acore_char_sel'];
            $cart_item_data['acore_new_guild_name'] = $_REQUEST['acore_new_guild_name'];
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
            $guildNewName = $cart_item['acore_new_guild_name'];

            $char = $charRepo->findOneByGuid($charId);

            $charName = $char ? $char->getName() : "The character <$charId> does not exist!";

            $custom_items[] = array("name" => 'Character', "value" => $charName);
            $custom_items[] = array("name" => 'New Guild Name', "value" => $guildNewName);
        }

        return $custom_items;
    }

    // 5) ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        if ($values['acore_item_sku'] != "guild-rename") {
            return;
        }

        if (isset($values['acore_char_sel']) && isset($values["acore_item_sku"])) {
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
            \wc_add_order_item_meta($item_id, "acore_new_guild_name", $values['acore_new_guild_name']);
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
                if ($item["acore_item_sku"] == "guild-rename") {
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

            $soap = $WoWSrv->getGuildSoap();
            
            foreach ($items as $item) {
                if (isset($item["acore_item_sku"])) {
                    if ($item["acore_item_sku"] == "guild-rename") {
                        $guildName = $WoWSrv->getGuildNameByLeader($item["acore_char_sel"]);

                        if (empty($guildName))
                        {
                            throw new \Exception("The character is not the guild master.");
                        }

                        $res = $soap->guildRename($guildName, $item["acore_new_guild_name"]);
                        if ($res instanceof \Exception) {
                            throw new \Exception("There was an error renaming your guild." . $res->getMessage());
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }

}

GuildChange::init();
