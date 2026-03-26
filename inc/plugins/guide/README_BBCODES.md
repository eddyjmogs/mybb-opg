# Sistema Modular de BBCodes - One Piece Gaiden

## Estructura del Proyecto (por ahora)

```
inc/plugins/
├── lib/
│   ├── spoiler.php              # Librería de spoilers reutilizable
│   └── spoiler_migration.php    # Guía de migración del sistema antiguo
│
├── BBCustom_ficha.php           # [ficha] - Muestra ficha completa
├── BBCustom_npc.php             # [npc=ID] - Muestra NPCs
├── BBCustom_tecnica.php         # [tecnica=TID] - Muestra técnicas
├── BBCustom_objeto.php          # [objeto=ID] - Muestra objetos/items
└── codigos_rol.php              # Plugin antiguo (legacy)
```

## Plugins Modulares Creados

### 1. BBCustom_ficha.php
**BBCode**: `[ficha]`

Muestra la ficha completa del personaje que escribe el post, incluyendo:
- Estadísticas (FUE, RES, DES, PUN, AGI, REF, VOL)
- Recursos (Vitalidad, Energía, Haki)
- Equipamiento (Bolsa, Ropa, Espacios)
- Virtudes y Defectos

**Base de datos**:
- `mybb_op_thread_personaje` (ficha específica del thread)
- `mybb_op_fichas` (ficha general)
- `mybb_op_virtudes_usuarios` (virtudes del usuario)
- `mybb_op_equipamiento_personaje` (equipamiento del thread)

### 2. BBCustom_npc.php
**BBCode**: `[npc=ID-NPC]` o `[npc=URL_GENERADOR]`

Muestra información de NPCs desde:
- Base de datos: `mybb_op_npcs` (formato: `[npc=69-NPC001]`) **Gracias por el ejemplo Ubben**
- URL del generador (formato: `[npc=https://...]`)

**Características**:
- Stats completos
- Recursos (Vida, Energía, Haki)
- Armas equipadas (Principal, Secundaria, Terciaria)
- Técnica seleccionada
- Virtudes y defectos
- Descripción (Apariencia, Personalidad, Historia)

### 3. BBCustom_tecnica.php
**BBCode**: `[tecnica=TID]`

Muestra técnicas de combate con:
- Información básica (Clase, Estilo, Tier)
- Costos (Energía, Haki, Enfriamiento)
- Descripción y efectos
- Estado de aprendizaje (fecha o "No Aprendida")

**Base de datos**:
- `mybb_op_tecnicas`
- `mybb_op_tec_aprendidas`

### 4. BBCustom_objeto.php
**BBCode**: `[objeto=ID]`

Muestra objetos/items del inventario:
- Color del tier (1-6+)
- Descripción completa
- Requisitos, Escalado, Daño, Efecto
- Espacios que ocupa

**Base de datos**:
- `mybb_op_objetos`

## Librería de Spoilers (lib/spoiler.php)

### Funciones Disponibles

```php
// 1. Spoiler personalizado base
create_custom_spoiler($title, $content, $options = array())

// Opciones soportadas por la librería ahora:
// - 'icon' => '📋'  (emoji/icono o la mierda que queráis ponerle)
// - 'bg_color' => 'linear-gradient(...)'  (color de fondo)
// - 'text_color' => 'white'  (color del texto)
// - 'open' => false  (abrir por defecto)

// 2. Spoiler para fichas de personaje
create_character_spoiler($character_name, $content)

// 3. Spoiler para NPCs
create_npc_spoiler($npc_name, $content)

// 4. Spoiler para técnicas
create_technique_spoiler($technique_name, $content)

// 5. Spoiler para información general
create_info_spoiler($title, $content)
```

### Ejemplo de Uso

```php
<?php
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Spoiler simple
$html = create_info_spoiler("Título", "Contenido aquí");

// Spoiler personalizado
$html = create_custom_spoiler("Mi Título", $contenido, array(
    'icon' => '⚠️',
    'bg_color' => '#ff0000',
    'text_color' => 'white',
    'open' => true  // Abrir por defecto
));
```

## 🔄 Migración del Sistema Antiguo

### Sistema Antiguo (MyBB Spoiler)
```php
// ANTIGUO - NO USAR
$html = "<div class='spoiler'>
    <div class='spoiler_title'>
        <span class='spoiler_button' onclick=\"javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.display == 'block'){ ... }\">Título</span>
    </div>
    <div class='spoiler_content' style='display: none;'>Contenido</div>
</div>";
```

### Sistema Nuevo (Modular)
```php
// NUEVO - USAR ESTO
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";
$html = create_info_spoiler("Título", "Contenido");
```

### Ventajas del Sistema Nuevo

1. ✅ **Código más limpio**: Una línea en lugar de 10+
2. ✅ **IDs únicos automáticos**: Sin conflictos entre spoilers
3. ✅ **Estilos consistentes**: Mismo diseño en todos los plugins
4. ✅ **Fácil personalización**: Solo cambiar opciones
5. ✅ **Independiente de MyBB**: No depende del spoiler nativo
6. ✅ **Reutilizable**: Misma función en plugins y backend

