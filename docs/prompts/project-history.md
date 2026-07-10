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

- Centraliser la sidebar dans `resources/views/partials/sidebar.blade.php` pour Ã©viter la duplication entre dashboard, utilisateurs, flottes et vÃ©hicules.
- Reprendre la logique Flottes : une flotte n'est plus rattachÃ©e directement Ã  un abonnement fonctionnel, elle est affectÃ©e Ã  un admin responsable via `fleet_user`, puis l'admin gÃ¨rera les autres utilisateurs de la flotte.
- Mettre la page Flottes au mÃªme standard que la page Utilisateurs : recherche AJAX, tri, pagination Ã  5 lignes, toast, confirmation de suppression, modal de crÃ©ation/modification et dark mode.
- Ajouter la page VÃ©hicules : modÃ¨le `Vehicle`, migration `vehicles`, relation obligatoire avec une seule flotte, lien optionnel avec `devices.vehicle_id`, contrÃ´leur, routes, tableau AJAX, modal, traductions FR/EN et tests.
- Ajouter le menu `VÃ©hicules` aprÃ¨s `Flottes` dans la sidebar partagÃ©e.
- Ajouter le favicon EXAD Tracking depuis l'image fournie dans `public/images/icon-exad-tracking.png` et le charger via `resources/views/partials/favicon.blade.php`.
- Uniformiser la validation front des formulaires avec `public/js/form-validation.js` : suppression des bulles natives navigateur, bordures rouges, messages sous les champs et compatibilitÃ© avec le loading des boutons.
- Corriger les accents des traductions franÃ§aises importantes, notamment dashboard et validation.
- Ajouter et maintenir les tests Pest associÃ©s aux nouveaux comportements : accÃ¨s aux vÃ©hicules par flotte, tableau DataTable-like, crÃ©ation/suppression avec toast et confirmation.
- Ajouter les types de vÃ©hicules complets dans la page VÃ©hicules : voitures particuliÃ¨res, SUV/4x4, pick-up, utilitaires, camions, bus/autocars, motos, tricycles/tuk-tuk, agricoles, chantier, spÃ©ciaux, Ã©lectriques/hybrides et remorques, avec traductions FR/EN et validation Laravel synchronisÃ©e.
- Ajuster la nomenclature des types de vÃ©hicules : remplacer les catÃ©gories gÃ©nÃ©riques par Voiture, Fourgonnette, Camionnette, Van, Minibus, Tracteur, Bulldozer, Pelleteuse, Niveleuse, Chargeuse, Ambulance, VÃ©hicule de police, Camion pompier, DÃ©panneuse et Remorque, avec suppression du type Ã©lectrique/hybride.
- Mettre les libellÃ©s sÃ©lectionnables des types de vÃ©hicules au singulier, notamment Camion et Bus / autocar.
- Mettre Ã  jour la page Flottes et le dashboard pour tenir compte des vÃ©hicules enregistrÃ©s : compte total par flotte, rÃ©partition Premium/Basique et indicateur VÃ©hicules sur le tableau de bord.
- Ajouter le menu `Traceurs` aprÃ¨s `VÃ©hicules`, crÃ©er la page Traceurs sur la table `devices` existante avec tableau AJAX, modal de crÃ©ation/modification, suppression confirmÃ©e, traductions FR/EN et isolation par flotte/vÃ©hicule.
- Retirer `Codec` et `Statut` du formulaire Traceurs : ces champs seront mis Ã  jour automatiquement par le futur serveur Node.js d'Ã©coute des traceurs aprÃ¨s connexion IMEI.
- Ajouter la sÃ©lection progressive Marque/ModÃ¨le dans le formulaire Traceurs : migration `brand` sur `devices`, choix initial Teltonika/EDT, affichage du modÃ¨le aprÃ¨s choix de marque et select filtrable avec recherche locale.
- Corriger la recherche du modÃ¨le Traceur pour l'intÃ©grer directement dans le select personnalisÃ©, avec dropdown interne, filtrage local et synchronisation du champ `model`.
- Remplacer le champ libre OpÃ©rateur des traceurs par un select optionnel searchable intÃ©grÃ©, alimentÃ© par une liste d'opÃ©rateurs mobiles africains et validÃ© cÃ´tÃ© Laravel.
- Mettre le statut par dÃ©faut des traceurs Ã  `inactive`/Inactif en base et cÃ´tÃ© modÃ¨le Eloquent, avec migration des anciens `offline` vers `inactive`.
- Retirer la colonne Carte SIM du tableau Traceurs et afficher `Aucun signal` en rouge pour renforcer l'Ã©tat inactif/non connectÃ©.
- Masquer dans le formulaire de crÃ©ation Traceur les vÃ©hicules dÃ©jÃ  assignÃ©s Ã  un traceur, tout en gardant le vÃ©hicule courant sÃ©lectionnable lors d'une modification et en bloquant l'assignation cÃ´tÃ© validation Laravel.
- CrÃ©er le serveur local de test `gps-listener-server-local` : Ã©coute TCP JSON sans dÃ©pendance npm, simulateur client, commandes Artisan `gps:ingest-position` et `gps:mark-stale` pour accepter uniquement les IMEI enregistrÃ©s, crÃ©er les positions et mettre Ã  jour le statut/derniÃ¨re position des traceurs.
## 2026-06-02
- Aligner le toast instantanÃ© des alertes temps rÃ©el sur le composant toast applicatif existant, avec variante bleue thÃ¨me, bouton fermer et barre de progression.
- Rendre la crÃ©ation d'alertes tolÃ©rante aux indisponibilitÃ©s Reverb : une panne WebSocket est journalisÃ©e sans casser l'ingestion GPS ni l'enregistrement de l'alerte.
- GÃ©nÃ©raliser les alertes temps rÃ©el sur toutes les pages superadmin via le partial `partials.realtime-alerts`, afin d'afficher automatiquement les nouvelles alertes dans un toast bleu quel que soit l'Ã©cran ouvert.
- Ajouter un fallback AJAX `/alerts/recent` au toast d'alertes superadmin : Reverb reste prioritaire, mais les nouvelles alertes s'affichent aussi sans actualisation si le WebSocket est temporairement indisponible.
- Rendre les titres et messages d'alertes multilingues : les clÃ©s et paramÃ¨tres sont stockÃ©s en `metadata`, puis traduits selon la langue active de la session pour le tableau, l'endpoint AJAX et les toasts.
- Traduire aussi les anciennes alertes GPS systÃ¨me dÃ©jÃ  stockÃ©es en anglais (`No signal`, `Signal restored`) et forcer le rafraÃ®chissement live du tableau Alertes sur l'ordre par dÃ©faut, derniÃ¨re alerte en premier.
- Modifier l'ordre du tableau Alertes pour placer systÃ©matiquement les alertes traitÃ©es/rÃ©solues en derniÃ¨re position, mÃªme lorsqu'un tri AJAX est appliquÃ©.
- Ajouter un bouton cloche dans la topbar superadmin, juste avant le mode sombre, avec compteur rouge des nouvelles alertes et Mise à jour automatique lors des toasts live.
- RÃ©ordonner les actions de la topbar superadmin : plein Ã©cran, mode sombre, cloche alertes, langue, profil.
- Mettre Ã  jour le footer de la sidebar partagÃ©e pour afficher `EXAD Tracking - v.1.0` partout, via les traductions dashboard.
- Activer la page Personnalisation superadmin avec la sidebar partagÃ©e, afin que Carte, Alertes et Personnalisation affichent toutes le footer `EXAD Tracking - v.1.0`.
- Rendre la version de la sidebar visible globalement aussi en mode sidebar compacte/tablette : texte complet en sidebar large et `v.1.0` en affichage compact.
- Fixer la sidebar au viewport (`sticky`, hauteur `100vh`) et casser le cache CSS global pour que le footer de version reste visible sur les pages longues comme Carte et Alertes.
- Retirer l'indicateur technique `Temps rÃ©el indisponible` de la page Alertes, car le fallback AJAX assure la continuitÃ© sans exposer l'Ã©tat WebSocket Ã  l'utilisateur.
- Corriger le dÃ©clenchement d'alertes GPS : `gps:ingest-position` crÃ©e maintenant une alerte `signal_recovered` quand un traceur inactif/hors ligne revient en ligne, sans doublonner tant qu'il reste online ; les tests couvrent aussi `no_signal`.
- Installer Laravel Reverb et poser la base temps rÃ©el des alertes superadmin : configuration broadcasting/Reverb sans npm, canal privÃ© `superadmin.alerts`, modÃ¨le/migration `alerts`, Ã©vÃ©nement `AlertCreated`, service d'alertes, page `/alerts` avec tableau AJAX, statistiques, toast live et commande `alerts:demo`.
- Verrouiller les pages de console actuelles au rÃ´le superadmin : Tableau de bord, Utilisateurs, Flottes, VÃ©hicules, Traceurs et Carte passent toutes par le middleware superadmin, avec tests d'accÃ¨s mis Ã  jour.
- Remplacer le message vide de la page Carte par une formulation professionnelle compatible production, sans mention de serveur GPS local ni de simulation.

