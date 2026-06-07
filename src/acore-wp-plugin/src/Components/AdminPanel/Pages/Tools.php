<?php
    use ACore\Manager\Opts;
    $modulesCsv         = get_option('acore_modules_csv', '');
    $installedModules   = $modulesCsv ? array_map('trim', explode(',', $modulesCsv)) : [];
    $hasResurrectionMod = in_array('mod-resurrection-scroll', $installedModules);

    // If module is missing, treat as disabled for UI purposes
    $scrollEnabled = $hasResurrectionMod && Opts::I()->acore_resurrection_scroll == '1';
?>

<style>
    .acore-btn-danger {
        border-color: #d63638 !important;
        color: #d63638 !important;
        background: #f7f6f6 !important;
    }
    .acore-btn-danger .dashicons {
        margin-top: 3px;
    }
    body.acore-dark-mode .acore-btn-danger {
        background: #1c2128 !important;
    }
    .acore-days-inactive-disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }
    .acore-days-inactive-disabled input {
        pointer-events: none;
    }

    /* Missing module: greyed select + hover tooltip */
    .acore-missing-module-wrap {
        position: relative;
        display: inline-block;
    }
    .acore-missing-module-wrap select[disabled] {
        opacity: 0.45;
        cursor: not-allowed;
        pointer-events: none;
    }
    .acore-missing-module-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 0;
        background: #1c2128;
        color: #c9d1d9;
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 4px;
        white-space: nowrap;
        z-index: 100;
        box-shadow: 0 2px 8px rgba(0,0,0,0.25);
    }
    .acore-missing-module-wrap:hover .acore-missing-module-tooltip { display: block; }
</style>

<div class="wrap">
    <h1><?= __('AzerothCore', Opts::I()->page_alias)?></h1>
    <div class="card">
        <div class="card-body">
            <h2>Tools</h2>
            <hr>
            <form method="post">
                <div class="row">

                    <!-- Col 1: World Server Integration -->
                    <div class="col-sm-4">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>World Server Integration</h5>
                                <hr>
                                <table class="form-table table table-borderless" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th><label for="acore_item_restoration">Item Restoration Service</label></th>
                                            <td>
                                                <select name="acore_item_restoration" id="acore_item_restoration">
                                                    <option value="0">Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_item_restoration == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>
                                                <?php if (!$hasResurrectionMod): ?>
                                                    <span class="acore-missing-module-wrap">
                                                        <label for="acore_resurrection_scroll" style="color:#d63638;">Scroll of Resurrection</label>
                                                        <span class="acore-missing-module-tooltip">Requires module mod-resurrection-scroll</span>
                                                    </span>
                                                <?php else: ?>
                                                    <label for="acore_resurrection_scroll">Scroll of Resurrection</label>
                                                <?php endif; ?>
                                            </th>
                                            <td>
                                                <?php if (!$hasResurrectionMod): ?>
                                                    <span class="acore-missing-module-wrap">
                                                        <select name="acore_resurrection_scroll" id="acore_resurrection_scroll" disabled>
                                                            <option value="0">Disabled</option>
                                                        </select>
                                                        <span class="acore-missing-module-tooltip">Requires module mod-resurrection-scroll</span>
                                                    </span>
                                                <?php else: ?>
                                                    <select name="acore_resurrection_scroll" id="acore_resurrection_scroll">
                                                        <option value="0">Disabled</option>
                                                        <option value="1" <?php if ($scrollEnabled) echo 'selected'; ?>>Enabled</option>
                                                    </select>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label for="acore_resurrection_scroll_days_inactive">Days Inactive</label></th>
                                            <td>
                                                <span id="acore-days-inactive-wrap"
                                                      class="<?= !$scrollEnabled ? 'acore-days-inactive-disabled' : '' ?>"
                                                      title="<?= !$scrollEnabled ? 'Scroll of Resurrection must be enabled' : '' ?>">
                                                    <input type="number"
                                                           name="acore_resurrection_scroll_days_inactive"
                                                           id="acore_resurrection_scroll_days_inactive"
                                                           min="1"
                                                           value="<?= esc_attr(Opts::I()->acore_resurrection_scroll_days_inactive) ?>"
                                                           <?= !$scrollEnabled ? 'disabled' : '' ?>>
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Col 2: Web Integration -->
                    <div class="col-sm-4">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>Web Integration</h5>
                                <hr>
                                <table class="form-table table table-borderless" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th><label for="acore_security_logging">Security Logging</label></th>
                                            <td>
                                                <select name="acore_security_logging" id="acore_security_logging">
                                                    <option value="0" <?php if (Opts::I()->acore_security_logging != '1') echo 'selected'; ?>>Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_security_logging == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th><label for="acore_allow_old_passwords">Allow Old Passwords</label></th>
                                            <td>
                                                <select name="acore_allow_old_passwords" id="acore_allow_old_passwords">
                                                    <option value="0" <?php if (Opts::I()->acore_allow_old_passwords != '1') echo 'selected'; ?>>Disabled</option>
                                                    <option value="1" <?php if (Opts::I()->acore_allow_old_passwords == '1') echo 'selected'; ?>>Enabled</option>
                                                </select>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>

                                <hr style="margin:12px 0;">

                                <!-- Remove 2FA - action tool, buttons are type="button" so form submit is unaffected -->
                                <p style="font-weight:600; margin:0 0 4px; font-size:13px;">Remove 2FA</p>
                                <p style="font-size:12px; color:#646970; margin:0 0 12px;">
                                    Remove Website or In-game 2FA for any account. A warning is shown to the user until they re-enable it.
                                </p>

                                <!-- Website 2FA -->
                                <p style="font-size:12px; font-weight:600; margin:0 0 4px;">Website</p>
                                <div style="display:flex; gap:6px; align-items:center; margin-bottom:6px; flex-wrap:wrap;">
                                    <input type="text" id="acore-2fa-web-user" placeholder="Account name" style="flex:1 1 120px; min-width:80px;">
                                    <button type="button" id="acore-2fa-web-check" class="button button-secondary" style="white-space:nowrap;">Check</button>
                                    <button type="button" id="acore-2fa-web-remove" class="button acore-btn-danger" style="white-space:nowrap;" disabled>Remove</button>
                                </div>
                                <p id="acore-2fa-web-msg" style="font-size:12px; margin:0 0 12px; min-height:18px;"></p>

                                <!-- In-game 2FA -->
                                <p style="font-size:12px; font-weight:600; margin:0 0 4px;">In-Game</p>
                                <div style="display:flex; gap:6px; align-items:center; margin-bottom:6px; flex-wrap:wrap;">
                                    <input type="text" id="acore-2fa-game-user" placeholder="Account name" style="flex:1 1 120px; min-width:80px;">
                                    <button type="button" id="acore-2fa-game-check" class="button button-secondary" style="white-space:nowrap;">Check</button>
                                    <button type="button" id="acore-2fa-game-remove" class="button acore-btn-danger" style="white-space:nowrap;" disabled>Remove</button>
                                </div>
                                <p id="acore-2fa-game-msg" style="font-size:12px; margin:0 0 4px; min-height:18px;"></p>

                            </div><!-- /card-body Web Integration -->
                        </div><!-- /card Web Integration -->
                    </div><!-- /col2 -->

                </div><!-- /row -->

                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>">
                </p>
            </form>
        </div>
    </div>
