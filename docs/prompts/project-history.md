# Historique des prompts

Ce fichier garde une trace des demandes importantes effectuees pendant le projet.

## 2026-07-14 - Panneau trajets compact et lecteur fixe

- Réduction du panneau des trajets à 480 px sur ordinateur pour préserver une large zone de lecture de la carte.
- Transformation du lecteur de parcours en barre compacte et fixe dans la zone défilable : lecture, vitesse, redémarrage, progression et durée restent visibles pendant le défilement.
- Réorganisation des commandes de replay avec des boutons plus petits, des libellés lisibles et une hiérarchie visuelle plus nette.
- Refonte de la liste des trajets en éléments compacts avec chronologie, départ, arrivée, distance, durée, points GPS, vitesses et sélecteur de couleur.
- Ajout d'états de survol et de sélection plus sobres, ainsi que d'un rendu cohérent en mode sombre.
- Ajustement responsive du panneau et des contrôles sur tablette et mobile.
- Vérifications : `git diff --check`, compilation des vues Blade et `php artisan test --compact` OK avec 65 tests et 520 assertions.

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

- Centraliser la sidebar dans `resources/views/partials/sidebar.blade.php` pour éviter la duplication entre dashboard, utilisateurs, flottes et véhicules.
- Reprendre la logique Flottes : une flotte n'est plus rattachée directement à un abonnement fonctionnel, elle est affectée à un admin responsable via `fleet_user`, puis l'admin gèrera les autres utilisateurs de la flotte.
- Mettre la page Flottes au même standard que la page Utilisateurs : recherche AJAX, tri, pagination à 5 lignes, toast, confirmation de suppression, modal de création/modification et dark mode.
- Ajouter la page Véhicules : modèle `Vehicle`, migration `vehicles`, relation obligatoire avec une seule flotte, lien optionnel avec `devices.vehicle_id`, contrôleur, routes, tableau AJAX, modal, traductions FR/EN et tests.
- Ajouter le menu `Véhicules` après `Flottes` dans la sidebar partagée.
- Ajouter le favicon EXAD Tracking depuis l'image fournie dans `public/images/icon-exad-tracking.png` et le charger via `resources/views/partials/favicon.blade.php`.
- Uniformiser la validation front des formulaires avec `public/js/form-validation.js` : suppression des bulles natives navigateur, bordures rouges, messages sous les champs et compatibilité avec le loading des boutons.
- Corriger les accents des traductions françaises importantes, notamment dashboard et validation.
- Ajouter et maintenir les tests Pest associés aux nouveaux comportements : accès aux véhicules par flotte, tableau DataTable-like, création/suppression avec toast et confirmation.
- Ajouter les types de véhicules complets dans la page Véhicules : voitures particulières, SUV/4x4, pick-up, utilitaires, camions, bus/autocars, motos, tricycles/tuk-tuk, agricoles, chantier, spéciaux, électriques/hybrides et remorques, avec traductions FR/EN et validation Laravel synchronisée.
- Ajuster la nomenclature des types de véhicules : remplacer les catégories génériques par Voiture, Fourgonnette, Camionnette, Van, Minibus, Tracteur, Bulldozer, Pelleteuse, Niveleuse, Chargeuse, Ambulance, Véhicule de police, Camion pompier, Dépanneuse et Remorque, avec suppression du type électrique/hybride.
- Mettre les libellés sélectionnables des types de véhicules au singulier, notamment Camion et Bus / autocar.
- Mettre à jour la page Flottes et le dashboard pour tenir compte des véhicules enregistrés : compte total par flotte, répartition Premium/Basique et indicateur Véhicules sur le tableau de bord.
- Ajouter le menu `Traceurs` après `Véhicules`, créer la page Traceurs sur la table `devices` existante avec tableau AJAX, modal de création/modification, suppression confirmée, traductions FR/EN et isolation par flotte/véhicule.
- Retirer `Codec` et `Statut` du formulaire Traceurs : ces champs seront mis à jour automatiquement par le futur serveur Node.js d'écoute des traceurs après connexion IMEI.
- Ajouter la sélection progressive Marque/Modèle dans le formulaire Traceurs : migration `brand` sur `devices`, choix initial Teltonika/EDT, affichage du modèle après choix de marque et select filtrable avec recherche locale.
- Corriger la recherche du modèle Traceur pour l'intégrer directement dans le select personnalisé, avec dropdown interne, filtrage local et synchronisation du champ `model`.
- Remplacer le champ libre Opérateur des traceurs par un select optionnel searchable intégré, alimenté par une liste d'opérateurs mobiles africains et validé côté Laravel.
- Mettre le statut par défaut des traceurs à `inactive`/Inactif en base et côté modèle Eloquent, avec migration des anciens `offline` vers `inactive`.
- Retirer la colonne Carte SIM du tableau Traceurs et afficher `Aucun signal` en rouge pour renforcer l'état inactif/non connecté.
- Masquer dans le formulaire de création Traceur les véhicules déjà assignés à un traceur, tout en gardant le véhicule courant sélectionnable lors d'une modification et en bloquant l'assignation côté validation Laravel.
- Créer le serveur local de test `gps-listener-server-local` : écoute TCP JSON sans dépendance npm, simulateur client, commandes Artisan `gps:ingest-position` et `gps:mark-stale` pour accepter uniquement les IMEI enregistrés, créer les positions et mettre à jour le statut/dernière position des traceurs.
## 2026-06-02
- Aligner le toast instantané des alertes temps réel sur le composant toast applicatif existant, avec variante bleue thème, bouton fermer et barre de progression.
- Rendre la création d'alertes tolérante aux indisponibilités Reverb : une panne WebSocket est journalisée sans casser l'ingestion GPS ni l'enregistrement de l'alerte.
- Généraliser les alertes temps réel sur toutes les pages superadmin via le partial `partials.realtime-alerts`, afin d'afficher automatiquement les nouvelles alertes dans un toast bleu quel que soit l'écran ouvert.
- Ajouter un fallback AJAX `/alerts/recent` au toast d'alertes superadmin : Reverb reste prioritaire, mais les nouvelles alertes s'affichent aussi sans actualisation si le WebSocket est temporairement indisponible.
- Rendre les titres et messages d'alertes multilingues : les clés et paramètres sont stockés en `metadata`, puis traduits selon la langue active de la session pour le tableau, l'endpoint AJAX et les toasts.
- Traduire aussi les anciennes alertes GPS système déjà stockées en anglais (`No signal`, `Signal restored`) et forcer le rafraîchissement live du tableau Alertes sur l'ordre par défaut, dernière alerte en premier.
- Modifier l'ordre du tableau Alertes pour placer systématiquement les alertes traitées/résolues en dernière position, même lorsqu'un tri AJAX est appliqué.
- Ajouter un bouton cloche dans la topbar superadmin, juste avant le mode sombre, avec compteur rouge des nouvelles alertes et Mise � jour automatique lors des toasts live.
- Réordonner les actions de la topbar superadmin : plein écran, mode sombre, cloche alertes, langue, profil.
- Mettre à jour le footer de la sidebar partagée pour afficher `EXAD Tracking - v.1.0` partout, via les traductions dashboard.
- Activer la page Personnalisation superadmin avec la sidebar partagée, afin que Carte, Alertes et Personnalisation affichent toutes le footer `EXAD Tracking - v.1.0`.
- Rendre la version de la sidebar visible globalement aussi en mode sidebar compacte/tablette : texte complet en sidebar large et `v.1.0` en affichage compact.
- Fixer la sidebar au viewport (`sticky`, hauteur `100vh`) et casser le cache CSS global pour que le footer de version reste visible sur les pages longues comme Carte et Alertes.
- Retirer l'indicateur technique `Temps réel indisponible` de la page Alertes, car le fallback AJAX assure la continuité sans exposer l'état WebSocket à l'utilisateur.
- Corriger le déclenchement d'alertes GPS : `gps:ingest-position` crée maintenant une alerte `signal_recovered` quand un traceur inactif/hors ligne revient en ligne, sans doublonner tant qu'il reste online ; les tests couvrent aussi `no_signal`.
- Installer Laravel Reverb et poser la base temps réel des alertes superadmin : configuration broadcasting/Reverb sans npm, canal privé `superadmin.alerts`, modèle/migration `alerts`, événement `AlertCreated`, service d'alertes, page `/alerts` avec tableau AJAX, statistiques, toast live et commande `alerts:demo`.
- Verrouiller les pages de console actuelles au rôle superadmin : Tableau de bord, Utilisateurs, Flottes, Véhicules, Traceurs et Carte passent toutes par le middleware superadmin, avec tests d'accès mis à jour.
- Remplacer le message vide de la page Carte par une formulation professionnelle compatible production, sans mention de serveur GPS local ni de simulation.

