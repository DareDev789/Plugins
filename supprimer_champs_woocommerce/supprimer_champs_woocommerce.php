<?php
/**
 * Plugin Name: SUpprimer champs woocommerce Sendbazar
 * Description: Supprime certains champs de la page de validation de commande WooCommerce.
 * Version: 1.0
 * Author: Sendbazar (Wallin)
 */

if (!defined('ABSPATH')) exit;

add_filter('woocommerce_billing_fields', 'remove_billing_fields');
add_filter('woocommerce_shipping_fields', 'remove_shipping_fields');
add_filter('woocommerce_checkout_fields', 'remove_dokan_custom_checkout_fields');

function remove_billing_fields($fields) {
    unset($fields['billing_company']);
    return $fields;
}


function remove_dokan_custom_checkout_fields($fields) {
    unset($fields['billing']['billing_dokan_company_id_number']);
    unset($fields['billing']['billing_dokan_vat_number']);
    unset($fields['billing']['billing_dokan_bank_name']);
    unset($fields['billing']['billing_dokan_bank_iban']);
    unset($fields['shipping']['shipping_dokan_company_id_number']);

    return $fields;
}

function remove_shipping_fields($fields) {
    unset($fields['shipping_company']);
    return $fields;
}

add_action('woocommerce_thankyou', 'save_billing_shipping_to_cookies');

// Pour les utilisateurs non-connectés, stockés les informations de l'acheteur dans une cookies

function save_billing_shipping_to_cookies($order_id) {
    $order = wc_get_order($order_id);

    // Stocker les champs de facturation et de livraison dans les cookies
    foreach (['billing'] as $type) {
        foreach ($order->get_address($type) as $key => $value) {
            setcookie($type . '_' . $key, $value, time() + 3600 * 24 * 30, '/'); // Valable 30 jours
        }
    }
}

// Pre-remplir avant la commande

add_filter('woocommerce_checkout_get_value', 'prefill_from_cookies', 10, 2);

function prefill_from_cookies($value, $input) {
    if (isset($_COOKIE[$input])) {
        return sanitize_text_field($_COOKIE[$input]);
    }
    return $value;
}


// Stocker dans la session utilisateur si l'utilisateur est connecté

add_action('woocommerce_checkout_update_user_meta', 'save_billing_and_shipping_details');

