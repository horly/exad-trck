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

## 2026-07-19 - Garages et gestion des entretiens

- Ajout des pages `Garages` et `Entretien` dans le sous-menu Flottes.
- Les garages peuvent desservir plusieurs flottes et conserver leur type, leurs contacts, leur adresse geocodee, leurs specialites et leur statut.
- Ajout de la planification des entretiens preventifs ou correctifs par date, kilometrage et heures moteur, avec rappels et repetition automatique.
- Ajout de la cloture des interventions, de l'historique immutable, du suivi des couts et des pieces jointes.
- Evaluation des echeances toutes les 15 minutes et apres chaque telemetrie GPS ; creation d'alertes temps reel non dupliquees lorsqu'un entretien approche ou est en retard.
- Generalisation de la recherche d'adresse pour les conducteurs et les garages.
- Migrations locales appliquees : `garages`, `fleet_garage`, `maintenance_plans`, `maintenance_records` et `maintenance_documents`.
- Validation complete : 85 tests, 652 assertions. Aucun deploiement distant effectue a cette etape.

### Complement - uniformisation des modals d'entretien

- Les modals Garage, Planification et Cloture utilisent maintenant la largeur formulaire standard de 660 px.
- Ajout d'une icone contextuelle bleue a cote du titre de chaque nouveau modal.
- Le formulaire de planification adopte une presentation corporate en sections, avec corps defilable, actions fixes, interrupteur de recurrence et unites integrees aux champs.
- Verification ciblee : 5 tests, 31 assertions.

### Correction - garages globaux

- Suppression de la grille `Flottes desservies` du formulaire Garage.
- Les garages sont maintenant globaux et disponibles pour tous les vehicules, sans affectation par flotte.
- Suppression de la table pivot locale `fleet_garage`, devenue inutile.
- Verification ciblee : 15 tests, 107 assertions.

### Complement - icones des formulaires modaux

- Ajout d'une icone contextuelle a cote du titre des formulaires modaux Flotte, Vehicule, Traceur, Departement, Utilisateur, Plan d'abonnement, Regle d'alerte et Rapport planifie.
- Les modals Conducteur, Garage, Planification et Cloture d'entretien conservent leurs icones existantes.
- Ajout d'un test de regression couvrant les 11 modules de gestion concernes.
- Verification ciblee : 16 tests, 129 assertions.

### Complement - widgets KPI entretien

- Refonte corporate des widgets Plans actifs, A traiter, Cout planifie et Cout realise.
- Ajout d'une hierarchie KPI stable, d'icones encadrees et d'accents semantiques bleu, rouge, ambre et vert.
- Prise en charge du responsive et du mode sombre.
- Verification ciblee : 5 tests, 34 assertions.

### Complement - recherche de vehicules dans les modals

- Les champs Vehicule des formulaires Nouveau traceur et Planifier une revision utilisent maintenant un selecteur recherchable.
- Recherche instantanee par nom, immatriculation et flotte, avec liste scrollable et etat vide traduit.
- Conservation du select Laravel natif pour la validation, l'envoi et les regles de disponibilite des vehicules.
- Synchronisation assuree en creation, modification et reinitialisation des formulaires.
- Verification ciblee : 50 tests, 452 assertions.

### Complement - composition des widgets entretien

- Adaptation des KPI entretien au style de reference fourni : icone en haut, valeur dominante puis libelle.
- Suppression de la barre d'accent superieure et augmentation de l'espacement vertical.
- Conservation des couleurs semantiques et du responsive 4, 2 puis 1 colonne.
- Verification ciblee : 6 tests, 42 assertions.

### 2026-07-19 - Listes issues de la base recherchables dans tous les formulaires
- Nouvelle convention UI : tout champ de selection alimente par la base de donnees doit proposer une recherche, y compris dans les filtres applicatifs.
- Le composant partage `public/js/searchable-select.js` enrichit automatiquement les `<select data-searchable-database>` sans modifier leur fonctionnement natif ni leur soumission Laravel.
- Champs couverts : flottes, departements, vehicules, traceurs, garages, administrateurs assignables et abonnements, dans les formulaires Vehicule, Conducteur, Departement, Flotte, Traceur, Entretien, Regles d'alerte, Rapports et les filtres de la carte.
- Les listes fermees qui ne viennent pas de la base (statut, type, protocole, severite, periode, etc.) restent des selecteurs standards.
- La hauteur du selecteur recherchable est fixee a 40 px, identique aux autres champs des modales.
- Verification : compilation Blade reussie et suite complete `php artisan test` verte avec 88 tests et 726 assertions.

### 2026-07-19 - Politique de vitesse par vehicule
- Ajout d'une section corporate `Politique de vitesse` dans la modale de creation et modification d'un vehicule.
- Le champ `Vitesse maximale` est optionnel, exprime en km/h et limite a une valeur comprise entre 1 et 300.
- La valeur est synchronisee avec une regle `overspeed` dediee et liee au vehicule ; vider le champ supprime cette politique.
- Evaluation branchee directement dans l'ingestion GPS apres l'enregistrement de chaque position.
- Regle stricte demandee : une alerte est creee immediatement lorsque la vitesse recue est strictement superieure a la limite, sans tolerance ni duree de confirmation.
- Protection anti-duplication par episode : une seule alerte pendant un depassement continu, rearmement des que la vitesse revient a la limite ou en dessous.
- Ajout d'un etat persistant par regle et traceur dans `alert_rule_states` afin de conserver ce comportement entre les paquets GPS.
- Migrations locales appliquees : `2026_07_19_100000_add_speed_policy_rule_id_to_vehicles_table` et `2026_07_19_100100_create_alert_rule_states_table`.
- Verification : compilation Blade reussie et suite complete `php artisan test` verte avec 90 tests et 757 assertions.

### 2026-07-19 - Console SSH securisee dans les logs serveur
- La page Logs serveur propose maintenant deux onglets : `Logs`, qui conserve l'ecran existant, et `Console`, qui ouvre un terminal SSH interactif.
- L'acces reste reserve aux superadministrateurs et exige la saisie du compte Linux autorise `exad-tracking` ainsi que de son mot de passe.
- Laravel emet un ticket HMAC a usage unique valable 30 secondes ; le mot de passe Linux est transmis directement par WebSocket a la passerelle SSH et n'est jamais envoye, stocke ou journalise par Laravel.
- La passerelle Node.js est liee a `127.0.0.1`, verifie l'origine Web, l'empreinte de la cle hote SSH, le nom d'utilisateur autorise, l'expiration et le rejeu du ticket.
- La session SSH et son pseudo-terminal sont fermes lors de la deconnexion, du passage a l'onglet Logs, d'un changement de page, de la fermeture du navigateur, d'une perte WebSocket ou apres 30 minutes d'inactivite.
- Ajout des exemples de configuration systemd, Apache et environnement dans `deployment/` et `server-console-gateway/`. La fonctionnalite reste desactivee tant que `SERVER_CONSOLE_ENABLED` et les secrets ne sont pas configures sur le VPS.
- Verification locale : compilation Vite reussie, 3 tests Node.js passes et suite Laravel complete verte avec 93 tests et 778 assertions. Aucun deploiement distant effectue a cette etape.

#### Deploiement production
- Fonctionnalite deployee et activee sur le VPS apres confirmation explicite du risque lie a une console Web utilisant un compte Linux membre de `sudo`.
- Service `exad-server-console.service` actif et active au demarrage ; passerelle liee uniquement a `127.0.0.1:5091`.
- Proxy WebSocket Apache active sur `/server-console/socket` et configuration Laravel production activee avec un secret genere uniquement sur le serveur.
- Controles production reussis : login et assets en HTTP 200, negotiation WebSocket en HTTP 101.

#### Correction de l'authentification SSH Web
- Correction du refus avant authentification provoque par la difference de remplissage Base64 entre l'empreinte SHA-256 calculee par Node.js et celle affichee par OpenSSH.
- La passerelle force maintenant l'algorithme `ssh-ed25519`, normalise uniquement le suffixe Base64 `=` et conserve la comparaison stricte avec l'empreinte epinglee.
- Ajout d'un journal technique du niveau d'erreur SSH sans mot de passe ni saisie terminal.
- Validation production de bout en bout reussie : ticket, WebSocket, SSH, pseudo-terminal, commande `whoami`, utilisateur `exad-tracking`, puis fermeture immediate.

### 2026-07-19 - Presse-papiers, plein ecran et cadrage de la console SSH
- Le clic droit dans le terminal colle le contenu du presse-papiers directement dans la session SSH active.
- Lorsqu'un texte du terminal est selectionne, le clic droit copie cette selection dans le presse-papiers au lieu de la coller.
- Ajout d'un bouton iconographique pour basculer le panneau Console en plein ecran et revenir a l'affichage normal.
- Xterm recalcule ses colonnes et lignes lors du changement de mode, conserve le focus et revient sur la derniere sortie.
- Augmentation de l'espace inferieur du terminal afin que la derniere ligne et le curseur restent entierement visibles.
- Assets recompiles avec Vite ; verification locale complete : 93 tests Laravel passes avec 781 assertions et 3 tests Node.js passes.

### 2026-07-19 - Refonte corporate de la connexion et option Se souvenir de moi
- Refonte de la page de connexion en ecran d'acces professionnel : scene flotte plein cadre a gauche et panneau d'authentification blanc pleine hauteur a droite.
- Nouveau visuel original `public/images/login-fleet-corporate.png`, genere pour EXAD Tracking : flotte realiste sur un axe metropolitain, reperes GPS discrets et zone calme pour le formulaire.
- Simplification du contenu promotionnel, hierarchie typographique plus nette, champs de 48 px, focus accessible, bouton de connexion renforce et indicateurs de securite sobres.
- Responsive verifie en Chrome : rendu bureau 1920x900 et viewport mobile reel de 390 px sans debordement horizontal.
- Ajout de la case `Se souvenir de moi` au formulaire Fortify. Sans selection, aucun cookie persistant n'est cree ; avec selection, Laravel emet le cookie de rappel.
- La protection existante reste active : une session ouverte est toujours deconnectee apres 30 minutes d'inactivite. Le rappel permet surtout de retrouver la connexion apres fermeture du navigateur avant cette expiration.
- Tests mis a jour dans `InactiveSessionTest` ; suite complete verte avec 93 tests et 786 assertions.

#### Ajustement local de la direction visuelle
- Conservation stricte de la police monospace historique et de tous les textes initiaux de la page de connexion.
- Retrait des nouveaux libelles editoriaux ajoutes lors de la premiere refonte et restauration des quatre benefices ainsi que des quatre indicateurs d'origine.
- Remplacement du grand fond translucide par une colonne editoriale opaque, continue et sans effet de carte ; le visuel de flotte dispose maintenant de sa propre zone sans passer derriere les textes.
- Reorganisation des quatre indicateurs d'origine dans une grille 2 x 2 stable et rapprochement de l'ensemble fonctionnel pour supprimer les coupures et chevauchements visuels.
- Conservation de la nouvelle photographie, du panneau d'authentification corporate et de l'option `Se souvenir de moi`.
- Verification visuelle locale aux formats bureau 1920x900 et mobile 500x900. Cette correction n'a pas ete deployee sur le VPS.
- Verification technique : compilation Blade reussie et suite complete verte avec 93 tests et 786 assertions.

