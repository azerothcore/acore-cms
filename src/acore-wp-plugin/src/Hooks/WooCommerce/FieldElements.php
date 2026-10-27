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
          return;
        }

        $accountId = $account->getId();
        $characters = $charRepo->findByAccount($accountId);
        $deletedCharacters = $charRepo->findByDeleteInfos_Account($accountId);
        $charBanRepo = $ACoreSrv->getCharactersBannedRepo();
        $accBanRepo = $ACoreSrv->getAccountBannedRepo();

        if ($accBanRepo->isActiveById($accountId)) {
            echo '<br><br><span style="color: #ff7961;">Your account is banned!</span><br>';
            return;
        }

        if ($deleted && count($deletedCharacters) == 0) {
            echo '<br><span style="color: red;">You have no deleted characters to restore.</span><br><br><br>';
            return;
        }

        if (!$deleted && (!$characters || count($characters) == 0)) {
            echo '<br><span style="color: red;">You have to create a character in-game to use this service</span><br><br><br>';
            return;
        }

        $bannedChars = array();

        // characters that can't use this service (greyed out), and the first one that can (selected, shown in the icons)
        $blocked = array();
        $firstChar = $deleted ? ($deletedCharacters[0] ?? null) : ($characters[0] ?? null);
        if (!$deleted && $blockedReason !== null) {
            $firstChar = null;
            foreach ($characters as $character) {
                if ($charBanRepo->isActiveByGuid($character->getGuid())) {
                    continue; // banned characters are listed separately
                }
                $reason = $blockedReason($character);
                if ($reason !== null) {
                    $blocked[$character->getGuid()] = $reason;
                } elseif ($firstChar === null) {
                    $firstChar = $character;
                }
            }
        }
        ?>
        <br>
        <br>
        <label for="acore_char_sel">Select the character: </label>
        <br>
        <?php if ($firstChar) { ?>
            <img id="char-icon" style="display: inline-block;max-height: 50px;" src="<?= ACORE_URL_PLG . "web/assets/race/" . $firstChar->getRace() . ($firstChar->getGender() == 0 ? "m" : "f") . ".webp"; ?>" />
            <img id="class-icon" style="display: inline-block;max-height: 50px;" src="<?= ACORE_URL_PLG . "web/assets/class/" . $firstChar->getClass() . ".webp"; ?>" />
        <?php } ?>
        <select id="acore_char_sel" class="acore_char_sel" name="acore_char_sel">
            <?php

            if (!$deleted) {
                foreach ($characters as $key => $value):
                    if ($charBanRepo->isActiveByGuid($value->getGuid())) {
                        $bannedChars[] = $value->getName();
                        continue;
                    }
                    $isBlocked  = isset($blocked[$value->getGuid()]);
                    $isSelected = $firstChar && $firstChar->getGuid() == $value->getGuid();
                    echo '<option value="' . $value->getGuid() . '" data-charicon="' . $value->getCharIconUrl() . '" data-classicon="' . $value->getClassIconUrl() . '"'
                        . ($isBlocked ? ' disabled' : '') . ($isSelected ? ' selected' : '') . '>'
                        . esc_html($value->getName()) . ($isBlocked ? ' (' . esc_html__('not possible', 'acore-wp-plugin') . ')' : '') . '</option>';
                endforeach;
            } else {
                foreach ($deletedCharacters as $key => $value):
                    $deletedLabel = $value->getDeletedName() . ' (' . $value->getLevel() . ')';
                    $deletedDate = $value->getDeleteDateFormatted();
                    if ($deletedDate !== '') {
                        $deletedLabel .= ' (' . $deletedDate . ')';
                    }
                    echo '<option value="' . $value->getGuid() . '" data-charicon="' . $value->getCharIconUrl() . '" data-classicon="' . $value->getClassIconUrl() . '">' . $deletedLabel . '</option>';
                endforeach;
            }

            ?>
        </select>
        <br>
        <br>
        <script>
            function setIcon() {
                const charicon = document.getElementById("char-icon");
                const classicon = document.getElementById("class-icon");
                const selected = document.querySelector("#acore_char_sel").selectedOptions[0];
                if (!selected || !charicon || !classicon) {
                    return false;
                }
                charicon.src = selected.dataset.charicon;
                classicon.src = selected.dataset.classicon;
                return false;
            }
            document.getElementById("acore_char_sel").onchange = setIcon;
        </script>
        <?php
        if (!empty($blocked) && $firstChar === null) {
            echo '<p style="color: #ff7961;">' . esc_html__('None of your characters can use this service.', 'acore-wp-plugin') . '</p>';
        }
        if ($bannedChars) {
            echo "Some characters in your account are banned: <br>";
            echo implode(",", $bannedChars) . "<br>";
        }
    }

    /**
     * Characters that can't use a service: race icon, class icon, name, then the reason.
     * $reasons: guid => reason (several reasons for one character can be passed as an array).
     */
    public static function blockedCharacterRows($characters, array $reasons): void {
        echo '<div class="acore-char-blocked" style="margin: 0.5em 0 1em;">';
        echo '<p style="margin: 0 0 0.4em; color: #ff7961;">' . esc_html__('Not possible for:', 'acore-wp-plugin') . '</p>';
        foreach ($characters as $character) {
            if (!isset($reasons[$character->getGuid()])) {
                continue;
            }
            foreach ((array) $reasons[$character->getGuid()] as $reason) {
                echo '<div title="' . esc_attr($reason) . '" style="display: flex; align-items: center; gap: 6px; margin: 0 0 0.35em; cursor: help;">'
                    . '<img src="' . esc_url($character->getCharIconUrl()) . '" alt="" style="width: 28px; height: 28px; opacity: 0.6;">'
                    . '<img src="' . esc_url($character->getClassIconUrl()) . '" alt="" style="width: 28px; height: 28px; opacity: 0.6;">'
                    . '<strong>' . esc_html($character->getName()) . '</strong>'
                    . '<span style="color: #ff7961;">' . esc_html($reason) . '</span>'
                    . '</div>';
            }
        }
        echo '</div>';
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
