<?php
    use ACore\Manager\Opts;

    $modulesCsv        = get_option('acore_modules_csv', '');
    $modulesRefreshed  = intval(get_option('acore_modules_refreshed', 0));
    $storedModules     = $modulesCsv ? array_values(array_filter(explode(',', $modulesCsv))) : [];
    $storedRequirements = get_option('acore_module_requirements', [['Scroll of Resurrection', 'mod-resurrection-scroll']]);
?>

<div class="wrap">
    <h2><?= __('AzerothCore Settings', Opts::I()->page_alias) ?></h2>
    <p>Configure realm name and database connection.</p>

    <div style="display:flex; gap:20px; align-items:flex-start;">

        <!-- Col 1: settings form (35%) -->
        <div style="flex:0 0 auto; width:35%;">
            <form name="form-acore-settings" method="post" action="">
                <div class="card p-0">
                    <div class="card-body">
                        <h5>General Settings</h5>
                        <hr>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="acore_realm_alias">Realm Name:</label></th>
                                    <td><input type="text" name="acore_realm_alias" value="<?= Opts::I()->acore_realm_alias; ?>" size="20" placeholder="AzerothCore"></td>
                                </tr>
                            </tbody>
                        </table>
                        <p><a href="https://www.azerothcore.org/wiki/remote-access">First time using SOAP? Click me!</a></p>
                        <hr />
                        <h5 style="display:flex; align-items:stretch; justify-content:space-between; margin:0; line-height:1;">
                            <span style="line-height:1;">SOAP Settings</span>
                            <?php if (Opts::I()->acore_soap_host && Opts::I()->acore_soap_user): ?>
                            <span id="acore-soap-status" style="display:inline-flex; align-items:center; justify-content:center; align-self:stretch; font-size:9px; font-weight:700; color:#fff; background:#8b949e; padding:0 7px; border-radius:3px; min-width:52px; line-height:1; text-transform:uppercase; letter-spacing:0.06em;">Checking…</span>
                            <?php endif; ?>
                        </h5>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="acore_soap_host">IPv4:</label></th>
                                    <td><input type="text" name="acore_soap_host" value="<?= Opts::I()->acore_soap_host; ?>" size="20" placeholder="127.0.0.1"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_soap_port">Port:</label></th>
                                    <td><input type="text" name="acore_soap_port" value="<?= Opts::I()->acore_soap_port; ?>" size="20" placeholder="7878"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_soap_user">Username:</label></th>
                                    <td><input type="text" name="acore_soap_user" value="<?= Opts::I()->acore_soap_user; ?>" size="20"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_soap_pass">Password:</label></th>
                                    <td><input type="password" name="acore_soap_pass" value="<?= Opts::I()->acore_soap_pass; ?>" size="20"></td>
                                </tr>
                            </tbody>
                        </table>
                        <hr />
                        <h5>Database: Auth</h5>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="acore_db_auth_host">IPv4:</label></th>
                                    <td><input type="text" name="acore_db_auth_host" value="<?= Opts::I()->acore_db_auth_host; ?>" size="20" placeholder="127.0.0.1"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_auth_port">Port:</label></th>
                                    <td><input type="text" name="acore_db_auth_port" value="<?= Opts::I()->acore_db_auth_port; ?>" size="20" placeholder="3306"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_auth_user">Username:</label></th>
                                    <td><input type="text" name="acore_db_auth_user" value="<?= Opts::I()->acore_db_auth_user; ?>" size="20" placeholder="acore"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_auth_pass">Password:</label></th>
                                    <td><input type="password" name="acore_db_auth_pass" value="<?= Opts::I()->acore_db_auth_pass; ?>" size="20" placeholder="acore"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_auth_name">Database Name:</label></th>
                                    <td><input type="text" name="acore_db_auth_name" value="<?= Opts::I()->acore_db_auth_name; ?>" size="20" placeholder="acore_auth"></td>
                                </tr>
                            </tbody>
                        </table>
                        <hr />
                        <h5>Database: Characters</h5>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="acore_db_char_host">IPv4:</label></th>
                                    <td><input type="text" name="acore_db_char_host" value="<?= Opts::I()->acore_db_char_host; ?>" size="20" placeholder="127.0.0.1"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_char_port">Port:</label></th>
                                    <td><input type="text" name="acore_db_char_port" value="<?= Opts::I()->acore_db_char_port; ?>" size="20" placeholder="3306"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_char_user">Username:</label></th>
                                    <td><input type="text" name="acore_db_char_user" value="<?= Opts::I()->acore_db_char_user; ?>" size="20" placeholder="acore"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_char_pass">Password:</label></th>
                                    <td><input type="password" name="acore_db_char_pass" value="<?= Opts::I()->acore_db_char_pass; ?>" size="20" placeholder="acore"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_char_name">Database Name:</label></th>
                                    <td><input type="text" name="acore_db_char_name" value="<?= Opts::I()->acore_db_char_name; ?>" size="20" placeholder="acore_characters"></td>
                                </tr>
                            </tbody>
                        </table>
                        <hr />
                        <h5>Database: World</h5>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label for="acore_db_world_host">IPv4:</label></th>
                                    <td><input type="text" name="acore_db_world_host" value="<?= Opts::I()->acore_db_world_host; ?>" size="20" placeholder="127.0.0.1"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_world_port">Port:</label></th>
                                    <td><input type="text" name="acore_db_world_port" value="<?= Opts::I()->acore_db_world_port; ?>" size="20" placeholder="3306"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_world_user">Username:</label></th>
                                    <td><input type="text" name="acore_db_world_user" value="<?= Opts::I()->acore_db_world_user; ?>" size="20" placeholder="acore"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_world_pass">Password:</label></th>
                                    <td><input type="password" name="acore_db_world_pass" value="<?= Opts::I()->acore_db_world_pass; ?>" size="20" placeholder="acore"></td>
                                </tr>
                                <tr>
                                    <th><label for="acore_db_world_name">Database Name:</label></th>
                                    <td><input type="text" name="acore_db_world_name" value="<?= Opts::I()->acore_db_world_name; ?>" size="20" placeholder="acore_world"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="ajax-message"></div>

                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>" />
                    <input type="button" name="check-soap" id="check-soap" class="button-secondary" value="<?php esc_attr_e('Check SOAP', Opts::I()->page_alias) ?>" />
                </p>
                <h6>You will need to "Save Changes" above before checking your SOAP Configuration!</h6>
            </form>
        </div>

        <!-- Col 2: Modules list - compact, 1 column, shown when SOAP alive -->
        <div style="flex:0 0 auto; display:none;" id="acore-modules-panel">
            <div class="card">
                <div class="card-body" style="min-width:180px;">
                    <h5 style="margin-bottom:10px;">Modules</h5>

                    <!-- 1-column tag list -->
                    <div id="acore-modules-list" style="display:flex; flex-direction:column; gap:4px; min-height:32px;">
                        <?php foreach ($storedModules as $mod): ?>
                            <span class="acore-module-tag"><?= esc_html($mod) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($storedModules)): ?>
                            <span class="acore-modules-empty">No modules loaded.<br>Click Refresh.</span>
                        <?php endif; ?>
                    </div>

                    <!-- Refresh + last refreshed below list -->
                    <div style="margin-top:12px; display:flex; flex-direction:column; gap:4px;">
                        <button type="button" id="acore-modules-refresh" class="button button-secondary">Refresh</button>
                        <span id="acore-modules-refreshed" style="font-size:11px; color:#8b949e;">
                            <?php if ($modulesRefreshed): ?>
                                Last refreshed:<br><?= wp_date('jS \o\f F, Y \a\t H:i', $modulesRefreshed) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div><!-- /col2 modules -->

        <!-- Col 3: Module Requirements validator - compact, hidden until SOAP alive -->
        <div style="flex:0 0 auto; display:none;" id="acore-validate-panel">
            <div class="card">
                <div class="card-body" style="min-width:280px;">
                    <h5 style="margin-bottom:4px;">Module Requirements</h5>
                    <p style="font-size:12px; color:#646970; margin:0 0 10px;">
                        Map a WooCommerce product name to the module it requires. The system
                        checks if that module is active before allowing the purchase.
                    </p>

                    <table id="acore-req-table" style="width:100%; border-collapse:collapse; margin-bottom:8px;">
                        <thead>
                            <tr>
                                <th style="text-align:left; font-size:12px; padding:0 4px 4px 0;">Product Name</th>
                                <th style="text-align:left; font-size:12px; padding:0 0 4px 4px;">Module slug</th>
                                <th style="width:24px;"></th>
                            </tr>
                        </thead>
                        <tbody id="acore-req-tbody">
                            <?php foreach ($storedRequirements as $req): ?>
                            <tr>
                                <td style="padding:2px 4px 2px 0;"><input type="text" class="req-product regular-text" style="width:100%;" value="<?= esc_attr($req[0]) ?>"></td>
                                <td style="padding:2px 0 2px 4px;"><input type="text" class="req-module regular-text" style="width:100%;" value="<?= esc_attr($req[1]) ?>"></td>
                                <td><button type="button" class="button button-link-delete req-remove" style="color:#a00;" title="Remove">&times;</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px;">
                        <button type="button" id="acore-req-add" class="button button-secondary" style="font-size:12px; padding:2px 8px;">+ Add Row</button>
                        <button type="button" id="acore-req-save" class="button button-primary" style="font-size:12px; padding:2px 8px;">Save</button>
                    </div>
                    <span id="acore-req-msg" style="font-size:12px;"></span>
                </div>
            </div>
        </div><!-- /col3 validate -->

    </div><!-- /flex row -->
