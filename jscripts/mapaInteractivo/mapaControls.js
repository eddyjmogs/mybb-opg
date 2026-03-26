 /* Módulo: Controles UI del Mapa Interactivo */
(function(OPG, w) {
  'use strict';

  var controlsHtml = 
    '<div id="map-controls" style="position: absolute; bottom: 20px; right: 20px; z-index: 100; display: flex; flex-direction: column; gap: 8px;">' +
      '<button class="map-control-btn" data-action="zoom-in" title="Acercar (+)">' +
        '<i class="fa fa-plus"></i>' +
      '</button>' +
      '<button class="map-control-btn" data-action="zoom-out" title="Alejar (-)">' +
        '<i class="fa fa-minus"></i>' +
      '</button>' +
      '<button class="map-control-btn" data-action="reset" title="Resetear (0)">' +
        '<i class="fa fa-crosshairs"></i>' +
      '</button>' +
      '<div class="measure-wrap" style="position:relative; display:flex; align-items:center; gap:8px;">' +
        // Desplegable de distancia máxima (izquierda), oculto por defecto
        '<select id="measure-max-select" class="measure-max-select" title="Máx medición (m)" style="display:none; min-width:84px; height:36px; padding:4px; border-radius:6px; border:2px solid #667eea; background: #fff; color:#333;">' +
          '<option value="">Máx</option>' +
          '<option value="5">5 m</option>' +
          '<option value="10">10 m</option>' +
          '<option value="20">20 m</option>' +
          '<option value="50">50 m</option>' +
          '<option value="100">100 m</option>' +
          '<option value="200">200 m</option>' +
          '<option value="500">500 m</option>' +
        '</select>' +
        '<button class="map-control-btn" data-action="measure" title="Medición (Ctrl+M)">' +
          '<span style="font-size:18px;line-height:1;">📏</span>' +
        '</button>' +
        // Submenú de tipos de medición
        '<div class="measure-menu" style="position:absolute; bottom:0px; right:40px; padding:8px; border-radius:8px; display:none; flex-direction:column; gap:6px; z-index:110;">' +
          '<button class="map-control-btn measure-type" data-type="line" title="Línea">—</button>' +
          '<button class="map-control-btn measure-type" data-type="circle" title="Círculo">◯</button>' +
          '<button class="map-control-btn measure-type" data-type="cone" title="Cono">🔺</button>' +
          '<button class="map-control-btn measure-type" data-type="square" title="Cuadrado">▢</button>' +
          '<button class="map-control-btn measure-exit" data-action="measure-exit" title="Salir">✖️</button>' +
        '</div>' +
      '</div>' +
      '<button class="map-control-btn" data-action="fullscreen" title="Pantalla completa">' +
        '<i class="fa fa-expand"></i>' +
      '</button>' +
    '</div>';

  var styles = 
    '<style>' +
      '.map-control-btn {' +
        'width: 40px;' +
        'height: 40px;' +
        'background: rgba(255, 255, 255, 0.95);' +
        'border: 2px solid #667eea;' +
        'border-radius: 8px;' +
        'cursor: pointer;' +
        'display: flex;' +
        'align-items: center;' +
        'justify-content: center;' +
        'font-size: 16px;' +
        'color: #667eea;' +
        'transition: all 0.2s;' +
        'box-shadow: 0 2px 8px rgba(0,0,0,0.2);' +
      '}' +
      '.map-control-btn:hover {' +
        'background: #667eea;' +
        'color: white;' +
        'transform: scale(1.1);' +
      '}' +
      '.map-control-btn.active {' +
        'background: #ff6b6b;' +
        'color: white;' +
        'border-color: #ff6b6b;' +
      '}' +
      '.map-control-btn:active {' +
        'transform: scale(0.95);' +
      '}' +
      '#map-zoom-indicator {' +
        'position: absolute;' +
        'top: 20px;' +
        'right: 20px;' +
        'background: rgba(0,0,0,0.7);' +
        'color: white;' +
        'padding: 8px 12px;' +
        'border-radius: 6px;' +
        'font-size: 14px;' +
        'font-weight: bold;' +
        'z-index: 100;' +
        'pointer-events: none;' +
      '}' +
      '.map-fullscreen {' +
        'position: fixed !important;' +
        'top: 0 !important;' +
        'left: 0 !important;' +
        'width: 100vw !important;' +
        'height: 100vh !important;' +
        'margin: 0 !important;' +
        'z-index: 9999 !important;' +
        'border-radius: 0 !important;' +
      '}' +
      '.map-fullscreen #map-content {' +
        'width: 100% !important;' +
        'height: 100% !important;' +
      '}' +
      '.measure-wrap { position: relative; gap:8px; }' +
      '.measure-max-select { display: none; appearance: none; -webkit-appearance: none; cursor: pointer; min-width:84px; height:36px; padding:4px; border-radius:6px; border:2px solid #667eea; background: #fff; color:#333; }' +
      '.measure-max-select.show { display: inline-block !important; }' +
      '.measure-menu { display: flex; }' +
      '.measure-menu .measure-type { width: 38px; height: 36px; padding: 0; font-size: 14px; border-radius: 6px; }' +
      '.measure-menu .measure-type.active { background: #ff6b6b; color: white; border-color: #ff6b6b; }' +
      '.measure-menu .measure-exit { width: 38px; height: 36px; padding: 0; font-size: 14px; border-radius: 6px; align-self: flex-end; background: #fff; color: #667eea; border: 2px solid #667eea; }' +
      '.measure-menu .measure-exit:hover { background: #c0392b; color: white; border-color: #c0392b; }' +
      '.measure-menu.show { display: flex !important; }' +
    '</style>';

  var isFullscreen = false;

  function init() {
    // Esperar a que el mapa esté inicializado
    OPG.bus.on('mapa:initialized', function() {
      addControls();
      addZoomIndicator();
      setupControlEvents();

      // Actualizar apariencia del botón de medición cuando cambia el estado
      if (OPG && OPG.bus) {
        OPG.bus.on('mapa:measurement', function(data) {
          var btn = OPG.q('[data-action="measure"]');
          if (!btn) return;
          if (data && data.active) btn.classList.add('active');
          else btn.classList.remove('active');

          // Si viene el tipo, marcar el botón correspondiente en el submenú
          if (data && data.type) {
            var sel = OPG.q('.measure-menu .measure-type[data-type="' + data.type + '"]');
            var all = OPG.qa('.measure-menu .measure-type');
            for (var i = 0; i < all.length; i++) all[i].classList.toggle('active', all[i] === sel);
          }
          // Mostrar / ocultar select de distancia máxima
          var maxSel = OPG.q('#measure-max-select');
          if (maxSel) maxSel.style.display = (data && data.active) ? 'inline-block' : 'none';
        });
        // Estado inicial
        if (OPG && OPG._measurementActive) {
          var btn = OPG.q('[data-action="measure"]');
          if (btn) btn.classList.add('active');
          var maxSel = OPG.q('#measure-max-select');
          if (maxSel) maxSel.style.display = 'inline-block';
        }

        // Redundancia: Ctrl+M también aquí para asegurar que funciona aunque mapaUtils no tenga listener
        document.addEventListener('keydown', function(e) {
          if (e.ctrlKey && e.key && e.key.toLowerCase() === 'm') {
            e.preventDefault();
            console.log('Atajo Ctrl+M detectado en controls — alternando medición');
            handleAction('measure');
          }
        });
      }
    });
  }

  function addControls() {
    var mapContent = OPG.q('#map-content');
    if (!mapContent) return;

    // Añadir estilos
    var styleEl = document.createElement('div');
    styleEl.innerHTML = styles;
    document.head.appendChild(styleEl.firstChild);

    // Añadir controles
    var controlsEl = document.createElement('div');
    controlsEl.innerHTML = controlsHtml;
    mapContent.appendChild(controlsEl.firstChild);
  }

  function addZoomIndicator() {
    var mapContent = OPG.q('#map-content');
    if (!mapContent) return;

    var indicator = document.createElement('div');
    indicator.id = 'map-zoom-indicator';
    indicator.textContent = '100%';
    mapContent.appendChild(indicator);

    // Actualizar cuando cambia el zoom
    OPG.bus.on('mapa:zoom', function(data) {
      indicator.textContent = Math.round(data.zoom * 100) + '%';
    });

    OPG.bus.on('mapa:reset', function() {
      indicator.textContent = '100%';
    });
  }

  function setupControlEvents() {
    var controls = OPG.qa('.map-control-btn');
    
    for (var i = 0; i < controls.length; i++) {
      (function(btn) {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          var action = btn.getAttribute('data-action');
          // 'measure' se maneja por su propio handler (menú)
          if (action === 'measure') return;
          handleAction(action);
        });
      })(controls[i]);
    }

    var measureBtn = OPG.q('[data-action="measure"]');
    if (measureBtn) {
      measureBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        handleAction('measure');
      });
    }

    var maxSelect = OPG.q('#measure-max-select');
    if (maxSelect) {
      // Inicializar desde estado si ya existe
      if (typeof OPG._measurementMaxMeters === 'number') {
        maxSelect.value = String(OPG._measurementMaxMeters);
        maxSelect.style.display = 'inline-block';
      }
      maxSelect.addEventListener('change', function(ev) {
        var val = parseFloat(this.value);
        if (isNaN(val)) {
          OPG._measurementMaxMeters = null;
        } else {
          OPG._measurementMaxMeters = val;
        }
        if (OPG && OPG.bus) OPG.bus.emit('mapa:measurement-max', { maxMeters: OPG._measurementMaxMeters });
      });
    }

    // Manejo del submenú de medición
    var menuBtns = OPG.qa('.measure-menu .measure-type');
    for (var j = 0; j < menuBtns.length; j++) {
      (function(b) {
        b.addEventListener('click', function(ev) {
          ev.stopPropagation();
          var type = b.getAttribute('data-type');
          selectMeasureType(type);
        });
      })(menuBtns[j]);
    }

    var exitBtn = OPG.q('.measure-menu .measure-exit');
    if (exitBtn) {
      exitBtn.addEventListener('click', function(ev) {
        ev.stopPropagation();
        // Preferir la API de mapaUtils si está disponible
        if (OPG && OPG.mapaUtils && OPG.mapaUtils.medicion && typeof OPG.mapaUtils.medicion.desactivar === 'function') {
          OPG.mapaUtils.medicion.desactivar();
        } else {
          OPG._measurementActive = false;
          if (OPG && OPG.bus) OPG.bus.emit('mapa:measurement', { active: false });
        }
        var measureBtn = OPG.q('[data-action="measure"]');
        if (measureBtn) measureBtn.classList.remove('active');
        closeMeasureMenu();
      });
    }
  }

  function openMeasureMenu() {
    var menu = OPG.q('.measure-menu');
    if (!menu) return;
    menu.classList.add('show');
    menu.style.display = 'flex';
    setTimeout(function() { document.addEventListener('click', outsideMeasureMenuHandler); }, 0);
  }

  function closeMeasureMenu() {
    var menu = OPG.q('.measure-menu');
    if (!menu) return;
    menu.classList.remove('show');
    menu.style.display = 'none';
    document.removeEventListener('click', outsideMeasureMenuHandler);
  }

  function toggleMeasureMenu() {
    var menu = OPG.q('.measure-menu');
    if (!menu) return;
    if (menu.classList.contains('show')) closeMeasureMenu();
    else openMeasureMenu();
  }

  function outsideMeasureMenuHandler(e) {
    var wrap = OPG.q('.measure-wrap');
    if (wrap && !wrap.contains(e.target)) closeMeasureMenu();
  }

  function selectMeasureType(type) {
    var btns = OPG.qa('.measure-menu .measure-type');
    for (var i=0;i<btns.length;i++){btns[i].classList.toggle('active', btns[i].getAttribute('data-type')===type);}    
    var measureBtn = OPG.q('[data-action="measure"]');
    if (measureBtn) measureBtn.classList.add('active');
    if (OPG && OPG.mapaUtils && OPG.mapaUtils.medicion && typeof OPG.mapaUtils.medicion.activar === 'function') {
      OPG.mapaUtils.medicion.activar(type);
    } else if (OPG && OPG.bus) {
      OPG.bus.emit('mapa:measurement', { active: true, type: type });
    }
    closeMeasureMenu();
  }



  function handleAction(action) {
    switch(action) {
      case 'zoom-in':
        OPG.mapaInteractivo.zoom.in();
        break;
      case 'zoom-out':
        OPG.mapaInteractivo.zoom.out();
        break;
      case 'reset':
        OPG.mapaInteractivo.zoom.reset();
        break;
      case 'measure':
        // Si ya está activo, desactivar; si no, abrir el menú de tipos de medición
        if (OPG && OPG._measurementActive) {
          if (OPG && OPG.mapaUtils && OPG.mapaUtils.medicion && typeof OPG.mapaUtils.medicion.desactivar === 'function') {
            OPG.mapaUtils.medicion.desactivar();
          } else {
            OPG._measurementActive = false;
            if (OPG && OPG.bus) OPG.bus.emit('mapa:measurement', { active: false });
          }
        } else {
          // Abrir submenú para seleccionar tipo
          toggleMeasureMenu();
        }
        break;
      case 'fullscreen':
        toggleFullscreen();
        break;
    }
  }

  function toggleFullscreen() {
    var container = OPG.q('#interactive-map-container');
    if (!container) return;

    var btn = OPG.q('[data-action="fullscreen"]');
    
    if (!isFullscreen) {
      container.classList.add('map-fullscreen');
      if (btn) btn.innerHTML = '<i class="fa fa-compress"></i>';
      isFullscreen = true;
      
      // Escape para salir
      document.addEventListener('keydown', escapeHandler);
    } else {
      container.classList.remove('map-fullscreen');
      if (btn) btn.innerHTML = '<i class="fa fa-expand"></i>';
      isFullscreen = false;
      document.removeEventListener('keydown', escapeHandler);
    }
  }

  function escapeHandler(e) {
    if (e.key === 'Escape' && isFullscreen) {
      toggleFullscreen();
    }
  }

  // Auto-inicializar cuando OPG esté listo
  OPG.onReady(init);

  // Exportar
  OPG.mapaControls = {
    toggleFullscreen: toggleFullscreen
  };

})(OPG, window);
