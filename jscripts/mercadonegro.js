/**
 * Mercado Negro - Funcionalidad del Bazar
 * Sistema de compra y venta entre jugadores
 */

// Variables globales del bazar (si no existen)
if (typeof modoBazar === 'undefined') {
    var modoBazar = 'comprar';
}
if (typeof inventarioTradeable === 'undefined') {
    var inventarioTradeable = window.inventarioTradeable || [];
}
if (typeof listingsActivos === 'undefined') {
    var listingsActivos = window.listingsActivos || [];
}
if (typeof movidoInframundo === 'undefined') {
    var movidoInframundo = window.movidoInframundo || 0;
}

// HELPERS Formatos
// Config: si true, trata 'YYYY-MM-DD HH:MM:SS' como UTC
const FECHAS_BAZAR_EN_UTC = true;

function parseFechaMySQL(fecha) {
    const invalids = [null, undefined, '', '0000-00-00 00:00:00', 'null', '0'];
    if (invalids.includes(fecha)) return null;

    // Date nativa
    if (fecha instanceof Date) return isNaN(fecha.getTime()) ? null : fecha;

    // Numérico: epoch (segundos o milisegundos)
    if (typeof fecha === 'number') {
        const ms = fecha < 1e12 ? fecha * 1000 : fecha;
        const d = new Date(ms);
        return isNaN(d.getTime()) ? null : d;
    }

    // String normalizada
    let s = String(fecha).trim();
    s = s.replace(/\.\d+$/, '');   // quita microsegundos
    s = s.replace('T', ' ');       // T -> espacio

    // Si viene con offset o Z, deja que Date lo interprete
    if (/[+-]\d{2}:\d{2}|Z$/i.test(String(fecha))) {
        const d = new Date(String(fecha).replace(' ', 'T'));
        return isNaN(d.getTime()) ? null : d;
    }

    // "YYYY-MM-DD HH:MM:SS" → UTC o LOCAL según bandera
    const match = /^(\d{4})-(\d{2})-(\d{2})[ ](\d{2}):(\d{2}):(\d{2})$/.exec(s);
    if (match) {
        const [_, y, mo, d, h, mi, se] = match.map(Number);
        const useUTC = (typeof window !== 'undefined' && window.FECHAS_BAZAR_EN_UTC === true) || FECHAS_BAZAR_EN_UTC === true;
        return useUTC
            ? new Date(Date.UTC(y, mo - 1, d, h, mi, se))
            : new Date(y, mo - 1, d, h, mi, se);
    }

    // Último intento
    const d2 = new Date(s);
    return isNaN(d2.getTime()) ? null : d2;
}

function esSubastaActiva(fechaStrOrMs) {
    const finMs = typeof fechaStrOrMs === 'number'
        ? fechaStrOrMs
        : (parseFechaMySQL(fechaStrOrMs)?.getTime() ?? null);
    return !!(finMs && finMs > Date.now());
}

function tieneFechaSubasta(fechaStr) {
    return !!parseFechaMySQL(fechaStr);
}

// Función para inicializar el bazar
function inicializarBazar() {
    console.log('=== INICIALIZANDO BAZAR ===');

    const modoGuardado = (function() {
	  try { return localStorage.getItem('op.bazar.modo'); } catch (_) { return null; }
	})();
	modoBazar = modoGuardado || 'comprar';
	mostrarModoBazar(modoBazar);

    // Actualizar variables si están disponibles en window
    if (window.inventarioTradeable) inventarioTradeable = window.inventarioTradeable;
    if (window.listingsActivos) listingsActivos = window.listingsActivos;
    if (window.movidoInframundo) movidoInframundo = window.movidoInframundo;

    console.log('Inventario tradeable:', inventarioTradeable.length, 'objetos');
    console.log('Listings activos:', listingsActivos.length, 'listings');
    console.log('Dinero movido:', movidoInframundo);

    // Iniciar actualización automática de listings privados
    iniciarActualizacionPrivados();

    // Cargar el modo comprar por defecto cuando no haya un elemento anterior cargado en memoria
    console.log('=== BAZAR INICIALIZADO ===');
}

// Función para cambiar entre modo comprar y vender
function mostrarModoBazar(modo) {
    console.log('Cambiando a modo bazar:', modo);
    modoBazar = modo;
    try { localStorage.setItem('op.bazar.modo', modo); } catch (_) {}

    // Actualizar variables por si han cambiado
    if (window.inventarioTradeable) inventarioTradeable = window.inventarioTradeable;
    if (window.listingsActivos) listingsActivos = window.listingsActivos;

    // Actualizar botones activos
    $('.bazar-modo-btn').removeClass('bazar-modo-active');
    $('#btn-bazar-' + modo).addClass('bazar-modo-active');

    // Mostrar/ocultar secciones
    if (modo === 'comprar') {
        $('#bazar-comprar-section').css('display', 'block');
        $('#bazar-vender-section').css('display', 'none');
        cargarObjetosEnVenta();
    } else if (modo === 'vender') {
        $('#bazar-comprar-section').css('display', 'none');
        $('#bazar-vender-section').css('display', 'block');
        cargarInventarioTradeable();
    }
}

