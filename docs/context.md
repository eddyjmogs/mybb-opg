# context.md — Mapa tecnico del foro

## Vista general

Este repo contiene una instalacion completa de MyBB personalizada para One Piece Gaiden. Hay tres capas que conviven en el mismo runtime:

1. **Foro MyBB**: usuarios, sesiones, foros, hilos, posts, plugins, templates y panel admin
2. **Capa RPG**: fichas, inventario, tecnicas, economia, viajes, NPCs, akumas, recompensas
3. **Herramientas internas**: staff, narracion, moderacion operativa y una API JSON ligera

No son sistemas separados. Comparten:

- la misma base de datos
- la misma sesion MyBB
- el mismo bootstrap de [`global.php`](/Users/eddymogollon/Documents/Code/mybb-opg/global.php)
- el mismo motor de templates MyBB

## Arquitectura real

```text
Request HTTP
  -> entrypoint PHP (index.php, showthread.php, op/*.php, api/index.php)
  -> global.php
  -> init de MyBB: config, DB, cache, plugins, session, language, templates
  -> op/functions/op_functions.php (incluido globalmente)
  -> logica de pagina
  -> $templates->get(...) + eval(...)
  -> output_page(...) o json(...)
```

## La carpeta `templates/`

Este punto merece dejarse cristalino:

La carpeta [`templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates) contiene **codigo frontend de referencia** y una **exportacion local** de templates almacenados en la base de datos. Es muy util para trabajar la UI con Git, comparar cambios y tener una vista legible del frontend, pero **no es el source of truth de ejecucion por si sola**.

MyBB renderiza desde la tabla `mybb_templates`, y luego cachea/inyecta esos templates en runtime mediante `$templates->get(...)`.

Subdirectorios actuales:

- [`templates/mybb_templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates/mybb_templates): frontend general del foro
- [`templates/op_templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates/op_templates): frontend del sistema RPG y del staff

Scripts relacionados:

- [`export_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/export_templates.php): exporta DB -> archivos
- [`import_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/import_templates.php): importa archivos -> DB
- [`watch_templates.sh`](/Users/eddymogollon/Documents/Code/mybb-opg/watch_templates.sh): observa cambios y dispara import

Consecuencia practica:

- `templates/` es referencia editable y versionable
- `mybb_templates` en DB es la fuente efectiva que usa MyBB
- `cache/themes/` es salida generada, no el lugar correcto para cambiar frontend

## Sistemas principales

### Foro base

Entrypoints raiz como [`index.php`](/Users/eddymogollon/Documents/Code/mybb-opg/index.php), [`forumdisplay.php`](/Users/eddymogollon/Documents/Code/mybb-opg/forumdisplay.php), [`showthread.php`](/Users/eddymogollon/Documents/Code/mybb-opg/showthread.php), [`newthread.php`](/Users/eddymogollon/Documents/Code/mybb-opg/newthread.php) y [`newreply.php`](/Users/eddymogollon/Documents/Code/mybb-opg/newreply.php) manejan el flujo normal del foro.

Hay personalizacion fuerte en:

- [`global.php`](/Users/eddymogollon/Documents/Code/mybb-opg/global.php)
- [`index.php`](/Users/eddymogollon/Documents/Code/mybb-opg/index.php)
- [`member.php`](/Users/eddymogollon/Documents/Code/mybb-opg/member.php)
- [`showthread.php`](/Users/eddymogollon/Documents/Code/mybb-opg/showthread.php)
- [`newthread.php`](/Users/eddymogollon/Documents/Code/mybb-opg/newthread.php)
- [`newreply.php`](/Users/eddymogollon/Documents/Code/mybb-opg/newreply.php)

### Capa RPG en `/op/`

[`op/`](/Users/eddymogollon/Documents/Code/mybb-opg/op) concentra la mayor parte del juego:

- fichas y progresion
- economia
- crafteo y entrenamientos
- tecnicas y akumas
- viajes, islas y mapa
- mercado negro e intercambios
- contenido especial como cronologia, periodico, cartas, IA, etc.

La mayoria de estas paginas renderizan templates `op_*` via MyBB. Ejemplos claros:

- [`op/ficha.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/ficha.php)
- [`op/crafteo.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/crafteo.php)
- [`op/entrenamiento.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/entrenamiento.php)
- [`op/intercambio.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/intercambio.php)
- [`op/mercado_negro.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/mercado_negro.php)

### Staff en `/op/staff/`

[`op/staff/`](/Users/eddymogollon/Documents/Code/mybb-opg/op/staff) existe realmente y contiene **57 archivos**. No es una nota teorica: es una capa operativa importante del foro.

