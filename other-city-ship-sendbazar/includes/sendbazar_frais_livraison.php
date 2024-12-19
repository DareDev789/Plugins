<?php

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    function sendbazar_frais_livraison_init() {
        if (!class_exists('WC_sendbazar_frais_livraison')) {
            class WC_sendbazar_frais_livraison extends WC_Shipping_Method {
                public function __construct() {
                    $this->id                 = 'sendbazar_frais_livraison';
                    $this->method_title       = __( 'Sendbazar Frais Livraison' );
                    $this->method_description = __( 'Description of your shipping method' );

                    $this->enabled            = "yes";
                    $this->title              = __( 'Livraison par Sendbazar', 'sendbazar' );

                    $this->init();
                }

                public function init() {
                    $this->init_form_fields();
                    $this->init_settings();

                    add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
                }

                public function init_form_fields() {
                    $this->form_fields = array(
                        'enabled' => array(
                            'title'       => __( 'Enable/Disable', 'sendbazar' ),
                            'type'        => 'checkbox',
                            'label'       => __( 'Enable this shipping method', 'sendbazar' ),
                            'default'     => 'yes',
                        ),
                        'title' => array(
                            'title'       => __( 'Title', 'sendbazar' ),
                            'type'        => 'text',
                            'description' => __( 'Title to be displayed during checkout', 'sendbazar' ),
                            'default'     => __( 'Livraison par Sendbazar', 'sendbazar' ),
                            'desc_tip'    => true,
                        ),
                    );
                }

                public function calculate_shipping($package = []) {
                    $geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;
                
                    if ($geolocation) {
                        $destination_lat = $geolocation[0];
                        $destination_lon = $geolocation[1];
                
                        // Récupérer les coordonnées et poids de tous les produits
                        $products = [];
                        foreach ($package['contents'] as $content) {
                            $id_produit = $content['product_id'];
                            $latitude = get_post_meta($id_produit, 'dokan_geo_latitude', true); 
                            $longitude = get_post_meta($id_produit, 'dokan_geo_longitude', true);
                            $weight_init = (float) $content['data']->get_weight();
                            $quantity = (int) $content['quantity'];
                            $weight = $weight_init * $quantity;
                            if ($latitude && $longitude && $weight) { 
                                $products[] = [ 'id' => $id_produit, 
                                'latitude' => floatval($latitude), 
                                'longitude' => floatval($longitude), 
                                'weight' => floatval($weight), ]; 
                            } else { 
                                error_log("Coordonnées manquantes pour le produit ID: $id_produit");
                            } 
                        }

                        $clusters = $this->group_products_by_proximity($products, 35);
                
                        // Calculer les frais de livraison pour chaque groupe
                        $total_cost = 0;
                        foreach ($clusters as $cluster) {
                            $total_weight = array_sum(array_column($cluster, 'weight'));
                            $representative = $cluster[0];
                            $distance = $this->calculate_geodistance(
                                $representative['latitude'], 
                                $representative['longitude'], 
                                $destination_lat, 
                                $destination_lon
                            );

                            $total_cost += $this->calculate_fees_based_on_weight($distance, $total_weight, $representative['latitude'], $representative['longitude'], $destination_lat, $destination_lon);
                        }
                        $mytotal_Cost = $total_cost + ($total_cost * 0.20);

                        $currency = get_woocommerce_currency();

                        if ($currency !== 'EUR') {
                            $conversion_rate = $this->get_conversion_rate($currency);
                            $mytotal_Cost = $mytotal_Cost * $conversion_rate;
                        }
                
                        // Ajouter le tarif
                        $this->add_rate([
                            'id' => $this->id,
                            'label' => $this->title,
                            'cost' => $mytotal_Cost,
                            'calc_tax' => 'per_item',
                        ]);
                    }
                }

                private function group_products_by_proximity($products, $max_distance) {
                    $clusters = [];
                
                    foreach ($products as $product) {
                        $added = false;
                
                        foreach ($clusters as &$cluster) {
                            foreach ($cluster as $cluster_product) {
                                $distance = $this->calculate_geodistance(
                                    $product['latitude'],
                                    $product['longitude'],
                                    $cluster_product['latitude'],
                                    $cluster_product['longitude']
                                );
                
                                if ($distance <= $max_distance) {
                                    $cluster[] = $product;
                                    $added = true;
                                    break;
                                }
                            }
                
                            if ($added) break;
                        }
                
                        if (!$added) {
                            $clusters[] = [$product];
                        }
                    }
                
                    return $clusters;
                }
                
                private function calculate_geodistance($lat1, $lon1, $lat2, $lon2) {
                    $earth_radius = 6371; 
                    $dLat = deg2rad($lat2 - $lat1); 
                    $dLon = deg2rad($lon2 - $lon1); 
                    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2); 
                    $c = 2 * atan2(sqrt($a), sqrt(1 - $a)); 
                    $distance = $earth_radius * $c; 
                    return round($distance, 2); 
                }
                
                
                private function calculate_fees_based_on_weight($distance, $total_weight, $from_lat, $from_lon, $to_lat, $to_lon) {
                    $json = file_get_contents(plugin_dir_path(__FILE__) . 'rates.json');
                    $routes = json_decode($json, true)['routes'];
                
                    if ($distance > 35) {
                        foreach ($routes as $route) {
                            $route_from = $route['from']['coordinates'];
                            $route_to = $route['to']['coordinates'];
                            $reciprocal = $route['reciprocal'] ?? false;
                
                            $direct_match = $this->coordinates_match($from_lat, $from_lon, $to_lat, $to_lon, $route_from, $route_to);
                            $reciprocal_match = $reciprocal && $this->coordinates_match($from_lat, $from_lon, $to_lat, $to_lon, $route_to, $route_from);
                
                            if ($direct_match || $reciprocal_match) {
                                foreach ($route['prices'] as $price_range) {
                                    if ($total_weight >= $price_range['min_weight'] && $total_weight <= $price_range['max_weight']) {
                                        $prix_total = $price_range['price'];
                                    }

                                    // Si le poids est supérieur à la plage maximale de la dernière plage définie
                                    if ($index == count($route['prices']) - 1 && $total_weight > $price_range['max_weight']) {
                                        $base_cost_per_km = 0.0031;
                                        $additional_cost_per_kg = 1.14;
                                        $prix_total = ($distance * $base_cost_per_km) + ($additional_cost_per_kg * sqrt($total_weight));
                                    }
                                }
                            }
                            $base_prices = [
                                15 => 1.6,
                                65 => 3.2,
                                250 => 6.4,
                                'default' => 11.4,
                            ];
                    
                            foreach ($base_prices as $max_weight => $price) {
                                if ($total_weight <= $max_weight || $max_weight === 'default') {
                                    $additionnal_price_coursier = $price;
                                }
                            }
                            return $prix_total + $additionnal_price_coursier;
                        }
                        return 0;
                    } else {
                        $base_prices = [
                            15 => 1.6,
                            65 => 3.2,
                            250 => 6.4,
                            'default' => 11.4,
                        ];
                
                        foreach ($base_prices as $max_weight => $price) {
                            if ($total_weight <= $max_weight || $max_weight === 'default') {
                                return $price;
                            }
                        }
                    }
                
                    return 0;
                }                
                
                
                private function coordinates_match($from_lat, $from_lon, $to_lat, $to_lon, $route_from, $route_to) {
                    $from_distance = $this->calculate_geodistance($from_lat, $from_lon, $route_from['latitude'], $route_from['longitude']);
                    $to_distance = $this->calculate_geodistance($to_lat, $to_lon, $route_to['latitude'], $route_to['longitude']);
                
                    return ($from_distance <= 35 && $to_distance <= 35);
                }                   
            }
        }
    }

    add_action('woocommerce_shipping_init', 'sendbazar_frais_livraison_init');

    function add_sendbazar_frais_livraison($methods) {
        $methods['sendbazar_frais_livraison'] = 'WC_sendbazar_frais_livraison';
        return $methods;
    }

    add_filter('woocommerce_shipping_methods', 'add_sendbazar_frais_livraison');
}
