<?php

namespace ACore;

use ACore;

require_once 'Settings.controller.php';

class SettingsView {

    private $controller;
    private $model;

    /**
     *
     * @param \ACore\SettingsController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
        $this->model = $controller->getModel();
    }

    public function getHomeRender() {
        ob_start();

        // Now display the settings editing screen

        echo '<div class="wrap">';

        // header

        echo "<h2>" . __('AzerothCore', Opts::I()->page_alias) . "</h2>";

        // settings form
        ?>

        <p>Welcome to AzerothCore WP Plugin.</p>
        <p>Please take a look to the following links before continue:</p>
        <ul>
            <li><a href="https://www.azerothcore.org/">Project Homepage</a></li>
            <li><a href="https://github.com/AzerothCore/">Github Repositories</a></li>
            <li><a href="https://www.azerothcore.org/wiki/">Wiki</a></li>
            <li><a href="https://salt.bountysource.com/checkout/amount?team=azerothcore">Donations</a></li>
        </ul>
        <p>This project is fully open-source.</p>
        </div>

        <?php
        return ob_get_clean();
    }

    public function getSettingsRender() {
        ob_start();

        // Now display the settings editing screen

        echo '<div class="wrap">';

        // header

        echo "<h2>" . __('AzerothCore Settings', Opts::I()->page_alias) . "</h2>";

        // settings form
        ?>

        <form name="form-acore-settings" method="post" action="">
            <p>Realm Alias:
                <input type="text" name="acore_realm_alias" value="<?= Opts::I()->acore_realm_alias; ?>" size="20">
            </p>

            <hr />

            <p>Soap Host:
                <input type="text" name="acore_soap_host" value="<?= Opts::I()->acore_soap_host; ?>" size="20">
            </p>
            <p>Soap Port:
                <input type="text" name="acore_soap_port" value="<?= Opts::I()->acore_soap_port; ?>" size="20">
            </p>
            <p>Soap User:
                <input type="text" name="acore_soap_user" value="<?= Opts::I()->acore_soap_user; ?>" size="20" >
            </p>
            <p>Soap Pass:
                <input type="password" name="acore_soap_pass" value="<?= Opts::I()->acore_soap_pass; ?>" size="20" >
            </p>

            <hr />

            <p>Database Auth Host:
                <input type="text" name="acore_db_auth_host" value="<?= Opts::I()->acore_db_auth_host; ?>" size="20">
            </p>
            <p>Database Auth Port:
                <input type="text" name="acore_db_auth_port" value="<?= Opts::I()->acore_db_auth_port; ?>" size="20">
            </p>
            <p>Database Auth User:
                <input type="text" name="acore_db_auth_user" value="<?= Opts::I()->acore_db_auth_user; ?>" size="20" >
            </p>
            <p>Database Auth Pass:
                <input type="password" name="acore_db_auth_pass" value="<?= Opts::I()->acore_db_auth_pass; ?>" size="20" >
            </p>
            <p>Database Auth Name:
                <input type="text" name="acore_db_auth_name" value="<?= Opts::I()->acore_db_auth_name; ?>" size="20" >
            </p>

            <hr />

            <p>Database Characters Host:
                <input type="text" name="acore_db_char_host" value="<?= Opts::I()->acore_db_char_host; ?>" size="20">
            </p>
            <p>Database Characters Port:
                <input type="text" name="acore_db_char_port" value="<?= Opts::I()->acore_db_char_port; ?>" size="20">
            </p>
            <p>Database Characters User:
                <input type="text" name="acore_db_char_user" value="<?= Opts::I()->acore_db_char_user; ?>" size="20" >
            </p>
            <p>Database Characters Pass:
                <input type="password" name="acore_db_char_pass" value="<?= Opts::I()->acore_db_char_pass; ?>" size="20" >
            </p>
            <p>Database Characters Name:
                <input type="text" name="acore_db_char_name" value="<?= Opts::I()->acore_db_char_name; ?>" size="20" >
            </p>

            <hr />

            <p>Database World Host:
                <input type="text" name="acore_db_world_host" value="<?= Opts::I()->acore_db_world_host; ?>" size="20">
            </p>
            <p>Database World Port:
                <input type="text" name="acore_db_world_port" value="<?= Opts::I()->acore_db_world_port; ?>" size="20">
            </p>
            <p>Database World User:
                <input type="text" name="acore_db_world_user" value="<?= Opts::I()->acore_db_world_user; ?>" size="20" >
            </p>
            <p>Database World Pass:
                <input type="password" name="acore_db_world_pass" value="<?= Opts::I()->acore_db_world_pass; ?>" size="20" >
            </p>
            <p>Database World Name:
                <input type="text" name="acore_db_world_name" value="<?= Opts::I()->acore_db_world_name; ?>" size="20" >
            </p>

            <hr />
            <div id="ajax-message"></div>

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>" />
                <input type="button" name="check-soap" id="check-soap" class="button-secondary" value="<?php esc_attr_e('Check SOAP', Opts::I()->page_alias) ?>" />
            </p>

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
        </script>
        <?php
        return ob_get_clean();
    }

}