#### Restauration locale du design d'origine
- Abandon integral de la nouvelle direction visuelle apres validation utilisateur.
- Restauration de l'ancien fond mondial avec flotte, de la carte de connexion, des dimensions, espacements, textes et de la police d'origine.
- Suppression locale de l'image generee `login-fleet-corporate.png` et de tous les styles propres a la refonte.
- Conservation exclusive de la fonctionnalite `Se souvenir de moi`, integree sobrement dans le formulaire historique.
- Verification visuelle locale au format bureau 1920x900. Aucun deploiement VPS effectue.
- Verification technique : compilation Blade reussie et suite complete verte avec 93 tests et 786 assertions.

#### Deploiement de la restauration d'origine
- Restauration deployee sur le VPS apres autorisation explicite : ancien fond, carte, typographie, contenus et dimensions de connexion remis en production.
- L'option `Se souvenir de moi` reste la seule evolution fonctionnelle conservee sur cet ecran.
- Sauvegarde distante creee avant remplacement : `/tmp/exadtracking-before-login-restore-20260719-140916.tar.gz`.
- Caches Laravel reconstruits et controles publics de la page et du CSS reussis en HTTP 200.

### 2026-07-19 - Nouvelle direction bleu nuit de la page de connexion
- Nouvelle composition locale conforme a la reference utilisateur : panneau flotte bleu nuit a gauche et espace d'authentification clair sans carte flottante a droite.
- Conservation de la police monospace, de tous les textes historiques et de l'option `Se souvenir de moi`.
- Ajout du visuel original `public/images/login-fleet-night.png` : carte mondiale connectee, flotte realiste et zone sombre reservee au contenu HTML.
- Les quatre benefices restent affiches avec des icones cyan et les quatre indicateurs sont alignes dans une bande sombre stable en bas du panneau.
- Le formulaire utilise des champs de 46 px et un bouton bleu corporate, tandis que le pied de page est limite au panneau droit.
- Responsive verifie localement aux formats 1920x900, 1257x710 et mobile 500x900.
- Aucun deploiement VPS effectue pour cette nouvelle direction visuelle.
- Verification technique : compilation Blade reussie et suite complete verte avec 93 tests et 788 assertions.

#### Ajustement des proportions de connexion
- Passage de la composition bureau a `62%` pour le visuel flotte et `38%` pour la zone d'authentification afin de mieux exposer l'arriere-plan.
- Reduction du formulaire a 390 px maximum, des champs a 42 px et du bouton de connexion a 44 px.
- Remplacement du cadenas encadre de l'indicateur de securite par une icone bouclier-cadenas bleue, plus nette et sans boite decorative.
- Controle visuel local reussi aux formats 1257x710 et mobile 500x900. Aucun deploiement VPS effectue.

#### Recomposition du visuel flotte
- Le fond `login-fleet-night.png` a ete regenere avec une composition reculee : les six vehicules disposent maintenant de marges et restent entierement visibles.
- La flotte et la carte connectee sont concentrees a droite, tandis que les textes utilisent une zone bleu nuit uniforme et sans details concurrents a gauche.
- Retour a un affichage CSS plein cadre `cover`, sans reduction artificielle, raccord de couleur ni bande visible.
- Cadrage valide localement aux formats 1920x918, 1257x710 et mobile 500x900. Aucun deploiement VPS effectue.

#### Deploiement de la connexion bleu nuit
- Nouvelle direction visuelle deployee sur le VPS apres autorisation explicite, avec le fond flotte recompose et la repartition bureau `62/38`.
- Sauvegarde distante creee avant remplacement : `/tmp/exadtracking-before-login-night-20260719-150609.tar.gz`.
- Caches Laravel reconstruits ; page, CSS et image controles publiquement en HTTP 200.
- Apache actif, maintenance inactive et fonctionnalite `Se souvenir de moi` conservee.

### 2026-07-19 - Validation contextuelle des formulaires modaux
- Regle d'interface retenue : les erreurs de validation doivent toujours apparaitre sur le champ concerne, avec son etat invalide et un message immediatement dessous ; aucun bandeau global ne doit remplacer cette indication.
- Application de la regle aux formulaires `Planifier une revision`, `Cloturer une revision` et `Garage`, les dernieres modales d'entretien qui n'affichaient pas encore toutes leurs erreurs sur les champs.
- Les modales concernees se rouvrent automatiquement apres un echec, conservent les valeurs saisies et retirent les anciens etats invalides lors d'une nouvelle creation ou edition.
- L'erreur metier exigeant au moins une echeance preventive est maintenant affichee sous le champ `Prochaine echeance` par date, auquel elle est rattachee par le serveur.
- Les erreurs de cloture utilisent un sac dedie afin de rouvrir la modale de cloture, sans interagir avec celle de planification.
- Tests de regression ajoutes pour l'entretien et les garages ; suite complete verte avec 96 tests et 813 assertions.
- Deploiement VPS effectue apres autorisation explicite, avec sauvegarde ciblee prealable dans `/tmp/exadtracking-before-field-validation-20260719-174701.tar.gz`.
- Caches Laravel reconstruits, application remise en ligne et controle public reussi en HTTP 200.

### 2026-07-19 - Connexion sans visuel sur tablette et mobile
- Le panneau bleu nuit et son image de flotte sont maintenant reserves aux affichages bureau a partir de `992 px`.
- De la tablette au mobile, seule la zone de connexion claire occupe la page complete ; aucun espace n'est conserve pour le visuel masque.
- Le prechargement de `login-fleet-night.png` est lui aussi limite aux ecrans d'au moins `992 px` afin d'eviter un telechargement inutile sur les terminaux mobiles.
- Le formulaire, le selecteur de langue et le pied de page conservent leur comportement responsive existant.
- Test de regression ajoute ; suite complete verte avec 97 tests et 818 assertions.
- Deploiement VPS effectue apres autorisation explicite ; sauvegarde ciblee creee dans `/tmp/exadtracking-before-login-responsive-20260719-193124.tar.gz`.
- Caches Laravel reconstruits et controles publics reussis en HTTP 200 pour la page de connexion et le CSS responsive.

### 2026-07-19 - Page Profil, photo recadree et authentification a deux facteurs
- Ajout d'une page `Mon profil` accessible aux trois roles authentifies depuis le menu utilisateur global, sans information d'abonnement dans son contenu.
- Interface corporate responsive composee de sections Photo, Authentification a deux facteurs, Informations personnelles, Adresse e-mail et Mot de passe ; disposition en deux colonnes sur bureau et une colonne sur tablette/mobile, avec prise en charge du theme sombre.
- Ajout de Cropper.js `1.6.2` en dependance locale et dans `public/vendor/cropperjs` : recadrage carre, apercu circulaire, zoom, rotation, reinitialisation et export WebP `512 x 512` avant envoi.
- Ajout de `profile_photo_path` aux utilisateurs, stockage public des photos, remplacement/suppression de l'ancien fichier et affichage de la photo dans la topbar ainsi que la liste des utilisateurs, avec repli sur l'initiale.
- Separation securisee des mises a jour : nom/telephone/adresse, e-mail exigeant le mot de passe actuel et mot de passe gere par Fortify.
- Alignement des regles serveur de mot de passe avec l'interface : 12 caracteres minimum, majuscule, minuscule, lettre, chiffre, symbole et confirmation.
- Activation du moteur 2FA TOTP de Fortify avec confirmation obligatoire. La fonctionnalite reste desactivee par defaut et ne s'active qu'apres choix de l'utilisateur, verification du mot de passe, scan du QR Code et validation d'un code a six chiffres.
- Ajout de la desactivation protegee par mot de passe, de l'affichage/regeneration proteges des huit codes de recuperation et des vues Fortify de confirmation du mot de passe et de defi 2FA.
- Traductions francaises et anglaises ajoutees pour toute la page, les messages Fortify et les etats de securite.
- Migration locale `2026_07_19_200000_add_profile_photo_path_to_users_table` executee et lien `public/storage` cree.
- Verification locale : scripts JavaScript valides, vues Blade compilees et suite complete verte avec 106 tests et 903 assertions.
- Aucun deploiement VPS effectue.

#### Validation interactive du mot de passe du profil
- Alignement du formulaire de changement de mot de passe sur le comportement interactif de la modale `Nouvel utilisateur`.
- Les criteres restent neutres avant la saisie, deviennent rouges lorsqu'ils ne sont pas satisfaits et verts des qu'ils sont valides.
- Les champs nouveau mot de passe et confirmation affichent eux aussi leur etat en temps reel ; la correspondance est recalculee lors de toute modification de l'un des deux champs.
- Ajout d'une zone d'annonce accessible pour les criteres et maintien de la validation Fortify cote serveur.
- Verification locale : JavaScript valide, vues Blade compilees et suite complete verte avec 107 tests et 914 assertions.
- Aucun deploiement VPS effectue.

### 2026-07-19 - Personnalisation globale de l'application
- Remplacement de l'ecran vide de personnalisation par un formulaire corporate responsive reserve au superadmin.
- Ajout des sections Identite de l'application, Identite visuelle, Couleurs de l'application et Informations de support.
- Conformement a la demande, aucun champ `Slogan`, `Description` ou `Texte de copyright` n'a ete ajoute dans l'interface, la validation ou la base de donnees.
- Ajout de la migration locale `2026_07_19_210000_create_application_settings_table` et du modele singleton `ApplicationSetting` avec les valeurs EXAD par defaut.
- Les reglages disponibles sont : nom, nom court, site web, logo, favicon, sept couleurs de theme, e-mail support et telephone support.
- Les fichiers logo et favicon sont valides, stockes sur le disque public et remplaces sans conserver les anciennes versions. Les formats SVG ne sont pas acceptes afin d'eviter de servir un contenu actif non nettoye depuis le domaine de l'application.
- Ajout d'une previsualisation immediate des images et des couleurs, ainsi que d'une commande restaurant la palette EXAD avant enregistrement.
- Propagation reelle du nom, du nom court, du logo et du favicon dans la connexion, la sidebar, les defis d'authentification, les rapports et les titres des pages.
- Propagation des couleurs via les variables CSS communes pour la navigation laterale, les boutons, les avatars et les accents de l'application, avec prise en charge du theme sombre.
- Les erreurs de validation sont affichees directement sous les champs concernes et les formulaires conservent les valeurs apres echec.
- Migration executee localement en lot 30. JavaScript valide, vues Blade compilees et suite complete verte avec 112 tests et 963 assertions.
- Aucun deploiement VPS effectue.

#### Deploiement du Profil et de la Personnalisation
- Deploiement VPS effectue apres autorisation explicite de l'ensemble Profil, Cropper.js, 2FA Fortify et Personnalisation globale.
- Migrations production executees : `2026_07_19_200000_add_profile_photo_path_to_users_table` et `2026_07_19_210000_create_application_settings_table`.
- Sauvegarde distante prealable : `/tmp/exadtracking-before-profile-customization-20260719-231801.tar.gz`.
- Autoload Composer optimise, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; signal de redemarrage envoye aux workers de queue.
- Le premier passage s'est arrete au controle JavaScript car Node.js n'est pas present dans le `PATH` non interactif root. Le garde-fou a automatiquement remis Laravel en ligne, puis le deploiement a repris sans reextraction a partir de l'autoload et des migrations.
- Verification production reussie : application hors maintenance, lien `public/storage` actif, routes Profil/2FA/Personnalisation presentes, Apache, listener GPS et console Web actifs.
- Controles publics reussis en HTTPS 200 pour la connexion, le CSS et le JavaScript de personnalisation ainsi que Cropper.js.

