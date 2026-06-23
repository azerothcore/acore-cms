<?php

namespace ACore\Components\Tools;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;

class ToolsApi {
    public static function ItemRestoreList($request) {
        return ACoreServices::I()->getRestorableItemsByCharacter($request['cguid']);
    }

    public static function ItemRestore($data) {
        $cname = $data['cname'];
        $item  = filter_var($data['item'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($item === false) {
            return new \WP_Error('invalid_item', 'Invalid item id.', ['status' => 400]);
        }
        if (!ACoreServices::I()->currentAccountOwnsCharacterName($cname)) {
            return new \WP_Error('forbidden', 'You do not own that character.', ['status' => 403]);
        }
        return ACoreServices::I()->getServerSoap()->executeCommand("item restore $item $cname");
    }
}

add_action( 'rest_api_init', function () {
   register_rest_route( ACORE_SLUG . '/v1', 'item-restore/list/(?P<cguid>\d+)', array(
       'methods'             => 'GET',
       'permission_callback' => function() { return is_user_logged_in(); },
       'callback'            => function( $request ) {
            return ToolsApi::ItemRestoreList($request);
       }
   ));

   register_rest_route( ACORE_SLUG . '/v1', 'item-restore', array(
    'methods'             => 'POST',
    'permission_callback' => function() { return is_user_logged_in(); },
    'callback'            => function( $request ) {

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
});
