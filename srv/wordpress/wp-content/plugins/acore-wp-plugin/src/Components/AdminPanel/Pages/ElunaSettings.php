<?php
    use ACore\Manager\Opts;
?>

<div class="wrap">
    <h2> <?= __('AzerothCore Settings', Opts::I()->page_alias) ?></h2>
    <p>Configure database connection for Eluna script that need use of the CMS.</p>
    <form name="form-acore-eluna-settings" method="post" action="">
        <div class="row">
            <div class="col-sm-6">
                <div class="card p-0">
                    <div class="card-body">
                        <h5>
                        Eluna configuration
                        </h5>
                        <hr>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                    <label for="acore_db_eluna_host">Database Eluna Host:</label>
                                    </th>
                                    <td>
                                        <input type="text" name="acore_db_eluna_host" value="<?= Opts::I()->acore_db_eluna_host; ?>" size="20">
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="acore_db_eluna_port">Database Eluna Port:</label>
                                    </th>
                                    <td>
                                        <input type="text" name="acore_db_eluna_port" value="<?= Opts::I()->acore_db_eluna_port; ?>" size="20">
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="acore_db_eluna_user">Database Eluna User:</label>
                                    </th>
                                    <td>
                                        <input type="text" name="acore_db_eluna_user" value="<?= Opts::I()->acore_db_eluna_user; ?>" size="20" >
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="acore_realm_alias">Database Eluna Pass:</label>
                                    </th>
                                    <td>
                                        <input type="password" name="acore_db_eluna_pass" value="<?= Opts::I()->acore_db_eluna_pass; ?>" size="20" >
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <label for="acore_db_eluna_name">Database Eluna Name:</label>
                                    </th>
                                    <td>
                                        <input type="text" name="acore_db_eluna_name" value="<?= Opts::I()->acore_db_eluna_name; ?>" size="20" >
                                    </td>
                                </tr>
                                <tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="submit">
                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>" />
                </p>
            </div>
            <div class="col-sm-6">
                <div class="card p-0">
                    <div class="card-body">
                        <h5>
                            Eluna Modules
                        </h5>
                        <hr>
                        <table class="form-table table table-borderless" role="presentation">
                            <tbody>
                                <tr>
                                    <th>
                                        <label for="eluna_recruit_a_friend">Recruit a Friend <a href="https://github.com/55Honey/Acore_RecruitAFriend" target="_blank"><span class="dashicons dashicons-external"></span></a></label>
                                    </th>
                                    <td>
                                        <select name="eluna_recruit_a_friend" id="eluna_recruit_a_friend">
                                            <option value="0">Disabled</option>
                                            <option value="1" <?php if (Opts::I()->eluna_recruit_a_friend == '1') echo 'selected'; ?>>Enabled</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="eluna_raf_config" <?php if (Opts::I()->eluna_recruit_a_friend != '1') echo 'style="display:none;"'?>>
                                    <th>
                                        <label for="eluna_raf_config[check_ip]">RAF: Check IP abuse</label>
                                    </th>
                                    <td>
                                        <select name="eluna_raf_config[check_ip]" id="eluna_raf_config_check_ip">
                                            <option value="0">Disabled</option>
                                            <option value="1" <?php if (Opts::I()->eluna_raf_config['check_ip'] === '1') echo 'selected'; ?>>Enabled</option>
                                        </select>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    jQuery('#check-soap').on('click', function(e) {
        jQuery.ajax({
            url: "<?php echo get_rest_url(null, 'wp-acore/v1/server-info'); ?>",
            success: function(response) {
                jQuery('#ajax-message').html('<div class="notice notice-info"><p>SOAP Response: <strong>' + response.message + '</strong></p></div>');
            },
            error: function(response) {
                jQuery('#ajax-message').html('<div class="notice notice-error"><p>An unknown error happens requesting SOAP status.</div>');
            },
        })
    });
    jQuery('#eluna_recruit_a_friend').on('change', function() {
        jQuery('.eluna_raf_config').toggle();
    })
</script>
