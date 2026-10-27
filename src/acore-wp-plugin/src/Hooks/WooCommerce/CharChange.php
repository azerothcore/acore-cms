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
                __('%1$s %2$s can\'t change race: no other %3$s race can be a %2$s.', 'acore-wp-plugin'),
                $raceName, $className, $alliance ? __('Alliance', 'acore-wp-plugin') : __('Horde', 'acore-wp-plugin')
            );
        }
        return sprintf(
            __('%1$s %2$s can\'t change faction: no %3$s race can be a %2$s.', 'acore-wp-plugin'),
            $raceName, $className, $alliance ? __('Horde', 'acore-wp-plugin') : __('Alliance', 'acore-wp-plugin')
        );
    }

    /**
     * The races a character could become with a race / faction change: race => null when possible, or the reason.
     * Race change: the other races of its faction. Faction change: the races of the other faction.
     */
    private static function raceOptions($sku, int $race, int $class): array {
        $alliance = in_array($race, self::$allianceRaces, true);
        if (!$alliance && !in_array($race, self::$hordeRaces, true)) {
            return [];
        }
        $own     = $alliance ? self::$allianceRaces : self::$hordeRaces;
        $targets = $sku === 'char-change-race' ? $own : ($alliance ? self::$hordeRaces : self::$allianceRaces);
        $classRaces = self::classRaces();
        $className  = self::$classNames[$class] ?? ('class ' . $class);
        $options = [];
        foreach ($targets as $target) {
            $targetName = self::$raceNames[$target] ?? ('race ' . $target);
            if ($target === $race) {
                $options[$target] = __('Current race', 'acore-wp-plugin');
            } elseif (isset($classRaces[$class]) && !in_array($target, $classRaces[$class], true)) {
                $options[$target] = sprintf(__('%1$s %2$s isn\'t possible.', 'acore-wp-plugin'), $targetName, $className);
            } else {
                $options[$target] = null;
            }
        }
        return $options;
    }

    /**
     * Under the character dropdown: a table per faction of the races each character could become (check / cross,
     * with the reason as a tooltip), then the characters that can't use the service at all, in red.
     */
    private static function availabilityNotice($sku, $username): void {
        $skus = $sku === 'char-change' ? ['char-change-race', 'char-change-faction'] : [$sku];
        $skus = array_values(array_intersect($skus, ['char-change-race', 'char-change-faction']));
        if (empty($skus)) {
            return;
        }
        try {
            $ACoreSrv = ACoreServices::I();
            $account  = $ACoreSrv->getAccountRepo()->findOneByUsername($username);
            if (!$account || $ACoreSrv->getAccountBannedRepo()->isActiveById($account->getId())) {
                return;
            }
            $banRepo    = $ACoreSrv->getCharactersBannedRepo();
            $characters = [];
            foreach ((array) $ACoreSrv->getCharactersRepo()->findByAccount($account->getId()) as $char) {
                if (!$banRepo->isActiveByGuid($char->getGuid())) {
                    $characters[] = $char;
                }
            }
        } catch (\Throwable $e) {
            return;
        }
        if (empty($characters)) {
            return;
        }

        // every table gets the same name column (the longest name) and race columns, so they line up
        $nameChars = 0;
        foreach ($characters as $char) {
            $nameChars = max($nameChars, function_exists('mb_strlen') ? mb_strlen($char->getName()) : strlen($char->getName()));
        }

        $reasons = [];
        foreach ($skus as $serviceSku) {
            if (count($skus) > 1) {
                echo '<p style="margin: 0.5em 0 0.25em;"><strong>' . esc_html($serviceSku === 'char-change-race'
                    ? __('Race change', 'acore-wp-plugin') : __('Faction change', 'acore-wp-plugin')) . '</strong></p>';
            }
            // one table per faction of the target races, so each table only has the races of one faction
            $groups = [];
            foreach ($characters as $char) {
                $options = self::raceOptions($serviceSku, $char->getRace(), $char->getClass());
                if (empty($options)) {
                    continue;
                }
                $key = in_array(array_key_first($options), self::$allianceRaces, true) ? 'alliance' : 'horde';
                $groups[$key][] = [$char, $options];
                $reason = self::serviceBlockReason($serviceSku, $char->getRace(), $char->getClass());
                if ($reason !== null) {
                    $reasons[$char->getGuid()][] = $reason;
                }
            }
            foreach (['alliance' => self::$allianceRaces, 'horde' => self::$hordeRaces] as $key => $races) {
                if (!empty($groups[$key])) {
                    self::availabilityTable($races, $groups[$key], $nameChars);
                }
            }
        }
        if (!empty($reasons)) {
            FieldElements::blockedCharacterRows($characters, $reasons);
        }
    }

    private static function availabilityTable(array $races, array $rows, int $nameChars): void {
        $nameChars += 2; // bold text is a little wider than ch
        $cell = 'padding: 4px 6px; text-align: center; vertical-align: middle; overflow: hidden;';
        // fixed layout: name column = two bordered icons + the longest name, each race column 72px
        $nameWidth = 'calc(' . (int) $nameChars . 'ch + 90px)';
        $columns   = count($races);
        echo '<table class="acore-char-races" style="table-layout: fixed; width: calc(' . (int) $nameChars . 'ch + 90px + ' . ($columns * 72) . 'px); max-width: 100%; margin: 0.25em 0 0.75em; border-collapse: collapse;">'
            . '<colgroup><col style="width: ' . $nameWidth . ';">' . str_repeat('<col style="width: 72px;">', $columns) . '</colgroup>'
            . '<thead><tr><th style="' . $cell . '"></th>';
        foreach ($races as $race) {
            $name = self::$raceNames[$race] ?? ('race ' . $race);
            echo '<th style="' . $cell . ' font-weight: normal;" title="' . esc_attr($name) . '">'
                . FieldElements::charIconsHtml($race, 0, ACORE_URL_PLG . 'web/assets/race/' . $race . 'm.webp', '', 24) . '<br>'
                . '<small>' . esc_html($name) . '</small></th>';
        }
        echo '</tr></thead><tbody>';
        foreach ($rows as [$char, $options]) {
            echo '<tr><td style="' . $cell . ' text-align: left; white-space: nowrap;">'
                . FieldElements::charIconsHtml($char->getRace(), $char->getClass(), $char->getCharIconUrl(), $char->getClassIconUrl(), 24)
                . ' <strong style="vertical-align: middle;">' . esc_html($char->getName()) . '</strong></td>';
            foreach ($races as $race) {
                if (!array_key_exists($race, $options)) {
                    echo '<td style="' . $cell . '"></td>';
                } elseif ($race === $char->getRace()) {
                    echo '<td style="' . $cell . ' cursor: help;" title="' . esc_attr($options[$race]) . '">'
                        . FieldElements::charIconsHtml($char->getRace(), 0, $char->getCharIconUrl(), '', 20) . '</td>';
                } elseif ($options[$race] === null) {
                    echo '<td style="' . $cell . ' color: #4caf50; cursor: help;" title="' . esc_attr__('Possible', 'acore-wp-plugin') . '">&#10004;</td>';
                } else {
                    echo '<td style="' . $cell . ' color: #ff7961; cursor: help;" title="' . esc_attr($options[$race]) . '">&#10008;</td>';
                }
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
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
    /**
     * What a race / faction change or customization also includes: [text, optional "not included" service SKU
     * linked in the text]. Filter: acore_char_change_includes.
     */
    private static function includes($sku): ?array {
        $notes = apply_filters('acore_char_change_includes', [
            'char-change-faction'   => [__('A faction change also includes a character rename, a character customization and a race change.', 'acore-wp-plugin'), null],
            'char-change-race'      => [__('A race change also includes a character rename and a character customization. It does not include a %s.', 'acore-wp-plugin'), 'char-change-faction'],
            'char-change-customize' => [__('A customization includes a character rename, a gender change and face / skin customization.', 'acore-wp-plugin'), null],
        ]);
        return $notes[$sku] ?? null;
    }

    /** The includes text; $html links the service it doesn't include to its product page. */
    private static function includesText($sku, bool $html): string {
        $note = self::includes($sku);
        if (!$note) {
            return '';
        }
        [$text, $linkSku] = $note;
        if ($linkSku === null) {
            return $html ? esc_html($text) : $text;
        }
        $label = __('faction change', 'acore-wp-plugin');
        if (!$html) {
            return sprintf($text, $label);
        }
        $url = '';
        if (function_exists('wc_get_product_id_by_sku')) {
            $id = wc_get_product_id_by_sku($linkSku);
            if ($id) {
                $linked = wc_get_product($id);
                // a variation links to its parent product
                $url = $linked ? get_permalink($linked->get_parent_id() ?: $linked->get_id()) : '';
            }
        }
        $link = $url ? '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a>' : esc_html($label);
        return sprintf(esc_html($text), $link);
    }

    /** What the service includes: on the product page and in a pop-up on "Add to cart" (FieldElements::serviceNotice). */
    private static function includesNotice($sku, bool $isVariable): void {
        $skus  = $isVariable ? ['char-change-faction', 'char-change-race', 'char-change-customize'] : [$sku];
        $notes = [];
        foreach ($skus as $noteSku) {
            $text = self::includesText($noteSku, true);   // escaped in includesText
            if ($text !== '') {
                $notes[$noteSku] = $text;
            }
        }
        if (empty($notes)) {
            return;
        }
        FieldElements::serviceNotice($notes, $sku, $isVariable);
    }

    public static function before_add_to_cart_button() {
        global $product;
        if (!in_array($product->get_sku(), self::$skuList)) {
            return;
        }

        $current_user = wp_get_current_user();

        if ($current_user) {
            $sku = $product->get_sku();
            if ($sku === 'char-change-race' || $sku === 'char-change-faction') {
                // characters that can't use it are greyed out in the dropdown and listed with the reason
                FieldElements::charList($current_user->user_login, false, function ($character) use ($sku) {
                    return self::serviceBlockReason($sku, $character->getRace(), $character->getClass());
                });
            } else {
                // the variable "char-change" product: the option depends on the chosen variation, so only list them
                FieldElements::charList($current_user->user_login, self::isDeletedSKU($sku));
            }
            self::availabilityNotice($sku, $current_user->user_login);
        }

        self::includesNotice($product->get_sku(), $product->is_type('variable'));

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

        $includes = self::includesText($cart_item['acore_item_sku'], false);
        if ($includes !== '') {
            $custom_items[] = array("name" => __('Included', 'acore-wp-plugin'), "value" => $includes);
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
