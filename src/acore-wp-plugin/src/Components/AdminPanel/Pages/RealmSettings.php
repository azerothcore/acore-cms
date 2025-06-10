<?php
    use ACore\Manager\Opts;
?>

<div class="wrap">
    <h2><?= __('AzerothCore Settings', Opts::I()->page_alias) ?></h2>
    <p>Configure realm name and database connection.</p>
    <form name="form-acore-settings" method="post" action="">
        <div class="card w-50 p-0">
            <div class="card-body">
                <h5>
                    General Settings
                </h5>
                <hr>
                <table class="form-table table table-borderless" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_realm_alias">Realm Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_realm_alias" value="<?= Opts::I()->acore_realm_alias; ?>" size="20" placeholder="AzerothCore">
                            </td>
                        </tr>
                    </tbody>
                    
                </table>
                <p>
                    <a href="https://www.azerothcore.org/wiki/remote-access">First time using SOAP? Click me!</a>
                </p>
                <hr />
                <table class="form-table table table-borderless" role="presentation">
                <h5>
                    SOAP Settings
                </h5>
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_soap_host">IPv4:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_soap_host" value="<?= Opts::I()->acore_soap_host; ?>" size="20" placeholder="127.0.0.1">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_soap_port">Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_soap_port" value="<?= Opts::I()->acore_soap_port; ?>" size="20" placeholder="7878">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_soap_user">Username:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_soap_user" value="<?= Opts::I()->acore_soap_user; ?>" size="20" >
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Password:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_soap_pass" value="<?= Opts::I()->acore_soap_pass; ?>" size="20" >
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>

                <hr />

                <table class="form-table table table-borderless" role="presentation">
                <h5>
                    Database: Auth
                </h5>
                    <tbody>
                        <tr>
                            <th scope="row">
                            <label for="acore_db_auth_host">IPv4:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_host" value="<?= Opts::I()->acore_db_auth_host; ?>" size="20" placeholder="127.0.0.1">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_auth_port">Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_port" value="<?= Opts::I()->acore_db_auth_port; ?>" size="20" placeholder="3306">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_auth_user">Username:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_user" value="<?= Opts::I()->acore_db_auth_user; ?>" size="20" placeholder="acore">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Password:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_db_auth_pass" value="<?= Opts::I()->acore_db_auth_pass; ?>" size="20" placeholder="acore">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_auth_name">Database Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_name" value="<?= Opts::I()->acore_db_auth_name; ?>" size="20" placeholder="acore_auth">
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>

                <hr />

                <table class="form-table table table-borderless" role="presentation">
                <h5>
                    Database: Characters
                </h5>
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_db_char_host">IPv4:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_host" value="<?= Opts::I()->acore_db_char_host; ?>" size="20" placeholder="127.0.0.1">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_char_port">Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_port" value="<?= Opts::I()->acore_db_char_port; ?>" size="20" placeholder="3306">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_char_user">Username:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_user" value="<?= Opts::I()->acore_db_char_user; ?>" size="20" placeholder="acore">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Password:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_db_char_pass" value="<?= Opts::I()->acore_db_char_pass; ?>" size="20" placeholder="acore">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_char_name">Database Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_name" value="<?= Opts::I()->acore_db_char_name; ?>" size="20" placeholder="acore_characters">
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>

            <hr />

                <table class="form-table table table-borderless" role="presentation">
                <h5>
                    Database: World
                </h5>
                    <tbody>
                        <tr>
                            <th scope="row">
                            <label for="acore_db_world_host">IPv4:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_host" value="<?= Opts::I()->acore_db_world_host; ?>" size="20" placeholder="127.0.0.1">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_world_port">Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_port" value="<?= Opts::I()->acore_db_world_port; ?>" size="20" placeholder="3306">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_world_user">Username:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_user" value="<?= Opts::I()->acore_db_world_user; ?>" size="20" placeholder="acore">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Password:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_db_world_pass" value="<?= Opts::I()->acore_db_world_pass; ?>" size="20" placeholder="acore">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_world_name">Database Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_name" value="<?= Opts::I()->acore_db_world_name; ?>" size="20" placeholder="acore_world">
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="ajax-message"></div>

        <p class="submit">
            <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>" />
            <input type="button" name="check-soap" id="check-soap" class="button-secondary" value="<?php esc_attr_e('Check SOAP', Opts::I()->page_alias) ?>" />
        </p>
        <h6>
            You will need to "Save Changes" above before checking your SOAP Configuration!
        </h6>

    </form>
</div>

<script>
    jQuery('#check-soap').on('click', function(e) {
        jQuery('#ajax-message').html('');
        jQuery.ajax({
            url: "<?php echo get_rest_url(null, ACORE_SLUG . '/v1/server-info'); ?>",
            success: function(response) {
                jQuery('#ajax-message').html('<div class="notice notice-info"><p>SOAP Response: <strong>' + response.message + '</strong></p></div>');
            },
            error: function(response) {
                jQuery('#ajax-message').html('<div class="notice notice-error"><p>An unknown error happens requesting SOAP status.</div>');
            },
        })
    });
</script>
