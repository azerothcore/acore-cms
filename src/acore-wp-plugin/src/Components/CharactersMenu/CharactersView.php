<?php

namespace ACore\Components\CharactersMenu;

use ACore\Utils\AcoreCharColors;

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
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.5');
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

                                <ul id="acore-characters-order" class="acore-char-list list-unstyled">
                                    <?php foreach ($characters as $char) {
                                        $clsStyle = AcoreCharColors::rowStyle(intval($char["class"]), intval($char["race"]));
                                    ?>
                                        <li>
                                            <div class="acore-char-row" style="<?= esc_attr($clsStyle) ?>">
                                                <span class="acore-char-name"><?= esc_html($char["name"]) ?></span>
                                                <span class="acore-char-meta">
                                                    <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char["level"])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char["level"]))) ?>">Level <?= intval($char["level"]) ?></span>
                                                    <img class="race-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char["race"]))) ?>" src="<?= ACORE_URL_PLG . "web/assets/race/" . $char["race"] . ($char["gender"] == 0 ? "m" : "f") . ".webp"; ?>">
                                                    <img class="class-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char["class"]))) ?>" src="<?= ACORE_URL_PLG . "web/assets/class/" . $char["class"] . ".webp"; ?>">
                                                </span>
                                            </div>
                                            <input name="characterorder[]" value="<?= $char["guid"] ?>">
                                        </li>
                                    <?php } ?>
                                </ul>

                                <?php if (!empty($characters)) { ?>
                                    <input type="submit" name="submit" class="button button-primary" value="Save Order">
                                    <button type="submit" name="acore_reset_order" value="1" class="button" style="margin-left:6px;">Reset Order</button>
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