</div><!-- /wrap -->

<script>
(function($){
    var restBase  = '<?= esc_js(rest_url(ACORE_SLUG . '/v1/')) ?>';
    var nonce     = '<?= esc_js(wp_create_nonce('wp_rest')) ?>';
    var $soapBtn  = $('#check-soap');
    var $soapStat = $('#acore-soap-status');
    var $modPanel = $('#acore-modules-panel');
    var $valPanel = $('#acore-validate-panel');

    /* ── SOAP check on page load ──────────────────────────────────────── */
    function checkSoap(manual) {
        if (manual) $soapBtn.prop('disabled', true).val('Checking…');
        $.get(restBase + 'server-info', function(data) {
            setSoapStatus(true);
        }).fail(function() {
            setSoapStatus(false);
        }).always(function() {
            if (manual) $soapBtn.prop('disabled', false).val('Check SOAP');
        });
    }

    function setSoapStatus(ok) {
        if ($soapStat.length) {
            $soapStat.text(ok ? 'Connected' : 'Offline')
                     .css('background', ok ? '#238636' : '#da3633');
        }
        if (ok) {
            $modPanel.show();
            $valPanel.show();
        }
    }

    checkSoap(false);
    $soapBtn.on('click', function(){ checkSoap(true); });

    /* ── Modules refresh ─────────────────────────────────────────────── */
    $('#acore-modules-refresh').on('click', function(){
        var $btn = $(this).prop('disabled', true).text('Refreshing…');
        $.ajax({
            url: restBase + 'server-modules', method: 'POST',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', nonce); }
        }).done(function(res){
            var $list = $('#acore-modules-list').empty();
            if (res.modules && res.modules.length) {
                res.modules.forEach(function(m){
                    $list.append($('<span>').addClass('acore-module-tag').text(m));
                });
            } else {
                $list.append($('<span>').addClass('acore-modules-empty').text('No modules found.'));
            }
            var d = new Date(res.refreshed * 1000);
            $('#acore-modules-refreshed').html('Last refreshed:<br>' + d.toLocaleString());
        }).fail(function(){
            alert('Failed to refresh modules. Is SOAP configured and the server running?');
        }).always(function(){
            $btn.prop('disabled', false).text('Refresh');
        });
    });

    /* ── Module Requirements ─────────────────────────────────────────── */
    function addReqRow(product, module) {
        var row = $('<tr>').append(
            $('<td style="padding:2px 4px 2px 0;">').append($('<input type="text" class="req-product regular-text" style="width:100%;">').val(product || '')),
            $('<td style="padding:2px 0 2px 4px;">').append($('<input type="text" class="req-module regular-text" style="width:100%;">').val(module || '')),
            $('<td>').append($('<button type="button" class="button button-link-delete req-remove" style="color:#a00;" title="Remove">').text('×'))
        );
        $('#acore-req-tbody').append(row);
    }

    $('#acore-req-add').on('click', function(){ addReqRow('', ''); });

    $(document).on('click', '.req-remove', function(){ $(this).closest('tr').remove(); });

    $('#acore-req-save').on('click', function(){
        var $btn = $(this).prop('disabled', true).text('Saving…');
        var $msg = $('#acore-req-msg');
        var reqs = [];
        $('#acore-req-tbody tr').each(function(){
            var p = $(this).find('.req-product').val().trim();
            var m = $(this).find('.req-module').val().trim();
            if (p && m) reqs.push([p, m]);
        });
        $.ajax({
            url: restBase + 'server-module-requirements', method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ requirements: reqs }),
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', nonce); }
        }).done(function(){
            $msg.css('color', '#238636').text('Saved!');
            setTimeout(function(){ $msg.text(''); }, 3000);
        }).fail(function(){
            $msg.css('color', '#da3633').text('Save failed.');
        }).always(function(){
            $btn.prop('disabled', false).text('Save');
        });
    });

    /* ── Settings form AJAX save ─────────────────────────────────────── */
    $('form[name="form-acore-settings"]').on('submit', function(e){
        e.preventDefault();
        var $msg = $('#ajax-message');
        $msg.html('<p>Saving…</p>');
        $.post(window.location.href, $(this).serialize() + '&noheader=true', function(){
            $msg.html('<div class="notice notice-success"><p>Settings saved.</p></div>');
        }).fail(function(){
            $msg.html('<div class="notice notice-error"><p>Save failed.</p></div>');
        });
    });

})(jQuery);
</script>
