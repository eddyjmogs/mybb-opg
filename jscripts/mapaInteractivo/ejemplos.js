/* Ejemplo de uso del Sistema de Mapa Interactivo OPG */

// ========================================
// 1. INICIALIZACIÓN BÁSICA
// ========================================

OPG.onReady(function() {
  // El mapa se inicializa automáticamente con el TID del thread
  OPG.mapaInteractivo.init(7689);
});


// ========================================
// 2. CARGAR MAPA MANUALMENTE
// ========================================

OPG.mapaInteractivo.load('/images/op/islas/201/mapa_isla_dawn.png');


// ========================================
// 3. AGREGAR MARCADORES DE PERSONAJES
// ========================================

// Marcador simple
OPG.mapaInteractivo.markers.add({
  x: 30,              // Posición X en porcentaje (0-100)
  y: 40,              // Posición Y en porcentaje (0-100)
  label: 'Luffy',
  color: '#ff6b6b',
  avatar: '/avatars/luffy.png'
});

// Usando el helper de personajes
OPG.mapaPersonajes.addPlayer({
  uid: 850,
  username: 'Monkey D. Luffy',
  avatar: '/avatars/luffy.png',
  x: 25,
  y: 30,
  fichaUrl: '/ficha.php?uid=850'
});


// ========================================
// 4. AGREGAR UBICACIONES/PUNTOS DE INTERÉS
// ========================================

OPG.mapaPersonajes.addLocation({
  name: 'Puerto de Foosha',
  x: 20,
  y: 25,
  description: 'Pueblo natal de Luffy, en East Blue'
});

OPG.mapaPersonajes.addLocation({
  name: 'Bar de Makino',
  x: 22,
  y: 26,
  description: 'Donde Luffy conoció a Shanks'
});


// ========================================
// 5. CONTROL DE ZOOM
// ========================================

// Zoom específico (50% a 300%)
OPG.mapaInteractivo.zoom.set(1.5);  // 150%

// Incrementar/decrementar
OPG.mapaInteractivo.zoom.in();      // +20%
OPG.mapaInteractivo.zoom.out();     // -20%

// Resetear
OPG.mapaInteractivo.zoom.reset();   // Volver a 100%


// ========================================
// 6. EVENTOS Y REACCIONES
// ========================================

// Cuando se carga el mapa
OPG.bus.on('mapa:loaded', function(data) {
  console.log('✅ Mapa cargado:', data.url);
  
  // Agregar todos los personajes del thread
  var players = [
    { uid: 850, username: 'Luffy', avatar: '/avatar1.png', x: 25, y: 30 },
    { uid: 123, username: 'Zoro', avatar: '/avatar2.png', x: 30, y: 35 },
    { uid: 456, username: 'Nami', avatar: '/avatar3.png', x: 28, y: 33 }
  ];
  
  players.forEach(function(p) {
    OPG.mapaPersonajes.addPlayer(p);
  });
});

// Cuando se hace click en un marcador
OPG.bus.on('mapa:marker-click', function(data) {
  var marker = data.marker;
  console.log('👆 Click en:', marker.label);
  
  // Mostrar información personalizada
  if (marker.data && marker.data.fichaUrl) {
    window.open(marker.data.fichaUrl, '_blank');
  }
});

// Cuando cambia el zoom
OPG.bus.on('mapa:zoom', function(data) {
  console.log('🔍 Zoom:', Math.round(data.zoom * 100) + '%');
});


// ========================================
// 7. GESTIÓN DE MARCADORES
// ========================================

// Guardar referencia a un marcador
var luffy = OPG.mapaInteractivo.markers.add({
  x: 30, y: 40,
  label: 'Luffy',
  color: '#ff6b6b'
});

// Remover marcador específico
OPG.mapaInteractivo.markers.remove(luffy.id);

// Obtener todos los marcadores
var allMarkers = OPG.mapaInteractivo.markers.getAll();
console.log('Total de marcadores:', allMarkers.length);

// Limpiar todos los marcadores
OPG.mapaInteractivo.markers.clear();


// ========================================
// 8. OBTENER ESTADO DEL MAPA
// ========================================

var estado = OPG.mapaInteractivo.getState();
console.log('Estado del mapa:', estado);
// {
//   tid: 7689,
//   mapUrl: '/images/mapa.png',
//   isVisible: true,
//   zoom: 1.5,
//   pan: { x: 0, y: 0 },
//   markersCount: 3
// }


// ========================================
// 9. EJEMPLO COMPLETO: MAPA DE ROL
// ========================================

