<?php

namespace ACore\Creature\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use ACore\System\Utils\ApiController;

/**
 *
 * @Route("/{_prefix}/creature/template/", defaults = { "_prefix" = "def" })
 */
class CreatureTmplController extends ApiController {

    /**
     *
     * @Route("entry/{entry}", name="creature_template_single")
     */
    public function getEntryAction(Request $req, $entry) {
        $res = $this->getRepo($req)->findOneByEntry($entry);
        return $this->serialize($res);
    }
    
    /**
     *
     * @Route("maxlevel/{level}", name="creature_template_list_maxlevel")
     */
    public function getMaxLevelAction(Request $req, $level) {
        $res = $this->getRepo($req)->findByMaxlevel($level);
        return $this->serialize($res);
    }

    /**
     * 
     * @param Request $req
     * @return \ACore\Creature\Repository\CreatureTmplRepository
     */
    protected function getRepo(Request $req) {
        return parent::get("creature.creature_mgr")->getCreatureTmplRepo($req->get("_prefix"));
    }

}