- Intégrer Mapbox localement pour la page Carte : assets `public/vendor/mapbox`, token `MAPBOX_PUBLIC_TOKEN`, route `/map`, endpoint GeoJSON `/map/devices`, layers clusterisés par statut, filtres, statistiques, popups et actualisation automatique.
- Enrichir la gestion des traceurs avec une table `tracker_events`, des états live détaillés sur `devices`, la génération automatique des événements GPS (signal, mouvement, contact), une modale de détails Traceur affichant Flotte, emplacement, GSM et derniers événements, ainsi que la Mise � jour du simulateur local.
- Ajouter la section Alimentation dans les détails Traceur avec tension externe, batterie interne, niveau de batterie et contact, puis clarifier la ligne `P` comme information de parking.
- Ajouter l’historique des trajets d’un équipement sur les pages Traceurs et Carte : endpoint partagé `/trackers/{device}/trips`, choix de période, rendu timeline, résumé distance/durée et GeoJSON de tracé Mapbox.
- Remplacer les coordonnées brutes des trajets par des adresses lisibles via Mapbox Reverse Geocoding quand `positions.address` est vide, avec mémorisation de l’adresse trouvée en base.
- Ajouter sur la page Carte un bouton de popup permettant d’ouvrir les détails du traceur avec la même modale que la page Traceurs, via le partial et le script partagés `tracker-details`.
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
- Ajout de l'état visuel parking sur la carte : les traceurs en ligne, arrêtés et moteur coupé s'affichent avec un marqueur bleu `P`, compatible Google Maps et Mapbox.
- Ajout de l'état visuel arrêt moteur allumé : les traceurs en ligne, arrêtés et moteur allumé s'affichent avec un marqueur carré pour les distinguer du parking.
- Ajout de l'état visuel en mouvement : les traceurs en ligne et mobiles s'affichent avec une flèche bleue orientée par l'angle GPS et une trace récente qui disparaît progressivement derrière le véhicule.
- La page Carte n'affiche plus les véhicules par défaut : une case permet d'afficher tous les véhicules, la recherche affiche une liste de résultats, et la sélection d'un véhicule affiche uniquement ce véhicule avec sa légende.
- Ajout du panneau de filtres carte repliable, avec bouton flottant pour le réafficher comme dans une interface de tracking type Navixy.
- Amélioration de la précision des emplacements : reverse geocoding Google prioritaire avec fallback Mapbox, remplacement des adresses trop génériques et affichage adresse + coordonnées + altitude dans les détails traceur.
- Amélioration de l'historique des trajets : ajout d'un service de recalage Google Roads optionnel pour faire suivre la ligne du parcours aux routes disponibles, avec fallback automatique sur les points GPS bruts.
- Correction de la logique d'historique des trajets : un trajet est maintenant borné par les points d'arrêt/parking, afin d'afficher les lieux où le véhicule s'est arrêté ou s'est mis en parking.
- Correction de l'heure affichée dans les trajets : l'heure est convertie selon le fuseau horaire de la position GPS via Google Time Zone API, avec fallback `Africa/Kinshasa` lorsque l'API n'est pas disponible.
- La fiche détails traceur affiche maintenant la dernière adresse d'arrêt/parking connue plutôt que l'adresse courante du serveur si le véhicule est déjà reparti.
- Correction des marqueurs de la carte : flèche directionnelle bleue sans cercle pour les véhicules en mouvement, `P` bleu pour le parking et carré bleu pour un véhicule arrêté moteur allumé.
- Le label du véhicule est maintenant ancré à droite du marqueur sans déplacer visuellement la position GPS du véhicule.
- La position courante du véhicule affichée sur la carte utilise maintenant strictement les coordonnées GPS brutes du traceur, sans recalage Google Roads, afin de respecter les cas où le véhicule se trouve dans une parcelle ou hors macadam.
- Les micro-segments de trajet sans distance réelle sont filtrés pour éviter les lignes parasites à `0.00 km` dans l'historique.
- Modernisation du panneau de filtres de la page Carte : largeur réduite, rendu glass plus premium, cartes statistiques compactes, boutons en pastilles, champs affinés, focus plus propre et compatibilité dark mode.
- Ajout d'icônes Font Awesome dans le panneau de filtres Carte et extension de la carte pour occuper toute la zone disponible de la page, avec un panneau flottant au-dessus de la carte.
- Décalage vers le bas du bouton flottant d'affichage des filtres Carte pour éviter la superposition avec les contrôles natifs Google Maps.
- Correction de la trace des véhicules en mouvement : la ligne se termine maintenant sur la position GPS exacte de l'icône, même lorsque le reste du trajet est recalé par Google Roads.
- Ajout d'une animation progressive de 5 secondes sur les marqueurs Google Maps pour éviter l'effet de saut entre deux actualisations et garder le véhicule sélectionné visible pendant son déplacement.
- Correction Carte : le dernier segment de la trace suit maintenant l'icône pendant l'animation, afin que la ligne reste toujours derrière le véhicule en mouvement.
- Correction Carte : moteur coupé force le parking `P`, moteur allumé sans mouvement affiche le carré, et l'état mouvement n'est possible que si le moteur n'est pas coupé.
- Correction Carte : r�tablissement de l'affichage Google Maps apr�s une erreur de syntaxe JavaScript caus�e par un nom de variable dupliqu� dans la gestion des marqueurs anim�s.
- Correction Carte : l'animation du v�hicule suit maintenant le m�me chemin que la trace, et la trace progressive ne contient plus de points futurs devant l'ic�ne.
- Correction Carte : la popup Google Maps est plus compacte en haut et le point de statut affiche toujours le vrai statut du traceur, avec 'En ligne' en vert.
- Correction Carte : suppression de l'espace sup�rieur ajout� par l'en-t�te natif Google InfoWindow afin d'�quilibrer le padding haut/bas de la popup v�hicule.
- Correction Carte : �quilibrage du padding gauche/droite dans la popup v�hicule Google Maps, avec r�serve uniquement sur l'en-t�te pour le bouton fermer.
- Correction Carte : application compl�te du mode sombre � la popup v�hicule Google Maps, incluant fond, pointe, bouton fermer et bouton secondaire.
- Correction Details traceur : la carte Emplacement utilise maintenant le debut reel de la session parking actuelle lorsque le moteur est coupe.
- Le temps Parking, l'adresse, les coordonnees, l'altitude, la direction et le temps relatif affiches dans Emplacement sont bases sur le premier point de la sequence continue ignition=false, et non sur le dernier ping d'actualisation.
- Ajout d'un test de regression pour garantir qu'un traceur deja en parking conserve l'adresse et la direction du moment ou le vehicule s'est mis en parking.
- Correction GSM : les valeurs de signal envoyees en barres 0-5 par certains traceurs sont normalisees en pourcentage 0-100 lors de l'ingestion GPS, et l'affichage corrige aussi les anciennes valeurs deja stockees.
- Ajout de la page superadmin `/events` pour consulter tous les evenements collectes par les traceurs, avec recherche, tri AJAX et pagination 5 lignes comme les autres tableaux.
- Ajout du menu Evenements dans la sidebar et d'un lien depuis les details traceur vers l'historique complet filtre par traceur.
- Extension de la collecte des evenements GPS : le payload peut maintenant contenir `events` pour enregistrer des evenements comme porte ouverte, freinage brusque, acceleration brusque, virage brusque, remorquage, collision ou SOS.

