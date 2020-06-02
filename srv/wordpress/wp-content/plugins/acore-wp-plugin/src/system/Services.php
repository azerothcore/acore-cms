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
        $this->mgrAuth = $this->getKernel()->getContainer()->get("auth_db.auth_db_mgr");
        $this->mgrAuth->createAuthEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_auth_host,
            'port' => Opts::I()->acore_db_auth_port,
            'dbname' => Opts::I()->acore_db_auth_name,
            'user' => Opts::I()->acore_db_auth_user,
            'password' => Opts::I()->acore_db_auth_pass,
            'charset' => 'UTF8',
        ));
        $this->emAuth = $this->mgrAuth->getAuthEm($this->realmAlias);

        $this->mgrChar = $this->getKernel()->getContainer()->get("char_db.char_db_mgr");
        $this->mgrChar->createCharEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_char_host,
            'port' => Opts::I()->acore_db_char_port,
            'dbname' => Opts::I()->acore_db_char_name,
            'user' => Opts::I()->acore_db_char_user,
            'password' => Opts::I()->acore_db_char_pass,
            'charset' => 'UTF8',
        ));
        $this->emChar = $this->mgrChar->getCharEm($this->realmAlias);

        $this->mgrWorld = $this->getKernel()->getContainer()->get("world_db.world_db_mgr");
        $this->mgrWorld->createWorldEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_world_host,
            'port' => Opts::I()->acore_db_world_port,
            'dbname' => Opts::I()->acore_db_world_name,
            'user' => Opts::I()->acore_db_world_user,
            'password' => Opts::I()->acore_db_world_pass,
            'charset' => 'UTF8',
        ));
        $this->emWorld = $this->mgrWorld->getWorldEm($this->realmAlias);

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
        $inst->getAccountMgr();
        //$acc = $inst->getAccountRepo();
        $inst->getAccountSoap();
        $inst->getCharactersMgr();

        //$char = $inst->getCharactersRepo();
        $inst->getCharactersSoap();
        $inst->getGameMailSoap();
        $soap = $inst->getServerSoap();

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
    public function getAccountMgr()
    {
        return $this->mgrAuth->getAuthEm($this->realmAlias);
    }

    /**
     * 
     * @return \ACore\Character\Services\CharacterMgr
     */
    public function getCharactersMgr()
    {
        return $this->mgrChar->getCharEm($this->realmAlias);
    }

    /**
     * 
     * @return \ACore\Account\Repository\AccountRepository
     */
    public function getAccountRepo()
    {
        return $this->getKernel()->getContainer()->get("account.account_mgr")->getAccountRepo($this->emAuth);
    }

    /**
     * 
     * @return \ACore\Account\Repository\AccountBannedRepository
     */
    public function getAccountBannedRepo()
    {
        return $this->getKernel()->getContainer()->get("account.account_mgr")->getAccountBannedRepo($this->emAuth);
    }

    /**
     * 
     * @return \ACore\Character\Repository\CharacterRepository
     */
    public function getCharactersRepo()
    {
        return $this->getKernel()->getContainer()->get("character.character_mgr")->getCharacterRepo($this->emChar);
    }

    /**
     * 
     * @return \ACore\Character\Repository\CharacterBannedRepository
     */
    public function getCharactersBannedRepo()
    {
        return $this->getKernel()->getContainer()->get("character.character_mgr")->getCharacterBannedRepo($this->emChar);
    }

    /**
     * 
     * @return \ACore\Account\Services\AccountSoapMgr
     */
    public function getAccountSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("account.account_soap_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Character\Services\CharacterSoapMgr
     */
    public function getCharactersSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("character.character_soap_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     * 
     * @return \ACore\GameMail\Services\MailMgr
     */
    public function getGameMailSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("game_mail.mail_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Server\Services\ServerSoapMgr
     */
    public function getServerSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("server.server_soap_mgr");
        $mgr->configure($this->soapParams);
        return $mgr;
    }
}


add_action('init', function () {
    //Services::basicWorkingTests();
    //die();
});
