<?php

namespace ACore\Components\AdminPanel;

class SettingsView {

    private $controller;
    private $model;

    /**
     *
     * @param ACore\SettingsController $controller
     */
    public function __construct($controller) {
        $this->controller = $controller;
        $this->model = $controller->getModel();
    }

    public function getHomeRender() {
        return $this->loadPageLayout('Home');
    }

    public function getSettingsRender() {
        return $this->loadPageLayout('RealmSettings');
    }

    public function getElunaSettingsRender() {
        return $this->loadPageLayout('ElunaSettings');
    }

    public function getPvpRewardsRender($data, $result) {
        return $this->loadPageLayout('PVPRewards');
    }

    public function getToolsRender() {
        return $this->loadPageLayout('Tools');
    }

    private function loadPageLayout($pageName) {
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();
        include(__DIR__ . '/Pages/' . $pageName . '.php');
        return ob_get_clean();
    }
}
