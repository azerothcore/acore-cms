<?php

namespace ACore\Components\CharactersMenu;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;
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
                <div class="updated"><p><strong>Character settings successfully saved.</strong></p></div>
                <?php
            }
        }

        $accId = ACoreServices::I()->getAcoreAccountId();
        if (!$accId) {
            echo $this->getView()->getHomeRender([], 0, null);
            return;
        }

        $conn  = ACoreServices::I()->getCharacterEm()->getConnection();

        $query = "SELECT
            c.`guid`, c.`name`, c.`order`, c.`race`, c.`class`, c.`level`, c.`gender`,
            cb.`bandate`   AS `ban_bandate`,
            cb.`unbandate` AS `ban_unbandate`
            FROM `characters` c
            LEFT JOIN `character_banned` cb
                ON cb.`guid` = c.`guid`
               AND cb.`active` = 1
               AND (cb.`unbandate` = 0 OR cb.`unbandate` = cb.`bandate` OR cb.`unbandate` > UNIX_TIMESTAMP())
            WHERE c.`deleteDate` IS NULL AND c.`account` = ?
            ORDER BY (c.`order` IS NULL), c.`order`, c.`guid`
        ";
        $chars = $conn->executeQuery($query, [$accId])->fetchAllAssociative();

        $authConn = ACoreServices::I()->getAccountEm()->getConnection();

        $muteRow  = $authConn
            ->executeQuery("SELECT `mutetime` FROM `account` WHERE `id` = ?", [$accId])
            ->fetchAssociative();
        $mutetime = $muteRow ? intval($muteRow['mutetime']) : 0;
        // Negative = pending mute (seconds magnitude, applied on next login); positive = Unix timestamp expiry

        $accBanRow = $authConn
            ->executeQuery(
                "SELECT `bandate`, `unbandate` FROM `account_banned`
                 WHERE `id` = ? AND `active` = 1
                   AND (`unbandate` = 0 OR `unbandate` = `bandate` OR `unbandate` > UNIX_TIMESTAMP())
                 ORDER BY `bandate` DESC LIMIT 1",
                [$accId]
            )
            ->fetchAssociative();

        $serverRevision    = '';
        $serverRevisionUrl = '';
        try {
            $worldConn  = ACoreServices::I()->getWorldEm()->getConnection();
            $versionRow = $worldConn
                ->executeQuery("SELECT `core_version`, `core_revision` FROM `version` LIMIT 1")
                ->fetchAssociative();
            if ($versionRow) {
                $full = $versionRow['core_version'] ?: '';
                if (preg_match('/^(AzerothCore rev\.\s+\S+\s+\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/', $full, $m)) {
                    $serverRevision = $m[1];
                } else {
                    $serverRevision = $full;
                }
                $hash = $versionRow['core_revision'] ?: '';
                if ($hash) {
                    $serverRevisionUrl = 'https://github.com/azerothcore/azerothcore-wotlk/commit/' . $hash;
                }
            }
        } catch (\Throwable $e) {
            // non-fatal
        }

        $punishmentEnabled = Opts::I()->acore_punishment_info_enabled == '1';
        echo $this->getView()->getHomeRender(
            $chars, $mutetime, $accBanRow,
            $serverRevision, $serverRevisionUrl,
            Opts::I()->acore_bug_report_url ?: '',
            Opts::I()->acore_pdump_enabled == '1',
            $punishmentEnabled && Opts::I()->acore_punishment_info_account_ban  == '1',
            $punishmentEnabled && Opts::I()->acore_punishment_info_account_mute == '1',
            $punishmentEnabled && Opts::I()->acore_punishment_info_character_ban == '1'
        );
    }

    public function getView() {
        return $this->view;
    }

    private function resetCharacterOrder() {
        $accId = ACoreServices::I()->getAcoreAccountId();
        $accId = is_numeric($accId) ? (int) $accId : 0;
        if ($accId <= 0) {
            return;
        }
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
