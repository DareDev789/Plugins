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

// Ajouter un champ de parrainage sur la page du panier
function sendbazar_add_referral_code_cart() {
    if (WC()->cart->total < 65) {
        return; // Ne rien afficher si le total est inférieur à 65 €
    }
    ?>
    <div class="sendbazar-referral-container" style="margin-top: 20px;">
        <label for="parrainage_code">Code de Parrainage :</label>
        <input type="text" id="parrainage_code" name="parrainage_code" placeholder="Entrez le code ici..." value="<?php echo esc_attr(WC()->session->get('parrainage_code', '')); ?>"/>
        <button type="button" id="apply_referral_code">Appliquer</button>
        <p id="sendbazar_referral_message"></p>
    </div>

    <script>
    document.getElementById("apply_referral_code").addEventListener("click", function() {
        let referralCode = document.getElementById("parrainage_code").value;
        let messageBox = document.getElementById("sendbazar_referral_message");

        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `action=sendbazar_apply_referral_code&referral_code=${encodeURIComponent(referralCode)}`
        })
        .then(response => response.json())
        .then(data => {
            messageBox.innerText = data.message;
            if (data.success) {
                messageBox.style.color = "green";
            } else {
                messageBox.style.color = "red";
            }
        });
    });
    </script>
    <?php
}
add_action('woocommerce_cart_totals_before_order_total', 'sendbazar_add_referral_code_cart');

// Ajouter un champ de parrainage sur la page de validation de commande
function sendbazar_add_referral_checkout($checkout) {
    if (WC()->cart->total < 65) {
        return; // Ne rien afficher si le total est inférieur à 65 €
    }
    echo '<div id="sendbazar_referral_checkout_field">';
    woocommerce_form_field('parrainage_code', [
        'type'        => 'text',
        'class'       => ['sendbazar-referral-field form-row-wide'],
        'label'       => 'Code de Parrainage',
        'placeholder' => 'Entrez le code ici...',
    ], WC()->session->get('parrainage_code', ''));
    echo '</div>';
}
add_action('woocommerce_after_order_notes', 'sendbazar_add_referral_checkout');

// Vérifier et enregistrer le code de parrainage à la commande
function sendbazar_save_referral_code_order($order_id) {
    if (!empty($_POST['parrainage_code'])) {
        update_post_meta($order_id, 'parrainage_code', sanitize_text_field($_POST['parrainage_code']));
    }
}
add_action('woocommerce_checkout_update_order_meta', 'sendbazar_save_referral_code_order');

// Vérifier le code de parrainage
function sendbazar_apply_referral_code() {
    $referral_code = sanitize_text_field($_POST['referral_code'] ?? '');
    if (empty($referral_code)) {
        wp_send_json_error(['message' => 'Veuillez entrer un code.']);
    }

    // Vérifier si le code existe
    $users = get_users(['meta_key' => 'parrainage_code', 'meta_value' => $referral_code]);
    if (empty($users)) {
        wp_send_json_error(['message' => 'Ce code de parrainage est invalide.']);
    }

    // Enregistrer temporairement le code en session WooCommerce
    WC()->session->set('parrainage_code', $referral_code);
    wp_send_json_success(['message' => 'Code appliqué avec succès !']);
}
add_action('wp_ajax_sendbazar_apply_referral_code', 'sendbazar_apply_referral_code');
add_action('wp_ajax_nopriv_sendbazar_apply_referral_code', 'sendbazar_apply_referral_code');

// Appliquer une réduction de 5€ si un code de parrainage est valide
function sendbazar_apply_referral_discount($cart) {
    if (is_admin() && !defined('DOING_AJAX')) {
        return;
    }

    // Vérifier si un code de parrainage est enregistré en session
    $referral_code = WC()->session->get('parrainage_code');
    if (!empty($referral_code)) {
        $users = get_users(['meta_key' => 'parrainage_code', 'meta_value' => $referral_code]);
        if (!empty($users)) {
            $cart->add_fee('Réduction Parrainage', -5);
        }
    }
}
add_action('woocommerce_cart_calculate_fees', 'sendbazar_apply_referral_discount');

// Afficher un message si une réduction de parrainage est appliquée
function sendbazar_display_referral_discount_message() {
    $referral_code = WC()->session->get('parrainage_code');
    if (!empty($referral_code)) {
        echo '<p style="color: green; font-weight: bold;">🎉 Félicitations ! Vous avez bénéficié d’une réduction de 5€ grâce au code parrainage.</p>';
    }
}
add_action('woocommerce_cart_totals_before_order_total', 'sendbazar_display_referral_discount_message');
add_action('woocommerce_review_order_before_order_total', 'sendbazar_display_referral_discount_message');

// Ajouter le code de parrainage et la remise aux détails de la commande
function sendbazar_save_referral_discount_to_order($order_id) {
    $referral_code = WC()->session->get('parrainage_code');
    if (!empty($referral_code)) {
        update_post_meta($order_id, 'parrainage_code', $referral_code);
        update_post_meta($order_id, 'sendbazar_referral_discount', 5);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'sendbazar_save_referral_discount_to_order');

// Afficher la réduction dans l’email de confirmation de commande
function sendbazar_display_referral_discount_in_emails($order, $sent_to_admin, $plain_text) {
    $order_id = $order->get_id();
    $referral_code = get_post_meta($order_id, 'parrainage_code', true);
    $discount = get_post_meta($order_id, 'sendbazar_referral_discount', true);

    if (!empty($referral_code) && $discount) {
        echo '<p><strong>Code de Parrainage utilisé :</strong> ' . esc_html($referral_code) . '</p>';
        echo '<p><strong>Réduction appliquée :</strong> -5€</p>';
    }
}
add_action('woocommerce_email_order_meta', 'sendbazar_display_referral_discount_in_emails', 10, 3);

function update_parrainage_code() {
    if (!is_user_logged_in() || !isset($_POST['new_code'])) {
        wp_send_json(['success' => false, 'message' => 'Erreur d\'authentification']);
    }

    $current_user = wp_get_current_user();
    $new_code = sanitize_text_field($_POST['new_code']);

    if (empty($new_code) || strlen($new_code) > 15) {
        wp_send_json(['success' => false, 'message' => 'Le code doit contenir entre 1 et 15 caractères.']);
    }

    // Vérifier que le code est unique
    global $wpdb;
    $existing_code = $wpdb->get_var($wpdb->prepare(
        "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'parrainage_code' AND meta_value = %s",
        $new_code
    ));

    if ($existing_code) {
        wp_send_json(['success' => false, 'message' => 'Ce code est déjà utilisé par un autre utilisateur.']);
    }

    // Mettre à jour le code de parrainage
    update_user_meta($current_user->ID, 'parrainage_code', $new_code);

    wp_send_json(['success' => true, 'message' => 'Code mis à jour avec succès.']);
}
add_action('wp_ajax_update_parrainage_code', 'update_parrainage_code');





