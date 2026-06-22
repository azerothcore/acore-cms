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

/**
 * Validate a 6-digit website 2FA (TOTP) code for a user.
 *
 * Uses the WP 2FA plugin's own authenticator
 * (\WP2FA\Authenticator\Authentication::is_valid_authcode), which transparently
 * handles the plugin's "lsc_" key prefix, OpenSSL decryption and the configured
 * time-drift window. Falls back to a manual decrypt + RFC 6238 check for older
 * plugin builds that lack that method.
 */
function acore_wp2fa_code_is_valid(int $userId, string $token): bool {
    $rawKey = (string) get_user_meta($userId, 'wp_2fa_totp_key', true);
    if ($rawKey === '') return false;

    // Preferred path: let the plugin validate the code itself.
    $auth = '\WP2FA\Authenticator\Authentication';
    if (is_callable([$auth, 'is_valid_authcode'])) {
        if (is_callable([$auth, 'clear_decrypted_key'])) {
            $auth::clear_decrypted_key();
        }
        try {
            return (bool) $auth::is_valid_authcode($rawKey, $token);
        } catch (\Throwable $e) {
            // fall through to the manual path below
        }
    }

    // Fallback: decrypt the secret ourselves, then validate (RFC 6238).
    $secret = acore_wp2fa_decrypt_secret($rawKey);
    if ($secret === '') return false;
    return acore_totp_validate($secret, $token);
}

/**
 * Decrypt a WP 2FA stored TOTP key into its raw Base32 secret.
 * The plugin stores the value as "lsc_" + base64(iv . ciphertext) when OpenSSL is
 * available, or as a plain Base32 secret otherwise.
 */
function acore_wp2fa_decrypt_secret(string $rawKey): string {
    if ($rawKey === '') return '';

    $prefix     = 'lsc_';
    $hasPrefix  = strpos($rawKey, $prefix) === 0;
    $cipherText = $hasPrefix ? substr($rawKey, strlen($prefix)) : $rawKey;

    // No prefix and already Base32? Encryption was disabled - use as-is.
    if (!$hasPrefix) {
        $clean = strtoupper(rtrim(trim($cipherText), '='));
        if ($clean !== '' && preg_match('/^[A-Z2-7]+$/', $clean)) {
            return $cipherText;
        }
    }

    foreach (['\WP2FA\Authenticator\Open_SSL', '\WP2FA\Utils\Open_SSL'] as $cls) {
        if (is_callable([$cls, 'decrypt'])) {
            try { $dec = $cls::decrypt($cipherText); }
            catch (\Throwable $e) { $dec = ''; }
            if (is_string($dec) && $dec !== '') return $dec;
        }
    }

    return '';
}

/**
 * Whether the user currently has Website 2FA (TOTP) active.
 */
function acore_website_totp_enabled(int $userId): bool {
    $primary = get_user_meta($userId, 'wp_2fa_enabled_methods', true);
    $totpKey = get_user_meta($userId, 'wp_2fa_totp_key', true);
    return !empty($totpKey) && (
        (is_array($primary) && in_array('totp', $primary, true)) ||
        $primary === 'totp'
    );
}

function acore_website_2fa_enabled(int $userId): bool {
    $primary = get_user_meta($userId, 'wp_2fa_enabled_methods', true);
    $methods = is_array($primary) ? $primary : ($primary !== '' ? [$primary] : []);
    return acore_website_totp_enabled($userId) || in_array('email', $methods, true);
}

function acore_2fa_unlock_key(int $userId): string {
    $token = function_exists('wp_get_session_token') ? (string) wp_get_session_token() : '';
    return 'acore_2fa_panel_unlock_' . $userId . '_' . hash('sha256', $token);
}

/**
 * Whether the current user has In-game 2FA active (account.totp_secret set).
 */
