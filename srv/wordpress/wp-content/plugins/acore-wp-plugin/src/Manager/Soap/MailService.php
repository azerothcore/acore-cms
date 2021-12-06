<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class MailService {

    use AcoreSoapTrait;

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
