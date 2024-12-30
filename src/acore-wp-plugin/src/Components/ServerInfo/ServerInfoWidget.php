<?php

namespace ACore\Components\ServerInfo;

use ACore\Components\ServerInfo\ServerInfoApi;

// Creating the widget
class ServerInfoWidget extends \WP_Widget {

    function __construct() {
        parent::__construct(
                // Base ID of your widget
                'acore_server_info_widget',
                // Widget name will appear in UI
                __('Server Info', 'acore_server_info_widget'),
                // Widget description
                array('description' => __('Server info for wow', 'acore_server_info_widget'),)
        );
    }

    private function parseInfo($string) {
        $result = $string;
        $result = \str_replace("Characters in world", "<br>Characters in world", $result);
        $result = \str_replace("Server uptime", "<br>Uptime ( from daily restart )", $result);
        $result = \str_replace("Update time diff", "<br>Update time diff", $result);
        return $result;
    }

    // Creating widget front-end
    // This is where the action happens
    public function widget($args, $instance) {
        $title = apply_filters('widget_title', $instance['title']);
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if (!empty($title))
            echo $args['before_title'] . $title . $args['after_title'];

        // This is where you run the code and display the output
        ?>
        <div class="textwidget">
            <?php
                $info=ServerInfoApi::serverInfo();
                $string=$this->parseInfo($info);

                if (strpos($string,'SoapFault exception') !== false) {
                    echo "Error: server offline?";
                } else {
                    echo $string;
                }

                $accountCount = ServerInfoApi::AccountCount();

                ?>
                <br/>
                <br/>
                <p>Total Accounts created: <?=$accountCount?></p>
        </div><?php
        echo $args['after_widget'];
    }

    // Widget Backend
    public function form($instance) {
        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = __('Server Info', 'acore_server_info_widget');
        }
        // Widget admin form
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
        </p>
        <?php
    }

    // Updating widget replacing old instances with new
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title']) ) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }

}

// Class wpb_widget ends here
// Register and load the widget

add_action('widgets_init', function () {
    register_widget(__NAMESPACE__ . '\\ServerInfoWidget');
});

