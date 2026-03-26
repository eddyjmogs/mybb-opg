// PrensaRosa.js - Funcionalidad exclusiva de Prensa Rosa
// Sistema de navegación y características específicas para el periódico rosa

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales para Prensa Rosa
    let currentPage = 1;
    let totalPages = 8; // Páginas específicas de Prensa Rosa (actualizado)
    let isDarkModeActive = false;
    
    // Referencias a elementos del DOM
    const prevBtn = document.getElementById('prev-page');
    const nextBtn = document.getElementById('next-page');
    const currentPageElement = document.getElementById('current-page');
    const totalPagesElement = document.getElementById('total-pages');
    const darkModeToggle = document.querySelector('.dark-mode-toggle');
    const portadaInicial = document.getElementById('portada-inicial');
    
    // Inicializar Prensa Rosa
    init();
    
    function init() {
        // PRIMERO: Limpiar cualquier event listener previo para evitar duplicados
        document.removeEventListener('keydown', handleKeyNavigation);
        
        // Verificar si venimos del periódico normal con parámetro de limpieza
        const urlParams = new URLSearchParams(window.location.search);
        const shouldClean = urlParams.get('clean') === '1';
        
        if (shouldClean) {
            // Limpiar URL sin recargar la página
            window.history.replaceState({}, document.title, window.location.pathname);
            // Forzar limpieza completa del estado
            forceCompleteCleanupPrensaRosa();
        }
        
        // Diagnóstico de estado para detectar conflictos
        detectStateConflictsPrensaRosa();
        // Ocultar portada inicial después de 3 segundos
        setTimeout(function() {
            if (portadaInicial) {
                portadaInicial.classList.add('fade-out');
                setTimeout(() => portadaInicial.style.display = 'none', 1000);
            }
        }, 3000);
        
        // Click en portada para saltarla
        if (portadaInicial) {
            portadaInicial.addEventListener('click', function() {
                portadaInicial.classList.add('fade-out');
                setTimeout(() => portadaInicial.style.display = 'none', 500);
            });
        }
        
        // Event listeners para navegación
        if (prevBtn) prevBtn.addEventListener('click', previousPage);
        if (nextBtn) nextBtn.addEventListener('click', nextPage);
        
        // Event listener para modo oscuro
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', toggleDarkMode);
        }
        
        // Navegación con teclado
        document.addEventListener('keydown', handleKeyNavigation);
        
        // Actualizar interfaz inicial
        updatePageCounter();
        updateNavigationButtons();
        
        // Cargar preferencias guardadas
        loadPreferences();
        
        // Inicializar contenido específico de Prensa Rosa
        initPrensaRosaFeatures();
    }
    
    // Navegación entre páginas
    function nextPage() {
        if (currentPage < totalPages) {
            currentPage++;
            showPage(currentPage);
            updatePageCounter();
            updateNavigationButtons();
        }
    }
    
    function previousPage() {
        if (currentPage > 1) {
            currentPage--;
            showPage(currentPage);
            updatePageCounter();
            updateNavigationButtons();
        }
    }
    
    function showPage(pageNumber) {
        // Ocultar todas las páginas
        const allPages = document.querySelectorAll('.newspaper-page');
        allPages.forEach(page => page.classList.remove('active'));
        
        // Mostrar la página correcta
        const pageId = `page-${pageNumber}`;
        const targetPage = document.getElementById(pageId);
        if (targetPage) {
            targetPage.classList.add('active');
        }
        
        // Scroll suave hacia arriba
        window.scrollTo({
            top: 780,
            behavior: 'smooth'
        });
        
        // Efectos especiales para páginas de Prensa Rosa
        addPrensaRosaEffects(pageNumber);
    }
    
    function updatePageCounter() {
        if (currentPageElement) currentPageElement.textContent = currentPage;
        if (totalPagesElement) totalPagesElement.textContent = totalPages;
    }
    
    function updateNavigationButtons() {
        if (prevBtn) {
            prevBtn.disabled = currentPage <= 1;
        }
        
        if (nextBtn) {
            nextBtn.disabled = currentPage >= totalPages;
        }
    }
    
    // Toggle Modo Oscuro específico para Prensa Rosa
    function toggleDarkMode() {
        isDarkModeActive = !isDarkModeActive;
        document.body.classList.toggle('modo-noche', isDarkModeActive);
        
        if (darkModeToggle) {
            const icon = darkModeToggle.querySelector('.icon');
            const text = darkModeToggle.querySelector('.text');
            
            if (isDarkModeActive) {
                if (icon) icon.textContent = '';
                if (text) text.textContent = 'Modo Claro';
            } else {
                if (icon) icon.textContent = '';
                if (text) text.textContent = 'Modo Oscuro';
            }
        }
        
        // Guardar preferencia en localStorage
        localStorage.setItem('prensarosa-dark-mode', isDarkModeActive);
    }
    
    // Navegación con teclado
    function handleKeyNavigation(e) {
        switch(e.key) {
            case 'ArrowLeft':
                previousPage();
                e.preventDefault();
                break;
            case 'ArrowRight':
                nextPage();
                e.preventDefault();
                break;
            case 'Home':
                currentPage = 1;
                showPage(currentPage);
                updatePageCounter();
                updateNavigationButtons();
                e.preventDefault();
                break;
            case 'End':
                currentPage = totalPages;
                showPage(currentPage);
                updatePageCounter();
                updateNavigationButtons();
                e.preventDefault();
                break;
        }
    }
    
    // Funciones específicas de Prensa Rosa
    function initPrensaRosaFeatures() {
        // Inicializar efectos de corazones flotantes
        createFloatingHearts();
        
        // Inicializar animaciones de couples spotlight
        initCoupleSpotlight();
        
        // Inicializar efectos de scandal alerts
        initScandalAlerts();
        
        // Configurar lazy loading para imágenes de parejas
        setupCoupleImageLoading();
    }
    
    function createFloatingHearts() {
        // Limpiar cualquier contenedor existente primero
        const existingContainer = document.querySelector('.floating-hearts-container');
        if (existingContainer) {
            existingContainer.remove();
        }
        
        const heartsContainer = document.createElement('div');
        heartsContainer.className = 'floating-hearts-container';
        heartsContainer.setAttribute('data-prensa-rosa', 'true');
        heartsContainer.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        `;
        document.body.appendChild(heartsContainer);
        
        // Crear corazones cada cierto tiempo
        const heartInterval = setInterval(() => {
            if (Math.random() < 0.3) { // 30% de probabilidad
                createHeart(heartsContainer);
            }
        }, 3000);
        
        // Guardar referencia para poder limpiar después
        window.prensaRosaHeartInterval = heartInterval;
    }
    
    function createHeart(container) {
        const heart = document.createElement('div');
        heart.innerHTML = '💖';
        heart.style.cssText = `
            position: absolute;
            font-size: ${Math.random() * 20 + 15}px;
            opacity: 0.7;
            animation: floatHeart ${Math.random() * 3 + 4}s ease-in-out forwards;
            left: ${Math.random() * 100}%;
            bottom: -50px;
        `;
        
        container.appendChild(heart);
        
        // Remover el corazón después de la animación
        setTimeout(() => {
            if (heart.parentNode) {
                heart.parentNode.removeChild(heart);
            }
        }, 7000);
    }
    
    function initCoupleSpotlight() {
        const coupleSpotlights = document.querySelectorAll('.couple-spotlight');
        
        coupleSpotlights.forEach((spotlight, index) => {
            // Animación de entrada escalonada
            spotlight.style.opacity = '0';
            spotlight.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                spotlight.style.transition = 'all 0.6s ease';
                spotlight.style.opacity = '1';
                spotlight.style.transform = 'translateY(0)';
            }, index * 200);
            
            // Efecto hover especial
            spotlight.addEventListener('mouseenter', function() {
                const hearts = this.querySelectorAll('.heart-divider');
                hearts.forEach(heart => {
                    heart.style.animation = 'pulse 0.5s infinite alternate';
                });
            });
            
            spotlight.addEventListener('mouseleave', function() {
                const hearts = this.querySelectorAll('.heart-divider');
                hearts.forEach(heart => {
                    heart.style.animation = 'none';
                });
            });
        });
    }
    
    function initScandalAlerts() {
        const scandalAlerts = document.querySelectorAll('.scandal-alert');
        
        scandalAlerts.forEach(alert => {
            // Efecto de pulso para alertas de escándalo
            alert.addEventListener('mouseenter', function() {
                this.style.animation = 'pulse 1s infinite';
            });
            
            alert.addEventListener('mouseleave', function() {
                this.style.animation = 'none';
            });
        });
    }
    
    function setupCoupleImageLoading() {
        // Lazy loading específico para imágenes de parejas
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.classList.remove('lazy');
                        }
                        
                        // Efecto de fade-in para las imágenes cargadas
                        img.style.opacity = '0';
                        img.onload = () => {
                            img.style.transition = 'opacity 0.5s ease';
                            img.style.opacity = '1';
                        };
                        
                        observer.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('.couple-image[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    function addPrensaRosaEffects(pageNumber) {
        // Efectos específicos según la página
        switch(pageNumber) {
            case 1:
                // Página principal - efectos de entrada
                animateMainStory();
                break;
            case 2:
                // Página de romances - efectos de corazones
                enhanceRomanceSection();
                break;
            case 3:
                // Página de escándalos - efectos dramáticos
                enhanceScandalSection();
                break;
            case 4:
                // Nueva página de Juuken - efectos especiales
                enhanceJuukenInterview();
                break;
            default:
                // Efectos generales para otras páginas
                addGeneralEffects();
                break;
        }
    }
    
    function animateMainStory() {
        const mainStory = document.querySelector('#page-1 .noticia-principal');
        if (mainStory) {
            mainStory.style.opacity = '0';
            mainStory.style.transform = 'scale(0.9)';
            
            setTimeout(() => {
                mainStory.style.transition = 'all 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                mainStory.style.opacity = '1';
                mainStory.style.transform = 'scale(1)';
            }, 300);
        }
    }
    
    function enhanceRomanceSection() {
        const romanceSection = document.querySelector('#page-2 .romance-section');
        if (romanceSection) {
            // Crear efecto de brillo sutil
            romanceSection.style.boxShadow = '0 0 20px rgba(255, 105, 180, 0.3)';
            
            // Animar elementos internos
            const couples = romanceSection.querySelectorAll('.couple-spotlight');
            couples.forEach((couple, index) => {
                setTimeout(() => {
                    couple.style.transform = 'translateX(-10px)';
                    setTimeout(() => {
                        couple.style.transition = 'transform 0.3s ease';
                        couple.style.transform = 'translateX(0)';
                    }, 100);
                }, index * 150);
            });
        }
    }
    
    function enhanceScandalSection() {
        const scandalSection = document.querySelector('#page-3 .scandal-alert');
        if (scandalSection) {
            // Efecto de parpadeo sutil para escándalos
            let blinkCount = 0;
            const blinkInterval = setInterval(() => {
                scandalSection.style.opacity = scandalSection.style.opacity === '0.8' ? '1' : '0.8';
                blinkCount++;
                
                if (blinkCount >= 6) {
                    clearInterval(blinkInterval);
                    scandalSection.style.opacity = '1';
                }
            }, 200);
        }
    }
    
    function enhanceJuukenInterview() {
        const juukenPage = document.querySelector('#page-4');
        if (juukenPage) {
            // Efecto de brillo diamantino para el título - REMOVIDO
            const pageHeader = juukenPage.querySelector('.page-header h2');
            if (pageHeader) {
                // Los efectos de textShadow y animation han sido removidos
            }
            
            // Crear efectos de diamantes flotantes específicos para esta página
            createDiamondEffects(juukenPage);
            
            // Animar las cajas de chismes con efectos especiales
            const gossipBoxes = juukenPage.querySelectorAll('.gossip-box');
            gossipBoxes.forEach((box, index) => {
                setTimeout(() => {
                    box.style.transition = 'all 0.8s ease';
                    box.style.opacity = '1';
                    box.style.transform = 'scale(1.02)';
                    box.style.boxShadow = '0 8px 25px rgba(64, 224, 208, 0.3)';
                }, index * 300);
            });
            
            // Efecto especial para las líneas tachadas
            const strikethroughElements = juukenPage.querySelectorAll('span[style*="line-through"]');
            strikethroughElements.forEach(element => {
                element.addEventListener('mouseenter', function() {
                    this.style.opacity = '1';
                    this.style.textDecoration = 'none';
                    this.style.background = 'rgba(255, 105, 180, 0.2)';
                });
                
                element.addEventListener('mouseleave', function() {
                    this.style.opacity = '0.7';
                    this.style.textDecoration = 'line-through';
                    this.style.background = 'none';
                });
            });
        }
    }
    
    function createDiamondEffects(container) {
        // Crear efecto de diamantes flotantes específico para la entrevista de Juuken
        const diamondInterval = setInterval(() => {
            if (Math.random() < 0.4) {
                const diamond = document.createElement('div');
                diamond.innerHTML = '💎';
                diamond.style.cssText = `
                    position: absolute;
                    font-size: ${Math.random() * 15 + 10}px;
                    opacity: 0.8;
                    animation: floatDiamond ${Math.random() * 2 + 3}s ease-in-out forwards;
                    left: ${Math.random() * 100}%;
                    top: ${Math.random() * 100}%;
                    pointer-events: none;
                    z-index: 1;
                `;
                
                container.style.position = 'relative';
                container.appendChild(diamond);
                
                setTimeout(() => {
                    if (diamond.parentNode) {
                        diamond.parentNode.removeChild(diamond);
                    }
                }, 5000);
            }
        }, 2000);
        
        // Limpiar el intervalo después de un tiempo
        setTimeout(() => clearInterval(diamondInterval), 30000);
    }
    
    function addGeneralEffects() {
        // Efectos generales para cualquier página
        const gossipBoxes = document.querySelectorAll('.gossip-box');
        gossipBoxes.forEach((box, index) => {
            setTimeout(() => {
                box.style.opacity = '0';
                box.style.transform = 'translateY(10px)';
                box.style.transition = 'all 0.4s ease';
                
                setTimeout(() => {
                    box.style.opacity = '1';
                    box.style.transform = 'translateY(0)';
                }, 50);
            }, index * 100);
        });
    }
    
    // Cargar preferencias guardadas
    function loadPreferences() {
        // Verificar si venimos del periódico normal y necesitamos limpieza completa
        const comingFromPeriodico = sessionStorage.getItem('periodico-cleaned') === 'true';
        
        if (comingFromPeriodico) {
            // Limpiar completamente cualquier estado del periódico normal
            document.body.className = ''; // Reset completo de clases
            sessionStorage.removeItem('periodico-cleaned');
            
            // Limpiar localStorage del periódico normal
            localStorage.removeItem('periodico-dark-mode');
        }
        
        // Limpiar cualquier estado del periódico normal primero
        document.body.classList.remove('modo-noche');
        
        // Cerrar modales del periódico normal que puedan estar abiertos
        const wantedModal = document.getElementById('wantedModal');
        if (wantedModal) {
            wantedModal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Limpiar intervalos del periódico normal si existen
        if (window.periodicoInterval) {
            clearInterval(window.periodicoInterval);
            window.periodicoInterval = null;
        }
        
        // Restablecer estilos de elementos que podrían estar afectados por el periódico normal
        const affectedElements = document.querySelectorAll('.noticia-principal, .wanted-poster, .criminal-card, .periodico-container');
        affectedElements.forEach(element => {
            element.style.animation = '';
            element.style.transform = '';
            element.style.transition = '';
            element.style.opacity = '';
            element.style.boxShadow = '';
        });
        
        // Cargar modo oscuro específico de Prensa Rosa después de la limpieza
        const savedDarkMode = localStorage.getItem('prensarosa-dark-mode');
        if (savedDarkMode === 'true') {
            isDarkModeActive = false; // Reset para que toggleDarkMode funcione correctamente
            toggleDarkMode();
        }
    }
    
    // Función para limpiar el estado de Prensa Rosa antes de cambiar de página
    function cleanupPrensaRosaState() {
        console.log('Iniciando limpieza del estado de Prensa Rosa...');
        
        // Remover clases específicas de Prensa Rosa de forma más agresiva
        document.body.className = document.body.className
            .replace(/prensa-rosa|modo-noche/g, '')
            .replace(/\s+/g, ' ')
            .trim();
        
        // Limpiar localStorage específico de Prensa Rosa
        localStorage.removeItem('prensarosa-dark-mode');
        localStorage.removeItem('prensa-rosa-current-page');
        
        // Limpiar intervalos de corazones flotantes de forma más completa
        const heartsContainer = document.querySelector('.floating-hearts-container');
        if (heartsContainer) {
            heartsContainer.remove();
        }
        
        // Limpiar TODOS los contenedores relacionados con Prensa Rosa
        const prensaRosaContainers = document.querySelectorAll('[data-prensa-rosa], .floating-hearts-container, .prensa-rosa-effects');
        prensaRosaContainers.forEach(container => {
            if (container.parentNode) {
                container.parentNode.removeChild(container);
            }
        });
        
        // Limpiar efectos dinámicos y estilos específicos de Prensa Rosa
        const dynamicStyles = document.querySelectorAll('style[data-prensa-rosa]');
        dynamicStyles.forEach(style => {
            if (style.parentNode) {
                style.parentNode.removeChild(style);
            }
        });
        
        // Limpiar intervalos específicos de Prensa Rosa de forma más agresiva
        if (window.prensaRosaHeartInterval) {
            clearInterval(window.prensaRosaHeartInterval);
            window.prensaRosaHeartInterval = null;
        }
        
        // Limpiar TODOS los intervalos globales de forma más agresiva
        for (let i = 1; i < 99999; i++) {
            try {
                window.clearInterval(i);
                window.clearTimeout(i);
            } catch(e) {
                // Ignorar errores al limpiar intervalos
            }
        }
        
        // Limpiar variables globales de Prensa Rosa
        if (window.prensaRosaFunctions) {
            delete window.prensaRosaFunctions;
        }
        
        // Remover event listeners específicos para evitar conflictos
        try {
            document.removeEventListener('keydown', handleKeyNavigation);
        } catch(e) {
            // Ignorar si no existe
        }
        
        // Limpiar contenido dinámico que podría interferir
        const periodicoContainer = document.querySelector('.periodico-container');
        if (periodicoContainer) {
            // Remover efectos visuales específicos de Prensa Rosa
            periodicoContainer.style.cssText = periodicoContainer.style.cssText
                .replace(/box-shadow[^;]*;?/gi, '')
                .replace(/background[^;]*;?/gi, '');
        }
        
        // Limpiar efectos de elementos específicos de forma más completa
        const elements = document.querySelectorAll('.couple-spotlight, .gossip-box, .scandal-alert, .romance-section, .noticia-principal, .periodico-header');
        elements.forEach(element => {
            // Resetear todos los estilos inline que podrían haber sido modificados
            const propertiesToReset = ['animation', 'transform', 'transition', 'opacity', 'boxShadow', 'background', 'color', 'border'];
            propertiesToReset.forEach(prop => {
                element.style[prop] = '';
            });
        });
        
        // Resetear el body completamente
        document.body.style.removeProperty('background');
        document.body.style.removeProperty('color');
        document.body.style.removeProperty('overflow');
        
        // Forzar reset del viewport
        window.scrollTo(0, 0);
        
        // Marcar que se ha limpiado el estado para el periódico normal
        sessionStorage.setItem('prensa-rosa-cleaned', 'true');
        // Limpiar marcadores de otros estados
        sessionStorage.removeItem('periodico-cleaned');
        
        // Forzar una limpieza del cache del navegador para estilos
        const links = document.querySelectorAll('link[rel="stylesheet"]');
        links.forEach(link => {
            const href = link.href;
            link.href = href + (href.includes('?') ? '&' : '?') + 'cache=' + Date.now();
        });
        
        console.log('Estado de Prensa Rosa limpiado completamente');
    }
    
    // Smooth scrolling para el botón back to top
    const backToTop = document.getElementById('backtop');
    if (backToTop) {
        backToTop.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Mostrar/ocultar botón basado en scroll con efecto rosa
        window.addEventListener('scroll', function() {
            if (window.scrollY > 300) {
                backToTop.style.display = 'block';
                backToTop.style.background = 'linear-gradient(145deg, #ff69b4, #ff1493)';
            } else {
                backToTop.style.display = 'none';
            }
        });
    }
    
    // Función para detectar conflictos de estado en Prensa Rosa
function detectStateConflictsPrensaRosa() {
    // Verificar si hay elementos del periódico normal que podrían interferir
    const periodicoElements = document.querySelectorAll('.wanted-poster, .criminal-card');
    const periodicoStyles = document.querySelectorAll('style[data-periodico]');
    
    // Verificar si hay modales del periódico normal abiertos
    const wantedModal = document.getElementById('wantedModal');
    const modalOpen = wantedModal && (wantedModal.style.display === 'block' || wantedModal.classList.contains('show'));
    
    // Si detectamos interferencia del periódico normal, limpiar
    if (periodicoStyles.length > 0 || modalOpen) {
        console.warn('Conflicto de estado detectado: elementos del periódico normal en Prensa Rosa');
        forceCompleteCleanupPrensaRosa();
        
        // Recargar el contenido si es necesario
        setTimeout(() => {
            loadPreferences();
        }, 200);
    }
    
    // Verificar localStorage inconsistente
    const periodicoStorage = localStorage.getItem('periodico-dark-mode');
    const prensaRosaStorage = localStorage.getItem('prensarosa-dark-mode');
    
    if (periodicoStorage && !prensaRosaStorage) {
        // Estamos en Prensa Rosa pero hay configuraciones del periódico normal
        console.warn('Configuración de localStorage inconsistente detectada en Prensa Rosa');
        localStorage.removeItem('periodico-dark-mode');
    }
}

// Función de limpieza forzada completa para Prensa Rosa cuando hay problemas de estado
function forceCompleteCleanupPrensaRosa() {
    console.log('Ejecutando limpieza forzada completa para Prensa Rosa...');
    
    // Reset completo del DOM body
    document.body.className = '';
    
    // Limpiar todo el localStorage relacionado
    const keys = Object.keys(localStorage);
    keys.forEach(key => {
        if (key.includes('periodico') || key.includes('prensarosa')) {
            localStorage.removeItem(key);
        }
    });
    
    // Limpiar todo el sessionStorage relacionado
    const sessionKeys = Object.keys(sessionStorage);
    sessionKeys.forEach(key => {
        if (key.includes('periodico') || key.includes('prensa-rosa')) {
            sessionStorage.removeItem(key);
        }
    });
    
    // Limpiar todos los estilos dinámicos
    const allDynamicStyles = document.querySelectorAll('style[data-periodico], style[data-prensa-rosa]');
    allDynamicStyles.forEach(style => style.remove());
    
    // Limpiar todos los contenedores dinámicos
    const dynamicContainers = document.querySelectorAll('.floating-hearts-container, .prensa-rosa-effects, .wanted-modal');
    dynamicContainers.forEach(container => container.remove());
    
    // Limpiar TODOS los intervalos globales de forma agresiva
    for (let i = 1; i < 99999; i++) {
        window.clearInterval(i);
        window.clearTimeout(i);
    }
    
    // Limpiar variables globales
    if (window.prensaRosaHeartInterval) {
        clearInterval(window.prensaRosaHeartInterval);
        window.prensaRosaHeartInterval = null;
    }
    if (window.periodicoInterval) {
        clearInterval(window.periodicoInterval);
        window.periodicoInterval = null;
    }
    if (window.prensaRosaFunctions) {
        delete window.prensaRosaFunctions;
    }
    if (window.periodicoFunctions) {
        delete window.periodicoFunctions;
    }
    
    // Resetear todos los elementos afectados
    const allAffectedElements = document.querySelectorAll('*[style]');
    allAffectedElements.forEach(element => {
        // Solo resetear propiedades que podrían haber sido modificadas por los scripts
        element.style.animation = '';
        element.style.transform = '';
        element.style.transition = '';
        element.style.opacity = '';
        element.style.boxShadow = '';
        element.style.background = '';
        element.style.color = '';
    });
    
    // Cerrar cualquier modal que pueda estar abierto
    const modals = document.querySelectorAll('.wanted-modal, .modal');
    modals.forEach(modal => {
        modal.style.display = 'none';
        modal.classList.remove('show');
    });
    
    document.body.style.overflow = '';
    
    console.log('Limpieza completa forzada de Prensa Rosa ejecutada');
}

    // Agregar CSS para animaciones específicas de Prensa Rosa
    const style = document.createElement('style');
    style.setAttribute('data-prensa-rosa', 'true');
    style.textContent = `
        @keyframes floatHeart {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.7;
            }
            25% {
                transform: translateY(-25vh) rotate(90deg);
                opacity: 1;
            }
            50% {
                transform: translateY(-50vh) rotate(180deg);
                opacity: 0.8;
            }
            75% {
                transform: translateY(-75vh) rotate(270deg);
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        @keyframes floatDiamond {
            0% {
                transform: translateY(0) rotate(0deg) scale(1);
                opacity: 0.8;
            }
            25% {
                transform: translateY(-20px) rotate(90deg) scale(1.1);
                opacity: 1;
            }
            50% {
                transform: translateY(-40px) rotate(180deg) scale(0.9);
                opacity: 0.9;
            }
            75% {
                transform: translateY(-20px) rotate(270deg) scale(1.1);
                opacity: 0.7;
            }
            100% {
                transform: translateY(0) rotate(360deg) scale(1);
                opacity: 0;
            }
        }
        
        .couple-spotlight:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(255, 105, 180, 0.3);
        }
        
        .gossip-box:hover {
            border-color: #ff1493;
            background: rgba(255, 182, 193, 0.3);
        }
    `;
    document.head.appendChild(style);
    
    // Agregar cleanup cuando la página se descarga
    window.addEventListener('beforeunload', function() {
        if (window.prensaRosaHeartInterval) {
            clearInterval(window.prensaRosaHeartInterval);
            window.prensaRosaHeartInterval = null;
        }
        // Limpiar estado al salir
        cleanupPrensaRosaState();
    });
});

// Funciones de utilidad específicas para Prensa Rosa (fuera del DOMContentLoaded)
function formatBerries(amount) {
    return new Intl.NumberFormat('es-ES').format(amount) + ' ♥';
}

function createSparkleEffect(element) {
    const sparkle = document.createElement('div');
    sparkle.innerHTML = '✨';
    sparkle.style.cssText = `
        position: absolute;
        pointer-events: none;
        font-size: 20px;
        left: ${Math.random() * element.offsetWidth}px;
        top: ${Math.random() * element.offsetHeight}px;
    `;
    
    element.style.position = 'relative';
    element.appendChild(sparkle);
    
    setTimeout(() => {
        if (sparkle.parentNode) {
            sparkle.parentNode.removeChild(sparkle);
        }
    }, 1000);
}

// Exportar funciones específicas de Prensa Rosa
window.prensaRosaFunctions = {
    formatBerries: formatBerries,
    createSparkleEffect: createSparkleEffect
};
