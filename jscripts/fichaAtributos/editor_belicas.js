/* Editor Bélicas - Módulo OPG */
(function(OPG, w){
  'use strict';
  
  // Verifica que el textarea exista antes de continuar
  if (!document.getElementById('belicas')) return;
  
  var BELICAS = {
    'Combatiente': {sub: {'Berserker': 0, 'Campeón': 0}, espe1: '', nivel: 1},
    'Espadachín': {sub: {'Samurái': 0, 'Mosquetero': 0}, espe1: '', nivel: 1},
    'Guerrero': {sub: {'Castigador': 0, 'Warhammer': 0}, espe1: '', nivel: 1},
    'Tirador': {sub: {'Duelista': 0, 'Francotirador': 0}, espe1: '', nivel: 1},
    'Arquero': {sub: {'Ballestero': 0, 'Cazador': 0}, espe1: '', nivel: 1},
    'Artillero': {sub: {'Destructor': 0, 'Juggernaut': 0}, espe1: '', nivel: 1},
    'Tecnicista': {sub: {'Diletante': 0, 'WeaponMaster': 0}, espe1: '', nivel: 1},
    'Pícaro': {sub: {'Gambito': 0, 'Trickster': 0}, espe1: '', nivel: 1},
    'Artista': {sub: {'Bardo': 0, 'Trovador': 0}, espe1: '', nivel: 1},
    'Escudero': {sub: {'Vanguardia': 0, 'Bastión': 0}, espe1: '', nivel: 1},
    'Asesino': {sub: {'Sombra': 0, 'Verdugo': 0}, espe1: '', nivel: 1},
    'Artista Marcial': {sub: {'Acróbata': 0, 'Monje': 0}, espe1: '', nivel: 1}
  };
  
  function crearEditorBelicas() {
    var textarea = document.getElementById('belicas');
    if (!textarea) return;
    
    var dataActual = OPG.utils.safeJSON.parse(textarea.value.trim() || '{}', {});
    
    var contenedor = document.createElement('div');
    contenedor.className = 'editor-belicas-visual';
    contenedor.style.cssText = 'margin:10px 0; padding:15px; background:#ff7e00; border:2px solid #ff7e00; border-radius:8px;';
    
    var titulo = document.createElement('h4');
    titulo.textContent = 'Bélicas disponibles:';
    titulo.style.cssText = 'color:#fff; margin:0 0 10px 0; font-size:14px; font-weight:bold; cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between;';
    
    var toggleIcon = document.createElement('span');
    toggleIcon.textContent = '▼';
    toggleIcon.style.cssText = 'font-size:12px; transition:transform 0.3s; transform:rotate(-90deg);';
    titulo.appendChild(toggleIcon);
    
    contenedor.appendChild(titulo);
    
    var grid = document.createElement('div');
    grid.style.cssText = 'display:none; flex-direction:column; gap:8px;';
    grid.className = 'belicas-grid';
    
    titulo.onclick = function() {
      var isCollapsed = grid.style.display === 'none';
      grid.style.display = isCollapsed ? 'flex' : 'none';
      toggleIcon.style.transform = isCollapsed ? 'rotate(0deg)' : 'rotate(-90deg)';
    };
    
    for (var belica in BELICAS) {
      if (!BELICAS.hasOwnProperty(belica)) continue;
      
      var belicaContainer = document.createElement('div');
      belicaContainer.style.cssText = 'display:flex; flex-direction:column;';
      
      var label = document.createElement('label');
      label.style.cssText = 'display:flex; align-items:center; padding:6px 10px; background:#fff; border-radius:4px; cursor:pointer; transition:background 0.2s;';
      label.onmouseover = function(){ this.style.background = '#ffe5cc'; };
      label.onmouseout = function(){ this.style.background = '#fff'; };
      
      var checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = dataActual.hasOwnProperty(belica);
      checkbox.setAttribute('data-belica', belica);
      checkbox.style.cssText = 'margin-right:8px;';
      
      var texto = document.createElement('span');
      texto.textContent = belica;
      texto.style.cssText = 'color:#333; font-size:13px; font-weight:bold;';
      
      label.appendChild(checkbox);
      label.appendChild(texto);
      belicaContainer.appendChild(label);
      
      // Crear desplegable de especialidades
      var subContainer = document.createElement('div');
      subContainer.className = 'sub-container-' + belica.replace(/\s+/g, '_');
      subContainer.style.cssText = 'display:' + (checkbox.checked ? 'flex' : 'none') + '; flex-direction:column; gap:4px; margin-left:20px; margin-top:4px;';
      
      for (var sub in BELICAS[belica].sub) {
        if (!BELICAS[belica].sub.hasOwnProperty(sub)) continue;
        
        var subLabel = document.createElement('label');
        subLabel.style.cssText = 'display:flex; align-items:center; padding:4px 8px; background:#ffe5cc; border-radius:3px; cursor:pointer; font-size:12px;';
        
        var subCheck = document.createElement('input');
        subCheck.type = 'checkbox';
        subCheck.checked = dataActual[belica] && dataActual[belica].sub && dataActual[belica].sub[sub] > 0;
        subCheck.setAttribute('data-belica', belica);
        subCheck.setAttribute('data-sub', sub);
        subCheck.style.cssText = 'margin-right:6px;';
        
        var subTexto = document.createElement('span');
        subTexto.textContent = sub;
        subTexto.style.cssText = 'color:#333; flex:1;';
        
        var nivelInput = document.createElement('input');
        nivelInput.type = 'number';
        nivelInput.min = '1';
        nivelInput.max = '10';
        nivelInput.value = (dataActual[belica] && dataActual[belica].sub && dataActual[belica].sub[sub]) || 1;
        nivelInput.setAttribute('data-belica', belica);
        nivelInput.setAttribute('data-sub', sub);
        nivelInput.style.cssText = 'width:50px; margin-left:8px; padding:2px 4px; border:1px solid #ccc; border-radius:3px; display:' + (subCheck.checked ? 'block' : 'none') + ';';
        nivelInput.className = 'nivel-input-' + belica.replace(/\s+/g, '_') + '-' + sub.replace(/\s+/g, '_');
        
        subCheck.onchange = function() {
          var bel = this.getAttribute('data-belica');
          var su = this.getAttribute('data-sub');
          var inp = document.querySelector('.nivel-input-' + bel.replace(/\s+/g, '_') + '-' + su.replace(/\s+/g, '_'));
          if (inp) {
            inp.style.display = this.checked ? 'block' : 'none';
            if (!this.checked) inp.value = 1;
          }
          actualizarJSON();
        };
        
        nivelInput.onchange = actualizarJSON;
        
        subLabel.appendChild(subCheck);
        subLabel.appendChild(subTexto);
        subLabel.appendChild(nivelInput);
        subContainer.appendChild(subLabel);
      }
      
      belicaContainer.appendChild(subContainer);
      
      // Toggle del desplegable al marcar/desmarcar belica
      checkbox.onchange = function() {
        var bel = this.getAttribute('data-belica');
        var subCont = document.querySelector('.sub-container-' + bel.replace(/\s+/g, '_'));
        if (subCont) {
          subCont.style.display = this.checked ? 'flex' : 'none';
        }
        actualizarJSON();
      };
      
      grid.appendChild(belicaContainer);
    }
    
    contenedor.appendChild(grid);
    textarea.parentNode.insertBefore(contenedor, textarea);
    
    // Ocultar textarea original
    textarea.style.display = 'none';
  }
  
  function actualizarJSON() {
    var textarea = document.getElementById('belicas');
    if (!textarea) return;
    
    var checkboxes = document.querySelectorAll('input[data-belica]:not([data-sub])');
    var nuevoData = {};
    
    for (var i = 0; i < checkboxes.length; i++) {
      if (checkboxes[i].checked) {
        var belica = checkboxes[i].getAttribute('data-belica');
        var subData = {sub: {}, espe1: '', espe2: '', nivel: 1};
        
        // Obtener todas las especialidades y sus valores
        var especialidadesActivas = [];
        
        // Primero, llenar con todas las especialidades en 0
        for (var subKey in BELICAS[belica].sub) {
          if (BELICAS[belica].sub.hasOwnProperty(subKey)) {
            subData.sub[subKey] = 0;
          }
        }
        
        // Luego, actualizar solo las que están marcadas
        var subChecks = document.querySelectorAll('input[type="checkbox"][data-belica="' + belica + '"][data-sub]');
        for (var j = 0; j < subChecks.length; j++) {
          var subNombre = subChecks[j].getAttribute('data-sub');
          if (subChecks[j].checked) {
            var nivelInput = document.querySelector('input[type="number"][data-belica="' + belica + '"][data-sub="' + subNombre + '"]');
            var nivelValor = nivelInput ? parseInt(nivelInput.value, 10) : 1;
            if (nivelValor > 0) {
              subData.sub[subNombre] = nivelValor;
              especialidadesActivas.push(subNombre);
            }
          }
        }
        
        // Asignar espe1, espe2 y nivel según especialidades activas
        if (especialidadesActivas.length > 0) {
          subData.espe1 = especialidadesActivas[0];
          if (especialidadesActivas.length > 1) {
            subData.espe2 = especialidadesActivas[1];
          }
        } else {
          subData.espe1 = '';
          delete subData.espe2;
        }
        // El nivel siempre es 1
        subData.nivel = 1;
        
        nuevoData[belica] = subData;
      }
    }
    
    textarea.value = OPG.utils.safeJSON.stringify(nuevoData, '{}');
  }
  
  // Inicializar cuando el DOM esté listo
  OPG.onReady(function(){
    crearEditorBelicas();
  });
  
})(window.OPG || {}, window);
