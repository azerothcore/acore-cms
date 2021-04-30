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
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="acore_realm_alias">Realm Alias:</label>
                        </th>
                        <td>
                            <input type="text" name="acore_realm_alias" value="<?= Opts::I()->acore_realm_alias; ?>" size="20">
                        </td>
                    </tr>
                </tbody>
            </table>

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

            <p class="submit">
                <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes', Opts::I()->page_alias) ?>" />
            </p>

        </form>
        </div>

        <?php
        return ob_get_clean();
    }

    public function getPvpRewardsRender() {
        ob_start();

        // Now display the settings editing screen

        echo '<div class="wrap">';

        // header

        echo "<h2>" . __('PvP Rewards', Opts::I()->page_alias) . "</h2>";

        // settings form
        ?>

            <div id="dashboard-widgets-wrap">
                <div id="dashboard-widgets" class="metabox-holder">
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="normal-sortables" class="meta-box-sortables">
                            <div id="dashboard_site_health" class="postbox ">
                                <div class="postbox-header"><h2 class="hndle">Give rewards</h2>
                                </div>
                                <div class="inside">
                                    <form name="post" action="" method="post" id="quick-press" class="initial-form hide-if-no-js">
                                    <table class="form-table" role="presentation">
                                        <tbody>
                                            <tr>
                                                <th scope="row">
                                                    <label for="token">Mycred Token</label>
                                                </th>
                                                <td>
                                                    ChromiePoins
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                <label for="token">
                                                    <label for="amount">Amount per result</label>
                                                </th>
                                                <td>
                                                    <input type="number" name="amount" id="amount" autocomplete="off" min=0 value=0 required />
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="result">Result to reward</label>
                                                </th>
                                                <td>
                                                    <select name="result" id="result" required>
                                                        <option value=null selected disabled>Select result</option>
                                                        <option value=0>Looser</option>
                                                        <option value=1>Winner</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="bracket">Bracket</label>
                                                </th>
                                                <td>
                                                    <select name="bracket" id="bracket" required>
                                                        <option value=0 selected disabled>Select bracket</option>
                                                        <option value=1>10-19</option>
                                                        <option value=2>20-29</option>
                                                        <option value=3>30-39</option>
                                                        <option value=4>40-49</option>
                                                        <option value=5>50-59</option>
                                                        <option value=6>60-69</option>
                                                        <option value=7>70-79</option>
                                                        <option value=8>80</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="month">Month</label>
                                                </th>
                                                <td>
                                                    <select name="month" id="month" required>
                                                        <option value=0 selected disabled>Select month</option>
                                                        <option value=1>January</option>
                                                        <option value=2>February</option>
                                                        <option value=3>March</option>
                                                        <option value=4>April</option>
                                                        <option value=5>May</option>
                                                        <option value=6>June</option>
                                                        <option value=7>July</option>
                                                        <option value=8>August</option>
                                                        <option value=9>September</option>
                                                        <option value=10>October</option>
                                                        <option value=11>November</option>
                                                        <option value=12>December</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="year">Year</label>
                                                </th>
                                                <td>
                                                    <select name="year" id="year" required>
                                                        <?php $year = (int) (new \DateTime())->format('Y');
                                                        for ($i = $year; $i >= 2015; $i--) {
                                                            echo "<option value=$i selected>$i</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>
                                                    <input type="submit" name="submit" id="preview" class="button-secondary" value="<?php esc_attr_e('Preview', Opts::I()->page_alias) ?>" />
                                                </td>
                                                <td align="right">
                                                    <input type="submit" name="submit" id="send-rewards" class="button-primary" value="<?php esc_attr_e('Send rewards', Opts::I()->page_alias) ?>" />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="postbox-container-2" class="postbox-container">
                        <div id="normal-sortables" class="meta-box-sortables">
                            <div id="dashboard_site_health" class="postbox ">
                                <div class="postbox-header"><h2 class="hndle">PvP Summary</h2>
                                </div>
                                <div class="inside">
                                        <p>asdasdfasdf</p>
                                        <p>adsfasdfa</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <hr />

        </div>

        <?php
        return ob_get_clean();
    }

}
