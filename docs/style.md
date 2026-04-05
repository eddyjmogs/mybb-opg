# OPG Forum — Style Guide
*One Piece Grand Line · Foro de Rol*

---

## 1. Identidad visual

El foro debe sentirse como un **documento náutico antiguo** del mundo de One Piece — un mapa del Grand Line, una ficha del Gobierno Mundial, un diario de bitácora pirata. La estética es cálida, premium y temática sin caer en lo genérico. Todo elemento debe tener intención: texturas, colores y ornamentos que cuenten que este es el mundo de One Piece.

---

## 2. Paleta de colores

### Colores principales

| Nombre | Hex | Uso |
|---|---|---|
| Marfil | `#f2ede4` | Fondo principal |
| Marfil claro | `#faf7f0` | Centro del gradiente radial |
| Marfil oscuro | `#e8dfd0` | Bordes del gradiente radial |
| Blanco carta | `#ffffff` | Fondo de cards y elementos |
| Ciruela | `#5a3278` | Acento principal — headers, bordes, ornamentos |
| Ciruela oscuro | `#2e0f48` | Gradiente de headers (inicio) |
| Ciruela medio | `#3d1a60` | Gradiente de headers (fin) |
| Ciruela lavanda | `#e8d4ff` | Texto sobre fondo ciruela |
| Terracota | `#c4581a` | Acento secundario — CTAs, anuncios, variaciones |
| Terracota oscuro | `#a84020` | Gradiente de CTAs (fin) |
| Terracota claro | `#fde8c0` | Texto sobre fondo terracota |
| Borde cálido | `#c8b8d8` | Bordes de cards (ciruela tenue) |
| Borde neutro | `#d8cdb8` | Divisores y separadores |
| Texto principal | `#3a1a08` | Títulos y cuerpo principal |
| Texto secundario | `#a06040` | Metadatos, descripciones |
| Texto acento | `#7a4aaa` | Stats, contadores (ciruela suave) |
| Texto muted | `#9a8060` | Labels, etiquetas de sección |

### Colores de estado

| Estado | Hex | Uso |
|---|---|---|
| Online | `#4a8a30` | Punto de usuario activo |
| Away | `#c4a030` | Punto de usuario ausente |
| Post activo | `#5a3278` | Border-left de post row |
| Post revisión | `#c4581a` | Border-left de post row |
| Post cerrado | `#c4a078` | Border-left de post row |

### Colores de facciones

| Facción | Hex |
|---|---|
| Piratas | `#c4581a` |
| Marines | `#1a3878` |
| Revolucionarios | `#5a3278` |
| Shichibukai | `#7a3278` |
| Staff | `#8a3010` |
| Neutrales | `#7a6040` |

---

## 3. Tipografía

### Fuentes

Tres fuentes con roles distintos y bien definidos:

| Fuente | Origen | Rol |
|---|---|---|
| **Playfair Display** | Google Fonts | Headers de sección, logo, títulos de card, stats numéricas |
| **Source Serif 4** | Google Fonts | Cuerpo de posts, descripciones largas, texto narrativo |
| **System sans-serif** | Sistema | UI pequeña — metadatos, labels, badges, nav links |

### Importación (Google Fonts)

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=Source+Serif+4:ital,opsz,wght@0,8..60,400;0,8..60,600;1,8..60,400&display=swap" rel="stylesheet">
```

### Variables CSS

```css
--font-display: 'Playfair Display', Georgia, serif;
--font-body: 'Source Serif 4', Georgia, serif;
--font-ui: system-ui, sans-serif;
```

### Cuándo usar cada fuente

**Playfair Display** — para todo lo que es estructura y jerarquía del foro. Headers de sección, nombre del logo en navbar, títulos de cards de categoría, valores numéricos de stats. Peso 700 como mínimo. Evitar en cuerpos de texto — fatiga en párrafos largos.

**Source Serif 4** — exclusivamente para contenido narrativo generado por usuarios. Cuerpo de posts de rol, descripciones de fichas de personaje, entradas de biblioteca. `15px`, `line-height: 1.8`, color `#3a2010`. El `line-height` generoso es obligatorio para que los roleos largos respiren.

