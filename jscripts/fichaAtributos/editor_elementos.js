/* Editor Elementos - Módulo OPG */
(function(OPG, w){
  'use strict';
  
  // Verifica que el textarea exista antes de continuar
  if (!document.getElementById('elementos')) return;
  
  var ELEMENTOS = ['Piro', 'Aqua', 'Aero', 'Electro', 'Cryo'];
  
  function crearEditorElementos() {
    var textarea = document.getElementById('elementos');
    if (!textarea) return;
    
    var dataActual = OPG.utils.safeJSON.parse(textarea.value.trim() || '{}', {});
    
    var contenedor = document.createElement('div');
    contenedor.className = 'editor-elementos-visual';
    contenedor.style.cssText = 'margin:10px 0; padding:15px; background:#ff7e00; border:2px solid #ff7e00; border-radius:8px;';
    
    var titulo = document.createElement('h4');
    titulo.textContent = 'Elementos disponibles:';
    titulo.style.cssText = 'color:#fff; margin:0 0 10px 0; font-size:14px; font-weight:bold; cursor:pointer; user-select:none; display:flex; align-items:center; justify-content:space-between;';
    
    var toggleIcon = document.createElement('span');
    toggleIcon.textContent = '▼';
    toggleIcon.style.cssText = 'font-size:12px; transition:transform 0.3s; transform:rotate(-90deg);';
    titulo.appendChild(toggleIcon);
    
    contenedor.appendChild(titulo);
    
    var grid = document.createElement('div');
    grid.style.cssText = 'display:none; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:8px;';
    grid.className = 'elementos-grid';
    
    titulo.onclick = function() {
      var isCollapsed = grid.style.display === 'none';
      grid.style.display = isCollapsed ? 'grid' : 'none';
      toggleIcon.style.transform = isCollapsed ? 'rotate(0deg)' : 'rotate(-90deg)';
    };
    
    for (var i = 0; i < ELEMENTOS.length; i++) {
      var elemento = ELEMENTOS[i];
      
      var label = document.createElement('label');
      label.style.cssText = 'display:flex; align-items:center; padding:6px 10px; background:#fff; border-radius:4px; cursor:pointer; transition:background 0.2s;';
      label.onmouseover = function(){ this.style.background = '#ffe5cc'; };
      label.onmouseout = function(){ this.style.background = '#fff'; };
      
      var checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = dataActual[elemento] === 1;
      checkbox.setAttribute('data-elemento', elemento);
      checkbox.style.cssText = 'margin-right:8px;';
      
      checkbox.onchange = actualizarJSON;
      
      var texto = document.createElement('span');
      texto.textContent = elemento;
      texto.style.cssText = 'color:#333; font-size:13px;';
      
      label.appendChild(checkbox);
      label.appendChild(texto);
      grid.appendChild(label);
    }
    
    contenedor.appendChild(grid);
    textarea.parentNode.insertBefore(contenedor, textarea);
    
    // Ocultar textarea original
    textarea.style.display = 'none';
  }
  
  function actualizarJSON() {
    var textarea = document.getElementById('elementos');
    if (!textarea) return;
    
    var checkboxes = document.querySelectorAll('input[data-elemento]');
    var nuevoData = {};
    
    for (var i = 0; i < checkboxes.length; i++) {
      var elemento = checkboxes[i].getAttribute('data-elemento');
      nuevoData[elemento] = checkboxes[i].checked ? 1 : 0;
    }
    
    textarea.value = OPG.utils.safeJSON.stringify(nuevoData, '{}');
  }
  
  // Inicializar cuando el DOM esté listo
  OPG.onReady(function(){
    crearEditorElementos();
  });
  
})(window.OPG || {}, window);