### 2026-07-19 - Tolerance des angles GPS hors plage
- Diagnostic du traceur Teltonika IMEI `353201355315547`, affiche hors ligne dans EXAD Tracking alors qu'il reste joignable sur Navixy.
- Les journaux de production confirment que l'IMEI est reconnu, mais qu'un relevé Codec8 Extended est refuse par Laravel lorsque son angle depasse `359`. Le rejet du relevé empeche la mise a jour de `last_seen_at` et l'ACK de donnees, puis le traceur est classe hors ligne apres cinq minutes.
- Les lignes `codec8_extended records=1 ACK=1` visibles autour des connexions recentes appartiennent a d'autres IMEI ; le traceur concerne effectue actuellement plusieurs connexions IMEI courtes sans nouveau relevé exploitable.
- Correction locale de `gps:ingest-position` : un angle `360` est normalise a `0`, tandis qu'une valeur entiere hors plage est consideree indisponible et remplacee par le dernier cap fiable du traceur. Les controles stricts restent inchanges pour l'IMEI, les coordonnees, la vitesse et la date GPS.
- Tests de regression ajoutes pour `65535` et `360`. Suite complete verte avec 112 tests et 968 assertions.
- Aucun deploiement VPS effectue ; la correction reste locale dans l'attente d'une autorisation explicite.

#### Deploiement et retablissement du traceur
- Correctif deploye sur le VPS le 20 juillet 2026 apres autorisation explicite, uniquement dans `routes/console.php`.
- Sauvegarde distante prealable : `/tmp/exadtracking-before-gps-angle-20260720-105618.php`.
- Les caches Laravel ont ete reconstruits et `gps-tcp.service` redemarre apres validation de la syntaxe PHP.
- Le traceur `353201355315547` a immediatement repris l'envoi de lots Codec8 Extended avec `records=2 ACK=2` et son statut est repasse a `online`.
- Le journal d'erreurs est reste de taille identique pendant le controle post-deploiement : aucun nouveau rejet d'angle n'a ete genere.
- Le traceur vide encore son historique dans l'ordre `Oldest`; les positions GPS anciennes avancent progressivement tandis que `last_seen_at` reste actuel.
- Verification finale : listener GPS actif, application hors maintenance et connexion publique en HTTPS 200.

### 2026-07-20 - Separation du rattrapage GPS et de l'etat en direct
- Le rattrapage du traceur `353201355315547` a revele que les positions archivees, envoyees dans un ordre variable, remplaçaient les coordonnees et le mouvement affiches en direct. `last_position_at` pouvait meme regresser vers une heure GPS plus ancienne.
- Ajout local d'un garde-fou : une position de plus de 15 minutes ou anterieure a la derniere position live reste enregistree dans `positions`, mais ne modifie plus l'etat courant du traceur.
- Les positions de rattrapage ne declenchent plus les evenements de mouvement/allumage, les sessions conducteur, les alertes de georeperage, les politiques de vitesse ni l'evaluation d'entretien.
- `last_seen_at` et le statut de connexion continuent d'etre actualises afin de conserver le traceur en ligne pendant la synchronisation de son historique.
- Tests ajoutes pour un relevé vieux de 14 heures et un relevé recent reçu hors ordre. Les anciens tests GPS a dates fixes utilisent maintenant des temps relatifs et restent stables apres un changement de jour.
- Suite complete verte avec 113 tests et 982 assertions.
- Aucun deploiement VPS effectue pour ce second correctif dans l'attente d'une autorisation explicite.

#### Tolerance des sentinelles de telemetrie GPS
- Une nouvelle interruption du rattrapage a ete identifiee sur une altitude hors plage, apres le traitement des anciens angles invalides.
- L'ingestion locale ignore maintenant les valeurs sentinelles entieres hors plage pour la vitesse, l'altitude, les satellites, le signal GSM et le niveau de batterie, sans rejeter les coordonnees ni bloquer l'ACK du paquet.
- Les valeurs non numeriques restent refusees par la validation afin de ne pas masquer un payload structurellement incorrect.
- Le test de regression couvre dans un meme relevé les sentinelles `65535` et `255`, tout en verifiant la conservation du dernier cap valide et l'enregistrement de la position.
- Suite complete toujours verte avec 113 tests et 982 assertions. Aucun deploiement VPS effectue.

#### Deploiement du rattrapage GPS protege
- Correctif combine deploye sur le VPS le 20 juillet 2026 apres autorisation explicite, uniquement dans `routes/console.php`.
- Sauvegarde distante prealable : `/tmp/exadtracking-before-gps-replay-20260720-121350.php`.
- Les sentinelles hors plage de vitesse, altitude, satellites, GSM et batterie ne bloquent plus l'ACK des paquets.
- Les archives de plus de 15 minutes et les positions reçues hors ordre restent stockees sans remplacer l'etat live ni declencher les traitements temps reel.
- Verification production : reprise continue de `records=2 ACK=2`, traceur `online`, `last_seen_at` actuel et aucune croissance du journal d'erreurs pendant le controle.
- Preuve de separation : la derniere archive stockee a progresse jusqu'a 23:14:09 UTC, tandis que `last_position_at` est reste fige a 22:49:59 UTC au lieu de rejouer le trajet sur la carte.
- Listener GPS actif, application hors maintenance et connexion HTTPS en HTTP 200.
- Validation locale avant deploiement : 113 tests Laravel passes avec 982 assertions.

### 2026-07-21 - Logo interne configurable
- Ajout d'un logo interne distinct du logo principal dans la page Personnalisation, avec une carte dediee, un apercu sur le fond reel de la sidebar et une previsualisation immediate du fichier choisi.
- Le logo interne est reserve a l'en-tete de la sidebar. Tant qu'aucun fichier n'est configure, l'application conserve automatiquement le comportement precedent en utilisant le logo principal adapte aux surfaces sombres.
- Ajout du champ nullable `internal_logo_path` dans `application_settings`, avec une migration reversible, une validation image et un stockage separe dans `application-branding/internal-logo`.
- Le remplacement d'un logo interne supprime l'ancien fichier du disque public sans modifier le logo principal ni le favicon.
- La grille d'identite visuelle affiche trois cartes sur grand ecran, deux sur les ecrans intermediaires et une sur tablette/mobile.
- Migration executee localement et suite complete verte avec 114 tests et 991 assertions.
- Aucun deploiement VPS effectue dans l'attente d'une autorisation explicite.

### 2026-07-21 - Espace client cloisonne par flotte
- Ajout d'une affectation directe `fleet_id` pour les comptes admin et utilisateur, avec migration automatique des affectations historiques depuis `fleet_user` puis depuis l'ancien abonnement lorsque necessaire.
- Chaque admin et utilisateur client ne voit desormais que sa flotte. Le dashboard, les vehicules, les traceurs, les positions, les alertes, les evenements et les rapports reutilisent ce cloisonnement dans leurs requetes serveur.
- Le dashboard client affiche les indicateurs de sa flotte et masque la carte lorsque l'utilisateur simple ne possede pas l'autorisation correspondante.
- Les admins disposent automatiquement des quatre capacites client : carte, rapports, garages et entretiens. Les utilisateurs simples recoivent seulement les autorisations choisies par leur admin.
- La gestion des flottes, vehicules, traceurs, conducteurs et departements reste reservee au superadmin, en interface comme dans les routes d'ecriture.
- Un admin peut maintenant creer, modifier et supprimer uniquement les utilisateurs simples de sa propre flotte. Il ne peut ni promouvoir un utilisateur, ni choisir une autre flotte, ni administrer un compte externe a sa flotte.
- Le formulaire superadmin de creation d'utilisateur exige une flotte consultable par recherche. Le formulaire admin affiche sa flotte en lecture seule et propose quatre controles corporate pour les autorisations.
- Les garages crees par un client appartiennent a sa flotte. Les garages globaux du superadmin restent consultables et utilisables pour l'entretien, mais ne peuvent pas etre modifies par un client.
- Les alertes client sont actualisees par interrogation periodique de l'endpoint deja filtre par flotte, tandis que le canal WebSocket prive du superadmin reste inaccessible aux autres roles.
- La connexion de tous les roles redirige maintenant vers le dashboard adapte au compte.
- Migration locale `2026_07_21_010000_add_client_fleet_access_fields` executee avec succes.
- Tests de securite croisee ajoutes ; suite complete verte avec 120 tests et 1037 assertions.
- Aucun deploiement VPS effectue dans l'attente d'une autorisation explicite.

#### Interface client orientee vehicules et masquage des traceurs
- Le menu `Traceurs` est maintenant reserve au superadmin. Les routes de liste, de details et de trajets par identifiant de traceur sont egalement protegees par le middleware superadmin.
- La liste des vehicules client remplace l'IMEI par un statut de suivi `En ligne` ou `Hors ligne` et conserve `Aucun traceur` lorsqu'aucun equipement n'est rattache.
- Un dashboard client distinct remplace le dashboard technique : indicateurs et tableau centres sur les vehicules de la flotte, alertes recentes et raccourcis limites aux permissions du compte, sans nom, modele, IMEI ni identifiant de traceur.
- La carte client ignore tout filtre de flotte forge, masque le selecteur de flotte et limite la recherche au nom du vehicule et a l'immatriculation. Le GeoJSON ne retourne plus les proprietes techniques du traceur et utilise un identifiant public base sur le vehicule.
- Les trajets client passent par la nouvelle route `/vehicles/{vehicle}/trips`. L'identifiant interne du traceur n'apparait plus dans l'URL fournie a la carte, tandis que le serveur resout l'equipement apres controle de la flotte visible.
- Les evenements client n'affichent plus la colonne traceur. Les alertes de perte et de reprise de signal emploient des messages centres sur le vehicule et la recherche client n'interroge plus le nom ou l'IMEI du traceur.
- Les rapports HTML, CSV et PDF masquent le filtre traceur, les colonnes techniques, les compteurs de traceurs et les donnees IMEI pour les clients. Les parametres `fleet_id` et `device_id` envoyes manuellement sont ignores et les planifications refusent les vehicules hors flotte.
- Le superadmin conserve l'ensemble des vues, filtres, recherches, exports et routes techniques existants.
- Verification locale : syntaxe PHP et JavaScript valide, routes controlees, vues Blade compilees et suite complete verte avec 122 tests et 1085 assertions.
- Aucun deploiement VPS effectue ; ces changements restent locaux jusqu'a une autorisation explicite.

#### Correction de l'ordre visuel du dashboard client
- Les blocs Etat des vehicules, Alertes recentes et Actions rapides heritaient du conteneur flexible du dashboard superadmin sans posseder d'ordre explicite. Ils apparaissaient donc avant l'en-tete et les indicateurs client.
- Ajout d'un ordre CSS propre au dashboard client : en-tete, indicateurs, etat des vehicules, puis alertes et actions rapides.
- Le separateur des metadonnees d'alertes utilise maintenant une entite HTML stable afin d'eviter les caracteres mal encodes.
- Test de regression ajoute sur l'ordre structurel et les regles CSS ; vues Blade compilees et suite complete verte avec 123 tests et 1091 assertions.
- Aucun deploiement VPS effectue.

#### Reconnexion apres expiration de session sans erreur 419
- Les pages d'authentification ne sont plus conservees dans le cache du navigateur afin d'eviter la restauration d'un formulaire contenant un ancien jeton CSRF.
- Le formulaire de connexion renouvelle son jeton CSRF juste avant l'envoi et recharge automatiquement la page lorsqu'elle provient du cache de navigation arriere.
- Les erreurs CSRF 419 restantes sur une requete navigateur redirigent vers une page de connexion fraiche avec un message d'expiration, tandis que les requetes JSON conservent leur reponse 419 habituelle.
- Ajout d'un endpoint public limite au renouvellement du jeton de session, avec des en-tetes interdisant sa mise en cache.
- Tests de regression ajoutes pour les en-tetes de cache, le renouvellement CSRF, la redirection apres expiration et le maintien du statut 419 pour les appels JSON. Suite complete verte avec 125 tests et 1103 assertions.
- Aucun deploiement VPS effectue.

