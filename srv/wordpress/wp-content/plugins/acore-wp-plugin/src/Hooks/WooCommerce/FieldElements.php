<?php

namespace ACore\Hooks\WooCommerce;

use ACore\Manager\ACoreServices;

class FieldElements {

    public static function charList($username, $deleted = false): void {
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

        $bannedChars = array();
        ?>
        <label for="acore_char_sel">Select the character: </label>
        <img id="char-icon" src="<" />
        <img id="class-icon" src="" />
        <select id="acore_char_sel" class="acore_char_sel" name="acore_char_sel">
            <?php

            if (!$deleted) {
                foreach ($characters as $key => $value):
                    if ($charBanRepo->isActiveByGuid($value->getGuid())) {
                        $bannedChars[] = $value->getName();
                        continue;
                    }
                    echo '<option value="' . $value->getGuid() . '" data-charicon="' . ACORE_URL_PLG . "web/assets/race/" . $value->getRace() . ($value->getGender() == 0 ? "m" : "f") . ".webp" . '" data-classicon="' . ACORE_URL_PLG . "web/assets/class/" . $value->getClass() . ".webp" . '">' . $value->getName() . '</option>';
                endforeach;
            } else {
                foreach ($deletedCharacters as $key => $value):
                    echo '<option value="' . $value->getGuid() . '" data-charicon="' . ACORE_URL_PLG . "web/assets/race/" . $value->getRace() . ($value->getGender() == 0 ? "m" : "f") . ".webp" . '" data-classicon="' . ACORE_URL_PLG . "web/assets/class/" . $value->getClass() . ".webp" . '">' . $value->getDeletedName() . '</option>';
                endforeach;
            }

            ?>
        </select>
        <br>
        <script>
            function setIcon() {
                var charicon = document.getElementById("char-icon");
                var classicon = document.getElementById("class-icon");
                var selected = jQuery(this).find("option:selected");
                charicon.src = selected.data("charicon");
                classicon.src = selected.data("classicon")
                return false;
            }
            document.getElementById("acore_char_sel").onchange = setIcon;
        </script>
        <?php
        if ($bannedChars) {
            echo "Some characters in your account are banned: <br>";
            echo implode(",", $bannedChars) . "<br>";
        }
    }

    public static function destCharacter($label): void {
        ?>
        <label for="acore_char_dest"><?= $label ?></label>
        <input type="text" placeholder="Character name..." id="acore_char_dest" class="acore_char_dest" name="acore_char_dest">
        <br>
        <?php
    }

    public static function destAccount(): void {
        ?>
        <label for="acore_dest_account">Destination account: </label>
        <input required type="text" id="acore_dest_account" class="acore_dest_account" name="acore_dest_account">
        <br>
        <?php
    }

}
