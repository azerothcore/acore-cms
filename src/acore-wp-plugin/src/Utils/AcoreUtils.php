<?php

namespace ACore\Utils;

class AcoreUtils
{
    /**
     * Handles errors for different request types:
     * - Throws GraphQL UserError if GraphQL request.
     * - Sends JSON error with status 500 for REST API and AJAX.
     * - Writes error to STDERR for CLI/CRON.
     * - Calls HTML error callback if provided.
     * - Shows admin notice if in admin.
     * - Shows inline HTML error on front-end.
     * - Throws Exception as fallback.
     *
     * @param string        $message           Error message to show or return.
     * @param callable|null $htmlErrorCallback Optional callback for HTML errors.
     *
     * @return void
     * @throws \GraphQL\Error\UserError When request is GraphQL.
     * @throws \Exception For fallback cases.
     */
    public static function handle_acore_error(string $message, ?callable $htmlErrorCallback = null): void
    {
        // GraphQL request
        if (
            (defined('GRAPHQL_HTTP_REQUEST') && GRAPHQL_HTTP_REQUEST === true)
            && class_exists('\GraphQL\Error\UserError')
        ) {
            throw new \GraphQL\Error\UserError($message);
        }

        // REST API request
        if (function_exists('wp_doing_rest') && wp_doing_rest()) {
            wp_send_json_error(['message' => $message], 500);
        }

        // AJAX request
        if (defined('DOING_AJAX') && DOING_AJAX) {
            wp_send_json_error(['message' => $message], 500);
        }

        // CLI or CRON
        if ((defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON)) {
            fwrite(STDERR, "ERROR: " . $message . PHP_EOL);
            exit(1);
        }

        // HTML error callback
        if ($htmlErrorCallback && is_callable($htmlErrorCallback)) {
            $htmlErrorCallback($message);
            return;
        }

        // Admin area notice
        if (is_admin()) {
            add_action('admin_notices', function () use ($message) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
            return;
        }

        // Fallback: Throw generic exception
        throw new \Exception($message);
    }

    /**
     * Set a flash message to be shown in the admin area (next request).
     *
     * @param string $message The message to show.
     * @param string $type    The type of notice: 'success', 'error', 'warning', 'info'.
     * @param int|null $user_id  The user ID to associate the message with. Defaults to the currently logged-in user.
     * 
     * @return void
     */
    public static function set_flash_message(string $message, string $type = 'error', ?int $user_id = null): void
    {
        $user_id ??= get_current_user_id();

        set_transient('acore_flash_notice_' . $user_id, [
            'message' => $message,
            'type'    => $type,
        ], MINUTE_IN_SECONDS);
    }

    /**
     * Show and clear the flash message in the admin area.
     *
     * @return void
     */
    public static function show_flash_message(): void
    {
        if (!is_admin()) {
            return;
        }

        $notice = get_transient('acore_flash_notice_' . get_current_user_id());

        if ($notice && !empty($notice['message'])) {
            $type = in_array($notice['type'], ['success', 'error', 'warning', 'info']) ? $notice['type'] : 'info';
            $class = 'notice notice-' . esc_attr($type) . ' is-dismissible';
            echo '<div class="' . $class . '"><p>' . esc_html($notice['message']) . '</p></div>';
            delete_transient('acore_flash_notice_' . get_current_user_id());
        }
    }
}
