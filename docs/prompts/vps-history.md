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