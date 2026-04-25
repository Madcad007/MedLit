<?php
/**
 * Plugin Name: MedLit Plain
 * Plugin URI:  https://example.com/medlit-plain
 * Description: Minimalversion von MedLit AI ohne Login- oder API-Key-Prüfung.
 * Version:     1.0.0
 * Author:      MedLit
 * License:     GPL-2.0-or-later
 * Text Domain: medlit-plain
 */

defined('ABSPATH') || exit;

add_shortcode('medlit_search', 'medlit_render_app');

function medlit_render_app() {
    ob_start();
    include plugin_dir_path(__FILE__) . 'app/index.html';
    return ob_get_clean();
}
