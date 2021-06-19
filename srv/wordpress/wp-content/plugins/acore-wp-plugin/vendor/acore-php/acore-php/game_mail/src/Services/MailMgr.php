<?php

namespace ACore\GameMail\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use \ACore\Soap\Utils\SoapTrait;

class MailMgr {

    use ContainerAwareTrait;
    use SoapTrait;

    public function sendItem($playerName, $subject, $message, $itemId, $stack) {
        $_message = addslashes($message);
        $_subject = addslashes($subject);
        $_itemId = intval($itemId);
        $_stack = intval($stack);
        return $this->executeCommand('.send items  ' . $playerName . ' "' . $_subject . '" "' . $_message . '" ' . $_itemId . ':' . $_stack);
    }

    public function sendMoney($playerName, $subject, $message, $money) {
        $_message = addslashes($message);
        $_subject = addslashes($subject);
        $money = intval($money);
        return $this->executeCommand('.send items  ' . $playerName . ' "' . $_subject . '" "' . $_message . '" ' . $money);
    }

    // requires https://github.com/55Honey/Acore_SendAndBind
    public function sendItemAndBind($guid, $message, $itemId, $stack) {
        $_message = addslashes($message);
        $_itemId = intval($itemId);
        $_stack = intval($stack);
        return $this->executeCommand('.senditemandbind ' . $guid . ' ' . $_itemId . ' ' . $_stack . ' ' . $_message);
    }
}
