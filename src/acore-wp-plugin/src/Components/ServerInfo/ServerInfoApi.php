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


/**
 * Pure-PHP TOTP validator (RFC 6238 / HOTP-SHA1).
 * Accepts the Base32-encoded secret stored by WP2FA and a 6-digit token string.
 * Validates against the current 30-second window +-1 step.
 */
function acore_totp_validate(string $base32Secret, string $rawToken): bool {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $input    = strtoupper(rtrim($base32Secret, '='));
    $bits     = '';
    foreach (str_split($input) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) continue;
        $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $secret = '';
    foreach (str_split($bits, 8) as $chunk) {
        if (strlen($chunk) < 8) break;
        $secret .= chr(bindec($chunk));
    }
    if ($secret === '') return false;

    $token     = (int) $rawToken;
    $timestamp = (int) floor(time() / 30);

    for ($step = -1; $step <= 1; $step++) {
        $t    = $timestamp + $step;
        $msg  = "\x00\x00\x00\x00" . pack('N', $t);
        $hash = hash_hmac('sha1', $msg, $secret, true);
        $off  = ord($hash[19]) & 0x0f;
        $otp  = (
            ((ord($hash[$off])   & 0x7f) << 24) |
            ((ord($hash[$off+1]) & 0xff) << 16) |
            ((ord($hash[$off+2]) & 0xff) <<  8) |
             (ord($hash[$off+3]) & 0xff)
        );
        if (($otp % 1000000) === $token) return true;
    }
    return false;
}

