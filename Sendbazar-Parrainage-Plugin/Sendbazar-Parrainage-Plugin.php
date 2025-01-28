<?php
/**
 * Plugin Name: Sendbazar Parrainage Plugin
 * Description: Un plugin de parrainage pour WooCommerce permettant aux clients et vendeurs de générer un code de parrainage, offrant des remises et des crédits virtuels.
 * Version: 1.0.0
 * Author: [Votre Nom]
 */

if (!defined('ABSPATH')) {
    exit;
}

class WooCommerceParrainagePlugin
{

    public function __construct()
    {
        // Ajouter le champ de code parrainage sur les pages Cart et Checkout
        add_action('woocommerce_before_cart_totals', [$this, 'display_referral_input']);
        add_action('woocommerce_before_checkout_form', [$this, 'display_referral_input']);

        // Appliquer la réduction si le code parrainage est utilisé
        add_action('woocommerce_cart_calculate_fees', [$this, 'apply_referral_discount']);

        // Ajouter le script JS pour gérer l'entrée dynamique du code
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);

        // Ajouter une page d'administration pour gérer les codes parrainage
        add_filter('woocommerce_get_sections_advanced', [$this, 'add_admin_settings_section']);
        add_action('woocommerce_settings_advanced_parrainage', [$this, 'admin_settings_content']);
    }

    // Afficher le champ de code parrainage uniquement si le total du panier >= 65 €
    public function display_referral_input()
    {
        if (WC()->cart->total >= 65) {
            echo '<div class="parrainage-code; margin-bottom : 5px">
                    <label for="referral_code">Code de Parrainage :</label>
                    <input type="text" id="referral_code" name="referral_code" placeholder="Entrez votre code">
                    <button type="button" class="button apply-referral">Appliquer</button>
                  </div>';
        }
    }

    // Appliquer une réduction si un code parrainage est saisi
    public function apply_referral_discount()
    {
        if (!empty($_POST['referral_code']) && WC()->cart->total >= 65) {
            $code = sanitize_text_field($_POST['referral_code']);

            // Vérifier si le code est valide
            $users = get_users([
                'meta_key' => 'parrainage_code',
                'meta_value' => $code,
            ]);

            if (!empty($users)) {
                // Appliquer la remise
                WC()->cart->add_fee('Réduction Parrainage', -5, true, '');
            } else {
                wc_add_notice('Code de parrainage invalide.', 'error');
            }
        }
    }

    // Ajouter un script JS pour gérer l'entrée du code parrainage
    public function enqueue_scripts()
    {
        if (is_cart() || is_checkout()) {
            wp_enqueue_script(
                'referral-code-script',
                plugin_dir_url(__FILE__) . 'js/referral-code.js',
                ['jquery'],
                time(),
                true
            );
        }
    }

    // Ajouter une section d'administration pour les codes de parrainage
    public function add_admin_settings_section($sections)
    {
        $sections['parrainage'] = 'Parrainage';
        return $sections;
    }

    // Afficher le contenu de la section "Parrainage" dans l'administration
    public function admin_settings_content()
    {
        $users = get_users([
            'meta_key' => 'parrainage_code',
            'meta_compare' => 'EXISTS',
        ]);

        echo '<h2>Gestion du Parrainage</h2>';
        echo '<table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Code de Parrainage</th>
                        <th>Crédits Accumulés</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($users as $user) {
            $code = get_user_meta($user->ID, 'parrainage_code', true);
            $credits = get_user_meta($user->ID, 'parrainage_credits', true) ?: 0;

            echo '<tr>
                    <td>' . esc_html($user->display_name) . '</td>
                    <td>' . esc_html($code) . '</td>
                    <td>' . wc_price($credits) . '</td>
                    <td>
                        <button class="button mark-paid" data-user-id="' . esc_attr($user->ID) . '">Marquer comme payé</button>
                    </td>
                  </tr>';
        }

        echo '</tbody></table>';
    }
}

// Initialisation du plugin
new WooCommerceParrainagePlugin();
