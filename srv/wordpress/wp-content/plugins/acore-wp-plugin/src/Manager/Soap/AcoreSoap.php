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

    public function executeCommand($command, $logCommand = false, $orderId = null)
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
        global $wpdb;
        $userId = null;
        if ($logCommand) {
            $user = wp_get_current_user();
            $userId = $user->ID;
            if (!$orderId) {
                $orderId = "NULL";
            }
            $soapLogsTableName = $wpdb->prefix . ACORE_SOAP_LOGS_TABLENAME;
            $query = "INSERT INTO `$soapLogsTableName` (`user_id`, `command`, `success`, `result`, `order_id`, `executed_at`)
            VALUES ($userId, %s, %d, %s, $orderId, NOW())";
        }

        try {
            $result = $soap->executeCommand(new \SoapParam($command, 'command'));
            if ($logCommand) {
                $wpdb->query(
                    $wpdb->prepare($query, [$command, 1, $result])
                );
            }
            return $result;
        } catch (\Exception $e) {
            if ($logCommand) {
                $wpdb->query(
                    $wpdb->prepare($query, [$command, 0, $e->getMessage()])
                );
            }
            return $e->getMessage();
        }
    }
}
