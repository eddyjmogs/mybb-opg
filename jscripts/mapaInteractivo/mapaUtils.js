/* Utilidades Avanzadas del Mapa Interactivo */
(function(OPG, w) {
  'use strict';

  // ========================================
  // EXPORTAR/IMPORTAR CONFIGURACIÓN
  // ========================================
  
  function exportarConfig() {
    var state = OPG.mapaInteractivo.getState();
    var markers = OPG.mapaInteractivo.markers.getAll();
    
    var config = {
      version: '1.0',
      tid: state.tid,
      mapUrl: state.mapUrl,
      zoom: state.zoom,
      pan: state.pan,
      markers: markers.map(function(m) {
        return {
          x: m.x,
          y: m.y,
          label: m.label,
          color: m.color,
          avatar: m.avatar,
          data: m.data
        };
      }),
      timestamp: Date.now()
    };
    
    return config;
  }

  function importarConfig(config) {
    if (!config || config.version !== '1.0') {
      console.error('Configuración inválida');
      return false;
    }
    
    // Cargar mapa
    OPG.mapaInteractivo.load(config.mapUrl);
    
    // Esperar a que cargue
    OPG.bus.on('mapa:loaded', function() {
      // Restaurar zoom y pan
      if (config.zoom) OPG.mapaInteractivo.zoom.set(config.zoom);
      
      // Cargar marcadores
      config.markers.forEach(function(m) {
        OPG.mapaInteractivo.markers.add(m);
      });
    });
    
    return true;
  }

  function descargarJSON() {
    var config = exportarConfig();
    var json = JSON.stringify(config, null, 2);
    var blob = new Blob([json], { type: 'application/json' });
    var url = URL.createObjectURL(blob);
    
    var a = document.createElement('a');
    a.href = url;
    a.download = 'mapa_thread_' + config.tid + '.json';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  function generarBBCode() {
    var config = exportarConfig();
    var json = JSON.stringify(config);
    return '[mapa_data]' + json + '[/mapa_data]';
  }

  // ========================================
  // MEDICIÓN DE DISTANCIAS
  // ========================================
  
  var measureMode = false;
  var measurePoints = [];
  var measureType = 'line';
  var _dynamicMoveHandler = null;

  // Asegurar flag global inicial (false) para que otros módulos la consulten
  if (typeof OPG._measurementActive === 'undefined') OPG._measurementActive = false;
  if (typeof OPG._measurementMaxMeters === 'undefined') OPG._measurementMaxMeters = null;

  // Devuelve colores en base al umbral máximo (si existe)
  function _getMeasureColors(meters) {
    // Aceptar strings numéricos y forzar Number; si no es válido, considerar null
    var max = null;
    if (OPG && typeof OPG._measurementMaxMeters !== 'undefined' && OPG._measurementMaxMeters !== null) {
      var parsed = Number(OPG._measurementMaxMeters);
      max = !isNaN(parsed) ? parsed : null;
    }

    var base = { stroke: '#ff6b6b', fill: 'rgba(255,107,107,0.15)', text: 'white' };

    // Debug: si está en modo debug del mapa, loggear valores para ayudar a diagnóstico
    try {
      if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaUtils] _getMeasureColors — meters:', meters, ' max:', max);
    } catch (e) {}

    if (max === null || typeof meters !== 'number' || isNaN(meters)) return base;
    if (meters <= max) return { stroke: '#2ed573', fill: 'rgba(46,213,115,0.15)', text: 'white' };
    return { stroke: '#c0392b', fill: 'rgba(192,57,43,0.18)', text: 'white' };
  }

  // Escuchar cambios en el control de max
  if (OPG && OPG.bus) {
    OPG.bus.on('mapa:measurement-max', function(data) {
      if (data && typeof data.maxMeters === 'number') OPG._measurementMaxMeters = data.maxMeters;
      else OPG._measurementMaxMeters = null;
    });
  }

  // Convertir coordenadas % a px (uuar dims sionesdnaiurmles pnraoevitar noblesesca ndoacoesCSS t aasfo mr doble escalado con CSS transform)
  function percentToPx(x, y) {
    var img = OPG.q('#map-image');
    if (!img) return { x: 0, y: 0, w: 0, h: 0 };
    var rect = img.getBoundingClientRect ? img.getBoundingClientRect() : null;
    var w = (img.naturalWidth && img.naturalWidth > 0) ? img.naturalWidth : (rect ? rect.width : (img.width || 0));
    var h = (img.naturalHeight && img.naturalHeight > 0) ? img.naturalHeight : (rect ? rect.height : (img.height || 0));
    w = w || 0; h = h || 0;
    return { x: (x / 100) * w, y: (y / 100) * h, w: w, h: h };
  }

  // Eliminar formas de medida previas
  function clearMeasureShapes() {
    var ids = ['measure-line', 'measure-shape', 'measure-temp-point', 'measure-line-inner', 'measure-circle-inner', 'measure-cone-inner', 'measure-rect-inner'];
    ids.forEach(function(id) {
      var el = OPG.q('#' + id);
      if (el) el.parentNode.removeChild(el);
    });
  }



  // Handler de click para medición: registra puntos en % y dibuja según el tipo
  function measureClickHandler(e) {
    console.log('measureClickHandler fired — type:', measureType, 'mode:', measureMode, 'points so far:', measurePoints.length);
    if (!measureMode) return;

    // Si el click vino de un marcador, usar sus coordenadas (evitar abrir su acción)
    var markerEl = (e && e.target && e.target.closest) ? e.target.closest('.map-marker') : null;
    var x, y;
    if (markerEl) {
      var markerId = markerEl.getAttribute && markerEl.getAttribute('data-marker-id');
      if (markerId && OPG && OPG.mapaInteractivo && OPG.mapaInteractivo.markers && OPG.mapaInteractivo.markers.getAll) {
        var markers = OPG.mapaInteractivo.markers.getAll();
        for (var i = 0; i < markers.length; i++) {
          if (markers[i].id === markerId) {
            x = markers[i].x;
            y = markers[i].y;
            break;
          }
        }
      }
      try { if (e.stopPropagation) e.stopPropagation(); if (e.preventDefault) e.preventDefault(); } catch (err) {}
    }

    if (typeof x === 'undefined' || typeof y === 'undefined') {
      var img = OPG.q('#map-image');
      if (img) {
        var imgRect = img.getBoundingClientRect();
        x = ((e.clientX - imgRect.left) / imgRect.width) * 100;
        y = ((e.clientY - imgRect.top) / imgRect.height) * 100;
      } else {
        var mapContent = OPG.q('#map-content');
        if (!mapContent) return;
        var rect = mapContent.getBoundingClientRect();
        x = ((e.clientX - rect.left) / rect.width) * 100;
        y = ((e.clientY - rect.top) / rect.height) * 100;
      }
    }

    measurePoints.push({ x: x, y: y });

    // Mostrar marcador temporal (usar #map-content como fallback)
    var temp = OPG.q('#measure-temp-point');
    if (temp) temp.parentNode.removeChild(temp);
    var markersLayer = OPG.q('#map-markers-layer');
    var fallbackLayer = OPG.q('#map-content');
    var layerToUse = markersLayer || fallbackLayer;
    if (layerToUse) {
      var dot = document.createElement('div');
      dot.id = 'measure-temp-point';
      dot.style.cssText = 'position:absolute;left:' + x + '%;top:' + y + '%;width:10px;height:10px;margin-left:-5px;margin-top:-5px;border-radius:50%;background:#fff;border:2px solid #ff6b6b;z-index:60;pointer-events:none;';
      layerToUse.appendChild(dot);
    }

    // Si es el primer punto, iniciar medición dinámica (seguir cursor)
    if (measurePoints.length === 1) {
      startDynamicMeasure(x, y);
      return;
    }

    // Si llegamos aquí es porque es el segundo clic: finalizar medición
    // Remover listener dinámico
    stopDynamicMeasure();

    // Finalizar con el punto actual (ya estaba añadido)
    var p1 = measurePoints[0];
    var p2 = measurePoints[1];

    var distanciaPercent = calcularDistancia(p1.x, p1.y, p2.x, p2.y);
    var distanciaMeters = calcularDistanciaMetros(p1.x, p1.y, p2.x, p2.y);

    // Limpiar forma previa (incluye la línea dinámica)
    clearMeasureShapes();

    // Dibujar según tipo seleccionado
    switch (measureType) {
      case 'circle':
        dibujarCircleMedicion(p1.x, p1.y, p2.x, p2.y, distanciaMeters);
        if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'circle', center: p1, edge: p2, radiusMeters: distanciaMeters });
        break;
      case 'cone':
        dibujarSectorMedicion(p1.x, p1.y, p2.x, p2.y, distanciaMeters, 60);
        if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'cone', apex: p1, directionPoint: p2, radiusMeters: distanciaMeters, angle: 60 });
        break;
      case 'square':
        dibujarRectMedicion(p1.x, p1.y, p2.x, p2.y, distanciaMeters);
        if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'square', corners: [p1, p2], diagonalMeters: distanciaMeters });
        break;
      case 'line':
      default:
        dibujarLineaMedicion(p1.x, p1.y, p2.x, p2.y, distanciaPercent);
        if (OPG && OPG.bus) OPG.bus.emit('mapa:measured', { type: 'line', points: [p1, p2], distancePercent: distanciaPercent, distanceMeters: distanciaMeters });
        break;
    }

    // Eliminar marcador temporal
    var temp2 = OPG.q('#measure-temp-point');
    if (temp2) temp2.parentNode.removeChild(temp2);

    measurePoints = [];
  }

  // Inicia medición dinámica: crea línea SVG con punto fijo y añade listener mousemove
  function startDynamicMeasure(px, py) {
    var markersLayer = OPG.q('#map-markers-layer');
    var fallbackLayer = OPG.q('#map-content');
    var layerToUse = markersLayer || fallbackLayer;
    if (!layerToUse) return;
    clearMeasureShapes();

    var mapContent = OPG.q('#map-content');
    if (!mapContent) return;

    // Crear elementos según tipo
    if (measureType === 'line') {
      var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.id = 'measure-line';
      svg.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;';

      var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
      line.setAttribute('x1', px + '%');
      line.setAttribute('y1', py + '%');
      line.setAttribute('x2', px + '%');
      line.setAttribute('y2', py + '%');
      line.setAttribute('stroke', '#ff6b6b');
      line.setAttribute('stroke-width', '2');
      line.setAttribute('stroke-dasharray', '5,5');
      line.id = 'measure-line-seg';

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', px + '%');
      text.setAttribute('y', py + '%');
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '14');
      text.setAttribute('font-weight', 'bold');
      text.id = 'measure-line-text';
      text.textContent = '';

      svg.appendChild(line);
      svg.appendChild(text);
      layerToUse.appendChild(svg);

      _dynamicMoveHandler = function(ev) {
        var rect = OPG.q('#map-image') ? OPG.q('#map-image').getBoundingClientRect() : mapContent.getBoundingClientRect();
        var cx = ((ev.clientX - rect.left) / rect.width) * 100;
        var cy = ((ev.clientY - rect.top) / rect.height) * 100;
        var lineEl = OPG.q('#measure-line-seg');
        var textEl = OPG.q('#measure-line-text');
        if (!lineEl || !textEl) return;
        lineEl.setAttribute('x2', cx + '%');
        lineEl.setAttribute('y2', cy + '%');
        var midX = (parseFloat(lineEl.getAttribute('x1')) + cx) / 2;
        var midY = (parseFloat(lineEl.getAttribute('y1')) + cy) / 2;
        textEl.setAttribute('x', midX + '%');
        textEl.setAttribute('y', midY + '%');

        // Calcular metros y mostrar
        var x1 = parseFloat(lineEl.getAttribute('x1'));
        var y1 = parseFloat(lineEl.getAttribute('y1'));
        var meters = calcularDistanciaMetros(x1, parseFloat(lineEl.getAttribute('y1')), cx, cy);
        if (typeof meters === 'number') textEl.textContent = meters.toFixed(2) + ' m';
        else textEl.textContent = calcularDistancia(x1, y1, cx, cy).toFixed(2) + '%';

        var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
        if (Number.isFinite(max) && typeof meters === 'number' && meters > max) {
          var rect = OPG.q('#map-image') ? OPG.q('#map-image').getBoundingClientRect() : mapContent.getBoundingClientRect();
          var imgW = rect.width || 0, imgH = rect.height || 0;
          if (imgW && imgH) {
            var x1px = (x1/100) * imgW; var y1px = (y1/100) * imgH; var x2px = (cx/100) * imgW; var y2px = (cy/100) * imgH;
            var dx = x2px - x1px; var dy = y2px - y1px; var distPx = Math.sqrt(dx*dx+dy*dy);
            var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
            var scale = 1; try { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1;}catch(e){}
            var maxPx = max * pxPerMeter * scale;
            var ux = dx / distPx; var uy = dy / distPx;
            var mxpx = x1px + ux * Math.min(maxPx, distPx);
            var mxpy = y1px + uy * Math.min(maxPx, distPx);
            var mx = (mxpx / imgW) * 100; var my = (mxpy / imgH) * 100;

            var inner = OPG.q('#measure-line-inner');
            if (!inner) {
              inner = document.createElementNS('http://www.w3.org/2000/svg','line'); inner.id='measure-line-inner'; inner.setAttribute('stroke-width','3');
              var parentSvg = lineEl.closest('svg') || lineEl.parentNode; parentSvg.appendChild(inner);
            }
            inner.setAttribute('x1', lineEl.getAttribute('x1')); inner.setAttribute('y1', lineEl.getAttribute('y1'));
            inner.setAttribute('x2', mx + '%'); inner.setAttribute('y2', my + '%'); inner.setAttribute('stroke', '#2ed573');

            lineEl.setAttribute('stroke', '#c0392b');
            textEl.setAttribute('fill', '#c0392b');
            return;
          }
        }

        var colors = _getMeasureColors(meters);
        var inner2 = OPG.q('#measure-line-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
        lineEl.setAttribute('stroke', colors.stroke);
        textEl.setAttribute('fill', colors.text);
      };
    } else if (measureType === 'circle') {
      var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.id = 'measure-shape';
      svg.setAttribute('viewBox', '0 0 100 100');
      svg.setAttribute('preserveAspectRatio', 'none');
      svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';

      var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      circle.setAttribute('cx', px);
      circle.setAttribute('cy', py);
      circle.setAttribute('r', 0);
      circle.setAttribute('fill', 'rgba(255,107,107,0.15)');
      circle.setAttribute('stroke', '#ff6b6b');
      circle.setAttribute('stroke-width', '0.5');
      circle.id = 'measure-circle-dyn';

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', px);
      text.setAttribute('y', py);
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '3');
      text.setAttribute('font-weight', 'bold');
      text.id = 'measure-circle-text';

      svg.appendChild(circle);
      svg.appendChild(text);
      layerToUse.appendChild(svg);

      _dynamicMoveHandler = function(ev) {
        var rect = OPG.q('#map-image') ? OPG.q('#map-image').getBoundingClientRect() : mapContent.getBoundingClientRect();
        var cx = ((ev.clientX - rect.left) / rect.width) * 100;
        var cy = ((ev.clientY - rect.top) / rect.height) * 100;
        var cx0 = px, cy0 = py;
        var circleEl = OPG.q('#measure-circle-dyn');
        var textEl = OPG.q('#measure-circle-text');
        if (!circleEl || !textEl) return;
        // calcular y mostrar metros
        var meters = calcularDistanciaMetros(cx0, cy0, cx, cy);

        // Intentar usar coordenadas en px (si el rect está disponible) para mantener la circularidad
        var imgRectLocal = rect;
        var imgW = (imgRectLocal && imgRectLocal.width) || 0;
        var imgH = (imgRectLocal && imgRectLocal.height) || 0;

        // Si aún no se ha hecho la conversión a px, hágalo ahora (setea viewBox en px y convierte cx/cy)
        var svgEl = circleEl.closest('svg') || circleEl.parentNode;
        if (svgEl && !svgEl._usingPx && imgW && imgH) {
          svgEl.setAttribute('viewBox', '0 0 ' + imgW + ' ' + imgH);
          svgEl.setAttribute('preserveAspectRatio', 'xMidYMid meet');
          var cx0Px = (cx0 / 100) * imgW;
          var cy0Px = (cy0 / 100) * imgH;
          circleEl.setAttribute('cx', cx0Px);
          circleEl.setAttribute('cy', cy0Px);
          textEl.setAttribute('x', cx0Px);
          textEl.setAttribute('y', cy0Px);
          svgEl._usingPx = true;
        }

        // Convertir coordenadas actuales a px si es posible
        var cxPxNow = imgW ? (cx / 100) * imgW : cx;
        var cyPxNow = imgH ? (cy / 100) * imgH : cy;
        var pxPxNow = imgW ? (cx0 / 100) * imgW : cx0;
        var pyPxNow = imgH ? (cy0 / 100) * imgH : cy0;

        // radio en px (mantener circularidad)
        var dxpx = cxPxNow - pxPxNow;
        var dypx = cyPxNow - pyPxNow;
        var rPx = Math.sqrt(dxpx*dxpx + dypx*dypx);

        // actualizar texto en px si tenemos dimensiones
        if (imgW && imgH) {
          textEl.setAttribute('x', Math.min(imgW - 8, pxPxNow + rPx + 8));
          textEl.setAttribute('y', Math.max(14, pyPxNow));
        } else {
          var rPct = Math.sqrt(Math.pow(cx - cx0, 2) + Math.pow(cy - cy0, 2));
          textEl.setAttribute('x', Math.min(99, cx0 + rPct + 2));
          textEl.setAttribute('y', Math.max(2, cy0));
        }
        textEl.textContent = (typeof meters === 'number') ? meters.toFixed(2) + ' m' : '';

        var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
        if (Number.isFinite(max) && Number.isFinite(meters) && imgW && imgH && meters > max) {
          var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
          var scale = 1; try { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1;}catch(e){}
          var measuredPx = meters * pxPerMeter * scale; var maxPx = max * pxPerMeter * scale;

          // outer red en px
          circleEl.setAttribute('r', Math.min(imgW, measuredPx));
          circleEl.setAttribute('fill', 'rgba(192,57,43,0.18)');
          circleEl.setAttribute('stroke', '#c0392b');
          circleEl.setAttribute('stroke-width', '2');

          // inner green
          var inner = OPG.q('#measure-circle-inner');
          if (!inner) {
            inner = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            inner.id = 'measure-circle-inner';
            var parent = circleEl.closest('svg') || circleEl.parentNode;
            parent.appendChild(inner);
          }
          inner.setAttribute('cx', circleEl.getAttribute('cx'));
          inner.setAttribute('cy', circleEl.getAttribute('cy'));
          inner.setAttribute('r', Math.min(imgW, maxPx));
          inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
          inner.setAttribute('stroke', '#2ed573');
          inner.setAttribute('stroke-width', '2');
          textEl.setAttribute('fill', '#c0392b');

        } else {
          var colors = _getMeasureColors(meters);
          // si no tenemos dimensiones, fallback a r en %
          if (!imgW || !imgH) {
            var rPct = Math.sqrt(Math.pow(cx - cx0, 2) + Math.pow(cy - cy0, 2));
            circleEl.setAttribute('r', rPct);
            textEl.setAttribute('x', Math.min(99, cx0 + rPct + 2));
            textEl.setAttribute('y', Math.max(2, cy0));
          } else {
            circleEl.setAttribute('r', rPx);
          }
          circleEl.setAttribute('fill', colors.fill);
          circleEl.setAttribute('stroke', colors.stroke);
          textEl.setAttribute('fill', colors.text);
          var inner2 = OPG.q('#measure-circle-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
        }
      };
    } else if (measureType === 'cone') {
      var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.id = 'measure-shape';
      svg.setAttribute('viewBox', '0 0 100 100');
      svg.setAttribute('preserveAspectRatio', 'none');
      svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';

      var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('fill', 'rgba(255,107,107,0.18)');
      path.setAttribute('stroke', '#ff6b6b');
      path.setAttribute('stroke-width', '0.5');
      path.id = 'measure-cone-dyn';

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', px);
      text.setAttribute('y', py);
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '3');
      text.setAttribute('font-weight', 'bold');
      text.id = 'measure-cone-text';

      svg.appendChild(path);
      svg.appendChild(text);
      layerToUse.appendChild(svg);

      var angleDeg = 60; // valor por defecto, puede exponerse luego

      _dynamicMoveHandler = function(ev) {
        var rect = OPG.q('#map-image') ? OPG.q('#map-image').getBoundingClientRect() : mapContent.getBoundingClientRect();
        var cx = ((ev.clientX - rect.left) / rect.width) * 100;
        var cy = ((ev.clientY - rect.top) / rect.height) * 100;
        var ax = px, ay = py;
        var radiusPct = Math.sqrt(Math.pow(cx - ax, 2) + Math.pow(cy - ay, 2));
        var theta = Math.atan2(cy - ay, cx - ax);
        var half = (angleDeg * Math.PI / 180) / 2;
        var startAngle = theta - half;
        var endAngle = theta + half;
        var x1 = ax + radiusPct * Math.cos(startAngle);
        var y1 = ay + radiusPct * Math.sin(startAngle);
        var x2 = ax + radiusPct * Math.cos(endAngle);
        var y2 = ay + radiusPct * Math.sin(endAngle);
        var largeArc = (angleDeg > 180) ? 1 : 0;
        var d = 'M ' + ax + ' ' + ay + ' L ' + x1 + ' ' + y1 + ' A ' + radiusPct + ' ' + radiusPct + ' 0 ' + largeArc + ' 1 ' + x2 + ' ' + y2 + ' Z';
        var pathEl = OPG.q('#measure-cone-dyn');
        var textEl = OPG.q('#measure-cone-text');
        if (!pathEl || !textEl) return;
        pathEl.setAttribute('d', d);
        var meters = calcularDistanciaMetros(ax, ay, cx, cy);
        textEl.setAttribute('x', ax + radiusPct / 2);
        textEl.setAttribute('y', ay);
        textEl.textContent = (typeof meters === 'number') ? meters.toFixed(2) + ' m' : '';

        var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
        var imgRectLocal = rect;
        if (Number.isFinite(max) && Number.isFinite(meters) && meters > max && imgRectLocal && imgRectLocal.width) {
          var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
          var scale = 1; try { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1;}catch(e){}
          var measuredPx = meters * pxPerMeter * scale; var maxPx = max * pxPerMeter * scale;
          // vector en px para la dirección (soporta anisotropía)
          var dxpx = (cx - ax) / 100 * imgRectLocal.width; var dypx = (cy - ay) / 100 * imgRectLocal.height;
          var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx) || 1;
          var ux = dxpx / distPx; var uy = dypx / distPx;
          var measuredPct = Math.sqrt(Math.pow(ux * (measuredPx / imgRectLocal.width) * 100, 2) + Math.pow(uy * (measuredPx / imgRectLocal.height) * 100, 2));
          var maxPct = Math.sqrt(Math.pow(ux * (maxPx / imgRectLocal.width) * 100, 2) + Math.pow(uy * (maxPx / imgRectLocal.height) * 100, 2));

          // Outer measured (red)
          var x1m = ax + measuredPct * Math.cos(startAngle);
          var y1m = ay + measuredPct * Math.sin(startAngle);
          var x2m = ax + measuredPct * Math.cos(endAngle);
          var y2m = ay + measuredPct * Math.sin(endAngle);
          var dOuter = 'M ' + ax + ' ' + ay + ' L ' + x1m + ' ' + y1m + ' A ' + measuredPct + ' ' + measuredPct + ' 0 ' + largeArc + ' 1 ' + x2m + ' ' + y2m + ' Z';
          pathEl.setAttribute('d', dOuter);
          pathEl.setAttribute('fill', 'rgba(192,57,43,0.18)');
          pathEl.setAttribute('stroke', '#c0392b');
          pathEl.setAttribute('stroke-width', '0.5');

          // Inner max (green)
          var inner = OPG.q('#measure-cone-inner');
          if (!inner) { inner = document.createElementNS('http://www.w3.org/2000/svg','path'); inner.id='measure-cone-inner'; var parent=pathEl.closest('svg')||pathEl.parentNode; parent.appendChild(inner); }
          var x1i = ax + maxPct * Math.cos(startAngle); var y1i = ay + maxPct * Math.sin(startAngle);
          var x2i = ax + maxPct * Math.cos(endAngle); var y2i = ay + maxPct * Math.sin(endAngle);
          var dInner = 'M ' + ax + ' ' + ay + ' L ' + x1i + ' ' + y1i + ' A ' + maxPct + ' ' + maxPct + ' 0 ' + largeArc + ' 1 ' + x2i + ' ' + y2i + ' Z';
          inner.setAttribute('d', dInner); inner.setAttribute('fill','rgba(46,213,115,0.15)'); inner.setAttribute('stroke','#2ed573'); inner.setAttribute('stroke-width','0.5');
          textEl.setAttribute('fill','#c0392b');
          try { if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaUtils] Cone dynamic clipping — meters:', meters, 'max:', max, 'dxpx:', dxpx, 'dypx:', dypx, 'ux:', ux, 'uy:', uy, 'measuredPx:', measuredPx, 'maxPx:', maxPx, 'measuredPct:', measuredPct, 'maxPct:', maxPct); } catch(e) {}
        } else {
          var colors = _getMeasureColors(meters);
          pathEl.setAttribute('fill', colors.fill);
          pathEl.setAttribute('stroke', colors.stroke);
          textEl.setAttribute('fill', colors.text);
          var inner2 = OPG.q('#measure-cone-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
        }
      };
    } else if (measureType === 'square') {
      var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.id = 'measure-shape';
      svg.setAttribute('viewBox', '0 0 100 100');
      svg.setAttribute('preserveAspectRatio', 'none');
      svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999;';

      var rectEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      rectEl.setAttribute('x', px);
      rectEl.setAttribute('y', py);
      rectEl.setAttribute('width', 0);
      rectEl.setAttribute('height', 0);
      rectEl.setAttribute('fill', 'rgba(255,107,107,0.15)');
      rectEl.setAttribute('stroke', '#ff6b6b');
      rectEl.setAttribute('stroke-width', '0.5');
      rectEl.id = 'measure-rect-dyn';

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', px);
      text.setAttribute('y', py);
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '3');
      text.setAttribute('font-weight', 'bold');
      text.id = 'measure-rect-text';

      svg.appendChild(rectEl);
      svg.appendChild(text);
      layerToUse.appendChild(svg);

      _dynamicMoveHandler = function(ev) {
        var rect = OPG.q('#map-image') ? OPG.q('#map-image').getBoundingClientRect() : mapContent.getBoundingClientRect();
        var cx = ((ev.clientX - rect.left) / rect.width) * 100;
        var cy = ((ev.clientY - rect.top) / rect.height) * 100;
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
        textEl.textContent = (typeof meters === 'number') ? meters.toFixed(2) + ' m (diag)' : '';

        var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
        if (Number.isFinite(max) && Number.isFinite(meters) && meters > max) {
          rectDyn.setAttribute('fill', 'rgba(192,57,43,0.18)');
          rectDyn.setAttribute('stroke', '#c0392b');
          rectDyn.setAttribute('stroke-width', '0.5');

          var scale = 1; try { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1;}catch(e){}
          var scaleF = Math.max(0.001, max / meters);
          var innerW = widthP * scaleF; var innerH = heightP * scaleF;
          var innerLeft = left + (widthP - innerW) / 2; var innerTop = top + (heightP - innerH) / 2;
          var inner = OPG.q('#measure-rect-inner');
          if (!inner) { inner = document.createElementNS('http://www.w3.org/2000/svg','rect'); inner.id='measure-rect-inner'; var parent = rectDyn.closest('svg')||rectDyn.parentNode; parent.appendChild(inner); }
          inner.setAttribute('x', innerLeft); inner.setAttribute('y', innerTop); inner.setAttribute('width', innerW); inner.setAttribute('height', innerH); inner.setAttribute('fill','rgba(46,213,115,0.15)'); inner.setAttribute('stroke','#2ed573'); inner.setAttribute('stroke-width','0.5');
          textEl.setAttribute('fill','#c0392b');
        } else {
          var inner2 = OPG.q('#measure-rect-inner'); if (inner2) inner2.parentNode.removeChild(inner2);
          var colors = _getMeasureColors(meters);
          rectDyn.setAttribute('fill', colors.fill);
          rectDyn.setAttribute('stroke', colors.stroke);
          textEl.setAttribute('fill', colors.text);
        }
      };
    }

    mapContent.addEventListener('mousemove', _dynamicMoveHandler, true);
    var markersLayerEl = OPG.q('#map-markers-layer');
    if (markersLayerEl) markersLayerEl.addEventListener('mousemove', _dynamicMoveHandler, true);
  }

  // Detiene la medición dinámica y remueve listeners
  function stopDynamicMeasure() {
    var mapContent = OPG.q('#map-content');
    if (mapContent && _dynamicMoveHandler) {
      try { mapContent.removeEventListener('mousemove', _dynamicMoveHandler, true); } catch (e) { try { mapContent.removeEventListener('mousemove', _dynamicMoveHandler); } catch (ee) {} }
    }
    var markersLayerEl = OPG.q('#map-markers-layer');
    if (markersLayerEl && _dynamicMoveHandler) {
      try { markersLayerEl.removeEventListener('mousemove', _dynamicMoveHandler, true); } catch (e) { try { markersLayerEl.removeEventListener('mousemove', _dynamicMoveHandler); } catch (ee) {} }
    }
    _dynamicMoveHandler = null;
  }

  function activarMedicion(type) {
    if (type) measureType = type;
    measureMode = true;
    measurePoints = [];

    // Marcar flag global para que otros módulos como mapaInteractivo lo detecten
    OPG._measurementActive = true;

    var mapContent = OPG.q('#map-content');
    if (mapContent) {
      mapContent.style.cursor = 'crosshair';
      // Usar captura para garantizar recibir el evento antes que otros handlers que puedan detener la propagación
      mapContent.addEventListener('click', measureClickHandler, true);
      console.log('Listener de medición añadido (captura) al #map-content');

      // También escuchar clicks en la capa de marcadores para permitir usar personajes como puntos
      var markersLayer = OPG.q('#map-markers-layer');
      if (markersLayer) {
        markersLayer.addEventListener('click', measureClickHandler, true);
        console.log('Listener de medición añadido (captura) al #map-markers-layer');
      }
    }

    // Emitir evento para sincronizar UI y comportamiento
    if (OPG && OPG.bus) OPG.bus.emit('mapa:measurement', { active: true, type: measureType });

    console.log('Modo medición activado. Tipo:', measureType);
  }

  function desactivarMedicion() {
    measureMode = false;
    measurePoints = [];

    OPG._measurementActive = false;

    var mapContent = OPG.q('#map-content');
    if (mapContent) {
      mapContent.style.cursor = 'grab';
      // Remover listener en fase de captura
      try { mapContent.removeEventListener('click', measureClickHandler, true); } catch (err) { try { mapContent.removeEventListener('click', measureClickHandler); } catch (e) {} }

      var markersLayer = OPG.q('#map-markers-layer');
      if (markersLayer) {
        try { markersLayer.removeEventListener('click', measureClickHandler, true); } catch (e) { try { markersLayer.removeEventListener('click', measureClickHandler); } catch (ee) {} }
      }
    }

      // Detener cualquier medición dinámica en curso
      try { if (typeof stopDynamicMeasure === 'function') stopDynamicMeasure(); } catch (e) {}

      // Limpiar formas y temporales
      clearMeasureShapes();

    // Emitir evento para sincronizar UI y comportamiento
    if (OPG && OPG.bus) OPG.bus.emit('mapa:measurement', { active: false });
  }

  function calcularDistancia(x1, y1, x2, y2) {
    var dx = x2 - x1;
    var dy = y2 - y1;
    return Math.sqrt(dx * dx + dy * dy);
  }

  // Convertir distancia en coordenadas % a metros
  function calcularDistanciaMetros(x1, y1, x2, y2) {
    // Definiciones: cada cuadricula = 20 px = 5 metros
    var GRID_CELL_PX = 20;
    var METERS_PER_CELL = 5;

    var img = OPG.q('#map-image');
    var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
    var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
    var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;
    if (!imgW || !imgH) {
      // Si no tenemos dimensiones, aproximar usando porcentaje como unidad relativa
      var percentDist = calcularDistancia(x1, y1, x2, y2);
      return percentDist; // fallback: devolver porcentaje
    }

    var x1px = (x1 / 100) * imgW;
    var y1px = (y1 / 100) * imgH;
    var x2px = (x2 / 100) * imgW;
    var y2px = (y2 / 100) * imgH;

    var dx = x2px - x1px;
    var dy = y2px - y1px;
    var distPx = Math.sqrt(dx * dx + dy * dy);

    // Considerar el zoom/escala actual: el tamaño mostrado de la cuadricula es GRID_CELL_PX * scale
    var scale = 1;
    try {
      if (OPG && OPG.mapaInteractivo && typeof OPG.mapaInteractivo.getState === 'function') {
        var s = OPG.mapaInteractivo.getState();
        if (s && typeof s.zoom === 'number') scale = s.zoom || 1;
      }
    } catch (e) { scale = 1; }

    // Convertir a metros usando cuadricula y teniendo en cuenta la escala actual
    var meters = (distPx / (GRID_CELL_PX * scale)) * METERS_PER_CELL;
    return meters;
  }

  function dibujarLineaMedicion(x1, y1, x2, y2, metersOverride) {
    var markersLayer = OPG.q('#map-markers-layer');
    if (!markersLayer) return;

    // Crear línea SVG
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.id = 'measure-line';
    svg.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 50;';

    var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    line.setAttribute('x1', x1 + '%');
    line.setAttribute('y1', y1 + '%');
    line.setAttribute('x2', x2 + '%');
    line.setAttribute('y2', y2 + '%');
    line.setAttribute('stroke-width', '2');
    line.setAttribute('stroke-dasharray', '5,5');

    var meters = (typeof metersOverride === 'number' && !isNaN(metersOverride)) ? metersOverride : calcularDistanciaMetros(x1, y1, x2, y2);

    var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
    // Si hay umbral y sobrepasa, recortar la línea y mostrar segmento verde hasta el máximo
    if (typeof meters === 'number' && Number.isFinite(max) && meters > max) {
      var img = OPG.q('#map-image');
      var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
      var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
      var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;
      if (imgW && imgH) {
        var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
        var scale = 1;
        try { if (OPG && OPG.mapaInteractivo && typeof OPG.mapaInteractivo.getState === 'function') { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1; } } catch(e) { scale = 1; }

        var x1px = (x1 / 100) * imgW; var y1px = (y1 / 100) * imgH;
        var x2px = (x2 / 100) * imgW; var y2px = (y2 / 100) * imgH;
        var dxpx = x2px - x1px; var dypx = y2px - y1px; var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx);

        var maxPx = max * pxPerMeter * scale;
        var ux = dxpx / distPx; var uy = dypx / distPx;
        var mxpx = x1px + ux * Math.min(maxPx, distPx);
        var mxpy = y1px + uy * Math.min(maxPx, distPx);
        var mx = (mxpx / imgW) * 100; var my = (mxpy / imgH) * 100;

        var lineInner = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        lineInner.setAttribute('x1', x1 + '%');
        lineInner.setAttribute('y1', y1 + '%');
        lineInner.setAttribute('x2', mx + '%');
        lineInner.setAttribute('y2', my + '%');
        lineInner.setAttribute('stroke', '#2ed573');
        lineInner.setAttribute('stroke-width', '3');

        var lineOuter = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        lineOuter.setAttribute('x1', mx + '%');
        lineOuter.setAttribute('y1', my + '%');
        lineOuter.setAttribute('x2', x2 + '%');
        lineOuter.setAttribute('y2', y2 + '%');
        lineOuter.setAttribute('stroke', '#c0392b');
        lineOuter.setAttribute('stroke-width', '2');
        lineOuter.setAttribute('stroke-dasharray', '5,5');

        svg.appendChild(lineInner);
        svg.appendChild(lineOuter);

        var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        var midX = (x1 + x2) / 2;
        var midY = (y1 + y2) / 2;
        text.setAttribute('x', midX + '%');
        text.setAttribute('y', midY + '%');
        text.setAttribute('fill', 'white');
        text.setAttribute('font-size', '12');
        text.setAttribute('font-weight', 'bold');
        text.textContent = meters.toFixed(2) + ' m (max ' + max + ' m)';

        markersLayer.appendChild(svg);
        try { if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaUtils] Line clipped — meters:', meters, 'max:', max); } catch(e) {}
        return;
      }
    }

    var colors = _getMeasureColors(meters);
    line.setAttribute('stroke', colors.stroke);

    var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    var midX = (x1 + x2) / 2;
    var midY = (y1 + y2) / 2;
    text.setAttribute('x', midX + '%');
    text.setAttribute('y', midY + '%');
    text.setAttribute('fill', colors.text);
    text.setAttribute('font-size', '14');
    text.setAttribute('font-weight', 'bold');
    text.textContent = (typeof meters === 'number') ? meters.toFixed(2) + ' m' : calcularDistancia(x1, y1, x2, y2).toFixed(2) + '%';

    svg.appendChild(line);
    svg.appendChild(text);
    markersLayer.appendChild(svg);
  }

  // Dibujar círculo de medición usando coordenadas en % (centro y punto en el borde)
  function dibujarCircleMedicion(pCenterX, pCenterY, pEdgeX, pEdgeY, meters) {
    var markersLayer = OPG.q('#map-markers-layer');
    if (!markersLayer) return;
    clearMeasureShapes();
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.id = 'measure-shape';
    svg.setAttribute('viewBox', '0 0 100 100');
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:50;';

    var dx = pEdgeX - pCenterX;
    var dy = pEdgeY - pCenterY;
    var rPct = Math.sqrt(dx * dx + dy * dy);

    var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;
    var img = OPG.q('#map-image');
    var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
    var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
    var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;

    if (Number.isFinite(meters) && Number.isFinite(max) && meters > max && imgW && imgH) {
      var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
      var scale = 1;
      try { if (OPG && OPG.mapaInteractivo && typeof OPG.mapaInteractivo.getState === 'function') { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1; } } catch(e) { scale = 1; }
      var measuredPx = meters * pxPerMeter * scale;
      var maxPx = max * pxPerMeter * scale;
      // calcular vector en px entre centro y borde para manejar anisotropía
      var dxpx = (pEdgeX - pCenterX) / 100 * imgW; var dypx = (pEdgeY - pCenterY) / 100 * imgH;
      var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx) || 1;
      var ux = dxpx / distPx; var uy = dypx / distPx;
      var measuredPct = Math.sqrt(Math.pow(ux * (measuredPx / imgW) * 100, 2) + Math.pow(uy * (measuredPx / imgH) * 100, 2));
      var maxPct = Math.sqrt(Math.pow(ux * (maxPx / imgW) * 100, 2) + Math.pow(uy * (maxPx / imgH) * 100, 2));

      var outer = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      outer.setAttribute('cx', pCenterX);
      outer.setAttribute('cy', pCenterY);
      outer.setAttribute('r', Math.min(99, measuredPct));
      outer.setAttribute('fill', 'rgba(192,57,43,0.18)');
      outer.setAttribute('stroke', '#c0392b');
      outer.setAttribute('stroke-width', '0.5');

      var inner = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
      inner.setAttribute('cx', pCenterX);
      inner.setAttribute('cy', pCenterY);
      inner.setAttribute('r', Math.min(99, maxPct));
      inner.setAttribute('fill', 'rgba(46,213,115,0.15)');
      inner.setAttribute('stroke', '#2ed573');
      inner.setAttribute('stroke-width', '0.5');

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      var tx = Math.min(99, pCenterX + measuredPct + 2);
      var ty = Math.max(2, pCenterY);
      text.setAttribute('x', tx);
      text.setAttribute('y', ty);
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '3');
      text.setAttribute('font-weight', 'bold');
      text.textContent = meters.toFixed(2) + ' m (max ' + max + ' m)';

      svg.appendChild(outer);
      svg.appendChild(inner);
      svg.appendChild(text);
      markersLayer.appendChild(svg);
      try { if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaUtils] Circle clipped — meters:', meters, 'max:', max, 'dxpx:', dxpx, 'dypx:', dypx, 'ux:', ux, 'uy:', uy, 'measuredPx:', measuredPx, 'maxPx:', maxPx, 'measuredPct:', measuredPct, 'maxPct:', maxPct); } catch(e) {}
      return;
    }

    var colors = _getMeasureColors(meters);

    // Asegurar que el SVG use unidades en píxeles según la imagen para evitar estiramientos
    if (imgW && imgH) {
      svg.setAttribute('viewBox', '0 0 ' + imgW + ' ' + imgH);
      // 'meet' preserva la relación y evita distorsiones; el overlay tiene el mismo tamaño que la imagen
      svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    }

    // Calcular radio en píxeles (para mantener la circularidad independientemente del aspect ratio)
    var dxpx = ((pEdgeX - pCenterX) / 100) * imgW;
    var dypx = ((pEdgeY - pCenterY) / 100) * imgH;
    var rPx = Math.sqrt(dxpx * dxpx + dypx * dypx);

    var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', ((pCenterX / 100) * imgW));
    circle.setAttribute('cy', ((pCenterY / 100) * imgH));
    circle.setAttribute('r', rPx);
    circle.setAttribute('fill', colors.fill);
    circle.setAttribute('stroke', colors.stroke);
    circle.setAttribute('stroke-width', '2');

    // Texto en píxeles para posición estable
    var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    var tx = Math.min(imgW - 8, ((pCenterX / 100) * imgW) + rPx + 8);
    var ty = Math.max(14, ((pCenterY / 100) * imgH));
    text.setAttribute('x', tx);
    text.setAttribute('y', ty);
    text.setAttribute('fill', colors.text);
    text.setAttribute('font-size', '14');
    text.setAttribute('font-weight', 'bold');
    text.textContent = (typeof meters === 'number') ? meters.toFixed(2) + ' m' : '';

    svg.appendChild(circle);
    svg.appendChild(text);
    markersLayer.appendChild(svg);
  }

  // Dibujar sector/cono en % (apex en % y punto de dirección)
  function dibujarSectorMedicion(apexX, apexY, dirX, dirY, radiusMeters, angleDeg) {
    var markersLayer = OPG.q('#map-markers-layer');
    if (!markersLayer) return;
    clearMeasureShapes();
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.id = 'measure-shape';
    svg.setAttribute('viewBox', '0 0 100 100');
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:50;';

    var ax = apexX, ay = apexY;
    var dx = dirX, dy = dirY;
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
    var img = OPG.q('#map-image');
    var imgRect = img && img.getBoundingClientRect ? img.getBoundingClientRect() : null;
    var imgW = (imgRect && imgRect.width) || (img && (img.naturalWidth || img.width)) || 0;
    var imgH = (imgRect && imgRect.height) || (img && (img.naturalHeight || img.height)) || 0;

    if (Number.isFinite(radiusMeters) && Number.isFinite(max) && radiusMeters > max && imgW) {
      var GRID_CELL_PX = 20; var METERS_PER_CELL = 5; var pxPerMeter = GRID_CELL_PX / METERS_PER_CELL;
      var scale = 1;
      try { if (OPG && OPG.mapaInteractivo && typeof OPG.mapaInteractivo.getState === 'function') { var s = OPG.mapaInteractivo.getState(); if (s && typeof s.zoom === 'number') scale = s.zoom || 1; } } catch(e) { scale = 1; }
      var measuredPx = radiusMeters * pxPerMeter * scale;
      var maxPx = max * pxPerMeter * scale;
      // usar vector en px entre apex y punto de dirección para anisotropía
      var dxpx = (dirX - ax) / 100 * imgW; var dypx = (dirY - ay) / 100 * (imgH || imgW); // use imgH when available
      var distPx = Math.sqrt(dxpx*dxpx + dypx*dypx) || 1;
      var ux = dxpx / distPx; var uy = dypx / distPx;
      var measuredPct = Math.sqrt(Math.pow(ux * (measuredPx / imgW) * 100, 2) + Math.pow(uy * (measuredPx / imgH) * 100, 2));
      var maxPct = Math.sqrt(Math.pow(ux * (maxPx / imgW) * 100, 2) + Math.pow(uy * (maxPx / imgH) * 100, 2));

      var x1m = ax + measuredPct * Math.cos(startAngle);
      var y1m = ay + measuredPct * Math.sin(startAngle);
      var x2m = ax + measuredPct * Math.cos(endAngle);
      var y2m = ay + measuredPct * Math.sin(endAngle);
      var dOuter = 'M ' + ax + ' ' + ay + ' L ' + x1m + ' ' + y1m + ' A ' + measuredPct + ' ' + measuredPct + ' 0 ' + largeArc + ' 1 ' + x2m + ' ' + y2m + ' Z';

      var x1i = ax + maxPct * Math.cos(startAngle);
      var y1i = ay + maxPct * Math.sin(startAngle);
      var x2i = ax + maxPct * Math.cos(endAngle);
      var y2i = ay + maxPct * Math.sin(endAngle);
      var dInner = 'M ' + ax + ' ' + ay + ' L ' + x1i + ' ' + y1i + ' A ' + maxPct + ' ' + maxPct + ' 0 ' + largeArc + ' 1 ' + x2i + ' ' + y2i + ' Z';

      var pathOuter = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      pathOuter.setAttribute('d', dOuter);
      pathOuter.setAttribute('fill', 'rgba(192,57,43,0.18)');
      pathOuter.setAttribute('stroke', '#c0392b');
      pathOuter.setAttribute('stroke-width', '0.5');

      var pathInner = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      pathInner.setAttribute('d', dInner);
      pathInner.setAttribute('fill', 'rgba(46,213,115,0.15)');
      pathInner.setAttribute('stroke', '#2ed573');
      pathInner.setAttribute('stroke-width', '0.5');

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', ax + maxPct / 2);
      text.setAttribute('y', ay);
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '3');
      text.setAttribute('font-weight', 'bold');
      text.textContent = radiusMeters.toFixed(2) + ' m (max ' + max + ' m)';

      svg.appendChild(pathOuter);
      svg.appendChild(pathInner);
      svg.appendChild(text);
      markersLayer.appendChild(svg);
      try { if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaUtils] Sector clipped — meters:', radiusMeters, 'max:', max, 'dxpx:', dxpx, 'dypx:', dypx, 'ux:', ux, 'uy:', uy, 'measuredPx:', measuredPx, 'maxPx:', maxPx, 'measuredPct:', measuredPct, 'maxPct:', maxPct); } catch(e) {}
      return;
    }

    var colors = _getMeasureColors(radiusMeters);

    var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    var d = 'M ' + ax + ' ' + ay + ' L ' + x1 + ' ' + y1 + ' A ' + radiusPct + ' ' + radiusPct + ' 0 ' + largeArc + ' 1 ' + x2 + ' ' + y2 + ' Z';
    path.setAttribute('d', d);
    path.setAttribute('fill', colors.fill);
    path.setAttribute('stroke', colors.stroke);
    path.setAttribute('stroke-width', '0.5');

    var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    text.setAttribute('x', ax + radiusPct / 2);
    text.setAttribute('y', ay);
    text.setAttribute('fill', colors.text);
    text.setAttribute('font-size', '3');
    text.setAttribute('font-weight', 'bold');
    text.textContent = (typeof radiusMeters === 'number') ? radiusMeters.toFixed(2) + ' m' : '';

    svg.appendChild(path);
    svg.appendChild(text);
    markersLayer.appendChild(svg);
  }

  // Dibujar rectángulo / cuadrado entre dos puntos en %
  function dibujarRectMedicion(p1x, p1y, p2x, p2y, diagMeters) {
    var markersLayer = OPG.q('#map-markers-layer');
    if (!markersLayer) return;
    clearMeasureShapes();
    var left = Math.min(p1x, p2x);
    var top = Math.min(p1y, p2y);
    var widthP = Math.abs(p2x - p1x);
    var heightP = Math.abs(p2y - p1y);

    var max = (OPG && typeof OPG._measurementMaxMeters !== 'undefined') ? Number(OPG._measurementMaxMeters) : null;

    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.id = 'measure-shape';
    svg.setAttribute('viewBox', '0 0 100 100');
    svg.setAttribute('preserveAspectRatio', 'none');
    svg.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:50;';

    if (Number.isFinite(diagMeters) && Number.isFinite(max) && diagMeters > max) {
      var outer = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
      outer.setAttribute('x', left);
      outer.setAttribute('y', top);
      outer.setAttribute('width', widthP);
      outer.setAttribute('height', heightP);
      outer.setAttribute('fill', 'rgba(192,57,43,0.18)');
      outer.setAttribute('stroke', '#c0392b');
      outer.setAttribute('stroke-width', '0.5');

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
      inner.setAttribute('stroke-width', '0.5');

      var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
      text.setAttribute('x', Math.min(99, left + 2));
      text.setAttribute('y', Math.min(99, top + 4));
      text.setAttribute('fill', 'white');
      text.setAttribute('font-size', '3');
      text.setAttribute('font-weight', 'bold');
      text.textContent = diagMeters.toFixed(2) + ' m (max ' + max + ' m)';

      svg.appendChild(outer);
      svg.appendChild(inner);
      svg.appendChild(text);
      markersLayer.appendChild(svg);
      try { if (OPG && OPG.flags && OPG.flags.debug) console.log('[mapaUtils] Rect clipped — meters:', diagMeters, 'max:', max); } catch(e) {}
      return;
    }

    var colors = _getMeasureColors(diagMeters);

    var rectEl = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    rectEl.setAttribute('x', left);
    rectEl.setAttribute('y', top);
    rectEl.setAttribute('width', widthP);
    rectEl.setAttribute('height', heightP);
    rectEl.setAttribute('fill', colors.fill);
    rectEl.setAttribute('stroke', colors.stroke);
    rectEl.setAttribute('stroke-width', '0.5');

    var text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    text.setAttribute('x', Math.min(99, left + 2));
    text.setAttribute('y', Math.min(99, top + 4));
    text.setAttribute('fill', colors.text);
    text.setAttribute('font-size', '3');
    text.setAttribute('font-weight', 'bold');
    text.textContent = (typeof diagMeters === 'number') ? diagMeters.toFixed(2) + ' m (diag)' : '';

    svg.appendChild(rectEl);
    svg.appendChild(text);
    markersLayer.appendChild(svg);
  }

  // ========================================
  // ÁREAS/ZONAS
  // ========================================
  
  function crearZona(data) {
    // data: { x, y, width, height, color, label }
    var markersLayer = OPG.q('#map-markers-layer');
    if (!markersLayer) return;
    
    var zona = document.createElement('div');
    zona.className = 'map-zone';
    zona.style.cssText = 
      'position: absolute;' +
      'left: ' + data.x + '%;' +
      'top: ' + data.y + '%;' +
      'width: ' + data.width + '%;' +
      'height: ' + data.height + '%;' +
      'background: ' + (data.color || 'rgba(255,0,0,0.2)') + ';' +
      'border: 2px dashed ' + (data.borderColor || '#ff0000') + ';' +
      'pointer-events: auto;' +
      'z-index: 5;' +
      'display: flex;' +
      'align-items: center;' +
      'justify-content: center;' +
      'font-weight: bold;' +
      'color: white;' +
      'text-shadow: 2px 2px 4px rgba(0,0,0,0.8);';
    
    if (data.label) {
      zona.textContent = data.label;
    }
    
    markersLayer.appendChild(zona);
    return zona;
  }

  // ========================================
  // RUTAS/CAMINOS
  // ========================================
  
  function crearRuta(puntos, opciones) {
    // puntos: [{x, y}, {x, y}, ...]
    // opciones: { color, width, animated }
    var markersLayer = OPG.q('#map-markers-layer');
    if (!markersLayer || puntos.length < 2) return;
    
    var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.className = 'map-route';
    svg.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 8;';
    
    var pathData = 'M ' + puntos[0].x + ' ' + puntos[0].y;
    for (var i = 1; i < puntos.length; i++) {
      pathData += ' L ' + puntos[i].x + ' ' + puntos[i].y;
    }
    
    var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    path.setAttribute('d', pathData);
    path.setAttribute('stroke', opciones.color || '#4CAF50');
    path.setAttribute('stroke-width', opciones.width || '3');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    
    if (opciones.animated) {
      path.setAttribute('stroke-dasharray', '10,5');
      path.style.animation = 'dash 1s linear infinite';
      
      // Añadir keyframes si no existen
      if (!document.querySelector('#route-animation-style')) {
        var style = document.createElement('style');
        style.id = 'route-animation-style';
        style.textContent = '@keyframes dash { to { stroke-dashoffset: -15; } }';
        document.head.appendChild(style);
      }
    }
    
    svg.appendChild(path);
    markersLayer.appendChild(svg);
    return svg;
  }

  // ========================================
  // MINIMAP
  // ========================================
  
  function crearMinimap() {
    var mapContent = OPG.q('#map-content');
    if (!mapContent) return;
    
    var minimap = document.createElement('div');
    minimap.id = 'map-minimap';
    minimap.style.cssText = 
      'position: absolute;' +
      'bottom: 20px;' +
      'left: 20px;' +
      'width: 150px;' +
      'height: 150px;' +
      'background: rgba(0,0,0,0.7);' +
      'border: 2px solid #667eea;' +
      'border-radius: 8px;' +
      'overflow: hidden;' +
      'z-index: 100;';
    
    var minimapImg = document.createElement('img');
    minimapImg.src = OPG.mapaInteractivo.getState().mapUrl;
    minimapImg.style.cssText = 'width: 100%; height: 100%; object-fit: contain;';
    
    minimap.appendChild(minimapImg);
    mapContent.appendChild(minimap);
    
    return minimap;
  }

  // ========================================
  // COORDENADAS DEL CURSOR
  // ========================================
  
  function mostrarCoordenadas() {
    var mapContent = OPG.q('#map-content');
    if (!mapContent) return;
    
    var coordsDiv = document.createElement('div');
    coordsDiv.id = 'map-coordinates';
    coordsDiv.style.cssText = 
      'position: absolute;' +
      'top: 60px;' +
      'right: 20px;' +
      'background: rgba(0,0,0,0.7);' +
      'color: white;' +
      'padding: 8px 12px;' +
      'border-radius: 6px;' +
      'font-size: 12px;' +
      'font-family: monospace;' +
      'z-index: 100;' +
      'pointer-events: none;';
    
    coordsDiv.textContent = 'X: 0%, Y: 0%';
    mapContent.appendChild(coordsDiv);
    
    mapContent.addEventListener('mousemove', function(e) {
      var rect = mapContent.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      coordsDiv.textContent = 'X: ' + x.toFixed(1) + '%, Y: ' + y.toFixed(1) + '%';
    });
  }

  // ========================================
  // CAPTURA DE PANTALLA
  // ========================================
  
  function capturarPantalla() {
    var mapContent = OPG.q('#map-content');
    if (!mapContent || typeof html2canvas === 'undefined') {
      console.error('html2canvas no está disponible');
      return;
    }
    
    html2canvas(mapContent).then(function(canvas) {
      var url = canvas.toDataURL('image/png');
      var a = document.createElement('a');
      a.href = url;
      a.download = 'mapa_screenshot_' + Date.now() + '.png';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
    });
  }

  // ========================================
  // EXPORTAR API
  // ========================================
  
  OPG.mapaUtils = {
    // Exportar/Importar
    exportar: exportarConfig,
    importar: importarConfig,
    descargarJSON: descargarJSON,
    generarBBCode: generarBBCode,
    
    // Medición
    medicion: {
      activar: activarMedicion,
      desactivar: desactivarMedicion,
      setMax: function(m) { if (typeof m === 'number') OPG._measurementMaxMeters = m; else OPG._measurementMaxMeters = null; if (OPG && OPG.bus) OPG.bus.emit('mapa:measurement-max', { maxMeters: OPG._measurementMaxMeters }); },
      getMax: function() { return OPG._measurementMaxMeters; }
    },

    
    // Zonas
    crearZona: crearZona,
    
    // Rutas
    crearRuta: crearRuta,
    
    // UI
    crearMinimap: crearMinimap,
    mostrarCoordenadas: mostrarCoordenadas,
    capturarPantalla: capturarPantalla
  };

  // ========================================
  // ATAJOS DE TECLADO AVANZADOS
  // ========================================
  
  OPG.onReady(function() {
    document.addEventListener('keydown', function(e) {
      // Ctrl+E: Exportar
      if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        var bbcode = generarBBCode();
        console.log('BBCode generado:', bbcode);
        if (typeof prompt !== 'undefined') {
          prompt('BBCode del mapa:', bbcode);
        }
      }
      
      // Ctrl+M: Medición (case-insensitive)
      if (e.ctrlKey && e.key && e.key.toLowerCase() === 'm') {
        e.preventDefault();
        console.log('Tecla Ctrl+M detectada — alternando medición (measureMode =', measureMode, ')');
        if (measureMode) desactivarMedicion();
        else activarMedicion();
      }
      
      // Ctrl+D: Descargar JSON
      if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        descargarJSON();
      }
      
      // Ctrl+Shift+C: Mostrar coordenadas
      if (e.ctrlKey && e.shiftKey && e.key === 'C') {
        e.preventDefault();
        var coords = OPG.q('#map-coordinates');
        if (coords) coords.parentNode.removeChild(coords);
        else mostrarCoordenadas();
      }
    });
  });

})(OPG, window);
