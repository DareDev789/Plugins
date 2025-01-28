var capitals = {
    "Madagascar": { lat: -18.8792, lng: 47.5079 }
    // "Maurice": { lat: -20.3484, lng: 57.5522 },
    // "Comores": { lat: -11.6455, lng: 43.3333 },
    // "Seychelles": { lat: -4.6796, lng: 55.4915 },
    // "Réunion": { lat: -21.1151, lng: 55.5364 },
    // "Mozambique": { lat: -25.9661, lng: 32.5711 },
    // "Tanzanie": { lat: -6.3690, lng: 34.8888 },
    // "Kenya": { lat: -1.2921, lng: 36.8219 },
    // "Ouganda": { lat: 0.3476, lng: 32.5825 },
    // "Rwanda": { lat: -1.9403, lng: 29.8739 },
    // "Burundi": { lat: -3.3731, lng: 29.9189 },
    // "Soudan du Sud": { lat: 4.8594, lng: 31.5713 },
    // "Éthiopie": { lat: 9.1450, lng: 40.4897 },
    // "Somalie": { lat: 2.0469, lng: 45.3182 },
    // "Djibouti": { lat: 11.8251, lng: 42.5903 },
    // "Érythrée": { lat: 15.1794, lng: 39.7823 },
    // "Égypte": { lat: 30.0444, lng: 31.2357 },
    // "Soudan": { lat: 15.5007, lng: 32.5599 },
    // "Afrique du Sud": { lat: -25.7461, lng: 28.1881 },
    // "Namibie": { lat: -22.9576, lng: 18.4904 },
    // "Botswana": { lat: -24.6541, lng: 25.9087 },
    // "Zimbabwe": { lat: -17.8252, lng: 31.0335 },
    // "Zambie": { lat: -15.4167, lng: 28.2833 },
    // "Malawi": { lat: -13.9632, lng: 33.7741 },
    // "Angola": { lat: -8.8383, lng: 13.2343 },
    // "Gabon": { lat: -0.8037, lng: 11.6094 },
    // "République du Congo": { lat: -4.2634, lng: 15.2429 },
    // "République Démocratique du Congo": { lat: -4.0383, lng: 21.7587 },
    // "Cameroun": { lat: 3.8480, lng: 11.5021 },
    // "Guinée équatoriale": { lat: 3.7500, lng: 8.7832 },
    // "République Centrafricaine": { lat: 4.3947, lng: 18.5582 },
    // "Tchad": { lat: 12.1048, lng: 15.0444 },
    // "Niger": { lat: 13.5116, lng: 2.1254 },
    // "Mali": { lat: 12.6392, lng: -8.0029 },
    // "Burkina Faso": { lat: 12.3714, lng: -1.5197 },
    // "Côte d'Ivoire": { lat: 5.3599, lng: -4.0083 },
    // "Libéria": { lat: 6.2907, lng: -10.7605 },
    // "Sierra Leone": { lat: 8.4606, lng: -13.2611 },
    // "Guinée": { lat: 9.6412, lng: -13.5784 },
    // "Guinée-Bissau": { lat: 11.8650, lng: -15.5984 },
    // "Sénégal": { lat: 14.6937, lng: -17.4441 },
    // "Gambie": { lat: 13.4432, lng: -15.3101 },
    // "Cap-Vert": { lat: 14.9167, lng: -23.5087 },
    // "Ghana": { lat: 5.6037, lng: -0.1870 },
    // "Togo": { lat: 6.1228, lng: 1.2255 },
    // "Bénin": { lat: 6.5244, lng: 2.6790 },
    // "Nigéria": { lat: 9.0820, lng: 8.6753 },
    // "Sao Tomé-et-Principe": { lat: 0.1864, lng: 6.6131 },
    // "Maroc": { lat: 31.7917, lng: -7.0926 },
    // "Algérie": { lat: 28.0339, lng: 1.6596 },
    // "Tunisie": { lat: 33.8869, lng: 9.5375 },
    // "Libye": { lat: 26.3351, lng: 17.2283 },
    // "Sahara Occidental": { lat: 24.2155, lng: -12.8858 }
};

