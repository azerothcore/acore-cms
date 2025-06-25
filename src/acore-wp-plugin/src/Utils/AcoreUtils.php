<?php

namespace ACore\Utils;

class AcoreUtils
{
    /**
     * Handles errors in a unified way for both GraphQL and standard WordPress requests.
     *
     * If the current request is a GraphQL request (GRAPHQL_REQUEST is defined and true),
     * a GraphQL UserError is thrown. Otherwise, the user is redirected to the Acore CMS settings page
     * and an admin notice is displayed.
     *
     * @param string $message The error message to display or return.
     *
     * @throws UserError If the request is a GraphQL request.
     */
    public static function handle_acore_error(string $message): void
    {
        if(defined('GRAPHQL_HTTP_REQUEST') && GRAPHQL_HTTP_REQUEST === true){
            throw new \GraphQL\Error\UserError($message);
        }
        
        wp_redirect(admin_url('admin.php?page=' . ACORE_SLUG . '-settings'));
        echo "<div class='notice notice-error'><p>" . esc_html($message) . "</p></div>";
        exit;
    }
}