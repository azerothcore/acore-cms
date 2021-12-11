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
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();
        include(__DIR__ . '/Pages/Home.php');
        return ob_get_clean();
    }

    public function getSettingsRender() {
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();
        include(__DIR__ . '/Pages/RealmSettings.php');
        return ob_get_clean();
    }

    public function getElunaSettingsRender() {
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();
        include(__DIR__ . '/Pages/ElunaSettings.php');
        return ob_get_clean();
    }

    public function getPvpRewardsRender($amount, $isWinner, $bracket, $month, $year, $top, $fixedAmount, $stepAmount, $result) {
        $myCredConfs = get_option('mycred_pref_core');
        extract([$amount, $isWinner, $bracket, $month, $year, $top, $fixedAmount, $stepAmount, $result, $myCredConfs]);
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();
        include(__DIR__ . '/Pages/PVPRewards.php');
        return ob_get_clean();
    }

    public function getToolsRender() {
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        ob_start();
        include(__DIR__ . '/Pages/Tools.php');
        return ob_get_clean();
    }
}
