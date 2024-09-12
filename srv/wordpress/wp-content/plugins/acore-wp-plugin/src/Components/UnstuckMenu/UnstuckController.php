<?php

namespace ACore\Components\UnstuckMenu;

use ACore\Manager\ACoreServices;
use ACore\Components\UnstuckMenu\UnstuckView;

class UnstuckController {
    private $view;

    public function __construct() {
        $this->view = new UnstuckView($this);
    }

    public function loadCharacters() {
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

        echo $this->getView()->getUnstuckmenuRender($chars);
    }

    public static function unstuck($charName) {
        $soap = ACoreServices::I()->getUnstuckSoap();

        $soap->teleportName($charName);
    }

    public function getView() {
        return $this->view;
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( ACORE_SLUG . '/v1', 'unstuck', array(
        'methods'  => 'GET',
        'callback' => function( $request ) {
            $charName = $request->get_param( 'charName' );
            $data = ['message' => UnstuckController::unstuck($charName)];
            return $data;
        }
    ) );
 });