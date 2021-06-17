<?php

namespace ACore;

require_once 'User.view.php';
require_once 'Settings.model.php';

class UserController {

    /**
     *
     * @var UserView
     */
    private $view;

    /**
     *
     * @var UserModel
     */
    private $model;
    private $data;

    public function __construct() {
        $this->model = new SettingsModel();
        $this->view = new UserView($this);
    }

    public function showRafProgress() {

        echo $this->getView()->getRafProgressRender();
    }

    /**
     *
     * @return UserView
     */
    public function getView() {
        return $this->view;
    }

    /**
     *
     * @return UserModel
     */
    public function getModel() {
        return $this->model;
    }

}
