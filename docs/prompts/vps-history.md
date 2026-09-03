# Historique VPS - EXAD Tracking

Ce fichier trace les actions realisees sur le serveur de test VPS pour garder une reference claire de l'installation et du deploiement.

> Note securite : les mots de passe root, sudo, MySQL et phpMyAdmin ne doivent pas etre stockes en clair dans ce fichier.

## Serveur

- VPS cible : `109.199.102.172`
- Systeme detecte : Ubuntu 24.04.4 LTS (Noble Numbat)
- Utilisateur d'exploitation cree : `exad-tracking`
- L'utilisateur `exad-tracking` a ete ajoute au groupe `sudo`
- Le travail d'installation et de deploiement se fait avec `exad-tracking`, avec `sudo` seulement pour les actions systeme

## Serveur web

- Choix serveur web : Apache
- Nginx etait present/actif et occupait le port 80
- Nginx a ete arrete et desactive
- Apache a ete demarre avec succes
- Le domaine `exadtracking.app` pointe vers le VPS
- Dossier applicatif choisi : `/var/www/exadtracking.app`
- Racine web Laravel configuree : `/var/www/exadtracking.app/public`
- Correction appliquee sur le VirtualHost SSL : le `DocumentRoot` pointait vers `/var/www/exadtracking.app`, ce qui exposait l'index du projet
- Correction finale : le VirtualHost HTTPS pointe maintenant vers `/var/www/exadtracking.app/public`

## PHP

- Version PHP locale cible : PHP 8.2
- Decision : garder PHP 8.2 sur le VPS pour rester aligne avec l'environnement local
- Extensions Laravel prevues/installees :
  - `php8.2-cli`
  - `php8.2-common`
  - `php8.2-fpm`
  - `php8.2-curl`
  - `php8.2-xml`
  - `php8.2-mbstring`
  - `php8.2-mysql`
  - `php8.2-zip`
  - `php8.2-bcmath`
  - `php8.2-gd`
  - `php8.2-intl`
  - `php8.2-readline`
- PHP CLI a ete force sur PHP 8.2 via `update-alternatives`
- Les paquets PHP 8.2 ont ete marques en `hold` pour eviter un changement involontaire lors des `apt upgrade`

## MySQL et phpMyAdmin

- MySQL installe et active
- Base Laravel creee : `exad_tracking`
- Utilisateur MySQL applicatif : `exad_tracking_user`
- Droits accordes sur la base `exad_tracking`
- phpMyAdmin installe avec Apache
- Probleme rencontre : politique MySQL trop stricte pour le mot de passe phpMyAdmin pendant `dbconfig-common`
- Resolution : installation/configuration phpMyAdmin sans stocker les mots de passe dans l'historique
- phpMyAdmin accessible via le domaine/IP du serveur

## Composer

- Version Composer cible locale : 2.9.5
- Composer installe sur le VPS
- Installation Laravel lancee avec :

```bash
composer install --no-dev --optimize-autoloader
```

- Important : ne pas utiliser `composer update` sur le VPS pour conserver les versions verrouillees par `composer.lock`

## Node.js et npm

- Version Node.js locale cible : `v24.15.0`
- Version npm locale cible : `11.12.1`
- Node.js installe via `nvm` avec l'utilisateur `exad-tracking`
- Version active configuree :

```bash
nvm install 24.15.0
nvm use 24.15.0
nvm alias default 24.15.0
```

## Supervisor

- Supervisor installe et active
- Il servira a maintenir les processus applicatifs :
  - worker Laravel queue
  - Laravel Reverb
  - GPS listener local

## Git et deploiement

- Depot distant utilise : `git@github.com:horly/exad-trck.git`
- Cle SSH generee/autorisee pour permettre au VPS de cloner le depot
- Projet clone dans :

```bash
/var/www/exadtracking.app
```

- Branche de deploiement : `main`
- Etat apres clone : working tree clean

## Configuration Laravel VPS

- Fichier `.env` serveur configure pour :
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://exadtracking.app`
  - langue par defaut : anglais
  - base MySQL : `exad_tracking`
  - utilisateur MySQL : `exad_tracking_user`
  - Mapbox public token
  - Reverb
  - queue database
  - cache database
  - session database
  - GPS listener local
- `APP_KEY` genere avec :

```bash
php artisan key:generate
```

## Migrations et cache Laravel

- Les migrations ont ete executees en production avec :

```bash
php artisan migrate --force
```

- Les tables Laravel ont ete creees avec succes, dont :
  - users
  - cache
  - jobs
  - devices
  - positions
  - fleets
  - vehicles
  - alerts
  - tracker_events
- Lien storage cree avec :

```bash
php artisan storage:link
```

- Cache Laravel regenere :

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Permissions

- Proprietaire applicatif :

```bash
exad-tracking:www-data
```

- Permissions Laravel appliquees sur :
  - `/var/www/exadtracking.app/storage`
  - `/var/www/exadtracking.app/bootstrap/cache`

## Points restants

- Configurer Supervisor pour `queue:work`
- Configurer Supervisor pour `reverb:start`
- Configurer Supervisor pour le GPS listener
- Verifier HTTPS/Reverb cote navigateur
- Verifier la reception des alertes temps reel en production
- Configurer le port du GPS listener et les regles firewall associees

## Serveur GPS production - validation TCP reelle

- Dossier serveur GPS production retenu sur le VPS :

```bash
/var/www/exadtracking.app/gps-listener-server-prod
```

- Protocole principal retenu : TCP
- Port TCP principal : `5027`
- UDP conserve comme protocole secondaire, mais le test reel validant a ete fait en TCP
- Services systemd prevus/utilises :
  - `gps-tcp.service`
  - `gps-udp.service`
- Le port TCP `5027` a ete verifie depuis un poste externe avec succes :

```powershell
Test-NetConnection 109.199.102.172 -Port 5027
```

- Resultat attendu/obtenu : `TcpTestSucceeded : True`

### Correction environnement Node.js

- Probleme rencontre : le serveur Node.js ne recevait pas les variables MySQL depuis le `.env` Laravel.
- Symptome observe dans `gps-tcp-error.log` :

```text
Access denied for user ''@'localhost'
```

- Correction appliquee : creation d'un fichier d'environnement systemd dedie :

```bash
/etc/exad-gps-listener.env
```

- Ce fichier fournit au service GPS les variables necessaires :
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
  - `GPS_LISTENER_LARAVEL_PATH`
  - `GPS_LISTENER_CACHE_TTL`
- Le service `gps-tcp.service` charge ce fichier via :

```ini
EnvironmentFile=/etc/exad-gps-listener.env
```

### Correction handshake TCP Teltonika

- Probleme rencontre : le test IMEI bloquait sans reponse.
- Cause : le listener TCP devait bufferiser correctement les donnees TCP et gerer le handshake IMEI Teltonika.
- Correction appliquee dans :

```bash
/var/www/exadtracking.app/gps-listener-server-prod/src/listeners/tcp-listener.js
```

- Resultat obtenu avec un IMEI enregistre :

```bash
printf '\x00\x0F353691840797368' | nc -w 2 127.0.0.1 5027 | xxd
```

- Reponse attendue/obtenue :

```text
00000000: 01
```

### Correction ingestion Laravel

- Probleme rencontre : Laravel refusait le payload envoye par Node.js.
- Erreurs observees :

```text
The lat field is required.
The lng field is required.
The external voltage field must not be greater than 100.
The battery voltage field must not be greater than 100.
```

- Correction appliquee dans :

```bash
/var/www/exadtracking.app/gps-listener-server-prod/src/services/laravel-ingestor.js
```

- Ajustements :
  - `latitude` converti en `lat`
  - `longitude` converti en `lng`
  - `external_voltage` converti de millivolts vers volts
  - `battery_voltage` converti de millivolts vers volts
  - le `codec` est transmis dans le payload JSON pour mise a jour du champ existant `devices.codec`

### Test reel traceur

- Traceur reel teste : Teltonika FMB003
- IMEI reel teste :

```text
353201355315547
```

- Le traceur devait etre branche au port OBD du vehicule pour emettre correctement les donnees.
- Apres branchement OBD vehicule, le serveur a recu les connexions reelles.
- Logs observes :

```text
[TCP] connection from 169.159.210.4:51120
[TCP] IMEI received: 353201355315547
[TCP] IMEI accepted: 353201355315547
[TCP] 353201355315547 codec8_extended records=2 ACK=2
[TCP] 353201355315547 codec8_extended records=1 ACK=1
```

- Validation :
  - connexion TCP reelle fonctionnelle
  - IMEI reconnu depuis la base Laravel
  - Codec 8 Extended detecte et decode
  - ACK AVL envoye correctement au traceur
  - le serveur recoit des records reels du traceur
  - le flux production TCP est operationnel

### Etat final GPS TCP

- `gps-tcp.service` : operationnel
- Port `5027/tcp` : accessible depuis l'exterieur
- Traceur reel : connecte et accepte
- Codec : `codec8_extended`
- ACK : fonctionnel
- Prochaine etape technique :
  - verifier les positions creees en base
  - verifier la mise a jour du statut traceur dans l'interface
  - verifier l'affichage carte
  - verifier les alertes temps reel
  - stabiliser ensuite UDP et les futurs decodeurs EDT/generic

## 2026-07-09 - D�ploiement page Abonnements
- D�ploiement cibl� vers `/var/www/exadtracking.app` avec l'utilisateur `exad-tracking`.
- Premi�re archive ZIP refus�e fonctionnellement car les chemins avaient �t� aplatis � l'extraction ; correction via archive `tar.gz` conservant l'arborescence.
- Nettoyage cibl� des fichiers mal plac�s � la racine du projet distant.
- Ex�cution c�t� VPS : `composer dump-autoload --optimize`, `php artisan optimize:clear`, `php artisan migrate --force`, seeders `VehicleSubscriptionFeatureSeeder` et `VehicleSubscriptionPlanSeeder`, puis `php artisan optimize`.
- V�rification distante : routes `GET subscriptions` et `PATCH subscriptions` disponibles.

## 2026-07-10 - Production GPS : codec, OBD/CAN et diagnostic traceur
- Déploiement ciblé vers `/var/www/exadtracking.app` des fichiers Laravel liés à la télémétrie enrichie des traceurs.
- Exécution de la migration production ajoutant les champs OBD/CAN sur la table `devices`.
- Conservation du champ existant `devices.codec` pour enregistrer le codec réel reçu, sans ajouter de champ `last_codec`.
- Mise à jour du serveur d'écoute production dans `/var/www/exadtracking.app/gps-listener-server-prod`.
- Extension du décodeur Teltonika Codec 8 / Codec 8 Extended pour extraire les valeurs IO utiles : contact, mouvement, GSM, tensions, batterie, odomètre, heures moteur, OBD RPM, vitesse OBD, papillon, température moteur, tension module, charge moteur, erreurs, distance défaut, carburant CAN et kilométrage CAN.
- Mise à jour de l'ingestion Laravel pour envoyer `codec`, `obd`, `can`, `io`, `sensors`, `raw`, `odometer`, `engine_seconds` et `gps_time` à la commande `gps:ingest-position`.
- Vérification que les variables Google/Mapbox sont présentes côté production ; le reverse geocoding est maintenant appelé à l'ingestion Laravel pour les nouvelles positions lorsque l'adresse n'est pas fournie.
- Commandes exécutées côté VPS : `php artisan migrate --force`, `php artisan optimize:clear`, `php artisan optimize`, `node --check` sur le décodeur et l'ingestor, puis `systemctl restart gps-tcp.service`.
- État final vérifié : `gps-tcp.service` actif et réception continue de paquets réels `codec8_extended`.

## 2026-07-10 - Déploiement zoom carte et extraction OBD/CAN robuste
- Déploiement ciblé vers `/var/www/exadtracking.app` des fichiers : `public/js/google-map.js`, `resources/views/trackers/partials/details.blade.php`, `routes/console.php` et `docs/prompts/project-history.md`.
- Objectif : rapprocher légèrement le zoom de sélection carte, rendre la queue de trace plus transparente, supprimer les doublons Odomètre/Heures moteur et renforcer l'extraction OBD/CAN depuis les payloads bruts.
- Vérifications côté VPS : `php -l routes/console.php`, `php -l resources/views/trackers/partials/details.blade.php`, `node --check public/js/google-map.js`.
- Caches Laravel : `config:clear`, `view:clear`, `route:clear`, puis `config:cache` et `view:cache` exécutés avec succès.
- Note : `route:cache` a interrompu la session SSH et n'a pas généré de fichier de cache de routes ; les routes restent donc non cachées, ce qui est acceptable et plus sûr pour éviter un cache incomplet.

