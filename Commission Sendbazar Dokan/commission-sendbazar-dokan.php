<?php
/*
Plugin Name: Commission Sendbazar Dokan
Description: Multiplie les prix des produits pour certains utilisateurs sur Dokan.
Version: 1.1
Author: Razafindrazokiny Wallin
*/

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Inclure le fichier des paramètres d'administration
require_once plugin_dir_path(__FILE__) . 'admin-settings.php';

// Fonction pour vérifier si l'email contient '@sendbazar'
function is_sendbazar_email($user_id) {
    $user_info = get_userdata($user_id);
    return strpos($user_info->user_email, '@sendbazar.') !== false;
}

// Actions pour Dokan
add_action('dokan_new_product_added', 'multiply_price_on_add_product', 10, 1);
add_action('dokan_product_updated', 'multiply_price_on_add_product', 10, 1);

// Actions pour WooCommerce
add_action('woocommerce_save_product_variation', 'multiply_price_on_add_variation', 20, 1);
add_action('woocommerce_save_product_variation', 'divide_price_on_edit', 10, 1);

// Hook pour diviser les prix lors de l'affichage du formulaire d'édition d'un produit dans Dokan

add_action('dokan_product_edit_after_product_tags', 'divide_price_on_edit');

function multiply_price_on_add_variation($variation_id) {
    $user_id = get_current_user_id();
    $variation = wc_get_product($variation_id);

    if (!$variation || !$variation->is_type('variation')) {
        error_log('Variation non trouvée ou incorrecte.');
        return;
    }

    // Récupérer les paramètres
    $mycommissionVendeur = get_option('mycommission_vendeur', 2);
    $mycommissionFournisseur = get_option('mycommission_fournisseur', 3);
    $fraisBancaireVendeur = get_option('frais_bancaire_vendeur', 0);
    $fraisBancaireFournisseur = get_option('frais_bancaire_fournisseur', 0);

    $regular_price = $variation->get_regular_price();
    $sale_price = $variation->get_sale_price();

    if (!is_sendbazar_email($user_id)) {
        if ($regular_price !== '') {
            $new_regular_price = ($regular_price * $mycommissionVendeur) + $fraisBancaireVendeur;
            $variation->set_regular_price($new_regular_price);
            update_post_meta($variation_id, '_regular_price', $new_regular_price);
        }
        if ($sale_price !== '') {
            $new_sale_price = ($sale_price * $mycommissionVendeur) + $fraisBancaireVendeur;
            $variation->set_sale_price($new_sale_price);
            update_post_meta($variation_id, '_sale_price', $new_sale_price);
        }
    } else {
        if ($regular_price !== '') {
            $new_regular_price = ($regular_price * $mycommissionFournisseur) + $fraisBancaireFournisseur;
            $variation->set_regular_price($new_regular_price);
            update_post_meta($variation_id, '_regular_price', $new_regular_price);
        }
        if ($sale_price !== '') {
            $new_sale_price = ($sale_price * $mycommissionFournisseur) + $fraisBancaireFournisseur;
            $variation->set_sale_price($new_sale_price);
            update_post_meta($variation_id, '_sale_price', $new_sale_price);
        }
    }
    $variation->save();
}

function apply_commission_to_booking_product($product_id) {
    // Vérifiez si le produit est de type 'booking'
    $booking_type = get_post_meta($product_id, '_wc_booking_type', true);
    
    if ($booking_type !== 'booking') {
        error_log('Le produit ID ' . $product_id . ' n\'est pas de type booking.');
        return;
    }

    // Définir le coût maximum
    $max_cost = 9;
    update_post_meta($product_id, '_new_booking_cost', $max_cost);
    
    // Pour débogage
    error_log('Le coût de réservation pour le produit ID ' . $product_id . ' a été mis à jour à ' . $max_cost);
}

add_action('dokan_product_updated', 'apply_commission_to_booking_product');
add_action('save_post', 'apply_commission_to_booking_product');


