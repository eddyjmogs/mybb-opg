
//#region HELPERS

// Devuelve el prefijo numérico inicial ("309" de "309-PET001")
function getNumericPrefix(id) {
  var m = String(id || '').match(/^(\d+)/);
  return m ? m[1] : '';
}

function sleep(time) {
  return new Promise((resolve) => setTimeout(resolve, time));
}

// Función helper para acceso seguro a propiedades de objetos
function safeGet(obj, property, defaultValue = 'N/A') {
  if (!obj || typeof obj !== 'object') {
    console.warn(`safeGet: objeto es null/undefined o no es un objeto:`, obj);
    return defaultValue;
  }
  
  if (obj.hasOwnProperty(property)) {
    return obj[property];
  } else {
    console.warn(`safeGet: propiedad '${property}' no encontrada en objeto:`, obj);
    return defaultValue;
  }
}

// Función helper para crear texto de oficios de manera segura
function createOficioTexto(mainOficio, oficiosInfo) {
  if (!mainOficio) {
    console.warn('createOficioTexto: mainOficio es null/undefined');
    return '';
  }
  
  if (!oficiosInfo) {
    console.warn(`createOficioTexto: oficiosInfo es null/undefined para oficio '${mainOficio}'`);
    return mainOficio + ' (Nivel desconocido): ';
  }
  
  const nivel = safeGet(oficiosInfo, 'nivel', 'desconocido');
  return mainOficio + ` (${nivel}): `;
}

// Función para verificar que todos los objetos necesarios estén disponibles
function verificarObjetosInicializados() {
  const errores = [];
  
  if (typeof oficios === 'undefined') {
    errores.push('oficios no está definido');
  }
  
  if (typeof belicas === 'undefined') {
    errores.push('belicas no está definido');
  }
  
  if (errores.length > 0) {
    console.error('Objetos no inicializados:', errores);
    return false;
  }
  
  return true;
}

// Override global para interceptar errores de acceso a propiedades
window.originalError = window.onerror;
window.onerror = function(message, source, lineno, colno, error) {
  if (message && message.includes("Cannot read properties of undefined")) {
    console.error('=== ERROR INTERCEPTADO ===');
    console.error('Mensaje:', message);
    console.error('Archivo:', source);
    console.error('Línea:', lineno);
    console.error('Columna:', colno);
    console.error('Error objeto:', error);
    
    // Verificar estado de objetos principales
    verificarObjetosInicializados();
    
    // Llamar el handler original si existe
    if (window.originalError) {
      return window.originalError(message, source, lineno, colno, error);
    }
    
    return true; // Prevenir que el error rompa la ejecución
  }
  
  // Llamar el handler original para otros errores
  if (window.originalError) {
    return window.originalError(message, source, lineno, colno, error);
  }
};

// Función para crear oficiosInfo de manera segura
function createSafeOficiosInfo(oficioName) {
  if (!oficioName) {
    console.warn('createSafeOficiosInfo: oficioName es null/undefined');
    return { nivel: 'desconocido' };
  }
  
  if (typeof oficios === 'undefined') {
    console.warn('createSafeOficiosInfo: objeto oficios no está definido');
    return { nivel: 'desconocido' };
  }
  
  const oficioInfo = oficios[oficioName];
  if (!oficioInfo) {
    console.warn(`createSafeOficiosInfo: oficio '${oficioName}' no encontrado en oficios`);
    return { nivel: 'desconocido' };
  }
  
  return oficioInfo;
}

// Variable global de fallback para oficiosInfo
if (typeof oficiosInfo === 'undefined') {
  window.oficiosInfo = { nivel: 'desconocido' };
  console.warn('Variable oficiosInfo inicializada con valor de fallback');
}

// Funciones para manejo de NPCs en biografía
function loadNpcsAcompanantesBio() {
    // Limpiamos el contenedor para evitar duplicados
    $('#npcs_acompanantes').html('');

    // Verificar que las variables existan
    if (typeof npcs_array_json !== 'undefined' && npcs_array_json.length > 0) {
        // Mostramos los NPCs
        for (var i = 0; i < npcs_array_json.length; i++) {
            var npcs = npcs_json[npcs_array_json[i]][0];
            
			var key = npcs_array_json[i];             // puede ser "309-NPC001"
			var keyNum = getNumericPrefix(key);           // "309"

			let fichaNum = String(query_uid);
			if (keyNum !== fichaNum) continue;
			
            var npcHtml = `<a href="/op/compas.php?npc_id=${npcs.npc_id}" target="_blank">
                <div 
                    class="npcBox npcItemBio" 
                    style="position: relative; background-size: cover; background: url(${npcs.avatar1}); border: 3px solid #8062d6; margin-right: 10px; display: inline-block;" 
                >
                    <div class="clipBox" style="background-color: #8062d6;"></div>
                    <div style="text-align: center; transform: rotate(349deg);">
                        <span class="npcName">${npcs.nombre}</span>
                    </div>
                </div></a>
            `;
            
            $('#npcs_acompanantes').append(npcHtml);
        }
    }

    // Verificar mascotas
        // Mostramos las mascotas
        for (var i = 0; i < mascotas_array_json.length; i++) {
            var mascotas = mascotas_json[mascotas_array_json[i]][0];
			
			var key = mascotas_array_json[i];             // puede ser "309-PET001"
			var keyNum = getNumericPrefix(key);           // "309"

			let fichaNum = String(query_uid);
			if (keyNum !== fichaNum) continue;
            
            var mascotaHtml = `<a href="/op/compas.php?npc_id=${mascotas.npc_id}" target="_blank">
                <div class="npcBox npcItemBio"
					 style="position: relative; background-size: cover; background: url(${mascotas.avatar1}); border: 3px solid #ff5900; margin-right: 10px; display: inline-block;"
				>
                    <div class="clipBox" style="background-color: #ff5900;"></div>
                    <div style="text-align: center; transform: rotate(349deg);">
                        <span class="npcName">${mascotas.nombre}</span>
                    </div>
                </div></a>
            `;
            
            $('#npcs_acompanantes').append(mascotaHtml);
        }
		
    // Si no hay NPCs ni mascotas, mostramos un mensaje
    if ((typeof npcs_array_json === 'undefined' || npcs_array_json.length === 0) && 
        (typeof mascotas_array_json === 'undefined' || mascotas_array_json.length === 0)) {
        $('#npcs_acompanantes').html('<div style="width: 100%; text-align: center; font-family: InterRegular; margin-top: 100px;">No tienes mascotas ni acompañantes</div>');
    }
}

function closeModalBio() {
    var modal = document.getElementById('npcModalBio');
    if (modal) {
        modal.style.display = "none";
    }
}

// Funciones para manejo de NPCs en biografía
function loadNpcsAcompanantesBio() {
    // Limpiamos el contenedor para evitar duplicados
    $('#npcs_acompanantes').html('');

    // Verificar que las variables existan
    if (typeof npcs_array_json !== 'undefined' && npcs_array_json.length > 0) {
        // Mostramos los NPCs
        for (var i = 0; i < npcs_array_json.length; i++) {
            var npcs = npcs_json[npcs_array_json[i]][0];
            
			var key = npcs_array_json[i];             // puede ser "309-NPC001"
			var keyNum = getNumericPrefix(key);           // "309"


			let fichaNum = String(query_uid);
			if (keyNum !== fichaNum) continue;
			
            var npcHtml = `<a href="/op/compas.php?npc_id=${npcs.npc_id}" target="_blank">
                <div 
                    class="npcBox npcItemBio" 
                    style="position: relative; background-size: cover; background: url(${npcs.avatar1}); border: 3px solid #8062d6; margin-right: 10px; display: inline-block;" 
                >
                    <div class="clipBox" style="background-color: #8062d6;"></div>
                    <div style="text-align: center; transform: rotate(349deg);">
                        <span class="npcName">${npcs.nombre}</span>
                    </div>
                </div></a>
            `;
            
            $('#npcs_acompanantes').append(npcHtml);
        }
    }

    // Verificar mascotas
        // Mostramos las mascotas
        for (var i = 0; i < mascotas_array_json.length; i++) {
            var mascotas = mascotas_json[mascotas_array_json[i]][0];
			
			var key = mascotas_array_json[i];             // puede ser "309-PET001"
			var keyNum = getNumericPrefix(key);           // "309"


			let fichaNum = String(query_uid);
			if (keyNum !== fichaNum) continue;
            
            var mascotaHtml = `<a href="/op/compas.php?npc_id=${mascotas.npc_id}" target="_blank">
                <div class="npcBox npcItemBio"
					 style="position: relative; background-size: cover; background: url(${mascotas.avatar1}); border: 3px solid #ff5900; margin-right: 10px; display: inline-block;"
				>
                    <div class="clipBox" style="background-color: #ff5900;"></div>
                    <div style="text-align: center; transform: rotate(349deg);">
                        <span class="npcName">${mascotas.nombre}</span>
                    </div>
                </div></a>
            `;
            
            $('#npcs_acompanantes').append(mascotaHtml);
        }
		
    // Si no hay NPCs ni mascotas, mostramos un mensaje
    if ((typeof npcs_array_json === 'undefined' || npcs_array_json.length === 0) && 
        (typeof mascotas_array_json === 'undefined' || mascotas_array_json.length === 0)) {
        $('#npcs_acompanantes').html('<div style="width: 100%; text-align: center; font-family: InterRegular; margin-top: 100px;">No tienes mascotas ni acompañantes</div>');
    }
}

function closeModalBio() {
    var modal = document.getElementById('npcModalBio');
    if (modal) {
        modal.style.display = "none";
    }
}

function clickPestana(tipo) {
    $('.pestana').removeClass('pestana-active');
    
    // Guardar en sessionStorage el último tab visitado
    sessionStorage.setItem('lastFichaTab', tipo);
    
    // Ocultar todas las secciones primero
    $('#portada').css('display', 'none');
    $('#biografia').css('display', 'none');
    $('#belico').css('display', 'none');
    $('#tecnicass').css('display', 'none');
    $('#inventario').css('display', 'none');
    $('#secreto1').css('display', 'none'); // Asegurarse de ocultar también la sección secreto1
    
	// Restaurar el estilo normal del nombre y apodo (sin tachar) por defecto
    $('.nombre').css({
        'text-decoration': 'none',
        'background-color': 'transparent',
        'color': '' // Restaurar al color definido en CSS
    });
    $('.apodo').css({
        'text-decoration': 'none',
        'background-color': 'transparent',
        'color': faccionColor // Restaurar al color definido en CSS
    });

    // Mostrar la sección seleccionada y aplicar estilos correspondientes
    if (tipo == 'portada') {
        $('#portada').css('display', 'block');
        $('.fondo-ficha').css('background-image', `url(/images/op/uploads/Fondo${faccion}1_One_Piece_Gaiden_Foro_Rol.webp)`);
        $('#pestana_portada').addClass('pestana-active');
    }

    if (tipo == 'biografia') {
        $('#biografia').css('display', 'block');
        $('.fondo-ficha').css('background-image', `url(/images/op/uploads/Fondo${faccion}2_One_Piece_Gaiden_Foro_Rol.webp)`);
        $('#pestana_biografia').addClass('pestana-active');
    }

    if (tipo == 'belico') {
        $('#belico').css('display', 'block');
        $('.fondo-ficha').css('background-image', `url(/images/op/uploads/Fondo${faccion}3_One_Piece_Gaiden_Foro_Rol.webp)`);
        $('#pestana_belico').addClass('pestana-active');
    }

    if (tipo == 'tecnicass') {
        $('#tecnicass').css('display', 'block');
        $('.fondo-ficha').css('background-image', `url(/images/op/uploads/Fondo${faccion}4_One_Piece_Gaiden_Foro_Rol.webp)`);
        $('#pestana_tecnicas').addClass('pestana-active');
    }

    if (tipo == 'inventario') {
        $('#inventario').css('display', 'block');
        $('.fondo-ficha').css('background-image', `url(/images/op/uploads/Fondo${faccion}5_One_Piece_Gaiden_Foro_Rol.webp)`);
        $('#pestana_inventario').addClass('pestana-active');
    }
    
    // Añadir manejo para la pestaña secreto1
    if (tipo == 'secreto1') {
        $('#secreto1').css('display', 'block');
        // $('.fondo-ficha').css('background-image', `url(https://pbs.twimg.com/media/DZeebFlVwAAivzq.jpg)`);
		$('.fondo-ficha').css('background-image', `url(/images/op/uploads/Fondo${faccion}Secret_One_Piece_Gaiden_Foro_Rol.webp)`);
        $('#pestana_secreto1').addClass('pestana-active');
        
        // Aplicar el efecto de tachado en negro al nombre y apodo
        $('.nombre').css({
            'text-decoration': 'line-through',
            'text-decoration-color': 'black',
            'text-decoration-thickness': '4px',
            'background-color': 'black',
            'color': 'black'
        });
        $('.apodo').css({
            'text-decoration': 'line-through',
            'text-decoration-color': 'black',
            'text-decoration-thickness': '4px',
            'background-color': 'black',
            'color': 'black'
        });
    }
}

function sleep (time) {
  return new Promise((resolve) => setTimeout(resolve, time));
}

