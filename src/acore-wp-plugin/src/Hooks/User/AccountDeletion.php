<?php

namespace ACore\Hooks\User;

use ACore\Manager\ACoreServices;
use ACore\Utils\AcoreUtils;

const ACORE_DELETE_ACCOUNT_ACTION = 'acore_delete_account';
const ACORE_DELETE_ACCOUNT_COUNTDOWN = 10;
const ACORE_DELETE_ACCOUNT_ARM_ACTION = 'acore_delete_account_arm';
const ACORE_DELETE_ACCOUNT_ARMED_META = 'acore_delete_account_armed_at';
// An armed confirmation older than this must be armed again by retyping the name.
const ACORE_DELETE_ACCOUNT_ARM_TTL = 10 * MINUTE_IN_SECONDS;

/**
 * Website capabilities that mark a user as staff. Staff accounts are removed by
 * another administrator, never through self-service. `edit_posts` is here because
 * wp_delete_user() without a reassign target deletes every post the user wrote.
 */
const ACORE_DELETE_ACCOUNT_STAFF_CAPS = [
    'edit_posts',
    'manage_options',
    'edit_others_posts',
    'list_users',
    'edit_users',
    'delete_users',
    'promote_users',
    'manage_woocommerce',
];

/**
 * Game account linked to a website user. Website and game accounts share the
 * username, and `account.username` is unique, so there is at most one.
 *
 * @return array{id:int,username:string,gmlevel:int}|null
 */
function acore_delete_account_get_game_account(\WP_User $user): ?array
{
    $conn = ACoreServices::I()->getAccountEm()->getConnection();
    $row  = $conn->executeQuery(
        'SELECT a.`id`, a.`username`, COALESCE(MAX(aa.`gmlevel`), 0) AS gmlevel
         FROM `account` a
         LEFT JOIN `account_access` aa ON aa.`id` = a.`id`
         WHERE a.`username` = ?
         GROUP BY a.`id`, a.`username`',
        [$user->user_login]
    )->fetchAssociative();

    if (!$row) {
        return null;
    }

    return [
        'id'       => (int) $row['id'],
        'username' => (string) $row['username'],
        'gmlevel'  => (int) $row['gmlevel'],
    ];
}

/**
 * On multisite the deletion removes the user from the whole network, so a role
 * on any of their sites counts, not only on the current one.
 */
function acore_delete_account_is_staff(\WP_User $user): bool
{
    $hasStaffCap = function () use ($user): bool {
        $current = new \WP_User($user->ID);
        foreach (ACORE_DELETE_ACCOUNT_STAFF_CAPS as $cap) {
            if ($current->has_cap($cap)) {
                return true;
            }
        }
        return false;
    };

    if (!is_multisite()) {
        return $hasStaffCap();
    }

    foreach (array_keys(get_blogs_of_user($user->ID, true)) as $blogId) {
        switch_to_blog($blogId);
        $isStaff = $hasStaffCap();
        restore_current_blog();
        if ($isStaff) {
            return true;
        }
    }
    return false;
}

/**
 * Deleting a user without a reassign target deletes the posts they authored,
 * whatever their role is now: on multisite every post of any type on each of
 * their sites, on a single site the post types wp_delete_user() picks.
 */
function acore_delete_account_owns_posts(\WP_User $user): bool
{
    global $wpdb;

    if (!is_multisite()) {
        // Same selection as wp_delete_user(). Types like `user_request` (privacy
        // requests) survive the deletion and must not block it.
        $types = [];
        foreach (get_post_types([], 'objects') as $type) {
            if ($type->delete_with_user || (null === $type->delete_with_user && post_type_supports($type->name, 'author'))) {
                $types[] = $type->name;
            }
        }
        $types = apply_filters('post_types_to_delete_with_user', $types, $user->ID);
        if (!$types) {
            return false;
        }
        $placeholders = implode(',', array_fill(0, count($types), '%s'));
        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$wpdb->posts} WHERE post_author = %d AND post_type IN ($placeholders) LIMIT 1",
            array_merge([$user->ID], $types)
        ));
    }

    foreach (array_keys(get_blogs_of_user($user->ID, true)) as $blogId) {
        switch_to_blog($blogId);
        $owns = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT 1 FROM {$wpdb->posts} WHERE post_author = %d LIMIT 1",
            $user->ID
        ));
        restore_current_blog();
        if ($owns) {
            return true;
        }
    }
    return false;
}

/**
 * True when the game account row is gone. The core commits the deletion to the
 * auth DB asynchronously, so this polls for a few seconds before giving up.
 */
