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

        if (!$deleted && (!$characters || count($characters) == 0)) {
            echo '<br><span style="color: red;">You have to create a character in-game to use this service</span><br><br><br>';
            return;
        }

        $bannedChars = array();
        ?>
        <br>
        <br>
        <label for="acore_char_sel">Select the character: </label>
        <br>
        <?php  if ($characters && count($characters) > 0) { ?>
            <img id="char-icon" style="display: inline-block;max-height: 50px;" src="<?= ACORE_URL_PLG . "web/assets/race/" . $characters[0]->getRace() . ($characters[0]->getGender() == 0 ? "m" : "f") . ".webp"; ?>" />
            <img id="class-icon" style="display: inline-block;max-height: 50px;" src="<?= ACORE_URL_PLG . "web/assets/class/" . $characters[0]->getClass() . ".webp"; ?>" />
        <?php  } ?>
        <select id="acore_char_sel" class="acore_char_sel" name="acore_char_sel">
            <?php

            if (!$deleted) {
                foreach ($characters as $key => $value):
                    if ($charBanRepo->isActiveByGuid($value->getGuid())) {
                        $bannedChars[] = $value->getName();
                        continue;
                    }
                    echo '<option value="' . $value->getGuid() . '" data-charicon="' . $value->getCharIconUrl() . '" data-classicon="' . $value->getClassIconUrl() . '">' . $value->getName() . '</option>';
                endforeach;
            } else {
                foreach ($deletedCharacters as $key => $value):
                    echo '<option value="' . $value->getGuid() . '" data-charicon="' . $value->getCharIconUrl() . '" data-classicon="' . $value->getClassIconUrl() . '">' . $value->getDeletedName() . '</option>';
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
                charicon.src = selected.dataset.charicon;
                classicon.src = selected.dataset.classicon;
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