## 2026-07-10 - Correction mapping OBD/CAN production
- Connexion au VPS `109.199.102.172` avec l�utilisateur `exad-tracking`.
- V�rification du d�codeur Teltonika production : `/var/www/exadtracking.app/gps-listener-server-prod/src/protocols/teltonika/decoder.js`.
- Correction du mapping `engine_load_percent` pour lire prioritairement l�IO OBD `52` (valeur absolue de charge), avec fallback sur `31`.
- Red�marrage du service `gps-tcp.service` apr�s correction.
- �tat v�rifi� : `gps-tcp.service` actif.

## 2026-07-11 - Déploiement diagnostic traceur et OBD/CAN
- Déploiement ciblé vers `/var/www/exadtracking.app` des corrections Laravel liées au détail traceur.
- Fichiers concernés : modèle `Device`, vue détails traceur, traductions traceur, commande d'ingestion GPS, migration `last_obd_runtime_seconds` et historiques projet/VPS.
- Objectif : séparer proprement Diagnostic traceur et OBD/CAN, conserver le protocole côté diagnostic, et afficher les métriques OBD/CAN dans un bloc dédié inspiré de Navixy.
- Commandes prévues côté VPS : extraction ciblée, `php artisan migrate --force`, nettoyage des caches Laravel, puis cache config/vue.

## 2026-07-13 - Déploiement historique détaillé et replay des trajets
- Déploiement ciblé vers `/var/www/exadtracking.app` du service de trajets, des vues, traductions, styles et scripts Google Maps liés au replay.
- Ajout en production de la chronologie détaillée, de la sélection des trajets, des couleurs personnalisables et du replay jusqu’à `x300`.
- Sauvegarde préalable des fichiers remplacés dans `/tmp/exadtracking-before-trip-replay-20260713.tar.gz`.
- Vérifications distantes : syntaxe PHP valide, scripts JavaScript valides avec Node via NVM, aucune migration en attente.
- Caches Laravel reconstruits avec `optimize:clear`, `config:cache` et `view:cache`.
- Contrôle final : `https://exadtracking.app/login` répond en HTTP `200`, la route des trajets est présente et les assets `20260713-trip-replay` sont déployés.

## 2026-07-14 - Déploiement du panneau latéral des trajets
- Déploiement ciblé vers `/var/www/exadtracking.app` de la fermeture coordonnée des fenêtres véhicule/traceur et du nouveau panneau compact des trajets.
- Sauvegarde préalable des fichiers remplacés dans `/tmp/exadtracking-before-trip-panel-20260714.tar.gz`.
- Reconstruction complète des caches Laravel avec `php artisan optimize:clear` puis `php artisan optimize`.
- Vérification distante des marqueurs `20260714-trip-panel` et `closeSourceThenOpen`.
- Contrôle HTTP final : `https://exadtracking.app/css/dashboard.css?v=20260714-trip-panel` répond en `HTTP 200` et contient les styles du panneau.
- État vérifié : Laravel `12.61.0`, PHP `8.2.31`, environnement `production`, debug désactivé et maintenance inactive.

## 2026-07-14 - Déploiement de la version compacte du panneau des trajets
- Déploiement ciblé vers `/var/www/exadtracking.app` des styles et vues du panneau de trajets compact.
- Réduction de la largeur du panneau à `480px`, barre de lecture conservée en position visible, commandes de replay compactées et liste des trajets modernisée.
- Fichiers déployés : `public/css/dashboard.css`, vues carte/traceurs, résultat des trajets et historique projet.
- Sauvegarde préalable des fichiers remplacés dans `/tmp/exadtracking-before-trip-panel-compact-20260714.tar.gz`.
- Caches Laravel reconstruits avec `php artisan optimize:clear`, `php artisan config:cache` et `php artisan view:cache`.
- Vérifications distantes réussies : marqueur d’asset `20260714-trip-panel-compact`, styles du panneau, contrôle du replay et réponse `HTTP 200` de `https://exadtracking.app/login`.
- État final : Laravel `12.61.0`, PHP `8.2.31`, environnement `production`, debug désactivé et maintenance inactive.

## 2026-07-14 - Déploiement des commandes de replay iconographiques
- Déploiement ciblé vers `/var/www/exadtracking.app` des commandes compactes du panneau de trajets.
- Les actions lecture/pause, reprise et effacement utilisent désormais uniquement leurs icônes ; le sélecteur conserve uniquement les vitesses `x1`, `x3`, `x10`, `x30`, `x100` et `x300`.
- Suppression du chevron décoratif des cartes de trajet, toute la carte restant sélectionnable.
- Sauvegarde préalable des fichiers remplacés dans `/tmp/exadtracking-before-trip-icons-20260714.tar.gz`.
- Contrôles distants réussis : syntaxe des vues Blade, nettoyage des caches, reconstruction du cache de configuration et compilation des vues.
- Contrôle HTTP final réussi : page de connexion, CSS et JavaScript en `HTTP 200`, avec présence des marqueurs de la version `20260714-trip-controls-icons`.

## 2026-07-19 - Deploiement conducteurs et UID RFID/iButton/NFC
- Deploiement cible vers `/var/www/exadtracking.app` du socle Conducteurs/Departements, des routes associees, de la sidebar Flottes extensible et du modal details traceur enrichi.
- Ajout en production de la colonne `devices.last_driver_identifier_uid` pour memoriser le dernier UID conducteur recu depuis un traceur.
- Migrations production executees avec succes :
  - `2026_07_16_090000_create_departments_table`
  - `2026_07_16_090100_create_drivers_table`
  - `2026_07_16_090200_create_driver_identifiers_table`
  - `2026_07_16_090300_create_driver_vehicle_table`
  - `2026_07_16_090400_create_driver_sessions_table`
  - `2026_07_16_235226_add_profile_fields_to_drivers_table`
  - `2026_07_19_090000_add_last_driver_identifier_uid_to_devices_table`
- Mise a jour de `gps:ingest-position` pour enregistrer `last_driver_identifier_uid` et retourner `driver_identifier_uid` dans la reponse JSON d'ingestion.
- Mise a jour du serveur GPS production dans `/var/www/exadtracking.app/gps-listener-server-prod` :
  - le decodeur Teltonika extrait maintenant un UID conducteur depuis les IO RFID/iButton/NFC connus ;
  - l'ingestor Node transmet `driver_identifier_uid` a Laravel ;
  - les UID 8 octets des IO conducteur sont conserves en hexadecimal pour eviter les pertes de precision.
- Redemarrage de `gps-tcp.service` effectue avec succes ; etat verifie : `active`.
- Sauvegarde prealable ciblee des fichiers remplaces dans `/tmp/exadtracking-before-driver-identifier-20260719`.
- Verifications distantes reussies : syntaxe PHP sur le controleur, le modele, le service, `routes/console.php` et la vue details traceur ; `node --check` sur le decodeur et l'ingestor ; routes `drivers.*` et `trackers.details` disponibles ; migrations marquees `Ran`.
- Controle HTTP final : `https://exadtracking.app/login` repond en `HTTP 200`.

## 2026-07-19 - Deploiement correction liste des trajets
- Deploiement cible vers `/var/www/exadtracking.app` de `app/Services/DeviceTripService.php`.
- Correction de la segmentation des trajets : les arrets courts intermediaires ne ferment plus immediatement un trajet, afin d'eviter les trajets repetes dans la liste alors que la trace carte est correcte.
- Sauvegarde prealable du fichier remplace dans `/tmp/exadtracking-before-trip-list-20260719/app/Services/DeviceTripService.php`.
- Verifications distantes reussies : `php -l app/Services/DeviceTripService.php`, presence des marqueurs `STOP_SPLIT_MINUTES`, `pendingStops` et `stopDurationMinutes`, nettoyage des caches Laravel puis `config:cache`.
- Controle HTTP final : `https://exadtracking.app/login` repond en `HTTP 200`.

## 2026-07-19 - Deploiement sessions securisees, recherche d'adresse et geofence conducteur

- Deploiement cible vers `/var/www/exadtracking.app` des changements locaux en attente : modal details traceur reduit a 740 px, segmentation des trajets, deconnexion automatique et geofence conducteur.
- Session production configuree avec `SESSION_LIFETIME=30`, `SESSION_INACTIVITY_TIMEOUT=30` et `SESSION_EXPIRE_ON_CLOSE=true` ; l'option persistante « Se souvenir de moi » est neutralisee cote Fortify.
- Ajout de la recherche d'adresses reelles des conducteurs via Google avec repli Mapbox, et stockage des coordonnees du centre de zone.
- Migration production executee : `2026_07_19_013501_add_geofence_fields_to_drivers_and_driver_sessions_tables`.
- Le flux `gps:ingest-position` evalue maintenant le rayon du conducteur identifie et cree une alerte `geofence_exit` uniquement lors du passage hors zone ; le retour dans la zone rearme une sortie ulterieure.
- Sauvegarde ciblee creee dans `/tmp/exadtracking-before-geofence-20260719.tar.gz`.
- Verifications reussies : syntaxe PHP distante, route `drivers.addresses.search`, migration marquee `Ran`, caches config/vue reconstruits, application remise en ligne.
- Controle HTTP : connexion, CSS et scripts d'inactivite/recherche d'adresse en HTTP 200 ; message d'expiration et largeur du modal verifies.
- Test reel du geocodage production reussi : « Boulevard du 30 Juin, Kinshasa » retourne une adresse.
- Validation locale avant deploiement : 80 tests et 621 assertions.

## 2026-07-19 - Deploiement Entretien, Garages, selects recherchables et politique de vitesse

- Deploiement vers `/var/www/exadtracking.app` de l'ensemble des changements locaux en attente : pages Garages et Entretien, formulaires corporate, selects issus de la base recherchables, widgets Entretien, recherche d'adresse conducteur, alertes geofence et politique de vitesse par vehicule.
- Politique de vitesse branchee dans `gps:ingest-position` : alerte `overspeed` immediate lorsque la vitesse recue est strictement superieure a la limite du vehicule, sans tolerance ni duree de confirmation.
- Protection anti-duplication persistante par episode de depassement ; rearmement lorsque la vitesse revient a la limite ou en dessous.
- Migrations production executees avec succes :
  - `2026_07_19_020900_create_garages_table`
  - `2026_07_19_020900_create_maintenance_plans_table`
  - `2026_07_19_020901_create_maintenance_records_table`
  - `2026_07_19_020902_create_maintenance_documents_table`
  - `2026_07_19_100000_add_speed_policy_rule_id_to_vehicles_table`
  - `2026_07_19_100100_create_alert_rule_states_table`
- Autoload Composer optimise, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; signal de redemarrage gracieux envoye aux workers de queue.
- Incident sans impact applicatif : la variable Bash du nom de sauvegarde initiale a ete interpretee par PowerShell. Laravel n'est donc pas passe en maintenance et un fichier archive parasite nomme ` app` a ete cree a la racine distante. Son chemin absolu a ete verifie puis le fichier a ete supprime. Un instantane propre post-deploiement a ete cree dans `/tmp/exadtracking-deployed-maintenance-speed-20260719-042540.tar.gz`.
- Verifications finales : application en production, debug desactive, maintenance inactive, `gps-tcp.service` actif, routes Garages/Entretien/recherche d'adresse presentes et toutes les nouvelles migrations marquees `Ran`.
- Controles HTTP reussis en `200` : page de connexion, `dashboard.css?v=20260719-speed-policy` et `searchable-select.js?v=20260719-database-selects`, avec marqueurs attendus presents.
- Validation locale avant deploiement : 90 tests et 757 assertions.

## 2026-07-19 - Deploiement et activation de la console SSH Web

