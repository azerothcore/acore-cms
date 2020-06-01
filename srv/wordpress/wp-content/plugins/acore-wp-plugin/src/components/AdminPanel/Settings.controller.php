<?php

namespace ACore;

require_once 'Settings.view.php';
require_once 'Settings.model.php';

class SettingsController {

    /**
     *
     * @var SettingsView
     */
    private $view;

    /**
     *
     * @var SettingsModel 
     */
    private $model;
    private $data;

    public function __construct() {
        $this->model = new SettingsModel();
        $this->view = new SettingsView($this);
    }

    public function init() {
        //must check that the user has the required capability 
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        // See if the user has posted us some information
        // If they did, this hidden field will be set to 'Y'

        foreach (sOptsConfNames() as $key => $value) {
            if (isset($_POST[$key])) {
                $this->model->storeConf($key, $_POST[$key]);
            }
        }

        $this->data = $this->model->loadData(); // reload confs
        // Put a "settings saved" message on the screen
        ?>
        <div class="updated"><p><strong>Option saved</strong></p></div>
        <?php

        echo $this->getView()->getRender();
    }

    /**
     * 
     * @return SettingsView
     */
    public function getView() {
        return $this->view;
    }

    /**
     * 
     * @return SettingsModel
     */
    public function getModel() {
        return $this->model;
    }

}
