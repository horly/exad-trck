# Historique des prompts

Ce fichier garde une trace des demandes importantes effectuees pendant le projet.

## 2026-05-27

- Creer le dossier `docs/prompts` pour y conserver l'historique des prompts de ce projet.
- Renommer `history.md` en `project-history.md`.
- Renommer le projet dans `.env` avec `APP_NAME="EXAD TRACKING"`.
- Creer les tables `devices` et `positions`, les modeles Eloquent `Device` et `Position`, leurs relations de base, puis executer `php artisan migrate`. Temps d'execution du prompt : environ 4 minutes.
- Creer une page de connexion corporate pour EXAD Tracking avec Bootstrap installe via Composer, assets locaux dans `public/vendor/bootstrap`, logo EXAD dans `public/images`, routes auth minimales, controleur de login/logout et dashboard temporaire. Temps d'execution du prompt : environ 12 minutes.
- Definir la page de connexion comme page initiale de l'application via la route `/`. Temps d'execution du prompt : environ 1 minute.
- Adapter le design de la page login pour s'inspirer d'une interface avec illustration logistique/tracking a gauche et carte de connexion compacte a droite, sans ajouter de connexion Google non configuree. Temps d'execution du prompt : environ 4 minutes.
- Utiliser l'image de tracking fournie comme arriere-plan de la page login et ajuster le layout pour garder la carte de connexion lisible. Temps d'execution du prompt : environ 3 minutes.
- Aligner les couleurs primaires de la page login sur le bleu du logo EXAD. Temps d'execution du prompt : environ 1 minute.
- Reprendre la page login selon l'exemple fourni avec branding a gauche, benefices, statistiques, carte de connexion corporate et bouton SSO visuel non connecte. Temps d'execution du prompt : environ 5 minutes.
- Renforcer la lisibilite de tous les textes de la page login avec un panneau clair, plus de contraste et des textes secondaires assombris. Temps d'execution du prompt : environ 2 minutes.
- Corriger la page login pour respecter plus fidelement la reference fournie : texte directement sur l'arriere-plan, details de langue, benefices, statistiques, champs avec reperes internes et carte compacte. Temps d'execution du prompt : environ 5 minutes.
- Ajouter Font Awesome via Composer, publier ses assets localement dans `public/vendor/fontawesome`, remplacer les symboles de la page login par des icones Font Awesome et definir Roboto comme police principale. Temps d'execution du prompt : environ 6 minutes.
- Retirer le bouton Connexion entreprise, generer un logo EXAD transparent sans fond blanc et publier Roboto localement via Composer pour forcer la police de la page login. Temps d'execution du prompt : environ 5 minutes.
- Flouter l'image d'arriere-plan de la page login avec un calque dedie et rendre la page fixe/non scrollable en plein ecran. Temps d'execution du prompt : environ 3 minutes.
- Retirer le flou de l'arriere-plan login et reduire uniquement son opacite pour conserver une image nette avec textes lisibles. Temps d'execution du prompt : environ 1 minute.
- Forcer Roboto sur la variable Bootstrap `--bs-body-font-family` et sur les elements de formulaire/boutons de la page login. Temps d'execution du prompt : environ 2 minutes.
- Renommer la famille locale Roboto en `EXAD Roboto` et l'appliquer explicitement a tous les textes de la page login tout en preservant Font Awesome. Temps d'execution du prompt : environ 2 minutes.
- Replacer la police Google Roboto dans un fichier global `public/css/fonts.css`, sous son nom officiel `Roboto`, et la charger dans les vues Laravel. Temps d'execution du prompt : environ 3 minutes.
- Versionner les liens CSS pour casser le cache navigateur et appliquer Roboto explicitement via la classe globale `app-font-roboto`. Temps d'execution du prompt : environ 3 minutes.
- Continuer le projet en remplacant le dashboard temporaire par un tableau de bord authentifie avec compteurs flotte, derniers boitiers, positions recentes, styles dedies, factories et tests Pest de base.
- Appliquer la charte typographique Manrope pour l'UI globale et JetBrains Mono pour les donnees techniques, avec variables CSS centralisees, lissage des polices, tailles dashboard ajustees et cache CSS versionne.
- Reprendre la page de connexion pour se rapprocher du design de reference corporate : layout `login-shell`, image GPS a gauche, carte blanche a droite, SVG inline sans FontAwesome, traductions FR/EN, route `/lang/{locale}` et middleware de localisation.
- Ajuster finement l'interface login selon la capture fournie : recadrage du logo, carte plus compacte et alignee, fond GPS pleine largeur, francais par defaut, verification par capture Chrome headless en 1070x721.
- Remplacer l'arriere-plan de la page login par l'image fournie `ChatGPT Image 27 mai 2026, 17_09_50.png`, publiee en `public/images/login-vehicle-bg.png` et conservee en copie `public/images/login-vehicle-reference.png`.
- Afficher l'image d'arriere-plan login sans floutage, sans contraste ni overlay : suppression des gradients CSS pour rendre `login-vehicle-bg.png` directement.
- Retirer le bouton Connexion entreprise (SSO) et remplacer le selecteur de langue par un bouton FR/globe arrondi avec menu FR/EN stylise et etat actif.
- Reduire la taille du selecteur de langue et harmoniser son etat actif avec le bleu EXAD du theme.
- Compacter davantage le menu deroulant de langue : largeur, hauteur des options, badges FR/EN et taille de texte reduits.
- Retirer le bouton de mode sombre du haut de la page login et nettoyer ses styles CSS.
- Identifier la police de la reference comme une police monospace type JetBrains Mono et l'appliquer comme police globale de l'application avec fallbacks monospace systeme.
- Installer `joedixon/laravel-translation`, publier sa configuration/assets, securiser son UI `/languages` derriere `auth`, synchroniser les traductions FR/EN dans `resources/lang` pour le package et ajouter des tests du changement de langue.
- Installer Laravel Fortify pour l'authentification, remplacer les routes login/logout custom par Fortify, creer la structure roles `superadmin`/`admin`/`user`, ajouter les abonnements avec isolation des utilisateurs et devices par abonnement, definir les gates d'acces, creer les seeds des trois comptes demandes et ajouter des tests Pest de controle d'acces.
- Corriger la separation des espaces apres authentification : `/dashboard` est reserve au `superadmin`, les `admin` et `user` sont rediriges vers `/fleets`, avec une liste des flottes filtree par abonnement et un CRUD de flottes autorise uniquement a l'admin.
- Ajouter un middleware route `superadmin` (`EnsureUserIsSuperadmin`) et l'appliquer a `/dashboard` pour rendre la protection du tableau de bord superadmin explicite dans les routes.
- Recentrer la carte login dans sa colonne, replacer le bouton de langue en haut a droite et ajouter un footer login avec `EXAD Tracking` a la place de `ERP PLUS`.
- Ajouter les traductions Laravel FR/EN des messages de validation et centraliser les textes des pages flottes dans `resources/lang/*/fleets.php`.
- Installer et publier `akaunting/laravel-apexcharts`, puis refondre le tableau de bord superadmin avec sidebar sombre, topbar, cartes KPI, filtre periode et graphiques ApexCharts locaux pour l'evolution des abonnements et la repartition des roles.
- Corriger le dashboard superadmin pour appliquer uniquement le design de reference, sans reprendre son contenu ERP : conserver les indicateurs tracking GPS et utiliser ApexCharts pour l'evolution des positions et la repartition des statuts des boitiers.
- Ajouter un bouton de reduction/agrandissement de la sidebar avec etat persistant dans `localStorage`, applique au dashboard superadmin et a la page flottes.