- Deploiement cible vers `/var/www/exadtracking.app` des onglets Logs/Console, du terminal Xterm, du controleur de tickets Laravel et de la passerelle Node.js SSH/WebSocket.
- Sauvegarde prealable des fichiers remplaces dans `/tmp/exadtracking-before-server-console-20260719-050543.tar.gz`.
- Dependances de la passerelle installees avec `npm ci --omit=dev` : audit sans vulnerabilite, 3 tests Node.js passes et syntaxe validee.
- Activation effectuee apres confirmation explicite du risque par l'utilisateur : le compte SSH autorise `exad-tracking` appartient au groupe `sudo`.
- Secret HMAC aleatoire genere directement sur le VPS et conserve dans `/etc/exad-server-console.env` avec permissions `0640`, proprietaire `root` et groupe `exad-tracking`. Aucun mot de passe SSH n'est stocke par l'application.
- Empreinte de la cle hote SSH locale configuree et verifiee par la passerelle ; origine autorisee limitee a `https://exadtracking.app`.
- Installation et activation de `exad-server-console.service` ; service actif, active au demarrage et lie uniquement a `127.0.0.1:5091`.
- Activation des modules Apache `proxy_http` et `proxy_wstunnel`, puis activation du proxy `/server-console/socket` et rechargement Apache avec syntaxe valide.
- Laravel production configure avec `SERVER_CONSOLE_ENABLED=true`, URL de passerelle locale, ticket de 30 secondes et utilisateur autorise `exad-tracking` ; caches de configuration et de vues reconstruits.
- Verifications finales : routes Logs/Console presentes, page de connexion et assets console en HTTP 200, negotiation WebSocket publique en HTTP 101, Apache et service console actifs.
- La session SSH se ferme au changement d'onglet ou de page, a la fermeture du navigateur, a la perte WebSocket et apres 30 minutes d'inactivite.

### Correction du refus de connexion de la console SSH Web

- Les tentatives superadmin atteignaient correctement la passerelle avec un ticket valide, mais SSH coupait au niveau `handshake` avant la verification du mot de passe.
- Cause identifiee : Node.js calculait l'empreinte SHA-256 ED25519 avec un suffixe Base64 `=`, tandis que la valeur OpenSSH configuree omettait ce remplissage. Les empreintes etaient identiques mais la comparaison textuelle stricte les considerait differentes.
- Correctif deploye dans `server-console-gateway/src/server.js` : algorithme hote force a `ssh-ed25519` et suppression du seul remplissage final Base64 avant comparaison.
- L'epingle de cle hote reste stricte ; aucun contournement ou acceptation automatique de cle n'a ete ajoute.
- Sauvegarde du fichier precedent : `/tmp/server-console-server-before-ed25519-20260719.js`.
- Validation reelle de bout en bout reussie avec un client ephemere : ticket HMAC, WebSocket, authentification SSH, ouverture PTY, execution de `whoami`, resultat `exad-tracking`, puis fermeture immediate de la session.
- Service `exad-server-console.service` verifie actif apres correction. Le client de test temporaire a ete supprime.

## 2026-07-19 - Deploiement des outils de confort de la console SSH

- Deploiement cible des interactions presse-papiers, du mode plein ecran et du correctif de cadrage inferieur du terminal Xterm.
- Clic droit sans selection : lecture du presse-papiers du navigateur et collage direct dans la session SSH active, y compris pour une invite de mot de passe `sudo`.
- Clic droit avec selection terminal : copie de la selection dans le presse-papiers.
- Ajout d'un bouton iconographique plein ecran avec bascule `expand/compress`, recalcul des dimensions PTY et retour du focus au terminal.
- Augmentation de la marge inferieure afin que la derniere ligne et le curseur restent visibles.
- Sauvegarde prealable : `/tmp/exadtracking-before-console-tools-20260719-130240.tar.gz`.
- Caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; syntaxe de l'asset JavaScript validee.
- Controles production : login, CSS et JavaScript en HTTP 200, marqueurs `contextmenu` et `server-console-panel:fullscreen` presents, service `exad-server-console.service` actif.
- Validation locale avant deploiement : 93 tests Laravel avec 781 assertions et 3 tests Node.js passes.

## 2026-07-19 - Deploiement de la connexion corporate et du rappel utilisateur

- Deploiement cible de la nouvelle page de connexion, du visuel flotte `login-fleet-corporate.png` et du comportement Fortify `Se souvenir de moi`.
- Nouveau rendu production : scene routiere de flotte plein cadre sur la zone produit et panneau d'authentification blanc pleine hauteur, avec responsive mobile sans debordement horizontal.
- Le formulaire transmet maintenant `remember=1` uniquement lorsque la case est cochee. Fortify conserve les controles de compte actif et le rate limiting, puis demande a Laravel de creer le cookie de rappel.
- Sans selection de la case, aucun cookie de rappel persistant n'est emis. La deconnexion automatique apres 30 minutes d'inactivite d'une session ouverte reste active.
- Sauvegarde prealable : `/tmp/exadtracking-before-corporate-login-20260719-133238.tar.gz`.
- Syntaxe PHP distante validee sur le fournisseur Fortify, la vue et les traductions ; caches Laravel nettoyes puis caches de configuration et de vues reconstruits.
- Controles publics reussis : page de connexion et CSS en HTTP 200, nouvelle image en HTTP 200 avec 1 955 045 octets, marqueurs `remember` et `login-fleet-corporate.png` presents, Apache actif.
- Validation locale : rendu Chrome bureau 1920x900, viewport mobile 390 px sans debordement, suite complete verte avec 93 tests et 786 assertions.

## 2026-07-19 - Restauration du design de connexion d'origine

- Restauration ciblee de l'ecran de connexion historique : ancien fond `login-vehicle-bg.png`, carte centree, police monospace, textes, indicateurs et dimensions d'origine.
- Conservation de la seule evolution fonctionnelle `Se souvenir de moi` : le formulaire transmet `remember=1` lorsqu'elle est cochee et Fortify peut creer le cookie de rappel.
- Fichiers deployes : `app/Providers/FortifyServiceProvider.php`, `public/css/auth-login.css` et `resources/views/auth/login.blade.php`.
- Sauvegarde prealable : `/tmp/exadtracking-before-login-restore-20260719-140916.tar.gz`.
- Premier passage interrompu sans indisponibilite persistante : `unzip` a retourne un avertissement sur les separateurs Windows apres extraction. Le garde-fou a immediatement remis Laravel en ligne ; les fichiers extraits ont ensuite ete verifies, leurs permissions corrigees et le deploiement finalise.
- Syntaxe PHP valide, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; Apache actif et maintenance inactive.
- Controles publics reussis en HTTP 200 : page de connexion avec `remember-option` et CSS avec le marqueur du fond historique `login-vehicle-bg.png`.
- Validation locale avant deploiement : 93 tests Laravel passes avec 786 assertions.

## 2026-07-19 - Deploiement de la connexion bleu nuit

- Deploiement cible de la nouvelle direction visuelle de la page de connexion : panneau flotte bleu nuit a gauche et zone d'authentification claire sans carte flottante a droite.
- Repartition bureau configuree a `62%` pour le visuel et `38%` pour la connexion ; formulaire limite a 390 px, champs de 42 px et bouton de 44 px.
- Ajout du fond original `public/images/login-fleet-night.png`, recompose afin de conserver les six vehicules entierement visibles et une zone sombre sans details concurrents sous les textes.
- Conservation de la police, des textes historiques, des quatre benefices, des quatre indicateurs et de l'option `Se souvenir de moi`.
- Remplacement du cadenas encadre par un indicateur bouclier-cadenas bleu sans boite decorative.
- Fichiers deployes : `public/css/auth-login.css`, `public/images/login-fleet-night.png` et `resources/views/auth/login.blade.php`.
- Sauvegarde prealable : `/tmp/exadtracking-before-login-night-20260719-150609.tar.gz`.
- Syntaxe Blade valide, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; Apache actif et maintenance inactive.
- Controles publics reussis en HTTP 200 pour la page, le CSS versionne `20260719-night-fleet` et l'image de fond.
- Validation locale avant deploiement : 93 tests Laravel passes avec 788 assertions ; rendus verifies en 1920x918, 1257x710 et 500x900.

## 2026-07-19 - Deploiement de la validation contextuelle des modales

- Deploiement cible des erreurs rattachees aux champs dans les formulaires `Planifier une revision`, `Cloturer une revision` et `Garage`.
- Suppression des bandeaux d'erreur globaux des pages Entretien et Garages ; chaque champ invalide affiche maintenant son etat rouge et son message immediatement dessous.
- Conservation des anciennes valeurs apres validation et reouverture automatique de la modale concernee.
- Separation des erreurs de cloture dans le sac Laravel `completion` afin qu'elles ne puissent pas ouvrir la modale de planification.
- Sauvegarde prealable : `/tmp/exadtracking-before-field-validation-20260719-174701.tar.gz`.
- Syntaxe PHP validee, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; environnement `production`, debug desactive et maintenance inactive.
- Verification distante des marqueurs des trois modales et de l'absence de `alert-danger` dans les deux vues concernees.
- Controle public final : `https://exadtracking.app/login` repond en HTTP 200.
- Validation locale avant deploiement : 96 tests Laravel passes avec 813 assertions.

## 2026-07-19 - Masquage du visuel de connexion sur tablette et mobile

- Deploiement cible de `public/css/auth-login.css` et `resources/views/auth/login.blade.php`.
- Le panneau bleu nuit et l'image de flotte sont desormais masques sous `992 px` ; la zone de connexion claire occupe toute la largeur et toute la hauteur utile.
- Le prechargement de `login-fleet-night.png` est limite par `media="(min-width: 992px)"` afin d'eviter le telechargement inutile du grand visuel sur tablette et mobile.
- Sauvegarde prealable : `/tmp/exadtracking-before-login-responsive-20260719-193124.tar.gz`.
- Syntaxe Blade validee, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; application remise en ligne.
- Controles publics reussis en HTTP 200 pour la connexion et le CSS versionne `20260719-login-responsive` ; breakpoint tablette, prechargement bureau et masquage du panneau verifies dans les contenus servis.
- Validation locale avant deploiement : 97 tests Laravel passes avec 818 assertions.

## 2026-07-19 - Deploiement du Profil securise et de la Personnalisation globale

- Deploiement cible vers `/var/www/exadtracking.app` de la nouvelle page Profil, du recadrage Cropper.js, de l'authentification 2FA Fortify et du module complet de personnalisation de l'application.
- Sauvegarde prealable des sources remplacees : `/tmp/exadtracking-before-profile-customization-20260719-231801.tar.gz`.
- Ajout en production de la photo de profil recadree, de sa propagation dans la topbar et la liste des utilisateurs, de la modification separee des informations personnelles, de l'e-mail et du mot de passe interactif.
- Activation du choix utilisateur pour le TOTP : desactive par defaut, activation protegee par mot de passe et code a six chiffres, codes de recuperation, regeneration et desactivation protegees.
- Ajout de la personnalisation globale : nom, nom court, site web, logo, favicon, sept couleurs de theme et coordonnees de support. Les champs slogan, description et texte de copyright sont volontairement absents.
- L'identite et la palette sont propagees a la connexion, la sidebar, les titres, les rapports, les boutons, les avatars, les accents et le favicon.
- Migrations production executees avec succes :
  - `2026_07_19_200000_add_profile_photo_path_to_users_table`
  - `2026_07_19_210000_create_application_settings_table`
- Le lien `public/storage` a ete verifie actif. Autoload Composer optimise, caches Laravel nettoyes puis caches de configuration et de vues reconstruits ; workers de queue signales pour redemarrage.
- Premier passage interrompu au controle `node --check` car Node.js n'est pas expose dans le `PATH` non interactif root. Le `trap` de securite a remis Laravel en ligne automatiquement ; la reprise a finalise autoload, migrations, caches et services sans nouvelle extraction.
- Verifications finales : application hors maintenance, routes Profil/2FA/Personnalisation presentes, Apache, `gps-tcp.service` et `exad-server-console.service` actifs.
- Controles publics HTTPS reussis en `200` pour la page de connexion, `customization.css`, `customization.js` et `cropper.min.js`.
- Validation locale avant deploiement : 112 tests Laravel passes avec 963 assertions.

## 2026-07-19 - Diagnostic en lecture seule du traceur 353201355315547

- Consultation des journaux `gps-tcp.log` et `gps-tcp-error.log`, de l'etat du traceur en base et du code du listener TCP Teltonika, sans modification du VPS.
- L'IMEI est accepte par le listener, mais les derniers relevés recus ont ete refuses par la validation Laravel avec l'erreur `angle must not be greater than 359`.
- Dernier etat constate en base : `offline`, dernier signal enregistre le 19 juillet 2026 a 18:28:19 UTC et dernier cap valide `2` degres.
- Une capture reseau courte confirme que le listener recoit et decode correctement les paquets Codec8 Extended d'autres traceurs. Les connexions recentes de l'IMEI concerne se limitent actuellement a l'identification IMEI suivie d'une fermeture.
- Aucun fichier, service, cache ou enregistrement du VPS n'a ete modifie. Le correctif de tolerance des angles a uniquement ete prepare et valide en local.

