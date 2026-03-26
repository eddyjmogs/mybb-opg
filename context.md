# context.md — Technical Reference

## System Architecture

The application is a MyBB 1.8.36 forum with a custom RPG layer. Two codebases coexist:

1. **MyBB core** (`/inc/`, root-level PHP files) — handles users, sessions, threads, posts, templates, plugins, caching
2. **Game system** (`/op/`, `/opg/`, `BBCustom_*.php` plugins) — handles characters, combat, items, training, economy, travel

They share the same MySQL database and the same user session. Game pages include MyBB's `global.php` to bootstrap the full framework, then use `$db` and `$mybb->user` directly.

## How Major Systems Connect

```
User Request
    │
    ▼
Page Script (e.g., op/ficha.php)
    │
    ├── global.php ──► inc/init.php (bootstrap)
    │                     ├── class_core.php     ($mybb)
    │                     ├── db_mysqli.php       ($db)
    │                     ├── class_templates.php ($templates)
    │                     ├── class_datacache.php ($cache)
    │                     ├── class_plugins.php   ($plugins)
    │                     └── class_session.php   ($session)
    │
    ├── inc/config.php (DB credentials)
    ├── op/functions/op_functions.php (game helpers)
    │
    ▼
Game Logic
    ├── $db->query() / simple_select() ──► mybb_op_* tables
    ├── $mybb->user['uid'] ──► auth/permission checks
    ├── is_staff() / is_narra() ──► role gates
    └── output_page() or JSON response
```

### Character Sheet Flow
1. User creates character via `op/ficha_crear.php` → inserts into `mybb_op_fichas`
2. Staff approves via `op/staff/fichas_en_cola.php` → sets `aprobada_por` field
3. Character displayed via `op/ficha.php` → reads from `mybb_op_fichas`, `mybb_op_inventario`, `mybb_op_virtudes_usuarios`, `mybb_op_tec_aprendidas`, `mybb_op_akumas`
4. `[ficha]` BBCode in posts renders inline character data via `inc/plugins/BBCustom_ficha.php`

### Combat Flow
1. Players post in combat threads using `[personaje]`, `[ficha]`, `[npc=X]` markers
2. `op/api_combate.php` (JSON API) reads thread posts and extracts combat participants
3. Key functions: `getCombatData()`, `getCharacterData()`, `getMyTechniques()`, `getMyWeapons()`, `checkCombatReady()`
4. Damage and roll calculations handled server-side
5. Dice rolls via `[XdY]` BBCode processed by `BBCustom_dado.php`, results stored in `mybb_op_dados`

### Economy Flow
- Currency stored on `mybb_op_fichas`: `berries` (primary), `nika` (secondary), `kuro` (shop), `puntos_oficio` (craft points)
- `newpoints` on `mybb_users` tracks experience/role points via the NewPoints plugin
- Shops: `op/tienda.php`, `op/tienda_kuros.php`, `op/mercado_negro.php`
- Trading between players: `op/intercambio.php` → `mybb_op_intercambios`
- All currency changes logged via `log_audit_currency()` in `op_functions.php`

### Training & Progression Flow
- Stat training: `op/entrenamiento.php` → `mybb_op_entrenamientos_usuarios` (time-gated)
- Technique training: `op/entrenamiento_tecnicas.php` → `mybb_op_tecnicas_usuarios` (time-gated with `timestamp_end`)
- Upon completion, passive stat bonuses applied to `mybb_op_fichas` (e.g., `fuerza_pasiva += 5`)
- Crafting: `op/crafteo.php` → `mybb_op_crafteo_usuarios` (also time-gated)
- Experience limits per tier: `mybb_op_experiencia_limite`

