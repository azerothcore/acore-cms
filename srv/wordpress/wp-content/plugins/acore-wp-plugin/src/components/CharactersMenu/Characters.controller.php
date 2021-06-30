<?php

namespace ACore;

require_once 'Characters.view.php';

class CharactersController {
    private $view;

    public function __construct() {
        $this->view = new CharactersView($this);
    }

    public function loadHome() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->saveCharacterOrder();

            ?>
            <div class="updated"><p><strong>Option saved</strong></p></div>
            <?php
        }

        $accId = $this->getAcoreAccountId();
        $query = "SELECT
            `guid`, `name`, `order`, `race`, `class`, `level`, `gender`
            FROM `characters`
            WHERE `characters`.`deleteDate` IS NULL AND `account` = $accId
            ORDER BY `order`, `guid`
        ";
        $conn = ACoreServices::I()->getCharactersMgr()->getConnection();
        $stmt = $conn->query($query);
        $chars = $stmt->fetchAll();

        echo $this->getView()->getHomeRender($chars);
    }

    public function getView() {
        return $this->view;
    }

    private function getAcoreAccountId() {
        $user = wp_get_current_user();

        $query = "SELECT `id`
            FROM `account`
            WHERE `username` = ?
        ";
        $conn = ACoreServices::I()->getAccountMgr()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $user->get("user_login"));
        $stmt->execute();
        $res = $stmt->fetch();
        return $res["id"];
    }

    private function saveCharacterOrder() {
        if (!isset($_POST) || !isset($_POST["characterorder"])) {
            return;
        }

        // We need the account id to make sure people don't change the order for characters that do not belong to them
        $accId = $this->getAcoreAccountId();

        $query = "UPDATE `characters`
            SET `order` = ?
            WHERE `guid` = ? AND `account`= ?
        ";
        $conn = ACoreServices::I()->getCharactersMgr()->getConnection();
        $stmt = $conn->prepare($query);
        foreach ($_POST["characterorder"] as $order => $guid) {
            $stmt->bindValue(1, $order);
            $stmt->bindValue(2, $guid);
            $stmt->bindValue(3, $accId);
            $stmt->execute();
        }
    }
}
