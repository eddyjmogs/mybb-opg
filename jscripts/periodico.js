/**
 * Periódico One Piece Gaiden - Scripts JavaScript
 * Sistema de navegación, modo oscuro, carteles wanted y funcionalidades generales
 */

// Variables globales
let paginaActual = 1;
let totalPaginas = 1;

// Datos de los criminales wanted
const wantedData = {
  player: {
    1: {
      name: "Sephiroth",
      bounty: "603.750.000",
      avatar: "https://i.pinimg.com/1200x/ad/81/65/ad8165f048c5eba500fd1e11c4624dc0.jpg",
      dead: false,
      npc: false,
      profileLink: "/op/ficha.php?uid=276"
    },
    2: {
      name: "Joker",
      bounty: "50.000.000",
      avatar: "https://i.pinimg.com/736x/6a/b3/12/6ab3122f1773678b555ef4c6b929f7fa.jpg",
      dead: false,
      npc: false,
      profileLink: "/op/ficha.php?uid=2"
    },
    3: {
      name: "STORM BREAKER",
      bounty: "75.000.000",
      avatar: "https://i.imgur.com/TvftmBK.jpeg",
      dead: false,
      npc: false,
      profileLink: "/op/ficha.php?uid=3"
    },
    4: {
      name: "DEAD SHOT MIKE",
      bounty: "30.000.000",
      avatar: "https://i.imgur.com/J4em60X.jpeg",
      dead: true,
      npc: false,
      profileLink: "/op/ficha.php?uid=4"
    },
    5: {
      name: "IRON WILL SARAH",
      bounty: "45.000.000",
      avatar: "https://i.imgur.com/sAMXMHQ.jpeg",
      dead: false,
      npc: false,
      profileLink: "/op/ficha.php?uid=5"
    },
    6: {
      name: "IRON WILL SARAH",
      bounty: "45.000.000",
      avatar: "https://i.imgur.com/sAMXMHQ.jpeg",
      dead: false,
      npc: false,
      profileLink: "/op/ficha.php?uid=5"
    },
    7: {
      name: "IRON WILL SARAH",
      bounty: "45.000.000",
      avatar: "https://i.imgur.com/sAMXMHQ.jpeg",
      dead: false,
      npc: false,
      profileLink: "/op/ficha.php?uid=5"
    }
  },
  npc: {
    1: {
      name: "BLACK BART",
      bounty: "150.000.000",
      avatar: "https://onepiecegaiden.com/images/op/uploads/Alberta1.gif",
      dead: false,
      npc: true,
      profileLink: "/op/npcs.php?npc_id=1"
    },
    2: {
      name: "IRON FIST MORGAN",
      bounty: "95.000.000",
      avatar: "https://i.pinimg.com/736x/d4/e5/f6/d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9.jpg",
      dead: false,
      npc: true,
      profileLink: "/op/npcs.php?npc_id=2"
    },
    3: {
      name: "PHANTOM THIEF CLAIRE",
      bounty: "88.000.000",
      avatar: "https://i.pinimg.com/736x/g7/h8/i9/g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2.jpg",
      dead: false,
      npc: true,
      profileLink: "/op/npcs.php?npc_id=3"
    },
    4: {
      name: "MAD SCIENTIST KAIN",
      bounty: "67.000.000",
      avatar: "https://i.pinimg.com/736x/j0/k1/l2/j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5.jpg",
      dead: true,
      npc: true,
      profileLink: "/op/npcs.php?npc_id=4"
    }
  }
};

/**
 * Sistema de navegación del periódico
 */
class PeriodicoNavegacion {
  static navegarPagina(direccion) {
    const paginaAnterior = paginaActual;
    let nueva = paginaActual;
    
    if (direccion === 'next') nueva++;
    else if (direccion === 'prev') nueva--;
    else if (typeof direccion === 'number') nueva = direccion;
    
    // Asegurar que esté en el rango válido
    nueva = Math.max(1, Math.min(totalPaginas, nueva));
    
    console.log(`📋 Navegación: ${paginaAnterior} -> ${nueva} (dirección: ${direccion})`);
    
    // Solo cambiar si es diferente a la página actual
    if (nueva === paginaActual) {
      console.log('📋 No hay cambio de página');
      return;
    }
    
    this.mostrarPagina(nueva);
    this.actualizarBotones();
  }

  static mostrarPagina(num) {
    // Validar el número de página
    if (num < 1 || num > totalPaginas) {
      console.warn(`Página ${num} fuera de rango (1-${totalPaginas})`);
      return;
    }

    $('.newspaper-page').each(function(index) {
      const $page = $(this);
      const pageNum = index + 1;
      if (pageNum === num) {
        $page.show().addClass('active');
        console.log(`✅ Mostrando página ${num}:`, $page.attr('id'), $page.hasClass('active'));
      } else {
        $page.hide().removeClass('active');
        console.log(`❌ Ocultando página ${pageNum}:`, $page.attr('id'), $page.hasClass('active'));
      }
    });

    $('#current-page').text(num);
    paginaActual = num;

    console.log(`📄 Mostrando página ${num} de ${totalPaginas}`);

    // Scroll suave al inicio de la página - Deshabilitado para mantener posición actual
    // $('html, body').animate({ scrollTop: 0 }, 300);
  }

