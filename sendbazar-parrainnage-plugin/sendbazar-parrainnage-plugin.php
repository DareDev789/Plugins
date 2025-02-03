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
function sendbazar_add_parrainnage_rewrite()
{
    add_rewrite_rule('^parrainnage$', 'index.php?sendbazar_parrainnage=1', 'top');
}
add_action('init', 'sendbazar_add_parrainnage_rewrite');

// Ajouter une query var personnalisée.
function sendbazar_add_query_vars($vars)
{
    $vars[] = 'sendbazar_parrainnage';
    return $vars;
}
add_filter('query_vars', 'sendbazar_add_query_vars');

// Charger le template pour /parrainnage.
function sendbazar_parrainnage_template($template)
{
    if (get_query_var('sendbazar_parrainnage') == 1) {
        // Vérifie si l'utilisateur est connecté.
        if (is_user_logged_in()) {
            return plugin_dir_path(__FILE__) . 'templates/parrainnage-template.php';
        } else {
            $redirect_url = home_url('/mon-compte/') . '?redirect_to=' . urlencode(home_url('/parrainnage/'));
            wp_redirect($redirect_url);
            exit;
        }
    }
    return $template;
}
add_filter('template_include', 'sendbazar_parrainnage_template');


function nprogress_enqueue_scripts()
{
    wp_enqueue_script('nprogress-js', 'https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js', [], '0.2.0', true);
    wp_enqueue_style('nprogress-css', 'https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css', [], '0.2.0');
}
add_action('wp_enqueue_scripts', 'nprogress_enqueue_scripts');

// Activer les réécritures lors de l'activation du plugin.
function sendbazar_activate()
{
    sendbazar_add_parrainnage_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'sendbazar_activate');

// Nettoyer les réécritures lors de la désactivation.
function sendbazar_deactivate()
{
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'sendbazar_deactivate');

// Ajouter un champ de parrainage sur la page du panier
function sendbazar_add_referral_code_cart()
{
    if (WC()->cart->total < 65) {
        return; // Ne rien afficher si le total est inférieur à 65 €
    }
    ?>
    <div class="sendbazar-referral-container" style="margin-top: 20px;">
        <label for="parrainage_code">Code de Parrainage :</label>
        <input type="text" id="parrainage_code" name="parrainage_code" placeholder="Entrez le code ici..."
            value="<?php echo esc_attr(WC()->session->get('parrainage_code', '')); ?>" />
        <button type="button" id="apply_referral_code">Appliquer</button>
        <p id="sendbazar_referral_message"></p>
    </div>

    <script>
        document.getElementById("apply_referral_code").addEventListener("click", function () {
            let referralCode = document.getElementById("parrainage_code").value;
            let messageBox = document.getElementById("sendbazar_referral_message");
            NProgress.start();
            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: `action=sendbazar_apply_referral_code&referral_code=${encodeURIComponent(referralCode)}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        messageBox.style.color = "green";
                        messageBox.innerHTML = `<p style="font-size : 1.2rem"><i>Code de parrainnage validé !</i></p>`;
                        location.reload();
                    } else {
                        messageBox.style.color = "red";
                        messageBox.innerHTML = `<p style="font-size : 1.2rem"><i>Ce code n'est pas valide !</i></p>`;
                    }
                    NProgress.done();
                });
        });
    </script>
    <?php
}
add_action('woocommerce_cart_totals_before_order_total', 'sendbazar_add_referral_code_cart');

// Ajouter un champ de parrainage sur la page de validation de commande
function sendbazar_add_referral_checkout($checkout)
{
    if (WC()->cart->total < 65) {
        return; // Ne rien afficher si le total est inférieur à 65 €
    }
    echo '<div id="sendbazar_referral_checkout_field">';
    woocommerce_form_field('parrainage_code', [
        'type' => 'text',
        'class' => ['sendbazar-referral-field form-row-wide'],
        'label' => 'Code de Parrainage',
        'placeholder' => 'Entrez le code ici...',
    ], WC()->session->get('parrainage_code', ''));
    echo '</div>';
}
add_action('woocommerce_after_order_notes', 'sendbazar_add_referral_checkout');

