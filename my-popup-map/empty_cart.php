<?php
// empty_cart.php
include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if (class_exists('WooCommerce')) {
    WC()->cart->empty_cart();

    $customer = WC()->customer;
    if ($customer) {
        $customer->empty();
        $customer->save();
    }

    echo 'Le panier a été vidé et les informations du client ont été réinitialisées avec succès.';
} else {
    echo 'Erreur: WooCommerce n\'est pas disponible.';
}
?>