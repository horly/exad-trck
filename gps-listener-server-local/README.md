# GPS Listener Server Local

Serveur TCP local de test pour EXAD Tracking.

Il écoute des positions GPS simulées au format JSON ligne par ligne, vérifie que l'IMEI existe dans Laravel, puis met à jour :

- `devices.status`
- `devices.last_seen_at`
- `devices.last_position_at`
- `devices.last_latitude`
- `devices.last_longitude`
- `devices.last_speed`
- `devices.last_angle`
- la table `positions`

## Démarrer le serveur

Depuis la racine du projet Laravel :

```bash
node gps-listener-server-local/src/server.js
```

Par défaut :

- host : `127.0.0.1`
- port : `5027`
- délai offline : `5` minutes

Variables disponibles :

```bash
EXAD_GPS_HOST=127.0.0.1
EXAD_GPS_PORT=5027
EXAD_GPS_STALE_MINUTES=5
EXAD_GPS_STALE_INTERVAL_MS=60000
```

## Format attendu

Une ligne JSON par position :

```json
{"imei":"356307042441013","lat":-4.325,"lng":15.312,"speed":42,"angle":90}
```

Champs optionnels :

- `gps_time`
- `altitude`
- `satellites`
- `ignition`
- `movement`

## Simuler un traceur

```bash
node gps-listener-server-local/src/simulators/fake-tracker-client.js 356307042441013 -4.325 15.312 42 90
```

Le serveur accepte uniquement les IMEI déjà enregistrés dans la table `devices`.
