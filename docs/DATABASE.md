# DATABASE.md — Referencia operativa de la base de datos

Generado y ajustado a partir de [`rovddqmy_op.sql`](/Users/eddymogollon/Documents/Code/mybb-opg/rovddqmy_op.sql).

## Resumen

- **Base de datos:** `rovddqmy_op`
- **Motor esperado:** MySQL 5.7 / MySQLi
- **Prefijo principal:** `mybb_`
- **Tablas RPG:** `mybb_op_*`
- **Cantidad de tablas `mybb_op_*` en el dump actual:** 72
- **Triggers en el dump actual:** 2

## Idea clave

La base de datos no almacena solo contenido del juego. Tambien guarda piezas esenciales del frontend del foro, especialmente los templates de MyBB.

Eso conecta directamente con la carpeta [`templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates):

- en DB, la fuente efectiva de templates vive en `mybb_templates`
- en disco, `templates/` es una exportacion/copia de referencia para trabajar el frontend con Git

Por eso, si se modifica un archivo local en `templates/`, el foro no cambia hasta sincronizar esa edicion con `mybb_templates`.

## Triggers

### `u_fichas_triggers`

- **Evento:** `AFTER UPDATE` sobre `mybb_op_fichas`
- **Rol:** registrar snapshots de cambios de ficha en `mybb_audit_op_fichas`

Impacto practico:

- tocar stats, recursos, haki o monedas en `mybb_op_fichas` genera auditoria
- helpers como `log_audit_currency()` pueden terminar provocando escritura en ficha y luego trigger

### `u_users_triggers`

- **Evento:** `AFTER UPDATE` sobre `mybb_users`
- **Rol:** auditar cambios de `newpoints` en `mybb_audit_users`

## Tablas especialmente importantes

### Core del foro

- `mybb_users`
- `mybb_userfields`
- `mybb_sessions`
- `mybb_forums`
- `mybb_threads`
- `mybb_posts`
- `mybb_templates`
- `mybb_templatesets`
- `mybb_themestylesheets`
- `mybb_settings`

### RPG

- `mybb_op_fichas`
- `mybb_op_fichas_secret`
- `mybb_op_fichas_guardar`
- `mybb_op_objetos`
- `mybb_op_inventario`
- `mybb_op_tecnicas`
- `mybb_op_tec_aprendidas`
- `mybb_op_virtudes`
- `mybb_op_virtudes_usuarios`
- `mybb_op_akumas`
- `mybb_op_entrenamientos_usuarios`
- `mybb_op_crafteo_usuarios`
- `mybb_op_intercambios`
- `mybb_op_viajes`
- `mybb_op_npcs`
- `mybb_op_thread_personaje`
- `mybb_op_dados`

### Auditoria y soporte

- `mybb_op_audit_general`
- `mybb_audit_op_fichas`
- `mybb_op_fichas_audit`
- `mybb_audit_users`
- `mybb_op_avisos`
- `mybb_op_avisosvpn`

### Plugins

- `mybb_rtchat`
- `mybb_rtchat_bans`
- `mybb_rt_discord_webhooks`
- `mybb_rt_discord_webhooks_logs`
- `mybb_newpoints_*`

## Templates en la base

### `mybb_templates`

Es la tabla mas importante para entender el frontend real del foro.

Guarda:

- `title`: nombre del template
- `template`: HTML/texto del template
- `sid`: template set al que pertenece
- `version`, `dateline`

Relacion operacional:

- [`templates/mybb_templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates/mybb_templates) y [`templates/op_templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates/op_templates) son referencia/export local
- `mybb_templates` es la fuente efectiva que usa MyBB al resolver `$templates->get(...)`

### `mybb_templatesets`

Define los sets de templates. Los scripts [`export_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/export_templates.php) e [`import_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/import_templates.php) usan estos sets para mapear carpetas locales a `sid`.

## Tablas del dominio RPG

### `mybb_op_fichas`

Tabla nuclear del personaje.

Contiene, entre otras cosas:

- identidad del personaje
- stats base y pasivas
- recursos
- monedas
- reputacion
- haki y akuma
- bloques JSON como `belicas`, `oficios`, `estilos`, `elementos`, `equipamiento`
- estado de aprobacion

Regla operativa:

- `fid` funciona como identificador del personaje y coincide con el `uid` del usuario en muchos flujos
- actualizar esta tabla tiene efectos de auditoria via trigger

### `mybb_op_fichas_secret`

Identidades secretas alternativas de una ficha.

### `mybb_op_fichas_guardar`

Borradores del proceso de creacion de ficha.

### `mybb_op_objetos`

Catalogo maestro de objetos del juego.

Incluye:

- identificador logico `objeto_id`
- categoria/subcategoria
- stats como dano, bloqueo, alcance
- precio, craft y flags de comerciabilidad
- campos para customizacion y fusion

### `mybb_op_inventario`

Inventario por usuario.

Notas utiles:

- usa `objeto_id` como referencia logica
- tiene campos de customizacion por item
- guarda `oficios` en JSON

### `mybb_op_tecnicas`, `mybb_op_tec_aprendidas`, `mybb_op_tecnicas_usuarios`

Conjunto principal para tecnicas definidas, tecnicas aprendidas y procesos/colas asociados.

### `mybb_op_thread_personaje`

Snapshot de personaje orientado a combates o contexto de hilo. Importante para no asumir que todo se calcula siempre "en vivo" desde la ficha actual.

### `mybb_op_dados`

Persistencia de resultados de tiradas y BBCodes de dados.

## Personalizaciones sobre tablas MyBB

El juego no se limita a tablas `mybb_op_*`. Tambien extiende tablas core del foro. Ejemplos documentados y visibles en dump/codigo:

- `mybb_posts.faccionsecreta`
- `mybb_threads.prefix`
- `mybb_threads.year`
- `mybb_threads.estacion`
- `mybb_threads.day`
- `mybb_users.newpoints`
- varios campos `as_*` por Account Switcher
- campos custom en `mybb_forums` usados por la logica de islas/visibilidad

Conclusión practica:

- no asumir que `mybb_threads` o `mybb_forums` son "vanilla"
- antes de tocar consultas del foro, revisar si la capa RPG depende de columnas adicionales

## Integridad y modelo de datos

La base esta controlada principalmente desde PHP. En la practica eso implica:

- muchas relaciones son logicas, no forzadas por foreign keys
- hay dependencia fuerte en nombres de columnas e IDs hardcodeados
- la consistencia entre ficha, inventario, tecnicas y recompensas se resuelve en codigo

## Riesgos frecuentes al tocar datos

- actualizar `mybb_op_fichas` dispara auditoria
- actualizar `mybb_users.newpoints` tambien dispara auditoria
- cambiar templates solo en disco no cambia el frontend real
- editar `cache/themes/*` no persiste como fuente del tema
- varias consultas usan SQL interpolado y son sensibles a errores o inyeccion si se extienden mal

## Tablas a revisar primero segun tarea

Si la tarea es de frontend:

- `mybb_templates`
- `mybb_templatesets`
- `mybb_themestylesheets`

Si la tarea es de foro:

- `mybb_forums`
- `mybb_threads`
- `mybb_posts`
- `mybb_users`

Si la tarea es de personaje:

- `mybb_op_fichas`
- `mybb_op_fichas_secret`
- `mybb_op_fichas_guardar`
- `mybb_audit_op_fichas`

Si la tarea es de objetos/economia:

- `mybb_op_objetos`
- `mybb_op_inventario`
- `mybb_op_intercambios`
- `mybb_Objetos_Inframundo`

Si la tarea es de tecnicas/combate:

- `mybb_op_tecnicas`
- `mybb_op_tec_aprendidas`
- `mybb_op_thread_personaje`
- `mybb_op_dados`
- `mybb_op_consumir`

## Referencias relacionadas

- [`docs/CLAUDE.md`](/Users/eddymogollon/Documents/Code/mybb-opg/docs/CLAUDE.md)
- [`docs/context.md`](/Users/eddymogollon/Documents/Code/mybb-opg/docs/context.md)
- [`import_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/import_templates.php)
- [`export_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/export_templates.php)