**Sans-serif del sistema** — todo lo que es UI funcional. Links del navbar, metadatos de posts (autor, fecha, categoría), labels de stats, badges de estado, etiquetas de facción. Nunca para contenido narrativo.

### Escala tipográfica

| Elemento | Fuente | Tamaño | Peso | Color |
|---|---|---|---|---|
| Header de sección | Playfair Display | 12px | 700 | `#e8d4ff` — letter-spacing 0.1em |
| Título de card | Playfair Display | 12px | 700 | `#3a1a08` |
| Stat principal | Playfair Display | 18px | 700 | `#5a3278` o `#c4581a` |
| Nombre del logo | Playfair Display | 13px | 700 | `#e8d4ff` — letter-spacing 0.06em |
| Cuerpo de post | Source Serif 4 | 15px | 400 | `#3a2010` — line-height 1.8 |
| Cita o énfasis | Source Serif 4 | 15px | 400 italic | `#5a3278` |
| Título de post (lista) | Sans-serif | 11px | 500 | `#3a1a08` |
| Descripción de card | Sans-serif | 10px | 400 | `#a06040` |
| Metadato de post | Sans-serif | 10px | 400 | `#a06040` |
| Stat label | Sans-serif | 9px | 400 | `#a06040` — letter-spacing 0.04em, MAYÚSCULAS |
| Label de sección | Sans-serif | 9px | 400 | `#9a8060` — letter-spacing 0.1em |
| Badge / pill | Sans-serif | 9px | 400 | Variable según contexto |
| Nav link | Sans-serif | 11px | 400 | `#9a78b888` / `#e8d4ff` activo |

---

## 4. Fondo

### Gradiente radial base

El fondo no es un color plano — es un gradiente radial que simula luz natural sobre pergamino:

```css
background: radial-gradient(
  ellipse at 50% 25%,
  #faf7f0 0%,
  #f2ede4 55%,
  #e8dfd0 100%
);
```

### Ondas de mar (decoración de fondo)

Ondas SVG horizontales en tres bandas a muy baja opacidad, inspiradas en las corrientes del Grand Line. Las ondas son curvas sinusoidales suaves (`C` paths en SVG), no líneas rectas.

- **Banda superior:** ondas ciruela `#5a3278`, opacidad `0.06`, stroke `1px`
- **Banda media:** ondas ciruela `#5a3278`, opacidad `0.04`, stroke `0.8px`
- **Banda inferior:** ondas terracota `#c4581a`, opacidad `0.045`, stroke `0.7px`

### Grano de pergamino (solo fondo marfil)

Textura de ruido fractal aplicada únicamente sobre el fondo marfil, a opacidad `2-3%` máximo. No se aplica en cards, headers, posts ni ningún otro elemento. El contraste entre el fondo texturizado y los elementos lisos encima es lo que genera la sensación de profundidad.

- Tipo: SVG `feTurbulence` con `type="fractalNoise"`
- `baseFrequency`: `0.72`
- `numOctaves`: `4`
- `opacity`: `0.025` — nunca superar `0.03`

---

## 5. Navbar

### Especificaciones

```css
background: linear-gradient(135deg, #1e0a2e 0%, #3a1858 55%, #1e0a2e 100%);
height: 46px;
box-shadow: inset 0 -1px 0 #7a4aaa66, inset 0 1px 0 #ffffff15;
```

### Elementos

- **Logo:** sol de los Sun Pirates con silueta de Nika en `#e8d4ff` + texto "OPG" en Playfair Display
- **Links:** `11px`, color `#9a78b888`, activo en `#e8d4ff` con `background: #ffffff12`
- **CTA principal:** gradiente `#c4581a → #a84020`, texto `#fde8c0`, `inset 0 1px 0 #ffffff25`
- **Avatar:** círculo `#5a3278`, borde `1.5px solid #9a70c888`, texto `#ddc8f0`

---

## 6. Headers de sección

### Estilo

