<?php
/*
Template Name: Pharmacie Products
*/
// Inclure l'en-tête de votre thème
get_header();

// Nombre de produits par page
$products_per_page = 20;
// Détermine la page actuelle
$page = (get_query_var('page')) ? get_query_var('page') : 1;


$acces = '';

function pointInCoordinates($point, $coordinates) {

    $radius = 35;

    // Convert radius to degrees
    $radius_in_degrees = $radius / 111;

    // Initialize min and max lat/lon with the first coordinate
    $minLat = $coordinates[0][0] - $radius_in_degrees;
    $maxLat = $coordinates[0][0] + $radius_in_degrees;
    $minLon = $coordinates[0][1] - $radius_in_degrees;
    $maxLon = $coordinates[0][1] + $radius_in_degrees;

    // Update min and max lat/lon with the rest of the coordinates
    foreach ($coordinates as $coordinate) {
        $minLat = min($minLat, $coordinate[0] - $radius_in_degrees);
        $maxLat = max($maxLat, $coordinate[0] + $radius_in_degrees);
        $minLon = min($minLon, $coordinate[1] - $radius_in_degrees);
        $maxLon = max($maxLon, $coordinate[1] + $radius_in_degrees);
    }

    // Check if the point is within the bounds
    return ($point[0] >= $minLat && $point[0] <= $maxLat &&
            $point[1] >= $minLon && $point[1] <= $maxLon);
}

$polygon = [];

// Récupérer les coordonnées des villes
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
    // Ajouter les coordonnées au tableau $polygon
    $polygon[] = [(float)$lat, (float)$long];
}

$geolocation = isset($_COOKIE['geolocation']) ? explode(',', $_COOKIE['geolocation']) : null;
$show_products = false;

if ($geolocation) {
    $latitude = (float)$geolocation[0];
    $longitude = (float)$geolocation[1];
    $point = [$latitude, $longitude];

    // Vérifier si le point est dans le polygone
    $is_inside = pointInCoordinates($point, $polygon);

    if($is_inside){
        // Requête pour obtenir les produits avec le méta `pharmacie_produit` égal à "yes"
        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => 'pharmacie_produit',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'posts_per_page' => $products_per_page,
            'paged' => $page
        );
    }else{

        $acces = '1';
        // Requête pour obtenir les produits avec le méta `pharmacie_produit` égal à "yes"
        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => 'pharmacie_produit',
                    'value' => 'tsyza',
                    'compare' => '='
                )
            ),
            'posts_per_page' => $products_per_page,
            'paged' => $page
        );
    }
} else {
    //Géolocalisation non disponible

    $acces = '2';

    // Requête pour obtenir les produits avec le méta `pharmacie_produit` égal à "yes"
    $args = array(
        'post_type' => 'product',
        'meta_query' => array(
            array(
                'key' => 'pharmacie_produit',
                'value' => 'tsyizy',
                'compare' => '='
            )
        ),
        'posts_per_page' => $products_per_page,
        'paged' => $page
    );
}




if (isset($_GET['s_p'])) {
    $args['s'] = sanitize_text_field($_GET['s_p']);
}
if (isset($_GET['cat_produit'])) {
    $args['tax_query'] = array(
        array(
            'taxonomy' => 'product_cat',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['cat_produit'])
        )
    );
}

$pharmacie_query = new WP_Query($args);

if($acces=='1'){
    $messages = 'Le service pharmacie n\'est pas encore disponible dans cette ville.';
}elseif($acces=='2'){
    $messages = 'Vous devez choisir une ville de livraison pour pouvoir afficher des medicaments.';
}


//Ici, je veux recuperer tous les produits pharmaceutiques pour avoir un lien de filtrage après.
$argsPharma = array(
    'post_type' => 'product',
    'meta_query' => array(
        array(
            'key' => 'pharmacie_produit',
            'value' => 'yes',
            'compare' => '='
        )
    )
);
$pharmacie_queryPharma = new WP_Query($argsPharma);
// Récupérer tous les produits dans un tableau PHP
$products = array();
$categories = array();
if ($pharmacie_queryPharma->have_posts()) :
    while ($pharmacie_queryPharma->have_posts()) : $pharmacie_queryPharma->the_post();
        global $product;
        $products[] = array(
            'id' => $product->get_id(),
            'title' => get_the_title(),
            'link' => get_permalink(),
            'image' => get_the_post_thumbnail_url($product->get_id(), 'woocommerce_thumbnail'),
            'price' => $product->get_price_html()
        );

        // Obtenir les catégories du produit
        $product_categories = wp_get_post_terms($product->get_id(), 'product_cat');
        foreach ($product_categories as $category) {
            $categories[$category->term_id] = $category;
        }
    endwhile;
    wp_reset_postdata();
endif;




// Générer les liens de pagination
$pagination_links = paginate_links(array(
    'total' => $pharmacie_query->max_num_pages,
    'current' => $page,
    'format' => '?page=%#%',
    'prev_text' => '← Précédent',
    'next_text' => 'Suivant →',
    'add_args' => (isset($_GET['s_p']) && $_GET['s_p'] != '') ? array('s_p' => sanitize_text_field($_GET['s_p'])) : array(),
    'type' => 'array' // Générer les liens en tant que tableau
));

