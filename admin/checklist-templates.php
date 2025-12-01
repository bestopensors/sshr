<?php
/**
 * Checklist Templates for Different Project Tiers
 */

function getChecklistTemplate($tier) {
    $templates = [
        'basic' => [
            'agreement' => [
                'Potvrditi zahtjeve klijenta',
                'Definirati opseg projekta',
                'Potpisati sporazum',
                'Primiti početnu uplatu'
            ],
            'planning' => [
                'Kreirati strukturu stranice',
                'Planirati navigaciju',
                'Odabrati boje i fontove'
            ],
            'design' => [
                'Kreirati wireframe',
                'Dizajnirati homepage',
                'Dizajnirati ostale stranice',
                'Pripremiti dizajn za development'
            ],
            'development' => [
                'Postaviti HTML strukturu',
                'Dodati CSS stilove',
                'Implementirati JavaScript funkcionalnosti',
                'Dodati kontakt formu',
                'Integrirati Umami Analytics',
                'Dodati Cloudflare Turnstile',
                'Testirati responzivan dizajn'
            ],
            'content' => [
                'Pripremiti tekstove',
                'Optimizirati slike',
                'Dodati SEO meta tagove',
                'Provjeriti pravopis',
                'Postaviti stranicu na server'
            ],
            'testing' => [
                'Testirati na različitim preglednicima',
                'Testirati na mobilnim uređajima',
                'Provjeriti brzinu učitavanja',
                'Testirati kontakt formu',
                'Provjeriti (pagespeed.web.dev) i (sitechecker.pro)'
            ],
            'final' => [
                'Finalna provjera',
                'Povezati domenu',
                'Konfigurirati email sistem',
                'Predati klijentu'
            ]
        ],
        'professional' => [
            'agreement' => [
                'Potvrditi zahtjeve klijenta',
                'Definirati opseg projekta',
                'Potpisati sporazum',
                'Primiti početnu uplatu'
            ],
            'planning' => [
                'Kreirati sitemap',
                'Planirati informacijsku arhitekturu',
                'Odabrati CMS ili framework',
                'Planirati SEO strategiju',
                'Definirati user flow'
            ],
            'design' => [
                'Kreirati wireframes za sve stranice',
                'Dizajnirati homepage',
                'Dizajnirati unutarnje stranice',
                'Kreirati UI komponente',
                'Pripremiti design system',
                'Dizajnirati mobilnu verziju'
            ],
            'development' => [
                'Postaviti development okruženje',
                'Kreirati bazu podataka',
                'Implementirati backend funkcionalnosti',
                'Razviti frontend',
                'Integrirati CMS',
                'Dodati admin panel',
                'Implementirati SEO optimizacije',
                'Dodati Umami Analytics',
                'Dodati Cloudflare Turnstile',
                'Testirati responzivan dizajn'
            ],
            'content' => [
                'Kreirati sve tekstove',
                'Optimizirati sve slike',
                'Dodati SEO meta tagove',
                'Kreirati blog postove (ako je potrebno)',
                'Provjeriti pravopis i gramatiku',
                'Postaviti stranicu na server'
            ],
            'testing' => [
                'Testirati na različitim preglednicima',
                'Testirati na mobilnim uređajima',
                'Provjeriti brzinu učitavanja',
                'Testirati sve forme',
                'Testirati admin panel',
                'Provjeriti SEO optimizacije',
                'Provjeriti (pagespeed.web.dev) i (sitechecker.pro)'
            ],
            'final' => [
                'Finalna provjera',
                'Povezati domenu',
                'Konfigurirati email sistem',
                'Postaviti SSL certifikat',
                'Predati klijentu',
                'Obuka klijenta za admin panel'
            ]
        ],
        'premium' => [
            'agreement' => [
                'Potvrditi zahtjeve klijenta',
                'Definirati opseg projekta',
                'Kreirati projektni plan',
                'Potpisati sporazum',
                'Primiti početnu uplatu'
            ],
            'planning' => [
                'Kreirati detaljnu sitemap',
                'Planirati informacijsku arhitekturu',
                'Odabrati tehnologije i framework',
                'Planirati SEO strategiju',
                'Definirati user personas',
                'Planirati user experience',
                'Kreirati content strategiju'
            ],
            'design' => [
                'Kreirati wireframes za sve stranice',
                'Dizajnirati homepage',
                'Dizajnirati unutarnje stranice',
                'Kreirati UI komponente',
                'Pripremiti design system',
                'Dizajnirati mobilnu verziju',
                'Kreirati animacije i interakcije',
                'Dizajnirati email template'
            ],
            'development' => [
                'Postaviti development okruženje',
                'Kreirati bazu podataka',
                'Implementirati backend API',
                'Razviti frontend aplikaciju',
                'Integrirati CMS',
                'Dodati custom admin panel',
                'Implementirati napredne SEO optimizacije',
                'Dodati Umami Analytics',
                'Dodati Cloudflare Turnstile',
                'Implementirati caching',
                'Optimizirati performanse',
                'Testirati responsive dizajn',
                'Dodati PWA funkcionalnosti (ako potrebno)'
            ],
            'content' => [
                'Kreirati sve tekstove',
                'Optimizirati sve slike',
                'Dodati SEO meta tagove',
                'Kreirati blog postove',
                'Kreirati video sadržaj (ako potrebno)',
                'Provjeriti pravopis i gramatiku',
                'Optimizirati za pretraživanje',
                'Postaviti stranicu na server'
            ],
            'testing' => [
                'Testirati na različitim preglednicima',
                'Testirati na mobilnim uređajima',
                'Provjeriti brzinu učitavanja',
                'Testirati sve forme',
                'Testirati admin panel',
                'Provjeriti SEO optimizacije',
                'Testirati security',
                'Provjeriti accessibility',
                'Load testing',
                'Provjeriti (pagespeed.web.dev) i (sitechecker.pro)'
            ],
            'final' => [
                'Finalna provjera',
                'Povezati domenu',
                'Konfigurirati email sistem',
                'Postaviti SSL certifikat',
                'Optimizirati performanse na produkciji',
                'Predati klijentu',
                'Obuka klijenta za admin panel',
                'Postaviti backup sistem'
            ]
        ],
        'custom' => [
            'agreement' => [
                'Potvrditi zahtjeve klijenta',
                'Definirati opseg projekta',
                'Kreirati detaljni projektni plan',
                'Potpisati sporazum',
                'Primiti početnu uplatu'
            ],
            'planning' => [
                'Kreirati detaljnu sitemap',
                'Planirati informacijsku arhitekturu',
                'Odabrati tehnologije i framework',
                'Planirati SEO strategiju',
                'Definirati user personas',
                'Planirati user experience',
                'Kreirati content strategiju',
                'Planirati custom funkcionalnosti'
            ],
            'design' => [
                'Kreirati wireframes za sve stranice',
                'Dizajnirati homepage',
                'Dizajnirati unutarnje stranice',
                'Kreirati UI komponente',
                'Pripremiti design system',
                'Dizajnirati mobilnu verziju',
                'Kreirati animacije i interakcije',
                'Dizajnirati custom funkcionalnosti'
            ],
            'development' => [
                'Postaviti development okruženje',
                'Kreirati bazu podataka',
                'Implementirati backend API',
                'Razviti frontend aplikaciju',
                'Integrirati CMS',
                'Dodati custom admin panel',
                'Implementirati custom funkcionalnosti',
                'Implementirati napredne SEO optimizacije',
                'Dodati Umami Analytics',
                'Dodati Cloudflare Turnstile',
                'Implementirati caching',
                'Optimizirati performanse',
                'Testirati responsive dizajn'
            ],
            'content' => [
                'Kreirati sve tekstove',
                'Optimizirati sve slike',
                'Dodati SEO meta tagove',
                'Kreirati blog postove',
                'Provjeriti pravopis i gramatiku',
                'Optimizirati za pretraživanje',
                'Postaviti stranicu na server'
            ],
            'testing' => [
                'Testirati na različitim preglednicima',
                'Testirati na mobilnim uređajima',
                'Provjeriti brzinu učitavanja',
                'Testirati sve forme',
                'Testirati admin panel',
                'Provjeriti SEO optimizacije',
                'Testirati security',
                'Provjeriti accessibility',
                'Testirati custom funkcionalnosti',
                'Provjeriti (pagespeed.web.dev) i (sitechecker.pro)'
            ],
            'final' => [
                'Finalna provjera',
                'Povezati domenu',
                'Konfigurirati email sistem',
                'Postaviti SSL certifikat',
                'Optimizirati performanse na produkciji',
                'Predati klijentu',
                'Obuka klijenta za admin panel',
                'Postaviti backup sistem'
            ]
        ]
    ];
    
    return $templates[$tier] ?? [];
}

