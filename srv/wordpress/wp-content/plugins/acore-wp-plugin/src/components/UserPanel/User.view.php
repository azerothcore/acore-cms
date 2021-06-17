<?php

namespace ACore;

use ACore;

require_once 'User.controller.php';

class UserView {

    private $controller;
    private $model;

    /**
     *
     * @param \ACore\UserController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
        $this->model = $controller->getModel();
    }

    public function getRafProgressRender() {
        ob_start();
		?>
        <p>HERE GOES THE TABLE</p>
        <?php
        return ob_get_clean();
    }

}
