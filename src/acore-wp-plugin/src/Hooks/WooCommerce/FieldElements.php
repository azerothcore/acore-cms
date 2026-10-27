<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class FieldElements {

    /**
     * The character dropdown. $blockedReason (optional): function ($character) returning why that character can't
     * use the service, or null - such characters are greyed out (not selectable). The caller lists them with the
     * reason (blockedCharacterRows).
     */
    public static function charList($username, $deleted = false, ?callable $blockedReason = null): void {
        $ACoreSrv = ACoreServices::I();
        $accRepo = $ACoreSrv->getAccountRepo();
        $charRepo = $ACoreSrv->getCharactersRepo();

        $account = $accRepo->findOneByUsername($username);
        if (!$account) {
          // if the account does not exist in the core database
          echo '<br><br><span style="color: #ff7961;">Please <a href="/wp-login.php">log-in</a> or <a href="/wp-login.php?action=register">register</a> an account.</span><br>';
          self::disableAddToCart();
          return;
        }

        $accountId = $account->getId();
        $characters = $charRepo->findByAccount($accountId);
        $deletedCharacters = $charRepo->findByDeleteInfos_Account($accountId);
        $charBanRepo = $ACoreSrv->getCharactersBannedRepo();
        $accBanRepo = $ACoreSrv->getAccountBannedRepo();

        if ($accBanRepo->isActiveById($accountId)) {
            echo '<br><br><span style="color: #ff7961;">Your account is banned!</span><br>';
            self::disableAddToCart();
            return;
        }

        if ($deleted && count($deletedCharacters) == 0) {
            echo '<br><span style="color: red;">You have no deleted characters to restore.</span><br><br><br>';
            self::disableAddToCart();
            return;
        }

        if (!$deleted && (!$characters || count($characters) == 0)) {
            echo '<br><span style="color: red;">You have to create a character in-game to use this service</span><br><br><br>';
            self::disableAddToCart();
            return;
        }

        $bannedChars = array();

        // the characters to choose from: banned ones are listed separately, the ones that can't use this service
        // ($blockedReason) are greyed out, and the first one that can is selected
        $blocked = array();
        $options = array();
        $firstChar = null;
        foreach (($deleted ? $deletedCharacters : $characters) as $character) {
            if (!$deleted && $charBanRepo->isActiveByGuid($character->getGuid())) {
                $bannedChars[] = $character->getName();
                continue;
            }
            if ($deleted) {
                $label = $character->getDeletedName() . ' (' . $character->getLevel() . ')';
                if ($character->getDeleteDateFormatted() !== '') {
                    $label .= ' (' . $character->getDeleteDateFormatted() . ')';
                }
            } else {
                $label = $character->getName();
            }
            $reason = (!$deleted && $blockedReason !== null) ? $blockedReason($character) : null;
            if ($reason !== null) {
                $blocked[$character->getGuid()] = $reason;
            } elseif ($firstChar === null) {
                $firstChar = $character;
            }
            $options[] = [$character, $label, $reason !== null];
        }
        ?>
        <br>
        <br>
        <label id="acore_char_sel_label" for="acore_char_sel">Select the character: </label>
        <br>
        <select id="acore_char_sel" class="acore_char_sel" name="acore_char_sel" style="display: none;" aria-hidden="true" tabindex="-1">
            <?php
            if ($firstChar === null) {
                echo '<option value="" selected disabled></option>';
            }
            foreach ($options as [$character, $label, $isBlocked]) {
                echo '<option value="' . esc_attr($character->getGuid()) . '" data-charicon="' . esc_url($character->getCharIconUrl()) . '" data-classicon="' . esc_url($character->getClassIconUrl()) . '"'
                    . ($isBlocked ? ' disabled' : '') . ($firstChar && $firstChar->getGuid() == $character->getGuid() ? ' selected' : '') . '>'
                    . esc_html($label) . ($isBlocked ? ' (' . esc_html__('not possible', 'acore-wp-plugin') . ')' : '') . '</option>';
            }
            ?>
        </select>
        <?php self::charPicker($options, $firstChar); ?>
        <br>
        <br>
        <?php
        if ($firstChar === null) {
            echo '<p style="color: #ff7961;">' . esc_html__('None of your characters can use this service.', 'acore-wp-plugin') . '</p>';
            self::disableAddToCart();
        }
        if ($bannedChars) {
            echo "Some characters in your account are banned: <br>";
            echo implode(",", $bannedChars) . "<br>";
        }
    }

    /**
     * The service can't be bought (no account / character): grey out the product form's "Add to cart" button.
     * The cart validation refuses it anyway; this only saves a pointless click.
     */
    private static function disableAddToCart(): void {
        ?>
        <script>
            (function (script) {
                const form = script.closest("form.cart") || document.querySelector("form.cart");
                const button = form && form.querySelector('button[type="submit"], .single_add_to_cart_button');
                if (button) {
                    button.disabled = true;
                    button.classList.add("disabled");
                    button.setAttribute("aria-disabled", "true");
                    button.style.opacity = "0.5";
                    button.style.cursor = "not-allowed";
                }
            })(document.currentScript);
        </script>
        <?php
    }

    /**
     * The character dropdown as the characters list shows them: race and class icons with their faction / class
     * borders, the name and the level. It drives the hidden #acore_char_sel select (value + change event), so the form
     * and any code listening to the select keep working. $options: [character, label, blocked] from charList().
     */
    private static function charPicker(array $options, $selected): void {
        $item = function ($character, $label, $isBlocked) {
            return '<span class="acore-char-picker-item">'
                . self::charIconsHtml($character->getRace(), $character->getClass(), $character->getCharIconUrl(), $character->getClassIconUrl(), 24)
                . '<span class="acore-char-picker-name">' . esc_html($label) . '</span>'
                . ($isBlocked ? '<span class="acore-char-picker-note">' . esc_html__('not possible', 'acore-wp-plugin') . '</span>' : '')
                . '<span class="acore-char-picker-level">' . esc_html(sprintf(__('Level %d', 'acore-wp-plugin'), $character->getLevel())) . '</span>'
                . '</span>';
        };
        static $styled = false;
        if (!$styled) {
            $styled = true;
            ?>
            <style>
                .acore-char-picker { position: relative; display: inline-block; width: 340px; max-width: 100%; }
                .acore-char-picker-btn { display: flex; align-items: center; gap: 8px; width: 100%; min-height: 44px; padding: 6px 10px; margin: 0;
                    background: var(--acore-surface, #fff); color: inherit; font: inherit; text-align: left; cursor: pointer;
                    border: 1px solid var(--acore-border, #8c8f94); border-radius: 6px; box-shadow: none; text-transform: none; letter-spacing: normal; }
                .acore-char-picker-btn:hover { background: var(--acore-surface, #fff); color: inherit; border-color: var(--acore-accent, #2271b1); }
                .acore-char-picker-btn:focus-visible { outline: 2px solid var(--acore-accent, #2271b1); outline-offset: 1px; }
                .acore-char-picker-current { display: block; flex: 1; min-width: 0; }
                .acore-char-picker-caret { flex: none; opacity: 0.7; }
                .acore-char-picker-list { position: absolute; z-index: 100; left: 0; right: 0; top: calc(100% + 4px); margin: 0; padding: 4px;
                    list-style: none; max-height: 340px; overflow-y: auto; background: var(--acore-surface, #fff);
                    border: 1px solid var(--acore-border, #8c8f94); border-radius: 6px; box-shadow: var(--acore-shadow, 0 6px 20px rgba(0, 0, 0, 0.25)); }
                .acore-char-picker-list[hidden] { display: none; }
                .acore-char-picker-list li { margin: 0 0 4px; padding: 4px 8px; border-radius: 4px; cursor: pointer; list-style: none; }
                .acore-char-picker-list li:last-child { margin-bottom: 0; }
                .acore-char-picker-list li:hover, .acore-char-picker-list li:focus { background: var(--acore-hover, rgba(0, 0, 0, 0.06)); outline: none; }
                .acore-char-picker-list li[aria-selected="true"] { background: var(--acore-accent-soft, rgba(34, 113, 177, 0.12)); }
                .acore-char-picker-list li[aria-disabled="true"] { opacity: 0.45; cursor: not-allowed; }
                .acore-char-picker-item { display: flex; align-items: center; gap: 8px; min-width: 0; }
                .acore-char-picker-name { font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                .acore-char-picker-note { font-size: 0.8em; color: #ff7961; white-space: nowrap; }
                .acore-char-picker-level { margin-left: auto; padding-left: 8px; font-size: 0.85em; opacity: 0.75; white-space: nowrap; }
            </style>
            <?php
        }
        ?>
        <div class="acore-char-picker">
            <button type="button" class="acore-char-picker-btn" aria-haspopup="listbox" aria-expanded="false" aria-labelledby="acore_char_sel_label">
                <span class="acore-char-picker-current">
                    <?php
                    if ($selected) {
                        echo $item($selected, $selected->getName(), false);
                    } else {
                        echo '<span class="acore-char-picker-item"><span class="acore-char-picker-note">' . esc_html__('No character can use this service', 'acore-wp-plugin') . '</span></span>';
                    }
                    ?>
                </span>
                <span class="acore-char-picker-caret" aria-hidden="true">&#9662;</span>
            </button>
            <ul class="acore-char-picker-list" role="listbox" aria-labelledby="acore_char_sel_label" hidden>
                <?php foreach ($options as [$character, $label, $isBlocked]) {
                    $isSelected = $selected && $selected->getGuid() == $character->getGuid();
                    echo '<li role="option" tabindex="-1" data-value="' . esc_attr($character->getGuid()) . '"'
                        . ' aria-selected="' . ($isSelected ? 'true' : 'false') . '"' . ($isBlocked ? ' aria-disabled="true"' : '')
                        . ' style="' . esc_attr(\ACore\Utils\AcoreCharColors::rowStyle($character->getClass(), $character->getRace())) . '">'
                        . $item($character, $label, $isBlocked) . '</li>';
                } ?>
            </ul>
        </div>
        <script>
            (function () {
                const select = document.getElementById("acore_char_sel");
                const picker = document.currentScript && document.currentScript.previousElementSibling;
                if (!select || !picker || !picker.classList.contains("acore-char-picker")) {
                    return;
                }
                const button = picker.querySelector(".acore-char-picker-btn");
                const list = picker.querySelector(".acore-char-picker-list");
                const items = Array.from(list.querySelectorAll("li"));
                const enabled = () => items.filter((li) => li.getAttribute("aria-disabled") !== "true");

                function render() {
                    items.forEach((li) => li.setAttribute("aria-selected", li.dataset.value === select.value ? "true" : "false"));
                    const current = items.find((li) => li.dataset.value === select.value);
                    if (current) {
                        const copy = current.querySelector(".acore-char-picker-item").cloneNode(true);
                        copy.querySelectorAll(".acore-char-picker-note").forEach((note) => note.remove());
                        picker.querySelector(".acore-char-picker-current").replaceChildren(copy);
                    }
                }
                function open(show) {
                    list.hidden = !show;
                    button.setAttribute("aria-expanded", show ? "true" : "false");
                    if (show) {
                        (items.find((li) => li.dataset.value === select.value) || enabled()[0] || list).focus();
                    }
                }
                function choose(li) {
                    if (!li || li.getAttribute("aria-disabled") === "true") {
                        return;
                    }
                    if (select.value !== li.dataset.value) {
                        select.value = li.dataset.value;
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                    open(false);
                    button.focus();
                }

                button.addEventListener("click", () => open(list.hidden));
                list.addEventListener("click", (event) => choose(event.target.closest("li")));
                list.addEventListener("keydown", (event) => {
                    const choices = enabled();
                    const index = choices.indexOf(document.activeElement);
                    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                        event.preventDefault();
                        const next = choices[(index + (event.key === "ArrowDown" ? 1 : -1) + choices.length) % choices.length];
                        if (next) next.focus();
                    } else if (event.key === "Enter" || event.key === " ") {
                        event.preventDefault();
                        choose(document.activeElement.closest("li"));
                    } else if (event.key === "Escape" || event.key === "Tab") {
                        open(false);
                        if (event.key === "Escape") button.focus();
                    }
                });
                button.addEventListener("keydown", (event) => {
                    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                        event.preventDefault();
                        open(true);
                    }
                });
                document.addEventListener("click", (event) => {
                    if (!picker.contains(event.target)) open(false);
                });
                select.addEventListener("change", render);
            })();
        </script>
        <?php
    }

    /**
     * Characters that can't use a service: race icon, class icon, name, then the reason.
     * $reasons: guid => reason (several reasons for one character can be passed as an array).
     */
    /**
     * A character's race and class icons with the same borders as the characters list: faction color around the race,
     * class color around the class (the classes are the ones main.css / the dark mode use, the borders are inline here
     * because main.css isn't loaded on product pages). $classId 0 = race icon only, $raceIcon = icon URL override.
     */
    public static function charIconsHtml(int $raceId, int $classId, string $raceIcon, string $classIcon = '', int $size = 24): string {
        $vars = \ACore\Utils\AcoreCharColors::iconVars($classId, $raceId);
        $img  = 'width: ' . $size . 'px; height: ' . $size . 'px; display: inline-block; vertical-align: middle; transform: translateZ(0); border: 3px solid; border-radius: 4px; box-sizing: content-box;';
        $html = '<span class="acore-char-meta" style="' . esc_attr($vars) . ' display: inline-flex; align-items: center; gap: 4px;">'
            . '<img class="race-icon" src="' . esc_url($raceIcon) . '" alt="' . esc_attr(\ACore\Utils\AcoreCharColors::getRaceName($raceId)) . '"'
            . ' title="' . esc_attr(\ACore\Utils\AcoreCharColors::getRaceName($raceId)) . '" style="' . $img . ' border-color: var(--faction-color);">';
        if ($classId && $classIcon !== '') {
            $html .= '<img class="class-icon" src="' . esc_url($classIcon) . '" alt="' . esc_attr(\ACore\Utils\AcoreCharColors::getClassName($classId)) . '"'
                . ' title="' . esc_attr(\ACore\Utils\AcoreCharColors::getClassName($classId)) . '" style="' . $img . ' border-color: var(--cls-light);">';
        }
        return $html . '</span>';
    }

    public static function blockedCharacterRows($characters, array $reasons): void {
        static $styled = false;
        if (!$styled) {
            $styled = true;
            echo '<style>
                .acore-char-blocked { margin: 0.75em 0 1.25em; padding: 12px 16px; max-width: 760px; border: 1px solid rgba(255, 121, 97, 0.3);
                    border-left: 4px solid #ff7961; border-radius: 6px; background: rgba(255, 121, 97, 0.07); }
                .acore-char-blocked-title { margin: 0 0 10px; font-weight: 600; color: #ff7961; }
                .acore-char-blocked-grid { display: grid; grid-template-columns: auto auto 1fr; column-gap: 12px; row-gap: 10px; align-items: center; }
                .acore-char-blocked-row { display: contents; }
                .acore-char-blocked-name { font-weight: 600; white-space: nowrap; }
                .acore-char-blocked-reason { font-size: 0.92em; line-height: 1.4; opacity: 0.85; }
                .acore-char-blocked-reason div + div { margin-top: 2px; }
                @media (max-width: 520px) {
                    .acore-char-blocked-grid { grid-template-columns: auto 1fr; }
                    .acore-char-blocked-reason { grid-column: 1 / -1; margin: -6px 0 4px; }
                }
            </style>';
        }
        echo '<div class="acore-char-blocked"><p class="acore-char-blocked-title">&#10008; ' . esc_html__('Not possible for:', 'acore-wp-plugin') . '</p>'
            . '<div class="acore-char-blocked-grid">';
        foreach ($characters as $character) {
            if (empty($reasons[$character->getGuid()])) {
                continue;
            }
            $lines = array_map(function ($reason) {
                return '<div>' . esc_html($reason) . '</div>';
            }, (array) $reasons[$character->getGuid()]);
            echo '<div class="acore-char-blocked-row">'
                . self::charIconsHtml($character->getRace(), $character->getClass(), $character->getCharIconUrl(), $character->getClassIconUrl(), 24)
                . '<span class="acore-char-blocked-name">' . esc_html($character->getName()) . '</span>'
                . '<span class="acore-char-blocked-reason">' . implode('', $lines) . '</span>'
                . '</div>';
        }
        echo '</div></div>';
    }

    public static function destCharacter($label): void {
        ?>
        <div class="acore-gift-field">
            <label for="acore_char_dest"><?= $label ?></label>
            <input type="text" placeholder="Character name..." id="acore_char_dest" class="acore_char_dest" name="acore_char_dest">
            <br>
        </div>
        <?php
    }

    public static function destAccount(): void {
        ?>
        <label for="acore_dest_account">Destination account: </label>
        <input required type="text" id="acore_dest_account" class="acore_dest_account" name="acore_dest_account">
        <br>
        <?php
    }

    public static function get3dViewer(int $itemId = 0): void {

        global $post;
        $is_3d_enabled = get_post_meta($post->ID, '_custom_3d_checkbox', true) === 'yes';
        $race = get_post_meta($post->ID, '_3d_race', true);
        $gender = get_post_meta($post->ID, '_3d_gender', true);
        $gender = $gender === '2' ? rand(0, 1) : $gender;
        $creatureDisplayId = get_post_meta($post->ID, '_3d_npc_displayid', true);
        $itemDisplayId = get_post_meta($post->ID, '_3d_item_displayid', true);
        $isSingleItem = get_post_meta($post->ID, '_3d_single_item', true)  === 'yes';

        if ($itemId === 0 && $creatureDisplayId === '') {
            return;
        }

        if (!$is_3d_enabled) {
            return;
        }

        ?>
        <script>$ = jQuery;</script>
        <script src="https://wowgaming.altervista.org/modelviewer/scripts/viewer.min.js"></script>
        <script type="module">
            import { generateModels } from "<?= ACORE_URL_PLG . "web/libraries/wow-model-viewer/index.js" ?>";

            function show3dModel(displayId, entity, inventoryType=0, race=1, gender=0) {
                let model;
                if (entity === 'item') {
                    <?php if ($isSingleItem) { ?>

                    const HEAD_INVENTORY_TYPE = 1;
                    const SHOULDER_INVENTORY_TYPE = 3;
                    const WEAPONS_INVENTORY_TYPE =[13,14,15,17,21,22,23,25,26];
                    let type;

                    if (WEAPONS_INVENTORY_TYPE.includes(inventoryType)) {
                        type = 1; // WEAPON
                    } else if (inventoryType === HEAD_INVENTORY_TYPE) {
                        type = 2; // HEAD
                    } else if (inventoryType === SHOULDER_INVENTORY_TYPE) {
                        type = 4; // SHOULDER
                    }
                
                    model = {
                        type,
                        id: displayId,
                    };
                    <?php } else { ?>
                        const character = {
                            race,
                            gender,
                            skin: 0,
                            face: 0,
                            hairStyle: 0,
                            hairColor: 0,
                            facialStyle: 0,
                            items: [
                                [inventoryType,  displayId],
                            ],
                        };
                        model = character;
                    <?php } ?>

                }
                else if (entity === 'npc') {
                    model = {
                        type: 8,
                        id: displayId,
                    };
                }

                const wow3dviewerId = 'acore-3d-viewer-<?= $itemId ?>';
                const productElementCSSclass = '.woocommerce-product-gallery';
                document.querySelector(productElementCSSclass).innerHTML = '';
                document.querySelector(productElementCSSclass).style.backgroundColor='black';
                document.querySelector(productElementCSSclass).id = wow3dviewerId;
                generateModels(1, `#${wow3dviewerId}`, model);
            }

            <?php if ($creatureDisplayId !== '') { ?>
            show3dModel(<?= $creatureDisplayId ?>, 'npc');
            <?php } else { ?>
            fetch('https://wowgaming.altervista.org/modelviewer/data/get-displayid.php?type=item&id=<?= $itemId ?>')
                .then(response => response.text())
                .then(data => {
                    let [displayId, entity, inventoryType] = data.split(',');

                    <?php if ($itemDisplayId !== '') { ?>
                        displayId = <?= $itemDisplayId ?>;
                    <?php } ?>

                    inventoryType = Number(inventoryType);
                    <?php if ($race !== '' && $gender !== '') { ?>
                        show3dModel(displayId, entity, inventoryType, <?= $race ?>, <?= $gender ?>);
                    <?php } else { ?>
                        show3dModel(displayId, entity, inventoryType);
                    <?php } ?>
                });
            <?php } ?>
        </script>

        <?php
    }

}
