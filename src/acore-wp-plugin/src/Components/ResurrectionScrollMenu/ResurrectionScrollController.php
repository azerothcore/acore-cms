<?php

namespace ACore\Components\ResurrectionScrollMenu;

use ACore\Manager\ACoreServices;
use ACore\Manager\Opts;
use ACore\Components\ResurrectionScrollMenu\ResurrectionScrollView;

class ResurrectionScrollController
{
    private $view;

    public function __construct()
    {
        $this->view = new ResurrectionScrollView($this);
    }

    public function render()
    {
        $data = $this->getScrollData();
        echo $this->view->getRender($data);
    }

    private function getScrollData()
    {
        $accId = ACoreServices::I()->getAcoreAccountId();

        if (!isset($accId) || !is_numeric($accId)) {
            return null;
        }

        $conn = ACoreServices::I()->getCharacterEm()->getConnection();

        // Get scroll account data
        $scrollData = null;
        try {
            $query = "SELECT `AccountId`, `EndDate`, `Expired`
                FROM `mod_ress_scroll_accounts`
                WHERE `AccountId` = ?";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(1, $accId);
            $res = $stmt->executeQuery();
            $scrollData = $res->fetchAssociative();
        } catch (\Exception $e) {
            // Table may not exist if module is not installed
        }

        // Get most recent character logout time
        $lastLogout = null;
        try {
            $query = "SELECT MAX(`logout_time`) as `last_logout`
                FROM `characters`
                WHERE `account` = ? AND `deleteDate` IS NULL";
            $stmt = $conn->prepare($query);
            $stmt->bindValue(1, $accId);
            $res = $stmt->executeQuery();
            $row = $res->fetchAssociative();
            if ($row && $row['last_logout']) {
                $lastLogout = (int) $row['last_logout'];
            }
        } catch (\Exception $e) {
            // Ignore
        }

        $daysInactive = (int) Opts::I()->acore_resurrection_scroll_days_inactive;
        $isEligible = false;
        if ($lastLogout) {
            $daysSinceLogout = (int) ((time() - $lastLogout) / 86400);
            $isEligible = $daysSinceLogout >= $daysInactive;
        }

        return [
            'accountId' => $accId,
            'scrollData' => $scrollData,
            'lastLogout' => $lastLogout,
            'daysInactive' => $daysInactive,
            'isEligible' => $isEligible,
        ];
    }
}
