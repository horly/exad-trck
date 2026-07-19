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
