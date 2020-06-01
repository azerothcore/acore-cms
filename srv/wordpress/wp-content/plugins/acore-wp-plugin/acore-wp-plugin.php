<?php

/*
  Plugin Name: AzerothCore Wordpress Integration
  Description: Provides AzerothCore integration for Wordpress
  Version: 0.1
  Author: Yehonal and AzerothCore community
 */

define("ACORE_PATH_PLG", plugin_dir_path(__FILE__));
define("ACORE_URL_PLG", plugin_dir_url(__FILE__));


require ACORE_PATH_PLG . "/src/boot.php";