// Función para cargar objetos en venta por otros jugadores
function cargarObjetosEnVenta() {
    console.log('=== CARGANDO OBJETOS EN VENTA ===');
    console.log('listingsActivos:', listingsActivos);

    // Debug: verificar que los datos están correctos
    if (listingsActivos && listingsActivos.length > 0) {
        console.log('=== DEBUG LISTINGS ===');
        listingsActivos.forEach((listing, index) => {
            const fechaSubasta = listing.fecha_final_subasta;
            const esSubasta = esSubastaActiva(fechaSubasta);
            console.log(`[${index}] Listing ${listing.id}: fecha="${fechaSubasta}", esSubasta=${esSubasta}, precio_actual=${listing.precio_actual}, precio_compra=${listing.precio_compra}, estado=${listing.estado}`);
        });
        console.log('=== FIN DEBUG LISTINGS ===');
    }

    listingsActivos.forEach((l, i) => {
        const d = parseFechaMySQL(l.fecha_final_subasta);
        console.log(`[${i}] Listing ${l.id} raw="${l.fecha_final_subasta}"`,
            {
                finLocalToString: d && d.toString(), finISO: d && d.toISOString(), nowISO: new Date().toISOString(),
                finMs: d && d.getTime(), diffMs: d ? d.getTime() - Date.now() : null
            });
    });

    // Limpiar contenido
    $('#objetos-en-venta').empty();

    if (!listingsActivos || listingsActivos.length === 0) {
        $('#objetos-en-venta').append(`
            <div style="text-align: center; padding: 50px; color: white; font-family: InterRegular; font-size: 16px;">
                <i class="fa fa-shopping-cart" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i><br>
                No hay objetos en venta en este momento.<br>
                <small>¡Sé el primero en poner algo a la venta!</small>
            </div>
        `);
        return;
    }

    // Agrupar por subcategoría
    const objetosPorSubcategoria = {};

    listingsActivos.forEach(listing => {
        // Buscar el objeto primero en objetos_comerciables_json, luego en objetos_json
        let objeto = null;
        if (objetos_comerciables_json && objetos_comerciables_json[listing.objeto_id] && objetos_comerciables_json[listing.objeto_id][0]) {
            objeto = objetos_comerciables_json[listing.objeto_id];
        } else if (objetos_json && objetos_json[listing.objeto_id]) {
            objeto = objetos_json[listing.objeto_id];
        }

        if (!objeto || !objeto[0]) {
            console.warn('Objeto no encontrado:', listing.objeto_id);
            return;
        }

        const objetoData = objeto[0];
        const subcategoria = objetoData.subcategoria.toLowerCase().split(' ').join('_');

        if (!objetosPorSubcategoria[subcategoria]) {
            objetosPorSubcategoria[subcategoria] = {
                nombre: objetoData.subcategoria.toUpperCase(),
                objetos: []
            };
        }

        objetosPorSubcategoria[subcategoria].objetos.push({
            listing: listing,
            objeto: objetoData
        });
    });

    // Crear HTML para cada subcategoría
    Object.keys(objetosPorSubcategoria).forEach(subcategoria => {
        const data = objetosPorSubcategoria[subcategoria];

        $('#objetos-en-venta').append(`
            <div id="bazar_${subcategoria}_area">
                <div class="objeto_categoria">${data.nombre} - EN VENTA</div>
                <div class="barra_categoria"></div>
                <div id="bazar_${subcategoria}_objetos" class="objetos_lista"></div>
            </div>
        `);

        // Agregar cada objeto
        data.objetos.forEach(item => {
            agregarObjetoEnVenta(item.listing, item.objeto, subcategoria);
        });
    });

    console.log('=== FIN CARGA OBJETOS EN VENTA ===');
}

