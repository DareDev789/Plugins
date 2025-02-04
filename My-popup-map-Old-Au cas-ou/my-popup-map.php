<?php
/*
Plugin Name: My Popup Map
Description: Un plugin pour afficher google Map qui affiche des pays ou des villes qui ont des produits sur le marketPlace mais qui marche avec des produits enregistrés avec la géolocalisation
Version: 1.2
Author: Sendbazar (Wallin Razafindrazokiny)
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'functions.php';

function my_popup_map_scripts()
{
    wp_enqueue_script(
        'my-popup-map-script',
        plugins_url('my-popup-map.js', __FILE__),
        array('jquery'),
        time(),
        true
    );
}
add_action('wp_enqueue_scripts', 'my_popup_map_scripts');

function my_popup_map_style()
{
    wp_enqueue_style(
        'my-popup-map-style',
        plugins_url('my-popup-map.css', __FILE__),
        array(),
        time(),
        'all'
    );
}
add_action('wp_enqueue_scripts', 'my_popup_map_style');

function add_sweetalert()
{
    wp_enqueue_script('sweetalert', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', [], null, true);
}
add_action('wp_enqueue_scripts', 'add_sweetalert');


// Ajouter une page dans le menu d'administration
function register_popup_map_menu()
{
    add_menu_page(
        'Popup Map Menu',               // Titre de la page
        'Popup Map Menu',               // Texte du menu
        'manage_options',               // Capacité requise
        'popup_map_menu',               // Slug de la page
        'render_popup_map_menu_page',   // Fonction d'affichage
        'dashicons-location',           // Icône
        80                              // Position
    );
}
add_action('admin_menu', 'register_popup_map_menu');

// Contenu de la page
function render_popup_map_menu_page()
{
    if (isset($_POST['reset_cache']) && check_admin_referer('reset_cache_action')) {
        delete_transient('grouped_geolocation_products');
        get_products_geolocation_data_grouped();
        echo '<div class="notice notice-success"><p>Le cache a été réinitialisé avec succès.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Popup Map Menu - Gestion du Cache</h1>
        <p>Réinitialisez le cache des produits groupés géolocalisés si nécessaire.</p>
        <form method="post">
            <?php wp_nonce_field('reset_cache_action'); ?>
            <input type="hidden" name="reset_cache" value="1">
            <button type="submit" class="button button-primary">Réinitialiser le Cache</button>
        </form>
    </div>
    <?php
}

function my_popup_map_html()
{
    ?>
    <style>
        .plus,
        .minus {
            color: #707070 !important;
        }

        .imagePopup {
            width: 80%;
        }

        .content_list_ville {
            padding-top: 50px;
            padding: 50px;
        }

        .tropgrand {
            font-size: 0.7em;
        }

        #my-popup button {
            font-size: 0.65em;
        }

        body {
            width: 100% !important;
            padding: auto 3px;
        }

        @keyframes scale-animation {
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

        @media (max-width: 600px) {
            .Acacher {
                display: none;
            }

            .imagePopup {
                max-height: 20vh;
                width: auto;
                margin-top: 20px;
            }

            .content_list_ville {
                padding: 35px 30px !important;
                padding-bottom: 20px;
            }

            #my-popup button {
                font-size: 0.6em;
            }

            .duser-role {
                padding-left: 5px;
                position: relative;
            }

            #blog {
                padding: auto 15px;
            }
        }
    </style>
    <div id="my-popup-overlay" onclick="closeMap()"
        style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:9998;">
    </div>
    <div id="my-popup"
        style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%, -50%); width:80%; height:auto; background-color:white; z-index:999999; padding:0px; box-shadow:0 0 10px rgba(0,0,0,0.5);">
        <!-- Div superposée pour les boutons -->
        <div
            style="position: absolute; top: 0px; left: 50%; transform: translate(-50%, 0); display: inline-flex; flex-wrap: nowrap; z-index: 5000">
            <div>
                <button disabled
                    style="color: #707070 !important; background: #fff !important; border: none; white-space: nowrap; font-size: 1.5vw;"
                    id="listeVille" onclick="toggleMap()">
                    <i class="fas fa-list"></i> <span class="">Liste des villes</span>
                </button>
            </div>
            <div>
                <button
                    style="color: #fff !important; background: #f5848c !important; border: none; white-space: nowrap; font-size: 1.5vw;"
                    id="carteVille" onclick="toggleMap()">
                    <i class="fas fa-map"></i> <span class="">Carte des villes</span>
                </button>
            </div>
        </div>

        <!-- Première map -->
        <div id="map1"
            style="width:100%; height:65vh; position:relative; margin:0px; padding:10px; overflow:auto; display:none;">
            <?php echo do_shortcode('[geolocation_map_grouped]'); ?>
        </div>

        <!-- Deuxième map (cachée par défaut) -->
        <div id="mapvers2" style="width:100%; position:relative;">
            <div class="row"
                style="align-items: center position: relative; max-height : 80vh; overflow: auto; padding-top : 35px !important;  padding:15px 5px; background-image: url('<?php echo plugins_url('fond.webp', __FILE__); ?>'); background-position:center right; background-size:cover;">
                <div class="col-xl-5 col-lg-5 col-md-5 col-sm-12 col-12 Acacher">
                    <center>
                        <img class="imagePopup" src="<?php echo plugins_url('homme_montrant_mada-01.webp', __FILE__); ?>">
                    </center>
                </div>
                <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 content_list_ville"
                    style="padding: 2px 5px; position : relative">
                    <h2
                        style="font-size:1.5em; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7); color : #f5848c; font-weight : 700">
                        Déjà disponible dans plusieurs villes de Madagascar</h2>
                    <div class="elementor-widget-container">
                        <p style="color:#fff;" class="Acacher">Qu’il s’agisse de courses alimentaires, de matériaux de
                            construction pour vos projets à Madagascar, de cadeaux mode ou de préparation d’événements
                            locaux, vous trouverez tout ce dont vous avez besoin sur Sendbazar.</p>
                        <hr>
                        <p style="color:#fff;">Choisissez votre ville de livraison !</p>
                    </div>
                    <?php echo do_shortcode('[geolocation_form1]'); ?>
                </div>
            </div>
        </div>

        <!-- Bouton de fermeture -->
        <button onclick="closeMap()"
            style="position:absolute; top:-10px; right:-10px; background:red; border-radius: 50%; z-index:99990;">X</button>
    </div>
    <?php
}
add_action('wp_footer', 'my_popup_map_html');


add_filter('woocommerce_ship_to_different_address_checked', '__return_true');
add_filter('woocommerce_checkout_fields', 'customize_checkout_fields');
function customize_checkout_fields($fields)
{
    $fields['shipping']['shipping_phone'] = array(
        'label' => __('Phone', 'woocommerce'),
        'required' => true,
        'class' => array('form-row-wide'),
        'clear' => true,
        'default' => ''
    );
    return $fields;
}

add_action('woocommerce_checkout_update_order_meta', 'save_shipping_phone');
function save_shipping_phone($order_id)
{
    if (!empty($_POST['shipping_phone'])) {
        update_post_meta($order_id, '_shipping_phone', sanitize_text_field($_POST['shipping_phone']));
    }
}

add_action('wp_enqueue_scripts', 'add_custom_checkout_script');
function add_custom_checkout_script()
{
    if (is_checkout()) {
        wp_enqueue_script('custom-checkout-script', plugins_url('/js/custom-checkout.js', __FILE__), array('jquery'), time(), true);
        wp_enqueue_script('fuse-js', 'https://cdn.jsdelivr.net/npm/fuse.js@6.4.6', array(), null, true);
    }
}


function custom_pre_get_posts_query($query)
{
    if (is_admin() || (!$query->is_main_query() && !isset($query->query_vars['is_carousel']))) {
        return;
    }

    if ($query->is_post_type_archive('product') || $query->is_tax('product_cat') || $query->is_tax('product_tag')) {

        // Obtenir la géolocalisation depuis les cookies (si disponible)
        $geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;

        $pharmacie_category_id = get_term_by('slug', 'pharmacie', 'product_cat')->term_id;
        $child_categories = get_term_children($pharmacie_category_id, 'product_cat');
        $all_excluded_categories = array_merge([$pharmacie_category_id], $child_categories);

        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = array();
        }
        $tax_query = $query->get('tax_query');
        if (!is_array($tax_query)) {
            $tax_query = array();
        }

        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => $all_excluded_categories,
            'operator' => 'NOT IN',
        );

        $query->set('tax_query', $tax_query);


        if ($geolocation) {
            $latitude = $geolocation[0];
            $longitude = $geolocation[1];

            $radius = 35;

            $radius_in_degrees = $radius / 111;
            $longitude_min = $longitude - $radius_in_degrees;
            $longitude_max = $longitude + $radius_in_degrees;
            $latitude_min = $latitude - $radius_in_degrees;
            $latitude_max = $latitude + $radius_in_degrees;

            $all_product_ids = array();
            $all_product_idsNow = array();

            $meta_query_args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $all_excluded_categories,
                        'operator' => 'NOT IN',
                    ),
                ),
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'dokan_geo_longitude',
                        'value' => array($longitude_min, $longitude_max),
                        'compare' => 'BETWEEN',
                        'type' => 'DECIMAL(10,6)',
                    ),
                    array(
                        'key' => 'dokan_geo_latitude',
                        'value' => array($latitude_min, $latitude_max),
                        'compare' => 'BETWEEN',
                        'type' => 'DECIMAL(10,6)',
                    ),
                ),
                'fields' => 'ids',
            );
            $meta_query_result = new WP_Query($meta_query_args);

            if ($meta_query_result->have_posts()) {
                $all_product_ids = $meta_query_result->posts;
            }

            if (is_plugin_active('other-city-ship-sendbazar/other-city-ship-sendbazar.php')) {

                $selected_categories = get_option('ocss_selected_categories', []);
                $all_selected_categories = [];
                foreach ($selected_categories as $category_id) {
                    $children = get_term_children($category_id, 'product_cat');
                    $all_selected_categories = array_merge($all_selected_categories, [$category_id], $children);
                }
                $rates_data = get_rates_data();

                if (!empty($rates_data) && isset($rates_data['routes']) && !empty($selected_categories)) {
                    foreach ($rates_data['routes'] as $route) {
                        $from_coords = $route['from']['coordinates'] ?? null;
                        $to_coords = $route['to']['coordinates'] ?? null;

                        if ($from_coords && $to_coords) {
                            if (ocss_is_within_radius($latitude, $longitude, $from_coords['latitude'], $from_coords['longitude'])) {
                                $longitude_minRates = $to_coords['longitude'] - $radius_in_degrees;
                                $longitude_maxRates = $to_coords['longitude'] + $radius_in_degrees;
                                $latitude_minRates = $to_coords['latitude'] - $radius_in_degrees;
                                $latitude_maxRates = $to_coords['latitude'] + $radius_in_degrees;

                            } else if (ocss_is_within_radius($latitude, $longitude, $to_coords['latitude'], $to_coords['longitude'])) {
                                $longitude_minRates = $from_coords['longitude'] - $radius_in_degrees;
                                $longitude_maxRates = $from_coords['longitude'] + $radius_in_degrees;
                                $latitude_minRates = $from_coords['latitude'] - $radius_in_degrees;
                                $latitude_maxRates = $from_coords['latitude'] + $radius_in_degrees;
                            } else {
                                continue;
                            }
                            $meta_query_argsNow = array(
                                'post_type' => 'product',
                                'posts_per_page' => -1,
                                'tax_query' => array(
                                    array(
                                        'taxonomy' => 'product_cat',
                                        'field' => 'term_id',
                                        'terms' => $all_selected_categories,
                                        'operator' => 'IN',
                                    ),
                                ),
                                'meta_query' => array(
                                    'relation' => 'AND',
                                    array(
                                        'key' => 'dokan_geo_longitude',
                                        'value' => array($longitude_minRates, $longitude_maxRates),
                                        'compare' => 'BETWEEN',
                                        'type' => 'DECIMAL(10,6)',
                                    ),
                                    array(
                                        'key' => 'dokan_geo_latitude',
                                        'value' => array($latitude_minRates, $latitude_maxRates),
                                        'compare' => 'BETWEEN',
                                        'type' => 'DECIMAL(10,6)',
                                    ),
                                ),
                                'fields' => 'ids',
                            );
                            $meta_query_resultNow = new WP_Query($meta_query_argsNow);
                            if ($meta_query_resultNow->have_posts()) {
                                $product_idsNow = $meta_query_resultNow->posts;
                                $all_product_idsNow = array_merge($all_product_idsNow, $product_idsNow);
                            }
                        }
                    }
                    $all_product_idsNow = array_unique($all_product_idsNow);
                }
            }
            $combined_product_ids = array_merge($all_product_ids, $all_product_idsNow);
            $combined_product_ids = array_unique($combined_product_ids);

            $query->set('post__in', $combined_product_ids);
        }
    }
}
add_action('pre_get_posts', 'custom_pre_get_posts_query', 99);

add_action('woocommerce_add_to_cart', function () {
    // Vérifier si le cookie 'geolocation' est défini
    if (!isset($_COOKIE['geolocation'])) {
        return;
    }

    // Récupérer la ville depuis le cookie
    list($latitude, $longitude, $city) = explode(',', sanitize_text_field($_COOKIE['geolocation']));


    // Liste des villes disponibles
    $villes = [
        ['nom' => 'Antananarivo', 'codePostal' => '101', 'region' => 'Analamanga', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Tananarive', 'codePostal' => '101', 'region' => 'Analamanga', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Diego Suarez', 'codePostal' => '201', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Diego-Suarez', 'codePostal' => '201', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Ambilobe', 'codePostal' => '204', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Antsiranana', 'codePostal' => '201', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Ambanja', 'codePostal' => '203', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Nosy Be', 'codePostal' => '207', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Hell Ville', 'codePostal' => '207', 'region' => 'Diana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Andapa', 'codePostal' => '205', 'region' => 'SAVA', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'District d\'Andapa', 'codePostal' => '205', 'region' => 'SAVA', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Vohemar', 'codePostal' => '206', 'region' => 'SAVA', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Sambava', 'codePostal' => '208', 'region' => 'SAVA', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Tamatave', 'codePostal' => '501', 'region' => 'Atsinanana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Toamasina', 'codePostal' => '501', 'region' => 'Atsinanana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Antalaha', 'codePostal' => '207', 'region' => 'SAVA', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Mahajanga', 'codePostal' => '401', 'region' => 'Boeny', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Fianarantsoa', 'codePostal' => '301', 'region' => 'Haute Matsiatra', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Toliara', 'codePostal' => '601', 'region' => 'Atsimo-Andrefana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Tuléar', 'codePostal' => '601', 'region' => 'Atsimo-Andrefana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Antsirabe', 'codePostal' => '110', 'region' => 'Vakinankaratra', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Morondava', 'codePostal' => '619', 'region' => 'Menabe', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Manakara', 'codePostal' => '316', 'region' => 'Vatovavy-Fitovinany', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Ambositra', 'codePostal' => '306', 'region' => 'Amoron’i Mania', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Ambatondrazaka', 'codePostal' => '503', 'region' => 'Alaotra-Mangoro', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Farafangana', 'codePostal' => '309', 'region' => 'Atsimo-Atsinanana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Morombe', 'codePostal' => '618', 'region' => 'Atsimo-Andrefana', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Mananjary', 'codePostal' => '317', 'region' => 'Vatovavy-Fitovinany', 'pays' => 'MG', 'paysName' => 'Madagascar'],
        ['nom' => 'Soavinandriana', 'codePostal' => '119', 'region' => 'Itasy', 'pays' => 'MG'],
        ['nom' => 'Tsiroanomandidy', 'codePostal' => '118', 'region' => 'Bongolava', 'pays' => 'MG', 'paysName' => 'Madagascar']
    ];

    $ville = array_values(array_filter($villes, function ($v) use ($city) {
        return stripos($v['nom'], $city) !== false;
    }));

    if (!empty($ville)) {
        $ville = $ville[0];

        $customer = WC()->customer;

        $customer->set_shipping_city($ville['nom']);
        $customer->set_shipping_postcode($ville['codePostal']);
        $customer->set_shipping_state($ville['region']);
        $customer->set_shipping_country($ville['pays']);
        $customer->save();
        
    }
}, 10, 0);
