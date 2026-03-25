<?php

namespace ACore\Components\MailReturnMenu;

use ACore\Components\MailReturnMenu\MailReturnController;

add_action('rest_api_init', function () {
    // Get sent unread mails for a character
    register_rest_route(ACORE_SLUG . '/v1', 'mail-return/list/(?P<charGuid>\d+)', array(
        'methods'  => 'GET',
        'callback' => function ($request) {
            try {
                $charGuid = $request->get_param('charGuid');
                $mails = MailReturnController::getSentUnreadMails($charGuid);
                return new \WP_REST_Response($mails, 200);

            } catch (\InvalidArgumentException $e) {
                return new \WP_Error('invalid_character', $e->getMessage(), array('status' => 400));

            } catch (\Exception $e) {
                return new \WP_Error('server_error', 'An unexpected error occurred', array('status' => 500));
            }
        },
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));

    // Return a mail
    register_rest_route(ACORE_SLUG . '/v1', 'mail-return', array(
        'methods'  => 'POST',
        'callback' => function ($request) {
            try {
                $charGuid = $request->get_param('charGuid');
                $mailId = $request->get_param('mailId');
                $message = MailReturnController::returnMail($charGuid, $mailId);
                return new \WP_REST_Response(['message' => $message], 200);

            } catch (\InvalidArgumentException $e) {
                return new \WP_Error('invalid_request', $e->getMessage(), array('status' => 400));

            } catch (\Exception $e) {
                return new \WP_Error('server_error', 'An unexpected error occurred', array('status' => 500));
            }
        },
        'permission_callback' => function () {
            return is_user_logged_in();
        },
    ));
});
