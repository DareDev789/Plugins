<?php
/*
Plugin Name: Dokan Champs Ordonnance
Description: Ajouter un champs d'ordonnance sur les enregistrements des produits de categorie pharmacie
Version: 1.0
Author: Wallin Razafindrazokiny
*/

// Start session
function start_session() {
    if (!session_id()) {
        session_start();
    }
}
add_action('init', 'start_session');


// Afficher l'icône à côté du nom du produit
add_action('woocommerce_shop_loop_item_title', 'display_prescription_icon', 20);
add_action('woocommerce_single_product_summary', 'display_prescription_icon', 20);
add_action('woocommerce_cart_item_name', 'display_prescription_icon_cart', 20, 2);

function display_prescription_icon() {
    global $product;

    $prescription_required = get_post_meta($product->get_id(), 'prescription_required', true);

    if ($prescription_required == 'yes') {
        echo '<img title="' . esc_attr__('Ordonnance requise', 'dokan-lite') . '" style="width: 20px; margin-left: 5px;" src="' . esc_url(plugin_dir_url(__FILE__) . 'prescription-icon.png') . '" alt="' . esc_attr__('Ordonnance requise', 'dokan-lite') . '" />';
    }
}

function display_prescription_icon_cart($product_name, $cart_item) {
    $product_id = $cart_item['product_id'];
    $prescription_required = get_post_meta($product_id, 'prescription_required', true);

    if ($prescription_required == 'yes') {
        $product_name .= '<img title="' . esc_attr__('Ordonnance requise', 'dokan-lite') . '" style="width: 20px; margin-left: 5px;" src="' . esc_url(plugin_dir_url(__FILE__) . 'prescription-icon.png') . '" alt="' . esc_attr__('Ordonnance requise', 'dokan-lite') . '" />';
    }

    return $product_name;
}


// Affichage sur le page d'edit des produits
add_action('dokan_product_edit_after_product_tags', 'afficher_sur_page_edition', 99, 2);

function afficher_sur_page_edition($post, $post_id) {
    $ordonnance_requise = get_post_meta($post_id, 'prescription_required', true);
    $produit_pharmacie = get_post_meta($post_id, 'pharmacie_produit', true);
    ?>
    <div class="row dokan-form-group" style="background: #f1f1f1; padding: 5px;">
        <section class="col-lg-6 col-md-6 col-sm-6 col-12">
            <input type="checkbox" value="yes" name="pharmacie_produit" id="pharmacie_produit" <?php checked($produit_pharmacie, 'yes'); ?>>
            <label for="pharmacie_produit"><?php _e(' Produit pharmaceutique ?', 'dokan-lite'); ?></label>
        </section>
        <section class="col-lg-6 col-md-6 col-sm-6 col-12 pharmacy-field" style="display: none;">
            <label for="prescription_required"><?php _e('Ordonnance requise', 'dokan-lite'); ?></label>
            <select name="prescription_required" id="prescription_required" class="dokan-form-control">
                <option value="no" <?php selected($ordonnance_requise, 'no'); ?>><?php _e('Non', 'dokan-lite'); ?></option>
                <option value="yes" <?php selected($ordonnance_requise, 'yes'); ?>><?php _e('Oui', 'dokan-lite'); ?></option>
            </select>
        </section>
    </div>
    <script>
    jQuery(document).ready(function($) {
        function verifierChampPharmacie() {
            if ($('#pharmacie_produit').is(':checked')) {
                $('.pharmacy-field').show();
            } else {
                $('.pharmacy-field').hide();
                $('#prescription_required').val('no');
            }
        }

        $('#pharmacie_produit').change(verifierChampPharmacie);
        verifierChampPharmacie(); // Vérification initiale
    });
    </script>
    <?php
}

// Enregistrement des nouveaux champs
add_action('dokan_new_product_added', 'save_prescription_field', 10, 2);
add_action('dokan_product_updated', 'save_prescription_field', 10, 2);

function save_prescription_field($post_id, $postdata) {
    if (!empty($postdata['pharmacie_produit'])) {
        update_post_meta($post_id, 'pharmacie_produit', wc_clean($postdata['pharmacie_produit']));
    } else {
        update_post_meta($post_id, 'pharmacie_produit', 'no');
    }

    if (!empty($postdata['prescription_required'])) {
        update_post_meta($post_id, 'prescription_required', wc_clean($postdata['prescription_required']));
    } else {
        update_post_meta($post_id, 'prescription_required', 'no');
    }
}

