<?php
    /*
    Plugin Name: other city ship sendbazar (D'autre ville de livraison sur Sendabzar)
    Description: Un plugin pour afficher google Map qui affiche des pays ou des villes qui ont des produits sur le marketPlace mais qui marche avec des produits enregistrés avec la géolocalisation
    Version: 1.1
    Author: Sendbazar (Wallin Razafindrazokiny)
    */

    if (!defined('ABSPATH')) {
        exit; // Exit if accessed directly.
    }

    // Initialiser le plugin
    function ocss_sendbazar_init() {
        // Charger les fonctions admin
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
        }
        // Charger les fonctions frontend
        require_once plugin_dir_path(__FILE__) . 'includes/frontend-display.php';

        require_once plugin_dir_path(__FILE__) . 'includes/sendbazar_frais_livraison.php';
    }

    add_action('plugins_loaded', 'ocss_sendbazar_init');
?>