function multiply_price_on_add_product($product_id) {
    $user_id = get_current_user_id();
    $product = wc_get_product($product_id);

    if (!$product || $product->is_type('variable')) {
        return; // Les produits variables sont gérés par multiply_price_on_add_variation
    }
    // Récupérer les paramètres
    $mycommissionVendeur = get_option('mycommission_vendeur', 2);
    $mycommissionFournisseur = get_option('mycommission_fournisseur', 3);
    $fraisBancaireVendeur = get_option('frais_bancaire_vendeur', 0);
    $fraisBancaireFournisseur = get_option('frais_bancaire_fournisseur', 0);

    $regular_price = $product->get_regular_price();
    $sale_price = $product->get_sale_price();

    if (!is_sendbazar_email($user_id)) {
        if ($regular_price !== '') {
            $new_regular_price = ($regular_price * $mycommissionVendeur) + $fraisBancaireVendeur;
            $product->set_regular_price($new_regular_price);
        }
        if ($sale_price !== '') {
            $new_sale_price = ($sale_price * $mycommissionVendeur) + $fraisBancaireVendeur;
            $product->set_sale_price($new_sale_price);
        }
    } else {
        if ($regular_price !== '') {
            $new_regular_price = ($regular_price * $mycommissionFournisseur) + $fraisBancaireFournisseur;
            $product->set_regular_price($new_regular_price);
        }
        if ($sale_price !== '') {
            $new_sale_price = ($sale_price * $mycommissionFournisseur) + $fraisBancaireFournisseur;
            $product->set_sale_price($new_sale_price);
        }
    }
    $product->save();
}



function divide_price_on_edit($post_id) {
    $user_id = get_current_user_id();
    $product = wc_get_product($post_id);

    if (!$product) {
        error_log('Product not found.');
        return;
    }

    // Récupérer les paramètres
    $mycommissionVendeur = get_option('mycommission_vendeur', 2);
    $mycommissionFournisseur = get_option('mycommission_fournisseur', 3);
    $fraisBancaireVendeur = get_option('frais_bancaire_vendeur', 0);
    $fraisBancaireFournisseur = get_option('frais_bancaire_fournisseur', 0);

    if (!is_sendbazar_email($user_id)) {
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();

        if (!empty($regular_price)) {
            $regular_price = ($regular_price - $fraisBancaireVendeur) / $mycommissionVendeur;
            $regular_price = number_format($regular_price, 2, ',', '');
        }
        if (!empty($sale_price)) {
            $sale_price = ($sale_price - $fraisBancaireVendeur) / $mycommissionVendeur;
            $sale_price = number_format($sale_price, 2, ',', '');
        }

        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('input[name=\"_regular_price\"]').value = '{$regular_price}';
                document.querySelector('input[name=\"_sale_price\"]').value = '{$sale_price}';
            });
        </script>";
    } else {
        $regular_price = $product->get_regular_price();
        $sale_price = $product->get_sale_price();

        if (!empty($regular_price)) {
            $regular_price = ($regular_price - $fraisBancaireFournisseur) / $mycommissionFournisseur;
            $regular_price = number_format($regular_price, 2, ',', '');
        }
        if (!empty($sale_price)) {
            $sale_price = ($sale_price - $fraisBancaireFournisseur) / $mycommissionFournisseur;
            $sale_price = number_format($sale_price, 2, ',', '');
        }


        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelector('input[name=\"_regular_price\"]').value = '{$regular_price}';
                document.querySelector('input[name=\"_sale_price\"]').value = '{$sale_price}';
            });
        </script>";
    }

    // Gérer les produits variables
    if ($product->is_type('variable')) {
        $variations = $product->get_children();
        $i = 0;
        foreach ($variations as $variation_id) {
            $variation = wc_get_product($variation_id);
            $regular_price = $variation->get_regular_price();
            $sale_price = $variation->get_sale_price();
    
            if (!is_sendbazar_email($user_id)) {
                $regular_price = (!empty($regular_price)) ? ($regular_price - $fraisBancaireVendeur) / $mycommissionVendeur : '';
                $sale_price = (!empty($sale_price)) ? ($sale_price - $fraisBancaireVendeur) / $mycommissionVendeur : '';
            } else {
                $regular_price = (!empty($regular_price)) ? ($regular_price - $fraisBancaireFournisseur) / $mycommissionFournisseur : '';
                $sale_price = (!empty($sale_price)) ? ($sale_price - $fraisBancaireFournisseur) / $mycommissionFournisseur : '';
            }
    
            if (!empty($regular_price)) {
                $regular_price = number_format($regular_price, 2, ',', '');
            }
            if (!empty($sale_price)) {
                $sale_price = number_format($sale_price, 2, ',', '');
            }
    
            echo "<script>
                document.addEventListener('DOMContentLoaded', function() {
                    var intervalId = setInterval(function() {
                        var regularPriceInput = document.querySelector('input[name=\"variable_regular_price[{$i}]\"]');
                        var salePriceInput = document.querySelector('input[name=\"variable_sale_price[{$i}]\"]');
                        if (regularPriceInput && salePriceInput) {
                            regularPriceInput.value = '{$regular_price}';
                            salePriceInput.value = '{$sale_price}';
                            clearInterval(intervalId); // Arrêtez de vérifier une fois les champs trouvés
                        }
                    }, 100); // Vérifiez toutes les 100 millisecondes
                });
            </script>";
    
            $i++;
        }
    
        echo "
            <script>
                function checkForElements() {
                    var intervalId = setInterval(function() {
                        var elements = document.querySelectorAll('.dokan-variation-action-toolbar');
                        if (elements.length > 0) {
                            elements.forEach(function(element) {
                                element.setAttribute('style', 'display: none');
                            });
                        }
                    }, 100);
                }
                document.addEventListener('DOMContentLoaded', checkForElements);
            </script>
        ";
    }
}


