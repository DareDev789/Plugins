<?php
/**
 * Template Name: Parrainnage Template
 */

get_header(); ?>

<div class="parrainnage-container">
    <div class="container">
        <h1 style="font-size: 2.2rem;">Votre espace Parrainage</h1>
        <p>Bienvenue dans votre espace de parrainage. Ici, vous pouvez gérer vos invitations et récompenses.</p>
        
        <?php if (is_user_logged_in()) : ?>
            <div class="user-profile" style="margin-top: 20px;">
                <?php
                $current_user = wp_get_current_user(); 
                $avatar = get_avatar($current_user->ID, 96);
                $full_name = $current_user->display_name;
                ?>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <div class="user-avatar">
                        <?php echo $avatar; ?>
                    </div>
                    <div class="user-info">
                        <p style="margin: 0;">Bonjour, <strong><?php echo esc_html($full_name); ?></strong> !</p>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <p>Vous devez être connecté pour accéder à cette page.</p>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>