OPG.onReady(function() {
  // Inicializar
  OPG.mapaInteractivo.init(7689);
  
  // Esperar a que cargue
  OPG.bus.on('mapa:loaded', function() {
    
    // Agregar jugadores participantes
    OPG.mapaPersonajes.addPlayer({
      uid: 850,
      username: 'Luffy',
      avatar: '/avatars/luffy.png',
      x: 25,
      y: 30,
      fichaUrl: '/ficha.php?uid=850'
    });
    
    OPG.mapaPersonajes.addPlayer({
      uid: 123,
      username: 'Zoro',
      avatar: '/avatars/zoro.png',
      x: 30,
      y: 35,
      fichaUrl: '/ficha.php?uid=123'
    });
    
    // Agregar ubicaciones importantes
    OPG.mapaPersonajes.addLocation({
      name: 'Baratie',
      x: 50,
      y: 50,
      description: 'Restaurante flotante donde trabaja Sanji'
    });
    
    OPG.mapaPersonajes.addLocation({
      name: 'Barco de Mihawk',
      x: 70,
      y: 40,
      description: 'Ubicación del Shichibukai'
    });
    
    // Agregar enemigo/NPC
    OPG.mapaInteractivo.markers.add({
      x: 60,
      y: 45,
      label: 'Don Krieg',
      color: '#e74c3c',
      avatar: '/npc/krieg.png'
    });
  });
  
  // Manejar clicks en marcadores
  OPG.bus.on('mapa:marker-click', function(data) {
    // Abrir ficha en nueva pestaña si es un jugador
    if (data.marker.data && data.marker.data.fichaUrl) {
      window.open(data.marker.data.fichaUrl, '_blank');
    }
  });
});


// ========================================
// 10. CARGAR DESDE DATOS DEL THREAD
// ========================================

// Si guardas posiciones en el post en formato JSON
// Ejemplo: [mapa_data]{"players":[{"uid":850,"x":30,"y":40}]}[/mapa_data]

function cargarDesdeBBCode() {
  var posts = document.querySelectorAll('.post_body');
  
  for (var i = 0; i < posts.length; i++) {
    var html = posts[i].innerHTML;
    var match = html.match(/\[mapa_data\](.+?)\[\/mapa_data\]/);
    
    if (match) {
      var data = OPG.utils.safeJSON.parse(match[1]);
      
      if (data && data.players) {
        data.players.forEach(function(p) {
          OPG.mapaInteractivo.markers.add({
            x: p.x,
            y: p.y,
            label: p.username,
            color: p.color || '#ff6b6b',
            avatar: p.avatar
          });
        });
      }
      
      break;
    }
  }
}

// Llamar después de cargar el mapa
OPG.bus.on('mapa:loaded', cargarDesdeBBCode);


// ========================================
// 11. GUARDAR ESTADO DEL MAPA
// ========================================

function guardarConfiguracionMapa() {
  var config = {
    url: OPG.mapaInteractivo.getState().mapUrl,
    markers: OPG.mapaInteractivo.markers.getAll().map(function(m) {
      return {
        x: m.x,
        y: m.y,
        label: m.label,
        color: m.color,
        avatar: m.avatar
      };
    })
  };
  
  // Guardar en localStorage
  OPG.storage.set('mapa_config_thread_' + {$tid}, config);
  
  console.log('✅ Configuración guardada');
}

function cargarConfiguracionMapa() {
  var config = OPG.storage.get('mapa_config_thread_' + {$tid});
  
  if (config) {
    // Cargar mapa
    OPG.mapaInteractivo.load(config.url);
    
    // Esperar a que cargue
    OPG.bus.on('mapa:loaded', function() {
      // Cargar marcadores
      config.markers.forEach(function(m) {
        OPG.mapaInteractivo.markers.add(m);
      });
      
      console.log('✅ Configuración cargada');
    });
  }
}


// ========================================
// 12. MODO STAFF: CLICK PARA AGREGAR
// ========================================

if (window.IS_STAFF) {
  var modoEdicion = false;
  
  // Activar modo edición
  document.addEventListener('keydown', function(e) {
    if (e.key === 'e' && e.ctrlKey) {
      e.preventDefault();
      modoEdicion = !modoEdicion;
      console.log('Modo edición:', modoEdicion ? 'ON' : 'OFF');
    }
  });
  
  // Click en el mapa para agregar marcador
  var mapContent = OPG.q('#map-content');
  if (mapContent) {
    mapContent.addEventListener('click', function(e) {
      if (!modoEdicion) return;
      
      var rect = mapContent.getBoundingClientRect();
      var x = ((e.clientX - rect.left) / rect.width) * 100;
      var y = ((e.clientY - rect.top) / rect.height) * 100;
      
      var label = prompt('Nombre del marcador:');
      if (label) {
        OPG.mapaInteractivo.markers.add({
          x: x,
          y: y,
          label: label,
          color: '#667eea'
        });
        
        console.log('Marcador agregado en:', x.toFixed(2) + '%, ' + y.toFixed(2) + '%');
      }
    });
  }
}