- IntÃ©grer Mapbox localement pour la page Carte : assets `public/vendor/mapbox`, token `MAPBOX_PUBLIC_TOKEN`, route `/map`, endpoint GeoJSON `/map/devices`, layers clusterisÃ©s par statut, filtres, statistiques, popups et actualisation automatique.
- Enrichir la gestion des traceurs avec une table `tracker_events`, des Ã©tats live dÃ©taillÃ©s sur `devices`, la gÃ©nÃ©ration automatique des Ã©vÃ©nements GPS (signal, mouvement, contact), une modale de dÃ©tails Traceur affichant Flotte, emplacement, GSM et derniers Ã©vÃ©nements, ainsi que la Mise à jour du simulateur local.
- Ajouter la section Alimentation dans les dÃ©tails Traceur avec tension externe, batterie interne, niveau de batterie et contact, puis clarifier la ligne `P` comme information de parking.
- Ajouter lâ€™historique des trajets dâ€™un Ã©quipement sur les pages Traceurs et Carte : endpoint partagÃ© `/trackers/{device}/trips`, choix de pÃ©riode, rendu timeline, rÃ©sumÃ© distance/durÃ©e et GeoJSON de tracÃ© Mapbox.
- Remplacer les coordonnÃ©es brutes des trajets par des adresses lisibles via Mapbox Reverse Geocoding quand `positions.address` est vide, avec mÃ©morisation de lâ€™adresse trouvÃ©e en base.
- Ajouter sur la page Carte un bouton de popup permettant dâ€™ouvrir les dÃ©tails du traceur avec la mÃªme modale que la page Traceurs, via le partial et le script partagÃ©s `tracker-details`.
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
- Ajout de l'Ã©tat visuel parking sur la carte : les traceurs en ligne, arrÃªtÃ©s et moteur coupÃ© s'affichent avec un marqueur bleu `P`, compatible Google Maps et Mapbox.
- Ajout de l'Ã©tat visuel arrÃªt moteur allumÃ© : les traceurs en ligne, arrÃªtÃ©s et moteur allumÃ© s'affichent avec un marqueur carrÃ© pour les distinguer du parking.
- Ajout de l'Ã©tat visuel en mouvement : les traceurs en ligne et mobiles s'affichent avec une flÃ¨che bleue orientÃ©e par l'angle GPS et une trace rÃ©cente qui disparaÃ®t progressivement derriÃ¨re le vÃ©hicule.
- La page Carte n'affiche plus les vÃ©hicules par dÃ©faut : une case permet d'afficher tous les vÃ©hicules, la recherche affiche une liste de rÃ©sultats, et la sÃ©lection d'un vÃ©hicule affiche uniquement ce vÃ©hicule avec sa lÃ©gende.
- Ajout du panneau de filtres carte repliable, avec bouton flottant pour le rÃ©afficher comme dans une interface de tracking type Navixy.
- AmÃ©lioration de la prÃ©cision des emplacements : reverse geocoding Google prioritaire avec fallback Mapbox, remplacement des adresses trop gÃ©nÃ©riques et affichage adresse + coordonnÃ©es + altitude dans les dÃ©tails traceur.
- AmÃ©lioration de l'historique des trajets : ajout d'un service de recalage Google Roads optionnel pour faire suivre la ligne du parcours aux routes disponibles, avec fallback automatique sur les points GPS bruts.
- Correction de la logique d'historique des trajets : un trajet est maintenant bornÃ© par les points d'arrÃªt/parking, afin d'afficher les lieux oÃ¹ le vÃ©hicule s'est arrÃªtÃ© ou s'est mis en parking.
- Correction de l'heure affichÃ©e dans les trajets : l'heure est convertie selon le fuseau horaire de la position GPS via Google Time Zone API, avec fallback `Africa/Kinshasa` lorsque l'API n'est pas disponible.
- La fiche dÃ©tails traceur affiche maintenant la derniÃ¨re adresse d'arrÃªt/parking connue plutÃ´t que l'adresse courante du serveur si le vÃ©hicule est dÃ©jÃ  reparti.
- Correction des marqueurs de la carte : flÃ¨che directionnelle bleue sans cercle pour les vÃ©hicules en mouvement, `P` bleu pour le parking et carrÃ© bleu pour un vÃ©hicule arrÃªtÃ© moteur allumÃ©.
- Le label du vÃ©hicule est maintenant ancrÃ© Ã  droite du marqueur sans dÃ©placer visuellement la position GPS du vÃ©hicule.
- La position courante du vÃ©hicule affichÃ©e sur la carte utilise maintenant strictement les coordonnÃ©es GPS brutes du traceur, sans recalage Google Roads, afin de respecter les cas oÃ¹ le vÃ©hicule se trouve dans une parcelle ou hors macadam.
- Les micro-segments de trajet sans distance rÃ©elle sont filtrÃ©s pour Ã©viter les lignes parasites Ã  `0.00 km` dans l'historique.
- Modernisation du panneau de filtres de la page Carte : largeur rÃ©duite, rendu glass plus premium, cartes statistiques compactes, boutons en pastilles, champs affinÃ©s, focus plus propre et compatibilitÃ© dark mode.
- Ajout d'icÃ´nes Font Awesome dans le panneau de filtres Carte et extension de la carte pour occuper toute la zone disponible de la page, avec un panneau flottant au-dessus de la carte.
- DÃ©calage vers le bas du bouton flottant d'affichage des filtres Carte pour Ã©viter la superposition avec les contrÃ´les natifs Google Maps.
- Correction de la trace des vÃ©hicules en mouvement : la ligne se termine maintenant sur la position GPS exacte de l'icÃ´ne, mÃªme lorsque le reste du trajet est recalÃ© par Google Roads.
- Ajout d'une animation progressive de 5 secondes sur les marqueurs Google Maps pour Ã©viter l'effet de saut entre deux actualisations et garder le vÃ©hicule sÃ©lectionnÃ© visible pendant son dÃ©placement.
- Correction Carte : le dernier segment de la trace suit maintenant l'icÃ´ne pendant l'animation, afin que la ligne reste toujours derriÃ¨re le vÃ©hicule en mouvement.
- Correction Carte : moteur coupÃ© force le parking `P`, moteur allumÃ© sans mouvement affiche le carrÃ©, et l'Ã©tat mouvement n'est possible que si le moteur n'est pas coupÃ©.
- Correction Carte : rétablissement de l'affichage Google Maps après une erreur de syntaxe JavaScript causée par un nom de variable dupliqué dans la gestion des marqueurs animés.
- Correction Carte : l'animation du véhicule suit maintenant le même chemin que la trace, et la trace progressive ne contient plus de points futurs devant l'icône.
- Correction Carte : la popup Google Maps est plus compacte en haut et le point de statut affiche toujours le vrai statut du traceur, avec 'En ligne' en vert.
- Correction Carte : suppression de l'espace supérieur ajouté par l'en-tête natif Google InfoWindow afin d'équilibrer le padding haut/bas de la popup véhicule.
- Correction Carte : équilibrage du padding gauche/droite dans la popup véhicule Google Maps, avec réserve uniquement sur l'en-tête pour le bouton fermer.
- Correction Carte : application complète du mode sombre à la popup véhicule Google Maps, incluant fond, pointe, bouton fermer et bouton secondaire.
- Correction Details traceur : la carte Emplacement utilise maintenant le debut reel de la session parking actuelle lorsque le moteur est coupe.
- Le temps Parking, l'adresse, les coordonnees, l'altitude, la direction et le temps relatif affiches dans Emplacement sont bases sur le premier point de la sequence continue ignition=false, et non sur le dernier ping d'actualisation.
- Ajout d'un test de regression pour garantir qu'un traceur deja en parking conserve l'adresse et la direction du moment ou le vehicule s'est mis en parking.
- Correction GSM : les valeurs de signal envoyees en barres 0-5 par certains traceurs sont normalisees en pourcentage 0-100 lors de l'ingestion GPS, et l'affichage corrige aussi les anciennes valeurs deja stockees.
- Ajout de la page superadmin `/events` pour consulter tous les evenements collectes par les traceurs, avec recherche, tri AJAX et pagination 5 lignes comme les autres tableaux.
- Ajout du menu Evenements dans la sidebar et d'un lien depuis les details traceur vers l'historique complet filtre par traceur.
- Extension de la collecte des evenements GPS : le payload peut maintenant contenir `events` pour enregistrer des evenements comme porte ouverte, freinage brusque, acceleration brusque, virage brusque, remorquage, collision ou SOS.