add_action( 'rest_api_init', function () {
   register_rest_route( ACORE_SLUG . '/v1', 'server-info', array(
       'methods'  => 'GET',
       'callback' => function( $request ) {
            $result = ServerInfoApi::serverInfo();
            $errorPatterns = [
                'could not connect', 'connection refused', 'operation timed out',
                'error fetching', 'failed to enable', 'soap fault', 'not configured',
                'unable to connect', 'network unreachable',
            ];
            foreach ($errorPatterns as $pattern) {
                if (stripos($result, $pattern) !== false) {
                    return new \WP_Error('soap_error', $result, ['status' => 503]);
                }
            }
            return ['message' => $result];
       }
   ) );

   $defaultRequirements = [['Scroll of Resurrection', 'mod-resurrection-scroll']];

   register_rest_route( ACORE_SLUG . '/v1', 'server-module-requirements', array(
       'methods'             => 'GET',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( $request ) use ($defaultRequirements) {
           return ['requirements' => get_option('acore_module_requirements', $defaultRequirements)];
       }
   ));

   register_rest_route( ACORE_SLUG . '/v1', 'server-module-requirements', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( $request ) {
           $data  = $request->get_json_params();
           $reqs  = isset($data['requirements']) ? $data['requirements'] : [];
           $clean = [];
           foreach ($reqs as $row) {
               if (is_array($row) && count($row) === 2) {
                   $clean[] = [sanitize_text_field($row[0]), sanitize_text_field($row[1])];
               }
           }
           update_option('acore_module_requirements', $clean);
           return ['success' => true, 'requirements' => $clean];
       }
   ));

   register_rest_route( ACORE_SLUG . '/v1', 'server-modules', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( $request ) {
           $raw      = ACoreServices::I()->getServerSoap()->executeCommand('.server debug');
           $modules  = [];
           $capturing = false;
           foreach (explode("\n", $raw) as $line) {
               $line = trim($line);
               if (!$capturing) {
                   if (stripos($line, 'List of enabled modules:') !== false) $capturing = true;
                   continue;
               }
               if (preg_match('/\b(mod-[a-zA-Z0-9_-]+)/', $line, $m)) $modules[] = $m[1];
           }
           $csv       = implode(',', $modules);
           $timestamp = time();
           update_option('acore_modules_csv',       $csv);
           update_option('acore_modules_refreshed', $timestamp);
           return ['modules' => $modules, 'csv' => $csv, 'refreshed' => $timestamp];
       }
   ) );

   // Admin: check 2FA status for any user
   register_rest_route( ACORE_SLUG . '/v1', 'admin/2fa-check', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( \WP_REST_Request $request ) {
           $data     = $request->get_json_params();
           $type     = isset($data['type'])     ? sanitize_text_field($data['type'])     : '';
           $username = isset($data['username']) ? sanitize_text_field($data['username']) : '';
           if (!in_array($type, ['website', 'ingame'], true))
               return new \WP_Error('invalid_type', 'Invalid type.', ['status' => 400]);
           if ($username === '')
               return new \WP_Error('missing_username', 'Username is required.', ['status' => 400]);
           $user = get_user_by('login', $username);
           if (!$user)
               return new \WP_Error('user_not_found', 'No WordPress account found with that username.', ['status' => 404]);

           $log         = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
           $log         = is_array($log) ? $log : [];
           $lastRemoval = null;
           foreach (array_reverse($log) as $entry) {
               if ($entry['type'] === $type) { $lastRemoval = $entry; break; }
           }

           if ($type === 'website') {
               $totpKey = get_user_meta($user->ID, 'wp_2fa_totp_key', true);
               $active  = !empty($totpKey);
               $resp = ['found' => true, 'username' => $user->user_login, 'active' => $active];
               if ($lastRemoval) $resp['last_removal'] = ['date' => wp_date('jS \o\f F, Y \a\t H:i', $lastRemoval['timestamp']), 'staff' => $lastRemoval['staff']];
               return $resp;
           }

           try {
               $conn   = ACoreServices::I()->getAccountEm()->getConnection();
               $result = $conn->executeQuery('SELECT totp_secret FROM account WHERE username = ?', [strtoupper($username)]);
               $row    = $result->fetchAssociative();
               if (!$row) return new \WP_Error('no_game_account', 'No game account found for this user.', ['status' => 404]);
               $resp = ['found' => true, 'username' => $user->user_login, 'active' => $row['totp_secret'] !== null];
               if ($lastRemoval) $resp['last_removal'] = ['date' => wp_date('jS \o\f F, Y \a\t H:i', $lastRemoval['timestamp']), 'staff' => $lastRemoval['staff']];
               return $resp;
           } catch (\Exception $e) {
               return new \WP_Error('db_error', 'Database error.', ['status' => 500]);
           }
       }
   ));

   // Admin: remove 2FA for any user and log the action
   register_rest_route( ACORE_SLUG . '/v1', 'admin/2fa-remove', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( \WP_REST_Request $request ) {
           $data     = $request->get_json_params();
           $type     = isset($data['type'])     ? sanitize_text_field($data['type'])     : '';
           $username = isset($data['username']) ? sanitize_text_field($data['username']) : '';
           if (!in_array($type, ['website', 'ingame'], true))
               return new \WP_Error('invalid_type', 'Invalid type.', ['status' => 400]);
           if ($username === '')
               return new \WP_Error('missing_username', 'Username is required.', ['status' => 400]);
           $user = get_user_by('login', $username);
           if (!$user)
               return new \WP_Error('user_not_found', 'No WordPress account found with that username.', ['status' => 404]);

           if ($type === 'website') {
               delete_user_meta($user->ID, 'wp_2fa_totp_key');
               delete_user_meta($user->ID, 'wp_2fa_enabled_methods');
               delete_user_meta($user->ID, 'wp_2fa_backup_methods_enabled');
               delete_user_meta($user->ID, 'wp_2fa_grace_period_expiry');
               delete_user_meta($user->ID, 'wp_2fa_user_setup_started_at');
               delete_user_meta($user->ID, 'wp_2fa_user_authenticated_methods');
           } else {
               try {
                   $conn = ACoreServices::I()->getAccountEm()->getConnection();
                   $rows = $conn->executeStatement('UPDATE account SET totp_secret = NULL WHERE username = ?', [strtoupper($username)]);
                   if ($rows === 0) return new \WP_Error('no_game_account', 'No game account found for this user.', ['status' => 404]);
               } catch (\Exception $e) {
                   return new \WP_Error('db_error', 'Database error.', ['status' => 500]);
               }
           }

           $staff = wp_get_current_user();
           $now   = time();
           $log   = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
           $log   = is_array($log) ? $log : [];
           $log[] = ['type' => $type, 'timestamp' => $now, 'staff' => $staff->user_login, 'staff_id' => $staff->ID];
           update_user_meta($user->ID, 'acore_2fa_admin_log', $log);
           return ['success' => true, 'date' => wp_date('jS \o\f F, Y \a\t H:i', $now), 'staff' => $staff->user_login];
       }
   ));

   register_rest_route( ACORE_SLUG . '/v1', 'remove-ingame-2fa', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return is_user_logged_in(); },
       'callback'            => function( \WP_REST_Request $request ) {
           $user           = wp_get_current_user();
           $totpSecret     = get_user_meta($user->ID, 'wp_2fa_totp_key', true);
           $enabledMethods = get_user_meta($user->ID, 'wp_2fa_enabled_methods', true);
           $totpActive     = !empty($totpSecret) && (
               (is_array($enabledMethods) && in_array('totp', $enabledMethods)) ||
               $enabledMethods === 'totp'
           );
           if (!$totpActive)
               return new \WP_Error('no_website_2fa', __('You must have website 2FA (TOTP) enabled to remove in-game 2FA from here.'), ['status' => 403]);

           $data  = $request->get_json_params();
           $token = isset($data['token']) ? trim((string) $data['token']) : '';
           if (!preg_match('/^\d{6}$/', $token))
               return new \WP_Error('invalid_token', __('Please enter a valid 6-digit code.'), ['status' => 400]);
           if (!\ACore\Components\ServerInfo\acore_totp_validate($totpSecret, $token))
               return new \WP_Error('wrong_token', __('Incorrect code. Please try again.'), ['status' => 401]);

           try {
               $accId = ACoreServices::I()->getAcoreAccountId();
               if (!$accId) return new \WP_Error('no_account', __('Could not find your game account.'), ['status' => 404]);
               $conn = ACoreServices::I()->getAccountEm()->getConnection();
               $conn->executeStatement('UPDATE account SET totp_secret = NULL WHERE id = ?', [$accId]);
               return ['success' => true];
           } catch (\Exception $e) {
               return new \WP_Error('db_error', __('Database error. Please try again.'), ['status' => 500]);
           }
       }
   ) );
});
