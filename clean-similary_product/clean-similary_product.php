<?php
/*
Plugin Name: Clean Similary Product
Plugin URI: https://sendbazar.com
Description: Supprime les produits similaires de la page produit WooCommerce.
Version: 1.0
Author: Sendbazar (Wallin Razafindrazokiny)
Author URI: https://sendbazar.com
License: GPL2
*/

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Fonction pour retirer les produits similaires
function csp_remove_similar_products() {
    if (is_product()) {
        remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
    }
}
add_action('wp', 'csp_remove_similar_products');

function csp_display_same_category_products() {
    function pointInCoordinates($point, $coordinates) {
        $radius = 35;
        $radius_in_degrees = $radius / 111;
        $minLat = $coordinates[0][0] - $radius_in_degrees;
        $maxLat = $coordinates[0][0] + $radius_in_degrees;
        $minLon = $coordinates[0][1] - $radius_in_degrees;
        $maxLon = $coordinates[0][1] + $radius_in_degrees;
        foreach ($coordinates as $coordinate) {
            $minLat = min($minLat, $coordinate[0] - $radius_in_degrees);
            $maxLat = max($maxLat, $coordinate[0] + $radius_in_degrees);
            $minLon = min($minLon, $coordinate[1] - $radius_in_degrees);
            $maxLon = max($maxLon, $coordinate[1] + $radius_in_degrees);
        }
        return ($point[0] >= $minLat && $point[0] <= $maxLat &&
                $point[1] >= $minLon && $point[1] <= $maxLon);
    }
    $polygon = [];
    
    $villes_coords = get_option('dokan_champs_ordonnance_villes', '');
    $villes_coords_array = array();
    
    if ($villes_coords) {
        $villes_coords_lines = explode("\n", $villes_coords);
        foreach ($villes_coords_lines as $line) {
            list($ville, $coords) = explode(':', trim($line));
            $villes_coords_array[trim($ville)] = trim($coords);
        }
    }
    
    foreach ($villes_coords_array as $coords) {
        list($lat, $long) = explode(',', $coords);
        $polygon[] = [(float)$lat, (float)$long];
    }

    if (is_product()) {
        global $product;
        $terms = wp_get_post_terms($product->get_id(), 'product_cat');
        $pharmacie_product = get_post_meta($product->get_id(), 'pharmacie_produit', true);

        if ($terms && !is_wp_error($terms)) {
            $category_ids = wp_list_pluck($terms, 'term_id'); 

            // Récupérer les IDs des catégories mères
            $parent_category_ids = array();
            foreach ($category_ids as $category_id) {
                $parent_category_id = get_ancestors($category_id, 'product_cat');
                if (!empty($parent_category_id)) {
                    $parent_category_ids = array_merge($parent_category_ids, $parent_category_id);
                }
            }

            $all_category_ids = array_merge($category_ids, $parent_category_ids);

            $args = array(
                'post_type'      => 'product',
                'posts_per_page' => 5,
                'columns'        => 5,
                'post__not_in'   => array($product->get_id()),
                'is_carousel' => true,
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $all_category_ids, // Filtrer par catégories et parents
                    ),
                ),
                'meta_query'     => array(),
            );
            $geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;
            if($geolocation){
                $latitude = $geolocation[0];
                $longitude = $geolocation[1];
                $adress_product = $geolocation[2];
                
                // Rayon de recherche en kilomètres
                $radius = 35;

                // Conversion du rayon en degrés (1° de latitude = environ 111 km)
                $radius_in_degrees = $radius / 111;
                $longitude_min = $longitude - $radius_in_degrees;
                $longitude_max = $longitude + $radius_in_degrees;
                $latitude_min = $latitude - $radius_in_degrees;
                $latitude_max = $latitude + $radius_in_degrees;

                $args['meta_query'][] = array(
                    'relation' => 'AND',
                        array(
                            'key'     => 'dokan_geo_longitude',
                            'value'   => array($longitude_min, $longitude_max),
                            'compare' => 'BETWEEN', // Rechercher les produits en dehors de cette plage
                            'type'    => 'DECIMAL',
                        ),
                        array(
                            'key'     => 'dokan_geo_latitude',
                            'value'   => array($latitude_min, $latitude_max),
                            'compare' => 'BETWEEN', // Rechercher les produits en dehors de cette plage
                            'type'    => 'DECIMAL',
                        ),
                );
            }
            }


            // Exécuter la requête pour afficher les produits similaires
            $related_products = new WP_Query($args);

            if ($related_products->have_posts()) {
                echo '<h2 style="font-size: 1.3em !important;">Produits Similaires depuis '.$adress_product.'</h2>';
                echo '<ul class="products columns-5">'; // Ajout de la classe columns-5 pour afficher 5 produits par ligne

                while ($related_products->have_posts()) {
                    $related_products->the_post();
                    wc_get_template_part('content', 'product'); // Utiliser le template WooCommerce pour afficher les produits
                }

                echo '</ul>';
                wp_reset_postdata(); // Réinitialiser les données de la requête
            }
        }
}
add_action('woocommerce_after_single_product_summary', 'csp_display_same_category_products', 25);