## 2026-07-20 - Correction des angles GPS et reprise du traceur 353201355315547

- Deploiement cible de `routes/console.php` afin qu'un angle `360` soit normalise a `0` et qu'une valeur entiere hors plage conserve le dernier cap valide au lieu de faire rejeter tout le relevé GPS.
- Sauvegarde du fichier precedent : `/tmp/exadtracking-before-gps-angle-20260720-105618.php`.
- Syntaxe PHP validee, caches Laravel nettoyes puis caches de configuration et de vues reconstruits.
- `gps-tcp.service` redemarre et verifie actif ; application remise hors maintenance et Apache verifie actif.
- Apres redemarrage, l'IMEI `353201355315547` a repris l'envoi continu de paquets Codec8 Extended avec `records=2 ACK=2`.
- Le traceur est repasse `online`; `last_seen_at` avance de nouveau et les anciennes positions sont progressivement ingerees dans l'ordre `Oldest`.
- Aucun nouvel octet n'a ete ajoute a `gps-tcp-error.log` durant une fenetre de controle de 35 secondes.
- Controle public final : `https://exadtracking.app/login` repond en HTTP 200.
- Validation locale avant deploiement : 112 tests Laravel passes avec 968 assertions.

## 2026-07-20 - Constat du rejeu historique apres reprise GPS

- Controle en lecture seule apres retablissement : le traceur reste `online` et continue d'envoyer des lots acquittes, mais les archives reçues remplacent encore temporairement son etat de carte.
- Une regression reelle de `last_position_at` de 21:03 UTC vers 19:38 UTC a ete observee pendant l'arrivee de lots hors ordre, avec une vitesse historique de 28 km/h alors que le vehicule etait stationne.
- Aucun changement supplementaire n'a ete applique au VPS pendant ce constat.
- Un garde-fou a ete prepare et teste localement pour stocker les archives sans modifier l'etat live ni declencher d'alertes temps reel ; son deploiement attend une autorisation explicite.

### Nouvelle interruption sur une altitude sentinelle

- Le traceur est repasse `offline` apres son dernier ACK a 10:51:18 UTC, tandis que le listener et Apache sont restes actifs.
- Les nouvelles tentatives ont ete refusees par Laravel avec `altitude must not be greater than 10000`, puis le traceur a recommence les connexions IMEI sans ACK de donnees.
- Aucun changement VPS n'a ete effectue pendant ce diagnostic.
- La tolerance correspondante a ete ajoutee et validee localement avec les autres sentinelles GPS ; son deploiement attend une autorisation explicite.

## 2026-07-20 - Deploiement du rattrapage GPS protege

- Deploiement cible de `routes/console.php` avec deux protections : tolerance des sentinelles de telemetrie et separation entre archives GPS et etat live.
- Sauvegarde du fichier precedent : `/tmp/exadtracking-before-gps-replay-20260720-121350.php`.
- Syntaxe PHP validee, caches Laravel reconstruits, `gps-tcp.service` redemarre et verifie actif.
- Le traceur `353201355315547` est repasse `online` et envoie continuellement des lots Codec8 Extended acquittes avec `records=2 ACK=2`.
- Le fichier `gps-tcp-error.log` n'a pas grandi pendant la fenetre de verification post-deploiement.
- Les archives continuent d'etre inserees dans `positions`, mais ne modifient plus `last_position_at`, les coordonnees, la vitesse ou le mouvement live tant qu'elles sont anciennes ou hors ordre.
- Controle public final : `https://exadtracking.app/login` repond en HTTP 200.
- Validation locale avant deploiement : 113 tests Laravel passes avec 982 assertions.

## 2026-07-21 - Logo interne prepare localement

- Un reglage de logo interne distinct a ete ajoute localement pour la sidebar, avec migration, upload securise, apercu et tests de regression.
- Cette evolution n'est pas encore deployee : aucun fichier, service, cache ou schema du VPS n'a ete modifie.
- Validation locale : 114 tests Laravel passes avec 991 assertions.

## 2026-07-21 - Espace client prepare localement

- L'espace client par flotte, les permissions utilisateur, le dashboard cloisonne et la gestion des utilisateurs simples par leur admin ont ete implementes uniquement dans le projet local.
- Une migration locale ajoute l'affectation directe des utilisateurs et des garages a une flotte, ainsi que la tracabilite du compte createur.
- Les routes de creation des flottes, vehicules, traceurs, conducteurs et departements restent reservees au superadmin ; les garages, entretiens, rapports et la carte utilisent des permissions client explicites.
- La suite complete locale passe avec 120 tests et 1037 assertions.
- Aucun fichier, schema, service ou cache du VPS n'a ete modifie. Le deploiement attend une autorisation explicite.

### Masquage local des informations techniques des traceurs

- Le projet local reserve desormais le menu et les routes techniques des traceurs au superadmin.
- Le dashboard, la liste des vehicules, la carte, les evenements, les alertes et les rapports client ont ete adaptes pour fonctionner avec les vehicules de la flotte sans exposer le nom, le modele, l'IMEI ou l'identifiant interne des traceurs.
- Une route de trajets basee sur le vehicule remplace l'URL contenant l'identifiant du traceur cote client, avec controle serveur de la flotte visible.
- Les filtres de flotte et de traceur envoyes manuellement par un client sont ignores ou refuses selon l'operation ; les exports HTML, CSV et PDF appliquent la meme separation.
- Validation locale : 122 tests Laravel passes avec 1085 assertions, vues Blade compilees et scripts de carte valides.
- Aucun deploiement n'a ete lance et aucun fichier, schema, service ou cache du VPS n'a ete modifie.

### Correctif local de disposition du dashboard client

- L'ordre des sections du dashboard client a ete corrige localement afin que les alertes et actions rapides ne precedent plus l'en-tete et les indicateurs de flotte.
- Validation locale : vues Blade compilees et 123 tests Laravel passes avec 1091 assertions.
- Aucun fichier, service, cache ou schema du VPS n'a ete modifie.

### Correctif local de reconnexion apres expiration de session

- La recuperation des sessions expirees a ete corrigee localement pour ne plus afficher la page Laravel `419 PAGE EXPIRED` lors d'une nouvelle tentative de connexion.
- Les pages d'authentification et l'endpoint de renouvellement CSRF interdisent leur mise en cache ; le formulaire obtient un jeton frais avant chaque connexion.
- Une erreur 419 de navigateur redirige desormais vers une page de connexion neuve avec un message explicite, sans modifier le comportement des appels JSON.
- Validation locale : syntaxe PHP et JavaScript valide, vues Blade compilees et 125 tests Laravel passes avec 1103 assertions.
- Aucun fichier, service, cache ou schema du VPS n'a ete modifie. Le deploiement attend une autorisation explicite.

### Modal de details du traceur client prepare localement

- Le modal de details du traceur est maintenant accessible depuis la carte client au moyen d'une route basee sur le vehicule et limitee a la flotte visible.
- La reponse client masque le modele, l'IMEI, le nom technique et toute URL contenant l'identifiant interne du traceur ; le superadmin conserve son affichage complet.
- Validation locale : syntaxe PHP et JavaScript valide, vues Blade compilees et 125 tests Laravel passes avec 1115 assertions.
- Aucun fichier, service, cache ou schema du VPS n'a ete modifie. Le deploiement attend une autorisation explicite.

### Espace client complet en lecture seule prepare localement

- La colonne Flotte de l'espace superadmin permet maintenant d'entrer dans tout l'espace client de la flotte selectionnee, et pas uniquement dans son tableau de bord.
- Le menu client complet est disponible et les utilisateurs, vehicules, positions, alertes, evenements, garages, entretiens et rapports restent limites a cette flotte.
- Le contexte est applique temporairement a chaque requete sans modifier le compte superadmin. Une indication persistante `Lecture seule` permet de quitter proprement l'espace client et de revenir aux flottes.
- Toutes les requetes d'ecriture sont bloquees cote serveur avec une reponse 403 et les controles de modification sont masques dans l'interface.
- Le centrage des icones du bloc `Lecture seule` dans la sidebar a ete corrige localement.
- Validation locale : vues Blade compilees et 126 tests Laravel passes avec 1150 assertions.
- Cette version locale a ete deployee avec succes le 21 juillet 2026 apres autorisation explicite.

## 2026-07-21 - Deploiement de l'espace client cloisonne et de l'apercu en lecture seule

- Deploiement vers `/var/www/exadtracking.app` de l'espace client complet par flotte, des permissions client, du dashboard dedie, du masquage des informations techniques des traceurs, du correctif de reconnexion 419, du modal de details client, du logo interne configurable et de l'acces superadmin en lecture seule.
- La colonne Flotte permet au superadmin d'entrer dans toutes les pages client de la flotte choisie. Les requetes d'ecriture sont bloquees cote serveur avec une reponse 403 et les actions de modification sont masquees dans l'interface.
- Migrations production executees avec succes :
  - `2026_07_21_000000_add_internal_logo_path_to_application_settings_table`
  - `2026_07_21_010000_add_client_fleet_access_fields`
- Sauvegarde prealable des fichiers remplaces : `/tmp/exadtracking-before-client-space-20260721-201730.tar.gz`.
- Une premiere generation de la liste de sauvegarde a echoue avant la mise en maintenance et avant l'extraction a cause d'un saut de ligne mal echappe. Le garde-fou a conserve Laravel en ligne et la relance corrigee a termine le deploiement.
- Les modes de fichiers trop permissifs herites de l'archive Windows ont ete immediatement corriges : fichiers en `0644`, dossiers en `0755`, proprietaire `exad-tracking` et groupe `www-data`.
- Autoload Composer optimise, caches Laravel nettoyes puis reconstruits, migrations appliquees et signal de redemarrage envoye aux workers de queue.
- Verifications finales : environnement production, debug desactive, maintenance inactive, routes `fleets.dashboard` et `client-preview.exit` presentes, lien `public/storage` actif, Apache, `gps-tcp.service` et `exad-server-console.service` actifs.
- Controles HTTPS reussis en `200` pour la connexion et `dashboard.css?v=20260721-client-preview-icons`, avec les marqueurs CSS du mode lecture seule et du centrage des icones.
- Validation locale avant deploiement : 126 tests Laravel passes avec 1150 assertions.

## 2026-07-22 - API mobile privee preparee localement

- Une premiere API privee pour l'application mobile officielle a ete implementee localement avec Laravel Sanctum, des paires acces/rafraichissement par appareil, rotation, revocation et challenge 2FA Fortify.
- Les endpoints mobiles versionnes couvrent le bootstrap, le profil, le dashboard, les vehicules, la carte, les alertes et les evenements, avec cloisonnement par flotte et masquage des donnees techniques des traceurs.
- Deux migrations ajoutant `personal_access_tokens` et `mobile_sessions` ont ete executees avec succes sur la base locale. Aucune migration n'a ete executee sur le VPS.
- La future API publique OAuth2 pour les integrations clientes et la page de gestion des identifiants ne font pas partie de cette version.
- Validation locale : 134 tests Laravel passes avec 1232 assertions et 13 routes mobiles chargees.
- L'audit Composer signale 13 avis de securite dans 6 dependances existantes ; une mise a niveau dediee est recommandee avant l'ouverture d'une API publique.
- Aucun fichier, paquet, schema, service ou cache du VPS n'a ete modifie. Aucun deploiement n'a ete lance.

## 2026-07-22 - Projet Flutter cree localement

- Le projet `D:\App\Codex\exad-tracking-mobile` a ete cree localement comme dossier frere de l'application Laravel, avec les plateformes Android et iOS.
- Le template initial passe `flutter analyze` sans anomalie.
- L'application mobile n'est encore connectee a aucune API et aucun changement n'a ete apporte au VPS.
- Le dossier mobile possede maintenant son propre depot Git initialise sur la branche `main`. Aucun commit et aucun depot distant n'ont encore ete configures.

## 2026-07-22 - Interface mobile developpee localement