## 2026-05-29

- Centraliser la sidebar dans `resources/views/partials/sidebar.blade.php` pour √©viter la duplication entre dashboard, utilisateurs, flottes et v√©hicules.
- Reprendre la logique Flottes : une flotte n'est plus rattach√©e directement √† un abonnement fonctionnel, elle est affect√©e √† un admin responsable via `fleet_user`, puis l'admin g√®rera les autres utilisateurs de la flotte.
- Mettre la page Flottes au m√™me standard que la page Utilisateurs : recherche AJAX, tri, pagination √† 5 lignes, toast, confirmation de suppression, modal de cr√©ation/modification et dark mode.
- Ajouter la page V√©hicules : mod√®le `Vehicle`, migration `vehicles`, relation obligatoire avec une seule flotte, lien optionnel avec `devices.vehicle_id`, contr√¥leur, routes, tableau AJAX, modal, traductions FR/EN et tests.
- Ajouter le menu `V√©hicules` apr√®s `Flottes` dans la sidebar partag√©e.
- Ajouter le favicon EXAD Tracking depuis l'image fournie dans `public/images/icon-exad-tracking.png` et le charger via `resources/views/partials/favicon.blade.php`.
- Uniformiser la validation front des formulaires avec `public/js/form-validation.js` : suppression des bulles natives navigateur, bordures rouges, messages sous les champs et compatibilit√© avec le loading des boutons.
- Corriger les accents des traductions fran√ßaises importantes, notamment dashboard et validation.
- Ajouter et maintenir les tests Pest associ√©s aux nouveaux comportements : acc√®s aux v√©hicules par flotte, tableau DataTable-like, cr√©ation/suppression avec toast et confirmation.
- Ajouter les types de v√©hicules complets dans la page V√©hicules : voitures particuli√®res, SUV/4x4, pick-up, utilitaires, camions, bus/autocars, motos, tricycles/tuk-tuk, agricoles, chantier, sp√©ciaux, √©lectriques/hybrides et remorques, avec traductions FR/EN et validation Laravel synchronis√©e.
- Ajuster la nomenclature des types de v√©hicules : remplacer les cat√©gories g√©n√©riques par Voiture, Fourgonnette, Camionnette, Van, Minibus, Tracteur, Bulldozer, Pelleteuse, Niveleuse, Chargeuse, Ambulance, V√©hicule de police, Camion pompier, D√©panneuse et Remorque, avec suppression du type √©lectrique/hybride.
- Mettre les libell√©s s√©lectionnables des types de v√©hicules au singulier, notamment Camion et Bus / autocar.
- Mettre √† jour la page Flottes et le dashboard pour tenir compte des v√©hicules enregistr√©s : compte total par flotte, r√©partition Premium/Basique et indicateur V√©hicules sur le tableau de bord.
- Ajouter le menu `Traceurs` apr√®s `V√©hicules`, cr√©er la page Traceurs sur la table `devices` existante avec tableau AJAX, modal de cr√©ation/modification, suppression confirm√©e, traductions FR/EN et isolation par flotte/v√©hicule.
- Retirer `Codec` et `Statut` du formulaire Traceurs : ces champs seront mis √† jour automatiquement par le futur serveur Node.js d'√©coute des traceurs apr√®s connexion IMEI.
- Ajouter la s√©lection progressive Marque/Mod√®le dans le formulaire Traceurs : migration `brand` sur `devices`, choix initial Teltonika/EDT, affichage du mod√®le apr√®s choix de marque et select filtrable avec recherche locale.
- Corriger la recherche du mod√®le Traceur pour l'int√©grer directement dans le select personnalis√©, avec dropdown interne, filtrage local et synchronisation du champ `model`.
- Remplacer le champ libre Op√©rateur des traceurs par un select optionnel searchable int√©gr√©, aliment√© par une liste d'op√©rateurs mobiles africains et valid√© c√¥t√© Laravel.
- Mettre le statut par d√©faut des traceurs √† `inactive`/Inactif en base et c√¥t√© mod√®le Eloquent, avec migration des anciens `offline` vers `inactive`.
- Retirer la colonne Carte SIM du tableau Traceurs et afficher `Aucun signal` en rouge pour renforcer l'√©tat inactif/non connect√©.
- Masquer dans le formulaire de cr√©ation Traceur les v√©hicules d√©j√† assign√©s √† un traceur, tout en gardant le v√©hicule courant s√©lectionnable lors d'une modification et en bloquant l'assignation c√¥t√© validation Laravel.
- Cr√©er le serveur local de test `gps-listener-server-local` : √©coute TCP JSON sans d√©pendance npm, simulateur client, commandes Artisan `gps:ingest-position` et `gps:mark-stale` pour accepter uniquement les IMEI enregistr√©s, cr√©er les positions et mettre √† jour le statut/derni√®re position des traceurs.
## 2026-06-02
- Aligner le toast instantan√© des alertes temps r√©el sur le composant toast applicatif existant, avec variante bleue th√®me, bouton fermer et barre de progression.
- Rendre la cr√©ation d'alertes tol√©rante aux indisponibilit√©s Reverb : une panne WebSocket est journalis√©e sans casser l'ingestion GPS ni l'enregistrement de l'alerte.
- G√©n√©raliser les alertes temps r√©el sur toutes les pages superadmin via le partial `partials.realtime-alerts`, afin d'afficher automatiquement les nouvelles alertes dans un toast bleu quel que soit l'√©cran ouvert.
- Ajouter un fallback AJAX `/alerts/recent` au toast d'alertes superadmin : Reverb reste prioritaire, mais les nouvelles alertes s'affichent aussi sans actualisation si le WebSocket est temporairement indisponible.
- Rendre les titres et messages d'alertes multilingues : les cl√©s et param√®tres sont stock√©s en `metadata`, puis traduits selon la langue active de la session pour le tableau, l'endpoint AJAX et les toasts.
- Traduire aussi les anciennes alertes GPS syst√®me d√©j√† stock√©es en anglais (`No signal`, `Signal restored`) et forcer le rafra√Æchissement live du tableau Alertes sur l'ordre par d√©faut, derni√®re alerte en premier.
- Modifier l'ordre du tableau Alertes pour placer syst√©matiquement les alertes trait√©es/r√©solues en derni√®re position, m√™me lorsqu'un tri AJAX est appliqu√©.
- Ajouter un bouton cloche dans la topbar superadmin, juste avant le mode sombre, avec compteur rouge des nouvelles alertes et Mise ‡ jour automatique lors des toasts live.
- R√©ordonner les actions de la topbar superadmin : plein √©cran, mode sombre, cloche alertes, langue, profil.
- Mettre √† jour le footer de la sidebar partag√©e pour afficher `EXAD Tracking - v.1.0` partout, via les traductions dashboard.
- Activer la page Personnalisation superadmin avec la sidebar partag√©e, afin que Carte, Alertes et Personnalisation affichent toutes le footer `EXAD Tracking - v.1.0`.
- Rendre la version de la sidebar visible globalement aussi en mode sidebar compacte/tablette : texte complet en sidebar large et `v.1.0` en affichage compact.
- Fixer la sidebar au viewport (`sticky`, hauteur `100vh`) et casser le cache CSS global pour que le footer de version reste visible sur les pages longues comme Carte et Alertes.
- Retirer l'indicateur technique `Temps r√©el indisponible` de la page Alertes, car le fallback AJAX assure la continuit√© sans exposer l'√©tat WebSocket √† l'utilisateur.
- Corriger le d√©clenchement d'alertes GPS : `gps:ingest-position` cr√©e maintenant une alerte `signal_recovered` quand un traceur inactif/hors ligne revient en ligne, sans doublonner tant qu'il reste online ; les tests couvrent aussi `no_signal`.
- Installer Laravel Reverb et poser la base temps r√©el des alertes superadmin : configuration broadcasting/Reverb sans npm, canal priv√© `superadmin.alerts`, mod√®le/migration `alerts`, √©v√©nement `AlertCreated`, service d'alertes, page `/alerts` avec tableau AJAX, statistiques, toast live et commande `alerts:demo`.
- Verrouiller les pages de console actuelles au r√¥le superadmin : Tableau de bord, Utilisateurs, Flottes, V√©hicules, Traceurs et Carte passent toutes par le middleware superadmin, avec tests d'acc√®s mis √† jour.
- Remplacer le message vide de la page Carte par une formulation professionnelle compatible production, sans mention de serveur GPS local ni de simulation.

