<?php

namespace ACore\Components\UnstuckMenu;

use ACore\Components\UnstuckMenu\UnstuckController;

add_action('rest_api_init', function () {
  register_rest_route(ACORE_SLUG . '/v1', 'unstuck', array(
    'methods'  => 'POST',
    'callback' => function ($request) {
      $charName = $request->get_param('charName');
      $data = ['message' => UnstuckController::unstuck($charName)];
      return $data;
    }
  ));
});
