<?php

namespace ACore\Account\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use ACore\System\Utils\ApiController;

/**
 *
 * @Route("/{_prefix}/account/", defaults = { "_prefix" = "def" })
 */
class AccountController extends ApiController {

    /**
     *
     * @Route("id/{id}", name="account_single")
     */
    public function getIdAction(Request $req, $id) {
        //$res = $this->getRepo($req)->findOneById($id);
        //return $this->serialize($res);
        return NULL;
    }

    /**
     * 
     * @param Request $req
     * @return \ACore\Account\Repository\AccountRepository
     */
    protected function getRepo(Request $req) {
        return parent::get("account.account_mgr")->getAccountRepo($req->get("_prefix"));
    }

}