- Int√©grer Mapbox localement pour la page Carte : assets `public/vendor/mapbox`, token `MAPBOX_PUBLIC_TOKEN`, route `/map`, endpoint GeoJSON `/map/devices`, layers clusteris√©s par statut, filtres, statistiques, popups et actualisation automatique.
- Enrichir la gestion des traceurs avec une table `tracker_events`, des √©tats live d√©taill√©s sur `devices`, la g√©n√©ration automatique des √©v√©nements GPS (signal, mouvement, contact), une modale de d√©tails Traceur affichant Flotte, emplacement, GSM et derniers √©v√©nements, ainsi que la Mise ‡ jour du simulateur local.
- Ajouter la section Alimentation dans les d√©tails Traceur avec tension externe, batterie interne, niveau de batterie et contact, puis clarifier la ligne `P` comme information de parking.
- Ajouter l‚Äôhistorique des trajets d‚Äôun √©quipement sur les pages Traceurs et Carte : endpoint partag√© `/trackers/{device}/trips`, choix de p√©riode, rendu timeline, r√©sum√© distance/dur√©e et GeoJSON de trac√© Mapbox.
- Remplacer les coordonn√©es brutes des trajets par des adresses lisibles via Mapbox Reverse Geocoding quand `positions.address` est vide, avec m√©morisation de l‚Äôadresse trouv√©e en base.
- Ajouter sur la page Carte un bouton de popup permettant d‚Äôouvrir les d√©tails du traceur avec la m√™me modale que la page Traceurs, via le partial et le script partag√©s `tracker-details`.
## 2026-06-04 - Correction suppression des traceurs

