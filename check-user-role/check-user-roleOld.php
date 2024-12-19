<?php
/*
Plugin Name: Check User Role pour WooCommerce
Description: Plugin pour vérifier si un utilisateur est un vendeur ou un autre type.
Version: 1.0
Author: Sendbazar (Razafindrazokiny Wallin)
*/

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

function check_user_role() {
    $user_id = get_current_user_id(); 
    $user_info = get_userdata($user_id);
    
    if ($user_id && !empty($user_info->roles)) {
        return implode(', ', $user_info->roles);
    }
    
    return 'Aucun rôle trouvé';
}

// Fonction pour ajouter des styles en fonction du rôle de l'utilisateur
function add_custom_styles_based_on_role() {
    $user_roles = check_user_role();
    
    // Vérifie si l'utilisateur est un vendeur
    if ($user_roles == 'seller') {
        echo '
            <style>
                #menuSimple1_grand { display: none !important; }
                #menuSimple2_grand { display: none !important; }
                #menuSeller1_grand { display: block !important; }
                #menuSeller2_grand { display: block !important; }
                @media screen and (max-width: 767px) {
                    #menuSeller_Petit { display: block !important; }
                    #menuSimple_petit { display: none !important; }
                    #menuSeller1_grand { display: none !important; }
                    #menuSeller2_grand { display: none !important; }
                }
            </style>
        ';
    } else {
        echo '
            <style>
                #menuSimple1_grand { display: block !important; }
                #menuSimple2_grand { display: block !important; }
                #menuSeller1_grand { display: none !important; }
                #menuSeller2_grand { display: none !important; }
                @media screen and (max-width: 767px) {
                    #menuSeller_Petit { display: none !important; }
                    #menuSimple_petit { display: block !important; }
                    #menuSimple1_grand { display: none !important; }
                    #menuSimple2_grand { display: none !important; }
                }
            </style>
        ';
    }
}
add_action('wp_head', 'add_custom_styles_based_on_role');
