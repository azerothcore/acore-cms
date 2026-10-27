<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;

class CharChange extends \ACore\Lib\WpClass {

    private static $skuList = array(
        "char-change", // used with variations
        "char-change-name",
        "char-change-faction",
        "char-change-race",
        "char-change-customize",
        "char-restore-delete"
    );

    /**
     * SKUs that can be gifted through the smartstone token system, mapped to
     * the mod's TokenType enum (src/Smartstone.h):
     *   1 = Rename, 2 = Faction change, 3 = Race change, 4 = Customize.
     * char-restore-delete has no token equivalent and is intentionally absent.
     */
    private static $tokenTypes = array(
        "char-change-name" => 1,
        "char-change-faction" => 2,
        "char-change-race" => 3,
        "char-change-customize" => 4,
    );

    /** Playable races per faction (WotLK 3.3.5). */
    private static $allianceRaces = [1, 3, 4, 7, 11];   // Human, Dwarf, Night Elf, Gnome, Draenei
    private static $hordeRaces    = [2, 5, 6, 8, 10];   // Orc, Undead, Tauren, Troll, Blood Elf

    private static $raceNames = [
        1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
        6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei',
    ];

    private static $classNames = [
        1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
        6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid',
    ];

    /** Races that can be each class (WotLK 3.3.5). Filter: acore_char_change_class_races. */
    private static function classRaces(): array {
        return apply_filters('acore_char_change_class_races', [
            1  => [1, 2, 3, 4, 5, 6, 7, 8, 11],        // Warrior
            2  => [1, 3, 10, 11],                      // Paladin
            3  => [2, 3, 4, 6, 8, 10, 11],             // Hunter
            4  => [1, 2, 3, 4, 5, 7, 8, 10],           // Rogue
            5  => [1, 3, 4, 5, 8, 10, 11],             // Priest
            6  => [1, 2, 3, 4, 5, 6, 7, 8, 10, 11],    // Death Knight
            7  => [2, 6, 8, 11],                       // Shaman
            8  => [1, 5, 7, 8, 10, 11],                // Mage
            9  => [1, 2, 5, 7, 10],                    // Warlock
            11 => [4, 6],                              // Druid
        ]);
    }

    /**
     * Why a race / faction change can't be used by a character of this race and class, or null when it can.
     * Race change: another race of the same faction must be able to be that class (so not Tauren / Night Elf
     * Druids, Blood Elf Paladins, Draenei Shamans). Faction change: a race of the other faction must be able to.
     */
    public static function serviceBlockReason($sku, int $race, int $class): ?string {
        if ($sku !== 'char-change-race' && $sku !== 'char-change-faction') {
            return null;
        }
        $classRaces = self::classRaces();
        if (!isset($classRaces[$class])) {
            return null;   // unknown (custom) class: leave it to the server
        }
        $alliance = in_array($race, self::$allianceRaces, true);
        $horde    = in_array($race, self::$hordeRaces, true);
        if (!$alliance && !$horde) {
            return null;   // unknown (custom) race
        }

        $own   = $alliance ? self::$allianceRaces : self::$hordeRaces;
        $other = $alliance ? self::$hordeRaces : self::$allianceRaces;
        $candidates = $sku === 'char-change-race'
            ? array_diff(array_intersect($own, $classRaces[$class]), [$race])
            : array_intersect($other, $classRaces[$class]);
        if (!empty($candidates)) {
            return null;
        }

        $raceName  = self::$raceNames[$race] ?? ('race ' . $race);
        $className = self::$classNames[$class] ?? ('class ' . $class);
        if ($sku === 'char-change-race') {
            return sprintf(
                __('A %1$s %2$s can\'t change race: no other %3$s race can be a %2$s.', 'acore-wp-plugin'),
                $raceName, $className, $alliance ? __('Alliance', 'acore-wp-plugin') : __('Horde', 'acore-wp-plugin')
            );
        }
        return sprintf(
            __('A %1$s %2$s can\'t change faction: no %3$s race can be a %2$s.', 'acore-wp-plugin'),
            $raceName, $className, $alliance ? __('Horde', 'acore-wp-plugin') : __('Alliance', 'acore-wp-plugin')
        );
    }