// Vérifier et enregistrer le code de parrainage à la commande
function sendbazar_save_referral_code_order($order_id)
{
    if (!empty($_POST['parrainage_code'])) {
        global $wpdb;
        $parrain_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'parrainage_code' AND meta_value = %s",
            sanitize_text_field($_POST['parrainage_code'])
        ));
        if (!$parrain_id)
            return;
        update_post_meta($order_id, 'parrain_id', $parrain_id);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'sendbazar_save_referral_code_order');

// Vérifier le code de parrainage
function sendbazar_apply_referral_code()
{
    $referral_code = sanitize_text_field($_POST['referral_code'] ?? '');
    if (empty($referral_code)) {
        wp_send_json_error(['message' => 'Veuillez entrer un code.']);
    }

    // Vérifier si le code existe
    $users = get_users(['meta_key' => 'parrainage_code', 'meta_value' => $referral_code]);
    if (empty($users)) {
        wp_send_json_error(['message' => 'Ce code de parrainage est invalide.']);
    }

    WC()->session->set('parrainage_code', $referral_code);
    wp_send_json_success(['message' => 'Code appliqué avec succès !']);
}
add_action('wp_ajax_sendbazar_apply_referral_code', 'sendbazar_apply_referral_code');
add_action('wp_ajax_nopriv_sendbazar_apply_referral_code', 'sendbazar_apply_referral_code');

