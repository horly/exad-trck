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
