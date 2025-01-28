jQuery(document).ready(function($) {
    $('.apply-referral').on('click', function() {
        let referralCode = $('#referral_code').val();
        if (referralCode.trim() !== '') {
            $('<input>').attr({
                type: 'hidden',
                name: 'referral_code',
                value: referralCode
            }).appendTo('form.woocommerce-cart-form, form.checkout');

            $('form.woocommerce-cart-form, form.checkout').submit();
        } else {
            alert('Veuillez entrer un code de parrainage.');
        }
    });
});
 