#### Details du traceur accessibles depuis la carte client
- Le bouton `Historique et details` et le modal `Details du traceur` sont de nouveau disponibles dans la popup de carte des comptes clients autorises a consulter la carte.
- Une nouvelle route basee sur le vehicule resout le traceur apres controle de la flotte visible ; l'identifiant interne du traceur n'apparait donc ni dans le GeoJSON ni dans l'URL client.
- Le contenu client du modal masque le modele, l'IMEI et retire le filtre d'evenements contenant l'identifiant du traceur. Le nom technique du traceur reste absent, tandis que les informations operationnelles, le conducteur, l'alimentation, la localisation et les diagnostics restent disponibles.
- Le superadmin conserve le modal complet, l'IMEI et ses routes techniques existantes.
- Tests de regression ajoutes pour le rendu client, l'absence de modele, d'IMEI et d'ID technique, ainsi que le refus d'un vehicule appartenant a une autre flotte. Suite complete verte avec 125 tests et 1115 assertions.
- Aucun deploiement VPS effectue.

#### Espace client complet en lecture seule depuis les flottes
- Le nom de chaque flotte dans la colonne `Flotte` est maintenant un lien qui active le contexte client de cette flotte puis ouvre son tableau de bord.
- Le superadmin accede ensuite a l'ensemble de l'espace client : tableau de bord, utilisateurs, vehicules, carte, alertes, evenements, garages, entretiens et rapports. Toutes les donnees restent strictement limitees a la flotte selectionnee.
- Le role et la flotte sont projetes uniquement pendant chaque requete. L'identite reelle, le role superadmin et les donnees du compte en base ne sont jamais modifies et sont restaures apres traitement.
- Le contexte `Lecture seule` reste visible dans la sidebar avec le nom de la flotte et un bouton pour quitter l'espace client et revenir a la liste des flottes.
- Toutes les requetes d'ecriture sont refusees cote serveur avec une reponse 403 pendant cet apercu. Les boutons et formulaires de modification sont egalement masques dans l'interface, tandis que la deconnexion et la sortie de l'apercu restent accessibles.
- Tests de regression etendus au lien, a la navigation, a l'isolation des utilisateurs, vehicules et donnees cartographiques, au blocage des ecritures, a la sortie du contexte et au refus des comptes clients. Suite complete verte avec 126 tests et 1150 assertions.
- L'icone de contexte et l'icone de sortie de la sidebar sont maintenant centrees dans leurs zones d'action, sans etre affectees par les regles d'ellipse reservees aux libelles.
- Deploiement VPS effectue le 21 juillet 2026 apres autorisation explicite, avec l'ensemble de l'espace client cloisonne, le logo interne configurable, les correctifs de session et le contexte superadmin en lecture seule.
- Migrations production executees : `2026_07_21_000000_add_internal_logo_path_to_application_settings_table` et `2026_07_21_010000_add_client_fleet_access_fields`.
- Sauvegarde distante prealable : `/tmp/exadtracking-before-client-space-20260721-201730.tar.gz`.
- La premiere tentative de sauvegarde s'est arretee avant la maintenance et avant toute extraction a cause d'un separateur de ligne mal echappe. La relance corrigee a ensuite termine normalement sans indisponibilite residuelle.
- Les permissions issues de l'archive Windows ont ete normalisees apres extraction a `0644` pour les fichiers et `0755` pour les dossiers, avec `exad-tracking:www-data` comme proprietaire et groupe.
- Autoload Composer optimise, caches Laravel nettoyes et reconstruits, migrations appliquees et workers de queue signales pour redemarrage.
- Verifications production reussies : routes `fleets.dashboard` et `client-preview.exit`, application hors maintenance, Apache, listener GPS et console Web actifs, page de connexion et CSS en HTTPS 200, marqueurs de lecture seule et de centrage des icones presents.
- Validation locale avant deploiement : 126 tests Laravel passes avec 1150 assertions.

### 2026-07-22 - Premiere version de l'API mobile privee
- Ajout de Laravel Sanctum pour authentifier exclusivement l'application mobile officielle sous le prefixe versionne `/api/v1/mobile`. La future API publique d'integration avec `client_id` et `client_secret`, ainsi que sa page de gestion client, restent volontairement hors de ce lot.
- Mise en place de sessions mobiles distinctes par appareil avec un jeton d'acces de courte duree et un jeton de rafraichissement de longue duree. Les jetons sont haches en base, la rotation remplace systematiquement la paire et une nouvelle connexion sur le meme appareil revoque la session precedente.
- La deconnexion peut fermer la session courante ou toutes les sessions mobiles du compte. Un compte desactive est refuse et ses jetons sont revoques lors du prochain appel authentifie.
- L'authentification a deux facteurs Fortify est respectee : lorsqu'elle est activee et confirmee, l'API emet un challenge temporaire et n'accorde aucun jeton avant validation d'un code TOTP ou d'un code de recuperation.
- Ajout des endpoints prives pour le bootstrap et la personnalisation en lecture seule, le profil, le dashboard, les vehicules, la carte, les alertes et les evenements.
- Les scopes de visibilite existants cloisonnent les donnees par flotte. Les permissions client restent appliquees, notamment `map.view`, et un filtre de flotte forge par un client est ignore.
- Les ressources JSON mobiles ne retournent aucun IMEI, identifiant interne, nom, marque, codec ou modele technique de traceur. Le suivi est expose par vehicule avec uniquement les informations operationnelles utiles.
- Le contrat Flutter, le cycle des jetons, les erreurs metier et les recommandations de stockage securise sont documentes dans `docs/mobile-api.md`.
- Ajout de 8 tests API couvrant l'emission hachee des jetons, le bootstrap, la separation acces/refresh, la rotation, la revocation globale, le remplacement d'une session d'appareil, la 2FA, les comptes indisponibles, l'isolation des flottes et la permission carte.
- Suite complete verte avec 134 tests et 1232 assertions. Formatage Laravel Pint et chargement des 13 routes mobiles valides.
- Les migrations `create_personal_access_tokens_table` et `create_mobile_sessions_table` ont ete executees avec succes sur la base locale. Les echeances de session utilisent `DATETIME` pour rester compatibles avec la version MySQL locale et SQLite en test.
- L'audit Composer remonte 13 avis de securite dans 6 dependances deja presentes, dont Laravel, Guzzle et Symfony. Leur mise a niveau doit etre traitee dans un lot dedie avant exposition d'une API publique.
- Aucun deploiement VPS effectue. Les nouvelles tables et les variables `MOBILE_API_*` restent uniquement locales jusqu'a une autorisation explicite.

### 2026-07-22 - Creation du projet Flutter mobile
- Creation du projet mobile dans le dossier frere `D:\App\Codex\exad-tracking-mobile`, separe du depot Laravel mais place dans le meme repertoire parent.
- Le nom de package Dart est `exad_tracking_mobile` et l'identifiant Android initial est `com.exad.exad_tracking_mobile`.
- Les plateformes Android et iOS ont ete generees. Les dependances Flutter initiales ont ete resolues avec succes.
- Le projet reste volontairement sur le template Flutter standard : aucune connexion a l'API locale ou de production, aucune personnalisation et aucune logique metier n'ont encore ete ajoutees.
- Validation initiale reussie avec `flutter analyze` : aucune anomalie detectee.
- Aucun deploiement ni modification du VPS effectue.

#### Initialisation Git du projet mobile
- Un depot Git independant a ete initialise dans `D:\App\Codex\exad-tracking-mobile` sur la branche `main`.
- Le `.gitignore` Flutter exclut correctement les caches, fichiers IDE, configurations locales Android et artefacts iOS generes.
- Les sources Flutter, les projets Android/iOS, `pubspec.yaml` et `pubspec.lock` sont disponibles pour le futur premier commit.
- Aucun fichier n'a encore ete indexe et aucun commit mobile n'a ete cree.

### 2026-07-22 - Premiere interface fonctionnelle de l'application mobile
- Le template Flutter a ete remplace par une application client structuree par fonctionnalite : authentification, session, tableau de bord, carte, vehicules, alertes et espace compte.
- Le client HTTP utilise l'API mobile privee locale sous `http://10.0.2.2:8000/api/v1/mobile`, stocke les jetons acces/rafraichissement avec `flutter_secure_storage`, renouvelle automatiquement la session et prend en charge le challenge 2FA.
- L'ecran de connexion reprend l'identite EXAD, affiche les erreurs sur les champs et reste adapte aux petits ecrans. Les logos officiels ont ete integres comme ressources locales.
- La navigation inferieure est construite selon les permissions du compte. La carte n'apparait que lorsque `map_view` est autorisee.
- Le dashboard client presente uniquement les indicateurs operationnels de sa flotte. Les listes et details mobiles n'exposent ni IMEI, ni identifiant, ni nom, codec ou modele technique de traceur.
- La recherche des vehicules est limitee au nom et a l'immatriculation. Les statuts, vitesses, dernieres positions et alertes utilisent les ressources JSON cloisonnees de l'API privee.
- Une premiere vue cartographique interactive positionne les vehicules a partir de leurs coordonnees et permet d'ouvrir un resume operationnel sans information technique sensible.
- La configuration Android autorise les appels HTTP uniquement pour le developpement local et conserve la permission Internet pour les futures versions HTTPS.
- Validation locale reussie : `flutter analyze` sans anomalie, 2 tests d'interface passes, API Laravel locale disponible sur le port 8000 et nouvelle application installee sur l'emulateur Android `emulator-5554`.
- Le depot Git mobile reste sans commit. Aucun deploiement Laravel, API mobile ou application n'a ete effectue en production.

#### Refonte corporate, langues et separation superadmin/client
- L'ecran de connexion mobile adopte maintenant une composition corporate EXAD avec un bandeau bleu nuit, le logo en contraste, un motif de reseau discret, une proposition de valeur courte et un formulaire securise clairement hierarchise.
- Les erreurs locales et serveur restent attachees aux champs concernes. Le contraste de la barre d'etat Android est adapte au fond sombre de la connexion.
- La langue suit celle du telephone par defaut. Un selecteur permet de choisir explicitement Francais ou English, ou de revenir au mode systeme ; la preference est conservee dans le stockage securise du telephone.
- Les textes principaux de la connexion, de la 2FA, de la navigation, des dashboards, des vehicules, des alertes, de la carte et du compte sont disponibles en francais et en anglais.
- L'espace client affiche une identification `ESPACE CLIENT`, le nom de sa flotte et ses indicateurs operationnels habituels.
- Le superadmin dispose desormais d'une console mobile differente : identite visuelle bleu nuit, entree `Supervision`, indicateurs globaux, nombre de flottes representees, repartition des vehicules par flotte et activite generale du parc.
- Le dashboard API calcule cette repartition directement en base et retourne `fleets_total` ainsi que le total et les vehicules en ligne par flotte. Les chiffres superadmin ne dependent donc pas de la pagination a 50 vehicules de la liste mobile.
- L'espace compte distingue egalement le role superadmin et conserve le choix de langue. Les autorisations client continuent de controler dynamiquement la navigation, notamment la carte.
- Validation locale : `flutter analyze` sans anomalie, 4 tests Flutter passes et suite Laravel complete verte avec 135 tests et 1242 assertions. Le rendu de connexion anglais a ete controle sur `emulator-5554`, confirmant la prise en compte de la langue systeme.
- Aucun deploiement et aucun commit n'ont ete effectues.

