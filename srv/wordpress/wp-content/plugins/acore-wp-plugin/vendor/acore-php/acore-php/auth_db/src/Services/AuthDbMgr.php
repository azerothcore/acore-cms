<?php

namespace ACore\AuthDb\Services;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use ACore\AuthDb\Utils\AuthDbTrait;

class AuthDbMgr {

    use AuthDbTrait;
    use ContainerAwareTrait;
}