?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <section id="block-39" class="widget widget_block">
            <div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
        </section>
        <div class="dokan-store-wrap layout-left">
            <div id="dokan-secondary" class="dokan-store-sidebar" role="complementary">
                <div class="dokan-widget-area widget-collapse">
                    <!-- Affichage des catégories de produits -->
                    <div id="sidebar" class="sidebar-wrapper">
                        <h3 class="wp-block-heading"><?php _e('Tri par Categories', 'Dokan-champs-ordonnance'); ?></h3>
                        <?php
                        if (!empty($categories)) {
                            echo '<ul>';
                            foreach ($categories as $category) {
                                $category_link = add_query_arg('cat_produit', $category->slug, get_permalink());
                                echo '<li><a href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</a></li>';
                            }
                            echo '</ul>';
                        } else {
                            echo '<p>' . __('Aucun categorie trouvé.', 'Dokan-champs-ordonnance') . '</p>';
                        }
                        ?>
                    </div>
                    <!-- Fin Affichage des catégories de produits -->
                </div>
            </div>
            <div id="dokan-primary" class="dokan-single-store">
                <div id="dokan-content" class="store-page-wrap woocommerce" role="main">
                    <div class="dokan-store-products-filter-area dokan-clearfix">
                        <form class="" role="search" method="get" class="woocommerce-product-search" action="">
                            <label class="screen-reader-text" for="woocommerce-product-search-field"><?php _e('Search for:', 'woocommerce'); ?></label>
                            <input type="search" id="woocommerce-product-search-field" class="product-name-search dokan-store-products-filter-search" placeholder="<?php echo esc_attr__('Rechercher des medicaments', 'woocommerce'); ?>" value="<?php echo isset($_GET['s_p']) ? esc_attr($_GET['s_p']) : ''; ?>" name="s_p" />
                            <button type="submit" value="<?php echo esc_attr_x('Search', 'submit button', 'woocommerce'); ?>"><?php echo esc_html_x('Search', 'submit button', 'woocommerce'); ?></button>
                        </form>
                    </div>
                    <?php wc_print_notices(); ?>
                    <section id="block-39" class="widget widget_block">
                        <div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
                    </section>
                    <div class="seller-items">
                        <?php 
                            if($acces == ''){ ?>
                                <!-- Affichage des produits -->
                                <h3><?php _e('Produits pharmaceutiques <br>', 'Dokan-champs-ordonnance'); ?></h3>
                                <?php if ($pharmacie_query->have_posts()) : ?>
                                    <p class="woocommerce-result-count"><?php echo sprintf(__('%d produit(s) trouvé(s).', 'Dokan-champs-ordonnance'), $pharmacie_query->found_posts); ?></p>
                                    <ul class="products columns-5" style="position: relative; height: auto;">
                                        <?php while ($pharmacie_query->have_posts()) : $pharmacie_query->the_post(); ?>
                                            <li <?php wc_product_class(); ?>>
                                                <?php
                                                /**
                                                 * Hook: woocommerce_before_shop_loop_item.
                                                 *
                                                 * @hooked woocommerce_template_loop_product_link_open - 10
                                                 */
                                                do_action('woocommerce_before_shop_loop_item');

                                                /**
                                                 * Hook: woocommerce_before_shop_loop_item_title.
                                                 *
                                                 * @hooked woocommerce_show_product_loop_sale_flash - 10
                                                 * @hooked woocommerce_template_loop_product_thumbnail - 10
                                                 */
                                                do_action('woocommerce_before_shop_loop_item_title');

                                                /**
                                                 * Hook: woocommerce_shop_loop_item_title.
                                                 *
                                                 * @hooked woocommerce_template_loop_product_title - 10
                                                 */
                                                do_action('woocommerce_shop_loop_item_title');

                                                /**
                                                 * Hook: woocommerce_after_shop_loop_item_title.
                                                 *
                                                 * @hooked woocommerce_template_loop_rating - 5
                                                 * @hooked woocommerce_template_loop_price - 10
                                                 */
                                                do_action('woocommerce_after_shop_loop_item_title');

                                                /**
                                                 * Hook: woocommerce_after_shop_loop_item.
                                                 *
                                                 * @hooked woocommerce_template_loop_product_link_close - 5
                                                 * @hooked woocommerce_template_loop_add_to_cart - 10
                                                 */
                                                do_action('woocommerce_after_shop_loop_item');
                                                ?>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                    <!-- Pagination -->

                                    <nav role="navigation" id="nav-velow" class="site-navigation paging-navigation">
                                    <ul class="pager">
                                    </ul>
                                    <div class="dokan-pagination-container">
                                        <ul class="dokan-pagination">
                                        <?php
                                            // Vérifier s'il y a des liens de pagination
                                            if (is_array($pagination_links)) {
                                                foreach ($pagination_links as $link) {
                                                    // Ajouter la classe "active" au lien de la page courante
                                                if (strpos($link, 'current') !== false) {
                                                    echo '<li class="active"><a>' . $page . '</a></li>';
                                                } else {
                                                    echo '<li>' . $link . '</li>';
                                                }
                                                }
                                            }
                                            ?>
                                        </ul>
                                    </div>
                                    </nav>
                                <?php else : ?>
                                    <p><?php _e('Aucun produit trouvé.', 'Dokan-champs-ordonnance'); ?></p>
                                <?php endif; ?>
                                <?php wp_reset_postdata();
                            }elseif($acces == '1'){
                                ?><p class="woocommerce-result-count"><center><?php echo $messages; ?><br>
                                </center></p>
                                <section id="block-39" class="widget widget_block">
                                    <div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
                                </section>
                                <?php
                            }elseif($acces == '2'){
                                ?>
                                    <p class="woocommerce-result-count"><center><?php echo $messages; ?><br>
                                    <a href='#' onclick="openMap()"><button>Choisir une ville de livraison</button></a></center></p>
                                    <section id="block-39" class="widget widget_block">
                                        <div style="height:4px" aria-hidden="true" class="wp-block-spacer"></div>
                                    </section>
                                <?php
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>        
    </main>
</div>

<?php
// Inclure le pied de page de votre thème
get_footer();
?>