- Clarification métier : les alertes et les événements véhicule sont désormais séparés. Les pertes et retours de signal restent uniquement dans les alertes équipement/connexion.
- Correction de la collecte GPS : `signal_lost` et `signal_restored` ne sont plus créés dans `tracker_events`; les événements véhicule restent réservés aux faits véhicule comme moteur allumé/coupé, déplacement, arrêt, porte ouverte, freinage brusque, etc.
- Ajout du filtre `vehicleEvents()` pour masquer les anciens événements techniques dans le modal détails traceur et sur la page `/events`, renommée en Événements véhicules.

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
- Déplacement du bouton réduire/agrandir hors de la sidebar vers le topbar des pages principales, juste avant le titre.
- Création du partial `resources/views/partials/sidebar-toggle.blade.php` pour réutiliser le même contrôle partout.
- Ajustement du CSS pour supprimer le positionnement fixe du bouton, espacer le titre et préserver l’alignement des actions à droite.
- Mise à jour du cache-busting CSS/JS en `20260626-topbar-sidebar-toggle`.
- Vérifications : `php -l` sur les vues modifiées, `node --check public/js/dashboard-sidebar.js`, `php artisan test --filter=dashboard`.

## 2026-06-26 - Ajustement espacement bouton sidebar topbar
- Réduction du padding gauche du topbar pour rapprocher le bouton réduire/agrandir de la sidebar.
- Augmentation de l’espace entre le bouton et le titre de page afin que l’air soit placé au bon endroit.
- Mise à jour du cache-busting CSS/JS en `20260626-topbar-sidebar-spacing` sur les vues superadmin.
- Vérifications : `php -l resources/views/dashboard.blade.php`, `php -l resources/views/map/index.blade.php`, `node --check public/js/dashboard-sidebar.js`, `php artisan test --filter=dashboard`.