function chooseEspeOficio(oficio, espe) {
    // Verificar que el oficio existe
    if (!oficios[oficio]) {
        console.warn(`Oficio '${oficio}' no encontrado en el objeto oficios`);
        return;
    }
    
    let isOficio1 = oficio1 == oficio;
    let isOficio2 = oficio2 == oficio;
    
    let espesCount = 0;
    
    let isEspe1 = oficios[oficio].espe1 == espe;
    let isEspe2 = oficios[oficio].espe2 == espe;
    
    if (oficios[oficio].espe1) { espesCount = espesCount + 1; }
    if (oficios[oficio].espe2) { espesCount = espesCount + 1; }
    
    let espeNivel = oficios[oficio].sub[espe];
    
    if (espeNivel == 2) {
    
        if (nivel < 40) {
            alert(`No cumples el requisito mínimo de nivel 40 para aprender la especialización ` + espe + `.`);
            return;
        } 
    
        if (isOficio1 && isEspe1 && nikas >= 50 && puntos_oficio >= 5000) { if (confirm(`Subir a nivel 3 la especialización ` + espe + ` tiene un costo de 50 nikas y 5000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe1", "oficio1", espe); } } 
        else if (isOficio2 && isEspe1 && nikas >= 50 && puntos_oficio >= 5000) { if (confirm(`Subir a nivel 3 la especialización ` + espe + ` tiene un costo de 50 nikas y 5000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe1", "oficio2", espe); } } 
        else if (isOficio1 && isEspe2 && nikas >= 50 && puntos_oficio >= 5000) { if (confirm(`Subir a nivel 3 la especialización ` + espe + ` tiene un costo de 50 nikas y 5000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe2", "oficio1", espe); } } 
        else if (isOficio2 && isEspe2 && nikas >= 50 && puntos_oficio >= 5000) { if (confirm(`Subir a nivel 3 la especialización ` + espe + ` tiene un costo de 50 nikas y 5000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe2", "oficio2", espe); } } 
        else { alert(`No cumples los requisitos para mejorar la especialización ` + espe + `. Debes tener 50 nikas y 5000 puntos de oficio.`); }
    
    } else if (espeNivel == 1) {
    
        if (nivel < 30) {
            alert(`No cumples el requisito mínimo de nivel 30 para aprender la especialización ` + espe + `.`);
            return;
        } 
    
        if (isOficio1 && isEspe1 && nikas >= 25 && puntos_oficio >= 3500) { if (confirm(`Subir a nivel 2 la especialización ` + espe + ` tiene un costo de 25 nikas y 3500 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe1", "oficio1", espe); } } 
        else if (isOficio2 && isEspe1 && nikas >= 25 && puntos_oficio >= 3500) { if (confirm(`Subir a nivel 2 la especialización ` + espe + ` tiene un costo de 25 nikas y 3500 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe1", "oficio2", espe); } } 
        else if (isOficio1 && isEspe2 && nikas >= 25 && puntos_oficio >= 3500) { if (confirm(`Subir a nivel 2 la especialización ` + espe + ` tiene un costo de 25 nikas y 3500 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe2", "oficio1", espe); } } 
        else if (isOficio2 && isEspe2 && nikas >= 25 && puntos_oficio >= 3500) { if (confirm(`Subir a nivel 2 la especialización ` + espe + ` tiene un costo de 25 nikas y 3500 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe2", "oficio2", espe); } } 
        else { alert(`No cumples los requisitos para mejorar la especialización ` + espe + `. Debes tener 25 nikas y 3500 puntos de oficio.`); }
    
    } else if (espeNivel == 0) {
    
        if (nivel < 20) {
            alert(`No cumples el requisito mínimo de nivel 20 para aprender la especialización ` + espe + `.`);
            return;
        } 
        
        if (isOficio1 && espesCount == 0 && nikas >= 10 && puntos_oficio >= 2000) { if (confirm(`Para aprender la especialización ` + espe + ` tiene un costo de 10 nikas y 2000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe1", "oficio1", espe); } } 
        else if (isOficio2 && espesCount == 0 && nikas >= 10 && puntos_oficio >= 2000) { if (confirm(`Para aprender la especialización ` + espe + ` tiene un costo de 10 nikas y 2000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe1", "oficio2", espe); } }
        else if (isOficio1 && espesCount == 1 && nikas >= 10 && puntos_oficio >= 2000) { if (confirm(`Para aprender la especialización ` + espe + ` tiene un costo de 10 nikas y 2000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe2", "oficio1", espe); } } 
        else if (isOficio2 && espesCount == 1 && nikas >= 10 && puntos_oficio >= 2000) { if (confirm(`Para aprender la especialización ` + espe + ` tiene un costo de 10 nikas y 2000 puntos de oficio. ¿Estás de acuerdo?`)) { subirEspeOficio("espe2", "oficio2", espe); } }
        else { alert(`No cumples los requisitos para aprender la especialización ` + espe + `. Debes tener 10 nikas y 2000 puntos de oficio.`); }
    
    }
    
}
    
function chooseOficio(oficio, hasOficio) {
    
    let isOficio1 = oficio1 == oficio;
    let isOficio2 = oficio2 == oficio;
    
    if (hasOficio) {
    
        if (isOficio1 && puntos_oficio >= 1000 && nivel >= 10) {
            if (confirm(`Para subir el nivel al oficio de ` + oficio + ` tiene un costo de 1000 puntos de oficio. ¿Estás de acuerdo?`)) { subirOficio(oficio, 'oficio1'); }
        } else if (isOficio2 && puntos_oficio >= 1000 && nivel >= 10) {
            if (confirm(`Para subir el nivel al oficio de ` + oficio + ` tiene un costo de 1000 puntos de oficio. ¿Estás de acuerdo?`)) { subirOficio(oficio, 'oficio2'); }
        } else {
            alert(`No cumples los 1000 puntos de oficio o de nivel 10 para mejorar el oficio de ` + oficio + `.`);
        }
    
    } else {
    
        if (has_polivalente || has_erudito) {
            if (confirm(`Aprender el segundo oficio ` + oficio + ` es gratis para Eruditos y Polivalentes. ¿Estás de acuerdo con aprender este oficio?`)) { subirOficio(oficio, 'oficio2'); }
        } else {
            alert('No tienes acceso a subir el segundo oficio');
        }
    
    }	
}

function subirEspeOficio(espeNumber, oficioNumber, espe) {
    window.location.href = `/op/mejoras.php?accion=oficio_espe&espeNumber=` + espeNumber + `&oficioNumber=` + oficioNumber + `&espe=` + espe;
}
    
function subirOficio(oficio, oficioNumber) { 
    window.location.href = `/op/mejoras.php?accion=oficio&oficio=` + oficio + `&oficioNumber=` + oficioNumber;
}	
	


function insertarDisciplinaTecs(disciplina, camino) {
	if (tec_aprendidas_json[disciplina]) {	
	
		if (tec_aprendidas_json[camino]) {	
			let tecCount = tec_aprendidas_json[camino].length;
			for (let i = 0; i < tecCount; i++) {
				tec_aprendidas_json[disciplina].push(tec_aprendidas_json[camino][i]);
			}	
		}	

	}
}

function chooseOficioTest() {
    openOficiosModal();
}



// Nueva función para abrir el modal de oficios
function openOficiosModal() {
    // Lista de todos los oficios disponibles con sus imágenes
    const oficiosDisponibles = [
        { nombre: 'Cocinero', imagen: '/images/op/uploads/OficioFichaCocinero_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Médico', imagen: '/images/op/uploads/OficioFichaMédico_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Navegante', imagen: '/images/op/uploads/OficioFichaNavegante_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Artesano', imagen: '/images/op/uploads/OficioFichaArtesano_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Carpintero', imagen: '/images/op/uploads/OficioFichaCarpintero_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Aventurero', imagen: '/images/op/uploads/OficioFichaAventurero_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Inventor', imagen: '/images/op/uploads/OficioFichaInventor_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Investigador', imagen: '/images/op/uploads/OficioFichaInvestigador_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Mercader', imagen: '/images/op/uploads/OficioFichaMercader_One_Piece_Gaiden_Foro_Rol.webp' },
        { nombre: 'Recolector', imagen: '/images/op/uploads/OficioFichaRecolector_One_Piece_Gaiden_Foro_Rol.webp' }
    ];

    // Crear o reutilizar el modal
    var modalId = 'oficiosModal';
    var modal = document.getElementById(modalId);
    
    if (!modal) {
        $('body').append('<div id="' + modalId + '" class="modal"></div>');
        modal = document.getElementById(modalId);
    }

    // Construir el contenido del modal
    var modalContent = '<div class="modal-content oficios-modal-content">';
    modalContent += '<span class="close" onclick="closeOficiosModal()" style="position: absolute; right: 20px; top: 10px; font-size: 28px; cursor: pointer; z-index: 10001;">&times;</span>';
    
    // Cabecera
    modalContent += `<div class="modal-header" style="padding: 15px; background: ${borderColor}; text-align: center; border-radius: 8px 8px 0 0;">`;
    modalContent += '<h2 style="color: white; font-family: \'moonGetHeavy\'; margin: 0; text-shadow: 1px 1px 2px black;">SELECCIONAR SEGUNDO OFICIO</h2>';
    modalContent += '</div>';
    
    // Cuerpo con grid de oficios
    modalContent += '<div class="modal-body oficios-grid">';
    
    oficiosDisponibles.forEach(function(oficio, index) {
        // Verificar si ya tiene este oficio como primer oficio
        var isDisabled = (oficio1 === oficio.nombre);
        var disabledClass = isDisabled ? 'oficio-disabled' : '';
        var disabledText = isDisabled ? '<div class="oficio-ya-aprendido">YA APRENDIDO</div>' : '';
        
        modalContent += '<div class="oficio-card ' + disabledClass + '" onclick="' + (isDisabled ? '' : 'seleccionarOficio(\'' + oficio.nombre + '\')') + '">';
        modalContent += '<div class="oficio-imagen-container">';
        modalContent += '<img src="' + oficio.imagen + '" alt="' + oficio.nombre + '" class="oficio-imagen">';
        modalContent += disabledText;
        modalContent += '</div>';
        modalContent += '<div class="oficio-nombre">' + oficio.nombre + '</div>';
        modalContent += '</div>';
    });
    
    modalContent += '</div>';
    modalContent += '</div>';
    
    // Mostrar el modal con el contenido
    modal.innerHTML = modalContent;
    modal.style.display = "block";
    
    // Cerrar el modal al hacer clic fuera
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };
}

// Función para seleccionar un oficio
function seleccionarOficio(nombreOficio) {
    if (has_polivalente || has_erudito) {
        if (confirm('¿Estás seguro de que quieres aprender el oficio de ' + nombreOficio + '? Esta acción es permanente.')) {
            subirOficio(nombreOficio, 'oficio2');
        }
    } else {
        alert('No tienes acceso a aprender un segundo oficio. Necesitas la virtud Polivalente o Erudito.');
    }
}

// Función para cerrar el modal de oficios
function closeOficiosModal() {
    var modal = document.getElementById('oficiosModal');
    if (modal) {
        modal.style.display = "none";
    }
}

function capitalize(s) { return s && s[0].toUpperCase() + s.slice(1); }

// Función auxiliar para obtener técnicas de forma segura
function getTecnicas(key) {
    // Verificar que tec_aprendidas_json existe
    if (!tec_aprendidas_json) {
        console.error('Error: tec_aprendidas_json no está definido');
        return [];
    }
    if (!tec_aprendidas_json.hasOwnProperty(key)) {
        console.log(`No se encontraron técnicas para la clave: ${key}`);
        return [];
    }
    return tec_aprendidas_json[key] || [];
}

function showBlock(estilo, color) {
	$('#tecnicas-caja').html('');
	$('#tecnicas-background').css('background-color', color);
	
	// Guardar en sessionStorage el último tab visitado
	sessionStorage.setItem('lastTecnicasTab', estilo);
	sessionStorage.setItem('lastTecnicasColor', color);
	
	// Limpiar espacios y caracteres especiales del estilo
	let estiloKey = estilo.replace('_', ' ').trim();
	
	// Caso especial para "todo": usar HTML pregenerado
	if (estiloKey === 'todo') {
		if (typeof tecnicas_html_json !== 'undefined' && tecnicas_html_json && tecnicas_html_json['todo']) {
			$('#tecnicas-caja').html(tecnicas_html_json['todo']);
			return;
		}
		// Fallback al sistema antiguo
		if (tec_aprendidas_json && tec_aprendidas_json['todo']) {
			let todoTecs = tec_aprendidas_json['todo'];
			for (let i = 0; i < todoTecs.length; i++) {
				crearTecnica(todoTecs[i]);
			}
		}
		return;
	}
	
	// Caso especial para Haki: usar directamente el estilo "Haki"
	if (estiloKey === 'Haki') {
		// Intentar usar HTML pregenerado para el estilo Haki
		if (typeof tecnicas_html_json !== 'undefined' && tecnicas_html_json && tecnicas_html_json['Haki']) {
			$('#tecnicas-caja').html(tecnicas_html_json['Haki']);
			return;
		}
		
		// Fallback: combinar las ramas individualmente
		let hakiArray = ['Haoshoku', 'Kenbunshoku', 'Busoshoku'];
		let hayTecnicas = false;
		
		for (let tipoHaki of hakiArray) {
			if (tec_aprendidas_json && tec_aprendidas_json[tipoHaki] && Array.isArray(tec_aprendidas_json[tipoHaki])) {
				let tecHaki = tec_aprendidas_json[tipoHaki];
				for (let i = 0; i < tecHaki.length; i++) {
					crearTecnica(tecHaki[i]);
					hayTecnicas = true;
				}
			}
		}
		
		if (!hayTecnicas) {
			$('#tecnicas-caja').html('<div style="text-align: center; padding: 20px;">No hay técnicas de Haki aprendidas</div>');
		}
		
		return;
	}
	
	// Si existe tecnicas_html_json (HTML pregenerado desde PHP), usarlo
	if (typeof tecnicas_html_json !== 'undefined' && tecnicas_html_json && tecnicas_html_json[estiloKey]) {
		$('#tecnicas-caja').html(tecnicas_html_json[estiloKey]);
		return;
	}
	
	// Caso especial para Elementales: combinar múltiples estilos elementales
	if (estiloKey === 'Elementales') {
		let elementosArray = ['Electro', 'Piro', 'Cryo', 'Aqua', 'Aero'];
		let hayTecnicas = false;
		let htmlCombinado = '';
		
		for (let elemento of elementosArray) {
			// Buscar por estilo elemental
			if (typeof tecnicas_html_json !== 'undefined' && tecnicas_html_json && tecnicas_html_json[elemento]) {
				htmlCombinado += tecnicas_html_json[elemento];
				hayTecnicas = true;
			} else if (tec_aprendidas_json && tec_aprendidas_json[elemento] && Array.isArray(tec_aprendidas_json[elemento])) {
				// Fallback: generar con JavaScript si no hay HTML pregenerado
				let tecElemento = tec_aprendidas_json[elemento];
				for (let i = 0; i < tecElemento.length; i++) {
					crearTecnica(tecElemento[i]);
					hayTecnicas = true;
				}
			}
		}
		
		if (htmlCombinado) {
			$('#tecnicas-caja').html(htmlCombinado);
			return;
		}
		
		if (!hayTecnicas) {
			$('#tecnicas-caja').html('<div style="text-align: center; padding: 20px;">No hay técnicas elementales aprendidas</div>');
		}
		
		TF.initFiltros();
		TF.aplicarFiltros();
		return;
	}
	
	// Fallback: usar el sistema antiguo con tec_aprendidas_json
	// Verificar que el estilo existe y tiene técnicas
	if (!tec_aprendidas_json || !tec_aprendidas_json[estiloKey]) {
		// Intentar buscar ramas que coincidan con este estilo
		let hayTecnicas = false;
		for (let rama in tec_aprendidas_json) {
			if (rama === 'todo') continue;
			if (tec_aprendidas_json[rama] && Array.isArray(tec_aprendidas_json[rama]) && tec_aprendidas_json[rama].length > 0) {
				// Verificar si las técnicas de esta rama pertenecen al estilo buscado
				let primeraTecnica = tec_aprendidas_json[rama][0];
				if (primeraTecnica.estilo === estiloKey) {
					for (let i = 0; i < tec_aprendidas_json[rama].length; i++) {
						crearTecnica(tec_aprendidas_json[rama][i]);
						hayTecnicas = true;
					}
				}
			}
		}
		
		if (!hayTecnicas) {
			$('#tecnicas-caja').html('<div style="text-align: center; padding: 20px;">No hay técnicas disponibles para este estilo</div>');
			console.log('No se encontraron técnicas para el estilo:', estiloKey);
		}
		return;
	}
	
	let estiloTecs = tec_aprendidas_json[estiloKey];
	
	// Verificar que estiloTecs es un array
	if (!Array.isArray(estiloTecs) || estiloTecs.length === 0) {
		$('#tecnicas-caja').html('<div style="text-align: center; padding: 20px;">No hay técnicas aprendidas en este estilo</div>');
		console.log('El estilo no contiene un array de técnicas válido:', estiloKey);
		return;
	}
	
	for (let i = 0; i < estiloTecs.length; i++) {
		crearTecnica(estiloTecs[i]);
	}
	
	TF.initFiltros();
	TF.aplicarFiltros();
}

function crearTecnica(tecnica) {
		let requisitos = tecnica.requisitos ? (`<div class="tecnica_requisito">` + tecnica.requisitos + `</div>`) : ``;
	
		let tecnicaEnergia = '';
		let tecnicaEnergiaTurno = '';
		let tecnicaHaki = '';
		let tecnicaHakiTurno = '';
		let tecnicaEnfriamiento = '';

		let isPasiva = tecnica.clase == 'Pasiva';
		let pasivaBlock = `background-color: #f24a01;`;
		let pasivaGradient = `linear-gradient(180deg,#f26525 50%,#f48a5d 100%);`;
		let pasivaTextGradient = `linear-gradient(90deg, rgba(0, 116, 143, 0) 0%, #d15924 20%, #812f09 50%, #d15924 80%, rgba(0, 116, 143, 0) 100%)`;
// 
// 
	
		if (tecnica.energia) {
			tecnicaEnergia = `
				<div style=" display: flex; flex-direction: row; " title="Costo de Energía">
					<div style=" font-size: 26px; font-family: lemonMilkMedium; color: #ffa100; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; ">` + tecnica.energia + `</div>
					<img src="/images/op/uploads/Energia_One_Piece_Gaiden_Foro_Rol.png" style="height: 27px;margin-top: 5px;" alt="Costo de Energía" /> 
				</div>`;
		}
	
		if (tecnica.energia_turno) {
			tecnicaEnergiaTurno = `
				<div style=" display: flex; flex-direction: row; " title="Costo de Energía por Turno">
					<div style=" font-size: 26px; font-family: lemonMilkMedium; color: #ffa100; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; ">` + tecnica.energia_turno + `</div>
					<img src="/images/op/uploads/EnergiaPorTurno_One_Piece_Gaiden_Foro_Rol.png" style="height: 27px;margin-top: 5px;" alt="Costo de Energía por Turno" /> 
				</div>`;
		}
	
		if (tecnica.haki) {
			tecnicaHaki = `
				<div style=" display: flex; flex-direction: row; " title="Costo de Haki">
					<div style=" font-size: 26px; font-family: lemonMilkMedium; color: #21DEFF; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; ">` + tecnica.haki + `</div>
					<img src="/images/op/uploads/PuntosHaki_One_Piece_Gaiden_Foro_Rol.png" style="height: 27px;margin-top: 5px;" alt="Costo de Energía" /> 
				</div>`;
		}
	
		if (tecnica.haki_turno) {
			tecnicaHakiTurno = `
				<div style=" display: flex; flex-direction: row; " title="Costo de Haki por Turno">
					<div style=" font-size: 26px; font-family: lemonMilkMedium; color: #21DEFF; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; ">` + tecnica.haki_turno + `</div>
					<img src="/images/op/uploads/PuntosHakiMantenido_One_Piece_Gaiden_Foro_Rol.png" style="height: 27px;margin-top: 5px;" alt="Costo de Energía por Turno" /> 
				</div>`;
		}
	
	if (tecnica.enfriamiento) {
		tecnicaEnfriamiento = `
			<div style=" display: flex; flex-direction: row; " title="Enfriamiento">
				<div style=" font-size: 26px; font-family: lemonMilkMedium; color: #D8EC13; -webkit-text-stroke-width: 1px; -webkit-text-stroke-color: black; text-shadow: 1px 1px 2px black; ">` + tecnica.enfriamiento + `</div>

				<img src="/images/op/uploads/CD_One_Piece_Gaiden_Foro_Rol.png" style="height: 30px;margin-top: 4px;" alt="Enfriamiento" />
			</div>`;
	}
	
		let tecnicaNombre = `[` + tecnica.tid + `] ` + tecnica.nombre;
	
		let generalTecs = `
		<div class="tecnica_spoiler" style=" width: 96%; margin: 5px auto; ">
			<div class="spoiler_title">
				<span class="tecnica_spoiler_button" style="${isPasiva ? pasivaBlock : ''}" onclick="javascript: if(parentNode.parentNode.getElementsByTagName('div')[1].style.height == parentNode.parentNode.querySelector('.tecnica_spoiler_content2').offsetHeight + 'px'){ parentNode.parentNode.getElementsByTagName('div')[1].style.height = '0px'; this.innerHTML='` + tecnicaNombre + `';parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'hidden';sleep(150).then(() => { parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'hidden'; });} else { parentNode.parentNode.getElementsByTagName('div')[1].style.height = parentNode.parentNode.querySelector('.tecnica_spoiler_content2').offsetHeight + 'px'; this.innerHTML='` + tecnicaNombre + `'; parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'hidden';sleep(150).then(() => { parentNode.parentNode.querySelector('.tecnica_spoiler_content').style.overflow = 'visible'; });}">` + tecnicaNombre + `</span>
			</div>

		  <div class="tecnica_spoiler_content" style="height: 0px;${isPasiva ? `background: ${pasivaGradient};` : ''}">

			<div class="tecnica_spoiler_content2" style='display: flex;flex-direction: row;'>

				<div style="display: flex; flex-direction: column;width: 20%; margin-right: 26px;">
					<div class="tecnica_field" style="${isPasiva ? `background: ${pasivaTextGradient};` : ''}">` + tecnica.tid + `</div>
					<div class="tecnica_field" style="${isPasiva ? `background: ${pasivaTextGradient};` : ''}">` + tecnica.rama + `</div>
					<div class="tecnica_field" style="${isPasiva ? `background: ${pasivaTextGradient};` : ''}">` + tecnica.clase + `</div>
					<div class="tecnica_field" style="${isPasiva ? `background: ${pasivaTextGradient};` : ''}">Tier ` + tecnica.tier + `</div>
					<div class="tecnica_field" style="${isPasiva ? `background: ${pasivaTextGradient};` : ''}">` + new Date(tecnica.tiempo).toLocaleDateString('es-ES') + `</div>

					<div style="display: flex;flex-direction: row;justify-content: space-around;margin-top: 7px;width: 160px;">
						` + tecnicaEnergia + `
						` + tecnicaEnergiaTurno + `
						` + tecnicaHaki + `
						` + tecnicaHakiTurno + `
						` + tecnicaEnfriamiento + `
					</div>

				</div>

				<div style="display: flex; flex-direction: column;width: 80%;">
					` + requisitos + `
					<div class="tecnica_descripcion">` + tecnica.descripcion + `</div>
					<div class="tecnica_efecto">` + tecnica.efectos + `</div>
				</div>

			</div>

		  </div>

		</div>				
	`;
	
	
	$('#tecnicas-caja').append(generalTecs);
}

// <div class="tecnica_field" style="${isPasiva ? `background: ${pasivaTextGradient};` : ''}">` + tecnica.estilo + `</div>

// Función para abrir el modal de estilos
function openEstilosModal(estiloSlot) {
    console.log('Abriendo modal para slot:', estiloSlot);
    
    // Verificar que el modal no exista ya
    const existingModal = document.getElementById('estilosModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Crear el modal dinámicamente
    const modal = createEstilosModal(estiloSlot);
    document.body.appendChild(modal);
    
    // Mostrar el modal
    modal.style.display = 'block';
    
    // Cerrar modal al hacer clic fuera
    modal.onclick = function(event) {
        if (event.target === modal) {
            closeEstilosModal();
        }
    };
}

// Función para crear el modal de estilos
function createEstilosModal(estiloSlot) {
    const modal = document.createElement('div');
    modal.id = 'estilosModal';
    modal.className = 'modal';
    modal.style.cssText = 'position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); display: none;';
    
    const modalContent = document.createElement('div');
    modalContent.className = 'estilos-modal-content';
    
    const titulo = document.createElement('h2');
    titulo.style.cssText = 'text-align: center; margin-bottom: 20px; font-family: moonGetHeavy; color: #333; font-size: 24px;';
    titulo.textContent = 'Seleccionar Estilo de Combate';
    
    const closeBtn = document.createElement('span');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'position: absolute; top: 10px; right: 25px; font-size: 35px; font-weight: bold; color: #999; cursor: pointer; transition: color 0.3s;';
    closeBtn.onclick = closeEstilosModal;
    closeBtn.onmouseover = function() { this.style.color = '#000'; };
    closeBtn.onmouseout = function() { this.style.color = '#999'; };
    
    const estilosGrid = document.createElement('div');
    estilosGrid.className = 'estilos-grid';
    
    // Verificar qué estilos ya están aprendidos
    const estilosAprendidos = [estilo1, estilo2, estilo3, estilo4].filter(e => e && e !== '');
	
	let isCipherPol = faccion == 'CipherPol';
	let isRevolucionario = faccion == 'Revolucionario';
	let isGyojin = raza == 'Gyojin' || raza == 'Ningyo' || raza == 'Woko' || raza == 'Hafugyo' || raza == 'Wotan';
		
    const estilosData = [
		{
            id: 'Gunkata',
            nombre: 'Gunkata',
            // tipo: 'Física',
            imagen: '/images/op/uploads/FichaGunkata_One_Piece_Gaiden_Foro_Rol.webp',
            aprendido: estilosAprendidos.includes('Gunkata'),
            disponible: true,
            tipoColor: '#ff4444'
        },
		{
			id: 'Hasshoken',
			nombre: 'Hasshoken',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaHasshoken_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Hasshoken'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
            id: 'Santoryu',
            nombre: 'Santoryu',
            // tipo: 'Espada',
            imagen: '/images/op/uploads/FichaSantoryu_One_Piece_Gaiden_Foro_Rol.webp',
            aprendido: estilosAprendidos.includes('Santoryu'),
            disponible: true,
            tipoColor: '#44ff44'
        },
		{
			id: 'Kuroashi',
			nombre: 'Kuroashi',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaKuroashi_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Kuroashi'),
			disponible: true,
			tipoColor: '#ff4444'
   		},
		{
			id: 'Gyojin Karate',
			nombre: 'Gyojin Karate',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaGyojin Karate_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Gyojin Karate'),
			disponible: isGyojin,
			tipoColor: '#ff4444'
  	    },
		{
			id: 'Gyojin Bukijutsu',
			nombre: 'Gyojin Bukijutsu',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaGyojin Bukijutsu_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Gyojin Bukijutsu'),
			disponible: isGyojin,
			tipoColor: '#ff4444'
  	    },
		{
			id: 'Gyojin Jujutsu',
			nombre: 'Gyojin Jujutsu',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaGyojin Jujutsu_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Gyojin Jujutsu'),
			disponible: isGyojin,
			tipoColor: '#ff4444'
  	    },
        {
            id: 'Rokushiki',
            nombre: 'Rokushiki',
            // tipo: 'Física',
            imagen: '/images/op/uploads/FichaRokushiki_One_Piece_Gaiden_Foro_Rol.webp',
            aprendido: estilosAprendidos.includes('Rokushiki'),
            disponible: isCipherPol,
            tipoColor: '#ff4444'
        },
		{
			id: 'Okama Kempo',
			nombre: 'Okama Kempo',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaOkama Kempo_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Okama Kempo'),
			disponible: true,
			tipoColor: '#ff4444'
    	},
		{
			id: 'Sora Yokujin',
			nombre: 'Sora Yokujin',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaSora Yokujin_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Sora Yokujin'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Ninjutsu',
			nombre: 'Ninjutsu',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaNinjutsu_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Ninjutsu'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Ryusoken',
			nombre: 'Ryusoken',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaRyusoken_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Ryusoken'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Jiyuumura Kempo',
			nombre: 'Jiyuumura Kempo',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaJiyuumura Kempo_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Jiyuumura Kempo'),
			disponible: isRevolucionario,
			tipoColor: '#ff4444'
		},
		{
			id: 'Pop Green',
			nombre: 'Pop Green',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaPop Green_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Pop Green'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Clima Tact',
			nombre: 'Clima Tact',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaClima Tact_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Clima Tact'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Funekiri',
			nombre: 'Funekiri',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaFunekiri_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Funekiri'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Hakai Shin',
			nombre: 'Hakai Shin',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaHakai Shin_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Hakai Shin'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Railgun Style',
			nombre: 'Railgun Style',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaRailgun Style_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Railgun Style'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Shuron Hakke',
			nombre: 'Shuron Hakke',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaShuron Hakke_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Shuron Hakke'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Raqisat Alsahra',
			nombre: 'Raqisat Alsahra',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaRaqisat Alsahra_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Raqisat Alsahra'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Breeskjold',
			nombre: 'Breeskjold',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaBreeskjold_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Breeskjold'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Impacto Explosivo',
			nombre: 'Impacto Explosivo',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaImpacto Explosivo_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Impacto Explosivo'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Royal Guard',
			nombre: 'Royal Guard',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaRoyal Guard_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Royal Guard'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Shikaku Teikoku',
			nombre: 'Shikaku Teikoku',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaShikaku Teikoku_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Shikaku Teikoku'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Duelliste de Givre',
			nombre: 'Duelliste de Givre',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaDuelliste de Givre_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Duelliste de Givre'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Filo Della Vita',
			nombre: 'Filo Della Vita',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaFilo Della Vita_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Filo Della Vita'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Havets Symfoni',
			nombre: 'Havets Symfoni',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaHavets Symfoni_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Havets Symfoni'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Bakudai Karin',
			nombre: 'Bakudai Karin',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaBakudai Karin_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Bakudai Karin'),
			disponible: true,
			tipoColor: '#ff4444'
		},		
		
		
		{
			id: 'Yama Kurai',
			nombre: 'Yama Kurai',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaYama Kurai_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Yama Kurai'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Shiseiju',
			nombre: 'Shiseiju',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaShiseiju_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Shiseiju'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Sea Corsair',
			nombre: 'Sea Corsair',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaSea Corsair_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Sea Corsair'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Wano Nitoryu',
			nombre: 'Wano Nitoryu',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaWano Nitoryu_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Wano Nitoryu'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Mano de Tahur',
			nombre: 'Mano de Tahur',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaMano de Tahur_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Mano de Tahur'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Kokudan',
			nombre: 'Kokudan',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaKokudan_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Kokudan'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Kodai no Bushido',
			nombre: 'Kodai no Bushido',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaKodai no Bushido_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Kodai no Bushido'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Ittoryu Sekai',
			nombre: 'Ittoryu Sekai',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaIttoryu Sekai_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Ittoryu Sekai'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		
		{
			id: 'Global Performer',
			nombre: 'Global Performer',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaGlobal Performer_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Global Performer'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Cavalry Warrior',
			nombre: 'Cavalry Warrior',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaCavalry Warrior_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Cavalry Warrior'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Ashigara Dokoi',
			nombre: 'Ashigara Dokoi',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaAshigara Dokoi_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Ashigara Dokoi'),
			disponible: true,
			tipoColor: '#ff4444'
		},
		{
			id: 'Kanpo Kenpo',
			nombre: 'Kanpo Kenpo',
			// tipo: 'Física',
			imagen: '/images/op/uploads/FichaKanpo Kenpo_One_Piece_Gaiden_Foro_Rol.webp',
			aprendido: estilosAprendidos.includes('Kanpo Kenpo'),
			disponible: true,
			tipoColor: '#ff4444'
		}
    ];
	
    for (var i = 0; i < estilosData.length; i++) {
        const estiloCard = createEstiloCard(estilosData[i], estiloSlot);
        estilosGrid.appendChild(estiloCard);
    }

    // Ensamblar modal
    modalContent.appendChild(closeBtn);
    modalContent.appendChild(titulo);
    modalContent.appendChild(estilosGrid);
    modal.appendChild(modalContent);

    return modal;
}

function createEstiloCard(estilo, estiloSlot) {
    const card = document.createElement('div');
    card.className = 'estilo-card';
    
    if (!estilo.disponible || estilo.aprendido) {
        card.classList.add('estilo-disabled');
    }

    // Contenedor de imagen
    const imagenContainer = document.createElement('div');
    imagenContainer.className = 'estilo-imagen-container';
    
    const imagen = document.createElement('img');
    imagen.src = estilo.imagen;
    imagen.alt = estilo.nombre;
    imagen.className = 'estilo-imagen';
    imagen.style.cssText = 'width: 100%; height: 100%; object-fit: cover; object-position: center;';
    
    imagenContainer.appendChild(imagen);

    // Información del estilo
    const info = document.createElement('div');
    info.className = 'estilo-info';
    
//     const nombre = document.createElement('div');
//     nombre.className = 'estilo-nombre';
//     nombre.textContent = estilo.nombre;
    
//     const tipo = document.createElement('div');
//     tipo.className = 'estilo-tipo';
//     tipo.style.backgroundColor = estilo.tipoColor;
//     tipo.textContent = estilo.tipo;
    
//     info.appendChild(nombre);
//     info.appendChild(tipo);

    // Indicador si ya está aprendido
    if (estilo.aprendido) {
        const yaAprendido = document.createElement('div');
        yaAprendido.className = 'estilo-ya-aprendido';
        yaAprendido.textContent = 'Ya Aprendido';
        card.appendChild(yaAprendido);
    }

    // Evento de clic para seleccionar estilo
    if (estilo.disponible && !estilo.aprendido) {
        card.style.cursor = 'pointer';
        card.onclick = function() {
            seleccionarEstilo(estilo.id, estiloSlot);
        };
    }

    card.appendChild(imagenContainer);
    card.appendChild(info);
    
    return card;
}

function seleccionarEstilo(estiloId, estiloSlot) {
    console.log('Seleccionado estilo ' + estiloId + ' para slot ' + estiloSlot);
    
    if (confirm('¿Estás seguro de que quieres aprender el estilo ' + estiloId + '?')) {
        // Aquí puedes redirigir a tu script de guardado
        window.location.href = '/op/mejoras.php?accion=estilo&estilo=' + estiloId + '&slot=' + estiloSlot;
    }
    
    // closeEstilosModal();
}

// Función para cerrar el modal de estilos
function closeEstilosModal() {
    const modal = document.getElementById('estilosModal');
    if (modal) {
        modal.style.display = 'none';
        setTimeout(function() {
            if (modal.parentNode) {
                modal.parentNode.removeChild(modal);
            }
        }, 300);
    }
}

// Cerrar modal con la tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEstilosModal();
        closeWantedModal();
        closeHistorialModal();
    }
});

// Función auxiliar para obtener datos de estilos desde el servidor (opcional)
function cargarEstilosDisponibles() {
    // Implementar llamada AJAX para obtener estilos disponibles
    // según el nivel, disciplinas, etc. del personaje
    
    /*
    return fetch('/get_estilos_disponibles.php')
        .then(response => response.json())
        .then(data => data.estilos)
        .catch(error => {
            console.error('Error cargando estilos:', error);
            return [];
        });
    */
}

function clickRasgos(tipo) {
	$('.rasgos_box').removeClass('rasgos-active');

	if (tipo == 'virtudes') {
		$('#rasgos_virtudes').css('display', 'block');
		$('#rasgos_defectos').css('display', 'none');
		$('#virtudes_box').addClass('rasgos-active');
	}

	if (tipo == 'defectos') {
		$('#rasgos_virtudes').css('display', 'none');
		$('#rasgos_defectos').css('display', 'block');
		$('#defectos_box').addClass('rasgos-active');
	}
}		
function openAvatar1Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">Esta imagen también cambiará la de los post. (O indicar que no afectará a la de los posts o perfil, según cuál sea). La imagen tiene unas dimensiones de 250x450.</div>

				<input id="avatar1URL" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar1();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	

function openAvatar2Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">La imagen tiene unas dimensiones de 250x350.</div>

				<input id="avatar2URL" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar2();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	

function openAvatar1S1Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">Esta imagen también cambiará la de los post. (O indicar que no afectará a la de los posts o perfil, según cuál sea). La imagen tiene unas dimensiones de 250x450.</div>

				<input id="avatar1URLS1" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar1S1();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	

function openAvatar2S1Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">La imagen tiene unas dimensiones de 250x350.</div>

				<input id="avatar2URLS1" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar2S1();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}
	
function openAvatar3Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">La imagen tiene unas dimensiones de 580x280.</div>

				<input id="avatar3URL" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar3();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	
	
function openAvatar4Modal() {
	if (is_owner || g_is_staff) {
	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">La imagen tiene unas dimensiones de 300x300.</div>

				<input id="avatar4URL" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar4();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	
	
function openAvatar5Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">La imagen tiene unas dimensiones de 250x330.</div>

				<input id="avatar5URL" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar5();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	
function openAvatar5Modal() {
	if (is_owner || g_is_staff) {

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 250px; width: 620px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style=" font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px; ">Editar Imagen</div>
				<div style="margin-top: 10px;font-size: 18px;text-align: justify;">La imagen tiene unas dimensiones de 250x330.</div>

				<input id="avatar5URL" type="text" class="textbox" style="width: 540px;margin: auto;margin-top: 20px;" placeholder="URL de la imagen">
				<button onclick="cambiarAvatar5();" style=" width: 100px; margin: auto; margin-top: 20px; ">Guardar</button>
			</div>
		</div>
	`);
	modal.style.display = "block";
	}
}	

function openAparienciaModal() {
	let textBoxModal = '';
	if (is_owner || g_is_staff) {
		textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="aparienciaText" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 288px;">${apariencia2}</textarea>
			<button onclick="cambiarApariencia();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	} else {
	 	textBoxModal = `<div style=" width: 787px;height: 340px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${apariencia1}</div>
		</div>`;

	}
	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 400px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Apariencia</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}

function openAparienciaS1Modal() {
	let textBoxModal = '';
	if (is_owner || g_is_staff) {
		textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="aparienciaTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 488px;">${ficha_secret1.apariencia}</textarea>
			<button onclick="cambiarAparienciaS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	} else {
	 	textBoxModal = `<div style=" width: 787px;height: 540px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${ficha_secret1.apariencia}</div>
		</div>`;

	}
	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 600px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Apariencia</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}
	
function openPersonalidadModal() {
	
	let textBoxModal = '';
	if (is_owner || g_is_staff) {
	 	textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="personalidadText" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 288px;">${personalidad2}</textarea>
			<button onclick="cambiarPersonalidad();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	} else {

		textBoxModal = `<div style=" width: 787px;height: 340px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${personalidad1}</div>
		</div>`;
	}

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 400px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Personalidad</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}

function openPersonalidadS1Modal() {
	
	let textBoxModal = '';
	if (is_owner || g_is_staff) {

	 	textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="personalidadTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 488px;">${ficha_secret1.personalidad}</textarea>
			<button onclick="cambiarPersonalidadS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	} else {
		textBoxModal = `<div style=" width: 787px;height: 540px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${ficha_secret1.personalidad}</div>
		</div>`;
	}

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 600px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Personalidad</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}
	
function openHistoriaModal() {
	let textBoxModal = '';
	if (is_owner || g_is_staff) {
		textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="historiaText" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 488px;">${historia2}</textarea>
			<button onclick="cambiarHistoria();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;
		

	} else {
		textBoxModal = `<div style=" width: 787px;height: 540px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${historia1}</div>
		</div>`;
	}
	
	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 600px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Historia</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}

function openHistoriaS1Modal() {
	let textBoxModal = '';
	if (is_owner || g_is_staff) {
		textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="historiaTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 488px;">${ficha_secret1.historia}</textarea>
			<button onclick="cambiarHistoriaS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;
		

	} else {
		textBoxModal = `<div style=" width: 787px;height: 540px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${ficha_secret1.historia}</div>
		</div>`;
	}
	
	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 600px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Historia</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}

function openVisibleS1Modal() {
	if (g_is_staff) {

		let textBoxModal = `
			<div style=" text-align: center; ">
				<div id="visibleTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 19px;">Visibilidad Actual: ${ficha_secret1.es_visible ? 'Sí' : 'No'}</div>
				<button onclick="cambiarVisibleS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Cambiar</button>
			</div>`;

		$('#myModal').html(`
			<div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 128px; width: 820px; ">
				<div class="modal-body" style=" display: flex; flex-direction: column; ">
					<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">¿Es Visible Tu Identidad?</div>
					` + textBoxModal + `
				</div>
			</div>
		`);

		modal.style.display = "block";
	}
}

function openBandaSonoraModal() {
    if (is_owner || g_is_staff) {

    $('#myModal').html(`
        <div class="modal-content" style="background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 300px; width: 620px;">
            <div class="modal-body" style="display: flex; flex-direction: column;">
                <div style="font-size: 20px; text-align: center; font-family: moonGetHeavy; color: black; margin-top: 10px;">Editar Banda Sonora</div>
                <div style="margin-top: 10px; font-size: 18px; text-align: justify;">
                    Ingresa un enlace de YouTube. Esta canción se reproducirá automáticamente cuando alguien visite tu ficha.
                </div>
                <input id="bandaSonoraURL" type="text" class="textbox" style="width: 540px; margin: auto; margin-top: 20px;" 
                       placeholder="https://www.youtube.com/watch?v=..." 
                       value="${banda_sonora}">
                <div style="margin: auto; margin-top: 15px; display: flex; gap: 10px;">
                    <button onclick="cambiarBandaSonora();" style="width: 100px;">Guardar</button>
                    <button onclick="probarSonido();" style="width: 100px;">Probar</button>
                </div>
                <div style="margin-top: 15px; font-size: 14px; text-align: center; font-style: italic;">
                    Formatos aceptados: enlaces de YouTube (youtube.com/watch?v=ID o youtu.be/ID)
                </div>
            </div>
        </div>
    `);
    modal.style.display = "block";
	}
}


function openExtrasModal() {
	let textBoxModal = '';
	
	if (is_owner || g_is_staff) {
		textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="extrasText" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 288px;">${extras2}</textarea>
			<button onclick="cambiarExtras();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	} else {
		textBoxModal = `<div style=" width: 787px;height: 340px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${extras1}</div>
		</div>`;

	}

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 400px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Extras</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}

function openExtrasS1Modal() {
	let textBoxModal = '';
	
	if (is_owner || g_is_staff) {
		textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="extrasTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 488px;">${ficha_secret1.extra}</textarea>
			<button onclick="cambiarExtrasS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	} else {
		textBoxModal = `<div style=" width: 787px;height: 540px; background-color: white; border: 5px solid #ff7e00; box-sizing: border-box; overflow: auto;">
			<div style=" font-family: InterRegular; margin: 3px; font-size: 14px; ">${ficha_secret1.extra}</div>
		</div>`;

	}

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 600px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Extras</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	
	modal.style.display = "block";
}

function openApodoModal() {

	if (is_owner || g_is_staff) {

	let textBoxModal = `
		<div style=" text-align: center; ">
			<textarea id="apodoText" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 19px;">${apodo}</textarea>
			<button onclick="cambiarApodo();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 128px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Apodo</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	modal.style.display = "block";
	}
}

function openNombreS1Modal() {

	if (is_owner || g_is_staff) {

	let textBoxModal = `
		<div style=" text-align: center; ">
			<input id="nombreTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 28px;" placeholder="${ficha_secret1.nombre}">
			<button onclick="cambiarNombreS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 128px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Nombre</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	modal.style.display = "block";
	}
}

function openRangoS1Modal() {
    if (g_is_staff) {
        const rangosPorFaccion = {
            "Civil": {
                "Civil": "Ciudadano" 
            },
            "Pirata": {
                "Pirata": "Pirata",
                "Capitán Pirata": "CapitanPirata",
                "Corsario": "Corsario",
                "Bucanero": "Bucanero",
                "Lobo de Mar": "LoboDeMar",
                "Supernova": "Supernova",
                "Pirata Afamado": "PirataAfamado",
                "Vice Capitán Famoso": "ViceCapitanFamoso",
                "Capitán Famoso": "CapitanFamoso",
                "Shichibukai": "Shichibukai",
                "Gran Pirata": "GranPirata",
                "Gran Vice Capitán": "GranViceCapitan",
                "Gran Capitán": "GranCapitan",
                "Comandante de Yonkou": "ComandanteP",
                "Primer Comandante de Yonkou": "PrimerComandante",
                "Yonkou": "Yonkou",
                "Leyenda del Mar": "LeyendaDelMar",
                "Ala del Rey": "AladelRey",
                "Rey de los Piratas": "ReyPirata"
            },
            "Marine": {
                "Recluta": "ReclutaM",
                "Soldado Raso": "SoldadoM",
                "Sargento": "SargentoM",
                "Suboficial": "Suboficial",
                "Alferez": "Alferez",
                "Teniente": "Teniente",
                "Comandante": "ComandanteM",
                "Capitán": "Capitan",
                "Comodoro": "Comodoro",
                "Contralmirante": "ContraAlmirante",
                "Vice Almirante": "Vicealmirante",
                "Almirante": "Almirante",
                "Almirante de Flota": "AlmiranteFlota",
                "Instructor": "Instructor",
                "Inspector General": "Inspector",
                "Heroe de la Marina": "HeroeDeLaMarina"
            },
            "CipherPol": {
                "Cipher Pol 1": "CP1",
                "Cipher Pol 2": "CP2",
                "Cipher Pol 3": "CP3",
                "Cipher Pol 4": "CP4",
                "Cipher Pol 5": "CP5",
                "Cipher Pol 6": "CP6",
                "Cipher Pol 7": "CP7",
                "Cipher Pol 8": "CP8",
                "Cipher Pol 9": "CP9",
                "Cipher Pol 0": "CPAegis0",
                "Cipher Pol Masquerade": "CPMasquerade",
                "Comisario": "CPComisario",
                "Comandante Ejecutivo": "CPComandanteEjecutivo",
                "Caballero Divino": "CPCaballeroDivino",
                "Comandante Supremo": "CPComandanteSupremo"
            },
            "Cazador": {
                "Cazador": "Cazador",
                "Cazador Zeta": "CazadorZeta",
                "Cazador Epsilon": "CazadorEpsilon",
                "Cazador Delta": "CazadorDelta",
                "Cazador Gamma": "CazadorGamma",
                "Cazador Beta": "CazadorBeta",
                "Cazador Alpha": "CazadorAlpha",
                "Cazador Omega": "CazadorOmega",
                "Rey de los Cazadores": "ReyCazador"
            },
            "Revolucionario": {
                "Recluta": "ReclutaR",
                "Soldado Raso": "SoldadoR",
                "Sargento": "SargentoR",
                "Agente": "AgenteR",
                "Oficial": "Oficial",
                "Mariscal Revolucionario": "Mariscal",
                "General": "General",
                "Comandante Adjunto": "ComandanteAdjunto",
                "Comandante Revolucionario": "ComandanteR",
                "Jefe de Personal": "JefePersonal",
                "Comandante Supremo": "ComandanteSupremo"
            }
        };

        let faccionGuardada = ficha_secret1.faccion ? ficha_secret1.faccion.trim() : "";
        
        if (faccionGuardada === "Gob. Mundial") {
            faccionGuardada = "CipherPol";
        }

        let opcionesRango = rangosPorFaccion[faccionGuardada] || { "": "" };

        let selectOptions = '';
        for (const [valorEnvio, valorEnBD] of Object.entries(opcionesRango)) {
            let isSelected = (ficha_secret1.rango === valorEnBD) ? 'selected="selected"' : '';
            selectOptions += `<option value="${valorEnBD}" ${isSelected}>${valorEnvio}</option>`;
        }

        let textBoxModal = `
            <div style="text-align: center;">
                <select id="rangoTextS1" style="font-family: InterRegular; margin: 3px; font-size: 14px; width: 765px; height: 30px; cursor: pointer;">
                    ${selectOptions}
                </select>
                <button onclick="cambiarRangoS1();" style="width: 100px; margin: auto; margin-top: 8px; cursor: pointer;">Guardar</button>
            </div>`;

        $('#myModal').html(`
            <div class="modal-content" style="background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 140px; width: 820px;">
                <div class="modal-body" style="display: flex; flex-direction: column;">
                    <div style="font-size: 20px; text-align: center; font-family: interRegular; margin-top: 10px;">Rango</div>
                    ${textBoxModal}
                </div>
            </div>
        `);
        
        modal.style.display = "block";
    }
}

function openFaccionS1Modal() {
    if (g_is_staff) {
        const opcionesFaccion = {
            "Civil": "Civil",
            "Pirata": "Pirata",
            "Marine": "Marine",
            "Gob. Mundial": "Gob. Mundial",
            "Cazador": "Cazador",
            "Revolucionario": "Revolucionario"
        };

        let faccionGuardada = ficha_secret1.faccion ? ficha_secret1.faccion.trim() : "";
        
        if (faccionGuardada === "CipherPol") {
            faccionGuardada = "Gob. Mundial"; 
        }

        let selectOptions = '';
        for (const [valorEnvio, textoVisible] of Object.entries(opcionesFaccion)) {
            let isSelected = (faccionGuardada === valorEnvio) ? 'selected="selected"' : '';
            selectOptions += `<option value="${valorEnvio}" ${isSelected}>${textoVisible}</option>`;
        }

        let textBoxModal = `
            <div style="text-align: center;">
                <select id="faccionTextS1" style="font-family: InterRegular; margin: 3px; font-size: 14px; width: 765px; height: 30px; cursor: pointer;">
                    ${selectOptions}
                </select>
                <button onclick="cambiarFaccionS1();" style="width: 100px; margin: auto; margin-top: 8px; cursor: pointer;">Guardar</button>
            </div>`;

        $('#myModal').html(`
            <div class="modal-content" style="background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 140px; width: 820px;">
                <div class="modal-body" style="display: flex; flex-direction: column;">
                    <div style="font-size: 20px; text-align: center; font-family: interRegular; margin-top: 10px;">Facción</div>
                    ${textBoxModal}
                </div>
            </div>
        `);

        modal.style.display = "block";
    }
}

function openApodoS1Modal() {

	if (is_owner || g_is_staff) {

	let textBoxModal = `
		<div style=" text-align: center; ">
			<input id="apodoTextS1" style="font-family: InterRegular;margin: 3px;font-size: 14px;width: 765px;height: 28px;" placeholder="${ficha_secret1.apodo}">
			<button onclick="cambiarApodoS1();" style=" width: 100px; margin: auto; margin-top: 8px; ">Guardar</button>
		</div>`;

	$('#myModal').html(`
	    <div class="modal-content" style=" background-color: #ffedd2; border: 4px solid #ffe59b; border-radius: 8px; height: 128px; width: 820px; ">
			<div class="modal-body" style=" display: flex; flex-direction: column; ">
				<div style="font-size: 20px;text-align: center;font-family: interRegular;margin-top: 10px;">Apodo</div>
				` + textBoxModal + `
			</div>
		</div>
	`);
	
	modal.style.display = "block";
	}
}

function cambiarBandaSonora() {
    let bandaSonoraUrl = $('#bandaSonoraURL').val();
    $.post(`/op/ficha.php?uid=${query_uid}`, { cambiar_banda_sonora: bandaSonoraUrl }, function(data) { 
        location.reload(); 
    });
}

function probarSonido() {
    let bandaSonoraUrl = $('#bandaSonoraURL').val();
    if (bandaSonoraUrl) {
        // Extraer ID de YouTube
        let videoId = '';
        const youtubeRegex = [
            /youtube\.com\/watch\?v=([^&]+)/,
            /youtu\.be\/([^?]+)/,
            /youtube\.com\/embed\/([^?]+)/,
            /youtube\.com\/v\/([^?]+)/
        ];
        
        for (const regex of youtubeRegex) {
            const match = bandaSonoraUrl.match(regex);
            if (match && match[1]) {
                videoId = match[1];
                break;
            }
        }
        
        if (videoId && typeof player !== 'undefined') {
            // Guardar el ID actual para restaurarlo después
            const currentVideoId = player.getVideoData().video_id;
            
            // Reproducir el nuevo video para probar
            player.loadVideoById({
                videoId: videoId,
                startSeconds: 0
            });
            
            // Mostrar mensaje
            alert("Reproduciendo música de prueba. El video actual se restaurará al cerrar el modal.");
            
            // Restaurar el video anterior cuando se cierre el modal
            $(window).one('click', function(e) {
                if (e.target == modal) {
                    player.loadVideoById({
                        videoId: currentVideoId,
                        startSeconds: 0
                    });
                }
            });
        } else {
            alert("No se pudo extraer un ID de YouTube válido del enlace proporcionado.");
        }
    } else {
        alert("Por favor, introduce un enlace de YouTube.");
    }
}

function cambiarAvatar1() { 
	let avatarUrl = $('#avatar1URL').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarBiografia_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar1: avatarUrl }, function( data ) { location.reload(); }); 
}	
	
function cambiarAvatar2() { 
	let avatarUrl = $('#avatar2URL').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarReputacion1_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar2: avatarUrl }, function( data ) { location.reload(); }); 
}	
function cambiarAvatar3() { 
	let avatarUrl = $('#avatar3URL').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarReputacion2_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar3: avatarUrl }, function( data ) { location.reload(); }); 
}	
function cambiarAvatar4() { 
	let avatarUrl = $('#avatar4URL').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarHabilidades_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar4: avatarUrl }, function( data ) { location.reload(); }); 
}	
function cambiarAvatar5() { 
	let avatarUrl = $('#avatar5URL').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarInventario_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar5: avatarUrl }, function( data ) { location.reload(); }); 
}	
function cambiarApariencia() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_apariencia: $('#aparienciaText').val() }, function( data ) { location.reload(); }); }	
function cambiarPersonalidad() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_personalidad: $('#personalidadText').val() }, function( data ) { location.reload(); }); }	
function cambiarHistoria() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_historia: $('#historiaText').val() }, function( data ) { location.reload(); }); }	
function cambiarExtras() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_extras: $('#extrasText').val() }, function( data ) { location.reload(); }); }	
function cambiarApodo() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_apodo: $('#apodoText').val() }, function( data ) { location.reload(); }); }	

function cambiarAparienciaS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_aparienciaS1: $('#aparienciaTextS1').val() }, function( data ) { location.reload(); }); }	
function cambiarPersonalidadS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_personalidadS1: $('#personalidadTextS1').val() }, function( data ) { location.reload(); }); }	
function cambiarHistoriaS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_historiaS1: $('#historiaTextS1').val() }, function( data ) { location.reload(); }); }	
function cambiarExtrasS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_extrasS1: $('#extrasTextS1').val() }, function( data ) { location.reload(); }); }	
function cambiarApodoS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_apodoS1: $('#apodoTextS1').val() }, function( data ) { location.reload(); }); }	
function cambiarNombreS1() { 
	let nombreText = $('#nombreTextS1').val();
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_nombreS1: nombreText })
		.done(data => { 
			console.log('fichakuro OK:', data); 
			location.reload(); 
		})
		.fail(xhr => { 
			console.error('[fichakuro] backend error:', xhr.status, xhr.responseText); 
		});
}	
function cambiarRangoS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_rangoS1: $('#rangoTextS1').val() }, function( data ) { location.reload(); }); }	
function cambiarFaccionS1() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_faccionS1: $('#faccionTextS1').val() }, function( data ) { location.reload(); }); }	

function cambiarEquipamiento() { $.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_equipamiento: JSON.stringify(objetosEquipados) }, function( data ) { console.log('done'); }); }	

function cambiarVisibleS1() { 
	let isVisibleOpposite = ficha_secret1.es_visible == '0' ? '1' : '0';
	
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { 
		cambiar_visibleS1: isVisibleOpposite }, function( data ) { location.reload(); }); 
}	

function cambiarAvatar1S1() { 
	let avatarUrl = $('#avatar1URLS1').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarBiografia_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar1S1: avatarUrl }, function( data ) { location.reload(); }); 
	//$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar1S1: avatarUrl })
	//  .done(resp => { console.log('fichakuro OK:', resp); })
	//  .fail(xhr => { console.error('fichakuro FAIL:', xhr.status, xhr.responseText); });

}	
	
function cambiarAvatar2S1() { 
	let avatarUrl = $('#avatar2URLS1').val();
	if (avatarUrl == '') { avatarUrl = '/images/op/uploads/AvatarReputacion1_One_Piece_Gaiden_Foro_Rol.png'; }
	$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar2S1: avatarUrl }, function( data ) {location.reload();}); 
	//$.post(`/op/fichakuro.php?uid=${query_uid}`, { cambiar_avatar2S1: avatarUrl })
	//  .done(resp => { console.log('fichakuro OK:', resp); })
	//  .fail(xhr => { console.error('fichakuro FAIL:', xhr.status, xhr.responseText); });
}	
		
// When the user clicks on <span> (x), close the modal
function closeModal() {
	modal.style.display = "none";
}
	
function clickAkuma(id) {
	modal.style.display = "block";
}



// /\[(RES|FUE)x(\d+|\d+,\d+)]/g

// let efectosRegex = /\[(RES|FUE)x(\d+|\d+,\d+)]/g;

function getStatCodigo(codigo) {
    console.log(codigo);
    return statCodigo[codigo];
}

// let statCodigo = {
// 	RES: 'Resistencia',
// 	FUE: 'Fuerza',
// 	DES: 'Destreza',
// 	PUN: 'Puntería',
// 	AGI: 'Agilidad',
// 	REF: 'Reflejos',
// 	CAK: 'Control de Akuma',
// 	VOL: 'Voluntad'
// }

function addEfectos(descripcion) {
    for (let i = 0; i < efectosArray.length; i++) {
    
        let efecto = efectosArray[i];
        descripcion = descripcion.replace(efecto, `
            <span class="tec-tooltip"><strong>` + efectosCodigo[efecto].nombre + `</strong><span class="tec-tooltiptext">` + efectosCodigo[efecto].texto + `</span></span>
        `);
    
    }
    return descripcion;
}

function addEfectoStats(efectos) {
    let efectosRegex = /\[(RES|FUE|DES|PUN|AGI|REF|CAK|VOL|NIVEL)x(\d+|\d+,\d+)]/g;
    const efectosMatch = [...efectos.matchAll(efectosRegex)];
    
    console.log(efectosMatch);
    
    for (let i = 0; i < efectosMatch.length; i++) {
        let codigo = efectosMatch[i][0];
        let stat = efectosMatch[i][1]
        let multi = parseFloat(efectosMatch[i][2].replace(',', '.'));
        
        let statNum = statNums[efectosMatch[i][1]];
        
        let htmlTag = `
            <span class="tec-tooltip"><strong>` + (statNum * multi) + `</strong><span class="stat-tooltiptext">` + codigo + `</span></span>
        `
        efectos = efectos.replace(codigo, htmlTag);
    }
    
    for (let i = 0; i < efectosArray.length; i++) {
    
        let efecto = efectosArray[i];
        efectos = efectos.replace(efecto, `
            <span class="tec-tooltip"><strong>` + efectosCodigo[efecto].nombre + `</strong><span class="tec-tooltiptext">` + efectosCodigo[efecto].texto + `</span></span>
        `);
    
    }
    
    return efectos;
}

function openModalDesbloquearEstilo1() {
    if (nivel >= 8 && estilo1 == 'bloqueado') { 
        if (confirm('Puedes desbloquear el primer slot de estilo. Será gratuito. ¿Estás de acuerdo?')) {
            window.location.href = '/op/mejoras.php?accion=estilo1_desbloquear';
        }
    } else {
        alert('Aún no puedes desbloquear el primer estilo.\nDebes ser nivel 8 mínimo.');
    }
}

function openModalDesbloquearEstilo2() {
    if (nivel >= 16 && nikas >= 25 && estilo2 == 'bloqueado' && estilo1 != 'bloqueado') { 
        if (confirm('Puedes desbloquear el segundo slot de estilo. Deberás invertir 25 nikas. ¿Estás de acuerdo?')) {
            window.location.href = '/op/mejoras.php?accion=estilo2_desbloquear';
        }
    } else {
        alert('Aún no puedes desbloquear el segundo estilo.\nDebes ser nivel 16 y tener más de 25 nikas.');
    }
}

function openModalDesbloquearEstilo3() {	
    if (nivel >= 25 && nikas >= 50 && estilo3 == 'bloqueado' && estilo2 != 'bloqueado') {
        if (confirm('Puedes desbloquear el tercer slot de estilo. Deberás invertir 50 nikas. ¿Estás de acuerdo?')) {
            window.location.href = '/op/mejoras.php?accion=estilo3_desbloquear';
        }
    } else {
        alert('Aún no puedes desbloquear el tercer estilo.\nDebes ser nivel 25 y tener más de 50 nikas.');
    }
}

function openModalDesbloquearEstilo4() {	
    if (nivel >= 35 && nikas >= 75 && estilo4 == 'bloqueado' && estilo3 != 'bloqueado') {
        if (confirm('Puedes desbloquear el cuarto slot de estilo. Deberás invertir 75 nikas. ¿Estás de acuerdo?')) {
            window.location.href = '/op/mejoras.php?accion=estilo4_desbloquear';
        }
    } else {
        alert('Aún no puedes desbloquear el cuarto estilo.\nDebes ser nivel 35 y tener más de 75 nikas.');
    }
}
function getNoBloqueadoHtml(estiloNumero) {
    if (is_owner) {
        return `<img style="width: 260px; height: 125px; border-radius: 8px; position: relative; overflow: hidden;cursor:pointer;" onclick="openEstilosModal('estilo` + estiloNumero + `');" src="/images/op/uploads/FichaSlotLibre_One_Piece_Gaiden_Foro_Rol.webp" />`;
    } else {
        return `<img style="width: 260px; height: 125px; border-radius: 8px; position: relative; overflow: hidden;" src="/images/op/uploads/FichaSlotLibre_One_Piece_Gaiden_Foro_Rol.webp" />`;
    }
}

function getBloqueadoHtml(estiloNumero) {
    if (is_owner) {
        return `<img style="width: 260px; height: 125px; border-radius: 8px; position: relative; overflow: hidden;cursor:pointer;" onclick="openModalDesbloquearEstilo` + estiloNumero + `();" src="/images/op/uploads/FichaSlotBloqueado_One_Piece_Gaiden_Foro_Rol.webp" />`;
    } else {
        return `<img style="width: 260px; height: 125px; border-radius: 8px; position: relative; overflow: hidden;" src="/images/op/uploads/FichaSlotBloqueado_One_Piece_Gaiden_Foro_Rol.webp" />`;
    }
}

function getEstiloHtml(estilo) {
    return `<img style="width: 260px; height: 125px; border-radius: 8px; position: relative; overflow: hidden;" src="/images/op/uploads/Ficha` + estilo + `_One_Piece_Gaiden_Foro_Rol.webp" />`
}

function chooseEstilo(estilo) {
    if (confirm('¿Estás seguro que quieres seleccionar el estilo ' + estilo + '?')) {
        window.location.href = '/op/mejoras.php?accion=estilo&estilo=' + estilo;
    }
}

/* HAKI */

function subirHaki(haki) { 
	window.location.href = '/op/mejoras.php?accion=' + haki;
}

/* DISCIPLINAS CAMINOS */

function subirBelica(belica, belicaNumber) { 
	window.location.href = `/op/mejoras.php?accion=belica&belica=` + belica + `&belicaNumber=` + belicaNumber;
}

function subirCamino(espeNumber, belicaNumber, camino) { 																						
	window.location.href = `/op/mejoras.php?accion=belica_espe&espeNumber=` + espeNumber + `&belicaNumber=` + belicaNumber + `&espe=` + camino;
}

function chooseCamino(belica, camino) {
	
	if (!is_owner) { return; }

	// Validar que la disciplina existe
	if (!belicas[belica]) {
		alert(`Error: La disciplina ${belica} no es válida.`);
		return;
	}

	// Validar que el camino existe en la disciplina
	if (!belicas[belica].sub || !belicas[belica].sub.hasOwnProperty(camino)) {
		alert(`Error: El camino ${camino} no está disponible para la disciplina ${belica}.`);
		return;
	}

	// Verificar que el valor del camino es un número (puede ser 0, 1, o 2)
	if (typeof belicas[belica].sub[camino] !== 'number') {
		alert(`Error: El valor del camino ${camino} no es válido para la disciplina ${belica}.`);
		return;
	}

	let isBelica1 = belica1 == belica;
	let isBelica2 = belica2 == belica;
	let isBelica3 = belica3 == belica;
	let isBelica4 = belica4 == belica;
	let isBelica5 = belica5 == belica;
	let isBelica6 = belica6 == belica;
	let isBelica7 = belica7 == belica;	
	let isBelica8 = belica8 == belica;
	let isBelica9 = belica9 == belica;
	let isBelica10 = belica10 == belica;
	let isBelica11 = belica11 == belica;
	let isBelica12 = belica12 == belica;

	// Validar que el camino es válido para esta disciplina
	if (typeof belicas[belica].sub[camino] === 'undefined') {
		alert(`Error: El camino ${camino} no existe en la disciplina ${belica}. Contacta a un moderador.`);
		return;
	}

	let espesCount = 0;

	let isEspe1 = belicas[belica].espe1 == camino;
	let isEspe2 = belicas[belica].espe2 == camino;

	if (belicas[belica].espe1) { espesCount = espesCount + 1; }
	if (belicas[belica].espe2) { espesCount = espesCount + 1; }

	// Si es una especialidad, validar que está asignada a una posición válida
	if (belicas[belica].sub[camino] == 1 && !isEspe1 && !isEspe2) {
		alert(`Error: La especialización ${camino} no está asignada correctamente en la disciplina ${belica}.`);
		return;
	}

	if (isEspe1 && belicas[belica].sub[belicas[belica].espe2] == 2) {
		alert(`No puedes tener más de una especialidad, y ya tienes especialidad en ${belicas[belica].espe2}.`);
		return;
	} else if (isEspe2 && belicas[belica].sub[belicas[belica].espe1] == 2) {
	    alert(`No puedes tener más de una especialidad, y ya tienes especialidad en ${belicas[belica].espe1}.`);
		return;
    }
	
	let espeNivel = belicas[belica].sub[camino];

	let nikaReq = 0;

	if (espeNivel == 1) {

		if (nivel < 20) {
			alert(`No cumples el requisito mínimo de nivel 20 para aprender la especialidad de ` + camino + `.`);
			return;
		} 

		if (isBelica1 && isEspe1) { nikaReq = 45; }
		if (isBelica2 && isEspe1) { nikaReq = 60; }
		if (isBelica3 && isEspe1) { nikaReq = 75; }
		if (isBelica4 && isEspe1) { nikaReq = 100; }
		if (isBelica5 && isEspe1) { nikaReq = 125; }
		if (isBelica6 && isEspe1) { nikaReq = 150; }
		if (isBelica7 && isEspe1) { nikaReq = 175; }
		if (isBelica8 && isEspe1) { nikaReq = 200; }
		if (isBelica9 && isEspe1) { nikaReq = 225; }
		if (isBelica10 && isEspe1) { nikaReq = 250; }
		if (isBelica11 && isEspe1) { nikaReq = 275; }
		if (isBelica12 && isEspe1) { nikaReq = 300; }

		if (isBelica1 && isEspe2) { nikaReq = 45; }
		if (isBelica2 && isEspe2) { nikaReq = 60; }
		if (isBelica3 && isEspe2) { nikaReq = 75; }
		if (isBelica4 && isEspe2) { nikaReq = 100; }
		if (isBelica5 && isEspe2) { nikaReq = 125; }
		if (isBelica6 && isEspe2) { nikaReq = 150; }
		if (isBelica7 && isEspe2) { nikaReq = 175; }
		if (isBelica8 && isEspe2) { nikaReq = 200; }
		if (isBelica9 && isEspe2) { nikaReq = 225; }
		if (isBelica10 && isEspe2) { nikaReq = 250; }
		if (isBelica11 && isEspe2) { nikaReq = 275; }
		if (isBelica12 && isEspe2) { nikaReq = 300; }

		if (isBelica1 && isEspe1 && nikas >= 45) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 45 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica1", camino); } } 
		else if (isBelica2 && isEspe1 && nikas >= 60) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 60 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica2", camino); } } 
		else if (isBelica3 && isEspe1 && nikas >= 75) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 75 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica3", camino); } } 
		else if (isBelica4 && isEspe1 && nikas >= 100) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 100 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica4", camino); } } 
		else if (isBelica5 && isEspe1 && nikas >= 125) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 125 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica5", camino); } } 
		else if (isBelica6 && isEspe1 && nikas >= 150) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 150 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica6", camino); } } 
		else if (isBelica7 && isEspe1 && nikas >= 175) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 175 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica7", camino); } }
		else if (isBelica8 && isEspe1 && nikas >= 200) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 200 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica8", camino); } } 
		else if (isBelica9 && isEspe1 && nikas >= 225) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 225 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica9", camino); } } 
		else if (isBelica10 && isEspe1 && nikas >= 250) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 250 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica10", camino); } }
		else if (isBelica11 && isEspe1 && nikas >= 275) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 275 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica11", camino); } } 
		else if (isBelica12 && isEspe1 && nikas >= 300) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 300 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica12", camino); } }

		else if (isBelica1 && isEspe2 && nikas >= 45) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 45 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica1", camino); } } 
		else if (isBelica2 && isEspe2 && nikas >= 60) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 60 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica2", camino); } } 
		else if (isBelica3 && isEspe2 && nikas >= 75) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 75 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica3", camino); } } 
		else if (isBelica4 && isEspe2 && nikas >= 100) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 100 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica4", camino); } } 
		else if (isBelica5 && isEspe2 && nikas >= 125) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 125 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica5", camino); } } 
		else if (isBelica6 && isEspe2 && nikas >= 150) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 150 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica6", camino); } } 
		else if (isBelica7 && isEspe2 && nikas >= 175) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 175 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica7", camino); } }
		else if (isBelica8 && isEspe2 && nikas >= 200) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 200 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica8", camino); } }
		else if (isBelica9 && isEspe2 && nikas >= 225) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 225 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica9", camino); } }
		else if (isBelica10 && isEspe2 && nikas >= 250) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 250 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica10", camino); } }
		else if (isBelica11 && isEspe2 && nikas >= 275) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 275 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica11", camino); } }
		else if (isBelica12 && isEspe2 && nikas >= 300) { if (confirm(`Para aprender la especialización de ` + camino + ` tiene un costo de 300 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica12", camino); } }

		else { alert(`No cumples los requisitos para aprender la especialización de` + camino + `. Debes tener ` + nikaReq + ` nikas.`); }

	} else if (espeNivel == 0) {

		if (nivel < 8) {
			alert(`No cumples el requisito mínimo de nivel 8 para aprender el camino ` + camino + `.`);
			return;
		} 

		// Costes base para caminos
		const COSTES_BASE = {
			espe1: {  // Primer camino
				belica1: 0, belica2: 20, belica3: 30, belica4: 45,
				belica5: 60, belica6: 75, belica7: 90, belica8: 105,
				belica9: 120, belica10: 135, belica11: 150, belica12: 165
			},
			espe2: {  // Segundo camino
				belica1: 15, belica2: 30, belica3: 40, belica4: 60,
				belica5: 75, belica6: 90, belica7: 105, belica8: 120,
				belica9: 135, belica10: 150, belica11: 165, belica12: 180
			}
		};

		// Valor por defecto alto para evitar falsos positivos
		nikaReq = 999;

		// Determinar qué número de bélica es (1-12)
		let belicaNum = 0;
		if (isBelica1) belicaNum = 1;
		else if (isBelica2) belicaNum = 2;
		else if (isBelica3) belicaNum = 3;
		else if (isBelica4) belicaNum = 4;
		else if (isBelica5) belicaNum = 5;
		else if (isBelica6) belicaNum = 6;
		else if (isBelica7) belicaNum = 7;
		else if (isBelica8) belicaNum = 8;
		else if (isBelica9) belicaNum = 9;
		else if (isBelica10) belicaNum = 10;
		else if (isBelica11) belicaNum = 11;
		else if (isBelica12) belicaNum = 12;

		// Si no se encontró un número de bélica válido
		if (belicaNum === 0) {
			alert(`Error: No se pudo determinar el número de disciplina.`);
			return;
		}

		// Asignar el coste basado en si es primer o segundo camino
		if (espesCount === 0) {
			nikaReq = COSTES_BASE.espe1[`belica${belicaNum}`];
		} else if (espesCount === 1) {
			nikaReq = COSTES_BASE.espe2[`belica${belicaNum}`];
		}

		// Verificar que se encontró un coste válido
		if (nikaReq === 999 || nikaReq === undefined) {
			alert(`Error: No se pudo determinar el coste para el camino ${camino} en la disciplina ${belica}.`);
			return;
		}

		if (isBelica1 && espesCount == 0 && nikas >= 0) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 0 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica1", camino); } } 
		else if (isBelica2 && espesCount == 0 && nikas >= 20) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 20 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica2", camino); } }
		else if (isBelica3 && espesCount == 0 && nikas >= 30) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 30 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica3", camino); } }
		else if (isBelica4 && espesCount == 0 && nikas >= 40) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 40 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica4", camino); } }
		else if (isBelica5 && espesCount == 0 && nikas >= 60) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 60 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica5", camino); } }
		else if (isBelica6 && espesCount == 0 && nikas >= 75) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 75 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica6", camino); } }
		else if (isBelica7 && espesCount == 0 && nikas >= 90) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 90 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica7", camino); } }
		else if (isBelica8 && espesCount == 0 && nikas >= 105) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 105 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica8", camino); } }
		else if (isBelica9 && espesCount == 0 && nikas >= 120) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 120 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica9", camino); } }
		else if (isBelica10 && espesCount == 0 && nikas >= 135) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 135 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica10", camino); } } 
		else if (isBelica11 && espesCount == 0 && nikas >= 150) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 150 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica11", camino); } } 
		else if (isBelica12 && espesCount == 0 && nikas >= 165) { if (confirm(`Para aprender el primer camino ` + camino + ` tiene un costo de 165 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe1", "belica12", camino); } }	

		else if (isBelica1 && espesCount == 1 && nikas >= 15) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 15 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica1", camino); } } 
		else if (isBelica2 && espesCount == 1 && nikas >= 30) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 30 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica2", camino); } }
		else if (isBelica3 && espesCount == 1 && nikas >= 40) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 40 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica3", camino); } }
		else if (isBelica4 && espesCount == 1 && nikas >= 60) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 60 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica4", camino); } }
		else if (isBelica5 && espesCount == 1 && nikas >= 75) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 75 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica5", camino); } }
		else if (isBelica6 && espesCount == 1 && nikas >= 90) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 90 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica6", camino); } }
		else if (isBelica7 && espesCount == 1 && nikas >= 105) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 105 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica7", camino); } }
		else if (isBelica8 && espesCount == 1 && nikas >= 120) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 120 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica8", camino); } }
		else if (isBelica9 && espesCount == 1 && nikas >= 135) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 135 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica9", camino); } }
		else if (isBelica10 && espesCount == 1 && nikas >= 150) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 150 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica10", camino); } } 
		else if (isBelica11 && espesCount == 1 && nikas >= 165) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 165 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica11", camino); } } 
		else if (isBelica12 && espesCount == 1 && nikas >= 180) { if (confirm(`Para aprender el segundo camino ` + camino + ` tiene un costo de 180 nikas. ¿Estás de acuerdo?`)) { subirCamino("espe2", "belica12", camino); } }

		else { alert(`No cumples los requisitos para aprender el camino ` + camino + `. Debes tener ` + nikaReq + ` nikas.`); }
	}
}


function chooseBelica(belica, hasBelica) {
	
	if (!is_owner) { return; }
	
	let isBelica1 = belica1 == belica;
	let isBelica2 = belica2 == belica;
	let isBelica3 = belica3 == belica;
	let isBelica4 = belica4 == belica;
	let isBelica5 = belica5 == belica;
	let isBelica6 = belica6 == belica;
	let isBelica7 = belica7 == belica;
	let isBelica8 = belica8 == belica;
	let isBelica9 = belica9 == belica;
	let isBelica10 = belica10 == belica;
	let isBelica11 = belica11 == belica;
	let isBelica12 = belica12 == belica;
	let countBelicas = Object.keys(belicas).length;

	let nivelReq = 0;
	let nikaReq = 0;

	if (countBelicas == 1 && nikas >= 10) {
		if (confirm(`Para aprender la disciplina ` + belica + ` tiene un costo de 10 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica2'); }
	} else if (countBelicas == 2 && nikas >= 20) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 20 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica3'); }
	} else if (countBelicas == 3 && nikas >= 35) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 35 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica4'); }
	} else if (countBelicas == 4 && nikas >= 50) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 50 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica5'); }
	} else if (countBelicas == 5 && nikas >= 65) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 65 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica6'); }
	} else if (countBelicas == 6 && nikas >= 80) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 80 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica7'); }
	} else if (countBelicas == 7 && nikas >= 95) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 95 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica8'); }
	} else if (countBelicas == 8 && nikas >= 110) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 110 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica9'); }
	} else if (countBelicas == 9 && nikas >= 125) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 125 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica10'); }
	} else if (countBelicas == 10 && nikas >= 140) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 140 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica11'); }
	} else if (countBelicas == 11 && nikas >= 155) {
		if (confirm(`Para aprender la nivel a la disciplina ` + belica + ` tiene un costo de 155 nikas. ¿Estás de acuerdo?`)) { subirBelica(belica, 'belica12'); }
	} else {

		if (countBelicas == 1) { nikaReq = 10; }
		if (countBelicas == 2) { nikaReq = 20; }
		if (countBelicas == 3) { nikaReq = 35; }
		if (countBelicas == 4) { nikaReq = 50; }
		if (countBelicas == 5) { nikaReq = 65; }
		if (countBelicas == 6) { nikaReq = 80; }
		if (countBelicas == 7) { nikaReq = 95; }
		if (countBelicas == 8) { nikaReq = 110; }
		if (countBelicas == 9) { nikaReq = 125; }
		if (countBelicas == 10) { nikaReq = 140; }
		if (countBelicas == 11) { nikaReq = 155; }

		alert(`No cumples los requisitos para aprender la disciplina ` + belica + `. Debes tener ` + nikaReq + ` nikas.`);
	}
		
}

function updateBelicaUI(belicaName) {
	// Verificar que el nombre de la bélica sea válido
	if (!belicaName || belicaName === '') {
		console.warn('Nombre de bélica vacío o undefined');
		return;
	}
	
	// Verificar que el objeto belicas existe
	if (typeof belicas === 'undefined') {
		console.error('El objeto belicas no está definido');
		return;
	}
	
	let belica = belicas[belicaName];
	
	// Verificar que la bélica existe antes de acceder a sus propiedades
	if (!belica) {
		console.warn(`Bélica '${belicaName}' no encontrada en el objeto belicas`);
		return;
	}
	
	let nivel = belica.nivel;
	$(`#${belicaName.replace(" ", "_")}_img`).css("filter", "grayscale(0)");

	let caminos = Object.keys(belica.sub);
	let nivelBody = `#${belicaName.replace(" ", "_")}_nivel_body`;

	if (nivel == 1) {
		$(nivelBody).css('cursor', 'auto').css('background-color', '#28ce26');
	} else {
		$(nivelBody).css('background-color', '#8d888f');
	}

	caminos.forEach(camino => {
		let caminoNivel = belica.sub[camino];
		let caminoNivelBody = `#${camino.replace(" ", "_")}_nivel_body`;
		let caminoNivelId = `#${camino.replace(" ", "_")}_nivel`;

		if (nivel == 1 && (caminoNivel == 0 || caminoNivel == 1)) {
			$(caminoNivelBody).css('cursor', 'pointer');
			if (caminoNivel == 0) $(caminoNivelBody).css('background-color', '#9d57b4');
			if (caminoNivel == 1) {
				$(caminoNivelBody).css('background-color', '#e1740a');
				$(caminoNivelId).html(`C`);
			}
			$(caminoNivelBody).off('click').on('click', function() { chooseCamino(belicaName, camino); });
		} else if (caminoNivel == 2) {
			$(caminoNivelBody).css('background-color', '#28ce26');
			$(caminoNivelId).html(`E`);
		}
	});
}

// Inventario

// Función para desequipar todo desde el inventario (también sirve para desbugearlo a nivel user)
function desequiparTodo(){
    debugger;
    if (is_owner || g_is_staff) {
        objetosEquipados = JSON.parse('{"ropa": null, "bolsa": null, "espacios": {}}');
        cambiarEquipamiento();
        location.reload();
    }
}	

function getStatCodigo(codigo) {
    return statCodigo[codigo];
}

// let efectosArray = Object.keys(efectosCodigo);

function addEfectos(descripcion) {
    for (let i = 0; i < efectosArray.length; i++) {
    
        let efecto = efectosArray[i];
        descripcion = descripcion.replace(efecto, `
            <span class="tec-tooltip"><strong>` + efectosCodigo[efecto].nombre + `</strong><span class="tec-tooltiptext">` + efectosCodigo[efecto].texto + `</span></span>
        `);
    
    }
    return descripcion;
}

function addEfectoStats(efectos) {
    
    console.log(efectos);
    // let efectosRegex = /\[(RES|FUE|DES|PUN|AGI|REF|CAK|VOL|Nivel|NIVEL)x(\d+|\d+,\d+)]/g;
    let efectosRegex = /\[(\d+|\d+,\d+)x(RES|FUE|DES|PUN|AGI|REF|CAK|VOL|Nivel|NIVEL)]/g;
    const efectosMatch = [...efectos.matchAll(efectosRegex)];
    
    console.log(efectosMatch);
    
    for (let i = 0; i < efectosMatch.length; i++) {
        let codigo = efectosMatch[i][0];
        let stat = efectosMatch[i][2];
        let multi = parseFloat(efectosMatch[i][1].replace(',', '.'));
        
        let statNum = statNums[efectosMatch[i][2]];
        
        // let multiplied = statNum * multi;
        
        console.log(stat, multi, statNum);
        
        let htmlTag = `<span class="tec-tooltip"><strong>` + (statNum * multi).toFixed(1) + `</strong><span class="stat-tooltiptext">` + codigo + `</span></span>`
        efectos = efectos.replace(codigo, htmlTag);
    }
    
    for (let i = 0; i < efectosArray.length; i++) {
    
        let efecto = efectosArray[i];
        efectos = efectos.replace(efecto, `<span class="tec-tooltip"><strong>` + efectosCodigo[efecto].nombre + `</strong><span class="tec-tooltiptext">` + efectosCodigo[efecto].texto + `</span></span>`);
    
    }
    
    return efectos;
}

// Función para filtrar por categoría
function filtrarCategoria(categoria) {
    const boton = document.getElementById('btn-' + categoria);
    const index = filtroInventario.categorias.indexOf(categoria);
    
    if (index > -1) {
        // Si ya está seleccionado, quitarlo
        filtroInventario.categorias.splice(index, 1);
        boton.style.backgroundColor = '#666666';
    } else {
        // Si no está seleccionado, agregarlo
        filtroInventario.categorias.push(categoria);
        boton.style.backgroundColor = '#000000';
    }
    
    aplicarFiltrosInventario();
}

// Función para filtrar por tier
function filtrarTier(tier) {
    const botonId = tier === 'ESP' ? 'btn-tier-esp' : 'btn-tier' + tier;
    const boton = document.getElementById(botonId);
    const index = filtroInventario.tiers.indexOf(tier.toString());
    
    if (index > -1) {
        // Si ya está seleccionado, quitarlo
        filtroInventario.tiers.splice(index, 1);
        boton.style.backgroundColor = '#666666';
    } else {
        // Si no está seleccionado, agregarlo
        filtroInventario.tiers.push(tier.toString());
        boton.style.backgroundColor = '#000000';
    }
    
    aplicarFiltrosInventario();
}

// Función para aplicar filtros del inventario
function aplicarFiltrosInventario() {
    console.log('Aplicando filtros de inventario:', filtroInventario);
    
    // Obtener todos los contenedores de categorías
    const categorias = ['especiales', 'consumibles', 'armas', 'utensilios', 'materiales', 'estructuras'];
    
    categorias.forEach(categoria => {
        const contenedor = document.getElementById('objetos_' + categoria);
        if (!contenedor) return;
        
        let mostrarCategoria = false;
        
        // Si hay filtros de categoría activos, verificar si esta categoría debe mostrarse
        if (filtroInventario.categorias.length > 0) {
            mostrarCategoria = filtroInventario.categorias.includes(categoria);
        } else {
            mostrarCategoria = true; // Si no hay filtros de categoría, mostrar todas
        }
        
        if (mostrarCategoria) {
            contenedor.style.display = 'block';
            
            // Aplicar filtros a los objetos individuales dentro de esta categoría
            const objetos = contenedor.querySelectorAll('.item-outer');
            objetos.forEach(objeto => {
                let mostrarObjeto = true;
                
                // Filtrar por tier si hay filtros activos
                if (filtroInventario.tiers.length > 0) {
                    // Buscar el tier en el tooltip del objeto, excluyendo mydescripcion
                    const tooltip = objeto.querySelector('.tooltiptext');
                    if (tooltip) {
                        // Clonar el tooltip para no modificar el original
                        const tooltipClone = tooltip.cloneNode(true);
                        
                        // Remover el elemento mydescripcion del clon
                        const mydescripcion = tooltipClone.querySelector('.mydescripcion');
                        if (mydescripcion) {
                            mydescripcion.remove();
                        }
                        
                        const textoTooltip = tooltipClone.textContent || tooltipClone.innerText;
                        let tierEncontrado = false;
                        
                        filtroInventario.tiers.forEach(tierBuscado => {
                            // Buscar "Tier X" en el texto del tooltip
                            if (tierBuscado === 'ESP') {
                                // Para tier especial, buscar varias variantes
                                if (textoTooltip.includes('Tier ESP') || 
                                    textoTooltip.includes('Tier Especial') || 
                                    textoTooltip.includes('Tier 6') ||
                                    textoTooltip.includes('Tier 7') ||
                                    textoTooltip.includes('Tier 8') ||
                                    textoTooltip.includes('Tier 9') ||
                                    textoTooltip.includes('Tier 10')) {
                                    tierEncontrado = true;
                                }
                            } else {
                                // Para tiers normales (1-5)
                                if (textoTooltip.includes('Tier ' + tierBuscado)) {
                                    tierEncontrado = true;
                                }
                            }
                        });
                        
                        if (!tierEncontrado) {
                            mostrarObjeto = false;
                        }
                    } else {
                        mostrarObjeto = false; // Si no hay tooltip, ocultar
                    }
                }
                
                // Filtrar por búsqueda de texto (solo en el nombre del objeto)
                if (filtroInventario.busqueda) {
                    const nombreObjeto = objeto.querySelector('.item-nombre').textContent.toLowerCase();
                    if (!nombreObjeto.includes(filtroInventario.busqueda.toLowerCase())) {
                        mostrarObjeto = false;
                    }
                }
                
                objeto.style.display = mostrarObjeto ? 'block' : 'none';
            });
        } else {
            contenedor.style.display = 'none';
        }
    });
}

// Event listener para el buscador
document.addEventListener('DOMContentLoaded', function() {
    const buscador = document.getElementById('buscador-inventario');
    if (buscador) {
        buscador.addEventListener('input', function() {
            filtroInventario.busqueda = this.value;
            aplicarFiltrosInventario();
        });
    }
});

// Función para limpiar filtros del inventario
function limpiarFiltrosInventario() {
    filtroInventario = { categorias: [], tiers: [], busqueda: '' };
    
    // Resetear estilos de botones
    const botones = document.querySelectorAll('[id^="btn-"]');
    botones.forEach(boton => {
        if (boton.id.includes('tier') || boton.id.includes('consumibles') || 
            boton.id.includes('armas') || boton.id.includes('utensilios') || 
            boton.id.includes('estructuras') || boton.id.includes('materiales') || 
            boton.id.includes('especiales')) {
            boton.style.backgroundColor = '#666666';
        }
    });
    
    // Limpiar buscador
    const buscador = document.getElementById('buscador-inventario');
    if (buscador) {
        buscador.value = '';
    }
    
    aplicarFiltrosInventario();
}

// Variable para almacenar espacios ocupados por objetos del inventario

// Función para abrir selector de equipamiento (bolsa/ropa)
function abrirSelectorEquipamiento(tipo) {
    if (!is_owner) { return; }
    
    tipoEquipamientoSeleccionado = tipo;
    
    var modal = document.getElementById('modalSelectorEquipamiento');
    var titulo = document.getElementById('tituloSelectorEquipamiento');
    var lista = document.getElementById('listaEquipamientoDisponible');
    
    // Actualizar título según el tipo
    if (tipo === 'equipajes') {
        titulo.innerHTML = 'SELECCIONAR BOLSA/EQUIPAJE';
    } else if (tipo === 'ropa y armaduras') {
        titulo.innerHTML = 'SELECCIONAR ROPA/ARMADURA/ESPECIAL MT';
    }
    
    // Limpiar lista anterior
    lista.innerHTML = '';
    
    // Filtrar objetos por tipo
    var objetosDisponibles = [];
    for (var i = 0; i < objetos_array_json.length; i++) {
        var objetoId = objetos_array_json[i];
        if (objetoId === 'LLST001') continue;
        
        var objeto = objetos_json[objetoId][0];
        var subcategoriaLower = objeto.subcategoria.toLowerCase();
        
        // Para ropa y armaduras, también incluir objetos ESPECIAL MT
        if (tipo === 'ropa y armaduras') {
            if (subcategoriaLower === tipo || subcategoriaLower === 'especial mt') {
                objetosDisponibles.push(objetoId);
            }
        } else if (subcategoriaLower === tipo) {
            objetosDisponibles.push(objetoId);
        }
    }
    // Crear elementos de la lista
    for (var j = 0; j < objetosDisponibles.length; j++) {
        var objetoId = objetosDisponibles[j];
        var objeto = objetos_json[objetoId][0];
        var subcategoria = objeto.subcategoria.toLowerCase().split(' ').join('_');
        var imagen_id = objeto.imagen_id;
        
        var imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
        if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objetoId === 'EANM001' || 
            objetoId === 'LLST001' || objetoId === 'THR001' || subcategoria === 'materiales' || 
            subcategoria === 'tecnicas' || objetoId === 'EPA001' || objetoId === 'KMP001' || 
            objetoId === 'VCD001' || objetoId === 'VCZ001' || subcategoria === 'documentos' || 
            subcategoria === 'recetas') {
            imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.gif";
        }
        
        var imagen = '/images/op/iconos/' + imagen_nombre;
        if (objeto.imagen_avatar !== '') {
            imagen = objeto.imagen_avatar;
        }
        
        // Crear elemento de la lista
        var elementoLista = document.createElement('div');
        elementoLista.style.cssText = 'width: 120px; height: 140px; border: 2px solid #ccc; border-radius: 8px; padding: 10px; text-align: center; cursor: pointer; background-color: #f9f9f9; transition: all 0.3s; display: inline-block; margin: 5px; vertical-align: top;';
        
        elementoLista.innerHTML = '<img src="' + imagen + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"><div style="font-family: moonGetHeavy; font-size: 11px; margin-top: 8px; color: #333; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: 100%;">' + (objeto.apodo !== '' ? objeto.apodo : objeto.nombre) + '</div>';
        
        // Eventos de hover
        elementoLista.setAttribute('onmouseover', 'this.style.backgroundColor="#e3f2fd"; this.style.borderColor="#2196f3";');
        elementoLista.setAttribute('onmouseout', 'this.style.backgroundColor="#f9f9f9"; this.style.borderColor="#ccc";');
        
        // Evento de click para equipar
        elementoLista.setAttribute('onclick', 'equiparItem("' + objetoId + '")');
        
        lista.appendChild(elementoLista);
    }
    
    // Mostrar modal
    modal.style.display = 'block';
}

// Función para cerrar el selector de equipamiento
function cerrarSelectorEquipamiento() {
    var modal = document.getElementById('modalSelectorEquipamiento');
    modal.style.display = 'none';
    tipoEquipamientoSeleccionado = '';
}

function reindexObject(obj) {
const values = Object.keys(obj)
    .sort((a, b) => Number(a) - Number(b)) // sort numerically
    .map(key => obj[key]); // extract values in order

const newObj = {};
values.forEach((value, index) => {
    newObj[index] = value;
});

return newObj;
}

// Función para desequipar item
function desequiparItem() {
    if (!is_owner) { return; }
    if (tipoEquipamientoSeleccionado === 'equipajes' && objetosEquipados.bolsa) {
        objetosEquipados.bolsa = null;
        var imagenBolsa = document.getElementById('imagen-bolsa-equipada');
        imagenBolsa.src = '/images/op/uploads/SinMochila_One_Piece_Gaiden_Foro_Rol.jpg';
        imagenBolsa.alt = '';
        
        var slotBolsa = document.getElementById('slot-bolsa');
        slotBolsa.title = '';
        
        mostrarMensajeEquipamiento('Bolsa desequipada correctamente.');
        
    } else if (tipoEquipamientoSeleccionado === 'ropa y armaduras' && objetosEquipados.ropa) {
        objetosEquipados.ropa = null;
        var imagenRopa = document.getElementById('imagen-ropa-equipada');
        imagenRopa.src = '/images/op/uploads/SinRopa_One_Piece_Gaiden_Foro_Rol.jpg';
        imagenRopa.alt = '';
        
        var slotRopa = document.getElementById('slot-ropa');
        slotRopa.title = '';
        
        mostrarMensajeEquipamiento('Ropa desequipada correctamente.');
    }
    
    cambiarEquipamiento();
    actualizarContadorEspacios();
    cerrarSelectorEquipamiento();
}

// Función para mostrar mensajes de equipamiento
function mostrarMensajeEquipamiento(mensaje) {
    // Crear div de mensaje temporal
    var mensajeDiv = document.createElement('div');
    mensajeDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; background-color: #4caf50; color: white; padding: 15px 20px; border-radius: 5px; font-family: moonGetHeavy; font-size: 14px; z-index: 2000; box-shadow: 0 2px 5px rgba(0,0,0,0.3);';
    mensajeDiv.innerHTML = mensaje;
    
    document.body.appendChild(mensajeDiv);
    
    // Remover mensaje después de 3 segundos
    setTimeout(function() {
        if (mensajeDiv.parentNode) {
            mensajeDiv.parentNode.removeChild(mensajeDiv);
        }
    }, 3000);
}

// Función para extraer espacios del efecto y daño del objeto
function extraerEspaciosDelEfecto(efecto, dano) {
    var espacios = 0;
    
    // Buscar en el campo efecto
    if (efecto) {
        var patronEspacios = /\[otorga\s+(\d+)\s+espacios?\]/i;
        var match = efecto.match(patronEspacios);
        
        if (match) {
            espacios += parseInt(match[1]);
        } else {
            var patronSinCorchetes = /otorga\s+(\d+)\s+espacios?/i;
            var matchSinCorchetes = efecto.match(patronSinCorchetes);
            
            if (matchSinCorchetes) {
                espacios += parseInt(matchSinCorchetes[1]);
            } else {
                var patronGeneral = /(\d+)\s+espacios?/i;
                var matchGeneral = efecto.match(patronGeneral);
                
                if (matchGeneral) {
                    espacios += parseInt(matchGeneral[1]);
                }
            }
        }
    }
    
    // Buscar en el campo daño
    if (dano) {
        var patronEspaciosDano = /\[otorga\s+(\d+)\s+espacios?\]/i;
        var matchDano = dano.match(patronEspaciosDano);
        
        if (matchDano) {
            espacios += parseInt(matchDano[1]);
        } else {
            var patronDanoSinCorchetes = /otorga\s+(\d+)\s+espacios?/i;
            var matchDanoSinCorchetes = dano.match(patronDanoSinCorchetes);
            
            if (matchDanoSinCorchetes) {
                espacios += parseInt(matchDanoSinCorchetes[1]);
            }
        }
    }
    
    return espacios;
}

// Función para extraer espacios requeridos por un objeto
function extraerEspaciosRequeridos(objeto) {
    //return parseInt(objeto.espacios) || 1;
    const n = parseInt(objeto.espacios, 10);
    // Permite 0 si así está definido; solo por NaN vuelve a 1
    return Number.isFinite(n) ? n : 1;
}

// Función para calcular espacios disponibles
function calcularEspaciosDisponibles() {
    var espaciosBase = 5; // ####################
    var espaciosAdicionales = 0;

    // Contar espacios adicionales de objetos equipados
    if (objetosEquipados.bolsa) {
        var objetoBolsa = objetos_json[objetosEquipados.bolsa][0];
        espaciosAdicionales += extraerEspaciosDelEfecto(objetoBolsa.efecto, objetoBolsa.dano);
    }
    
    if (objetosEquipados.ropa) {
        var objetoRopa = objetos_json[objetosEquipados.ropa][0];
        espaciosAdicionales += extraerEspaciosDelEfecto(objetoRopa.efecto, objetoRopa.dano);
    }
    
    try {
        if ((typeof oficio1 !== 'undefined' && oficio1 === 'Aventurero') ||
            (typeof oficio2 !== 'undefined' && oficio2 === 'Aventurero')) {
            espaciosAdicionales += 4;
        }
    } catch (e) {
    // ignora si no están definidas
    }
    
    actualizarEspaciosOcupados();
    
    // return espaciosBase + espaciosAdicionales - espaciosOcupados;
    const total = espaciosBase + espaciosAdicionales - espaciosOcupados;
    return total < 0 ? 0 : total;
}

function actualizarEspaciosOcupados() {
    let espaciosOcupadosActuales = 0;
    
    //let objetosEquipadosKeys = Object.keys(objetosEquipados.espacios);
    //for (let i = 0; i < objetosEquipadosKeys.length; i++) {
    //	espaciosOcupadosActuales += objetosEquipados.espacios[i].espacios;
    //}
    
    if (!objetosEquipados || !objetosEquipados.espacios) {
        espaciosOcupados = 0;
        return;
    }

    const keys = Object.keys(objetosEquipados.espacios);
    for (const key of keys) {
        const slot = objetosEquipados.espacios[key];
        if (slot && typeof slot.espacios !== 'undefined') {
            espaciosOcupadosActuales += Number(slot.espacios) || 0;
        }
    }
    
    espaciosOcupados = espaciosOcupadosActuales;
}

// Función para equipar objeto en espacio específico
function equiparObjetoEnEspacio(objetoId, espacioIndex) {
    var objeto = objetos_json[objetoId][0];
    var espaciosRequeridos = extraerEspaciosRequeridos(objeto);
    var espaciosDisponibles = calcularEspaciosDisponibles();
    
    // Verificar si hay espacios suficientes
    // if (espaciosOcupados + espaciosRequeridos > espaciosDisponibles) {
    if (espaciosRequeridos > espaciosDisponibles) {
        mostrarMensajeEquipamiento('No hay suficientes espacios disponibles. Se requieren ' + espaciosRequeridos + ' espacios.');
        return false;
    }
    
    // Verificar si el espacio específico está libre
    if (objetosEquipados.espacios[espacioIndex]) {
        mostrarMensajeEquipamiento('Este espacio ya está ocupado.');
        return false;
    }
    
    // Equipar el objeto
    objetosEquipados.espacios[espacioIndex] = {
        objetoId: objetoId,
        espacios: espaciosRequeridos
    };
    
    espaciosOcupados += espaciosRequeridos;
    
    // Actualizar visuales
    //actualizarContadorEspacios();
    actualizarContadorEspacios && actualizarContadorEspacios();
    mostrarMensajeEquipamiento(objeto.nombre + ' equipado correctamente.');
    cambiarEquipamiento();
    
    return true;
}

// Función para desequipar objeto de espacio específico
function desequiparObjetoDeEspacio(espacioIndex) {
    if (!objetosEquipados.espacios[espacioIndex]) {
        mostrarMensajeEquipamiento('No hay ningún objeto equipado en este espacio.');
        return false;
    }
    
    var objetoEquipado = objetosEquipados.espacios[espacioIndex];
    var objeto = objetos_json[objetoEquipado.objetoId][0];
    
    // Liberar espacios
    espaciosOcupados -= objetoEquipado.espacios;
    
    // Remover objeto equipado
    delete objetosEquipados.espacios[espacioIndex];
    
    objetosEquipados['espacios'] = reindexObject(objetosEquipados['espacios']);
    
    // Actualizar visuales
    //actualizarContadorEspacios();
    actualizarContadorEspacios && actualizarContadorEspacios();
    mostrarMensajeEquipamiento(objeto.nombre + ' desequipado correctamente.');
    cambiarEquipamiento();

    return true;
}

// Función para generar espacios visuales
// Función para generar espacios visuales
function generarEspaciosVisuales() {
    var contenedorEspacios = document.getElementById('espacios-equipamiento-visual');
    if (!contenedorEspacios) return;
    
    // Limpiar espacios anteriores
    contenedorEspacios.innerHTML = '';
    
    var espaciosDisponibles = calcularEspaciosDisponibles() + Object.keys(objetosEquipados.espacios).length;
    let countObjetosEquipados = 0;
    // Crear espacios visuales
    for (var i = 0; i < espaciosDisponibles; i++) {
        var espacioDiv = document.createElement('div');
        var objetoEquipado = objetosEquipados.espacios[i];
        
        espacioDiv.className = 'espacio-equipamiento';
        espacioDiv.id = 'espacio-' + i;
        
        if (objetoEquipado) {
            countObjetosEquipados += 1;
            // Espacio ocupado - mostrar objeto equipado
            if (!objetos_json[objetoEquipado.objetoId] || !objetos_json[objetoEquipado.objetoId][0]) {
                // El objeto ya no existe en objetos_json (fue eliminado o el ID es inválido), saltar este espacio
                console.warn('generarEspaciosVisuales: objeto no encontrado en objetos_json:', objetoEquipado.objetoId);
                continue;
            }
            var objeto = objetos_json[objetoEquipado.objetoId][0];
            var subcategoria = objeto.subcategoria.toLowerCase().split(' ').join('_');
            var imagen_id = objeto.imagen_id;
            
            var imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
            if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objetoEquipado.objetoId === 'EANM001' || 
                objetoEquipado.objetoId === 'LLST001' || objetoEquipado.objetoId === 'THR001' || subcategoria === 'materiales' || 
                subcategoria === 'tecnicas' || objetoEquipado.objetoId === 'EPA001' || objetoEquipado.objetoId === 'KMP001' || 
                objetoEquipado.objetoId === 'VCD001' || objetoEquipado.objetoId === 'VCZ001' || subcategoria === 'documentos' || 
                subcategoria === 'recetas') {
                imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.gif";
            }
            
            var imagen = '/images/op/iconos/' + imagen_nombre;
            if (objeto.imagen_avatar !== '') {
                imagen = objeto.imagen_avatar;
            }
            
            espacioDiv.style.cssText = 'width: 70px; height: 70px; min-width: 70px; min-height: 70px; border: 2px solid #4caf50; border-radius: 8px; background-color: rgba(76, 175, 80, 0.1); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; position: relative; box-sizing: border-box; background-image: url(' + imagen + '); background-size: 50px 50px; background-position: center; background-repeat: no-repeat; flex-shrink: 0;';
            
            // Tooltip con información del objeto
            espacioDiv.title = objeto.nombre + ' (' + objetoEquipado.espacios + ' espacios)';
            
            // Contenido del espacio con overlay
            espacioDiv.innerHTML = '<div style="position: absolute; bottom: 2px; left: 2px; right: 2px; background-color: rgba(0,0,0,0.7); color: white; font-family: moonGetHeavy; font-size: 7px; text-align: center; border-radius: 3px; padding: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + (objeto.apodo !== '' ? objeto.apodo.substring(0, 8) : objeto.nombre.substring(0, 8)) + '</div>';
            
            // Eventos
            espacioDiv.setAttribute('onmouseover', 'this.style.borderColor="#2e7d32"; this.style.backgroundColor="rgba(76, 175, 80, 0.2)";');
            espacioDiv.setAttribute('onmouseout', 'this.style.borderColor="#4caf50"; this.style.backgroundColor="rgba(76, 175, 80, 0.1)";');
            espacioDiv.setAttribute('onclick', 'mostrarOpcionesObjeto(' + i + ')');
            
        } else {
            // Espacio vacío
            espacioDiv.style.cssText = 'width: 70px; height: 70px; min-width: 70px; min-height: 70px; border: 2px dashed #ccc; border-radius: 8px; background-color: rgba(255, 255, 255, 0.3); display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; position: relative; box-sizing: border-box; flex-shrink: 0;';
            
            // Contenido del espacio
            espacioDiv.innerHTML = '<div style="font-family: moonGetHeavy; font-size: 9px; color: #666; text-align: center; line-height: 10px;">ESPACIO<br>' + (espaciosOcupados + i + 1 - countObjetosEquipados) + '</div>';
            
            // Efectos hover
            espacioDiv.setAttribute('onmouseover', 'this.style.borderColor="#2196f3"; this.style.backgroundColor="rgba(33, 150, 243, 0.1)";');
            espacioDiv.setAttribute('onmouseout', 'this.style.borderColor="#ccc"; this.style.backgroundColor="rgba(255, 255, 255, 0.3)";');
            // espacioDiv.setAttribute('onclick', 'abrirSelectorObjetoInventario(' + i + ')');
            espacioDiv.setAttribute('onclick', 'abrirSelectorObjetoInventario(' + (countObjetosEquipados) + ')');
        }
        
        // Agregar al contenedor
        contenedorEspacios.appendChild(espacioDiv);
    }
    
    // Si no hay espacios, mostrar mensaje
    if (espaciosDisponibles === 0) {
        var mensajeDiv = document.createElement('div');
        mensajeDiv.style.cssText = 'width: 100%; text-align: center; color: #999; font-family: moonGetHeavy; font-size: 12px; padding: 20px 0;';
        mensajeDiv.innerHTML = 'No hay espacios adicionales<br><small>Equipa una bolsa o ropa para obtener más espacios</small>';
        contenedorEspacios.appendChild(mensajeDiv);
    }
}

// Función para verificar si un objeto está equipado
function objetoEstaEquipado(objetoId) {
    // Verificar si está equipado como bolsa
    if (objetosEquipados.bolsa === objetoId) {
        return true;
    }
    
    // Verificar si está equipado como ropa
    if (objetosEquipados.ropa === objetoId) {
        return true;
    }
    
    let cuantasVecesEquipado = 0;
    // Verificar si está equipado en algún espacio
    for (var espacioIndex in objetosEquipados.espacios) {
        if (objetosEquipados.espacios[espacioIndex].objetoId === objetoId) {
            // return true;
            cuantasVecesEquipado++;
        }
    }
    
    if (cuantasVecesEquipado >= parseInt(objetos_json[objetoId][0].cantidad)) {
        return true;
    }
    
    return false;
}

// Función para abrir selector de objetos del inventario
function abrirSelectorObjetoInventario(espacioIndex) {
    if (!is_owner) { return; }
    var modal = document.getElementById('modalSelectorEquipamiento');
    var titulo = document.getElementById('tituloSelectorEquipamiento');
    var lista = document.getElementById('listaEquipamientoDisponible');
    
    // Actualizar título
    titulo.innerHTML = 'SELECCIONAR OBJETO PARA EQUIPAR';
    
    // Limpiar lista anterior
    lista.innerHTML = '';
    
    // Obtener todos los objetos del inventario disponibles para equipar
    var objetosDisponibles = [];
    for (var i = 0; i < objetos_array_json.length; i++) {
        var objetoId = objetos_array_json[i];
        if (objetoId === 'LLST001') continue;
        if (objetoEstaEquipado(objetoId)) continue;
        
        var objeto = objetos_json[objetoId][0];
        var subcategoriaLower = objeto.subcategoria.toLowerCase();
        // Filtrar objetos que no son equipajes (estos van en slots específicos)
        // Los objetos de ropa y armaduras pueden ir en slot específico O en espacios
        // Los objetos de especial MT pueden ir en slot de ropa O en espacios
        if (subcategoriaLower !== 'equipajes') {
            objetosDisponibles.push(objetoId);
        }
    }
    
    // Crear elementos de la lista
    for (var j = 0; j < objetosDisponibles.length; j++) {
        var objetoId = objetosDisponibles[j];
        var objeto = objetos_json[objetoId][0];
        var subcategoria = objeto.subcategoria.toLowerCase().split(' ').join('_');
        var imagen_id = objeto.imagen_id;
        var espaciosRequeridos = extraerEspaciosRequeridos(objeto);
        
        var imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
        if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objetoId === 'EANM001' || 
            objetoId === 'LLST001' || objetoId === 'THR001' || subcategoria === 'materiales' || 
            subcategoria === 'tecnicas' || objetoId === 'EPA001' || objetoId === 'KMP001' || 
            objetoId === 'VCD001' || objetoId === 'VCZ001' || subcategoria === 'documentos' || 
            subcategoria === 'recetas') {
            imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.gif";
        }
        
        var imagen = '/images/op/iconos/' + imagen_nombre;
        if (objeto.imagen_avatar !== '') {
            imagen = objeto.imagen_avatar;
        }
        
        // Verificar si se puede equipar
        console.log();
        // var puedeEquipar = espaciosOcupados + espaciosRequeridos <= calcularEspaciosDisponibles();
        var puedeEquipar = espaciosRequeridos <= calcularEspaciosDisponibles();
        var colorFondo = puedeEquipar ? '#f9f9f9' : '#ffebee';
        var colorBorde = puedeEquipar ? '#ccc' : '#f44336';
        
        // Crear elemento de la lista
        var elementoLista = document.createElement('div');
        elementoLista.style.cssText = 'width: 120px; height: 140px; border: 2px solid ' + colorBorde + '; border-radius: 8px; padding: 10px; text-align: center; cursor: pointer; background-color: ' + colorFondo + '; transition: all 0.3s; display: inline-block; margin: 5px; vertical-align: top;';
        
        elementoLista.innerHTML = '<img src="' + imagen + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"><div style="font-family: moonGetHeavy; font-size: 11px; margin-top: 8px; color: #333; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; width: 100%;">' + (objeto.apodo !== '' ? objeto.apodo : objeto.nombre) + '</div><div style="font-family: moonGetHeavy; font-size: 9px; color: #666; margin-top: 4px;">' + espaciosRequeridos + ' Espacios</div>';
        
        if (puedeEquipar) {
            // Eventos de hover
            elementoLista.setAttribute('onmouseover', 'this.style.backgroundColor="#e3f2fd"; this.style.borderColor="#2196f3";');
            elementoLista.setAttribute('onmouseout', 'this.style.backgroundColor="#f9f9f9"; this.style.borderColor="#ccc";');
            
            // Evento de click para equipar
            elementoLista.setAttribute('onclick', 'equiparObjetoInventario("' + objetoId + '", ' + espacioIndex + ')');
        } else {
            // Objeto no equipable por falta de espacios
            elementoLista.title = 'No hay suficientes espacios disponibles';
        }
        
        lista.appendChild(elementoLista);
    }
    
    // Modificar botones del modal
    var botonesDiv = modal.querySelector('div[style*="margin-top: 20px"]');
    botonesDiv.innerHTML = '<button onclick="cerrarSelectorEquipamiento()" style="background-color: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-family: \'moonGetHeavy\';">CANCELAR</button>';
    
    // Mostrar modal
    modal.style.display = 'block';
    
    // Guardar el índice del espacio para uso posterior
    window.espacioSeleccionado = espacioIndex;
}

// Función para equipar objeto del inventario
function equiparObjetoInventario(objetoId, espacioIndex) {
    if (equiparObjetoEnEspacio(objetoId, espacioIndex)) {
        cerrarSelectorEquipamiento();
        generarEspaciosVisuales();
    }
}

// Función para mostrar opciones de objeto equipado
function mostrarOpcionesObjeto(espacioIndex) {
    if (!is_owner) { return; }
    var objetoEquipado = objetosEquipados.espacios[espacioIndex];
    if (!objetoEquipado) return;
    
    var objeto = objetos_json[objetoEquipado.objetoId][0];
    
    if (confirm('¿Deseas desequipar ' + objeto.nombre + '?')) {
        desequiparObjetoDeEspacio(espacioIndex);
        generarEspaciosVisuales();
    }
}

// Función para actualizar el contador de espacios
function actualizarContadorEspacios() { // ################
    

    
    var espaciosMaximos = 5;
    var espaciosAdicionales = calcularEspaciosDisponibles();
    var espaciosTotales = espaciosMaximos + espaciosAdicionales;
    
    var contadorEspacios = document.getElementById('espacios-equipamiento');
    // if (espaciosAdicionales > 0) {
    contadorEspacios.innerHTML = 'ESPACIOS: ' + espaciosOcupados + ' / ' + (espaciosAdicionales + espaciosOcupados);
        // contadorEspacios.innerHTML = 'ESPACIOS: ' + espaciosOcupados + ' / ' + espaciosAdicionales + ' (+' + espaciosAdicionales + ') = ' + espaciosOcupados + ' / ' + espaciosTotales;
    // } else {
    //     contadorEspacios.innerHTML = 'ESPACIOS: ' + espaciosOcupados + ' / ' + espaciosMaximos;
    // }
    
    // Cambiar color basado en los espacios disponibles
    if (espaciosOcupados >= (espaciosAdicionales + espaciosOcupados)) {
        contadorEspacios.style.color = '#f44336'; // Rojo cuando está lleno
    } else if (espaciosAdicionales > 0) {
        contadorEspacios.style.color = '#4caf50'; // Verde cuando hay espacios adicionales
    } else {
        contadorEspacios.style.color = 'white';
    }
    
    // Generar espacios visuales
    generarEspaciosVisuales();
}

// Función para equipar un item (bolsa/ropa)
function equiparItem(objetoId) {
    var objeto = objetos_json[objetoId][0];
    var subcategoria = objeto.subcategoria.toLowerCase().split(' ').join('_');
    var imagen_id = objeto.imagen_id;
    
    var imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
    if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objetoId === 'EANM001' || 
        objetoId === 'LLST001' || objetoId === 'THR001' || subcategoria === 'materiales' || 
        subcategoria === 'tecnicas' || objetoId === 'EPA001' || objetoId === 'KMP001' || 
        objetoId === 'VCD001' || objetoId === 'VCZ001' || subcategoria === 'documentos' || 
        subcategoria === 'recetas') {
        imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.gif";
    }
    
    var imagen = '/images/op/iconos/' + imagen_nombre;
    if (objeto.imagen_avatar !== '') {
        imagen = objeto.imagen_avatar;
    }
    
    var espaciosOtorgados = extraerEspaciosDelEfecto(objeto.efecto, objeto.dano);
    
    if (tipoEquipamientoSeleccionado === 'equipajes') {
        objetosEquipados.bolsa = objetoId;
        var imagenBolsa = document.getElementById('imagen-bolsa-equipada');
        imagenBolsa.src = imagen;
        imagenBolsa.alt = objeto.nombre;
        
        var slotBolsa = document.getElementById('slot-bolsa');
        slotBolsa.title = objeto.nombre + ' - +' + espaciosOtorgados + ' Espacios';
        
    } else if (tipoEquipamientoSeleccionado === 'ropa y armaduras') {
        objetosEquipados.ropa = objetoId;
        var imagenRopa = document.getElementById('imagen-ropa-equipada');
        imagenRopa.src = imagen;
        imagenRopa.alt = objeto.nombre;
        
        var slotRopa = document.getElementById('slot-ropa');
        slotRopa.title = objeto.nombre + ' - +' + espaciosOtorgados + ' Espacios';
    }
    
    cambiarEquipamiento();
    actualizarContadorEspacios();
    cerrarSelectorEquipamiento();
    mostrarMensajeEquipamiento(objeto.nombre + ' equipado correctamente. +' + espaciosOtorgados + ' espacios añadidos.');
}

function equiparItemBolsa(objetoId) {
    var objeto = objetos_json[objetoId][0];
    var subcategoria = objeto.subcategoria.toLowerCase().split(' ').join('_');
    var imagen_id = objeto.imagen_id;
    
    var imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
    if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objetoId === 'EANM001' || 
        objetoId === 'LLST001' || objetoId === 'THR001' || subcategoria === 'materiales' || 
        subcategoria === 'tecnicas' || objetoId === 'EPA001' || objetoId === 'KMP001' || 
        objetoId === 'VCD001' || objetoId === 'VCZ001' || subcategoria === 'documentos' || 
        subcategoria === 'recetas') {
        imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.gif";
    }
    
    var imagen = '/images/op/iconos/' + imagen_nombre;
    if (objeto.imagen_avatar !== '') {
        imagen = objeto.imagen_avatar;
    }
    
    var espaciosOtorgados = extraerEspaciosDelEfecto(objeto.efecto, objeto.dano);

    objetosEquipados.bolsa = objetoId;
    var imagenBolsa = document.getElementById('imagen-bolsa-equipada');
    imagenBolsa.src = imagen;
    imagenBolsa.alt = objeto.nombre;

    var slotBolsa = document.getElementById('slot-bolsa');
    slotBolsa.title = objeto.nombre + ' - +' + espaciosOtorgados + ' Espacios';
}

function equiparItemRopa(objetoId) {
    var objeto = objetos_json[objetoId][0];
    var subcategoria = objeto.subcategoria.toLowerCase().split(' ').join('_');
    var imagen_id = objeto.imagen_id;
    
    var imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
    if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objetoId === 'EANM001' || 
        objetoId === 'LLST001' || objetoId === 'THR001' || subcategoria === 'materiales' || 
        subcategoria === 'tecnicas' || objetoId === 'EPA001' || objetoId === 'KMP001' || 
        objetoId === 'VCD001' || objetoId === 'VCZ001' || subcategoria === 'documentos' || 
        subcategoria === 'recetas') {
        imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.gif";
    }
    
    var imagen = '/images/op/iconos/' + imagen_nombre;
    if (objeto.imagen_avatar !== '') {
        imagen = objeto.imagen_avatar;
    }
    
    var espaciosOtorgados = extraerEspaciosDelEfecto(objeto.efecto, objeto.dano);

    objetosEquipados.ropa = objetoId;
    var imagenRopa = document.getElementById('imagen-ropa-equipada');
    imagenRopa.src = imagen;
    imagenRopa.alt = objeto.nombre;

    var slotRopa = document.getElementById('slot-ropa');
    slotRopa.title = objeto.nombre + ' - +' + espaciosOtorgados + ' Espacios';
    
}

// FIN INVENTARIO

/* PORTADA CODIGO */

function openHistorialModal() {
    // Crear o reutilizar el modal
    var modalId = 'historialModal';
    var modal = document.getElementById(modalId);
    
    if (!modal) {
        $('body').append('<div id="' + modalId + '" class="modal"></div>');
        modal = document.getElementById(modalId);
    }

    // Construir el contenido del modal
    var modalContent = '<div class="modal-content historial-modal-content">';
    modalContent += '<span class="close" onclick="closeHistorialModal()" style="position: absolute; right: 20px; top: 10px; font-size: 28px; cursor: pointer; z-index: 10001; color: #333;">&times;</span>';
    
    // Cabecera
    modalContent += '<div class="historial-header">';
    modalContent += '<h2>HISTORIAL DE TRANSACCIONES</h2>';
    modalContent += '</div>';
    
    // Grid de columnas - cambiar a 5 columnas
    modalContent += '<div class="historial-grid" style="grid-template-columns: repeat(5, 1fr);">';
    
    // Columna Kuros
    modalContent += '<div class="historial-column">';
    modalContent += '<div class="historial-column-header kuros-header">';
    modalContent += '<div style="display: flex; align-items: center; justify-content: center; gap: 10px;">';
    modalContent += '<img style="height: 24px;" src="/images/op/uploads/KuroPoint_One_Piece_Gaiden_Foro_Rol.png">';
    modalContent += 'KUROS';
    modalContent += '</div>';
    modalContent += '</div>';
    modalContent += '<div class="historial-column-content" id="historial-kuros">';
    modalContent += cargarHistorialKuros();
    modalContent += '</div>';
    modalContent += '</div>';
	
	// Columna Experiencia (NUEVA)
    modalContent += '<div class="historial-column">';
    modalContent += '<div class="historial-column-header experiencia-header">';
    modalContent += '<div style="display: flex; align-items: center; justify-content: center; gap: 10px;">';
    modalContent += '<img style="height: 24px;" src="/images/op/ficha/SolNivel_One_Piece_Gaiden_Foro_Rol.png">';
    modalContent += 'EXPERIENCIA';
    modalContent += '</div>';
    modalContent += '</div>';
    modalContent += '<div class="historial-column-content" id="historial-experiencia">';
    modalContent += cargarHistorialExperiencia();
    modalContent += '</div>';
    modalContent += '</div>';
	
	// Columna Nikas
    modalContent += '<div class="historial-column">';
    modalContent += '<div class="historial-column-header nikas-header">';
    modalContent += '<div style="display: flex; align-items: center; justify-content: center; gap: 10px;">';
    modalContent += '<img style="height: 24px;" src="/images/op/indice/SolMenu_One_Piece_Gaiden_Foro_Rol.png">';
    modalContent += 'NIKAS';
    modalContent += '</div>';
    modalContent += '</div>';
    modalContent += '<div class="historial-column-content" id="historial-nikas">';
    modalContent += cargarHistorialNikas();
    modalContent += '</div>';
    modalContent += '</div>';
	
	// Columna Berries
    modalContent += '<div class="historial-column">';
    modalContent += '<div class="historial-column-header berries-header">';
    modalContent += '<div style="display: flex; align-items: center; justify-content: center; gap: 10px;">';
    modalContent += '<img style="height: 24px;" src="https://64.media.tumblr.com/ef457fd69a4ae7fdfb7ce6d2daa27de1/tumblr_nkz8u0C2UL1up54j8o1_1280.png">';
    modalContent += 'BERRIES';
    modalContent += '</div>';
    modalContent += '</div>';
    modalContent += '<div class="historial-column-content" id="historial-berries">';
    modalContent += cargarHistorialBerries();
    modalContent += '</div>';
    modalContent += '</div>';
    
    // Columna Puntos de Oficio
    modalContent += '<div class="historial-column">';
    modalContent += '<div class="historial-column-header puntos-oficio-header">';
    modalContent += '<div style="display: flex; align-items: center; justify-content: center; gap: 10px;">';
    modalContent += '<img style="height: 28px;" src="https://static.thenounproject.com/png/5421605-200.png">';
    modalContent += 'PUNTOS OFICIO';
    modalContent += '</div>';
    modalContent += '</div>';
    modalContent += '<div class="historial-column-content" id="historial-puntos-oficio">';
    modalContent += cargarHistorialPuntosOficio();
    modalContent += '</div>';
    modalContent += '</div>';
    
    
    modalContent += '</div>'; // Cierre historial-grid
    modalContent += '</div>'; // Cierre modal-content
    
    // Mostrar el modal con el contenido
    modal.innerHTML = modalContent;
    modal.style.display = "block";
    
    // Cerrar el modal al hacer clic fuera
    modal.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    };
}

// Funciones para cargar el historial de cada tipo de moneda
function cargarHistorialNikas() {
    var historial = [];
    
    for (let i = 0; i < historial_nikas.length; i++) {
        let historialNikas = parseHistorialNikas(historial_nikas[i].log, historial_nikas[i].categoria);
        if (historialNikas.nikas == 0) { continue; }
        historial.push({ fecha: historial_nikas[i].tiempo, ...historialNikas });
    }
    
	console.log(historial);
    let html = historial.map(item => {
        const claseColor = item.nikas > 0 ? 'cantidad-positiva' : 'cantidad-negativa';
        const signo = item.nikas > 0 ? '+' : '';
        return `
			<div class="historial-item nikas-item">
			  <div class="historial-item-fecha">${extractDate(item.fecha)}</div>
			  <div class="historial-item-descripcion">${item.descripcion}</div>
			  <div class="historial-item-cantidad ${claseColor}">${signo}${item.nikas.toFixed(2)} Nikas</div>
			  <div class="historial-item-cantidad">${item.before} + ${item.nikas.toFixed(2)} = ${item.after}</div>
			</div>`;
		}).join('');

    return html || '<div style="text-align: center; color: #666; margin-top: 50px;">Sin transacciones</div>';
}

function parseHistorialKuros(input, categoria) {
	
	if (categoria == '[Post]') {
		
		const threadMatch = input.match(/\[\s*(\d*)\s*\|\|\|\s*(.*?)\s*\]/);

		let thread = null;
		let title = "";

		if (threadMatch) {
			// threadMatch[1] puede estar vacío, así que solo parseamos si tiene valor
			if (threadMatch[1]) {
				thread = parseInt(threadMatch[1], 10);
			}

			// threadMatch[2] puede estar vacío
			if (threadMatch[2]) {
				title = threadMatch[2].trim();
			}
		}
		
		let descripcion = '';
		
		if (threadMatch && threadMatch[0] === '[ ||| ]') {
			descripcion = `Creación de tema`;
		} else {
			descripcion = `Post en tema <a target="_blank" href="/showthread.php?tid=${thread}">${title}</a>.`;
		}
		return { descripcion, ...extractKurosPost(input) };
	} else {
		console.log({ descripcion: categoria, ...extractKuros(input) });
		return { descripcion: categoria, ...extractKuros(input) };
	}	
}

function parseHistorialExperiencia(input, categoria) {
	if (categoria == '[Post]') {
		// const descripcion = `Post en tema`;

		const threadMatch = input.match(/\[\s*(\d*)\s*\|\|\|\s*(.*?)\s*\]/);

		let thread = null;
		let title = "";

		if (threadMatch) {
			// threadMatch[1] puede estar vacío, así que solo parseamos si tiene valor
			if (threadMatch[1]) {
				thread = parseInt(threadMatch[1], 10);
			}

			// threadMatch[2] puede estar vacío
			if (threadMatch[2]) {
				title = threadMatch[2].trim();
			}
		}
		
		let descripcion = '';
		
		if (threadMatch && threadMatch[0] === '[ ||| ]') {
			descripcion = `Creación de tema`;
		} else {
			descripcion = `Post en tema <a target="_blank" href="/showthread.php?tid=${thread}">${title}</a>.`;
		}
		
		return { descripcion, ...extractExperienciaPost(input) };
	} else if (categoria == '[Modificación de experiencia]') {
		const descripcion = `Modificación de atributos`;
		return { descripcion, ...extractExperiencia(input) };
	} else {
		return { descripcion: categoria, ...extractExperiencia(input) };
	}
	
}

function extractKuros(input) {
	const match = input.match(/Kuros:\s*(-?\d+)->(-?\d+)\s*\((-?\d+)\)/);

	const kurosBefore = match ? parseInt(match[1]) : 0;
	const kuros = match ? parseInt(match[3]) : 0;
	const kurosAfter = match ? parseInt(match[2]) : 0;
	
	return { kuros, kurosBefore, kurosAfter };
}

function extractKurosPost(input) {
	const kurosMatch = input.match(/([\d.]+) Kuros \(([\d.]+) \+ [\d.]+ = ([\d.]+)\)/);
	
	const kuros = kurosMatch ? parseFloat(kurosMatch[1]) : 0;
	const kurosBefore = kurosMatch ? parseFloat(kurosMatch[2]) : 0;
	const kurosAfter = kurosMatch ? parseFloat(kurosMatch[3]) : 0;
	
	return { kuros, kurosBefore, kurosAfter };
}



function extractExperienciaPost(input) {

    const match = input.match(/(-?\d+(?:\.\d+)?) de Experiencia \((\d+(?:\.\d+)?) \+ (-?\d+(?:\.\d+)?) = (\d+(?:\.\d+)?)\)/);

    if (!match) {
        return { experiencia: 0, experienciaBefore: 0, experienciaAfter: 0 };
    }

    const experiencia = parseFloat(match[3]);       // change
    const experienciaBefore = parseFloat(match[2]); // before
    const experienciaAfter = parseFloat(match[4]);  // after

    return { experiencia, experienciaBefore, experienciaAfter };
}

function extractExperiencia(input) {
	const match = input.match(/Experiencia:\s*(-?\d+(?:\.\d+)?)->(-?\d+(?:\.\d+)?)\s*\((-?\d+)\)/);

	const experienciaBefore = match ? parseInt(match[1]) : 0;
	const experienciaAfter = match ? parseInt(match[2]) : 0;
	const experiencia = match ? parseInt(match[3]) : 0;
	
	return { experiencia, experienciaBefore, experienciaAfter };
}

function extractNikas(input) {
	const match = input.match(/Nikas:\s*(-?\d+)->(-?\d+)\s*\((-?\d+)\)/);

	const before = match ? parseInt(match[1]) : 0;
	const after = match ? parseInt(match[2]) : 0;
	const nikas = match ? parseInt(match[3]) : 0;
	
	return { nikas, before, after };
}

function extractBerries(input) {
	const match = input.match(/Berries:\s*(-?\d+)->(-?\d+)\s*\((-?\d+)\)/);

	const before = match ? parseInt(match[1]) : 0;
	const after = match ? parseInt(match[2]) : 0;
	const berries = match ? parseInt(match[3]) : 0;
	
	return { berries, before, after };
}

function extractPuntosOficio(input) {
	const match = input.match(/Puntos de oficio:\s*(-?\d+)->(-?\d+)\s*\((-?\d+)\)/);

	const before = match ? parseInt(match[1]) : 0;
	const after = match ? parseInt(match[2]) : 0;
	const puntos_oficio = match ? parseInt(match[3]) : 0;
	
	return { puntos_oficio, before, after };
}

function parseHistorialNikas(input, categoria) {
	return { descripcion: categoria, ...extractNikas(input) };
}

function parseHistorialPuntosOficio(input, categoria) {
	return { descripcion: categoria, ...extractPuntosOficio(input) };
}

function parseHistorialBerries(input, categoria) {
	return { descripcion: categoria, ...extractBerries(input) };
}

function cargarHistorialKuros() {
    var historial = [];
    
    for (let i = 0; i < historial_kuro.length; i++) {
        let historialKuros = parseHistorialKuros(historial_kuro[i].log, historial_kuro[i].categoria);
        if (historialKuros.kuros == 0) { continue; }
        historial.push({ fecha: historial_kuro[i].tiempo, ...historialKuros });
    }
    
    let html = historial.map(item => {
        const claseColor = item.kuros > 0 ? 'cantidad-positiva' : 'cantidad-negativa';
        const signo = item.kuros > 0 ? '+' : '';
        return `
			<div class="historial-item kuros-item">
			  <div class="historial-item-fecha">${extractDate(item.fecha)}</div>
			  <div class="historial-item-descripcion">${item.descripcion}</div>
			  <div class="historial-item-cantidad ${claseColor}">${signo}${item.kuros.toFixed(2)} Kuros</div>
			  <div class="historial-item-cantidad">${item.kurosBefore} + ${item.kuros.toFixed(2)} = ${item.kurosAfter}</div>
			</div>`;
		}).join('');

    return html || '<div style="text-align: center; color: #666; margin-top: 50px;">Sin transacciones</div>';
}

function cargarHistorialExperiencia() {
    var historial = [];
    
	
    for (let i = 0; i < historial_experiencia.length; i++) {
        let historialExperiencia = parseHistorialExperiencia(historial_experiencia[i].log, historial_experiencia[i].categoria);
        if (historialExperiencia.experiencia == 0) { continue; }
        historial.push({ fecha: historial_experiencia[i].tiempo, ...historialExperiencia });
    }

    let html = historial.map(item => {
        const claseColor = item.experiencia > 0 ? 'cantidad-positiva' : 'cantidad-negativa';
        const signo = item.experiencia > 0 ? '+' : '';
        return `
			<div class="historial-item kuros-item">
			  <div class="historial-item-fecha">${extractDate(item.fecha)}</div>
			  <div class="historial-item-descripcion">${item.descripcion}</div>
			  <div class="historial-item-cantidad ${claseColor}">${signo}${item.experiencia.toFixed(2)} EXP</div>
			  <div class="historial-item-cantidad">${item.experienciaBefore} + ${item.experiencia.toFixed(2)} = ${item.experienciaAfter}</div>
			</div>`;
		}).join('');

    return html || '<div style="text-align: center; color: #666; margin-top: 50px;">Sin transacciones</div>';
}


function cargarHistorialPuntosOficio() {
    var historial = [];
    
    for (let i = 0; i < historial_puntos_oficio.length; i++) {
        let historialPuntosOficio = parseHistorialPuntosOficio(historial_puntos_oficio[i].log, historial_puntos_oficio[i].categoria);
        if (historialPuntosOficio.puntos_oficio == 0) { continue; }
        historial.push({ fecha: historial_puntos_oficio[i].tiempo, ...historialPuntosOficio });
    }
    

    let html = historial.map(item => {
        const claseColor = item.puntos_oficio > 0 ? 'cantidad-positiva' : 'cantidad-negativa';
        const signo = item.puntos_oficio > 0 ? '+' : '';
        return `
			<div class="historial-item puntos-oficio-item">
			  <div class="historial-item-fecha">${extractDate(item.fecha)}</div>
			  <div class="historial-item-descripcion">${item.descripcion}</div>
			  <div class="historial-item-cantidad ${claseColor}">${signo}${item.puntos_oficio.toFixed(2)} Puntos de Oficio</div>
			  <div class="historial-item-cantidad">${item.before} + ${item.puntos_oficio.toFixed(2)} = ${item.after}</div>
			</div>`;
		}).join('');

    return html || '<div style="text-align: center; color: #666; margin-top: 50px;">Sin transacciones</div>';
}

function cargarHistorialBerries() {
    var historial = [];
    
    for (let i = 0; i < historial_berries.length; i++) {
        let historialBerries = parseHistorialBerries(historial_berries[i].log, historial_berries[i].categoria);
        if (historialBerries.berries == 0) { continue; }
        historial.push({ fecha: historial_berries[i].tiempo, ...historialBerries });
    }
    
    let html = historial.map(item => {
        const claseColor = item.berries > 0 ? 'cantidad-positiva' : 'cantidad-negativa';
        const signo = item.berries > 0 ? '+' : '';
        return `
			<div class="historial-item berries-item">
			  <div class="historial-item-fecha">${extractDate(item.fecha)}</div>
			  <div class="historial-item-descripcion">${item.descripcion}</div>
			  <div class="historial-item-cantidad ${claseColor}">${signo}${item.berries.toFixed(0)} Berries</div>
			  <div class="historial-item-cantidad">${item.before} + ${item.berries.toFixed(0)} = ${item.after}</div>
			</div>`;
		}).join('');

    return html || '<div style="text-align: center; color: #666; margin-top: 50px;">Sin transacciones</div>';
}

function extractDate(dateTimeStr) {
    // Divide la cadena por el espacio y toma la primera parte
    if (!dateTimeStr) return "";
    return dateTimeStr.split(" ")[0];
}

// Función para cerrar el modal de historial
function closeHistorialModal() {
    var modal = document.getElementById('historialModal');
    if (modal) {
        modal.style.display = "none";
    }
}

// Cerrar modal con tecla Escape
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeHistorialModal();
    }
});

// Función para hacer el contenedor de akuma clickeable y mostrar el modal
function openAkumaModal() {
    if (!hasAkuma || !akumaNombre || akumaNombre === '')
    
    var modal = document.getElementById("akumaModal");
    if (!modal) {
        $('body').append('<div id="akumaModal" class="modal"></div>');
        modal = document.getElementById("akumaModal");
    }

    // Determinar colores según categoría
    var frutaGradient = '';
    if (akumaCategoria == 'Paramecia') {
        frutaGradient = 'linear-gradient(90deg, rgba(0, 116, 143, 0) 0%, #ef3c3c 20%, #810b0b 50%, #ef3c3c 80%, rgba(0, 116, 143, 0) 100%)';
    } else if (akumaCategoria == 'Logia') {
        frutaGradient = 'linear-gradient(90deg, rgba(0, 116, 143, 0) 0%, #4CAF50 20%, #2E7D32 50%, #4CAF50 80%, rgba(0, 116, 143, 0) 100%)';
    } else if (akumaCategoria == 'Zoan') {
        frutaGradient = 'linear-gradient(90deg, rgba(0, 116, 143, 0) 0%, #FF9800 20%, #E65100 50%, #FF9800 80%, rgba(0, 116, 143, 0) 100%)';
    }

    // Función para obtener información del nivel de control de Akuma
    function getControlAkumaInfo(nivel) {
		var rangos = [];
		if (fichaCamino !== 'Akuma') {
			rangos = [
            { nombre: 'Dominio Básico', costo: 0 },
            { nombre: 'Pasiva 1', costo: 5 },
            { nombre: 'Dominio Intermedio', costo: 10 },
            { nombre: 'Pasiva 2', costo: 20 },
            { nombre: 'Dominio Maestro', costo: 25 },
            { nombre: 'Pasiva 3', costo: 40 }
            //{ nombre: 'Despertado', costo: 75 } → No hay despertar en este camino
        	];	
		} else if (fichaCamino === 'Akuma') {
			rangos = [
            { nombre: 'Dominio Básico', costo: 0 },
            { nombre: 'Pasiva 1', costo: 0 },
            { nombre: 'Dominio Intermedio', costo: 5 },
            { nombre: 'Pasiva 2', costo: 15 },
            { nombre: 'Dominio Maestro', costo: 20 },
            { nombre: 'Pasiva 3', costo: 30 },
            { nombre: 'Despertado', costo: 80 }
        	];
		}
        
        
        return rangos[nivel] || rangos[0];
    }

    // Obtener información actual y siguiente nivel
    const controlAkumaActual = parseInt(control_akuma) || 0;
    
    const rangoActual = getControlAkumaInfo(controlAkumaActual);
    const siguienteRango = getControlAkumaInfo(controlAkumaActual + 1);
    
    let nivelesDesbloqueados = '';
	for (let i = 0; i <= controlAkumaActual; i++) {
		const rangoInfo = getControlAkumaInfo(i);
		const esActual = (i === controlAkumaActual);
		nivelesDesbloqueados += `
			<div class="control-nivel-desbloqueado" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; padding: 6px 10px; background: ${esActual ? 'linear-gradient(90deg, #4CAF50 0%, #45a049 100%)' : 'linear-gradient(90deg, #81C784 0%, #66BB6A 100%)'}; border-radius: 5px; color: white; border-left: 4px solid ${esActual ? '#2E7D32' : '#4CAF50'};">
				<div style="display: flex; align-items: center;">
					<span style="font-family: 'moonGetHeavy'; font-size: ${esActual ? '16px' : '14px'}; margin-right: 10px; text-shadow: 1px 1px 2px black;">${rangoInfo.nombre}</span>
					${esActual ? `<span style="background: rgba(255,255,255,0.3); padding: 2px 8px; border-radius: 12px; font-family: 'InterRegular'; font-size: 10px; font-weight: bold;">ACTUAL</span>` : ''}
				</div>
			</div>
		`;
	}

	// Siguiente nivel o nivel máximo
	let siguienteNivelHtml = '';
	if (controlAkumaActual < 6 && is_owner) {
		const puedeDesbloquear = nikas >= siguienteRango.costo;
		const colorBoton = puedeDesbloquear ? '#FF9800' : '#666666';
		const cursorBoton = puedeDesbloquear ? 'pointer' : 'not-allowed';
		siguienteNivelHtml = `
			<div class="control-siguiente" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; background: linear-gradient(90deg, #2196F3 0%, #1976D2 100%); border-radius: 5px; color: white;">
				<div style="display: flex; flex-direction: column;">
					<span style="font-family: 'moonGetHeavy'; font-size: 12px;">Siguiente Nivel:</span>
					<span style="font-family: 'moonGetHeavy'; font-size: 14px; text-shadow: 1px 1px 2px black;">${siguienteRango.nombre}</span>
				</div>
				<div style="display: flex; flex-direction: column; align-items: center;">
					<span style="font-family: 'moonGetHeavy'; font-size: 10px; margin-bottom: 5px;">Costo: ${siguienteRango.costo} Nikas</span>
					${(is_owner || g_is_staff) ? `<button onclick="desbloquearControlAkuma()" style="background-color: ${colorBoton}; color: white; border: none; border-radius: 3px; padding: 5px 10px; font-family: 'moonGetHeavy'; font-size: 10px; cursor: ${cursorBoton}; transition: all 0.3s;" ${puedeDesbloquear ? '' : 'disabled'}>DESBLOQUEAR</button>` : ''}
				</div>
			</div>
		`;
	} else if (controlAkumaActual == 6) {
		siguienteNivelHtml = `
			<div class="control-maximo" style="text-align: center; padding: 15px; background: linear-gradient(90deg, #9C27B0 0%, #7B1FA2 100%); border-radius: 50px; color: white;">
				<span style="font-family: 'moonGetHeavy'; font-size: 16px; text-shadow: 1px 1px 2px black;">¡NIVEL MÁXIMO ALCANZADO!</span>
				<div style="font-family: 'InterRegular'; font-size: 12px; margin-top: 5px; opacity: 0.9;">Has dominado completamente tu Akuma no Mi</div>
			</div>
		`;
	}

	// Dominios y Pasivas
	let dominiosHtml = '';
	if (akumaDominios && akumaDominios !== '') {
		dominiosHtml = `
			<div class="akuma-descripcion-background-libro" style="background: ${frutaGradient}; margin-top: 15px;">
				<div class="akuma-descripcion-titulo-libro">Dominios</div>
			</div>
			<div class="akuma-descripcion-libro">${akumaDominios}</div>
		`;
	}
	let pasivasHtml = '';
	if (akumaPasivas && akumaPasivas !== '') {
		pasivasHtml = `
			<div class="akuma-descripcion-background-libro" style="background: ${frutaGradient}; margin-top: 15px;">
				<div class="akuma-descripcion-titulo-libro">Pasivas</div>
			</div>
			<div class="akuma-descripcion-libro">${akumaPasivas}</div>
		`;
	}

	// Modal HTML usando template literal
	var modalContent = `
	<div class="modal-content akuma-libro-modal">
		<span class="close" onclick="closeAkumaModal()" style="color: white; float: right; font-size: 28px; font-weight: bold; position: absolute; right: 20px; top: 10px; z-index: 1001; cursor: pointer;">&times;</span>
		<div class="modal-body akuma-libro-body">
			<div class="akuma-libro-left">
				<div class="akuma-id-badge">ID: ${(akumaNombre ? akumaNombre.substring(0, 3).toUpperCase() : 'N/A')}</div>
				<div class="akuma-nombre-libro">${akumaNombre}</div>
				<div class="akuma-subnombre-libro">${akumaSubnombre || ''}</div>
				<div class="akuma-tipo-tier-libro" style="background: ${frutaGradient};">
					<div class="akuma-tipo-tier-text"><span class="akuma-tipo">${akumaCategoria}</span> | Tier <span class="akuma-tier">${akumaTier}</span></div>
				</div>
				<div class="akuma-imagen-libro-container">
					<img class="akuma-imagen-libro" src="${akumaImagen}" alt="${akumaNombre}">
				</div>
			</div>
			<div class="akuma-libro-right">
				<div id="npc_info_akuma" class="npc-info-akuma" style="margin-top: -140px;"></div>
				<div class="akuma-descripcion-background-libro" style="background: ${frutaGradient};">
					<div class="akuma-descripcion-titulo-libro">Descripción</div>
				</div>
				<div class="akuma-descripcion-libro" style="max-height: 107px;">${akumaDescripcion || 'Sin descripción disponible'}</div>
				<div class="akuma-descripcion-background-libro" style="background: ${frutaGradient}; margin-top: 0px;">
					<div class="akuma-descripcion-titulo-libro">Control de Akuma</div>
				</div>
				<div class="akuma-control-container" style="padding: 0px 70px; border-radius: 5px; margin-top: 5px;">
					${nivelesDesbloqueados}
					${siguienteNivelHtml}
				</div>
				${dominiosHtml}
				${pasivasHtml}
			</div>
		</div>
	</div>
	`;
    
    // Mostrar el modal
    modal.innerHTML = modalContent;
    modal.style.display = "block";
    
    // Cerrar modal al hacer clic fuera
    modal.onclick = function(event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    };
}

// Función para desbloquear el siguiente nivel de control de Akuma
function desbloquearControlAkuma() {
    const controlAkumaActual = parseInt(control_akuma) || 0;
    
    // Costos por nivel
	var costos = [];
	var rangos = [];
	var rangosDisponibles = [];
	var nivelNecesarioSiguiente;
	//debugger;
	if (fichaCamino === 'Voz') {
		nivelRequerido = 
		costos = [0, 5, 10, 20, 25, 40];
    	rangos = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
	} else if (fichaCamino !== "Haki") {
		costos = [0, 0, 5, 15, 20, 30, 80];
    	rangos = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
	} 
    
    if (controlAkumaActual >= 6) {
        alert('Ya has alcanzado el nivel máximo de control de Akuma.');
        return;
    }
    
    const siguienteNivel = controlAkumaActual + 1;
    const costoRequerido = costos[siguienteNivel];
    const nombreSiguienteNivel = rangos[siguienteNivel];
    
	if (akumaTier > 1) {
		var SCase = Number(akumaTier);
		switch (SCase) {
			case 1:
				if (nivelUsuario < 6){
					rangosDisponibles = ['Dominio Básico'];
					nivelNecesarioSiguiente = 6;
				} else if (nivelUsuario >= 6 && nivelUsuario < 10) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1'];
					nivelNecesarioSiguiente = 10;
				} else if (nivelUsuario >= 10 && nivelUsuario < 15) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio'];
					nivelNecesarioSiguiente = 15;
				} else if (nivelUsuario >= 15 && nivelUsuario < 18) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2'];
					nivelNecesarioSiguiente = 18;
				} else if (nivelUsuario >= 18 && nivelUsuario < 21) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro'];
					nivelNecesarioSiguiente = 21;
				} else if (nivelUsuario >= 21 && nivelUsuario < 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
					nivelNecesarioSiguiente = 35;
				} else if (nivelUsuario >= 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
				}
				break;
			case 2:
				if (nivelUsuario < 6){
					rangosDisponibles = ['Dominio Básico'];
					nivelNecesarioSiguiente = 6;
				} else if (nivelUsuario >= 6 && nivelUsuario < 11) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1'];
					nivelNecesarioSiguiente = 11;
				} else if (nivelUsuario >= 11 && nivelUsuario < 16) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio'];
					nivelNecesarioSiguiente = 16;
				} else if (nivelUsuario >= 16 && nivelUsuario < 19) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2'];
					nivelNecesarioSiguiente = 19;
				} else if (nivelUsuario >= 19 && nivelUsuario < 22) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro'];
					nivelNecesarioSiguiente = 22;
				} else if (nivelUsuario >= 22 && nivelUsuario < 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
					nivelNecesarioSiguiente = 35;
				} else if (nivelUsuario >= 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
				}
				break;
			case 3:
				if (nivelUsuario < 7){
					rangosDisponibles = ['Dominio Básico'];
					nivelNecesarioSiguiente = 7;
				} else if (nivelUsuario >= 7 && nivelUsuario < 12) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1'];
					nivelNecesarioSiguiente = 12;
				} else if (nivelUsuario >= 12 && nivelUsuario < 17) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio'];
					nivelNecesarioSiguiente = 17;
				} else if (nivelUsuario >= 17 && nivelUsuario < 21) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2'];
					nivelNecesarioSiguiente = 21;
				} else if (nivelUsuario >= 21 && nivelUsuario < 25) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro'];
					nivelNecesarioSiguiente = 25;
				} else if (nivelUsuario >= 25 && nivelUsuario < 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
					nivelNecesarioSiguiente = 35;
				} else if (nivelUsuario >= 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
				}
				break;
			case 4:
				if (nivelUsuario < 7){
					rangosDisponibles = ['Dominio Básico'];
					nivelNecesarioSiguiente = 7;
				} else if (nivelUsuario >= 7 && nivelUsuario < 13) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1'];
					nivelNecesarioSiguiente = 13;
				} else if (nivelUsuario >= 13 && nivelUsuario < 18) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio'];
					nivelNecesarioSiguiente = 18;
				} else if (nivelUsuario >= 18 && nivelUsuario < 21) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2'];
					nivelNecesarioSiguiente = 21;
				} else if (nivelUsuario >= 21 && nivelUsuario < 26) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro'];
					nivelNecesarioSiguiente = 26;
				} else if (nivelUsuario >= 26 && nivelUsuario < 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
					nivelNecesarioSiguiente = 35;
				} else if (nivelUsuario >= 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
				}
				break;
			case 5:
				if (nivelUsuario < 8){
					rangosDisponibles = ['Dominio Básico'];
					nivelNecesarioSiguiente = 8;
				} else if (nivelUsuario >= 8 && nivelUsuario < 14) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1'];
					nivelNecesarioSiguiente = 14;
				} else if (nivelUsuario >= 14 && nivelUsuario < 20) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio'];
					nivelNecesarioSiguiente = 20;
				} else if (nivelUsuario >= 20 && nivelUsuario < 25) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2'];
					nivelNecesarioSiguiente = 25;
				} else if (nivelUsuario >= 25 && nivelUsuario < 29) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro'];
					nivelNecesarioSiguiente = 29;
				} else if (nivelUsuario >= 29 && nivelUsuario < 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
					nivelNecesarioSiguiente = 35;
				} else if (nivelUsuario >= 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
				}
				break;
			case 6:
				if (nivelUsuario < 8){
					rangosDisponibles = ['Dominio Básico'];
					nivelNecesarioSiguiente = 8;
				} else if (nivelUsuario >= 8 && nivelUsuario < 15) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1'];
					nivelNecesarioSiguiente = 15;
				} else if (nivelUsuario >= 15 && nivelUsuario < 21) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio'];
					nivelNecesarioSiguiente = 21;
				} else if (nivelUsuario >= 21 && nivelUsuario < 26) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2'];
					nivelNecesarioSiguiente = 26;
				} else if (nivelUsuario >= 26 && nivelUsuario < 30) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro'];
					nivelNecesarioSiguiente = 30;
				} else if (nivelUsuario >= 30 && nivelUsuario < 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3'];
					nivelNecesarioSiguiente = 35;
				} else if (nivelUsuario >= 35) {
					rangosDisponibles = ['Dominio Básico', 'Pasiva 1', 'Dominio Intermedio', 'Pasiva 2', 'Dominio Maestro', 'Pasiva 3', 'Despertado'];
				}
				break;
		}
	}
	if (rangosDisponibles.includes(nombreSiguienteNivel)) {} else {
		alert('No tienes suficiente nivel. Necesitas mínimo nivel ' + nivelNecesarioSiguiente + '.');
		return;
	}
	
    if (nikas < costoRequerido) {
        alert('No tienes suficientes Nikas. Necesitas ' + costoRequerido + ' Nikas para desbloquear ' + nombreSiguienteNivel + '.');
        return;
    }
    
    if (confirm('¿Deseas desbloquear "' + nombreSiguienteNivel + '" por ' + costoRequerido + ' Nikas?')) {
        // Redirigir a la página de mejoras para procesar el desbloqueo
        window.location.href = '/op/mejoras.php?accion=control_akuma';
    }
}