// Appliquer une réduction de 5€ si un code de parrainage est valide
function sendbazar_apply_referral_discount($cart)
{
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
function sendbazar_display_referral_discount_message()
{
    $referral_code = WC()->session->get('parrainage_code');
    if (!empty($referral_code)) {
        echo '<p style="color: green; font-weight: bold;">🎉 Félicitations ! Vous avez bénéficié d’une réduction de 5€ grâce au code parrainage.</p>';
    }
}
add_action('woocommerce_cart_totals_before_order_total', 'sendbazar_display_referral_discount_message');
add_action('woocommerce_review_order_before_order_total', 'sendbazar_display_referral_discount_message');

// Ajouter le code de parrainage et la remise aux détails de la commande
function sendbazar_save_referral_discount_to_order($order_id)
{
    $referral_code = WC()->session->get('parrainage_code');
    if (!empty($referral_code)) {
        update_post_meta($order_id, 'parrainage_code', $referral_code);
        update_post_meta($order_id, 'sendbazar_referral_discount', 5);
    }
}
add_action('woocommerce_checkout_update_order_meta', 'sendbazar_save_referral_discount_to_order');

// Afficher la réduction dans l’email de confirmation de commande
function sendbazar_display_referral_discount_in_emails($order, $sent_to_admin, $plain_text)
{
    $order_id = $order->get_id();
    $referral_code = get_post_meta($order_id, 'parrainage_code', true);
    $discount = get_post_meta($order_id, 'sendbazar_referral_discount', true);

    if (!empty($referral_code) && $discount) {
        echo '<p><strong>Code de Parrainage utilisé :</strong> ' . esc_html($referral_code) . '</p>';
        echo '<p><strong>Réduction appliquée :</strong> -5€</p>';
    }
}
add_action('woocommerce_email_order_meta', 'sendbazar_display_referral_discount_in_emails', 10, 3);

function update_parrainage_code()
{
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


// Ajouter des crédits de parrainage lorsque la commande est marquée comme "terminée"
function credit_parrain_after_order($order_id)
{
    $order = wc_get_order($order_id);

    // Récupérer l'ID du parrain enregistré dans la commande
    $parrain_id = get_post_meta($order_id, 'parrain_id', true);

    if (!$parrain_id) {
        error_log("Aucun parrain trouvé pour la commande #$order_id");
        return;
    }

    global $wpdb;

    // Vérifier si le parrain existe bien dans la base de données
    $user_exists = get_userdata($parrain_id);
    if (!$user_exists) {
        error_log("Parrain ID #$parrain_id introuvable pour la commande #$order_id");
        return;
    }

    // Récupérer et convertir le crédit existant en entier
    $current_credits = (int) get_user_meta($parrain_id, 'parrainage_credits', true);
    $new_credits = $current_credits + 5;

    // Mettre à jour le crédit de parrainage
    update_user_meta($parrain_id, 'parrainage_credits', $new_credits);

    // Récupérer l'email du parrain
    $parrain_email = $user_exists->user_email;

    if (!empty($parrain_email)) {
        // Envoyer un email au parrain
        $subject = "🎉 Vous avez gagné 5€ de parrainage !";
        $message = "Félicitations ! Une nouvelle commande a été validée avec votre code de parrainage.\n\nVotre solde total est maintenant de {$new_credits}€.\n\nMerci pour votre fidélité !";
        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        wp_mail($parrain_email, $subject, $message, $headers);
    } else {
        error_log("Impossible d'envoyer l'email : adresse email introuvable pour le parrain #$parrain_id");
    }
}
add_action('woocommerce_order_status_completed', 'credit_parrain_after_order');


// Ajouter une page d'administration
function add_parrainage_admin_page()
{
    add_menu_page(
        'Parrainage Plugin', // Titre de la page
        'Parrainage', // Nom du menu
        'manage_options', // Capability
        'parrainage-plugin', // Slug
        'display_parrainage_admin_page', // Fonction d'affichage
        'dashicons-groups', // Icône
        25 // Position
    );
}
add_action('admin_menu', 'add_parrainage_admin_page');

// Affichage de la page d'administration
function display_parrainage_admin_page()
{
    global $wpdb;

    // Récupérer tous les utilisateurs ayant un code de parrainage
    $users = get_users([
        'meta_key' => 'parrainage_credits',
        'orderby' => 'meta_value_num',
        'order' => 'DESC'
    ]);

    echo '<div class="wrap">';
    echo '<h1>Parrainage Plugin</h1>';
    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>Utilisateur</th><th>Code</th><th>Crédits</th><th>Statut</th><th>Actions</th></tr></thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        $user_id = $user->ID;
        $code = get_user_meta($user_id, 'parrainage_code', true);
        $credits = get_user_meta($user_id, 'parrainage_credits', true) ?: 0;
        $status = get_user_meta($user_id, 'parrainage_payment_status', true) ?: 'Non demandé';

        echo "<tr>
                <td>{$user->display_name}</td>
                <td>{$code}</td>
                <td>{$credits} €</td>
                <td>{$status}</td>
                <td><a href='" . admin_url("admin.php?page=parrainage-plugin&user_id=$user_id") . "'>Voir</a></td>
              </tr>";
    }

    echo '</tbody></table>';
    echo '</div>';

    // Afficher les détails d'un utilisateur si on clique sur "Voir"
    if (isset($_GET['user_id'])) {
        display_user_parrainage_details(intval($_GET['user_id']));
    }
}

// Afficher les détails d'un utilisateur
function display_user_parrainage_details($user_id)
{
    global $wpdb;

    $user = get_userdata($user_id);
    if (!$user) {
        echo '<p>Utilisateur non trouvé.</p>';
        return;
    }

    $credits = get_user_meta($user_id, 'parrainage_credits', true) ?: 0;
    $status = get_user_meta($user_id, 'demande_paiement', true) ?: 'Non demandé';

    echo "<h2>Détails de {$user->display_name}</h2>";
    echo "<p>Crédits accumulés : <strong>{$credits} €</strong></p>";
    echo "<p><strong>Statut du paiement :</strong> {$status}</p>";

    if ($credits > 0 && $status !== 'Payé') {
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">
                <input type="hidden" name="user_id" value="' . esc_attr($user_id) . '">
                <input type="hidden" name="action" value="mark_as_paid">
                <button type="submit" class="button button-primary" onclick="return confirm(\'Confirmez-vous le paiement de ' . $credits . ' € à ' . $user->display_name . ' ?\')">
                    Marquer comme payé
                </button>
              </form>';
    }

    // Récupérer toutes les commandes des filleuls
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT ID FROM {$wpdb->prefix}posts WHERE post_type = 'shop_order' AND ID IN (
            SELECT post_id FROM {$wpdb->prefix}postmeta WHERE meta_key = 'parrain_id' AND meta_value = %d
        )",
        $user_id
    ));

    if ($orders) {
        echo '<h3>Commandes des filleuls</h3>';
        echo '<table class="widefat fixed">';
        echo '<thead><tr><th>ID de commande</th><th>Date</th><th>Montant</th><th>Voir</th></tr></thead>';
        echo '<tbody>';

        foreach ($orders as $order) {
            $order_id = $order->ID;
            $order_data = wc_get_order($order_id);
            $order_total = $order_data ? $order_data->get_total() : 'N/A';
            $order_date = $order_data ? $order_data->get_date_created()->date('Y-m-d H:i') : 'N/A';

            echo "<tr>
                    <td>#{$order_id}</td>
                    <td>{$order_date}</td>
                    <td>{$order_total} €</td>
                    <td><a href='" . admin_url("post.php?post=$order_id&action=edit") . "'>Voir</a></td>
                  </tr>";
        }

        echo '</tbody></table>';
    } else {
        echo '<p>Aucune commande trouvée pour ce parrain.</p>';
    }
}

