<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class CartValidation extends \ACore\Lib\WpClass {

    private static $skuList = array(
        "char-change" => ['acore_char_sel'],
        "char-change-name" => ['acore_char_sel'],
        "char-change-faction" => ['acore_char_sel'],
        "char-change-race" => ['acore_char_sel'],
        "char-change-customize" => ['acore_char_sel'],
        "char-restore-delete" => ['acore_char_sel'],
        "char-transfer-sku" => ['acore_char_sel', 'acore_dest_account'],
        "itemsend" => ['acore_char_sel'],
        "guild-rename" => ['acore_char_sel', 'acore_new_guild_name'],
        "item-restoration" => ['acore_char_sel', 'acore_restore_item_sel'],
        "carboncopy-tickets" => [],
        "transmog-item" => ['acore_char_sel'],
        "transmog-itemset" => ['acore_char_sel'],
        "name-unlock" => ['acore_unlock_name'],
    );

    public static function init() {
        add_action('woocommerce_add_to_cart_validation', self::sprefix() . 'add_to_cart_validation', 10, 5);
    }

    public static function add_to_cart_validation($flaq, $product_id, $quantity, $variation_id = null, $variations = null) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        $sku = $product->get_sku();
        if (!isset(self::$skuList[$sku]) && strpos($sku, "itemsend") === false) {
            return true;
        }

        $current_user = wp_get_current_user();

        if (!$current_user) {
            \wc_add_notice(__('You must be logged to buy it!', 'acore-wp-plugin'), 'error');
            return false;
        }

        $activeSku = isset(self::$skuList[$sku]) ? $sku : "itemsend";

        $ACoreSrv = ACoreServices::I();
        $accRepo = $ACoreSrv->getAccountRepo();
        $charRepo = $ACoreSrv->getCharactersRepo();
        $accBanRepo = $ACoreSrv->getAccountBannedRepo();

        $account = $accRepo->findOneByUsername($current_user->user_login);

        if (!isset($account)) {
            \wc_add_notice(__('Account not found. Please reconnect and try again.', 'woocommerce'), 'error');
            return false;
        }

        $accountId = $account->getId();

        if ($accBanRepo->isActiveById($accountId)) {
            \wc_add_notice(__('This account is banned!', 'woocommerce'), 'error');
            return false;
        }

        if (in_array('acore_char_sel', self::$skuList[$activeSku])) {
            $guid = intval($_REQUEST['acore_char_sel']);

            if ($guid === 0) {
                \wc_add_notice(__('No selected character', 'acore-wp-plugin'), 'error');
                return false;
            }

            $char = $charRepo->findOneByGuid($guid);

            if (!$char || ($char->getAccount() != $accountId && $sku !== 'char-restore-delete')) {
                \wc_add_notice(__('This character is not available in your account!', 'acore-wp-plugin'), 'error');
                return false;
            }
        }

        if (in_array('acore_dest_account', self::$skuList[$activeSku])) {
            $accountName = $_REQUEST['acore_dest_account'];

            $destAcc = $accRepo->findOneByUsername($accountName);

            if (!$char || $char->getAccount() != $accountId) {
                \wc_add_notice(__('This character is not in your account!', 'acore-wp-plugin'), 'error');
                return false;
            }

            if ($charBanRepo->isActiveByGuid($guid)) {
                \wc_add_notice(__('This character is banned!', 'acore-wp-plugin'), 'error');
                return false;
            }

            if (!$destAcc || $destAcc->getId() == $accountId) {
                \wc_add_notice(__('Destination account not valid!', 'woocommerce'), 'error');
                return false;
            }

            if ($accBanRepo->isActiveById($destAcc->getId())) {
                \wc_add_notice(__('Destination account is banned!', 'woocommerce'), 'error');
                return false;
            }
        }

        if (in_array('acore_new_guild_name', self::$skuList[$activeSku])) {
            $guid = intval($_REQUEST['acore_char_sel']);

            if (empty($ACoreSrv->getGuildNameByLeader($guid))) {
                \wc_add_notice(__('The character is not a guild master.', 'acore-wp-plugin'), 'error');
                return false;
            }
        }

        return true;
    }
}

CartValidation::init();
