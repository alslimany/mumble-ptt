# PTT Walkie‑Talkie System – Architecture

> EVO‑like Push‑to‑Talk solution for Android and Linux PoC devices.

---

## 1. High‑Level System Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                           CLIENT LAYER                              │
│                                                                     │
│  ┌─────────────────┐  ┌─────────────────┐  ┌────────────────────┐  │
│  │  Android App    │  │  Linux PoC       │  │  Admin Web UI      │  │
│  │  (Kotlin +      │  │  (talKKonnect /  │  │  (Laravel +        │  │
│  │   Jumble lib)   │  │   talkiepi)      │  │   Inertia + React) │  │
│  └────────┬────────┘  └────────┬─────────┘  └────────┬───────────┘  │
└───────────┼────────────────────┼────────────────────┼──────────────┘
            │  HTTPS + WSS       │  HTTPS + WSS        │  HTTPS + WSS
            ▼                    ▼                     ▼
┌─────────────────────────────────────────────────────────────────────┐
│                           EDGE LAYER                                │
│          ┌──────────────────────────────────────────┐               │
│          │   NGINX (SSL termination, load balancing, │               │
│          │          reverse proxy, static assets)    │               │
│          └──────────────────────────────────────────┘               │
└───────────────────────────────────┬─────────────────────────────────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            ▼                       ▼                       ▼
┌───────────────────┐  ┌───────────────────────┐  ┌───────────────────┐
│  Laravel 12 API   │  │  Laravel Reverb        │  │  Murmur           │
│  (PHP 8.3 / FPM)  │  │  (WebSocket server)    │  │  (Mumble server)  │
│                   │  │                        │  │  Port 64738 TCP/  │
│  - REST API       │  │  - Broadcast channels  │  │  UDP (voice)      │
│  - Ice RPC client │  │  - Echo channels       │  │  Port 6502 (Ice)  │
│  - Queue workers  │  │  - Presence channels   │  └────────┬──────────┘
└────────┬──────────┘  └────────────────────────┘           │ Ice RPC
         │                                                   │
         ▼                                                   ▼
