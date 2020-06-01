<?php

namespace ACore;

use \ACore\Services;

class SInfoApi {
    public static function serverInfo() {
        return Services::I()->getServerSoap()->executeCommand("server info");
    }

    public static function AccountCount() {
        return Services::I()->getAccountRepo()->count([]);
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

