<?php

namespace ACore;

require_once 'User.view.php';

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
        $user = wp_get_current_user();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $maxRecruitDatetime = (new \DateTime($user->get("user_registered")))->modify('+7days');
            if ($maxRecruitDatetime >= (new \DateTime())) {
                ?><div class="notice notice-error">
                    <p>You can't be recruited by a friend, the 7 days limit are passed.</p>
                </div>
                <?php
            } else {
                if (!isset($_POST["recruited"])) {
                    wp_die('<div class="notice notice-error"><p>No recruiter value sent.</p></div>');
                }
                $recruiterCode = $_POST["recruited"];
                $existingRecruiterId = $acServices->getUserNameByUserId($recruiterCode);
                $newRecruitId = $acServices->getAcoreAccountId();
                if (!$newRecruitId || !$existingRecruiterId) {
                    wp_die('<div class="notice notice-error"><p>Recruiter id or user id not found, please try again or contact a staff member.</p></div>');
                }
                $soap = ACoreServices::I()->getServerSoap();
                $res = $soap->serverInfo();
                if ($res instanceof \Exception) {
                    wp_die('<div class="notice notice-error"><p>Sorry, the server seems to be offline, try again later!</p></div>');
                }
                $res = $soap->executeCommand("bindraf $newRecruitId $recruiterCode");
                ?><div class="notice notice-info">
                    <p><?php echo $res; ?></p>
                </div>
                <?php
            }
        }

        $accId = $acServices->getAcoreAccountId();
        $query = "SELECT `account_id`, `recruiter_account`, `time_stamp`, `ip_abuse_counter`, `kick_counter`
            FROM `recruit_a_friend_links`
            WHERE `account_id` = $accId
        ";
        $conn = $acServices->getDatabaseMgr()->getConnection();
        $stmt = $conn->query($query);
        $rafPersonalInfo = $stmt->fetch();

        $query = "SELECT COALESCE(`reward_level`, '0') as reward_level
            FROM `recruit_a_friend_rewards`
            WHERE `recruiter_account` = $accId
        ";
        $conn = $acServices->getDatabaseMgr()->getConnection();
        $stmt = $conn->query($query);
        $rafPersonalProgress = $stmt->fetch();

        $query = "SELECT `account_id`, `recruiter_account`, `time_stamp`, `ip_abuse_counter`, `kick_counter`
            FROM `recruit_a_friend_links`
            WHERE `recruiter_account` = $accId
        ";
        $stmt = $conn->query($query);
        $rafRecruitedInfo = $stmt->fetchAll();

        echo $this->getView()->getRafProgressRender($rafPersonalInfo, $rafPersonalProgress, $rafRecruitedInfo);
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