    /** On the product page: which of the account's characters can't use this race / faction change, and why. */
    private static function blockedCharactersNotice($sku, $username): void {
        $skus = $sku === 'char-change' ? ['char-change-race', 'char-change-faction'] : [$sku];
        $skus = array_intersect($skus, ['char-change-race', 'char-change-faction']);
        if (empty($skus)) {
            return;
        }
        try {
            $ACoreSrv = ACoreServices::I();
            $account  = $ACoreSrv->getAccountRepo()->findOneByUsername($username);
            $characters = $account ? $ACoreSrv->getCharactersRepo()->findByAccount($account->getId()) : [];
        } catch (\Throwable $e) {
            return;
        }
        $lines = [];
        foreach ((array) $characters as $char) {
            foreach ($skus as $serviceSku) {
                $reason = self::serviceBlockReason($serviceSku, $char->getRace(), $char->getClass());
                if ($reason !== null) {
                    $lines[] = '<strong>' . esc_html($char->getName()) . '</strong>: ' . esc_html($reason);
                }
            }
        }
        if (!empty($lines)) {
            echo '<p class="acore-char-change-blocked" style="color: #ff7961;">' . esc_html__('Not possible for:', 'acore-wp-plugin')
                . '<br>' . implode('<br>', $lines) . '</p>';
        }
    }

    private static function giftingEnabled(): bool {
        return Opts::I()->acore_smartstone_enabled == '1';
    }

    /** A concrete service SKU the buyer may gift (smartstone must be enabled). */
    public static function isGiftEligible($sku): bool {
        return self::giftingEnabled() && isset(self::$tokenTypes[$sku]);
    }

    /**
     * Whether to offer the gift field on the product page. Uses the display SKU,
     * which for the variable "char-change" product is the parent, so the base
     * SKU is accepted here too. Not every variation is giftable (deleted
     * character restoration is not): the field is hidden per variation client
     * side, and isGiftEligible gates the variation SKU server side.
     */
    private static function isGiftableDisplaySku($sku): bool {
        return self::giftingEnabled()
            && ($sku === 'char-change' || isset(self::$tokenTypes[$sku]));
    }

