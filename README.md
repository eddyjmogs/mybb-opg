# One Piece Gaiden

Foro de rol personalizado ambientado en el universo de One Piece, construido sobre MyBB 1.8 con un sistema de juego completamente personalizado.

## Stack

- **Backend:** PHP 7.0+ / MySQL (MySQLi)
- **Motor de foro:** MyBB 1.8
- **Frontend:** jQuery, AJAX, SCEditor
- **APIs externas:** VPN detection (VPNApi.io, ProxyCheck.io, IPHub.info), Discord Webhooks

## Estructura principal

```
/
├── op/          # Sistema de juego principal (fichas, combate, tiendas, crafteo, etc.)
│   └── staff/   # Herramientas de administración para staff
├── opg/         # Sistema de gacha (cofres, akumas, haki)
├── api/         # Endpoints REST para acceso programático
├── inc/
│   ├── plugins/ # Plugins de MyBB y BBCodes personalizados
│   └── config.php  # Configuración de base de datos (NO commitear)
├── jscripts/    # JavaScript del cliente (mapas, cartas, calculadoras)
└── images/      # Assets estáticos
```

## Sistemas de juego

- **Fichas de personaje** — creación, atributos, facciones y tiers de poder
- **Combate** — API de combate con cálculo de daño y tiradas de dados
- **Entrenamiento** — subida de nivel de técnicas y habilidades
- **Economía** — moneda, tiendas, mercado negro, intercambios
- **Crafteo** — creación y mejora de equipamiento
- **Exploración** — viajes entre islas y aventuras
- **Gacha** — tiradas de frutas del diablo, haki y cofres
- **Social** — sistema de tripulación, jerarquías, recompensas

## Configuración local

### Requisitos

- PHP 7.4+
- MySQL 5.7+ / MariaDB 10.4+
- Apache con `mod_rewrite` habilitado

## Variables de entorno / Claves API

Las siguientes claves se configuran directamente en los archivos indicados (pendiente migrar a variables de entorno):

| Clave | Archivo |
|-------|---------|
| VPNApi.io Key | `index.php`, `newthread.php` |
| ProxyCheck.io Key | `index.php`, `newthread.php` |
| IPHub API Key | `index.php`, `newthread.php` |
| Discord Webhook URL | `inc/plugins/rt_discord_webhooks.php` |

## BBCodes personalizados

Procesadores BBCode específicos del juego ubicados en `inc/plugins/`:

| BBCode | Archivo | Descripción |
|--------|---------|-------------|
| `[ficha]` | `BBCustom_ficha.php` | Hoja de personaje |
| `[fichasecreta]` | `BBCustom_fichasecreta.php` | Ficha oculta |
| `[dado]` | `BBCustom_dado.php` | Tirada de dados |
| `[consumir]` | `BBCustom_consumir.php` | Consumo de objetos |
| `[mantenida]` | `BBCustom_mantenida.php` | Estado mantenido |