// Action pour marquer un paiement comme effectué
function mark_parrainage_as_paid()
{
    if (!current_user_can('manage_options') || !isset($_POST['user_id'])) {
        wp_die('Accès refusé.');
    }

    $user_id = intval($_POST['user_id']);
    $credits = get_user_meta($user_id, 'parrainage_credits', true) ?: 0;

    if ($credits > 0) {
        // Réinitialiser les crédits
        update_user_meta($user_id, 'parrainage_credits', 0);
        update_user_meta($user_id, 'demande_paiement', 'Payé');

        // Envoyer un e-mail de confirmation à l'utilisateur
        $user = get_userdata($user_id);
        $to = $user->user_email;
        $subject = "Paiement de parrainage effectué";
        $message = "Bonjour {$user->display_name},\n\nVotre paiement de {$credits}€ a été effectué.\n\nMerci de votre fidélité !";
        wp_mail($to, $subject, $message);

        // Redirection après traitement
        wp_redirect(admin_url('admin.php?page=parrainage-plugin&user_id=' . $user_id));
        exit;
    } else {
        wp_die('Crédits insuffisants.');
    }
}

add_action('admin_post_mark_as_paid', 'mark_parrainage_as_paid');



add_action('wp_ajax_download_parrainage_image', 'download_parrainage_image');
add_action('wp_ajax_nopriv_download_parrainage_image', 'download_parrainage_image');

function download_parrainage_image() {
    // Vérifier le nonce pour éviter les attaques CSRF
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'download_parrainage_nonce')) {
        wp_send_json_error(['message' => 'Nonce invalide.']);
        wp_die();
    }

    if (!isset($_POST['parrainage_code'])) {
        wp_send_json_error(['message' => 'Code de parrainage manquant.']);
        wp_die();
    }

    $parrainage_code = sanitize_text_field($_POST['parrainage_code']);

    // Chemins des fichiers (dans le plugin)
    $background_path = plugin_dir_path(__FILE__) . 'assets/parrainage-bg.jpg';
    $font_path = plugin_dir_path(__FILE__) . 'assets/ARIAL.TTF';

    // Vérification des fichiers
    if (!file_exists($background_path)) {
        wp_send_json_error(['message' => "L'image de fond est introuvable dans le plugin !"]);
        wp_die();
    }

    if (!file_exists($font_path)) {
        wp_send_json_error(['message' => "Le fichier de police est introuvable dans le plugin !"]);
        wp_die();
    }

    // Déterminer le dossier de destination
    $upload_dir = wp_upload_dir();
    if (empty($upload_dir['path']) || empty($upload_dir['url'])) {
        wp_send_json_error(['message' => "Erreur lors de la récupération du dossier d'upload."]);
        wp_die();
    }

    $output_filename = 'parrainage-' . $parrainage_code . '.jpg';
    $output_path = $upload_dir['path'] . '/' . $output_filename;
    $output_url = $upload_dir['url'] . '/' . $output_filename;

    // Créer l'image
    $image = imagecreatefromjpeg($background_path);
    $white = imagecolorallocate($image, 255, 255, 255);

    // Ajouter le texte
    imagettftext($image, 40, 0, 50, 200, $white, $font_path, $parrainage_code);

    // Sauvegarder l'image
    imagejpeg($image, $output_path, 100);
    imagedestroy($image);

    // Retourner l'URL de l'image
    wp_send_json_success(['image_url' => $output_url]);
    wp_die();
}