- Correction de l'erreur `SQLSTATE[23000]` lors de la suppression d'un traceur ayant des positions liees.
- La suppression du traceur passe maintenant par une transaction applicative.
- Les positions rattachees au traceur sont supprimees avant la suppression du traceur, afin de respecter la contrainte `positions_device_id_foreign`.
- Les evenements traceur restent geres par la cascade existante et les alertes conservent leur comportement `nullOnDelete`.

## 2026-06-04 - Mise a jour modeles Teltonika

- Mise a jour de la liste des modeles Teltonika disponibles dans le formulaire traceur.
- Ajout des familles BASIC, FAST&EASY, ADVANCED, AUTONOMOUS, E-MOBILITY, OBD, CAN et PRO selon la liste fournie.

## 2026-06-04 - Page logs serveur GPS

- Creation d'une page superadmin `Logs serveur` accessible via la sidebar.
- Ajout de la route `/server-logs` et de l'endpoint AJAX `/server-logs/content`.
- La page affiche en temps reel les fichiers autorises de `storage/logs` :
  - `gps-tcp.log`
  - `gps-tcp-error.log`
  - `gps-udp.log`
  - `gps-udp-error.log`
  - `gps-tcpdump.log`
  - `laravel.log`
- Ajout de controles : selection du fichier, nombre de lignes, pause/reprise et rafraichissement manuel.
- Le backend refuse les chemins arbitraires et ne lit que les fichiers explicitement autorises.

## 2026-06-04 - Sidebar scrollable invisible

- La sidebar reste scrollable lorsque le menu depasse la hauteur disponible.
- La barre de defilement visuelle est masquee sur Firefox, Chromium/WebKit et anciens moteurs Microsoft.

## 2026-06-04 - Correction boutons logs serveur

- Correction de la coloration des onglets et boutons de la page `Logs serveur`.
- Remplacement de la variable CSS inexistante par le bleu theme `--exad-primary`.
- Les etats actif, hover et focus restent maintenant lisibles.

## 2026-06-04 - Page monitoring serveur

- Creation d'une page superadmin `Monitoring serveur` accessible via la sidebar apres `Logs serveur`.
- Ajout des routes `/server-monitoring` et `/server-monitoring/metrics`.
- Ajout d'un endpoint JSON de metriques temps reel pour :
  - CPU
  - RAM
  - swap
  - disque
  - charge systeme
  - uptime
  - debits reseau entrants/sortants
  - interfaces reseau
  - informations PHP/Laravel/environnement
- La page se rafraichit automatiquement via AJAX toutes les 2 secondes.
- Les lectures systeme utilisent `/proc` sur Linux et retournent `Indisponible` proprement en environnement local non compatible.

## 2026-06-04 - Correction monitoring indisponible

- Correction de l'endpoint `/server-monitoring/metrics` qui pouvait passer en `Indisponible` sur Windows a cause de l'appel `sys_getloadavg`.
- L'appel systeme est maintenant protege et reference explicitement la fonction globale PHP.
- Les deltas CPU/reseau ne dependent plus du cache Laravel en base de donnees : un petit etat JSON local est utilise dans `storage/framework/cache`.
- La page `Monitoring serveur` affiche maintenant un rendu initial cote serveur avant le rafraichissement AJAX, afin que les metriques Ubuntu disponibles apparaissent meme si le script ou l'endpoint est temporairement retarde.
- Le script AJAX force `cache: no-store`, met le tableau reseau en etat indisponible propre en cas d'echec et evite l'affichage `Indisponible cores`.
- Correction du `500 Server Error` observe sur le VPS : remplacement de l'import invalide `Illuminate\Support\CarbonInterval` par `Carbon\CarbonInterval` pour le calcul de l'uptime.
- Refonte visuelle de la page `Monitoring serveur` en interface de supervision plus professionnelle : suppression de l'heure/message d'actualisation visibles, ajout de graphiques ApexCharts locaux pour CPU/RAM, disque, trafic reseau et charge systeme.
- Correction du dark mode sur la section Reseau du monitoring : table Bootstrap, en-tetes, lignes, bordures et cartes de debit respectent maintenant le theme sombre.
- Ajout d'icones de debit entrant/sortant sur les cartes reseau du monitoring serveur.

## 2026-06-05 - Google Maps par defaut

