<?php

namespace ACore\Components\UnstuckMenu;


class UnstuckView
{
    private $controller;

    public function __construct($controller) {
        $this->controller = $controller;
    }

    public function getUnstuckmenuRender($chars)
    {
        ob_start();
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');

        ?>

        <div class="wrap">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <h3>Unstuck</h3>
                        <p>Unstuck your characters and teleport them to the home location of each race</p>
                        <hr>
                        <ul id="acore-characters-unstuck" class="list-unstyled">
                            <?php foreach ($chars as $char) { ?>
                                <li>
                                    <div class="menu-item-bar">
                                        <div class="menu-item-handle">
                                            <span class="item-title menu-item-title"><?= $char["name"] ?></span>
                                            <span class="item-controls">
                                                <span class="item-type">
                                                    level <?= $char["level"] ?>&ensp;
                                                    <img src="<?= ACORE_URL_PLG . "web/assets/race/" . $char["race"] . ($char["gender"] == 0 ? "m" : "f") . ".webp"; ?>">
                                                    <img src="<?= ACORE_URL_PLG . "web/assets/class/" . $char["class"] . ".webp"; ?>">
                                                </span>
                                                <button class="unstuck-button" data-char-name="<?= $char["name"] ?>">Unstuck</button>
                                            </div>
                                    </div>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        
        <!-- TODO: HOW TO FIX THIS JS + PHP ? -->
        <script>
    jQuery(document).ready(function($) {
        $(".unstuck-button").click(function() {
            var charName = $(this).data('char-name');

            console.log("Button clicked!" + $(this).data('char-name'));

            var data = {
                action: 'unstuck',
                char_name: charName,
                nonce: '<?php echo wp_create_nonce('unstuck_nonce'); ?>'
            };

            $.post(ajaxurl, data, function(response) {
                console.log(response);
            });
        });
    });
</script>

<?php
        return ob_get_clean();
    }
}