#### APK de test sur telephone reel
- Un APK Android debug a ete compile avec l'API locale `http://192.168.1.64:8000/api/v1/mobile` afin de tester l'application sur un telephone connecte au meme reseau Wi-Fi que le PC.
- Laravel ecoute temporairement sur `0.0.0.0:8000` et le dossier de l'APK est expose localement sur le port `8090` pour permettre son telechargement depuis le telephone.
- L'APK genere se trouve dans `D:\App\Codex\exad-tracking-mobile\build\app\outputs\flutter-apk\app-debug.apk` et peut etre telecharge localement via `http://192.168.1.64:8090/app-debug.apk`.
- Cette configuration est reservee au developpement local en HTTP. Aucun deploiement, publication sur un store ou commit n'a ete effectue.

### 2026-07-22 - Google Maps, geolocalisation et parcours mobiles
- La police monospace imposee par le theme Flutter a ete retiree. Android utilise maintenant sa police systeme Roboto et iOS son equivalent natif, tout en conservant la hierarchie et les couleurs EXAD.
- La carte simulee a ete remplacee par `google_maps_flutter`. Les positions de l'API deviennent de vrais marqueurs Google Maps, avec centrage global, centrage sur un vehicule, affichage du statut, vitesse et immatriculation.
- Un panneau lateral repliable permet de rechercher les vehicules par nom ou immatriculation. Il reste lateral sur tablette et se masque automatiquement apres selection sur telephone afin de liberer la carte.
- La fiche du vehicule selectionne propose trois actions : details operationnels, trajets et evenements. Les informations sensibles du traceur restent absentes de l'API et de l'interface mobile.
- Les evenements sont filtres par vehicule. Les trajets proposent aujourd'hui, hier, semaine et mois en cours ; un trajet selectionne est dessine comme une polyline sur Google Maps et recadre automatiquement la camera.
- Ajout de la route privee `GET /api/v1/mobile/vehicles/{vehicle}/trips`, protegee par la session mobile, la permission carte et le scope de flotte. Elle renvoie les segments, le resume et le GeoJSON sans IMEI, identifiant, nom ou modele technique du traceur.
- La geolocalisation utilise `geolocator` uniquement a la demande. Un dialogue explique l'autorisation, Android affiche ensuite sa permission native, et les cas GPS desactive, refus permanent ou position indisponible dirigent proprement vers les parametres ou un message explicite. Aucun suivi en arriere-plan n'est active.
- La cle Google Maps Android est lue depuis `android/local.properties` via `MAPS_API_KEY` et reste exclue de Git. Le package Android est `com.exad.exad_tracking_mobile` et le SHA-1 debug actuel est `DA:0C:99:F1:D6:2E:12:28:16:A3:B9:31:27:5A:8F:4F:A5:8E:03:49`.
- Une cle distincte avec `Maps SDK for Android` active et des restrictions package/SHA-1 doit encore etre fournie dans `local.properties` pour afficher les tuiles Google sur les appareils reels. La cle web ou serveur Laravel n'est pas reutilisee.
- Validation locale reussie : `flutter analyze` sans anomalie, 4 tests Flutter passes, APK debug compile, 10 tests API mobiles passes avec 100 assertions et suite Laravel complete verte avec 136 tests et 1250 assertions.
- Aucun commit, deploiement VPS ou publication mobile n'a ete effectue.

#### Modal mobile aligne sur les details du web
- Le detail mobile du vehicule reprend maintenant les rubriques du modal web : identite vehicule et flotte, emplacement, conducteur, alimentation, GSM, diagnostic traceur, OBD/CAN et cinq derniers evenements.
- Les champs operationnels comprennent notamment qualite GPS, coordonnees, stationnement ou mouvement, direction, adresse, altitude, conducteur RFID/iButton/NFC, tensions, batterie, contact, signal GSM, operateur, SIM, codec, satellites, protocole, odometre, heures moteur, entrees/sorties, capteurs et metriques OBD/CAN disponibles.
- L'affichage est empile sur telephone et passe automatiquement sur deux colonnes en largeur tablette. Les rubriques conservent des icones, couleurs fonctionnelles, dates de mise a jour et etats vides explicites.
- Ajout de `GET /api/v1/mobile/vehicles/{vehicle}/details`, protege par la session mobile, la permission carte et le scope de flotte. L'IMEI, l'identifiant interne, le nom, la marque et le modele technique du traceur restent absents.
- La carte blanche de l'emulateur a ete diagnostiquee : `MAPS_API_KEY` est absente de `android/local.properties`. Le code et le manifeste Android sont prets, mais Google Maps ne peut pas charger ses tuiles tant qu'une cle Android restreinte n'est pas ajoutee.
- Validation locale : analyse Flutter sans anomalie, 5 tests Flutter passes, APK debug compile, 11 tests API mobiles passes avec 113 assertions et suite Laravel complete verte avec 137 tests et 1263 assertions.
- Aucun commit, deploiement VPS ou publication mobile n'a ete effectue.

#### Activation locale de Google Maps Android
- Une cle Android distincte a ete creee dans Google Cloud pour `EXAD Tracking Android`, limitee a `Maps SDK for Android` et restreinte au package `com.exad.exad_tracking_mobile` avec le certificat SHA-1 debug.
- La cle est conservee uniquement dans `android/local.properties` sous `MAPS_API_KEY`; sa valeur n'est ni versionnee ni inscrite dans la documentation.
- Apres `flutter clean` et restauration des dependances, l'APK debug a ete compile, installe et lance sur `emulator-5554`.
- Verification reussie : le renderer Google Maps `LATEST` est charge sans erreur d'autorisation, les tuiles de Kinshasa s'affichent, les deux vehicules positionnes sont visibles et le panneau de recherche fonctionne.
- Aucun commit, deploiement VPS ou publication mobile n'a ete effectue.

#### Carte mobile live alignee sur le web
- Les cartes web et mobile partagent maintenant `DeviceMovementService` pour determiner les etats en mouvement, en stationnement et moteur allume a l'arret, ainsi que pour construire une trace GPS recente, continue et limitee en distance.
- L'endpoint mobile de carte retourne les memes etats operationnels et la meme trace courte que le web, tout en conservant le cloisonnement par flotte et le masquage de l'identite technique du traceur.
- L'ecran Flutter interroge uniquement la carte toutes les 10 secondes lorsqu'elle est visible. Le rafraichissement est suspendu hors de l'onglet ou lorsque l'application passe en arriere-plan.
- Les nouvelles positions des vehicules en mouvement sont interpolees sur 5 secondes le long de la trace serveur. La polyline progresse avec le marqueur et la camera suit le vehicule selectionne sans recharger le dashboard, les alertes ou le reste de la session.
- Le panneau mobile affiche des compteurs positionnes, en ligne, en mouvement et hors ligne, une recherche nom/immatriculation, un filtre d'etat, l'heure de la derniere mise a jour et un interrupteur de suivi direct.
- Les marqueurs et les lignes vehicule distinguent les etats mouvement, stationnement, moteur allume a l'arret, maintenance, inactif, en ligne et hors ligne. Sur telephone, la selection replie le panneau, zoome au niveau rue et conserve les actions Details, Trajets et Evenements.
- Validation locale : analyse Flutter sans anomalie, 6 tests Flutter passes, APK debug compile et verifie visuellement sur `emulator-5554`; 11 tests API mobiles passent avec 118 assertions, 3 tests cartographiques web passent avec 23 assertions et la suite Laravel complete passe avec 137 tests et 1268 assertions.
- Aucun commit, deploiement VPS ou publication mobile n'a ete effectue.

### 2026-07-22 - Deploiement web de l'API mobile et du suivi cartographique partage
- Le projet Laravel a ete deploye sur `/var/www/exadtracking.app` avec l'API mobile privee, Sanctum, les details et trajets mobiles, ainsi que `DeviceMovementService` partage par les cartes web et mobile.
- Les cartes web et mobile utilisent maintenant les memes regles de mouvement, stationnement, moteur allume a l'arret et construction de trace GPS recente. Aucun fichier Flutter ni aucune cle Google Maps n'a ete copie sur le serveur.
- Composer a installe `laravel/sanctum` v4.3.3 et les migrations `personal_access_tokens` et `mobile_sessions` ont ete appliquees en production.
- Les caches Laravel ont ete nettoyes et reconstruits, l'autoload optimise et le signal de redemarrage des workers de queue envoye. Les 15 routes `api/v1/mobile` sont chargees.
- Verification production : connexion HTTPS et endpoint de sante en 200, API protegee en 401 sans jeton, validation JSON en 422, maintenance desactivee, Apache, GPS TCP et console serveur actifs.
- La sauvegarde de retour arriere est conservee dans `/tmp/exadtracking-before-mobile-api-live-map-20260722-134157.tar.gz`.
- Validation locale avant deploiement : 137 tests Laravel et 1268 assertions, 6 tests Flutter et analyse Flutter sans anomalie. Aucune publication de l'application mobile n'a ete effectuee.

#### Barre de navigation mobile aux couleurs EXAD
- La barre de navigation inferieure Flutter utilise maintenant la couleur principale issue de la personnalisation de l'application, avec un indicateur actif dans la couleur secondaire.
- Les icones et libelles actifs sont blancs ; les elements inactifs conservent un blanc attenue afin de rester lisibles sans concurrencer l'onglet selectionne.
- La zone de navigation systeme Android reprend la meme couleur et supprime la bande blanche sous la barre sur les appareils utilisant la navigation gestuelle.
- Validation locale reussie : `flutter analyze` sans anomalie et 6 tests Flutter passes.
- Aucun deploiement, commit ou publication mobile n'a ete effectue.

#### Identite native et API de production de l'application mobile
- Le nom affiche par l'application est maintenant `EXAD Tracking` sur Android et iOS.
- Les icones de lancement Android, y compris l'icone adaptative, et le catalogue iOS ont ete regeneres depuis `public/images/icon-exad-tracking.png`, qui correspond au favicon EXAD utilise par l'application web.
- L'URL mobile par defaut est desormais `https://exadtracking.app/api/v1/mobile`. L'application installee lit donc les positions et mouvements recus par le serveur de production au lieu du serveur d'ecoute local statique.
- La variable de compilation `API_BASE_URL` reste disponible pour pointer ponctuellement vers `http://10.0.2.2:8000/api/v1/mobile` pendant le developpement local.
- L'endpoint de production a ete controle avec un en-tete JSON et refuse correctement une requete sans jeton en `401`; l'API mobile privee et son cloisonnement restent donc appliques.
- Validation locale reussie : generation des icones natives, `flutter analyze` sans anomalie, 6 tests Flutter passes, APK debug compile, installe et lance sur `emulator-5554`.
- Aucun commit, deploiement VPS ou publication sur un store n'a ete effectue.