function save_billing_and_shipping_details($customer_id) {
    if ($customer_id) {
        update_user_meta($customer_id, 'billing_first_name', sanitize_text_field($_POST['billing_first_name']));
        update_user_meta($customer_id, 'billing_last_name', sanitize_text_field($_POST['billing_last_name']));
        update_user_meta($customer_id, 'billing_address_1', sanitize_text_field($_POST['billing_address_1']));
        update_user_meta($customer_id, 'billing_address_2', sanitize_text_field($_POST['billing_address_2']));
        update_user_meta($customer_id, 'billing_city', sanitize_text_field($_POST['billing_city']));
        update_user_meta($customer_id, 'billing_postcode', sanitize_text_field($_POST['billing_postcode']));
        update_user_meta($customer_id, 'billing_country', sanitize_text_field($_POST['billing_country']));
        update_user_meta($customer_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
        update_user_meta($customer_id, 'billing_email', sanitize_email($_POST['billing_email']));
    }
}

add_filter('woocommerce_checkout_get_value', 'prefill_checkout_fields', 10, 2);

function prefill_checkout_fields($value, $input) {
    $customer_id = get_current_user_id();

    if ($customer_id) {
        if (strpos($input, 'billing_') === 0 || strpos($input, 'shipping_') === 0) {
            $saved_value = get_user_meta($customer_id, $input, true);
            return $saved_value ? $saved_value : $value;
        }
    }

    return $value;
}


// add_filter( 'gettext', function( $translated_text, $text, $domain ) {
//     if ( $domain === 'woocommerce' ) {
//         $translations = [
//             'Billing details' => 'Détails de facturation',
//             'Shipping details' => 'Détails de livraison',
//             'Place order' => 'Passer la commande',
//             'Order details' => 'Détails de la commande',
//             'Cart' => 'Panier',
//             'Checkout' => 'Caisse',
//             'Total' => 'Total',
//             'Product' => 'Produit',
//             'Price' => 'Prix',
//             'Quantity' => 'Quantité',
//             'Remove' => 'Supprimer',
//             'Order' => 'Commande',
//             'Coupon code' => 'Code promo',
//             'Apply coupon' => 'Appliquer le code promo',
//             'Shipping' => 'Livraison',
//             'Payment method' => 'Mode de paiement',
//             'Your order' => 'Votre commande',
//             'Update cart' => 'Mettre à jour le panier',
//             'Proceed to checkout' => 'Passer à la caisse',
//             'Subtotal' => 'Sous-total',
//             'Discount' => 'Remise',
//             'Taxes' => 'Taxes',
//             'First name' => 'Prénom',
//             'Last name' => 'Nom',
//             'Phone' => 'Téléphone',


//             'Select country / region' => 'Sélectionnez le pays / la région',
//             'Country / Region' => 'Pays/Région',
//             'Street address' => 'Adresse de la rue',
//             'Postcode / ZIP' => 'Code postal',
//             'Town / City' => 'Ville',
//             'Email address' => 'Adresse email',

//             'Create an account?' => 'Create an account?',
//             'Ship to a different address?' => 'Expédier à une autre adresse ?',
//             'Have a coupon?' => 'Vous avez un coupon ?',
//             'Click here to enter your code' => 'Cliquez ici pour entrer votre code',
//             'Returning customer?' => 'Déjà client ?',
//             'Enter your address to view shipping options.' => 'Entrez votre adresse pour afficher les options d\'expédition.',
//             'Calculate shipping' => 'Calculer l\'expédition',
//             'Cart totals' => 'Calculer l\'expédition',
//             'Select a category' => 'Sélectionnez une catégorie',
//             'Click here to login' => 'Cliquez ici pour vous connecter',

//             'Create an account?' => 'Créer un compte?',
//             'Order notes (optional)' => 'Note de commande (facultatif)',
//             'Notes about your order, e.g. special notes for delivery.' => 'Notes à propos de votre commande, par exemple, des notes spéciales pour la livraison.',
//             'House number and street name' => 'Numéro et nom de rue',
//             'Apartment, suite, unit, etc. (optional)' => 'Appartement, suite, unité, etc. (facultatif)',
//             'Showing %1$d–%2$d of %3$d results' => 'Montrant %1$d–%2$d de %3$d résultat',
//             'Select options' => 'Sélectionnez options',
//             'Add to cart' => 'Ajouter au panier',
//             'Reviews' => 'Commentaires',
//             'There are no product reviews yet.' => 'Il n\'y a pas des critiques de produits encore.',
//             'Be the first to review &ldquo;%s&rdquo;' => 'Soyez le premier à commenter “%s”',
//             'Your rating *' => 'Votre cote *',
//             'Your review *' => 'Votre avis *',
//             'Submit' => 'Soumettre',
//             'Read more' => 'Lire plus',
//             'All' => 'Tous',
//             'Compare' => 'Comparer',
//             'View cart' => 'Voir le panier',
//             'Sort by:' => 'Trier par:',
//             'Sort by popularity' => 'Trier par popularité',
//             'Sort by average rating' => 'Trier par note moyenne',
//             'Sort by latest' => 'Trier par les plus récents',
//             'Sort by price: low to high' => 'Trier par prix: de faible à élevé',
//             'Sort by price: high to low' => 'Trier par prix: de faible à élevé',
//             '%s has been added to your cart.' => '%s a été ajouté à votre panier.',
//             '%s have been added to your cart.' => '%s ont bien été ajouté à votre panier.',
//             'Clear'=>'Effacer',
//             'Sale!' => "Solde!",
//             'Choose an option' => "Choisir une option",
//             'Your cart is currently empty.' => 'Votre panier est vide.',
//             'Return to shop' => 'Retourner à la boutique',
//             'Additional information' => 'Information supplementaire',
//             'Showing the single result' => 'Afficher le résultat unique',
//         ];

//         // Vérifie si le texte existe dans les traductions
//         if ( isset( $translations[ $text ] ) ) {
//             return $translations[ $text ];
//         }
//     }
//     return $translated_text;
// }, 20, 3 );