<?php
// empty_cart.php
include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

if (class_exists('WooCommerce')) {
    WC()->cart->empty_cart();
    echo 'Le panier a été vidé avec succès.';
} else {
    echo 'Erreur: WooCommerce n\'est pas disponible.';
}
?>