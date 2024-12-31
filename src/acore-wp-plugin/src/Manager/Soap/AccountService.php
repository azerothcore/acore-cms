<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class AccountService {

    use AcoreSoapTrait;

    /**
     *
     * @param type $name
     * @param type $password
     * @param type $email
     * @param type $addon
     * @return \Exception|boolean
     */
    public function createAccountFull($name, $password, $email, $addon) {

        $res = $this->createAccount($name, $password);

        if ($res instanceof \Exception)
            return $res;

        $res = $this->setAccountRegMail($name, $email);

        if ($res instanceof \Exception)
            return $res;

        $res = $this->setAccountEmail($name, $email);

        if ($res instanceof \Exception)
            return $res;

        $res = $this->setAccountAddon($name, $addon);

        if ($res instanceof \Exception)
            return $res;

        return true;
    }

    public function createAccount($name, $password) {
        return $this->executeCommand('account create ' . $name . ' ' . $password);
    }

    /**
     * This is a static mail that won't change
     * @param type $username
     * @param type $email
     * @return type
     */
    public function setAccountRegMail($username, $email) {
        $email = strtolower($email);
        return $this->executeCommand('account set sec regmail ' . $username . ' ' . $email . ' ' . $email);
    }

    public function banAccount($username, $bantime, $reason) {
        return $this->executeCommand('ban account ' . $username . ' ' . $bantime . ' ' . $reason);
    }

    public function unbanAccount($username) {
        return $this->executeCommand('unban account ' . $username);
    }

    public function setAccountEmail($username, $email) {
        $email = strtolower($email);
        return $this->executeCommand('account set sec email ' . $username . ' ' . $email . ' ' . $email);
    }

    public function setAccountPassword($username, $pass) {
        return $this->executeCommand('account set password ' . $username . ' ' . $pass . ' ' . $pass);
    }

    public function setAccountAddon($username, $addon) {
        return $this->executeCommand('account set addon ' . $username . ' ' . $addon);
    }

    public function deleteAccount($username) {
        return $this->executeCommand('account delete ' . $username);
    }

    // CarbonCopy tickets - https://github.com/55Honey/Acore_CarbonCopy/
    public function addCCTickets($accoutName, $quantity) {
        return $this->executeCommand("CCACCOUNTTICKETS $accoutName $quantity");
    }

}