## 2026-06-26 - Sidebar réduite par défaut sur tablette et mobile
- Ajustement du script sidebar pour forcer l’état réduit au chargement dès que la résolution est inférieure ou égale à 1366px, même si un ancien état agrandi était sauvegardé.
- Conservation du bouton réduire/agrandir : l’utilisateur peut toujours ouvrir la sidebar manuellement sur tablette ou mobile.
- Correction CSS de l’état tablette : les textes du menu ne s’affichent que lorsque la sidebar est réellement agrandie, et restent masqués en mode réduit.
- Mise à jour du cache-busting CSS/JS en `20260626-responsive-sidebar-default` sur les vues superadmin.
- Vérifications : `node --check public/js/dashboard-sidebar.js`, `php -l resources/views/dashboard.blade.php`, `php -l resources/views/map/index.blade.php`, `php artisan test --filter=dashboard`.

## 2026-07-07 - Dashboard corporate widgets et ordre Suivi flotte
- Réorganisation visuelle du tableau de bord : le bloc `Suivi flotte / Derniers traceurs actifs` s’affiche maintenant juste après les widgets KPI.
- Amélioration corporate des widgets : accent vertical par tonalité, fond premium plus sobre, hiérarchie valeur/libellé/détail renforcée, barre de progression plus nette et compatibilité dark mode.
- Ajustement responsive des widgets : 3 colonnes sur desktop, 2 sur tablette et 1 sur mobile.
- Mise à jour du cache CSS dashboard vers `20260707-dashboard-corporate-widgets`.
- Vérifications : `php artisan test --filter=dashboard` et `php -l app\Http\Controllers\DashboardController.php`.

