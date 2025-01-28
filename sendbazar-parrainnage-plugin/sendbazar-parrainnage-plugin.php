<?php
/**
 * Plugin Name: Sendbazar Parrainnage Plugin
 * Description: Crée un lien /parrainnage visible uniquement pour les utilisateurs connectés, avec un template personnalisé.
 * Version: 1.0
 * Author: Votre Nom
 */

if (!defined('ABSPATH')) {
    exit; // Empêche un accès direct.
}

// Ajouter une réécriture pour le lien /parrainnage.
function sendbazar_add_parrainnage_rewrite() {
    add_rewrite_rule('^parrainnage$', 'index.php?sendbazar_parrainnage=1', 'top');
}
add_action('init', 'sendbazar_add_parrainnage_rewrite');

// Ajouter une query var personnalisée.
function sendbazar_add_query_vars($vars) {
    $vars[] = 'sendbazar_parrainnage';
    return $vars;
}
add_filter('query_vars', 'sendbazar_add_query_vars');

// Charger le template pour /parrainnage.
function sendbazar_parrainnage_template($template) {
    if (get_query_var('sendbazar_parrainnage') == 1) {
        // Vérifie si l'utilisateur est connecté.
        if (is_user_logged_in()) {
            return plugin_dir_path(__FILE__) . 'templates/parrainnage-template.php';
        } else {
            // Redirige vers la page de connexion si non connecté.
            wp_redirect(wp_login_url(home_url('/parrainnage')));
            exit;
        }
    }
    return $template;
}
add_filter('template_include', 'sendbazar_parrainnage_template');

// Activer les réécritures lors de l'activation du plugin.
function sendbazar_activate() {
    sendbazar_add_parrainnage_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sendbazar_activate');

// Nettoyer les réécritures lors de la désactivation.
function sendbazar_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sendbazar_deactivate');
