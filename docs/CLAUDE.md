# CLAUDE.md — One Piece Gaiden

## Resumen del proyecto

One Piece Gaiden es un foro de rol en espanol construido sobre **MyBB 1.8.36** con una capa RPG muy personalizada. El repositorio no es un tema aislado: incluye el core del foro, overrides del frontend, plugins, una API propia y decenas de modulos del juego en `/op/` y `/opg/`.

## Stack real

- **Backend:** PHP con MySQLi
- **Motor del foro:** MyBB 1.8.36
- **Base de datos:** MySQL con prefijo `mybb_`; gran parte del juego vive en tablas `mybb_op_*`
- **Frontend:** templates de MyBB + CSS cacheado por temas + JS custom en `jscripts/`
- **Integraciones:** JWT para la API interna, Discord webhooks, deteccion VPN/proxy

## Como esta organizado el repo

```text
/                       -> entrypoints MyBB y paginas principales del foro
/admin/                 -> panel de administracion de MyBB
/api/                   -> API JSON minima (auth, ficha, threads, thread, new_reply)
/cache/                 -> archivos generados por MyBB, incluidos CSS compilados por tema
/docs/                  -> documentacion operativa del repo
/images/                -> assets estaticos
/inc/                   -> core MyBB, plugins y librerias
/jscripts/              -> JS del foro y de sistemas personalizados
/op/                    -> capa RPG principal
/op/staff/              -> 57 herramientas internas para staff/narracion
/op/functions/          -> helpers del juego usados por casi todo `/op/`
/opg/                   -> sistemas de tiradas/gacha puntuales
/templates/             -> export/copia local de templates de MyBB y codigo frontend de referencia
```

## Punto clave sobre `templates/`

La carpeta [`templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates) **no es por si sola la fuente de renderizado en runtime**. MyBB sirve los templates desde la tabla `mybb_templates` de la base de datos mediante `$templates->get(...)`.

Dentro de [`templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates) hay dos exportaciones locales:

- [`templates/mybb_templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates/mybb_templates): templates del frontend general del foro
- [`templates/op_templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates/op_templates): templates de paginas y herramientas del sistema RPG/staff

Estos archivos sirven como:

- referencia visual y de frontend para editar templates con Git
- copia exportada/sincronizable del contenido de `mybb_templates`
- apoyo para reconstruir o comparar cambios de UI

Pero **siguen siendo codigo de referencia hasta que se importa a la base**. La sincronizacion se hace con:

- [`export_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/export_templates.php)
- [`import_templates.php`](/Users/eddymogollon/Documents/Code/mybb-opg/import_templates.php)
- [`watch_templates.sh`](/Users/eddymogollon/Documents/Code/mybb-opg/watch_templates.sh)

Si cambias un `.html` en `templates/` y no lo importas, MyBB no lo reflejara en produccion.

## Flujo de ejecucion

### Foro MyBB

1. El entrypoint define `IN_MYBB` y `THIS_SCRIPT`
2. Carga [`global.php`](/Users/eddymogollon/Documents/Code/mybb-opg/global.php)
3. `global.php` inicializa MyBB, sesion, plugins, cache, lenguaje y templates
4. Muchas paginas cargan templates con `$templates->get('...')`
5. Se renderiza con `eval(...)` y `output_page(...)`

### Capa RPG

La mayoria de las paginas en `/op/` tambien cargan [`global.php`](/Users/eddymogollon/Documents/Code/mybb-opg/global.php) y luego usan [`op/functions/op_functions.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/functions/op_functions.php).

Eso significa que la capa RPG:

- comparte sesion y usuario con el foro
- usa el mismo `$db`, `$mybb`, `$templates`, `$cache`, `$plugins`
- renderiza via templates MyBB, no con un motor aparte

## Archivos y modulos importantes

- [`global.php`](/Users/eddymogollon/Documents/Code/mybb-opg/global.php): bootstrap principal, ademas incluye `op/functions/op_functions.php`
- [`op/functions/op_functions.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/functions/op_functions.php): helpers reales de permisos, auditoria y seguridad
- [`op/op_functions.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/op_functions.php): copia historica parecida; no asumir que es la fuente principal
- [`api/index.php`](/Users/eddymogollon/Documents/Code/mybb-opg/api/index.php): router de API con JWT hardcodeado
- [`inc/plugins/`](/Users/eddymogollon/Documents/Code/mybb-opg/inc/plugins): plugins MyBB y BBCodes custom

## Permisos y helpers del juego

Los checks mas usados viven en [`op/functions/op_functions.php`](/Users/eddymogollon/Documents/Code/mybb-opg/op/functions/op_functions.php):

- `does_ficha_exist($uid)`
- `is_staff($uid)`
- `is_narra($uid)`
- `is_mod($uid)`
- `is_user($uid)`
- `log_audit(...)`
- `log_audit_currency(...)`
- `log_security_event(...)`

Importante: `is_staff()` combina grupos MyBB con una whitelist hardcodeada de UIDs.

## API interna

La API de [`api/`](/Users/eddymogollon/Documents/Code/mybb-opg/api) es pequena y manual:

- `auth/login`
- `me`
- `threads`
- `thread`
- `new_reply`
- `ficha`

No es una capa desacoplada del foro: reutiliza MyBB, consulta tablas directamente y tiene decisiones de seguridad aun muy basicas.

## Base de datos

- El dump principal es [`rovddqmy_op.sql`](/Users/eddymogollon/Documents/Code/mybb-opg/rovddqmy_op.sql)
- Hay **72 tablas `mybb_op_*`** en el dump actual
- Existen **2 triggers**: `u_fichas_triggers` y `u_users_triggers`
- Hay columnas custom tambien en tablas MyBB core como `mybb_posts`, `mybb_threads`, `mybb_users` y `mybb_forums`

Ver [`docs/DATABASE.md`](/Users/eddymogollon/Documents/Code/mybb-opg/docs/DATABASE.md) para detalles.

## Cosas a no asumir

- `templates/` no es el runtime del frontend por si solo
- `cache/themes/` no es fuente editable, es salida generada
- `op/` no es un microservicio separado: esta montado encima de MyBB
- el chat `rt_chat` evita CRUD principal en DB para mensajes, pero el plugin si agrega tablas/configuracion propias
- varios archivos del repo contienen secretos o valores hardcodeados y no deben copiarse sin revisar

## Archivos sensibles o delicados

- `inc/config.php`: credenciales
- `api/index.php`: `JWT_SECRET = 'test'`
- `index.php`, `newthread.php`, `op/ficha.php`: logica e integraciones VPN/proxy sensibles
- `import_templates.php` y `export_templates.php`: tocan DB directamente
- `cache/` y `uploads/`: no tratarlos como codigo fuente

## Regla practica para editar UI

Si el cambio es visual:

1. revisa primero el template correspondiente en [`templates/`](/Users/eddymogollon/Documents/Code/mybb-opg/templates)
2. confirma que template usa realmente la pagina mediante `$templates->get(...)`
3. recuerda que el cambio local es de referencia/export hasta importarlo a DB
4. no edites `cache/themes/*` como fuente principal
