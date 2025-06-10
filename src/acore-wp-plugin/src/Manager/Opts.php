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
    public $eluna_raf_config=["check_ip" => '0'];
    public $acore_item_restoration="";
    public $acore_name_unlock_thresholds = [
        [5, 30], // level < 5 -> 30 days
        [30, 90], // level < 30 -> 90 days
        [60, 180], // level < 60 -> 180 days
        [81, 360], // else, 360 days
    ];
    public $acore_name_unlock_allowed_banned_names_table="";

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
