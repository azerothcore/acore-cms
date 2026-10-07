<?php

namespace ACore\Components\CharactersMenu;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;
use ACore\Utils\AcoreCharColors;

add_action('rest_api_init', function () {
    register_rest_route(ACORE_SLUG . '/v1', 'pdump/(?P<guid>\d+)', [
        'methods'             => 'GET',
        'callback'            => __NAMESPACE__ . '\handlePdump',
        'permission_callback' => '__return_true',
    ]);
    register_rest_route(ACORE_SLUG . '/v1', 'pdump/all', [
        'methods'             => 'POST',
        'callback'            => __NAMESPACE__ . '\handlePdumpAll',
        'permission_callback' => '__return_true',
    ]);
});

function pdumpGetAccountSecurity(int $accId): int
{
    try {
        $authConn = ACoreServices::I()->getAccountEm()->getConnection();
        // Mirrors LOGIN_GET_GMLEVEL_BY_REALMID: realm-specific row takes priority
        // over the catch-all (RealmID = -1) via ORDER BY RealmID DESC LIMIT 1.
        $row = $authConn->executeQuery(
            'SELECT `gmlevel` FROM `account_access`
             WHERE `id` = ? AND (`RealmID` = -1 OR `RealmID` = (SELECT `id` FROM `realmlist` LIMIT 1))
             ORDER BY `RealmID` DESC LIMIT 1',
            [$accId]
        )->fetchAssociative();
        return $row !== false ? (int) $row['gmlevel'] : 0;
    } catch (\Throwable $e) {
        return 0;
    }
}

/**
 * Resolve the account's effective RBAC permissions the way worldserver does:
 * default permissions for its security level, plus account grants, minus
 * account denials, expanded through rbac_linked_permissions.
 *
 * @param int $accId AzerothCore account ID
 * @return array<int,true>|null Permission IDs as keys, null if RBAC tables are unavailable
 */
