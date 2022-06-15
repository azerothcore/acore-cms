<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\Opts;
use ACore\Manager\ACoreServices;

class NameUnlock extends \ACore\Lib\WpClass {
    private static $sku = "name-unlock";
    private static $errEmptyCharName = "Please enter the character name you would like to unlock.";
    private static $errCharDoesNotExist = "This character does not exist.";
    private static $errNameUnavailable = "This name is not eligible for an unlock request.";
    private static $errTempBanned = "This character is banned temporarily. Please try again at a later date.";
    private static $errPermaBanned = "This character has been banned permanently. Please contact support to make sure the name is appropriate.";

    public static function init() {
        add_action('woocommerce_after_add_to_cart_quantity', self::sprefix() . 'before_add_to_cart_button');
        add_filter('woocommerce_add_cart_item_data', self::sprefix() . 'add_cart_item_data', 20, 3);
        add_action('woocommerce_checkout_order_processed', self::sprefix() . 'checkout_order_processed', 20, 2);
        add_action('woocommerce_add_order_item_meta', self::sprefix() . 'add_order_item_meta', 1, 3);
        add_action('woocommerce_payment_complete', self::sprefix() . 'payment_complete');
    }

    // LIST
    public static function before_add_to_cart_button() {
        global $product;
        if ($product->get_sku() != self::$sku) {
            return;
        }

        $current_user = wp_get_current_user();

        ?>
        <p>Please enter the name you would like to unlock.</p>
        <label for="acore_name_to_unlock">Character name:</label>
        <br>
        <input type="text" maxlength="24" id="acore_name_to_unlock" class="acore_name_to_unlock" name="acore_name_to_unlock" style="width: 300px;">
        <br>
        <?php
        if ($current_user) {
            FieldElements::charList($current_user->user_login);
        }
    }

    // SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);

        if ($product->get_sku() != self::$sku || !isset($_REQUEST['acore_name_to_unlock']) || !isset($_REQUEST['acore_char_sel'])) {
            return $cart_item_data;
        }

        $charName = ucfirst(strtolower($_REQUEST['acore_name_to_unlock']));
        if (empty($charName)) {
            throw new \Exception(self::$errEmptyCharName);
        }

        $ACoreSrv = ACoreServices::I();
        $charRepo = $ACoreSrv->getCharactersRepo();
        $accountRepo = $ACoreSrv->getAccountRepo();
        $accountAccessRepo = $ACoreSrv->getAccountAccessRepo();
        $accountBannedRepo = $ACoreSrv->getAccountBannedRepo();

        // Check if the character exists
        $char = $charRepo->findOneByName($charName);
        if (!isset($char)) {
            throw new \Exception(self::$errCharDoesNotExist);
        }

        // Check if the character belongs to a GM account
        $qb = $accountAccessRepo->createQueryBuilder("aa");
        $rows = $qb->select("aa")
            ->where($qb->expr()->gt("aa.gmlevel", 0))
            ->andWhere($qb->expr()->eq("aa.id", $char->getAccount()))
            ->getQuery()
            ->getResult();
        if (!empty($rows)) {
            throw new \Exception(self::$errNameUnavailable);
        }

        // Check the last login date
        $acc = $accountRepo->findOneById($char->getAccount());
        $now = new \DateTime();
        $daysSinceLastLogin = $now->diff($acc->getLastLogin())->days;
        $thresholds = Opts::I()->acore_name_unlock_thresholds;
        foreach ($thresholds as $threshold) {
            [$thresholdLevel, $minDays] = $threshold;

            if ($thresholdLevel == "" || $minDays == "" || $char->getLevel() >= $thresholdLevel) {
                continue;
            }

            if ($daysSinceLastLogin < $minDays) {
                throw new \Exception(self::$errNameUnavailable);
            } else {
                break;
            }
        }

