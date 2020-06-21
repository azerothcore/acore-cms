<?php

namespace ACore;

class FieldElements {

    public static function charList($username) {
        $ACoreSrv = ACoreServices::I();
        $accRepo = $ACoreSrv->getAccountRepo();
        $charRepo = $ACoreSrv->getCharactersRepo();



        $accountId = $accRepo->findOneByUsername($username)->getId();
        $characters = $charRepo->findByAccount($accountId);
        $charBanRepo = $ACoreSrv->getCharactersBannedRepo();
        $accBanRepo = $ACoreSrv->getAccountBannedRepo();

        if ($accBanRepo->isActiveById($accountId)) {
            echo "Your account is banned!<br>";
            return;
        }

        $bannedChars = array();
        ?>
        <label for="acore_char_sel">Select the character: </label> 
        <select id="acore_char_sel" class="acore_char_sel" name="acore_char_sel">
            <?php
            foreach ($characters as $key => $value):
                if ($charBanRepo->isActiveByGuid($value->getGuid())) {
                    $bannedChars[] = $value->getName();
                    continue;
                }

                echo '<option value="' . $value->getGuid() . '">' . $value->getName() . '</option>'; //close your tags!!
            endforeach;
            ?>
        </select>
        <br>
        <?php
        if ($bannedChars) {
            echo "Some characters in your account are banned: <br>";
            echo implode(",", $bannedChars) . "<br>";
        }
    }

    public static function destCharacter($label) {
        ?>
        <label for="acore_char_dest"><?= $label ?></label> 
        <input type="text" placeholder="Character name..." id="acore_char_dest" class="acore_char_dest" name="acore_char_dest">
        <br>
        <?php
    }

    public static function destAccount() {
        ?>
        <label for="acore_dest_account">Destination account: </label> 
        <input required type="text" id="acore_dest_account" class="acore_dest_account" name="acore_dest_account">
        <br>
        <?php
    }

}