function ajouter_recaptcha_script() {
    echo '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}
add_action('wp_head', 'ajouter_recaptcha_script');

// Display the ordonnance upload form on the cart page if needed
function afficher_formulaire_ordonnance_dans_panier() {
    $requires_prescription = false;
    $ordonnance_products = array();

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $prescription_required = get_post_meta($product_id, 'prescription_required', true);
        if ($prescription_required === 'yes') {
            $requires_prescription = true;
            $ordonnance_products[] = $product_id;
        }
    }

    if ($requires_prescription) {
        $ordonnance_urls = WC()->session->get('ordonnance_urls');

        echo '<div id="ordonnance-upload-section" style=padding : 10px; box-shadow : 0px 0px 5px #707070">';
        
        // Afficher les médicaments nécessitant une ordonnance dans le panier
        if ($ordonnance_products) {
            echo '<h2>' . __('Ces médicaments nécessitent une ordonnance', 'dokan-champs-ordonnance') . '</h2>';
            echo '<ul class="woocommerce-shipping-totals shipping">';
            foreach ($ordonnance_products as $product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    echo '<li style="list-style-type: square">' . $product->get_name() . '</li>';
                }
            }
            echo '</ul>';
        }

        if (!$ordonnance_urls) {
            ?>
            <form action="" method="post" enctype="multipart/form-data">
                <p>
                    <label for="ordonnance"><?php _e('Télécharger votre ordonnance :', 'dokan-champs-ordonnance'); ?></label>
                    <input type="file" name="ordonnance[]" id="ordonnance" multiple required>
                </p>
                <p>
                    <div class="g-recaptcha" data-sitekey="6LfAay8qAAAAAORPohtNLLoGyyKkNDWuDreiGTK0"></div>
                </p>
                <p>
                    <button class="checkout-button button alt wc-forward" type="submit" name="upload_ordonnance" value="<?php _e('Télécharger l\'ordonnance', 'dokan-champs-ordonnance'); ?>">Télécharger l'ordonnance</button>
                </p>
            </form>
            <?php
        } else {
            echo '<p>' . __('Vous avez déjà téléchargé une ordonnance.', 'dokan-champs-ordonnance') . '</p>';
            echo '<p><a href="#" id="effacer_ordonnance" class="button">' . __('Effacer l\'ordonnance', 'dokan-champs-ordonnance') . '</a></p>';
            ?>
            <script>
                jQuery(document).ready(function($) {
                    $('#effacer_ordonnance').click(function(e) {
                        e.preventDefault();
                        if (confirm("Êtes-vous sûr de vouloir effacer l'ordonnance?")) {
                            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                                action: 'effacer_ordonnance'
                            }, function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    alert('Échec de la suppression de l\'ordonnance.');
                                }
                            });
                        }
                    });
                });
            </script>
            <?php
        }

        echo '</div>';
    }
}
add_action('woocommerce_before_cart_totals', 'afficher_formulaire_ordonnance_dans_panier');

// Handle the ordonnance file upload
function handle_ordonnance_upload_in_cart() {
    if (isset($_POST['upload_ordonnance']) && !empty($_FILES['ordonnance']['name'][0])) {
        $upload_dir = wp_upload_dir();
        $upload_path = $upload_dir['basedir'] . '/ordonnances/';

        // Create directory if not exists
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0755, true);
        }

        $allowed_types = array('pdf', 'jpg', 'jpeg', 'png');
        $uploaded_files = array();
        foreach ($_FILES['ordonnance']['name'] as $key => $value) {
            $file_name = $_FILES['ordonnance']['name'][$key];
            $file_tmp = $_FILES['ordonnance']['tmp_name'][$key];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $file_mime = mime_content_type($file_tmp);

            if (in_array($file_ext, $allowed_types) && in_array($file_mime, array('application/pdf', 'image/jpeg', 'image/png'))) {
                $new_file_name = uniqid() . '.' . $file_ext;
                $filepath = $upload_path . $new_file_name;

                // Move the uploaded file
                if (move_uploaded_file($file_tmp, $filepath)) {
                    $file_url = $upload_dir['baseurl'] . '/ordonnances/' . $new_file_name;
                    $uploaded_files[] = $file_url;
                } else {
                    wc_add_notice(__('Le téléchargement de l\'ordonnance a échoué.', 'dokan-champs-ordonnance'), 'error');
                }
            } else {
                wc_add_notice(__('Type de fichier non autorisé.', 'dokan-champs-ordonnance'), 'error');
            }
        }

        if (!empty($uploaded_files)) {
            $combined_file_url = combine_files($uploaded_files, $upload_path);
            WC()->session->set('ordonnance_urls', $combined_file_url);
            wc_add_notice(__('L\'ordonnance a été téléchargée avec succès.', 'dokan-champs-ordonnance'), 'success');
        }
    }
}
add_action('wp_loaded', 'handle_ordonnance_upload_in_cart');