## Creación de Nuevos Plugins

### Plantilla Base

```php
<?php
/**
 * BBCustom Mi Plugin
 * Procesa BBCode [micodigo=PARAM]
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// Cargar librería de spoilers
require_once MYBB_ROOT . "inc/plugins/lib/spoiler.php";

// Hook para procesar DESPUÉS de MyBB
$plugins->add_hook("postbit", "BBCustom_micodigo_run");

function BBCustom_micodigo_info()
{
    return array(
        "name"          => "Mi Código BBCode",
        "description"   => "BBCode [micodigo=X] modular",
        "website"       => "",
        "author"        => "Tu Nombre",
        "authorsite"    => "",
        "version"       => "1.0",
        "codename"      => "BBCustom_micodigo",
        "compatibility" => "*"
    );
}

function BBCustom_micodigo_activate() {}
function BBCustom_micodigo_deactivate() {}

function BBCustom_micodigo_run(&$post)
{
    global $db;
    
    // Protección contra procesamiento múltiple
    static $processed_pids = array();
    $current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);
    
    if (in_array($current_pid, $processed_pids)) {
        return;
    }
    
    $message = &$post['message'];
    
    // Procesar BBCode
    while (preg_match('#\[micodigo=(.*?)\]#si', $message, $matches))
    {
        $param = $matches[1];
        
        // Buscar datos en BD
        $query = $db->query("SELECT * FROM mybb_tabla WHERE id='$param'");
        $data = $db->fetch_array($query);
        
        if (!$data) {
            $html = "<div style='background: #f44336; color: white; padding: 10px; border-radius: 5px;'>No encontrado: $param</div>";
        } else {
            // Generar HTML
            $content = "HTML generado aquí con los datos";
            $html = create_info_spoiler($data['nombre'], $content);
        }
        
        $message = preg_replace('#\[micodigo=' . preg_quote($param, '#') . '\]#si', $html, $message, 1);
    }
    
    $processed_pids[] = $current_pid;
}
```

## Notas Importantes

### Hook Correcto
**Usar `postbit` en lugar de `parse_message`**:
- ✅ `postbit`: Se ejecuta DESPUÉS del procesamiento de MyBB
- ❌ `parse_message`: MyBB inserta `<br />` en el HTML y lo rompe

### Protección Anti-Duplicado
Siempre usar `static $processed_pids`:
```php
static $processed_pids = array();
$current_pid = isset($post['pid']) ? $post['pid'] : md5($post['message']);

if (in_array($current_pid, $processed_pids)) {
    return;
}
// ... tu código ...
$processed_pids[] = $current_pid;
```

### Reemplazo Seguro
Usar `preg_quote` para evitar problemas con caracteres especiales:
```php
$message = preg_replace(
    '#\[codigo=' . preg_quote($param, '#') . '\]#si', 
    $html, 
    $message, 
    1  // Solo reemplazar la primera ocurrencia
);
```

## Tablas de Base de Datos Usadas Conocidas (ampliar)

- `mybb_op_fichas` - Fichas de personajes
- `mybb_op_thread_personaje` - Fichas específicas de thread
- `mybb_op_virtudes_usuarios` - Virtudes de usuarios
- `mybb_op_virtudes` - Definiciones de virtudes
- `mybb_op_equipamiento_personaje` - Equipamiento por thread
- `mybb_op_objetos` - Catálogo de objetos
- `mybb_op_npcs` - NPCs de la BD
- `mybb_op_tecnicas` - Técnicas de combate
- `mybb_op_tec_aprendidas` - Técnicas aprendidas por usuarios

## Estilos y Colores

### Colores de Tier (Objetos/Armas)
- Tier 1: `#808080` (Gris)
- Tier 2: `#4dfe45` (Verde)
- Tier 3: `#457bfe` (Azul)
- Tier 4: `#cf44ff` (Púrpura)
- Tier 5: `#febb46` (Dorado)
- Tier 6+: `#faa500` (Naranja)

### Gradientes de Spoilers
- Ficha: `linear-gradient(135deg, #667eea 0%, #764ba2 100%)` (Morado)
- NPC: `linear-gradient(135deg, #f093fb 0%, #f5576c 100%)` (Rosa)
- Técnica: `linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)` (Cyan)
- Info: `linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)` (Verde)

## To-Do / Próximos BBCodes

- [ ] `[personaje=uid]` - Mostrar ficha de otro usuario
- [ ] `[equipamiento]` - Mostrar solo equipamiento
- [ ] `[vida=X]`, `[energia=X]`, `[haki=X]` - Mostrar gastos
- [ ] `[dado_guardado=X]` - Mostrar resultado de tirada
- [ ] `[consumido=X]` - Marcar objeto consumido
- [ ] `[viaje=X]` - Mostrar información de viaje

## Soporte

Para reportar bugs o solicitar nuevas características, escríbelas en papel de baño y tíralas por el desagüe.

---

**Autor**: Cascabelles  
**Versión**: 1.0  
**Última actualización**: 05/02/2026