function acore_delete_account_game_account_gone(int $accountId): bool
{
    $conn = ACoreServices::I()->getAccountEm()->getConnection();
    for ($i = 0; $i < 10; $i++) {
        $exists = $conn->executeQuery('SELECT 1 FROM `account` WHERE `id` = ?', [$accountId])->fetchOne();
        if ($exists === false) {
            return true;
        }
        usleep(500000);
    }
    return false;
}

/**
 * Why the user cannot delete their own account, or null when they can.
 * Throws when the game database cannot be reached.
 */
function acore_delete_account_block_reason(\WP_User $user, ?array $gameAccount): ?string
{
    if (is_super_admin($user->ID)) {
        return __('Staff accounts cannot be deleted from here. Ask another administrator.', 'acore-wp-plugin');
    }

    if (acore_delete_account_is_staff($user)) {
        return __('Staff accounts cannot be deleted from here. Ask another administrator.', 'acore-wp-plugin');
    }

    if (acore_delete_account_owns_posts($user)) {
        return __('Your account owns content on this site. Ask an administrator to delete it.', 'acore-wp-plugin');
    }

    if ($gameAccount && $gameAccount['gmlevel'] > 0) {
        return __('Game accounts with GM access cannot be deleted from here. Ask an administrator.', 'acore-wp-plugin');
    }

    return null;
}

// Late priority so the section renders below every other block of the profile page.
add_action('show_user_profile', __NAMESPACE__ . '\acore_profile_danger_zone', 999);