// Block checkout if ordonnance is required but not uploaded
function verifier_ordonnance_avant_validation_commande() {
    $requires_prescription = false;

    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = $cart_item['product_id'];
        $prescription_required = get_post_meta($product_id, 'prescription_required', true);
        if ($prescription_required === 'yes') {
            $requires_prescription = true;
            break;
        }
    }

    $ordonnance_urls = WC()->session->get('ordonnance_urls');

    if ($requires_prescription && !$ordonnance_urls) {
        wc_add_notice(__('Une ordonnance est requise pour les produits dans votre panier.', 'dokan-champs-ordonnance'), 'notice');
        // Vérifie si l'URL actuelle n'est pas celle du panier
        if (!is_cart()) {
            wp_safe_redirect(wc_get_cart_url());
            exit; // Assurez-vous que le script s'arrête après la redirection
        }
    }
}
add_action('woocommerce_check_cart_items', 'verifier_ordonnance_avant_validation_commande');

// Combine files into a single ZIP or PDF
function combine_files($files, $upload_path) {
    $zip = new ZipArchive();
    $zip_filename = 'ordonnance_' . time() . '.zip';
    $zip_filepath = $upload_path . $zip_filename;

    if ($zip->open($zip_filepath, ZipArchive::CREATE) !== TRUE) {
        return false;
    }

    foreach ($files as $file) {
        $zip->addFile(str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $file), basename($file));
    }

    $zip->close();

    return wp_get_upload_dir()['baseurl'] . '/ordonnances/' . $zip_filename;
}

// AJAX action to delete ordonnance file
function effacer_ordonnance() {
    $ordonnance_urls = WC()->session->get('ordonnance_urls');
    if ($ordonnance_urls) {
        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ordonnance_urls);

        // Delete file from server
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Clear ordonnance URL from session
        WC()->session->set('ordonnance_urls', '');

        wp_send_json_success();
    } else {
        wp_send_json_error();
    }
}

add_action('wp_ajax_effacer_ordonnance', 'effacer_ordonnance');
add_action('wp_ajax_nopriv_effacer_ordonnance', 'effacer_ordonnance');

// Ajouter le lien de l'ordonnance aux métadonnées de la commande
function ajouter_lien_ordonnance_commande($order, $data) {
    if ($ordonnance_urls = WC()->session->get('ordonnance_urls')) {
        $order->update_meta_data('_ordonnance_urls', $ordonnance_urls);
        WC()->session->__unset('ordonnance_urls');
    }
}
add_action('woocommerce_checkout_create_order', 'ajouter_lien_ordonnance_commande', 10, 2);

// Afficher le lien de l'ordonnance dans les emails envoyés au client et à l'administrateur
function afficher_lien_ordonnance_email($order, $sent_to_admin, $plain_text, $email) {
    $ordonnance_urls = $order->get_meta('_ordonnance_urls');
    if ($ordonnance_urls) {
        echo '<h2>' . __('Lien de l\'ordonnance') . '</h2>';
        echo '<p><a href="' . esc_url($ordonnance_urls) . '">' . esc_html($ordonnance_urls) . '</a></p>';
    }
}
add_action('woocommerce_email_order_meta', 'afficher_lien_ordonnance_email', 10, 4);

// Ajouter le lien de l'ordonnance aux métadonnées de l'email
function ajouter_lien_ordonnance_email_meta($fields, $sent_to_admin, $order) {
    $ordonnance_urls = $order->get_meta('_ordonnance_urls');
    if ($ordonnance_urls) {
        $fields['ordonnance_urls'] = array(
            'label' => __('Lien de l\'ordonnance', 'votre-domaine-de-texte'),
            'value' => '<a href="' . esc_url($ordonnance_urls) . '">' . esc_html($ordonnance_urls) . '</a>',
        );
    }
    return $fields;
}
add_filter('woocommerce_email_order_meta_fields', 'ajouter_lien_ordonnance_email_meta', 10, 3);

