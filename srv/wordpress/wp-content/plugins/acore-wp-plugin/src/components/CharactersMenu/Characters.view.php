<?php

namespace ACore;

use ACore;

require_once 'Characters.controller.php';

class CharactersView {
    private $controller;

    /**
     *
     * @param \ACore\CharactersController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function getHomeRender($characters) {
        $races = [ "Human", "Orc", "Dwarf", "Night Elf", "Undead", "Tauren", "Gnome", "Troll", "Blood Elf", "Draenei" ];
        $classes = [ "Warrior", "Paladin", "Hunter", "Rogue", "Priest", "Death Knight", "Shaman", "Mage", "Warlock", "", "Druid" ];
        ob_start();

        ?>
        <div class="wrap">
            <h2>Characters Settings</h2>

            <h3>Order</h3>
            <p>This sections allows you to change the order in which the characters appear in your in-game character selection screen.</p>
            <form action="" method="POST" novalidate="novalidate">
                <style>
                    #acore-characters-order .menu-item-handle {
                        max-width: 265px;
                    }

                    #acore-characters-order .item-type {
                        padding: 0px;
                        margin-top: 5px;
                    }

                    #acore-characters-order .item-type img {
                        display: inline-block;
                        vertical-align: middle;
                        height: 32px;
                        transform: translateZ(0); /* prevent blurry image on chrome */
                    }

                    #acore-characters-order input {
                        display: none;
                    }
                </style>

                <ul id="acore-characters-order">
                    <?php foreach ($characters as $char) { ?>
                        <li>
                            <div class="menu-item-bar">
                                <div class="menu-item-handle ui-sortable-handle">
                                    <span class="item-title menu-item-title"><?= $char["name"] ?></span>
                                    <span class="item-controls">
                                    <span class="item-type">
                                        level <?= $char["level"] ?>&ensp;
                                        <img src="<?= ACORE_URL_PLG . "web/assets/race/" . $char["race"] . ($char["gender"] == 0 ? "m" : "f") . ".webp"; ?>">
                                        <img src="<?= ACORE_URL_PLG . "web/assets/class/" . $char["class"] . ".webp"; ?>">
                                    </span>
                                </div>
                            </div>
                            <input name="characterorder[]" value="<?= $char["guid"] ?>">
                        </li>
                    <?php } ?>
                </ul>

                <?php if (!empty($characters)) { ?>
                    <input type="submit" name="submit" class="button button-primary" value="Save Order">
                <?php } ?>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#acore-characters-order").sortable();
            });
        </script>

        <?php
        return ob_get_clean();
    }
}