- Clarification m�tier : les alertes et les �v�nements v�hicule sont d�sormais s�par�s. Les pertes et retours de signal restent uniquement dans les alertes �quipement/connexion.
- Correction de la collecte GPS : `signal_lost` et `signal_restored` ne sont plus cr��s dans `tracker_events`; les �v�nements v�hicule restent r�serv�s aux faits v�hicule comme moteur allum�/coup�, d�placement, arr�t, porte ouverte, freinage brusque, etc.
- Ajout du filtre `vehicleEvents()` pour masquer les anciens �v�nements techniques dans le modal d�tails traceur et sur la page `/events`, renomm�e en �v�nements v�hicules.

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
- D�placement du bouton r�duire/agrandir hors de la sidebar vers le topbar des pages principales, juste avant le titre.
- Cr�ation du partial `resources/views/partials/sidebar-toggle.blade.php` pour r�utiliser le m�me contr�le partout.
- Ajustement du CSS pour supprimer le positionnement fixe du bouton, espacer le titre et pr�server l�alignement des actions � droite.
- Mise � jour du cache-busting CSS/JS en `20260626-topbar-sidebar-toggle`.
- V�rifications : `php -l` sur les vues modifi�es, `node --check public/js/dashboard-sidebar.js`, `php artisan test --filter=dashboard`.

## 2026-06-26 - Ajustement espacement bouton sidebar topbar
- R�duction du padding gauche du topbar pour rapprocher le bouton r�duire/agrandir de la sidebar.
- Augmentation de l�espace entre le bouton et le titre de page afin que l�air soit plac� au bon endroit.
- Mise � jour du cache-busting CSS/JS en `20260626-topbar-sidebar-spacing` sur les vues superadmin.
- V�rifications : `php -l resources/views/dashboard.blade.php`, `php -l resources/views/map/index.blade.php`, `node --check public/js/dashboard-sidebar.js`, `php artisan test --filter=dashboard`.

## 2026-06-26 - Sidebar r�duite par d�faut sur tablette et mobile
- Ajustement du script sidebar pour forcer l��tat r�duit au chargement d�s que la r�solution est inf�rieure ou �gale � 1366px, m�me si un ancien �tat agrandi �tait sauvegard�.
- Conservation du bouton r�duire/agrandir : l�utilisateur peut toujours ouvrir la sidebar manuellement sur tablette ou mobile.
- Correction CSS de l��tat tablette : les textes du menu ne s�affichent que lorsque la sidebar est r�ellement agrandie, et restent masqu�s en mode r�duit.
- Mise � jour du cache-busting CSS/JS en `20260626-responsive-sidebar-default` sur les vues superadmin.
- V�rifications : `node --check public/js/dashboard-sidebar.js`, `php -l resources/views/dashboard.blade.php`, `php -l resources/views/map/index.blade.php`, `php artisan test --filter=dashboard`.

