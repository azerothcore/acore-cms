<?php

namespace ACore\Manager;

class Opts {

    private static $instance=null;

    public $acore_plg_name="AzerothCore WP Integration";
    public $acore_org_name="ACore";
    public $acore_org_alias="acore";
    public $acore_page_alias="wp-acore";
    public $acore_realm_alias="AzerothCore";
    public $acore_soap_host="";
    public $acore_soap_port="";
    public $acore_soap_user="";
    public $acore_soap_pass="";
    public $acore_db_char_host="";
    public $acore_db_char_port="";
    public $acore_db_char_user="";
    public $acore_db_char_pass="";
    public $acore_db_char_name="";
    public $acore_db_auth_host="";
    public $acore_db_auth_port="";
    public $acore_db_auth_user="";
    public $acore_db_auth_pass="";
    public $acore_db_auth_name="";
    public $acore_db_world_host="";
    public $acore_db_world_port="";
    public $acore_db_world_user="";
    public $acore_db_world_pass="";
    public $acore_db_world_name="";
    public $acore_db_eluna_host="";
    public $acore_db_eluna_port="";
    public $acore_db_eluna_user="";
    public $acore_db_eluna_pass="";
    public $acore_db_eluna_name="";
    public $eluna_recruit_a_friend="";
    public $eluna_raf_config=["check_ip" => '0', "end_raf_on_same_ip" => '1'];
    public $acore_resurrection_scroll="";
    public $acore_resurrection_scroll_days_inactive="180";
    public $acore_item_restoration="";
    public $acore_smartstone_enabled="";
    public $acore_name_unlock_thresholds = [
        [5, 30], // level < 5 -> 30 days
        [30, 90], // level < 30 -> 90 days
        [60, 180], // level < 60 -> 180 days
        [81, 360], // else, 360 days
    ];
    public $acore_name_unlock_allowed_banned_names_table="";
    public $acore_punishment_info_enabled="0";
    public $acore_punishment_info_account_ban="1";
    public $acore_punishment_info_account_mute="1";
    public $acore_punishment_info_character_ban="1";
    public $acore_pdump_enabled="0";
    public $acore_pdump_log_enabled="1";
    public $acore_bug_report_url="https://github.com/azerothcore/acore-cms/issues/new";
    public $acore_pdump_cooldown_single="2592000";  // 1 month (30 days)
    public $acore_pdump_cooldown_all="7776000";     // 3 months (90 days)
    public $acore_pdump_subscription_enabled="0";
    public $acore_pdump_subscription_cooldowns=[];
    public $acore_pdump_rbac_enabled="0";
    // Minimum account security required to use PDUMP, like realmlist.allowedSecurityLevel. 0 = everyone (default).
    public $acore_pdump_allowed_sec_level="0";
    public $acore_pdump_single_enabled="1";
    public $acore_pdump_all_enabled="1";
    // Automatically block PDUMP when the realm's allowedSecurityLevel is >= 1 (maintenance / GM-only mode).
    public $acore_pdump_block_maintenance="1";
    // Master toggle for the minimum-requirements checks (playtime, account age, character level).
    public $acore_pdump_min_req_enabled="0";
    // Individual toggles for each minimum requirement check.
    public $acore_pdump_min_playtime_enabled="0";
    public $acore_pdump_min_acct_age_enabled="0";
    public $acore_pdump_min_char_level_enabled="0";
    // Minimum total account playtime in seconds (sum across all characters). 0 = no requirement.
    public $acore_pdump_min_playtime="0";
    // Minimum account age in seconds since registration. 0 = no requirement.
    public $acore_pdump_min_acct_age="0";
    // Minimum character level any character on the account must have reached. 0 = no requirement.
    public $acore_pdump_min_char_level="80";
    public $acore_pdump_rbac_cooldowns=[
        ['perm_id' => 195, 'perm_name' => 'Player',        'single' => 0, 'all' => 0, 'use_default' => 1],
        ['perm_id' => 194, 'perm_name' => 'Moderator',     'single' => 0, 'all' => 0, 'use_default' => 1],
        ['perm_id' => 193, 'perm_name' => 'Gamemaster',    'single' => 0, 'all' => 0, 'use_default' => 1],
        ['perm_id' => 192, 'perm_name' => 'Administrator', 'single' => 0, 'all' => 0, 'use_default' => 1],
    ];
    public $acore_pdump_contributor_enabled="0";
    public $acore_pdump_contributor_cooldowns=[
        ['level' => 1, 'name' => 'Bronze',   'single' => 0, 'all' => 0, 'use_default' => 1],
        ['level' => 2, 'name' => 'Silver',   'single' => 0, 'all' => 0, 'use_default' => 1],
        ['level' => 3, 'name' => 'Gold',     'single' => 0, 'all' => 0, 'use_default' => 1],
        ['level' => 4, 'name' => 'Platinum', 'single' => 0, 'all' => 0, 'use_default' => 1],
    ];
    public $acore_security_logging="0";
    public $acore_allow_old_passwords="0";
    public $acore_geoip_lookup="0";
    public $acore_totp_master_secret="";

    public function __get($property) {
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }

    public function __set($property, $value) {
        if (property_exists($this, $property)) {
            $this->$property = $value;
        }
    }

    public function loadFromArray($confs) {
        foreach ($confs as $conf => $value) {
            $this->$conf=$value; // variables variable ( created dynamically if not exists )
        }
    }

    public function loadFromDb() {
        $confs=$this->getConfs();
        foreach ($confs as $conf => $value) {
            $this->$conf=get_option($conf, $value); // variables variable ( created dynamically if not exists )
        }
    }

    private function __construct() {
        $this->loadFromDb();
    }

    /**
     * Singleton
     * @return Opts
     */
    public static function I() {
        if (!self::$instance) {
            self::$instance=new self();
        }

        return self::$instance;
    }

    public function getConfs() {
        return \get_object_vars($this);
    }

    public function getRealmAliasUri() {
        // remove html tags
        $clean = strip_tags($this->acore_realm_alias);
        // transliterate
        $clean = transliterator_transliterate('Any-Latin;Latin-ASCII;', $clean);
        // remove non-number and non-letter characters
        $clean = str_replace('--', '-', preg_replace('/[^a-z0-9-\_]/i', '', preg_replace(array(
            '/\s/',
            '/[^\w-\.\-]/'
        ), array(
            '_',
            ''
        ), $clean)));
        // replace '-' for '_'
        $clean = strtr($clean, array(
            '-' => '_'
        ));
        // remove double '__'
        $positionInString = stripos($clean, '__');
        while ($positionInString !== false) {
            $clean = str_replace('__', '_', $clean);
            $positionInString = stripos($clean, '__');
        }
        // remove '_' from the end and beginning of the string
        $clean = rtrim(ltrim($clean, '_'), '_');
        // lowercase the string
        return strtolower($clean);
    }
}
