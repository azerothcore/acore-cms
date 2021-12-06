<?php

namespace ACore\Components\ServerInfo;

use ACore\Manager\ACoreServices;

class ServerInfoApi {
    public static function serverInfo() {
        return ACoreServices::I()->getServerSoap()->executeCommand("server info");
    }

    public static function AccountCount() {
        return ACoreServices::I()->getAccountRepo()->count([]);
    }
}


add_action( 'rest_api_init', function () {
   register_rest_route( ACORE_SLUG . '/v1', 'server-info', array(
       'methods'  => 'GET',
       'callback' => function( $request ) {
            $data = ['message' => ServerInfoApi::serverInfo()];
            return $data;
       }
   ) );
});