Rectángulos sin border-radius. Gradiente cuero ciruela con grano sutil encima.

```css
background: linear-gradient(135deg, #2e0f48 0%, #5a3278 50%, #3d1a60 100%);
box-shadow: inset 0 1px 0 #ffffff20, inset 0 -1px 0 #00000035;
padding: 9px 14px;
border-radius: 0;
```

### Acento lateral

Barra vertical terracota de `3px` de ancho a la izquierda del texto del header:

```css
width: 3px;
height: 18px;
background: #c4581a;
```

### Texto

Georgia, `12px`, `700`, `#e8d4ff`, `letter-spacing: 0.1em`

---

## 7. Cards de categoría

### Base

```css
background: #ffffff;
border: 1px solid #c8b8d8;
border-top: 2px solid #5a3278; /* o #c4581a para variante terracota */
border-radius: 0;
padding: 12px;
box-shadow: inset 0 1px 0 #ffffff90;
```

### Ícono de categoría

Con borde izquierdo del color del acento de la card:

```css
border-left: 2px solid #5a3278;
padding-left: 6px;
```

### Esquinas decorativas (trazo fino)

Trazo `0.8px` en el color del acento, `8px` de largo, en las 4 esquinas internas:

```css
/* esquina superior izquierda */
position: absolute; top: 4px; left: 4px;
width: 8px; height: 8px;
border-top: 0.8px solid #5a3278;
border-left: 0.8px solid #5a3278;
opacity: 0.7;
```

### Número romano fantasma

En la esquina derecha, muy transparente, como marca de archivo:

```css
position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
font-family: Georgia, serif;
font-size: 48px; font-weight: 700;
color: #5a3278; opacity: 0.06;
```

---

## 8. Posts y filas de actividad

### Base

```css
background: #ffffff;
border: 1px solid #c8b8d8;
border-left: 3px solid #5a3278; /* color varía según estado */
border-radius: 0;
padding: 9px 12px;
box-shadow: inset 0 1px 0 #ffffff80;
```

### Estados de border-left

| Estado | Color |
|---|---|
| Activo | `#5a3278` ciruela |
| En revisión | `#c4581a` terracota |
| Cerrado | `#c4a078` neutro cálido |

### Ticket diagonal (solo posts activos)

Triángulo en la esquina superior derecha del color del acento:

```css
position: absolute; top: 0; right: 0;
width: 0; height: 0;
border-style: solid;
border-width: 0 16px 16px 0;
border-color: transparent #5a3278 transparent transparent;
opacity: 0.7;
```

### Avatar de post

```css
border-left: 2px solid [color-acento];
border-radius: 0;
width: 28px; height: 28px;
```

### Badge de estado

```css
border-left: 2px solid currentColor;
border-radius: 0;
font-size: 9px; padding: 2px 7px;
```

---

## 9. Ornamentos y separadores

### Separador entre header y contenido (diamantes)

Tres diamantes rotados 45° — dos pequeños terracota a los lados, uno grande ciruela en el centro — flanqueados por líneas que se desvanecen:

```css
/* línea izquierda */
flex: 1; height: 1px;
background: linear-gradient(90deg, transparent, #5a327855);

/* diamante grande */
width: 5px; height: 5px;
background: #5a3278; transform: rotate(45deg); opacity: 0.5;

/* diamante pequeño */
width: 3px; height: 3px;
background: #c4581a; transform: rotate(45deg); opacity: 0.6;
```

### Círculo con anillo discontinuo (separador premium)

El separador más elaborado — un círculo relleno central flanqueado por un anillo de puntos discontinuos y dos líneas que se desvanecen. Usado en secciones de alta jerarquía como separador entre el header y el contenido principal.

```svg
<svg width="20" height="16" viewBox="0 0 20 16" fill="none">
  <circle cx="10" cy="8" r="3.5" fill="#c4a078" opacity="0.6"/>
  <circle cx="10" cy="8" r="6"
    fill="none" stroke="#c4a078"
    stroke-width="0.8" stroke-dasharray="2 2" opacity="0.4"/>
  <circle cx="2" cy="8" r="1.2" fill="#c4a078" opacity="0.4"/>
  <circle cx="18" cy="8" r="1.2" fill="#c4a078" opacity="0.4"/>
</svg>
```

