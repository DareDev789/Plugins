<?php

function get_rates_data() {
    $rates_file_path = plugin_dir_path(__FILE__) . 'rates.json';
    if (file_exists($rates_file_path)) {
        $json_data = file_get_contents($rates_file_path);
        $decoded_data = json_decode($json_data, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded_data;
        } else {
            error_log('Erreur JSON dans rates.json : ' . json_last_error_msg());
        }
    } else {
        error_log('Fichier rates.json introuvable : ' . $rates_file_path);
    }
    return [];
}


// Ajouter une icône à côté des produits appartenant aux catégories sélectionnées
function ocss_sendbazar_add_icon_to_product_name($title, $id) {
    if (is_admin() || get_post_type($id) !== 'product') {
        return $title;
    }

    $selected_categories = get_option('ocss_selected_categories', []);
    
    $geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;
    if (!$geolocation) {
        return $title;
    }

    $latitudeLiv = $geolocation[0];
    $longitudeLiv = $geolocation[1];
    $adress_productLiv = $geolocation[2];

    $product_categories = wp_get_post_terms($id, 'product_cat', ['fields' => 'ids']);
    if (is_wp_error($product_categories)) {
        return $title; 
    }

    $all_selected_categories = [];
    foreach ($selected_categories as $category_id) {
        $children = get_term_children($category_id, 'product_cat');
        $all_selected_categories = array_merge($all_selected_categories, [$category_id], $children);
    }

    if (array_intersect($all_selected_categories, $product_categories)) {
        $product_latitude = get_post_meta($id, 'dokan_geo_latitude', true);
        $product_longitude = get_post_meta($id, 'dokan_geo_longitude', true);
        $product_adresse = get_post_meta($id, 'dokan_geo_address', true);

        if (!empty($product_latitude) && !empty($product_longitude)) {
            $rates_data = get_rates_data();

            if (!empty($rates_data) && isset($rates_data['routes'])) {
                foreach ($rates_data['routes'] as $route) {
                    $from_coords = $route['from']['coordinates'] ?? null;
                    $to_coords = $route['to']['coordinates'] ?? null;

                    if ($from_coords && $to_coords) {
                        if (!ocss_is_within_radius($product_latitude, $product_longitude, $latitudeLiv, $longitudeLiv) &&
                            (
                                (ocss_is_within_radius($latitudeLiv, $longitudeLiv, $to_coords['latitude'], $to_coords['longitude']) ||
                                ocss_is_within_radius($latitudeLiv, $longitudeLiv, $from_coords['latitude'], $from_coords['longitude']))
                                &&
                                (ocss_is_within_radius($product_latitude, $product_longitude, $to_coords['latitude'], $to_coords['longitude']) ||
                                ocss_is_within_radius($product_latitude, $product_longitude, $from_coords['latitude'], $from_coords['longitude']))
                            )
                        ) {
                            $icon = '<span style="color: #0CE4E4; font-size: 0.7em !important; margin-left: 5px;"><br>Livraison possible depuis ' . esc_html($product_adresse) . '</span>';
                            $title .= $icon;
                            break;
                        }
                    }
                }
            }
        }
    }

    return $title;
}
add_filter('the_title', 'ocss_sendbazar_add_icon_to_product_name', 10, 2);