// Función para agregar un objeto en venta al HTML
function agregarObjetoEnVenta(listing, objeto, subcategoria) {
    const tier = objeto.tier;
    const imagen_id = objeto.imagen_id;
    const imagen_avatar = objeto.imagen_avatar;

    let imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
    if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objeto.objeto_id === 'EANM001' ||
        objeto.objeto_id === 'LLST001' || objeto.objeto_id === 'THR001' || subcategoria === 'materiales' ||
        subcategoria === 'tecnicas' || objeto.objeto_id === 'EPA001' || objeto.objeto_id === 'KMP001' ||
        objeto.objeto_id === 'VCD001' || objeto.objeto_id === 'VCZ001' || subcategoria === 'documentos' ||
        subcategoria === 'recetas') {
        imagen_nombre = subcategoria + "_" + tier + "_One_Piece_Gaiden_Foro_Rol.gif";
    }

    let imagen = `/images/op/iconos/${imagen_nombre}`;
    if (imagen_avatar !== '') {
        imagen = imagen_avatar;
    }

    var colorTier = '#faa500';
    switch (String(imagen_id)) {
        case '1': colorTier = '#808080'; break;
        case '2': colorTier = '#4dfe45'; break;
        case '3': colorTier = '#457bfe'; break;
        case '4': colorTier = '#cf44ff'; break;
        case '5': colorTier = '#febb46'; break;
        default:
            colorTier = '#808080';
    }

    // --- Determinar si es subasta o venta directa ---
	const fechaSubasta = (listing && listing.fecha_final_subasta !== undefined && listing.fecha_final_subasta !== null)
		? listing.fecha_final_subasta
		: null;

	const finMs = listing._finMs ?? (parseFechaMySQL(listing.fecha_final_subasta)?.getTime() ?? null);
	let esSubasta = !!(finMs && finMs > Date.now());

	// Fallback anti “venta directa” si hay pujas y no hay compra inmediata:
	if (!esSubasta) {
		const tieneCompraInmediata = Number(listing.precio_compra) > 0;
		const hayActividadSubasta = Number(listing.precio_actual) > 0 || Number(listing.precio_minimo) > 0;
		if ((listing.estado === 'activa' || listing.estado === 'open') && !tieneCompraInmediata && hayActividadSubasta) {
			esSubasta = true;
		}
	}

	const tipoVenta = esSubasta ? 'SUBASTA' : 'VENTA DIRECTA';

	// ==== NUEVO: pintar precios correctos ====
	let precioMostrar = '';
	let etiquetaPrecio = '';

	if (esSubasta) {
		const pujaActual = (listing.precio_actual != null) ? Number(listing.precio_actual) : Number(listing.precio_minimo);
		if (listing.precio_actual != null) {
			// ya hay pujas
			precioMostrar = `Puja actual: ${calculateBerries(pujaActual)}`;
		} else {
			// sin pujas -> mostrar mínimo
			precioMostrar = `Precio mín: ${calculateBerries(pujaActual)}`;
		}
		etiquetaPrecio = calculateBerries(pujaActual);
	} else {
		// Venta directa pura: el backend guarda precio_actual como precio fijo
		const precioVenta = Number(
			(listing.precio_actual != null ? listing.precio_actual : null)
			?? (listing.precio_compra != null ? listing.precio_compra : null)
			?? listing.precio_minimo
		);
		precioMostrar = calculateBerries(precioVenta);
		etiquetaPrecio = calculateBerries(precioVenta);
	}

    // Para mostrar tiempo restante (solo si tenemos finMs)
    let infoTiempo = '';
    if (esSubasta && finMs) {
        const tiempoRestante = calcularTiempoRestante(new Date(finMs));
        if (tiempoRestante && tiempoRestante !== 'NO ES SUBASTA') {
            infoTiempo = `<br><small style="color: #ffcc00;">${tiempoRestante}</small>`;
        }
    }

    // Debug útil
    console.log('=== ANÁLISIS DE TIPO DE VENTA ===');
    console.log('Listing ID:', listing.id);
    console.log('fecha_final_subasta (original):', fechaSubasta, '(tipo:', typeof fechaSubasta, ')');
    console.log('esSubasta:', esSubasta);
    console.log('tipoVenta:', tipoVenta);
    console.log('precio_minimo:', listing.precio_minimo);
    console.log('precio_compra:', listing.precio_compra);
    console.log('precio_actual:', listing.precio_actual);
    console.log('===================================');

    // Info extra
    const infoVendedor = listing.vendedor_nombre ? `<br><small>Vendedor: ${listing.vendedor_nombre}</small>` : '';
    const notasVendedor = listing.notas ? `<br><em style="color: #cccccc; font-size: 11px;">"${listing.notas}"</em>` : '';

    // Verificar si el objeto todavía no es visible públicamente
    let esPrivado = false;
    let infoPrivado = '';
    if (typeof usuarioUid !== 'undefined' && listing.vendedor_uid == usuarioUid && listing.disponible_desde) {
        const disponibleDesde = parseFechaMySQL(listing.disponible_desde);
        if (disponibleDesde && disponibleDesde.getTime() > Date.now()) {
            esPrivado = true;
            const tiempoRestantePrivado = calcularTiempoRestante(disponibleDesde);
            infoPrivado = `<br><div style="background: #e67e22; padding: 5px; margin-top: 5px; border-radius: 3px; font-size: 11px;">
                <i class="fa fa-eye-slash"></i> <strong>SOLO VISIBLE PARA TI</strong><br>
            </div>`;
        }
    }

    let botonRecuperar = '';
    if (typeof usuarioUid !== 'undefined' && listing.vendedor_uid == usuarioUid) {
        botonRecuperar = `
            <div style="margin-top: 5px;">
                <button class="btn-recuperar" data-listing-id="${listing.id}" 
                        style="background: #d35400; color: white; border: none; padding: 3px 8px; border-radius: 3px; font-size: 10px; cursor: pointer;">
                    Recuperar Objeto
                </button>
            </div>`;
    }

    $(`#bazar_${subcategoria}_objetos`).append(`
        <div>
            <div class="item-outer bazar-listing ${esPrivado ? 'listing-privado' : ''}" data-listing-id="${listing.id}" data-es-subasta="${esSubasta}">
                <div class="item-nombre">${objeto.nombre} ${listing.cantidad > 1 ? `(x${listing.cantidad})` : ''}</div>
                <div class="item-image">
                    <div class="tooltip">
                        <img src="${imagen}" ${esPrivado ? 'style="opacity: 0.7; filter: grayscale(30%);"' : ''} />
                        <div class="tooltiptext item-tooltip">
                            <div style="font-size: 15px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: ${colorTier};border-top-left-radius: 6px;border: 0px;border-top-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;">
                                ${objeto.nombre}
                            </div>
                            <div class="bazar-badge">${tipoVenta}</div>
                            ${objeto.requisitos ? `<div style="font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;">${objeto.requisitos}</div>` : ''}
                            ${objeto.escalado ? `<div style="font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;">${objeto.escalado}</div>` : ''}
                            <div class="mydescripcion" style="font-family: InterRegular;padding: 5px;text-align: justify;">${objeto.descripcion}</div>
                            ${objeto.dano ? `<div style="font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;">${objeto.dano}</div>` : ''}
                            ${objeto.efecto ? `<div style="font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;">${objeto.efecto}</div>` : ''}
                            <div style="font-family: moonGetHeavy;padding: 2px 5px;display: flex;flex-direction: row;justify-content: space-between;background: ${colorTier};border-top: 1px solid black;border-bottom: 1px solid black;font-size: 9px;text-shadow: 1px 1px 2px black;filter: saturate(0.7);">
                                <div>${objeto.espacios} Espacios</div>
                                <div>ID: <span>${objeto.objeto_id}</span></div>
                                <div>Cantidad: ${listing.cantidad}</div>
                            </div>
                            <div style="font-size: 9px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: ${colorTier};border-bottom-left-radius: 6px;border: 0px;border-bottom-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;">
                                ${objeto.subcategoria} - Tier ${objeto.tier} [${tipoVenta}]
                            </div>
                            <div style="font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;background-color: rgba(0,0,0,0.3);">
                                ${precioMostrar}${infoVendedor}${infoTiempo}${notasVendedor}${infoPrivado}${botonRecuperar}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img src="/images/op/uploads/Cartel_Precio_One_Piece_Gaiden_Foro_Rol.png" style="width: 82px; margin-top: 2px;">
            <div class="item-price">${etiquetaPrecio}</div>
        </div>
    `);
}

