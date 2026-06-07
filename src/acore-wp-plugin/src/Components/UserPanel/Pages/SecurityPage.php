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
    $restBase  = rest_url(ACORE_SLUG . '/v1/remove-ingame-2fa');
    $restNonce = wp_create_nonce('wp_rest');

    // Admin-removal log: find last entry per type
    $adminLog         = get_user_meta($user->ID, 'acore_2fa_admin_log', true);
    $adminLog         = is_array($adminLog) ? $adminLog : [];
    $lastWebRemoval   = null;
    $lastGameRemoval  = null;
    foreach ($adminLog as $entry) {
        if ($entry['type'] === 'website') $lastWebRemoval  = $entry;
        if ($entry['type'] === 'ingame')  $lastGameRemoval = $entry;
    }
    // Only warn if 2FA is not currently active (user hasn't re-enabled yet)
    $showWebWarning  = $lastWebRemoval  && !$websiteTotpEnabled;
    $showGameWarning = $lastGameRemoval && !$ingame2faActive;
    ?>

    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><span><?php _e('Two-Factor Authentication', 'acore-wp-plugin'); ?></span></h2>
        </div>
        <div class="inside">

            <?php if ($showWebWarning || $showGameWarning): ?>
                <div class="notice notice-warning inline" style="margin:0 0 18px; padding:10px 14px;">
                    <p style="margin:0 0 4px; font-weight:600;">
                        <span class="dashicons dashicons-warning" style="color:#dba617; margin-right:4px; vertical-align:middle;"></span>
                        <?php _e('Your 2FA was manually removed by a staff member.', 'acore-wp-plugin'); ?>
                    </p>
                    <?php if ($showWebWarning): ?>
                        <p style="margin:4px 0 0; font-size:13px;">
                            - <?php
                            printf(
                                __('Website 2FA removed on %1$s by %2$s. Please re-enable it for account security.', 'acore-wp-plugin'),
                                '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastWebRemoval['timestamp'])) . '</strong>',
                                '<strong>' . esc_html($lastWebRemoval['staff']) . '</strong>'
                            ); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($showGameWarning): ?>
                        <p style="margin:4px 0 0; font-size:13px;">
                            - <?php
                            printf(
                                __('In-game 2FA removed on %1$s by %2$s. Please re-enable it for account security.', 'acore-wp-plugin'),
                                '<strong>' . esc_html(wp_date('jS \o\f F, Y \a\t H:i', $lastGameRemoval['timestamp'])) . '</strong>',
                                '<strong>' . esc_html($lastGameRemoval['staff']) . '</strong>'
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
                echo $wp2faHtml;
                ?>
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
                <div style="position:relative; opacity:0.5; pointer-events:none; user-select:none;">
            <?php endif; ?>

            <?php if (!$ingame2faActive): ?>
                <!-- In-game 2FA disabled -  show setup instructions -->
                <p style="margin:12px 0 8px; color:#646970; font-size:13px;">
                    <?php _e('To enable in-game 2FA, follow these steps inside the game:', 'acore-wp-plugin'); ?>
                </p>
                <ol style="margin:0 0 0 18px; font-size:13px; line-height:1.8;">
                    <li>
                        <?php _e('Type the following command to begin the setup:', 'acore-wp-plugin'); ?>
                        <?php _e('Type the following command to begin the setup:', 'acore-wp-plugin'); ?>
                        <code style="margin-left:4px;">.account 2fa setup 1</code>
                    </li>
                    <li>
                        <?php _e('Your authenticator app will show a QR code or secret key — open your app and scan or enter it.', 'acore-wp-plugin'); ?>
                    </li>
                    <li>
                        <?php _e('Once the key is saved in the app, type the 6-digit code shown to confirm:', 'acore-wp-plugin'); ?>
                        <code style="margin-left:4px;">.account 2fa setup &lt;6-digit-code&gt;</code>
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
                <p style="font-size:12px; color:#8b949e; margin:6px 0 0;">
                    <?php _e('Set up Website 2FA first to manage In-game 2FA from here.', 'acore-wp-plugin'); ?>
                </p>
            <?php endif; ?>

        </div><!-- /postbox inside -->
    </div><!-- /postbox 2FA -->

    <!-- ── Recent Connections ──────────────────────────────────────────── -->
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="hndle"><span><?php _e('Recent Connections', 'acore-wp-plugin'); ?></span></h2>
        </div>
        <div class="inside">

            <?php if (empty($connections)): ?>
                <p style="color:#646970;"><?php _e('No connections recorded yet.', 'acore-wp-plugin'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" style="max-width:860px;">
                    <thead>
                        <tr>
                            <th><?php _e('IPv4 Address', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Date / Time', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Action', 'acore-wp-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $row): ?>
                            <tr>
                                <td><?= esc_html($row['ip']) ?></td>
                                <td><?= esc_html(\ACore\Hooks\User\acore_format_connection_date($row['timestamp'])) ?></td>
                                <td><?= esc_html($row['type'] ?? 'login') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                msg.textContent = '<?php _e('Please enter a valid 6-digit code.', 'acore-wp-plugin'); ?>';
                return;
            }
            removeBtn.disabled = true;
            removeBtn.textContent = '<?php _e('Removing…', 'acore-wp-plugin'); ?>';
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
                    msg.textContent   = '<?php _e('In-game 2FA removed successfully. You can set it up again inside the game.', 'acore-wp-plugin'); ?>';
                    // Refresh the page after short delay so the status badge updates
                    setTimeout(function(){ window.location.reload(); }, 2200);
                } else {
                    throw data;
                }
            })
            .catch(function(err){
                msg.style.color   = '#d63638';
                msg.textContent   = (err && (err.message || (err.data && err.data.message))) || '<?php _e('An error occurred. Please try again.', 'acore-wp-plugin'); ?>';
                removeBtn.disabled = false;
                removeBtn.textContent = '<?php _e('Remove In-game 2FA', 'acore-wp-plugin'); ?>';
            });
        });
    }
})();
</script>
