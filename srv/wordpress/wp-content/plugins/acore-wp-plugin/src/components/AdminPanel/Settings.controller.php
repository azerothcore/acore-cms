<?php

namespace ACore;

require_once 'Settings.view.php';
require_once 'Settings.model.php';

class SettingsController {

    /**
     *
     * @var SettingsView
     */
    private $view;

    /**
     *
     * @var SettingsModel
     */
    private $model;
    private $data;

    public function __construct() {
        $this->model = new SettingsModel();
        $this->view = new SettingsView($this);
    }

    public function loadHome() {
        //must check that the user has the required capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'

        echo $this->getView()->getHomeRender();
    }

    public function loadSettings() {
        //must check that the user has the required capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            foreach (Opts::I()->getConfs() as $key => $value) {
                if (isset($_POST[$key])) {
                    $this->model->storeConf($key, $_POST[$key]);
                }
            }

            $this->data = $this->model->loadData(); // reload confs
            // Put a "settings saved" message on the screen
            ?>
            <div class="updated"><p><strong>Option saved</strong></p></div>
            <?php
        }

        echo $this->getView()->getSettingsRender();
    }

    public function loadPvpRewards() {
        //must check that the user has the required capability
        if (!current_user_can('manage_options')) {
            wp_die(__('<div class="notice notice-error"><p>You do not have sufficient permissions to access this page.</p></div>'));
        }

        if (!is_plugin_active('mycred/mycred.php')) {
            wp_die(__('<div class="notice notice-error"><p>You need mycred plugin active to use PvP Rewards.</p></div>'));
        }

        $myCredConfs = get_option('mycred_pref_core');

        if (!isset($myCredConfs['cred_id']) || empty($myCredConfs['cred_id'])) {
            wp_die(__('<div class="notice notice-error"><p>No Cred ID Found. Please check settings. <a href="' . admin_url( 'admin.php?page=' . MYCRED_SLUG . '-settings' ) . '" >' . __( 'MyCred Settings', 'mycred' ) . '</a></p></div>'));
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            global $wpdb;
            $tableResult = $wpdb->query("CREATE TEMPORARY TABLE temp_pvp_rewards (
                `account` VARCHAR(255),
                `points` INT
            )");
            if ($tableResult === false) {
                wp_die(__('<div class="notice notice-error"><p>Error trying to create temporal table. Please check mysql user privileges.</p></div>'));
            }
        }

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'
        $amount = 0;
        $isWinner = 1;
        $bracket = 0;
        $bracketAnd = '';
        $month = 1;
        $year = 2010;
        $result = [];
        $top = 0;
        $fixedAmount = 0;
        $stepAmount = 0;
        $mycredTokenName = $myCredConfs['cred_id'];
        $authDbName = Opts::I()->acore_db_auth_name;
        $charDbName = Opts::I()->acore_db_char_name;

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            global $wpdb;
            $rewards = [];
            if (isset($_POST["amount"])) {
                $amount = (int) $_POST['amount'];
            }
            if (isset($_POST["is_winner"])) {
                $isWinner = (int) $_POST['is_winner'];
            }
            if (isset($_POST["bracket"])) {
                if ((int) $_POST['bracket'] != 0) {
                    $bracket = $_POST['bracket'];
                    $bracketAnd = 'AND bracket_id = ' . $_POST['bracket'];
                }
            }
            if (isset($_POST["month"])) {
                $month = (int) $_POST['month'];
            }
            if (isset($_POST["year"])) {
                $year = (int) $_POST['year'];
            }
            if (isset($_POST["top"])) {
                $top = (int) $_POST['top'];
            }
            if (isset($_POST["fixed_amount"])) {
                $fixedAmount = (int) $_POST['fixed_amount'];
            }
            if (isset($_POST["step_amount"])) {
                $stepAmount = (int) $_POST['step_amount'];
            }
            $query = "SELECT
                character_guid,
                COUNT(character_guid) * $amount AS points,
                account.username
            FROM pvpstats_players
            INNER JOIN pvpstats_battlegrounds ON pvpstats_players.battleground_id = pvpstats_battlegrounds.id
            INNER JOIN characters ON pvpstats_players.character_guid = characters.guid
            INNER JOIN `$authDbName`.account AS account ON characters.account = account.id
            WHERE characters.deleteDate IS NULL
                AND pvpstats_players.winner = $isWinner
                $bracketAnd
                AND MONTH(date) = $month
                AND YEAR(date) = $year
            GROUP BY character_guid
            ORDER BY COUNT(character_guid) DESC";

            $connection = ACoreServices::I()->getCharactersMgr()->getConnection();
            $result = $connection->query($query)->fetchAll();
            if ($result) {
                $accountCounter = 0;
                $pointsCounter = 0;
                $i = $top;
                foreach ($result as $item) {
                    if ($i > 0) {
                        $pointsCounter += $fixedAmount + ($stepAmount * $i);
                        $i--;
                    }
                    $pointsCounter += $item['points'];
                    $key = strtolower($item['username']);
                    if (isset($rewards[$key])) {
                        $rewards[$key] += $item['points'];
                    } else {
                        $accountCounter++;
                        $rewards[$key] = $item['points'];
                    }
                }
                $insertTempValues = [];
                $i = $top;
                foreach ($rewards as $key => $value) {
                    if ($i > 0) {
                        $value += $fixedAmount + ($stepAmount * $i);
                        $i--;
                    }
                    $insertTempValues[] = "('$key', $value)";
                }
                $query = "INSERT INTO temp_pvp_rewards (`account`, `points`) VALUES " . implode(', ', $insertTempValues);
                $wpdb->query($query);

                $query = "UPDATE `{$wpdb->prefix}usermeta` um
                    LEFT JOIN `{$wpdb->prefix}users` u ON u.`ID` = um.user_id
                    LEFT JOIN temp_pvp_rewards t ON t.account = u.user_login
                    SET `meta_value` = CAST(`meta_value` AS INT) + t.`points`
                    WHERE u.`ID` IS NOT NULL
                    AND t.`points` IS NOT NULL
                    AND um.meta_key = '$mycredTokenName'";
                $wpdb->query($query);

                $query = "INSERT INTO `{$wpdb->prefix}usermeta` (`user_id`, `meta_key`, `meta_value`)
                        SELECT u.`ID`, '$mycredTokenName', t.`points`
                        FROM `{$wpdb->prefix}users` u
                        LEFT JOIN temp_pvp_rewards t ON t.account = u.user_login
                        WHERE t.`points` IS NOT NULL
                        AND u.`ID` NOT IN (SELECT `user_id` FROM `{$wpdb->prefix}usermeta` WHERE meta_key = '$mycredTokenName')";
                $wpdb->query($query);
            }
            ?>
            <div class="updated"><p><strong>Rewards sent to a total of <?php echo $accountCounter; ?> accounts and a total of
            <?php $formattedPoints = number_format(
                $pointsCounter,
                $myCredConfs['format']['decimals'],
                $myCredConfs['format']['separators']['decimal'],
                $myCredConfs['format']['separators']['thousand']
            );
                $pointName = $pointsCounter == 1 ? $myCredConfs['name']['singular'] : $myCredConfs['name']['plural'];
                echo $formattedPoints . " " . $pointName ?> were given.</strong></p></div>
            <?php
        } elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (isset($_GET["amount"])) {
                $amount = (int) $_GET['amount'];
            }
            if (isset($_GET["is_winner"])) {
                $isWinner = (int) $_GET['is_winner'];
            }
            if (isset($_GET["bracket"])) {
                if ((int) $_GET['bracket'] != 0) {
                    $bracket = $_GET['bracket'];
                    $bracketAnd = 'AND bracket_id = ' . $_GET['bracket'];
                }
            }
            if (isset($_GET["month"])) {
                $month = (int) $_GET['month'];
            }
            if (isset($_GET["year"])) {
                $year = (int) $_GET['year'];
            }
            if (isset($_GET["top"])) {
                $top = (int) $_GET['top'];
            }
            if (isset($_GET["fixed_amount"])) {
                $fixedAmount = (int) $_GET['fixed_amount'];
            }
            if (isset($_GET["step_amount"])) {
                $stepAmount = (int) $_GET['step_amount'];
            }
            $query = "SELECT
                count(character_guid) total_battle,
                characters.name as character_name,
                count(character_guid) * $amount AS points,
                account.username
            FROM pvpstats_players
            INNER JOIN pvpstats_battlegrounds ON pvpstats_players.battleground_id = pvpstats_battlegrounds.id
            INNER JOIN characters ON pvpstats_players.character_guid = characters.guid
            INNER JOIN `$authDbName`.account AS account ON characters.account = account.id
            WHERE characters.deleteDate IS NULL
                AND pvpstats_players.winner = $isWinner
                $bracketAnd
                AND MONTH(date) = $month
                AND YEAR(date) = $year
            GROUP BY character_guid
            ORDER BY total_battle DESC
            LIMIT 10";

            $connection = ACoreServices::I()->getCharactersMgr()->getConnection();
            $result = $connection->query($query)->fetchAll();
        }

        echo $this->getView()->getPvpRewardsRender(
            $amount,
            $isWinner,
            $bracket,
            $month,
            $year,
            $top,
            $fixedAmount,
            $stepAmount,
            $result
        );
    }

    /**
     *
     * @return SettingsView
     */
    public function getView() {
        return $this->view;
    }

    /**
     *
     * @return SettingsModel
     */
    public function getModel() {
        return $this->model;
    }

}