// Función para cargar el inventario tradeable del jugador
function cargarInventarioTradeable() {
    console.log('=== CARGANDO INVENTARIO TRADEABLE ===');
    console.log('inventarioTradeable:', inventarioTradeable);
    console.log('inventarioTradeable length:', inventarioTradeable ? inventarioTradeable.length : 'undefined');
    console.log('objetos_comerciables_json disponible:', typeof objetos_comerciables_json !== 'undefined');

    if (typeof objetos_comerciables_json === 'string') {
        try { objetos_comerciables_json = JSON.parse(objetos_comerciables_json); } catch (e) { }
    }

    // Limpiar contenido - usar el ID correcto del HTML
    $('#inventario-tradeable-lista').empty();

    // Verificar si inventarioTradeable está definido y tiene elementos
    if (!inventarioTradeable || inventarioTradeable.length === 0) {
        console.log('No hay inventario tradeable disponible');
        $('#inventario-tradeable-lista').append(`
            <div style="text-align: center; padding: 50px; color: white; font-family: InterRegular; font-size: 16px;">
                <i class="fa fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i><br>
                No tienes objetos que puedas vender.<br>
                <small>Se muestran los objetos con (comerciable = 0 y negro = 1) o cuyo ID empieza por números, ya que son crafteos únicos</small>
            </div>
        `);
        return;
    }

    // Verificar que objetos_comerciables_json esté disponible
    if (typeof objetos_comerciables_json === 'undefined' || !objetos_comerciables_json) {
        console.error('objetos_comerciables_json no está definido');
        $('#inventario-tradeable-lista').append(`
            <div style="text-align: center; padding: 50px; color: white; font-family: InterRegular; font-size: 16px;">
                <i class="fa fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; color: #ff4444;"></i><br>
                Error: No se pueden cargar los datos de objetos comerciables.<br>
                <small>objetos_comerciables_json no está disponible</small>
            </div>
        `);
        return;
    }

    console.log('Procesando', inventarioTradeable.length, 'objetos del inventario');
    console.log('objetos_comerciables_json contiene', Object.keys(objetos_comerciables_json).length, 'objetos');

    // Agrupar por subcategoría - SOLO OBJETOS COMERCIABLES
    const objetosPorSubcategoria = {};
    let objetosVendibles = 0;

    inventarioTradeable.forEach(item => {

        const obj = objetos_comerciables_json[item.objeto_id]?.[0];
        if (!obj) {
            console.warn('Objeto no encontrado en objetos_comerciables_json:', item.objeto_id);
            return;
        }

        const esComerciable = Number(obj.comerciable) === 0;
        const esNegro = Number(obj.negro) === 1;
        const empiezaNumero = /^\d/.test(item.objeto_id || obj.objeto_id);

        // Regla de inclusión
        const esVendible = (esComerciable && esNegro) || empiezaNumero;
        if (!esVendible) {
            return;
        }

        objetosVendibles++;

        const subcatNombre = (obj.subcategoria || 'Otros').trim();
        const subcategoria = subcatNombre.toLowerCase().replace(/\s+/g, '_');

        if (!objetosPorSubcategoria[subcategoria]) {
            objetosPorSubcategoria[subcategoria] = {
                nombre: subcatNombre.toUpperCase(),
                objetos: []
            };
        }

        const motivos = [];
        if (esComerciable && esNegro) motivos.push('TRADEABLE + MERCADO NEGRO');
        if (empiezaNumero) motivos.push('OBJETO ÚNICO');
        const badgeTexto = motivos.join(' / ') || 'MI INVENTARIO';

        objetosPorSubcategoria[subcategoria].objetos.push({
            inventario: item,
            objeto: obj,
            badgeTexto
        });
    });

    console.log('Objetos comerciables encontrados:', objetosVendibles);

    // Si no hay objetos comerciables
    if (objetosVendibles === 0) {
        $('#inventario-tradeable-lista').append(`
            <div style="text-align: center; padding: 50px; color: white; font-family: InterRegular; font-size: 16px;">
                <i class="fa fa-box-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i><br>
                No tienes objetos comerciables en tu inventario.<br>
                <small>Solo los objetos con la propiedad "comerciable = 0" y "negro = 1" o los crafteos únicos pueden ser vendidos.</small>
            </div>
        `);
        return;
    }

    // Crear HTML para cada subcategoría - usar el ID correcto
    Object.keys(objetosPorSubcategoria).forEach(subcategoria => {
        const data = objetosPorSubcategoria[subcategoria];

        $('#inventario-tradeable-lista').append(`
            <div id="vender_${subcategoria}_area">
                <div class="objeto_categoria">${data.nombre} - MI INVENTARIO COMERCIABLE</div>
                <div class="barra_categoria"></div>
                <div id="vender_${subcategoria}_objetos" class="objetos_lista"></div>
            </div>
        `);

        // Agregar cada objeto
        data.objetos.forEach(item => {
            agregarObjetoVendible(item.inventario, item.objeto, subcategoria, item.badgeTexto);
        });
    });

    console.log('=== FIN CARGA INVENTARIO TRADEABLE ===');
}

// Función para agregar un objeto vendible al HTML
function agregarObjetoVendible(inventarioItem, objeto, subcategoria, badgeTexto = 'MI INVENTARIO') {
    const tier = objeto.tier;
    const imagen_id = objeto.imagen_id;
    const imagen_avatar = objeto.imagen_avatar;

    let imagen_nombre = subcategoria + "_" + imagen_id + "_One_Piece_Gaiden_Foro_Rol.jpg";
    if (subcategoria === 'cofres' || subcategoria === 'akuma_no_mi' || objeto.objeto_id === 'EANM001' ||
        objeto.objeto_id === 'LLST001' || objeto.objeto_id === 'THR001' || subcategoria === 'materiales' ||
        subcategoria === 'tecnicas' || objeto.objeto_id === 'EPA001' || objeto.objeto_id === 'KMP001' ||
        objeto.objeto_id === 'VCD001' || objeto.objeto_id === 'VCZ001' || subcategoria === 'documentos' ||
        subcategoria === 'recetas') {
        imagen_nombre = subcategoria + "_" + tier + "_One_Piece_Gaiden_Foro_Rol.gif";
    }

    let imagen = `/images/op/iconos/${imagen_nombre}`;
    if (imagen_avatar !== '') {
        imagen = imagen_avatar;
    }

    let colorTier = '#faa500';
    if (imagen_id == '1') { colorTier = '#808080'; }
    if (imagen_id == '2') { colorTier = '#4dfe45'; }
    if (imagen_id == '3') { colorTier = '#457bfe'; }
    if (imagen_id == '4') { colorTier = '#cf44ff'; }
    if (imagen_id == '5') { colorTier = '#febb46'; }
    if (parseInt(imagen_id) >= 6) {
        colorTier = 'linear-gradient(315deg, rgba(255,0,0,1) 0%, rgba(255,154,0,1) 10%, rgba(208,222,33,1) 20%, rgba(79,220,74,1) 30%, rgba(63,218,216,1) 40%, rgba(47,201,226,1) 50%, rgba(28,127,238,1) 60%, rgba(95,21,242,1) 70%, rgba(186,12,248,1) 80%, rgba(251,7,217,1) 90%, rgba(255,0,0,1) 100%);';
    }

    $(`#vender_${subcategoria}_objetos`).append(`
        <div>
            <div class="item-outer bazar-vendible" data-objeto-id="${objeto.objeto_id}" data-inventario-id="${inventarioItem.id}">
                <div class="item-nombre">${objeto.nombre} ${inventarioItem.cantidad > 1 ? `(x${inventarioItem.cantidad})` : ''}</div>
                <div class="item-image">
                    <div class="tooltip">
                        <img src="${imagen}" />
                        <div class="tooltiptext item-tooltip">
                            <div style="font-size: 15px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: ${colorTier};border-top-left-radius: 6px;border: 0px;border-top-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;">
                                ${objeto.nombre}
                            </div>
                            <div class="bazar-badge">${badgeTexto}</div>
                            ${objeto.requisitos ? `<div style="font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;">${objeto.requisitos}</div>` : ''}
                            ${objeto.escalado ? `<div style="font-family: InterRegular;padding: 5px;text-align: justify;border-bottom: 1px solid #5e5e5e;">${objeto.escalado}</div>` : ''}
                            <div class="mydescripcion" style="font-family: InterRegular;padding: 5px;text-align: justify;">${objeto.descripcion}</div>
                            ${objeto.dano ? `<div style="font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;">${objeto.dano}</div>` : ''}
                            ${objeto.efecto ? `<div style="font-family: InterRegular;padding: 5px;text-align: center;border-top: 1px solid #5e5e5e;">${objeto.efecto}</div>` : ''}
                            <div style="font-family: moonGetHeavy;padding: 2px 5px;display: flex;flex-direction: row;justify-content: space-between;background: ${colorTier};border-top: 1px solid black;border-bottom: 1px solid black;font-size: 9px;text-shadow: 1px 1px 2px black;filter: saturate(0.7);">
                                <div>${objeto.espacios} Espacios</div>
                                <div>ID: <span>${objeto.objeto_id}</span></div>
                                <div>Cantidad: ${inventarioItem.cantidad}</div>
                            </div>
                            <div style="font-size: 9px;font-family: moonGetHeavy;letter-spacing: 1px;text-align: center;background: ${colorTier};border-bottom-left-radius: 6px;border: 0px;border-bottom-right-radius: 6px;padding: 3px;text-shadow: 1px 1px 3px black;">
                                ${objeto.subcategoria} - Tier ${objeto.tier} [TRADEABLE]
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <img src="/images/op/uploads/Cartel_Precio_One_Piece_Gaiden_Foro_Rol.png" style="width: 82px; margin-top: 2px;">
            <div class="item-price">VENTA</div>
        </div>
    `);
}

