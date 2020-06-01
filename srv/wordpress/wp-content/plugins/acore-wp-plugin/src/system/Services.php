<?php

namespace ACore;

use ACore\Defines\Common;
use ACore\Opts;

class Services
{

    private static $instance;

    /**
     *
     * @var type 
     */
    private $kernel;

    public function __construct()
    {
        Opts::I()->loadFromDb();

        $this->kernel = require_once ACORE_PATH_PLG . "/src/core/app.php";
        $this->kernel->boot();
        $this->realmAlias = Opts::I()->acore_realm_alias;
        $mgrAuth = $this->getKernel()->getContainer()->get("auth_db.auth_db_mgr");
        $mgrAuth->createAuthEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_auth_host,
            'port' => Opts::I()->acore_db_auth_port,
            'dbname' => Opts::I()->acore_db_auth_name,
            'user' => Opts::I()->acore_db_auth_user,
            'password' => Opts::I()->acore_db_auth_pass,
            'charset' => 'UTF8',
        ));
        $mgrChar = $this->getKernel()->getContainer()->get("char_db.char_db_mgr");
        $mgrChar->createCharEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_char_host,
            'port' => Opts::I()->acore_db_char_port,
            'dbname' => Opts::I()->acore_db_char_name,
            'user' => Opts::I()->acore_db_char_user,
            'password' => Opts::I()->acore_db_char_pass,
            'charset' => 'UTF8',
        ));
        $mgrWorld = $this->getKernel()->getContainer()->get("world_db.world_db_mgr");
        $mgrWorld->createWorldEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_world_host,
            'port' => Opts::I()->acore_db_world_port,
            'dbname' => Opts::I()->acore_db_world_name,
            'user' => Opts::I()->acore_db_world_user,
            'password' => Opts::I()->acore_db_world_pass,
            'charset' => 'UTF8',
        ));

        $this->soapParams = array(
            "host" => Opts::I()->acore_soap_host,
            "port" => Opts::I()->acore_soap_port,
            "protocol" => "http",
            "user" => Opts::I()->acore_soap_user,
            "pass" => Opts::I()->acore_soap_pass,
        );
    }

    public static function basicWorkingTests()
    {
        $inst = static::I();
        echo Common::EXPANSION_WOTLK;
        $inst->getAzthAccountMgr();
        //$acc = $inst->getAzthAccountRepo();
        $inst->getAzthAccountSoap();
        $inst->getAzthCharactersMgr();

        //$char = $inst->getAzthCharactersRepo();
        $inst->getAzthCharactersSoap();
        $inst->getAzthGameMailSoap();
        $soap = $inst->getAzthServerSoap();

        echo $soap->serverInfo();
        echo "<br>";
        die();
    }

    /**
     * 
     * @return \AppKernel
     */
    function getKernel()
    {
        return $this->kernel;
    }

    /**
     * 
     * @return Services
     */
    public static function I()
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * 
     * @return \ACore\Account\Services\AccountMgr
     */
    public function getAzthAccountMgr()
    {
        $mgr = $this->getKernel()->getContainer()->get("account.account_mgr");
        $mgr->getAuthEm($this->realmAlias); // configure db connection
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Character\Services\CharacterMgr
     */
    public function getAzthCharactersMgr()
    {
        $mgr = $this->getKernel()->getContainer()->get("character.character_mgr");
        $mgr->getCharEm($this->realmAlias); // configure db connection
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Account\Repository\AccountRepository
     */
    public function getAzthAccountRepo()
    {
        return $this->getKernel()->getContainer()->get("account.account_mgr")->getAccountRepo($this->realmAlias);
    }

    /**
     * 
     * @return \ACore\Account\Repository\AccountBannedRepository
     */
    public function getAzthAccountBannedRepo()
    {
        return $this->getKernel()->getContainer()->get("account.account_mgr")->getAccountBannedRepo($this->realmAlias);
    }

    /**
     * 
     * @return \ACore\Character\Repository\CharacterRepository
     */
    public function getAzthCharactersRepo()
    {
        return $this->getKernel()->getContainer()->get("character.character_mgr")->getCharacterRepo($this->realmAlias);
    }

    /**
     * 
     * @return \ACore\Character\Repository\CharacterBannedRepository
     */
    public function getAzthCharactersBannedRepo()
    {
        return $this->getKernel()->getContainer()->get("character.character_mgr")->getCharacterBannedRepo($this->realmAlias);
    }

    /**
     * 
     * @return \ACore\Account\Services\AccountSoapMgr
     */
    public function getAzthAccountSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("account.account_soap_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Character\Services\CharacterSoapMgr
     */
    public function getAzthCharactersSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("character.character_soap_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     * 
     * @return \ACore\GameMail\Services\MailMgr
     */
    public function getAzthGameMailSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("game_mail.mail_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Server\Services\ServerSoapMgr
     */
    public function getAzthServerSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("server.server_soap_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }
}


add_action('init', function() {
    //Services::basicWorkingTests();
    //die();
});
