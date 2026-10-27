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
        "name-unlock" => ['acore_char_sel', 'acore_name_to_unlock'],
        // smartstone_<type>_<id>: validated in Smartstone::add_to_cart_validation (account-wide types need no character)
    );

    /**
     * The $skuList entry for a SKU: the SKU itself, or its prefix for SKUs with an id (itemsend_<id>,
     * transmog-item_<id>, transmog-itemset_<id>). Null when the SKU isn't a service.
     */
    private static function listKey($sku): ?string {
        $sku = strtolower((string) $sku);   // SKUs are case-insensitive (SkuLowercase.php)
        if (isset(self::$skuList[$sku])) {
            return $sku;
        }
        $prefix = explode('_', $sku)[0];
        return ($prefix !== $sku && isset(self::$skuList[$prefix])) ? $prefix : null;
    }

    public static function init() {
        add_action('woocommerce_add_to_cart_validation', self::sprefix() . 'add_to_cart_validation', 10, 5);
    }

    public static function add_to_cart_validation($flaq, $product_id, $quantity, $variation_id = null, $variations = null) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        $sku = strtolower((string) $product->get_sku());
        $activeSku = self::listKey($sku);
        if ($activeSku === null) {
            return true;
        }

        $current_user = wp_get_current_user();

        if (!$current_user || !$current_user->exists()) {
            \wc_add_notice(__('You must be logged to buy it!', 'acore-wp-plugin'), 'error');
            return false;
        }

        $ACoreSrv = ACoreServices::I();
        $accRepo = $ACoreSrv->getAccountRepo();
        $charRepo = $ACoreSrv->getCharactersRepo();
        $accBanRepo = $ACoreSrv->getAccountBannedRepo();
        $charBanRepo = $ACoreSrv->getCharactersBannedRepo();

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

        // Gift via smartstone token: for giftable char-change services, a
        // recipient name replaces the self character selection. Validate the
        // recipient and skip the remaining self-service field checks.
        if (CharChange::isGiftEligible($sku)) {
            $destName = isset($_REQUEST['acore_char_dest']) ? trim((string) $_REQUEST['acore_char_dest']) : '';
            if ($destName !== '') {
                return CharChange::validateGiftRecipient($destName);
            }
        }

        if (in_array('acore_char_sel', self::$skuList[$activeSku])) {
            $guid = intval($_REQUEST['acore_char_sel'] ?? 0);

            if ($guid === 0) {
                \wc_add_notice(__('No character selected. Please select a character and try again.', 'acore-wp-plugin'), 'error');
                return false;
            }

            $char = $charRepo->findOneByGuid($guid);

            if ($sku === 'char-restore-delete') {
                // a deleted character has no account; it must be one of this account's deleted characters
                $owned = false;
                foreach ((array) $charRepo->findByDeleteInfos_Account($accountId) as $deletedChar) {
                    if ($deletedChar->getGuid() == $guid) {
                        $owned = true;
                        break;
                    }
                }
            } else {
                $owned = $char && $char->getAccount() == $accountId;
            }
            if (!$char || !$owned) {
                \wc_add_notice(__('This character is not available in your account!', 'acore-wp-plugin'), 'error');
                return false;
            }

            if ($charBanRepo->isActiveByGuid($guid)) {
                \wc_add_notice(__('This character is banned!', 'acore-wp-plugin'), 'error');
                return false;
            }

            // race / faction change: the class must exist for another race of the same / the other faction
            $blocked = CharChange::serviceBlockReason($sku, $char->getRace(), $char->getClass());
            if ($blocked !== null) {
                \wc_add_notice($blocked, 'error');
                return false;
            }
        }

        if (in_array('acore_name_to_unlock', self::$skuList[$activeSku])) {
            if (trim((string) ($_REQUEST['acore_name_to_unlock'] ?? '')) === '') {
                \wc_add_notice(__('Please enter the character name you would like to unlock.', 'acore-wp-plugin'), 'error');
                return false;
            }
        }

        if (in_array('acore_dest_account', self::$skuList[$activeSku])) {
            $accountName = trim((string) ($_REQUEST['acore_dest_account'] ?? ''));

            $destAcc = $accRepo->findOneByUsername($accountName);

            if (!$char || $char->getAccount() != $accountId) {
                \wc_add_notice(__('This character is not in your account!', 'acore-wp-plugin'), 'error');
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

        if (in_array('acore_restore_item_sel', self::$skuList[$activeSku])) {
            $itemID = intval($_REQUEST['acore_restore_item_sel'] ?? 0);

            if ($itemID === 0) {
                \wc_add_notice(__('No item selected', 'acore-wp-plugin'), 'error');
                return false;
            }
        }

        return true;
    }
}

CartValidation::init();