// Función para cerrar el modal de akuma
function closeAkumaModal() {
    var modal = document.getElementById("akumaModal");
    if (modal) {
        modal.style.display = "none";
    }
}

function alzarPeso(kg) {
	if (kg >= 0 && kg <= 5) { return 1; }
	if (kg > 5 && kg <= 50) { return 5; }
	if (kg > 50 && kg <= 100) { return 10; }
	if (kg > 100 && kg <= 250) { return 20; }
	if (kg > 250 && kg <= 500) { return 30; }
	if (kg > 500 && kg <= 1000) { return 50; }
	if (kg > 1000 && kg <= 5000) { return 75; }
	if (kg > 5000 && kg <= 10000) { return 100; }
	if (kg > 10000 && kg <= 20000) { return 125; }
	if (kg > 20000 && kg <= 30000) { return 150; }
	if (kg > 30000 && kg <= 50000) { return 175; }
	if (kg > 50000 && kg <= 100000) { return 200; }
}

$('#fuerza_alzar').html(alzarPeso(peso));

// Funciones para el modal de cronología
function openCronologiaModal() {
    let cronologiaContent = '';
    let cronologiaActions = '';
    
    if (cronologia && cronologia.trim() !== '') {
        // Si hay una cronología, mostrar botón para ver en nueva ventana
        cronologiaContent = `
            <div style="text-align: center; padding: 10px; font-family: InterRegular; color: #8B4513;">
                <p style="margin: 5px 0; font-size: 14px;">Cronología disponible</p>
                <button onclick="abrirCronologia();" 
                        style="padding: 8px 16px; background-color: #ff7e00; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; font-weight: bold;">
                    📖 Ver
                </button>
            </div>
        `;
    } else {
        // Si no hay cronología, mostrar mensaje
        cronologiaContent = `
            <div style="text-align: center; padding: 10px; font-family: InterRegular; color: #8B4513;">
                <p style="margin: 5px 0; font-size: 14px;">No hay cronología disponible</p>
            </div>
        `;
    }
    
    // Si es el propietario o staff, mostrar botón para editar
    if (is_owner || g_is_staff) {
        cronologiaActions = `
            <div style="text-align: center; border-top: 1px solid #ffe59b; padding-top: 10px;">
                <input id="cronologiaURL" type="text" 
                       style="width: 85%; padding: 6px; margin-bottom: 8px; border: 1px solid #ffe59b; border-radius: 3px; font-size: 12px;"
                       placeholder="URL de la cronología..." 
                       value="${cronologia}">
                <br>
                <button onclick="cambiarCronologia();" 
                        style="padding: 6px 12px; background-color: #ff7e00; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">
                    Guardar
                </button>
            </div>
        `;
    }
    
    // Actualizar el contenido del modal
    document.getElementById('cronologiaContent').innerHTML = cronologiaContent;
    document.getElementById('cronologiaActions').innerHTML = cronologiaActions;
    
    // Mostrar el modal
    document.getElementById('cronologiaModal').style.display = "block";
    
    // Cerrar modal al hacer clic fuera
    document.getElementById('cronologiaModal').onclick = function(event) {
        if (event.target === document.getElementById('cronologiaModal')) {
            closeCronologiaModal();
        }
    };
}

