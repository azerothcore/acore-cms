<?php
defined('ABSPATH') || exit;

$user = wp_get_current_user();
$changedLabel = $passwordChangedAt
    ? \ACore\Hooks\User\acore_format_connection_date($passwordChangedAt)
    : __('Never', 'acore-wp-plugin');
$expandOnLoad = !empty($passwordMessage);
?>

<div class="wrap" id="acore-security-page">
    <h1><?php _e('Security', 'acore-wp-plugin'); ?></h1>

    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><span><?php _e('Password', 'acore-wp-plugin'); ?></span></h2>
        </div>
        <div class="inside">

            <?php if (!empty($passwordMessage)): ?>
                <div class="notice notice-<?= esc_attr($passwordMessage['type']) ?> inline" style="margin:0 0 16px;">
                    <p><?= esc_html($passwordMessage['text']) ?></p>
                </div>
            <?php endif; ?>

            <p style="margin:0 0 14px;">
                <span style="color:#646970;"><?php _e('Password last updated:', 'acore-wp-plugin'); ?></span>
                <strong style="margin-left:6px;"><?= esc_html($changedLabel) ?></strong>
            </p>

            <button type="button" id="acore-set-password-btn" class="button">
                <?php _e('Set New Password', 'acore-wp-plugin'); ?>
            </button>

            <div id="acore-password-form-wrap" style="<?= $expandOnLoad ? '' : 'display:none;' ?>margin-top:20px;">
                <form method="post" id="acore-password-form">
                    <?php wp_nonce_field('acore_security_change_password', 'acore_pw_nonce'); ?>
                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row">
                                <label for="acore_old_pass"><?php _e('Current Password', 'acore-wp-plugin'); ?></label>
                            </th>
                            <td>
                                <div class="acore-pw-field">
                                    <input type="password" name="acore_old_pass" id="acore_old_pass"
                                        class="regular-text" autocomplete="current-password" />
                                    <button type="button" class="acore-pw-toggle button" aria-label="<?php _e('Show password', 'acore-wp-plugin'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="acore_new_pass"><?php _e('New Password', 'acore-wp-plugin'); ?></label>
                            </th>
                            <td>
                                <div class="acore-pw-field">
                                    <input type="password" name="acore_new_pass" id="acore_new_pass"
                                        class="regular-text" autocomplete="new-password" />
                                    <button type="button" class="acore-pw-toggle button" aria-label="<?php _e('Show password', 'acore-wp-plugin'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="acore_confirm_pass"><?php _e('Confirm New Password', 'acore-wp-plugin'); ?></label>
                            </th>
                            <td>
                                <div class="acore-pw-field">
                                    <input type="password" name="acore_confirm_pass" id="acore_confirm_pass"
                                        class="regular-text" autocomplete="new-password" />
                                    <button type="button" class="acore-pw-toggle button" aria-label="<?php _e('Show password', 'acore-wp-plugin'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <p class="submit" style="margin-top:4px;">
                        <button type="submit" name="acore_change_password" class="button button-primary">
                            <?php _e('Save Password', 'acore-wp-plugin'); ?>
                        </button>
                        <button type="button" id="acore-cancel-password-btn" class="button" style="margin-left:6px;">
                            <?php _e('Cancel', 'acore-wp-plugin'); ?>
                        </button>
                    </p>
                </form>
            </div>

        </div>
    </div>

    <?php
    $websiteTotpEnabled = !empty($twoFaData['plugin_active']) && !empty($twoFaData['totp_enabled']);
    $websiteAnyEnabled  = !empty($twoFaData['plugin_active']) && (!empty($twoFaData['totp_enabled']) || !empty($twoFaData['email_enabled']));
    $twofaUnlocked      = $websiteTotpEnabled && (bool) get_transient(\ACore\Components\ServerInfo\acore_2fa_unlock_key($user->ID));
    $restBase   = rest_url(ACORE_SLUG . '/v1/remove-ingame-2fa');
    $verifyBase = rest_url(ACORE_SLUG . '/v1/verify-website-2fa');
    $statusBase = rest_url(ACORE_SLUG . '/v1/2fa-status');
    $restNonce  = wp_create_nonce('wp_rest');

    // Admin-removal log: find last entry per type
    $adminLog         = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
    $adminLog         = is_array($adminLog) ? $adminLog : [];
    $lastWebRemoval    = null;
    $lastGameRemoval   = null;
    $lastBackupRemoval = null;
    foreach ($adminLog as $entry) {
        if ($entry['type'] === 'website') $lastWebRemoval    = $entry;
        if ($entry['type'] === 'ingame')  $lastGameRemoval   = $entry;
        if ($entry['type'] === 'backup')  $lastBackupRemoval = $entry;
    }
    $backupCodesMeta = get_user_meta($user->ID, 'wp_2fa_backup_codes', true);
    $backupCodesLeft = is_array($backupCodesMeta) ? count($backupCodesMeta) : 0;
    // Only warn if 2FA is not currently active (user hasn't re-enabled yet)
    $showWebWarning    = $lastWebRemoval    && !$websiteAnyEnabled;
    $showGameWarning   = $lastGameRemoval   && !$ingame2faActive;
    $showBackupWarning = $lastBackupRemoval && $backupCodesLeft === 0;
    ?>

    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><span><?php _e('Two-Factor Authentication', 'acore-wp-plugin'); ?></span></h2>
        </div>
        <div class="inside">

            <?php if ($showWebWarning || $showGameWarning || $showBackupWarning): ?>
                <div class="notice notice-warning inline" style="margin:0 0 18px; padding:10px 14px;">
                    <p style="margin:0 0 4px; font-weight:600;">
                        <span class="dashicons dashicons-warning" style="color:#dba617; margin-right:4px; vertical-align:middle;"></span>
                        <?php _e('Your two-factor authentication was removed.', 'acore-wp-plugin'); ?>
                    </p>
                    <?php if ($showWebWarning): ?>
                        <p style="margin:4px 0 0; font-size:13px;">
                            - <?php
                            $webDate = '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastWebRemoval['timestamp'])) . '</strong>';
                            if (($lastWebRemoval['by'] ?? 'admin') === 'self') {
                                printf(
                                    __('Website 2FA was manually removed by you on %1$s (last IP: %2$s). Please re-enable it for account security.', 'acore-wp-plugin'),
                                    $webDate,
                                    '<strong>' . esc_html($lastWebRemoval['ip'] ?? __('unknown', 'acore-wp-plugin')) . '</strong>'
                                );
                            } else {
                                printf(
                                    __('Website 2FA was manually removed by an administrator on %1$s. Please re-enable it for account security.', 'acore-wp-plugin'),
                                    $webDate
                                );
                            }
                            ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($showGameWarning): ?>
                        <p style="margin:4px 0 0; font-size:13px;">
                            - <?php
                            printf(
                                __('In-game 2FA was manually removed by an administrator on %1$s. Please re-enable it for account security.', 'acore-wp-plugin'),
                                '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastGameRemoval['timestamp'])) . '</strong>'
                            ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($showBackupWarning): ?>
                        <p style="margin:4px 0 0; font-size:13px;">
                            - <?php
                            printf(
                                __('Your two-factor backup codes were removed by an administrator on %1$s. Please generate new backup codes.', 'acore-wp-plugin'),
                                '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastBackupRemoval['timestamp'])) . '</strong>'
                            ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <p style="margin:0 0 16px;">
                <?php _e('You will need an authenticator app (such as Google Authenticator or any app of your choice) configured with a <strong>Time-based (TOTP)</strong> key.', 'acore-wp-plugin'); ?>
                <?php _e('Keep in mind there are <strong>two separate setups</strong>: one exclusively for logging into the website, and another for logging into the game server. Each has its own independent code.', 'acore-wp-plugin'); ?>
            </p>

            <hr style="margin:0 0 20px;">

            <!-- ── Website 2FA ─────────────────────────────────────── -->
            <h3 style="margin:0 0 12px;"><?php _e('Website', 'acore-wp-plugin'); ?></h3>

            <?php if (!empty($twoFaData['plugin_active'])): ?>
                <?php
                global $wp_filter;
                ob_start();
                if (!empty($wp_filter['show_user_profile'])) {
                    foreach ($wp_filter['show_user_profile']->callbacks as $callbacks) {
                        foreach ($callbacks as $cb) {
                            $func = $cb['function'];
                            $id   = '';
                            if (is_array($func) && is_object($func[0]))      $id = get_class($func[0]);
                            elseif (is_array($func) && is_string($func[0]))  $id = $func[0];
                            elseif (is_string($func))                         $id = $func;
                            if ($id && (
                                stripos($id, 'WP2FA')      !== false ||
                                stripos($id, 'wp_2fa')     !== false ||
                                stripos($id, 'Two_Factor') !== false
                            )) {
                                call_user_func($func, $user);
                            }
                        }
                    }
                }
                $wp2faHtml = ob_get_clean();
                // Strip the WP2FA section heading + subtitle (redundant with our own "Website" label)
                $wp2faHtml = preg_replace(
                    '/<h[1-4][^>]*>\s*Two-factor authentication settings\s*<\/h[1-4]>\s*(<p[^>]*>[^<]*<\/p>)?/i',
                    '',
                    $wp2faHtml
                );
                ?>

                <?php if ($websiteTotpEnabled && $twofaUnlocked): ?>
                    <!-- Already unlocked recently: show the management UI directly (no re-prompt on refresh) -->
                    <div id="acore-2fa-panel" style="margin-top:16px;">
                        <?= $wp2faHtml ?>
                    </div>
                <?php elseif ($websiteTotpEnabled): ?>
                    <!-- 2FA is enabled: require a current TOTP code before revealing the management UI (incl. backup codes) -->
                    <div id="acore-2fa-gate">
                        <p style="margin:0 0 8px; color:#646970; font-size:13px;">
                            <?php _e('For your security, enter your current website 2FA code to view or regenerate your backup codes and 2FA settings.', 'acore-wp-plugin'); ?>
                        </p>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <input type="text" id="acore-2fa-gate-code" inputmode="numeric" pattern="\d{6}"
                                   maxlength="6" placeholder="000000" autocomplete="one-time-code"
                                   style="width:110px; text-align:center; letter-spacing:0.2em; font-size:18px;">
                            <button type="button" id="acore-2fa-gate-btn" class="button button-primary">
                                <?php _e('Unlock', 'acore-wp-plugin'); ?>
                            </button>
                        </div>
                        <div id="acore-2fa-gate-msg" style="font-size:13px; margin-top:8px;"></div>
                    </div>
                    <div id="acore-2fa-panel" style="display:none; margin-top:16px;">
                        <?= $wp2faHtml ?>
                    </div>
                <?php else: ?>
                    <!-- 2FA not set up yet: show the plugin UI directly so the user can configure it -->
                    <?= $wp2faHtml ?>
                <?php endif; ?>

            <?php else: ?>
                <p style="color:#646970;"><?php _e('Two-Factor Authentication plugin is not active.', 'acore-wp-plugin'); ?></p>
            <?php endif; ?>

            <hr style="margin:20px 0;">

            <!-- ── In-game 2FA ─────────────────────────────────────── -->
            <h3 style="margin:0 0 4px;">
                <?php _e('In-game', 'acore-wp-plugin'); ?>
                <?php if ($ingame2faActive): ?>
                    <span style="display:inline-block; font-size:11px; font-weight:700; background:#00a32a; color:#fff; padding:2px 8px; border-radius:3px; vertical-align:middle; text-transform:uppercase; letter-spacing:0.05em;">
                        <?php _e('Enabled', 'acore-wp-plugin'); ?>
                    </span>
                <?php else: ?>
                    <span style="display:inline-block; font-size:11px; font-weight:700; background:#8b949e; color:#fff; padding:2px 8px; border-radius:3px; vertical-align:middle; text-transform:uppercase; letter-spacing:0.05em;">
                        <?php _e('Disabled', 'acore-wp-plugin'); ?>
                    </span>
                <?php endif; ?>
            </h3>

            <?php if (!$websiteTotpEnabled): ?>
                <!-- Website 2FA not set up -  grey out entire in-game block with notice -->
                <div class="acore-2fa-disabled">
            <?php endif; ?>

            <?php if (!$ingame2faActive): ?>
                <!-- In-game 2FA disabled -  show setup instructions -->
                <p style="margin:12px 0 8px; color:#646970; font-size:13px;">
                    <?php _e('To enable in-game 2FA, follow these steps inside the game:', 'acore-wp-plugin'); ?>
                </p>
                <ol style="margin:0 0 0 18px; font-size:13px; line-height:1.8;">
                    <li>
                        <?php _e('Log into any character and type', 'acore-wp-plugin'); ?>
                        <code style="margin-left:4px;">.account 2fa setup 1</code>
                    </li>
                    <li>
                        <?php _e('The game will display your <strong>2FA Key</strong>, for example:', 'acore-wp-plugin'); ?>
                        <code style="margin-left:4px;">K6NXC763GDQTZJG3CTH4WIOGAW6MZYOO</code>
                    </li>
                    <li>
                        <?php _e('Type it (or copy &amp; paste) into the authenticator app on your phone.', 'acore-wp-plugin'); ?>
                        <br><em style="opacity:0.85;"><?php _e('Tip: to copy text from the in-game chat you can use an addon such as Prat (3.3.5).', 'acore-wp-plugin'); ?></em>
                    </li>
                    <li>
                        <?php _e('When adding the key in your app, set the key type to <strong>Time based</strong> (TOTP).', 'acore-wp-plugin'); ?>
                    </li>
                    <li>
                        <?php _e('Your app will show a 6-digit code that refreshes every few seconds. Use the code currently shown - if it is about to refresh, wait for a fresh one to avoid errors.', 'acore-wp-plugin'); ?>
                    </li>
                    <li>
                        <?php _e('Back in game, type', 'acore-wp-plugin'); ?>
                        <code style="margin-left:4px;">.account 2fa setup &lt;6-digit-code&gt;</code>
                        <?php _e('- replace &lt;6-digit-code&gt; with your actual 6-digit code, <strong>without</strong> the &lt; &gt; brackets.', 'acore-wp-plugin'); ?>
                    </li>
                    <li>
                        <?php _e('You are all set. Close the game client and open it again - it will now ask for your 6-digit code at login.', 'acore-wp-plugin'); ?>
                    </li>
                </ol>
            <?php else: ?>
                <!-- In-game 2FA enabled - show remove form -->
                <p style="margin:12px 0 8px; color:#646970; font-size:13px;">
                    <?php _e('To disable in-game 2FA, enter your current website 2FA code below:', 'acore-wp-plugin'); ?>
                </p>

                <div id="acore-ingame-2fa-wrap">
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                        <input type="text" id="acore-ingame-2fa-code" inputmode="numeric" pattern="\d{6}"
                               maxlength="6" placeholder="000000"
                               style="width:110px; text-align:center; letter-spacing:0.2em; font-size:18px;">
                        <button type="button" id="acore-ingame-2fa-remove" class="button button-primary">
                            <?php _e('Remove In-game 2FA', 'acore-wp-plugin'); ?>
                        </button>
                    </div>
                    <div id="acore-ingame-2fa-msg" style="font-size:13px;"></div>
                </div>
            <?php endif; ?>

            <?php if (!$websiteTotpEnabled): ?>
                </div><!-- /greyed-out wrapper -->
                <p class="acore-2fa-required-note">
                    <span class="dashicons dashicons-lock"></span>
                    <?php _e('Website 2FA must be set up before you can manage In-game 2FA here.', 'acore-wp-plugin'); ?>
                </p>
            <?php endif; ?>

        </div><!-- /postbox inside -->
    </div><!-- /postbox 2FA -->

    <!-- ── Recent Connections ──────────────────────────────────────────── -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle acore-conn-heading"><span><?php _e('Recent Connections', 'acore-wp-plugin'); ?></span><span class="acore-conn-myip"><?php _e('Your IPv4:', 'acore-wp-plugin'); ?> <?= esc_html(\ACore\Hooks\User\acore_resolve_client_ip()) ?></span></h2>
        </div>
        <div class="inside">

            <?php
            $myIp     = \ACore\Hooks\User\acore_resolve_client_ip();
            $perPage  = 50;
            $total    = is_array($connections) ? count($connections) : 0;
            $maxPage  = max(1, (int) ceil($total / $perPage));
            $connPage = max(1, min($maxPage, (int) ($_GET['conn_page'] ?? 1)));
            $offset   = ($connPage - 1) * $perPage;
            $pageRows = array_slice((array) $connections, $offset, $perPage);
            $from     = $total ? $offset + 1 : 0;
            $to       = $offset + count($pageRows);
            ?>

            <?php if (empty($connections)): ?>
                <p class="acore-conn-note"><?php _e('No connections recorded yet.', 'acore-wp-plugin'); ?></p>
            <?php else: ?>
                <p class="acore-conn-note" style="margin:0 0 8px;">
                    <?php _e('Showing', 'acore-wp-plugin'); ?> <span id="acore-conn-from"><?= (int) $from ?></span>-<span id="acore-conn-to"><?= (int) $to ?></span> <?php _e('of', 'acore-wp-plugin'); ?> <span id="acore-conn-total"><?= (int) $total ?></span> <?php _e('entries.', 'acore-wp-plugin'); ?>
                    <?php if ($total > $perPage): ?>
                        <?php _e('This only shows 50 at once; you can see more by pressing the button below.', 'acore-wp-plugin'); ?>
                    <?php endif; ?>
                </p>
                <table class="wp-list-table widefat fixed striped acore-conn-table" style="max-width:860px;">
                    <thead>
                        <tr>
                            <th><?php _e('IP Address', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Country', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Date / Time', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Where', 'acore-wp-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="acore-conn-tbody">
                        <?php foreach ($pageRows as $row): ?>
                            <?php
                                $ip      = $row['ip_address'] ?? ($row['ip'] ?? '');
                                $country = $row['country'] ?? '';
                                $when    = $row['login_at'] ?? ($row['timestamp'] ?? '');
                                $src     = (($row['source'] ?? 'website') === 'ingame')
                                            ? __('In-game', 'acore-wp-plugin')
                                            : __('Website', 'acore-wp-plugin');
                                $isCurrent = ($ip !== '' && $ip === $myIp);
                            ?>
                            <tr<?= $isCurrent ? ' class="acore-conn-current" title="' . esc_attr__('This matches your current IP', 'acore-wp-plugin') . '"' : '' ?>>
                                <td><?= esc_html($ip) ?></td>
                                <td><?= esc_html($country !== '' ? $country : 'Unknown') ?></td>
                                <td><?= esc_html($when !== '' ? \ACore\Hooks\User\acore_format_connection_date($when) : '') ?></td>
                                <td><?= esc_html($src) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($connPage < $maxPage): ?>
                    <p style="margin-top:10px;">
                        <button type="button" id="acore-conn-more" class="button" data-page="<?= (int) $connPage ?>"><?php _e('See more', 'acore-wp-plugin'); ?> &darr;</button>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

        </div>
    </div><!-- /postbox connections -->

</div><!-- /wrap -->

<script>
(function(){
    /* Password form toggle */
    var btn    = document.getElementById('acore-set-password-btn');
    var wrap   = document.getElementById('acore-password-form-wrap');
    var cancel = document.getElementById('acore-cancel-password-btn');
    if (btn)    btn.addEventListener('click',    function(){ wrap.style.display = ''; });
    if (cancel) cancel.addEventListener('click', function(){ wrap.style.display = 'none'; });

    /* Show/hide password toggles */
    document.querySelectorAll('.acore-pw-toggle').forEach(function(toggle){
        toggle.addEventListener('click', function(){
            var input = this.closest('.acore-pw-field').querySelector('input');
            var icon  = this.querySelector('.dashicons');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('dashicons-visibility', 'dashicons-hidden');
            } else {
                input.type = 'password';
                icon.classList.replace('dashicons-hidden', 'dashicons-visibility');
            }
        });
    });

    /* In-game 2FA removal */
    var removeBtn = document.getElementById('acore-ingame-2fa-remove');
    if (removeBtn) {
        removeBtn.addEventListener('click', function(){
            var code = document.getElementById('acore-ingame-2fa-code').value.trim();
            var msg  = document.getElementById('acore-ingame-2fa-msg');
            if (!/^\d{6}$/.test(code)) {
                msg.style.color = '#d63638';
                msg.textContent = '<?php echo esc_js(__('Please enter a valid 6-digit code.', 'acore-wp-plugin')); ?>';
                return;
            }
            removeBtn.disabled = true;
            removeBtn.textContent = '<?php echo esc_js(__('Removing…', 'acore-wp-plugin')); ?>';
            msg.textContent = '';
            fetch('<?= esc_js($restBase) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':   '<?= esc_js($restNonce) ?>'
                },
                body: JSON.stringify({ token: code })
            })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data.success) {
                    msg.style.color   = '#00a32a';
                    msg.textContent   = '<?php echo esc_js(__('In-game 2FA removed successfully. You can set it up again inside the game.', 'acore-wp-plugin')); ?>';
                    // Refresh the page after short delay so the status badge updates
                    setTimeout(function(){ window.location.reload(); }, 2200);
                } else {
                    throw data;
                }
            })
            .catch(function(err){
                msg.style.color   = '#d63638';
                msg.textContent   = (err && (err.message || (err.data && err.data.message))) || '<?php echo esc_js(__('An error occurred. Please try again.', 'acore-wp-plugin')); ?>';
                removeBtn.disabled = false;
                removeBtn.textContent = '<?php echo esc_js(__('Remove In-game 2FA', 'acore-wp-plugin')); ?>';
            });
        });
    }

    /* Website 2FA gate: require a valid current TOTP code to reveal the panel */
    var gateBtn = document.getElementById('acore-2fa-gate-btn');
    if (gateBtn) {
        var revealPanel = function(){
            var gate  = document.getElementById('acore-2fa-gate');
            var panel = document.getElementById('acore-2fa-panel');
            if (gate)  gate.style.display  = 'none';
            if (panel) panel.style.display = '';
        };
        var submitGate = function(){
            var input = document.getElementById('acore-2fa-gate-code');
            var msg   = document.getElementById('acore-2fa-gate-msg');
            var code  = (input.value || '').trim();
            if (!/^\d{6}$/.test(code)) {
                msg.style.color = '#d63638';
                msg.textContent = '<?php echo esc_js(__('Please enter a valid 6-digit code.', 'acore-wp-plugin')); ?>';
                return;
            }
            gateBtn.disabled = true;
            gateBtn.textContent = '<?php echo esc_js(__('Verifying…', 'acore-wp-plugin')); ?>';
            msg.textContent = '';
            fetch('<?= esc_js($verifyBase) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':   '<?= esc_js($restNonce) ?>'
                },
                body: JSON.stringify({ token: code })
            })
            .then(function(r){ return r.json(); })
            .then(function(data){
                if (data && data.success) {
                    revealPanel();
                } else {
                    throw data;
                }
            })
            .catch(function(err){
                msg.style.color = '#d63638';
                msg.textContent = (err && (err.message || (err.data && err.data.message))) || '<?php echo esc_js(__('Incorrect code. Please try again.', 'acore-wp-plugin')); ?>';
                gateBtn.disabled = false;
                gateBtn.textContent = '<?php echo esc_js(__('Unlock', 'acore-wp-plugin')); ?>';
            });
        };
        gateBtn.addEventListener('click', submitGate);
        var gateInput = document.getElementById('acore-2fa-gate-code');
        if (gateInput) {
            gateInput.addEventListener('keydown', function(e){
                if (e.key === 'Enter') { e.preventDefault(); submitGate(); }
            });
        }
    }
})();