var geolocation = document.cookie.split('; ').find(row => row.startsWith('geolocation='));
var addressCookie = '';
var paysCookie = '';
var allAddressCookie = '';

if (geolocation) {
    const geolocationParts = geolocation.split('=')[1].split(',');
    addressCookie = geolocationParts[2] || '';
    paysCookie = geolocationParts[3] || '';
    allAddressCookie = `${addressCookie}, ${paysCookie}`;
}
        
var map;
var infoWindows = [];
var countryMarkers = [];
var cityMarkers = [];
var productMarkers = [];


function initCustomMap(products) {
    map = new google.maps.Map(document.getElementById('geolocation-map'), {
        zoom: 2.8,
        center: { lat: -8.0, lng: 32.0 },
        mapTypeControl: false, 
        streetViewControl: false, 
        fullscreenControl: false, 
    });

    Object.keys(products).forEach(function(country, i_country) {
        let cities = products[country];
        let countryLatLng = capitals[country] || { 
            lat: parseFloat(cities[0][0].latitude), 
            lng: parseFloat(cities[0][0].longitude) 
        };

        let countryMarker = new google.maps.Marker({
            position: countryLatLng,
            map: map,
            title: country,
            icon: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
        });

        let countryInfoWindow = new google.maps.InfoWindow({
            content: '<strong id="countryInfoWindowContent' + i_country +'">' + country + '</strong>'
        });

        countryInfoWindow.open(map, countryMarker);

        google.maps.event.addListener(countryInfoWindow, 'domready', function() {
            let infoWindowContentElement = document.getElementById('countryInfoWindowContent' + i_country);
            if (infoWindowContentElement) {
                infoWindowContentElement.addEventListener('click', function() {
                    countryClickHandler(countryMarker, cities);
                });
            }
        });

        google.maps.event.addListener(countryMarker, 'click', function() {
            countryClickHandler(countryMarker, cities);
        });

        countryMarkers.push(countryMarker);
        infoWindows.push(countryInfoWindow);
    });

    map.addListener('zoom_changed', function() {
        let zoomLevel = map.getZoom();
        setMarkersVisibility(cityMarkers, zoomLevel > 5 && zoomLevel <= 12);
        setMarkersVisibility(countryMarkers, zoomLevel <= 5);
        setMarkersVisibility(productMarkers, zoomLevel > 12);
    });
}

