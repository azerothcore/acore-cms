<?php

namespace ACore\Soap\Utils;

use \ACore\Soap\Services\SoapMgr;

trait SoapTrait {

    /**
     *
     * @var SoapMgr 
     */
    protected $soapMgr;

    /**
     * 
     * @return SoapMgr
     */
    public function getSoap() {
        return $this->soap;
    }

    public function setSoap(SoapMgr $soap) {
        $this->soap = $soap;
    }
    
    public function configure($params) {
        $this->soap->configure($params);
    }

    public function executeCommand($command) {
        return $this->getSoap()->executeCommand($command);
    }

}