function abrirCronologia() {
    if (cronologia && cronologia.trim() !== '') {
        window.open(cronologia, '_blank');
    }
}

function closeCronologiaModal() {
    document.getElementById('cronologiaModal').style.display = "none";
}

function cambiarCronologia() {
    let cronologiaUrl = document.getElementById('cronologiaURL').value;
    $.post(`/op/ficha.php?uid=${query_uid}`, { cambiar_cronologia: cronologiaUrl }, function(data) { 
        location.reload(); 
    });
}

//#endregion

/* WANTED MODAL */

// Función para abrir el modal del wanted
function openWantedModal() {
  const modal = document.getElementById('wantedModal');
  if (modal) {
    modal.style.display = 'block';
    // Formatear la reputación con separador de miles
    const berriesEl = document.getElementById('berriesWanted');
    if (berriesEl) {
      const reputacion = parseInt(berriesEl.textContent);
      if (!isNaN(reputacion)) {
        berriesEl.textContent = reputacion.toLocaleString('es-ES');
      }
    }
  }
}

// Función para cerrar el modal del wanted
function closeWantedModal() {
  const modal = document.getElementById('wantedModal');
  if (modal) {
    modal.style.display = 'none';
  }
}

// Cerrar modal al hacer clic fuera de él
window.addEventListener('click', function(event) {
  const modal = document.getElementById('wantedModal');
  if (event.target === modal) {
    closeWantedModal();
  }
});