  static actualizarBotones() {
    const prevBtn = $('#prev-page');
    const nextBtn = $('#next-page');

    if (!prevBtn.length || !nextBtn.length) {
      console.warn('⚠️ Botones de navegación no encontrados');
      return;
    }

    prevBtn.prop('disabled', paginaActual <= 1);
    nextBtn.prop('disabled', paginaActual >= totalPaginas);
  }

  static inicializar() {
    // Verificar que los elementos del DOM existen
    if ($('#prev-page').length === 0 || $('#next-page').length === 0 || $('#current-page').length === 0 || $('#total-pages').length === 0) {
      console.error('❌ Elementos de navegación no encontrados en el DOM');
      return;
    }

    // Configurar total de páginas
    totalPaginas = $('.newspaper-page').length;

    // Depuración: Verificar el número de páginas detectadas
    console.log(`🔍 Total de páginas detectadas: ${totalPaginas}`);

    if (totalPaginas === 0) {
      console.error('❌ No se detectaron páginas. Verifique la estructura HTML.');
      return;
    }

    $('#total-pages').text(totalPaginas);

    // Mostrar primera página
    this.mostrarPagina(1);
    this.actualizarBotones();

    // Remover eventos anteriores para evitar duplicación
    $('#prev-page').off('click.navegacion');
    $('#next-page').off('click.navegacion');

    // Eventos de navegación con namespace
    $('#prev-page').on('click.navegacion', (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.navegarPagina('prev');
    });

    $('#next-page').on('click.navegacion', (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.navegarPagina('next');
    });
  }
}

/**
 * Sistema de carteles wanted
 */
class WantedSystem {
  static formatearBounty(bounty) {
    return parseInt(bounty.replace(/\./g, '')).toLocaleString('es-ES') + ' ฿';
  }

  static abrirModal(type, id) {
    const criminal = wantedData[type]?.[id];
    if (!criminal) return;
    
    const modal = $('#wantedModal');
    const modalContent = $('#wantedModalContent');
    const avatarImg = $('#wantedAvatarImg');
    const nameElement = $('#wantedNameLarge');
    const bountyElement = $('#wantedBountyLarge');
    const avatarContainer = $('#wantedAvatarContainer');
    
    // Configurar el contenido del modal
    avatarImg.attr({
      'src': criminal.avatar,
      'alt': criminal.name
    });
    
    nameElement.html(`<a href="${criminal.profileLink}" target="_blank">${criminal.name}</a>`);
    bountyElement.text(this.formatearBounty(criminal.bounty));
    
    // Configurar el fondo según el tipo (NPC o Player)
    modalContent.toggleClass('npc', criminal.npc);
    
    // Aplicar efecto de muerto si es necesario
    if (criminal.dead) {
      modalContent.css('filter', 'saturate(0.5)');
      if (!avatarContainer.find('.wanted-death-overlay').length) {
        avatarContainer.append(
          '<img src="https://static.vecteezy.com/system/resources/previews/017/178/056/non_2x/red-cross-mark-on-transparent-background-free-png.png" class="wanted-death-overlay">'
        );
      }
    } else {
      modalContent.css('filter', 'initial');
      avatarContainer.find('.wanted-death-overlay').remove();
    }
    
    // Mostrar el modal con animación
    modal.fadeIn(300);
    $('body').addClass('modal-open');
  }

  static cerrarModal() {
    $('#wantedModal').fadeOut(300);
    $('body').removeClass('modal-open');
  }

  static inicializar() {
    // Remover eventos anteriores
    $('#wantedModal').off('click.modal');
    $('.wanted-modal-close').off('click.modal');
    
    // Eventos para cerrar modal con namespace
    $('#wantedModal').on('click.modal', (e) => {
      if (e.target.id === 'wantedModal') {
        this.cerrarModal();
      }
    });

    $('.wanted-modal-close').on('click.modal', () => this.cerrarModal());

    // Prevenir scroll del body cuando el modal está abierto
    $(document).off('keydown.modal').on('keydown.modal', (e) => {
      if ($('#wantedModal').is(':visible') && e.which === 27) { // ESC
        this.cerrarModal();
      }
    });
  }
}

/**
 * Sistema de modo oscuro
 */
class ModoOscuro {
  static toggle() {
    const body = $('body');
    const buttons = $('.dark-mode-toggle');
    
    if (body.hasClass('modo-noche')) {
      body.removeClass('modo-noche');
      localStorage.setItem('darkMode', 'false');
      this.actualizarBoton(buttons, false);
      console.log('☀️ Modo claro activado');
    } else {
      body.addClass('modo-noche');
      localStorage.setItem('darkMode', 'true');
      this.actualizarBoton(buttons, true);
      console.log('🌙 Modo oscuro activado');
    }
  }

  static actualizarBoton(buttons, modoOscuro) {
    buttons.each(function() {
      const btn = $(this);
      const icon = btn.find('.icon');
      const text = btn.find('.text');
      
      if (modoOscuro) {
        icon.text('☀️');
        text.text('Modo Claro');
      } else {
        icon.text('🌙');
        text.text('Modo Oscuro');
      }
    });
  }

