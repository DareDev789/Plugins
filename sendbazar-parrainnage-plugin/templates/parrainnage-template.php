<?php
/**
 * Template Name: Parrainnage Template
 */

get_header();


$current_user = wp_get_current_user();
$parrainage_code = get_user_meta($current_user->ID, 'parrainage_code', true);
$parrainage_credits = get_user_meta($current_user->ID, 'parrainage_credits', true);

if (!$parrainage_code) {
    // Générer un code de parrainage unique si inexistant
    $parrainage_code = strtoupper(wp_generate_password(8, false, false));
    update_user_meta($current_user->ID, 'parrainage_code', $parrainage_code);
}

if (!$parrainage_credits) {
    $parrainage_credits = 0;
}

// Récupérer toutes les commandes avec le code de parrainage
$args = array(
    'meta_key' => 'parrainage_code',
    'meta_value' => $parrainage_code,
    'post_type' => 'shop_order',
    'post_status' => 'wc-completed',
);
$orders = get_posts($args);

// Calculer la somme des crédits
$total_credits = 0;
foreach ($orders as $order_post) {
    $order = wc_get_order($order_post->ID);
    $total_credits += 5;
}
?>

<div class="parrainage-container">
    <h2>Mon Parrainage</h2>
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

    <p>Crédits accumulés : <strong><?php echo esc_html(number_format($total_credits, 2)); ?> €</strong></p>
    
    <h3>Commandes de parrainage</h3>
    <ul>
    <?php foreach ($orders as $order_post): ?>
        <?php $order = wc_get_order($order_post->ID); ?>
        <li>Commande #<?php echo $order->get_order_number(); ?> - <?php echo wc_price($order->get_total()); ?></li>
    <?php endforeach; ?>
    </ul>
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
    document.getElementById("edit-code-section").style.display = "block";
});

document.getElementById("save-code-btn").addEventListener("click", function () {
    let newCode = document.getElementById("new-parrainage-code").value;
    let messageBox = document.getElementById("code-message");

    fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: `action=update_parrainage_code&new_code=${encodeURIComponent(newCode)}`
    })
    .then(response => response.json())
    .then(data => {
        messageBox.innerText = data.message;
        if (data.success) {
            document.getElementById("parrainage-code-display").innerText = newCode;
            document.getElementById("edit-code-section").style.display = "none";
        }
    });
});
</script>

<?php get_footer(); ?>
