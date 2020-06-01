<?php

namespace ACore;

use ACore\Defines\Common;

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
        $this->kernel = require_once ACORE_PATH_PLG . "/src/core/app.php";
        $this->kernel->boot();
        $mgr = $this->getKernel()->getContainer()->get("auth_db.auth_db_mgr");
        $mgr->createAuthEm(array(
            'driver' => 'pdo_mysql',
            'host' => sOpts()->acore_db_auth_host,
            'port' => sOpts()->acore_db_auth_port,
            'dbname' => sOpts()->acore_db_auth_name,
            'user' => sOpts()->acore_db_auth_user,
            'password' => sOpts()->acore_db_auth_pass,
            'charset' => 'UTF8',
        ));
        $mgr = $this->getKernel()->getContainer()->get("char_db.char_db_mgr");
        $mgr->createCharEm(array(
            'driver' => 'pdo_mysql',
            'host' => sOpts()->acore_db_char_host,
            'port' => sOpts()->acore_db_char_port,
            'dbname' => sOpts()->acore_db_char_name,
            'user' => sOpts()->acore_db_char_user,
            'password' => sOpts()->acore_db_char_pass,
            'charset' => 'UTF8',
        ));
        $mgr = $this->getKernel()->getContainer()->get("world_db.world_db_mgr");
        $mgr->createWorldEm(array(
            'driver' => 'pdo_mysql',
            'host' => sOpts()->acore_db_world_host,
            'port' => sOpts()->acore_db_world_port,
            'dbname' => sOpts()->acore_db_world_name,
            'user' => sOpts()->acore_db_world_user,
            'password' => sOpts()->acore_db_world_pass,
            'charset' => 'UTF8',
        ));
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
        $mgr->getAuthEm("game"); // configure db connection
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Character\Services\CharacterMgr
     */
    public function getAzthCharactersMgr()
    {
        $mgr = $this->getKernel()->getContainer()->get("character.character_mgr");
        $mgr->getCharEm("game"); // configure db connection
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Account\Repository\AccountRepository
     */
    public function getAzthAccountRepo()
    {
        return $this->getKernel()->getContainer()->get("account.account_mgr")->getAccountRepo("game");
    }

    /**
     * 
     * @return \ACore\Account\Repository\AccountBannedRepository
     */
    public function getAzthAccountBannedRepo()
    {
        return $this->getKernel()->getContainer()->get("account.account_mgr")->getAccountBannedRepo("game");
    }

    /**
     * 
     * @return \ACore\Character\Repository\CharacterRepository
     */
    public function getAzthCharactersRepo()
    {
        return $this->getKernel()->getContainer()->get("character.character_mgr")->getCharacterRepo("game");
    }

    /**
     * 
     * @return \ACore\Character\Repository\CharacterBannedRepository
     */
    public function getAzthCharactersBannedRepo()
    {
        return $this->getKernel()->getContainer()->get("character.character_mgr")->getCharacterBannedRepo("game");
    }

    /**
     * 
     * @return \ACore\Account\Services\AccountSoapMgr
     */
    public function getAzthAccountSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("account.account_soap_mgr");
        $mgr->configure("game");
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Character\Services\CharacterSoapMgr
     */
    public function getAzthCharactersSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("character.character_soap_mgr");
        $mgr->configure("game");
        return $mgr;
    }

    /**
     * 
     * @return \ACore\GameMail\Services\MailMgr
     */
    public function getAzthGameMailSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("game_mail.mail_mgr");
        $mgr->configure("game");
        return $mgr;
    }

    /**
     * 
     * @return \ACore\Server\Services\ServerSoapMgr
     */
    public function getAzthServerSoap()
    {
        $mgr = $this->getKernel()->getContainer()->get("server.server_soap_mgr");
        $mgr->configure("game");
        return $mgr;
    }
}
/*
if ($_GET["apaw_test"]) {
    WoWSrv::basicWorkingTests();
    die();
}*/