## 2026-07-08 - Ajustement taille marqueurs carte
- Réduction légère des marqueurs de carte : flèche de déplacement, icône `P` parking et carré d’arrêt moteur allumé.
- Harmonisation des tailles Google Maps et Mapbox pour garder un rendu cohérent entre fournisseurs.
- Mise à jour du cache-busting de `map.css` et `map.js` en `20260708-map-marker-size`.
- Vérifications : `node --check public\js\map.js` et `php -l resources\views\map\index.blade.php`.

## 2026-07-08 - Correction ordre topbar pages superadmin
- Correction du bug où le topbar/navigation descendait en bas des pages Utilisateurs et Carte après la réorganisation du dashboard.
- Isolation des règles `flex/order` sur la page tableau de bord via la classe dédiée `dashboard-home-main` au lieu de les appliquer globalement à `.dashboard-main`.
- Mise à jour du cache-busting `dashboard.css` en `20260708-dashboard-order-scope` sur les vues superadmin.
- Vérifications : `php -l` sur les vues dashboard/users/map et `php artisan test --filter=dashboard`.

## 2026-07-08 - Tooltip ville carte mondiale
- Normalisation du nom de ville au survol du point bleu Datamaps : première lettre en majuscule, reste en minuscule.
- Correction des noms dont les lettres arrivent espacées afin d'afficher un nom compact, par exemple Kinshasa.
- Suppression de l'espacement typographique dans le tooltip de la carte mondiale.
- Vérifications : `node --check public\js\dashboard-charts.js`, `php -l resources\views\dashboard.blade.php`.

