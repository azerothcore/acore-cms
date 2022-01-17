<?php

namespace ACore\Components\UserPanel;

class UserView {
    /* Page: Recruit a Friend */
    public function getRafProgressRender($rafPersonalInfo, $rafPersonalProgress, $rafRecruitedInfo) {
        extract([ $rafPersonalInfo, $rafPersonalProgress, $rafRecruitedInfo ]);
        ob_start();
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        include(__DIR__ . '/Pages/RafProgressPage.php');
        return ob_get_clean();
    }

    /* Page: Item Restoration */
    function getItemRestorationRender($characters) {
        extract([$characters]);
        ob_start();
        wp_enqueue_style('bootstrap-css', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3');
        wp_enqueue_style('acore-css', ACORE_URL_PLG . 'web/assets/css/main.css', array(), '0.1');
        wp_enqueue_script('bootstrap-js', '//cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array(), '5.1.3');
        wp_enqueue_script('power-js', 'https://wow.zamimg.com/widgets/power.js', array());
        include(__DIR__ . '/Pages/ItemRestorationPage.php');
        return ob_get_clean();
    }
}