        // Check if the account is banned
        $bans = $accountBannedRepo->findById($char->getAccount());
        foreach ($bans as $ban) {
            if (!$ban->isActive()) {
                continue;
            }
            if ($ban->isPermanent()) {
                $allowedBannedNamesTable = Opts::I()->acore_name_unlock_allowed_banned_names_table;
                if ($allowedBannedNamesTable != "") {
                    $conn = $ACoreSrv->getCharacterEm()->getConnection();
                    $sql = "SELECT allowed_name FROM $allowedBannedNamesTable WHERE allowed_name = :name";
                    $stmt = $conn->executeQuery(
                        $sql,
                        ["name" => $char->getName()]
                    );
                    $row = $stmt->fetchAssociative();
                    if ($row === false) {
                        throw new \Exception(self::$errPermaBanned);
                    }
                } else {
                    throw new \Exception(self::$errPermaBanned);
                }
            } else {
                throw new \Exception(self::$errTempBanned);
            }
        }

        $cart_item_data['acore_char_sel'] = $_REQUEST['acore_char_sel'];
        $cart_item_data['acore_name_unlock_name'] = $char->getName();
        $cart_item_data['acore_name_unlock_id'] = $char->getGuid();
        $cart_item_data['acore_item_sku'] = $product->get_sku();
        // below statement make sure every add to cart action as unique line item
        $cart_item_data['unique_key'] = md5(microtime() . rand());

        return $cart_item_data;
    }

    // ADD DATA TO FINAL ORDER META
    // This is a piece of code that will add your custom field with order meta.
    public static function add_order_item_meta($item_id, $values, $cart_item_key) {
        if ($values['acore_item_sku'] != self::$sku) {
            return;
        }

        if (isset($values["acore_item_sku"])) {
            \wc_add_order_item_meta($item_id, "acore_name_unlock_name", $values['acore_name_unlock_name']);
            \wc_add_order_item_meta($item_id, "acore_name_unlock_id", $values['acore_name_unlock_id']);
            \wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
            \wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
        }
    }

    // Check before payment
    public static function checkout_order_processed($order_id, $posted_data) {
        $order = new \WC_Order($order_id);
        $items = $order->get_items();

        $soap = ACoreServices::I()->getServerSoap();

        foreach ($items as $item) {
            if ($item["acore_item_sku"] && $item["acore_item_sku"] == self::$sku) {
                $res = $soap->serverInfo();
                if ($res instanceof \Exception) {
                    throw new \Exception(__('The server seems to be offline, try again later!', 'woocommerce'));
                }
                break;
            }
        }
    }

    // DO THE FINAL ACTION
    public static function payment_complete($order_id) {
        $WoWSrv = ACoreServices::I();
        $logs = new \WC_Logger();
        try {
            $accSoap = $WoWSrv->getAccountSoap();
            $charSoap = $WoWSrv->getCharactersSoap();
            $charRepo = $WoWSrv->getCharactersRepo();
            $accRepo = $WoWSrv->getAccountRepo();
            $order = new \WC_Order($order_id);
            $items = $order->get_items();

            foreach ($items as $item) {
                if (isset($item["acore_item_sku"]) && $item["acore_item_sku"] == self::$sku) {
                    $charId = $item["acore_name_unlock_id"];
                    $char = $charRepo->findOneByGuid($charId);
                    $acc = $accRepo->findOneById($char->getAccount());
                    $accSoap->banAccount($acc->getUsername(), "1m", "Name Unlock");
                    $name = $char->getName();

                    $randomNameLen = 12;
                    $randomName = "";
                    $randomNameTaken = false;
                    do {
                        $randomName = ucfirst(substr(str_shuffle(str_repeat("abcdefghijklmnopqrstuvwxyz", $randomNameLen)), 0, $randomNameLen));
                        $randomNameTaken = $charRepo->findOneByName($randomName) != null;
                    } while ($randomNameTaken);

                    $charSoap->changeName($name, $randomName); // Force a random name to unlock the current one
                    $charSoap->changeName($randomName); // Give a free rename

                    $target = $WoWSrv->getCharName($item["acore_char_sel"]);
                    $charSoap->changeName($target, $name); // Rename the target with the unlocked name
                }
            }
        } catch (\Exception $e) {
            $logs->add("acore_log", $e->getMessage());
        }
    }
}

NameUnlock::init();
