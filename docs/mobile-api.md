# API mobile privee EXAD Tracking

## Portee

Cette API sert exclusivement a l'application mobile officielle EXAD Tracking. Elle est versionnee sous `/api/v1/mobile` et utilise Laravel Sanctum avec une session distincte par appareil.

Elle ne remplace pas la future API publique d'integration. Les identifiants `client_id` / `client_secret`, la page client de gestion des acces et OAuth2 seront traites dans un lot separe.

## Securite

- Le mot de passe est verifie uniquement par le serveur.
- Un compte desactive est refuse et ses sessions mobiles actives sont revoquees au prochain appel.
- Les comptes admin et utilisateur doivent etre affectes a une flotte. Le superadmin conserve son acces global.
- Si la 2FA est activee et confirmee, aucun jeton n'est emis avant validation du code TOTP ou d'un code de recuperation.
- Chaque appareil recoit un jeton d'acces de 60 minutes et un jeton de rafraichissement de 30 jours.
- Le rafraichissement remplace les deux jetons. Une ancienne paire ne peut plus etre reutilisee.
- Une nouvelle connexion avec le meme `device_identifier` revoque la session precedente de cet appareil.
- Les jetons sont haches dans `personal_access_tokens`; leur valeur en clair n'est retournee qu'une fois.
- Flutter devra conserver les jetons dans le stockage securise Android/iOS, jamais dans des preferences ordinaires ni dans les journaux.
- Aucun secret global embarque dans l'application mobile n'est considere comme une protection, car il pourrait etre extrait du binaire.

Les durees sont configurables avec :

```dotenv
MOBILE_API_ACCESS_TOKEN_TTL=60
MOBILE_API_REFRESH_TOKEN_TTL=30
MOBILE_API_2FA_CHALLENGE_TTL=5
```

## Authentification

### Connexion

`POST /api/v1/mobile/auth/login`

```json
{
  "email": "admin@entreprise.com",
  "password": "mot-de-passe",
  "device_identifier": "uuid-stable-genere-par-l-application",
  "device_name": "Samsung Galaxy S24",
  "platform": "android",
  "app_version": "1.0.0"
}
```

Sans 2FA, la reponse contient `data.tokens.access_token`, `data.tokens.refresh_token` et le profil. Avec 2FA, le serveur repond `202` avec `challenge_token` et `expires_in`, sans jeton d'acces.

### Validation 2FA

`POST /api/v1/mobile/auth/two-factor`

```json
{
  "challenge_token": "...",
  "code": "123456"
}
```

Le champ `recovery_code` peut remplacer `code`. Un challenge expire apres cinq minutes et devient inutilisable apres une validation reussie.

### Rafraichissement

`POST /api/v1/mobile/auth/refresh` avec `Authorization: Bearer <refresh_token>`.

La reponse retourne une nouvelle paire. L'application doit remplacer atomiquement les deux anciennes valeurs dans son stockage securise.

### Deconnexion

- `POST /api/v1/mobile/auth/logout` ferme la session de l'appareil courant.
- `POST /api/v1/mobile/auth/logout-all` ferme toutes les sessions mobiles du compte.

Ces routes utilisent le jeton d'acces.

## Endpoints authentifies

Toutes les routes suivantes utilisent `Authorization: Bearer <access_token>` :

| Methode | Route | Usage |
| --- | --- | --- |
| GET | `/api/v1/mobile/bootstrap` | Profil, permissions et personnalisation en lecture seule |
| GET | `/api/v1/mobile/me` | Profil courant |
| GET | `/api/v1/mobile/dashboard` | Indicateurs, vehicules et alertes recentes |
| GET | `/api/v1/mobile/drivers` | Chauffeurs visibles en lecture seule, sans identifiant RFID/iButton |
| GET | `/api/v1/mobile/departments` | Departements visibles et capacites de gestion |
| POST | `/api/v1/mobile/departments` | Creation pour admin client ou superadmin |
| PUT | `/api/v1/mobile/departments/{id}` | Modification ou desactivation autorisee |
| DELETE | `/api/v1/mobile/departments/{id}` | Suppression superadmin si aucun chauffeur n'est affecte |
| GET | `/api/v1/mobile/vehicles` | Liste paginee et filtree des vehicules |
| GET | `/api/v1/mobile/vehicles/{id}` | Detail operationnel d'un vehicule visible |
| GET | `/api/v1/mobile/vehicles/{id}/details` | Rubriques detaillees de suivi, conducteur, alimentation, GSM, diagnostic, OBD/CAN et evenements |
| POST | `/api/v1/mobile/vehicles/{id}/engine-commands` | Immobilisation ou autorisation de demarrage securisee |
| GET | `/api/v1/mobile/vehicles/{id}/trips` | Trajets et trace GeoJSON d'un vehicule visible |
| GET | `/api/v1/mobile/map/vehicles` | GeoJSON live des vehicules, avec permission `map.view` |
| GET | `/api/v1/mobile/alerts` | Alertes paginees |
| GET | `/api/v1/mobile/events` | Evenements vehicule pagines |

