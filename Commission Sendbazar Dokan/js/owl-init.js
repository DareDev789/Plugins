jQuery(document).ready(function($) {
    $('.dokan-store-carousel').owlCarousel({
        items: 6,
            loop: true,
            margin: 10,
            nav: true,
            autoplay: true,
            autoplayTimeout: 3000,
            responsive: {
                0: { items: 2 },
                600: { items: 3 },
                1000: { items: 6 }
        }
    });
});