// Función para calcular tiempo restante en subastas
function calcularTiempoRestante(fechaFinal) {
    const fin = fechaFinal instanceof Date ? fechaFinal : parseFechaMySQL(fechaFinal);
    if (!fin) return 'NO ES SUBASTA';
    const diff = fin.getTime() - Date.now();
    if (diff <= 0) return 'FINALIZADA';
    const d = Math.floor(diff / 86400000);
    const h = Math.floor((diff % 86400000) / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    if (d > 0) return `${d}d ${h}h restantes`;
    if (h > 0) return `${h}h ${m}m restantes`;
    return `${m}m restantes`;
}

// Función para actualizar el estado de los listings privados
function actualizarListingsPrivados() {
    const ahora = Date.now();
    let hayActualizaciones = false;
    
    $('.listing-privado').each(function() {
        const $listing = $(this);
        const listingId = $listing.data('listing-id');
        const listing = listingsActivos.find(l => l.id == listingId);
        
        if (listing && listing.disponible_desde) {
            const disponibleDesde = parseFechaMySQL(listing.disponible_desde);
            if (disponibleDesde && disponibleDesde.getTime() <= ahora) {
                // El listing ya es público, remover la clase privada
                $listing.removeClass('listing-privado');
                $listing.find('img').css({'opacity': '1', 'filter': 'none'});
                
                // Actualizar tooltip para remover el mensaje de privado
                const $tooltip = $listing.find('.tooltiptext');
                $tooltip.find('[style*="background: #e67e22"]').remove();
                
                hayActualizaciones = true;
                console.log(`Listing ${listingId} ahora es público`);
            }
        }
    });
    
    return hayActualizaciones;
}

// Iniciar verificación periódica de listings privados
let intervalActualizacionPrivados = null;

function iniciarActualizacionPrivados() {
    if (intervalActualizacionPrivados) {
        clearInterval(intervalActualizacionPrivados);
    }
    
    // Verificar cada minuto si hay listings que se han vuelto públicos
    intervalActualizacionPrivados = setInterval(() => {
        const actualizado = actualizarListingsPrivados();
        if (actualizado) {
            console.log('Algunos listings privados ahora son públicos');
        }
    }, 60000); // 60 segundos
}

// Event handlers para el bazar
$(document).ready(function () {
    // Click en objetos en venta (comprar)
    $(document).on('click', '.bazar-listing', function () {
        const listingId = $(this).data('listing-id');

        console.log('=== CLICK EN BAZAR-LISTING ===');
        console.log('listingId:', listingId);

        if (!listingId) {
            console.error('listingId no definido');
            alert('Error: ID del listing no encontrado');
            return;
        }

        // Encontrar el listing en los datos
        const listing = listingsActivos.find(l => l.id == listingId);
        if (!listing) {
            console.error('Listing no encontrado en datos:', listingId);
            alert('Error: Objeto no encontrado');
            return;
        }

        // Determinar tipo de venta basado en los datos reales
        const fechaSubasta = listing.fecha_final_subasta;
        const fechasInvalidas = [null, undefined, '', '0000-00-00 00:00:00', 'null', '0'];
        const tieneFecharValida = fechaSubasta && !fechasInvalidas.includes(fechaSubasta);

        // Solo permitir compra directa (subastas deshabilitadas)
        console.log('Abriendo modal de COMPRA DIRECTA');
        mostrarModalComprarDirecto(listingId);

    });

    // Click en objetos del inventario (vender)
    $(document).on('click', '.bazar-vendible', function () {
        const objetoId = $(this).data('objeto-id');
        const inventarioId = $(this).data('inventario-id');
        mostrarModalVender(objetoId, inventarioId);
    });

    // Click en botón de recuperar objeto
    $(document).on('click', '.btn-recuperar', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Evitar que active el click del listing

        const listingId = $(this).data('listing-id');
        mostrarModalRecuperar(listingId);
    });
});