/* Implantes */

if (implantes[0] != '') {
	for (let i = 0; i < implantes.length; i++) {
		let objeto = objetos_json[implantes[i]][0];
		let colorTier = '#faa500';
		const imagen_id = objeto.imagen_id;
		let efecto = '';

		if (imagen_id == '1') { colorTier = '#808080'; }
		if (imagen_id == '2') { colorTier = '#4dfe45'; }	
		if (imagen_id == '3') { colorTier = '#457bfe'; }
		if (imagen_id == '4') { colorTier = '#cf44ff'; }
		if (imagen_id == '5') { colorTier = '#febb46'; }
		if (parseInt(imagen_id) >= 6) { colorTier = 'linear-gradient(315deg, rgba(255,0,0,1) 0%, rgba(255,154,0,1) 10%, rgba(208,222,33,1) 20%, rgba(79,220,74,1) 30%, rgba(63,218,216,1) 40%, rgba(47,201,226,1) 50%, rgba(28,127,238,1) 60%, rgba(95,21,242,1) 70%, rgba(186,12,248,1) 80%, rgba(251,7,217,1) 90%, rgba(255,0,0,1) 100%);'; }

		if (objeto.efecto) {
			efecto = `<div style="font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;">` + objeto.efecto + `</div>`;
		}

		$('#implantes-container').append(`
			<div style="display: flex;flex-direction: row;border-bottom: 2px solid black;">
				<div>
					<div class="tooltip">
						<img style="width: 50px;" id="imagen-item" src="${objeto['imagen_avatar']}">
						<div class="tooltiptext item-tooltip" style=" left: 50px; ">
							<div style="font-size: 15px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: ` + colorTier + `;border-top-left-radius: 6px;border: 0px;border-top-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;">${objeto['nombre']}</div>

							<div class="mydescripcion" style="font-family: InterRegular;padding: 5px;text-align: justify;">` + objeto.descripcion + `</div>
							${efecto}

							<div style="font-family: moonGetHeavy;padding: 2px 5px;display: flex;flex-direction: row;justify-content: space-between;background: ` + colorTier + `;border-top: 1px solid black;border-bottom: 1px solid black;font-size: 9px;text-shadow: 1px 1px 2px black;filter: saturate(0.7);">
								<div>Cantidad: ` + objeto.cantidad + `</div>
								<div>` + objeto.espacios + ` Espacios</div>
								<div>ID: <span>` + objeto.objeto_id + `</span></div>
							</div>

							<div style="font-size: 9px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: ` + colorTier + `;border-bottom-left-radius: 6px;border: 0px;border-bottom-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;">` + objeto.subcategoria + ` - Tier ` + objeto.tier + `</div>
						</div>


					</div>
				</div>
				<div style="background-color: #8062d6;width: 100%;border-left: 2px solid black;font-size: 17px;color: white;font-family: moonGetHeavy;align-content: center;">${objeto['nombre']}</div>
			</div>
		`);
	}
} 

