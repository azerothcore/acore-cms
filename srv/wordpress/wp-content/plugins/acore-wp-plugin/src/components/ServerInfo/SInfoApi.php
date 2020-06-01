<?php

namespace ACore;

use \ACore\Services;

class SInfoApi {
    public static function serverInfo() {
        return Services::I()->getAzthServerSoap()->executeCommand("server info");
    }
}


add_action( 'rest_api_init', function () {

   register_rest_route( 'wp-acore/v1', 'server-info',array(

       'methods'  => 'GET',
       'callback' => function() {
           return SInfoApi::serverInfo();
       }

   ) );
});