## 2026-07-07 - Dashboard corporate widgets et ordre Suivi flotte
- R�organisation visuelle du tableau de bord : le bloc `Suivi flotte / Derniers traceurs actifs` s�affiche maintenant juste apr�s les widgets KPI.
- Am�lioration corporate des widgets : accent vertical par tonalit�, fond premium plus sobre, hi�rarchie valeur/libell�/d�tail renforc�e, barre de progression plus nette et compatibilit� dark mode.
- Ajustement responsive des widgets : 3 colonnes sur desktop, 2 sur tablette et 1 sur mobile.
- Mise � jour du cache CSS dashboard vers `20260707-dashboard-corporate-widgets`.
- V�rifications : `php artisan test --filter=dashboard` et `php -l app\Http\Controllers\DashboardController.php`.

## 2026-07-08 - Ajustement taille marqueurs carte
- R�duction l�g�re des marqueurs de carte : fl�che de d�placement, ic�ne `P` parking et carr� d�arr�t moteur allum�.
- Harmonisation des tailles Google Maps et Mapbox pour garder un rendu coh�rent entre fournisseurs.
- Mise � jour du cache-busting de `map.css` et `map.js` en `20260708-map-marker-size`.
- V�rifications : `node --check public\js\map.js` et `php -l resources\views\map\index.blade.php`.

## 2026-07-08 - Correction ordre topbar pages superadmin
- Correction du bug o� le topbar/navigation descendait en bas des pages Utilisateurs et Carte apr�s la r�organisation du dashboard.
- Isolation des r�gles `flex/order` sur la page tableau de bord via la classe d�di�e `dashboard-home-main` au lieu de les appliquer globalement � `.dashboard-main`.
- Mise � jour du cache-busting `dashboard.css` en `20260708-dashboard-order-scope` sur les vues superadmin.
- V�rifications : `php -l` sur les vues dashboard/users/map et `php artisan test --filter=dashboard`.

## 2026-07-08 - Tooltip ville carte mondiale
- Normalisation du nom de ville au survol du point bleu Datamaps : premi�re lettre en majuscule, reste en minuscule.
- Correction des noms dont les lettres arrivent espac�es afin d'afficher un nom compact, par exemple Kinshasa.
- Suppression de l'espacement typographique dans le tooltip de la carte mondiale.
- V�rifications : `node --check public\js\dashboard-charts.js`, `php -l resources\views\dashboard.blade.php`.

## 2026-07-08 - Recherche ville carte depuis dashboard
- Correction de la recherche carte lorsqu'un point bleu du dashboard envoie une ville avec lettres espac�es, par exemple `K I N S H A S A`.
- Normalisation du champ recherche carte : affichage en casse propre (`Kinshasa`) et suppression de l'espacement typographique.
- Normalisation backend dans `MapController` pour accepter les villes espac�es dans le param�tre `search`.
- Normalisation des libell�s de ville c�t� dashboard avant g�n�ration du lien vers la carte.
- V�rifications : `node --check public\js\google-map.js`, `node --check public\js\dashboard-charts.js`, `php -l app\Http\Controllers\MapController.php`, `php -l app\Http\Controllers\DashboardController.php`.
## 2026-07-08 - Gestion des abonnements v�hicules
- Cr�ation de la page superadmin `Abonnements`, accessible depuis la sidebar apr�s `Utilisateurs`, pour g�rer les plans Basique, Standard et Premium.
- Ajout de la table `vehicle_subscription_plans` avec les fonctionnalit�s configurables en JSON, l��tat actif, la couleur et l�ordre d�affichage.
- Pr�enregistrement des plans par d�faut : Basique, Standard et Premium, avec la matrice de fonctionnalit�s demand�e.
- Int�gration des plans dans le formulaire V�hicules : le champ abonnement utilise d�sormais les plans actifs au lieu de valeurs cod�es en dur.
- Mise � jour des flottes pour afficher les compteurs Basique, Standard et Premium.
- Ajout des traductions fran�aises et anglaises de la page, des fonctionnalit�s et des messages.
- Ajustement du test utilisateurs pour accepter le nouveau menu `Abonnements` tout en v�rifiant que le formulaire utilisateur ne contient toujours pas de champs abonnement, grade ou statut.
- Commandes ex�cut�es : `php artisan migrate`, `php artisan db:seed --class=VehicleSubscriptionPlanSeeder`, `php artisan route:list --path=subscriptions`, `php artisan test --stop-on-failure`.
- V�rifications : `php -l` sur les contr�leurs, le mod�le, la migration et le seeder concern�s ; suite compl�te OK avec 59 tests pass�s.

## 2026-07-08 - Ajout abonnement via modal uniquement
- Retrait de la carte inline de creation d'abonnement sur la page `Abonnements`.
- Ajout d'un bouton `Nouvel abonnement` qui ouvre un modal dedie, au format des autres formulaires de l'application.
- Conservation de la creation d'abonnement avec nom, couleur, description et choix des fonctionnalites existantes.
- Suppression de la possibilite d'ajouter ou modifier les fonctionnalites depuis cette page : seules les affectations des fonctionnalites existantes aux abonnements restent disponibles.
- Ajout des traductions FR/EN du bouton, du titre modal et des actions creer/annuler.
- Nettoyage du CSS lie a l'ancienne carte inline et ajout du style modal.
- Verifications : `php -l` sur le controleur, la vue et le test ; `php artisan test --stop-on-failure` OK avec 60 tests et 471 assertions.

## 2026-07-08 - Bouton matrice fonctionnalit�s en bas
- D�placement du bouton de sauvegarde de la matrice des abonnements sous le tableau des fonctionnalit�s.
- Renommage du libell� en `Enregistrer les fonctionnalit�s` c�t� fran�ais et `Save features` c�t� anglais.
- Ajout d'un alignement bas � droite d�di� via `.subscription-matrix-actions`.
- V�rifications : `php -l resources\views\subscriptions\index.blade.php`, `php -l resources\lang\fr\subscriptions.php`, `php -l resources\lang\en\subscriptions.php`.

