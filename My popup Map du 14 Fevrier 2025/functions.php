<?php

function calculer_distance($lat1, $lon1, $lat2, $lon2) {
    $rayon_terre = 6371; // Rayon de la Terre en kilomètres

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $rayon_terre * $c;
}

function get_products_geolocation_data_grouped() {
    $cache_key = 'grouped_geolocation_products';
    $cached_products = get_transient($cache_key);
    if (false !== $cached_products) {
        return $cached_products;
    }

    $args = array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => 'dokan_geo_latitude',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => 'dokan_geo_longitude',
                'compare' => 'EXISTS',
            ),
        ),
        'orderby'        => 'date',
        'order'          => 'ASC',
    );

    $query = new WP_Query($args);
    $products = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product_id = get_the_ID();
            $latitude   = get_post_meta($product_id, 'dokan_geo_latitude', true);
            $longitude  = get_post_meta($product_id, 'dokan_geo_longitude', true);
            $address    = get_post_meta($product_id, 'dokan_geo_address', true);

            if (empty($latitude) || empty($longitude) || empty($address)) {
                continue;
            }

            $address_parts = explode(',', $address);
            $country = trim(end($address_parts));
            $grouped = false;

            if (!isset($products[$country])) {
                $products[$country] = array();
            }

            foreach ($products[$country] as $city => $addresses) {
                foreach ($addresses as $group_address => $group_products) {
                    $group_lat = $group_products[0]['latitude'];
                    $group_lon = $group_products[0]['longitude'];
                    $distance = calculer_distance($latitude, $longitude, $group_lat, $group_lon);

                    if ($distance <= 35) {
                        $products[$country][$city][$group_address][] = array(
                            'product_id' => $product_id,
                            'title'      => get_the_title(),
                            'latitude'   => $latitude,
                            'longitude'  => $longitude,
                            'address'    => $address,
                        );
                        $grouped = true;
                        break 2;
                    }
                }
            }

            if (!$grouped) {
                $city = $address;

                if (!isset($products[$country][$city])) {
                    $products[$country][$city] = array();
                }

                $products[$country][$city][$address][] = array(
                    'product_id' => $product_id,
                    'title'      => get_the_title(),
                    'latitude'   => $latitude,
                    'longitude'  => $longitude,
                    'address'    => $address,
                );
            }
        }
        wp_reset_postdata();
    }

    set_transient($cache_key, $products, HOUR_IN_SECONDS);

    return $products;
}


//Tsy mahazo mandeha am page de validation de commande izy koa tsisy ville de livraison
add_action('template_redirect', function () {
    if (is_checkout() && !is_wc_endpoint_url('order-received')) {
        if (!isset($_COOKIE['geolocation']) || empty($_COOKIE['geolocation'])) {
            wp_redirect(home_url());
            exit;
        }
    }
});


function display_geolocation_map_grouped() {
    $dokan_appearance = get_option('dokan_appearance', array());
    $api_key = '';

    if (!empty($dokan_appearance['gmap_api_key']) && 'google_maps' === $dokan_appearance['map_api_source']) {
        $api_key = $dokan_appearance['gmap_api_key'];
    }
    $products = get_products_geolocation_data_grouped();
    
    ?>
    <div id="geolocation-map" style="width: 100%; height: 100%;"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                initCustomMap(<?php echo json_encode($products, JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
            } else {
                var script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo esc_attr($api_key); ?>&callback=function() { initCustomMap(<?php echo json_encode($products, JSON_HEX_APOS | JSON_HEX_QUOT); ?>); }';
                script.async = true;
                script.defer = true;
                document.body.appendChild(script);
            }
        });
    </script>
    <?php
}

function geolocation_map_grouped_shortcode() {
    ob_start();
    display_geolocation_map_grouped();
    return ob_get_clean();
}
add_shortcode('geolocation_map_grouped', 'geolocation_map_grouped_shortcode');