### Item/Object System
- Item definitions: `mybb_op_objetos` — 30+ columns including `objeto_id`, `nombre`, `categoria`, `subcategoria`, `tier`, `comerciable`, `negro`, `negro_berries`, `berries`, `berriesCrafteo`, `dano`, `bloqueo`, `alcance`, `efecto`, `espacios`, `imagen`, `custom`, `editable`, `fusion_tipo`, `engastes`, `oficio`, `nivel`, `tiempo_creacion`, `requisitos`, `escalado`
- User inventory: `mybb_op_inventario` — unique key on `(objeto_id, uid)`. Columns: `objeto_id`, `uid`, `cantidad`, `imagen`, `apodo`, `autor`, `autor_uid`, `oficios` (JSON), `especial`, `editado`, `usado`, `vendidoReciente`
- Custom items created by users get ID format: `{base_id}-{uid}-{count}` with `custom=1` in `mybb_op_objetos`
- Equipment snapshots per combat thread: `mybb_op_equipamiento_personaje` — stores `equipamiento` as JSON keyed by `(tid, pid, uid)`
- Crafting recipe unlocks: `mybb_op_inventario_crafteo` — tracks `objeto_id`, `nombre`, `uid`, `desbloqueado`
- Item consumption via `[consumir=ID]` BBCode → `BBCustom_consumir.php` → stored in `mybb_op_consumir` with `(tid, pid, uid, counter, objeto_id, content)`
- Underworld auction house: `mybb_Objetos_Inframundo` — bid/buy-now system with `estado` enum (activa, vendida, cancelada, finalizada_sin_venta)

## Database Schema

> **Complete schema reference is in `DATABASE.md`** — every table, column, type, default, index, trigger, and entity relationship. Source dump: `rovddqmy_op.sql` (MySQL 5.7).

### Quick Reference

- **69 custom game tables** prefixed `mybb_op_*`, mixed InnoDB/MyISAM, no foreign key constraints
- **2 database triggers:** `u_fichas_triggers` (snapshots character changes → `mybb_audit_op_fichas`) and `u_users_triggers` (logs `newpoints` changes → `mybb_audit_users`)
- **Custom columns on MyBB core tables:**
  - `mybb_posts.faccionsecreta` (varchar 80) — secret faction override per post
  - `mybb_threads.prefix` — thread type codes: 3=Aventura, 10=MT, 1=Común, 6=Evento, 9=Autonarrada, 14=Requerimiento
  - `mybb_threads.year` / `estacion` / `day` — in-game date fields
  - `mybb_users.newpoints` (decimal 16,2) — experience/role points (trigger-monitored)
  - `mybb_users.as_*` fields — Account switcher plugin columns

### Key Tables by Domain

| Domain | Tables | Notes |
|--------|--------|-------|
| Character | `fichas`, `fichas_secret`, `fichas_guardar`, `fichas_audit` | `fichas.fid` = `users.uid` (1:1). Trigger writes audit on every UPDATE |
| Items | `objetos`, `inventario`, `inventario_crafteo`, `cambioid`, `Objetos_Inframundo` | `inventario` has unique key on `(objeto_id, uid)`. Auction house in `Objetos_Inframundo` |
| Techniques | `tecnicas`, `tec_aprendidas`, `tec_para_aprender`, `tecnicas_usuarios`, `tecnicas_mantenidas` | `tecnicas.tid` is a string ID (e.g., `"DCO001"`). Training queue uses `tiempo_iniciado`/`tiempo_finaliza` |
| Combat | `thread_personaje`, `dados`, `equipamiento_personaje`, `mantenidas_html`, `tiradanaval`, `consumir` | `thread_personaje` snapshots all stats at combat entry. `dados` stores BBCode dice results |
| Powers | `akumas`, `virtudes`, `virtudes_usuarios` | `akumas.akuma_id` is string PK. `virtud_id` uses `V***`/`D***` prefix |
| NPCs | `npcs`, `npcs_usuarios`, `mascotas` | NPC has full stat block. `npcs_usuarios`/`mascotas` link NPCs to owners |
| Economy | `kuros`, `intercambios`, `codigos_admin`, `codigos_usuarios`, `recompensas_usuarios` | Currencies live on `fichas` (berries, nika, kuro). `intercambios` tracks both sender and receiver |
| Training | `entrenamientos_usuarios`, `crafteo_usuarios`, `crafteo_npcs`, `creacion_usuarios`, `oficios_usuarios` | All time-gated with `timestamp_end`. `entrenamientos_usuarios` has unique `uid` (one active per user) |
| World | `islas`, `isla_eventos`, `viajes`, `razas`, `barcos`, `mapa_posiciones`, `misiones_lista` | `isla_eventos` has season enum. `barcos` defines ship stats. `mapa_posiciones` stores % coordinates |
| Gacha | `tirada_akumas`, `tirada_cofre`, `tirada_haki`, `tirada_rey`, `cofres` | `cofres` is the loot table (weighted by `peso`). Roll results in `tirada_*` tables |
| Adventures | `peticionAventuras`, `peticionAventuras_meta` | `estado`: 0=created, 1=approved, 2=denied, 3=finished, 4=pending narrator, 5=deletion request |
| Content | `sabiasque`, `likes`, `leidos`, `hide`, `hentai`, `adviento_abiertos`, `avisos`, `peticiones` | `likes` tracks who liked which post. `hentai` is adult content opt-in per user |
| Audit | 14 audit tables | `audit_general` is the catch-all. Specialized audits for stats, crafting, training, rewards, mod actions, AI detection |