Les listes acceptent `per_page` de 1 a 50. La recherche des vehicules et de la carte porte uniquement sur le nom et l'immatriculation. Un filtre `fleet_id` forge par un client est ignore ; seul le superadmin peut l'utiliser.

Pour un superadmin, le dashboard retourne egalement `summary.fleets_total` et
`fleet_distribution`. Cette repartition est agregee directement en base avec le
nombre total et le nombre de vehicules en ligne par flotte ; elle ne depend donc
pas de la pagination de la liste des vehicules. Pour un compte client,
`fleet_distribution` reste vide et les indicateurs demeurent limites a sa flotte.

La route cartographique retourne les memes etats operationnels que le web :
`is_moving`, `is_parking`, `is_stationary_running`, vitesse, cap, contact,
statut et qualite GPS, pourcentage du signal reseau, niveau de batterie et
derniere date de signal. Pour un vehicule en mouvement, `trail` contient au plus
les points GPS recents, continus et valides necessaires a l'interpolation du
marqueur. Le client mobile actualise cette route toutes les 10 secondes lorsque
la carte est active et anime le deplacement pendant 5 secondes.

Un traceur en ligne avec des coordonnees valides conserve le statut GPS
`available` meme lorsque le compteur de satellites n'est pas transmis. Les
pourcentages reseau et batterie restent `null` lorsqu'aucune mesure native n'est
fournie ; l'interface ne fabrique pas de valeur a partir du seul statut en ligne
ou de la tension de batterie.

La route des trajets accepte `period=today|yesterday|week|current_month|last_month|custom`.
Pour une periode personnalisee, elle exige `start_date` et `end_date`. La reponse
contient les segments, leur trace GeoJSON et un resume kilometrique sans exposer
l'identifiant, l'IMEI, le nom ou le modele technique du traceur.

## Cloisonnement et donnees sensibles

Les scopes Laravel existants sont appliques a chaque requete. Un admin ou utilisateur ne peut lire que les vehicules, positions, alertes et evenements de sa flotte.

Les ressources mobiles orientent le suivi autour du vehicule. Elles ne retournent
pas l'IMEI, l'identifiant interne, le nom, la marque ni le modele technique du
traceur. Le statut de communication, la position, la vitesse, le cap et les
informations operationnelles utiles restent disponibles. Le detail enrichi peut
retourner le protocole, le codec, l'operateur et la SIM comme le modal web client.

Le endpoint mobile detaille reprend la structure operationnelle du modal web :
identite du vehicule, emplacement, conducteur de la session active identifie par RFID/iButton/NFC,
alimentation, GSM, diagnostic, OBD/CAN, etat d'immobilisation autorise et cinq derniers evenements. Les identifiants chauffeur restent masques pour les comptes clients.

Un UID memorise historiquement sur le traceur n'est jamais utilise pour presenter un conducteur courant : la session doit etre active, appartenir au meme vehicule et correspondre a un chauffeur actif et autorise. Le mot CAN P4/AVL 517 est decode bit par bit ; `engine_running` correspond uniquement au bit moteur 11 et non a la valeur numerique complete.

`engine_control` ne contient ni texte de commande ni snapshot de securite. Il expose `outputs.1` et `outputs.2`, chacun avec `number`, `active`, `busy` et `next_action`. La commande POST exige `{action: "immobilize"|"release", output: 1|2, confirmation: true}`. L'action `immobilize` active uniquement la sortie choisie apres validation de l'arret moteur et ne coupe jamais le moteur pendant la conduite ; `release` la desactive sans modifier l'autre sortie.

Pendant une commande, l'application actualise automatiquement le detail du vehicule toutes les cinq secondes jusqu'a un etat terminal. Une reponse immediate confirmant la sortie ciblee termine la commande sans attendre un nouveau point GPS ; une reponse mise en file (`QUEUED`) reste en attente de telemetrie posterieure a l'envoi. Les commandes arrivees a expiration ne maintiennent jamais l'interface en etat occupe.

Les lectures de position du detail utilisent l'index compose `device_id/gps_time` et ne chargent le paquet brut complet que pour la position effectivement affichee. Le temps de reponse reste ainsi stable pour les traceurs possedant plusieurs centaines de milliers de positions. En cas d'erreur transitoire, l'application propose de relancer directement le chargement du detail.

## Codes d'erreur metier

- `ACCOUNT_UNAVAILABLE` : compte desactive ou flotte client absente.
- `INVALID_ACCESS_TOKEN` : refresh token ou jeton non mobile utilise sur une route de donnees.
- `MOBILE_SESSION_EXPIRED` : session d'appareil expiree ou revoquee.
- `INVALID_REFRESH_TOKEN` : jeton d'acces utilise pour rafraichir.
- `REFRESH_SESSION_EXPIRED` : paire de rafraichissement expiree ou deja remplacee.

Les erreurs de validation utilisent le format Laravel `422` avec le champ `errors`.
