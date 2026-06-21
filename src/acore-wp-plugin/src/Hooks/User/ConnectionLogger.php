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
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    return sanitize_text_field($ip);
}

function acore_lookup_country($ip) {
    $private_ranges = ['127.', '10.', '192.168.', '172.16.', '172.17.', '172.18.',
                       '172.19.', '172.20.', '172.21.', '172.22.', '172.23.', '172.24.',
                       '172.25.', '172.26.', '172.27.', '172.28.', '172.29.', '172.30.',
                       '172.31.', '::1'];

    foreach ($private_ranges as $range) {
        if (strpos($ip, $range) === 0) {
            return 'Local';
        }
    }

    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,countryCode", [
        'timeout'   => 3,
        'sslverify' => false,
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

/**
 * Build realistic mock connection rows for UI preview (?mock_connections=N).
 * IPs repeat across website/in-game like real usage, and the viewer's own IP
 * appears in both so the "current IP" highlight is visible.
 */
function acore_mock_login_history($count) {
    // Default size when ?mock_connections is present without a number, and a
    // hard cap so it can never render more than this many rows.
    $count = (int) $count;
    if ($count <= 0) {
        $count = 120;
    }
    $count = max(1, min(200, $count)); // 200 = hard cap
    $myIp  = acore_resolve_client_ip();
    $pool  = [
        [$myIp, 'Local'],
        ['203.0.113.45', 'US'],
        ['198.51.100.22', 'GB'],
        ['192.0.2.181', 'DE'],
        ['203.0.113.45', 'US'],
        [$myIp, 'Local'],
        ['198.51.100.22', 'GB'],
    ];
    $out = [];
    for ($i = 0; $i < $count; $i++) {
        $entry = $pool[$i % count($pool)];
        $out[] = [
            'ip_address' => $entry[0],
            'country'    => $entry[1],
            'login_at'   => date('Y-m-d H:i:s', time() - $i * 3600),
            'source'     => ($i % 2 === 0) ? 'website' : 'ingame',
        ];
    }
    return $out;
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
