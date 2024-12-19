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

/* Fonction pour ajouter des styles en fonction du rôle de l'utilisateur
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

*/





// Fonction pour ajouter des styles en fonction du lien, si celui ci contient "espace-vendeurs"
function add_custom_styles_based_on_link() {
	$mylink = $_SERVER['REQUEST_URI'];
	$myword = "espace-vendeurs";
	
	if(str_contains($mylink, $myword))
	{
		        echo '
            <style>
                .elementor-3462 .elementor-element.elementor-element-1cc9c1a8{ display: none !important; }
				
                .elementor-3462 .elementor-element.elementor-element-ae8ccac:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-ae8ccac > .elementor-motion-effects-container > .elementor-motion-effects-layer { display: none !important; }
				
				
				.elementor-3462 .elementor-element.elementor-element-2b58967:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-2b58967 > .elementor-motion-effects-container > .elementor-motion-effects-layer { display: none !important; }
				
				.elementor-3462 .elementor-element.elementor-element-7cefe45:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-7cefe45 > .elementor-motion-effects-container > .elementor-motion-effects-layer { display: none !important; }
              
                }
            </style>
        ';
		
	}
	else
	{
		echo '
            <style>
                .elementor-3462 .elementor-element.elementor-element-ac04f01 > .elementor-container { display: none !important; }
				
                .elementor-3462 .elementor-element.elementor-element-eef2313:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-eef2313 > .elementor-motion-effects-container > .elementor-motion-effects-layer{ display: none !important; }
				
				.elementor-3462 .elementor-element.elementor-element-43b2b84:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-43b2b84 > .elementor-motion-effects-container > .elementor-motion-effects-layer { display: none !important; }
                
                }
            </style>
        ';
		
		
		
	}
	
}
add_action('wp_head', 'add_custom_styles_based_on_link');




