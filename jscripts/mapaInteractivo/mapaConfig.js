/* Configuración del Sistema de Mapa Interactivo */
(function(OPG, w) {
  'use strict';

  // ========================================
  // CONFIGURACIÓN GENERAL
  // ========================================
  
  var CONFIG = {
    // Zoom
    zoom: {
      min: 0.5,          // Zoom mínimo (50%)
      max: 3,            // Zoom máximo (300%)
      step: 0.2,         // Incremento por acción (20%)
      wheelSensitivity: 1 // Sensibilidad de la rueda del mouse
    },
    
    // Marcadores
    markers: {
      defaultSize: 40,   // Tamaño base en píxeles
      defaultColor: '#ff6b6b',
      avatarBorder: 3,   // Grosor del borde del avatar
      labelOffset: 25,   // Distancia del label al marcador
      
      // Plantillas por tipo
      types: {
        player: {
          color: '#ff6b6b',
          icon: '👤',
          size: 50,
          zIndex: 20
        },
        npc: {
          color: '#51cf66',
          icon: '🎭',
          size: 40,
          zIndex: 15
        },
        enemy: {
          color: '#e74c3c',
          icon: '⚔️',
          size: 45,
          zIndex: 18
        },
        location: {
          color: '#ffd43b',
          icon: '📍',
          size: 35,
          zIndex: 10
        },
        treasure: {
          color: '#f39c12',
          icon: '💎',
          size: 38,
          zIndex: 12
        },
        danger: {
          color: '#c0392b',
          icon: '⚠️',
          size: 42,
          zIndex: 16
        }
      }
    },
    
    // Animaciones
    animations: {
      fadeIn: 400,       // Duración fade in (ms)
      slideToggle: 300,  // Duración slide toggle (ms)
      markerPulse: true  // Pulse en marcadores
    },
    
    // Controles UI
    controls: {
      showZoomIndicator: true,
      showMinimap: false,
      showCoordinates: false,
      enableFullscreen: true,
      buttonStyle: 'modern' // 'modern' o 'classic'
    },
    
    // Storage
    storage: {
      saveMapUrl: true,
      saveMarkers: false,
      saveZoomLevel: false,
      savePanPosition: false,
      prefix: 'opg::mapa::'
    },
    
    // Búsqueda automática de mapas
    autoSearch: {
      enabled: true,
      delay: 500,        // Delay antes de buscar (ms)
      selectors: [
        '.trow1.scaleimages',
        '.post_body',
        '.thread_review_row'
      ],
      patterns: {
        bbcode: /\[mapa[^\]]*\]([^\[]+)\[\/mapa\]/i,
        altText: 'mapa',
        minWidth: 800
      }
    },
    
    // Debug
    debug: false
  };

  // ========================================
  // TEMAS DE COLOR
  // ========================================
  
  var THEMES = {
    default: {
      headerBg: 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
      containerBg: '#2a2a2a',
      containerBorder: '#444',
      mapBg: '#1a1a1a',
      controlBg: 'rgba(255, 255, 255, 0.95)',
      controlBorder: '#667eea',
      controlColor: '#667eea'
    },
    
    ocean: {
      headerBg: 'linear-gradient(135deg, #1e3c72 0%, #2a5298 100%)',
      containerBg: '#0a1929',
      containerBorder: '#1e4976',
      mapBg: '#0d1b2a',
      controlBg: 'rgba(255, 255, 255, 0.95)',
      controlBorder: '#2a5298',
      controlColor: '#2a5298'
    },
    
    fire: {
      headerBg: 'linear-gradient(135deg, #ff7019 0%, #d15306 100%)',
      containerBg: '#2a1a1a',
      containerBorder: '#ff7019',
      mapBg: '#1a0f0f',
      controlBg: 'rgba(255, 255, 255, 0.95)',
      controlBorder: '#ff7019',
      controlColor: '#ff7019'
    },
    
    forest: {
      headerBg: 'linear-gradient(135deg, #56ab2f 0%, #a8e063 100%)',
      containerBg: '#1a2a1a',
      containerBorder: '#56ab2f',
      mapBg: '#0f1a0f',
      controlBg: 'rgba(255, 255, 255, 0.95)',
      controlBorder: '#56ab2f',
      controlColor: '#56ab2f'
    }
  };

  // ========================================
  // TEXTOS PERSONALIZABLES
  // ========================================
  
  var STRINGS = {
    es: {
      mapTitle: 'Mapa del Escenario',
      toggleShow: 'Mostrar',
      toggleHide: 'Ocultar',
      zoomIn: 'Acercar',
      zoomOut: 'Alejar',
      reset: 'Resetear',
      fullscreen: 'Pantalla completa',
      exitFullscreen: 'Salir de pantalla completa',
      noMapFound: 'No se encontró mapa en el thread',
      mapLoaded: 'Mapa cargado correctamente',
      markerAdded: 'Marcador agregado',
      markerRemoved: 'Marcador eliminado',
      coordinates: 'Coordenadas',
      editMode: 'Modo edición',
      saveConfig: 'Guardar configuración',
      loadConfig: 'Cargar configuración'
    },
    
    en: {
      mapTitle: 'Scenario Map',
      toggleShow: 'Show',
      toggleHide: 'Hide',
      zoomIn: 'Zoom In',
      zoomOut: 'Zoom Out',
      reset: 'Reset',
      fullscreen: 'Fullscreen',
      exitFullscreen: 'Exit Fullscreen',
      noMapFound: 'No map found in thread',
      mapLoaded: 'Map loaded successfully',
      markerAdded: 'Marker added',
      markerRemoved: 'Marker removed',
      coordinates: 'Coordinates',
      editMode: 'Edit Mode',
      saveConfig: 'Save Config',
      loadConfig: 'Load Config'
    }
  };

  // ========================================
  // APLICAR CONFIGURACIÓN
  // ========================================
  
  function applyConfig(customConfig) {
    if (!customConfig) return CONFIG;
    
    // Merge profundo
    for (var key in customConfig) {
      if (typeof customConfig[key] === 'object' && !Array.isArray(customConfig[key])) {
        CONFIG[key] = CONFIG[key] || {};
        for (var subkey in customConfig[key]) {
          CONFIG[key][subkey] = customConfig[key][subkey];
        }
      } else {
        CONFIG[key] = customConfig[key];
      }
    }
    
    return CONFIG;
  }

  function applyTheme(themeName) {
    var theme = THEMES[themeName] || THEMES.default;
    var container = OPG.q('#interactive-map-container');
    var header = OPG.q('#map-header');
    var mapContent = OPG.q('#map-content');
    
    if (container) {
      container.style.background = theme.containerBg;
      container.style.borderColor = theme.containerBorder;
    }
    
    if (header) {
      header.style.background = theme.headerBg;
    }
    
    if (mapContent) {
      mapContent.style.background = theme.mapBg;
    }
    
    // Actualizar controles
    var controls = OPG.qa('.map-control-btn');
    for (var i = 0; i < controls.length; i++) {
      controls[i].style.borderColor = theme.controlBorder;
      controls[i].style.color = theme.controlColor;
    }
  }

  function setLanguage(lang) {
    return STRINGS[lang] || STRINGS.es;
  }

  // ========================================
  // EXPORTAR
  // ========================================
  
  OPG.mapaConfig = {
    get: function() { return CONFIG; },
    set: applyConfig,
    themes: THEMES,
    applyTheme: applyTheme,
    strings: STRINGS,
    setLanguage: setLanguage,
    
    // Helpers para modificar configuración específica
    setZoomLimits: function(min, max, step) {
      CONFIG.zoom.min = min;
      CONFIG.zoom.max = max;
      if (step) CONFIG.zoom.step = step;
    },
    
    setMarkerDefaults: function(size, color) {
      CONFIG.markers.defaultSize = size;
      CONFIG.markers.defaultColor = color;
      CONFIG.markers.types.player.size = size;
      CONFIG.markers.types.npc.size = size;
      CONFIG.markers.types.enemy.size = size;
      CONFIG.markers.types.location.size = size;
      CONFIG.markers.types.treasure.size = size;
      CONFIG.markers.types.danger.size = size;
    },
    
    toggleDebug: function() {
      CONFIG.debug = !CONFIG.debug;
      OPG.flags.debug = CONFIG.debug;
      return CONFIG.debug;
    },
    
    enableAutoSave: function(enabled) {
      CONFIG.storage.saveMapUrl = enabled;
      CONFIG.storage.saveMarkers = enabled;
      CONFIG.storage.saveZoomLevel = enabled;
      CONFIG.storage.savePanPosition = enabled;
    }
  };

  // Aplicar configuración inicial
  OPG.flags.debug = CONFIG.debug;

})(OPG, window);
