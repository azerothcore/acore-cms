<?php

namespace ACore\Soap\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class SoapMgr {
    
    use ContainerAwareTrait;

    private $params = null;

    public function configure($alias) {
        $this->params = $this->container->getParameter("soap")["connections"][$alias];
    }

    public function isConfigured() {
        return $this->params != null;
    }

    public function executeCommand($command) {
        if (!$this->params) {
            throw new Exception("Soap service is not configured, please use configure() function before!");
        }

        $soap = new \SoapClient(NULL, Array(
            'location' => $this->params["protocol"] . '://' . $this->params["host"] . ':' . $this->params["port"] . '/',
            'uri' => 'urn:TC',
            'style' => SOAP_RPC,
            'login' => $this->params["user"],
            'password' => $this->params["pass"],
            'trace' => 1,
            'keep_alive' => false //php 5.4 only
        ));

        try {
            $result = $soap->executeCommand(new \SoapParam($command, 'command'));
            return $result;
        } catch (\Exception $e) {
            return $e;
        }
    }

}
