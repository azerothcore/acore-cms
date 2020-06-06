<?php

namespace ACore\Soap;

// workaround
ini_set('soap.wsdl_cache_enabled', 0);
ini_set('soap.wsdl_cache_ttl', 0);
ini_set('soap.wsdl_cache', 0);

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreSoap extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\SoapExtension();
    }

}
