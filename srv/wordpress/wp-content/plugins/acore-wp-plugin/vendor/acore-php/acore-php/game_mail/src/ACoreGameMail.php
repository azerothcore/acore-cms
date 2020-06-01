<?php

namespace ACore\GameMail;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ACoreGameMail extends Bundle {

    public function getContainerExtension() {
        return new DependencyInjection\MailExtension();
    }

}
