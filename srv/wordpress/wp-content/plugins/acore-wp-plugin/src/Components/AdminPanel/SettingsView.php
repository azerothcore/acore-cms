<?php

namespace ACore\Components\AdminPanel;

use ACore\Manager\Opts;

class SettingsView {

    private $controller;
    private $model;

    /**
     *
     * @param ACore\SettingsController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
        $this->model = $controller->getModel();
    }

    public function getHomeRender() {

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();

        // Now display the settings editing screen

        ?>
        <div class="wrap">
        <?php
        echo "<h2>" . __('AzerothCore', Opts::I()->page_alias) . "</h2>";
        ?>
            <div class="card w-50">
                <div class="card-body">
                    <p class="fs-6"><b>Welcome to AzerothCore WP Plugin.</b></p>
                    <p class="fs-6">Please take a look to the following links before continue:</p>
                    <ul class="list-unstyled">
                        <li><a href="https://www.azerothcore.org/" target="_blank">Project Homepage</a></li>
                        <li><a href="https://github.com/AzerothCore/" target="_blank">Github Repositories</a></li>
                        <li><a href="https://www.azerothcore.org/wiki/" target="_blank">Wiki</a></li>
                        <li><a href="https://www.paypal.com/donate/?hosted_button_id=L69ANPSR8BJDU" target="_blank">Sponsor</a></li>
                        <li><a href="https://salt.bountysource.com/checkout/amount?team=azerothcore" target="_blank">Donations</a></li>
                    </ul>
                    <p class="fs-6">AzerothCore is made with ❤️ and is fully open-source.</p>
                </div>
            </div>
        </div>

        <?php
        return ob_get_clean();
    }

    public function getSettingsRender() {

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();

        // Now display the settings editing screen

        ?>
        <div class="wrap">
        <?php
        echo "<h2>" . __('AzerothCore Settings', Opts::I()->page_alias) . "</h2>";
        ?>
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
                                    <label for="acore_realm_alias">Realm Alias:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_realm_alias" value="<?= Opts::I()->acore_realm_alias; ?>" size="20">
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
                                    <input type="text" name="acore_soap_host" value="<?= Opts::I()->acore_soap_host; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_soap_port">Soap Port:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_soap_port" value="<?= Opts::I()->acore_soap_port; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_soap_user">Soap User:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_soap_user" value="<?= Opts::I()->acore_soap_user; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_realm_alias">Soap Pass:</label>
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
                        <tbody>
                            <tr>
                                <th scope="row">
                                <label for="acore_db_auth_host">Database Auth Host:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_auth_host" value="<?= Opts::I()->acore_db_auth_host; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_auth_port">Database Auth Port:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_auth_port" value="<?= Opts::I()->acore_db_auth_port; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_auth_user">Database Auth User:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_auth_user" value="<?= Opts::I()->acore_db_auth_user; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_realm_alias">Database Auth Pass:</label>
                                </th>
                                <td>
                                    <input type="password" name="acore_db_auth_pass" value="<?= Opts::I()->acore_db_auth_pass; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_auth_name">Database Auth Name:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_auth_name" value="<?= Opts::I()->acore_db_auth_name; ?>" size="20" >
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
                                    <input type="text" name="acore_db_char_host" value="<?= Opts::I()->acore_db_char_host; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_char_port">Database Characters Port:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_char_port" value="<?= Opts::I()->acore_db_char_port; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_char_user">Database Characters User:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_char_user" value="<?= Opts::I()->acore_db_char_user; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_realm_alias">Database Characters Pass:</label>
                                </th>
                                <td>
                                    <input type="password" name="acore_db_char_pass" value="<?= Opts::I()->acore_db_char_pass; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_char_name">Database Characters Name:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_char_name" value="<?= Opts::I()->acore_db_char_name; ?>" size="20" >
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
                                    <input type="text" name="acore_db_world_host" value="<?= Opts::I()->acore_db_world_host; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_world_port">Database World Port:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_world_port" value="<?= Opts::I()->acore_db_world_port; ?>" size="20">
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_world_user">Database World User:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_world_user" value="<?= Opts::I()->acore_db_world_user; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_realm_alias">Database World Pass:</label>
                                </th>
                                <td>
                                    <input type="password" name="acore_db_world_pass" value="<?= Opts::I()->acore_db_world_pass; ?>" size="20" >
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="acore_db_world_name">Database World Name:</label>
                                </th>
                                <td>
                                    <input type="text" name="acore_db_world_name" value="<?= Opts::I()->acore_db_world_name; ?>" size="20" >
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
        <?php
        return ob_get_clean();
    }

    public function getElunaSettingsRender() {

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();

        // Now display the settings editing screen

        ?>
        <div class="wrap">
            <?php
            echo "<h2>" . __('AzerothCore Settings', Opts::I()->page_alias) . "</h2>";
            ?>

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
        <?php
        return ob_get_clean();
    }

    public function getPvpRewardsRender($data, $result) {

        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();

        // Now display the settings editing screen

        $myCredConfs = get_option('mycred_pref_core');
        echo '<div class="wrap">';

        // header

        echo "<h2>" . __('PvP Rewards', Opts::I()->page_alias) . "</h2>";

        // settings form
        ?>
            <p>Give custom credit rewards to players based on BG win or lose.</p>
            <div class="row">
                <div class="col-sm-4">
                    <div class="card p-0">
                        <div class="card-body">
                            <h5>Give rewards</h5>
                            <hr>
                            <form name="pvp-rewards" method="post" id="pvp-rewards" class="initial-form hide-if-no-js">
                                <input type="hidden" name="page" value="<?= ACORE_SLUG . '-pvp-rewards'; ?>" />
                                <table class="form-table table table-borderless" role="presentation">
                                    <tbody>
                                        <tr>
                                            <th scope="row">
                                            <label for="token">
                                                <label>Cred ID</label>
                                            </th>
                                            <td>
                                                <?php if (isset($myCredConfs['cred_id'])) {
                                                    echo $myCredConfs['cred_id'] . ( isset($myCredConfs['name']['singular']) ? " (" . $myCredConfs['name']['singular'] . ")" : "");
                                                } else {
                                                    echo "<p>No Cred ID Found. Please check settings.</p>";
                                                    echo '<a href="' . admin_url( 'admin.php?page=' . MYCRED_SLUG . '-settings' ) . '" >' . __( 'MyCred Settings', 'mycred' ) . '</a>';
                                                } ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                            <label for="token">
                                                <label for="amount">Amount per result</label>
                                            </th>
                                            <td>
                                                <input type="number" name="amount" id="amount" autocomplete="off" min=0 value=<?php echo $data['amount']; ?> required />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="is_winner">Result to reward</label>
                                            </th>
                                            <td>
                                                <select name="is_winner" id="result" required>
                                                    <option value=null selected disabled>Select result</option>
                                                    <option value=0 <?php if ($data['isWinner'] == 0) echo 'selected'; ?>>Looser</option>
                                                    <option value=1 <?php if ($data['isWinner'] == 1) echo 'selected'; ?>>Winner</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="bracket">Bracket</label>
                                            </th>
                                            <td>
                                                <select name="bracket" id="bracket" required>
                                                    <option value=0 <?php if ($data['bracket'] == 0) echo 'selected'; ?>>All</option>
                                                    <option value=1 <?php if ($data['bracket'] == 1) echo 'selected'; ?>>10-19</option>
                                                    <option value=2 <?php if ($data['bracket'] == 2) echo 'selected'; ?>>20-29</option>
                                                    <option value=3 <?php if ($data['bracket'] == 3) echo 'selected'; ?>>30-39</option>
                                                    <option value=4 <?php if ($data['bracket'] == 4) echo 'selected'; ?>>40-49</option>
                                                    <option value=5 <?php if ($data['bracket'] == 5) echo 'selected'; ?>>50-59</option>
                                                    <option value=6 <?php if ($data['bracket'] == 6) echo 'selected'; ?>>60-69</option>
                                                    <option value=7 <?php if ($data['bracket'] == 7) echo 'selected'; ?>>70-79</option>
                                                    <option value=8 <?php if ($data['bracket'] == 8) echo 'selected'; ?>>80</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="month">Month</label>
                                            </th>
                                            <td>
                                                <select name="month" id="month" required>
                                                    <option value=0 <?php if ($data['month'] == 0) echo 'selected'; ?> disabled>Select month</option>
                                                    <option value=1 <?php if ($data['month'] == 1) echo 'selected'; ?>>January</option>
                                                    <option value=2 <?php if ($data['month'] == 2) echo 'selected'; ?>>February</option>
                                                    <option value=3 <?php if ($data['month'] == 3) echo 'selected'; ?>>March</option>
                                                    <option value=4 <?php if ($data['month'] == 4) echo 'selected'; ?>>April</option>
                                                    <option value=5 <?php if ($data['month'] == 5) echo 'selected'; ?>>May</option>
                                                    <option value=6 <?php if ($data['month'] == 6) echo 'selected'; ?>>June</option>
                                                    <option value=7 <?php if ($data['month'] == 7) echo 'selected'; ?>>July</option>
                                                    <option value=8 <?php if ($data['month'] == 8) echo 'selected'; ?>>August</option>
                                                    <option value=9 <?php if ($data['month'] == 9) echo 'selected'; ?>>September</option>
                                                    <option value=10 <?php if ($data['month'] == 10) echo 'selected'; ?>>October</option>
                                                    <option value=11 <?php if ($data['month'] == 11) echo 'selected'; ?>>November</option>
                                                    <option value=12 <?php if ($data['month'] == 12) echo 'selected'; ?>>December</option>
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
                                                        echo "<option value=$i" . (($i == $year) ? " seleted" : "") . ">$i</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label>Top Extra Rewards</label>
                                            </th>
                                            <td><hr>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="top">Top</label>
                                            </th>
                                            <td>
                                                <select name="top" id="result" required>
                                                    <option value=0 <?php if ($data['top'] == 0) echo 'selected'; ?>>None</option>
                                                    <option value=5 <?php if ($data['top'] == 5) echo 'selected'; ?>>Top 5</option>
                                                    <option value=10 <?php if ($data['top'] == 10) echo 'selected'; ?>>Top 10</option>
                                                    <option value=15 <?php if ($data['top'] == 15) echo 'selected'; ?>>Top 15</option>
                                                    <option value=20 <?php if ($data['top'] == 20) echo 'selected'; ?>>Top 20</option>
                                                    <option value=25 <?php if ($data['top'] == 25) echo 'selected'; ?>>Top 25</option>
                                                </select>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="fixed_amount">Fixed amount</label>
                                            </th>
                                            <td>
                                                <input type="number" name="fixed_amount" id="fixed_amount" autocomplete="off" min=0 value=<?php echo $data['fixedAmount']; ?> required />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="step_amount">Step amount</label>
                                            </th>
                                            <td>
                                                <input type="number" name="step_amount" id="step_amount" autocomplete="off" min=0 value=<?php echo $data['stepAmount']; ?> required />
                                            </td>
                                        </tr>
                                        <tr>
                                            <th scope="row">
                                                <label for="limit_rewards">Limit rewards</label>
                                            </th>
                                            <td>
                                                <input type="number" name="limit_rewards" id="limit_rewards" autocomplete="off" min=0 value=<?php echo $data['limitRewards']; ?> required />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <input type="submit" name="preview" id="preview" class="button-secondary" value="<?php esc_attr_e('Preview', Opts::I()->page_alias) ?>" />
                                            </td>
                                            <td align="right">
                                                <input type="submit" name="send-rewards" id="send-rewards" class="button-primary" value="<?php esc_attr_e('Send rewards', Opts::I()->page_alias) ?>" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-sm-8">
                    <div class="card p-0">
                        <div class="card-body">
                            <h5>PvP Summary</h5>
                            <hr>
                            <?php
                            if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($result) && is_array($result) && count($result) > 0) {?>
                                <p><strong>This is a preview of top 10 players related to the options selected.</strong></p>
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Char name</th>
                                            <th>Result Count</th>
                                            <th>Points to Obtain</th>
                                            <th>Extra Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $i = $data['top'];
                                    foreach ($result as $item) {
                                        echo "<tr><td>" . $item['username'] . "</td>";
                                        echo "<td>" . $item['character_name'] . "</td>";
                                        echo "<td>" . $item['total_battle'] . "</td>";
                                        $points = number_format(
                                            $item['points'],
                                            $myCredConfs['format']['decimals'],
                                            $myCredConfs['format']['separators']['decimal'],
                                            $myCredConfs['format']['separators']['thousand']);
                                        echo "<td>" . $points . "</td>";
                                        if ($i > 0) {
                                            $temp = $data['fixedAmount'] + ($data['stepAmount'] * $i);
                                            $points = number_format(
                                                $temp,
                                                $myCredConfs['format']['decimals'],
                                                $myCredConfs['format']['separators']['decimal'],
                                                $myCredConfs['format']['separators']['thousand']);
                                            echo "<td>" . $points . "</td></tr>";
                                            $i--;
                                        } else {
                                            echo "<td>0</td></tr>";
                                        }
                                    }
                                    ?>
                                    </tbody>
                                </table>
                                <?php
                            } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
                                echo "<p>No results found.</p>";
                            } else {
                                echo "<p>No results found.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

        <hr />
        <script>
            jQuery('#preview').on('click', function(e) {
                jQuery('#pvp-rewards').attr('method', 'GET');
            });
            jQuery('#send-rewards').on('click', function(e) {
                jQuery('#pvp-rewards').attr('method', 'POST');
            });
            jQuery('#pvp-rewards').on('submit', function(e) {
                var r = confirm("You sure you want to continue?");
                return r;
            });
        </script>
        </div>

        <?php
        return ob_get_clean();
    }

}
