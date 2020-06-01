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

    public function getRender() {
        ob_start();

        // Now display the settings editing screen

        echo '<div class="wrap">';

        // header

        echo "<h2>" . __('AzerothCore Settings', sOpts()->page_alias) . "</h2>";

        // settings form
        ?>

        <form name="form-acore-settings" method="post" action="">
            <p>Realm Alias: 
                <input type="text" name="acore_realm_alias" value="<?= sOpts()->acore_realm_alias; ?>" size="20">
            </p>

            <hr />

            <p>Soap Host: 
                <input type="text" name="acore_soap_host" value="<?= sOpts()->acore_soap_host; ?>" size="20">
            </p>
            <p>Soap Port: 
                <input type="text" name="acore_soap_port" value="<?= sOpts()->acore_soap_port; ?>" size="20">
            </p>
            <p>Soap User: 
                <input type="text" name="acore_soap_user" value="<?= sOpts()->acore_soap_user; ?>" size="20" >
            </p>
            <p>Soap Pass: 
                <input type="text" name="acore_soap_pass" value="<?= sOpts()->acore_soap_pass; ?>" size="20" >
            </p>

            <hr />

            <p>Database Auth Host: 
                <input type="text" name="acore_db_auth_host" value="<?= sOpts()->acore_db_auth_host; ?>" size="20">
            </p>
            <p>Database Auth Port: 
                <input type="text" name="acore_db_auth_port" value="<?= sOpts()->acore_db_auth_port; ?>" size="20">
            </p>
            <p>Database Auth User: 
                <input type="text" name="acore_db_auth_user" value="<?= sOpts()->acore_db_auth_user; ?>" size="20" >
            </p>
            <p>Database Auth Pass: 
                <input type="text" name="acore_db_auth_pass" value="<?= sOpts()->acore_db_auth_pass; ?>" size="20" >
            </p>
            <p>Database Auth Name: 
                <input type="text" name="acore_db_auth_name" value="<?= sOpts()->acore_db_auth_name; ?>" size="20" >
            </p>

            <hr />

            <p>Database Characters Host: 
                <input type="text" name="acore_db_char_host" value="<?= sOpts()->acore_db_char_host; ?>" size="20">
            </p>
            <p>Database Characters Port: 
                <input type="text" name="acore_db_char_port" value="<?= sOpts()->acore_db_char_port; ?>" size="20">
            </p>
            <p>Database Characters User: 
                <input type="text" name="acore_db_char_user" value="<?= sOpts()->acore_db_char_user; ?>" size="20" >
            </p>
            <p>Database Characters Pass: 
                <input type="text" name="acore_db_char_pass" value="<?= sOpts()->acore_db_char_pass; ?>" size="20" >
            </p>
            <p>Database Characters Name: 
                <input type="text" name="acore_db_char_name" value="<?= sOpts()->acore_db_char_name; ?>" size="20" >
            </p>

            <hr />

            <p>Database World Host: 
                <input type="text" name="acore_db_world_host" value="<?= sOpts()->acore_db_world_host; ?>" size="20">
            </p>
            <p>Database World Port: 
                <input type="text" name="acore_db_world_port" value="<?= sOpts()->acore_db_world_port; ?>" size="20">
            </p>
            <p>Database World User: 
                <input type="text" name="acore_db_world_user" value="<?= sOpts()->acore_db_world_user; ?>" size="20" >
            </p>
            <p>Database World Pass: 
                <input type="text" name="acore_db_world_pass" value="<?= sOpts()->acore_db_world_pass; ?>" size="20" >
            </p>
            <p>Database World Name: 
                <input type="text" name="acore_db_world_name" value="<?= sOpts()->acore_db_world_name; ?>" size="20" >
            </p>

            <hr />

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', sOpts()->page_alias) ?>" />
            </p>

        </form>
        </div>

        <?php
        return ob_get_clean();
    }

}
