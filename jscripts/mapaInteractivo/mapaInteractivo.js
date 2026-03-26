// ...existing code...
/* Módulo: Mapa Interactivo de OPG */
(function(OPG, w) {
  'use strict';

  // Estado del módulo
  var state = {
    tid: null,
    mapUrl: null,
    isVisible: false,
    markers: [],
    zoom: 1,
    pan: { x: 0, y: 0 },
    isDragging: false,
    dragStart: { x: 0, y: 0 },
    isFirstPost: false,
    isSettingInitialPosition: false,
    initialPosition: null,
    measurementActive: false,
    config: {
      minZoom: 0.5,
      maxZoom: 2,
      zoomStep: 0.1,
      markerSize: 20,
      initialPositionBounds: { minX: 0, maxX: 100, minY: 0, maxY: 100 }
    }
  };

  var EPHEMERAL_ICONS = false; // Si es true, los iconos de marcador no se guardan en el localStorage

  // Referencias DOM
  var dom = {
    container: null,
    image: null,
    markersLayer: null,
    gridLayer: null,
    toggleBtn: null,
    mapContent: null
  };

  // ========== Eliminar punto de inicio y permitir recolocar ========== 
  function removeInitialPosition() {
    // Eliminar marcador y estado
    state.initialPosition = null;
    OPG.storage.remove('thread_initial_pos_' + state.tid);
    try {
        localStorage.removeItem('thread_initial_pos_' + state.tid);
        sessionStorage.removeItem && sessionStorage.removeItem('thread_initial_pos_' + state.tid);
    } catch(e) {}

    removeMarker('initial-position');

    // Resetear inputs ocultos
    var fx = document.getElementById('map_initial_x');
    var fy = document.getElementById('map_initial_y');
    var ffixed = document.getElementById('map_initial_fixed');
    if (fx) fx.value = '';
    if (fy) fy.value = '';
    if (ffixed) ffixed.value = '0';

    // UI
    var btn = OPG.q('#set-initial-position-btn');
    var delBtn = document.getElementById('delete-initial-position-btn');
    var fixBtn = document.getElementById('fix-initial-position-btn');
    var cancelBtn = document.getElementById('cancel-initial-position-btn');

    if (fixBtn) fixBtn.style.display = 'none';
    if (cancelBtn) cancelBtn.style.display = 'none';

    if (btn) {
        btn.disabled = false;
        btn.style.opacity = '1';
        btn.innerHTML = '<i class="fa fa-map-pin"></i> Establecer Inicio';
        btn.style.background = 'rgba(46, 213, 115, 0.3)';
        btn.style.borderColor = 'rgba(46, 213, 115, 0.6)';
    }
    if (delBtn) {
        delBtn.style.display = 'none';
    }

    state.isSettingInitialPosition = false;
    if (dom.mapContent) dom.mapContent.style.cursor = 'grab';

    OPG.bus.emit('mapa:notification', {
        type: 'info',
        message: 'Punto de inicio eliminado. Puedes establecer uno nuevo.'
    });
  }

  /* ========== Inicialización ========== */
  function init(threadId, isFirstPost) {
    if (!threadId) return;
    state.tid = threadId;
    state.isFirstPost = !!isFirstPost;
    
    // Cachear elementos DOM
    dom.container = OPG.q('#interactive-map-container');
    dom.image = OPG.q('#map-image');
    dom.markersLayer = OPG.q('#map-markers-layer');
    // Crear o cachear capa de cuadrícula
    dom.gridLayer = document.getElementById('map-grid-layer');
    if (!dom.gridLayer) {
      dom.gridLayer = document.createElement('div');
      dom.gridLayer.id = 'map-grid-layer';
      dom.gridLayer.style.position = 'absolute';
      dom.gridLayer.style.left = '0';
      dom.gridLayer.style.top = '0';
      dom.gridLayer.style.width = '0';
      dom.gridLayer.style.height = '0';
      dom.gridLayer.style.pointerEvents = 'none';
      dom.gridLayer.style.zIndex = '6';
      dom.gridLayer.style.opacity = '0.35';
      // Insertar justo después de la imagen para asegurar superposición
      if (dom.image && dom.image.parentNode) {
        dom.image.parentNode.insertBefore(dom.gridLayer, dom.image.nextSibling);
      } else {
        dom.container.appendChild(dom.gridLayer);
      }
    }
    dom.toggleBtn = OPG.q('#toggle-map-btn');
    dom.mapContent = OPG.q('#map-content');

    if (!dom.container || !dom.image) {
      console.warn('[MapaInteractivo] ❌ Elementos DOM no encontrados - container:', !!dom.container, 'image:', !!dom.image);
      return;
    }

    console.log('[MapaInteractivo] ✅ Elementos DOM encontrados correctamente');

    // Prevenir arrastre de imagen por defecto
    dom.image.setAttribute('draggable', 'false');
    dom.image.style.userDrag = 'none';
    dom.image.style.webkitUserDrag = 'none';
    dom.image.style.pointerEvents = 'auto';
    
    // Prevenir eventos de drag del navegador
    dom.image.addEventListener('dragstart', function(e) { e.preventDefault(); });
    dom.image.addEventListener('drop', function(e) { e.preventDefault(); });
    dom.mapContent.addEventListener('dragover', function(e) { e.preventDefault(); });

    // Configurar eventos
    setupEvents();

    // Mostrar botón de ubicación inicial si es primer post
    if (state.isFirstPost) {
      var setInitialBtn = OPG.q('#set-initial-position-btn');
      if (setInitialBtn) {
        setInitialBtn.style.display = 'inline-block';
      }

      // Crear botones de Fijar y Cancelar si no existen
      var fixBtn = document.getElementById('fix-initial-position-btn');
        if (!fixBtn) {
            fixBtn = document.createElement('button');
            fixBtn.id = 'fix-initial-position-btn';
            fixBtn.innerHTML = '<i class="fa fa-check"></i> Fijar';
            fixBtn.style = 'margin-left:8px;background:rgba(46,213,115,0.15);border:1px solid #28a745;color:#2ed573;padding:6px 12px;border-radius:5px;cursor:pointer;display:none;';
            setInitialBtn.parentNode.insertBefore(fixBtn, setInitialBtn.nextSibling);
        }
    
      var cancelBtn = document.getElementById('cancel-initial-position-btn');
        if (!cancelBtn) {
            cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancel-initial-position-btn';
            cancelBtn.innerHTML = '<i class="fa fa-times"></i> Cancelar';
            cancelBtn.style = 'margin-left:8px;background:rgba(220,53,69,0.15);border:1px solid #dc3545;color:#dc3545;padding:6px 12px;border-radius:5px;cursor:pointer;display:none;';
            setInitialBtn.parentNode.insertBefore(cancelBtn, fixBtn.nextSibling);
        }

      // Listeners para los botones de fijar y cancelar
      fixBtn.addEventListener('click', function() {
        finalizeInitialPosition();
      });

      cancelBtn.addEventListener('click', function() {
        cancelInitialPosition();
      });

      // Crear botón de eliminar punto de inicio si no existe
      var delBtn = document.getElementById('delete-initial-position-btn');
      if (!delBtn) {
        delBtn = document.createElement('button');
        delBtn.id = 'delete-initial-position-btn';
        delBtn.innerHTML = '<i class="fa fa-trash"></i> Quitar Inicio';
        delBtn.style = 'margin-left:8px;background:rgba(220,53,69,0.15);border:1px solid #dc3545;color:#dc3545;padding:6px 12px;border-radius:5px;cursor:pointer;display:none;';
        setInitialBtn.parentNode.insertBefore(delBtn, setInitialBtn.nextSibling);
        
        delBtn.addEventListener('click', function() {
          if (OPG && OPG.mapaInteractivo && OPG.mapaInteractivo.initialPosition && typeof OPG.mapaInteractivo.initialPosition.remove === 'function') {
            OPG.mapaInteractivo.initialPosition.remove();
          }
        });
      }
    }

    // Intentar cargar mapa guardado
    var savedUrl = OPG.storage.get('thread_map_' + state.tid);

    // Vamos a limpiar un poquito el almacenamiento local para que no se vuelva la cosa loca cuando vea que la BD y el localStorage no coinciden
    if (EPHEMERAL_ICONS) {
        try {
            localStorage.removeItem('thread_initial_pos_' + threadId);
            sessionStorage.removeItem && sessionStorage.removeItem('thread_initial_pos_' + threadId);
        } catch(e) {}
    }

    var savedInitialPos = EPHEMERAL_ICONS // almacena la posición inicial relativa no sacada de DB.
        ? null
        : OPG.storage.get('thread_initial_pos_' + state.tid);

    console.log('[MapaInteractivo] Mapa guardado en localStorage:', savedUrl || '(ninguno)');
    console.log('[MapaInteractivo] Posición inicial guardada:', savedInitialPos || '(ninguna)');
    console.log('[MapaInteractivo] savedUrl type:', typeof savedUrl, 'value:', savedUrl);
    console.log('[MapaInteractivo] savedInitialPos type:', typeof savedInitialPos, 'value:', savedInitialPos);
    
    // Parsear posición inicial de forma robusta
    if (savedInitialPos) {
      try {
        console.log('[MapaInteractivo] Intentando parsear posición inicial...');
        if (typeof savedInitialPos === 'object' && savedInitialPos !== null && typeof savedInitialPos.x === 'number' && typeof savedInitialPos.y === 'number') {
          // Ya es un objeto válido
          state.initialPosition = savedInitialPos;
          console.log('[MapaInteractivo] ✅ Posición ya era objeto:', state.initialPosition);
        } else if (typeof savedInitialPos === 'string') {
          // Intentar parsear como JSON
          var parsed = JSON.parse(savedInitialPos);
          if (parsed && typeof parsed.x === 'number' && typeof parsed.y === 'number') {
            state.initialPosition = parsed;
            console.log('[MapaInteractivo] ✅ Posición parseada correctamente:', state.initialPosition);
          } else {
            throw new Error('Formato inválido tras parsear');
          }
        } else {
          throw new Error('Tipo inesperado en savedInitialPos');
        }
      } catch(e) {
        console.error('[MapaInteractivo] ❌ Error parseando posición inicial, limpiando clave:', e);
        // Eliminar clave corrupta para evitar errores futuros
        OPG.storage.remove('thread_initial_pos_' + state.tid);
        state.initialPosition = null;
      }
    }
    
    console.log('[MapaInteractivo] Verificando savedUrl:', !!savedUrl);
    
    if (savedUrl) {
      console.log('[MapaInteractivo] Cargando mapa desde localStorage...');
      // Usar setTimeout para asegurar que el DOM está listo
      setTimeout(function() {
        loadMap(savedUrl);
      }, 1000);
    } else {
      console.log('[MapaInteractivo] No hay mapa guardado, buscando en thread...');
      // Buscar en el thread después de un delay
      setTimeout(searchMapInThread, 1000);
    }

    if (!state.initialPosition && w.OPG && OPG.initialPos && typeof OPG.initialPos.x === 'number' && typeof OPG.initialPos.y === 'number') {
        state.initialPosition = { x: OPG.initialPos.x, y: OPG.initialPos.y };
    }

    OPG.bus.on('mapa:loaded', function() {
        if (!state.initialPosition) return;
        var avatarUrl = (OPG.user && OPG.user.avatar) ? OPG.user.avatar : null;
        // Evita duplicar si ya lo pintaste
        if (!state.markers.some(m => m.id === 'initial-position')) {
            addMarker({
            id: 'initial-position',
            x: state.initialPosition.x,
            y: state.initialPosition.y,
            label: 'Inicio',
            color: '#2ed573',
            avatar: avatarUrl
            });
            _removeMySmallDuplicates();
        }
    });

    // Emitir evento de inicialización
    OPG.bus.emit('mapa:initialized', { tid: state.tid });
  }

    // Pinta todos los marcadores persistentes cuando el mapa se cargue
    OPG.bus.on('mapa:loaded', function () {
        if (!dom.markersLayer) return;
        dom.markersLayer.style.pointerEvents = 'auto';
        if (!Array.isArray(OPG.initialPositions)) return;

        // Detecta mi UID de forma robusta (varias fuentes posibles)
        const viewerId = Number(
            (OPG && OPG.user && (OPG.user.uid || OPG.user.id)) ??
            (typeof window !== 'undefined' && (window.CURRENT_UID || window.USER_ID)) ??
            0
        );

        const seen = new Set();

        OPG.initialPositions.forEach(function (p) {
            const puid = Number(p && p.uid);
            if (puid === viewerId) return; // me salto SIEMPRE mi propio punto (yo pinto el grande)

            const key = puid + ':' + p.fid;
            if (seen.has(key)) return;
            seen.add(key);

            // Si ya existe un marcador de jugador para este UID, no pintamos el initial pequeño
            if (state.markers.some(function(m){ return m && m.id === ('player_' + puid); })) {
              // Saltar esta entrada
              return;
            }

            addMarker({
            id: 'initial-position-' + puid + '-' + p.fid,
            x: p.x,
            y: p.y,
            label: p.username || 'Inicio',
            color: '#4dabf7',
            avatar: p.avatar || null
            });
            _removeMySmallDuplicates();
        });

        // Si ya tengo mi initialPosition, elimina cualquier "small" mío pegado al mismo sitio
        const me = state.initialPosition;
        if (me) {
            const EPS = 2.0; // tolerancia en %
            const all = OPG.mapaInteractivo.markers.getAll();
            all.forEach(function (m) {
            if (!m || typeof m.id !== 'string') return;
            // cubre varios formatos de id por si cambian
            const isPossiblyMine =
                m.id.indexOf('initial-position-') === 0 &&
                (m.id.includes('-' + viewerId + '-') || m.id.endsWith('-' + viewerId) || m.id.includes(viewerId + '-'));

            if (isPossiblyMine && m.id !== 'initial-position') {
                const dx = Math.abs((m.x || 0) - me.x);
                const dy = Math.abs((m.y || 0) - me.y);
                if (dx <= EPS && dy <= EPS) {
                OPG.mapaInteractivo.markers.remove(m.id); // fuera duplicado pequeño
                }
            }
            });
        }
        _removeMySmallDuplicates();
    });

  function _removeMySmallDuplicates() {
    const viewerId = Number(
        (OPG && OPG.user && (OPG.user.uid || OPG.user.id)) ??
        (typeof window !== 'undefined' && (window.CURRENT_UID || window.USER_ID)) ??
        0
    );
    const me = state.initialPosition;
    const EPS = 2.0; // tolerancia en % (más generosa)

    const all = OPG.mapaInteractivo.markers.getAll();
    all.forEach(function (m) {
        if (!m || typeof m.id !== 'string') return;

        // 1) Si detectamos mi UID, borra cualquier "initial-position-<miUid>-*" (da igual dónde esté)
        if (viewerId && m.id.indexOf('initial-position-') === 0 && m.id.includes('-' + viewerId + '-')) {
        if (m.id !== 'initial-position') {
            OPG.mapaInteractivo.markers.remove(m.id);
        }
        return;
        }

        // 2) Si no hay UID fiable, borra por proximidad al punto grande
        if (me && m.id.indexOf('initial-position-') === 0 && m.id !== 'initial-position') {
        const dx = Math.abs((m.x || 0) - me.x);
        const dy = Math.abs((m.y || 0) - me.y);
        if (dx <= EPS && dy <= EPS) {
            OPG.mapaInteractivo.markers.remove(m.id);
        }
        }

        // 3) BONUS: si el avatar coincide con el mío y no es el grande, bórralo
        if (OPG.user && OPG.user.avatar && m.id.indexOf('initial-position-') === 0 && m.id !== 'initial-position') {
        const el = m.element && m.element.querySelector && m.element.querySelector('img');
        if (el && el.src && el.src === OPG.user.avatar) {
            OPG.mapaInteractivo.markers.remove(m.id);
        }
        }
    });
  }

  // Evita duplicados: si se añade un marcador tipo player_<uid>, eliminar cualquier initial-position-<uid>-* pequeño
  OPG.bus.on('mapa:marker-added', function(evt) {
    try {
      var m = evt && evt.marker;
      if (!m || !m.id || typeof m.id !== 'string') return;
      if (m.id.indexOf('player_') !== 0) return;
      var uid = m.id.split('_')[1];
      if (!uid) return;
      var list = state.markers.slice();
      list.forEach(function(mm) {
        if (!mm || typeof mm.id !== 'string') return;
        if (mm.id.indexOf('initial-position-' + uid + '-') === 0 && mm.id !== 'initial-position') {
          removeMarker(mm.id);
          console.log('[MapaInteractivo] Eliminado initial-position duplicado:', mm.id, 'por player_' + uid);
        }
      });
    } catch (e) {
      console.error('[MapaInteractivo] Error al limpiar duplicados de player:', e);
    }
  });

  /* ========== Búsqueda de Mapa ========== */
  function searchMapInThread() {
    console.log('[MapaInteractivo] Buscando mapa en el thread...');
    var threadReview = document.querySelectorAll('.trow1.scaleimages, .post_body, .thread_review_row');
    console.log('[MapaInteractivo] Elementos encontrados para buscar:', threadReview.length);
    var mapFound = null;

    for (var i = 0; i < threadReview.length && !mapFound; i++) {
      var element = threadReview[i];
      var html = element && element.innerHTML ? element.innerHTML : '';

      // Buscar patrón BBCode [mapa]...[/mapa] (ahora permite cualquier contenido dentro)
      var bbcodeMatch = html.match(/\[mapa[^\]]*\]([\s\S]*?)\[\/mapa\]/i);
      if (bbcodeMatch) {
        var inner = bbcodeMatch[1].trim();
        // Intentar extraer URL dentro de [img]...[/img]
        var imgBb = inner.match(/\[img[^\]]*\]([\s\S]*?)\[\/img\]/i);
        if (imgBb) {
          mapFound = imgBb[1].trim();
        } else {
          // Intentar extraer src="..." o cualquier URL http(s)
          var srcMatch = inner.match(/src=["']?([^"'\s>]+)["']?/i) || inner.match(/https?:\/\/[\w\-./?=&%#]+/i);
          if (srcMatch) mapFound = srcMatch[1] || srcMatch[0];
          else mapFound = inner; // fallback al contenido
        }

        console.log('[MapaInteractivo] Mapa encontrado vía BBCode (extraído):', mapFound);
        if (mapFound) break;
      }

      // Buscar imágenes que puedan ser mapas: comprobar src, data-src, data-original, srcset y tamaños reales
      var images = element.querySelectorAll('img');
      for (var j = 0; j < images.length && !mapFound; j++) {
        var img = images[j];
        var src = img.getAttribute('src') || img.getAttribute('data-src') || img.getAttribute('data-original') || '';
        var alt = (img.getAttribute('alt') || '').toLowerCase();
        var widthAttr = parseInt(img.getAttribute('width')) || 0;
        var naturalW = img.naturalWidth || 0;
        var clientW = img.clientWidth || 0;
        var computedW = Math.max(widthAttr, naturalW, clientW);

        // Revisar si está dentro de un enlace (a veces la imagen es mini y el enlace apunta a la grande)
        var anchorHref = '';
        try { var a = img.closest ? img.closest('a') : null; if (a) anchorHref = a.getAttribute('href') || ''; } catch(e) {}

        // Criterios mejorados: alt/src/href incluye "mapa" o filename con "map" o tamaño real >= 800px
        if (alt.indexOf('mapa') >= 0 || src.toLowerCase().indexOf('mapa') >= 0 || anchorHref.toLowerCase().indexOf('mapa') >= 0 || /\bmap\b/i.test(src) || computedW >= 800) {
          mapFound = src || anchorHref || img.getAttribute('src') || anchorHref;
          console.log('[MapaInteractivo] Mapa encontrado vía imagen:', mapFound, '(computed width:', computedW, ', alt:', alt, ', src:', src, ', href:', anchorHref, ')');
          break;
        }

        // Fallback: aceptar imágenes grandes aunque no contengan la palabra "mapa" (umbral 600px)
        if (!mapFound && computedW >= 600) {
          mapFound = src || anchorHref || img.getAttribute('src') || anchorHref;
          console.log('[MapaInteractivo] Mapa encontrado por tamaño (fallback):', mapFound, '(computed width:', computedW, ')');
          break;
        }

        // También comprobar srcset por si las imágenes usan srcset con versiones grandes
        var srcset = img.getAttribute('srcset') || '';
        if (!mapFound && srcset) {
          var candidates = srcset.split(',').map(function(s){ return s.trim().split(' ')[0]; });
          for (var k=0;k<candidates.length;k++){
            var c = candidates[k];
            if (c && /\.(png|jpe?g|gif|webp)(\?|$)/i.test(c)) { mapFound = c; console.log('[MapaInteractivo] Mapa encontrado vía srcset:', mapFound); break; }
          }
          if (mapFound) break;
        }
      }
    }

    if (mapFound) {
      loadMap(mapFound);
    } else {
      console.log('[MapaInteractivo] ❌ No se encontró mapa en el thread — ejecutando diagnóstico de candidatos');
      diagnoseMapCandidates(threadReview);
    }
  }

  // Función de diagnóstico: lista imágenes, anchors y estilos relevantes en los posts
  function diagnoseMapCandidates(threadReview) {
    console.log('[MapaInteractivo] Diagnóstico: listando imágenes y enlaces candidatos en posts (' + (threadReview.length || 0) + ')...');
    for (var i = 0; i < threadReview.length; i++) {
      var el = threadReview[i];
      if (!el) continue;
      var imgs = el.querySelectorAll('img');
      console.log('[Diag] Post', i, '- imgs:', imgs.length, ' textLength:', (el.textContent || '').trim().length);

      for (var j = 0; j < imgs.length; j++) {
        var img = imgs[j];
        var src = img.getAttribute('src') || img.getAttribute('data-src') || img.getAttribute('data-original') || '';
        var srcset = img.getAttribute('srcset') || '';
        var alt = img.getAttribute('alt') || '';
        var widthAttr = img.getAttribute('width') || '';
        var naturalW = img.naturalWidth || 0;
        var clientW = img.clientWidth || 0;
        var anchorHref = '';
        try { var a = img.closest ? img.closest('a') : null; if (a) anchorHref = a.getAttribute('href') || ''; } catch (e) {}
        console.log('[Diag]  img[' + j + '] src:', src, 'srcset:', srcset, 'alt:', alt, 'widthAttr:', widthAttr, 'natural/client:', naturalW, clientW, 'anchorHref:', anchorHref);
      }

      // Revisar anchors que apunten a imágenes o attachments
      var anchors = el.querySelectorAll('a');
      for (var k = 0; k < anchors.length; k++) {
        var ah = anchors[k];
        var href = ah.getAttribute('href') || '';
        if (!href) continue;
        if (/\.(png|jpe?g|gif|webp)(\?|$)/i.test(href) || /attachment/i.test(href) || /attachments/i.test(href)) {
          console.log('[Diag]  anchor[' + k + '] href (posible imagen/attachment):', href);
        }
      }

      // Revisar background-image en el elemento (o en descencientes directos)
      try {
        var bg = window.getComputedStyle(el).getPropertyValue('background-image') || '';
        if (bg && bg !== 'none') console.log('[Diag]  background-image en post:', bg);
      } catch (e) {}

      // Revisar elementos con inline style que contengan background-image
      var elems = el.querySelectorAll('[style]');
      for (var m = 0; m < elems.length; m++) {
        var s = elems[m].getAttribute('style') || '';
        if (s.indexOf('background-image') >= 0) console.log('[Diag]  elemento con inline background-image:', s);
      }
    }
    console.log('[MapaInteractivo] Diagnóstico completo.');
  }

  /* ========== Carga de Mapa ========== */
  function loadMap(url) {
    if (!url) {
      console.warn('[MapaInteractivo] loadMap llamado sin URL');
      return;
    }
    
    // Re-cachear imagen por si acaso
    if (!dom.image) {
      dom.image = OPG.q('#map-image');
    }
    
    if (!dom.image) {
      console.error('[MapaInteractivo] ❌ No se encontró #map-image en loadMap');
      return;
    }
    
    // Convertir URL relativa a absoluta si es necesario
    if (url.indexOf('http') !== 0) {
      if (url.indexOf('/') === 0) {
        // Es una ruta relativa, añadir el dominio
        var baseUrl = window.location.protocol + '//' + window.location.host;
        url = baseUrl + url;
        console.log('[MapaInteractivo] URL convertida a absoluta:', url);
      }
    }
    
    console.log('[MapaInteractivo] Cargando mapa:', url);
    state.mapUrl = url;
    dom.image.setAttribute('src', url);
    console.log('[MapaInteractivo] src establecido:', dom.image.getAttribute('src'));
    
    // Manejar errores de carga
    dom.image.onerror = function() {
      console.error('[MapaInteractivo] ❌ Error al cargar la imagen:', url);
    };
    
    dom.image.onload = function() {
      console.log('[MapaInteractivo] ✅ Imagen cargada correctamente');
      drawGrid(); // Dibuja la cuadrícula sobre la imagen
    };
    
    show();
    
    // Guardar en storage
    OPG.storage.set('thread_map_' + state.tid, url);
    
    // Emitir evento
    OPG.bus.emit('mapa:loaded', { url: url, tid: state.tid });
    console.log('[MapaInteractivo] ✅ Mapa cargado y evento emitido');
  }

  /* ========== Visibilidad ========== */
  function show() {
    if (!dom.container) {
      console.warn('[MapaInteractivo] show() - container no existe');
      return;
    }
    
    console.log('[MapaInteractivo] Mostrando contenedor del mapa...');
    state.isVisible = true;
    dom.container.style.display = 'block';
    
    // Animación fade-in
    dom.container.style.opacity = '0';
    setTimeout(function() {
      dom.container.style.transition = 'opacity 0.4s';
      dom.container.style.opacity = '1';
      console.log('[MapaInteractivo] ✅ Mapa visible con opacity: 1');
    }, 10);

    OPG.bus.emit('mapa:shown', { tid: state.tid });
  }

  function hide() {
    if (!dom.container) return;
    state.isVisible = false;
    dom.container.style.opacity = '0';
    setTimeout(function() {
      dom.container.style.display = 'none';
    }, 400);

    OPG.bus.emit('mapa:hidden', { tid: state.tid });
  }

  function toggle() {
    if (state.isVisible) hide();
    else show();
  }

  /* ========== Toggle Contenido ========== */
  function toggleContent() {
    if (!dom.mapContent || !dom.toggleBtn) return;
    
    var isContentVisible = dom.mapContent.style.display !== 'none';
    
    if (isContentVisible) {
      dom.mapContent.style.height = dom.mapContent.offsetHeight + 'px';
      dom.mapContent.style.overflow = 'hidden';
      dom.mapContent.style.transition = 'height 0.3s ease';
      
      setTimeout(function() {
        dom.mapContent.style.height = '0';
      }, 10);
      
      setTimeout(function() {
        dom.mapContent.style.display = 'none';
      }, 310);
      
      dom.toggleBtn.innerHTML = '<i class="fa fa-eye"></i> Mostrar';
    } else {
      dom.mapContent.style.display = 'block';
      dom.mapContent.style.height = '0';
      
      setTimeout(function() {
        dom.mapContent.style.height = '792.36px';
      }, 10);
      
      setTimeout(function() {
        dom.mapContent.style.height = '';
        dom.mapContent.style.overflow = '';
      }, 310);
      
      dom.toggleBtn.innerHTML = '<i class="fa fa-eye-slash"></i> Ocultar';
    }
  }

  /* ========== Sistema de Zoom ========== */
  function setZoom(level) {
    state.zoom = OPG.utils.clamp(level, state.config.minZoom, state.config.maxZoom);
    applyTransform();
    OPG.bus.emit('mapa:zoom', { zoom: state.zoom });
  }

  function zoomIn() {
    setZoom(state.zoom + state.config.zoomStep);
  }

  function zoomOut() {
    setZoom(state.zoom - state.config.zoomStep);
  }

  function resetZoom() {
    state.zoom = 1;
    state.pan = { x: 0, y: 0 };
    applyTransform();
    OPG.bus.emit('mapa:reset');
  }

  /* ========== Sistema de Pan (Arrastre) ========== */
  function setPan(x, y) {
    state.pan.x = x;
    state.pan.y = y;
    applyTransform();
  }

  function drawGrid() {
    if (!dom.image || !dom.gridLayer) return;
    // Limpiar cuadrícula previa
    dom.gridLayer.innerHTML = '';
    var imgW = dom.image.width; // ancho mostrado
    var imgH = dom.image.height; // alto mostrado
    if (!imgW || !imgH) return;
    dom.gridLayer.style.width = imgW + 'px';
    dom.gridLayer.style.height = imgH + 'px';
    dom.gridLayer.style.left = dom.image.offsetLeft + 'px';
    dom.gridLayer.style.top = dom.image.offsetTop + 'px';
    var cell = 20;
    var cols = Math.floor(imgW / cell);
    var rows = Math.floor(imgH / cell);
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', imgW);
    svg.setAttribute('height', imgH);
    svg.setAttribute('viewBox', '0 0 ' + imgW + ' ' + imgH);
    svg.style.position = 'absolute';
    svg.style.left = '0';
    svg.style.top = '0';
    svg.style.width = '100%';
    svg.style.height = '100%';
    svg.style.pointerEvents = 'none';
    // Líneas verticales
    for (var c = 0; c <= cols; c++) {
    var x = c * cell;
    var vline = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    vline.setAttribute('x1', x);
    vline.setAttribute('y1', 0);
    vline.setAttribute('x2', x);
    vline.setAttribute('y2', imgH);
    vline.setAttribute('stroke', '#222');
    vline.setAttribute('stroke-width', '0.7');
    svg.appendChild(vline);
    }
    // Líneas horizontales
    for (var r = 0; r <= rows; r++) {
    var y = r * cell;
    var hline = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    hline.setAttribute('x1', 0);
    hline.setAttribute('y1', y);
    hline.setAttribute('x2', imgW);
    hline.setAttribute('y2', y);
    hline.setAttribute('stroke', '#222');
    hline.setAttribute('stroke-width', '0.7');
    svg.appendChild(hline);
    }
    dom.gridLayer.appendChild(svg);
  }

  function applyTransform() {
    if (!dom.image) return;
    var transform = 'translate(' + state.pan.x + 'px, ' + state.pan.y + 'px) scale(' + state.zoom + ')';
    dom.image.style.transform = transform;
    dom.image.style.transformOrigin = '0 0';
    dom.markersLayer.style.transform = transform;
    dom.markersLayer.style.transformOrigin = '0 0';
    if (dom.gridLayer) {
      dom.gridLayer.style.transform = transform;
      dom.gridLayer.style.transformOrigin = '0 0';
    }
    drawGrid();
    // Dibuja la cuadrícula sobre la imagen
  }

  /* ========== Marcadores ========== */
  function addMarker(data) {
    // data: { id, x, y, label, color, avatar }
    var marker = {
      id: data.id || OPG.utils.uid('marker'),
      x: OPG.utils.toFloat(data.x, 0),
      y: OPG.utils.toFloat(data.y, 0),
      label: data.label || 'Marcador',
      color: data.color || '#ff6b6b',
      avatar: data.avatar || null,
      element: null
    };

    // Crear elemento DOM
    var el = document.createElement('div');
    el.className = 'map-marker';
    el.setAttribute('data-marker-id', marker.id);

    // Si es el marcador de inicio, ajusta a la celda de la cuadrícula y muestra avatar
    if (marker.id === 'initial-position') {
        var cell = state.config.markerSize; // px de la cuadrícula (usar tamaño configurado)
        el.style.position = 'absolute';
        el.style.left = marker.x + '%';
        el.style.top = marker.y + '%';
        el.style.transform = 'translate(-50%, -50%)';
        el.style.width = cell + 'px';
        el.style.height = cell + 'px';
        el.style.boxSizing = 'border-box'; // evita que border/padding alteren el tamaño
        el.style.padding = '0';
        el.style.margin = '0';
        el.style.cursor = 'pointer';
        el.style.pointerEvents = 'auto';
        el.style.zIndex = '11';

        el.style.border = '0';
        el.style.outline = '0';
        el.style.lineHeight = '0';
        el.style.fontSize = '0';

        // Contenedor circular exactamente 20x20
        var innerHtml =
            '<div style="' +
            'width:' + cell + 'px;' +
            'height:' + cell + 'px;' +
            'border-radius:50%;' +
            'overflow:hidden;' +
            'background:#fff;' +
            'box-shadow:0 2px 8px rgba(0,0,0,0.18);' +
            'display:flex;align-items:center;justify-content:center;' +
            '">';

        if (marker.avatar) {
            innerHtml += '<img src="' + OPG.utils.escapeHtml(marker.avatar) + '" ' +
                        'style="width:100%;height:100%;object-fit:cover;display:block;" />';
        } else {
            innerHtml += '<div style="width:100%;height:100%;background:' + marker.color + ';"></div>';
        }
        innerHtml += '</div>';
        el.innerHTML = innerHtml;
    } else { // Si no es el marcador de inicio
      el.style.cssText = 
        'position: absolute;' +
        'left: ' + marker.x + '%;' +
        'top: ' + marker.y + '%;' +
        'width: ' + state.config.markerSize + 'px;' +
        'height: ' + state.config.markerSize + 'px;' +
        'transform: translate(-60%, -60%);' +
        'cursor: pointer;' +
        'pointer-events: auto;' +
        'z-index: 10;';
      // Contenido del marcador
      if (marker.avatar) {
        el.innerHTML = 
          '<div style="width: 100%; height: 100%; border-radius: 50%; border: 2px solid ' + marker.color + '; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.3); background: white;">' +
            '<img src="' + OPG.utils.escapeHtml(marker.avatar) + '" style="width: 100%; height: 100%; object-fit: cover;" />' +
          '</div>';
      } else {
        el.innerHTML =
          '<div style="width: 100%; height: 100%; border-radius: 50%; background: ' + marker.color + '; display: flex; align-items: center; justify-content: center; font-size: 20px; color: white; font-weight: bold; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">📍</div>';
      }
    }

    // Evento click
    el.addEventListener('click', function(e) {
      e.stopPropagation();
      OPG.bus.emit('mapa:marker-click', { marker: marker });
    });

    marker.element = el;
    state.markers.push(marker);
    dom.markersLayer.appendChild(el);

    OPG.bus.emit('mapa:marker-added', { marker: marker });
    return marker;
  }

  function removeMarker(markerId) {
    var index = -1;
    for (var i = 0; i < state.markers.length; i++) {
      if (state.markers[i].id === markerId) {
        index = i;
        break;
      }
    }
    
    if (index >= 0) {
      var marker = state.markers[index];
      if (marker.element && marker.element.parentNode) {
        marker.element.parentNode.removeChild(marker.element);
      }
      state.markers.splice(index, 1);
      OPG.bus.emit('mapa:marker-removed', { markerId: markerId });
    }
  }

  function clearMarkers() {
    while (state.markers.length > 0) {
      removeMarker(state.markers[0].id);
    }
  }

  /* ========== Eventos ========== */
  function setupEvents() {
    // Toggle contenido
    if (dom.toggleBtn) {
      dom.toggleBtn.addEventListener('click', toggleContent);
    }

    // Zoom con rueda del mouse
    if (dom.mapContent) {
      dom.mapContent.addEventListener('wheel', function(e) {
        e.preventDefault();
        if (e.deltaY < 0) zoomIn();
        else zoomOut();
      });

      // Arrastre (pan) o establecer ubicación inicial
      dom.mapContent.addEventListener('mousedown', function(e) {
        if (e.target === dom.image || e.target === dom.markersLayer) {
          e.preventDefault(); // Prevenir arrastre de imagen
          
          // Si está en modo establecer ubicación inicial
          if (state.isSettingInitialPosition) {
            handleSetInitialPosition(e);
            return;
          }

          // Si está activo el modo medición, no iniciar arrastre
          if (state.measurementActive || (typeof OPG !== 'undefined' && OPG._measurementActive)) {
            return;
          }
          
          state.isDragging = true;
          state.dragStart.x = e.clientX - state.pan.x;
          state.dragStart.y = e.clientY - state.pan.y;
          dom.mapContent.style.cursor = 'grabbing';
        }
      });

      document.addEventListener('mousemove', function(e) {
        if (state.isDragging) {
          e.preventDefault(); // Prevenir selección de texto
          setPan(
            e.clientX - state.dragStart.x,
            e.clientY - state.dragStart.y
          );
        }
      });

      document.addEventListener('mouseup', function() {
        if (state.isDragging) {
          state.isDragging = false;
          dom.mapContent.style.cursor = state.isSettingInitialPosition ? 'crosshair' : 'grab';
        }
      });

      // Cursor grab cuando pasa sobre el mapa
      dom.mapContent.addEventListener('mouseenter', function() {
        if (!state.isDragging) {
          // Si medición activa, usar crosshair
          if (state.measurementActive || (typeof OPG !== 'undefined' && OPG._measurementActive)) dom.mapContent.style.cursor = 'crosshair';
          else dom.mapContent.style.cursor = state.isSettingInitialPosition ? 'crosshair' : 'grab';
        }
      });

      // Escuchar eventos de medición para desactivar/activar pan
      if (OPG && OPG.bus) {
        // Variables para fallback local si mapaUtils no está presente
        var measureFallbackPoints = [];
        var measureFromMarkerRecently = false; // flag para evitar doble-click cuando se hace click en marcador
        var measureFallbackType = 'line';
        var measureFallbackConeAngle = 60; // grados
        var _measureFallbackMoveHandler = null;

        // Valores estándar para mediciones en fallback
        var MEASURE_FONT_PX = 14;
        var MEASURE_BORDER_PX = 2;
        var MEASURE_BORDER_INNER_PX = 3;

        function clearMeasureFallbackVisuals() {
          var line = OPG.q('#measure-line'); if (line) line.parentNode.removeChild(line);
          var shape = OPG.q('#measure-shape'); if (shape) shape.parentNode.removeChild(shape);
          var temp = OPG.q('#measure-temp-point'); if (temp) temp.parentNode.removeChild(temp);
          // inner dynamic pieces
          var li = OPG.q('#measure-line-inner'); if (li) li.parentNode.removeChild(li);
          var ci = OPG.q('#measure-circle-inner'); if (ci) ci.parentNode.removeChild(ci);
          var co = OPG.q('#measure-cone-inner'); if (co) co.parentNode.removeChild(co);
          var ri = OPG.q('#measure-rect-inner'); if (ri) ri.parentNode.removeChild(ri);
          // remove any dynamic handler attached DOM-wise
          if (_measureFallbackMoveHandler && dom.mapContent) {
            try { dom.mapContent.removeEventListener('mousemove', _measureFallbackMoveHandler, true); } catch (e) { try { dom.mapContent.removeEventListener('mousemove', _measureFallbackMoveHandler); } catch (ee) {} }
            if (dom.markersLayer) try { dom.markersLayer.removeEventListener('mousemove', _measureFallbackMoveHandler, true); } catch (e) { try { dom.markersLayer.removeEventListener('mousemove', _measureFallbackMoveHandler); } catch (ee) {} }
            _measureFallbackMoveHandler = null;
          }
        }

        // Helpers locales para fallback (evitar dependencia en mapaUtils)
        function calcularDistancia(x1, y1, x2, y2) {
          var dx = x2 - x1;
          var dy = y2 - y1;
          return Math.sqrt(dx * dx + dy * dy);
        }

        function calcularDistanciaMetros(x1, y1, x2, y2) {
          // GRID_CELL_PX = 20, METERS_PER_CELL = 5 (mismo cálculo que en mapaUtils)
          var img = dom.image || OPG.q('#map-image');
          var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
          var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
          var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;
          if (!imgW || !imgH) return null;

          var x1px = (x1 / 100) * imgW;
          var y1px = (y1 / 100) * imgH;
          var x2px = (x2 / 100) * imgW;
          var y2px = (y2 / 100) * imgH;

          var dx = x2px - x1px;
          var dy = y2px - y1px;
          var distPx = Math.sqrt(dx * dx + dy * dy);

          var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
          var meters = (distPx / (20 * scale)) * 5;
          return meters;
        }

        // Helper: obtener colores según umbral máximo definido (OPG._measurementMaxMeters)
        function _fallbackGetMeasureColors(meters) {
          var max = null;
          try {
            if (OPG && typeof OPG._measurementMaxMeters !== 'undefined' && OPG._measurementMaxMeters !== null) {
              var parsed = Number(OPG._measurementMaxMeters);
              max = !isNaN(parsed) ? parsed : null;
            }
          } catch (e) { max = null; }
          if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaInteractivo] _fallbackGetMeasureColors — meters:', meters, ' max:', max);
          var base = { stroke: '#ff6b6b', fill: 'rgba(255,107,107,0.15)', text: 'white' };
          if (max === null || typeof meters !== 'number' || isNaN(meters)) return base;
          if (meters <= max) return { stroke: '#2ed573', fill: 'rgba(46,213,115,0.15)', text: 'white' };
          return { stroke: '#c0392b', fill: 'rgba(192,57,43,0.18)', text: 'white' };
        }

        function drawMeasureFallbackLine(p1, p2, distancia) {
          var markersLayer = dom.markersLayer || OPG.q('#map-markers-layer');
          if (!markersLayer) return;
          // Eliminar previo
          clearMeasureFallbackVisuals();

          var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.id = 'measure-line';
          svg.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 50;';

          var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
          line.setAttribute('x1', p1.x + '%');
          line.setAttribute('y1', p1.y + '%');
          line.setAttribute('x2', p2.x + '%');
          line.setAttribute('y2', p2.y + '%');
          // Si hay un umbral máximo y la distancia es en metros, recortar/mostrar la parte hasta el máximo
          var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
          if (typeof distancia === 'number' && Number.isFinite(max) && distancia > max) {
            var img = dom.image || OPG.q('#map-image');
            var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
            var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
            var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;
            if (imgW && imgH) {
              var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
              var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;

              var x1px = (p1.x / 100) * imgW; var y1px = (p1.y / 100) * imgH;
              var x2px = (p2.x / 100) * imgW; var y2px = (p2.y / 100) * imgH;
              var dxpx = x2px - x1px; var dypx = y2px - y1px;
              var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx);

              var maxPx = max * pxPerMeter * scale;
              var ux = dxpx / distPx; var uy = dypx / distPx;
              var mxpx = x1px + ux * Math.min(maxPx, distPx);
              var mxpy = y1px + uy * Math.min(maxPx, distPx);
              var mx = (mxpx / imgW) * 100; var my = (mxpy / imgH) * 100;

              // Línea verde hasta el máximo
              var lineInner = document.createElementNS('http://www.w3.org/2000/svg', 'line');
              lineInner.setAttribute('x1', p1.x + '%');
              lineInner.setAttribute('y1', p1.y + '%');
              lineInner.setAttribute('x2', mx + '%');
              lineInner.setAttribute('y2', my + '%');
              lineInner.setAttribute('stroke', '#2ed573');
              lineInner.setAttribute('stroke-width', '3');

              // Línea roja desde el máximo hasta el punto final
              var lineOuter = document.createElementNS('http://www.w3.org/2000/svg', 'line');
              lineOuter.setAttribute('x1', mx + '%');
              lineOuter.setAttribute('y1', my + '%');
              lineOuter.setAttribute('x2', p2.x + '%');
              lineOuter.setAttribute('y2', p2.y + '%');
              lineOuter.setAttribute('stroke', '#c0392b');
              lineOuter.setAttribute('stroke-width', '2');
              lineOuter.setAttribute('stroke-dasharray', '5,5');

              svg.appendChild(lineInner);
              svg.appendChild(lineOuter);

              var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
              var midX = (p1.x + p2.x) / 2;
              var midY = (p1.y + p2.y) / 2;
              text.setAttribute('x', midX + '%');
              text.setAttribute('y', midY + '%');
              text.setAttribute('fill', 'white');
              text.setAttribute('font-size', String(MEASURE_FONT_PX));
              text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
              text.setAttribute('font-weight', 'bold');
              text.textContent = distancia.toFixed(2) + ' m (max ' + max + ' m)';

              svg.appendChild(text);
              markersLayer.appendChild(svg);
              try { console.log('[mapaInteractivo] Fallback draw line clipped — meters:', distancia, 'max:', max); } catch(e) {}
              return;
            }
          }

          // Caso normal (sin recorte)
          var colorsLine = _fallbackGetMeasureColors(typeof distancia === 'number' ? distancia : NaN);
          line.setAttribute('stroke', colorsLine.stroke);
          line.setAttribute('stroke-width', '2');
          line.setAttribute('stroke-dasharray', '5,5');

          var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          var midX = (p1.x + p2.x) / 2;
          var midY = (p1.y + p2.y) / 2;
          text.setAttribute('x', midX + '%');
          text.setAttribute('y', midY + '%');
          text.setAttribute('fill', colorsLine.text || 'white');
          text.setAttribute('font-size', String(MEASURE_FONT_PX));
          text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
          text.setAttribute('font-weight', 'bold');
          if (typeof distancia === 'number') {
            text.textContent = distancia.toFixed(2) + ' m';
          } else {
            text.textContent = (typeof distancia === 'string' ? distancia : String(distancia));
          }

          svg.appendChild(line);
          svg.appendChild(text);
          markersLayer.appendChild(svg);
        }

        // Inicia medición dinámica para el fallback: dibuja la forma apropiada y sigue el cursor
        function startMeasureFallbackDynamic(p1) {
          var layer = dom.markersLayer || OPG.q('#map-markers-layer') || dom.mapContent;
          if (!layer) return;
          clearMeasureFallbackVisuals();

          var px = p1.x, py = p1.y;
          var img = dom.image || OPG.q('#map-image');

          // Helper para calcular coords de cursor en %
          function cursorPct(ev) {
            var rect = img ? img.getBoundingClientRect() : dom.mapContent.getBoundingClientRect();
            return { x: ((ev.clientX - rect.left) / rect.width) * 100, y: ((ev.clientY - rect.top) / rect.height) * 100 };
          }

          if (measureFallbackType === 'line') {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.id = 'measure-line';
            svg.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;';

            var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.id = 'measure-line-seg';
            line.setAttribute('x1', px + '%');
            line.setAttribute('y1', py + '%');
            line.setAttribute('x2', px + '%');
            line.setAttribute('y2', py + '%');
            line.setAttribute('stroke', '#ff6b6b');
            line.setAttribute('stroke-width', '2');
            line.setAttribute('stroke-dasharray', '5,5');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.id = 'measure-line-text';
            text.setAttribute('x', px + '%');
            text.setAttribute('y', py + '%');
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');
            text.textContent = '';

            svg.appendChild(line);
            svg.appendChild(text);
            layer.appendChild(svg);

            _measureFallbackMoveHandler = function(ev) {
              var c = cursorPct(ev);
              var cx = c.x, cy = c.y;
              var lineEl = OPG.q('#measure-line-seg');
              var textEl = OPG.q('#measure-line-text');
              if (!lineEl || !textEl) return;
              lineEl.setAttribute('x2', cx + '%');
              lineEl.setAttribute('y2', cy + '%');
              var midX = (parseFloat(lineEl.getAttribute('x1')) + cx) / 2;
              var midY = (parseFloat(lineEl.getAttribute('y1')) + cy) / 2;
              textEl.setAttribute('x', midX + '%');
              textEl.setAttribute('y', midY + '%');

              var x1 = parseFloat(lineEl.getAttribute('x1'));
              var y1 = parseFloat(lineEl.getAttribute('y1'));
              var meters = 0;
              var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
              var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
              var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;
              if (imgW && imgH) {
                var x1px = (x1/100) * imgW; var y1px = (y1/100) * imgH; var x2px = (cx/100) * imgW; var y2px = (cy/100) * imgH;
                var dxpx = x2px - x1px; var dypx = y2px - y1px;
                var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx);
                var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
                meters = (distPx / (20 * scale)) * 5;
                textEl.textContent = meters.toFixed(2) + ' m';

                // Split visual dinámico si supera máximo
                var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
                if (Number.isFinite(max) && meters > max) {
                  var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
                  var maxPx = max * pxPerMeter * scale;
                  var ux = dxpx / distPx; var uy = dypx / distPx;
                  var mxpx = x1px + ux * Math.min(maxPx, distPx);
                  var mxpy = y1px + uy * Math.min(maxPx, distPx);
                  var mx = (mxpx / imgW) * 100; var my = (mxpy / imgH) * 100;

                  // inner (verde)
                  var inner = OPG.q('#measure-line-inner');
                  if (!inner) {
                    inner = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    inner.id = 'measure-line-inner';
                    inner.setAttribute('stroke-width', '3');
                    var parentSvg = lineEl.closest('svg') || lineEl.parentNode;
                    parentSvg.appendChild(inner);
                  }
                  inner.setAttribute('x1', lineEl.getAttribute('x1'));
                  inner.setAttribute('y1', lineEl.getAttribute('y1'));
                  inner.setAttribute('x2', mx + '%');
                  inner.setAttribute('y2', my + '%');
                  inner.setAttribute('stroke', '#2ed573');

                  // outer (rojo)
                  lineEl.setAttribute('x2', cx + '%');
                  lineEl.setAttribute('y2', cy + '%');
                  lineEl.setAttribute('stroke', '#c0392b');
                  textEl.setAttribute('fill', '#c0392b');
                } else {
                  // dentro del máximo -> eliminar inner si existe y pintar según color único
                  var inner2 = OPG.q('#measure-line-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
                  var colors = _fallbackGetMeasureColors(meters);
                  lineEl.setAttribute('stroke', colors.stroke);
                  textEl.setAttribute('fill', colors.text);
                }

              } else {
                textEl.textContent = calcularDistancia(x1, y1, cx, cy).toFixed(2) + '%';
              }
              try { if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaInteractivo] Dynamic fallback line — meters:', meters, 'max:', OPG._measurementMaxMeters); } catch(e) {}
            };

          } else if (measureFallbackType === 'circle') {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.id = 'measure-shape';
            svg.setAttribute('viewBox', '0 0 100 100');
            svg.setAttribute('preserveAspectRatio', 'none');
            svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';

            // Intentar obtener dimensiones de la imagen para usar px y mantener la circularidad
            var imgRectLocalInit = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
            var imgWLocalInit = (imgRectLocalInit && imgRectLocalInit.width) || (img && (img.naturalWidth || img.width)) || 0;
            var imgHLocalInit = (imgRectLocalInit && imgRectLocalInit.height) || (img && (img.naturalHeight || img.height)) || 0;
            if (imgWLocalInit && imgHLocalInit) {
              svg.setAttribute('viewBox', '0 0 ' + imgWLocalInit + ' ' + imgHLocalInit);
              svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
            }

            var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.id = 'measure-circle-dyn';
            // px,py vienen en % — convertir a px si hay dimensiones disponibles
            var cxPx = (imgWLocalInit ? (px / 100) * imgWLocalInit : px);
            var cyPx = (imgHLocalInit ? (py / 100) * imgHLocalInit : py);
            circle.setAttribute('cx', cxPx);
            circle.setAttribute('cy', cyPx);
            circle.setAttribute('r', 0);
            circle.setAttribute('fill', 'rgba(255,107,107,0.15)');
            circle.setAttribute('stroke', '#ff6b6b');
            circle.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
            circle.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.id = 'measure-circle-text';
            text.setAttribute('x', cxPx);
            text.setAttribute('y', cyPx);
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');

            svg.appendChild(circle);
            svg.appendChild(text);
            layer.appendChild(svg);

            _measureFallbackMoveHandler = function(ev) {
              var c = cursorPct(ev);
              var cx = c.x, cy = c.y;
              var meters = calcularDistanciaMetros(px, py, cx, cy);

              // Referencias seguras a los elementos SVG creados
              var circleEl = OPG.q('#measure-circle-dyn');
              var textEl = OPG.q('#measure-circle-text');
              if (!circleEl || !textEl) return;

              var imgRectLocal = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
              var imgWLocal = (imgRectLocal && imgRectLocal.width) || (img && (img.naturalWidth || img.width)) || 0;
              var imgHLocal = (imgRectLocal && imgRectLocal.height) || (img && (img.naturalHeight || img.height)) || 0;

              // convertir coordenadas de % a px (siempre que sea posible)
              var cxPxNow = imgWLocal ? (cx / 100) * imgWLocal : cx;
              var cyPxNow = imgHLocal ? (cy / 100) * imgHLocal : cy;
              var pxPxNow = imgWLocal ? (px / 100) * imgWLocal : px;
              var pyPxNow = imgHLocal ? (py / 100) * imgHLocal : py;

              // radio en px para mantener forma circular
              var dxpx = cxPxNow - pxPxNow;
              var dypx = cyPxNow - pyPxNow;
              var rPx = Math.sqrt(dxpx*dxpx + dypx*dypx);

              // Actualizar texto en px
              textEl.setAttribute('x', Math.min((imgWLocal || 9999) - 8, pxPxNow + rPx + 8));
              textEl.setAttribute('y', Math.max(14, pyPxNow));
              if (Number.isFinite(meters)) textEl.textContent = meters.toFixed(2) + ' m';
              else textEl.textContent = calcularDistancia(px, py, cx, cy).toFixed(2) + '%';

              var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;

              if (Number.isFinite(max) && Number.isFinite(meters) && imgWLocal && imgHLocal && meters > max) {
                var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
                var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
                var measuredPx = meters * pxPerMeter * scale; var maxPx = max * pxPerMeter * scale;

                // outer (rojo) en px
                circleEl.setAttribute('r', Math.min(imgWLocal, measuredPx));
                circleEl.setAttribute('fill', 'rgba(192,57,43,0.18)');
                circleEl.setAttribute('stroke', '#c0392b');

                // inner (verde)
                var inner = OPG.q('#measure-circle-inner');
                if (!inner) {
                  inner = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                  inner.id = 'measure-circle-inner';
                  var parent = circleEl.closest('svg') || circleEl.parentNode;
                  parent.appendChild(inner);
                }
                inner.setAttribute('cx', circleEl.getAttribute('cx'));
                inner.setAttribute('cy', circleEl.getAttribute('cy'));
                inner.setAttribute('r', Math.min(imgWLocal, maxPx));
                inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
                inner.setAttribute('stroke', '#2ed573');
                inner.setAttribute('stroke-width', String(MEASURE_BORDER_INNER_PX));
                inner.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_INNER_PX + 'px');
                textEl.setAttribute('fill', '#c0392b');

              } else {
                var colors = _fallbackGetMeasureColors(meters);
                circleEl.setAttribute('r', rPx);
                circleEl.setAttribute('fill', colors.fill);
                circleEl.setAttribute('stroke', colors.stroke);
                textEl.setAttribute('fill', colors.text);
                var inner2 = OPG.q('#measure-circle-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
              }
            };

          } else if (measureFallbackType === 'cone') {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.id = 'measure-shape';
            svg.setAttribute('viewBox', '0 0 100 100');
            svg.setAttribute('preserveAspectRatio', 'none');
            svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';

            var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.id = 'measure-cone-dyn';
            path.setAttribute('fill', 'rgba(255,107,107,0.18)');
            path.setAttribute('stroke', '#ff6b6b');
            path.setAttribute('stroke-width', String(MEASURE_BORDER_PX) + 'px');
            path.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.id = 'measure-cone-text';
            text.setAttribute('x', px);
            text.setAttribute('y', py);
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');

            svg.appendChild(path);
            svg.appendChild(text);
            layer.appendChild(svg);
            try { console.log('[mapaInteractivo] Fallback cone init', { fontPx: MEASURE_FONT_PX, borderPx: MEASURE_BORDER_PX, px: px, py: py, layerRect: layer.getBoundingClientRect ? layer.getBoundingClientRect() : null, viewBox: svg.getAttribute('viewBox') }); } catch(e) { console.log('[mapaInteractivo] Fallback cone init - error reading sizing'); }

            var angle = measureFallbackConeAngle || 60;
            _measureFallbackMoveHandler = function(ev) {
              var c = cursorPct(ev);
              var cx = c.x, cy = c.y;
              var ax = px, ay = py;
              var radiusPct = Math.sqrt(Math.pow(cx - ax, 2) + Math.pow(cy - ay, 2));
              var theta = Math.atan2(cy - ay, cx - ax);
              var half = (angle * Math.PI / 180) / 2;
              var startAngle = theta - half;
              var endAngle = theta + half;
              var x1 = ax + radiusPct * Math.cos(startAngle);
              var y1 = ay + radiusPct * Math.sin(startAngle);
              var x2 = ax + radiusPct * Math.cos(endAngle);
              var y2 = ay + radiusPct * Math.sin(endAngle);
              var largeArc = (angle > 180) ? 1 : 0;
              var d = 'M ' + ax + ' ' + ay + ' L ' + x1 + ' ' + y1 + ' A ' + radiusPct + ' ' + radiusPct + ' 0 ' + largeArc + ' 1 ' + x2 + ' ' + y2 + ' Z';
              var pathEl = OPG.q('#measure-cone-dyn');
              var textEl = OPG.q('#measure-cone-text');
              if (!pathEl || !textEl) return;
              pathEl.setAttribute('d', d);
              var meters = calcularDistanciaMetros(ax, ay, cx, cy);
              textEl.setAttribute('x', ax + radiusPct / 2);
              textEl.setAttribute('y', ay);
                textEl.setAttribute('font-size', String(MEASURE_FONT_PX));
                textEl.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');

              var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
              var imgRectLocal = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
              if (Number.isFinite(max) && Number.isFinite(meters) && meters > max && imgRectLocal && imgRectLocal.width) {
                var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
                var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
                var measuredPx = meters * pxPerMeter * scale; var maxPx = max * pxPerMeter * scale;
                // vector en px para la dirección
                var dxpx = (cx - ax) / 100 * imgRectLocal.width; var dypx = (cy - ay) / 100 * imgRectLocal.height;
                var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx) || 1;
                var ux = dxpx / distPx; var uy = dypx / distPx;
                var measuredPct = Math.sqrt(Math.pow(ux * (measuredPx / imgRectLocal.width) * 100, 2) + Math.pow(uy * (measuredPx / imgRectLocal.height) * 100, 2));
                var maxPct = Math.sqrt(Math.pow(ux * (maxPx / imgRectLocal.width) * 100, 2) + Math.pow(uy * (maxPx / imgRectLocal.height) * 100, 2));

                // outer (rojo) — recortar al máximo si corresponde
                var x1m = ax + measuredPct * Math.cos(startAngle);
                var y1m = ay + measuredPct * Math.sin(startAngle);
                var x2m = ax + measuredPct * Math.cos(endAngle);
                var y2m = ay + measuredPct * Math.sin(endAngle);
                var dOuter = 'M ' + ax + ' ' + ay + ' L ' + x1m + ' ' + y1m + ' A ' + measuredPct + ' ' + measuredPct + ' 0 ' + largeArc + ' 1 ' + x2m + ' ' + y2m + ' Z';
                pathEl.setAttribute('d', dOuter);
                pathEl.setAttribute('fill', 'rgba(192,57,43,0.18)');
                try { var cs = window.getComputedStyle ? window.getComputedStyle(textEl) : null; console.log('[mapaInteractivo] Fallback cone CLIPPED — meters,max,measuredPx,maxPx:', meters, max, measuredPx, maxPx, 'textAttrFontSize:', textEl.getAttribute('font-size'), 'computedFont:', cs && cs.fontSize, 'pathStrokeAttr:', pathEl.getAttribute('stroke-width')); } catch(e) { console.log('[mapaInteractivo] Fallback cone CLIPPED - debug error'); }
                pathEl.setAttribute('stroke', '#c0392b');
                pathEl.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
                pathEl.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

                // inner (verde)
                var inner = OPG.q('#measure-cone-inner');
                if (!inner) {
                  inner = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                  inner.id = 'measure-cone-inner';
                  var parent = pathEl.closest('svg') || pathEl.parentNode;
                  parent.appendChild(inner);
                }
                var x1i = ax + maxPct * Math.cos(startAngle);
                var y1i = ay + maxPct * Math.sin(startAngle);
                var x2i = ax + maxPct * Math.cos(endAngle);
                var y2i = ay + maxPct * Math.sin(endAngle);
                var dInner = 'M ' + ax + ' ' + ay + ' L ' + x1i + ' ' + y1i + ' A ' + maxPct + ' ' + maxPct + ' 0 ' + largeArc + ' 1 ' + x2i + ' ' + y2i + ' Z';
                inner.setAttribute('d', dInner);
                inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
                inner.setAttribute('stroke', '#2ed573');
                inner.setAttribute('stroke-width', String(MEASURE_BORDER_INNER_PX));
                inner.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_INNER_PX + 'px');
                inner.setAttribute('stroke-width', String(MEASURE_BORDER_INNER_PX));
                inner.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_INNER_PX + 'px');
                textEl.setAttribute('fill', '#c0392b');

              } else {
                var colors = _fallbackGetMeasureColors(meters);
                pathEl.setAttribute('fill', colors.fill);
                pathEl.setAttribute('stroke', colors.stroke);
                pathEl.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
                pathEl.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');
                textEl.setAttribute('fill', colors.text);
                textEl.setAttribute('font-size', String(MEASURE_FONT_PX));
                textEl.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
                var inner2 = OPG.q('#measure-cone-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
              }
            };

          } else if (measureFallbackType === 'square') {
            var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.id = 'measure-shape';
            svg.setAttribute('viewBox', '0 0 100 100');
            svg.setAttribute('preserveAspectRatio', 'none');
            svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';

            var rectEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            rectEl.id = 'measure-rect-dyn';
            rectEl.setAttribute('x', px);
            rectEl.setAttribute('y', py);
            rectEl.setAttribute('width', 0);
            rectEl.setAttribute('height', 0);
            rectEl.setAttribute('fill', 'rgba(255,107,107,0.15)');
            rectEl.setAttribute('stroke', '#ff6b6b');
            rectEl.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
            rectEl.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.id = 'measure-rect-text';
            text.setAttribute('x', px);
            text.setAttribute('y', py);
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');

            svg.appendChild(rectEl);
            svg.appendChild(text);
            layer.appendChild(svg);

            _measureFallbackMoveHandler = function(ev) {
              var c = cursorPct(ev);
              var cx = c.x, cy = c.y;
              var left = Math.min(px, cx);
              var top = Math.min(py, cy);
              var widthP = Math.abs(cx - px);
              var heightP = Math.abs(cy - py);
              var rectDyn = OPG.q('#measure-rect-dyn');
              var textEl = OPG.q('#measure-rect-text');
              if (!rectDyn || !textEl) return;
              rectDyn.setAttribute('x', left);
              rectDyn.setAttribute('y', top);
              rectDyn.setAttribute('width', widthP);
              rectDyn.setAttribute('height', heightP);
              var meters = calcularDistanciaMetros(px, py, cx, cy);
              textEl.setAttribute('x', Math.min(99, left + 2));
              textEl.setAttribute('y', Math.min(99, top + 4));
              if (Number.isFinite(meters)) textEl.textContent = meters.toFixed(2) + ' m (diag)';
              else textEl.textContent = calcularDistancia(px, py, cx, cy).toFixed(2) + '%';

              var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
              if (Number.isFinite(max) && Number.isFinite(meters) && meters > max) {
                // outer red
                rectDyn.setAttribute('fill', 'rgba(192,57,43,0.18)');
                rectDyn.setAttribute('stroke', '#c0392b');

                // inner green scaled centered
                var scaleF = Math.max(0.001, max / meters);
                var innerW = widthP * scaleF; var innerH = heightP * scaleF;
                var innerLeft = left + (widthP - innerW) / 2; var innerTop = top + (heightP - innerH) / 2;
                var inner = OPG.q('#measure-rect-inner');
                if (!inner) {
                  inner = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                  inner.id = 'measure-rect-inner';
                  var parent = rectDyn.closest('svg') || rectDyn.parentNode; parent.appendChild(inner);
                }
                inner.setAttribute('x', innerLeft);
                inner.setAttribute('y', innerTop);
                inner.setAttribute('width', innerW);
                inner.setAttribute('height', innerH);
                inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
                inner.setAttribute('stroke', '#2ed573');
                textEl.setAttribute('fill', '#c0392b');
                try { var cs = window.getComputedStyle ? window.getComputedStyle(textEl) : null; console.log('[mapaInteractivo] Fallback rect CLIPPED — meters,max,innerW/H:', meters, max, innerW, innerH, 'textAttrFontSize:', textEl.getAttribute('font-size'), 'computedFont:', cs && cs.fontSize); } catch(e) { console.log('[mapaInteractivo] Fallback rect CLIPPED - debug error'); }
              } else {
                var inner2 = OPG.q('#measure-rect-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
                var colors = _fallbackGetMeasureColors(meters);
                rectDyn.setAttribute('fill', colors.fill);
                rectDyn.setAttribute('stroke', colors.stroke);
                textEl.setAttribute('fill', colors.text);
                try { var cs = window.getComputedStyle ? window.getComputedStyle(textEl) : null; console.log('[mapaInteractivo] Fallback rect NORMAL — meters:', meters, 'colors:', colors, 'textAttrFontSize:', textEl.getAttribute('font-size'), 'computedFont:', cs && cs.fontSize); } catch(e) { console.log('[mapaInteractivo] Fallback rect NORMAL - debug error'); }
              }
            };

          }

          if (dom.mapContent) dom.mapContent.addEventListener('mousemove', _measureFallbackMoveHandler, true);
          if (dom.markersLayer) dom.markersLayer.addEventListener('mousemove', _measureFallbackMoveHandler, true);
        }

        function stopMeasureFallbackDynamic() {
          if (_measureFallbackMoveHandler) {
            try { if (dom.mapContent) dom.mapContent.removeEventListener('mousemove', _measureFallbackMoveHandler, true); } catch (e) { try { if (dom.mapContent) dom.mapContent.removeEventListener('mousemove', _measureFallbackMoveHandler); } catch (ee) {} }
            try { if (dom.markersLayer) dom.markersLayer.removeEventListener('mousemove', _measureFallbackMoveHandler, true); } catch (e) { try { if (dom.markersLayer) dom.markersLayer.removeEventListener('mousemove', _measureFallbackMoveHandler); } catch (ee) {} }
            _measureFallbackMoveHandler = null;
          }
        }

        function drawMeasureFallbackCircle(pCenter, pEdge, meters) {
          var markersLayer = dom.markersLayer || OPG.q('#map-markers-layer');
          if (!markersLayer) return;
          clearMeasureFallbackVisuals();
          // Usar coordenadas en % para que escale igual que la línea
          var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.id = 'measure-shape';
          svg.setAttribute('viewBox', '0 0 100 100');
          svg.setAttribute('preserveAspectRatio', 'none');
          svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:50;';

          // radio en unidades % (distancia euclidiana entre puntos en %)
          var dx = pEdge.x - pCenter.x;
          var dy = pEdge.y - pCenter.y;
          var rPct = Math.sqrt(dx * dx + dy * dy);

          var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
          var img = dom.image || OPG.q('#map-image');
          var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
          var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
          var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;

          if (Number.isFinite(meters) && Number.isFinite(max) && meters > max && imgW && imgH) {
            var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
            var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
            var measuredPx = meters * pxPerMeter * scale;
            var maxPx = max * pxPerMeter * scale;

            // Dibujar en coordenadas en píxeles para mantener la circularidad
            svg.setAttribute('viewBox', '0 0 ' + imgW + ' ' + imgH);
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

            var cxPx = (pCenter.x / 100) * imgW;
            var cyPx = (pCenter.y / 100) * imgH;

            var rOuter = Math.min(measuredPx, Math.min(imgW, imgH) / 2);
            var rInner = Math.min(maxPx, Math.min(imgW, imgH) / 2);

            var outer = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            outer.setAttribute('cx', cxPx);
            outer.setAttribute('cy', cyPx);
            outer.setAttribute('r', rOuter);
            outer.setAttribute('fill', 'rgba(192,57,43,0.18)');
            outer.setAttribute('stroke', '#c0392b');
            outer.setAttribute('stroke-width', '2');

            var inner = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            inner.setAttribute('cx', cxPx);
            inner.setAttribute('cy', cyPx);
            inner.setAttribute('r', rInner);
            inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
            inner.setAttribute('stroke', '#2ed573');
            inner.setAttribute('stroke-width', '2');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', Math.min(imgW - 8, cxPx + rOuter + 8));
            text.setAttribute('y', Math.max(14, cyPx));
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');
            text.textContent = meters.toFixed(2) + ' m (max ' + max + ' m)';

            svg.appendChild(outer);
            svg.appendChild(inner);
            svg.appendChild(text);
            markersLayer.appendChild(svg);
            try { console.log('[mapaInteractivo] Fallback draw circle clipped (px) — meters:', meters, 'max:', max, 'measuredPx:', measuredPx, 'maxPx:', maxPx); } catch(e) {}
            return;
          }

          var colors = _fallbackGetMeasureColors(meters);

          // Si tenemos dimensiones, dibujar en px para mantener la circularidad
          if (imgW && imgH) {
            // Ajustar viewBox para trabajar en px
            svg.setAttribute('viewBox', '0 0 ' + imgW + ' ' + imgH);
            svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

            var cxPx = (pCenter.x / 100) * imgW;
            var cyPx = (pCenter.y / 100) * imgH;
            var edgePxX = (pEdge.x / 100) * imgW;
            var edgePxY = (pEdge.y / 100) * imgH;
            var dxpx = edgePxX - cxPx;
            var dypx = edgePxY - cyPx;
            var rPx = Math.sqrt(dxpx*dxpx + dypx*dypx);

            var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', cxPx);
            circle.setAttribute('cy', cyPx);
            circle.setAttribute('r', rPx);
            circle.setAttribute('fill', colors.fill);
            circle.setAttribute('stroke', colors.stroke);
            circle.setAttribute('stroke-width', '2');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', Math.min(imgW - 8, cxPx + rPx + 8));
            text.setAttribute('y', Math.max(14, cyPx));
            text.setAttribute('fill', colors.text);
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');
            if (Number.isFinite(meters)) text.textContent = meters.toFixed(2) + ' m'; else text.textContent = calcularDistancia(pCenter.x, pCenter.y, pEdge.x, pEdge.y).toFixed(2) + '%';

            svg.appendChild(circle);
            svg.appendChild(text);
            markersLayer.appendChild(svg);

          } else {
            // Fallback: si no tenemos dimensiones, usar % como antes
            var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', pCenter.x);
            circle.setAttribute('cy', pCenter.y);
            circle.setAttribute('r', rPct);
            circle.setAttribute('fill', colors.fill);
            circle.setAttribute('stroke', colors.stroke);
            circle.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
            circle.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            var tx = Math.min(99, pCenter.x + rPct + 2);
            var ty = Math.max(2, pCenter.y);
            text.setAttribute('x', tx);
            text.setAttribute('y', ty);
            text.setAttribute('fill', colors.text);
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');
            if (Number.isFinite(meters)) text.textContent = meters.toFixed(2) + ' m'; else text.textContent = calcularDistancia(pCenter.x, pCenter.y, pEdge.x, pEdge.y).toFixed(2) + '%';

            svg.appendChild(circle);
            svg.appendChild(text);
            markersLayer.appendChild(svg);
          }

          svg.appendChild(circle);
          svg.appendChild(text);
          markersLayer.appendChild(svg);
          try { console.log('[mapaInteractivo] Fallback draw circle — meters:', meters, 'max:', (OPG && OPG._measurementMaxMeters)); } catch(e) {}
        }

        function drawMeasureFallbackSector(apex, dirPoint, radiusMeters, angleDeg) {
          var markersLayer = dom.markersLayer || OPG.q('#map-markers-layer');
          if (!markersLayer) return;
          clearMeasureFallbackVisuals();
          // Usar sistema en % para evitar desplazamientos con zoom (viewBox 0..100)
          var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.id = 'measure-shape';
          svg.setAttribute('viewBox', '0 0 100 100');
          svg.setAttribute('preserveAspectRatio', 'none');
          svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:50;';

          // Crear elementos para el cono final
          var pathEl = document.createElementNS('http://www.w3.org/2000/svg', 'path');
          pathEl.id = 'measure-cone-dyn';
          pathEl.setAttribute('fill', 'rgba(255,107,107,0.18)');
          pathEl.setAttribute('stroke', '#ff6b6b');
          pathEl.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
          pathEl.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

          var textEl = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          textEl.id = 'measure-cone-text';
          textEl.setAttribute('x', '0');
          textEl.setAttribute('y', '0');
          textEl.setAttribute('fill', 'white');
          textEl.setAttribute('font-size', String(MEASURE_FONT_PX));
          textEl.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
          textEl.setAttribute('font-weight', 'bold');

          svg.appendChild(pathEl);
          svg.appendChild(textEl);
          markersLayer.appendChild(svg);

          var ax = apex.x;
          var ay = apex.y;
          var dx = dirPoint.x;
          var dy = dirPoint.y;
          var radiusPct = Math.sqrt(Math.pow(dx - ax, 2) + Math.pow(dy - ay, 2));

          var theta = Math.atan2(dy - ay, dx - ax);
          var half = (angleDeg * Math.PI / 180) / 2;
          var startAngle = theta - half;
          var endAngle = theta + half;

          var x1 = ax + radiusPct * Math.cos(startAngle);
          var y1 = ay + radiusPct * Math.sin(startAngle);
          var x2 = ax + radiusPct * Math.cos(endAngle);
          var y2 = ay + radiusPct * Math.sin(endAngle);
          var largeArc = (angleDeg > 180) ? 1 : 0;

          var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
          var img = dom.image || OPG.q('#map-image');
          var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
          var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
          var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;

          if (Number.isFinite(radiusMeters) && Number.isFinite(max) && radiusMeters > max && imgW && imgH) {
            var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
            var scale = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
            var measuredPx = radiusMeters * pxPerMeter * scale;
            var maxPx = max * pxPerMeter * scale;
            // calcular vector en px para la dirección
            var dxpx = (dx - ax) / 100 * imgW; var dypx = (dy - ay) / 100 * (imgRect.height || imgRect.width);
            var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx) || 1;
            var ux = dxpx / distPx; var uy = dypx / distPx;
            var measuredPct = Math.sqrt(Math.pow(ux * (measuredPx / imgW) * 100, 2) + Math.pow(uy * (measuredPx / (imgRect.height || imgRect.width)) * 100, 2));
            var maxPct = Math.sqrt(Math.pow(ux * (maxPx / imgW) * 100, 2) + Math.pow(uy * (maxPx / (imgRect.height || imgRect.width)) * 100, 2));

            // Outer path (measured) in red
            var x1m = ax + measuredPct * Math.cos(startAngle);
            var y1m = ay + measuredPct * Math.sin(startAngle);
            var x2m = ax + measuredPct * Math.cos(endAngle);
            var y2m = ay + measuredPct * Math.sin(endAngle);
            var dOuter = 'M ' + ax + ' ' + ay + ' L ' + x1m + ' ' + y1m + ' A ' + measuredPct + ' ' + measuredPct + ' 0 ' + largeArc + ' 1 ' + x2m + ' ' + y2m + ' Z';
            pathEl.setAttribute('d', dOuter);
            pathEl.setAttribute('fill', 'rgba(192,57,43,0.18)');
            pathEl.setAttribute('stroke', '#c0392b');

            // Inner (max) green path
            var inner = OPG.q('#measure-cone-inner');
            if (!inner) {
              inner = document.createElementNS('http://www.w3.org/2000/svg', 'path');
              inner.id = 'measure-cone-inner';
              var parent = pathEl.closest('svg') || pathEl.parentNode;
              parent.appendChild(inner);
            }
            var x1i = ax + maxPct * Math.cos(startAngle);
            var y1i = ay + maxPct * Math.sin(startAngle);
            var x2i = ax + maxPct * Math.cos(endAngle);
            var y2i = ay + maxPct * Math.sin(endAngle);
            var dInner = 'M ' + ax + ' ' + ay + ' L ' + x1i + ' ' + y1i + ' A ' + maxPct + ' ' + maxPct + ' 0 ' + largeArc + ' 1 ' + x2i + ' ' + y2i + ' Z';
            inner.setAttribute('d', dInner);
            inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
            inner.setAttribute('stroke', '#2ed573');
                  inner.setAttribute('stroke-width', String(MEASURE_BORDER_INNER_PX) + 'px');
                  inner.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_INNER_PX + 'px');

          } else {
            // Dibujo normal del cono (sin recorte por máximo)
            var d = 'M ' + ax + ' ' + ay + ' L ' + x1 + ' ' + y1 + ' A ' + radiusPct + ' ' + radiusPct + ' 0 ' + largeArc + ' 1 ' + x2 + ' ' + y2 + ' Z';
            pathEl.setAttribute('d', d);

            var colors = _fallbackGetMeasureColors(radiusMeters);
            pathEl.setAttribute('fill', colors.fill);
            pathEl.setAttribute('stroke', colors.stroke);
            textEl.setAttribute('fill', colors.text);
            // Posicionar y mostrar texto con medida
            try {
              textEl.setAttribute('x', Math.min(99, ax + radiusPct / 2));
              textEl.setAttribute('y', Math.max(2, ay));
              if (Number.isFinite(radiusMeters)) textEl.textContent = radiusMeters.toFixed(2) + ' m'; else textEl.textContent = calcularDistancia(ax, ay, dx, dy).toFixed(2) + '%';
            } catch (e) {}

            var inner2 = OPG.q('#measure-cone-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
          }
        }

        function drawMeasureFallbackRect(p1, p2, diagMeters) {
          var markersLayer = dom.markersLayer || OPG.q('#map-markers-layer');
          if (!markersLayer) return;
          clearMeasureFallbackVisuals();
          // Dibujar rectángulo en % usando SVG viewBox para evitar desplazamientos al escalar
          var left = Math.min(p1.x, p2.x);
          var top = Math.min(p1.y, p2.y);
          var widthP = Math.abs(p2.x - p1.x);
          var heightP = Math.abs(p2.y - p1.y);

          var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
          svg.id = 'measure-shape';
          svg.setAttribute('viewBox', '0 0 100 100');
          svg.setAttribute('preserveAspectRatio', 'none');
          svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:50;';

          var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;

          if (Number.isFinite(diagMeters) && Number.isFinite(max) && diagMeters > max) {
            // Dibujar rectángulo rojo completo y un rectángulo verde interno escalado al máximo
            var outer = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            outer.setAttribute('x', left);
            outer.setAttribute('y', top);
            outer.setAttribute('width', widthP);
            outer.setAttribute('height', heightP);
            outer.setAttribute('fill', 'rgba(192,57,43,0.18)');
            outer.setAttribute('stroke', '#c0392b');
            outer.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
            outer.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

            // Escalar centrado
            var scaleF = Math.max(0.001, max / diagMeters);
            var innerW = widthP * scaleF;
            var innerH = heightP * scaleF;
            var innerLeft = left + (widthP - innerW) / 2;
            var innerTop = top + (heightP - innerH) / 2;

            var inner = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            inner.setAttribute('x', innerLeft);
            inner.setAttribute('y', innerTop);
            inner.setAttribute('width', innerW);
            inner.setAttribute('height', innerH);
            inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
            inner.setAttribute('stroke', '#2ed573');
            inner.setAttribute('stroke-width', String(MEASURE_BORDER_INNER_PX));
            inner.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_INNER_PX + 'px');

            var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', Math.min(99, left + 2));
            text.setAttribute('y', Math.min(99, top + 4));
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', String(MEASURE_FONT_PX));
            text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
            text.setAttribute('font-weight', 'bold');
            text.textContent = diagMeters.toFixed(2) + ' m (max ' + max + ' m)';

            svg.appendChild(outer);
            svg.appendChild(inner);
            svg.appendChild(text);
            markersLayer.appendChild(svg);
            try { console.log('[mapaInteractivo] Fallback draw rect clipped — meters:', diagMeters, 'max:', max); } catch(e) {}
            return;
          }

          var colors = _fallbackGetMeasureColors(diagMeters);

          var rectEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
          rectEl.setAttribute('x', left);
          rectEl.setAttribute('y', top);
          rectEl.setAttribute('width', widthP);
          rectEl.setAttribute('height', heightP);
          rectEl.setAttribute('fill', colors.fill);
          rectEl.setAttribute('stroke', colors.stroke);
          rectEl.setAttribute('stroke-width', String(MEASURE_BORDER_PX));
          rectEl.setAttribute('style', 'stroke-width:' + MEASURE_BORDER_PX + 'px');

          var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
          text.setAttribute('x', Math.min(99, left + 2));
          text.setAttribute('y', Math.min(99, top + 4));
          text.setAttribute('fill', colors.text);
          text.setAttribute('font-size', String(MEASURE_FONT_PX));
          text.setAttribute('style', 'font-size:' + MEASURE_FONT_PX + 'px');
          text.setAttribute('font-weight', 'bold');
          if (Number.isFinite(diagMeters)) text.textContent = diagMeters.toFixed(2) + ' m (diag)'; else text.textContent = '';

          svg.appendChild(rectEl);
          svg.appendChild(text);
          markersLayer.appendChild(svg);
          try { console.log('[mapaInteractivo] Fallback draw rect — meters:', diagMeters, 'max:', (OPG && OPG._measurementMaxMeters)); } catch(e) {}
        }
        function measureFallbackClick(e) {
          // Mejor detección de marcador: recorrer ancestros para encontrar '.map-marker' (más robusto que closest en algunos entornos)
          try {
            var elCheck = e && e.target;
            while (elCheck && elCheck !== dom.mapContent && elCheck !== document.body) {
              if (elCheck.classList && elCheck.classList.contains && elCheck.classList.contains('map-marker')) {
                console.log('measureFallbackClick ignored because target is marker (ancestor found)');
                return;
              }
              elCheck = elCheck.parentNode;
            }
          } catch (err) {}

          // Ignorar clicks si justo antes hubo un click en marcador (evita doble registro)
          if (measureFromMarkerRecently) {
            console.log('measureFallbackClick ignored due to recent marker click');
            measureFromMarkerRecently = false; // limpiar y salir
            return;
          }

          // Solo si el modo está activo en el estado global
          if (!state.measurementActive && !(typeof OPG !== 'undefined' && OPG._measurementActive)) return;
          // Ignorar clicks fuera del contenido del mapa
          if (!dom.mapContent) return;
          // Preferir rect de la imagen para coordenadas correctas con zoom/pan
          var img = dom.image || OPG.q('#map-image');
          if (img) {
            var rect = img.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
          } else {
            var rect = dom.mapContent.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width) * 100;
            var y = ((e.clientY - rect.top) / rect.height) * 100;
          }

          console.log('measureFallbackClick fired (img-aware):', x.toFixed(2) + '%,', y.toFixed(2) + '%', 'state.measurementActive=', state.measurementActive);

          measureFallbackPoints.push({ x: x, y: y });

          // marcador temporal
          var temp = OPG.q('#measure-temp-point'); if (temp) temp.parentNode.removeChild(temp);
          var markersLayer = dom.markersLayer || OPG.q('#map-markers-layer');
          if (markersLayer) {
            var dot = document.createElement('div');
            dot.id = 'measure-temp-point';
            dot.style.cssText = 'position:absolute;left:' + x + '%;top:' + y + '%;width:10px;height:10px;margin-left:-5px;margin-top:-5px;border-radius:50%;background:#fff;border:2px solid #ff6b6b;z-index:60;pointer-events:none;';
            markersLayer.appendChild(dot);
          }

          // Si es el primer punto, iniciar medición dinámica
          if (measureFallbackPoints.length === 1) {
            startMeasureFallbackDynamic(measureFallbackPoints[0]);
          }

          if (measureFallbackPoints.length === 2) {
            // detener dinámica si estaba activa
            try { stopMeasureFallbackDynamic(); } catch (e) {}
            var p1 = measureFallbackPoints[0];
            var p2 = measureFallbackPoints[1];

            // Calcular metros usando dimensiones de la imagen y cuadricula (20px = 5m)
            var img = dom.image || OPG.q('#map-image');
            var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
            var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
            var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;
            var GRID_CELL_PX = 20; var METERS_PER_CELL = 5;
            var distanciaMeters = null;
            if (imgW && imgH) {
              var x1px = (p1.x / 100) * imgW; var y1px = (p1.y / 100) * imgH;
              var x2px = (p2.x / 100) * imgW; var y2px = (p2.y / 100) * imgH;
              var dx = x2px - x1px; var dy = y2px - y1px;
              var distPx = Math.sqrt(dx * dx + dy * dy);
              // Considerar escala/zoom actual
              var scale2 = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
              distanciaMeters = (distPx / (GRID_CELL_PX * scale2)) * METERS_PER_CELL;
            } else {
              // Fallback porcentual
              var dx = p2.x - p1.x; var dy = p2.y - p1.y; distanciaMeters = Math.sqrt(dx * dx + dy * dy);
            }

            // Dibujar según tipo seleccionado (fallback)
            switch (measureFallbackType) {
              case 'circle':
                drawMeasureFallbackCircle(p1, p2, distanciaMeters);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'circle', center: p1, edge: p2, radiusMeters: distanciaMeters });
                console.log('Fallback Medición (circle):', (distanciaMeters && typeof distanciaMeters === 'number') ? distanciaMeters.toFixed(2) + ' m' : distanciaMeters, p1, p2);
                break;
              case 'cone':
                drawMeasureFallbackSector(p1, p2, distanciaMeters, measureFallbackConeAngle);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'cone', apex: p1, directionPoint: p2, radiusMeters: distanciaMeters, angle: measureFallbackConeAngle });
                console.log('Fallback Medición (cone):', (distanciaMeters && typeof distanciaMeters === 'number') ? distanciaMeters.toFixed(2) + ' m' : distanciaMeters, p1, p2);
                break;
              case 'square':
                drawMeasureFallbackRect(p1, p2, distanciaMeters);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'square', corners: [p1, p2], diagonalMeters: distanciaMeters });
                console.log('Fallback Medición (square):', (distanciaMeters && typeof distanciaMeters === 'number') ? distanciaMeters.toFixed(2) + ' m (diag)' : distanciaMeters, p1, p2);
                break;
              case 'line':
              default:
                drawMeasureFallbackLine(p1, p2, distanciaMeters);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'line', points: [p1, p2], distanceMeters: distanciaMeters });
                console.log('Fallback Medición (line):', (distanciaMeters && typeof distanciaMeters === 'number') ? distanciaMeters.toFixed(2) + ' m' : distanciaMeters, p1, p2);
                break;
            }

            // limpiar marcador temporal
            var temp2 = OPG.q('#measure-temp-point'); if (temp2) temp2.parentNode.removeChild(temp2);

            measureFallbackPoints = [];
          }
        }

        // Nuevo: permitir clics en marcadores para medir (captura) y evitar su acción normal
        function measureFallbackMarkerClick(e) {
          if (!state.measurementActive && !(typeof OPG !== 'undefined' && OPG._measurementActive)) return;
          if (!dom.markersLayer) return;
          var markerEl = (e && e.target && e.target.closest) ? e.target.closest('.map-marker') : null;
          if (!markerEl) return;

          // Evitar la acción normal del marcador
          try { if (e.stopPropagation) e.stopPropagation(); if (e.preventDefault) e.preventDefault(); } catch (err) {}

          // Obtener coordenadas del marcador desde el ID
          var markerId = markerEl.getAttribute && markerEl.getAttribute('data-marker-id');
          var coords = null;
          if (markerId && OPG && OPG.mapaInteractivo && OPG.mapaInteractivo.markers && OPG.mapaInteractivo.markers.getAll) {
            var list = OPG.mapaInteractivo.markers.getAll();
            for (var mi = 0; mi < list.length; mi++) {
              if (list[mi].id === markerId) { coords = { x: list[mi].x, y: list[mi].y }; break; }
            }
          }

          if (!coords) return;

          console.log('measureFallbackMarkerClick:', markerId, coords);

          // Marcar que justo hubo click en marcador para evitar doble registro desde el contenedor
          measureFromMarkerRecently = true;
          setTimeout(function() { measureFromMarkerRecently = false; }, 300);

          // Simular pulsación registrando punto
          measureFallbackPoints.push(coords);

          // marcador temporal
          var temp = OPG.q('#measure-temp-point'); if (temp) temp.parentNode.removeChild(temp);
          var markersLayer2 = dom.markersLayer || OPG.q('#map-markers-layer');
          if (markersLayer2) {
            var dot2 = document.createElement('div');
            dot2.id = 'measure-temp-point';
            dot2.style.cssText = 'position:absolute;left:' + coords.x + '%;top:' + coords.y + '%;width:10px;height:10px;margin-left:-5px;margin-top:-5px;border-radius:50%;background:#fff;border:2px solid #ff6b6b;z-index:60;pointer-events:none;';
            markersLayer2.appendChild(dot2);
          }

          if (measureFallbackPoints.length === 1) {
            try { startMeasureFallbackDynamic(measureFallbackPoints[0]); } catch (e) { console.warn('startMeasureFallbackDynamic failed:', e); }
            return;
          }

          if (measureFallbackPoints.length === 2) {
            // detener dinámica si estaba activa
            try { stopMeasureFallbackDynamic(); } catch (e) {}
            var p1 = measureFallbackPoints[0];
            var p2 = measureFallbackPoints[1];

            // Calcular metros usando dimensiones de la imagen y cuadricula (20px = 5m)
            var img2 = dom.image || OPG.q('#map-image');
            var imgRect2 = img2 && img2.getBoundingClientRect ? img2.getBoundingClientRect() : null;
            var imgW2 = (imgRect2 && imgRect2.width) || (img2 && (img2.naturalWidth || img2.width)) || 0;
            var imgH2 = (imgRect2 && imgRect2.height) || (img2 && (img2.naturalHeight || img2.height)) || 0;
            var GRID_CELL_PX2 = 20; var METERS_PER_CELL2 = 5;
            var distanciaMeters2 = null;
            if (imgW2 && imgH2) {
              var x1px2 = (p1.x / 100) * imgW2; var y1px2 = (p1.y / 100) * imgH2;
              var x2px2 = (p2.x / 100) * imgW2; var y2px2 = (p2.y / 100) * imgH2;
              var dx2 = x2px2 - x1px2; var dy2 = y2px2 - y1px2;
              var distPx2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
              var scale3 = (typeof state !== 'undefined' && state && typeof state.zoom === 'number') ? state.zoom : 1;
              distanciaMeters2 = (distPx2 / (GRID_CELL_PX2 * scale3)) * METERS_PER_CELL2;
            } else {
              var dx2 = p2.x - p1.x; var dy2 = p2.y - p1.y; distanciaMeters2 = Math.sqrt(dx2 * dx2 + dy2 * dy2);
            }

            // Dibujar según tipo seleccionado en el fallback
            switch (measureFallbackType) {
              case 'circle':
                drawMeasureFallbackCircle(p1, p2, distanciaMeters2);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'circle', center: p1, edge: p2, radiusMeters: distanciaMeters2 });
                console.log('Fallback Medición (marker circle):', (distanciaMeters2 && typeof distanciaMeters2 === 'number') ? distanciaMeters2.toFixed(2) + ' m' : distanciaMeters2, p1, p2);
                break;
              case 'cone':
                drawMeasureFallbackSector(p1, p2, distanciaMeters2, measureFallbackConeAngle);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'cone', apex: p1, directionPoint: p2, radiusMeters: distanciaMeters2, angle: measureFallbackConeAngle });
                console.log('Fallback Medición (marker cone):', (distanciaMeters2 && typeof distanciaMeters2 === 'number') ? distanciaMeters2.toFixed(2) + ' m' : distanciaMeters2, p1, p2);
                break;
              case 'square':
                drawMeasureFallbackRect(p1, p2, distanciaMeters2);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'square', corners: [p1, p2], diagonalMeters: distanciaMeters2 });
                console.log('Fallback Medición (marker square):', (distanciaMeters2 && typeof distanciaMeters2 === 'number') ? distanciaMeters2.toFixed(2) + ' m (diag)' : distanciaMeters2, p1, p2);
                break;
              case 'line':
              default:
                drawMeasureFallbackLine(p1, p2, distanciaMeters2);
                if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'line', points: [p1, p2], distanceMeters: distanciaMeters2 });
                console.log('Fallback Medición (marker line):', (distanciaMeters2 && typeof distanciaMeters2 === 'number') ? distanciaMeters2.toFixed(2) + ' m' : distanciaMeters2, p1, p2);
                break;
            }

            // limpiar marcador temporal
            var temp3 = OPG.q('#measure-temp-point'); if (temp3) temp3.parentNode.removeChild(temp3);

            measureFallbackPoints = [];
          }
        }

        OPG.bus.on('mapa:measurement', function(data) {
          console.log('[MapaInteractivo] mapa:measurement recibido:', data, 'mapaUtils presente:', !!(OPG && OPG.mapaUtils && OPG.mapaUtils.medicion));
          state.measurementActive = !!data.active;
          // Actualizar tipo de medición para el fallback si se proporcionó
          if (data && data.type) {
            measureFallbackType = data.type;
            console.log('[MapaInteractivo] fallback measure type set to:', measureFallbackType);
          }

          // Si se activa la medición y estábamos arrastrando, detener arrastre
          if (state.measurementActive && state.isDragging) {
            state.isDragging = false;
          }

          // Determinar cursor deseado
          if (state.measurementActive) {
            var c = 'crosshair';
            if (dom.mapContent) dom.mapContent.style.cursor = c;
            if (dom.image) dom.image.style.cursor = c;
            if (dom.markersLayer) dom.markersLayer.style.cursor = c;

            // Si mapaUtils no está disponible, añadir fallback de medición local
            if (!(OPG && OPG.mapaUtils && OPG.mapaUtils.medicion)) {
              if (dom.mapContent) {
                dom.mapContent.addEventListener('click', measureFallbackClick, true);
                console.log('Fallback: listener de medición añadido al #map-content');
              }
              // Añadir listener específico en la capa de marcadores para poder usar personajes como puntos
              if (dom.markersLayer) {
                dom.markersLayer.addEventListener('click', measureFallbackMarkerClick, true);
                console.log('Fallback: listener de medición añadido al #map-markers-layer (marker clicks)');
              }
            } else {
              // Si mapaUtils ya está disponible, asegurarnos de eliminar cualquier fallback previo
              try { if (dom.mapContent) dom.mapContent.removeEventListener('click', measureFallbackClick, true); } catch (e) { try { if (dom.mapContent) dom.mapContent.removeEventListener('click', measureFallbackClick); } catch (ee) {} }
              try { if (dom.markersLayer) dom.markersLayer.removeEventListener('click', measureFallbackMarkerClick, true); } catch (e) { try { if (dom.markersLayer) dom.markersLayer.removeEventListener('click', measureFallbackMarkerClick); } catch (ee) {} }
              clearMeasureFallbackVisuals();
            }
          } else {
            var c = state.isSettingInitialPosition ? 'crosshair' : (state.isDragging ? 'grabbing' : 'grab');
            if (dom.mapContent) dom.mapContent.style.cursor = c;
            // Para la imagen y la capa de marcadores, dejar cursor vacío para heredar si estamos en estado por defecto
            if (dom.image) dom.image.style.cursor = c;
            if (dom.markersLayer) dom.markersLayer.style.cursor = c;

            // Remover fallback si existía
            try { if (dom.mapContent) dom.mapContent.removeEventListener('click', measureFallbackClick, true); } catch (e) { try { if (dom.mapContent) dom.mapContent.removeEventListener('click', measureFallbackClick); } catch (ee) {} }
            clearMeasureFallbackVisuals();
          }
        });
      }

      // Botón para establecer ubicación inicial
      var setInitialBtn = OPG.q('#set-initial-position-btn');
      if (setInitialBtn) {
        setInitialBtn.addEventListener('click', function() {
          toggleSetInitialPositionMode();
        });
      }

      dom.mapContent.addEventListener('mouseleave', function() {
        dom.mapContent.style.cursor = 'default';
      });
    }

    // Doble click para reset
    if (dom.image) {
      dom.image.addEventListener('dblclick', resetZoom);
    }

    // Atajos de teclado
    document.addEventListener('keydown', function(e) {
      if (!state.isVisible) return;
      
      // + o = para zoom in
      if (e.key === '+' || e.key === '=') {
        e.preventDefault();
        zoomIn();
      }
      // - para zoom out
      else if (e.key === '-') {
        e.preventDefault();
        zoomOut();
      }
      // 0 para reset
      else if (e.key === '0') {
        e.preventDefault();
        resetZoom();
      }
    });
  }

  /* ========== Sistema de Ubicación Inicial ========== */
  function toggleSetInitialPositionMode() {
    state.isSettingInitialPosition = !state.isSettingInitialPosition;
    
    var btn = OPG.q('#set-initial-position-btn');
    var fixBtn = document.getElementById('fix-initial-position-btn');
    var cancelBtn = document.getElementById('cancel-initial-position-btn');
    var delBtn = document.getElementById('delete-initial-position-btn');
    if (!btn) return;
    
    if (state.isSettingInitialPosition) {
      // Activar modo
      btn.style.background = 'rgba(46, 213, 115, 0.8)';
      btn.style.borderColor = '#2ed573';
      btn.innerHTML = '<i class="fa fa-crosshairs"></i> Seleccionando';
      if (dom.mapContent) dom.mapContent.style.cursor = 'crosshair';
      
      if (fixBtn) fixBtn.style.display = 'inline-block';
      if (cancelBtn) cancelBtn.style.display = 'inline-block';
      if (delBtn) delBtn.style.display = 'none'; // se oculta mientras se está estableciendo nueva posición

      
      // Mostrar instrucciones
      OPG.bus.emit('mapa:notification', {
        type: 'info',
        message: 'Haz clic en el mapa para establecer la ubicación inicial'
      });
    } else {
      // Salir del modo, punto de decisión
      btn.style.background = 'rgba(46, 213, 115, 0.3)';
      btn.style.borderColor = 'rgba(46, 213, 115, 0.6)';
      btn.innerHTML = '<i class="fa fa-map-pin"></i> Establecer Inicio';
      if (dom.mapContent) dom.mapContent.style.cursor = 'grab';

      if (fixBtn) fixBtn.style.display = 'none';
      if (cancelBtn) cancelBtn.style.display = 'none';
      // delBtn se gestiona en finalize/cancel, no aquí
    }
  }

  function finalizeInitialPosition() {
    var btn = OPG.q('#set-initial-position-btn');
    var delBtn = document.getElementById('delete-initial-position-btn');
    var fixBtn = document.getElementById('fix-initial-position-btn');
    var cancelBtn = document.getElementById('cancel-initial-position-btn');

    if (!state.initialPosition) {
        OPG.bus.emit('mapa:notification', {
        type: 'warning',
        message: 'No se ha seleccionado ninguna ubicación inicial'
        });
        if (fixBtn) {
        fixBtn.style.transition = 'background-color 0.3s';
        fixBtn.style.background = '#ff4757';
        setTimeout(function() {
            fixBtn.style.background = 'rgba(46, 213, 115, 0.15)';
        }, 600);
        }
        return;
    }

    // Escribir inputs ocultos
    var fx = document.getElementById('map_initial_x');
    var fy = document.getElementById('map_initial_y');
    var ffixed = document.getElementById('map_initial_fixed');
    var ffid = document.getElementById('map_initial_fid'); // <-- unificado

    if (fx) fx.value = (state.initialPosition.x || 0).toFixed(3);
    if (fy) fy.value = (state.initialPosition.y || 0).toFixed(3);
    if (ffixed) ffixed.value = '1';
    if (ffid) ffid.value = (typeof fichaActualId !== 'undefined' && fichaActualId) ? String(fichaActualId) : '';

    // UI
    if (btn) {
        btn.innerHTML = '<i class="fa fa-check"></i> Inicio Establecido';
        btn.disabled = true;
        btn.style.opacity = '0.7';
    }
    if (delBtn) delBtn.style.display = 'inline-block';
    if (fixBtn) fixBtn.style.display = 'none';
    if (cancelBtn) cancelBtn.style.display = 'none';

    // Salir del modo selección
    state.isSettingInitialPosition = false;
    if (dom.mapContent) dom.mapContent.style.cursor = 'grab';

    OPG.bus.emit('mapa:notification', {
        type: 'success',
        message: 'Ubicación inicial establecida correctamente'
    });
  }

  function cancelInitialPosition() {
    // Cancela y limpia todo (incluye inputs y UI)
    removeInitialPosition();

    OPG.bus.emit('mapa:notification', {
        type: 'info',
        message: 'Establecimiento de ubicación inicial cancelado'
    });
  }
  
  function handleSetInitialPosition(e) {
    if (!state.isSettingInitialPosition) return;
    
    // Calcular posición relativa en el mapa (teniendo en cuenta zoom y pan)
    var rect = dom.image.getBoundingClientRect();
    var displayedX = (e.clientX - rect.left);
    var displayedY = (e.clientY - rect.top);

    // Mapear a coordenadas originales (no transformadas) dividiendo por el zoom
    var zoom = state.zoom || 1;
    var origX = displayedX / zoom;
    var origY = displayedY / zoom;

    var cell = 20; // tamaño de celda en píxeles (coordenadas originales)
    var cellX = Math.floor(origX / cell);
    var cellY = Math.floor(origY / cell);

    // Centro de la celda en coordenadas originales
    var cxOriginal = cellX * cell + cell / 2;
    var cyOriginal = cellY * cell + cell / 2;

    // Convertir a porcentaje respecto a la imagen original (no transformada)
    var imgW = dom.image.width || dom.image.naturalWidth;
    var imgH = dom.image.height || dom.image.naturalHeight;
    if (!imgW || !imgH) {
      console.warn('[MapaInteractivo] Imagen sin dimensiones válidas');
      return;
    }

    var xPercent = (cxOriginal / imgW) * 100;
    var yPercent = (cyOriginal / imgH) * 100;

    // Validar límites (preparado para futura restricción de márgenes)
    var bounds = state.config.initialPositionBounds;
    if (xPercent < bounds.minX || xPercent > bounds.maxX || yPercent < bounds.minY || yPercent > bounds.maxY) {
      OPG.bus.emit('mapa:notification', {
        type: 'warning',
        message: 'La ubicación debe estar dentro de los márgenes permitidos'
      });
      return;
    }

    // Guardar ubicación inicial (en porcentaje, para persistencia)
    state.initialPosition = { x: xPercent, y: yPercent };

    if (!EPHEMERAL_ICONS) { // Sólo guardar en caso de no ser íconos efímeros
        OPG.storage.set('thread_initial_pos_' + state.tid, JSON.stringify(state.initialPosition));
    }

    // Eliminar marcador anterior si existe
    if (state.markers.some(function(m) { return m.id === 'initial-position'; })) {
      removeMarker('initial-position');
    }
    // Obtener avatar del usuario si está disponible y loguear para depuración
    var avatarUrl = null;
    if (typeof OPG.user === 'object') {
      console.log('[MapaInteractivo] Avatar detectado:', OPG.user.avatar);
      if (OPG.user.avatar) {
        avatarUrl = OPG.user.avatar;
      }
    } else {
      console.log('[MapaInteractivo] OPG.user no es un objeto:', OPG.user);
    }
    // Añadir marcador exactamente en el centro de la celda
    addMarker({
      id: 'initial-position',
      x: xPercent,
      y: yPercent,
      label: 'Inicio',
      color: '#2ed573',
      avatar: avatarUrl
    });
  }

  /* ========== API Pública ========== */
  OPG.mapaInteractivo = {
    init: init,
    load: loadMap,
    show: show,
    hide: hide,
    toggle: toggle,
    zoom: {
      set: setZoom,
      in: zoomIn,
      out: zoomOut,
      reset: resetZoom
    },
    markers: {
      add: addMarker,
      remove: removeMarker,
      clear: clearMarkers,
      getAll: function() { return state.markers.slice(); }
    },
    initialPosition: {
      set: handleSetInitialPosition,
      get: function() { return state.initialPosition; },
      toggle: toggleSetInitialPositionMode,
      remove: removeInitialPosition
    },
    getState: function() {
      return {
        tid: state.tid,
        mapUrl: state.mapUrl,
        isVisible: state.isVisible,
        zoom: state.zoom,
        pan: state.pan,
        markersCount: state.markers.length
      };
    }
  };

})(OPG, window);
