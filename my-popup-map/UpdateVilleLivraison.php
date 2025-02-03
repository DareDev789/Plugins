<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    include_once $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php';

    if (class_exists('WooCommerce')) {
        WC()->cart->empty_cart();

        // Récupérer le contenu JSON de la requête
        $data = json_decode(file_get_contents('php://input'), true);

        if (!isset($data['city'], $data['latitude'], $data['longitude'])) {
            wp_send_json_error('Paramètres manquants', 400);
        }

        // Sécurisation des données
        $city = sanitize_text_field($data['city']);
        $latitude = floatval($data['latitude']);
        $longitude = floatval($data['longitude']);

        // Mettre à jour le cookie "geolocation"
        setcookie('geolocation', "$latitude,$longitude,$city", time() + (86400 * 30), "/");

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

        // Récupérer uniquement le nom de la ville
        $addressCookie = explode(',', $city)[0];

        // Trouver la ville correspondante
        $ville = array_values(array_filter($villes, function ($v) use ($addressCookie) {
            return stripos($v['nom'], $addressCookie) !== false;
        }));

        if (!empty($ville)) {
            $ville = $ville[0];

            // Mettre à jour les informations de livraison
            $WC_Customer = new WC_Customer();

            $WC_Customer->set_shipping_state($ville['region']);
            $WC_Customer->set_shipping_city($ville['nom']);
            $WC_Customer->set_shipping_postcode($ville['codePostal']);
            $WC_Customer->set_shipping_country($ville['pays']);

            wp_send_json_success('Informations de livraison mises à jour avec succès');
        } else {
            wp_send_json_error('Ville non trouvée dans la liste');
        }
    } else {
        wp_send_json_error('Erreur: WooCommerce n\'est pas disponible.');
    }
} else {
    wp_send_json_error('Méthode non autorisée', 405);
}
?>