function verrouillerChampsLivraison() {
    function mettreAJourChamp(id, readOnly = true) {
        const champ = document.getElementById(id);
        if (champ) {
            if (champ.value !== '') {
                if (readOnly) champ.readOnly = true;
            }
        } else {
            console.warn(`Le champ ${id} est introuvable.`);
        }
    }

    mettreAJourChamp('shipping_city', true);
    mettreAJourChamp('shipping_state', true);
    mettreAJourChamp('shipping_postcode', true);
    mettreAJourChamp('shipping_country', true);
    
    let shippingCountry = document.getElementById('shipping_country');
    if (shippingCountry) {
        shippingCountry.disabled = true;
    }

    let shipCheckbox = document.getElementById('ship-to-different-address-checkbox');
    if (shipCheckbox) {
        shipCheckbox.checked = true;
        shipCheckbox.disabled = true;
    }
    
    let createaccount = document.getElementById('createaccount');
    if (createaccount) {
        createaccount.checked = true;
        createaccount.disabled = true;
    }
}

document.addEventListener('DOMContentLoaded', verrouillerChampsLivraison);

jQuery(document.body).on('updated_checkout', verrouillerChampsLivraison);