- La premiere interface EXAD Tracking Mobile a ete implementee dans le projet frere local avec connexion, 2FA, stockage securise des jetons, dashboard client, vehicules, alertes, carte et espace compte.
- L'application de developpement pointe uniquement vers l'API Laravel locale via `10.0.2.2:8000` et a ete verifiee sur l'emulateur Android local.
- Validation locale : analyse Flutter sans anomalie et 2 tests d'interface passes.
- Aucun fichier, schema, paquet, service, variable d'environnement ou cache du VPS n'a ete modifie. L'API mobile privee et ses migrations ne sont toujours pas deployees en production.

### Refonte mobile locale sans impact VPS

- La connexion Flutter a ete refondue dans un style corporate EXAD et l'application prend maintenant en charge la langue du telephone avec une preference persistante Francais/English.
- Les interfaces mobiles client et superadmin sont separees : dashboard de flotte pour le client et console de supervision globale pour le superadmin.
- Le contrat local du dashboard mobile fournit au superadmin une repartition des flottes agregee en base, independante de la pagination des vehicules.
- Validation locale : analyse Flutter sans anomalie, 4 tests Flutter passes et 135 tests Laravel passes avec 1242 assertions.
- Aucun fichier, service, paquet, schema, migration, variable ou cache du VPS n'a ete modifie. Aucun deploiement n'a ete execute.

### APK mobile de test local

- Un APK debug pointant vers l'API du PC sur le reseau local a ete genere pour un essai sur telephone Android reel.
- Les ports `8000` pour l'API et `8090` pour le telechargement de l'APK sont ouverts uniquement par des processus locaux sur le PC de developpement.
- Aucun acces, fichier, service, pare-feu, schema ou cache du VPS n'a ete modifie.

### Google Maps et trajets mobiles prepares localement
- Le projet Flutter local utilise maintenant Google Maps, une liste de vehicules repliable, la geolocalisation a la demande, les details operationnels, les evenements par vehicule et l'affichage des trajets sur la carte.
- Une route mobile privee de trajets par vehicule a ete ajoutee localement au projet Laravel avec cloisonnement par flotte et masquage des donnees techniques du traceur.
- La cle Android Google Maps n'est pas stockee dans le depot et devra etre configuree localement avec des restrictions package/SHA-1 avant le prochain essai cartographique reel.
- Validation locale : APK debug compile, analyse Flutter sans anomalie, 4 tests Flutter passes et 136 tests Laravel passes avec 1250 assertions.
- Aucun fichier, schema, migration, paquet, service, variable, cache ou pare-feu du VPS n'a ete modifie. Aucun deploiement n'a ete execute.

#### Details operationnels mobiles prepares localement
- Le modal mobile reprend desormais les memes familles d'informations que le modal web : emplacement, conducteur, alimentation, GSM, diagnostic, OBD/CAN et derniers evenements.
- Une route privee locale de details par vehicule fournit ces donnees avec cloisonnement par flotte, sans identite technique du traceur.
- La carte vide sur l'emulateur provient de l'absence locale de `MAPS_API_KEY`; aucune configuration Google Maps n'a ete ajoutee au VPS.
- Validation locale : APK debug compile, 5 tests Flutter passes et 137 tests Laravel passes avec 1263 assertions.
- Aucun fichier, service, schema, variable ou cache du VPS n'a ete modifie et aucun deploiement n'a ete execute.

#### Google Maps Android valide localement
- Une cle Google Maps Android restreinte au SDK, au package mobile et au certificat debug a ete configuree uniquement dans le fichier local non versionne du projet Flutter.
- L'APK debug a ete reconstruit et la carte Google Maps a ete controlee sur l'emulateur : tuiles, marqueurs de vehicules et recherche sont operationnels sans erreur d'autorisation.
- Aucune cle n'a ete copiee sur le VPS. Aucun fichier, service, schema, variable ou cache du serveur n'a ete modifie et aucun deploiement n'a ete execute.

#### Suivi cartographique mobile live prepare localement
- Les regles de mouvement et de trace GPS ont ete centralisees localement pour garantir le meme comportement sur les cartes web et mobile.
- L'API mobile locale fournit les etats mouvement, stationnement et moteur allume a l'arret ainsi qu'une trace GPS courte et continue. Flutter actualise ces donnees toutes les 10 secondes et anime les marqueurs sur 5 secondes uniquement lorsque la carte est active.
- Le rendu Google Maps, les compteurs, filtres, marqueurs, recentrage et actions vehicule ont ete valides sur l'emulateur Android.
- Validation locale complete : 6 tests Flutter, 137 tests Laravel et 1268 assertions passent. Aucun fichier, service, schema, variable ou cache du VPS n'a ete modifie et aucun deploiement n'a ete execute.

## 2026-07-22 - Deploiement de l'API mobile privee et du service cartographique partage
- Deploiement cible vers `/var/www/exadtracking.app` de 33 fichiers d'execution Laravel : API mobile versionnee, authentification Sanctum, sessions mobiles, ressources vehicule/alerte/evenement, details, trajets et service commun de mouvement cartographique.
- Archive de deploiement verifiee avant extraction avec SHA-256 `0dd6da56194fa15664f6afb2be8930ecd861c8d0284f819a2d2e92d4e05c046f`.
- Sauvegarde prealable des fichiers remplaces : `/tmp/exadtracking-before-mobile-api-live-map-20260722-134157.tar.gz`.
- `laravel/sanctum` v4.3.3 installe depuis le lock Composer. Les migrations suivantes ont ete appliquees en lot 11 :
  - `2026_07_22_000000_create_personal_access_tokens_table`
  - `2026_07_22_001000_create_mobile_sessions_table`
- Autoload Composer optimise, caches Laravel nettoyes puis reconstruits, migrations appliquees et redemarrage des workers de queue signale. Les 15 routes sous `api/v1/mobile` sont presentes.
- Verifications HTTPS : `/login` en 200, `/up` en 200, `/api/v1/mobile/bootstrap` en 401 JSON sans jeton et `/api/v1/mobile/auth/login` en 422 JSON pour une requete vide.
- Production confirmee avec debug desactive, maintenance inactive, permissions `0644` sur les fichiers deployes et `0775` sur `bootstrap/cache`. Apache, `gps-tcp.service` et `exad-server-console.service` sont actifs.
- Aucun nouvel evenement de niveau erreur dans les services pendant les dix minutes entourant le deploiement et aucune nouvelle erreur Laravel detectee. L'avertissement Composer sur la propriete Git du repertoire est non bloquant et n'affecte ni l'autoload ni l'application.
- Les archives et scripts temporaires ont ete supprimes du VPS apres validation ; seule la sauvegarde de retour arriere est conservee. Aucun APK, code Flutter ou secret Google Maps n'a ete deploye.

#### Barre de navigation mobile modifiee localement
- La barre de navigation inferieure et la zone de navigation systeme Android ont ete alignees localement sur les couleurs du theme EXAD.
- Validation locale : analyse Flutter sans anomalie et 6 tests Flutter passes.
- Aucun fichier, service, schema, variable ou cache du VPS n'a ete modifie et aucun deploiement n'a ete execute.

#### Application mobile reliee a l'API de production
- Le build mobile local utilise maintenant par defaut `https://exadtracking.app/api/v1/mobile` afin de lire les positions reelles deja recues par le serveur de production.
- Le controle HTTPS de l'endpoint mobile confirme une reponse JSON `401` sans jeton, conforme a la protection attendue de l'API privee.
- Le nom natif et les icones Android/iOS ont ete modifies uniquement dans le projet Flutter local. Aucun APK, asset mobile ou secret n'a ete copie sur le VPS.
- Aucun fichier, service, schema, migration, variable ou cache du VPS n'a ete modifie et aucun deploiement n'a ete execute.

#### Camera et marqueurs mobiles modifies localement
- La camera Flutter s'ouvre desormais au niveau rue sans cadrage global automatique et les marqueurs distinguent mouvement, parking, arret moteur allume, en ligne, hors ligne, inactif et maintenance.
- La modification concerne uniquement le rendu du client Flutter et reutilise les etats deja fournis par l'API de production.
- Validation locale : analyse Flutter sans anomalie, 6 tests Flutter passes et APK debug reinstalle sur l'emulateur.
- Aucun fichier, service, schema, migration, variable ou cache du VPS n'a ete modifie et aucun deploiement n'a ete execute.

#### Cloisonnement des details traceur prepare localement
- L'API Laravel locale fournit l'identite technique du traceur uniquement au superadmin et continue de la supprimer entierement pour les comptes clients.
- Le client Flutter local sait afficher le nom, l'identifiant interne, l'IMEI, la marque et le modele uniquement lorsque ce bloc superadmin est present.
- La carte d'identite mobile a ete alignee sur le modal web : immatriculation dans le titre, modele combine marque/modele, IMEI presente sous le libelle `ID`, flotte et pastille de statut. Les donnees techniques restent conditionnees au bloc superadmin.
- Validation locale : 12 tests API mobiles et 138 tests Laravel passent, ainsi que l'analyse et les 6 tests Flutter.
- Aucun fichier, service, schema, migration, variable ou cache du VPS n'a ete modifie et aucun deploiement n'a ete execute.

## 2026-07-22 - Deploiement du cloisonnement des details traceur mobiles
- Deploiement cible vers `/var/www/exadtracking.app` de `app/Http/Controllers/Api/V1/MobileVehicleDetailController.php` et `app/Services/MobileVehicleDetailService.php`.
- Archive Linux controlee avant extraction avec SHA-256 `727f08c0fd88df543c4372fefd0e0decf6e2f628e99223af433f4a02e8ce855a`.
- Sauvegarde de retour arriere conservee dans `/tmp/exadtracking-before-tracker-details-20260722-152058.tar.gz`.
- Les caches Laravel ont ete nettoyes puis reconstruits, les workers de queue signales et Apache recharge sans interruption de maintenance.
- Les fichiers deployes correspondent aux SHA-256 locaux, passent la verification syntaxique PHP et conservent les permissions `0644` avec le proprietaire `exad-tracking:www-data`.
- Verification production : `/login` et `/up` en `200`, API mobile protegee en `401` sans jeton, debug desactive, maintenance inactive, Apache, GPS TCP et console serveur actifs.
- Verification fonctionnelle sur l'application mobile connectee a la production : le superadmin recoit et affiche le modele et l'IMEI du traceur. Le masquage client reste couvert par les tests API.
- Aucune migration, modification de schema, variable d'environnement ou publication d'APK n'a ete effectuee.

## 2026-07-22 - Diagnostic production des trajets, correctif local non deploye
- Une analyse strictement en lecture seule des positions du traceur `353201355315547` a confirme que le serveur recoit des positions a vitesse nulle et coordonnees stables dont le drapeau `movement` reste pourtant actif.
- Cette incoherence explique la fusion de plusieurs trajets separes par des stationnements dans la liste EXAD Tracking, alors que Navixy les presente individuellement.
- Le correctif local utilise la vitesse comme signal prioritaire, separe les stationnements d'au moins cinq minutes, ordonne les donnees par heure GPS et filtre les micro-deplacements de parking.
- La simulation sur les donnees du 22/07/2026 retrouve 9 trajets significatifs au lieu de 3, sans ecriture dans la base de production.
- Le script temporaire de diagnostic a ete supprime de `/tmp` apres les controles.
- Aucun fichier applicatif, schema, cache, service ou variable du VPS n'a ete modifie. Le correctif attend une autorisation explicite de deploiement.

## 2026-07-22 - Deploiement du calcul detaille des trajets
- Deploiement cible de `app/Services/DeviceTripService.php` vers `/var/www/exadtracking.app`.
- Archive minimale controlee avant installation avec le SHA-256 `b8aa08e4dd10f37adf157d8bdf9800c26b619d609b3d26063c8116aa8c70b204` et contenant uniquement le service de trajets.
- Sauvegarde de retour arriere conservee dans `/tmp/exadtracking-before-trip-segmentation-20260722-183255.tar.gz`.
- Une premiere commande inline a ete interrompue avant modification par l'interpretation PowerShell des substitutions Linux. Lors de la reprise par script, la copie a reussi mais la tentative de changement de groupe a ete refusee ; le mode a ete immediatement corrige de `0600` a `0644` avant les controles HTTP.
- Le fichier de production appartient au compte de deploiement `exad-tracking`, reste lisible par Apache en `0644` et correspond exactement au SHA-256 local `67108983a177cc5e8cee464de3d809453f4ef33dc291f98969aa567ded7b969e`.
- `optimize:clear`, `config:cache`, `view:cache` et `queue:restart` ont reussi. Le rechargement explicite d'Apache via `sudo -n` a ete refuse faute d'autorisation non interactive ; Apache est reste actif et les controles HTTPS ont reussi.
- Etat final : environnement production, debug desactive, maintenance inactive, `/login` et `/up` en `200`, API mobile protegee en `401` sans jeton, Apache, `gps-tcp.service` et `exad-server-console.service` actifs.
- Les archives, le dossier de staging et le script temporaires ont ete supprimes du VPS. Aucun schema, migration, variable d'environnement ou code Flutter n'a ete modifie.

