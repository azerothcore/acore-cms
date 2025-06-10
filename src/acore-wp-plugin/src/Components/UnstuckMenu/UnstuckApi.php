<?php

namespace ACore\Components\UnstuckMenu;

use ACore\Components\UnstuckMenu\UnstuckController;

add_action('rest_api_init', function () {
  register_rest_route(ACORE_SLUG . '/v1', 'unstuck', array(
      'methods'  => 'POST',
      'callback' => function ($request) {
          try {
              $charName = $request->get_param('charName');
              $message = UnstuckController::unstuck($charName);
              return new \WP_REST_Response(['message' => $message], 200);

          } catch (\InvalidArgumentException $e) {
              return new \WP_Error('invalid_character or account', $e->getMessage(), array('status' => 400));

          } catch (\Exception $e) {
              return new \WP_Error('server_error', 'An unexpected error occurred', array('status' => 500));
          }
      },
      'permission_callback' => function () {
        return is_user_logged_in();  // Allow access to all logged-in users
      },
  ));
});