┌───────────────────────────────────────────────────────────────────┐
│                        DATA LAYER                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌───────────────────────┐   │
│  │  PostgreSQL  │  │  Redis        │  │  S3-compatible        │   │
│  │  (primary DB)│  │  (queue,      │  │  Object Storage       │   │
│  │              │  │   cache,      │  │  (voice recordings,   │   │
│  │              │  │   sessions,   │  │   log exports)        │   │
│  │              │  │   broadcast)  │  │                       │   │
│  └──────────────┘  └──────────────┘  └───────────────────────┘   │
└───────────────────────────────────────────────────────────────────┘
```

The system is divided into four horizontal layers:

| Layer | Components | Responsibility |
|---|---|---|
| **Client** | Android app, Linux PoC client, Admin Web UI | User interaction, voice capture, GPS reporting |
| **Edge** | NGINX | SSL termination, routing, rate limiting |
| **Backend Services** | Laravel API, Reverb, Murmur | Business logic, real-time events, voice mixing |
| **Data** | PostgreSQL, Redis, S3 | Persistence, pub/sub, media storage |

---

## 2. Mumble (Murmur) Voice Server via Ice RPC

[Mumble](https://www.mumble.info/) uses the open-source **Murmur** daemon as its server. The server exposes a management interface over [ZeroC Ice](https://zeroc.com/ice) – an advanced RPC protocol that provides a strongly-typed, bi-directional channel.

### How Ice RPC is Used

```
┌──────────────────────────────────────────────────────────────┐
│  Laravel Backend (MumbleIceService)                          │
│                                                              │
│  ice_init() ──► connect to murmur:6502 ──► Proxy object     │
│                                                              │
│  Virtual Server Management:                                  │
│    - createVirtualServer(orgId)   ← per-organisation server  │
│    - setConf(serverId, key, val)  ← configure ports/tokens   │
│    - getUsers(serverId)           ← list connected devices   │
│    - kickUser(serverId, userId)   ← remote force-disconnect  │
│    - setACL(serverId, acl)        ← permission management    │
│                                                              │
│  Channel Management:                                         │
│    - getChannels(serverId)        ← list channels/rooms      │
│    - addChannel(serverId, name)   ← create PTT group/room    │
│    - removeChannel(serverId, id)  ← delete room              │
│    - moveUserToChannel(...)       ← force room switch        │
│                                                              │
│  Callbacks (Murmur ► Laravel):                               │
│    - userConnected / userDisconnected                        │
│    - userStateChanged            ← mute, deaf, channel move  │
│    - userTextMessage             ← signalling messages       │
└──────────────────────────────────────────────────────────────┘
```

**Configuration (`.env`):**

```
MUMBLE_ICE_HOST=murmur
MUMBLE_ICE_PORT=6502
MUMBLE_ICE_SECRET=<strong-shared-secret>
MUMBLE_DEFAULT_SUPERPASSWORD=<admin-password>
```

Each **organisation** owns a dedicated **virtual Mumble server** (MurmurX instance). Channels within the virtual server map to **PTT groups/rooms**. The `MumbleIceService` class in `backend/app/Services/MumbleIceService.php` wraps all Ice calls.

---

## 3. Device Identification (IMEI & Serial)

Every physical device is uniquely identified before it can connect to the system.

### Android Devices (IMEI)

```
┌───────────────────────────────────────────────────────┐
│  Android App (first boot)                             │
│                                                       │
│  1. Request READ_PHONE_STATE permission               │
│  2. TelephonyManager.getImei(slotIndex) ──► IMEI      │
│     └── fallback: Settings.Secure.ANDROID_ID          │
│  3. POST /api/v1/devices/register                     │
│       { imei, model, os_version, org_token }          │
│  4. Receive { device_id, api_token, mumble_cert }     │
│  5. Store securely in EncryptedSharedPreferences       │
└───────────────────────────────────────────────────────┘
```

### Linux PoC Devices (Generated Serial)

```
┌───────────────────────────────────────────────────────┐
│  Linux Device (first boot script)                     │
│                                                       │
│  1. Check /etc/device-id                              │
│  2. If absent:                                        │
│       serial = UUID v4 + machine-id hash              │
│       echo $serial > /etc/device-id                   │
│       chmod 400 /etc/device-id                        │
│  3. POST /api/v1/devices/register                     │
│       { serial, hostname, arch, org_token }           │
│  4. Receive { device_id, api_token, mumble_cert }     │
│  5. Store in /etc/ptt/ (mode 600, owned by ptt user)  │
└───────────────────────────────────────────────────────┘
```

### Backend Registration Flow

```
POST /api/v1/devices/register
          │
          ▼
  Validate org_token
          │
          ▼
  Upsert Device record (devices table)
  ┌──────────────────────────────────────┐
  │  id, organisation_id, identifier,   │
  │  type (android|linux), model,       │
  │  api_token (hashed), status,        │
  │  last_seen_at, created_at           │
  └──────────────────────────────────────┘
          │
          ▼
  Generate Mumble client certificate (PEM)
  via openssl (per-device)
          │
          ▼
  Return { device_id, api_token, mumble_host,
           mumble_port, mumble_cert_pem,
           mumble_cert_key_pem }
```

---

## 4. Real‑Time Control Plane (Laravel Reverb)

[Laravel Reverb](https://reverb.laravel.com/) is the WebSocket server that powers all real-time events between the backend and clients.

### Event Channels

| Channel | Type | Subscribers | Events |
|---|---|---|---|
| `presence-org.{orgId}` | Presence | All org members | `device.online`, `device.offline`, `device.gps_update` |
| `private-device.{deviceId}` | Private | Single device | `force.channel_switch`, `force.mute`, `ptt.override`, `config.update` |
| `private-admin.{userId}` | Private | Admin users | `alert.device_offline`, `recording.ready` |

### Control Plane Flow

```
Admin (Web UI)
      │
      │  POST /api/v1/devices/{id}/force-channel
      ▼
Laravel Controller
      │
      ├── MumbleIceService::moveUserToChannel(serverId, userId, channelId)
      │         (direct Ice RPC call – immediate voice routing)
      │
      └── broadcast(new ForceChannelSwitchEvent($deviceId, $channelId))
                │
                ▼
          Laravel Reverb
                │
                ▼
          Android/Linux client receives WebSocket event
          and acknowledges the new channel
```

### Force PTT Override

The admin can unmute a device remotely:

```
ForceUnmuteEvent ──► private-device.{deviceId}
      │
      ├── Android: MumbleConnection.setMuted(false) via Jumble API
      └── Linux: SIGUSER1 signal to talKKonnect process