function acore_ingame_2fa_enabled(int $userId): bool {
    try {
        $services = \ACore\Manager\ACoreServices::I();
        $accId    = $services->getAcoreAccountId();
        if (!$accId) return false;
        $conn = $services->getAccountEm()->getConnection();
        $row  = $conn->executeQuery('SELECT totp_secret FROM account WHERE id = ?', [$accId])->fetchAssociative();
        return $row && $row['totp_secret'] !== null;
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Detect a user-initiated Website 2FA removal (done through the WP 2FA plugin UI)
 * and log it with the user's IP. Admin removals pre-set the "last seen" flag to
 * '0', so they are never misattributed here. Runs in the user's own session, so
 * the only IP ever recorded is the user's - never an administrator's.
 */
function acore_2fa_sync_self_removals(int $userId): void {
    $enabled = acore_website_2fa_enabled($userId);
    $seen    = get_user_meta($userId, 'acore_2fa_ws_seen_enabled', true);

    if ($seen === '') {
        update_user_meta($userId, 'acore_2fa_ws_seen_enabled', $enabled ? '1' : '0');
        return;
    }

    if ($seen === '1' && !$enabled) {
        $log = get_user_meta($userId, 'acore_2fa_admin_log', true);
        $log = is_array($log) ? $log : [];
        $log[] = [
            'type'      => 'website',
            'by'        => 'self',
            'timestamp' => time(),
            'ip'        => \ACore\Hooks\User\acore_resolve_client_ip(),
        ];
        update_user_meta($userId, 'acore_2fa_admin_log', $log);
    }

    update_user_meta($userId, 'acore_2fa_ws_seen_enabled', $enabled ? '1' : '0');
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
           try {
               $raw = ACoreServices::I()->getServerSoap()->executeCommand('.server debug');
           } catch (\Throwable $e) {
               return new \WP_Error('soap_error', $e->getMessage(), ['status' => 503]);
           }
           if (!is_string($raw)) {
               return new \WP_Error('soap_error', __('Unexpected module response from the server.', 'acore-wp-plugin'), ['status' => 503]);
           }
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
               $totpKey        = get_user_meta($user->ID, 'wp_2fa_totp_key', true);
               $enabledMethods = get_user_meta($user->ID, 'wp_2fa_enabled_methods', true);
               $active         = !empty($totpKey) && (
                   (is_array($enabledMethods) && in_array('totp', $enabledMethods, true)) ||
                   $enabledMethods === 'totp'
               );
               $backupCodes = get_user_meta($user->ID, 'wp_2fa_backup_codes', true);
               $resp = [
                   'found'        => true,
                   'username'     => $user->user_login,
                   'active'       => $active,
                   'backup_codes' => is_array($backupCodes) ? count($backupCodes) : 0,
               ];
               if ($lastRemoval) $resp['last_removal'] = ['date' => wp_date('jS \o\f F, Y \a\t H:i', $lastRemoval['timestamp']), 'by' => $lastRemoval['by'] ?? 'admin', 'staff' => $lastRemoval['staff'] ?? null, 'ip' => $lastRemoval['ip'] ?? null];
               return $resp;
           }

           try {
               $conn   = ACoreServices::I()->getAccountEm()->getConnection();
               $result = $conn->executeQuery('SELECT totp_secret FROM account WHERE username = ?', [strtoupper($username)]);
               $row    = $result->fetchAssociative();
               if (!$row) return new \WP_Error('no_game_account', 'No game account found for this user.', ['status' => 404]);
               $resp = ['found' => true, 'username' => $user->user_login, 'active' => $row['totp_secret'] !== null];
               if ($lastRemoval) $resp['last_removal'] = ['date' => wp_date('jS \o\f F, Y \a\t H:i', $lastRemoval['timestamp']), 'by' => $lastRemoval['by'] ?? 'admin', 'staff' => $lastRemoval['staff'] ?? null, 'ip' => $lastRemoval['ip'] ?? null];
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
               // Pre-set the user's last-seen flag so the self-removal detector
               // does not misattribute this admin action to the user.
               update_user_meta($user->ID, 'acore_2fa_ws_seen_enabled', '0');
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
           $log[] = ['type' => $type, 'by' => 'admin', 'timestamp' => $now, 'staff' => $staff->user_login, 'staff_id' => $staff->ID];
           update_user_meta($user->ID, 'acore_2fa_admin_log', $log);
           return ['success' => true, 'date' => wp_date('jS \o\f F, Y \a\t H:i', $now), 'staff' => $staff->user_login];
       }
   ));

   // Admin: remove a user's WP 2FA backup codes and notify them
   register_rest_route( ACORE_SLUG . '/v1', 'admin/backup-codes-remove', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( \WP_REST_Request $request ) {
           $data     = $request->get_json_params();
           $username = isset($data['username']) ? sanitize_text_field($data['username']) : '';
           if ($username === '')
               return new \WP_Error('missing_username', 'Username is required.', ['status' => 400]);
           $user = get_user_by('login', $username);
           if (!$user)
               return new \WP_Error('user_not_found', 'No WordPress account found with that username.', ['status' => 404]);

           delete_user_meta($user->ID, 'wp_2fa_backup_codes');

           $staff = wp_get_current_user();
           $now   = time();
           $log   = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
           $log   = is_array($log) ? $log : [];
           $log[] = ['type' => 'backup', 'by' => 'admin', 'timestamp' => $now, 'staff' => $staff->user_login, 'staff_id' => $staff->ID];
           update_user_meta($user->ID, 'acore_2fa_admin_log', $log);

           return ['success' => true, 'date' => wp_date('jS \o\f F, Y \a\t H:i', $now), 'staff' => $staff->user_login];
       }
   ));

   // Admin: look up a user's recorded login IP history (same data the user sees)
   register_rest_route( ACORE_SLUG . '/v1', 'admin/login-history', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return current_user_can('manage_options'); },
       'callback'            => function( \WP_REST_Request $request ) {
           $data     = $request->get_json_params();
           $username = isset($data['username']) ? sanitize_text_field($data['username']) : '';
           $page     = max(1, (int) ($data['page'] ?? 1));
           $perPage  = 50;
           if ($username === '')
               return new \WP_Error('missing_username', 'Username is required.', ['status' => 400]);

           $user = get_user_by('login', $username);
           if (!$user)
               return new \WP_Error('user_not_found', 'No WordPress account found with that username.', ['status' => 404]);
           $all = \ACore\Hooks\User\acore_get_login_history($user->ID, 500);

           $all    = is_array($all) ? $all : [];
           $total  = count($all);
           $offset = ($page - 1) * $perPage;
           $slice  = array_slice($all, $offset, $perPage);

           $history = [];
           foreach ($slice as $r) {
               $history[] = [
                   'ip'      => $r['ip_address'] ?? '',
                   'country' => $r['country'] ?? 'Unknown',
                   'date'    => isset($r['login_at']) ? \ACore\Hooks\User\acore_format_connection_date($r['login_at']) : '',
                   'where'   => (($r['source'] ?? 'website') === 'ingame') ? 'In-game' : 'Website',
               ];
           }
           return [
               'found'    => true,
               'username' => $username,
               'history'  => $history,
               'total'    => $total,
               'from'     => $total ? $offset + 1 : 0,
               'to'       => $offset + count($slice),
               'page'     => $page,
               'has_more' => ($offset + count($slice)) < $total,
           ];
       }
   ));

   register_rest_route( ACORE_SLUG . '/v1', 'remove-ingame-2fa', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return is_user_logged_in(); },
       'callback'            => function( \WP_REST_Request $request ) {
           $user           = wp_get_current_user();
           $rawKey         = (string) get_user_meta($user->ID, 'wp_2fa_totp_key', true);
           $enabledMethods = get_user_meta($user->ID, 'wp_2fa_enabled_methods', true);
           $totpActive     = !empty($rawKey) && (
               (is_array($enabledMethods) && in_array('totp', $enabledMethods)) ||
               $enabledMethods === 'totp'
           );
           if (!$totpActive)
               return new \WP_Error('no_website_2fa', __('You must have website 2FA (TOTP) enabled to remove in-game 2FA from here.'), ['status' => 403]);

           $data  = $request->get_json_params();
           $token = isset($data['token']) ? trim((string) $data['token']) : '';
           if (!preg_match('/^\d{6}$/', $token))
               return new \WP_Error('invalid_token', __('Please enter a valid 6-digit code.'), ['status' => 400]);
           if (!\ACore\Components\ServerInfo\acore_wp2fa_code_is_valid($user->ID, $token))
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

   // User: verify own website 2FA (TOTP) code - used to gate sensitive panels (e.g. backup codes)
   register_rest_route( ACORE_SLUG . '/v1', 'verify-website-2fa', array(
       'methods'             => 'POST',
       'permission_callback' => function() { return is_user_logged_in(); },
       'callback'            => function( \WP_REST_Request $request ) {
           $user   = wp_get_current_user();
           if (!acore_website_totp_enabled($user->ID))
               return new \WP_Error('no_website_2fa', __('Website 2FA is not enabled on your account.'), ['status' => 400]);

           $data  = $request->get_json_params();
           $token = isset($data['token']) ? trim((string) $data['token']) : '';
           if (!preg_match('/^\d{6}$/', $token))
               return new \WP_Error('invalid_token', __('Please enter a valid 6-digit code.'), ['status' => 400]);
           if (!\ACore\Components\ServerInfo\acore_wp2fa_code_is_valid($user->ID, $token))
               return new \WP_Error('wrong_token', __('Incorrect code. Please try again.'), ['status' => 401]);

           // Remember this unlock briefly so page refreshes don't re-prompt for the code.
           set_transient(acore_2fa_unlock_key($user->ID), time(), 30 * MINUTE_IN_SECONDS);

           return ['success' => true];
       }
   ) );

   // User: lightweight 2FA status for real-time removal detection on the Security page
   register_rest_route( ACORE_SLUG . '/v1', '2fa-status', array(
       'methods'             => 'GET',
       'permission_callback' => function() { return is_user_logged_in(); },
       'callback'            => function() {
           $user = wp_get_current_user();
           $log  = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
           $log  = is_array($log) ? $log : [];
           return [
               'website_enabled' => acore_website_2fa_enabled($user->ID),
               'ingame_enabled'  => acore_ingame_2fa_enabled($user->ID),
               'removal_count'   => count($log),
           ];
       }
   ) );

   // User: paginated connection history (for "see more" without a page reload)
   register_rest_route( ACORE_SLUG . '/v1', 'connections', array(
       'methods'             => 'GET',
       'permission_callback' => function() { return is_user_logged_in(); },
       'callback'            => function( \WP_REST_Request $request ) {
           $user    = wp_get_current_user();
           $perPage = 50;
           $page    = max(1, (int) $request->get_param('page'));

           $all    = \ACore\Hooks\User\acore_get_login_history($user->ID, 500);
           $all    = is_array($all) ? $all : [];
           $total  = count($all);
           $offset = ($page - 1) * $perPage;
           $slice  = array_slice($all, $offset, $perPage);
           $myIp   = \ACore\Hooks\User\acore_resolve_client_ip();

           $rows = array_map(function ($r) use ($myIp) {
               $ip = $r['ip_address'] ?? ($r['ip'] ?? '');
               return [
                   'ip'      => $ip,
                   'country' => ($r['country'] ?? '') !== '' ? $r['country'] : 'Unknown',
                   'date'    => ($r['login_at'] ?? '') !== '' ? \ACore\Hooks\User\acore_format_connection_date($r['login_at']) : '',
                   'where'   => (($r['source'] ?? 'website') === 'ingame') ? 'In-game' : 'Website',
                   'current' => ($ip !== '' && $ip === $myIp),
               ];
           }, $slice);

           return [
               'rows'     => array_values($rows),
               'page'     => $page,
               'total'    => $total,
               'from'     => $total ? $offset + 1 : 0,
               'to'       => $offset + count($slice),
               'has_more' => ($offset + count($slice)) < $total,
           ];
       }
   ) );
});
