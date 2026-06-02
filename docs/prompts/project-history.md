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
- Ajouter un bouton cloche dans la topbar superadmin, juste avant le mode sombre, avec compteur rouge des nouvelles alertes et mise à jour automatique lors des toasts live.
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