## 2026-07-09 - Libell� sauvegarde abonnements
- R�tablissement du libell� du bouton de matrice en `Enregistrer les abonnements` c�t� fran�ais et `Save subscriptions` c�t� anglais.
- V�rifications : `php -l resources\lang\fr\subscriptions.php`, `php -l resources\lang\en\subscriptions.php`.

## 2026-07-09 - Taille checks matrice abonnements
- R�duction des pastilles de validation de la matrice des abonnements pour un rendu plus discret.
- Ajustement de la taille d'ic�ne et de l'ombre port�e des checks actifs.
- Mise � jour du cache-busting CSS en `20260709-subscription-check-size`.
- V�rification : `php -l resources\views\subscriptions\index.blade.php`.

## 2026-07-09 - Raffinement dashboard superadmin
- R�organisation DOM du tableau de bord : le bloc `Suivi flotte / Derniers traceurs actifs` est d�sormais plac� directement apr�s les widgets KPI, avant la carte mondiale et les graphiques.
- Raffinement corporate des widgets KPI : cartes plus compactes, accents plus sobres, meilleure hi�rarchie visuelle et rendu dark mode am�lior�.
- Am�lioration de la carte mondiale Datamaps : pays plus lisibles par d�faut, hover par remplissage int�rieur sans bordure agressive, et conservation du clic sur les bulles vers la page Carte filtr�e.
- Mise � jour du cache-busting dashboard CSS/JS en `20260709-dashboard-refinement`.
- V�rifications : `php -l app\Http\Controllers\DashboardController.php`, `php -l resources\views\dashboard.blade.php`, `node --check public\js\dashboard-charts.js`.
## 2026-07-09 - Module r�gles d�alertes
- Cr�ation du module superadmin `R�gles alertes` pour configurer les r�gles de supervision inspir�es Navixy.
- Ajout de la table `alert_rules` avec s�paration claire entre alertes �quipement et �v�nements v�hicule.
- Pr�configuration des r�gles par d�faut : aucun signal, signal GSM faible, batterie faible, coupure alimentation externe, OBD d�connect�, brouillage GPS/GSM, exc�s de vitesse, ralenti prolong�, porte ouverte, freinage brusque, collision d�tect�e et SOS.
- Ajout du p�rim�tre de r�gle : tous les actifs, flotte, v�hicule ou traceur, avec seuil, unit�, canaux, planning et �tat actif.
- Cr�ation de la page `R�gles alertes` avec tableau AJAX style DataTable, recherche, tri, pagination, badges de criticit�, modal cr�ation/modification, confirmation de suppression et toast.
- Ajout des traductions fran�aises et anglaises de tous les textes visibles du module.
- Ajout du menu `R�gles alertes` dans la sidebar superadmin juste apr�s `Alertes`.
- Commandes ex�cut�es : `php artisan migrate`, `php artisan db:seed --class=AlertRuleSeeder`, `php artisan route:list --name=alert-rules`, `php artisan test --filter=dashboard`.
- V�rifications : `php -l` sur le contr�leur, le mod�le, la migration, le seeder et les vues du module.
- Tests module r�gles alertes : php artisan test --stop-on-failure OK avec 61 tests et 492 assertions.

## 2026-07-09 - T�l�m�trie traceur enrichie et supervision dashboard
- Ajout d'une migration pour stocker la t�l�m�trie avanc�e des traceurs : odom�tre, heures moteur, capteurs, IO et payload brut.
- Mise � jour du mod�le `Device` pour caster les nouvelles donn�es JSON et num�riques.
- Extension de la commande `gps:ingest-position` afin de sauvegarder codec, odom�tre, heures moteur, capteurs, IO et donn�es brutes envoy�es par le serveur GPS.
- Enrichissement de la fiche d�tail traceur : SIM, protocole, codec, satellites, odom�tre, heures moteur, nombre de capteurs, nombre d'entr�es/sorties et donn�es brutes consultables.
- Ajout de traductions FR/EN pour les nouvelles donn�es visibles de la fiche traceur.
- Ajout d'une supervision op�rationnelle sur le tableau de bord superadmin : traceurs sans signal, vitesses �lev�es, ralenti moteur et batteries faibles.
- Ajout du style responsive et dark mode pour les nouvelles cartes de supervision du dashboard.
- Commande ex�cut�e : `php artisan migrate`.
- V�rifications : `php -l` sur `DashboardController`, `Device`, `routes/console.php` et fichiers de langue dashboard.
- Tests : `php artisan test --stop-on-failure` OK avec 61 tests et 492 assertions.
## 2026-07-09 - Module rapports superadmin
- Cr�ation de la page superadmin `Rapports`, accessible depuis la sidebar, pour g�n�rer des rapports op�rationnels.
- Ajout des types de rapports : positions GPS, �v�nements v�hicules, alertes �quipement et synth�se des flottes.
- Ajout de filtres par p�riode, flotte, v�hicule, traceur et recherche texte, avec tableau AJAX, tri et pagination de 5 lignes.
- Ajout des exports CSV et impression/PDF navigateur pour les rapports filtr�s.
- Ajout de la planification de rapports r�currents avec fr�quence quotidienne, hebdomadaire ou mensuelle, destinataires et format.
- Cr�ation de la table `scheduled_reports` et du mod�le `ScheduledReport`.
- Ajout des traductions fran�aises et anglaises du module, du modal de planification, des colonnes et des messages.
- Ajout des styles d�di�s avec support responsive et dark mode, en conservant le format visuel des autres pages superadmin.
- Commandes ex�cut�es : `php artisan migrate`, `php artisan route:list --name=reports`, `php artisan test tests/Feature/ReportsTest.php`, `php artisan test --stop-on-failure`.
- V�rifications : `php -l` sur `ReportController`, `ScheduledReport`, la migration et le test.
- Tests : `php artisan test --stop-on-failure` OK avec 65 tests et 509 assertions.