## 2026-07-08 - Recherche ville carte depuis dashboard
- Correction de la recherche carte lorsqu'un point bleu du dashboard envoie une ville avec lettres espacées, par exemple `K I N S H A S A`.
- Normalisation du champ recherche carte : affichage en casse propre (`Kinshasa`) et suppression de l'espacement typographique.
- Normalisation backend dans `MapController` pour accepter les villes espacées dans le paramètre `search`.
- Normalisation des libellés de ville côté dashboard avant génération du lien vers la carte.
- Vérifications : `node --check public\js\google-map.js`, `node --check public\js\dashboard-charts.js`, `php -l app\Http\Controllers\MapController.php`, `php -l app\Http\Controllers\DashboardController.php`.
## 2026-07-08 - Gestion des abonnements véhicules
- Création de la page superadmin `Abonnements`, accessible depuis la sidebar après `Utilisateurs`, pour gérer les plans Basique, Standard et Premium.
- Ajout de la table `vehicle_subscription_plans` avec les fonctionnalités configurables en JSON, l’état actif, la couleur et l’ordre d’affichage.
- Préenregistrement des plans par défaut : Basique, Standard et Premium, avec la matrice de fonctionnalités demandée.
- Intégration des plans dans le formulaire Véhicules : le champ abonnement utilise désormais les plans actifs au lieu de valeurs codées en dur.
- Mise à jour des flottes pour afficher les compteurs Basique, Standard et Premium.
- Ajout des traductions françaises et anglaises de la page, des fonctionnalités et des messages.
- Ajustement du test utilisateurs pour accepter le nouveau menu `Abonnements` tout en vérifiant que le formulaire utilisateur ne contient toujours pas de champs abonnement, grade ou statut.
- Commandes exécutées : `php artisan migrate`, `php artisan db:seed --class=VehicleSubscriptionPlanSeeder`, `php artisan route:list --path=subscriptions`, `php artisan test --stop-on-failure`.
- Vérifications : `php -l` sur les contrôleurs, le modèle, la migration et le seeder concernés ; suite complète OK avec 59 tests passés.

## 2026-07-08 - Ajout abonnement via modal uniquement
- Retrait de la carte inline de creation d'abonnement sur la page `Abonnements`.
- Ajout d'un bouton `Nouvel abonnement` qui ouvre un modal dedie, au format des autres formulaires de l'application.
- Conservation de la creation d'abonnement avec nom, couleur, description et choix des fonctionnalites existantes.
- Suppression de la possibilite d'ajouter ou modifier les fonctionnalites depuis cette page : seules les affectations des fonctionnalites existantes aux abonnements restent disponibles.
- Ajout des traductions FR/EN du bouton, du titre modal et des actions creer/annuler.
- Nettoyage du CSS lie a l'ancienne carte inline et ajout du style modal.
- Verifications : `php -l` sur le controleur, la vue et le test ; `php artisan test --stop-on-failure` OK avec 60 tests et 471 assertions.

## 2026-07-08 - Bouton matrice fonctionnalités en bas
- Déplacement du bouton de sauvegarde de la matrice des abonnements sous le tableau des fonctionnalités.
- Renommage du libellé en `Enregistrer les fonctionnalités` côté français et `Save features` côté anglais.
- Ajout d'un alignement bas à droite dédié via `.subscription-matrix-actions`.
- Vérifications : `php -l resources\views\subscriptions\index.blade.php`, `php -l resources\lang\fr\subscriptions.php`, `php -l resources\lang\en\subscriptions.php`.

## 2026-07-09 - Libellé sauvegarde abonnements
- Rétablissement du libellé du bouton de matrice en `Enregistrer les abonnements` côté français et `Save subscriptions` côté anglais.
- Vérifications : `php -l resources\lang\fr\subscriptions.php`, `php -l resources\lang\en\subscriptions.php`.

## 2026-07-09 - Taille checks matrice abonnements
- Réduction des pastilles de validation de la matrice des abonnements pour un rendu plus discret.
- Ajustement de la taille d'icône et de l'ombre portée des checks actifs.
- Mise à jour du cache-busting CSS en `20260709-subscription-check-size`.
- Vérification : `php -l resources\views\subscriptions\index.blade.php`.