#### Camera de rue et marqueurs d'etat sur la carte mobile
- L'ajustement automatique de la camera sur tous les vehicules a l'ouverture de la carte a ete retire, car il eloignait la vue jusqu'a masquer le detail des rues.
- La carte s'ouvre maintenant sur le premier vehicule positionne avec un zoom de rue `15.5`. Le bouton de cadrage global reste disponible pour afficher volontairement tous les vehicules.
- Les epingles Google Maps generiques ont ete remplacees par des marqueurs coherents avec la carte web : fleche bleue orientee selon le cap pour un vehicule en mouvement, cercle bleu avec `P` pour le parking et carre bleu avec pause pour un vehicule a l'arret moteur allume.
- Les autres etats utilisent une icone voiture : vert en ligne, orange hors ligne, rouge inactif et violet en maintenance. Les couleurs reprennent la palette cartographique du web.
- Validation locale reussie : `flutter analyze` sans anomalie, 6 tests Flutter passes, APK debug compile, installe et lance sur `emulator-5554`.
- Aucun commit, deploiement VPS ou publication mobile n'a ete effectue.

#### Details techniques mobiles reserves au superadmin
- L'endpoint prive de details vehicule adapte maintenant sa reponse au role authentifie. Le superadmin recoit un bloc technique `tracker` avec l'identifiant interne, le nom, l'IMEI, la marque et le modele du traceur.
- Les administrateurs clients et utilisateurs simples ne recoivent pas la cle `tracker`; l'IMEI, le nom, la marque, le modele et l'identifiant interne restent donc absents de leur reponse API et de leur interface mobile.
- Pour le superadmin, la feuille mobile devient `Details du traceur`, utilise une icone de composant et affiche les informations techniques dans la carte principale, avant les rubriques emplacement, conducteur, alimentation, GSM, diagnostic, OBD/CAN et evenements.
- La premiere carte mobile reprend exactement la structure web : titre `Vehicule (immatriculation)`, puis `Modele`, `ID` correspondant a l'IMEI, `Flotte` et une pastille de statut pour le superadmin. Les lignes redondantes d'immatriculation et de configuration GPS ont ete retirees.
- Pour les comptes clients, la feuille reste `Details du vehicule` et conserve le meme titre, la flotte et le statut, sans les lignes `Modele` et `ID`.
- Validation locale : 12 tests API mobiles passes avec 126 assertions, suite Laravel complete verte avec 138 tests et 1276 assertions, analyse Flutter sans anomalie et 6 tests Flutter passes. L'APK debug a ete reinstalle sur `emulator-5554`.
- Aucun commit ni deploiement VPS n'a ete effectue. L'API de production doit etre deployee sur autorisation avant que le bloc technique superadmin soit visible dans l'application connectee au serveur public.

## 2026-07-22 - Deploiement des details techniques du traceur dans l'API mobile
- L'API Laravel de production fournit maintenant le bloc `details.tracker` uniquement aux comptes superadmin avec le modele, l'IMEI, le nom et l'identifiant interne du traceur.
- Les comptes administrateur client et utilisateur simple continuent de recevoir une reponse sans cle `details.tracker`; les informations techniques sensibles restent donc masquees cote client.
- Le deploiement a ete limite a `MobileVehicleDetailController.php` et `MobileVehicleDetailService.php`, sans migration ni modification de base de donnees.
- Verification visuelle sur `emulator-5554` apres redemarrage de l'application : la fiche superadmin affiche `Teltonika FMB003`, l'IMEI `353201355315547`, la flotte et le statut en ligne dans la structure attendue.
- Validation : 12 tests API mobiles et 126 assertions passes localement, syntaxe PHP valide, `/login` et `/up` en `200`, API protegee en `401` sans jeton, services de production actifs.
- Aucun commit ni publication sur un store n'a ete effectue.

## 2026-07-22 - Segmentation detaillee des trajets preparee localement
- L'analyse des 3 064 positions du traceur `353201355315547` sur la journee a montre que certains firmwares Teltonika conservent `movement=true` et parfois le contact actif pendant un stationnement, alors que la vitesse reste a `0` et que les coordonnees ne changent pas.
- Le calcul des trajets s'appuie maintenant prioritairement sur la vitesse mesuree : une position a vitesse nulle est consideree comme un arret, meme si le drapeau de mouvement reste actif. Le drapeau `movement` reste le repli lorsque la vitesse est absente.
- Un stationnement continu d'au moins cinq minutes separe deux trajets. Les points d'arret prolonges ne gonflent plus la duree de conduite, tandis que les pauses courtes restent integrees au trajet courant.
- La selection, l'ordre et les ecarts temporels utilisent desormais `gps_time`, avec repli sur `server_time` uniquement pour les anciennes positions sans heure GPS. Les transmissions retardees sont ainsi replacees dans l'ordre reel du parcours.
- Les micro-deplacements de stationnement inferieurs a 50 metres restent filtres sans retarder artificiellement l'heure de depart du trajet suivant.
- Une simulation en lecture seule sur les donnees de production du 22/07/2026 produit 9 trajets significatifs au lieu des 3 grands blocs actuels, avec des horaires et distances proches de la chronologie Navixy fournie.
- Validation locale : 8 tests de trajets avec 59 assertions, 12 tests API mobiles avec 126 assertions et suite Laravel complete avec 140 tests et 1 289 assertions.
- Ce correctif n'a pas ete deploye et aucun commit n'a ete cree.

## 2026-07-22 - Deploiement de la segmentation detaillee des trajets
- Le service `app/Services/DeviceTripService.php` a ete deploye seul vers la production, sans inclure les autres modifications locales en attente.
- La production segmente maintenant les trajets sur les arrets reels d'au moins cinq minutes, donne priorite a la vitesse sur le drapeau de mouvement, utilise l'heure GPS et filtre les micro-deplacements de parking.
- Une sauvegarde de retour arriere a ete creee dans `/tmp/exadtracking-before-trip-segmentation-20260722-183255.tar.gz`.
- Les caches Laravel ont ete nettoyes puis les caches de configuration et de vues reconstruits. Le signal de redemarrage des workers de queue a ete envoye.
- Le fichier distant correspond exactement au SHA-256 local `67108983a177cc5e8cee464de3d809453f4ef33dc291f98969aa567ded7b969e`, passe la verification syntaxique PHP et est lisible en mode `0644`.
- Verifications finales : production hors maintenance, debug desactive, `/login` et `/up` en `200`, API mobile en `401` sans jeton, Apache, GPS TCP et console serveur actifs.
- Aucun schema, migration, variable d'environnement, asset ou code Flutter n'a ete modifie. Aucun commit n'a ete cree.

## 2026-07-22 - Refonte corporate de la liste des trajets mobiles
- La feuille Flutter des trajets a ete transformee en chronologie operationnelle sobre, avec des cartes blanches, une bordure fine et la couleur de chaque trajet limitee a un accent lateral.
- Un resume de periode affiche maintenant le nombre de trajets, la distance totale et la duree cumulee sous le selecteur Aujourd'hui, Hier, Cette semaine et Ce mois.
- Les trajets sont regroupes visuellement par date. Chaque element distingue clairement l'heure et l'adresse de depart de l'heure et l'adresse d'arrivee au moyen d'une ligne chronologique et de deux reperes.
- La distance, la duree et la vitesse moyenne restent visibles dans un pied compact. Toute la carte est selectionnable pour afficher le parcours sur Google Maps.
- Le rendu a ete controle sur `emulator-5554` avec les donnees reelles de production : 13 trajets sont presentes sans debordement ni chevauchement sur le format telephone.
- Validation locale : `flutter analyze` sans anomalie et 6 tests Flutter passes.
- Aucun deploiement VPS, commit mobile, publication APK ou modification de l'API n'a ete effectue.

### APK Android release de test physique
- Un APK universel release a ete genere apres la refonte de la chronologie des trajets dans `D:\App\Codex\exad-tracking-mobile\build\app\outputs\flutter-apk\app-release.apk`.
- Le build utilise l'API de production et la cle Google Maps Android locale deja configuree. Il est signe avec le certificat Android de developpement et reste reserve aux tests, pas a une publication Play Store.
- La signature APK v2 a ete validee, puis le fichier exact a ete installe et lance avec succes sur `emulator-5554`.
- Taille : 54 638 999 octets. SHA-256 : `520e3449fa4778faf2b103bcb60703a9258f960b2da8ddac3427fa0084cdf03f`.
- Un serveur local temporaire expose le fichier sur le reseau Wi-Fi a l'adresse `http://192.168.1.68:8091/app-release.apk` pour les telephones physiques.
- Un QR code a forte correction d'erreur a ete genere entierement en local dans `build/app/outputs/flutter-apk/exad-tracking-apk-qr.png`; il encode directement cette URL de telechargement Wi-Fi.
- Aucun commit, deploiement VPS ou publication sur un store n'a ete effectue.

### Boutons cartographiques adaptes aux polices Android agrandies
- Les actions `Details`, `Trajets` et `Evenements` du panneau vehicule Flutter utilisent maintenant une hauteur stable, des espacements compacts et un libelle strictement limite a une ligne.
- Chaque libelle se reduit automatiquement dans l'espace disponible au lieu de revenir a la ligne, ce qui corrige le rendu observe sur Galaxy S24 Ultra avec les reglages Samsung de police ou d'affichage agrandis.
- Le rendu a ete controle sur `emulator-5554` avec l'echelle de police Android forcee a `1.3` : les trois icones et libelles restent centres, lisibles et alignes sur une seule ligne. L'echelle de l'emulateur a ensuite ete restauree a `1.0`.
- Validation locale : `flutter analyze` sans anomalie, 6 tests Flutter passes, APK release reconstruit, signature APK v2 validee et installation de controle reussie.
- Nouvel APK : 54 655 383 octets, SHA-256 `d807d05d97ec469b2418ef61592cbcdd061b8542d88e93a4f576d5ea5351e8eb`.
- Le lien Wi-Fi et le QR code existants restent valides : `http://192.168.1.68:8091/app-release.apk` repond en HTTP `200` et distribue le fichier reconstruit.
- Aucun commit, deploiement VPS ou publication sur un store n'a ete effectue.

#### Regeneration de l'APK de test
- L'APK release Android a ete regenere le 22/07/2026 a 20:52 depuis l'etat courant du projet Flutter.
- La signature APK v2 est valide. Le build est reproductible et conserve le SHA-256 `d807d05d97ec469b2418ef61592cbcdd061b8542d88e93a4f576d5ea5351e8eb` pour une taille de 54 655 383 octets.
- Le fichier est disponible sur le reseau local a l'adresse `http://192.168.1.68:8091/app-release.apk`, controlee en HTTP `200`. Le QR code existant reste valable.
- Aucun commit, deploiement VPS ou publication sur un store n'a ete effectue.

## 2026-07-22 - Livraison finale web et mobile des boutons responsives
- Le depot web ne contenait aucun ecart de code executable par rapport au dernier deploiement : seuls les historiques locaux etaient en attente. La production a donc ete controlee sans extraction ni remplacement de fichier inutile.
- Le service de trajets distant correspond toujours au SHA-256 local `67108983a177cc5e8cee464de3d809453f4ef33dc291f98969aa567ded7b969e`. Laravel est en production, debug desactive et maintenance inactive ; Apache, le listener GPS et la console serveur sont actifs.
- Les controles HTTPS executes depuis le VPS retournent `200` pour `/login`, `200` pour `/up` et `401` pour l'API mobile appelee sans jeton.
- L'APK mobile contenant les boutons cartographiques adaptes a ete installe puis lance avec succes sur `emulator-5554`. Sa distribution Wi-Fi locale sur `http://192.168.1.68:8091/app-release.apk` repond en `200`.
- Validation avant livraison : 140 tests Laravel avec 1 289 assertions, analyse Flutter sans anomalie et 6 tests Flutter passes.
- Cette livraison mobile reste une version de test signee avec le certificat Android de developpement. Aucune publication Play Store n'a ete effectuee.