### Chronologie mobile amelioree localement
- La liste des trajets de l'application Flutter a ete refondue localement avec resume de periode, regroupement par date, chronologie depart/arrivee et metriques compactes.
- L'application mobile a lu les 13 trajets de la journee fournis par l'API de production pour le controle visuel, sans modifier les donnees serveur.
- Aucun fichier, cache, service, schema, variable d'environnement ou configuration du VPS n'a ete modifie.

### APK mobile release genere localement
- Un APK Android release signe avec le certificat de developpement a ete compile et valide localement pour les tests sur appareils physiques.
- Le fichier est distribue temporairement depuis le PC de developpement sur le reseau local ; aucun APK, secret Google Maps, fichier ou service n'a ete copie sur le VPS.
- Le QR code de telechargement a egalement ete genere localement et ne sollicite aucun service du VPS.
- Aucun cache, schema, variable, pare-feu ou configuration du serveur de production n'a ete modifie.

### Correction locale des boutons mobiles sur ecrans Samsung
- Les trois actions du panneau vehicule mobile ont ete compactees et leurs libelles limites a une seule ligne avec reduction automatique lorsque la police Android est agrandie.
- La correction a ete validee localement a une echelle de police Android de `1.3`, puis integree dans un nouvel APK release de test dont le SHA-256 est `d807d05d97ec469b2418ef61592cbcdd061b8542d88e93a4f576d5ea5351e8eb`.
- L'APK reste servi par le PC de developpement sur le reseau Wi-Fi local. Aucun APK, fichier applicatif, cache, schema, variable ou service du VPS n'a ete modifie.

#### APK mobile regenere localement
- L'APK release de test a ete regenere depuis le projet Flutter et sa signature v2 validee. Son SHA-256 reste `d807d05d97ec469b2418ef61592cbcdd061b8542d88e93a4f576d5ea5351e8eb`.
- Le fichier reste distribue exclusivement depuis le PC de developpement. Aucun fichier, service, cache, schema ou parametre du VPS n'a ete modifie.

## 2026-07-22 - Controle de convergence web et livraison mobile locale
- Aucun ecart de code web executable n'etait present apres le dernier deploiement. Aucun fichier n'a donc ete recopie vers `/var/www/exadtracking.app` pendant cette operation.
- Le SHA-256 distant de `app/Services/DeviceTripService.php` reste `67108983a177cc5e8cee464de3d809453f4ef33dc291f98969aa567ded7b969e`, identique au fichier local deja deploye.
- Production confirmee hors maintenance avec debug desactive ; Apache, `gps-tcp.service` et `exad-server-console.service` sont actifs. `/login` et `/up` repondent en `200`, et l'API mobile sans jeton en `401`.
- Le nouvel APK de test a ete installe et lance sur l'emulateur Android puis rendu disponible depuis le PC sur le reseau local. Aucun APK ni secret cartographique n'a ete copie sur le VPS et aucune publication Play Store n'a ete effectuee.
- Validation finale : 140 tests Laravel avec 1 289 assertions, analyse Flutter sans anomalie et 6 tests Flutter passes.

## 2026-08-02 - Correction de la saturation du listener GPS TCP
- Diagnostic production : `gps-tcp.service` et le port `5027/tcp` etaient actifs, mais le processus conservait 2 875 connexions TCP etablies et 2 898 descripteurs pour seulement trois traceurs enregistres.
- Cause confirmee dans `gps-listener-server-prod/src/listeners/tcp-listener.js` : aucune echeance de handshake, aucun delai d'inactivite pour les connexions authentifiees, aucune limite de tampon par socket et aucun plafond global de connexions.
- Impact observe : 7 028 positions et 3 589 emplacements distincts recus le 31 juillet, puis seulement 479 positions sur un emplacement fixe le 1er aout. Laravel continuait a stocker les paquets disponibles, mais ne recevait plus de deplacements permettant de former des trajets.
- Correction deployee sur le listener de production : handshake limite a 15 secondes, connexion authentifiee inactive limitee a 15 minutes, keepalive TCP active, tampon limite a 1 Mio et maximum global fixe a 512 connexions. Ces valeurs restent surchargeables par variables d'environnement.
- Sauvegarde de retour arriere : `/tmp/exad-tcp-listener-before-fix-20260802.js`.
- Le fichier corrige a passe `node --check` avant et apres installation, puis `gps-tcp.service` a ete redemarre avec succes.
- Controle post-deploiement : service actif, connexions etablies ramenees de 2 875 a 1, descripteurs ramenes de 2 898 a 23 et nouvelles positions de nouveau ingerees.
- La commande `gps:mark-stale --minutes=5`, deja disponible mais non planifiee, est maintenant executee chaque minute avec `withoutOverlapping()` ; son premier passage a correctement marque deux traceurs silencieux hors ligne.
- Aucune execution du scheduler Laravel n'etait configuree sur le VPS malgre un service cron actif. L'entree `/etc/cron.d/exad-tracking` lance desormais `php artisan schedule:run` chaque minute sous l'utilisateur `exad-tracking`.
- Sauvegarde de retour arriere de la route console : `/tmp/exad-console-before-stale-schedule-20260802.php`. La syntaxe PHP, `schedule:list`, l'execution manuelle du scheduler et le service cron ont ete valides.

## 2026-08-16 - Correction des adresses manquantes dans les trajets
- Diagnostic production : Google et Mapbox etaient configures, mais 3 662 des 3 666 positions du jour ne possedaient pas encore d'adresse en base.
- Cause applicative : le calcul des trajets partageait un budget global de cinq secondes entre les fuseaux horaires, le geocodage inverse et Google Roads. Les derniers trajets ne tentaient plus de resoudre leurs adresses lorsque les traitements precedents avaient epuise ce budget, puis affichaient latitude et longitude.
- Correction deployee : les adresses de depart et d'arrivee sont desormais resolues avant les enrichissements optionnels et ne sont plus ignorees lorsque le budget Google Roads est expire.
- Les appels de fuseau horaire utilisent maintenant des delais explicites de connexion et de reponse plus courts. Les messages bruts des exceptions HTTP, susceptibles de contenir une cle dans leur URL, ne sont plus journalises par les services cartographiques modifies.
- Archive de deploiement controlee avant installation avec le SHA-256 `6dbb0ce0da27e917a12d11e73e99581d83522a1474270f4595c9dd53fb032811`.
- Sauvegarde de retour arriere : `/tmp/exadtracking-before-geocoding-20260816.tar.gz`.
- Validation : syntaxe PHP des quatre services, empreintes locales/distantes identiques, 145 tests Laravel et 1 318 assertions, caches configuration/vue reconstruits, `/up` et `/login` en HTTP 200.

## 2026-08-16 - Sélecteur du fournisseur cartographique
- Ajout dans la personnalisation superadmin d’un réglage global permettant de choisir Google Maps ou Mapbox comme carte de suivi par défaut, sans modifier les clés API ni redéployer l’application.
- Le choix est stocké dans `application_settings.map_provider`; la migration additive conserve `google` pour les installations existantes et la validation refuse tout fournisseur non pris en charge.
- La page de carte charge désormais les ressources Google Maps ou Mapbox à partir de ce réglage persistant. La production reste configurée sur Google Maps après la migration.
- Archive de déploiement vérifiée avec le SHA-256 `144988604211e0c2bba40239ae61558a0721756660a9e40135c4346b8bda571e` et sauvegarde de retour arrière conservée dans `/tmp/exadtracking-before-map-provider-20260816-151026.tar.gz`.
- Validation : empreintes des sept fichiers locaux/distants identiques, migration exécutée en lot 12, 145 tests Laravel et 1 321 assertions, caches reconstruits, `/up` et `/login` en HTTP 200, Apache et les services GPS actifs.

## 2026-08-31 - Correction de l'erreur 500 apres l'enregistrement d'un chauffeur
- Le journal Laravel de production a confirme une colonne `driver_id` ambigue dans la requete `primaryIdentifier` generee lors du rechargement de la liste des chauffeurs.
- L'enregistrement du chauffeur avait bien reussi ; l'erreur se produisait ensuite pendant la redirection vers `/drivers`.
- Les colonnes selectionnees pour l'identifiant principal sont maintenant qualifiees par la table `driver_identifiers`.
- Un test de regression recharge la liste apres la creation d'un chauffeur avec badge et confirme l'affichage de l'identifiant.
- Validation locale : 145 tests Laravel passes avec 1 323 assertions, syntaxe PHP valide et controle Pint cible reussi.
- Deploiement limite a `app/Http/Controllers/DriverController.php`, sans migration, modification de schema ou changement de donnees. Le fichier distant correspond au SHA-256 local `0a1077aaca2421d0b08df539de4db22754e3e19075a703b13fe5eadcaebf283b` et conserve `exad-tracking:www-data` en mode `0644`.
- Sauvegarde de retour arriere : `/tmp/exadtracking-before-driver-fix-20260831T190646Z.tar.gz`.
- Une premiere commande a ete arretee avant la sauvegarde et avant toute modification par l'interpretation PowerShell de controles Linux. La relance sans substitution locale a ensuite termine normalement.
- Les caches Laravel ont ete nettoyes et les caches de configuration et de vues reconstruits. La methode de liste charge correctement une vue avec le chauffeur existant ; `/login` et `/up` repondent en `200`, l'API mobile sans jeton en `401` et Apache reste actif.

## 2026-09-01 - Correction de l'identification iButton et du remplacement de badge
- La modification du badge `38000009A29C2114` etait bien enregistree pour Arnold Lula, mais la relation `primaryIdentifier` selectionnait d'abord l'ancien identifiant desactive et masquait le nouveau badge dans la liste.
- La relation selectionne maintenant le dernier identifiant actif. Un test de regression remplace un ancien badge, confirme sa desactivation et verifie l'affichage du nouveau badge.
- La normalisation des identifiants conducteur est centralisee. La sentinelle Teltonika `0000000000000000` est ignoree et ne peut plus ecraser la derniere cle valide.
- La recherche accepte l'ordre d'octets direct et inverse des iButton Teltonika de 8 octets. Le test d'ingestion couvre `38000009A29C2114` et sa representation brute inversee `14219CA209000038`.
- Les details web et mobile utilisent la meme resolution d'identifiant et remettent la valeur memorisee sur l'UID canonique enregistre en base apres correspondance.
- La production a ete corrigee de maniere ciblee : l'identifiant actif d'Arnold est type `ibutton` et le FMB140 du vehicule affecte memorise `38000009A29C2114` comme derniere cle valide a la place de la sentinelle nulle.
- Le dernier paquet controle ne contenait toujours pas l'AVL IO `78`; la correction logicielle conserve donc la cle valide, mais la lecture physique 1-Wire reste dependante de la configuration et du cablage du traceur.
- Archive de deploiement controlee avec le SHA-256 `eb1abfabf481d83e187160b318380a30df94bfc0ef57aad4ea062acab2959da0`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-ibutton-fix-20260901T120000Z.tar.gz`.
- Validation : 147 tests Laravel avec 1 336 assertions, Pint cible reussi, syntaxe PHP et empreintes locales/distantes identiques, caches reconstruits, `/up` et `/login` en HTTP 200 et `gps-tcp.service` actif.
- Aucun schema, migration, variable d'environnement ou code mobile n'a ete modifie. Les scripts, l'archive et le dossier de staging temporaires ont ete supprimes apres verification.

## 2026-09-01 - Liste des chauffeurs client en lecture seule
- La route `GET /drivers` est maintenant accessible a tous les comptes authentifies et limite les chauffeurs a `users.fleet_id`. La recherche, le tri, la pagination et les reponses AJAX appliquent la meme portee de flotte.
- Les clients et l'apercu client disposent du lien Chauffeurs dans la navigation. La colonne badge, les UID, le bouton Nouveau, les actions, le modal, les attributs JSON d'edition et les scripts de mutation ne sont pas rendus dans leur HTML.
- La recherche client ne consulte pas `driver_identifiers`, ce qui empeche d'utiliser un UID connu pour retrouver un chauffeur. Les relations de badge et les listes globales destinees au formulaire ne sont pas chargees pour les clients.
- Les routes POST, PUT/PATCH et DELETE des chauffeurs restent protegees par `auth + superadmin`; les tentatives directes sur un chauffeur de la flotte ou d'une autre flotte repondent en `403`.
- Les details traceur client affichent maintenant le modele, l'IMEI, la SIM, l'operateur, le protocole, le codec et les diagnostics. Les deux occurrences de l'identifiant conducteur restent masquees par une capacite independante reservee au superadmin.
- L'API mobile renvoie egalement l'identite du traceur aux clients, sans `driver.identifier_uid`, `driver.identifier_type` ni `diagnostic.driver_identifier_uid`. Le superadmin conserve ces champs.
- Validation locale : 148 tests Laravel passes avec 1 386 assertions, syntaxe PHP, compilation Blade, Pint cible et verification des routes reussis.
- Archive de deploiement SHA-256 `8f0f7b4fb657375c9235c5894c2f5ae23dd0609c5a424b73e4ed655cbf09d756`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-client-drivers-20260901T130000Z.tar.gz`.
- Les dix fichiers distants correspondent exactement aux empreintes locales. Les caches configuration et vues ont ete reconstruits, `/up` et `/login` repondent en HTTP 200 et `gps-tcp.service` reste actif.
- Aucun schema, migration, donnee metier, variable d'environnement ou code Flutter n'a ete modifie.