// Variables globales para Buso
let nikasCostoBuso = 10000;
let canGetBuso = false;
let nivelHakiBuso = 500;

if (buso == 0) {
	$('#buso_nivel').html('No Despertado');
	$('#buso_img').css('filter',  'grayscale(1)');
} else if (buso <= 6) {

	$('#buso_nivel').html(`Tier ${buso + 1} de Poder`);

	// Reinicializar valores para este nivel de buso
	nikasCostoBuso = 10000;
	canGetBuso = false;
	nivelHakiBuso = 500;
	

	
	if (buso == 1) { 
		if (nivel >= 15 || (hasFullHaki && nivel >= 10)) { canGetBuso = true; }
		if (hasFullHaki) { nikasCostoBuso = 0; } else { nikasCostoBuso = 10;  }
		nivelHakiBuso = 15; 
	}
	if (buso == 2) { 
		if (nivel >= 20 || (hasFullHaki && nivel >= 15)) { canGetBuso = true; }
		nivelHakiBuso = 20; nikasCostoBuso = 15; 
	}
	if (buso == 3) { 
		if (nivel >= 25 || (hasFullHaki && nivel >= 20)) { canGetBuso = true; }
		nivelHakiBuso = 25; nikasCostoBuso = 25; 
	}
	if (buso == 4) { 
		if (nivel >= 30 || (hasFullHaki && nivel >= 25)) { canGetBuso = true; }
		nivelHakiBuso = 30; nikasCostoBuso = 40; 
	}
	if (buso == 5) { 
		if (nivel >= 35 || (hasFullHaki && nivel >= 30)) { canGetBuso = true; }
		nivelHakiBuso = 35; nikasCostoBuso = 60; 
	}
	if (buso == 6) { 
		if (nivel >= 40 || (hasFullHaki && nivel >= 35)) { canGetBuso = true; }
		nivelHakiBuso = 40; nikasCostoBuso = 150; 
	}

	if (is_owner) {
		$('#buso_img').css('cursor',  'pointer');
	}
} else {
	if (buso == 1) {
		$('#buso_nivel').html(`Entrenable`);
	} else if (buso == 7 && hasFullHaki) {
		$('#buso_nivel').html(`Tier 9 de Poder`);
	} else {
		$('#buso_nivel').html(`Tier ${buso + 1} de Poder`);
	}
}

// Variables globales para Hao
let nikasCostoHao = 10000;
let canGetHao = false;
let nivelHakiHao = 500;

