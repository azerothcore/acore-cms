<?php

namespace ACore\Components\MailReturnMenu;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;
use ACore\Components\MailReturnMenu\MailReturnView;
use InvalidArgumentException;
use RuntimeException;

class MailReturnController
{
    private $view;

    public function __construct()
    {
        $this->view = new MailReturnView($this);
    }

    public function renderCharacters()
    {
        echo $this->view->getMailReturnRender(self::getCharactersByAcId());
    }

    private static function validateAccountId()
    {
        $accId = ACoreServices::I()->getAcoreAccountId();

        if (!isset($accId) || $accId === null || $accId === '' || trim($accId) === '' || !is_numeric($accId)) {
            throw new InvalidArgumentException("Invalid user account ID provided.");
        }

        return intval($accId);
    }

    private static function validateCharacterOwnership($conn, $charGuid, $accId)
    {
        $stmt = $conn->prepare(
            "SELECT `guid`, `name` FROM `characters`
            WHERE `guid` = ? AND `account` = ? AND `deleteDate` IS NULL"
        );
        $stmt->bindValue(1, $charGuid);
        $stmt->bindValue(2, $accId);
        $result = $stmt->executeQuery();
        $character = $result->fetchAssociative();

        if (!$character) {
            throw new InvalidArgumentException("Character not found");
        }

        return $character;
    }

    public static function getSentUnreadMails($charGuid)
    {
        if (!is_numeric($charGuid)) {
            throw new InvalidArgumentException("Invalid parameters");
        }
        $charGuid = intval($charGuid);

        $accId = self::validateAccountId();
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();

        self::validateCharacterOwnership($conn, $charGuid, $accId);

        // Get mails sent by this character that are unread by the receiver
        // messageType = 0 means player mail
        $query = "SELECT m.`id`, m.`subject`, m.`has_items`, m.`money`, m.`cod`,
                m.`expire_time`, m.`deliver_time`,
                rc.`name` AS receiver_name, rc.`race` AS receiver_race,
                rc.`class` AS receiver_class, rc.`gender` AS receiver_gender,
                rc.`level` AS receiver_level
            FROM `mail` m
            JOIN `characters` rc ON m.`receiver` = rc.`guid`
            WHERE m.`sender` = ?
            AND m.`messageType` = 0
            AND (m.`checked` & 1) = 0
            ORDER BY m.`deliver_time` DESC";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $charGuid);
        $result = $stmt->executeQuery();
        $mails = $result->fetchAllAssociative();

        // Fetch items for mails that have items
        $mailIds = [];
        foreach ($mails as $mail) {
            if ($mail['has_items'] == 1) {
                $mailIds[] = $mail['id'];
            }
        }

        $itemsByMail = [];
        if (!empty($mailIds)) {
            $placeholders = implode(',', array_fill(0, count($mailIds), '?'));
            $worldDb = Opts::I()->acore_db_world_name;
            $itemQuery = "SELECT mi.`mail_id`, ii.`itemEntry`, ii.`count`,
                    it.`name` AS item_name, it.`Quality` AS item_quality
                FROM `mail_items` mi
                JOIN `item_instance` ii ON mi.`item_guid` = ii.`guid`
                JOIN `$worldDb`.`item_template` it ON ii.`itemEntry` = it.`entry`
                WHERE mi.`mail_id` IN ($placeholders)";
            $stmt = $conn->prepare($itemQuery);
            $i = 1;
            foreach ($mailIds as $id) {
                $stmt->bindValue($i++, $id);
            }
            $itemResult = $stmt->executeQuery();
            foreach ($itemResult->fetchAllAssociative() as $item) {
                $itemsByMail[$item['mail_id']][] = $item;
            }
        }

        // Attach items to their mails. Mails still in the delivery delay (MailDeliveryDelay)
        // can't be returned yet; deliver_in is computed here so the page doesn't rely on the
        // visitor's clock.
        $now = time();
        foreach ($mails as &$mail) {
            $mail['items'] = $itemsByMail[$mail['id']] ?? [];
            $mail['deliver_in'] = max(0, intval($mail['deliver_time']) - $now);
        }

        return $mails;
    }

    public static function returnMail($charGuid, $mailId)
    {
        $accId = self::validateAccountId();
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();

        // Validate charGuid and mailId are integers to prevent injection
        if (!is_numeric($charGuid) || !is_numeric($mailId)) {
            throw new InvalidArgumentException("Invalid parameters");
        }
        $charGuid = intval($charGuid);
        $mailId = intval($mailId);

        // Verify the sender character belongs to the current user
        $sender = self::validateCharacterOwnership($conn, $charGuid, $accId);

        // Verify the mail was sent by this character and is still unread
        $mailQuery = "SELECT m.`id`, m.`receiver`, m.`deliver_time`
            FROM `mail` m
            WHERE m.`id` = ? AND m.`sender` = ? AND m.`messageType` = 0 AND (m.`checked` & 1) = 0";
        $stmt = $conn->prepare($mailQuery);
        $stmt->bindValue(1, $mailId);
        $stmt->bindValue(2, $charGuid);
        $mailResult = $stmt->executeQuery();
        $mail = $mailResult->fetchAssociative();

        if (!$mail) {
            throw new InvalidArgumentException("Mail not found or already read");
        }

        // The core refuses undelivered mails with a plain "not found", so explain it here
        $deliverIn = intval($mail['deliver_time']) - time();
        if ($deliverIn > 0) {
            throw new InvalidArgumentException(
                "Mail #$mailId has not been delivered yet. Try again in " . self::formatDuration($deliverIn) . "."
            );
        }

        // Verify the receiver character actually exists and has this mail
        $receiverQuery = "SELECT `guid`, `name` FROM `characters` WHERE `guid` = ? AND `deleteDate` IS NULL";
        $stmt = $conn->prepare($receiverQuery);
        $stmt->bindValue(1, $mail['receiver']);
        $receiverResult = $stmt->executeQuery();
        $receiver = $receiverResult->fetchAssociative();

        if (!$receiver) {
            throw new InvalidArgumentException("Receiver character not found");
        }

        // Use the receiver name from the database, not from user input
        $receiverName = $receiver['name'];

        // Execute SOAP command to return the mail
        $soap = ACoreServices::I()->getGameMailSoap();
        try {
            $result = $soap->executeCommand(".mail return $receiverName $mailId");
        } catch (\Exception $e) {
            throw new RuntimeException("Could not reach the game server: " . $e->getMessage());
        }

        // Command errors come back as the fault text (AcoreSoap returns it instead of
        // throwing), so only the core's LANG_MAIL_RETURN_SUCCESS text counts as success.
        $reply = trim(is_string($result) ? $result : '');
        if (!preg_match('/^Mail #' . $mailId . ' returned to sender\b/', $reply)) {
            throw new RuntimeException($reply !== '' ? $reply : "The game server did not confirm the return.");
        }

        return "Mail #$mailId returned successfully";
    }

    private static function formatDuration($seconds)
    {
        $minutes = intdiv($seconds, 60);
        $seconds = $seconds % 60;

        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
    }

    public static function getCharactersByAcId()
    {
        $accId = self::validateAccountId();

        $query = "SELECT
            c.`guid`, c.`name`, c.`order`, c.`race`, c.`class`, c.`level`, c.`gender`
            FROM `characters` c
            WHERE c.`deleteDate` IS NULL
            AND c.`account` = ?
            ORDER BY COALESCE(c.`order`, c.`guid`)
        ";
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $accId);
        $result = $stmt->executeQuery();
        return $result->fetchAllAssociative();
    }
}