// Afficher le lien de l'ordonnance dans l'admin de la commande
function afficher_lien_ordonnance_admin($order) {
    $ordonnance_urls = $order->get_meta('_ordonnance_urls');
    if ($ordonnance_urls) {
        echo '<p><strong>' . __('Lien de l\'ordonnance') . ':</strong> <a href="' . esc_url($ordonnance_urls) . '">' . esc_html($ordonnance_urls) . '</a></p>';
    }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'afficher_lien_ordonnance_admin', 10, 1);



function add_pharmacie_page_option() {
    add_settings_field(
        'page_for_pharmacie',
        __('Page de Pharmacie', 'Dokan-champs-ordonnance'),
        'page_for_pharmacie_callback',
        'reading',
        'default',
        array('label_for' => 'page_for_pharmacie')
    );

    register_setting('reading', 'page_for_pharmacie', array(
        'type' => 'integer',
        'sanitize_callback' => 'absint',
        'default' => 0,
    ));
}
add_action('admin_init', 'add_pharmacie_page_option');

function page_for_pharmacie_callback($args) {
    $value = get_option('page_for_pharmacie');
    wp_dropdown_pages(array(
        'name' => 'page_for_pharmacie',
        'show_option_none' => __('&mdash; Select &mdash;'),
        'option_none_value' => '0',
        'selected' => $value
    ));
}

function display_pharmacie_page($query) {
    if (!is_admin() && $query->is_main_query() && is_page(get_option('page_for_pharmacie'))) {
        $query->set('post_type', 'product'); // Assuming 'product' is the post type used by Dokan
        $query->is_archive = true;
        $query->is_singular = false;
    }
}
add_action('pre_get_posts', 'display_pharmacie_page');



function pharmacie_page_template_redirect() {
    if (is_page(get_option('page_for_pharmacie'))) {
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/archive-pharmacie.php'; // Update the path to your plugin's template
        if (file_exists($plugin_template)) {
            include($plugin_template);
            exit();
        }
    }
}
add_action('template_redirect', 'pharmacie_page_template_redirect');


// Ajouter le menu de paramètres
add_action('admin_menu', 'dokan_champs_ordonnance_add_admin_menu');
function dokan_champs_ordonnance_add_admin_menu() {
    add_menu_page(
        'Paramètres des coordonnées de villes pour les pharmacies',
        'Coordonnées des Villes',
        'manage_options',
        'dokan-champs-ordonnance-settings',
        'dokan_champs_ordonnance_settings_page',
        'dashicons-admin-generic'
    );
}

// Afficher la page de paramètres
function dokan_champs_ordonnance_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e('Paramètres des coordonnées de villes', 'dokan-champs-ordonnance'); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('dokan_champs_ordonnance_settings_group');
            do_settings_sections('dokan-champs-ordonnance-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Enregistrer les paramètres
add_action('admin_init', 'dokan_champs_ordonnance_settings_init');
function dokan_champs_ordonnance_settings_init() {
    register_setting('dokan_champs_ordonnance_settings_group', 'dokan_champs_ordonnance_villes');

    add_settings_section(
        'dokan_champs_ordonnance_settings_section',
        __('Coordonnées des Villes pour les pharmacies', 'dokan-champs-ordonnance'),
        null,
        'dokan-champs-ordonnance-settings'
    );

    add_settings_field(
        'dokan_champs_ordonnance_villes',
        __('Coordonnées des Villes', 'dokan-champs-ordonnance'),
        'dokan_champs_ordonnance_villes_field_callback',
        'dokan-champs-ordonnance-settings',
        'dokan_champs_ordonnance_settings_section'
    );
}

function dokan_champs_ordonnance_villes_field_callback() {
    $villes = get_option('dokan_champs_ordonnance_villes', '');
    echo '<textarea name="dokan_champs_ordonnance_villes" rows="10" cols="50" class="large-text">' . esc_textarea($villes) . '</textarea>';
    echo '<p class="description">' . __('Entrez les coordonnées des villes, une par ligne, au format "ville:latitude,longitude".', 'dokan-champs-ordonnance') . '</p>';
}

