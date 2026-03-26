/* Módulo: Gestión de Marcadores de Personajes */
(function(OPG, w) {
  'use strict';

  var markerTemplates = {
    player: function(data) {
      return {
        color: '#ff6b6b',
        icon: '👤',
        size: 50
      };
    },
    npc: function(data) {
      return {
        color: '#51cf66',
        icon: '🎭',
        size: 40
      };
    },
    enemy: function(data) {
      return {
        color: '#ff6b6b',
        icon: '⚔️',
        size: 45
      };
    },
    location: function(data) {
      return {
        color: '#ffd43b',
        icon: '📍',
        size: 35
      };
    }
  };

  function addPlayerMarker(playerData) {
    // playerData: { uid, username, avatar, x, y, fichaUrl }
    var template = markerTemplates.player(playerData);
    
    return OPG.mapaInteractivo.markers.add({
      id: 'player_' + playerData.uid,
      x: playerData.x,
      y: playerData.y,
      label: playerData.username,
      color: template.color,
      avatar: playerData.avatar,
      data: playerData
    });
  }

  function addLocationMarker(locationData) {
    // locationData: { name, x, y, description }
    var template = markerTemplates.location(locationData);
    
    return OPG.mapaInteractivo.markers.add({
      id: 'location_' + OPG.utils.uid('loc'),
      x: locationData.x,
      y: locationData.y,
      label: locationData.name,
      color: template.color,
      data: locationData
    });
  }

  function loadPlayersFromThread() {
    // Buscar información de jugadores en los posts del thread
    // Este es un ejemplo - adaptar según estructura real
    var posts = document.querySelectorAll('.post_author, .trow1');
    var players = [];

    for (var i = 0; i < posts.length; i++) {
      // Extraer datos del post
      // Esto es solo un ejemplo, ajustar según HTML real
      var usernameEl = posts[i].querySelector('.username, .largetext');
      var avatarEl = posts[i].querySelector('.avatar img');
      
      if (usernameEl && avatarEl) {
        players.push({
          uid: i + 1,
          username: usernameEl.textContent.trim(),
          avatar: avatarEl.getAttribute('src'),
          x: 20 + (i * 15), // Distribución automática
          y: 20 + (i * 10)
        });
      }
    }

    return players;
  }

  function showMarkerInfo(marker) {
    // Crear modal con información del marcador
    var info = marker.data || {};
    var html = 
      '<div style="text-align: center;">' +
        (marker.avatar ? '<img src="' + marker.avatar + '" style="width: 80px; height: 80px; border-radius: 50%; margin-bottom: 10px;" />' : '') +
        '<h3 style="margin: 5px 0; color: ' + marker.color + ';">' + OPG.utils.escapeHtml(marker.label) + '</h3>' +
        (info.description ? '<p>' + OPG.utils.escapeHtml(info.description) + '</p>' : '') +
        (info.fichaUrl ? '<a href="' + info.fichaUrl + '" target="_blank" style="color: #667eea;">Ver Ficha</a>' : '') +
      '</div>';

    // Usar sistema de modales de MyBB si existe
    if (typeof MyBB !== 'undefined' && MyBB.modal) {
      MyBB.modal(html, marker.label);
    } else {
      // Fallback: alert simple
      alert(marker.label);
    }
  }

  // Eventos
  OPG.bus.on('mapa:marker-click', function(data) {
    showMarkerInfo(data.marker);
  });

  // Auto-cargar jugadores cuando se carga el mapa
  OPG.bus.on('mapa:loaded', function() {
    // Descomentar para auto-cargar jugadores
    // var players = loadPlayersFromThread();
    // players.forEach(function(p) { addPlayerMarker(p); });
  });

  // API Pública
  OPG.mapaPersonajes = {
    addPlayer: addPlayerMarker,
    addLocation: addLocationMarker,
    loadFromThread: loadPlayersFromThread,
    templates: markerTemplates
  };

})(OPG, window);