// Modal para compra directa
function mostrarModalComprarDirecto(listingId) {
    console.log('mostrarModalComprarDirecto llamada con listingId:', listingId);
    console.log('listingsActivos disponible:', listingsActivos);

    const listing = listingsActivos.find(l => l.id == listingId); // Usar == en lugar de === para comparación flexible
    if (!listing) {
        console.error('Listing no encontrado con ID:', listingId);
        console.error('IDs disponibles:', listingsActivos.map(l => l.id));
        alert('Error: El objeto ya no está disponible.');
        return;
    }

    console.log('Listing encontrado:', listing);

    // Verificar que el objeto existe en objetos_comerciables_json (todos los objetos comerciables)
    let objeto = null;
    if (objetos_comerciables_json && objetos_comerciables_json[listing.objeto_id] && objetos_comerciables_json[listing.objeto_id][0]) {
        objeto = objetos_comerciables_json[listing.objeto_id][0];
    } else if (objetos_json && objetos_json[listing.objeto_id] && objetos_json[listing.objeto_id][0]) {
        objeto = objetos_json[listing.objeto_id][0];
    } else {
        console.error('Objeto no encontrado:', listing.objeto_id);
        alert('Error: No se pudo encontrar la información del objeto.');
        return;
    }

    const precioUnitario = Number(
    (listing.precio_actual != null ? listing.precio_actual : null)
    ?? (listing.precio_compra != null ? listing.precio_compra : null)
    ?? listing.precio_minimo
	);
	const precioTotal = precioUnitario * Number(listing.cantidad || 1);

    const modalHtml = `
        <div id="modal-comprar-directo" class="bazar-modal">
            <div class="bazar-modal-content">
                <span class="close" onclick="cerrarModal('modal-comprar-directo')">&times;</span>
                <h2>Comprar Objeto</h2>
                <div class="modal-objeto-info">
                    <h3>${objeto.nombre}</h3>
                    <p>Cantidad: ${listing.cantidad}</p>
                    <p>Precio total: ${calculateBerries(precioTotal)} berries</p>
                    ${listing.notas ? `<p><em>"${listing.notas}"</em></p>` : ''}
                </div>
                <div class="modal-botones">
                    <button onclick="confirmarCompraDirecta(${listingId})" class="btn-confirmar">Comprar</button>
                    <button onclick="cerrarModal('modal-comprar-directo')" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
    $('#modal-comprar-directo').show();
}

// Modal para pujar en subasta
function mostrarModalPujar(listingId) {
    console.log('mostrarModalPujar llamada con listingId:', listingId);
    console.log('listingsActivos disponible:', listingsActivos);

    const listing = listingsActivos.find(l => l.id == listingId); // Usar == en lugar de === para comparación flexible
    if (!listing) {
        console.error('Listing no encontrado con ID:', listingId);
        console.error('IDs disponibles:', listingsActivos.map(l => l.id));
        alert('Error: El objeto ya no está disponible.');
        return;
    }

    console.log('Listing encontrado:', listing);
    console.log('Propiedades del listing:', Object.keys(listing));
    console.log('precio_actual (raw):', listing.precio_actual);
    console.log('precio_actual (tipo):', typeof listing.precio_actual);
    console.log('precio_minimo (raw):', listing.precio_minimo);
    console.log('precio_minimo (tipo):', typeof listing.precio_minimo);
    console.log('precio_actual || precio_minimo:', listing.precio_actual || listing.precio_minimo);
    console.log('calculateBerries(precio_actual || precio_minimo):', calculateBerries(listing.precio_actual || listing.precio_minimo));

    // Verificar que el objeto existe en objetos_comerciables_json (todos los objetos comerciables)
    let objeto = null;
    if (objetos_comerciables_json && objetos_comerciables_json[listing.objeto_id] && objetos_comerciables_json[listing.objeto_id][0]) {
        objeto = objetos_comerciables_json[listing.objeto_id][0];
    } else if (objetos_json && objetos_json[listing.objeto_id] && objetos_json[listing.objeto_id][0]) {
        // Fallback para objetos del mercado negro
        objeto = objetos_json[listing.objeto_id][0];
    } else {
        console.error('Objeto no encontrado:', listing.objeto_id);
        alert('Error: No se pudo encontrar la información del objeto.');
        return;
    }

    const tiempoRestante = calcularTiempoRestante(listing.fecha_final_subasta);

    // Convertir precios a enteros para evitar problemas de tipo
    const precioMinimo = parseInt(listing.precio_minimo) || 0;
    const precioActual = listing.precio_actual ? parseInt(listing.precio_actual) : null;
    const pujaMinima = (precioActual || precioMinimo) + 1;

    console.log('Valores para modal:', {
        precioMinimo: precioMinimo,
        precioActual: precioActual,
        pujaMinima: pujaMinima
    });

    const modalHtml = `
        <div id="modal-pujar" class="bazar-modal">
            <div class="bazar-modal-content">
                <span class="close" onclick="cerrarModal('modal-pujar')">&times;</span>
                <h2>Pujar en Subasta</h2>
                <div class="modal-objeto-info">
                    <h3>${objeto.nombre}</h3>
                    <p>Cantidad: ${listing.cantidad}</p>
                    <p>Precio mínimo: ${calculateBerries(precioMinimo)} berries</p>
                    <p>Puja actual: ${calculateBerries(precioActual || precioMinimo)} berries</p>
                    <p>Tiempo restante: ${tiempoRestante}</p>
                    ${listing.notas ? `<p><em>"${listing.notas}"</em></p>` : ''}
                </div>
                <div class="modal-form">
                    <label>Tu puja:</label>
                    <input type="number" id="input-puja" placeholder="Cantidad en berries" min="${pujaMinima}">
                    <small>Puja mínima: ${calculateBerries(pujaMinima)} berries</small>
                </div>
                <div class="modal-botones">
                    <button onclick="confirmarPuja(${listingId})" class="btn-confirmar">Pujar</button>
                    <button onclick="cerrarModal('modal-pujar')" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
    $('#modal-pujar').show();
}