if (hao == -1) {
	$('#hao_nivel').html('Sin Obtener');
	$('#hao_img').css('filter',  'grayscale(1)');
} else if (hao == 0) {
	$('#hao_nivel').html('No Despertado');
	$('#hao_img').css('filter',  'grayscale(1)');
} else if (hao <= 6) {
	
	$('#hao_nivel').html(`Tier ${hao + 1} de Poder`);

	// Reinicializar valores para este nivel de hao
	nikasCostoHao = 10000;
	canGetHao = false;
	nivelHakiHao = 500;
	
	if (hao == 1) { 
		if (nivel >= 15 || (hasFullHaki && nivel >= 10)) { canGetHao = true; }
		if (hasFullHaki) { nikasCostoHao = 0; } else { nikasCostoHao = 10;  }
		nivelHakiHao = 15; 
	}
	if (hao == 2) { 
		if (nivel >= 20 || (hasFullHaki && nivel >= 15)) { canGetHao = true; }
		nivelHakiHao = 20; nikasCostoHao = 15; 
	}
	if (hao == 3) { 
		if (nivel >= 25 || (hasFullHaki && nivel >= 20)) { canGetHao = true; }
		nivelHakiHao = 25; nikasCostoHao = 25; 
	}
	if (hao == 4) { 
		if (nivel >= 30 || (hasFullHaki && nivel >= 25)) { canGetHao = true; }
		nivelHakiHao = 30; nikasCostoHao = 40; 
	}
	if (hao == 5) { 
		if (nivel >= 35 || (hasFullHaki && nivel >= 30)) { canGetHao = true; }
		nivelHakiHao = 35; nikasCostoHao = 60; 
	}
	if (hao == 6) { 
		if (nivel >= 40 || (hasFullHaki && nivel >= 35)) { canGetHao = true; }
		nivelHakiHao = 40; nikasCostoHao = 150; 
	}

	if (is_owner) {
		$('#hao_img').css('cursor',  'pointer');
	}
} else {
	if (hao == 1) {
		$('#hao_nivel').html(`Entrenable`);
	} else if (hao == 7 && hasFullHaki) {
		$('#hao_nivel').html(`Tier 9 de Poder`);
	} else {
		$('#hao_nivel').html(`Tier ${hao + 1} de Poder`);
	}
}

// Inicialización del progreso circular
const circularProgress = document.querySelectorAll(".circular-progress");

Array.from(circularProgress).forEach((progressBar) => {
  const progressValue = progressBar.querySelector(".percentage");
  const innerCircle = progressBar.querySelector(".inner-circle");
  let startValue = 0,
	endValue = Number(progressBar.getAttribute("data-percentage")),
	speed = 25,
	progressColor = progressBar.getAttribute("data-progress-color"),
	nivel = progressBar.getAttribute("data-nivel");

  const progress = setInterval(() => {
	startValue++;
	progressValue.textContent = nivel + '';
	progressValue.style.color = 'white';

	innerCircle.style.backgroundColor = progressBar.getAttribute("data-inner-circle-color");

	progressBar.style.background = `conic-gradient(` + progressColor + ` ` + startValue * 3.6 + `deg,` + progressBar.getAttribute("data-bg-color") + ` 0deg)`;
	if (startValue === endValue) {
	  clearInterval(progress);
	}
  }, speed);
});	

// Declaraciones de variables globales
let tecnica_descripciones = $('.tecnica_descripcion');
let tecnica_efectos = $('.tecnica_efecto');

let efectosCodigo = {
	'[Daño contundente]': { nombre: 'Daño contundente', texto: 'Provoca golpes que pueden causar entumecimiento, fracturas, mareos y suelen tener buena destructividad.' },
	'[Daño cortante]': { nombre: 'Daño cortante', texto: 'Provoca cortes que pueden causar hemorragias o amputaciones.' },
	'[Daño perforante]': { nombre: 'Daño perforante', texto: 'Pueden causar hemorragias. Tienen poca destructividad pero ignoran 40 puntos de defensa pasiva del enemigo.' },
	'[Daño elemental]': { nombre: 'Daño elemental', texto: 'Inflige daño basado en elementos naturales como fuego o agua.' },
	'[Daño espiritual]': { nombre: 'Daño espiritual', texto: 'Afecta la energía o espíritu del objetivo.' },
	'[Defensa pasiva física]': { nombre: 'Defensa pasiva física', texto: 'A partir de nivel 5 reduce el daño físico recibido en un valor igual a tu Resistencia.' },
	'[Defensa pasiva mental]': { nombre: 'Defensa pasiva mental', texto: 'A partir de nivel 5 reduce el daño espiritual recibido en un valor igual a tu Voluntad.' },
	'[Umbral del dolor]': { nombre: 'Umbral del dolor', texto: '' },
	'[Agarre]': { nombre: 'Agarre', texto: 'Inmoviliza al objetivo mediante un agarre fuerte.' },
	'[Asfixia]': { nombre: 'Asfixia', texto: 'Impide la respiración, causando daño progresivo.' },		
	'[Parálisis parcial]': { nombre: 'Parálisis parcial', texto: 'Inmoviliza parcialmente, limitando movimientos.' },
	'[Parálisis completa]': { nombre: 'Parálisis completa', texto: 'Inmoviliza totalmente, impidiendo cualquier movimiento.' },
	'[Canalizar]': { nombre: 'Canalizar', texto: 'Requiere permanecer concentrado en una tarea pero no limita el movimiento.' },
	'[Concentrar]': { nombre: 'Concentrar', texto: 'Requiere concentración absoluta, el usuario ha de mantenerse inmovil sin realizar técnica alguna.' },
	'[Ceguera]': { nombre: 'Ceguera', texto: 'Dificulta la visión temporalmente causando -12 Reflejos.' },
	'[Derribo]': { nombre: 'Derribo', texto: 'Lanza al objetivo haciéndole perder el equilibrio.' },
	'[Desarme]': { nombre: 'Desarme', texto: 'Fuerza al objetivo a soltar su arma o equipamiento.' },
	'[Desorientación]': { nombre: 'Desorientación', texto: 'Causa confusión y pérdida de dirección, -10 Reflejos.' },
	'[Confusión]': { nombre: 'Confusión', texto: 'Provoca alteración mental, dificultando la toma de decisiones, -10 Reflejos.' },
	'[Mareo]': { nombre: 'Mareo', texto: 'Desestabiliza al objetivo, dificultando su equilibrio, -15 Reflejos.' },
	'[Empuje]': { nombre: 'Empuje', texto: 'Mueve al objetivo hacia atrás con fuerza.' },
	'[Entumecimiento]': { nombre: 'Entumecimiento', texto: 'Reduce la movilidad debido a la rigidez corporal en la zona afectada, -15 Agilidad en dicha zona.' },
	'[Veneno leve]': { nombre: 'Veneno leve', texto: 'Inflige daño directo de 20 Puntos por turno durante 3 turnos.' },
	'[Veneno medio]': { nombre: 'Veneno medio', texto: 'Inflige daño directo de 30 Puntos por turno durante 4 turnos.' },
	'[Veneno grave]': { nombre: 'Veneno grave', texto: 'Inflige daño directo de 40 Puntos por turno, las curaciones serán un 50% menos efectivas mientras dure.' },
	'[Fractura]': { nombre: 'Fractura', texto: 'Rompe huesos, causando dolor y limitación de movimiento. -30 Agi en la zona.' },
	'[Frío]': { nombre: 'Frío', texto: 'En función de la diferencia entre el atributo que genera el efecto y el atributo resistencia del objetivo los efectos causados varían, revisar guía.' },
	'[Calor]': { nombre: 'Calor', texto: 'En función de la diferencia entre el atributo que genera el efecto y el atributo resistencia del objetivo los efectos causados varían, revisar guía.' },
	'[Hemorragia leve]': { nombre: 'Hemorragia leve', texto: 'Causa pérdida de sangre continua menor. Quita 10 puntos de energía cada turno durante 2 turnos.' },
	'[Hemorragia media]': { nombre: 'Hemorragia media', texto: 'Provoca pérdida de sangre continua moderada. Quita 20 puntos de energía cada turno durante 4 turnos.' },
	'[Hemorragia grave]': { nombre: 'Hemorragia grave', texto: 'Genera pérdida de sangre continua severa. Quita 40 puntos de energía cada turno de manera indefinida.' },
	'[Quemadura leve]': { nombre: 'Quemadura leve', texto: 'Produce daño superficial por calor. Hace 20 puntos de daño directo por turno durante 2 turnos.' },
	'[Quemadura media]': { nombre: 'Quemadura media', texto: 'Provoca daño significativo por calor. Hace 40 puntos de daño directo por turno durante 3 turnos.' },
	'[Quemadura grave]': { nombre: 'Quemadura grave', texto: 'Causa daño severo por calor. Hace 80 puntos de daño directo por turno.' },
	'[Miedo]': { nombre: 'Miedo', texto: 'Infunde temor, reduciendo la eficacia en combate, -10 Voluntad.' },
	'[Sordera]': { nombre: 'Sordera', texto: 'Dificulta la audición temporalmente, -8 Reflejos.' },
	'[Sueño]': { nombre: 'Sueño', texto: 'Induce un estado de sueño, el objetivo pierde 5 puntos en Fuerza, Agilidad, Destreza, Puntería, Control de Akuma y Reflejos.' },
	'[Terror]': { nombre: 'Terror', texto: 'Provoca un estado de pánico extremo, -15 Vol.' }
};

let efectosArray = Object.keys(efectosCodigo);

let belicasArray = [belica1, belica2, belica3, belica4, belica5, belica6, belica7, belica8, belica9, belica10, belica11, belica12];

let objeto_danos = $('.dano-objeto')

// Aplicar efectos de stats a elementos de daño de objetos
for (let i = 0; i < objeto_danos.length; i++) {
    let dano = $($('.dano-objeto')[i]);
    let efectoHtml = dano.html();
    
    dano.html(addEfectoStats(efectoHtml));
}

let statCodigo = {
	RES: 'Resistencia',
	FUE: 'Fuerza',
	DES: 'Destreza',
	PUN: 'Puntería',
	AGI: 'Agilidad',
	REF: 'Reflejos',
	CAK: 'Control de Akuma',
	VOL: 'Voluntad',
	Nivel: 'Nivel'
}

// Variables para mantener el estado de los filtros del inventario
let filtroInventario = {
	categorias: [],
	tiers: [],
	busqueda: ''
};

// Variables globales para el equipamiento
var tipoEquipamientoSeleccionado = '';

// Get the modal
var modal = document.getElementById("myModal");

// Event Handlers y código de inicialización

// Inicialización de pestañas de técnicas
$('#pestana-tecnicas').append(`
	<div class="pestana-tecnica todo" onclick="showBlock('todo', '#4856ff')" style="background-color: #ff6600;margin-top: 40px;height: 40px;width: 209px;position: relative;margin-left: -16px;">
		<div style=" color: white; font-size: 22px; text-align: center; position: relative; top: 4px; ">TODO</div>
	</div>`
);

$('#pestana-tecnicas').append(`
	<div class="pestana-tecnica belica1 ` + belica1 + `" onclick="showBlock('` + belica1 + `', '#4856ff')" style=" background-color: #4856ff;">
		<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica1 + `</div>
	</div>`
);

// Obtener técnicas de forma segura
let todoTecs = getTecnicas('todo');
let estiloTecs = belica1 ? getTecnicas(belica1) : [];
	
let tecUnicas = getTecnicas('Única');
let tecAkumas = getTecnicas('Akuma');

let tecEstilo1 = (estilo1 && estilo1 !== 'bloqueado') ? getTecnicas(estilo1) : [];
let tecEstilo2 = (estilo2 && estilo2 !== 'bloqueado') ? getTecnicas(estilo2) : [];
let tecEstilo3 = (estilo3 && estilo3 !== 'bloqueado') ? getTecnicas(estilo3) : [];
let tecEstilo4 = (estilo4 && estilo4 !== 'bloqueado') ? getTecnicas(estilo4) : [];
	
let especial = getTecnicas('Especial');
let raciales = getTecnicas('Racial');
let personaje = getTecnicas('Personaje');

// Seccion de elementales
let aero = elementos['Aero'];   
let aqua = elementos['Aqua'];   
let cryo = elementos['Cryo'];   
let piro = elementos['Piro'];   
let electro = elementos['Electro'];
	
$('#tecnicas-background').css('background-color', '#4856ff');

// Inicializar técnicas con el sistema de spoilers
// Restaurar último tab visitado o usar 'todo' por defecto
var lastTab = sessionStorage.getItem('lastTecnicasTab') || 'todo';
var lastColor = sessionStorage.getItem('lastTecnicasColor') || '#ff6600';
showBlock(lastTab, lastColor);

if (belica2) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica2 ` + belica2 + `" onclick="showBlock('` + belica2 + `', '#5aae29')" style=" background-color: #5aae29; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica2 +`</div>
		</div>`
	 );
}
	
if (belica3) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica3 ` + belica3 + `" onclick="showBlock('` + belica3 + `', '#1fc281')" style=" background-color: #1fc281; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica3 +`</div>
		</div>`
	 );
}
	
if (belica4) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica4 ` + belica4 + `" onclick="showBlock('` + belica4 + `', '#c2a61f')" style=" background-color: #c2a61f; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica4 +`</div>
		</div>`
	 );
}
	
if (belica5) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica5 ` + belica5 + `" onclick="showBlock('` + belica5 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica5 +`</div>
		</div>`
	 );
}
			
if (belica6) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica6 ` + belica6 + `" onclick="showBlock('` + belica6 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica6 +`</div>
		</div>`
	 );
}

if (belica7) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica7 ` + belica7 + `" onclick="showBlock('` + belica7 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica7 +`</div>
		</div>`
	 );
}

if (belica8) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica8 ` + belica8 + `" onclick="showBlock('` + belica8 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica8 +`</div>
		</div>`
	 );
}

if (belica9) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica9 ` + belica9 + `" onclick="showBlock('` + belica9 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica9 +`</div>
		</div>`
	 );
}

if (belica10) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica10 ` + belica10 + `" onclick="showBlock('` + belica10 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica10 +`</div>
		</div>`
	 );
}

if (belica11) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica11 ` + belica11 + `" onclick="showBlock('` + belica11 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica11 +`</div>
		</div>`
	 );
}

if (belica12) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica belica12 ` + belica12 + `" onclick="showBlock('` + belica12 + `', '#d462c2')" style=" background-color: #d462c2; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">` + belica12 +`</div>
		</div>`
	 );
}
	
if (tecUnicas && tecUnicas.length > 0) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica Única" onclick="showBlock('Única', '#8c29ae')" style=" background-color: #8c29ae; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Única</div>
		</div>`
	 );
}
	
if (tecAkumas && tecAkumas.length > 0) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica Akuma" onclick="showBlock('Akuma', '#5529ae')" style=" background-color: #5529ae; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Akuma</div>
		</div>`
	 );
}
	
if (tecEstilo1 && tecEstilo1.length > 0 && estilo1 && estilo1 !== 'bloqueado' && estilo1 !== 'no_bloqueado') {
	let estilo1P = estilo1.replace(' ', '_');
	
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica estilo1 ` + estilo1P + `" onclick="showBlock('` + estilo1P + `', '#2993ae')" style=" background-color: #2993ae; margin-top: 5px; ">
			<div style="color: white;font-size: 15px;text-align: center;position: relative;top: 7px;">` + estilo1 + `</div>
		</div>`
	 );
}

if (tecEstilo2 && tecEstilo2.length > 0 && estilo2 && estilo2 !== 'bloqueado' && estilo2 !== 'no_bloqueado') {
	let estilo2P = estilo2.replace(' ', '_');
	
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica estilo2 ` + estilo2P + `" onclick="showBlock('` + estilo2P + `', '#29ae79')" style=" background-color: #29ae79; margin-top: 5px; ">
			<div style="color: white;font-size: 15px;text-align: center;position: relative;top: 7px;">` + estilo2 + `</div>
		</div>`
	 );
}
	
if (tecEstilo3 && tecEstilo3.length > 0 && estilo3 && estilo3 !== 'bloqueado' && estilo3 !== 'no_bloqueado') {
	let estilo3P = estilo3.replace(' ', '_');
	
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica estilo3 ` + estilo3P + `" onclick="showBlock('` + estilo3P + `', '#97ae29')" style=" background-color: #97ae29; margin-top: 5px; ">
			<div style="color: white;font-size: 15px;text-align: center;position: relative;top: 7px;">` + estilo3 + `</div>
		</div>`
	 );
}
				
if (tecEstilo4 && tecEstilo4.length > 0 && estilo4 && estilo4 !== 'bloqueado' && estilo4 !== 'no_bloqueado') {
	let estilo4P = estilo4.replace(' ', '_');
	
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica estilo4 ` + estilo4P + `" onclick="showBlock('` + estilo4P + `', '#97ae29')" style=" background-color: #97ae29; margin-top: 5px; ">
			<div style="color: white;font-size: 15px;text-align: center;position: relative;top: 7px;">` + estilo4 + `</div>
		</div>`
	 );
}
	
if (raciales && raciales.length > 0) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica racial" onclick="showBlock('Racial', '#ff8636')" style=" background-color: #ff8636; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Raciales</div>
		</div>`
	 );	
}
	
if (personaje && personaje.length > 0) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica personaje" onclick="showBlock('Personaje', '#4bf15c')" style=" background-color: #4bf15c; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Personaje</div>
		</div>`
	 );	
}

if (especial && especial.length > 0) {
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica especial" onclick="showBlock('Especial', '#ae2969')" style=" background-color: #ae2969; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Especiales</div>
		</div>`
	 );	
}

