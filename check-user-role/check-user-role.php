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
					
					/*
					.elementor-3462 .elementor-element.elementor-element-7cefe45:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-7cefe45 > .elementor-motion-effects-container > .elementor-motion-effects-layer { display: none !important; }
					*/
					
					
				    @media only screen and (max-width: 770px) 
					{
						.elementor-3462 .elementor-element.elementor-element-43b2b84:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-43b2b84 > .elementor-motion-effects-container > .elementor-motion-effects-layer { 
								display: block !important; 
								margin-top: -35px;
							 Z-index : 1000 !important; 	
						}
						
						.footer-b2b-sbz {
							position: fixed !important; 
							bottom: 0 !important;
							left: 0 !important; 
							right: 0 !important;
						z-index : 1000 !important;
						}


						.wp-bottom-menu
						{
							display:none !important;
						}
						
						.footer-b2b-sbz-site, .footer-b2b-sbz-site2 {
							display:none !important;
						}
						
						
						
												
						/* page global */
						body, #blog .blog-post .entry-meta > span > a, #blog .blog-post.blog-large .entry-date a, #sidebar.sidebar-wrapper a, #footer ul.inline-menu > li a, #footer p.copyright, #footer .copyright a, .result-paging-wrapper ul.paging li a, .navigation.pagination a, .navigation.pagination span, .breadcrumb-wrapper.not-home li a, .breadcrumb li .active, .comment-navigation .nav-previous a, .comment-navigation .nav-next a, .post-navigation .nav-previous a, .post-navigation .nav-next a, ul.comment-item li .comment-header > a, .edit_repy_links a, #respond .logged-in-as a, .comments-area label, #respond form input, #respond .comment-form-comment textarea, #cancel-comment-reply-link, .detail-content.single_page p, .comment-content p, p.banner_subtitle, .swiper-content p, .bizberg_detail_cat, .bizberg_detail_user_wrapper a, .bizberg_detail_comment_count, .tag-cloud-heading, .single_page .tagcloud.tags a, .full-screen-search input[type="text"].search-field, .detail-content.single_page ul, .comment-content ul, .bizberg_default_page ul, .bizberg_default_page li, .bizberg_read_time
						{ 
							margin-top: 50px !important; 
							margin-bottom: 50px !important; 
							
							
						
						}
						
						.elementor-3462 .elementor-element.elementor-element-b0c713b > .elementor-element-populated
						{
							margin-top: -55px !important;
						}
						
						
					
					}
					
						
						
					
					.container{
						max-width: 100% !important;
						width: 100% !important;
						margin-top: -35px;
						
					}

					.bizberg_default_page .entry-content {
						width: 100% !important;
						
					}

					/* pour pied de page sbz commun */
					.elementor-10810 .elementor-element.elementor-element-6370d56:not(.elementor-motion-effects-element-type-background), .elementor-10810 .elementor-element.elementor-element-6370d56 > .elementor-motion-effects-container > .elementor-motion-effects-layer
					{ display: none !important; }
					
					
					/* bouton changer categorier */
					#Change_Cat { display: none !important; }
					

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
				
				.elementor-3462 .elementor-element.elementor-element-43b2b84:not(.elementor-motion-effects-element-type-background), .elementor-3462 .elementor-element.elementor-element-43b2b84 > .elementor-motion-effects-container > .elementor-motion-effects-layer { 
							display:none !important; 	
					}
					
					
                
                .footer-b2b-sbz 
					{
						display:none !important;
					}
					
			     .footer-b2b-sbz-site, .footer-b2b-sbz-site2
				 {
						display:none !important;
				 }
				
				
				
				
				
				@media only screen and (max-width: 770px) 
					{
						
						/* pour pied de page sbz commun à enlever si ecran mobile */
					.elementor-10810 .elementor-element.elementor-element-6370d56:not(.elementor-motion-effects-element-type-background), .elementor-10810 .elementor-element.elementor-element-6370d56 > .elementor-motion-effects-container > .elementor-motion-effects-layer
					{ display: none !important; }
						
					}
				
				
				
				
					
            </style>
        ';
		
		
		
	}
	
}
add_action('wp_head', 'add_custom_styles_based_on_link');