// Modal para vender objeto
function mostrarModalVender(objetoId, inventarioId) {
    const inventarioItem = inventarioTradeable.find(i => i.id === inventarioId);

    // Verificar que el objeto existe en objetos_comerciables_json
    if (!objetos_comerciables_json || !objetos_comerciables_json[objetoId] || !objetos_comerciables_json[objetoId][0]) {
        console.error('Objeto no encontrado en objetos_comerciables_json:', objetoId);
        alert('Error: No se pudo encontrar la información del objeto.');
        return;
    }

    const objeto = objetos_comerciables_json[objetoId][0];

    if (!inventarioItem || !objeto) return;

    const modalHtml = `
        <div id="modal-vender" class="bazar-modal">
            <div class="bazar-modal-content">
                <span class="close" onclick="cerrarModal('modal-vender')">&times;</span>
                <h2>Vender Objeto</h2>
                <div class="modal-objeto-info">
                    <h3>${objeto.nombre}</h3>
                    <p>Cantidad disponible: ${inventarioItem.cantidad}</p>
                </div>
                <div class="modal-form">
                    <label>Cantidad a vender:</label>
                    <input type="number" id="input-cantidad" value="1" min="1" max="${inventarioItem.cantidad}">
                    
                    <!-- Campos para Venta Directa -->
                    <div id="div-venta-directa" style="display: block;">
                        <label>Precio de venta (berries):</label>
                        <input type="number" id="input-precio-venta" placeholder="Precio de venta" min="1">
                    </div>
                    
                    <label>Notas (opcional):</label>
                    <textarea id="input-notas" placeholder="Información adicional para los compradores..." rows="3" maxlength="200"></textarea>
                </div>
                <div class="modal-botones">
                    <button onclick="confirmarVenta('${objetoId}', ${inventarioId})" class="btn-confirmar">Poner a la Venta</button>
                    <button onclick="cerrarModal('modal-vender')" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
    $('#modal-vender').show();
}

// Funciones de confirmación
function confirmarCompraDirecta(listingId) {
    if (!confirm('¿Confirmas que deseas comprar este objeto?')) return;

    $.post("/op/mercado_negro.php", {
        accion: 'comprar_ahora',
        listing_id: listingId
    }, function (data) {
        console.log('Respuesta de compra directa:', data);
        console.log('Tipo de respuesta:', typeof data);
        if (Array.isArray(data)) {
            console.log('Primer elemento:', data[0]);
            if (data[0]) {
                console.log('Success:', data[0].success);
                console.log('Message:', data[0].message);
            }
        }

        if (data && data[0] && data[0].success) {
            alert('¡Compra realizada exitosamente!');
            cerrarModal('modal-comprar-directo');
            cargarObjetosEnVenta(); // Recargar lista
            localStorage.setItem('modoBazar', modoBazar);
            location.reload();
        } else {
            console.error('Error en la compra. Respuesta completa:', data);
            alert('Error al realizar la compra: ' + (data[0]?.message || 'Error desconocido'));
        }
    }).fail(function (xhr, status, error) {
        console.error('Error de conexión en compra:', status, error);
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

function confirmarPuja(listingId) {
    const puja = parseInt($('#input-puja').val());
    if (!puja || puja <= 0) {
        alert('Por favor, ingresa una puja válida.');
        return;
    }

    console.log('confirmarPuja llamada con listingId:', listingId);
    console.log('listingsActivos:', listingsActivos);

    const listing = listingsActivos.find(l => l.id == listingId); // Usar == para comparación flexible
    if (!listing) {
        console.error('Listing no encontrado para puja con ID:', listingId);
        console.error('IDs disponibles:', listingsActivos.map(l => l.id));
        alert('Error: El objeto ya no está disponible.');
        return;
    }

    console.log('Listing encontrado para puja:', listing);
    console.log('Propiedades del listing para puja:', Object.keys(listing));
    console.log('precio_actual para puja:', listing.precio_actual);
    console.log('precio_minimo para puja:', listing.precio_minimo);

    // Usar precio_actual si existe, sino usar precio_minimo
    const precioActualRaw = listing.precio_actual;
    const precioMinimoRaw = listing.precio_minimo;

    console.log('Valores raw antes de conversión:', {
        precioActualRaw: precioActualRaw,
        precioMinimoRaw: precioMinimoRaw,
        tipoActual: typeof precioActualRaw,
        tipoMinimo: typeof precioMinimoRaw
    });

    // Convertir a entero para asegurar cálculos correctos
    const precioActual = precioActualRaw ? parseInt(precioActualRaw) : null;
    const precioMinimo = parseInt(precioMinimoRaw) || 0;
    const pujaActual = precioActual || precioMinimo;
    const minimo = pujaActual + 1;

    console.log('Valores después de conversión:', {
        precioActual: precioActual,
        precioMinimo: precioMinimo,
        pujaActual: pujaActual,
        minimo: minimo
    });

    if (puja < minimo) {
        alert(`La puja debe ser mayor a ${calculateBerries(pujaActual)} berries.`);
        return;
    }

    if (!confirm(`¿Confirmas tu puja de ${calculateBerries(puja)} berries?`)) return;

    console.log('Enviando puja:', { listingId: listingId, puja: puja });

    $.post("/op/mercado_negro.php", {
        accion: 'pujar',
        listing_id: listingId,
        puja: puja
    }, function (data) {
        console.log('Respuesta de puja:', data);
        if (data && data[0] && data[0].success) {
            alert('¡Puja realizada exitosamente!');
            cerrarModal('modal-pujar');
            cargarObjetosEnVenta(); // Recargar lista
            localStorage.setItem('modoBazar', modoBazar);
            location.reload();
        } else {
            console.error('Error en puja:', data);
            alert('Error al realizar la puja: ' + (data && data[0] && data[0].message ? data[0].message : 'Error desconocido'));
        }
    }).fail(function (xhr, status, error) {
        console.error('Error de conexión en puja:', status, error);
        console.error('Respuesta del servidor:', xhr.responseText);
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

function confirmarVenta(objetoId, inventarioId) {
    const cantidad = parseInt($('#input-cantidad').val());
    const notas = $('#input-notas').val().trim();

    if (!cantidad || cantidad <= 0) {
        alert('Por favor, ingresa una cantidad válida.');
        return;
    }

    // Venta directa: solo precio de venta, no hay subasta
    const precioVenta = parseInt($('#input-precio-venta').val());
    if (!precioVenta || precioVenta <= 0) {
        alert('Por favor, ingresa un precio de venta válido.');
        return;
    }

    // Para venta directa, usamos precio_compra como el precio fijo
    const precioMinimo = precioVenta; // Mínimo = precio de venta
    const precioCompra = precioVenta;  // Precio de compra = precio de venta
    const fechaFinalSubasta = '';      // Sin fecha de subasta

    if (!confirm(`¿Confirmas que deseas poner este objeto a la venta por ${calculateBerries(precioVenta)} berries?`)) {
        return;
    }

    console.log('Datos de venta:', {
        precioMinimo,
        precioCompra,
        fechaFinalSubasta,
        cantidad
    });

    // Datos que se van a enviar
    const postData = {
        accion: 'venta_fin',
        objeto_id: objetoId,
        inventario_id: inventarioId,
        cantidad: cantidad,
        precio_minimo: precioMinimo,
        precio_compra: precioCompra,
        fecha_final_subasta: fechaFinalSubasta,
        notas: notas || null
    };

    console.log('Enviando datos de venta:', postData);

    $.post("/op/mercado_negro.php", postData, function (data) {
        console.log('Respuesta del servidor (tipo):', typeof data);
        console.log('Respuesta del servidor (completa):', data);

        if (data && Array.isArray(data) && data[0]) {
            console.log('Primer elemento de respuesta:', data[0]);
            console.log('Success:', data[0].success);
            console.log('Message:', data[0].message);

            if (data[0].success) {
                alert('¡Objeto puesto a la venta exitosamente!');
                cerrarModal('modal-vender');
                cargarInventarioTradeable(); // Recargar inventario
                localStorage.setItem('modoBazar', modoBazar);
                location.reload();
            } else {
                alert('Error al poner el objeto a la venta: ' + data[0].message);
            }
        } else {
            console.error('Respuesta inesperada del servidor:', data);
            alert('Error: Respuesta inesperada del servidor');
        }
    }).fail(function (xhr, status, error) {
        console.error('Error de conexión:', status, error);
        console.error('Respuesta del servidor:', xhr.responseText);
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

// Funciones auxiliares

function cerrarModal(modalId) {
    $('#' + modalId).remove();
}

// Modal para recuperar objeto
function mostrarModalRecuperar(listingId) {
    const listing = listingsActivos.find(l => l.id == listingId);
    if (!listing) {
        alert('Error: Listing no encontrado');
        return;
    }

    // Verificar que el objeto existe en objetos_comerciables_json (todos los objetos comerciables)
    let objeto = null;
    if (objetos_comerciables_json && objetos_comerciables_json[listing.objeto_id] && objetos_comerciables_json[listing.objeto_id][0]) {
        objeto = objetos_comerciables_json[listing.objeto_id][0];
    } else if (objetos_json && objetos_json[listing.objeto_id] && objetos_json[listing.objeto_id][0]) {
        // Fallback para objetos del mercado negro
        objeto = objetos_json[listing.objeto_id][0];
    } else {
        console.error('Objeto no encontrado:', listing.objeto_id);
        alert('Error: No se pudo encontrar la información del objeto.');
        return;
    }

    // Determinar tipo de venta y si hay pujas
    const fechaSubasta = listing.fecha_final_subasta;
    const fechasInvalidas = [null, undefined, '', '0000-00-00 00:00:00', 'null', '0'];
    const esSubasta = fechaSubasta && !fechasInvalidas.includes(fechaSubasta);
    const tieneOfertas = listing.precio_actual && listing.precio_actual > listing.precio_minimo;

    let mensajeAdvertencia = '';
    if (esSubasta && tieneOfertas) {
        mensajeAdvertencia = `
            <div style="background-color: rgba(255, 165, 0, 0.2); border: 1px solid #ff6b35; padding: 10px; margin-bottom: 15px; border-radius: 5px;">
                <strong>⚠️ Advertencia:</strong> Esta subasta tiene pujas activas. Al recuperar el objeto, se reembolsarán automáticamente los berries al último pujador.
                <br><strong>Puja actual:</strong> ${calculateBerries(listing.precio_actual)} berries
            </div>`;
    }

    const modalHtml = `
        <div id="modal-recuperar" class="bazar-modal">
            <div class="bazar-modal-content">
                <span class="close" onclick="cerrarModal('modal-recuperar')">&times;</span>
                <h2>Recuperar Objeto</h2>
                <div class="modal-objeto-info">
                    <h3>${objeto.nombre}</h3>
                    <p>Cantidad: ${listing.cantidad}</p>
                    <p>Tipo: ${esSubasta ? 'Subasta' : 'Venta Directa'}</p>
                    ${listing.notas ? `<p><em>"${listing.notas}"</em></p>` : ''}
                </div>
                ${mensajeAdvertencia}
                <div style="text-align: center; margin-bottom: 15px;">
                    <p>¿Estás seguro de que deseas recuperar este objeto?</p>
                    <p><small>El objeto será devuelto a tu inventario y el anuncio se cancelará.</small></p>
                </div>
                <div class="modal-botones">
                    <button onclick="confirmarRecuperacion(${listingId})" class="btn-confirmar" style="background-color: #d35400;">Recuperar</button>
                    <button onclick="cerrarModal('modal-recuperar')" class="btn-cancelar">Cancelar</button>
                </div>
            </div>
        </div>
    `;

    $('body').append(modalHtml);
}

// Función para confirmar la recuperación del objeto
function confirmarRecuperacion(listingId) {
    $.post("/op/mercado_negro.php", {
        accion: 'recuperar_objeto',
        listing_id: listingId
    }, function (data) {
        console.log('Respuesta de recuperación:', data);

        if (data && data[0] && data[0].success) {
            alert('¡Objeto recuperado exitosamente!');
            cerrarModal('modal-recuperar');
            cargarObjetosEnVenta(); // Recargar lista
        } else {
            console.error('Error en la recuperación:', data);
            alert('Error al recuperar el objeto: ' + (data[0]?.message || 'Error desconocido'));
        }
    }).fail(function (xhr, status, error) {
        console.error('Error de conexión en recuperación:', status, error);
        alert('Error de conexión. Inténtalo de nuevo.');
    });
}

// Cerrar modales al hacer clic en el overlay
$(document).on('click', '.bazar-modal', function (e) {
    if (e.target === this) {
        $(this).remove();
    }
});