/* Real-time 2FA removal detection: reload when state changes */
(function(){
    var statusUrl = '<?= esc_js($statusBase) ?>';
    var nonce     = '<?= esc_js($restNonce) ?>';
    var initial   = {
        website: <?= $websiteTotpEnabled ? 'true' : 'false' ?>,
        ingame:  <?= $ingame2faActive ? 'true' : 'false' ?>,
        count:   <?= (int) count($adminLog) ?>
    };
    function check(){
        fetch(statusUrl, { headers: { 'X-WP-Nonce': nonce }, credentials: 'same-origin' })
            .then(function(r){ return r.ok ? r.json() : null; })
            .then(function(d){
                if (!d) return;
                if (d.website_enabled !== initial.website ||
                    d.ingame_enabled  !== initial.ingame  ||
                    d.removal_count   !== initial.count) {
                    window.location.reload();
                }
            })
            .catch(function(){});
    }
    setInterval(check, 20000);
})();

/* Recent Connections: load the next 50 in place (no page reload) */
(function(){
    var btn = document.getElementById('acore-conn-more');
    if (!btn) return;
    var tbody = document.getElementById('acore-conn-tbody');
    var toEl  = document.getElementById('acore-conn-to');
    var base  = '<?= esc_js(rest_url(ACORE_SLUG . '/v1/connections')) ?>';
    var nonce = '<?= esc_js(wp_create_nonce('wp_rest')) ?>';
    btn.addEventListener('click', function(){
        var next  = parseInt(btn.getAttribute('data-page'), 10) + 1;
        var url   = base + '?page=' + next;
        var label = btn.textContent;
        btn.disabled = true; btn.textContent = 'Loading...';
        fetch(url, { headers: { 'X-WP-Nonce': nonce } })
            .then(function(r){ return r.json(); })
            .then(function(d){
                (d.rows || []).forEach(function(row){
                    var tr = document.createElement('tr');
                    if (row.current) { tr.className = 'acore-conn-current'; tr.title = 'This matches your current IP'; }
                    ['ip','country','date','where'].forEach(function(k){
                        var td = document.createElement('td');
                        td.textContent = row[k] || '';
                        tr.appendChild(td);
                    });
                    tbody.appendChild(tr);
                });
                if (toEl && typeof d.to !== 'undefined') toEl.textContent = d.to;
                btn.setAttribute('data-page', d.page);
                if (d.has_more) { btn.disabled = false; btn.textContent = label; }
                else if (btn.parentNode) { btn.parentNode.removeChild(btn); }
            })
            .catch(function(){ btn.disabled = false; btn.textContent = label; });
    });
})();
</script>
