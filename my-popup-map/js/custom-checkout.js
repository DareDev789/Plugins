document.addEventListener('DOMContentLoaded', function() {
    var geolocation = document.cookie.split('; ').find(row => row.startsWith('geolocation='));

    if (geolocation) {
        geolocation = geolocation.split('=')[1].split(',');
        var addressCookie = geolocation[2];

            const villes = [
                { nom: 'Antananarivo', codePostal: '101', region: 'Analamanga', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Tananarive', codePostal: '101', region: 'Analamanga', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Diego Suarez', codePostal: '201', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Diego-Suarez', codePostal: '201', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Ambilobe', codePostal: '204', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Antsiranana', codePostal: '201', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Ambanja', codePostal: '203', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Nosy Be', codePostal: '207', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Hell Ville', codePostal: '207', region: 'Diana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Andapa', codePostal: '205', region: 'SAVA', pays: 'MG', paysName:'Madagascar' },
                { nom: 'District d\'Andapa', codePostal: '205', region: 'SAVA', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Vohemar', codePostal: '206', region: 'SAVA', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Sambava', codePostal: '208', region: 'SAVA', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Tamatave', codePostal: '501', region: 'Atsinanana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Toamasina', codePostal: '501', region: 'Atsinanana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Antalaha', codePostal: '207', region: 'SAVA', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Mahajanga', codePostal: '401', region: 'Boeny', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Fianarantsoa', codePostal: '301', region: 'Haute Matsiatra', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Toliara', codePostal: '601', region: 'Atsimo-Andrefana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Tuléar', codePostal: '601', region: 'Atsimo-Andrefana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Antsirabe', codePostal: '110', region: 'Vakinankaratra', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Morondava', codePostal: '619', region: 'Menabe', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Manakara', codePostal: '316', region: 'Vatovavy-Fitovinany', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Ambositra', codePostal: '306', region: 'Amoron’i Mania', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Ambatondrazaka', codePostal: '503', region: 'Alaotra-Mangoro', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Farafangana', codePostal: '309', region: 'Atsimo-Atsinanana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Morombe', codePostal: '618', region: 'Atsimo-Andrefana', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Mananjary', codePostal: '317', region: 'Vatovavy-Fitovinany', pays: 'MG', paysName:'Madagascar' },
                { nom: 'Soavinandriana', codePostal: '119', region: 'Itasy', pays: 'MG' },
                { nom: 'Tsiroanomandidy', codePostal: '118', region: 'Bongolava', pays: 'MG', paysName:'Madagascar' }
            ];

            const fuse = new Fuse(villes, { 
                keys: ['nom'], 
                threshold: 0.7,
                ignoreLocation: true,
                findAllMatches: true,
                includeMatches: true,
            });
            const nomVilleNettoye = addressCookie.split(',')[0].trim();
            
        function trouverVille(nomVille) {
            const resultats = fuse.search(nomVille);
            return resultats.length > 0 ? resultats[0].item : null;
        }

        const ville = trouverVille(nomVilleNettoye);

        if (ville) {
            function mettreAJourChamp(id, valeur, readOnly = true) {
                const champ = document.getElementById(id);
                if (champ) {
                    champ.value = valeur;
                    if (readOnly) champ.readOnly = true;
                } else {
                    console.warn(`Le champ ${id} est introuvable.`);
                }
            }
            if (ville.nom) mettreAJourChamp('shipping_city', ville.nom, true);
            if (ville.region) mettreAJourChamp('shipping_state', ville.region, true);
            if (ville.codePostal) mettreAJourChamp('shipping_postcode', ville.codePostal, true);
            if (ville.pays) {
                mettreAJourChamp('shipping_country', ville.pays, true);
                document.getElementById('shipping_country').disabled = true;
            }
        } else {
            console.log('Ville non trouvée.');
        }

        // Coche et verrouille la case "ship-to-different-address"
        let shipCheckbox = document.getElementById('ship-to-different-address-checkbox');
        if (shipCheckbox) {
            shipCheckbox.checked = true;
            shipCheckbox.disabled = true;
        }
    }
});