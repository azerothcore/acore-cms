<?php

namespace ACore\Components\Tools;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;

class ToolsApi {
    // Sold and disenchanted rows are only offered when the admin enabled them, since redeeming them needs mod-enhanced-support
    public static function RecoverySourcesEnabled(): bool {
        return Opts::I()->acore_item_restoration_sources == '1';
    }

    public static function ItemRestoreList($request) {
        try {
            $rows = ACoreServices::I()->getRestorableItemsByCharacter($request['cguid']);
            if (!self::RecoverySourcesEnabled()) {
                $rows = array_values(array_filter($rows, function ($row) { return $row['Source'] === 0; }));
            }
            return $rows;
        } catch (\InvalidArgumentException $e) {
            return new \WP_Error('invalid_character', 'Character not found.', ['status' => 403]);
        }
    }

    public static function ItemRestore($data) {
        $cname = isset($data['cname']) && is_string($data['cname']) ? trim($data['cname']) : '';
        $item  = filter_var($data['item'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($item === false) {
            return new \WP_Error('invalid_item', 'Invalid item id.', ['status' => 400]);
        }
        if ($cname === '') {
            return new \WP_Error('invalid_character', 'Invalid character.', ['status' => 400]);
        }
        $ownedCharacterName = ACoreServices::I()->getOwnedRestorableItemCharacterName($item, $cname);
        if ($ownedCharacterName === null) {
            return new \WP_Error('forbidden', 'Restorable item not found for that character.', ['status' => 403]);
        }
        return ACoreServices::I()->getServerSoap()->executeCommand("item restore $item $ownedCharacterName");
    }

    /**
     * Asks the server whether character $cname can pay for recovery row $item (the vendor's copper and the
     * disenchant materials, from the bags or bank). Returns ['ok' => bool, 'message' => string].
     */
    public static function RecoveryCheck(int $item, string $cname): array {
        return self::RunRecoveryCommand('check', $item, $cname, 'can redeem');
    }

    /**
     * Takes the copper and materials from character $cname and mails recovery row $item.
     * Returns ['ok' => bool, 'message' => string].
     */
    public static function RecoveryRedeem(int $item, string $cname): array {
        return self::RunRecoveryCommand('redeem', $item, $cname, 'redeemed for');
    }

    private static function RunRecoveryCommand(string $action, int $item, string $cname, string $successText): array {
        $owned = ACoreServices::I()->getOwnedRecoveryItem($item, $cname);
        if ($owned === null) {
            return ['ok' => false, 'message' => 'Restorable item not found for that character.'];
        }

        $name  = $owned['name'];
        $reply = trim((string) ACoreServices::I()->getServerSoap()->executeCommand(".support recovery $action $item $name"));

        // AcoreSoap returns command errors as their text, so only the module's success reply counts
        $ok = preg_match('/^Recovery ' . $item . ': .* ' . preg_quote($successText, '/') . ' /', $reply) === 1;
        return ['ok' => $ok, 'message' => $reply !== '' ? $reply : 'The game server did not answer.'];
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