- Ajout de la configuration `MAP_PROVIDER=google` et `GOOGLE_MAPS_API_KEY` pour permettre le choix futur du fournisseur de carte.
- La page Carte charge maintenant Google Maps par defaut, avec le meme endpoint GeoJSON `/map/devices`, les memes filtres, les popups, les details traceur et l'historique des trajets.
- Le code Mapbox, les assets locaux Mapbox et `MAPBOX_PUBLIC_TOKEN` sont conserves pour une option de personnalisation future.
- Ajout du script `public/js/google-map.js` et des traductions de configuration Google Maps.
- Retrait du style Google Maps qui masquait les POI et le transit, afin d'afficher une carte plus detaillee avec lieux, commerces et libelles standards comme sur Navixy.
- Ajout de l'√©tat visuel parking sur la carte : les traceurs en ligne, arr√™t√©s et moteur coup√© s'affichent avec un marqueur bleu `P`, compatible Google Maps et Mapbox.
- Ajout de l'√©tat visuel arr√™t moteur allum√© : les traceurs en ligne, arr√™t√©s et moteur allum√© s'affichent avec un marqueur carr√© pour les distinguer du parking.
- Ajout de l'√©tat visuel en mouvement : les traceurs en ligne et mobiles s'affichent avec une fl√®che bleue orient√©e par l'angle GPS et une trace r√©cente qui dispara√Æt progressivement derri√®re le v√©hicule.
- La page Carte n'affiche plus les v√©hicules par d√©faut : une case permet d'afficher tous les v√©hicules, la recherche affiche une liste de r√©sultats, et la s√©lection d'un v√©hicule affiche uniquement ce v√©hicule avec sa l√©gende.
- Ajout du panneau de filtres carte repliable, avec bouton flottant pour le r√©afficher comme dans une interface de tracking type Navixy.
- Am√©lioration de la pr√©cision des emplacements : reverse geocoding Google prioritaire avec fallback Mapbox, remplacement des adresses trop g√©n√©riques et affichage adresse + coordonn√©es + altitude dans les d√©tails traceur.
- Am√©lioration de l'historique des trajets : ajout d'un service de recalage Google Roads optionnel pour faire suivre la ligne du parcours aux routes disponibles, avec fallback automatique sur les points GPS bruts.
- Correction de la logique d'historique des trajets : un trajet est maintenant born√© par les points d'arr√™t/parking, afin d'afficher les lieux o√π le v√©hicule s'est arr√™t√© ou s'est mis en parking.
- Correction de l'heure affich√©e dans les trajets : l'heure est convertie selon le fuseau horaire de la position GPS via Google Time Zone API, avec fallback `Africa/Kinshasa` lorsque l'API n'est pas disponible.
- La fiche d√©tails traceur affiche maintenant la derni√®re adresse d'arr√™t/parking connue plut√¥t que l'adresse courante du serveur si le v√©hicule est d√©j√† reparti.
- Correction des marqueurs de la carte : fl√®che directionnelle bleue sans cercle pour les v√©hicules en mouvement, `P` bleu pour le parking et carr√© bleu pour un v√©hicule arr√™t√© moteur allum√©.
- Le label du v√©hicule est maintenant ancr√© √† droite du marqueur sans d√©placer visuellement la position GPS du v√©hicule.
- La position courante du v√©hicule affich√©e sur la carte utilise maintenant strictement les coordonn√©es GPS brutes du traceur, sans recalage Google Roads, afin de respecter les cas o√π le v√©hicule se trouve dans une parcelle ou hors macadam.
- Les micro-segments de trajet sans distance r√©elle sont filtr√©s pour √©viter les lignes parasites √† `0.00 km` dans l'historique.
- Modernisation du panneau de filtres de la page Carte : largeur r√©duite, rendu glass plus premium, cartes statistiques compactes, boutons en pastilles, champs affin√©s, focus plus propre et compatibilit√© dark mode.
- Ajout d'ic√¥nes Font Awesome dans le panneau de filtres Carte et extension de la carte pour occuper toute la zone disponible de la page, avec un panneau flottant au-dessus de la carte.
- D√©calage vers le bas du bouton flottant d'affichage des filtres Carte pour √©viter la superposition avec les contr√¥les natifs Google Maps.
- Correction de la trace des v√©hicules en mouvement : la ligne se termine maintenant sur la position GPS exacte de l'ic√¥ne, m√™me lorsque le reste du trajet est recal√© par Google Roads.
- Ajout d'une animation progressive de 5 secondes sur les marqueurs Google Maps pour √©viter l'effet de saut entre deux actualisations et garder le v√©hicule s√©lectionn√© visible pendant son d√©placement.
- Correction Carte : le dernier segment de la trace suit maintenant l'ic√¥ne pendant l'animation, afin que la ligne reste toujours derri√®re le v√©hicule en mouvement.
- Correction Carte : moteur coup√© force le parking `P`, moteur allum√© sans mouvement affiche le carr√©, et l'√©tat mouvement n'est possible que si le moteur n'est pas coup√©.
- Correction Carte : rÈtablissement de l'affichage Google Maps aprËs une erreur de syntaxe JavaScript causÈe par un nom de variable dupliquÈ dans la gestion des marqueurs animÈs.
- Correction Carte : l'animation du vÈhicule suit maintenant le mÍme chemin que la trace, et la trace progressive ne contient plus de points futurs devant l'icÙne.
- Correction Carte : la popup Google Maps est plus compacte en haut et le point de statut affiche toujours le vrai statut du traceur, avec 'En ligne' en vert.
- Correction Carte : suppression de l'espace supÈrieur ajoutÈ par l'en-tÍte natif Google InfoWindow afin d'Èquilibrer le padding haut/bas de la popup vÈhicule.
- Correction Carte : Èquilibrage du padding gauche/droite dans la popup vÈhicule Google Maps, avec rÈserve uniquement sur l'en-tÍte pour le bouton fermer.
- Correction Carte : application complËte du mode sombre ‡ la popup vÈhicule Google Maps, incluant fond, pointe, bouton fermer et bouton secondaire.
- Correction Details traceur : la carte Emplacement utilise maintenant le debut reel de la session parking actuelle lorsque le moteur est coupe.
- Le temps Parking, l'adresse, les coordonnees, l'altitude, la direction et le temps relatif affiches dans Emplacement sont bases sur le premier point de la sequence continue ignition=false, et non sur le dernier ping d'actualisation.
- Ajout d'un test de regression pour garantir qu'un traceur deja en parking conserve l'adresse et la direction du moment ou le vehicule s'est mis en parking.
- Correction GSM : les valeurs de signal envoyees en barres 0-5 par certains traceurs sont normalisees en pourcentage 0-100 lors de l'ingestion GPS, et l'affichage corrige aussi les anciennes valeurs deja stockees.
- Ajout de la page superadmin `/events` pour consulter tous les evenements collectes par les traceurs, avec recherche, tri AJAX et pagination 5 lignes comme les autres tableaux.
- Ajout du menu Evenements dans la sidebar et d'un lien depuis les details traceur vers l'historique complet filtre par traceur.
- Extension de la collecte des evenements GPS : le payload peut maintenant contenir `events` pour enregistrer des evenements comme porte ouverte, freinage brusque, acceleration brusque, virage brusque, remorquage, collision ou SOS.

- Clarification mÈtier : les alertes et les ÈvÈnements vÈhicule sont dÈsormais sÈparÈs. Les pertes et retours de signal restent uniquement dans les alertes Èquipement/connexion.
- Correction de la collecte GPS : `signal_lost` et `signal_restored` ne sont plus crÈÈs dans `tracker_events`; les ÈvÈnements vÈhicule restent rÈservÈs aux faits vÈhicule comme moteur allumÈ/coupÈ, dÈplacement, arrÍt, porte ouverte, freinage brusque, etc.
- Ajout du filtre `vehicleEvents()` pour masquer les anciens ÈvÈnements techniques dans le modal dÈtails traceur et sur la page `/events`, renommÈe en …vÈnements vÈhicules.

