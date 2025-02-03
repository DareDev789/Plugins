<?php
/**
 * Template Name: Parrainnage Template
 */

get_header();


$current_user = wp_get_current_user();
$parrainage_code = get_user_meta($current_user->ID, 'parrainage_code', true);
$parrainage_credits = get_user_meta($current_user->ID, 'parrainage_credits', true);

if (!$parrainage_code) {
    $nom_sans_espace = strtoupper(preg_replace('/\s+/', '', $current_user->user_login));
    $prefix = substr($nom_sans_espace, 0, 6);

    $suffix = strtoupper(wp_generate_password(4, false, false));

    // Construire le code de parrainage final
    $parrainage_code = $prefix . '-' . $suffix;

    update_user_meta($current_user->ID, 'parrainage_code', $parrainage_code);
}


if (!$parrainage_credits) {
    $parrainage_credits = 0;
}

$args = array(
    'meta_key' => 'parrain_id',
    'meta_value' => $current_user->ID,
    'post_type' => 'shop_order',
    'post_status' => 'wc-completed',
);
$orders = get_posts($args);

?>

<div class="parrainage-container">
    <h2>Mon Parrainage</h2>
    <button id="generate-download-image-btn">Générer et Télécharger l’Image</button>
    <p>Partagez ce code avec vos proches pour leur offrir 5€ de réduction :</p>
    <div class="parrainage-code">
        Code : <strong id="parrainage-code-display"><?php echo esc_html($parrainage_code); ?></strong>
    </div>

    <button id="edit-code-btn">Modifier</button>
    <div id="edit-code-section" style="display: none; margin-top: 10px;">
        <input type="text" id="new-parrainage-code" placeholder="Nouveau code..." />
        <button id="save-code-btn">Enregistrer</button>
        <p id="code-message"></p>
    </div>

    <div style="padding : 10px 2px; margin : 10px 0px;">
        <p>Crédits accumulés : <strong><?php echo esc_html(number_format($parrainage_credits, 2)); ?> €</strong></p>

        <?php
        $demande_paiement = get_user_meta($current_user->ID, 'demande_paiement', true);

        if ($demande_paiement === 'en_attente'): ?>
            <p style="color: orange;">Votre demande de paiement est en attente de validation.</p>
        <?php elseif ($parrainage_credits > 0): ?>
            <button id="request-payment-btn">Demander un paiement</button>
        <?php endif; ?>

    </div>


    <?php if (!empty($orders)): ?>
        <h3 style="font-size : 2.3rem; margin-top : 10px;">Commandes de parrainage</h3>
        <ul style="padding : 5px; text-align : left; max-height: 500px; overflow: auto">
            <?php foreach ($orders as $order_post): ?>
                <?php $order = wc_get_order($order_post->ID); ?>
                <li style="margin: 1px 0px;">
                    Commande #<?php echo $order->get_order_number(); ?> -
                    <?php echo wc_price($order->get_total()); ?> -
                    <strong><?php echo $order->get_date_created()->date('d/m/Y'); ?></strong>
                </li>

            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p style="font-size : 1.3rem">
            <center><i>Aucune commande avec votre code de parrainage pour l'instant.</i></center>
        </p>
    <?php endif; ?>
</div>

<style>
    .parrainage-container {
        border: 1px solid #ddd;
        padding: 15px;
        border-radius: 8px;
        background: #f9f9f9;
        max-width: 400px;
        margin: 20px auto;
        text-align: center;
    }

    .parrainage-code {
        font-size: 20px;
        font-weight: bold;
        background: #eee;
        padding: 10px;
        border-radius: 5px;
        display: inline-block;
        margin-top: 10px;
    }
</style>

<script>
    document.getElementById("edit-code-btn").addEventListener("click", function () {
        let editSection = document.getElementById("edit-code-section");
        let editButton = document.getElementById("edit-code-btn");

        if (editSection.style.display === "none") {
            editSection.style.display = "block";
            editButton.innerText = "Annuler";
        } else {
            editSection.style.display = "none";
            editButton.innerText = "Modifier";
        }
    });

    document.getElementById("save-code-btn").addEventListener("click", function () {
        let newCode = document.getElementById("new-parrainage-code").value;
        let messageBox = document.getElementById("code-message");
        NProgress.start();
        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: `action=update_parrainage_code&new_code=${encodeURIComponent(newCode)}&nonce=<?php echo wp_create_nonce('update_parrainage_code_nonce'); ?>`
        })
            .then(response => response.json())
            .then(data => {
                messageBox.innerText = data.message;
                if (data.success) {
                    document.getElementById("parrainage-code-display").innerText = newCode;
                    document.getElementById("edit-code-section").style.display = "none";
                    document.getElementById("edit-code-btn").innerText = "Modifier";
                }
            })
            .catch(error => console.error("Erreur :", error))
            .finally(() => {
                NProgress.done();
            });
    });

    document.getElementById("request-payment-btn")?.addEventListener("click", function () {
        if (!confirm("Voulez-vous vraiment demander un paiement ?")) return;

        NProgress.start();
        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=request_parrainage_payment&nonce=<?php echo wp_create_nonce('parrainage_payment_nonce'); ?>`
        })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                if (data.success) {
                    location.reload(); // Rafraîchit la page pour afficher l'état "En attente"
                }
            })
            .catch(error => console.error("Erreur :", error))
            .finally(() => {
                NProgress.done();
            });
    });

    document.getElementById("generate-download-image-btn").addEventListener("click", function () {
        let code = document.getElementById("parrainage-code-display").innerText;

        NProgress.start();
        fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: `action=download_parrainage_image&parrainage_code=${encodeURIComponent(code)}&nonce=<?php echo wp_create_nonce('download_parrainage_nonce'); ?>`
        })
            .then(response => {
                console.log(response); // Afficher la réponse brute dans la console
                return response.json(); // Convertir en JSON
            })
            .then(data => {
                console.log(data); // Vérifier la réponse JSON
                if (data.success) {
                    let link = document.createElement("a");
                    link.href = data.image_url;
                    link.download = "parrainage-" + code + ".jpg";
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                } else {
                    alert("Erreur : " + data.message);
                }
            })
            .catch(error => console.error("Erreur :", error))
            .finally(() => {
                NProgress.done();
            });
    });


</script>


<?php get_footer(); ?>