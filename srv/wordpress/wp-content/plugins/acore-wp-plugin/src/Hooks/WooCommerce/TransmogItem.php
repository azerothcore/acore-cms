<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Components\Tools\ToolsApi;
use ACore\Manager\ACoreServices;

class TransmogItem extends \Acore\Lib\WpClass {

    private static $skuList = array(
        "transmogification"
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

        if ($product->get_sku() != "transmogification") {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            ?>
            <span>Please select the character you want to transmog gear on.</span>
            <?php
            FieldElements::charList($current_user->user_login);
            wp_enqueue_script('power-js', 'https://wow.zamimg.com/widgets/power.js', array());
            self::showTransmogItem();
        }
    }

    // SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);

        if ($product->get_sku() != "transmogification") {
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
        if ($values['acore_item_sku'] != "transmogification") {
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
                if ($item["acore_item_sku"] == "transmogification") {
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
                    if ($item["acore_item_sku"] == "transmogification") {
                        $itemId = $item["acore_transmog_item"];
                        $charGuid = $item["acore_char_sel"];

                        if (!$itemId) {
                            throw new \Exception("Fill in an item to transmog!");
                        }

                        $ACoreSrv = ACoreServices::I();
                        $charRepo = $ACoreSrv->getCharactersRepo();
                        $char = $charRepo->findOneByGuid($charGuid);

                        if ($char) {
                            $data = array('item' => $itemId, 'cname' => $char->getName());
                            ToolsApi::ItemTransmog($data);
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

    public static function showTransmogItem() {
        ?>
        <label for="acore_transmog_item">Please enter the item to transmog:</label>
        <input type="text" maxlength="24" id="acore_transmog_item" class="acore_transmog_item" name="acore_transmog_item" style="width: 300px;">
        <br><br>
        <div id="loader-icon">Loading...</div>
        <div id="item-list-no-content" class="alert alert-info hidden" role="alert">
            <span>That item doesn't excist</span>
        </div>
        <div class="table-responsive hidden" id="itemContainer" style="overflow: auto; height: 300px;">
            <table class="table table-bordered table-hover align-middle">
                <tbody style="background: #2c3338;" id="itemList">
                    <tr class="loading-item-list hidden">
                    </tr>
                </tbody>
            </table>
        </div>
        <br>

        <!-- 3D view of character here, not supported yet -->

        <script>
        const whTooltips = {colorLinks: true, iconizeLinks: true, renameLinks: true};

        // Register event listeners & element specifiers
        const itemContainer = document.getElementById('itemContainer');
        const itemList = document.getElementById('itemList');
        const itemListLoaders = document.querySelectorAll('.loading-item-list');
        const noResults =  document.getElementById('item-list-no-content');
        const loaderIcon = document.getElementById('loader-icon');
        const charList = document.querySelector("#acore_char_sel");
        const inputField = document.getElementById('acore_transmog_item');

        inputField.onkeypress = function() { transmogItem() };

        function transmogItem(charGuid) {
            noResults.style.display = 'none';
            loaderIcon.style.display = 'block';

            itemListLoaders.forEach(element => element.classList.remove('hidden'));
            itemContainer.classList.remove('hidden');
            const character = charGuid;
            const characterName = charList.options[charList.selectedIndex].innerText;

            fetch('<?= get_rest_url(null, 'acore/v1/item-transmog/list/'); ?>' + character)
            .then((response) => response.json())
            .then(function(items) {

                loaderIcon.style.display = 'none';

                if (!items || !items.length > 0) {
                    noResults.style.display = 'block';
                    itemList.style.display = 'none';
                    return;
                }
                
                document.querySelector("#itemList").innerHTML = "";
                itemList.style.display = 'block';

                items.forEach(item => {
                    const row = itemList.insertRow();
                    row.id = "row--" + item['Id'];

                    // Item
                    const itemCell = row.insertCell();
                    const itemLink = document.createElement('a');
                    itemLink.href = "#";
                    itemLink.setAttribute('data-wowhead', `item=${item['ItemEntry']}`);
                    itemLink.style.padding = "20px 0px 20px 66px";
                    itemLink.id = "row-item-" + item['Id'];
                    itemLink.className = "icon-item-transmog";
                    itemCell.appendChild(itemLink);
                });
            })
            .finally(() => {
                $WowheadPower.refreshLinks();
                itemListLoaders.forEach(element => element.classList.add('hidden'));

                // make larger the icon and fix css style
                setTimeout(() => {
                    document.querySelectorAll(".icon-item-transmog").forEach(itemImg => {
                    itemImg.style.background = itemImg.style.background.replace('.gif', '.jpg').replace('/tiny/', '/large/');
                    itemImg.style.paddingLeft = "66px";
                    });
                }, 1000);
            });
        }
        </script>
        <style>
                #loader-icon {
                    color: black;
                    font-size: 1.5em;
                    text-indent: -9999em;
                    overflow: hidden;
                    width: 1em;
                    height: 1em;
                    border-radius: 50%;
                    margin: 72px auto;
                    position: relative;
                    -webkit-transform: translateZ(0);
                    -ms-transform: translateZ(0);
                    transform: translateZ(0);
                    -webkit-animation: load6 1.7s infinite ease, round 1.7s infinite ease;
                    animation: load6 1.7s infinite ease, round 1.7s infinite ease;
                }
                @-webkit-keyframes load6 {
                0% {
                    box-shadow: 0 -0.83em 0 -0.4em, 0 -0.83em 0 -0.42em, 0 -0.83em 0 -0.44em, 0 -0.83em 0 -0.46em, 0 -0.83em 0 -0.477em;
                }
                5%,
                95% {
                    box-shadow: 0 -0.83em 0 -0.4em, 0 -0.83em 0 -0.42em, 0 -0.83em 0 -0.44em, 0 -0.83em 0 -0.46em, 0 -0.83em 0 -0.477em;
                }
                10%,
                59% {
                    box-shadow: 0 -0.83em 0 -0.4em, -0.087em -0.825em 0 -0.42em, -0.173em -0.812em 0 -0.44em, -0.256em -0.789em 0 -0.46em, -0.297em -0.775em 0 -0.477em;
                }
                20% {
                    box-shadow: 0 -0.83em 0 -0.4em, -0.338em -0.758em 0 -0.42em, -0.555em -0.617em 0 -0.44em, -0.671em -0.488em 0 -0.46em, -0.749em -0.34em 0 -0.477em;
                }
                38% {
                    box-shadow: 0 -0.83em 0 -0.4em, -0.377em -0.74em 0 -0.42em, -0.645em -0.522em 0 -0.44em, -0.775em -0.297em 0 -0.46em, -0.82em -0.09em 0 -0.477em;
                }
                100% {
                    box-shadow: 0 -0.83em 0 -0.4em, 0 -0.83em 0 -0.42em, 0 -0.83em 0 -0.44em, 0 -0.83em 0 -0.46em, 0 -0.83em 0 -0.477em;
                }
                }
                @keyframes load6 {
                0% {
                    box-shadow: 0 -0.83em 0 -0.4em, 0 -0.83em 0 -0.42em, 0 -0.83em 0 -0.44em, 0 -0.83em 0 -0.46em, 0 -0.83em 0 -0.477em;
                }
                5%,
                95% {
                    box-shadow: 0 -0.83em 0 -0.4em, 0 -0.83em 0 -0.42em, 0 -0.83em 0 -0.44em, 0 -0.83em 0 -0.46em, 0 -0.83em 0 -0.477em;
                }
                10%,
                59% {
                    box-shadow: 0 -0.83em 0 -0.4em, -0.087em -0.825em 0 -0.42em, -0.173em -0.812em 0 -0.44em, -0.256em -0.789em 0 -0.46em, -0.297em -0.775em 0 -0.477em;
                }
                20% {
                    box-shadow: 0 -0.83em 0 -0.4em, -0.338em -0.758em 0 -0.42em, -0.555em -0.617em 0 -0.44em, -0.671em -0.488em 0 -0.46em, -0.749em -0.34em 0 -0.477em;
                }
                38% {
                    box-shadow: 0 -0.83em 0 -0.4em, -0.377em -0.74em 0 -0.42em, -0.645em -0.522em 0 -0.44em, -0.775em -0.297em 0 -0.46em, -0.82em -0.09em 0 -0.477em;
                }
                100% {
                    box-shadow: 0 -0.83em 0 -0.4em, 0 -0.83em 0 -0.42em, 0 -0.83em 0 -0.44em, 0 -0.83em 0 -0.46em, 0 -0.83em 0 -0.477em;
                }
                }
                @-webkit-keyframes round {
                0% {
                    -webkit-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(360deg);
                    transform: rotate(360deg);
                }
                }
                @keyframes round {
                0% {
                    -webkit-transform: rotate(0deg);
                    transform: rotate(0deg);
                }
                100% {
                    -webkit-transform: rotate(360deg);
                    transform: rotate(360deg);
                }
                }
        </style>
        <?php
    }

}

TransmogItem::init();