```

---

## 5. GPS Location Flow

All field devices report their GPS coordinates to enable live tracking on the admin map.

```
┌────────────────────────────────────────────────────────────────┐
│  Android Device                                                │
│                                                                │
│  FusedLocationProviderClient                                   │
│    └── interval: 30 s (default), 10 s (active PTT)            │
│    └── priority: PRIORITY_HIGH_ACCURACY (GPS on)              │
│    └── background: ForegroundService (notification shown)     │
│                                                                │
│  POST /api/v1/devices/{id}/location                            │
│       { lat, lng, accuracy, speed, heading, battery, ts }     │
└───────────────────────────────────┬────────────────────────────┘
                                    │  REST (HTTPS)
                                    ▼
                         Laravel LocationController
                                    │
                    ┌───────────────┴────────────────┐
                    ▼                                ▼
           device_locations                  broadcast(
           table (append-only)               DeviceGpsUpdated)
                                                    │
                                                    ▼
                                           Reverb ──► Admin UI
                                           (Leaflet map marker
                                            updates live)
```

### GPS Data Retention

- **Hot data** (last 24 h): stored in Redis as sorted set (timestamp score) for instant dashboard retrieval.
- **Warm data** (up to 30 d): PostgreSQL `device_locations` table, indexed on `(device_id, recorded_at)`.
- **Cold data** (> 30 d): exported to S3 as daily Parquet files via scheduled Laravel job.

---

## 6. Voice Recording Archival

Murmur supports recording callbacks; the backend intercepts audio streams and stores them.

```
┌──────────────────────────────────────────────────────────────────┐
│  Recording Pipeline                                              │
│                                                                  │
│  1. Murmur AudioCallback (Ice) ──► RecordingWorker (PHP queue)  │
│       stream: {serverId, channelId, userId, pcm_chunk}          │
│                                                                  │
│  2. RecordingWorker buffers chunks in /tmp (local disk)         │
│       while PTT button is held (userState.selfMute changes)     │
│                                                                  │
│  3. On PTT release:                                             │
│       a. Encode PCM ──► Opus ──► OGG container (ffmpeg)        │
│       b. Upload to S3: recordings/{orgId}/{date}/{uuid}.ogg     │
│       c. Insert into `recordings` table:                        │
│            { id, org_id, device_id, channel_id,                 │
│              started_at, duration_s, s3_key, size_bytes }       │
│       d. Dispatch RecordingReadyEvent ──► admin notification    │
│                                                                  │
│  4. Admin dashboard:                                            │
│       GET /api/v1/recordings?device=&channel=&date=             │
│       GET /api/v1/recordings/{id}/stream                        │
│            └── pre-signed S3 URL (60-second TTL)               │
└──────────────────────────────────────────────────────────────────┘
```

### Retention Policy

| Tier | Duration | Storage |
|---|---|---|
| Active / Recent | 0–7 days | S3 Standard |
| Archive | 7–90 days | S3 Standard-IA |
| Long-term | 90+ days | S3 Glacier Instant |
| Deleted | After configured policy | — |

Retention is configurable per-organisation via `organisations.recording_retention_days`.

---

## 7. Technology Stack Summary

| Component | Technology | Version |
|---|---|---|
| Voice server | Murmur (Mumble) | 1.5.x |
| Voice codec | Opus | RFC 6716 |
| Backend API | Laravel | 12.x |
| PHP runtime | PHP-FPM | 8.3+ |
| Real-time | Laravel Reverb | 1.x |
| Frontend | Inertia.js + React | 2.x / 19.x |
| Mobile | Kotlin + Jumble lib | Coroutines, SDK 26+ |
| Linux client | talKKonnect | latest |
| Database | PostgreSQL | 15+ |
| Cache / Queue | Redis | 7+ |
| Object Storage | S3 / MinIO | — |
| Container | Docker + Compose | 24+ |
| Reverse Proxy | NGINX | 1.25+ |
| Ice RPC | ZeroC Ice | 3.7 |

---

## 8. Security Considerations

- **mTLS** between clients and Murmur: every device gets a unique client certificate issued at registration.
- **API tokens** are stored hashed (SHA-256 + salt) in PostgreSQL; Bearer token auth on every API request.
- **Ice secret** (`--ice-secretfile`) restricts management interface access to the Laravel backend container only.
- **Network segmentation**: Murmur Ice port (6502) is never exposed outside the Docker network.
- **Rate limiting**: NGINX limits registration and location endpoints to prevent flooding.
- **Recording URLs**: always served via pre-signed S3 URLs with short TTL; never publicly accessible.