//Carroussel les boutiques et categories mères
function enqueue_owlcarousel_assets() {
    // Enqueue OwlCarousel CSS
    wp_enqueue_style( 'owl-carousel-css', plugin_dir_url(__FILE__) . 'css/owl.carousel.min.css' );
    wp_enqueue_style( 'owl-carousel-theme-css', plugin_dir_url(__FILE__) . 'css/owl.theme.default.min.css' );

    // Enqueue OwlCarousel JS
    wp_enqueue_script( 'owl-carousel-js', plugin_dir_url(__FILE__) . 'js/owl.carousel.min.js', array( 'jquery' ), null, true );
    
    // Enqueue custom script for initializing the carousel
    wp_enqueue_script( 'custom-owl-init', plugin_dir_url(__FILE__) . 'js/owl-init.js', array( 'owl-carousel-js' ), null, true );
}
add_action( 'wp_enqueue_scripts', 'enqueue_owlcarousel_assets' );


function dokan_store_carousel_shortcode() {
    // Arguments pour récupérer les vendeurs Dokan
    $args = array(
        'role'    => 'seller',
        'orderby' => 'ID',
        'order'   => 'DESC',
        'posts_per_page' => 15,
    );

    // Récupérer les vendeurs
    $users = get_users( $args );
    $output = '<div class="dokan-store-carousel owl-carousel">';

    foreach ( $users as $user ) {
        // Récupérer les informations de la boutique
        $store_info = dokan_get_store_info( $user->ID );
        $store_name = isset($store_info['store_name']) ? $store_info['store_name'] : 'Boutique sans nom';
        $store_url = dokan_get_store_url( $user->ID );
        $store_banner = isset( $store_info['banner'] ) ? wp_get_attachment_url( $store_info['banner'] ) : 'URL_D_IMAGE_PAR_DÉFAUT';
        $profile_picture = isset( $store_info['gravatar'] ) ? $store_info['gravatar'] : '';
        
        if( !empty( $store_banner ) ) {
            // Créer chaque item du carrousel
            $output .= '<div class="store-item">'; // Corriger l'opacité
            $output .= '<a href="' . esc_url( $store_url ) . '">';
            
            // Corriger la syntaxe de background-image
            $output .= '<div style="width: 100%; height: 200px; position: relative; overflow: hidden; border-radius: 5%; padding: 10px; box-shadow: 0px 1px 0.5px #707070; background-image: url(' . esc_url( $store_banner ) . '); background-size: cover; background-position: center;">';
            
            $output .= '</div>';
            $output .= '<br><h4 style="font-size: 0.9em !important;"><center><i>' . esc_html( $store_name ) . '</i></center></h4>';
            $output .= '</a>';
            $output .= '</div>';
        }
    }

    $output .= '</div>';

    // Initialisation du carrousel OwlCarousel
    $output .= '<script>
    jQuery(document).ready(function($){
        $(".dokan-store-carousel").owlCarousel({
            items: 6,
            loop: true,
            dots: false,
            margin: 10,
            nav: true,
            autoplay: true,
            autoplayTimeout: 3000,
            responsive: {
                0: { items: 2 },
                600: { items: 3 },
                1000: { items: 6 }
            }
        });
    });
    </script>';

    return $output;
}
add_shortcode( 'dokan_store_carousel', 'dokan_store_carousel_shortcode' );