function pdumpGetAccountPermissions(int $accId): ?array
{
    try {
        $authConn = ACoreServices::I()->getAccountEm()->getConnection();
        $realm    = '(SELECT `id` FROM `realmlist` LIMIT 1)';
        $secLevel = pdumpGetAccountSecurity($accId);

        $granted = [];
        $rows = $authConn->executeQuery(
            "SELECT `permissionId` FROM `rbac_default_permissions`
             WHERE `secId` = ? AND (`realmId` = -1 OR `realmId` = {$realm})",
            [$secLevel]
        )->fetchFirstColumn();
        foreach ($rows as $id) {
            $granted[(int) $id] = true;
        }

        $denied = [];
        $rows = $authConn->executeQuery(
            "SELECT `permissionId`, `granted` FROM `rbac_account_permissions`
             WHERE `accountId` = ? AND (`realmId` = -1 OR `realmId` = {$realm})",
            [$accId]
        )->fetchAllAssociative();
        foreach ($rows as $row) {
            if ((int) $row['granted'] === 1) {
                $granted[(int) $row['permissionId']] = true;
            } else {
                $denied[(int) $row['permissionId']] = true;
            }
        }
        // Expand linked permissions (roles) on both sets, then remove denials
        $expand = function (array $perms) use ($authConn): array {
            $frontier = array_keys($perms);
            while (!empty($frontier)) {
                $placeholders = implode(',', array_fill(0, count($frontier), '?'));
                $linked = $authConn->executeQuery(
                    "SELECT `linkedId` FROM `rbac_linked_permissions` WHERE `id` IN ({$placeholders})",
                    $frontier
                )->fetchFirstColumn();
                $frontier = [];
                foreach ($linked as $id) {
                    $id = (int) $id;
                    if (!isset($perms[$id])) {
                        $perms[$id] = true;
                        $frontier[] = $id;
                    }
                }
            }
            return $perms;
        };

        return array_diff_key($expand($granted), $expand($denied));
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Ensure the PDUMP export log table exists (lazy, runs once via wp_option version flag).
 */
function pdumpEnsureLogTable(): void
{
    if (get_option('acore_pdump_log_db_v') === '1') {
        return;
    }
    global $wpdb;
    $table   = $wpdb->prefix . 'acore_pdump_log';
    $charset = $wpdb->get_charset_collate();
    $sql     = "CREATE TABLE IF NOT EXISTS `{$table}` (
        `id`          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `user_id`     BIGINT(20) UNSIGNED NOT NULL,
        `account_id`  INT(10) UNSIGNED    NOT NULL,
        `type`        VARCHAR(10)         NOT NULL,
        `ip`          VARCHAR(45)         NOT NULL DEFAULT '',
        `exported_at` DATETIME            NOT NULL,
        `characters`  LONGTEXT            NOT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id`     (`user_id`),
        KEY `exported_at` (`exported_at`)
    ) {$charset};";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
    update_option('acore_pdump_log_db_v', '1');
}

/**
 * Write a PDUMP export log entry.
 *
 * @param int    $userId WordPress user ID
 * @param int    $accId  AzerothCore account ID
 * @param string $type   'single' or 'all'
 * @param array  $chars  [['name'=>..., 'guid'=>..., 'level'=>..., 'race'=>..., 'class'=>...], ...]
 */
function pdumpWriteLog(int $userId, int $accId, string $type, array $chars): void
{
    global $wpdb;
    pdumpEnsureLogTable();
    $wpdb->insert(
        $wpdb->prefix . 'acore_pdump_log',
        [
            'user_id'     => $userId,
            'account_id'  => $accId,
            'type'        => $type,
            'ip'          => \ACore\Hooks\User\acore_resolve_client_ip(),
            'exported_at' => current_time('mysql'),
            'characters'  => wp_json_encode($chars),
        ],
        ['%d', '%d', '%s', '%s', '%s', '%s']
    );
}

/**
 * Format a number of seconds into a human-readable string like "1h 30m".
 */
function pdumpFormatCooldown(int $seconds): string
{
    $d = intdiv($seconds, 86400);
    $h = intdiv($seconds % 86400, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;

    $parts = [];
    if ($d) $parts[] = $d . 'd';
    if ($h) $parts[] = $h . 'h';
    if ($m) $parts[] = $m . 'm';
    if ($s) $parts[] = $s . 's';
    return implode(' ', $parts) ?: '0s';
}

/**
 * Check whether the current account is eligible to use PDUMP at all.
 *
 * Enforces:
 *  - Server maintenance gate (realmlist.allowedSecurityLevel > account.gmlevel)
 *  - Min account age since registration (when min-req enabled)
 *  - Min total playtime across all characters (when min-req enabled)
 *  - Min character level reached on any character (when min-req enabled)
 *
 * @param int $accId AzerothCore account ID
 * @return string|null  null if eligible; error message string if blocked
 */
function pdumpCheckEligibility(int $accId): ?string
{
    $opts = Opts::I();

    // ── Maintenance mode gate ───────────────────────────────────────────────
    // Mirrors authserver: lock = (realm.AllowedSecurityLevel > account.gmlevel)
    // so players below the threshold are blocked but GMs/admins are not.
    $accountSecurity = null;
    if ($opts->acore_pdump_block_maintenance == '1') {
        try {
            $authConn = ACoreServices::I()->getAccountEm()->getConnection();
            $rlRow    = $authConn->executeQuery(
                'SELECT `allowedSecurityLevel` FROM `realmlist` LIMIT 1'
            )->fetchAssociative();
            if ($rlRow) {
                $realmSecLevel   = (int) $rlRow['allowedSecurityLevel'];
                $accountSecurity = pdumpGetAccountSecurity($accId);
                if ($realmSecLevel > $accountSecurity) {
                    return 'Character export is unavailable while the server is in maintenance mode.';
                }
            }
        } catch (\Throwable $e) {
            return 'Could not verify server status. Please try again later.';
        }
    }

    // ── Player security level gate ──────────────────────────────────────────
    $allowedSecLevel = (int) $opts->acore_pdump_allowed_sec_level;
    if ($allowedSecLevel > 0) {
        if ($accountSecurity === null) {
            $accountSecurity = pdumpGetAccountSecurity($accId);
        }
        if ($accountSecurity < $allowedSecLevel) {
            return 'Character export is currently unavailable for your account.';
        }
    }

    // ── Minimum requirements ────────────────────────────────────────────────
    if ($opts->acore_pdump_min_req_enabled != '1') {
        return null;
    }

    $checkAge      = $opts->acore_pdump_min_acct_age_enabled   == '1';
    $checkPlaytime = $opts->acore_pdump_min_playtime_enabled    == '1';
    $checkLevel    = $opts->acore_pdump_min_char_level_enabled  == '1';

    $minAcctAge   = $checkAge      ? (int) $opts->acore_pdump_min_acct_age   : 0;
    $minPlaytime  = $checkPlaytime ? (int) $opts->acore_pdump_min_playtime   : 0;
    $minCharLevel = $checkLevel    ? (int) $opts->acore_pdump_min_char_level : 0;

    // ── Min account age (from auth DB) ─────────────────────────────────────
    if ($minAcctAge > 0) {
        try {
            $authConn = ACoreServices::I()->getAccountEm()->getConnection();
            $accRow   = $authConn->executeQuery(
                'SELECT UNIX_TIMESTAMP(`joindate`) AS joindate_ts FROM `account` WHERE `id` = ? LIMIT 1',
                [$accId]
            )->fetchAssociative();

            if ($accRow === false) {
                return 'Account not found.';
            }

            $accountAge = time() - (int) $accRow['joindate_ts'];
            if ($accountAge < $minAcctAge) {
                $remaining = $minAcctAge - $accountAge;
                return 'Your account must be at least ' . pdumpFormatCooldown($minAcctAge) . ' old to use PDUMP. '
                    . 'Eligible in: ' . pdumpFormatCooldown($remaining) . '.';
            }
        } catch (\Throwable $e) {
            return 'Could not verify account eligibility. Please try again later.';
        }
    }

    // ── Min playtime + min character level (from char DB) ──────────────────
    if ($minPlaytime > 0 || $minCharLevel > 0) {
        try {
            $charConn = ACoreServices::I()->getCharacterEm()->getConnection();

            if ($minPlaytime > 0) {
                $ptRow = $charConn->executeQuery(
                    'SELECT COALESCE(SUM(`totaltime`), 0) AS total_secs FROM `characters` WHERE `account` = ? AND `deleteDate` IS NULL',
                    [$accId]
                )->fetchAssociative();
                $totalPlaytime = (int) ($ptRow['total_secs'] ?? 0);
                if ($totalPlaytime < $minPlaytime) {
                    $remaining = $minPlaytime - $totalPlaytime;
                    return 'You need at least ' . pdumpFormatCooldown($minPlaytime) . ' of total playtime to use PDUMP. '
                        . 'You need ' . pdumpFormatCooldown($remaining) . ' more.';
                }
            }

            if ($minCharLevel > 0) {
                $lvRow = $charConn->executeQuery(
                    'SELECT MAX(`level`) AS max_level FROM `characters` WHERE `account` = ? AND `deleteDate` IS NULL',
                    [$accId]
                )->fetchAssociative();
                $maxLevel = (int) ($lvRow['max_level'] ?? 0);
                if ($maxLevel < $minCharLevel) {
                    return 'You must reach at least level ' . $minCharLevel . ' on one character to use PDUMP.';
                }
            }
        } catch (\Throwable $e) {
            return 'Could not verify character eligibility. Please try again later.';
        }
    }

    return null;
}

/**
 * Resolve the effective cooldown for the current user.
 *
 * Checks all enabled override systems (subscription, RBAC, contributor) and
 * returns the minimum (most permissive) applicable cooldown. Falls back to
 * the global default when no override matches.
 *
 * @param string $type   'single' or 'all'
 * @param int    $userId WordPress user ID
 * @param int    $accId  AzerothCore account ID
 * @return int Cooldown in seconds (0 = no cooldown)
 */
function pdumpResolveEffectiveCooldown(string $type, int $userId, int $accId): int
{
    $opts    = Opts::I();
    $default = max(0, (int) ($type === 'single'
        ? $opts->acore_pdump_cooldown_single
        : $opts->acore_pdump_cooldown_all));

    $candidates = [];

    // ── Subscription overrides ──────────────────────────────────────────────
    // Reads from acore_cms_subscriptions (auth DB), which stores the PMPro
    // membership level ID. Tiers are matched against that ID directly.
    if ($opts->acore_pdump_subscription_enabled == '1') {
        $tiers = (array) $opts->acore_pdump_subscription_cooldowns;
        if (!empty($tiers)) {
            try {
                $authConn = ACoreServices::I()->getAccountEm()->getConnection();
                $subRow   = $authConn->executeQuery(
                    'SELECT `membership_level` FROM `acore_cms_subscriptions`
                     WHERE `account_name` COLLATE utf8mb4_general_ci =
                           (SELECT `username` FROM `account` WHERE `id` = ? LIMIT 1)',
                    [$accId]
                )->fetchAssociative();
                if ($subRow) {
                    $memberLevel = (int) $subRow['membership_level'];
                    if ($memberLevel > 0) {
                        foreach ($tiers as $tier) {
                            if ((int) ($tier['level'] ?? 0) === $memberLevel) {
                                $candidates[] = !empty($tier['use_default'])
                                    ? $default
                                    : max(0, (int) ($tier[$type] ?? 0));
                                break;
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Auth DB unavailable — skip subscription override
            }
        }
    }

    // ── RBAC overrides ──────────────────────────────────────────────────────
    if ($opts->acore_pdump_rbac_enabled == '1') {
        $tiers = (array) $opts->acore_pdump_rbac_cooldowns;
        if (!empty($tiers)) {
            // Every tier whose permission the account holds is a candidate
            $permIds = pdumpGetAccountPermissions($accId);
            if ($permIds !== null) {
                foreach ($tiers as $tier) {
                    if (isset($permIds[(int) ($tier['perm_id'] ?? 0)])) {
                        $candidates[] = !empty($tier['use_default'])
                            ? $default
                            : max(0, (int) ($tier[$type] ?? 0));
                    }
                }
            }
        }
    }

    // ── Contributor overrides ────────────────────────────────────────────────
    // Reads from mod_contributors_accounts (char DB) by AzerothCore account ID.
    if ($opts->acore_pdump_contributor_enabled == '1') {
        $tiers = (array) $opts->acore_pdump_contributor_cooldowns;
        if (!empty($tiers)) {
            try {
                $charConn    = ACoreServices::I()->getCharacterEm()->getConnection();
                $contribRow  = $charConn->executeQuery(
                    'SELECT `Level` FROM `mod_contributors_accounts` WHERE `AccountId` = ? LIMIT 1',
                    [$accId]
                )->fetchAssociative();
                if ($contribRow) {
                    $contribLevel = (int) $contribRow['Level'];
                    foreach ($tiers as $tier) {
                        if ((int) ($tier['level'] ?? 0) === $contribLevel) {
                            $candidates[] = !empty($tier['use_default'])
                                ? $default
                                : max(0, (int) ($tier[$type] ?? 0));
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Char DB unavailable — skip contributor override
            }
        }
    }

    // Return the most permissive (minimum) cooldown across all matched overrides
    // and the global default, so an override can never exceed the default.
    return min(array_merge([$default], $candidates));
}

/**
 * Attempt to acquire a per-user advisory lock to make the cooldown
 * check-and-reserve atomic. Returns the lock name on success, null on failure.
 *
 * Uses MySQL GET_LOCK() which is automatically released when the DB session
 * ends, guarding against orphaned locks on unexpected PHP termination.
 *
 * @param int    $userId WordPress user ID
 * @param string $type   'single' or 'all'
 * @return string|null Lock name if acquired, null if already locked
 */
function pdumpAcquireLock(int $userId, string $type): ?string
{
    global $wpdb;
    $lockName = 'acore_pdump_' . $type . '_' . $userId;
    $acquired = (bool) $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, 0)', $lockName));
    return $acquired ? $lockName : null;
}

/**
 * Release a previously acquired advisory lock.
 */
function pdumpReleaseLock(string $lockName): void
{
    global $wpdb;
    $wpdb->query($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lockName));
}

/**
 * Give back a cooldown slot reserved by a failed export, restoring the
 * previous timestamp (or clearing it when there was none).
 *
 * @param int      $userId WordPress user ID
 * @param string   $type   'single' or 'all'
 * @param int|null $prev   Timestamp before the reservation, null if nothing was reserved
 */
function pdumpRestoreCooldown(int $userId, string $type, ?int $prev): void
{
    if ($prev === null) {
        return;
    }
    $key = '_acore_pdump_last_' . $type;
    if ($prev > 0) {
        update_user_meta($userId, $key, $prev);
    } else {
        delete_user_meta($userId, $key);
    }
}

function handlePdump(\WP_REST_Request $request): void
{
    $guid  = (int) $request->get_param('guid');
    $accId = ACoreServices::I()->getAcoreAccountId();

    if (Opts::I()->acore_pdump_enabled != '1') {
        wp_send_json_error(['message' => 'PDUMP export is not enabled on this server.'], 403);
        exit;
    }

    if (Opts::I()->acore_pdump_single_enabled != '1') {
        wp_send_json_error(['message' => 'Single character export is currently disabled.'], 403);
        exit;
    }

    if (!$accId || $guid < 1) {
        wp_send_json_error(['message' => 'Forbidden.'], 403);
        exit;
    }

    $eligibilityError = pdumpCheckEligibility($accId);
    if ($eligibilityError !== null) {
        wp_send_json_error(['message' => $eligibilityError], 403);
        exit;
    }

    $conn = ACoreServices::I()->getCharacterEm()->getConnection();
    $row  = $conn->executeQuery(
        "SELECT `name`, `level`, `race`, `class` FROM `characters`
         WHERE `guid` = ? AND `account` = ? AND `deleteDate` IS NULL
         LIMIT 1",
        [$guid, $accId]
    )->fetchAssociative();

    if (!$row) {
        wp_send_json_error(['message' => 'Character not found.'], 403);
        exit;
    }

    $userId   = get_current_user_id();
    $cooldown = pdumpResolveEffectiveCooldown('single', $userId, $accId);
    $lockName = null;
    $prevTime = null;

    if ($cooldown > 0) {
        $lockName = pdumpAcquireLock($userId, 'single');
        if ($lockName === null) {
            wp_send_json_error(['message' => 'Another export is already in progress. Please try again shortly.'], 429);
            exit;
        }

        wp_cache_delete($userId, 'user_meta');
        $lastTime = (int) get_user_meta($userId, '_acore_pdump_last_single', true);
        $elapsed  = time() - $lastTime;
        if ($elapsed < $cooldown) {
            $remaining = $cooldown - $elapsed;
            pdumpReleaseLock($lockName);
            wp_send_json_error([
                'message'     => 'You can export again in ' . pdumpFormatCooldown($remaining) . '.',
                'retry_after' => $remaining,
            ], 429);
            exit;
        }

        // Reserve the slot atomically before releasing the lock
        $prevTime = $lastTime;
        update_user_meta($userId, '_acore_pdump_last_single', time());
        pdumpReleaseLock($lockName);
    }

    $charName = $row['name'];

    $phpWarnings = [];
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$phpWarnings): bool {
        $phpWarnings[] = "[{$errno}] {$errstr} in {$errfile}:{$errline}";
        return true;
    });

    ob_start();
    $dump = null;
    try {
        $writer = new CharacterDumpWriter($conn);
        $dump   = $writer->getDump($guid);
    } catch (\Throwable $e) {
        $phpWarnings[] = get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
    }
    ob_end_clean();
    restore_error_handler();

    if (!empty($phpWarnings)) {
        pdumpRestoreCooldown($userId, 'single', $prevTime);
        wp_send_json_error([
            'message' => 'An internal error occurred while generating the dump.',
            'detail'  => implode("\n", $phpWarnings),
        ], 500);
        exit;
    }

    if ($dump === null) {
        pdumpRestoreCooldown($userId, 'single', $prevTime);
        wp_send_json_error(['message' => 'Character is deleted and cannot be exported.'], 400);
        exit;
    }

    $filename = 'char_' . $guid . '_' . date('Ymd_His') . '.dump';

    if (Opts::I()->acore_pdump_log_enabled == '1') {
        pdumpWriteLog($userId, $accId, 'single', [[
            'name'  => $charName,
            'guid'  => $guid,
            'level' => (int) ($row['level'] ?? 0),
            'race'  => AcoreCharColors::getRaceName((int) ($row['race']  ?? 0)),
            'class' => AcoreCharColors::getClassName((int) ($row['class'] ?? 0)),
        ]]);
    }

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($dump));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    echo $dump;
    exit;
}

function handlePdumpAll(\WP_REST_Request $request): void
{
    $accId = ACoreServices::I()->getAcoreAccountId();

    if (Opts::I()->acore_pdump_enabled != '1') {
        wp_send_json_error(['message' => 'PDUMP export is not enabled on this server.'], 403);
        exit;
    }

    if (Opts::I()->acore_pdump_all_enabled != '1') {
        wp_send_json_error(['message' => 'Export All is currently disabled.'], 403);
        exit;
    }

    if (!$accId) {
        wp_send_json_error(['message' => 'Forbidden.'], 403);
        exit;
    }

    $eligibilityError = pdumpCheckEligibility($accId);
    if ($eligibilityError !== null) {
        wp_send_json_error(['message' => $eligibilityError], 403);
        exit;
    }

    $body       = $request->get_json_params();
    $characters = $body['characters'] ?? [];
    if (empty($characters) || !is_array($characters)) {
        wp_send_json_error(['message' => 'No characters provided.'], 400);
        exit;
    }

    $userId      = get_current_user_id();
    $cooldownAll = pdumpResolveEffectiveCooldown('all', $userId, $accId);
    $lockName    = null;
    $prevTimeAll = null;

    if ($cooldownAll > 0) {
        $lockName = pdumpAcquireLock($userId, 'all');
        if ($lockName === null) {
            wp_send_json_error(['message' => 'Another export is already in progress. Please try again shortly.'], 429);
            exit;
        }

        wp_cache_delete($userId, 'user_meta');
        $lastTimeAll = (int) get_user_meta($userId, '_acore_pdump_last_all', true);
        $elapsedAll  = time() - $lastTimeAll;
        if ($elapsedAll < $cooldownAll) {
            $remaining = $cooldownAll - $elapsedAll;
            pdumpReleaseLock($lockName);
            wp_send_json_error([
                'message'     => 'You can export all again in ' . pdumpFormatCooldown($remaining) . '.',
                'retry_after' => $remaining,
            ], 429);
            exit;
        }

        // Reserve the slot atomically before releasing the lock
        $prevTimeAll = $lastTimeAll;
        update_user_meta($userId, '_acore_pdump_last_all', time());
        pdumpReleaseLock($lockName);
    }

    $conn = ACoreServices::I()->getCharacterEm()->getConnection();
    $now  = date('d_m_Y_H_i_s');

    $phpWarnings = [];
    set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use (&$phpWarnings): bool {
        $phpWarnings[] = "[{$errno}] {$errstr} in {$errfile}:{$errline}";
        return true;
    });

    ob_start();

    $files     = [];
    $logChars  = [];
    foreach ($characters as $char) {
        $guid = isset($char['guid']) ? (int) $char['guid'] : 0;
        if ($guid < 1) continue;

        $row = $conn->executeQuery(
            "SELECT `name`, `level`, `race`, `class` FROM `characters`
             WHERE `guid` = ? AND `account` = ? AND `deleteDate` IS NULL LIMIT 1",
            [$guid, $accId]
        )->fetchAssociative();

        if (!$row) continue;

        $writer = new CharacterDumpWriter($conn);
        $dump   = $writer->getDump($guid);
        if ($dump === null) continue;

        $order     = preg_replace('/[^0-9]/', '', (string)($char['order'] ?? '0'));
        $charLevel = (int) $row['level'];
        $raceName  = AcoreCharColors::getRaceName((int) $row['race']);
        $className = AcoreCharColors::getClassName((int) $row['class']);
        $raceSlug  = preg_replace('/[^a-zA-Z]/', '', $raceName);
        $classSlug = preg_replace('/[^a-zA-Z]/', '', $className);

        $files["{$order}_{$charLevel}_{$raceSlug}_{$classSlug}_{$now}.dump"] = $dump;
        $logChars[] = [
            'name'  => $row['name'],
            'guid'  => $guid,
            'level' => $charLevel,
            'race'  => $raceName,
            'class' => $className,
        ];
    }

    ob_end_clean();
    restore_error_handler();

    if (!empty($phpWarnings)) {
        pdumpRestoreCooldown($userId, 'all', $prevTimeAll);
        wp_send_json_error([
            'message' => 'An internal error occurred while generating the dump.',
            'detail'  => implode("\n", $phpWarnings),
        ], 500);
        exit;
    }

    if (empty($files)) {
        pdumpRestoreCooldown($userId, 'all', $prevTimeAll);
        wp_send_json_error(['message' => 'No valid characters could be exported.'], 400);
        exit;
    }

    $tmp = tempnam(sys_get_temp_dir(), 'pdump_');
    $zip = new \ZipArchive();
    $zip->open($tmp, \ZipArchive::OVERWRITE);
    foreach ($files as $filename => $content) {
        $zip->addFromString($filename, $content);
    }
    $zip->close();

    if (Opts::I()->acore_pdump_log_enabled == '1') {
        pdumpWriteLog($userId, $accId, 'all', $logChars);
    }

    if (ob_get_level()) ob_end_clean();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="dump_all_' . $now . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    readfile($tmp);
    unlink($tmp);
    exit;
}
