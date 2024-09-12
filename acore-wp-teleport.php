<?php
/**
 * Plugin Name: AzerothCore Unstuck
 * Description: Este plugin adiciona uma funcionalidade de unstuck para personagens do AzerothCore na dashboard do WooCommerce.
 * Version: 1.0
 * Author: Dr-Arayashiki
 */

if (!defined('ABSPATH')) {
    exit;
}

$soap_connection_info = array(
    'soap_uri' => 'urn:AC',
    'soap_host' => '127.0.0.1',
    'soap_port' => '7878',
    'account_name' => 'user',
    'account_password' => 'password'
);

// Função para sanitizar os inputs
function ac_sanitize_input($input) {
    return sanitize_text_field($input);
}

// Função para executar uma consulta preparada e retornar o resultado
function ac_execute_prepared_query($db_connection, $query, $params) {
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

function RemoteCommandWithSOAP($username, $password, $COMMAND)
{
    global $soap_connection_info;
    $result = '';

    try {
        $conn = new SoapClient(NULL, array(
            'location' => 'http://' . $soap_connection_info['soap_host'] . ':' . $soap_connection_info['soap_port'] . '/',
            'uri' => $soap_connection_info['soap_uri'],
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

function ac_unstuck_form()
{
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();

        $db_auth = new mysqli('localhost', 'acore', 'acore', 'acore_auth');
        $db_characters = new mysqli('localhost', 'acore', 'acore', 'acore_characters');

        if ($db_auth->connect_error || $db_characters->connect_error) {
            die('Erro de conexão: ' . $db_auth->connect_error . ' ' . $db_characters->connect_error);
        }

        $query_auth = "SELECT id FROM account WHERE username = ?";
        $result_auth = ac_execute_prepared_query($db_auth, $query_auth, array($current_user->user_login));

        if ($result_auth->num_rows > 0) {
            $account_id = $result_auth->fetch_assoc()['id'];

            $query_characters = "SELECT name FROM characters WHERE account = ?";
            $result_characters = ac_execute_prepared_query($db_characters, $query_characters, array($account_id));

            echo '<form id="ac-unstuck-form" method="post" action="' . esc_url($_SERVER['REQUEST_URI']) . '">
                <label for="character">Selecione o personagem:</label>
                <select name="character" id="character">';

            while ($row = $result_characters->fetch_assoc()) {
                echo '<option value="' . $row['name'] . '">' . $row['name'] . '</option>';
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

function handle_unstuck()
{
    global $soap_connection_info;

    if (isset($_POST['unstuck'])) {
        if (isset($_POST['character'])) {
            $character = ac_sanitize_input($_POST['character']); // Sanitiza o input do personagem
            $result = RemoteCommandWithSOAP($soap_connection_info['account_name'], $soap_connection_info['account_password'], ".teleport name " . $character . " \$home");

            if (strpos($result, 'There is no such command') !== false) {
                return '<p>Erro ao executar o comando unstuck: ' . $result . '</p>';
            } else {
                $current_user = wp_get_current_user();
                return '<p>Personagem destravado com sucesso, ' . $current_user->user_login . ' 😊</p>';
            }
        } else {
            return '<p>Por favor, selecione um personagem.</p>';
        }
    }
    return '';
}

add_action('init', 'handle_unstuck');

function ac_add_tools_endpoint()
{
    add_rewrite_endpoint('tools', EP_ROOT | EP_PAGES);
}

add_action('init', 'ac_add_tools_endpoint');

function ac_add_tools_link_my_account($items)
{
    $items['tools'] = 'Ferramentas';
    return $items;
}

add_filter('woocommerce_account_menu_items', 'ac_add_tools_link_my_account');

function ac_tools_endpoint_content()
{
    echo '<div class="woocommerce-MyAccount-content">';
    echo '<h2>Ferramentas</h2>';
    echo '<div id="ac-unstuck-result">' . handle_unstuck() . '</div>';
    ac_unstuck_form();
    echo '</div>';
}

add_action('woocommerce_account_tools_endpoint', 'ac_tools_endpoint_content');

function ac_unstuck_activate()
{
    ac_add_tools_endpoint();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'ac_unstuck_activate');

function ac_unstuck_deactivate()
{
    flush_rewrite_rules();
}

register_deactivation_hook(__FILE__, 'ac_unstuck_deactivate');

function ac_unstuck_shortcode()
{
    ob_start();
    echo '<div id="ac-unstuck-result">' . handle_unstuck() . '</div>';
    ac_unstuck_form();
    return ob_get_clean();
}

add_shortcode('ac_unstuck', 'ac_unstuck_shortcode');
?>