if (tec_aprendidas_json['Haoshoku'] || tec_aprendidas_json['Kenbunshoku'] || tec_aprendidas_json['Busoshoku']) {
		
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica Haki" onclick="showBlock('Haki', '#05a3b9')" style=" background-color: #05a3b9; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Haki</div>
		</div>`
	 );
}

if (electro == 1 || piro == 1 || cryo == 1 || aqua == 1 || aero == 1) {
	
	$('#pestana-tecnicas').append(`
		<div class="pestana-tecnica Elementales" onclick="showBlock('Elementales', '#1e7563')" style=" background-color: #1e7563; margin-top: 5px; ">
			<div style=" color: white; font-size: 19px; text-align: center; position: relative; top: 4px; ">Elementos</div>
		</div>`
	 );
}

// Configuración de estilos de combate
if (estilo1 == 'bloqueado') {
    $('#estilo1').html(getBloqueadoHtml('1'));
} else if (estilo1 == 'no_bloqueado') {
    $('#estilo1').html(getNoBloqueadoHtml('1'));
} else {
    $('#estilo1').html(getEstiloHtml(estilo1));
}

if (estilo2 == 'bloqueado') {
    $('#estilo2').html(getBloqueadoHtml('2'));
} else if (estilo2 == 'no_bloqueado') {
    $('#estilo2').html(getNoBloqueadoHtml('2'));
} else {
    $('#estilo2').html(getEstiloHtml(estilo2));
}

if (estilo3 == 'bloqueado') {
    $('#estilo3').html(getBloqueadoHtml('3'));
} else if (estilo3 == 'no_bloqueado') {
    $('#estilo3').html(getNoBloqueadoHtml('3'));
} else {
    $('#estilo3').html(getEstiloHtml(estilo3));
}

if (estilo4 == 'bloqueado') {
    $('#estilo4').html(getBloqueadoHtml('4'));
} else if (estilo4 == 'no_bloqueado') {
    $('#estilo4').html(getNoBloqueadoHtml('4'));
} else {
    $('#estilo4').html(getEstiloHtml(estilo4));
}

// Configuración de Haki

// Variables globales para Kenbun
let nikasCostoKenbun = 10000;
let canGetKenbun = false;
let nivelHakiKenbun = 500;

if (kenbun == 0) {
	$('#kenbun_nivel').html('No Despertado');
	$('#kenbun_img').css('filter',  'grayscale(1)');
} else if (kenbun <= 6) {

	$('#kenbun_nivel').html(`Tier ${kenbun + 1} de Poder`);

	// Reinicializar valores para este nivel de kenbun
	nikasCostoKenbun = 10000;
	canGetKenbun = false;
	nivelHakiKenbun = 500;
	
	if (kenbun == 1) {
		if (nivel >= 10 || (hasFullHaki && nivel >= 5)) { canGetKenbun = true; }
		if (hasFullHaki) { nikasCostoKenbun = 0; } else { nikasCostoKenbun = 10;  }
		nivelHakiKenbun = 10;
	}
	if (kenbun == 2) { 
		if (nivel >= 20 || (hasFullHaki && nivel >= 15)) { canGetKenbun = true; }
		nivelHakiKenbun = 20; nikasCostoKenbun = 15; 
	}
	if (kenbun == 3) { 
		if (nivel >= 25 || (hasFullHaki && nivel >= 20)) { canGetKenbun = true; }
		nivelHakiKenbun = 25; nikasCostoKenbun = 25; 
	}
	if (kenbun == 4) { 
		if (nivel >= 30 || (hasFullHaki && nivel >= 25)) { canGetKenbun = true; }
		nivelHakiKenbun = 30; nikasCostoKenbun = 40; 
	}
	if (kenbun == 5) { 
		if (nivel >= 35 || (hasFullHaki && nivel >= 30)) { canGetKenbun = true; }
		nivelHakiKenbun = 35; nikasCostoKenbun = 60; 
	}
	if (kenbun == 6) { 
		if (nivel >= 40 || (hasFullHaki && nivel >= 35)) { canGetKenbun = true; }
		nivelHakiKenbun = 40; nikasCostoKenbun = 150; 
	}

	if (is_owner) {
		$('#kenbun_img').css('cursor',  'pointer');
	}
} else {
	
	if (kenbun == 1) {
		$('#kenbun_nivel').html(`Entrenable`);
	} else if (kenbun == 7 && hasFullHaki) {
		$('#kenbun_nivel').html(`Tier 9 de Poder`);
	} else {
		$('#kenbun_nivel').html(`Tier ${kenbun + 1} de Poder`);
	}
}

console.log(buso);
console.log(hasFullHaki);
// console.log(canGetBuso);

// Verificación de seguridad: asegurar que el objeto belicas existe
if (typeof belicas === 'undefined') {
	console.error('El objeto belicas no está definido');
	belicas = {};
}

// Configuración de Belicas
if (belica1) updateBelicaUI(belica1);
if (belica2) updateBelicaUI(belica2);
if (belica3) updateBelicaUI(belica3);
if (belica4) updateBelicaUI(belica4);
if (belica5) updateBelicaUI(belica5);
if (belica6) updateBelicaUI(belica6);
if (belica7) updateBelicaUI(belica7);
if (belica8) updateBelicaUI(belica8);
if (belica9) updateBelicaUI(belica9);
if (belica10) updateBelicaUI(belica10);
if (belica11) updateBelicaUI(belica11);
if (belica12) updateBelicaUI(belica12);

// Event listener para cerrar modal con click fuera
$(document).ready(function() {
    $(window).click(function(event) {
        var modal = document.getElementById('modalSelectorEquipamiento');
        if (event.target === modal) {
            cerrarSelectorEquipamiento();
        }
    });
    
    if (objetosEquipados.bolsa) {
        let bolsaObjetoId = objetos_json[objetosEquipados.bolsa][0].objeto_id;
        if (bolsaObjetoId) { equiparItemBolsa(objetos_json[objetosEquipados.bolsa][0].objeto_id); }
    }
    
    if (objetosEquipados.ropa) {
        let ropaObjetoId = objetos_json[objetosEquipados.ropa][0].objeto_id;
        if (ropaObjetoId) { equiparItemRopa(objetos_json[objetosEquipados.ropa][0].objeto_id); }
    }

    actualizarContadorEspacios();
});

// Ejecutar la carga de ejemplos cuando el documento esté listo
$(document).ready(function() {
    // Reemplazar la llamada a loadNpcsAcompanantesBio por nuestra función de ejemplo
    loadNpcsAcompanantesBio();
});

// Inicialización de efectos de técnicas
for (let i = 0; i < tecnica_descripciones.length; i++) {
	let descripcion = $($('.tecnica_descripcion')[i]);
	let descripcionHtml = descripcion.html();
	descripcion.html(addEfectos(descripcionHtml));
}

for (let i = 0; i < tecnica_efectos.length; i++) {
	let efecto = $($('.tecnica_efecto')[i]);
	let efectoHtml = efecto.html();
	efecto.html(addEfectoStats(efectoHtml));
}

// Inicialización de avatares mouseover
$(".avatar_biografia").mouseover(function() {
	const time = Math.round(Date.now() / 500) % 360;
	$(this).css("transition","all 10s ease-out");
	$(this).css("filter","hue-rotate(" + time + "deg)");
}).mouseout(function() {
	$(this).css("transition","all 5s ease-out");
	$(this).css("filter","none");
});

// Inicialización de virtudes, defectos y akuma
let puntos_virtud = 0;
for (let i = 0; i < virtudes_array_json.length; i++) {
	let key = virtudes_array_json[i];
	puntos_virtud += parseInt(virtudes_json[key][0].puntos);
}

let puntos_defecto = 0;
for (let i = 0; i < defectos_array_json.length; i++) {
	let key = defectos_array_json[i];
	puntos_defecto += parseInt(defectos_json[key][0].puntos);
}

$('#virtudes_puntos').html(puntos_virtud);
$('#defectos_puntos').html(puntos_defecto);

if (hasAkuma) {
	$('#akuma_espacio').html(`
		<div style="width: 100%; background: url(/images/op/uploads/Libro%20Akuma%20_One_Piece_Gaiden_Foro_Rol.webp); background-repeat: no-repeat; background-size: cover; border: 2px solid black; padding: 10px 0; height: 280px; border-radius: 8px;" class="bbox">
			<div style="text-align: center; font-size: 17px; color: Black; font-family: moonGetHeavy; margin-top: 0px; margin-bottom: 5px; text-shadow: 2px 2px 1px white;">${akumaNombre}</div>
			<div style="text-align: center; font-size: 13px; color: white; font-family: moonGetHeavy; font-style: italic; margin-bottom: 5px; text-shadow: 1px 1px 1px black;">${akumaSubnombre}</div>
			<div id="frutaTipoTier" style="text-align: center;background: linear-gradient(90deg, rgba(0, 116, 143, 0) 0%, rgb(81, 25, 106) 20%, rgb(35, 5, 46) 50%, rgb(81, 25, 106) 80%, rgba(0, 116, 143, 0) 100%);margin-top: 8px;">
				<div style="font-family: moonGetHeavy;color: white;font-size: 12px;margin: auto;text-shadow: 2px 2px 1px black;letter-spacing: 2px;">
					<span id="frutaTipo">${akumaCategoria}</span> | Tier <span id="frutaTier">${akumaTier}</span>
				</div>
			</div>
			<div style="text-align: center; margin-top: -8px;">
				<img src="${akumaImagen}" style="width: 200px; height: 200px; box-shadow: 0px 0px 0px rgba(0, 0, 0, 0); border-radius: 0px;">
			</div>
		</div>
	`);
} else if (hasFullHaki) {
	$('#akuma_espacio').html(`
		<div style="width: 100%; background: url(/images/op/uploads/FullHaki_One_Piece_Gaiden_Foro_Rol.webp); background-repeat: no-repeat; background-size: cover; border: 2px solid black; padding: 10px 0; height: 280px; display: flex; justify-content: center; align-items: center; border-radius: 5px;" class="bbox">
			<span style="font-family: 'moonGetHeavy'; font-size: 24px; color: white; text-align: center; text-shadow: 2px 2px 1px black;"></span>
		</div>
	`);		
} else {
	$('#akuma_espacio').html(`
		<div style="width: 100%; background: url(/images/op/uploads/BuscandoAkuma%20_One_Piece_Gaiden_Foro_Rol.webp); background-repeat: no-repeat; background-size: cover; border: 2px solid black; padding: 10px 0; height: 280px; display: flex; justify-content: center; align-items: center; border-radius: 5px;" class="bbox">
			<span style="font-family: 'moonGetHeavy'; font-size: 24px; color: white; text-align: center; text-shadow: 2px 2px 1px black;"></span>
		</div>
	`);
}

// Hacer clickeable cualquier elemento que contenga información de akuma
$(document).ready(function() {
    if (hasAkuma && akumaNombre && akumaNombre !== '') {
        // Solo hacer clickeable el contenedor específico akuma_espacio
        var akumaEspacio = $('#akuma_espacio');
        
        if (akumaEspacio.length > 0) {
            akumaEspacio.css({
                'cursor': 'pointer',
                'transition': 'all 0.3s ease'
            }).on('click', openAkumaModal)
            .on('mouseenter', function() {
                $(this).css({
                    'filter': 'brightness(1.1) drop-shadow(0px 0px 8px rgba(255,126,0,0.8))',
                    'transform': 'scale(1.02)'
                });
            })
            .on('mouseleave', function() {
                $(this).css({
                    'filter': '',
                    'transform': ''
                });
            });
        }
    }
});

// Hacer el contenedor akuma_espacio clickeable si tiene akuma (método alternativo)
if (hasAkuma) {
    var akumaEspacio = document.getElementById('akuma_espacio');
    if (akumaEspacio) {
        akumaEspacio.style.cursor = 'pointer';
        akumaEspacio.onclick = function(event) {
            event.stopPropagation(); // Evitar que el evento se propague
            openAkumaModal();
        };
        
        // Agregar efectos hover
        akumaEspacio.addEventListener('mouseenter', function() {
            this.style.filter = 'brightness(1.1) drop-shadow(0px 0px 8px rgba(255,126,0,0.8))';
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'all 0.3s ease';
        });
        
        akumaEspacio.addEventListener('mouseleave', function() {
            this.style.filter = '';
            this.style.transform = '';
        });
    }
}

// Agregar animaciones CSS para el modal
var style = document.createElement('style');
style.innerHTML = `
    @-webkit-keyframes animatetop {
        from {top:-300px; opacity:0} 
        to {top:0; opacity:1}
    }
    @keyframes animatetop {
        from {top:-300px; opacity:0}
        to {top:0; opacity:1}
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
`;
document.head.appendChild(style);

// Hacer el contenedor akuma_espacio clickeable si tiene akuma
if (hasAkuma) {
    var akumaEspacio = document.getElementById('akuma_espacio');
    if (akumaEspacio) {
        akumaEspacio.style.cursor = 'pointer';
        akumaEspacio.onclick = openAkumaModal;
        
        // Agregar efectos hover
        akumaEspacio.addEventListener('mouseenter', function() {
            this.style.filter = 'brightness(1.1) drop-shadow(0px 0px 8px rgba(255,126,0,0.8))';
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'all 0.3s ease';
        });
        
        akumaEspacio.addEventListener('mouseleave', function() {
            this.style.filter = '';
            this.style.transform = '';
        });
    }
}

// Agregar animaciones CSS para el modal
var style = document.createElement('style');
style.innerHTML = `
    @-webkit-keyframes animatetop {
        from {top:-300px; opacity:0} 
        to {top:0; opacity:1}
    }
    @keyframes animatetop {
        from {top:-300px; opacity:0}
        to {top:0; opacity:1}
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 10000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
`;
document.head.appendChild(style);

// Hacer el contenedor akuma_espacio clickeable si tiene akuma
if (hasAkuma) {
    var akumaEspacio = document.getElementById('akuma_espacio');
    if (akumaEspacio) {
        akumaEspacio.style.cursor = 'pointer';
        akumaEspacio.onclick = openAkumaModal;
        
        // Agregar efectos hover
        akumaEspacio.addEventListener('mouseenter', function() {
            this.style.filter = 'brightness(1.1) drop-shadow(0px 0px 8px rgba(255,126,0,0.8))';
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'all 0.3s ease';
        });
        
        akumaEspacio.addEventListener('mouseleave', function() {
            this.style.filter = '';
            this.style.transform = '';
        });
    }
}

if (pestana != '') {
	clickPestana(pestana);
} else {
	// Restaurar último tab visitado o usar 'portada' por defecto
	var lastTab = sessionStorage.getItem('lastFichaTab') || 'portada';
	clickPestana(lastTab);
}

for (let i = 0; i < virtudes_array_json.length; i++) {
	let virtudes = virtudes_json[virtudes_array_json[i]][0];
	let virtudesNombre = virtudes.nombre;
	
	$('#rasgos_virtudes').append(`
		<div class="virtudCaja">
			<div class="virtudPuntos virtudColor">` + virtudes.puntos + `</div>
			<div class="virtudNombre virtudColor">` + virtudesNombre + `</div>
			<div class="virtudCajaTexto virtudBackground">
				<div style="display: flex; flex-direction: row;">
					<div style="width: 40px;text-align: center;position: relative;top: 17px;left: 2px;">
						<span style="color: #ffffff;font-family: moonGetHeavy;letter-spacing: 0px;font-size: 14px;color: red;"></span><br>

						<span style="color: #ffffff;font-family: moonGetHeavy;letter-spacing: 0px;font-size: 8px;">ID<br>` + virtudes.virtud_id + `</span>
					</div>
					<div style="width: 200px;margin-left: 10px;font-family: notoKuro;color: white;font-size: 12px;">` + virtudes.descripcion + `</div>
				</div>
			</div>
		</div>	
	`);
}

for (let i = 0; i < defectos_array_json.length; i++) {
	let defectos = defectos_json[defectos_array_json[i]][0];
	let defectosNombre = defectos.nombre;
	
	$('#rasgos_defectos').append(`
		<div class="virtudCaja">
			<div class="virtudPuntos defectoColor">` + Math.abs(defectos.puntos) + `</div>
			<div class="virtudNombre defectoColor">` + defectosNombre + `</div>
			<div class="virtudCajaTexto defectoBackground">
				<div style="display: flex; flex-direction: row;">
					<div style="width: 40px;text-align: center;position: relative;top: 17px;left: 2px;">
						<span style="color: #ffffff;font-family: moonGetHeavy;letter-spacing: 0px;font-size: 14px;color: red;"></span><br>

						<span style="color: #ffffff;font-family: moonGetHeavy;letter-spacing: 0px;font-size: 8px;">ID<br>` + defectos.virtud_id + `</span>
					</div>
					<div style="color: #ffffff;font-family: interRegular;letter-spacing: 0px;font-size: 11px;margin-top: 6px;padding-right: 5px;width: 240px;">` + defectos.descripcion + `</div>
				</div>
			</div>

		</div>
	`);
}

// DEBUG: Verificar objetos principales al inicio de la inicialización
console.log('Iniciando verificaciones de objetos...');
console.log('oficios:', typeof oficios, oficios);
console.log('belicas:', typeof belicas, belicas);
console.log('oficio1:', oficio1);
console.log('oficio2:', oficio2);
console.log('belica1:', belica1);

// Inicialización de espacios y subraza
espacios = 0;
if (altura > 0 && altura <= 50) { espacios = 0.25; }
if (altura > 50 && altura <= 100) { espacios = 0.5; }
if (altura > 100 && altura <= 300) { espacios = 1; }
if (altura > 300 && altura <= 500) { espacios = 2; }
if (altura > 500) { espacios = 2 + Math.ceil((altura - 300) / 200); }
	
subrazaTxt = 'Raza Pura';
subraza = tec_aprendidas_json['Racial']?.filter(i => { return i.tid.toLowerCase().includes('tri') });
if (subraza?.length > 0) { subrazaTxt = subraza[0].nombre; }

if (raza != 'Humano' && raza != 'Gyojin' && raza != 'Ningyo' && 
	raza != 'Gigante' && raza != 'Tontatta' && raza != 'Mink' &&
	raza != 'Skypian' && raza != 'Oni' && raza != 'Lunarian') {
	subrazaTxt = 'Raza Híbrida';
}

$("#subraza").html(subrazaTxt);
$("#espacios").html(espacios);

if (has_sin_oficio) {
	$("#oficios").html('<div style="width: 280px;height: 280px;background: url(/images/op/uploads/sin_oficio.webp);filter: drop-shadow(0px 0px 10px black);"></div>');
}

// Verificación de seguridad: asegurar que el objeto oficios existe
if (typeof oficios === 'undefined') {
	console.error('El objeto oficios no está definido');
	oficios = {};
}

// Protección preventiva para variables de oficios que podrían declararse más adelante
// Esta función intercepta declaraciones potencialmente problemáticas
function protegerVariablesOficio() {
	// Sobrescribir any oficiosInfo global que pueda estar undefined
	if (typeof window.oficiosInfo === 'undefined' || window.oficiosInfo === null) {
		window.oficiosInfo = {
			nivel: 'desconocido',
			sub: {},
			espe1: null,
			espe2: null
		};
		console.warn('Variable global oficiosInfo protegida con valores por defecto');
	}
	
	// Crear función helper global para acceso seguro a oficios
	window.getSafeOficio = function(oficioName) {
		if (!oficioName || typeof oficios === 'undefined') {
			return { nivel: 'desconocido', sub: {}, espe1: null, espe2: null };
		}
		
		const oficio = oficios[oficioName];
		if (!oficio) {
			console.warn(`Oficio '${oficioName}' no encontrado, devolviendo valores por defecto`);
			return { nivel: 'desconocido', sub: {}, espe1: null, espe2: null };
		}
		
		return oficio;
	};
}

// Ejecutar protecciones
protegerVariablesOficio();

// Código de inicialización de oficios
if (oficio1 != '') {

	let auxOficio1;
	if (oficio1 != "Médico") {
		auxOficio1 = oficio1;
	} else {
		auxOficio1 = "Medico";
	}

	let oficio1Obj = oficios[oficio1];
	let oficio1Nivel = oficios[oficio1].nivel;

	let subKeys = Object.keys(oficio1Obj.sub);
	let firstSub = subKeys[0];
	let secondSub = subKeys[1];

	if (firstSub === 'Recolector') {auxOficio20bj0 = 'Contrabandista';}
	else if (firstSub === 'Recolector') {auxOficio20bj0 = 'Contrabandista';}
	else {auxOficio20bj0 = firstSub;}

	if (secondSub === 'Recolector') {auxOficio20bj1 = 'Contrabandista';}
	else if (secondSub === 'Recolector') {auxOficio20bj1 = 'Contrabandista';}
	else {auxOficio20bj1 = secondSub;}

	let oficio1NivelTag = oficio1 + 'Nivel';
	let oficio1SubNivel1 = firstSub + 'Nivel';
	let oficio1SubNivel2 = secondSub + 'Nivel';

	let oficio1NivelId = '#' + oficio1 + 'Nivel';
	let oficio1SubNivelId1 = '#' + firstSub + 'Nivel';
	let oficio1SubNivelId2 = '#' + secondSub + 'Nivel';

	$('#oficios').append(`
		<div style="width: 280px;margin: 0px -2px;position: relative;box-sizing: border-box;">
				
			<div style="position: relative;height: 30px;background-color: ${borderColor};padding: 0 5px;margin: auto;text-align: center;border-radius: 15px 15px 0px 0px;border-top: 2px solid black;border-left: 2px solid black;border-right: 2px solid black;">
				<span style="font-family: 'moonGetHeavy';color: white;font-size: 20px;text-shadow: 1px 1px 0px black;">` + oficio1 + `</span>
				<span id="` + oficio1NivelTag + `" style="position: absolute;color: white;font-family: 'moonGetHeavy';left: 232px;top: 5px;background-color: #9d57b4;width: 40px;border-radius: 20px;border: 1px solid black;">` + oficio1Nivel + `</span>
			</div>
			<div style="height: 100px; background: url(/images/op/uploads/OficioFicha` + auxOficio1 + `_One_Piece_Gaiden_Foro_Rol.webp);">

				<div style="position: absolute;bottom: 7px;left: 6px;right: 6px;display: flex;">
					<div style="background-color: orange;width: 130px;height: 14px;border-radius: 10px;display: flex;margin: auto;">
						<span style="font-family: 'moonGetHeavy';color: white;font-size: 8px;margin: auto;text-shadow: 1px 1px 0px black;">` + auxOficio20bj0 + `</span>
						<span id="` + oficio1SubNivel1 + `" style="position: absolute;color: white;font-family: 'moonGetHeavy';left: 109px;top: 2px;background-color: #9d57b4;width: 20px;border-radius: 20px;border: 1px solid black;height: 9px;font-size: 6px;text-align: center;">` + oficio1Obj.sub[subKeys[0]] + `</span>

					</div>
					<div style="background-color: orange;width: 130px;height: 14px;border-radius: 10px;display: flex;margin: auto;">
						<span style="font-family: 'moonGetHeavy';color: white;font-size: 8px;margin: auto;text-shadow: 1px 1px 0px black;">` + auxOficio20bj1 + `</span>
						<span id="` + oficio1SubNivel2 + `" style="position: absolute;color: white;font-family: 'moonGetHeavy';left: 243px;top: 2px;background-color: #9d57b4;width: 20px;border-radius: 20px;border: 1px solid black;height: 9px;font-size: 6px;text-align: center;">` + oficio1Obj.sub[subKeys[1]] + `</span>

					</div>
				</div>
			</div>

		</div>
	`);

	if (is_owner && oficios[oficio1].nivel < 2) { $(oficio1NivelId).css('cursor', 'pointer'); $(oficio1NivelId).on('click', function() { chooseOficio(oficio1, true); }); }
	else { $(oficio1NivelId).css('cursor', 'default'); $(oficio1NivelId).css('background-color', '#28ce26');  }

	if (is_owner && oficios[oficio1]?.nivel == 2 && ((oficios[oficio1]?.sub[firstSub] >= 0 && !(oficios[oficio1]?.sub[secondSub] >= 1)) || (oficios[oficio1]?.sub[firstSub] >= 0 && (has_estudioso || has_erudito)))) { 
		
		if (oficios[oficio1]?.sub[firstSub] < 3) { $(oficio1SubNivelId1).css('cursor', 'pointer'); $(oficio1SubNivelId1).on('click', function() { chooseEspeOficio(oficio1, firstSub); }); } 
		else { $(oficio1SubNivelId1).css('background-color', '#25ba23');  }
	} else { 
		$(oficio1SubNivelId1).css('cursor', 'default'); 
		$(oficio1SubNivelId1).css('background-color', '#8d888f'); 

	}	

	if (is_owner && oficios[oficio1]?.nivel == 2 && ((oficios[oficio1]?.sub[secondSub] >= 0 && !(oficios[oficio1]?.sub[firstSub] >= 1)) || (oficios[oficio1]?.sub[secondSub] >= 0 && (has_estudioso || has_erudito)))) { 
		
		if (oficios[oficio1]?.sub[secondSub] < 3) { $(oficio1SubNivelId2).css('cursor', 'pointer'); $(oficio1SubNivelId2).on('click', function() { chooseEspeOficio(oficio1, secondSub); }); } 
		else { $(oficio1SubNivelId2).css('background-color', '#25ba23');  }
	} else { 
		$(oficio1SubNivelId2).css('cursor', 'default'); 
		$(oficio1SubNivelId2).css('background-color', '#8d888f'); 
	}	

}

if (oficio2 == '' && (has_polivalente || has_erudito)) {
	if (is_owner) {
		$('#oficios').append(`
			<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;" onclick="chooseOficioTest()">
				<img style="cursor: pointer; width: 280px;height: 130px;" src="/images/op/uploads/FichaSlotLibre_One_Piece_Gaiden_Foro_Rol.webp" />
			</div>
		`);
	} else {
		$('#oficios').append(`
			<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;">
				<img style="width: 280px;height: 130px;" src="/images/op/uploads/FichaSlotLibre_One_Piece_Gaiden_Foro_Rol.webp" />
			</div>
		`);
	}
}

if (oficio2 == '' && !(has_polivalente || has_erudito) && !has_sin_oficio) {
	$('#oficios').append(`
		<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;">
			<img style="width: 280px;height: 130px;" src="/images/op/uploads/FichaSlotBloqueado_One_Piece_Gaiden_Foro_Rol.webp" />
		</div>
	`);
}

if (oficio2 != '') {

	let auxOficio2;
	if (oficio2 != "Médico") {
		auxOficio2 = oficio2;
	} else {
		auxOficio2 = "Medico";
	}

	let oficio2Obj = oficios[oficio2];
	let oficio2Nivel = oficios[oficio2].nivel;

	let subKeys = Object.keys(oficio2Obj.sub);
	let firstSub = subKeys[0];
	let secondSub = subKeys[1];

	if (firstSub === 'Recolector') {auxOficio20bj0 = 'Contrabandista';}
	else if (firstSub === 'Recolector') {auxOficio20bj0 = 'Contrabandista';}
	else {auxOficio20bj0 = firstSub;}

	if (secondSub === 'Recolector') {auxOficio20bj1 = 'Contrabandista';}
	else if (secondSub === 'Recolector') {auxOficio20bj1 = 'Contrabandista';}
	else {auxOficio20bj1 = secondSub;}

	let oficio2NivelTag = oficio2 + 'Nivel2';
	let oficio2SubNivel1 = firstSub + 'Nivel2';
	let oficio2SubNivel2 = secondSub + 'Nivel2';

	let oficio2NivelId = '#' + oficio2 + 'Nivel2';
	let oficio2SubNivelId1 = '#' + firstSub + 'Nivel2';
	let oficio2SubNivelId2 = '#' + secondSub + 'Nivel2';

	$('#oficios').append(`
		<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;">
				
			<div style="position: relative;height: 30px;background-color: ${borderColor};padding: 0 5px;margin: auto;text-align: center;border-radius: 15px 15px 0px 0px;border-top: 2px solid black;border-left: 2px solid black;border-right: 2px solid black;">
				<span style="font-family: 'moonGetHeavy';color: white;font-size: 20px;text-shadow: 1px 1px 0px black;">` + oficio2 + `</span>
				<span id="` + oficio2NivelTag + `" style="position: absolute;color: white;font-family: 'moonGetHeavy';left: 232px;top: 5px;background-color: #9d57b4;width: 40px;border-radius: 20px;border: 1px solid black;">` + oficio2Nivel + `</span>
			</div>
			<div style="height: 100px; background: url(/images/op/uploads/OficioFicha` + auxOficio2 + `_One_Piece_Gaiden_Foro_Rol.webp);">

				<div style="position: absolute;bottom: 7px;left: 6px;right: 6px;display: flex;">
					<div style="background-color: orange;width: 130px;height: 14px;border-radius: 10px;display: flex;margin: auto;">
						<span style="font-family: 'moonGetHeavy';color: white;font-size: 8px;margin: auto;text-shadow: 1px 1px 0px black;">` + auxOficio20bj0 + `</span>
						<span id="` + oficio2SubNivel1 + `" style="position: absolute;color: white;font-family: 'moonGetHeavy';left: 109px;top: 2px;background-color: #9d57b4;width: 20px;border-radius: 20px;border: 1px solid black;height: 9px;font-size: 6px;text-align: center;">` + oficio2Obj.sub[subKeys[0]] + `</span>

					</div>
					<div style="background-color: orange;width: 130px;height: 14px;border-radius: 10px;display: flex;margin: auto;">
						<span style="font-family: 'moonGetHeavy';color: white;font-size: 8px;margin: auto;text-shadow: 1px 1px 0px black;">` + auxOficio20bj1 + `</span>
						<span id="` + oficio2SubNivel2 + `" style="position: absolute;color: white;font-family: 'moonGetHeavy';left: 243px;top: 2px;background-color: #9d57b4;width: 20px;border-radius: 20px;border: 1px solid black;height: 9px;font-size: 6px;text-align: center;">` + oficio2Obj.sub[subKeys[1]] + `</span>

					</div>
				</div>
			</div>

		</div>
	`);

	if (is_owner && oficios[oficio2].nivel < 2) { $(oficio2NivelId).css('cursor', 'pointer'); $(oficio2NivelId).on('click', function() { chooseOficio(oficio2, true); }); }
	else { $(oficio2NivelId).css('cursor', 'default'); $(oficio2NivelId).css('background-color', '#28ce26');  }

	if (is_owner && oficios[oficio2]?.nivel == 2 && ((oficios[oficio2]?.sub[firstSub] >= 0 && !(oficios[oficio2]?.sub[secondSub] >= 1)) || (oficios[oficio2]?.sub[firstSub] >= 0 && (has_estudioso || has_erudito)))) { 
		
		if (oficios[oficio2]?.sub[firstSub] < 3) { $(oficio2SubNivelId1).css('cursor', 'pointer'); $(oficio2SubNivelId1).on('click', function() { chooseEspeOficio(oficio2, firstSub); }); } 
		else { $(oficio2SubNivelId1).css('background-color', '#25ba23');  }
	} else { 
		$(oficio2SubNivelId1).css('cursor', 'default'); 
		$(oficio2SubNivelId1).css('background-color', '#8d888f'); 

	}	

	if (is_owner && oficios[oficio2]?.nivel == 2 && ((oficios[oficio2]?.sub[secondSub] >= 0 && !(oficios[oficio2]?.sub[firstSub] >= 1)) || (oficios[oficio2]?.sub[secondSub] >= 0 && (has_estudioso || has_erudito)))) { 
		
		if (oficios[oficio2]?.sub[secondSub] < 3) { $(oficio2SubNivelId2).css('cursor', 'pointer'); $(oficio2SubNivelId2).on('click', function() { chooseEspeOficio(oficio2, secondSub); }); } 
		else { $(oficio2SubNivelId2).css('background-color', '#25ba23');  }
	} else { 
		$(oficio2SubNivelId2).css('cursor', 'default'); 
		$(oficio2SubNivelId2).css('background-color', '#8d888f'); 
	}	

}

// Inicialización de berries y sección de técnicas
$('#berries').text(berries.toLocaleString('es-es'));

// Seccion de tecnicas  
aero = elementos['Aero'];   
aqua = elementos['Aqua'];   
cryo = elementos['Cryo'];   
piro = elementos['Piro'];   
electro = elementos['Electro'];  
oficiosCount = Object.keys(oficios).length;

$(window).click(function(e) {
	if (event.target == modal) {
		modal.style.display = "none";
	}
});

/* LIMITE NIVEL */
$('#limite_nivel').on('click', function() {
	let nikasCosto = 50;
	
	if (limite_nivel >= 20) { nikasCosto = 5; }
	if (limite_nivel >= 25) { nikasCosto = 10; }
	if (limite_nivel >= 30) { nikasCosto = 15; }
	if (limite_nivel >= 35) { nikasCosto = 20; }
	if (limite_nivel >= 40) { nikasCosto = 25; }
	
	if (confirm('Aumentar tu límite de nivel tendrá un costo de ' + nikasCosto + ' nikas. ¿Estás de acuerdo?')) {
		window.location.href = '/op/mejoras.php?accion=limite_nivel';
	}
});

/* HAKI EVENT HANDLERS */
if (is_owner) {
	$('#kenbun_img').css('cursor',  'pointer');
	$('#kenbun_img').on('click', function() {
		if (canGetKenbun) {
			if (confirm('Aumentar el nivel de tu Kenbunshoku Haki tendrá un costo de ' + nikasCostoKenbun + ' nikas. ¿Estás de acuerdo?')) {
				subirHaki('kenbun');
			}
		} else {
			alert(`No tienes nivel o nikas suficiente para entrenar este haki. Para subir Kenbun necesitas ${nikasCostoKenbun} nikas y nivel ${nivelHakiKenbun} (o 5 niveles menos si tienes Camino de la Voluntad).`);
		}
	});	
}

if (is_owner) {
	$('#buso_img').css('cursor',  'pointer');
	$('#buso_img').on('click', function() {
		if (canGetBuso) {

			
			if (confirm('Aumentar el nivel de tu Busoshoku Haki tendrá un costo de ' + nikasCostoBuso + ' nikas. ¿Estás de acuerdo?')) {
				subirHaki('buso');
			}
		} else {
			alert(`No tienes nivel o nikas suficiente para entrenar este haki. Para subir Buso necesitas ${nikasCostoBuso} nikas y nivel ${nivelHakiBuso} (o 5 niveles menos si tienes Camino de la Voluntad).`);
		}
	});	
}

if (is_owner) {
	$('#hao_img').css('cursor',  'pointer');
	$('#hao_img').on('click', function() {
		if (canGetHao) {
			if (confirm('Aumentar el nivel de tu Haoshoku Haki tendrá un costo de ' + nikasCostoHao + ' nikas. ¿Estás de acuerdo?')) {
				subirHaki('hao');
			}
		} else {
			alert(`No tienes nivel o nikas suficiente para entrenar este haki. Para subir Hao necesitas ${nikasCostoHao} nikas y nivel ${nivelHakiHao} (o 5 niveles menos si tienes Camino de la Voluntad).`);
		}
	});	
}

/* DISCIPLINAS EVENT HANDLERS */
if (!belicasArray.includes('Escudero')) {
	$('#Escudero_nivel_body').on('click', function() { 
		chooseBelica('Escudero', false);
	}); 
}

if (!belicasArray.includes('Artista Marcial')) {
	$('#Artista_Marcial_nivel_body').on('click', function() { 
		chooseBelica('Artista Marcial', false);
	}); 
}

if (!belicasArray.includes('Combatiente')) {
	$('#Combatiente_nivel_body').on('click', function() { 
		chooseBelica('Combatiente', false);
	}); 
}

if (!belicasArray.includes('Artista')) {
	$('#Artista_nivel_body').on('click', function() { 
		chooseBelica('Artista', false);
	}); 
}

if (!belicasArray.includes('Asesino')) {
	$('#Asesino_nivel_body').on('click', function() { 
		chooseBelica('Asesino', false);
	}); 
}

if (!belicasArray.includes('Guerrero')) {
	$('#Guerrero_nivel_body').on('click', function() { 
		chooseBelica('Guerrero', false);
	}); 
}

if (!belicasArray.includes('Espadachín')) {
	$('#Espadachín_nivel_body').on('click', function() { 
		chooseBelica('Espadachín', false);
	}); 
}

if (!belicasArray.includes('Tecnicista')) {
	$('#Tecnicista_nivel_body').on('click', function() { 
		chooseBelica('Tecnicista', false);
	}); 
}

if (!belicasArray.includes('Artillero')) {
	$('#Artillero_nivel_body').on('click', function() { 
		chooseBelica('Artillero', false);
	}); 
}

if (!belicasArray.includes('Arquero')) {
	$('#Arquero_nivel_body').on('click', function() { 
		chooseBelica('Arquero', false);
	}); 
}

if (!belicasArray.includes('Tirador')) {
	$('#Tirador_nivel_body').on('click', function() { 
		chooseBelica('Tirador', false);
	}); 
}

if (!belicasArray.includes('Pícaro')) {
	$('#Pícaro_nivel_body').on('click', function() { 
		chooseBelica('Pícaro', false);
	}); 
}

// Inicialización de técnicas de disciplinas
insertarDisciplinaTecs('Escudero', 'Vanguardia');
insertarDisciplinaTecs('Escudero', 'Bastión');
insertarDisciplinaTecs('Artista Marcial', 'Acróbata');
insertarDisciplinaTecs('Artista Marcial', 'Monje');
insertarDisciplinaTecs('Combatiente', 'Berserker');
insertarDisciplinaTecs('Combatiente', 'Campeón');
insertarDisciplinaTecs('Artista', 'Bardo');
insertarDisciplinaTecs('Artista', 'Trovador');

insertarDisciplinaTecs('Asesino', 'Sombra');
insertarDisciplinaTecs('Asesino', 'Verdugo');
insertarDisciplinaTecs('Guerrero', 'Castigador');
insertarDisciplinaTecs('Guerrero', 'Warhammer');
insertarDisciplinaTecs('Espadachín', 'Samurái');
insertarDisciplinaTecs('Espadachín', 'Mosquetero');
insertarDisciplinaTecs('Tecnicista', 'Diletante');
insertarDisciplinaTecs('Tecnicista', 'WeaponMaster');

insertarDisciplinaTecs('Artillero', 'Destructor');
insertarDisciplinaTecs('Artillero', 'Juggernaut');
insertarDisciplinaTecs('Arquero', 'Ballestero');
insertarDisciplinaTecs('Arquero', 'Cazador');
insertarDisciplinaTecs('Tirador', 'Duelista');
insertarDisciplinaTecs('Tirador', 'Francotirador');
insertarDisciplinaTecs('Pícaro', 'Gambito');
insertarDisciplinaTecs('Pícaro', 'Trickster');

// Inicialización de técnicas de Haki
if (tec_aprendidas_json['Haoshoku'] || tec_aprendidas_json['Kenbunshoku'] || tec_aprendidas_json['Busoshoku']) {
	tec_aprendidas_json['Haki'] = [];
}

if (tec_aprendidas_json['Haoshoku']) {
	let tecCount = tec_aprendidas_json['Haoshoku'].length;
	for (let i = 0; i < tecCount; i++) {
		tec_aprendidas_json['Haki'].push(tec_aprendidas_json['Haoshoku'][i]);
	}
}

if (tec_aprendidas_json['Kenbunshoku']) {
	let tecCount = tec_aprendidas_json['Kenbunshoku'].length;
	for (let i = 0; i < tecCount; i++) {
		tec_aprendidas_json['Haki'].push(tec_aprendidas_json['Kenbunshoku'][i]);
	}
}

if (tec_aprendidas_json['Busoshoku']) {
	let tecCount = tec_aprendidas_json['Busoshoku'].length;
	for (let i = 0; i < tecCount; i++) {
		tec_aprendidas_json['Haki'].push(tec_aprendidas_json['Busoshoku'][i]);
	}
}

// // Inicialización de lógica adicional de oficios
// if (oficio2 == '' && (has_polivalente || has_erudito)) {
// 	if (is_owner) {
// 		$('#oficios').append(`
// 			<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;" onclick="chooseOficioTest()">
// 				<img style="cursor: pointer; width: 280px;height: 130px;" src="/images/op/uploads/FichaSlotLibre_One_Piece_Gaiden_Foro_Rol.webp" />
// 			</div>
// 		`);
// 	} else {
// 		$('#oficios').append(`
// 			<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;">
// 				<img style="width: 280px;height: 130px;" src="/images/op/uploads/FichaSlotLibre_One_Piece_Gaiden_Foro_Rol.webp" />
// 			</div>
// 		`);
// 	}
// }

// if (oficio2 == '' && !(has_polivalente || has_erudito) && !has_sin_oficio) {
// 	$('#oficios').append(`
// 		<div style="width: 280px;margin: 9px -2px;position: relative;box-sizing: border-box;">
// 			<img style="width: 280px;height: 130px;" src="/images/op/uploads/FichaSlotBloqueado_One_Piece_Gaiden_Foro_Rol.webp" />
// 		</div>
// 	`);
// }

