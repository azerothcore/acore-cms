<?php

namespace ACore\Manager\Soap;

use ACore\Manager\Soap\AcoreSoapTrait;

class GuildService {

    use AcoreSoapTrait;

    public function guildRename($oldname, $newname) {
        return $this->executeCommand(".guild rename \"$oldname\" \"$newname\"");
    }
}
