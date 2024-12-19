<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Ajouter un menu d'administration pour les paramètres du plugin
add_action('admin_menu', 'dokan_pricing_multiplier_menu');
function dokan_pricing_multiplier_menu() {
    add_options_page(
        'Dokan Pricing Multiplier Settings',
        'Commission sendbazar Dokan',
        'manage_options',
        'dokan-pricing-multiplier',
        'dokan_pricing_multiplier_settings_page'
    );
}

// Afficher la page des paramètres du plugin
function dokan_pricing_multiplier_settings_page() {
    ?>
    <div class="wrap">
        <h1>Commission sendbazar Dokan Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('dokan_pricing_multiplier_settings');
            do_settings_sections('dokan_pricing_multiplier');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Enregistrer les paramètres du plugin
add_action('admin_init', 'dokan_pricing_multiplier_settings_init');
function dokan_pricing_multiplier_settings_init() {
    register_setting('dokan_pricing_multiplier_settings', 'mycommission_vendeur');
    register_setting('dokan_pricing_multiplier_settings', 'mycommission_fournisseur');
    register_setting('dokan_pricing_multiplier_settings', 'frais_bancaire_vendeur');
    register_setting('dokan_pricing_multiplier_settings', 'frais_bancaire_fournisseur');

    add_settings_section(
        'dokan_pricing_multiplier_section',
        'General Settings',
        'dokan_pricing_multiplier_section_callback',
        'dokan_pricing_multiplier'
    );

    add_settings_field(
        'mycommission_vendeur',
        'Commission Vendeur',
        'dokan_pricing_multiplier_field_callback',
        'dokan_pricing_multiplier',
        'dokan_pricing_multiplier_section',
        array(
            'label_for' => 'mycommission_vendeur',
            'type' => 'text'
        )
    );

    add_settings_field(
        'mycommission_fournisseur',
        'Commission Fournisseur',
        'dokan_pricing_multiplier_field_callback',
        'dokan_pricing_multiplier',
        'dokan_pricing_multiplier_section',
        array(
            'label_for' => 'mycommission_fournisseur',
            'type' => 'text'
        )
    );

    add_settings_field(
        'frais_bancaire_vendeur',
        'Frais Bancaire Vendeur',
        'dokan_pricing_multiplier_field_callback',
        'dokan_pricing_multiplier',
        'dokan_pricing_multiplier_section',
        array(
            'label_for' => 'frais_bancaire_vendeur',
            'type' => 'text'
        )
    );

    add_settings_field(
        'frais_bancaire_fournisseur',
        'Frais Bancaire Fournisseur',
        'dokan_pricing_multiplier_field_callback',
        'dokan_pricing_multiplier',
        'dokan_pricing_multiplier_section',
        array(
            'label_for' => 'frais_bancaire_fournisseur',
            'type' => 'text'
        )
    );
}

function dokan_pricing_multiplier_section_callback() {
    echo '<h1>Commission Sendbazar Dokan.</h1><h2>Petite explication : </h2><p>Si vous avez une commission de <code>9%</code> et frais bancaire de <code>0.55€</code>, nous avons donc </br><code>($VotrePrix + 9%) + 0.55</code></br></p> <p>Qui est equivaut à <code>($VotrePrix * 1.09) + 0.55</code></p>';
}

function dokan_pricing_multiplier_field_callback($args) {
    $option = get_option($args['label_for']);
    ?>
    <input type="<?php echo esc_attr($args['type']); ?>" 
           id="<?php echo esc_attr($args['label_for']); ?>" 
           name="<?php echo esc_attr($args['label_for']); ?>" 
           value="<?php echo esc_attr($option); ?>" />
    <?php
}
?>