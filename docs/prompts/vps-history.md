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
