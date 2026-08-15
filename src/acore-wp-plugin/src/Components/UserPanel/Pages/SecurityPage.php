<?php
defined('ABSPATH') || exit;

$user = wp_get_current_user();
$changedLabel = $passwordChangedAt
    ? \ACore\Hooks\User\acore_format_connection_date($passwordChangedAt)
    : __('Never', 'acore-wp-plugin');
$expandOnLoad = !empty($passwordMessage);
$passwordMaxLength = \ACore\Manager\UserValidator::PASSWORD_LENGTH;
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
                                        class="regular-text" autocomplete="new-password"
                                        maxlength="<?= (int) $passwordMaxLength ?>"
                                        aria-describedby="acore-password-length-description" />
                                    <button type="button" class="acore-pw-toggle button" aria-label="<?php _e('Show password', 'acore-wp-plugin'); ?>">
                                        <span class="dashicons dashicons-visibility"></span>
                                    </button>
                                </div>
                                <p class="description" id="acore-password-length-description">
                                    <?php printf(esc_html__('Maximum %d characters.', 'acore-wp-plugin'), (int) $passwordMaxLength); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="acore_confirm_pass"><?php _e('Confirm New Password', 'acore-wp-plugin'); ?></label>
                            </th>
                            <td>
                                <div class="acore-pw-field">
                                    <input type="password" name="acore_confirm_pass" id="acore_confirm_pass"
                                        class="regular-text" autocomplete="new-password"
                                        maxlength="<?= (int) $passwordMaxLength ?>"
                                        aria-describedby="acore-password-length-description" />
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
    $websiteTotpEnabled  = !empty($twoFaData['plugin_active']) && !empty($twoFaData['totp_enabled']);
    $websiteEmailEnabled = !empty($twoFaData['plugin_active']) && !empty($twoFaData['email_enabled']);
    $websiteAnyEnabled   = $websiteTotpEnabled || $websiteEmailEnabled;
    $twofaUnlocked       = $websiteAnyEnabled && (bool) get_transient(\ACore\Components\ServerInfo\acore_2fa_unlock_key($user->ID));
    $restBase         = rest_url(ACORE_SLUG . '/v1/remove-ingame-2fa');
    $verifyBase       = rest_url(ACORE_SLUG . '/v1/verify-website-2fa');
    $emailRequestBase = rest_url(ACORE_SLUG . '/v1/request-email-2fa');
    $emailVerifyBase  = rest_url(ACORE_SLUG . '/v1/verify-email-2fa');
    $statusBase = rest_url(ACORE_SLUG . '/v1/2fa-status');
    $enableBase = rest_url(ACORE_SLUG . '/v1/enable-ingame-2fa');
    $restNonce  = wp_create_nonce('wp_rest');

    // Label of the in-game otpauth:// entry, so the authenticator app shows which
    // server and account the code belongs to. The site name is decoded because the
    // app should show "Ben & Co", not the entity-encoded form WordPress stores.
    $qrIssuer  = trim(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES));
    if ($qrIssuer === '') $qrIssuer = 'AzerothCore';
    $qrAccount = strtoupper($user->user_login);

    // The site hands out the key itself unless the master secret is set to
    // something it cannot use, in which case `.account 2fa setup` is the way in.
    $ingameSetupOnSite = !$ingame2faActive && \ACore\Components\ServerInfo\IngameTotp::isConfigured();
    $ingameKey         = $ingameSetupOnSite ? \ACore\Components\ServerInfo\IngameTotp::pendingSecret($user->ID) : '';
    $ingameOtpauth     = $ingameSetupOnSite ? \ACore\Components\ServerInfo\IngameTotp::otpauthUri($qrAccount, $ingameKey) : '';

    // Removal takes any code the site can actually check - the in-game one when
    // its key is readable, the website one otherwise. A recent panel unlock stands
    // in for either. With none of the three the site cannot tell who is asking, so
    // it does not offer removal at all and the in-game command is the way out.
    $ingameRemoveNeedsCode = false;
    $ingameRemovable       = $twofaUnlocked;
    if ($ingame2faActive && !$twofaUnlocked) {
        $accId = \ACore\Manager\ACoreServices::I()->getAcoreAccountId();
        $ingameRemoveNeedsCode = $websiteTotpEnabled
            || ($accId && \ACore\Components\ServerInfo\IngameTotp::currentSecret($accId) !== '');
        $ingameRemovable = $ingameRemoveNeedsCode;
    }

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

            <p style="margin:0 0 10px;">
                <?php _e('You will need an authenticator app configured with a <strong>Time-based (TOTP)</strong> key.', 'acore-wp-plugin'); ?>
                <?php printf(
                    __('We recommend <a href="%s" target="_blank" rel="noopener noreferrer">FreeOTP</a>, a free and open source authenticator app, but any app of your choice (Google Authenticator, Aegis, ...) works too.', 'acore-wp-plugin'),
                    'https://freeotp.github.io/'
                ); ?>
                <?php _e('Keep in mind there are <strong>two separate setups</strong>: one exclusively for logging into the website, and another for logging into the game server. Each has its own independent code.', 'acore-wp-plugin'); ?>
            </p>

            <p style="margin:0 0 6px; font-size:13px; color:#646970;">
                <?php _e('If your app asks for these settings when you add the key manually, use:', 'acore-wp-plugin'); ?>
            </p>
            <ul style="margin:0 0 16px 18px; font-size:13px; line-height:1.7; list-style:disc;">
                <li><?php _e('Type: <strong>Time-based (TOTP)</strong>', 'acore-wp-plugin'); ?></li>
                <li><?php _e('Digits: <strong>6</strong>', 'acore-wp-plugin'); ?></li>
                <li><?php _e('Algorithm: <strong>SHA1</strong>', 'acore-wp-plugin'); ?></li>
                <li><?php _e('Interval: <strong>30 seconds</strong>', 'acore-wp-plugin'); ?></li>
            </ul>

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

                <?php if ($twofaUnlocked): ?>
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
                <?php elseif ($websiteEmailEnabled): ?>
                    <!-- Email-based 2FA: email a one-time code, then reveal the management UI -->
                    <div id="acore-2fa-gate-email">
                        <p style="margin:0 0 8px; color:#646970; font-size:13px;">
                            <?php _e('For your security, request a code by email and enter it to view or regenerate your backup codes and 2FA settings.', 'acore-wp-plugin'); ?>
                        </p>
                        <p style="margin:0 0 8px;">
                            <button type="button" id="acore-2fa-email-send" class="button">
                                <?php _e('Email me a code', 'acore-wp-plugin'); ?>
                            </button>
                        </p>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <input type="text" id="acore-2fa-email-code" inputmode="numeric" pattern="\d{6}"
                                   maxlength="6" placeholder="000000" autocomplete="one-time-code"
                                   style="width:110px; text-align:center; letter-spacing:0.2em; font-size:18px;">
                            <button type="button" id="acore-2fa-email-verify" class="button button-primary">
                                <?php _e('Unlock', 'acore-wp-plugin'); ?>
                            </button>
                        </div>
                        <div id="acore-2fa-email-msg" style="font-size:13px; margin-top:8px;"></div>
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

            <?php if ($ingameSetupOnSite): ?>
                <!-- In-game 2FA disabled, and the site can write the key itself: hand it out here -->
                <p style="margin:12px 0 10px; color:#646970; font-size:13px;">
                    <?php _e('Scan this QR code with your authenticator app - or add the key by hand - then enter the 6-digit code it gives you.', 'acore-wp-plugin'); ?>
                </p>

                <div class="acore-2fa-setup">
                    <div id="acore-ingame-qr-out" class="acore-qr-out"></div>
                    <div class="acore-2fa-setup-main">
                        <span class="acore-2fa-key-label"><?php _e('Your 2FA key', 'acore-wp-plugin'); ?></span>
                        <div class="acore-2fa-key-row">
                            <code id="acore-ingame-key"><?= esc_html($ingameKey) ?></code>
                            <button type="button" id="acore-ingame-key-copy" class="button"
                                    title="<?php esc_attr_e('Copy the key', 'acore-wp-plugin'); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </div>
                        <p class="acore-qr-msg" style="margin-bottom:12px;">
                            <?php _e('Adding it by hand? Time-based (TOTP), 6 digits, SHA1, 30 seconds.', 'acore-wp-plugin'); ?>
                        </p>
                        <div class="acore-2fa-confirm">
                            <input type="text" id="acore-ingame-enable-code" inputmode="numeric" pattern="\d{6}"
                                   maxlength="6" placeholder="000000" autocomplete="one-time-code">
                            <button type="button" id="acore-ingame-enable" class="button button-primary">
                                <?php _e('Enable In-game 2FA', 'acore-wp-plugin'); ?>
                            </button>
                        </div>
                        <div id="acore-ingame-enable-msg" class="acore-qr-msg"></div>
                    </div>
                </div>

                <p class="acore-qr-note">
                    <span class="dashicons dashicons-info-outline"></span>
                    <span>
                        <?php _e('Your app will show a 6-digit code that refreshes every 30 seconds. If the one you typed is about to expire, wait for a fresh one.', 'acore-wp-plugin'); ?>
                        <?php _e('Prefer to do it in game? Type <code>.account 2fa setup 1</code> and follow what it prints - the game hands out a key of its own, and the one above then no longer applies.', 'acore-wp-plugin'); ?>
                    </span>
                </p>

            <?php elseif (!$ingame2faActive): ?>
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
                        <?php _e('When adding the key in your app, set the key type to <strong>Time based</strong> (TOTP), with <strong>6</strong> digits, <strong>SHA1</strong> algorithm and a <strong>30 seconds</strong> interval.', 'acore-wp-plugin'); ?>
                        <br><?php _e('Or paste the key below to get a QR code with all of those settings already in it, and scan it with your app instead of typing anything:', 'acore-wp-plugin'); ?>
                        <div class="acore-qr">
                            <div class="acore-qr-controls">
                                <input type="text" id="acore-ingame-qr-key" spellcheck="false" autocomplete="off"
                                       autocapitalize="characters" placeholder="K6NXC763GDQTZJG3CTH4WIOGAW6MZYOO">
                                <button type="button" id="acore-ingame-qr-btn" class="button">
                                    <?php _e('Show QR code', 'acore-wp-plugin'); ?>
                                </button>
                            </div>
                            <p class="acore-qr-note">
                                <span class="dashicons dashicons-lock"></span>
                                <?php _e('The key stays in your browser: the QR code is drawn on this page and is never sent anywhere.', 'acore-wp-plugin'); ?>
                            </p>
                            <div id="acore-ingame-qr-out" class="acore-qr-out" style="display:none;"></div>
                            <div id="acore-ingame-qr-msg" class="acore-qr-msg"></div>
                        </div>
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
                    <?php if ($ingameRemoveNeedsCode): ?>
                        <?php _e('To disable in-game 2FA, enter the 6-digit code your authenticator app is showing:', 'acore-wp-plugin'); ?>
                    <?php elseif ($ingameRemovable): ?>
                        <?php _e('You can disable in-game 2FA here, or inside the game with <code>.account 2fa remove &lt;6-digit-code&gt;</code>.', 'acore-wp-plugin'); ?>
                    <?php else: ?>
                        <?php _e('This site cannot check your in-game code, so it cannot turn in-game 2FA off for you. Log into the game and type <code>.account 2fa remove &lt;6-digit-code&gt;</code> with the code your authenticator app is showing.', 'acore-wp-plugin'); ?>
                    <?php endif; ?>
                </p>

                <?php if ($ingameRemovable): ?>
                    <div id="acore-ingame-2fa-wrap">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                            <?php if ($ingameRemoveNeedsCode): ?>
                                <input type="text" id="acore-ingame-2fa-code" inputmode="numeric" pattern="\d{6}"
                                       maxlength="6" placeholder="000000" autocomplete="one-time-code"
                                       style="width:110px; text-align:center; letter-spacing:0.2em; font-size:18px;">
                            <?php endif; ?>
                            <button type="button" id="acore-ingame-2fa-remove" class="button button-primary">
                                <?php _e('Remove In-game 2FA', 'acore-wp-plugin'); ?>
                            </button>
                        </div>
                        <div id="acore-ingame-2fa-msg" style="font-size:13px;"></div>
                    </div>
                <?php endif; ?>
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
            var codeEl = document.getElementById('acore-ingame-2fa-code');
            var code   = codeEl ? codeEl.value.trim() : '';
            var msg    = document.getElementById('acore-ingame-2fa-msg');
            // The field is only rendered when there is a code to check against;
            // if one is typed it must be 6 digits.
            if (code !== '' && !/^\d{6}$/.test(code)) {
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

    /* Base32 length of an in-game key, both the one we hand out and the one the game does */
    var qrKeyLength = 32;

    /* Draws an otpauth:// URI into a container. The generator is loaded in the
       footer, so callers have to wait for DOMContentLoaded before drawing. */
    var qrDraw = function(container, uri){
        if (typeof qrcode === 'undefined') return false;
        var qr = qrcode(0, 'M');
        qr.addData(uri, 'Byte');
        qr.make();
        container.innerHTML = qr.createSvgTag({
            cellSize: 4,
            margin:   4,
            scalable: true,
            alt:      '<?php echo esc_js(__('In-game 2FA QR code', 'acore-wp-plugin')); ?>'
        });
        return true;
    };

    /* In-game 2FA setup, website-issued key: draw its QR code and confirm a first code */
    var enableBtn = document.getElementById('acore-ingame-enable');
    if (enableBtn) {
        var keyEl    = document.getElementById('acore-ingame-key');
        var keyOut   = document.getElementById('acore-ingame-qr-out');
        var enableIn = document.getElementById('acore-ingame-enable-code');
        var enableMsg = document.getElementById('acore-ingame-enable-msg');
        // JSON-encoded, not esc_js(): a site name may contain quotes, and esc_js()
        // would turn them into HTML entities inside the otpauth URI. The HEX flags
        // keep a site title made of comment or tag markup from ending this block early.
        var otpauth  = <?= wp_json_encode($ingameOtpauth, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        document.addEventListener('DOMContentLoaded', function(){
            if (!qrDraw(keyOut, otpauth)) {
                keyOut.style.display = 'none';
                enableMsg.style.color = '#d63638';
                enableMsg.textContent = '<?php echo esc_js(__('The QR code could not be drawn. Add the key to your app by hand with the settings shown here.', 'acore-wp-plugin')); ?>';
            }
        });

        var copyBtn = document.getElementById('acore-ingame-key-copy');
        if (copyBtn) {
            copyBtn.addEventListener('click', function(){
                var done = function(){
                    enableMsg.style.color = '#00a32a';
                    enableMsg.textContent = '<?php echo esc_js(__('Key copied.', 'acore-wp-plugin')); ?>';
                };
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(keyEl.textContent).then(done, function(){});
                    return;
                }
                var range = document.createRange();
                range.selectNodeContents(keyEl);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);
                if (document.execCommand('copy')) done();
            });
        }

        var submitEnable = function(){
            var code = (enableIn.value || '').trim();
            if (!/^\d{6}$/.test(code)) {
                enableMsg.style.color = '#d63638';
                enableMsg.textContent = '<?php echo esc_js(__('Please enter a valid 6-digit code.', 'acore-wp-plugin')); ?>';
                return;
            }
            enableBtn.disabled = true;
            enableBtn.textContent = '<?php echo esc_js(__('Enabling…', 'acore-wp-plugin')); ?>';
            enableMsg.textContent = '';
            fetch('<?= esc_js($enableBase) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce':   '<?= esc_js($restNonce) ?>'
                },
                body: JSON.stringify({ token: code })
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) throw d;
                enableMsg.style.color = '#00a32a';
                enableMsg.textContent = '<?php echo esc_js(__('In-game 2FA is on. The game will ask for a code the next time you log in.', 'acore-wp-plugin')); ?>';
                setTimeout(function(){ window.location.reload(); }, 2200);
            })
            .catch(function(err){
                enableMsg.style.color = '#d63638';
                enableMsg.textContent = (err && (err.message || (err.data && err.data.message))) || '<?php echo esc_js(__('An error occurred. Please try again.', 'acore-wp-plugin')); ?>';
                enableBtn.disabled = false;
                enableBtn.textContent = '<?php echo esc_js(__('Enable In-game 2FA', 'acore-wp-plugin')); ?>';
            });
        };
        enableBtn.addEventListener('click', submitEnable);
        enableIn.addEventListener('keydown', function(e){
            if (e.key === 'Enter') { e.preventDefault(); submitEnable(); }
        });
    }

    /* In-game 2FA setup, key issued by the game: turn what the user pastes into a QR code */
    var qrBtn   = document.getElementById('acore-ingame-qr-btn');
    var qrInput = document.getElementById('acore-ingame-qr-key');
    if (qrBtn && qrInput) {
        var qrOut     = document.getElementById('acore-ingame-qr-out');
        var qrMsg     = document.getElementById('acore-ingame-qr-msg');
        var qrIssuer  = <?= wp_json_encode($qrIssuer, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var qrAccount = <?= wp_json_encode($qrAccount, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        var qrShow    = '<?php echo esc_js(__('Show QR code', 'acore-wp-plugin')); ?>';
        var qrHide    = '<?php echo esc_js(__('Hide QR code', 'acore-wp-plugin')); ?>';
        var qrWanted  = false;

        var qrClear = function(){
            qrWanted = false;
            qrOut.style.display = 'none';
            qrOut.innerHTML     = '';
            qrMsg.textContent   = '';
            qrBtn.textContent   = qrShow;
        };

        var qrFail = function(text){
            qrOut.style.display = 'none';
            qrOut.innerHTML     = '';
            qrBtn.textContent   = qrShow;
            qrMsg.style.color   = '#d63638';
            qrMsg.textContent   = text;
        };

        var qrRender = function(){
            // The in-game key is base32; accept it typed in lowercase or with separators.
            var key = (qrInput.value || '').toUpperCase().replace(/[\s-]/g, '').replace(/=+$/, '');
            if (!/^[A-Z2-7]+$/.test(key)) {
                qrFail('<?php echo esc_js(__('That does not look like a 2FA key. Paste the key the game showed you, made of letters A-Z and digits 2-7.', 'acore-wp-plugin')); ?>');
                return;
            }
            // Exactly 32 characters: a shorter one still draws a perfectly scannable
            // QR code, and the app would then produce codes the server rejects.
            if (key.length !== qrKeyLength) {
                qrFail('<?php echo esc_js(__('A 2FA key is %1$d characters long, this one has %2$d. Check you copied all of it.', 'acore-wp-plugin')); ?>'
                       .replace('%1$d', qrKeyLength).replace('%2$d', key.length));
                return;
            }
            var uri = 'otpauth://totp/' + encodeURIComponent(qrIssuer) + ':' + encodeURIComponent(qrAccount)
                    + '?secret='    + key
                    + '&issuer='    + encodeURIComponent(qrIssuer)
                    + '&algorithm=SHA1&digits=6&period=30';
            if (!qrDraw(qrOut, uri)) {
                qrFail('<?php echo esc_js(__('The QR code generator could not be loaded. Reload the page, or add the key manually with the settings above.', 'acore-wp-plugin')); ?>');
                return;
            }
            qrOut.style.display = '';
            qrBtn.textContent   = qrHide;
            qrMsg.style.color   = '';
            qrMsg.textContent   = '<?php echo esc_js(__('Scan it with your authenticator app, then go on with the next step.', 'acore-wp-plugin')); ?>';
        };

        qrBtn.addEventListener('click', function(){
            if (qrOut.style.display !== 'none') { qrClear(); return; }
            qrWanted = true;
            qrRender();
        });
        qrInput.addEventListener('keydown', function(e){
            if (e.key === 'Enter') { e.preventDefault(); qrWanted = true; qrRender(); }
        });
        qrInput.addEventListener('input', function(){
            if (qrWanted) qrRender();
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

    /* Website 2FA gate (email method): email a code, then unlock the panel */
    var emailSend   = document.getElementById('acore-2fa-email-send');
    var emailVerify = document.getElementById('acore-2fa-email-verify');
    if (emailSend && emailVerify) {
        var emsg = document.getElementById('acore-2fa-email-msg');
        var revealEmailPanel = function(){
            var g = document.getElementById('acore-2fa-gate-email');
            var p = document.getElementById('acore-2fa-panel');
            if (g) g.style.display = 'none';
            if (p) p.style.display = '';
        };
        emailSend.addEventListener('click', function(){
            emailSend.disabled = true;
            emailSend.textContent = '<?php echo esc_js(__('Sending…', 'acore-wp-plugin')); ?>';
            emsg.textContent = '';
            fetch('<?= esc_js($emailRequestBase) ?>', {
                method: 'POST',
                headers: { 'X-WP-Nonce': '<?= esc_js($restNonce) ?>' }
            })
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (d && d.success) {
                    emsg.style.color = '#00a32a';
                    emsg.textContent = '<?php echo esc_js(__('A code has been sent to your email.', 'acore-wp-plugin')); ?>';
                } else { throw d; }
            })
            .catch(function(err){
                emsg.style.color = '#d63638';
                emsg.textContent = (err && (err.message || (err.data && err.data.message))) || '<?php echo esc_js(__('Could not send the code. Please try again.', 'acore-wp-plugin')); ?>';
            })
            .finally(function(){
                emailSend.disabled = false;
                emailSend.textContent = '<?php echo esc_js(__('Email me a code', 'acore-wp-plugin')); ?>';
            });
        });
        var submitEmail = function(){
            var code = (document.getElementById('acore-2fa-email-code').value || '').trim();
            if (!/^\d{6}$/.test(code)) {
                emsg.style.color = '#d63638';
                emsg.textContent = '<?php echo esc_js(__('Please enter a valid 6-digit code.', 'acore-wp-plugin')); ?>';
                return;
            }
            emailVerify.disabled = true;
            emailVerify.textContent = '<?php echo esc_js(__('Verifying…', 'acore-wp-plugin')); ?>';
            fetch('<?= esc_js($emailVerifyBase) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?= esc_js($restNonce) ?>' },
                body: JSON.stringify({ code: code })
            })
            .then(function(r){ return r.json(); })
            .then(function(d){ if (d && d.success) { revealEmailPanel(); } else { throw d; } })
            .catch(function(err){
                emsg.style.color = '#d63638';
                emsg.textContent = (err && (err.message || (err.data && err.data.message))) || '<?php echo esc_js(__('Incorrect code. Please try again.', 'acore-wp-plugin')); ?>';
                emailVerify.disabled = false;
                emailVerify.textContent = '<?php echo esc_js(__('Unlock', 'acore-wp-plugin')); ?>';
            });
        };
        emailVerify.addEventListener('click', submitEmail);
        var emailInput = document.getElementById('acore-2fa-email-code');
        if (emailInput) {
            emailInput.addEventListener('keydown', function(e){
                if (e.key === 'Enter') { e.preventDefault(); submitEmail(); }
            });
        }
    }
})();

/* Real-time 2FA removal detection: reload when state changes */
(function(){
    var statusUrl = '<?= esc_js($statusBase) ?>';
    var nonce     = '<?= esc_js($restNonce) ?>';
    var initial   = {
        website: <?= $websiteAnyEnabled ? 'true' : 'false' ?>,
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