- Correction UX Evenements vehicule : retrait du menu Evenements vehicules de la sidebar, car les evenements se consultent uniquement depuis le traceur ou vehicule selectionne.
- La route `/events` exige maintenant un traceur via `?device=...`; sans contexte elle redirige vers la page Traceurs avec un message.
- La liste complete des evenements affiche uniquement les evenements du vehicule associe au traceur selectionne, et ne melange plus les evenements des autres vehicules.

- Correction Carte : stabilisation de l animation Google Maps des vehicules en mouvement. L icone anime maintenant uniquement entre l ancienne position GPS reelle et la nouvelle position GPS reelle.
- La trace progressive est reconstruite derriere l icone pendant l animation afin d eviter les zigzags et les retours incoherents causes par le recalage Google Roads entre deux rafraichissements.

- Amelioration globale de la pagination des tableaux : remplacement de l affichage de toutes les pages par un rendu type DataTables avec 5 pages visibles, points de suspension et derniere page.
- Centralisation de la pagination dans `resources/views/partials/datatable-pagination.blade.php` pour les tableaux utilisateurs, flottes, vehicules, traceurs, alertes et evenements vehicule.
- Application du meme comportement au modal historique de connexion genere en JavaScript, avec rendu compatible dark mode.

- Amelioration du modal Details traceur : largeur legerement reduite, padding compacte, cartes plus modernes, liste des evenements contenue avec scroll interne discret et rendu dark mode ajuste.

- Amelioration UI Details traceur : icones colorees professionnellement par section, statut traceur colore, pastilles discretes et rendu dark mode ajuste.

- Correction UI Details traceur : le statut hors ligne/en ligne n'utilise plus les styles globaux de badge tableau et s'affiche en pastille compacte dans le modal.

- Amelioration du tableau de bord superadmin : ajout de graphiques ApexCharts locaux plus professionnels pour l'activite GPS, la repartition des statuts, la sante des signaux et la repartition des traceurs par flotte, avec dark mode et traductions.

## 2026-06-26 - Amelioration du panneau derniers traceurs actifs
- Refonte du bloc dashboard "Derniers traceurs actifs" avec un rendu plus professionnel et compact.
- Ajout des informations vehicule/flotte, badges statut courts, vitesse et dernier signal avec icones.
- Harmonisation du rendu clair/sombre et correction du statut qui s'etirait trop dans le tableau.
- Ajout des traductions FR/EN associees.
- Verification: php -l sur la vue et les traductions, puis php artisan test --compact (58 tests OK).

## 2026-06-26 - Widgets dashboard modernes et carte mondiale
- Modernisation des widgets du tableau de bord avec details, progression visuelle et prise en compte des filtres Semaine, Mois et Annee.
- Ajout d'une carte mondiale SVG/JS locale qui regroupe les traceurs positionnes par zone et affiche les volumes, les traceurs en ligne et les traceurs en mouvement.
- Adaptation des graphiques du dashboard a la periode selectionnee et ajout des donnees de carte dans le payload JavaScript.
- Ajout des traductions FR/EN associees et mise a jour du test dashboard.
- Verification: php -l, node --check et php artisan test --compact (58 tests OK).

## 2026-06-26 - Carte mondiale dashboard avec Datamaps
- Remplacement de la carte mondiale SVG maison du tableau de bord par Datamaps.js avec D3 et TopoJSON servis localement depuis public/vendor.
- Ajout des bulles proportionnelles par zone pour les traceurs positionnes, avec couleur selon l'etat dominant et popup de details.
- Harmonisation du panneau carte mondiale avec le mode clair/sombre et suppression de la dependance a un CDN au runtime.
- Verification : php -l resources/views/dashboard.blade.php, node --check public/js/dashboard-charts.js, php artisan test --compact (58 tests OK).

## 2026-06-26 - Ajustements Datamaps et widgets dashboard
- Correction du rendu Datamaps : carte plus visible des le chargement, pays colores uniquement au survol, puis retour automatique a la couleur initiale.
- Desactivation du highlight interne Datamaps pour eviter les pays qui restent marques apres passage du curseur.
- Reorganisation des widgets dashboard en grille 3 + 3 sur desktop, avec plus d'espace et un rendu plus professionnel.
- Verification : php -l resources/views/dashboard.blade.php, node --check public/js/dashboard-charts.js, php artisan test --compact (58 tests OK).

## 2026-06-26 - Dashboard premium et bulles carte cliquables
- Refonte visuelle des widgets dashboard : cartes plus modernes, accents colores par indicateur, hover subtil, progressions plus lisibles et compatibilite dark mode.
- Amelioration du rendu Datamaps : carte mondiale plus claire et plus nette, pays visibles des le chargement et hover limite au remplissage du pays sans bordure coloree.
- Ajout d'une navigation depuis les bulles de ville du dashboard vers la page Carte, avec filtre de recherche pre-rempli et affichage automatique des traceurs de la ville selectionnee.
- Extension de la recherche Carte pour inclure l'adresse courante du traceur, afin que les recherches par ville comme Kinshasa filtrent correctement les vehicules positionnes.
- Ajout d'un test de regression pour garantir que map.devices filtre les traceurs positionnes par ville presente dans l'adresse.
- Verification : php -l, node --check et php artisan test --compact (59 tests OK).

## 2026-06-26 - Correction dark mode derniers traceurs actifs
- Correction du mode sombre sur le bloc dashboard Derniers traceurs actifs : en-tete transparent, lignes sombres, bordures harmonisees, chips vitesse/signal adaptees et hover coherent.
- Mise a jour de la version CSS chargee par la vue dashboard pour eviter le cache navigateur.
- Verification : php -l resources/views/dashboard.blade.php et php artisan test --filter "authenticated users can view dashboard metrics".