## 2026-07-09 - Rapports PDF Dompdf, filtres et encodage traceur
- Installation de `barryvdh/laravel-dompdf` pour g�n�rer les exports PDF r�els des rapports.
- Mise � jour de l'export `format=print` pour t�l�charger un PDF A4 paysage via Dompdf au lieu d'une page HTML imprimable.
- Ajustement du formulaire de filtres Rapports : espacement entre recherche et boutons, bouton Filtrer au bleu du th�me, exports CSV/PDF mieux s�par�s et responsive.
- Correction des libell�s FR double-encod�s dans la fiche traceur : Odom�tre, entr�es/sorties, Donn�es brutes.
- Test renforc� : v�rification que l'export PDF r�pond en `application/pdf`.
- V�rifications : `php -l app/Http/Controllers/ReportController.php`, `php -l resources/lang/fr/trackers.php`, `php artisan test tests/Feature/ReportsTest.php --stop-on-failure`.

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
- Les rapports PDF Dompdf sont téléchargés directement au lieu d'être ouverts en lecture dans le navigateur.
- Le zoom automatique de sélection d'un véhicule sur la carte Google a été réduit pour conserver davantage de contexte autour du véhicule.
- La fiche détail traceur ne montre plus les données brutes et ajoute un bloc OBD/CAN bus avec odomètre, heures moteur, régime moteur, carburant, température et protocole lorsque ces données sont disponibles.

## 2026-07-10 - Codec traceur, OBD/CAN et diagnostics
- Réutilisation du champ existant `devices.codec` pour stocker le codec réel reçu par les traceurs, sans créer de champ doublon.
- Ajout d'une migration pour enrichir la table `devices` avec les métriques OBD/CAN : régime moteur, vitesse OBD, papillon, température moteur, tension module, charge moteur, distance avec défaut, erreurs, distance depuis réinitialisation, carburant CAN, kilométrage CAN et dates de mise à jour.
- Extension du modèle `Device` avec les nouveaux champs remplissables et les casts adaptés.
- Extension de la commande `gps:ingest-position` pour accepter les blocs `obd`, `can`, `io`, `sensors`, `raw`, le codec, l'odomètre, les heures moteur et l'adresse.
- Ajout d'une résolution d'adresse côté ingestion via le service de reverse geocoding lorsque le serveur GPS n'envoie pas directement l'adresse.
- Enrichissement de la fiche détail traceur : OBD/CAN bus, diagnostic traceur, satellites, odomètre, heures moteur, entrées/sorties, capteurs et dates de fraîcheur.
- Amélioration de l'affichage des rapports : espacement clair entre le nom du traceur et son numéro de série/IMEI.
- Ajout des traductions FR/EN pour les nouvelles métriques OBD/CAN visibles.
- Commandes exécutées : `php artisan migrate`, `php artisan test --filter=ReportsTest --stop-on-failure`.
- Vérifications : `php -l` sur `Device`, `routes/console.php`, la migration, les traductions et les vues rapports/détails traceur.

## 2026-07-10 - Carte : trace de déplacement courte et zoom de sélection
- Réduction du tracé de déplacement aux points récents, continus et bornés en distance pour éviter les longues lignes parasites.
- Rapprochement léger du zoom lorsqu’un véhicule est sélectionné depuis la recherche de la carte.
- Vérifications : `php -l app\Http\Controllers\MapController.php` et `node --check public\js\google-map.js`.

## 2026-07-10 - Zoom carte et extraction OBD/CAN robuste
- Rapprochement du zoom lors de la sélection d'un véhicule sur la carte Google afin de mieux cadrer le traceur sans perdre le contexte de rue.
- Allègement visuel de la queue de trace derrière le véhicule en mouvement avec une opacité plus faible et une ligne légèrement plus fine.
- Suppression des doublons Odomètre et Heures moteur dans le bloc Diagnostic traceur : ces informations restent centralisées dans le bloc OBD / CAN bus.
- Amélioration de la lecture OBD/CAN dans la fiche traceur : les valeurs peuvent désormais être relues depuis les blocs `obd`, `can`, `io`, `sensors`, `payload` et les clés numériques reçues dans le payload brut.
- Extension de `gps:ingest-position` pour extraire et normaliser automatiquement régime moteur, vitesse OBD, papillon, température moteur, tension module, charge moteur, distances défaut/réinitialisation, erreurs, carburant et kilométrage même lorsque le serveur GPS les envoie sous forme IO brute.
- Correction du formatage numérique pour conserver les valeurs `0` et lire correctement les valeurs avec espaces ou virgules comme `1 137,00 km`.
- Vérifications : `php -l routes/console.php`, `php -l app/Models/Device.php`, `node --check public/js/google-map.js`.

## 2026-07-10 - S�paration Diagnostic traceur et OBD/CAN Navixy
- Ajout du champ `devices.last_obd_runtime_seconds` pour stocker le moment d�ex�cution OBD sans le confondre avec les heures moteur.
- Diagnostic traceur recentr� sur l��tat du bo�tier : satellites, protocole, odom�tre, heures moteur, entr�es/sorties et capteurs.
- Bloc OBD / CAN bus recentr� sur les donn�es v�hicule : moment d�ex�cution, TR/MIN, vitesse OBD, papillon, temp�rature moteur, tension module, valeur absolue de charge, carburant, d�fauts, erreurs et kilom�trage depuis r�initialisation.
- Correction de l�extraction de la valeur absolue de charge : lecture prioritaire de l�IO Teltonika `52`, avec fallback `31`.
- Correction du fallback `engine_seconds` : il est trait� comme moment d�ex�cution OBD et non comme heures moteur.
- V�rification du d�codeur GPS production et red�marrage de `gps-tcp.service` apr�s correction du mapping `engine_load_percent`.
- Commande ex�cut�e : `php artisan migrate`.
- V�rifications : `php -l routes/console.php`, `php -l resources/views/trackers/partials/details.blade.php`, `php -l app/Models/Device.php`, `php -l database/migrations/2026_07_10_180000_add_obd_runtime_seconds_to_devices_table.php`.

