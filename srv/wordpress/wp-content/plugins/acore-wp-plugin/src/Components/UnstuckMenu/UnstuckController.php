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
                return $charName . " unstucked!";
            }
        }

        throw new InvalidArgumentException("Character not found");

    }

    public static function getCharactersByAcId(){
        $accId = ACoreServices::I()->getAcoreAccountId();

        // Logging Helpers
        // echo '<script>console.log(' . $accId . ')</script>';
        // echo '<script>console.log(' . wp_json_encode(wp_get_current_user()) . ')</script>';


        if (!isset($accId) || $accId === null || $accId === '' || trim($accId) === '' || !is_numeric($accId)) {
            throw new InvalidArgumentException("Invalid user account ID provided.");
        }    

        $query = "SELECT
            `guid`, `name`, `order`, `race`, `class`, `level`, `gender`
            FROM `characters`
            WHERE `characters`.`deleteDate` IS NULL AND `account` = $accId
            ORDER BY COALESCE(`order`, `guid`)
        ";
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $queryResult = $conn->executeQuery($query);
        return $queryResult->fetchAllAssociative();
    }
}