(function () {
    /**
     * Ouvre la popup pour la carte
     * @param {Event} event
     */
    function openMap(event) {
        if (event) event.preventDefault();
        document.getElementById('my-popup').style.display = 'block';
        document.getElementById('my-popup-overlay').style.display = 'block';
    }

    /**
     * Basculer entre les deux cartes (Liste et Carte des villes)
     */
    function toggleMap() {
        const mapvers2 = document.getElementById('mapvers2');
        const map1 = document.getElementById('map1');
        const listeVille = document.getElementById('listeVille');
        const carteVille = document.getElementById('carteVille');
        const isMapvers2Visible = window.getComputedStyle(mapvers2).display === 'block';

        if (isMapvers2Visible) {
            mapvers2.style.display = 'none';
            map1.style.display = 'block';
            listeVille.removeAttribute('disabled');
            carteVille.setAttribute('disabled', 'disabled');
            listeVille.style.background = '#f5848c';
            listeVille.style.color = '#fff';
            carteVille.style.background = '#fff';
            carteVille.style.color = '#707070';
        } else {
            map1.style.display = 'none';
            mapvers2.style.display = 'block';
            carteVille.removeAttribute('disabled');
            listeVille.setAttribute('disabled', 'disabled');
            carteVille.style.background = '#f5848c';
            carteVille.style.color = '#fff';
            listeVille.style.background = '#fff';
            listeVille.style.color = '#707070';
        }
    }

    /**
     * Ferme la popup
     */
    function closeMap() {
        document.getElementById('my-popup').style.display = 'none';
        document.getElementById('my-popup-overlay').style.display = 'none';
    }

    /**
     * Réinitialise la carte et les cookies associés
     * @param {string} name
     */
    function resetMap(name, lien) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        deleteCookie("categorie_select");
        fetch(window.location.origin + '/wp-content/plugins/my-popup-map/empty_cart.php', { method: 'POST' })
            .then(response => response.text())
            .then(() => {
                window.location.href = window.location.origin + '/' + lien;
            })
            .catch(error => {
                console.error('Erreur lors de la vidange du panier:', error);
            });
    }

    /**
     * Ajoute du texte personnalisé en dessous du menu
     */
    function addCustomTextBelowMenu() {
        if (geolocation) {
            const villeContainer = document.getElementById('ville_pays_dest_container');
            const villeText = document.getElementById('ville_pays_dest');
            if (villeContainer && villeText) {
                villeContainer.classList.add('ville_pays_dest1');
                villeText.style = "padding: 0px; display: flex"
                villeText.innerHTML = `
                    <p style="text-wrap: nowrap; padding-left: 2px; padding : 0px; font-size: 1.1em;">
                        <i class="fa fa-map-marker" aria-hidden="true"></i> 
                        Ville de livraison : ${allAddressCookie} (jusqu'à 35Km)
                    </p>
                    <div style="position: absolute; right: 10px; top: 0px;">
                        <a style="color: #0ce4e4; font-size: 14px;" href="#" onclick="resetMap('geolocation', '')">
                            <i class="fa fa-times"></i>
                        </a>
                    </div>
                `;

            } else {
                villeContainer?.classList.remove('ville_pays_dest1');
            }
        }
    }

    /**
     * Vérifie l'URL actuelle pour appliquer des actions spécifiques
     */
    function verifyUrl() {
        const currentUrl = window.location.href;

        if (currentUrl.includes("store_filter_nonce")) {
            const parsedUrl = new URL(decodeURIComponent(currentUrl));
            const params = parsedUrl.searchParams;

            const address = params.get('address') || '';
            const latitude = params.get('latitude') || '';
            const longitude = params.get('longitude') || '';

            if (address && latitude && longitude) {
                if (geolocation) {

                    if (allAddressCookie !== address) {
                        if (confirm(`Vous aviez déjà choisi une ville de  ${allAddressCookie}. \nVoulez-vous la remplacer par : ${address}?`)) {
                            resetMap('geolocation', '');
                        } else {
                            window.location.href = window.location.origin + '/produits/';
                        }
                    }
                } else if (confirm(`Voulez-vous choisir : ${address} comme votre ville de livraison ?`)) {
                    document.cookie = `geolocation=${latitude},${longitude},${address}; path=/;`;
                    addCustomTextBelowMenu();
                } else {
                    window.location.href = window.location.origin;
                }
            }
        }
    }

    /**
     * Initialisation
     */
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('a[href="#map_popup"]').forEach(link => {
            link.addEventListener('click', openMap);
        });

        addCustomTextBelowMenu();

        verifyUrl();
    });

    window.openMap = openMap;
    window.closeMap = closeMap;
    window.toggleMap = toggleMap;
    window.resetMap = resetMap;
})();

