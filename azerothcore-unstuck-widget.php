<?php
/**
 * Plugin Name: AzerothCore Unstuck Widget
 * Description: Este plugin adiciona uma funcionalidade de unstuck para personagens do AzerothCore como um widget do Elementor.
 * Version: 1.0
 * Author: Dr-Arayashiki
 */

if (!defined('ABSPATH')) {
    exit;
}

$ac_widget_soap_connection_info = array(
    'soap_uri' => 'urn:AC',
    'soap_host' => '127.0.0.1',
    'soap_port' => '7878',
    'account_name' => 'user',
    'account_password' => 'password'
);

// Função para sanitizar os inputs
function ac_widget_sanitize_input($input) {
    return sanitize_text_field($input);
}

// Função para executar uma consulta preparada e retornar o resultado
function ac_widget_execute_prepared_query($db_connection, $query, $params) {
    $stmt = $db_connection->prepare($query);
    if ($stmt) {
        if ($params) {
            $types = str_repeat('s', count($params)); // Assume que todos os parâmetros são strings
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result;
    } else {
        die('Erro ao preparar a consulta: ' . $db_connection->error);
    }
}

function ac_widget_remote_command_with_soap($username, $password, $COMMAND)
{
    global $ac_widget_soap_connection_info;
    $result = '';

    try {
        $conn = new SoapClient(NULL, array(
            'location' => 'http://' . $ac_widget_soap_connection_info['soap_host'] . ':' . $ac_widget_soap_connection_info['soap_port'] . '/',
            'uri' => $ac_widget_soap_connection_info['soap_uri'],
            'style' => SOAP_RPC,
            'login' => $username,
            'password' => $password
        ));
        $result = $conn->executeCommand(new SoapParam($COMMAND, 'command'));
        unset($conn);
    } catch (Exception $e) {
        $result = "Have error on soap!\n";
        if (strpos($e->getMessage(), 'There is no such command') !== false) {
            $result = 'There is no such command!';
        }
    }
    return $result;
}

function ac_widget_unstuck_form()
{
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        $db_auth = new mysqli('localhost', 'acore', 'acore', 'acore_auth');
        $db_characters = new mysqli('localhost', 'acore', 'acore', 'acore_characters');

        if ($db_auth->connect_error || $db_characters->connect_error) {
            die('Erro de conexão: ' . $db_auth->connect_error . ' ' . $db_characters->connect_error);
        }

        $query_auth = "SELECT id FROM account WHERE username = ?";
        $result_auth = ac_widget_execute_prepared_query($db_auth, $query_auth, array($current_user->user_login));

        if ($result_auth->num_rows > 0) {
            $account_id = $result_auth->fetch_assoc()['id'];

            $query_characters = "SELECT name FROM characters WHERE account = ?";
            $result_characters = ac_widget_execute_prepared_query($db_characters, $query_characters, array($account_id));

            echo '<form id="ac-unstuck-form" method="post" action="' . esc_url($_SERVER['REQUEST_URI']) . '">
                <label for="character">Selecione o personagem:</label>
                <select name="character" id="character">';

            while ($row = $result_characters->fetch_assoc()) {
                echo '<option value="' . esc_attr($row['name']) . '">' . esc_html($row['name']) . '</option>';
            }

            echo '</select>
                <input type="submit" name="unstuck" value="Unstuck">
            </form>';
        } else {
            echo '<p>O nome de usuário do WordPress não corresponde ao nome de usuário do AzerothCore.</p>';
        }

        $db_auth->close();
        $db_characters->close();
    } else {
        echo '<p>Você precisa estar logado para usar esta funcionalidade.</p>';
    }
}

function ac_widget_handle_unstuck()
{
    global $ac_widget_soap_connection_info;

    if (isset($_POST['unstuck'])) {
        if (isset($_POST['character'])) {
            $character = ac_widget_sanitize_input($_POST['character']); // Sanitiza o input do personagem
            $result = ac_widget_remote_command_with_soap($ac_widget_soap_connection_info['account_name'], $ac_widget_soap_connection_info['account_password'], ".teleport name " . $character . " \$home");

            if (strpos($result, 'There is no such command') !== false) {
                return '<p>Erro ao executar o comando unstuck: ' . esc_html($result) . '</p>';
            } else {
                $current_user = wp_get_current_user();
                return '<p>Personagem destravado com sucesso, ' . esc_html($current_user->user_login) . ' ??</p>';
            }
        } else {
            return '<p>Por favor, selecione um personagem.</p>';
        }
    }
    return '';
}

add_action('init', 'ac_widget_handle_unstuck');

function ac_widget_activate()
{
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'ac_widget_activate');

function ac_widget_deactivate()
{
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'ac_widget_deactivate');


// Register the Elementor Widget
function register_ac_widget_unstuck_widget($widgets_manager)
{
    if (!class_exists('\Elementor\Widget_Base')) {
        return; // Verifica se a classe do Elementor existe
    }

    class AC_Widget_Unstuck_Widget extends \Elementor\Widget_Base
    {
        public function get_name()
        {
            return 'ac_widget_unstuck';
        }

        public function get_title()
        {
            return __('AzerothCore Unstuck Widget', 'plugin-name');
        }

        public function get_icon()
        {
            return 'eicon-code';
        }

        public function get_categories()
        {
            return ['general'];
        }

        protected function render()
        {
            echo '<div id="ac-unstuck-result">' . ac_widget_handle_unstuck() . '</div>';
            ac_widget_unstuck_form();
        }
    }

    $widgets_manager->register_widget_type(new \AC_Widget_Unstuck_Widget());
}

add_action('elementor/widgets/widgets_registered', 'register_ac_widget_unstuck_widget');
