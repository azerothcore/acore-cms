<?php

use ACore\Manager\Opts;
?>

<div class="wrap">
    <h2><?= __('AzerothCore Settings', Opts::I()->page_alias) ?></h2>
    <p>Configure realm name and database connection.</p>
    <div class="alert alert-warning" role="alert">
        Each field includes a placeholder, which must be replaced before using the SOAP connection or other plugin features. 
        Hit the Save changes button after you filled in all the fields.
        The default values are the suggestions based on the default azerothcore docker setup.
    </div>
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
                                <label for="acore_realm_alias">Realm Alias:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_realm_alias" value="<?= Opts::I()->acore_realm_alias ?>" placeholder="AzerothCore" size="20">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <hr />
                <table class="form-table table table-borderless" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_soap_host">Soap Host:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_soap_host" value="<?= Opts::I()->acore_soap_host ?>" placeholder="ac-worldserver" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_soap_port">Soap Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_soap_port" value="<?= Opts::I()->acore_soap_port ?>" placeholder="7878" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_soap_user">Soap User:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_soap_user" value="<?= Opts::I()->acore_soap_user ?>" placeholder="admin" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Soap Pass:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_soap_pass" value="<?= Opts::I()->acore_soap_pass ?>" placeholder="admin" size="20">
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>

                <hr />

                <table class="form-table table table-borderless" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_db_auth_host">Database Auth Host:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_host" value="<?= Opts::I()->acore_db_auth_host; ?>" placeholder="ac-database" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_auth_port">Database Auth Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_port" value="<?= Opts::I()->acore_db_auth_port; ?>" placeholder="3306" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_auth_user">Database Auth User:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_user" value="<?= Opts::I()->acore_db_auth_user; ?>" placeholder="root" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Database Auth Pass:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_db_auth_pass" value="<?= Opts::I()->acore_db_auth_pass; ?>" placeholder="password" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_auth_name">Database Auth Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_auth_name" value="<?= Opts::I()->acore_db_auth_name; ?>" placeholder="acore_auth" size="20">
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>

                <hr />

                <table class="form-table table table-borderless" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_db_char_host">Database Characters Host:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_host" value="<?= Opts::I()->acore_db_char_host; ?>" placeholder="ac-database" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_char_port">Database Characters Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_port" value="<?= Opts::I()->acore_db_char_port; ?>" placeholder="3306" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_char_user">Database Characters User:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_user" value="<?= Opts::I()->acore_db_char_user; ?>" placeholder="root" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Database Characters Pass:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_db_char_pass" value="<?= Opts::I()->acore_db_char_pass; ?>" placeholder="password" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_char_name">Database Characters Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_char_name" value="<?= Opts::I()->acore_db_char_name; ?>" placeholder="acore_characters" size="20">
                            </td>
                        </tr>
                        <tr>
                    </tbody>
                </table>

                <hr />

                <table class="form-table table table-borderless" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="acore_db_world_host">Database World Host:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_host" value="<?= Opts::I()->acore_db_world_host; ?>" placeholder="ac-database" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_world_port">Database World Port:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_port" value="<?= Opts::I()->acore_db_world_port; ?>" placeholder="3306" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_world_user">Database World User:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_user" value="<?= Opts::I()->acore_db_world_user; ?>" placeholder="root" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_realm_alias">Database World Pass:</label>
                            </th>
                            <td>
                                <input type="password" name="acore_db_world_pass" value="<?= Opts::I()->acore_db_world_pass; ?>" placeholder="password" size="20">
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="acore_db_world_name">Database World Name:</label>
                            </th>
                            <td>
                                <input type="text" name="acore_db_world_name" value="<?= Opts::I()->acore_db_world_name; ?>" placeholder="acore_world" size="20">
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