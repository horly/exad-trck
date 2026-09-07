# EXAD GPS listener (production)

Versioned Teltonika TCP listener used by EXAD Tracking. It receives Codec 8/8E/16 AVL records and sends secured Codec 12 commands claimed from Laravel.

Output activation uses `setigndigout`, never `setdigout`: Laravel requires a fresh 30-second stopped telemetry window and the listener applies a final veto immediately before `socket.write()`. Teltonika firmware additionally queues the output change until ignition is off. Every command targets exactly one output and ignores the other state and timeout: DOUT1 uses `1? 0 ?` / `0? 0 ?`, while DOUT2 uses `?1 ? 0` / `?0 ? 0`.

The listener decodes `engine_running` from bit 11 of the complete P4/AVL 517 security word. Driver identifiers prefer the event IO, ignore all-zero sentinels, then fall back to the first non-zero supported RFID/iButton/NFC IO.

Run tests with `npm test`. Deploy this directory only after the Laravel migrations have completed.