function ocss_is_within_radius($product_latitude, $product_longitude, $city_latitude, $city_longitude, $radius = 35) {
    $earth_radius = 6371;
    $lat_diff = deg2rad($city_latitude - $product_latitude);
    $lon_diff = deg2rad($city_longitude - $product_longitude);
    $a = sin($lat_diff / 2) * sin($lat_diff / 2) +
        cos(deg2rad($product_latitude)) * cos(deg2rad($city_latitude)) *
        sin($lon_diff / 2) * sin($lon_diff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $distance = $earth_radius * $c;
    return ($distance <= $radius);
}




function Ocss_display_same_category_products() {
    if (is_product()) {
        global $product;

        // Récupération des informations de base
        $product_id = $product->get_id();
        
        $terms = wp_get_post_terms($product_id, 'product_cat');
        $top_parent_category_ids = [];

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $ancestors = get_ancestors($term->term_id, 'product_cat');
                if (!empty($ancestors)) {
                    $top_parent = end($ancestors);
                    $top_parent_category_ids[] = $top_parent;
                } else {
                    $top_parent_category_ids[] = $term->term_id;
                }
            }
        }

        $top_parent_category_ids = array_unique($top_parent_category_ids);
        $selected_categories = get_option('ocss_selected_categories', []);
        $all_selected_categories = [];
        foreach ($selected_categories as $category_id) {
            $children = get_term_children($category_id, 'product_cat');
            $all_selected_categories = array_merge($all_selected_categories, [$category_id], $children);
        }

        $filtered_top_parent_category_ids = array_intersect($top_parent_category_ids, $all_selected_categories);

        // Récupération des informations géographiques
        $geolocation = isset($_COOKIE['geolocation']) ? explode(',', sanitize_text_field($_COOKIE['geolocation'])) : null;

        $rates_data = get_rates_data();
        $all_product_idsNow = [];
        $ville = 'cette région';

        if ($geolocation) {
            $radius = 35;
            $radius_in_degrees = $radius / 111;
            $latitudeLiv = (float) $geolocation[0];
            $longitudeLiv = (float) $geolocation[1];
            $ville = sanitize_text_field($geolocation[2]);

            if (!empty($rates_data) && isset($rates_data['routes'])) {
                foreach ($rates_data['routes'] as $route) {
                    $from_coords = $route['from']['coordinates'] ?? null;
                    $to_coords = $route['to']['coordinates'] ?? null;

                    if ($from_coords && $to_coords) {
                        // Calcul des coordonnées pour la recherche
                        if (ocss_is_within_radius($latitudeLiv, $longitudeLiv, $from_coords['latitude'], $from_coords['longitude'])) {
                            $longitude_minRates = $to_coords['longitude'] - $radius_in_degrees;
                            $longitude_maxRates = $to_coords['longitude'] + $radius_in_degrees;
                            $latitude_minRates = $to_coords['latitude'] - $radius_in_degrees;
                            $latitude_maxRates = $to_coords['latitude'] + $radius_in_degrees;
                        } elseif (ocss_is_within_radius($latitudeLiv, $longitudeLiv, $to_coords['latitude'], $to_coords['longitude'])) {
                            $longitude_minRates = $from_coords['longitude'] - $radius_in_degrees;
                            $longitude_maxRates = $from_coords['longitude'] + $radius_in_degrees;
                            $latitude_minRates = $from_coords['latitude'] - $radius_in_degrees;
                            $latitude_maxRates = $from_coords['latitude'] + $radius_in_degrees;
                        } else {
                            continue;
                        }

                        // Requête pour les produits
                        $meta_query_argsNow = array(
                            'post_type'      => 'product',
                            'posts_per_page' => -1,
                            'tax_query'      => array(
                                array(
                                    'taxonomy' => 'product_cat',
                                    'field'    => 'term_id',
                                    'terms'    => $filtered_top_parent_category_ids,
                                    'operator' => 'IN',
                                ),
                            ),
                            'meta_query'     => array(
                                'relation' => 'AND',
                                array(
                                    'key'     => 'dokan_geo_longitude',
                                    'value'   => array($longitude_minRates, $longitude_maxRates),
                                    'compare' => 'BETWEEN',
                                    'type'    => 'DECIMAL(10,6)',
                                ),
                                array(
                                    'key'     => 'dokan_geo_latitude',
                                    'value'   => array($latitude_minRates, $latitude_maxRates),
                                    'compare' => 'BETWEEN',
                                    'type'    => 'DECIMAL(10,6)',
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

            // Exclure le produit actuel et limiter à 5 produits aléatoires
            $all_product_idsNow = array_diff($all_product_idsNow, [$product_id]);
            shuffle($all_product_idsNow);
            $random_product_ids = array_slice($all_product_idsNow, 0, 5);

            if (!empty($random_product_ids)) {
                $related_productsOcss = new WP_Query(array(
                    'post_type' => 'product',
                    'post__in'  => $random_product_ids,
                    'orderby'   => 'rand',
                ));

                if ($related_productsOcss->have_posts()) {
                    echo '<h2 style="font-size: 1.3em !important;">Produits similaires livrables à ' . esc_html($ville) . '</h2>';
                    echo '<ul class="products columns-5">';

                    while ($related_productsOcss->have_posts()) {
                        $related_productsOcss->the_post();
                        wc_get_template_part('content', 'product');
                    }

                    echo '</ul>';
                    wp_reset_postdata();
                }
            }
        }
    }
}
add_action('woocommerce_after_single_product_summary', 'Ocss_display_same_category_products', 25);






