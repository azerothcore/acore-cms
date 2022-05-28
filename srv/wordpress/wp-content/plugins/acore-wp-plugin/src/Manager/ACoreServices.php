<?php

namespace ACore\Manager;

use ACore\Manager\Common;
use ACore\Manager\Opts;
use ACore\Manager\AcoreConnector\AcoreManager;
use ACore\Manager\Auth\AuthManager;
use ACore\Manager\Character\CharacterManager;
use ACore\Manager\Soap\AcoreSoap;
use ACore\Manager\Soap\AccountService;
use ACore\Manager\Soap\CharacterService;
use ACore\Manager\Soap\GuildService;
use ACore\Manager\Soap\MailService;
use ACore\Manager\Soap\TransmogService;
use ACore\Manager\Soap\ServerService;
use ACore\Manager\World\WorldManager;

class ACoreServices
{

    private static $instance;

    public function __construct()
    {
        Opts::I()->loadFromDb();

        $this->realmAlias = Opts::I()->acore_realm_alias;
        $this->mgrAuth = new AuthManager();
        $this->mgrAuth->setAcoreManager(new AcoreManager());
        $this->mgrAuth->createAcoreEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_auth_host,
            'port' => Opts::I()->acore_db_auth_port,
            'dbname' => Opts::I()->acore_db_auth_name,
            'user' => Opts::I()->acore_db_auth_user,
            'password' => Opts::I()->acore_db_auth_pass,
            'charset' => 'UTF8',
        ));
        $this->emAuth = $this->mgrAuth->getAcoreEm($this->realmAlias);

        $this->mgrChar = new CharacterManager();
        $this->mgrChar->setAcoreManager(new AcoreManager());
        $this->mgrChar->createAcoreEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_char_host,
            'port' => Opts::I()->acore_db_char_port,
            'dbname' => Opts::I()->acore_db_char_name,
            'user' => Opts::I()->acore_db_char_user,
            'password' => Opts::I()->acore_db_char_pass,
            'charset' => 'UTF8',
        ));
        $this->emChar = $this->mgrChar->getAcoreEm($this->realmAlias);

        $this->mgrWorld = new WorldManager();
        $this->mgrWorld->setAcoreManager(new AcoreManager());
        $this->mgrWorld->createAcoreEm($this->realmAlias, array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_world_host,
            'port' => Opts::I()->acore_db_world_port,
            'dbname' => Opts::I()->acore_db_world_name,
            'user' => Opts::I()->acore_db_world_user,
            'password' => Opts::I()->acore_db_world_pass,
            'charset' => 'UTF8',
        ));
        $this->emWorld = $this->mgrWorld->getAcoreEm($this->realmAlias);

        $this->mgrEluna = new AcoreManager();
        $this->mgrEluna->configureEntities([]);
        $this->mgrEluna->createEm(array(
            'driver' => 'pdo_mysql',
            'host' => Opts::I()->acore_db_eluna_host,
            'port' => Opts::I()->acore_db_eluna_port,
            'dbname' => Opts::I()->acore_db_eluna_name,
            'user' => Opts::I()->acore_db_eluna_user,
            'password' => Opts::I()->acore_db_eluna_pass,
            'charset' => 'UTF8',
        ));
        $this->emEluna = $this->mgrEluna->getEm();

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
        $inst->getAccountEm();
        //$acc = $inst->getAccountRepo();
        $inst->getAccountSoap();
        $inst->getCharacterEm();

        //$char = $inst->getCharactersRepo();
        $inst->getCharactersSoap();
        $inst->getGameMailSoap();
        $inst->getGuildSoap();
        $soap = $inst->getServerSoap();

        echo $soap->serverInfo();
        echo "<br>";
        die();
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
     * @return \Doctrine\ORM\EntityManager
     */
    public function getAccountEm()
    {
        return $this->emAuth;
    }

    /**
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getCharacterEm()
    {
        return $this->emChar;
    }

    /**
     *
     * @return string
     */
    public function getCharName($charId, $deleted = false)
    {
        $char = $this->getCharactersRepo()->findOneByGuid($charId);

        if (!$char || (!$char->getName() && !$deleted) || ($deleted && !$char->getDeletedName())) {
            // even name is empty ( deleted char )
            $current_user = wp_get_current_user();
            throw new \Exception("Char $charId doesn't exists! account: " . $current_user->user_login);
        }

        return $deleted ? $char->getDeletedName() : $char->getName();
    }

    /**
     *
     * @return ACore\Manager\Auth\Repository\AccountRepository
     */
    public function getAccountRepo()
    {
        return $this->emAuth->getRepository('ACore\Manager\Auth\Entity\AccountEntity');
    }

    /**
     *
     * @return ACore\Manager\Auth\Repository\AccountAccessRepository
     */
    public function getAccountAccessRepo()
    {
        return $this->emAuth->getRepository('ACore\Manager\Auth\Entity\AccountAccessEntity');
    }

    /**
     *
     * @return ACore\Manager\Auth\Repository\AccountBannedRepository
     */
    public function getAccountBannedRepo()
    {
        return $this->emAuth->getRepository('ACore\Manager\Auth\Entity\AccountBannedEntity');
    }

    /**
     *
     * @return ACore\Manager\Character\Repository\CharacterRepository
     */
    public function getCharactersRepo()
    {
        return $this->emChar->getRepository('ACore\Manager\Character\Entity\CharacterEntity');
    }

    /**
     *
     * @return ACore\Manager\Character\Repository\CharacterBannedRepository
     */
    public function getCharactersBannedRepo()
    {
        return $this->emChar->getRepository('ACore\Manager\Character\Entity\CharacterBannedEntity');
    }

    /**
     *
     * @return ACore\Manager\Soap\AccountService
     */
    public function getAccountSoap()
    {
        $mgr = new AccountService();
        $mgr->setSoap(new AcoreSoap());
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     *
     * @return ACore\Manager\Soap\CharacterService
     */
    public function getCharactersSoap()
    {
        $mgr = new CharacterService();
        $mgr->setSoap(new AcoreSoap());
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     *
     * @return ACore\Manager\Soap\GuildService
     */
    public function getGuildSoap()
    {
        $mgr = new GuildService();
        $mgr->setSoap(new AcoreSoap());
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     *
     * @return ACore\Manager\Soap\MailService
     */
    public function getGameMailSoap()
    {
        $mgr = new MailService();
        $mgr->setSoap(new AcoreSoap());
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     *
     * @return ACore\Manager\Soap\TransmogService
     */
    public function getTransmogSoap()
    {
        $mgr = new TransmogService();
        $mgr->setSoap(new AcoreSoap());
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     *
     * @return ACore\Manager\Soap\ServerService
     */
    public function getServerSoap()
    {
        $mgr = new ServerService();
        $mgr->setSoap(new AcoreSoap());
        $mgr->configure($this->soapParams);
        return $mgr;
    }

    /**
     *
     * @return ACore\Manager\AcoreConnection\AcoreManager
     */
    public function getElunaMgr()
    {
        return $this->mgrEluna->getEm();
    }

    public function getAcoreAccountId() {
        $user = wp_get_current_user();
        $query = "SELECT `id`
            FROM `account`
            WHERE `username` = ?
        ";
        $conn = $this->getAccountEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $user->get("user_login"));
        $res = $stmt->executeQuery();
        return $res->fetchOne();
    }

    public function getAcoreAccountLastIp() {
        $user = wp_get_current_user();
        $query = "SELECT `last_ip`
            FROM `account`
            WHERE `username` = ?
        ";
        $conn = $this->getAccountEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $user->get("user_login"));
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        return $res->fetchOne();
    }

    public function getAcoreAccountTotaltime($beauty=false) {
        $user = wp_get_current_user();
        $query = "SELECT `totaltime`
            FROM `account`
            WHERE `username` = ?
        ";
        $conn = $this->getAccountEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $user->get("user_login"));
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        $row = $res->fetchAssociative();
        $total = isset($row["totaltime"]) ? (int) $row["totaltime"] : 0;
        if ($beauty) {
            $days = floor($total / (3600 * 24));
            $hours = floor(($total / 3600) % 24);
            $minutes = floor(($total % 3600) / 60);
            if ($days > 0) {
                return "$days day(s), $hours hour(s) and $minutes minute(s)";
            } elseif ($hours > 0) {
                return "$hours hour(s) and $minutes minute(s)";
            } else {
                return "$minutes minute(s)";
            }
        }
        return $total;
    }

    public function getAcoreAccountLastIpById($id) {
        $query = "SELECT `last_ip`
            FROM `account`
            WHERE `id` = ?
        ";
        $conn = $this->getAccountEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $id);
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        return $res->fetchOne();
    }

    public function getAcoreAccountIdByName($username) {
        $query = "SELECT `id`
            FROM `account`
            WHERE `username` = ?
        ";
        $conn = $this->getAccountEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $username);
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        return $res->fetchOne();
    }

    public function getUserNameByUserId($usedId) {
        $query = "SELECT `username`
            FROM `account`
            WHERE `id` = ?
        ";
        $conn = $this->getAccountEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $usedId);
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        return $res->fetchOne();
    }

    public function getRestorableItemsByCharacter($character) {
        $query = "SELECT `Id`, `ItemEntry`
            FROM `recovery_item`
            WHERE `Guid` = ?
        ";
        $conn = $this->getCharacterEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $character);
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        return $res->fetchAllAssociative();
    }

    /** 
     *  @param int $guildLeaderGuid
     *  @return string
     *  Returns the guild name searching by guild leader.
    */
    public function getGuildNameByLeader($guildLeaderGuid) {
        $query = "SELECT `name`
            FROM `guild`
            WHERE `leaderguid` = ?
        ";
        $conn = $this->getCharacterEm()->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $guildLeaderGuid);
        $stmt->executeQuery();
        $res = $stmt->executeQuery();
        return $res->fetchOne();
    }
}


add_action('init', function () {
    //ACoreServices::basicWorkingTests();
    //die();
});
