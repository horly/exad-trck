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
- `devices.last_movement`
- `devices.last_satellites`
- `devices.last_gsm_signal`
- `devices.last_battery_level`
- `devices.last_external_voltage`
- `devices.last_battery_voltage`
- la table `positions`
- la table `tracker_events`
- les alertes de signal

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
- `gsm_signal`
- `battery_level`
- `external_voltage`
- `battery_voltage`
- `address`
- `ignition`
- `movement`

## Simuler un traceur

```bash
node gps-listener-server-local/src/simulators/fake-tracker-client.js 356307042441013 -4.325 15.312 42 90 80 90 12.4 4.1
```

Le serveur accepte uniquement les IMEI déjà enregistrés dans la table `devices`.
