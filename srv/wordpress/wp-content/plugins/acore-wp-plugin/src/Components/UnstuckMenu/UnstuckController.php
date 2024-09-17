<?php

namespace ACore\Components\UnstuckMenu;

use ACore\Manager\ACoreServices;
use ACore\Components\UnstuckMenu\UnstuckView;
use InvalidArgumentException;

class UnstuckController
{
    private $view;

    public function __construct()
    {
        $this->view = new UnstuckView($this);
    }

    public function renderCharacters()
    {
        echo $this->view->getUnstuckmenuRender(self::getCharactersByAcId());
    }

    public static function unstuck($charName)
    {
        $soap = ACoreServices::I()->getUnstuckSoap();

        // Validate if the provided charName is part of the user who requests the unstuck operation
        $characters = self::getCharactersByAcId();
        foreach ($characters as $character) {
            if ($character['name'] === $charName) {

                $soap->unstuckByName($charName);

                self::updateUnstuckCD($charName);

                return $charName . " unstucked!";
            }
        }

        throw new InvalidArgumentException("Character not found");
    }

    public static function getCharactersByAcId()
    {
        $accId = ACoreServices::I()->getAcoreAccountId();

        if (!isset($accId) || $accId === null || $accId === '' || trim($accId) === '' || !is_numeric($accId)) {
            throw new InvalidArgumentException("Invalid user account ID provided.");
        }

        $query = "SELECT
            c.`guid`, c.`name`, c.`order`, c.`race`, c.`class`, c.`level`, c.`gender`, csc.`time`
            FROM `characters` c
            LEFT JOIN `character_spell_cooldown` csc  ON c.`guid` = csc.`guid`
            AND csc.`spell` = 8690 # hearthstone spell
            WHERE c.`deleteDate` IS NULL 
            AND c.`account` = $accId
            ORDER BY COALESCE(c.`order`, c.`guid`)
        ";
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $queryResult = $conn->executeQuery($query);
        return $queryResult->fetchAllAssociative();
    }


    public static function updateUnstuckCD($charName)
    {
        $accId = ACoreServices::I()->getAcoreAccountId();
        $newTime = $newTime = time() + (30 * 60); // 30 minutes;

        $query = "
            INSERT INTO `character_spell_cooldown` (`guid`, `spell`, `time`)
            SELECT c.`guid`, 8690, $newTime
            FROM `characters` c
            WHERE c.`name` = '$charName'
            AND c.`deleteDate` IS NULL
            AND c.`account` = $accId
            ON DUPLICATE KEY UPDATE character_spell_cooldown.`time` = $newTime
        ";

        $conn = ACoreServices::I()->getCharacterEm()->getConnection();

        try {
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':newTime' => $newTime,
                ':charName' => $charName,
                ':accId' => $accId
            ]);
            return $result->rowCount();
        } catch (\Exception $e) {
            error_log('Failed to update character cooldown: ' . $e->getMessage());
            return -1;
        }
    }
}
