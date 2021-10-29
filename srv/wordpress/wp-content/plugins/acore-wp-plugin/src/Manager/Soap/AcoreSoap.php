<?php

namespace ACore\Manager\Soap;


class AcoreSoap
{

    private $params = null;

    public function configure($params)
    {
        $this->params = $params;
    }

    public function isConfigured()
    {
        return $this->params != null;
    }

    public function executeCommand($command)
    {
        if (!$this->params) {
            throw new \Exception("Soap service is not configured, please use configure() function before!");
        }

        $soap = new \SoapClient(null, array(
            'location' => $this->params["protocol"] . '://' . $this->params["host"] . ':' . $this->params["port"] . '/',
            'uri' => 'urn:AC',
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
            return $e->getMessage();
        }
    }
}
