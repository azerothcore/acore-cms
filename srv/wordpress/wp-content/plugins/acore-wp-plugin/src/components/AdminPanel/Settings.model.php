<?php

namespace ACore;

require_once 'Settings.controller.php';

class SettingsModel {

    public function storeConf($conf, $value) {
        update_option($conf, $value);
    }

    public function loadData() {
        return Opts::I()->loadFromDb();
    }

    public function deleteData() {
        
    }

}