## 2026-07-09 - Raffinement dashboard superadmin
- Réorganisation DOM du tableau de bord : le bloc `Suivi flotte / Derniers traceurs actifs` est désormais placé directement après les widgets KPI, avant la carte mondiale et les graphiques.
- Raffinement corporate des widgets KPI : cartes plus compactes, accents plus sobres, meilleure hiérarchie visuelle et rendu dark mode amélioré.
- Amélioration de la carte mondiale Datamaps : pays plus lisibles par défaut, hover par remplissage intérieur sans bordure agressive, et conservation du clic sur les bulles vers la page Carte filtrée.
- Mise à jour du cache-busting dashboard CSS/JS en `20260709-dashboard-refinement`.
- Vérifications : `php -l app\Http\Controllers\DashboardController.php`, `php -l resources\views\dashboard.blade.php`, `node --check public\js\dashboard-charts.js`.
## 2026-07-09 - Module règles d’alertes
- Création du module superadmin `Règles alertes` pour configurer les règles de supervision inspirées Navixy.
- Ajout de la table `alert_rules` avec séparation claire entre alertes équipement et événements véhicule.
- Préconfiguration des règles par défaut : aucun signal, signal GSM faible, batterie faible, coupure alimentation externe, OBD déconnecté, brouillage GPS/GSM, excès de vitesse, ralenti prolongé, porte ouverte, freinage brusque, collision détectée et SOS.
- Ajout du périmètre de règle : tous les actifs, flotte, véhicule ou traceur, avec seuil, unité, canaux, planning et état actif.
- Création de la page `Règles alertes` avec tableau AJAX style DataTable, recherche, tri, pagination, badges de criticité, modal création/modification, confirmation de suppression et toast.
- Ajout des traductions françaises et anglaises de tous les textes visibles du module.
- Ajout du menu `Règles alertes` dans la sidebar superadmin juste après `Alertes`.
- Commandes exécutées : `php artisan migrate`, `php artisan db:seed --class=AlertRuleSeeder`, `php artisan route:list --name=alert-rules`, `php artisan test --filter=dashboard`.
- Vérifications : `php -l` sur le contrôleur, le modèle, la migration, le seeder et les vues du module.
- Tests module règles alertes : php artisan test --stop-on-failure OK avec 61 tests et 492 assertions.

## 2026-07-09 - Télémétrie traceur enrichie et supervision dashboard
- Ajout d'une migration pour stocker la télémétrie avancée des traceurs : odomètre, heures moteur, capteurs, IO et payload brut.
- Mise à jour du modèle `Device` pour caster les nouvelles données JSON et numériques.
- Extension de la commande `gps:ingest-position` afin de sauvegarder codec, odomètre, heures moteur, capteurs, IO et données brutes envoyées par le serveur GPS.
- Enrichissement de la fiche détail traceur : SIM, protocole, codec, satellites, odomètre, heures moteur, nombre de capteurs, nombre d'entrées/sorties et données brutes consultables.
- Ajout de traductions FR/EN pour les nouvelles données visibles de la fiche traceur.
- Ajout d'une supervision opérationnelle sur le tableau de bord superadmin : traceurs sans signal, vitesses élevées, ralenti moteur et batteries faibles.
- Ajout du style responsive et dark mode pour les nouvelles cartes de supervision du dashboard.
- Commande exécutée : `php artisan migrate`.
- Vérifications : `php -l` sur `DashboardController`, `Device`, `routes/console.php` et fichiers de langue dashboard.
- Tests : `php artisan test --stop-on-failure` OK avec 61 tests et 492 assertions.
## 2026-07-09 - Module rapports superadmin
- Création de la page superadmin `Rapports`, accessible depuis la sidebar, pour générer des rapports opérationnels.
- Ajout des types de rapports : positions GPS, événements véhicules, alertes équipement et synthèse des flottes.
- Ajout de filtres par période, flotte, véhicule, traceur et recherche texte, avec tableau AJAX, tri et pagination de 5 lignes.
- Ajout des exports CSV et impression/PDF navigateur pour les rapports filtrés.
- Ajout de la planification de rapports récurrents avec fréquence quotidienne, hebdomadaire ou mensuelle, destinataires et format.
- Création de la table `scheduled_reports` et du modèle `ScheduledReport`.
- Ajout des traductions françaises et anglaises du module, du modal de planification, des colonnes et des messages.
- Ajout des styles dédiés avec support responsive et dark mode, en conservant le format visuel des autres pages superadmin.
- Commandes exécutées : `php artisan migrate`, `php artisan route:list --name=reports`, `php artisan test tests/Feature/ReportsTest.php`, `php artisan test --stop-on-failure`.
- Vérifications : `php -l` sur `ReportController`, `ScheduledReport`, la migration et le test.
- Tests : `php artisan test --stop-on-failure` OK avec 65 tests et 509 assertions.

## 2026-07-09 - Rapports PDF Dompdf, filtres et encodage traceur
- Installation de `barryvdh/laravel-dompdf` pour générer les exports PDF réels des rapports.
- Mise à jour de l'export `format=print` pour télécharger un PDF A4 paysage via Dompdf au lieu d'une page HTML imprimable.
- Ajustement du formulaire de filtres Rapports : espacement entre recherche et boutons, bouton Filtrer au bleu du thème, exports CSV/PDF mieux séparés et responsive.
- Correction des libellés FR double-encodés dans la fiche traceur : Odomètre, entrées/sorties, Données brutes.
- Test renforcé : vérification que l'export PDF répond en `application/pdf`.
- Vérifications : `php -l app/Http/Controllers/ReportController.php`, `php -l resources/lang/fr/trackers.php`, `php artisan test tests/Feature/ReportsTest.php --stop-on-failure`.