## 2026-09-03 - Immobilisation moteur par traceur compatible
- Mise en place d'une chaine complete de commande distante persistante entre Laravel et l'ecouteur GPS, avec suivi des statuts, tentatives, expiration, acquittement et confirmation.
- La demande d'immobilisation attend obligatoirement l'arret complet du vehicule ; aucune commande de coupure n'est remise au traceur pendant la conduite.
- La compatibilite est geree par une matrice extensible marque-modele, appliquee dans l'autorisation et dans l'action metier. Le FMB140 est autorise et le FMB003 est explicitement exclu.
- Le detail du traceur presente un seul bouton qui alterne entre immobilisation et autorisation du demarrage. La confirmation se fait par une alerte SweetAlert compacte, sans formulaire.
- Tous les textes fonctionnels utilisent `traceur` ou `tracker` au lieu d'une marque. Les noms de marque sont conserves uniquement lorsqu'ils identifient reellement le materiel ou le protocole interne.
- L'acces reste limite au superadministrateur, protege par la session, CSRF et un limiteur de frequence. Les controles serveur de securite moteur restent independants de l'interface.
- Validation : 176 tests Laravel passes avec 1 574 assertions, compilation Blade et controles syntaxiques PHP/JavaScript reussis.
- Production controlee : FMB003 refuse, FMB140 autorise, Apache et l'ecouteur GPS actifs, `/up` et `/login` en HTTP 200.

## 2026-09-03 - Delegation de l'immobilisation moteur aux comptes clients
- Les administrateurs de flotte peuvent maintenant immobiliser ou reactiver le demarrage des vehicules compatibles de leur propre flotte. Les utilisateurs standards restent interdits par defaut.
- Une autorisation individuelle `engine.control`, presentee dans la gestion des utilisateurs sous le libelle `Commander l'immobilisation moteur`, permet a l'administrateur de flotte d'accorder puis de retirer cette capacite a un utilisateur precis. L'utilisateur ne peut pas se l'attribuer lui-meme.
- L'autorisation serveur exige cumulativement un compte actif, la meme flotte que le vehicule, un modele de traceur compatible et un abonnement comprenant l'arret moteur distant. Les verifications de securite moteur existantes restent appliquees apres verrouillage transactionnel.
- L'interface client utilise des routes basees sur le vehicule et ne divulgue pas l'identifiant interne du traceur. Le bouton reste masque pour les comptes non autorises et pendant la previsualisation client. L'acces a la carte doit aussi etre accorde aux utilisateurs standards pour ouvrir les details du vehicule.
- Aucune migration n'est requise : l'autorisation est stockee dans le champ JSON `permissions` deja existant.
- Validation locale : 181 tests Laravel passes avec 1 602 assertions, dont 23 tests cibles avec 252 assertions ; Pint, syntaxe PHP, routes et compilation Blade valides.
- Aucun deploiement ni commit n'a ete effectue pour cette evolution.

## 2026-09-03 - Deploiement de la delegation client de l'immobilisation
- Les administrateurs clients peuvent maintenant utiliser l'immobilisation moteur sur les vehicules compatibles de leur flotte. La permission individuelle `engine.control` est disponible dans la gestion des utilisateurs pour deleguer ou retirer cette capacite a un compte standard.
- Le deploiement cible les 10 fichiers d'autorisation, de resolution vehicule-traceur, d'interface, de traductions et de routes. Aucune migration, donnee metier ou configuration d'environnement n'a ete modifiee.
- L'archive de deploiement `deploy-exadtracking-client-engine-permission-20260903-150821.tar.gz` a pour SHA-256 `8ab06b73134f6a67130f94a921fc3cb32a87f4bc4c9931e470e9caa293ac8ce4`.
- La sauvegarde de retour arriere est `/tmp/exadtracking-before-client-engine-permission-20260903-152020.tar.gz`, protegee en mode `0600`, avec le SHA-256 `4beeed2ec7a1c02d477c3c5aa99e90bafbdd80f13b9b107d28c6da5effcaf8c6`.
- Les 10 fichiers distants correspondent exactement aux empreintes locales. Les caches Laravel ont ete nettoyes, puis les caches de configuration et de vues reconstruits ; le signal de redemarrage des workers a ete emis.
- Verification production : route client protegee par session, CSRF et limiteur `engine-control`, environnement `production`, debug desactive, maintenance inactive, `/up` et `/login` en HTTP 200, `/map` en redirection authentifiee, Apache, PHP-FPM, GPS TCP et Supervisor actifs.
- Aucun commit n'a ete cree.

## 2026-09-03 - Retrait des abonnements prepare localement
- La rubrique `Abonnements`, ses routes de consultation et de modification, son controleur, sa vue et ses traductions ont ete retires. L'URL historique `/subscriptions` retourne desormais une page introuvable.
- Les formulaires et tableaux Vehicules n'affichent plus de champ, de colonne ou de badge de plan. La recherche et le tri par plan ont egalement ete supprimes.
- Le tableau Flottes ne repartit plus les vehicules entre Basique, Standard et Premium ; il conserve uniquement le nombre total de vehicules. Son en-tete utilise maintenant le libelle generique `Gestion de flotte`.
- L'immobilisation moteur n'est plus conditionnee par une offre ou un plan. Elle reste protegee par le compte actif, la flotte, la permission individuelle, la compatibilite du traceur et les controles de securite qui interdisent toute coupure pendant la conduite.
- Les nouveaux utilisateurs et traceurs sont relies directement a leur flotte sans nouvelle ecriture de `subscription_id`. Les colonnes historiques `subscription_id` et `subscription_plan` sont conservees en base, sans suppression ni migration destructive, pour proteger les donnees existantes.
- Aucun contrat de l'API mobile n'exposait d'abonnement ; aucune reponse mobile n'a donc ete modifiee.
- Validation locale : 182 tests Laravel passes avec 1 597 assertions, dont 113 tests cibles avec 1 031 assertions ; Pint, syntaxe PHP, compilation Blade, inventaire des routes et controle des occurrences visibles valides.
- Cette evolution n'est pas encore deployee et aucun commit n'a ete cree.

## 2026-09-03 - Deploiement du retrait des abonnements
- La suppression des abonnements a ete deployee vers `/var/www/exadtracking.app` : 21 fichiers applicatifs ont ete mis a jour et les quatre anciens fichiers du controleur, de la vue et des traductions Abonnements ont ete retires.
- L'archive transferee est `/tmp/deploy-exadtracking-remove-subscriptions-20260903-154237.tar.gz`, avec le SHA-256 `622bc9fb6eaae463bc64816e202997672cc398fe974c193095eb5d7b74bd4759` verifie avant installation.
- La sauvegarde de retour arriere est `/tmp/exadtracking-before-remove-subscriptions-20260903-154433.tar.gz`, protegee en mode `0600`, avec le SHA-256 `afa930744c0e181b2fb3f31488a2e51152a390f4ecad04d8bf5594d558a3b395`.
- Laravel a ete place temporairement en maintenance pendant la copie, puis les caches ont ete nettoyes et les caches de configuration et de vues reconstruits. Les workers de queue ont recu le signal de redemarrage et l'application a ete remise en ligne sans erreur d'installation.
- Aucune migration, suppression de colonne ou modification de donnee metier n'a ete realisee. Les anciennes valeurs `subscription_id` et `subscription_plan` restent conservees en base pour permettre un retour arriere sans perte de donnees.
- Validation avant deploiement : 182 tests Laravel passes avec 1 597 assertions, dont 113 tests cibles avec 1 031 assertions ; Pint, syntaxe PHP, compilation Blade, routes et controle des occurrences visibles valides.
- Aucun commit n'a ete cree.

## 2026-09-03 - Gestion des departements par les comptes clients preparee localement
- La page Departements est maintenant consultable par les administrateurs et utilisateurs standards, avec un filtrage serveur strict sur leur propre flotte. Le superadministrateur conserve la vue globale.
- L'administrateur client peut creer, modifier et desactiver les departements de sa flotte. Le `fleet_id` transmis par le navigateur est remplace cote serveur par celui du compte afin d'empecher toute ecriture dans une autre flotte.
- Les utilisateurs standards disposent d'une consultation seule : aucun bouton de creation, formulaire, modal ou action de modification n'est rendu, et les requetes d'ecriture directes sont refusees en `403`.
- La suppression definitive reste reservee au superadministrateur et est bloquee lorsqu'un chauffeur est encore affecte. Le changement de flotte d'un departement occupe est egalement refuse afin d'eviter des associations incoherentes.
- L'apercu client du superadministrateur reste filtre sur la flotte selectionnee et entierement en lecture seule. Aucune permission delegable ni migration n'a ete ajoutee ; le statut `inactive` existant sert a desactiver un departement.
- Validation locale : 188 tests Laravel passes avec 1 643 assertions, dont 20 tests de gestion de flotte avec 193 assertions ; Pint, syntaxe PHP, compilation Blade et routes valides.
- Cette evolution n'est pas encore deployee et aucun commit n'a ete cree.

## 2026-09-03 - Deploiement de la gestion client des departements
- Les 11 fichiers applicatifs de gestion, autorisation, interface, navigation, traduction et routage des departements ont ete deployes vers `/var/www/exadtracking.app`.
- L'administrateur client peut maintenant creer, modifier et desactiver les departements de sa flotte. Les utilisateurs standards disposent de la consultation seule et le superadministrateur conserve la gestion globale ainsi que la suppression des departements inutilises.
- Archive transferee : `/tmp/deploy-exadtracking-client-departments-20260903-164715.tar.gz`, SHA-256 `13264d87a669087776721128880e12133207d86b218c94a33addd3ba69be69a0`.
- Sauvegarde de retour arriere : `/tmp/exadtracking-before-client-departments-20260903-164844.tar.gz`, SHA-256 `c5b53178a1ac0fb6bd0490c76f5bcbc4f1ae16850c3832251f83396dd2a4ea2c`, permissions `0600`.
- Les 11 fichiers distants correspondent exactement aux fichiers locaux, avec l'empreinte agregee `5cfdc3b33d55b11038f5037f479018f64552a593ec9c140510305bf328208ecd`. Les caches ont ete reconstruits et les workers de queue signales.
- Verification production : environnement `production`, debug desactive, maintenance inactive, `/up` et `/login` en HTTP 200, `/departments` en redirection HTTP 302 sans session, Apache, PHP-FPM, GPS TCP et Supervisor actifs.
- Aucune migration ni donnee metier n'a ete modifiee. Aucun commit n'a ete cree.

## 2026-09-03 - Alignement de l'application mobile avec les fonctions web
- L'API mobile expose maintenant les departements avec le meme cloisonnement et les memes droits que le web : lecture pour les comptes de flotte, gestion pour l'administrateur client, gestion globale et suppression conditionnelle pour le superadministrateur.
- Le detail mobile des vehicules retourne un etat d'immobilisation minimal et non sensible. L'application affiche un seul bouton dynamique, masque pour les traceurs incompatibles comme le FMB003 et pour les comptes non autorises.
- Les commandes mobiles reutilisent l'action metier serveur existante, son limiteur et ses garde-fous : une immobilisation attend toujours l'arret moteur confirme et ne coupe jamais le moteur pendant la conduite.
- Le renouvellement des jetons Flutter est maintenant partage entre les requetes concurrentes. La methode, les parametres et le corps de la requete initiale sont conserves au rejeu, ce qui elimine une cause de fermeture de session intermittente.
- La documentation serveur et mobile a ete synchronisee. Validation : 28 tests Laravel cibles avec 243 assertions, 15 tests Flutter passes, analyse Flutter sans anomalie et APK Android debug compile avec succes.
- Aucun deploiement de ces nouveaux endpoints ni commit n'a encore ete effectue.