Algunas herramientas destacadas:

- fichas: `fichas_en_cola.php`, `ficha_atributos.php`, `modificar_ficha.php`
- objetos: `objetos_crear.php`, `objetos_modificar.php`, `objetos_ficha.php`
- tecnicas: `tecnicas_crear.php`, `tecnicas_modificar.php`, `tecnicas_ficha.php`
- akumas y virtudes
- entrega de recompensas
- salarios, avisos, peticiones, logs y consolas internas

### API en `/api/`

La API en [`api/`](/Users/eddymogollon/Documents/Code/mybb-opg/api) es pequena y acoplada al foro:

- [`api/index.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/index.php): router y JWT
- [`api/auth.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/auth.php): login manual y `me`
- [`api/threads.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/threads.php): listados de foro/subforos
- [`api/thread.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/thread.php): hilo con posts
- [`api/new_reply.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/new_reply.php): alta de respuesta
- [`api/ficha.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/ficha.php): datos de ficha

No usa una capa ORM ni un framework externo. Son consultas directas sobre tablas MyBB.

## Helpers compartidos y duplicidad

El helper importante es [`op/functions/op_functions.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/functions/op_functions.php). De hecho [`global.php`](/Users/eddymogollon/Documents/Code/mybb-opg/global.php#L498) lo carga en cada request normal.

Tambien existe [`op/op_functions.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/op_functions.php), una copia historica parecida. Para cambios nuevos conviene partir de `op/functions/op_functions.php`, no asumir que ambas versiones estan sincronizadas.

Funciones clave:

- `does_ficha_exist($uid)`
- `select_one_query_with_id(...)`
- `log_audit(...)`
- `log_audit_currency(...)`
- `is_staff($uid)`
- `is_narra($uid)`
- `is_mod($uid)`
- `is_user($uid)`
- `log_security_event(...)`

## Flujo de render de frontend

El frontend del foro no se decide solo por archivos PHP. Intervienen varias capas:

1. template MyBB en DB
2. export local opcional en `templates/`
3. CSS generado en `cache/themes/theme*/`
4. JS de `jscripts/`
5. assets en `images/` y `fonts/`

Por eso, cuando una pagina "se ve rara", puede estar afectada por:

- el template HTML de MyBB
- el CSS cacheado del tema
- un plugin que inserta markup
- logica PHP que prepara variables para el template

## Plugins y BBCodes

En [`inc/plugins/`](/Users/eddymogollon/Documents/Code/mybb-opg/inc/plugins) hay tanto plugins clasicos de MyBB como extensiones muy propias del juego.

Plugins/customizaciones destacadas:

- `accountswitcher`
- `newpoints`
- `recentthread` y `recentthreads`
- `rt_chat`
- `rt_discord_webhooks`
- `styleUsernames`
- BBCodes `BBCustom_*`

Los BBCodes custom conectan posts del foro con el sistema RPG:

- ficha
- ficha secreta
- dado
- objeto
- tecnica
- npc
- consumir
- mantenida
- recursos
- hide
- spoiler

## Base de datos y persistencia

El dump [`rovddqmy_op.sql`](/Users/eddymogollon/Documents/Code/mybb-opg/rovddqmy_op.sql) muestra:

- **72 tablas `mybb_op_*`**
- **2 triggers**
- tablas core MyBB personalizadas con columnas extra
- plugins con tablas propias como `mybb_rtchat`, `mybb_rt_discord_webhooks`, `mybb_newpoints_*`

La integridad esta gestionada sobre todo desde PHP; no hay una malla fuerte de foreign keys que proteja el dominio.

## Riesgos y gotchas

- Mucha logica usa SQL interpolado manualmente
- La API tiene `JWT_SECRET = 'test'`
- `templates/` puede inducir a error si se toma como runtime directo
- `cache/themes/` es generado y puede sobreescribirse
- Hay helpers duplicados en `op/functions/op_functions.php` y `op/op_functions.php`
- Hay archivos grandes y muy personalizados donde conviene leer con foco antes de tocar

## Heuristica practica para trabajar en el foro

Si el cambio es de comportamiento:

- empieza por el PHP que arma variables y permisos
- revisa luego el template MyBB correspondiente

Si el cambio es visual:

- usa `templates/` como referencia de frontend
- confirma el nombre del template real en `$templates->get(...)`
- recuerda importar a DB si quieres que el foro lo use de verdad

Si el cambio es de datos:

- revisa primero [`docs/DATABASE.md`](/Users/eddymogollon/Documents/Code/mybb-opg/docs/DATABASE.md)
- luego valida en el dump y en el codigo que consume esa tabla
