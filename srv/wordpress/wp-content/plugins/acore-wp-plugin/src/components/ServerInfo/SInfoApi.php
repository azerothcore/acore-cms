<?php

namespace ACore;

use \ACore\ACoreServices;

class SInfoApi {
    public static function serverInfo() {
        return ACoreServices::I()->getServerSoap()->executeCommand("server info");
    }

    public static function AccountCount() {
        return ACoreServices::I()->getAccountRepo()->count([]);
    }
}


add_action( 'rest_api_init', function () {

   register_rest_route( 'wp-acore/v1', 'server-info', array(

       'methods'  => 'GET',
       'callback' => function() {
           return SInfoApi::serverInfo();
       }

   ) );
});