## 2026-07-13 - Historique détaillé des trajets et replay cartographique
- Refonte du modal `Trajets` avec une présentation chronologique plus détaillée : adresses de départ et d’arrivée, horaires, distance, durée, nombre de points GPS, vitesse moyenne et vitesse maximale.
- Ajout de la sélection individuelle des trajets et de la mise en évidence du parcours choisi sur la carte Google.
- Ajout d’un replay animé du trajet avec lecture, pause, remise à zéro, navigation dans la progression et vitesses `x1`, `x3`, `x10`, `x30`, `x100` et `x300`.
- Ajout d’une couleur personnalisable pour chaque parcours, appliquée immédiatement à la trace cartographique.
- Enrichissement du GeoJSON des trajets avec identifiant stable, couleur, nombre de points et statistiques de vitesse.
- Ajout d’un mode replay latéral sur grand écran pour conserver simultanément la carte et la chronologie, avec retour automatique au modal centré sur tablette et mobile.
- Ajout du support responsive et dark mode pour le sélecteur de période, la chronologie, la barre de replay, les cartes de trajets et les totaux.
- Ajout des traductions françaises et anglaises de tous les nouveaux contrôles et indicateurs.
- Renforcement du test fonctionnel des trajets pour vérifier les métadonnées GeoJSON, les statistiques, la couleur, la barre de replay et la vitesse `x300`.
- Vérifications : `php -l app/Services/DeviceTripService.php`, `node --check public/js/tracker-trips.js`, `node --check public/js/google-map.js`, `git diff --check`.
- Tests : `php artisan test --compact` OK avec 65 tests et 520 assertions.
- Interface locale ciblée : `http://127.0.0.1:8000`.

## 2026-07-14 - Panneau latéral des trajets
- Fermeture automatique de la fiche véhicule et de la fenêtre cartographique avant l’ouverture des trajets.
- Transformation du grand modal centré en panneau compact aligné à gauche afin de conserver la carte visible pour la prévisualisation et le replay.
- Réorganisation des périodes, des commandes de lecture, de la vitesse, de la progression et des cartes de trajet pour éviter les chevauchements et les textes tronqués.
- Ajout d’un comportement responsive : panneau latéral sur grand écran et modal adapté sur tablette et mobile.
- Compatibilité maintenue avec Google Maps et Mapbox pour la fermeture de la fenêtre source.
- Vérifications : compilation des vues Blade, feuille CSS servie en HTTP 200 sur `127.0.0.1:8000` et `php artisan test` OK avec 65 tests et 520 assertions.

## 2026-07-14 - Commandes de replay iconographiques
- Simplification des commandes de lecture du trajet : lecture/pause, reprise et effacement sont désormais représentés uniquement par leurs icônes.
- Conservation du texte uniquement dans le sélecteur de vitesse (`x1`, `x3`, `x10`, `x30`, `x100`, `x300`).
- Maintien des intitulés accessibles avec `aria-label`, infobulles et libellés masqués pour les lecteurs d’écran.
- Suppression du chevron décoratif présent dans chaque carte de trajet, la carte entière restant sélectionnable.
- Harmonisation du format compact des commandes sur ordinateur, tablette et mobile.

## 2026-07-16 - Organisation des flottes, conducteurs et départements
- Réorganisation de la navigation superadmin : le menu `Flottes` devient un groupe extensible partagé contenant `Flottes`, `Véhicules`, `Traceurs`, `Conducteurs` et `Départements`, sans dupliquer les éléments dans la sidebar.
- Ajout du module `Départements` avec rattachement obligatoire à une flotte, code, description, statut, recherche AJAX, tri et pagination de 5 lignes.
- Ajout du module `Conducteurs` avec photo, identité, flotte, département, matricule employé, badge RFID/iButton/NFC, coordonnées, adresse, mots-clés, permis de conduire et statut.
- Remplacement de l'affectation permanente à un traceur par une sélection multiple de `Véhicules autorisés`, limitée aux véhicules de la flotte du conducteur.
- Normalisation et unicité globale des identifiants RFID/iButton/NFC afin de préparer l'identification automatique des conducteurs par le serveur GPS.
- Ajout des tables `departments`, `drivers`, `driver_identifiers`, `driver_vehicle` et `driver_sessions` pour préparer le suivi des prises de véhicule, changements de conducteur et fins de session.
- Ajout des relations Eloquent entre flottes, départements, conducteurs, véhicules, traceurs et sessions conducteur.
- Ajout des contrôleurs, Form Requests et validations d'isolation : un département et les véhicules autorisés doivent appartenir à la flotte sélectionnée.
- Ajout des formulaires dans des modals conformes au format global de l'application, avec erreurs sous les champs, états de chargement, toasts, confirmations de suppression, responsive et mode sombre.
- Ajout des traductions françaises et anglaises de tous les textes visibles des deux modules et de la navigation.
- Correction du comportement du groupe `Flottes` lors de l'agrandissement de la sidebar depuis son état réduit.
- Commande exécutée : `php artisan migrate --no-interaction`.
- Vérifications : compilation des vues Blade avec `php artisan view:cache`, réponse HTTP locale sur `http://127.0.0.1:8000`, contrôle de syntaxe du test et contrôle d'accès invité.
- Tests ciblés : `php artisan test tests\\Feature\\FleetOrganizationTest.php --stop-on-failure` OK avec 6 tests et 33 assertions.
- Suite complète : `php artisan test --stop-on-failure` OK avec 71 tests et 553 assertions.

## 2026-07-16 - Identification des conducteurs par badge GPS
- Intégration de l'identification conducteur au flux réel `gps:ingest-position` pour les badges RFID, iButton et NFC transmis à la racine ou dans les données IO du traceur.
- Normalisation automatique des identifiants reçus afin de reconnaître un même badge malgré les espaces, tirets, deux-points ou différences de casse.
- Ouverture d'une session de conduite uniquement lorsque le conducteur est actif, appartient à la flotte du véhicule et possède une autorisation explicite sur ce véhicule.
- Fermeture automatique de la session lors de la coupure du contact, d'un changement de conducteur ou de la présentation d'un badge refusé.
- Enregistrement du conducteur, du badge, du véhicule, du traceur, des positions de début et de fin, des horaires et du motif de fermeture dans `driver_sessions`.
- Correction de la lecture du véhicule après la génération des événements GPS : le service recharge désormais la relation complète afin de ne pas réutiliser une relation partielle sans `fleet_id`.
- Ajout de tests fonctionnels couvrant l'ouverture et la fermeture d'une session autorisée ainsi que le rejet d'un badge non autorisé.
- Tests ciblés : `php artisan test tests\\Feature\\FleetOrganizationTest.php --stop-on-failure` OK avec 8 tests et 47 assertions.
- Suite complète : `php artisan test --stop-on-failure` OK avec 73 tests et 567 assertions.

