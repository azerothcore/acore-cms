<?php

namespace ACore\Components\UserPanel;

use ACore\Manager\Opts;
use ACore\Manager\ACoreServices;
use ACore\Components\UserPanel\UserView;

class UserController {

    /**
     *
     * @var UserView
     */
    private $view;

    public function __construct() {
        $this->view = new UserView($this);
    }

    public function showRafProgress() {
        try {
            $acServices = ACoreServices::I();
        } catch (\Exception $e) {
            wp_die(__($e->getMessage()));
        }
        $errorMessages = [];
        $user = wp_get_current_user();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $maxRecruitDatetime = (new \DateTime($user->get("user_registered")))->modify('+7days');

            if ($maxRecruitDatetime < (new \DateTime())) {
                $errorMessages[] = "You can't be recruited by a friend, the 7 days limit has passed.";
            } else {
                if (!isset($_POST["recruited"])) {
                    $errorMessages[] = "No recruiter value sent.";
                } else {
                    $recruiterCode = $_POST["recruited"];
                    $existingRecruiterId = $acServices->getUserNameByUserId($recruiterCode);
                    $newRecruitId = $acServices->getAcoreAccountId();

                    if (!$newRecruitId || !$existingRecruiterId) {
                        $errorMessages[] = "Recruiter id or user id not found, please try again or contact a staff member.";
                    }

                    if ($recruiterCode == $newRecruitId) {
                        $errorMessages[] = "You can't recruit yourself.";
                    }

                    if (Opts::I()->eluna_raf_config['check_ip'] === '1') {
                        $userIp = $acServices->getAcoreAccountLastIp();
                        $activeUserIp = "";
                        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
                            // check ip from share internet
                            $activeUserIp = $_SERVER['HTTP_CLIENT_IP'];
                        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                            // to check ip is pass from proxy
                            $activeUserIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
                        } else {
                            // use default remote ip
                            $activeUserIp = $_SERVER['REMOTE_ADDR'];
                        }

                        $activeUserIp = apply_filters( 'wpb_get_ip', $activeUserIp );
                        $recruiterIp = $acServices->getAcoreAccountLastIpById($recruiterCode);
                        if (isset($userIp) && isset($recruiterIp)) {
                            if ($userIp != '127.0.0.1' && $recruiterIp != '127.0.0.1' && ($userIp == $recruiterIp || $activeUserIp == $recruiterIp)) {
                                $errorMessages[] = "You can't be recruited by a player with your same IP.";
                            }
                        }
                    }

                    if (count($errorMessages) == 0) {
                        $soap = ACoreServices::I()->getServerSoap();
                        $res = $soap->serverInfo();

                        if ($res instanceof \Exception) {
                            $errorMessages[] = "The server seems to be offline, try again later!";
                        } else {
                            $res = $soap->executeCommand("bindraf $newRecruitId $recruiterCode");
                            if ($res instanceof \Exception) {
                                $errorMessages[] = "An error ocurred while binding accounts. Please try again later.";
                            }
                        }

                    }
                }

            }
            if (count($errorMessages) > 0) {
                ?><div class="notice notice-error"><p><?= implode(" ", $errorMessages) ?></p></div><?php
            }
        }

        $accId = $acServices->getAcoreAccountId();
        if (!isset($accId)) {
            wp_die("<div class=\"notice notice-error\"><p>An error ocurred while loading your account information, please try again later. If this errors continues, please ask for support.</p></div>");
        }
        $query = "SELECT `account_id`, `recruiter_account`, `time_stamp`, `ip_abuse_counter`, `kick_counter`
            FROM `recruit_a_friend_links`
            WHERE `account_id` = $accId
        ";
        $conn = $acServices->getElunaMgr()->getConnection();
        $queryResult = $conn->executeQuery($query);
        $rafPersonalInfo = $queryResult->fetchAssociative();

        $query = "SELECT COALESCE(`reward_level`, 0) as reward_level
            FROM `recruit_a_friend_rewards`
            WHERE `recruiter_account` = $accId
        ";
        $conn = $acServices->getElunaMgr()->getConnection();
        $queryResult = $conn->executeQuery($query);
        $rafPersonalProgress = $queryResult->fetchAssociative();

        if (!isset($rafPersonalProgress['reward_level'])) {
            $rafPersonalProgress = ['reward_level' => 0];
        }

        $query = "SELECT `account_id`, `recruiter_account`, `time_stamp`, `ip_abuse_counter`, `kick_counter`
            FROM `recruit_a_friend_links`
            WHERE `recruiter_account` = $accId
        ";
        $queryResult = $conn->executeQuery($query);
        $rafRecruitedInfo = $queryResult->fetchAllAssociative();

        echo $this->getView()->getRafProgressRender($rafPersonalInfo, $rafPersonalProgress, $rafRecruitedInfo);
    }

    public function showItemRestorationPage() {
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $this->saveCharacterOrder();
            ?>
            <div class="updated"><p><strong>Character settings succesfully saved.</strong></p></div>
            <?php
        }

        $account = ACoreServices::I()->getAcoreAccountId();
        $conn = ACoreServices::I()->getCharacterEm()->getConnection();
        $queryResult = $conn->executeQuery(
            "   SELECT `guid`, `name`, `order`
                FROM `characters`
                WHERE `characters`.`deleteDate` IS NULL AND `account` = $account
                ORDER BY `order`, `guid`
            "
        );

        echo $this->getView()->getItemRestorationRender($queryResult->fetchAllAssociative());
    }

    /**
     *
     * @return UserView
     */
    public function getView() {
        return $this->view;
    }

    /**
     *
     * @return UserModel
     */
    public function getModel() {
        return $this->model;
    }

}