## Authentication & Session Flow

1. User logs in via `member.php` → `LoginDataHandler` validates credentials
2. MyBB creates session in `mybb_sessions` table, sets `mybbuser` cookie with `uid_loginkey`
3. On each request, `$session->init()` reads cookie, calls `load_user($uid, $loginkey)`, queries `mybb_users` + `mybb_userfields`
4. `$mybb->user` populated with full user record; `$mybb->usergroup` has permissions
5. CSRF: `generate_post_check()` creates token from user salt + timestamp; `verify_post_check()` validates on POST

### API Authentication (separate)
- `/api/index.php` implements JWT auth
- Login via POST to `?route=auth/login` with username/password → returns JWT token
- Subsequent requests send `Authorization: Bearer <token>` header
- `bearer_uid()` decodes JWT and returns `uid`
- `jwt_sign()` and `jwt_payload()` handle token creation/validation

## Key Entry Points

| URL Pattern | File | Purpose |
|-------------|------|---------|
| `/index.php` | `index.php` | Forum homepage |
| `/showthread.php?tid=X` | `showthread.php` | View thread |
| `/newthread.php?fid=X` | `newthread.php` | Create thread |
| `/newreply.php?tid=X` | `newreply.php` | Post reply |
| `/member.php?action=login` | `member.php` | Login |
| `/member.php?action=profile&uid=X` | `member.php` | User profile |
| `/op/ficha.php?id=X` | `op/ficha.php` | Character sheet |
| `/op/ficha_crear.php` | `op/ficha_crear.php` | Create character |
| `/op/inventario.php` | `op/inventario.php` | View inventory |
| `/op/mercado_negro.php` | `op/mercado_negro.php` | Black market shop |
| `/op/crafteo.php` | `op/crafteo.php` | Crafting system |
| `/op/entrenamiento.php` | `op/entrenamiento.php` | Stat training |
| `/op/entrenamiento_tecnicas.php` | `op/entrenamiento_tecnicas.php` | Technique training |
| `/op/viajes.php` | `op/viajes.php` | Travel/sailing |
| `/op/api_combate.php` | `op/api_combate.php` | Combat JSON API |
| `/op/staff/*` | `op/staff/*.php` | Staff admin tools |
| `/api/index.php?route=X` | `api/index.php` | REST API |
| `/xmlhttp.php?ext=rt_chat` | `xmlhttp.php` | Real-time chat AJAX |

## Custom BBCode Processors

All in `inc/plugins/`, hook into the post parser:

| Tag | File | What It Does |
|-----|------|-------------|
| `[ficha]` | `BBCustom_ficha.php` | Renders inline character sheet from `mybb_op_fichas` |
| `[fichasecreta]` | `BBCustom_fichasecreta.php` | Renders secret character sheet from `mybb_op_fichas_secret` |
| `[dado]` / `[XdY]` | `BBCustom_dado.php` | Dice roller — rolls and stores results in `mybb_op_dados` |
| `[objeto=ID]` | `BBCustom_objeto.php` | Displays item card from `mybb_op_objetos` |
| `[tecnica=TID]` | `BBCustom_tecnica.php` | Displays technique card from `mybb_op_tecnicas` |
| `[npc=ID]` | `BBCustom_npc.php` | Renders NPC data from `mybb_op_npcs` |
| `[consumir=ID]` | `BBCustom_consumir.php` | Consumes item from inventory |
| `[mantenida=ID]` / `[fin=ID]` | `BBCustom_mantenida.php` | Activates/deactivates sustained techniques |
| `[recursos=X]` | `BBCustom_recursos.php` | Displays HP/energy/haki bars |
| `[personajesecreto]` | `BBCustom_personajesecreto.php` | Switches to secret identity |
| `[hide]` | `BBCustom_hide.php` | Content visible only to staff/author |
| `[cerrado]` | `BBCustom_cerrado.php` | Closes the thread |
| `[spoiler]` | `BBCustom_spoiler.php` | Collapsible spoiler box |

## External Service Integrations

### Discord Webhooks
- **Plugin:** `inc/plugins/rt_discord_webhooks.php`
- **Tables:** `mybb_rt_discord_webhooks`, `mybb_rt_discord_webhooks_logs`
- **API function:** `fetch_api()` in `inc/plugins/rt/src/DiscordWebhooks/functions.php`
- **Triggers:** New threads, new replies, user registrations, post edits
- Converts BBCode to Discord Markdown automatically

### VPN/Proxy Detection
- Implemented in `index.php`, `newthread.php`, and `op/ficha.php`
- Three providers with failover: VPNApi.io → ProxyCheck.io → IPHub.info
- Detections logged to `mybb_op_avisosvpn`
- Uses `fetch_remote_file()` or cURL for API calls

### Real-Time Chat
- **Plugin:** `inc/plugins/rt_chat.php`
- **Frontend:** `jscripts/rt_chat.js` (uses Fetch API)
- **Endpoint:** `/xmlhttp.php?ext=rt_chat&action=insert_update_message|load_messages|delete_message`
- Zero-database design — uses Redis/Memcache for storage

### External Database
- `op/db_extra.php` connects to an external database at `bdopg.iceiy.com`
- Used for data that lives outside the main forum database (exact tables not defined in the local SQL dump)

## Shared Game Functions

**File: `op/functions/op_functions.php`**

| Function | Purpose |
|----------|---------|
| `does_ficha_exist($uid)` | Returns character if exists and approved |
| `select_one_query_with_id($table, $id_name, $id)` | Generic single-row SELECT |
| `get_obj_from_query($query)` | Extract single result from query |
| `log_audit($uid, $username, $categoria, $log)` | Log to `mybb_op_audit_general` |
| `log_audit_currency($uid, $username, $user_uid, $cat, $currency, $amount)` | Log currency changes |
| `is_staff($uid)` | Staff check (hardcoded UIDs + groups 4, 6, 14, 16) |
| `is_narra($uid)` | Narrator check (group 15) |
| `is_mod($uid)` | Moderator check |
| `is_user($uid)` | Admin check (UIDs 1, 3, 6, 7, 9) |
| `is_peti_mod($uid)` | Petition moderator (UID 1 only) |
| `log_security_event($tipo, $endpoint, $extra_info)` | Log to `mybb_op_security_log` |

**File: `op/functions/security_monitor.php`**
- Rate limiting and security monitoring
- Logs to `mybb_op_security_log`