Flanqueado por líneas degradadas a ambos lados:

```css
flex: 1; height: 1px;
background: linear-gradient(90deg, transparent, #c4a07855); /* izquierda */
background: linear-gradient(90deg, #c4a07855, transparent); /* derecha */
```

Color del ornamento: `#c4a078` — dorado neutro cálido, más suave que terracota y ciruela para no competir con el contenido.

---

### Línea doble de título de sección

Dos líneas horizontales antes del título de sección, la segunda más corta:

```css
/* línea 1 */
height: 1px; width: 100%;
background: linear-gradient(90deg, #5a3278, #5a327800);

/* línea 2 */
height: 1px; width: 60%;
background: linear-gradient(90deg, #5a3278, #5a327800);
margin-top: 3px;
```

### Franja lateral de sección

Barra vertical a la izquierda de bloques grandes de contenido:

```css
width: 3px;
background: linear-gradient(180deg, #5a3278, #5a327800);
border-radius: 2px 0 0 2px;
```

---

## 10. Sidebar y stats

### Cards de stat

```css
background: #ffffff;
border: 1px solid #c8b8d8;
border-top: 2px solid #5a3278; /* o #c4581a */
border-radius: 0;
padding: 10px;
```

- Valor: Georgia, `18px`, `700`, ciruela o terracota
- Label: Sans, `9px`, `#a06040`, `letter-spacing: 0.04em`, MAYÚSCULAS

### Tripulación activa

```css
background: #ffffff;
border: 1px solid #c8b8d8;
border-top: 2px solid #5a3278;
border-radius: 0;
padding: 12px;
```

- Título: Georgia, `11px`, `700`, `#3a1f58`, `letter-spacing: 0.06em`
- Punto online: `6px`, `border-radius: 50%`, color según estado
- Facción: `9px`, `border-left: 2px solid [color-facción]`, `padding-left: 4px`

---

## 11. Rangos de usuario

Sistema de pips (puntos) que indican rango. Se muestran junto al nombre del usuario en la sidebar y en su perfil.

| Rango | Pips | Color |
|---|---|---|
| Grumete | 1 pip | `#5a3278` |
| Marinero | 2 pips | `#5a3278` |
| Capitán | 3 pips | `#5a3278` |
| Comandante | 3 pips | `#c4581a` |
| Yonko | 4 pips | `#c4581a` |

Pips inactivos (posiciones vacías): `#d8cdb8`

---

## 12. Principios generales

### Esquinas

Todos los elementos son **rectangulares sin border-radius** (0px). Las únicas excepciones son avatares de usuario (círculos) y dots de estado online.

### Bordes

- Cards: `1px solid #c8b8d8`
- Acentos top: `2px solid #5a3278` o `2px solid #c4581a`
- Acentos laterales: `3px solid` (posts) o `2px solid` (badges, franjas)
- Nunca `border-radius` en bordes de un solo lado

### Sin grano

No se usa textura de ruido/grano en ningún elemento. La única textura permitida son las ondas SVG del fondo.

### Jerarquía de acentos

El ciruela `#5a3278` es el color dominante — headers, stats principales, ornamentos. El terracota `#c4581a` es secundario — CTAs, variaciones, anuncios, alertas. Nunca al mismo nivel visual.

### Tipografía serif = importancia

Georgia se usa solo para elementos de alto valor: títulos de sección, títulos de card, stats numéricas, nombre del logo. El cuerpo y los metadatos siempre en sans-serif.

---

## 13. Logo OPG

El logo es el sol de los Sun Pirates con la silueta de Nika integrada, en `#e8d4ff` sobre el navbar ciruela. Aparece únicamente en el navbar — no como marca de agua ni elemento decorativo de fondo. Las ondas de mar son el único elemento decorativo global.

---

*OPG Forum Style Guide — generado a partir de sesión de diseño colaborativa*