## 2026-07-17 - Finalisation du formulaire conducteur
- Complétion du formulaire conducteur avec le numéro de sécurité sociale, les coordonnées, l’adresse, le rayon d’emplacement et toutes les informations du permis de conduire.
- Correction du modal afin que son contenu reste défilable tout en conservant les boutons `Annuler` et `Enregistrer` visibles dans un pied de modal fixe.
- Ajout de la prise en charge complète des nouveaux champs dans le modèle, les validations, la création, la modification et les données d’édition du modal.
- Correction des traductions françaises et anglaises du module conducteurs, notamment des accents et libellés visibles.
- Migration exécutée : `php artisan migrate --no-interaction`.
- Tests ciblés : `php artisan test --compact tests/Feature/FleetOrganizationTest.php` OK avec 8 tests et 49 assertions.
- Suite complète : `php artisan test --compact` OK avec 73 tests et 569 assertions.

## 2026-07-17 - Adresses propres à chaque trajet
- Correction de l’historique des trajets afin de géocoder séparément le point de départ et le point d’arrivée à partir de leurs coordonnées GPS réelles.
- Une ancienne adresse conservée dans les données du traceur n’est désormais utilisée qu’en solution de repli lorsque le géocodage est indisponible.
- Ajout d’un test de régression vérifiant que deux limites de trajet ayant des coordonnées différentes affichent bien deux adresses différentes.
- Tests ciblés : `php artisan test --compact --filter="tracker trips"` OK avec 5 tests et 39 assertions.

## 2026-07-17 - Sélecteur des véhicules autorisés conducteur
- Remplacement du simple texte d'aide `Véhicules autorisés` par un vrai sélecteur multi-véhicules dans le modal conducteur.
- Le sélecteur affiche uniquement les véhicules de la flotte choisie, avec recherche interne, compteur de sélection et états vides traduits.
- Harmonisation visuelle du sélecteur avec le format des modals de l'application, y compris le mode sombre et le responsive.

## 2026-07-17 - Largeur du modal conducteur
- Réduction de la largeur maximale du modal conducteur pour un affichage plus compact.
- Conservation du scroll interne et des actions fixes `Annuler` / `Créer` ou `Enregistrer`.

## 2026-07-17 - Uniformisation des modals
- Alignement des modals conducteur, v�hicule et d�partement sur la largeur standard du modal utilisateur (660px).
- Conservation du comportement responsive pleine largeur sur mobile.

## 2026-07-19 - UID conducteur traceur et fiche conducteur
- Ajout du champ `devices.last_driver_identifier_uid` pour memoriser le dernier UID RFID/iButton/NFC recu par le traceur.
- Reutilisation de la normalisation du service de sessions conducteur afin de traiter les UID recus a la racine du payload ou dans les blocs IO.
- Mise a jour de `gps:ingest-position` pour enregistrer l'UID recu sur le traceur, y compris lorsqu'il ne correspond pas a une session conducteur autorisee.
- Ajout de l'UID conducteur dans le bloc Diagnostic traceur du modal details.
- Ajout d'une carte `Conducteur` dans le modal details traceur : le conducteur est affiche uniquement si l'UID recu correspond a un identifiant actif d'un conducteur autorise sur le vehicule lie au traceur ; sinon un etat `Aucun conducteur identifie` est affiche.
- Tests cibles : `php artisan test --compact --filter="tracker details"` OK avec 3 tests et 28 assertions.
- Tests conducteur : `php artisan test --compact tests\Feature\FleetOrganizationTest.php --stop-on-failure` OK avec 8 tests et 51 assertions.
- Suite complete : `php artisan test --compact --stop-on-failure` OK avec 74 tests et 579 assertions.

## 2026-07-19 - Correction liste des trajets repetes
- Correction de la segmentation des trajets afin qu'un arret court intermediaire ne coupe plus le parcours en plusieurs trajets repetes dans la liste.
- Les points arretes courts restent inclus dans le meme parcours ; un trajet n'est ferme qu'apres un arret durable.
- Ajout d'un test de regression couvrant un deplacement continu avec arret court puis reprise.
- Tests cibles : `php artisan test --compact --filter="tracker trips"` OK avec 6 tests et 46 assertions.
- Suite complete : `php artisan test --compact --stop-on-failure` OK avec 75 tests et 586 assertions.

## 2026-07-19 - Deconnexion automatique apres inactivite

- La duree de session Laravel est fixee a 30 minutes.
- L'interface deconnecte automatiquement l'utilisateur apres 30 minutes sans clic, clavier, defilement ou interaction tactile.
- L'activite est synchronisee entre les onglets ouverts et les requetes automatiques de la carte ne prolongent pas la connexion.
- Apres expiration, l'utilisateur est redirige vers la connexion avec un message explicatif.
- Tests ajoutes dans `tests/Feature/InactiveSessionTest.php` ; suite complete validee avec 77 tests et 595 assertions.

### Complement - fermeture du navigateur

- Le cookie de session est maintenant configure avec `SESSION_EXPIRE_ON_CLOSE=true`.
- L'option « Se souvenir de moi » a ete retiree de la connexion.
- Fortify ignore egalement toute tentative d'envoyer manuellement `remember=1`, afin qu'aucun cookie d'authentification persistant ne soit cree.
- Tests valides : 78 tests et 601 assertions.

## 2026-07-19 - Recherche d'adresse et alerte de sortie de zone conducteur

- Le champ adresse du conducteur recherche des adresses reelles via le fournisseur cartographique configure (Google en priorite, Mapbox en repli).
- La selection d'une adresse enregistre egalement `location_latitude` et `location_longitude` sur le conducteur.
- La migration `2026_07_19_013501_add_geofence_fields_to_drivers_and_driver_sessions_tables` ajoute les coordonnees et l'etat de geofence des sessions de conduite.
- Chaque position GPS d'un conducteur identifie est comparee au rayon configure autour de son adresse.
- Une alerte temps reel `geofence_exit` est creee uniquement lors du passage hors zone ; les positions suivantes ne la repetent pas.
- Le retour dans le rayon rearme l'alerte pour une sortie ulterieure.
- Migration locale appliquee et suite complete validee : 80 tests, 621 assertions.
