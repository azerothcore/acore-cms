<?php

namespace ACore\Components\CharactersMenu;

class CharactersView {
    private $controller;

    /**
     *
     * @param ACore\Components\CharactersMenu\CharactersController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function getHomeRender($characters) {
        ob_start();
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        wp_enqueue_script('jquery-ui-sortable');
        ?>
        <div class="wrap">
            <h2>Characters Settings</h2>
            <p>Check some details and configure of your characters.</p>

            <div class="row">
                <div class="col-sm-4">
                    <div class="card">
                        <div class="card-body">
                            <h3>Order</h3>
                            <p>Change the order in which the characters appear in your in-game character selection screen.</p>
                            <hr>
                            <form action="" method="POST" novalidate="novalidate">

                                <ul id="acore-characters-order" class="list-unstyled">
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
                    </div>
                </div>
            </div>
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