    /**
     * Validate a gift recipient (character name -> account). Adds a WooCommerce
     * notice and returns false on any problem. Mirrors the smartstone
     * account-wide gifting checks.
     */
    public static function validateGiftRecipient($destName): bool {
        $ACoreSrv = ACoreServices::I();

        $char = $ACoreSrv->getCharactersRepo()->findOneByName($destName);
        if (!$char) {
            \wc_add_notice(__('Recipient character not found.', 'acore-wp-plugin'), 'error');
            return false;
        }
        if ($ACoreSrv->getCharactersBannedRepo()->isActiveByGuid($char->getGuid())) {
            \wc_add_notice(__('Recipient character is banned.', 'acore-wp-plugin'), 'error');
            return false;
        }
        if ($ACoreSrv->getAccountBannedRepo()->isActiveById($char->getAccount())) {
            \wc_add_notice(__('Recipient account is banned.', 'acore-wp-plugin'), 'error');
            return false;
        }
        return true;
    }

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
            self::blockedCharactersNotice($product->get_sku(), $current_user->user_login);
        }

        // Optional gifting via smartstone token: leaving the field blank keeps
        // the normal self-service (instant apply to the selected character).
        if (self::isGiftableDisplaySku($product->get_sku())) {
            self::giftServiceField($product->is_type('variable'));
        }
    }

    /**
     * The recipient field starts collapsed behind a "Gift Service" button, since
     * buyers filled it in by reflex and ended up gifting by mistake. Collapsing
     * clears it, so a hidden name never rides along in the POST.
     *
     * On variable products the field renders once for the parent, but not every
     * variation is giftable (char-restore-delete has no token equivalent), so the
     * whole block hides while a non-giftable variation is selected.
     * isGiftEligible stays the server side gate either way.
     */
    private static function giftServiceField(bool $isVariable): void {
        ?>
        <div class="acore-gift-service">
            <button type="button" class="button acore-gift-toggle" aria-expanded="false" aria-controls="acore-gift-panel">
                <?= esc_html__('Gift Service', 'acore-wp-plugin') ?>
            </button>
            <div id="acore-gift-panel" class="acore-gift-panel" style="display: none; margin-top: 10px;">
                <?php FieldElements::destCharacter(__('Character to gift this service to:', 'acore-wp-plugin')); ?>
            </div>
            <br>
        </div>
        <script>
            jQuery(function ($) {
                var giftService = $('.acore-gift-service');
                var toggle = giftService.find('.acore-gift-toggle');
                var panel = giftService.find('.acore-gift-panel');

                function collapse(animate) {
                    toggle.attr('aria-expanded', 'false');
                    panel.find('input').val('');
                    animate ? panel.slideUp(200) : panel.hide();
                }

                // Browsers restore form values on reload/back, which would leave a hidden recipient.
                collapse(false);

                toggle.on('click', function () {
                    if (toggle.attr('aria-expanded') === 'true') {
                        collapse(true);
                        return;
                    }
                    toggle.attr('aria-expanded', 'true');
                    panel.slideDown(200, function () {
                        panel.find('input').trigger('focus');
                    });
                });

                <?php if ($isVariable) { ?>
                var giftableSkus = <?= json_encode(array_keys(self::$tokenTypes)) ?>;
                $('form.variations_form')
                    .on('found_variation', function (event, variation) {
                        var giftable = giftableSkus.indexOf(variation.sku) !== -1;
                        giftService.toggle(giftable);
                        if (!giftable) {
                            collapse(false);
                        }
                    })
                    .on('reset_data', function () {
                        giftService.hide();
                        collapse(false);
                    });
                <?php } ?>
            });
        </script>
        <?php
    }

    // 3) SAVE INTO ITEM DATA
    // This code will store the custom fields ( for the product that is being added to cart ) into cart item data
    // ( each cart item has their own data )
    public static function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = $variation_id ? \wc_get_product($variation_id) : \wc_get_product($product_id);
        $sku = $product->get_sku();
        if (!in_array($sku, self::$skuList)) {
            return $cart_item_data;
        }

        // Gift mode: a recipient name was supplied for a giftable service. Resolve
        // it to an account now so payment_complete can grant the token there.
        // add_to_cart_validation has already vetted existence/ban state.
        $destName = self::isGiftEligible($sku) && isset($_REQUEST['acore_char_dest'])
            ? trim((string) $_REQUEST['acore_char_dest'])
            : '';
        if ($destName !== '') {
            $cart_item_data['acore_item_sku'] = $sku;
            $cart_item_data['unique_key'] = md5(microtime() . rand());

            $ACoreSrv = ACoreServices::I();
            $char = $ACoreSrv->getCharactersRepo()->findOneByName($destName);
            if ($char) {
                $account = $ACoreSrv->getAccountRepo()->findOneById($char->getAccount());
                if ($account) {
                    $cart_item_data['acore_gift_account'] = $account->getUsername();
                    $cart_item_data['acore_gift_charname'] = $char->getName();
                }
            }
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

        if (!array_key_exists('acore_item_sku', $cart_item)) {
            return $custom_items;
        }

        if (!in_array($cart_item['acore_item_sku'], self::$skuList)) {
            return $custom_items;
        }

        if (isset($cart_item['acore_gift_account'])) {
            $giftCharname = isset($cart_item['acore_gift_charname'])
                ? $cart_item['acore_gift_charname']
                : '(recipient)';
            $custom_items[] = array("name" => 'Gift for', "value" => $giftCharname);
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

        // Gift: persist the resolved recipient so payment_complete can grant the
        // token. The account login is stored under an underscore-prefixed key so
        // WooCommerce keeps it out of customer-facing order details and emails;
        // only the character name is shown to the buyer. Charname is informational.
        if (isset($values['acore_gift_account'])) {
            wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
            wc_add_order_item_meta($item_id, "_acore_gift_account", $values['acore_gift_account']);
            if (isset($values['acore_gift_charname'])) {
                wc_add_order_item_meta($item_id, "acore_gift_charname", $values['acore_gift_charname']);
            }
            return;
        }

        if (isset($values['acore_char_sel']) && isset($values["acore_item_sku"])) {
            wc_add_order_item_meta($item_id, "acore_char_sel", $values['acore_char_sel']);
            wc_add_order_item_meta($item_id, "acore_item_sku", $values['acore_item_sku']);
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
                    // Gift: grant a token to the recipient's account instead of
                    // applying the change directly. The recipient redeems it in-game.
                    if (!empty($item["_acore_gift_account"])
                        && isset(self::$tokenTypes[$item["acore_item_sku"]])) {
                        $tokenType = self::$tokenTypes[$item["acore_item_sku"]];
                        $res = $WoWSrv->getSmartstoneSoap()->grantToken($item["_acore_gift_account"], $tokenType);
                        if ($res instanceof \Exception) {
                            throw new \Exception("There was an error granting a token to " . $item["_acore_gift_account"] . " - " . $res->getMessage());
                        }
                        continue;
                    }

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