function pointInCoordinatesCart($point, $coordinates) {
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

function is_within_delivery_radius($lat_liv, $lon_liv, $from_lat, $from_lon, $prod_lat, $prod_lon, $to_lat, $to_lon) {
    $earth_radius = 6371;
    $radius = 35;

    $lat_diff_from = deg2rad($from_lat - $lat_liv);
    $lon_diff_from = deg2rad($from_lon - $lon_liv);
    $a_from = sin($lat_diff_from / 2) * sin($lat_diff_from / 2) +
        cos(deg2rad($lat_liv)) * cos(deg2rad($from_lat)) * 
        sin($lon_diff_from / 2) * sin($lon_diff_from / 2);
    $c_from = 2 * atan2(sqrt($a_from), sqrt(1 - $a_from));
    $distancefrom = $earth_radius * $c_from;

    $lat_diff_to = deg2rad($to_lat - $prod_lat);
    $lon_diff_to = deg2rad($to_lon - $prod_lon);
    $a_to = sin($lat_diff_to / 2) * sin($lat_diff_to / 2) +
        cos(deg2rad($prod_lat)) * cos(deg2rad($to_lat)) *
        sin($lon_diff_to / 2) * sin($lon_diff_to / 2);
    $c_to = 2 * atan2(sqrt($a_to), sqrt(1 - $a_to));
    $distanceTo = $earth_radius * $c_to;

    return ($distanceTo <= $radius && $distancefrom <= $radius);
}


function validate_cart_product_geolocation($passed, $product_id, $quantity) {
    // Récupération de la géolocalisation depuis le cookie
    $geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;

    if ($geolocation) {
        $latitude_cookie = $geolocation[0];
        $longitude_cookie = $geolocation[1];
        $address_cookie = isset($geolocation[2]) ? $geolocation[2] : '';

        // Récupération des métadonnées du produit
        $latitude_product = get_post_meta($product_id, 'dokan_geo_latitude', true);
        $longitude_product = get_post_meta($product_id, 'dokan_geo_longitude', true);
        $address_product = get_post_meta($product_id, 'dokan_geo_address', true);
        $pharmacie_produit = get_post_meta($product_id, 'pharmacie_produit', true);

        $product_categories_objects = wp_get_post_terms($product_id, 'product_cat');
        $product_categories_ids = wp_list_pluck($product_categories_objects, 'term_id');

        $pointCart = [$latitude_cookie, $longitude_cookie];

        if ($pharmacie_produit == "yes") {
            $polygonCart = [];
            $villes_coords = get_option('dokan_champs_ordonnance_villes', '');
            $villes_coords_array = [];

            if ($villes_coords) {
                $villes_coords_lines = explode("\n", $villes_coords);
                foreach ($villes_coords_lines as $line) {
                    list($ville, $coords) = explode(':', trim($line));
                    $villes_coords_array[trim($ville)] = trim($coords);
                }
            }

            foreach ($villes_coords_array as $coords) {
                list($lat, $long) = explode(',', $coords);
                $polygonCart[] = [(float)$lat, (float)$long];
            }

            $is_insideCart = pointInCoordinatesCart($pointCart, $polygonCart);

            if ($is_insideCart) {
                return true;
            } else {
                wc_add_notice(__('Le service pharmacie n\'est pas encore disponible dans cette ville '.$address_cookie.' .'), 'error');
                return false;
            }
        } else {
            if ($latitude_product && $longitude_product) {
                $lat1 = deg2rad($latitude_cookie);
                $lon1 = deg2rad($longitude_cookie);
                $lat2 = deg2rad($latitude_product);
                $lon2 = deg2rad($longitude_product);
                $earth_radius = 6371;

                $delta_lat = $lat2 - $lat1;
                $delta_lon = $lon2 - $lon1;
                $a = sin($delta_lat / 2) * sin($delta_lat / 2) + cos($lat1) * cos($lat2) * sin($delta_lon / 2) * sin($delta_lon / 2);
                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $distance = $earth_radius * $c;

                if ($distance > 35) {
                    if (is_plugin_active('other-city-ship-sendbazar/other-city-ship-sendbazar.php')) {
                        $rates_data = get_rates_data();
                        $selected_categories = get_option('ocss_selected_categories', []);
                        $par_defaut = false;
                        $all_selected_categories = [];
                        foreach ($selected_categories as $category_id) {
                            $children = get_term_children($category_id, 'product_cat');
                            $all_selected_categories = array_merge($all_selected_categories, [$category_id], $children);
                        }

                        if (array_intersect($all_selected_categories, $product_categories_ids)) {
                            if (!empty($rates_data) && isset($rates_data['routes']) && !empty($selected_categories)) {
                                foreach ($rates_data['routes'] as $route) {
                                    $from_coords = $route['from']['coordinates'] ?? null;
                                    $to_coords = $route['to']['coordinates'] ?? null;
                                    
                                    if ($from_coords && $to_coords) {
                                        if (is_within_delivery_radius($latitude_cookie, $longitude_cookie, $from_coords['latitude'], $from_coords['longitude'], $latitude_product, $longitude_product, $to_coords['latitude'], $to_coords['longitude'])){
                                            $par_defaut = true;
                                            break;
                                        } else if (is_within_delivery_radius($latitude_cookie, $longitude_cookie, $to_coords['latitude'], $to_coords['longitude'], $latitude_product, $longitude_product, $from_coords['latitude'], $from_coords['longitude'])){
                                            $par_defaut = true;
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        if ( !$par_defaut ) {
                            wc_add_notice(__('Le produit que vous avez sélectionné n\'est pas disponible à '.$address_cookie.' .'), 'error');
                            return false;
                        } else {
                            return true;
                        }
                    } else {
                        wc_add_notice(__('Le produit que vous avez sélectionné n\'est pas disponible à '.$address_cookie.' .'), 'error');
                        return false;
                    }
                } else {
                    return true;
                }
            }
        }
    } else {
        wc_add_notice(__('Veuillez choisir une ville de livraison avant de mettre des produits dans votre panier. <a href="#" id="open-map-link" onclick="openMap()">Choisir une ville</a>'), 'error');
        ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    openMap();
                });
            </script>
        <?php
        return false;
    }

    return $passed;
}
add_filter('woocommerce_add_to_cart_validation', 'validate_cart_product_geolocation', 10, 3);


function display_geolocation_form() {
    // Récupère les produits groupés par pays et ville
    $products = get_products_geolocation_data_grouped();

    ob_start(); ?>

    <form id="geolocation-form">
        <label style="color:#fff;" for="country">Pays :</label>
        <select id="country" name="country">
            <option value="">Sélectionnez un pays</option>
            <?php foreach ($products as $country => $cities) : ?>
                <option value="<?php echo esc_attr($country); ?>"><?php echo esc_html($country); ?></option>
            <?php endforeach; ?>
        </select>

        <label style="color:#fff;"  for="city">Ville :</label>
        <select id="city" name="city" disabled>
            <option value="">Sélectionnez une ville</option>
        </select>

        <input type="hidden" id="latitude" name="latitude" />
        <input type="hidden" id="longitude" name="longitude" />
        <div style="margin-bottom : 5px"></div>
        <button style="background : #f5848c !important" type="submit">Choisir comme ville de livraison</button>
    </form>

    <script>
        jQuery(document).ready(function($) {
            var products = <?php echo json_encode($products); ?>;
            
            $('#country').change(function() {
                var country = $(this).val();
                $('#city').empty().append('<option value="">Sélectionnez une ville</option>');
                if (country) {
                    $.each(products[country], function(city, locations) {
                        $('#city').append('<option value="'+city+'">'+city+'</option>');
                    });
                    $('#city').prop('disabled', false);
                } else {
                    $('#city').prop('disabled', true);
                }
            });

            $('#city').change(function() {
                var country = $('#country').val();
                var city = $(this).val();
                if (country && city) {
                    var location = products[country][city][Object.keys(products[country][city])[0]][0];
                    $('#latitude').val(location.latitude);
                    $('#longitude').val(location.longitude);
                }
            });

            $('#geolocation-form').submit(function(e) {
                e.preventDefault();
                var city = $('#city').val();
                var latitude = $('#latitude').val();
                var longitude = $('#longitude').val();
                AllowGeolocation(city, latitude, longitude);
            });
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('geolocation_form', 'display_geolocation_form');


function display_geolocation_form1() {
    $products = get_products_geolocation_data_grouped();

    ob_start(); ?>

    <form id="geolocation-form1">
        <label style="color:#fff;" for="country1">Pays :</label>
        <select id="country1" name="country1">
            <option value="">Sélectionnez un pays</option>
            <?php foreach ($products as $country => $cities) : ?>
                <option value="<?php echo esc_attr($country); ?>"><?php echo esc_html($country); ?></option>
            <?php endforeach; ?>
        </select>

        <label style="color:#fff;" for="city1">Ville :</label>
        <select id="city1" name="city1" disabled>
            <option value="">Sélectionnez une ville</option>
        </select>

        <input type="hidden" id="latitude1" name="latitude1" />
        <input type="hidden" id="longitude1" name="longitude1" />
        <div style="margin-bottom : 5px"></div>
        <button style="background : #f5848c !important" type="submit">Choisir comme ville de livraison</button>
    </form>

    <script>
    jQuery(document).ready(function($) {
        var products = <?php echo json_encode($products); ?>;
        
        $('#country1').change(function() {
            var country = $(this).val();
            $('#city1').empty().append('<option value="">Sélectionnez une ville</option>');
            if (country) {
                $.each(products[country], function(city, locations) {
                    $('#city1').append('<option value="'+city+'">'+city+'</option>');
                });
                $('#city1').prop('disabled', false);
            } else {
                $('#city1').prop('disabled', true);
            }
        });

        $('#city1').change(function() {
            var country = $('#country1').val();
            var city = $(this).val();
            if (country && city) {
                var location = products[country][city][Object.keys(products[country][city])[0]][0];
                $('#latitude1').val(location.latitude);
                $('#longitude1').val(location.longitude);
            }
        });

        $('#geolocation-form1').submit(function(e) {
            e.preventDefault();
            var city = $('#city1').val();
            var latitude = $('#latitude1').val();
            var longitude = $('#longitude1').val();
            AllowGeolocation(city, latitude, longitude);
        });
    });
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('geolocation_form1', 'display_geolocation_form1');


function popup_cat() {
    $geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;
    $maville = "";
    $ville = "";
    if ($geolocation) {
        $address_parts = array_slice($geolocation, 2);

        $adress_product = implode(', ', $address_parts);
        
        $maville = $adress_product;
    }
    ?>
    <div id="popup-categories" style="position:fixed; top:0; left:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5); z-index:9998; display:none;"></div>
    <div class="modal-content" style="width: 600px; max-width: 90%; padding: 50px 20px; padding-bottom : 10px; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: #fff; border-radius: 10px; z-index:9999; display:none; overflow-y: visible;">
        <div id="FermerCat" style="width: 30px; height: 30px; padding: 0px; border-radius: 50%; cursor: pointer; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5); position:absolute; top:-5px; right:-3px; background:red; border-radius: 50%;">X</div>
        <div style="position:absolute; top:0px; left:0px; padding-left : 20px; padding-top : 10px; display : flex; align-items : end; max-width : ">
            <img style="width : 12px; margin-right : 9px;" src="<?php echo plugins_url('icon2.png', __FILE__); ?>"/>
            <p style="font-size: 0.8em; color : #752275; font-weight : 400; padding : 0px; margin : 0px; margin-bottom: -3px;"><?php echo $maville ?></p>
        </div>
        <p style="margin-top: 8px;"><i>Choix de catégorie de produits</i></p>
        <h3>Qu'est-ce que vous recherchez ?</h3>
        <hr>
        <div style="max-height: 38vh; overflow-y: auto; width: 100%;">
            <ul style="list-style-type: none; padding-left: 0;">
                <?php
                $selected_categories = get_option('popup_categories');
                $category_orders = get_option('popup_category_orders', array());

                if ($selected_categories) {
                    $categories = get_terms(array(
                        'taxonomy' => 'product_cat',
                        'hide_empty' => false,
                        'include' => $selected_categories,
                    ));
                    
                    $ordered_categories = [];
                    $unordered_categories = [];
                    
                    foreach ($categories as $category) {
                        if (isset($category_orders[$category->term_id]) && $category_orders[$category->term_id] != '') {
                            $ordered_categories[] = $category;
                        } else {
                            $unordered_categories[] = $category;
                        }
                    }

                    usort($ordered_categories, function($a, $b) use ($category_orders) {
                        return $category_orders[$a->term_id] - $category_orders[$b->term_id];
                    });

                    usort($unordered_categories, function($a, $b) {
                        return strcmp($a->name, $b->name);
                    });

                    // Fusionner les deux listes : celles avec un ordre d'abord, puis celles triées par alphabet
                    $sorted_categories = array_merge($ordered_categories, $unordered_categories);
                } else {
                    $sorted_categories = array();
                }

                $placeholder_image_url = home_url('/wp-content/uploads/woocommerce-placeholder-300x300.png');

                foreach ($sorted_categories as $category) {
                    $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                    $thumbnail_url = wp_get_attachment_url($thumbnail_id);
                    $image_url = $thumbnail_url ? esc_url($thumbnail_url) : $placeholder_image_url;

                    echo '<li class="category-item" data-slug="' . esc_attr($category->slug) . '" style="cursor: pointer; margin-bottom: 10px; padding: 4px 5px; background-color: #f0f0f0; border-radius: 5px; display: flex; align-items: center;">';
                    echo '<img src="' . $image_url . '" alt="' . esc_attr($category->name) . '" style="width: 50px; height: 50px; margin-right: 10px; border-radius: 5px;">';
                    echo esc_html($category->name) . '</li>';
                }
                ?>
            </ul>
        </div>
        <ul style="list-style-type: none; padding-left: 0; margin-top: 15px;">
            <li class="category-item" data-slug="TousProduits" style="cursor: pointer; margin-bottom: 10px; padding: 4px 5px; background-color: #f3f3f3; border-radius: 5px; display: flex; align-items: center; box-shadow : 0px 0px 3px #f5848c">
            <img src="https://i0.wp.com/dev.sendbazar.eu/wp-content/uploads/2024/02/cropped-logo-fond-rose-1.png" alt="TousProduits" style="width: 50px; height: 50px; margin-right: 10px; border-radius: 5px;">
            Voir tous les produits</li>
        </ul>
    </div>

    <div id="Change_Cat" style="position: fixed; bottom: 70px; right: 35px; cursor: pointer; display: none; color: #fff; align-items: center; justify-content: center; animation: scale-animation 2s infinite; flex-direction: column;">
        <svg width="120" height="80" style="overflow: visible; position: absolute; top: -20px; right : -50px">
            <defs>
                <path id="curve" d="M 10,40 A 40,40 0 0,1 90,40" />
            </defs>
            <text fill="#0CE4E4" font-size="12" text-anchor="middle" dominant-baseline="middle">
                <textPath href="#curve" startOffset="50%">
                    Choix des catégories
                </textPath>
            </text>
        </svg>
        <div style="width: 40px; height: 40px; padding: 0px; background-color: #0CE4E4; border-radius: 50%; cursor: pointer; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 2px 2px 6px rgba(0, 0, 0, 0.5);">
            <i class="fas fa-box" style="font-size: 10px;"></i>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'popup_cat');

function my_popup_map_settings_page() {
    $categories = get_terms(array(
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'parent' => 0, 
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    $selected_categories = get_option('popup_categories', array());
    $category_orders = get_option('popup_category_orders', array());

    $used_orders = array_values($category_orders);
    ?>
    <div class="wrap">
        <h1>Personnaliser les catégories du Popup</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('my_popup_map_settings');
            do_settings_sections('my-popup-map-settings');
            ?>
            <h2>Sélectionnez les catégories à afficher et définissez l'ordre</h2>
            <table class="form-table">
                <?php
                $total_categories = count($categories);
                
                foreach ($categories as $category) {
                    $checked = in_array($category->term_id, (array)$selected_categories) ? 'checked' : '';
                    $order = isset($category_orders[$category->term_id]) ? $category_orders[$category->term_id] : '';

                    echo '<tr>';
                        echo '<td>';
                            echo '<label>';
                            echo '<input type="checkbox" name="popup_categories[]" value="' . esc_attr($category->term_id) . '" ' . $checked . '> ' . esc_html($category->name);
                            echo '</label> ';
                        echo '</td>';
                        echo '<td>';
                            echo '<label>Ordre: <select name="popup_category_orders[' . esc_attr($category->term_id) . ']">';
                            echo '<option value="">Sélectionnez l\'ordre</option>';
                            for ($i = 1; $i <= $total_categories; $i++) {
                                $selected = ($order == $i) ? 'selected' : '';
                                if (!in_array($i, $used_orders) || $order == $i) {
                                    echo '<option value="' . $i . '" ' . $selected . '>' . $i . '</option>';
                                }
                            }
                            echo '</select></label>';
                        echo '</td>';
                    echo '</tr>';
                }
                ?>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

function my_popup_map_register_settings() {
    register_setting('my_popup_map_settings', 'popup_categories');
    register_setting('my_popup_map_settings', 'popup_category_orders'); // Enregistrer les ordres des catégories
}
add_action('admin_init', 'my_popup_map_register_settings');


function my_popup_map_menu() {
    add_menu_page(
        'Personnaliser les Catégories',
        'Catégories Popup',
        'manage_options',
        'my-popup-map-settings', 
        'my_popup_map_settings_page',
        'dashicons-list-view',
        60
    );
}
add_action('admin_menu', 'my_popup_map_menu');

function modifier_textes_woocommerce($translated_text, $text, $domain) {
    if ($domain === 'woocommerce') {
        $remplacements = [
            'Billing details'  => 'Infos sur l\'expéditeur',
            'Ship to a different address?' => 'Informations sur le destinataire',
        ];
        if (isset($remplacements[$text])) {
            return $remplacements[$text];
        }
    }
    return $translated_text;
}
add_filter('gettext', 'modifier_textes_woocommerce', 20, 3);
add_filter('gettext_with_context', 'modifier_textes_woocommerce', 20, 3);