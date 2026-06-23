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
    if (!ctype_digit((string) $bits)) {
        return false;
    }
    $bits  = (int) $bits;
    $ipBin = @inet_pton($ip);
    $suBin = @inet_pton($subnet);
    if ($ipBin === false || $suBin === false || strlen($ipBin) !== strlen($suBin)) {
        return false;
    }
    if ($bits > strlen($ipBin) * 8) {
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

function acore_geoip_is_public_ip($ip): bool {
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return false;
    }
    $long = ip2long($ip);
    // RFC 6598 shared address space (100.64.0.0/10) is not covered by the flags.
    if ($long !== false && ($long & 0xFFC00000) === (ip2long('100.64.0.0') & 0xFFC00000)) {
        return false;
    }
    return true;
}

// Reuse a country already resolved for this IP in the history table (oldest wins).
function acore_geoip_known_country($ip) {
    global $wpdb;
    $table = $wpdb->prefix . 'acore_login_history';
    $c = $wpdb->get_var($wpdb->prepare(
        "SELECT country FROM {$table} WHERE ip_address = %s AND country NOT IN ('Unknown', 'Local', '') ORDER BY login_at ASC, id ASC LIMIT 1",
        $ip
    ));
    return $c ?: null;
}

function acore_geoip_is_blocked(): bool {
    return (bool) get_transient('acore_geoip_blocked');
}

// Honour ip-api rate-limit headers: if X-Rl hits 0, stop calling for X-Ttl seconds.
function acore_geoip_note_headers($response): void {
    $rl  = wp_remote_retrieve_header($response, 'x-rl');
    $ttl = wp_remote_retrieve_header($response, 'x-ttl');
    if ($rl !== '' && (int) $rl <= 0) {
        set_transient('acore_geoip_blocked', 1, max(1, (int) $ttl));
    }
}

function acore_lookup_country($ip) {
    if (!acore_geoip_is_public_ip($ip)) {
        return 'Local';
    }
    if (get_option('acore_geoip_lookup', '0') !== '1') {
        return 'Unknown';
    }
    // Already resolved this IP before? Reuse it - no API call.
    $known = acore_geoip_known_country($ip);
    if ($known) {
        return $known;
    }
    // Inside an ip-api cooldown - defer to the backfill cron.
    if (acore_geoip_is_blocked()) {
        return 'Unknown';
    }

    $response = wp_remote_get("http://ip-api.com/json/{$ip}?fields=status,countryCode", [
        'timeout' => 3,
    ]);
    if (is_wp_error($response)) {
        return 'Unknown';
    }
    acore_geoip_note_headers($response);

    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($data['status'], $data['countryCode']) && $data['status'] === 'success') {
        return $data['countryCode'];
    }
    return 'Unknown';
}

/* GeoIP backfill: resolve Unknown countries in the background, oldest first,
   reusing known IPs and using the batch endpoint, within ip-api's limits. */
add_filter('cron_schedules', function ($s) {
    if (!isset($s['acore_5min'])) {
        $s['acore_5min'] = ['interval' => 300, 'display' => 'Every 5 minutes (ACore)'];
    }
    return $s;
});

add_action('init', __NAMESPACE__ . '\\acore_geoip_schedule_backfill');
function acore_geoip_schedule_backfill() {
    if (!wp_next_scheduled('acore_geoip_backfill_event')) {
        wp_schedule_event(time() + 300, 'acore_5min', 'acore_geoip_backfill_event');
    }
}

add_action('acore_geoip_backfill_event', __NAMESPACE__ . '\\acore_geoip_backfill');
function acore_geoip_backfill() {
    if (get_option('acore_security_logging', '0') !== '1'
        || get_option('acore_geoip_lookup', '0') !== '1'
        || acore_geoip_is_blocked()) {
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix . 'acore_login_history';

    $rows = $wpdb->get_results(
        "SELECT ip_address, MIN(login_at) AS oldest
         FROM {$table}
         WHERE country = 'Unknown'
         GROUP BY ip_address
         ORDER BY oldest ASC
         LIMIT 100",
        ARRAY_A
    );
    if (!$rows) {
        return;
    }

    $toQuery = [];
    foreach ($rows as $r) {
        $ip = $r['ip_address'];
        if (!acore_geoip_is_public_ip($ip)) {
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET country = 'Local' WHERE ip_address = %s AND country = 'Unknown'", $ip));
            continue;
        }
        $known = acore_geoip_known_country($ip);
        if ($known) {
            $wpdb->query($wpdb->prepare("UPDATE {$table} SET country = %s WHERE ip_address = %s AND country = 'Unknown'", $known, $ip));
            continue;
        }
        $toQuery[] = $ip;
    }
    if (empty($toQuery)) {
        return;
    }

    $toQuery  = array_slice(array_values(array_unique($toQuery)), 0, 100);
    $response = wp_remote_post('http://ip-api.com/batch?fields=status,countryCode,query', [
        'timeout' => 5,
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => wp_json_encode(array_map(function ($ip) { return ['query' => $ip]; }, $toQuery)),
    ]);
    if (is_wp_error($response)) {
        return;
    }
    acore_geoip_note_headers($response);

    $results = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($results)) {
        return;
    }
    foreach ($results as $res) {
        if (empty($res['query'])) {
            continue;
        }
        if (isset($res['status'], $res['countryCode']) && $res['status'] === 'success') {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$table} SET country = %s WHERE ip_address = %s AND country = 'Unknown'",
                $res['countryCode'],
                $res['query']
            ));
        }
    }
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