function display_parent_categories_carousel() {
    // Récupérer toutes les catégories mères des produits
    $args = array(
        'taxonomy'   => 'product_cat',
        'parent'     => 0,
        'hide_empty' => false,
        'number'     => 12,
    );
    
    $categories = get_terms( $args );
    $output = '';

    if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
        $output .= '<div class="owl-carousel owl-theme">';

        foreach ( $categories as $category ) {
            $category_name = esc_html( $category->name );
            $category_link = esc_url( get_term_link( $category ) );
            $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
            $category_image = wp_get_attachment_url( $thumbnail_id );

            if ( ! empty( $category_image ) ) {
                $output .= '<div class="category-item">';
                $output .= '<a href="' . $category_link . '">';
                $output .= '<div style="width: 100%; height: 180px; position: relative; overflow: hidden; border-radius: 5%; padding: 0px; box-shadow: 0px 0px 2px #707070; background-image: url(' . esc_url( $category_image ) . '); background-size: cover; background-position: center;">';
                $output .= '<div style="background: rgba(255, 255, 255, 0.8); width: 100%; bottom : 15px; left : 0; position : absolute; padding : 10px 0px"><h4 style="font-size: 0.9em !important; color : #B55298; font-weight : 600"><center>' . $category_name . '</center></h4></div>';
                $output .= '</div>';
                $output .= '</a>';
                $output .= '</div>';
            }
        }

        $output .= '</div>';
    } else {
        $output .= '<p>Aucune catégorie mère trouvée.</p>';
    }

    return $output;
}

add_shortcode( 'parent_categories_carousel', 'display_parent_categories_carousel' );

// Enqueue the OWL Carousel assets
function enqueue_owl_carousel_assets() {
    // Charger les scripts et les styles pour Owl Carousel
    wp_enqueue_style( 'owl-carousel-css', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css', array(), '2.3.4' );
    wp_enqueue_script( 'owl-carousel-js', 'https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js', array('jquery'), '2.3.4', true );

    // Initialisation du script pour le carrousel
    wp_add_inline_script( 'owl-carousel-js', "
        jQuery(document).ready(function($) {
            $('.owl-carousel').owlCarousel({
                loop: true,
                margin: 10,
                nav: true,
                dots: false,
                autoplay: true,
                autoplayTimeout: 3000,
                responsive: {
                    0: {
                        items: 2
                    },
                    600: {
                        items: 5
                    },
                    1000: {
                        items: 7
                    }
                }
            });
        });
    ");
}
add_action( 'wp_enqueue_scripts', 'enqueue_owl_carousel_assets' );

