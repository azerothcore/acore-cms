<?php

namespace ACore\Components\Tools;

use ACore\Manager\ACoreServices;

class ToolsApi {
    public static function ItemRestoreList($request) {
        return ACoreServices::I()->getRestorableItemsByCharacter($request['cguid']);
    }

    public static function ItemRestore($request) {
        return ACoreServices::I()->getServerSoap()->executeCommand("item restore");
    }
}

add_action( 'rest_api_init', function () {
   register_rest_route( ACORE_SLUG . '/v1', 'item-restore/list/(?P<cguid>\d+)', array(
       'methods'  => 'GET',
       'callback' => function( $request ) {
            return ToolsApi::ItemRestoreList($request);
       }
   ));
});