function ppc_enqueue_carousel_scripts() {
    wp_enqueue_style('slick-carousel-css', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css');
    wp_enqueue_script('slick-carousel-js', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js', array('jquery'), '1.8.1', true);
    wp_enqueue_script('ppc-carousel-js', plugin_dir_url(__FILE__) . 'js/ppc-carousel.js', array('slick-carousel-js'), '1.0', true);
}
add_action('wp_enqueue_scripts', 'ppc_enqueue_carousel_scripts');

function ppc_popular_products_carousel() {

    $pharmacie_category_id = get_term_by('slug', 'pharmacie', 'product_cat')->term_id;
    $child_categories = get_term_children($pharmacie_category_id, 'product_cat');
    $all_excluded_categories = array_merge([$pharmacie_category_id], $child_categories);

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => 10,
    //    'is_carousel' => true,
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $all_excluded_categories,
                'operator' => 'NOT IN',
            ),
        ),
    );

    $query = new WP_Query($args);

    $output = '<div class="ppc-carousel">';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            global $product;
            
            $output .= '<div class="ppc-product">';
            $output .= '<div class="ppc_div_images"><a href="' . get_permalink() . '">' . woocommerce_get_product_thumbnail() . '</a></div>';
            $output .= '<p class="ppc-product-title"><a href="' . get_permalink() . '">' . get_the_title() . '</a></p>';
            $output .= '<span class="ppc-product-price">' . $product->get_price_html() . '</span>';
            $output .= '</div>';
        }
        wp_reset_postdata();
    }

    $output .= '</div>';
    ?>
    <style>
        .ppc-carousel {
            margin: 20px 0;
        }
        .ppc-product {
            text-align: center;
        }
        .ppc-product:hover img{
            transform : scale(1.1);
            transition : 0.5s ease;
        }
        .ppc_div_images img {
            max-width: 100%;
            height: auto;
            transition : 0.5s ease;
        }
        .ppc_div_images{
            margin : 5px; 
            box-shadow : 0px 0px 1px #707070;
            position: relative;
            overflow: hidden;
        }
        .ppc-product-title a{
            font-size: 1em;
            margin-top: 10px;
            color : #707070;
            font-weight : 500;
        }
        .ppc-product-price {
            color: #F5848C;
            font-weight: 400;
        }
    </style>
    <?php
    return $output;
}
add_shortcode('popular_products_carousel', 'ppc_popular_products_carousel');


function spinner(){
    ?>
        <div id="loading-spinner" style="display: none;">
            <img src="<?php echo plugins_url('my-loader.gif', __FILE__); ?>" alt="Chargement...">
        </div>
        <style>
            #loading-spinner {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 99999999;
                display: flex;
                justify-content: center;
                align-items: center;
            }
            @keyframes zoom {
                0% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.2);
                }
                100% {
                    transform: scale(1);
                }
            }
            .popmake-content{
                max-height : 60vh;
                overflow: auto;
            }

            #loading-spinner img {
                width: 250px;
                max-width: 40%;
                animation: zoom 2s infinite;
            }
            @media (max-width: 668px) {
                #blog{
                    margin-top: 5vh !important;
                }
                #main{
                    margin-top: 12vh !important;
                }
            }
        </style>
        <script>
            window.addEventListener("beforeunload", function() {
                var spinner = document.getElementById('loading-spinner');
                // Vérifiez si l'URL contient "mon-compte" ou "tableau-de-bord"
                if (!window.location.href.includes("mon-compte") && !window.location.href.includes("tableau-de-bord") && !window.location.href.includes("espace-vendeurs")) {
                    spinner.style.display = 'flex';
                }
            });

            (function($) {
                $(document).ajaxStart(function() {
                    if (!window.location.href.includes("mon-compte") && !window.location.href.includes("tableau-de-bord")  && !window.location.href.includes("espace-vendeurs")) {
                        $('#loading-spinner').fadeIn();
                    }
                }).ajaxStop(function() {
                    $('#loading-spinner').fadeOut();
                });
            })(jQuery);
        </script>
    <?php
}

add_action('wp_footer', 'spinner');
