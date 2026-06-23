<?php

namespace ACore\Components\CharactersMenu;

use ACore\Manager\ACoreServices;
use ACore\Components\CharactersMenu\CharactersView;

class CharactersController {
    private $view;

    public function __construct() {
        $this->view = new CharactersView($this);
    }

    public function loadHome() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            check_admin_referer('acore_character_order', 'acore_character_order_nonce');
            if (isset($_POST["acore_reset_order"])) {
                $this->resetCharacterOrder();
                ?>
                <div class="updated"><p><strong>Character order reset successfully.</strong></p></div>
                <?php
            } else {
                $this->saveCharacterOrder();
                ?>
                <div class="updated"><p><strong>Character settings succesfully saved.</strong></p></div>
                <?php
            }
        }

        $accId = ACoreServices::I()->getAcoreAccountId();
        $query = "SELECT
            `guid`, `name`, `order`, `race`, `class`, `level`, `gender`
            FROM `characters`
            WHERE `characters`.`deleteDate` IS NULL AND `account` = $accId
            ORDER BY COALESCE(`order`, `guid`)
        ";
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $queryResult = $conn->executeQuery($query);
        $chars = $queryResult->fetchAllAssociative();

        echo $this->getView()->getHomeRender($chars);
    }

    public function getView() {
        return $this->view;
    }

    private function resetCharacterOrder() {
        $accId = ACoreServices::I()->getAcoreAccountId();
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $stmt = $conn->prepare(
            "UPDATE `characters` SET `order` = NULL WHERE `account` = ? AND `deleteDate` IS NULL"
        );
        $stmt->bindValue(1, $accId);
        $stmt->executeQuery();
    }

    private function saveCharacterOrder() {
        if (!isset($_POST) || !isset($_POST["characterorder"])) {
            return;
        }

        // We need the account id to make sure people don't change the order for characters that do not belong to them
        $accId = ACoreServices::I()->getAcoreAccountId();

        $query = "UPDATE `characters`
            SET `order` = ?
            WHERE `guid` = ? AND `account`= ?
        ";
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $stmt = $conn->prepare($query);
        foreach ($_POST["characterorder"] as $order => $guid) {
            $stmt->bindValue(1, $order);
            $stmt->bindValue(2, $guid);
            $stmt->bindValue(3, $accId);
            $stmt->executeQuery();
        }
    }
}