## 2026-09-01 - Liste des chauffeurs dans l'application mobile
- Ajout de la route privee `GET /api/v1/mobile/drivers`, limitee a la flotte du compte client et paginee. La ressource expose uniquement le nom, le matricule, le contact, la flotte, le departement, les vehicules autorises et le statut.
- Aucun identifiant RFID, iButton ou NFC n'est charge, recherche ou renvoye. L'API ne declare aucune route mobile de creation, modification ou suppression de chauffeur.
- L'application Flutter dispose maintenant d'une entree Chauffeurs dans Plus, d'une liste en lecture seule, d'une recherche locale, d'un rafraichissement manuel et du chargement de toutes les pages. Les details vehicule client ne rendent plus les identifiants conducteur dans les blocs Conducteur et Diagnostic.
- Validation locale : 149 tests Laravel avec 1 402 assertions, analyse Flutter sans anomalie et 12 tests Flutter passes. L'APK de recette `1.0.0+10` a ete genere avec le SHA-256 `7414638a162d79029b9314a79b18f8cd4ba4c4cfca752d2a15520e2ea1e44dc5`, installe avec succes puis lance sur l'emulateur Android.
- Archive de deploiement Linux controlee avec le SHA-256 `5c8ea851cffee6e01206748636a8006b95b9181b8fa7210b25ad3ca712e4d620`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-mobile-drivers-20260901T132000Z.tar.gz`.
- Deux premieres extractions ont ete arretees avant toute installation : le ZIP Windows utilisait des separateurs inverses puis avait laisse un staging sans droit de traversee. Le staging temporaire a ete repare et supprime avant l'installation depuis l'archive `tar.gz` validee.
- Les trois fichiers de production correspondent aux empreintes locales, les caches ont ete reconstruits, `/up` repond en 200 et la route Chauffeurs sans jeton JSON en 401. L'environnement reste en production, debug desactive et maintenance inactive ; Apache, GPS TCP et la console serveur sont actifs.

## 2026-09-01 - Page publique de telechargement Android
- Publication de `https://exadtracking.app/application`, accessible sans authentification et reliee depuis la page de connexion. Le rendu responsive reprend le theme sombre et bleu d'EXAD Tracking, avec version actuelle, fonctionnalites, guide d'installation, langues francaise et anglaise et historique des versions.
- Huit APK Android universels sont disponibles en telechargement prive via Laravel : version `1.0.0`, builds 3 a 10, Android 7.0 minimum. Les chemins de fichiers ne sont jamais fournis par la requete ; seuls les slugs declares dans `config/mobile_releases.php` sont acceptes.
- Les APK sont stockes hors du dossier public dans `storage/app/private/mobile-releases/android`. Les telechargements utilisent le type `application/vnd.android.package-archive`, `nosniff`, cache immuable, prise en charge des requetes partielles et une limite de 8 requetes par minute et par adresse IP.
- Les huit empreintes SHA-256 ont ete verifiees avant activation et sont consultables integralement sur la page. Les fichiers totalisent 434 780 160 octets et conservent le proprietaire `exad-tracking:exad-tracking` en mode `0644`.
- La page avertit que les APK actuels utilisent le certificat Android de recette et qu'un futur certificat de distribution pourra imposer une reinstallation. Elle precise aussi qu'un retour vers un ancien build peut demander une desinstallation avec perte de session et preferences locales.
- Validation locale : 155 tests Laravel avec 1 430 assertions, 6 tests cibles de telechargement avec 28 assertions, Pint, syntaxe PHP, compilation Blade, routes, page HTTP et en-tetes APK valides.
- Archive code SHA-256 `3fbe0ff0d75d8430d57f9535202de84335b5294db13410ab595c9bdf6a6f8f71`; archive APK SHA-256 `bd521be3c2cf733c9e18025342fc7416f3b228f39c7e911938b88ad2151e6575`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-public-mobile-downloads-20260901T140000Z.tar.gz`.
- Les neuf fichiers applicatifs distants correspondent exactement aux empreintes locales. En production, `/application` et `/up` repondent en 200, le build 10 repond en 200 et en 206 sur une plage de 1 024 octets, et Apache, GPS TCP et la console serveur restent actifs.

## 2026-09-01 - Affichage des versions Android completes
- La page publique affiche desormais le couple Android `versionName+versionCode` pour chaque APK : `1.0.0+10` pour la version actuelle et `1.0.0+9` a `1.0.0+3` pour les archives. Le numero de build reste egalement indique separement.
- La version complete est derivee de `version` et `build` dans le controleur afin d'eviter toute divergence avec la configuration, le nom du fichier et les metadonnees APK.
- Les libelles accessibles des boutons de telechargement ont ete alignes en francais et en anglais, y compris pour le bouton principal.
- Validation locale : 155 tests Laravel avec 1 436 assertions et 6 tests cibles avec 34 assertions. L'archive minimale de deploiement a ete verifiee avec le SHA-256 `4768a97e8988b8c15d8d96925a626024a84428a98b1451348f58a37aa33f82e9`.
- Sauvegarde de retour arriere : `/tmp/exadtracking-before-version-labels-20260901-120004.tar.gz`, SHA-256 `0c51fb72e32ed12a5e401712b2c5d26ffc062f0dc1fc70b87b5b7da1fc970913`.
- En production, `/application` repond en HTTP 200 et contient les huit versions completes. Le telechargement du build 10 repond en HTTP 200 avec `EXAD-Tracking-1.0.0+10.apk`; Apache 2, PHP 8.2 FPM et Supervisor sont actifs.
- Aucun APK, schema, migration, donnee metier ni variable d'environnement n'a ete modifie.

## 2026-09-02 - Correction de la flotte carte et de la resolution du chauffeur
- Le diagnostic agrege de production a confirme une cause commune aux deux anomalies : 2 traceurs sur 4 conservaient une ancienne `fleet_id` differente de la flotte de leur vehicule.
- Le filtre web et mobile utilise maintenant la flotte du vehicule comme source de verite pour un traceur affecte. Un traceur sans vehicule continue d'utiliser sa propre `fleet_id`.
- Le scope de visibilite des traceurs ne permet plus a une ligne incoherente d'etre visible simultanement par l'ancienne et la nouvelle flotte.
- Les details web et mobile resolvent le chauffeur avec la flotte du vehicule. L'iButton `38000009A29C2114`, son chauffeur actif et l'affectation au vehicule etaient valides ; seule la comparaison avec l'ancienne flotte du traceur bloquait l'affichage.
- Lorsqu'un vehicule change de flotte, le traceur qui lui est affecte est desormais synchronise dans la meme transaction pour eviter toute nouvelle divergence.
- Apres sauvegarde, les 2 `devices.fleet_id` incoherents ont ete realignes sur `vehicles.fleet_id`. Aucun chauffeur, badge, vehicule ni autre champ n'a ete modifie. Etat final : 4 traceurs affectes, 4 correspondances de flotte et 0 divergence.
- Validation locale : 157 tests Laravel avec 1 451 assertions, dont 69 tests cibles avec 665 assertions, Pint et syntaxe PHP valides.
- Archive de deploiement SHA-256 `35de0ed036a09d8967d20bbd8129e145ea2ec6778935b68a980c2e70ac35c233`. Sauvegarde code : `/tmp/exadtracking-before-fleet-driver-fix-20260902-103648.tar.gz` (`026116e08d52d92c3b483dbeb73ac686baf3a592b19074a5601af9b1650dac0d`). Sauvegarde des correspondances corrigees : `/tmp/exadtracking-before-fleet-realignment-20260902-103648.json` (`fc54291a3229d76cf797b06bf3a5fcd4db2ab22acb3ecd3209d524f84d76ed7e`). Les deux sauvegardes sont protegees en mode `0600`.
- Les sept fichiers distants correspondent exactement aux empreintes locales. Apache 2, PHP 8.2 FPM et Supervisor sont actifs ; `/up` repond en HTTP 200 et `/map` redirige normalement vers l'authentification sans session.

## 2026-09-03 - Deploiement cumulatif et amelioration des adresses de trajets
- Deploiement des 36 fichiers applicatifs en attente couvrant les correctifs chauffeurs/iButton, la portee des flottes web et mobile, la liste des chauffeurs client en lecture seule, la page publique Android et le reverse geocoding des trajets.
- Le reverse geocoding ne s'arrete plus sur la premiere commune renvoyee : une adresse generique laisse maintenant le fournisseur de secours chercher une rue. La requete Mapbox v6 n'utilise plus `limit=1` avec plusieurs types ni le type obsolete `poi`, toutes les features sont classees et `name` est combine avec `place_formatted` lorsque `full_address` manque.
- Le cache passe de `reverse-geocode:v2` a `v3` afin de ne pas conserver pendant trente jours les anciennes adresses generiques. Si aucun fournisseur ne connait une rue pour les coordonnees demandees, la commune reste le meilleur libelle disponible.
- Validation locale : 158 tests Laravel passes avec 1 453 assertions, test de regression Google generique vers rue Mapbox, syntaxe PHP et Pint cibles valides.
- Archive de deploiement SHA-256 `d3e78826c4fe84d12df896815dee35b5572a5ad080638946d412226841f25157`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-all-20260903-092806.tar.gz` (`02c6234bb426a2936653f38174b4cfd0255f423e83f90a9348f76fd1067927fc`).
- Les 36 empreintes locales et distantes correspondent. Aucune migration n'etait en attente ; l'autoload et les caches Laravel ont ete reconstruits, puis PHP-FPM et Apache recharges.
- Verification production : `/up`, `/login` et `/application` repondent en HTTP 200, la version Android `1.0.0+10` est affichee, l'API chauffeurs mobile sans jeton repond en 401, le mode maintenance est desactive et Apache, PHP-FPM, GPS TCP et Supervisor sont actifs.
- Une premiere copie preservee a ete arretee sur la mise a jour interdite de l'horodatage du dossier racine ; la remise en ligne automatique a fonctionne, puis la copie a ete relancee sans preservation des metadonnees du dossier racine.

