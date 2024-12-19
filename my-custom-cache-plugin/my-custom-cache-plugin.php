<?php
/*
Plugin Name: My Custom Cache Plugin
 * Description: Gère le cache des fichiers CSS et JS et charge les images de manière différée.
 * Version: 1.0
 * Author: Sendbazar
 */

// Bloque l'accès direct au fichier.
if (!defined('ABSPATH')) exit;

// Enregistre les fichiers CSS et JS en cache
function cmp_enqueue_cached_files() {
    $css_files = [
        get_stylesheet_directory_uri() . '/style.css',
        get_template_directory_uri() . '/assets/css/custom.css',
    ];
    $js_files = [
        get_template_directory_uri() . '/assets/js/custom.js',
    ];

    // Concatène et minifie CSS
    $css_content = '';
    foreach ($css_files as $css_file) {
        $css_content .= file_get_contents($css_file);
    }
    $css_content = cmp_minify_css($css_content);
    file_put_contents(WP_CONTENT_DIR . '/cache/cmp-style.css', $css_content);
    wp_enqueue_style('cmp-cache-style', content_url('/cache/cmp-style.css'), [], null);

    // Concatène et minifie JS
    $js_content = '';
    foreach ($js_files as $js_file) {
        $js_content .= file_get_contents($js_file);
    }
    $js_content = cmp_minify_js($js_content);
    file_put_contents(WP_CONTENT_DIR . '/cache/cmp-scripts.js', $js_content);
    wp_enqueue_script('cmp-cache-script', content_url('/cache/cmp-scripts.js'), [], null, true);
}
add_action('wp_enqueue_scripts', 'cmp_enqueue_cached_files', 10);

// Minifie le CSS
function cmp_minify_css($css) {
    return preg_replace('/\s+/', ' ', $css);
}

// Minifie le JS
function cmp_minify_js($js) {
    return preg_replace('/\s+/', ' ', $js);
}

// Active le chargement différé des images
function cmp_lazy_load_images() {
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const lazyImages = document.querySelectorAll('img');
        lazyImages.forEach(img => {
            img.setAttribute('loading', 'lazy');
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'cmp_lazy_load_images');
