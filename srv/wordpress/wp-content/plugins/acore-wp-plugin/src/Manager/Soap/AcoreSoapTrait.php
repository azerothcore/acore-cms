<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoap;

trait AcoreSoapTrait {

    /**
     *
     * @var AcoreSoap
     */
    protected $soapMgr;

    /**
     *
     * @return AcoreSoap
     */
    public function getSoap() {
        return $this->soap;
    }

    public function setSoap(AcoreSoap $soap) {
        $this->soap = $soap;
    }

    public function configure($params) {
        $this->soap->configure($params);
    }

    public function executeCommand($command) {
        return $this->getSoap()->executeCommand($command);
    }

}
