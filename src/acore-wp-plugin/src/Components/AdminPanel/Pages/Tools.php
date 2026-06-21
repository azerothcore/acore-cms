<?php
    use ACore\Manager\Opts;
    $modulesCsv         = get_option('acore_modules_csv', '');
    $installedModules   = $modulesCsv ? array_map('trim', explode(',', $modulesCsv)) : [];
    $hasResurrectionMod = in_array('mod-resurrection-scroll', $installedModules);

    // If module is missing, treat as disabled for UI purposes
    $scrollEnabled = $hasResurrectionMod && Opts::I()->acore_resurrection_scroll == '1';
?>

<style>
    /* .acore-btn-danger styling lives in theme.css (shared light/dark) */
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

    /* Confirm modal (defaults to "No") */
    .acore-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 100000;
    }
    .acore-modal-box {
        background: #fff;
        color: #1d2327;
        max-width: 420px;
        width: calc(100% - 40px);
        padding: 20px;
        border-radius: 6px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
    }
    body.acore-dark-mode .acore-modal-box {
        background: #1c2128;
        color: #c9d1d9;
    }
    .acore-modal-text {
        font-size: 13px;
        line-height: 1.5;
        white-space: pre-line;
        margin: 0 0 16px;
    }
    .acore-modal-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
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

                                <!-- Remove 2FA -->
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

                                <!-- Backup codes (always visible; greyed until a Website check finds codes) -->
                                <div id="acore-backup-wrap" style="margin:0 0 12px; opacity:0.45;">
                                    <p style="font-size:12px; font-weight:600; margin:0 0 4px;">Backup Codes</p>
                                    <span id="acore-backup-info" style="font-size:12px;">Check a Website account above to view backup codes.</span>
                                    <button type="button" id="acore-backup-remove" class="button acore-btn-danger" style="white-space:nowrap; margin-left:6px;" disabled>Remove backup codes</button>
                                </div>

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

                    <!-- Col 3: Name Unlock Settings -->
                    <div class="col-sm-4">
                        <div class="card p-0">
                            <div class="card-body">
                                <h5>Name Unlock Settings</h5>
                                <hr>

                                <span>Allowed banned names table (characters database):</span>
                                <input type="text" name="acore_name_unlock_allowed_banned_names_table"
                                    value="<?= Opts::I()->acore_name_unlock_allowed_banned_names_table ?>">
                                <br><br>

                                <span>Inactivity Thresholds per Level:</span>
                                <table id="acore-name-unlock-thresholds" class="form-table table table-borderless" role="presentation">
                                    <thead>
                                        <tr>
                                            <th>Max Level (&lt;)</th>
                                            <th>Minimum Days of Inactivity</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                                <div style="display:flex; gap:6px; margin-top:4px;">
                                    <div id="acore-name-unlock-thresholds-add" class="button">
                                        <span class="dashicons dashicons-plus" style="margin-top:5px;"></span> Add
                                    </div>
                                    <div id="acore-name-unlock-reset" class="button acore-btn-danger" title="Reset Name Unlock to defaults">
                                        <span class="dashicons dashicons-image-rotate" style="margin-top:5px;"></span> Reset
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- /col3 -->

                </div><!-- /row -->

                <!-- User Login History (admin lookup) -->
                <div class="card p-0" style="margin-top:16px;">
                    <div class="card-body">
                        <h5>User Login History</h5>
                        <hr>
                        <p style="font-size:12px; color:#646970; margin:0 0 8px;">
                            Look up the recorded login IP history for any account (the same list the user sees on their Security page).
                        </p>
                        <div style="display:flex; gap:6px; align-items:center; margin-bottom:10px; flex-wrap:wrap;">
                            <input type="text" id="acore-history-user" placeholder="Account name" style="flex:0 1 220px;">
                            <button type="button" id="acore-history-lookup" class="button button-secondary">Look up</button>
                        </div>
                        <p id="acore-history-msg" style="font-size:12px; margin:0 0 8px; min-height:18px;"></p>
                        <table id="acore-history-table" class="wp-list-table widefat fixed striped" style="display:none; max-width:760px;">
                            <thead>
                                <tr><th>IPv4 Address</th><th>Country</th><th>Date / Time</th><th>Where</th></tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                        <p style="margin-top:8px;">
                            <button type="button" id="acore-history-more" class="button" style="display:none;">See more</button>
                        </p>
                    </div>
                </div>

                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>">
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Reusable confirm modal (default button is "No") -->
<div id="acore-confirm-modal" class="acore-modal-overlay" style="display:none;">
    <div class="acore-modal-box">
        <p id="acore-confirm-modal-text" class="acore-modal-text"></p>
        <div class="acore-modal-actions">
            <button type="button" id="acore-confirm-yes" class="button acore-btn-danger">Yes</button>
            <button type="button" id="acore-confirm-no" class="button button-secondary">No</button>
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

    function wire2fa(type, $userInput, $check, $remove, $msg, onCheck) {
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
                            var lr  = data.last_removal;
                            var who = (lr.by === 'self') ? 'the user themselves' : (lr.staff || 'an administrator');
                            txt += ' Last removed on ' + lr.date + ' by ' + who;
                            if (lr.by === 'self' && lr.ip) { txt += ' (IP ' + lr.ip + ')'; }
                            txt += '.';
                        }
                        $msg.css('color','#d63638').text(txt);
                        $remove.prop('disabled', true);
                    } else {
                        $msg.css('color','#238636').text('2FA is active for ' + data.username + '.');
                        $remove.prop('disabled', false);
                    }
                    if (typeof onCheck === 'function') onCheck(data, username);
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

    wire2fa('website', $('#acore-2fa-web-user'),  $('#acore-2fa-web-check'),  $('#acore-2fa-web-remove'),  $('#acore-2fa-web-msg'), function(data){
        var count = parseInt(data.backup_codes, 10) || 0;
        if (count > 0) {
            $('#acore-backup-wrap').css('opacity', '1');
            $('#acore-backup-info').css('color','#646970').text(count + ' unused backup code' + (count === 1 ? '' : 's') + ' remaining.');
            $('#acore-backup-remove').prop('disabled', false);
        } else {
            $('#acore-backup-wrap').css('opacity', '0.45');
            $('#acore-backup-info').css('color','#646970').text('No backup codes generated for this account.');
            $('#acore-backup-remove').prop('disabled', true);
        }
    });
    wire2fa('ingame',  $('#acore-2fa-game-user'), $('#acore-2fa-game-check'), $('#acore-2fa-game-remove'), $('#acore-2fa-game-msg'));

    /* Remove backup codes (uses the Website account-name input) */
    $('#acore-backup-remove').on('click', function(){
        var username = $('#acore-2fa-web-user').val().trim();
        if (!username) { return; }
        var $btn = $(this);
        acoreConfirm('Remove all backup codes for ' + username + '? They will need to generate new ones.', function(){
            $btn.prop('disabled', true).text('Removing…');
            ajaxPost('admin/backup-codes-remove', { username: username })
                .done(function(data){
                    $('#acore-backup-info').css('color','#238636').text('Backup codes removed on ' + data.date + '. The user has been notified.');
                })
                .fail(function(xhr){
                    var err = xhr.responseJSON ? (xhr.responseJSON.message || 'Error.') : 'Error.';
                    $('#acore-backup-info').css('color','#d63638').text(err);
                    $btn.prop('disabled', false);
                })
                .always(function(){ $btn.text('Remove backup codes'); });
        });
    });

    /* User Login History lookup */
    var acoreHistory = { username: '', mock: null, page: 0, total: 0, shown: 0 };

    function acoreHistoryFetch(page) {
        var $tbl = $('#acore-history-table'), $tb = $tbl.find('tbody'),
            $msg = $('#acore-history-msg'), $more = $('#acore-history-more');
        return ajaxPost('admin/login-history', {
                username: acoreHistory.username,
                mock:     acoreHistory.mock,
                page:     page
            })
            .done(function(data){
                var rows = data.history || [];
                if (page === 1) { $tb.empty(); acoreHistory.shown = 0; acoreHistory.total = data.total || 0; }
                if (page === 1 && !rows.length) {
                    $tbl.hide(); $more.hide();
                    $msg.css('color','#646970').text('No login history recorded for ' + data.username + '.');
                    return;
                }
                rows.forEach(function(r){
                    $('<tr>').append(
                        $('<td>').text(r.ip),
                        $('<td>').text(r.country),
                        $('<td>').text(r.date),
                        $('<td>').text(r.where)
                    ).appendTo($tb);
                });
                acoreHistory.shown += rows.length;
                acoreHistory.page   = data.page || page;
                acoreHistory.total  = data.total || acoreHistory.total;
                $tbl.show();
                $msg.css('color','#646970').text('Showing ' + acoreHistory.shown + ' of ' + acoreHistory.total + ' for ' + data.username + '.');
                $more.toggle(!!data.has_more);
            })
            .fail(function(xhr){
                if (page === 1) { $tbl.hide(); $more.hide(); }
                var err = xhr.responseJSON ? (xhr.responseJSON.message || 'Error.') : 'Error.';
                $msg.css('color','#d63638').text(err);
            });
    }

    $('#acore-history-lookup').on('click', function(){
        var username = $('#acore-history-user').val().trim();
        if (!username) { $('#acore-history-msg').css('color','#d63638').text('Enter an account name first.'); return; }
        acoreHistory.username = username;
        acoreHistory.mock     = new URLSearchParams(location.search).get('mock_connections');
        $('#acore-history-msg').css('color','#646970').text('');
        var $btn = $(this).prop('disabled', true).text('Looking up…');
        acoreHistoryFetch(1).always(function(){ $btn.prop('disabled', false).text('Look up'); });
    });

    $('#acore-history-more').on('click', function(){
        var $b = $(this).prop('disabled', true).text('Loading…');
        acoreHistoryFetch(acoreHistory.page + 1).always(function(){ $b.prop('disabled', false).text('See more'); });
    });

    /* ── Name Unlock Thresholds ───────────────────────────────────────── */
    const deleteThreshold = (ev) => {
        const $btn = $(ev.target).closest('.acore-btn-danger');
        const $tr  = $btn.closest('tr');
        acoreConfirm('Remove this inactivity threshold row?', function () {
            $tr.remove();
            let i = 0;
            $('#acore-name-unlock-thresholds tbody tr').each(function () {
                const previ = $(this).data('i');
                $(this).data('i', i);
                $(this).find(`input[name="acore_name_unlock_thresholds[${previ}][0]"]`).attr('name', `acore_name_unlock_thresholds[${i}][0]`);
                $(this).find(`input[name="acore_name_unlock_thresholds[${previ}][1]"]`).attr('name', `acore_name_unlock_thresholds[${i}][1]`);
                i++;
            });
        });
    };

    const addThreshold = (i = undefined, level = '', days = '') => {
        if (i === undefined) {
            const $trs = $('#acore-name-unlock-thresholds tbody tr');
            i = $trs.length ? $($trs[$trs.length - 1]).data('i') + 1 : 0;
        }
        const $tr = $('<tr>').appendTo('#acore-name-unlock-thresholds tbody');
        $tr.data('i', i);
        let $td = $('<td>').appendTo($tr);
        $(`<input type="number" name="acore_name_unlock_thresholds[${i}][0]" min="1" max="256" value="${level}">`).appendTo($td);
        $td = $('<td>').appendTo($tr);
        $(`<input type="number" name="acore_name_unlock_thresholds[${i}][1]" min="1" value="${days}">`).appendTo($td);
        $td = $('<td>').appendTo($tr);
        const $btnDel = $(`<div class="button acore-btn-danger">`).appendTo($td);
        $btnDel.append(`<span class="dashicons dashicons-trash"></span>`);
        $btnDel.on('click', deleteThreshold);
    };

    $('#acore-name-unlock-thresholds-add').on('click', () => addThreshold());

    /* ── Confirm modal (Yes / No; doing nothing = no action) ─────────── */
    function acoreConfirm(message, onConfirm) {
        const $overlay = $('#acore-confirm-modal');
        $('#acore-confirm-modal-text').text(message);
        $overlay.css('display', 'flex');
        // Nothing is auto-focused: if the user does nothing (Escape / click
        // outside), nothing happens - they must explicitly click Yes or No.

        const close = () => {
            $overlay.hide();
            $('#acore-confirm-yes').off('click.acoreConfirm');
            $('#acore-confirm-no').off('click.acoreConfirm');
            $overlay.off('click.acoreConfirm');
            $(document).off('keydown.acoreConfirm');
        };

        $('#acore-confirm-yes').on('click.acoreConfirm', function () {
            close();
            onConfirm();
        });
        $('#acore-confirm-no').on('click.acoreConfirm', close);
        // Click outside the box = No.
        $overlay.on('click.acoreConfirm', function (e) {
            if (e.target === this) close();
        });
        // Escape = No.
        $(document).on('keydown.acoreConfirm', function (e) {
            if (e.key === 'Escape') close();
        });
    }

    /* ── Reset Name Unlock to Defaults ──────────────────────────────── */
    $('#acore-name-unlock-reset').on('click', function () {
        acoreConfirm(
            'Reset Name Unlock settings to defaults?\n\n' +
            'This will clear the banned names table and delete all inactivity thresholds.\n\n' +
            'This cannot be undone. Continue?',
            function () {
                $('input[name="acore_name_unlock_allowed_banned_names_table"]').val('');
                $('#acore-name-unlock-thresholds tbody tr').remove();
                $('input[name="Submit"]').closest('form').submit();
            }
        );
    });

    <?php foreach (Opts::I()->acore_name_unlock_thresholds as $i => $threshold) {
        if ($threshold[0] != '' && $threshold[1] != '') {
            echo "addThreshold($i, $threshold[0], $threshold[1]);";
        }
    } ?>

})(jQuery);
</script>