## 2026-07-09 - Rapports PDF inline et boutons rapports
- Changement de l'export PDF Dompdf de telechargement vers affichage inline dans le navigateur via stream().
- Correction des couleurs des boutons de filtrage, export et planification des rapports pour eviter l'heritage CSS sombre sur les textes et icones.
- Verification ajoutee dans les tests pour garantir le Content-Disposition inline du PDF.
- Tests executes : php artisan test tests\\Feature\\ReportsTest.php --stop-on-failure, puis php artisan test --stop-on-failure.


## 2026-07-10 - Carte Google : zoom selection et trace GPS reelle
- Limitation du zoom lors de la selection d'un seul vehicule sur la carte Google afin de conserver une vue operationnelle moins serree.
- Remplacement de la trace de mouvement snappee par la trace GPS brute des positions enregistrees, avec jusqu'a 80 points sur les 120 dernieres minutes.
- Correction de l'animation du vehicule : la ligne de suivi reste derriere l'icone et s'appuie sur le segment courant du trajet pour eviter les retours visuels.
- Ajustement du test carte pour verifier que la trace suit les points GPS enregistres.
- Verifications : `php -l app\\Http\\Controllers\\MapController.php`, `php -l tests\\Feature\\ExampleTest.php`, `node --check public/js/google-map.js`, `php artisan test --filter=map`.

### 2026-07-10 - Rapports PDF, carte et fiche traceur
- Les rapports PDF Dompdf sont tÃ©lÃ©chargÃ©s directement au lieu d'Ãªtre ouverts en lecture dans le navigateur.
- Le zoom automatique de sÃ©lection d'un vÃ©hicule sur la carte Google a Ã©tÃ© rÃ©duit pour conserver davantage de contexte autour du vÃ©hicule.
- La fiche dÃ©tail traceur ne montre plus les donnÃ©es brutes et ajoute un bloc OBD/CAN bus avec odomÃ¨tre, heures moteur, rÃ©gime moteur, carburant, tempÃ©rature et protocole lorsque ces donnÃ©es sont disponibles.

## 2026-07-10 - Codec traceur, OBD/CAN et diagnostics
- RÃ©utilisation du champ existant `devices.codec` pour stocker le codec rÃ©el reÃ§u par les traceurs, sans crÃ©er de champ doublon.
- Ajout d'une migration pour enrichir la table `devices` avec les mÃ©triques OBD/CAN : rÃ©gime moteur, vitesse OBD, papillon, tempÃ©rature moteur, tension module, charge moteur, distance avec dÃ©faut, erreurs, distance depuis rÃ©initialisation, carburant CAN, kilomÃ©trage CAN et dates de mise Ã  jour.
- Extension du modÃ¨le `Device` avec les nouveaux champs remplissables et les casts adaptÃ©s.
- Extension de la commande `gps:ingest-position` pour accepter les blocs `obd`, `can`, `io`, `sensors`, `raw`, le codec, l'odomÃ¨tre, les heures moteur et l'adresse.
- Ajout d'une rÃ©solution d'adresse cÃ´tÃ© ingestion via le service de reverse geocoding lorsque le serveur GPS n'envoie pas directement l'adresse.
- Enrichissement de la fiche dÃ©tail traceur : OBD/CAN bus, diagnostic traceur, satellites, odomÃ¨tre, heures moteur, entrÃ©es/sorties, capteurs et dates de fraÃ®cheur.
- AmÃ©lioration de l'affichage des rapports : espacement clair entre le nom du traceur et son numÃ©ro de sÃ©rie/IMEI.
- Ajout des traductions FR/EN pour les nouvelles mÃ©triques OBD/CAN visibles.
- Commandes exÃ©cutÃ©es : `php artisan migrate`, `php artisan test --filter=ReportsTest --stop-on-failure`.
- VÃ©rifications : `php -l` sur `Device`, `routes/console.php`, la migration, les traductions et les vues rapports/dÃ©tails traceur.

## 2026-07-10 - Carte : trace de dÃ©placement courte et zoom de sÃ©lection
- RÃ©duction du tracÃ© de dÃ©placement aux points rÃ©cents, continus et bornÃ©s en distance pour Ã©viter les longues lignes parasites.
- Rapprochement lÃ©ger du zoom lorsquâ€™un vÃ©hicule est sÃ©lectionnÃ© depuis la recherche de la carte.
- VÃ©rifications : `php -l app\Http\Controllers\MapController.php` et `node --check public\js\google-map.js`.
