# EXAD GPS listener (production)

Versioned Teltonika TCP listener used by EXAD Tracking. It receives Codec 8/8E/16 AVL records and sends secured Codec 12 commands claimed from Laravel.

Engine immobilization uses `setigndigout`, never `setdigout`: Laravel requires a fresh 30-second stopped telemetry window and the listener applies a final veto immediately before `socket.write()`. Teltonika firmware additionally queues the output change until ignition is off.

Run tests with `npm test`. Deploy this directory only after the Laravel migrations have completed.
