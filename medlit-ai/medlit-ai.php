<?php
/**
 * Plugin Name: MedLit AI
 * Plugin URI:  https://example.com/medlit-ai
 * Description: Evidenzbasierte medizinische Literatursuche mit OpenAI.
 * Version:     1.1.0
 * Author:      MedLit
 * License:     GPL-2.0-or-later
 * Text Domain: medlit-ai
 */

defined('ABSPATH') || exit;

/* ──────────────────────────────────────────────
   1.  SHORTCODE  [medlit_search]
────────────────────────────────────────────── */
add_shortcode('medlit_search', 'medlit_render_app');

function medlit_render_app() {
    if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
        return '<div class="medlit-error">⚠️ Bitte <code>OPENAI_API_KEY</code> in der <code>wp-config.php</code> definieren.</div>';
    }

    ob_start();
    ?>
    <script>
    window.MEDLIT_CONFIG = {
        ajaxUrl: <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>,
        nonce:   <?php echo wp_json_encode(wp_create_nonce('medlit_nonce')); ?>
    };
    </script>
    <?php
    include plugin_dir_path(__FILE__) . 'app/index.html';
    return ob_get_clean();
}

/* ──────────────────────────────────────────────
   2.  AJAX-ENDPOINT: OpenAI API-Proxy
       POST /wp-admin/admin-ajax.php
       action=medlit_claude
────────────────────────────────────────────── */
add_action('wp_ajax_medlit_claude',        'medlit_claude_proxy');
add_action('wp_ajax_nopriv_medlit_claude', 'medlit_claude_proxy');

function medlit_claude_proxy() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        wp_send_json_error(['message' => 'Ungültige Methode.'], 405);
    }

    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE || empty($body)) {
        wp_send_json_error(['message' => 'Ungültiger JSON-Body.'], 400);
    }

    $nonce = isset($body['nonce']) ? sanitize_text_field($body['nonce']) : '';
    if (!wp_verify_nonce($nonce, 'medlit_nonce')) {
        wp_send_json_error(['message' => 'Ungültige Sicherheitsprüfung.'], 403);
    }

    $allowed_models = ['gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-3.5-turbo'];
    $model = isset($body['model']) && in_array($body['model'], $allowed_models, true)
        ? $body['model']
        : 'gpt-4o';

    $messages = isset($body['messages']) && is_array($body['messages']) ? $body['messages'] : [];
    if (empty($messages)) {
        wp_send_json_error(['message' => 'Keine Nachrichten übermittelt.'], 400);
    }

    $clean_messages = [];
    foreach ($messages as $msg) {
        if (!isset($msg['role'], $msg['content'])) continue;
        $role    = in_array($msg['role'], ['user', 'assistant'], true) ? $msg['role'] : 'user';
        $content = is_string($msg['content']) ? wp_strip_all_tags($msg['content']) : '';
        if ($content === '') continue;
        $clean_messages[] = ['role' => $role, 'content' => $content];
    }

    if (empty($clean_messages)) {
        wp_send_json_error(['message' => 'Keine gültigen Nachrichten.'], 400);
    }

    $max_tokens = min((int) ($body['max_tokens'] ?? 2200), 4096);

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 120,
        'headers' => [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . OPENAI_API_KEY,
        ],
        'body' => wp_json_encode([
            'model'       => $model,
            'max_tokens'  => $max_tokens,
            'messages'    => $clean_messages,
            'temperature' => 0.25,
        ]),
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'API-Verbindungsfehler: ' . $response->get_error_message()], 502);
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $body_raw  = wp_remote_retrieve_body($response);
    $data      = json_decode($body_raw, true);

    if ($http_code !== 200) {
        $err_msg = $data['error']['message'] ?? "OpenAI HTTP-Fehler $http_code";
        wp_send_json_error(['message' => $err_msg], $http_code);
    }

    wp_send_json_success($data);
}
