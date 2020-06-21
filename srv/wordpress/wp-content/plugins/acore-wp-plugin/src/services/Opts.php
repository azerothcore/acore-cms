<?php

namespace ACore;

class Opts {
    
    private static $instance=null;
    
    public $acore_plg_name="AzerothCore WP Integration";
    public $acore_org_name="ACore";
    public $acore_org_alias="acore";
    public $acore_page_alias="wp-acore";
    public $acore_realm_alias="";
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
}
