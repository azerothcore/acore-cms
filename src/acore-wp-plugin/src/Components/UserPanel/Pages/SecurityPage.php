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

    <div class="postbox" style="max-width:680px;margin-top:20px;">
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

    <?php if (!empty($twoFaData['plugin_active'])): ?>
    <div class="postbox" style="max-width:680px;margin-top:20px;">
        <div class="postbox-header">
            <h2 class="hndle"><span><?php _e('Two-Factor Authentication', 'acore-wp-plugin'); ?></span></h2>
        </div>
        <div class="inside">
            <?php
            $primaryMethods = (array) ($twoFaData['primary_methods'] ?? []);
            $backupMethods  = (array) ($twoFaData['backup_methods']  ?? []);
            $hasPrimary     = !empty(array_filter($primaryMethods));
            $hasBackup      = !empty(array_filter($backupMethods));
            ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><?php _e('Primary method', 'acore-wp-plugin'); ?></th>
                    <td>
                        <?php if ($hasPrimary): ?>
                            <span style="color:#00a32a;">&#10003; <?= esc_html(implode(', ', array_filter($primaryMethods))) ?></span>
                        <?php else: ?>
                            <span style="color:#d63638;"><?php _e('No enabled primary method', 'acore-wp-plugin'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Backup method(s)', 'acore-wp-plugin'); ?></th>
                    <td>
                        <?php if ($hasBackup): ?>
                            <span style="color:#00a32a;">&#10003; <?= esc_html(implode(', ', array_filter($backupMethods))) ?></span>
                        <?php else: ?>
                            <span style="color:#d63638;"><?php _e('No enabled backup methods', 'acore-wp-plugin'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <p style="margin-top:4px;">
                <a href="<?= esc_url($twoFaData['setup_url']) ?>" class="button button-primary">
                    <?php _e('Configure 2FA', 'acore-wp-plugin'); ?>
                </a>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <div class="postbox" style="margin-top:20px;">
        <div class="postbox-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h2 class="hndle"><span><?php _e('Recent Connections', 'acore-wp-plugin'); ?></span></h2>
            <?php if (count($connections) > 50): ?>
                <button type="button" id="acore-expand-connections" class="button" style="margin-right:12px;">
                    <?php _e('Show All', 'acore-wp-plugin'); ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="inside" style="padding:0;">
            <?php if (empty($connections)): ?>
                <p style="padding:12px 16px;"><?php _e('No connections recorded yet.', 'acore-wp-plugin'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped" id="acore-connections-table">
                    <thead>
                        <tr>
                            <th><?php _e('IPv4 Address', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Country', 'acore-wp-plugin'); ?></th>
                            <th><?php _e('Date', 'acore-wp-plugin'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($connections as $i => $row): ?>
                            <tr <?= $i >= 50 ? 'class="acore-conn-hidden" style="display:none;"' : '' ?>>
                                <td><?= esc_html($row->ip_address) ?></td>
                                <td><?= esc_html($row->country) ?></td>
                                <td><?= esc_html(\ACore\Hooks\User\acore_format_connection_date($row->login_at)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.acore-pw-field {
    display: flex;
    align-items: center;
    gap: 6px;
}
.acore-pw-toggle {
    padding: 0 6px !important;
    height: 30px;
    line-height: 28px;
}
.acore-pw-toggle .dashicons {
    margin-top: 6px;
}
</style>

<script>
(function() {
    document.querySelectorAll('.acore-pw-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = btn.previousElementSibling;
            var icon  = btn.querySelector('.dashicons');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('dashicons-visibility', 'dashicons-hidden');
                btn.setAttribute('aria-label', '<?php _e('Hide password', 'acore-wp-plugin'); ?>');
            } else {
                input.type = 'password';
                icon.classList.replace('dashicons-hidden', 'dashicons-visibility');
                btn.setAttribute('aria-label', '<?php _e('Show password', 'acore-wp-plugin'); ?>');
            }
        });
    });

    var btn     = document.getElementById('acore-set-password-btn');
    var cancel  = document.getElementById('acore-cancel-password-btn');
    var wrap    = document.getElementById('acore-password-form-wrap');

    if (btn && wrap) {
        btn.addEventListener('click', function() {
            wrap.style.display = '';
            btn.style.display  = 'none';
            document.getElementById('acore_old_pass').focus();
        });
    }

    if (cancel && wrap) {
        cancel.addEventListener('click', function() {
            wrap.style.display = 'none';
            btn.style.display  = '';
        });
    }

    <?php if ($expandOnLoad): ?>
    if (btn) btn.style.display = 'none';
    <?php endif; ?>

    var newPass     = document.getElementById('acore_new_pass');
    var confirmPass = document.getElementById('acore_confirm_pass');
    var saveBtn     = document.querySelector('#acore-password-form .button-primary');
    var matchHint   = document.createElement('p');
    matchHint.className = 'description';
    matchHint.style.marginTop = '4px';
    if (confirmPass) confirmPass.closest('td').appendChild(matchHint);

    function checkMatch() {
        if (!confirmPass.value) {
            matchHint.textContent = '';
            saveBtn.disabled = false;
            return;
        }
        if (newPass.value === confirmPass.value) {
            matchHint.style.color = '#00a32a';
            matchHint.textContent = '<?php _e('Passwords match.', 'acore-wp-plugin'); ?>';
            saveBtn.disabled = false;
        } else {
            matchHint.style.color = '#d63638';
            matchHint.textContent = '<?php _e('Passwords do not match.', 'acore-wp-plugin'); ?>';
            saveBtn.disabled = true;
        }
    }

    if (newPass)     newPass.addEventListener('input', checkMatch);
    if (confirmPass) confirmPass.addEventListener('input', checkMatch);

    var expandBtn = document.getElementById('acore-expand-connections');
    if (expandBtn) {
        expandBtn.addEventListener('click', function() {
            document.querySelectorAll('#acore-connections-table .acore-conn-hidden').forEach(function(row) {
                row.style.display = '';
                row.classList.remove('acore-conn-hidden');
            });
            expandBtn.style.display = 'none';
        });
    }
})();
</script>