jQuery(document).ready(function($) {
    function getCookie(name) {
        let cookieArr = document.cookie.split("; ");
        for (let i = 0; i < cookieArr.length; i++) {
            let cookiePair = cookieArr[i].split("=");
            if (name === cookiePair[0]) {
                return decodeURIComponent(cookiePair[1]);
            }
        }
        return null;
    }

    function open_popup_cat(){
        $('#popup-categories').fadeIn();
        $('.modal-content').fadeIn();
    }

    function close_popup_cat(){
        $('#popup-categories').fadeOut();
        $('.modal-content').fadeOut();
    }

    function open_Change_Cat(){
        $('#Change_Cat').fadeIn();
    }

    function Close_Change_Cat(){
        $('#Change_Cat').fadeOut();
    }

    $('#Change_Cat').click(function() {
        if(!geolocation){
            openMap();
        }else{
            open_popup_cat();
            Close_Change_Cat();
        }
    });

    if(!window.location.pathname.includes('espace-vendeurs') && !window.location.pathname.includes('mon-compte') && !window.location.pathname.includes('tableau-de-bord')){
        $('#Change_Cat').fadeIn();
        if(!geolocation){
            openMap();
        }else{
            if (window.location.pathname.includes('produits') && !getCookie('categorie_select')) {
                open_popup_cat();
                Close_Change_Cat();
            }
        }
    }
    
    $('.category-item').click(function() {
        let categorySlug = $(this).data('slug');
        if(categorySlug === 'TousProduits'){
            document.cookie = "categorie_select=TousProduits; path=/; SameSite=Lax; Secure;";
            window.location.href = '/produits/';
        }else{
            document.cookie = "categorie_select=" + categorySlug + "; path=/; SameSite=Lax; Secure;";
            window.location.href = '/categorie-produit/' + categorySlug;
        }
    });

    $('#popup-categories').click(function(event) {
        close_popup_cat();
        open_Change_Cat();
    });
    $('#FermerCat').click(function(event) {
        close_popup_cat();
        open_Change_Cat();
    });
    
});

function getCookie1(name) {
    let cookieArr = document.cookie.split(";");

    for(let i = 0; i < cookieArr.length; i++) {
        let cookie = cookieArr[i].trim();

        if (cookie.indexOf(name + "=") === 0) {
            return cookie.substring((name + "=").length, cookie.length);
        }
    }

    return null;
}

function deleteCookie(name) {
    document.cookie = name + "=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
}

function redirectOrReload() {
    const currentURL = window.location.pathname;
    let directionValue = getCookie1("direction");

    if (directionValue) {
        deleteCookie("direction");
        window.location.href = directionValue;
    } else {
        if (
            currentURL.startsWith('/pharmacies/') ||
            currentURL.startsWith('/produits/') ||
            currentURL.startsWith('/produit/')
        ) {
            window.location.reload();
        } else {
            window.location.href = '/produits/';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const links = document.querySelectorAll('a');
    
    links.forEach(link => {
        link.addEventListener('click', function (event) {
            const clickedUrl = link.href;
            
            const targetUrl = window.location.origin + '/produits/';
            const targetUrl1 = window.location.origin + '/produit/';
            const targetUrl2 = window.location.origin + '/categorie-produit/';
            const targetUrl3 = window.location.origin + '/pharmacies/';
            
            if (clickedUrl.startsWith(targetUrl) || clickedUrl.startsWith(targetUrl1) || clickedUrl.startsWith(targetUrl2) || clickedUrl.startsWith(targetUrl3)) {
                if (!geolocation) {
                    event.preventDefault();
                    document.cookie = "direction=" + clickedUrl + "; path=/";
                    window.stop();
                    
                    openMap(event);
                }
            }
        });
    });
});


function addProductMarkers(product, address) {
    let productMarker = new google.maps.Marker({
        position: { lat: parseFloat(product['latitude']), lng: parseFloat(product['longitude']) },
        map: map,
        title: product['address']
    });

    let productInfoWindow = new google.maps.InfoWindow({
        content: '<strong>' + product['address'] + '</strong>'
    });


    google.maps.event.addListener(productMarker, 'click', function() {
        productClickHandler(productMarker, productInfoWindow, product, address);
    });

    productMarkers.push(productMarker);
    infoWindows.push(productInfoWindow);
}

function productClickHandler(productMarker, productInfoWindow, product, address) {
    for (let i = 0; i < infoWindows.length; i++) {
        infoWindows[i].close();
    }
    productInfoWindow.open(map, productMarker);
    const city = address;
    const latitude = product['latitude'];
    const longitude = product['longitude'];
    AllowGeolocation(city, latitude, longitude);
}

function clearMarkers(markers) {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setMap(null);
    }
    markers.length = 0;
}

