<?php
// Ajouter un menu pour les réglages du plugin
function ocss_sendbazar_menu() {
    add_options_page(
        'Other City Ship Settings', 
        'Other City Ship Sendbazar', 
        'manage_options', 
        'ocss_sendbazar', 
        'ocss_sendbazar_settings_page'
    );
}
add_action('admin_menu', 'ocss_sendbazar_menu');

// Afficher la page des réglages
function ocss_sendbazar_settings_page() {
    ?>
    <div class="wrap">
        <h1>Other City Ship Sendbazar Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('ocss_sendbazar_settings');
            do_settings_sections('ocss_sendbazar');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function ocss_sendbazar_register_settings() {
    register_setting('ocss_sendbazar_settings', 'ocss_selected_categories');

    add_settings_section(
        'ocss_sendbazar_main', 
        'Sélectionnez les catégories qui peuvent être livrées dans d\'autres villes', 
        null, 
        'ocss_sendbazar'
    );

    add_settings_field(
        'ocss_selected_categories', 
        'Catégories', 
        'ocss_sendbazar_categories_field', 
        'ocss_sendbazar', 
        'ocss_sendbazar_main'
    );
}
add_action('admin_init', 'ocss_sendbazar_register_settings');

function ocss_sendbazar_categories_field() {
    $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
    $selected_categories = get_option('ocss_selected_categories', []);
    ?>
    <select name="ocss_selected_categories[]" multiple style="width: 100%; min-height: 450px;">
        <?php foreach ($categories as $category): ?>
            <option value="<?php echo $category->term_id; ?>" <?php echo in_array($category->term_id, $selected_categories) ? 'selected' : ''; ?>>
                <?php echo $category->name; ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p>Choisissez les catégories pour lesquelles les produits seront marqués comme livrables partout.</p>
    <?php
}