  static cargarPreferencia() {
    const darkMode = localStorage.getItem('darkMode');
    if (darkMode === 'true') {
      $('body').addClass('modo-noche');
      this.actualizarBoton($('.dark-mode-toggle'), true);
    } else {
      this.actualizarBoton($('.dark-mode-toggle'), false);
    }
  }

  static inicializar() {
    this.cargarPreferencia();
    
    // Remover eventos anteriores para evitar duplicación
    $('.dark-mode-toggle').off('click.darkmode');
    
    // Evento con namespace
    $('.dark-mode-toggle').on('click.darkmode', (e) => {
      e.preventDefault();
      e.stopPropagation();
      this.toggle();
    });
  }
}

/**
 * Sistema de portada
 */
class Portada {
  static cerrar() {
    const portada = $('#portada-inicial');
    if (!portada.length) return;
    
    portada.addClass('fade-out');
    setTimeout(() => portada.hide(), 1000);
  }

  static inicializar() {
    // Remover evento anterior
    $('#portada-inicial').off('click.portada');
    
    // Event listener para cerrar portada al hacer clic con namespace
    $('#portada-inicial').on('click.portada', (e) => {
      e.preventDefault();
      this.cerrar();
    });
    
    // Auto-cerrar portada inmediatamente para testing
    this.cerrar();
  }
}

/**
 * Utilidades generales
 */
class Utils {
  static lazy_loading() {
    // Implementar lazy loading para imágenes
    if ('IntersectionObserver' in window) {
      const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('lazy');
            observer.unobserve(img);
          }
        });
      });

      document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
      });
    }
  }

  static configurarTeclado() {
    // Remover eventos anteriores
    $(document).off('keydown.navegacion');
    
    // Navegación por teclado con namespace
    $(document).on('keydown.navegacion', (e) => {
      // Solo si no hay modal abierto
      if ($('#wantedModal').is(':visible')) return;
      
      switch(e.which) {
        case 37: // left arrow
          e.preventDefault();
          PeriodicoNavegacion.navegarPagina('prev');
          break;
        case 39: // right arrow
          e.preventDefault();
          PeriodicoNavegacion.navegarPagina('next');
          break;
      }
    });
  }

  static optimizarRendimiento() {
    // Remover eventos anteriores
    $(window).off('resize.periodico');
    
    // Debounce para resize events con namespace
    let resizeTimer;
    $(window).on('resize.periodico', () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        // Ajustar elementos responsivos si es necesario
        PeriodicoNavegacion.actualizarBotones();
      }, 250);
    });
  }

  static precargarImagenes() {
    // Precargar imágenes críticas
    const imagenesCriticas = [
      'https://pbs.twimg.com/media/FDidQ2NXoAYFvcH.jpg',
      '/images/op/uploads/Wanted2_One_Piece_Gaiden_Foro_Rol.png',
      '/images/op/uploads/WantedNPC_One_Piece_Gaiden_Foro_Rol.png'
    ];

    imagenesCriticas.forEach(src => {
      const img = new Image();
      img.src = src;
    });
  }
}

/**
 * Funciones globales para compatibilidad con MyBB
 * Estas funciones mantienen la compatibilidad con llamadas inline en el HTML
 */
window.openWantedModal = (type, id) => WantedSystem.abrirModal(type, id);
window.closeWantedModal = () => WantedSystem.cerrarModal();
window.cerrarPortadaSimple = () => Portada.cerrar();
window.toggleDarkModeSimple = () => ModoOscuro.toggle();
window.navegarPagina = (direccion) => PeriodicoNavegacion.navegarPagina(direccion);

/**
 * Inicialización principal
 */
$(document).ready(() => {
  console.log('🏴‍☠️ Iniciando El Gaviotazo...');
  
  // Inicializar todos los sistemas
  try {
    PeriodicoNavegacion.inicializar();
    console.log('✅ Navegación inicializada');
    
    WantedSystem.inicializar();
    console.log('✅ Sistema Wanted inicializado');
    
    ModoOscuro.inicializar();
    console.log('✅ Modo oscuro inicializado');
    
    Portada.inicializar();
    console.log('✅ Portada inicializada');
    
    // Configurar utilidades
    Utils.configurarTeclado();
    console.log('✅ Teclado configurado');
    
    Utils.optimizarRendimiento();
    console.log('✅ Rendimiento optimizado');
    
    Utils.precargarImagenes();
    console.log('✅ Imágenes precargadas');
    
    Utils.lazy_loading();
    console.log('✅ Lazy loading configurado');

    console.log('🏴‍☠️ El Gaviotazo cargado correctamente');
  } catch (error) {
    console.error('❌ Error al cargar El Gaviotazo:', error);
    console.error('Stack trace:', error.stack);
  }
});

/**
 * Exportaciones para módulos (si se usa en el futuro)
 */
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    PeriodicoNavegacion,
    WantedSystem,
    ModoOscuro,
    Portada,
    Utils,
    wantedData
  };
}
