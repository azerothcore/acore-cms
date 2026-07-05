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
            <div class="row">
                <div class="col-sm-4">
                    <div class="card">
                        <div class="card-body">
                            <h3>Order Character Screen</h3>
                            <p>Change the order in which the characters appear in your in-game character selection screen, by dragging them, matches in-game position.</p>
                            <hr>
                            <form action="" method="POST" novalidate="novalidate">
                                <?php wp_nonce_field('acore_character_order', 'acore_character_order_nonce'); ?>

                                <ul id="acore-characters-order" class="acore-char-list list-unstyled">
                                    <?php $charPos = 0; foreach ($characters as $char) {
                                        $clsStyle = AcoreCharColors::rowStyle(intval($char["class"]), intval($char["race"]));
                                        $charPos++;
                                        $displayPos = $char['order'] !== null ? intval($char['order']) + 1 : $charPos;
                                    ?>
                                        <li>
                                            <div class="acore-char-row" style="<?= esc_attr($clsStyle) ?>">
                                                <span class="acore-char-pos"><?= $displayPos ?></span>
                                                <span class="acore-char-name"><?= esc_html($char["name"]) ?></span>
                                                <span class="acore-char-meta">
                                                    <span class="acore-level" data-exp="<?= AcoreCharColors::expansionSlug(intval($char["level"])) ?>" title="<?= esc_attr(AcoreCharColors::expansionLabel(intval($char["level"]))) ?>">Level <?= intval($char["level"]) ?></span>
                                                    <img class="race-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getRaceName(intval($char["race"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/race/" . intval($char["race"]) . (intval($char["gender"]) == 0 ? "m" : "f") . ".webp") ?>">
                                                    <img class="class-icon" height="32" width="32" title="<?= esc_attr(AcoreCharColors::getClassName(intval($char["class"]))) ?>" src="<?= esc_url(ACORE_URL_PLG . "web/assets/class/" . intval($char["class"]) . ".webp") ?>">
                                                </span>
                                            </div>
                                            <input type="hidden" name="characterorder[]" value="<?= esc_attr($char["guid"]) ?>">
                                        </li>
                                    <?php } ?>
                                </ul>

                                <?php if (!empty($characters)) { ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                                        <input type="submit" name="submit" class="button button-primary" value="Save Order">
                                        <input type="submit" name="acore_reset_order" class="button acore-btn-danger" value="Reset Order">
                                    </div>
                                <?php } ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- .acore-btn-danger styling lives in theme.css (shared light/dark) -->

        <script>
        jQuery(document).ready(function($) {
            $("#acore-characters-order").sortable({
                update: function(event, ui) {
                    var order = [];
                    $("#acore-characters-order li").each(function(index) {
                        var input = $(this).find("input[name='characterorder[]']");
                        order.push(input.val());
                        $(this).find(".acore-char-pos").text(index + 1);
                    });
                }
            });
            $("#acore-characters-order").disableSelection();
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
