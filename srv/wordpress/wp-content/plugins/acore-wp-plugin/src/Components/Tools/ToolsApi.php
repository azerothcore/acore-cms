<?php

namespace ACore\Components\Tools;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;

class ToolsApi {
    public static function ItemRestoreList($request) {
        return ACoreServices::I()->getRestorableItemsByCharacter($request['cguid']);
    }

    public static function ItemRestore($data) {
        $item = $data['item'];
        $cname = $data['cname'];
        return ACoreServices::I()->getServerSoap()->executeCommand("item restore $item $cname");
    }

    public static function UnlockedTransmogList($request) {
        return ACoreServices::I()->getUnlockedTransmogbyCharacter($request['cguid']);
    }

    public static function ItemTransmog($data) {
        $item = $data['item'];
        $cname = $data['cname'];
        return ACoreServices::I()->getServerSoap()->executeCommand("item transmog $item $cname");
    }
}

add_action( 'rest_api_init', function () {
   register_rest_route( ACORE_SLUG . '/v1', 'item-restore/list/(?P<cguid>\d+)', array(
       'methods'  => 'GET',
       'callback' => function( $request ) {
            return ToolsApi::ItemRestoreList($request);
       }
   ));

   register_rest_route( ACORE_SLUG . '/v1', 'item-restore', array(
    'methods'  => 'POST',
    'callback' => function( $request ) {

        // if free item-restoration is disabled, return
        if (Opts::I()->acore_item_restoration != '1') {
            return http_response_code(401);
        }

        $data = $request->get_json_params();
        // return  var_dump($data);
        if (! $data['cname'] ) {
            return http_response_code(401);
        }

        return ToolsApi::ItemRestore($data);
    }

    ));

    register_rest_route( ACORE_SLUG . '/v1', 'item-transmog/(?P<cguid>\d+)', array(
        'methods' => 'GET',
        'callback' => function( $request ) {
            return ToolsApi::UnlockedTransmogList($request);
        }
    ));

    register_rest_route( ACORE_SLUG . '/v1', 'item-transmog', array(
        'methods' => 'POST',
        'callback' => function($request) {

            $data = $request->get_json_params();
            // return  var_dump($data);
            if (! $data['cname'] ) {
                return http_response_code(401);
            }
    
            return ToolsApi::ItemTransmog($data);
        }
    ));
});