</div>

<script>
(function($){
    var restBase = '<?= esc_js(rest_url(ACORE_SLUG . '/v1/')) ?>';
    var nonce    = '<?= esc_js(wp_create_nonce('wp_rest')) ?>';

    /* Re-enable the Days Inactive input when scroll is toggled on */
    $('#acore_resurrection_scroll').on('change', function(){
        var on = $(this).val() === '1';
        var $wrap = $('#acore-days-inactive-wrap');
        $wrap.toggleClass('acore-days-inactive-disabled', !on)
             .attr('title', on ? '' : 'Scroll of Resurrection must be enabled');
        $('#acore_resurrection_scroll_days_inactive').prop('disabled', !on);
    });

    /* ── 2FA helpers ──────────────────────────────────────────────────── */
    function ajaxPost(endpoint, body) {
        return $.ajax({
            url: restBase + endpoint,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(body),
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', nonce); }
        });
    }

    function wire2fa(type, $userInput, $check, $remove, $msg) {
        $check.on('click', function(){
            var username = $userInput.val().trim();
            if (!username) { $msg.css('color','#d63638').text('Enter an account name first.'); return; }
            $check.prop('disabled', true).text('Checking…');
            $remove.prop('disabled', true);
            ajaxPost('admin/2fa-check', { type: type, username: username })
                .done(function(data){
                    if (!data.active) {
                        var txt = '2FA is not active for ' + data.username + '.';
                        if (data.last_removal) {
                            txt += ' Last removed on ' + data.last_removal.date + ' by ' + data.last_removal.staff + '.';
                        }
                        $msg.css('color','#d63638').text(txt);
                        $remove.prop('disabled', true);
                    } else {
                        $msg.css('color','#238636').text('2FA is active for ' + data.username + '.');
                        $remove.prop('disabled', false);
                    }
                })
                .fail(function(xhr){
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || JSON.stringify(xhr.responseJSON)) : 'Error.';
                    $msg.css('color','#d63638').text(err);
                    $remove.prop('disabled', true);
                })
                .always(function(){
                    $check.prop('disabled', false).text('Check');
                });
        });

        $remove.on('click', function(){
            var username = $userInput.val().trim();
            if (!confirm('Remove ' + type + ' 2FA for ' + username + '? This cannot be undone.')) return;
            $remove.prop('disabled', true).text('Removing…');
            $check.prop('disabled', true);
            ajaxPost('admin/2fa-remove', { type: type, username: username })
                .done(function(data){
                    $msg.css('color','#238636')
                        .text('Removed on ' + data.date + ' by ' + data.staff + '. User will see a warning until they re-enable 2FA.');
                    $remove.prop('disabled', true);
                })
                .fail(function(xhr){
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || JSON.stringify(xhr.responseJSON)) : 'Error.';
                    $msg.css('color','#d63638').text(err);
                    $remove.prop('disabled', false);
                })
                .always(function(){
                    $check.prop('disabled', false).text('Check');
                    if ($remove.text() === 'Removing…') $remove.text('Remove');
                });
        });
    }

    wire2fa('website', $('#acore-2fa-web-user'),  $('#acore-2fa-web-check'),  $('#acore-2fa-web-remove'),  $('#acore-2fa-web-msg'));
    wire2fa('ingame',  $('#acore-2fa-game-user'), $('#acore-2fa-game-check'), $('#acore-2fa-game-remove'), $('#acore-2fa-game-msg'));

})(jQuery);
</script>