## 2026-09-04 - Coherence conducteur courant et etat moteur CAN
- Le conducteur des details web et mobile provient maintenant exclusivement de la session de conduite active du traceur et du vehicule. Un ancien UID conserve dans `devices.last_driver_identifier_uid` ne peut plus afficher un chauffeur apres la fermeture de sa session.
- Les identifiants iButton exacts restent prioritaires sur leur representation en ordre d'octets inverse. La validation refuse desormais l'affectation a deux chauffeurs de badges physiquement equivalents dans les deux ordres.
- Le listener privilegie l'identifiant porte par l'IO declencheur, ignore les sentinelles composees uniquement de zeros et recherche ensuite le premier identifiant non nul parmi les IO compatibles.
- Le mot de securite CAN P4/AVL 517 n'est plus converti directement en booleen. Le listener et Laravel extraient le bit moteur 11 : `317` indique un moteur arrete et `2048` un moteur en marche.
- Les controles d'immobilisation echouent maintenant de maniere sure si l'etat moteur est absent ou ambigu et refusent explicitement un mot P4 dont le bit moteur est actif.
- Des tests PHP et Node couvrent les sessions actives, les UID perimes, les ordres d'octets inverses, les sentinelles iButton et les mots P4 moteur arrete/en marche.

## 2026-09-05 - Application Android 1.0.0+13 publiee
- La page publique `/application` propose maintenant le build `1.0.0+13` comme derniere version et conserve le build `1.0.0+12` dans les versions precedentes.
- Le build 13 apporte la carte mobile modernisee inspiree de X-Monitor, la telemetrie GPS/reseau/batterie, la duree de stationnement et une navigation principale simplifiee.
- L'APK 13 mesure `55 346 947` octets et porte le SHA-256 `6be2ecd4bcba06967ec76a54d6d0cad8c5e9b3fd5e8889768520896cb98abe3d`. La signature Android v2, les empreintes locale/distante et les telechargements partiels ont ete verifies.
- Validation : analyse Flutter sans anomalie, 17 tests Flutter passes, 6 tests Laravel avec 34 assertions, `/up` et `/application` en HTTP 200 et APK 12/13 en HTTP 206 pour les requetes partielles.
- Aucun commit n'a ete cree.

## 2026-09-05 - Telemetrie de Suzuki Horly et politique de conservation Android
- L'API mobile de la carte expose maintenant les statuts GPS et les pourcentages de signal reseau et de batterie. Une valeur batterie brute egale a zero est remplacee par une estimation issue de la tension interne lorsqu'elle est valide, sans fabriquer de pourcentage reseau.
- En production, Suzuki Horly restitue desormais GPS `100 %`, reseau `80 %` et batterie `80 %` au dernier controle.
- La page publique conserve le build actuel 13 et cinq builds precedents, de 12 a 8. Les builds 3 a 7 ont ete retires du catalogue et du stockage serveur.
- Validation : Pint, 199 tests Laravel avec 1 701 assertions, et controles HTTP publics conformes. Aucun commit n'a ete cree.
- Correction du suivi des commandes d'immobilisation : acquittement immediat des sorties DOUT confirmees, attente maintenue pour les reponses mises en file, confirmation uniquement par une telemetrie posterieure a l'envoi et rafraichissement mobile automatique toutes les cinq secondes.
- La fenetre de securite accepte des echantillons de stationnement espaces tout en exigeant toujours trois mesures concordantes, un signal recent, vitesse et regime a zero, contact coupe et moteur arrete. Une commande expiree ne bloque plus l'interface.

## 2026-09-05 - Correctif de suivi des commandes moteur et build Android 14
- Les reponses du traceur qui confirment exactement DOUT1 et DOUT2 terminent desormais la commande immediatement. Les reponses `QUEUED` restent en attente d'une telemetrie ulterieure et aucune ancienne telemetrie ne peut confirmer une commande nouvelle.
- L'application mobile recharge l'etat toutes les cinq secondes pendant le traitement, sans masquer les informations deja affichees, puis reactive le bouton unique lorsque la commande atteint un etat terminal.
- La securite d'immobilisation exige toujours trois mesures concordantes moteur arrete, y compris lorsque les signaux de stationnement sont espaces. Les commandes expirees ne figent plus l'interface.
- Le build Android `1.0.0+14` a ete genere pour publication. Le catalogue conserve la version courante et cinq versions precedentes, de 13 a 9.

## 2026-09-05 - Toggle moteur et flottes mobiles repliables
- Le grand bouton de commande moteur mobile est remplace par un interrupteur unique aligne sur le comportement du web. Le libelle et l'icone suivent automatiquement l'action disponible, avec confirmation obligatoire avant envoi.
- La liste des vehicules est regroupee par flotte. Chaque en-tete affiche le nombre de vehicules et le ratio en ligne, puis permet de replier ou developper uniquement sa flotte.
- Une recherche force temporairement l'affichage des groupes contenant un resultat afin qu'un vehicule trouve ne reste pas masque.
- Validation : 18 tests Flutter passes, analyse statique sans anomalie et APK Android `1.0.0+15` genere avec signature v2 valide.

## 2026-09-05 - Correctif des timeouts de details traceur
- Le profilage de production a mesure 349 321 positions pour Suzuki Horly et un temps de construction de 22,2 secondes, superieur au delai mobile de 18 secondes. Trois tris SQL non couverts par l'index consommaient environ 18 secondes a eux seuls.
- Les details mobile et web utilisent maintenant l'index `device_id/gps_time`, evitent le tri secondaire couteux et ne decodent plus 250 paquets CAN bruts pour calculer le debut du stationnement.
- Les cinq evenements sont charges par une requete bornee sans fonction de fenetrage inutile pour un seul traceur. L'etat de commande moteur selectionne uniquement les colonnes necessaires.
- L'ecran mobile conserve un message d'erreur explicite et propose desormais une action `Reessayer` en cas de panne transitoire.
- Validation : 205 tests Laravel avec 1 721 assertions, 18 tests Flutter, analyse Flutter sans anomalie et APK Android `1.0.0+16` signe en v2. Le build 16 a ete installe et lance sur l'emulateur `emulator-5554`.

## 2026-09-06 - Sorties du traceur independantes preparees localement
- La commande Web expose deux interrupteurs distincts, `Sortie #1` et `Sortie #2`, avec un etat et un traitement independants.
- Chaque requete cible explicitement une seule sortie. L'etat et la temporisation de l'autre sortie sont ignores avec `?`, afin de ne pas perturber un iButton, un voyant, un buzzer ou un autre accessoire cable.
- L'activation conserve la validation serveur de l'arret moteur et le veto final du listener ; la desactivation de la sortie selectionnee reste immediate. Les permissions, le cloisonnement par flotte, la protection CSRF et la limitation de debit sont inchanges.
- L'API mobile conserve les actions `immobilize` et `release`, exige maintenant `output` egal a `1` ou `2`, et expose `outputs.1/2` avec `active`, `busy` et `next_action`, sans divulguer le texte Codec 12.
- L'application Flutter affiche les deux sorties dans une carte unique, transmet le numero cible dans chaque requete et laisse les interrupteurs indisponibles tant que l'ancienne API ne fournit pas leurs etats distincts.
- Validation : 207 tests Laravel avec 1 741 assertions, 10 tests Node et 18 tests Flutter passes ; analyse Flutter et syntaxes PHP/JavaScript sans anomalie.
- L'APK Android `1.0.0+17` a ete genere, installe et lance sur `emulator-5554`. Aucun deploiement Web/API, aucune publication d'APK et aucune commande vers un traceur n'ont ete effectues.

## 2026-09-06 - Publication de l'application Android 1.0.0+17
- Le build Android `1.0.0+17` a ete publie sur la page publique `/application` comme version courante. Son APK mesure `55 363 415` octets et porte le SHA-256 `e9d8eff678f3dc7aa199069d27ed24e6d965711feda71a83c1cd73637d4eefd7`.
- Le catalogue conserve cinq versions precedentes reellement disponibles : builds `13`, `12`, `11`, `10` et `9`. Le build `8` a ete retire du stockage serveur apres sauvegarde.
- L'archive de publication portait le SHA-256 `e94f5bfa2e89101ee67fd13b9b0fa7071d19270a29dd1ce55069e5c164f7440d`. La sauvegarde de retour arriere est `/tmp/exadtracking-before-mobile-build17-20260906-095900.tar.gz`, protegee en mode `0600`, SHA-256 `b9121aed583ee8cc8c41c85bdbd0f421590220325be6b58f95b0cc6c599ad340`.
- Verification production : `/up` et `/application` repondent en HTTP `200`, le build 17 est affiche sur la page et son telechargement partiel repond en HTTP `206` avec le type APK attendu. Apache et l'ecouteur GPS restent actifs, maintenance desactivee.
- Cette operation a publie uniquement l'APK et son catalogue. Le lot Web/API qui separe DOUT1 et DOUT2 reste prepare localement et n'a pas ete deploye par cette operation.

## 2026-09-06 - Deploiement Web/API des sorties DOUT1 et DOUT2 independantes
- Le Web et l'API exposent maintenant `Sortie #1` et `Sortie #2` separement. Chaque commande exige le numero de sortie et utilise `?` pour ne jamais modifier l'autre sortie ni sa temporisation.
- Les etats, commandes actives et confirmations sont suivis independamment pour chaque sortie. Le build Android `1.0.0+17`, deja publie, utilise ce nouveau contrat.
- Les controles de securite moteur ont ete synchronises dans Laravel et le listener : une activation attend toujours l'arret complet et un etat moteur explicitement arrete. Le listener decode le bit moteur du mot CAN P4 avant toute autorisation.
- Archive de deploiement SHA-256 `6de59456322e60609a9ccb3e0a97326b2dc04961395da9d1786f976174bc4e5f`. Sauvegarde : `/tmp/exadtracking-before-independent-outputs-web-20260906-101100.tar.gz`, SHA-256 `debac5d84bc14a336fcdefec1c16fcf2efec7d1669884b4f2ed5c2b9fc270394`, permissions `0600`.
- Une premiere finalisation s'est arretee avant reconstruction des caches car `node` n'etait pas dans le PATH non interactif de `root`. Le garde de maintenance a maintenu Laravel en ligne ; la finalisation a ensuite utilise le binaire Node exact du service et s'est terminee sans erreur.
- Les pages Carte et Traceurs utilisent le cache-busting `20260906-independent-outputs`, afin qu'aucun navigateur ne conserve l'ancien JavaScript a une seule sortie. Sauvegarde ciblee : `/tmp/exadtracking-before-independent-outputs-cache-20260906-101100.tar.gz`, SHA-256 `42bc784d17ad0068ea2fd250eba3fcae214fe24be8f28ae27045bbf1f3f8272c`.
- Verification : 38 tests Laravel avec 299 assertions, 10 tests Node, syntaxe PHP/JavaScript, trois routes de commande, empreintes locales/distantes, `/up` et `/application` en HTTP `200`, API sans jeton en `401`, Apache, Supervisor et `gps-tcp.service` actifs, maintenance inactive.
- Aucune migration, donnee metier, variable d'environnement ou commande vers un traceur n'a ete executee.
