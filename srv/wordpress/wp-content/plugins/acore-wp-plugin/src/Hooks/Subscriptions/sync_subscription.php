<?php

use ACore\Manager\Opts;

add_filter('cron_schedules', 'example_add_cron_interval');
function example_add_cron_interval($schedules)
{
  $schedules['every_5_minutes'] = array(
    'interval' => 300,
    'display'  => esc_html__('Sync pmpro subscriptions with azerothcore'),
  );
  return $schedules;
}

function bl_cron_exec_sync_subs()
{
  global $wpdb;

  $db_credentials = array(
    'host' => Opts::I()->acore_db_auth_host,
    'port' => Opts::I()->acore_db_auth_port,
    'dbname' => Opts::I()->acore_db_auth_name,
    'user' => Opts::I()->acore_db_auth_user,
    'password' => Opts::I()->acore_db_auth_pass,
  );

  $acore_auth_db = new wpdb(
    $db_credentials['user'],
    $db_credentials['password'],
    $db_credentials['dbname'],
    $db_credentials['host'] . ':' . $db_credentials['port']
  );

  $create_table_subscriptions = "CREATE TABLE IF NOT EXISTS `acore_cms_subscriptions` (
    `account_name` VARCHAR(255) NOT NULL,
    `membership_level` INT NOT NULL
  );";
  $acore_auth_db->get_results($create_table_subscriptions);

  // get all active accounts and membership level
  $query = $wpdb->prepare(
    "SELECT user_id, wu.user_login AS AccountName, membership_level_id, status
    FROM " . $wpdb->prefix . "pmpro_subscriptions wpmu
    LEFT JOIN " . $wpdb->prefix . "users wu ON wpmu.user_id = wu.ID
    WHERE wpmu.status=\"active\";"
  );
  $subscriptions_accounts_rows = $wpdb->get_results($query);

  $all_active_accounts = '';
  $all_active_accounts_obj = array();
  foreach ($subscriptions_accounts_rows as $row) {
    $all_active_accounts .= '"' . $row->AccountName . '",';
    $all_active_accounts_obj[$row->AccountName] = $row;
  }
  $all_active_accounts = substr($all_active_accounts, 0, -1);


  // DELETE all the accounts that are not active
  $query_clean = $acore_auth_db->prepare(
    "DELETE FROM `acore_cms_subscriptions` WHERE `account_name` NOT IN (" . $all_active_accounts . ")"
  );
  $acore_auth_db->get_results($query_clean);


  // get current player membership in game
  $query = $acore_auth_db->prepare("SELECT `account_name`, `membership_level` FROM `acore_cms_subscriptions`;");
  $current_subscriptions_in_game_rows = $acore_auth_db->get_results($query);

  // UPDATE membership level if changed
  if ($current_subscriptions_in_game_rows != null) {
    foreach ($current_subscriptions_in_game_rows as $row) {
      if (array_key_exists($row->account_name, $all_active_accounts_obj)) {
        if ($all_active_accounts_obj[$row->account_name]->membership_level_id != $row->membership_level) {
          $query_update_membership_level = "UPDATE `acore_cms_subscriptions` SET `membership_level`= " . $all_active_accounts_obj[$row->account_name]->membership_level_id . " WHERE `account_name`=\"" . $row->account_name . "\";\n";
          $query = $acore_auth_db->prepare($query_update_membership_level);
          $acore_auth_db->get_results($query);
        }
        unset($all_active_accounts_obj[$row->account_name]);
      }
    }
  }

  // INSERT new accounts with membership if any
  $query_insert_membership_new = '';
  foreach ($all_active_accounts_obj as $row) {
    $query_insert_membership_new .= "('" . $row->AccountName . "', " . $row->membership_level_id . "),\n";
  }

  if ($query_insert_membership_new != '') {
    $query_insert_membership_new = "INSERT INTO `acore_cms_subscriptions` (`account_name`, `membership_level`) VALUES \n" . substr($query_insert_membership_new, 0, -2) . ";";
    $query = $acore_auth_db->prepare($query_insert_membership_new);
    $acore_auth_db->get_results($query);
  }
}

add_action('bl_cron_hook', 'bl_cron_exec_sync_subs');

if (!wp_next_scheduled('bl_cron_hook')) {
  wp_schedule_event(time(), 'every_5_minutes', 'bl_cron_hook');
}