## 2026-06-26 - Modernisation globale de la sidebar
- Refonte visuelle de la sidebar superadmin : fond premium en bleu du theme, accents lumineux discrets, liens plus modernes, icones mieux integrees et etat actif plus professionnel.
- Harmonisation des etats hover/active en mode complet, compact et tablette, avec scrollbar masquee mais sidebar toujours scrollable.
- Mise a jour du cache-buster dashboard.css sur les pages superadmin pour appliquer le nouveau rendu globalement.
- Verification : php -l resources/views/partials/sidebar.blade.php et php artisan test --filter=dashboard (3 tests OK).

## 2026-06-26 - Bouton responsive de reduction sidebar
- Repositionnement du bouton reduction/agrandissement comme une poignee flottante sur la sidebar, independante du flux du menu.
- Le bouton reste visible et fonctionnel en desktop, tablette, mobile, sidebar ouverte et sidebar reduite.
- Amelioration du script sidebar : etat accessible aria-expanded et ouverture/reduction adaptee aux petites resolutions.
- Mise a jour des cache-busters CSS/JS dashboard-sidebar sur les vues superadmin.
- Verification : serveur local confirme sur http://127.0.0.1:8002/ et php artisan test --filter=dashboard (3 tests OK).

## 2026-06-26 - Bouton sidebar suspendu hors menu
- Repositionnement du bouton de reduction/agrandissement hors de la sidebar, comme une action suspendue cote contenu, juste au-dessus de la ligne du topbar.
- Le bouton suit automatiquement la largeur actuelle de la sidebar ouverte ou reduite via une variable CSS responsive.
- Ajout du rendu dark mode du bouton et nettoyage des anciennes positions responsive qui le remettaient dans la sidebar.
- Mise a jour du cache-buster dashboard.css sur les vues superadmin.
- Verification : node --check dashboard-sidebar.js, php -l sidebar.blade.php et php artisan test --filter=dashboard (3 tests OK).

## 2026-06-26 - Repositionnement bouton sidebar en poignee laterale
- Deplacement du bouton de reduction/agrandissement en poignee accrochee au bord droit de la sidebar, a moitie dehors, pour eviter les collisions avec les contenus comme le panneau Carte.
- Conservation du comportement responsive et du suivi de largeur sidebar ouverte/reduite.
- Mise a jour du cache-buster dashboard.css sur les vues superadmin.
- Verification : php -l sidebar.blade.php, node --check dashboard-sidebar.js et php artisan test --filter=dashboard (3 tests OK).

## 2026-06-26 - Repositionnement du bouton de sidebar
- DÈplacement du bouton rÈduire/agrandir hors de la sidebar vers le topbar des pages principales, juste avant le titre.
- CrÈation du partial `resources/views/partials/sidebar-toggle.blade.php` pour rÈutiliser le mÍme contrÙle partout.
- Ajustement du CSS pour supprimer le positionnement fixe du bouton, espacer le titre et prÈserver líalignement des actions ‡ droite.
- Mise ‡ jour du cache-busting CSS/JS en `20260626-topbar-sidebar-toggle`.
- VÈrifications : `php -l` sur les vues modifiÈes, `node --check public/js/dashboard-sidebar.js`, `php artisan test --filter=dashboard`.

## 2026-06-26 - Ajustement espacement bouton sidebar topbar
- RÈduction du padding gauche du topbar pour rapprocher le bouton rÈduire/agrandir de la sidebar.
- Augmentation de líespace entre le bouton et le titre de page afin que líair soit placÈ au bon endroit.
- Mise ‡ jour du cache-busting CSS/JS en `20260626-topbar-sidebar-spacing` sur les vues superadmin.
- VÈrifications : `php -l resources/views/dashboard.blade.php`, `php -l resources/views/map/index.blade.php`, `node --check public/js/dashboard-sidebar.js`, `php artisan test --filter=dashboard`.

## 2026-06-26 - Sidebar rÈduite par dÈfaut sur tablette et mobile
- Ajustement du script sidebar pour forcer líÈtat rÈduit au chargement dËs que la rÈsolution est infÈrieure ou Ègale ‡ 1366px, mÍme si un ancien Ètat agrandi Ètait sauvegardÈ.
- Conservation du bouton rÈduire/agrandir : líutilisateur peut toujours ouvrir la sidebar manuellement sur tablette ou mobile.
- Correction CSS de líÈtat tablette : les textes du menu ne síaffichent que lorsque la sidebar est rÈellement agrandie, et restent masquÈs en mode rÈduit.
- Mise ‡ jour du cache-busting CSS/JS en `20260626-responsive-sidebar-default` sur les vues superadmin.
- VÈrifications : `node --check public/js/dashboard-sidebar.js`, `php -l resources/views/dashboard.blade.php`, `php -l resources/views/map/index.blade.php`, `php artisan test --filter=dashboard`.

## 2026-07-07 - Dashboard corporate widgets et ordre Suivi flotte
- RÈorganisation visuelle du tableau de bord : le bloc `Suivi flotte / Derniers traceurs actifs` síaffiche maintenant juste aprËs les widgets KPI.
- AmÈlioration corporate des widgets : accent vertical par tonalitÈ, fond premium plus sobre, hiÈrarchie valeur/libellÈ/dÈtail renforcÈe, barre de progression plus nette et compatibilitÈ dark mode.
- Ajustement responsive des widgets : 3 colonnes sur desktop, 2 sur tablette et 1 sur mobile.
- Mise ‡ jour du cache CSS dashboard vers `20260707-dashboard-corporate-widgets`.
- VÈrifications : `php artisan test --filter=dashboard` et `php -l app\Http\Controllers\DashboardController.php`.

