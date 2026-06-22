<?php

namespace ACore\Hooks\User;

add_action('init', __NAMESPACE__ . '\\acore_create_login_history_table');

function acore_create_login_history_table() {
    if (get_option('acore_login_history_db_version') === '2') {
        return;
    }

    global $wpdb;
    $table   = $wpdb->prefix . 'acore_login_history';
    $collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id bigint(20) UNSIGNED NOT NULL,
        ip_address varchar(45) NOT NULL,
        country varchar(10) NOT NULL DEFAULT 'Unknown',
        source varchar(20) NOT NULL DEFAULT 'website',
        login_at datetime NOT NULL,
        PRIMARY KEY (id),
        KEY user_id (user_id),
        KEY login_at (login_at)
    ) $collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('acore_login_history_db_version', '2');
}

add_action('wp_login', __NAMESPACE__ . '\\acore_log_login', 10, 2);

function acore_log_login($user_login, $user) {
    if (get_option('acore_security_logging', '0') !== '1') {
        return;
    }

    global $wpdb;

    $ip = acore_resolve_client_ip();
    $country = acore_lookup_country($ip);

    $wpdb->insert(
        $wpdb->prefix . 'acore_login_history',
        [
            'user_id'    => $user->ID,
            'ip_address' => $ip,
            'country'    => $country,
            'source'     => 'website',
            'login_at'   => current_time('mysql'),
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );

    // Also capture the account's most recent in-game login IP.
    acore_log_ingame_last_ip($user);
}

/**
 * Record the player's last in-game login IP (from the game account table) as an
 * "ingame" connection entry. Skipped if it already matches the latest one.
 */
function acore_log_ingame_last_ip($user) {
    try {
        $conn = \ACore\Manager\ACoreServices::I()->getAccountEm()->getConnection();
        $row  = $conn->executeQuery(
            'SELECT last_ip, last_login FROM account WHERE username = ?',
            [strtoupper($user->user_login)]
        )->fetchAssociative();
    } catch (\Throwable $e) {
        return;
    }

    if (!$row || empty($row['last_ip']) || $row['last_ip'] === '0.0.0.0') {
        return;
    }

    $ip      = sanitize_text_field($row['last_ip']);
    $loginAt = !empty($row['last_login']) ? $row['last_login'] : current_time('mysql');

    global $wpdb;
    $table = $wpdb->prefix . 'acore_login_history';

    // De-dup: skip if the latest ingame row already matches this IP + time.
    $last = $wpdb->get_row($wpdb->prepare(
        "SELECT ip_address, login_at FROM {$table} WHERE user_id = %d AND source = 'ingame' ORDER BY login_at DESC, id DESC LIMIT 1",
        $user->ID
    ), ARRAY_A);
    if ($last && $last['ip_address'] === $ip && (string) $last['login_at'] === (string) $loginAt) {
        return;
    }

    $wpdb->insert(
        $table,
        [
            'user_id'    => $user->ID,
            'ip_address' => $ip,
            'country'    => acore_lookup_country($ip),
            'source'     => 'ingame',
            'login_at'   => $loginAt,
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );
}

function acore_resolve_client_ip() {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ip     = $remote;

    // Honour forwarded headers only when REMOTE_ADDR is a configured trusted proxy.
    if (get_option('acore_trust_proxy_headers', '0') === '1' && acore_ip_is_trusted_proxy($remote)) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts     = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $candidate = trim($parts[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate;
            }
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
    }

    $ip = trim((string) $ip);
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        $ip = $remote;
    }
    return $ip;
}

function acore_ip_is_trusted_proxy($ip): bool {
    $list = get_option('acore_trusted_proxies', '');
    if (!is_string($list) || trim($list) === '') {
        return false;
    }
    foreach (preg_split('/[\s,]+/', trim($list)) as $entry) {
        if ($entry !== '' && acore_ip_in_cidr($ip, $entry)) {
            return true;
        }
    }
    return false;
}

function acore_ip_in_cidr($ip, $cidr): bool {
    if (strpos($cidr, '/') === false) {
        return $ip === $cidr;
    }
    list($subnet, $bits) = explode('/', $cidr, 2);
    $bits  = (int) $bits;
    $ipBin = @inet_pton($ip);
    $suBin = @inet_pton($subnet);
    if ($ipBin === false || $suBin === false || strlen($ipBin) !== strlen($suBin)) {
        return false;
    }
    $bytes = intdiv($bits, 8);
    $rem   = $bits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($suBin, 0, $bytes)) {
        return false;
    }
    if ($rem > 0) {
        $mask = chr((0xff << (8 - $rem)) & 0xff);
        if ((ord($ipBin[$bytes]) & ord($mask)) !== (ord($suBin[$bytes]) & ord($mask))) {
            return false;
        }
    }
    return true;
}

function acore_lookup_country($ip) {
    // Only send public IPs to the GeoIP provider (filter_var + RFC 6598 100.64/10).
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return 'Local';
    }
    $long = ip2long($ip);
    if ($long !== false && ($long & 0xFFC00000) === (ip2long('100.64.0.0') & 0xFFC00000)) {
        return 'Local';
    }

    if (get_option('acore_geoip_lookup', '0') !== '1') {
        return 'Unknown';
    }

    $response = wp_remote_get("https://ip-api.com/json/{$ip}?fields=status,countryCode", [
        'timeout' => 3,
    ]);

    if (is_wp_error($response)) {
        return 'Unknown';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($data['status'], $data['countryCode']) && $data['status'] === 'success') {
        return $data['countryCode'];
    }

    return 'Unknown';
}

function acore_get_login_history($user_id, $limit = 50) {
    global $wpdb;
    $table = $wpdb->prefix . 'acore_login_history';
    return $wpdb->get_results($wpdb->prepare(
        "SELECT ip_address, country, login_at, source FROM $table WHERE user_id = %d ORDER BY login_at DESC LIMIT %d",
        $user_id,
        $limit
    ), ARRAY_A);
}

function acore_format_connection_date($datetime_str) {
    $dt = new \DateTime($datetime_str);
    $day = (int) $dt->format('j');

    if (in_array($day % 100, [11, 12, 13])) {
        $suffix = 'th';
    } else {
        switch ($day % 10) {
            case 1:  $suffix = 'st'; break;
            case 2:  $suffix = 'nd'; break;
            case 3:  $suffix = 'rd'; break;
            default: $suffix = 'th';
        }
    }

    return sprintf('%d%s of %s, %s at %s',
        $day,
        $suffix,
        $dt->format('F'),
        $dt->format('Y'),
        $dt->format('H:i')
    );
}