function acore_profile_danger_zone($user)
{
    global $pagenow;
    if ($pagenow !== 'profile.php' || isset($_GET['page'])) {
        return;
    }

    try {
        $gameAccount = acore_delete_account_get_game_account($user);
        $blockReason = acore_delete_account_block_reason($user, $gameAccount);
    } catch (\Throwable $e) {
        $gameAccount = null;
        $blockReason = __('Account deletion is unavailable right now because the game database cannot be reached.', 'acore-wp-plugin');
    }

    $accountName = $user->user_login;
?>
    <div class="acore-danger-zone" id="acore-danger-zone">
        <h2><?php _e('Danger zone', 'acore-wp-plugin'); ?></h2>
        <div class="acore-danger-zone-row">
            <div>
                <p class="acore-danger-zone-title"><?php _e('Delete account', 'acore-wp-plugin'); ?></p>
                <p class="acore-danger-zone-desc">
                    <?php if ($gameAccount): ?>
                        <?php _e('Permanently deletes your website account and your game account, including all of its characters. This cannot be undone.', 'acore-wp-plugin'); ?>
                    <?php else: ?>
                        <?php _e('Permanently deletes your website account. No game account is linked to it. This cannot be undone.', 'acore-wp-plugin'); ?>
                    <?php endif; ?>
                </p>
                <?php if ($blockReason): ?>
                    <p class="acore-danger-zone-desc"><strong><?= esc_html($blockReason) ?></strong></p>
                <?php endif; ?>
            </div>
            <button type="button" class="button acore-danger-button" id="acore-delete-account-open" <?= $blockReason ? 'disabled' : '' ?>>
                <?php _e('Delete account', 'acore-wp-plugin'); ?>
            </button>
        </div>
    </div>

    <?php if (!$blockReason): ?>
    <div id="acore-delete-account-modal" class="acore-delete-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="acore-delete-account-title">
        <div class="acore-delete-box">
            <h4 id="acore-delete-account-title"><?php _e('Delete your account?', 'acore-wp-plugin'); ?></h4>
            <p>
                <?php if ($gameAccount): ?>
                    <?php _e('Your website account, your game account and all of its characters will be deleted permanently.', 'acore-wp-plugin'); ?>
                <?php else: ?>
                    <?php _e('Your website account will be deleted permanently.', 'acore-wp-plugin'); ?>
                <?php endif; ?>
            </p>
            <p>
                <label for="acore-delete-account-name">
                    <?php printf(
                        __('Type %s to confirm.', 'acore-wp-plugin'),
                        '<strong>' . esc_html($accountName) . '</strong>'
                    ); ?>
                </label>
            </p>
            <?php // No name attribute: this input sits inside the profile form and must not be submitted with it. ?>
            <input type="text" id="acore-delete-account-name" class="regular-text" autocomplete="off" autocapitalize="off" spellcheck="false">
            <div class="acore-delete-actions">
                <button type="button" class="button acore-danger-button" id="acore-delete-account-confirm" disabled><?php _e('Confirm', 'acore-wp-plugin'); ?></button>
                <button type="button" class="button" id="acore-delete-account-cancel"><?php _e('Cancel', 'acore-wp-plugin'); ?></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
    .acore-danger-zone {
        margin: 32px 0 16px;
        max-width: 860px;
        border: 1px solid #d63638;
        border-radius: 6px;
        padding: 4px 16px 12px;
        background: #fff;
    }
    .acore-danger-zone h2 { color: #d63638; }
    .acore-danger-zone-row {
        display: flex;
        gap: 16px;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
    }
    .acore-danger-zone-title { font-weight: 600; margin: 0 0 4px; }
    .acore-danger-zone-desc { margin: 0 0 4px; color: #50575e; }
    .wp-core-ui .button.acore-danger-button {
        color: #d63638;
        border-color: #d63638;
    }
    .wp-core-ui .button.acore-danger-button:hover:not(:disabled),
    .wp-core-ui .button.acore-danger-button:focus:not(:disabled) {
        background: #d63638;
        border-color: #d63638;
        color: #fff;
    }
    .acore-delete-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        z-index: 100000;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .acore-delete-box {
        background: #ffffff;
        color: #1d2327;
        border: 1px solid #c3c4c7;
        border-top: 4px solid #d63638;
        border-radius: 8px;
        padding: 24px 28px;
        max-width: 480px;
        width: 90%;
        box-shadow: 0 4px 24px rgba(0,0,0,0.2);
    }
    .acore-delete-box h4 { margin: 0 0 12px; font-size: 15px; font-weight: 700; color: #d63638; }
    .acore-delete-box p { font-size: 13px; line-height: 1.6; margin: 0 0 12px; }
    .acore-delete-box input[type="text"] { width: 100%; margin-bottom: 20px; }
    .acore-delete-actions { display: flex; gap: 8px; justify-content: flex-end; }
    body.acore-dark-mode .acore-danger-zone { background: #1e1e1e; border-color: #f86368; }
    body.acore-dark-mode .acore-danger-zone h2,
    body.acore-dark-mode .acore-delete-box h4 { color: #f86368; }
    body.acore-dark-mode .acore-danger-zone-desc { color: #c3c4c7; }
    body.acore-dark-mode .acore-delete-box { background: #1e1e1e; color: #e0e0e0; border-color: #3a3a3a; border-top-color: #f86368; }
    body.acore-dark-mode .acore-delete-box p { color: #e0e0e0; }
    </style>

    <script>
    (function() {
        // show_user_profile renders inside the profile form, above its submit
        // button. Move the section below the form so it is the last thing on the page.
        var zone = document.getElementById('acore-danger-zone');
        var form = document.getElementById('your-profile');
        if (zone && form && form.contains(zone)) {
            form.parentNode.insertBefore(zone, form.nextSibling);
        }
    })();
    </script>

    <?php if (!$blockReason): ?>
    <script>
    (function() {
        var expected  = <?= wp_json_encode($accountName) ?>;
        var seconds   = <?= (int) ACORE_DELETE_ACCOUNT_COUNTDOWN ?>;
        var label     = <?= wp_json_encode(__('Confirm', 'acore-wp-plugin')) ?>;
        var postUrl   = <?= wp_json_encode(admin_url('admin-post.php')) ?>;
        var ajaxUrl   = <?= wp_json_encode(admin_url('admin-ajax.php')) ?>;
        var armError  = <?= wp_json_encode(__('Could not start the countdown. Please reload the page and try again.', 'acore-wp-plugin')) ?>;
        var nonce     = <?= wp_json_encode(wp_create_nonce(ACORE_DELETE_ACCOUNT_ACTION . '_' . $user->ID)) ?>;
        var modal     = document.getElementById('acore-delete-account-modal');
        var openBtn   = document.getElementById('acore-delete-account-open');
        var cancelBtn = document.getElementById('acore-delete-account-cancel');
        var confirmBtn = document.getElementById('acore-delete-account-confirm');
        var input     = document.getElementById('acore-delete-account-name');
        var timer     = null;
        var remaining = 0;
        var submitting = false;
        // Bumped on every reset so a late arm reply cannot start a stale countdown.
        var armSeq    = 0;

        function stopCountdown() {
            armSeq++;
            if (timer) {
                if (timer !== -1) clearInterval(timer);
                timer = null;
            }
            confirmBtn.disabled = true;
            confirmBtn.textContent = label;
        }

        function runCountdown() {
            timer = setInterval(function() {
                remaining--;
                if (remaining > 0) {
                    confirmBtn.textContent = label + ' (' + remaining + ')';
                    return;
                }
                clearInterval(timer);
                timer = null;
                confirmBtn.textContent = label;
                confirmBtn.disabled = false;
            }, 1000);
        }

        // The server records when the name matched and refuses the deletion until
        // the countdown has elapsed there too, so the countdown starts on its reply.
        function startCountdown() {
            var seq = ++armSeq;
            remaining = seconds;
            confirmBtn.disabled = true;
            confirmBtn.textContent = label + ' (' + remaining + ')';
            timer = -1;
            var body = new URLSearchParams();
            body.append('action', <?= wp_json_encode(ACORE_DELETE_ACCOUNT_ARM_ACTION) ?>);
            body.append('_acore_nonce', nonce);
            body.append('acore_confirm_name', input.value);
            fetch(ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body })
                .then(function(res) { return res.json(); })
                .then(function(json) {
                    if (seq !== armSeq) return;
                    if (!json || !json.success) throw new Error('arm failed');
                    runCountdown();
                })
                .catch(function() {
                    if (seq !== armSeq) return;
                    timer = null;
                    confirmBtn.textContent = label;
                    window.alert(armError);
                });
        }

        function onInput() {
            if (input.value !== expected) {
                stopCountdown();
                return;
            }
            // Keep a running or finished countdown when the text still matches.
            if (!timer && confirmBtn.disabled) {
                startCountdown();
            }
        }

        function close() {
            if (submitting) return;
            stopCountdown();
            input.value = '';
            modal.style.display = 'none';
            openBtn.focus();
        }

        openBtn.addEventListener('click', function() {
            modal.style.display = 'flex';
            input.focus();
        });
        cancelBtn.addEventListener('click', close);
        modal.addEventListener('click', function(e) { if (e.target === modal) close(); });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display !== 'none') close();
        });
        input.addEventListener('input', onInput);
        // Enter would otherwise submit the surrounding profile form.
        input.addEventListener('keydown', function(e) { if (e.key === 'Enter') e.preventDefault(); });

        confirmBtn.addEventListener('click', function() {
            if (confirmBtn.disabled || input.value !== expected) return;
            submitting = true;
            confirmBtn.disabled = true;
            cancelBtn.disabled = true;

            // The profile page is already one big form and forms cannot nest,
            // so the request goes through a form built outside of it.
            var form = document.createElement('form');
            form.method = 'post';
            form.action = postUrl;
            [['action', <?= wp_json_encode(ACORE_DELETE_ACCOUNT_ACTION) ?>], ['_acore_nonce', nonce], ['acore_confirm_name', input.value]].forEach(function(pair) {
                var field = document.createElement('input');
                field.type = 'hidden';
                field.name = pair[0];
                field.value = pair[1];
                form.appendChild(field);
            });
            document.body.appendChild(form);
            form.submit();
        });
    })();
    </script>
    <?php endif; ?>
<?php
}

add_action('wp_ajax_' . ACORE_DELETE_ACCOUNT_ARM_ACTION, __NAMESPACE__ . '\acore_ajax_arm_delete_account');

/**
 * Records when the typed name matched, so the deletion request can be refused
 * until the countdown has really elapsed.
 */
function acore_ajax_arm_delete_account()
{
    $user = wp_get_current_user();
    check_ajax_referer(ACORE_DELETE_ACCOUNT_ACTION . '_' . $user->ID, '_acore_nonce');

    $typed = isset($_POST['acore_confirm_name']) ? (string) wp_unslash($_POST['acore_confirm_name']) : '';
    if (!hash_equals($user->user_login, $typed)) {
        wp_send_json_error(null, 400);
    }

    update_user_meta($user->ID, ACORE_DELETE_ACCOUNT_ARMED_META, (string) microtime(true));
    wp_send_json_success(['wait' => ACORE_DELETE_ACCOUNT_COUNTDOWN]);
}

add_action('admin_post_' . ACORE_DELETE_ACCOUNT_ACTION, __NAMESPACE__ . '\acore_handle_delete_account');

function acore_handle_delete_account()
{
    $profileUrl = admin_url('profile.php');

    if (!is_user_logged_in() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_safe_redirect(home_url('/'));
        exit;
    }

    $user = wp_get_current_user();
    check_admin_referer(ACORE_DELETE_ACCOUNT_ACTION . '_' . $user->ID, '_acore_nonce');

    $fail = function (string $message) use ($user, $profileUrl) {
        AcoreUtils::set_flash_message($message, 'error', $user->ID);
        wp_safe_redirect($profileUrl);
        exit;
    };

    $typed = isset($_POST['acore_confirm_name']) ? (string) wp_unslash($_POST['acore_confirm_name']) : '';
    if (!hash_equals($user->user_login, $typed)) {
        $fail(__('The account name you typed does not match. Nothing was deleted.', 'acore-wp-plugin'));
    }

    $armedAt = (float) get_user_meta($user->ID, ACORE_DELETE_ACCOUNT_ARMED_META, true);
    $elapsed = microtime(true) - $armedAt;
    if ($armedAt <= 0 || $elapsed < ACORE_DELETE_ACCOUNT_COUNTDOWN || $elapsed > ACORE_DELETE_ACCOUNT_ARM_TTL) {
        $fail(sprintf(
            __('Please type your account name and wait %d seconds before confirming. Nothing was deleted.', 'acore-wp-plugin'),
            ACORE_DELETE_ACCOUNT_COUNTDOWN
        ));
    }
    // One confirmation per arming: a failed attempt below needs a new countdown.
    delete_user_meta($user->ID, ACORE_DELETE_ACCOUNT_ARMED_META);

    try {
        $gameAccount = acore_delete_account_get_game_account($user);
    } catch (\Throwable $e) {
        $fail(__('The game database cannot be reached right now. Nothing was deleted, please try again later.', 'acore-wp-plugin'));
    }

    $blockReason = acore_delete_account_block_reason($user, $gameAccount);
    if ($blockReason) {
        $fail($blockReason);
    }

    if ($gameAccount) {
        // `account delete` goes through AccountMgr::DeleteAccount, which kicks the
        // player and removes the characters with all their data, account_access,
        // account_banned, account_muted, realmcharacters and the account row.
        try {
            $result = ACoreServices::I()->getAccountSoap()->deleteAccount($gameAccount['username']);
        } catch (\Throwable $e) {
            $result = $e->getMessage();
        }

        // AcoreSoap returns SOAP faults as strings. The reply text (LANG_ACCOUNT_DELETED)
        // is only recognisable on English servers, so otherwise check the auth DB.
        $deleted = is_string($result) && stripos($result, 'Account deleted:') !== false;
        if (!$deleted) {
            try {
                $deleted = acore_delete_account_game_account_gone($gameAccount['id']);
            } catch (\Throwable $e) {
                $deleted = false;
            }
        }
        if (!$deleted) {
            $reply = is_string($result) ? trim($result) : '';
            $fail(sprintf(
                __('The game server did not delete your game account (%s). Nothing was deleted, please try again later.', 'acore-wp-plugin'),
                $reply !== '' ? $reply : __('no reply', 'acore-wp-plugin')
            ));
        }
    }

    $userId = $user->ID;

    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'acore_login_history', ['user_id' => $userId], ['%d']);

    // Log out before deleting so the session cleanup does not write meta for a removed user.
    wp_logout();

    // The game account is already gone; skip the hook that would send `account delete` again.
    remove_action('wp_delete_user', __NAMESPACE__ . '\after_delete', 10);
    remove_action('wpmu_delete_user', __NAMESPACE__ . '\after_delete', 10);

    require_once ABSPATH . 'wp-admin/includes/user.php';
    if (is_multisite()) {
        require_once ABSPATH . 'wp-admin/includes/ms.php';
        wpmu_delete_user($userId);
    } else {
        wp_delete_user($userId);
    }

    wp_safe_redirect(add_query_arg('acore_account_deleted', '1', home_url('/')));
    exit;
}

add_action('wp_footer', __NAMESPACE__ . '\acore_account_deleted_notice');

function acore_account_deleted_notice()
{
    if (empty($_GET['acore_account_deleted']) || is_user_logged_in()) {
        return;
    }
?>
    <div id="acore-account-deleted-notice" role="status" style="position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:100000;max-width:90%;background:#1d2327;color:#fff;padding:12px 40px 12px 16px;border-radius:6px;box-shadow:0 4px 24px rgba(0,0,0,0.3);font-size:14px;">
        <?php _e('Your account has been deleted. Goodbye!', 'acore-wp-plugin'); ?>
        <button type="button" aria-label="<?php esc_attr_e('Dismiss', 'acore-wp-plugin'); ?>" onclick="this.parentNode.remove()" style="position:absolute;top:6px;right:8px;background:none;border:0;color:#fff;font-size:18px;cursor:pointer;">&times;</button>
    </div>
<?php
}