## 2026-07-08 - Ajustement taille marqueurs carte
- RÈduction lÈgËre des marqueurs de carte : flËche de dÈplacement, icÙne `P` parking et carrÈ díarrÍt moteur allumÈ.
- Harmonisation des tailles Google Maps et Mapbox pour garder un rendu cohÈrent entre fournisseurs.
- Mise ‡ jour du cache-busting de `map.css` et `map.js` en `20260708-map-marker-size`.
- VÈrifications : `node --check public\js\map.js` et `php -l resources\views\map\index.blade.php`.

## 2026-07-08 - Correction ordre topbar pages superadmin
- Correction du bug o˘ le topbar/navigation descendait en bas des pages Utilisateurs et Carte aprËs la rÈorganisation du dashboard.
- Isolation des rËgles `flex/order` sur la page tableau de bord via la classe dÈdiÈe `dashboard-home-main` au lieu de les appliquer globalement ‡ `.dashboard-main`.
- Mise ‡ jour du cache-busting `dashboard.css` en `20260708-dashboard-order-scope` sur les vues superadmin.
- VÈrifications : `php -l` sur les vues dashboard/users/map et `php artisan test --filter=dashboard`.

## 2026-07-08 - Tooltip ville carte mondiale
- Normalisation du nom de ville au survol du point bleu Datamaps : premiËre lettre en majuscule, reste en minuscule.
- Correction des noms dont les lettres arrivent espacÈes afin d'afficher un nom compact, par exemple Kinshasa.
- Suppression de l'espacement typographique dans le tooltip de la carte mondiale.
- VÈrifications : `node --check public\js\dashboard-charts.js`, `php -l resources\views\dashboard.blade.php`.

## 2026-07-08 - Recherche ville carte depuis dashboard
- Correction de la recherche carte lorsqu'un point bleu du dashboard envoie une ville avec lettres espacÈes, par exemple `K I N S H A S A`.
- Normalisation du champ recherche carte : affichage en casse propre (`Kinshasa`) et suppression de l'espacement typographique.
- Normalisation backend dans `MapController` pour accepter les villes espacÈes dans le paramËtre `search`.
- Normalisation des libellÈs de ville cÙtÈ dashboard avant gÈnÈration du lien vers la carte.
- VÈrifications : `node --check public\js\google-map.js`, `node --check public\js\dashboard-charts.js`, `php -l app\Http\Controllers\MapController.php`, `php -l app\Http\Controllers\DashboardController.php`.
## 2026-07-08 - Gestion des abonnements vÈhicules
- CrÈation de la page superadmin `Abonnements`, accessible depuis la sidebar aprËs `Utilisateurs`, pour gÈrer les plans Basique, Standard et Premium.
- Ajout de la table `vehicle_subscription_plans` avec les fonctionnalitÈs configurables en JSON, líÈtat actif, la couleur et líordre díaffichage.
- PrÈenregistrement des plans par dÈfaut : Basique, Standard et Premium, avec la matrice de fonctionnalitÈs demandÈe.
- IntÈgration des plans dans le formulaire VÈhicules : le champ abonnement utilise dÈsormais les plans actifs au lieu de valeurs codÈes en dur.
- Mise ‡ jour des flottes pour afficher les compteurs Basique, Standard et Premium.
- Ajout des traductions franÁaises et anglaises de la page, des fonctionnalitÈs et des messages.
- Ajustement du test utilisateurs pour accepter le nouveau menu `Abonnements` tout en vÈrifiant que le formulaire utilisateur ne contient toujours pas de champs abonnement, grade ou statut.
- Commandes exÈcutÈes : `php artisan migrate`, `php artisan db:seed --class=VehicleSubscriptionPlanSeeder`, `php artisan route:list --path=subscriptions`, `php artisan test --stop-on-failure`.
- VÈrifications : `php -l` sur les contrÙleurs, le modËle, la migration et le seeder concernÈs ; suite complËte OK avec 59 tests passÈs.

## 2026-07-08 - Ajout abonnement via modal uniquement
- Retrait de la carte inline de creation d'abonnement sur la page `Abonnements`.
- Ajout d'un bouton `Nouvel abonnement` qui ouvre un modal dedie, au format des autres formulaires de l'application.
- Conservation de la creation d'abonnement avec nom, couleur, description et choix des fonctionnalites existantes.
- Suppression de la possibilite d'ajouter ou modifier les fonctionnalites depuis cette page : seules les affectations des fonctionnalites existantes aux abonnements restent disponibles.
- Ajout des traductions FR/EN du bouton, du titre modal et des actions creer/annuler.
- Nettoyage du CSS lie a l'ancienne carte inline et ajout du style modal.
- Verifications : `php -l` sur le controleur, la vue et le test ; `php artisan test --stop-on-failure` OK avec 60 tests et 471 assertions.

## 2026-07-08 - Bouton matrice fonctionnalitÈs en bas
- DÈplacement du bouton de sauvegarde de la matrice des abonnements sous le tableau des fonctionnalitÈs.
- Renommage du libellÈ en `Enregistrer les fonctionnalitÈs` cÙtÈ franÁais et `Save features` cÙtÈ anglais.
- Ajout d'un alignement bas ‡ droite dÈdiÈ via `.subscription-matrix-actions`.
- VÈrifications : `php -l resources\views\subscriptions\index.blade.php`, `php -l resources\lang\fr\subscriptions.php`, `php -l resources\lang\en\subscriptions.php`.

## 2026-07-09 - LibellÈ sauvegarde abonnements
- RÈtablissement du libellÈ du bouton de matrice en `Enregistrer les abonnements` cÙtÈ franÁais et `Save subscriptions` cÙtÈ anglais.
- VÈrifications : `php -l resources\lang\fr\subscriptions.php`, `php -l resources\lang\en\subscriptions.php`.

## 2026-07-09 - Taille checks matrice abonnements
- RÈduction des pastilles de validation de la matrice des abonnements pour un rendu plus discret.
- Ajustement de la taille d'icÙne et de l'ombre portÈe des checks actifs.
- Mise ‡ jour du cache-busting CSS en `20260709-subscription-check-size`.
- VÈrification : `php -l resources\views\subscriptions\index.blade.php`.