function setMarkersVisibility(markers, visibility) {
    for (let i = 0; i < markers.length; i++) {
        markers[i].setVisible(visibility);
    }
}

function countryClickHandler(countryMarker, cities) {
    for (let i = 0; i < infoWindows.length; i++) {
        infoWindows[i].close();
    }
    
    map.setZoom(5);
    map.setCenter(countryMarker.getPosition());

    clearMarkers(cityMarkers);
    clearMarkers(productMarkers);

    for (let city in cities) {
        if (cities.hasOwnProperty(city)) {
            addCityMarkers(city, cities[city]);
        }
    }

    setMarkersVisibility(cityMarkers, true);
    setMarkersVisibility(countryMarkers, false);
}

function addCityMarkers(city, addresses) {
    for (let address in addresses) {
        if (addresses.hasOwnProperty(address)) {
            let addressData = addresses[address][0];

            let addressMarker = new google.maps.Marker({
                position: { lat: parseFloat(addressData['latitude']), lng: parseFloat(addressData['longitude']) },
                map: map,
                title: address,
                icon: 'http://maps.google.com/mapfiles/ms/icons/blue-dot.png'
            });

            let addressInfoWindow = new google.maps.InfoWindow({
                content: '<strong>' + address + '</strong>'
            });

            google.maps.event.addListener(addressMarker, 'click', function() {
                addressClickHandler(addressMarker, addressInfoWindow, address, addresses[address]);
            });
            cityMarkers.push(addressMarker);
            infoWindows.push(addressInfoWindow);
        }
    }
}

function addressClickHandler(addressMarker, addressInfoWindow, address, products) {
    for (let i = 0; i < infoWindows.length; i++) {
        infoWindows[i].close();
    }
    map.setZoom(12);
    map.setCenter(addressMarker.getPosition());

    clearMarkers(productMarkers);

    products.forEach(function(product) {
        addProductMarkers(product, address);
    });
}

function AllowGeolocation(city, latitude, longitude){
    if (city && latitude && longitude) {
        closeMap()
        var geolocation = document.cookie.split('; ').find(row => row.startsWith('geolocation='));
        let message = '';
        if (geolocation) {
            const geolocationParts = geolocation.split('=')[1].split(',');
            let addressCookie = geolocationParts[2];
            let paysCookie = geolocationParts[3];
            let allAddress = addressCookie + ',' + paysCookie;
            message = `<p style="font-size:1.1em">
                    Vous aviez déjà choisi la ville de livraison <br><strong>${allAddress}</strong>.<br> 
                        Voulez-vous la remplacer par : <br><strong>${city}</strong> ?<br> <br>
                        <span style="color: red;">Cette action va vider votre panier si vous avez déjà des produits dedans.</span>
                </p>`;
        } else {
            message = '<p style="font-size:1.1em">Vous avez choisi la ville de livraison : <strong>' + city + '</strong><br> Voulez-vous continuer ?</p>';
        }
        Swal.fire({
            html: message,
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Oui, continuer',
            cancelButtonText: 'Annuler',
            confirmButtonColor: '#0fe3de',
            cancelButtonColor: '#d33',
        }).then((result) => {
            if (result.isConfirmed) {
                deleteCookie("categorie_select");
                fetch(window.location.origin + '/wp-content/plugins/my-popup-map/empty_cart.php', { method: 'POST' })
                    .then(response => response.text())
                    .then(() => {
                        document.cookie = "geolocation=" + latitude + "," + longitude + "," + city + "; path=/";
                        redirectOrReload();
                    })
                    .catch(error => {
                        console.error('Erreur lors de la vidange du panier:', error);
                        openMap();
                    });
            }else{
                openMap();
            } 
        });
    }
}