## 2026-09-03 - Modal corporate, etats CAN complets et Android build 11
- Le detail traceur Web adopte une presentation corporate avec synthese operationnelle, cartes compactes, donnees techniques repliables et une section OBD/CAN pleine largeur.
- Les AVL Teltonika `90`, `132` et `517`, ainsi que les IO individuels `652-658`, `898-900`, `909-911` et `913`, sont maintenant normalises pour les portes, le toit, Webasto, l'embrayage, l'allumage, la cle, les freins, le moteur, le capot et le coffre. L'IO inverse `911` est traite explicitement.
- L'ingestion ne confond plus l'odomètre de trajet `199` avec le kilometrage total. Les odometres CAN en metres sont convertis en kilometres et la temperature moteur `115` est convertie depuis son entier signe au dixieme de degre.
- L'API mobile expose ces etats sans identifiant conducteur, et l'application Flutter les affiche dans la section OBD/CAN avec les memes libelles traduits que le Web.
- L'APK Android universel `1.0.0+11` (versionCode 11) a ete compile, signe en APK Signature Scheme v2 et publie avec le SHA-256 `149b9a0a32e1e8cf478456a9282fe4ddb62e3b7c649608d7cb9c6ba7ecbbc3d7`. Les builds 3 a 10 restent disponibles dans les archives.
- La compilation locale a necessite OpenJDK 17 car le JBR 25 d'Android Studio n'est pas pris en charge par la chaine Gradle courante. Un JDK Microsoft portable est configure pour les prochains builds ; les premieres tentatives ont echoue localement avant toute modification du VPS.
- Validation avant deploiement : 164 tests Laravel avec 1 527 assertions, 12 tests Flutter passes, analyse Flutter sans anomalie et tests cibles API/telechargement/CAN passes.
- Archive de deploiement SHA-256 `8f7c38fd22ddd95445fd7ac7d808f860165deb9ffe48397e3a5f7730bda0dd64`. Sauvegarde de retour arriere protegee en mode `0600` : `/tmp/exadtracking-before-canbus-20260903-103000.tar.gz`, SHA-256 `2948b83602df6cb01eac991b57c2e95b26d37d3f2ed1fc8d1370046b67d10ee4`.
- Les 15 fichiers applicatifs et l'APK distant correspondent exactement aux empreintes locales. Aucune migration ni modification de donnee metier n'a ete necessaire.
- Verification production : le FMB140 `353201357467643` restitue correctement ses derniers masques `90=0`, `132=33554435` et `517=317`; `/application` affiche `1.0.0+11`, le telechargement partiel repond en HTTP `206` avec le bon nom, et Apache, PHP-FPM, GPS TCP et Supervisor sont actifs.

## 2026-09-03 - Compactage du modal de details traceur
- Le modal de details passe de `1060px` a `900px`, avec des en-tetes, marges, cartes et lignes plus compacts. Les cartes sont alignees par rangee et les donnees techniques passent de trois a deux colonnes.
- Les doublons d'etat, qualite GPS, stationnement et coordonnees ont ete retires des cartes secondaires puisqu'ils restent visibles dans la synthese operationnelle. Le CAN Bus est presente sur deux colonnes et conserve son adaptation mobile.
- Validation locale : 164 tests Laravel avec 1 527 assertions, compilation Blade et controle des espaces valides.
- Archive de deploiement SHA-256 `cda9fbb5edac154e611f06029f00da4f4cdf6da77ef4a387bfa7a9397508c6f1`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-modal-compact-20260903-111329.tar.gz`, SHA-256 `c196287884722f934b93a1e2e07afac2944c156c588ca0a65279ee049208772a`.
- Les quatre fichiers distants et la feuille CSS servie publiquement correspondent exactement aux empreintes locales. `/up` repond en HTTP 200, le mode maintenance est desactive et Apache, PHP-FPM, GPS TCP et Supervisor sont actifs.

## 2026-09-03 - Modal details compact v2
- La largeur maximale du modal passe de `900px` a `760px` et sa hauteur est plafonnee a `780px`. Le bouton de fermeture est force a l'extremite droite avec un en-tete reparti sur toute la largeur.
- Le cache CSS des pages Carte et Traceurs passe a `20260903-tracker-modal-compact-v2` afin que les navigateurs chargent immediatement la correction.
- Validation locale : compilation Blade reussie et 2 tests cibles passes avec 69 assertions.
- Archive de deploiement SHA-256 `ba087fda6f2dedfc9fa2a5be12921071a8199f4434e4140c9c8e01ab800201f3`. Sauvegarde de retour arriere : `/tmp/exadtracking-before-modal-compact-v2-20260903-112423.tar.gz`, SHA-256 `4c2863871f1231c308c9ff81e0575a1af6872fa58d5e91da6a6c10e290321449`.
- Les trois fichiers distants et la feuille CSS servie publiquement correspondent aux empreintes locales. `/up` repond en HTTP 200, le mode maintenance est desactive et Apache, PHP-FPM, GPS TCP et Supervisor sont actifs.

## 2026-09-03 - Immobilisation moteur securisee et interface simplifiee
- L'arret moteur a distance est transporte vers les traceurs compatibles par commandes persistantes, avec historique des tentatives, expiration, acquittement et confirmation. Les trois migrations de commandes et de profils d'immobilisation ont ete appliquees en lot 13.
- L'immobilisation ne peut jamais etre reclamee par l'ecouteur GPS pendant la conduite : le serveur exige une telemetrie recente, une vitesse nulle, un contact coupe, un regime moteur nul et une fenetre d'arret confirmee. La liberation du demarrage reste disponible pour annuler ou inverser une demande.
- La compatibilite est centralisee dans une matrice marque-modele et reverifiee dans le Gate Laravel ainsi que dans l'action metier. Le FMB140 est actuellement autorise ; le FMB003 est masque dans l'interface et refuse meme lors d'une requete directe.
- L'interface affiche un seul bouton dynamique : `Immobiliser le demarrage`, puis `Autoriser le demarrage` des qu'une immobilisation est demandee ou confirmee. L'ancien formulaire a ete supprime et remplace par une confirmation SweetAlert2 `11.26.25` oui/non.
- Les textes visibles emploient desormais le terme generique `traceur`/`tracker`. Le nom Teltonika reste uniquement dans l'identite reelle du materiel, la matrice de compatibilite et les composants internes du protocole.
- L'acces reste reserve au superadministrateur authentifie, avec protection CSRF et limitation a trois commandes par minute. Le motif d'audit est produit automatiquement par le serveur et les demandes opposees se remplacent de maniere transactionnelle.
- Validation locale finale : 176 tests Laravel passes avec 1 574 assertions ; tests cibles de commande et de securite passes, Pint, syntaxe PHP/JavaScript et compilation Blade valides.
- Archive principale SHA-256 `c1a0d21c8c1dca5775d1c3210ed1c1b53b67f9143fbb151b44bd01fe0df29704`. Sauvegarde de retour arriere protegee en mode `0600` : `/tmp/exadtracking-before-engine-toggle-20260903-143000.tar.gz`, SHA-256 `23c8164cdc75fe6e2c289e51dc5b084e3b0a118a0a6a8bf38ce4699f5ca037ee`.
- Une premiere finalisation s'est arretee sur un changement de groupe refuse pour un fichier existant. Le garde de maintenance a immediatement remis Laravel en ligne ; la finalisation a ensuite reussi sans modifier les proprietaires existants.
- Le correctif de vocabulaire a ete deploye avec l'archive SHA-256 `4cfbbccae2e673886dc269ada5a3402d913dd499abe0a3454b3a099cb284a8ec`. Sa sauvegarde est `/tmp/exadtracking-before-tracker-wording-20260903-143503.tar.gz`, SHA-256 `2d98252a9e211b660da1b10dd4699845fd87abae2acf59561d28699336307c96`, en mode `0600`.
- Verification production finale : `FMB003_BLOCKED`, `FMB140_ALLOWED`, SweetAlert servi en HTTP 200, `/up` et `/login` en HTTP 200, mode maintenance desactive, Apache et `gps-tcp.service` actifs.

## 2026-09-03 - Delegation client de l'immobilisation preparee localement
- Le controle moteur est etendu aux administrateurs clients pour les vehicules compatibles de leur propre flotte. Les utilisateurs standards restent bloques sauf attribution explicite de la permission `engine.control` par leur administrateur.
- La gestion existante des utilisateurs permet d'accorder et de revoquer cette permission sans migration. Un utilisateur standard ne peut ni gerer les comptes ni s'accorder lui-meme l'autorisation.
- Le serveur reverifie le compte actif, la flotte du vehicule, la compatibilite du traceur, la fonctionnalite d'abonnement et l'autorisation apres verrouillage transactionnel. Les controles qui interdisent toute coupure pendant la conduite ne changent pas.
- Les details client n'exposent que des URL fondees sur le vehicule, masquent le bouton sans autorisation et le masquent egalement en previsualisation client, ou les commandes POST sont interdites.
- Validation locale : 181 tests Laravel passes avec 1 602 assertions, dont 23 tests cibles avec 252 assertions ; Pint, syntaxe PHP, routes et compilation Blade valides.
- Cette evolution n'est pas encore deployee en production et aucun commit n'a ete cree.

## 2026-09-03 - Deploiement de la delegation client de l'immobilisation
- Deploiement cible de 10 fichiers vers `/var/www/exadtracking.app` pour autoriser l'administrateur client et les utilisateurs standards munis de la permission `engine.control` a commander les vehicules compatibles de leur propre flotte.
- Archive transferee : `/tmp/deploy-exadtracking-client-engine-permission-20260903-150821.tar.gz`, SHA-256 `8ab06b73134f6a67130f94a921fc3cb32a87f4bc4c9931e470e9caa293ac8ce4`.
- Sauvegarde de retour arriere : `/tmp/exadtracking-before-client-engine-permission-20260903-152020.tar.gz`, SHA-256 `4beeed2ec7a1c02d477c3c5aa99e90bafbdd80f13b9b107d28c6da5effcaf8c6`, permissions `0600`.
- Aucune migration ni donnee metier n'a ete modifiee. Les caches Laravel ont ete nettoyes, les caches de configuration et de vues reconstruits et les workers de queue signales.
- Les 10 empreintes distantes correspondent aux fichiers locaux et la syntaxe PHP distante est valide. Les routes plateforme et vehicule sont presentes ; la route client applique authentification, CSRF, limitation `engine-control` et previsualisation client.
- Etat final : environnement `production`, debug desactive, maintenance inactive, `/up` et `/login` en HTTP 200, `/map` en HTTP 302 sans session, appel POST sans jeton CSRF refuse en HTTP 419, Apache, PHP-FPM, `gps-tcp.service` et Supervisor actifs.
- Aucun commit n'a ete cree.

## 2026-09-03 - Retrait des abonnements prepare localement
- Suppression locale de la surface Abonnements : menu, routes, controleur, vue et traductions. Les plans disparaissent aussi des formulaires et tableaux Vehicules ainsi que des repartitions du tableau Flottes.
- Les restrictions fonctionnelles par plan ont ete retirees. L'immobilisation moteur depend maintenant uniquement de l'autorisation, de la flotte, du modele compatible et des garde-fous de securite moteur.
- Les nouveaux comptes et traceurs ne recopient plus `subscription_id`. Les colonnes et donnees historiques restent intactes afin d'eviter une migration destructive ; `fleet_id` demeure la frontiere d'acces serveur et mobile.
- Validation locale : 182 tests Laravel passes avec 1 597 assertions, 113 tests cibles avec 1 031 assertions, syntaxe PHP, Pint, compilation Blade et routes valides. Aucune occurrence d'abonnement ne subsiste dans les vues, traductions ou routes actives.
- Aucun fichier de production n'a encore ete modifie, aucune migration n'est requise et aucun commit n'a ete cree.

## 2026-09-03 - Deploiement du retrait des abonnements
- Deploiement cible de 21 fichiers vers `/var/www/exadtracking.app`, avec retrait controle des quatre anciens fichiers `SubscriptionPlanController.php`, `subscriptions/index.blade.php` et des traductions `subscriptions.php` francaise et anglaise.
- Archive transferee : `/tmp/deploy-exadtracking-remove-subscriptions-20260903-154237.tar.gz`, SHA-256 local et distant `622bc9fb6eaae463bc64816e202997672cc398fe974c193095eb5d7b74bd4759`.
- Sauvegarde de retour arriere : `/tmp/exadtracking-before-remove-subscriptions-20260903-154433.tar.gz`, SHA-256 `afa930744c0e181b2fb3f31488a2e51152a390f4ecad04d8bf5594d558a3b395`, permissions `0600`.
- L'installation a active le mode maintenance uniquement pendant la copie, puis a execute `optimize:clear`, reconstruit les caches de configuration et de vues, signale les workers de queue et remis Laravel en ligne sans erreur.
- Aucune migration, suppression de colonne, donnee metier ou variable d'environnement n'a ete modifiee. Les colonnes historiques des abonnements restent intactes pour garantir un retour arriere non destructif.
- Validation locale avant livraison : 182 tests Laravel avec 1 597 assertions, 113 tests cibles avec 1 031 assertions, Pint, syntaxe PHP, compilation Blade et inventaire des routes valides.
- Aucun commit n'a ete cree.
