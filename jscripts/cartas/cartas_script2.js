// One Piece Gaiden - Script Principal del Sistema de Cartas Compatible con MyBB
//<script type="text/javascript">
console.log('Iniciando carga del sistema de cartas...');
	(function() {
    //'use strict';
    
    
    
    // Esperar a que el DOM esté completamente cargado
    function initCardGame() {
        console.log('Inicializando CardGame...');
        
        // Verificar compatibilidad con MyBB
        if (typeof MyBB !== 'undefined') {
            console.log('MyBB detectado - Inicializando modo compatible');
        }
        
        // Verificar que los datos externos estén cargados
        if (typeof window.CardGameData === 'undefined' || typeof window.CardGameConfig === 'undefined') {
            console.error('Error: No se han cargado los datos de cartas. Verifica que cartas.js esté cargado.');
            return;
        }
        
        // Objeto principal del juego
        var CardGame = {
            initialized: false,
            playerData: {
                money: 999999,
                cards: {},
                inventory: {}
            },
            
            // Usar configuración externa
            config: window.CardGameConfig,
            
            // Usar base de datos externa
            packsData: window.CardGameData,
            
            // Función para determinar si una carta es legendaria (especial + 5 estrellas)
            isLegendaryCard: function(card) {
                return card.type === 'especial' && card.rarity === 5;
            },
            
            // Aplicar efecto iridiscente a elementos
            applyIridescentEffect: function(element, card) {
                if (this.isLegendaryCard(card)) {
                    element.classList.add('legendary-5-star');
                    element.style.background = 'linear-gradient(45deg, #ff6b6b, #ffa500, #ffff00, #00ff00, #00bfff, #8a2be2)';
                    element.style.backgroundSize = '300% 300%';
                    element.style.animation = 'rainbow 2s ease infinite';
                }
            },
            
            // Inicialización
            init: function() {
                if (this.initialized) return;
                
                try {
                    this.loadPlayerData();
                    this.initializeUI();
                    this.initialized = true;
                    console.log('Sistema de cartas inicializado correctamente');
                } catch (e) {
                    console.error('Error al inicializar el sistema de cartas:', e);
                }
            },
            
            // Cargar datos del jugador
            loadPlayerData: function() {
                try {
                    var saved = localStorage.getItem('cardGamePlayerData');
                    if (saved) {
                        this.playerData = JSON.parse(saved);
                    }
                } catch (e) {
                    console.warn('No se pudieron cargar los datos guardados:', e);
                }
                this.updateUI();
            },
            
            // Guardar datos
            savePlayerData: function() {
                try {
                    localStorage.setItem('cardGamePlayerData', JSON.stringify(this.playerData));
                } catch (e) {
                    console.error('Error al guardar datos:', e);
                }
            },
            
            // Resetear datos
            resetPlayerData: function() {
                this.playerData = {
                    money: 999999,
                    cards: {},
                    inventory: {}
                };
                this.savePlayerData();
                this.updateUI();
                this.showNotification('¡Datos reiniciados!', 'success');
            },
            
            // Desbloquear todas las cartas
            unlockAllCards: function() {
                var totalUnlocked = 0;
                
                // Recorrer todos los packs y sus cartas
                for (var packId in this.packsData) {
                    var pack = this.packsData[packId];
                    for (var cardId in pack.cards) {
                        // Si la carta no existe o tiene 0, agregar 1
                        if (!this.playerData.cards[cardId]) {
                            this.playerData.cards[cardId] = 1;
                            totalUnlocked++;
                        }
                    }
                }
                
                this.savePlayerData();
                this.updateUI();
                
                // Actualizar la colección si está visible
                var coleccionSection = document.getElementById('coleccion-section');
                if (coleccionSection && coleccionSection.classList.contains('active')) {
                    this.loadCollection();
                }
                
                this.showNotification('¡' + totalUnlocked + ' cartas desbloqueadas!', 'success');
            },
            
            // Inicializar UI
            initializeUI: function() {
                this.updateUI();
                this.loadShop();
                this.loadCollectionFilters();
            },
            
            // Actualizar interfaz
            updateUI: function() {
                var moneyElement = document.getElementById('player-money');
                if (moneyElement) {
                    moneyElement.textContent = this.playerData.money.toLocaleString();
                }
                this.updateCollectionStats();
            },
            
            // Actualizar estadísticas
            updateCollectionStats: function() {
                var collectedElement = document.getElementById('cards-collected');
                var totalElement = document.getElementById('total-cards');
                
                if (collectedElement && totalElement) {
                    var collected = Object.keys(this.playerData.cards).length;
                    var total = this.getTotalCardsCount();
                    
                    collectedElement.textContent = collected;
                    totalElement.textContent = total;
                }
            },
            
            // Total de cartas
            getTotalCardsCount: function() {
                var total = 0;
                for (var packId in this.packsData) {
                    var cards = this.packsData[packId].cards;
                    total += Array.isArray(cards) ? cards.length : Object.keys(cards).length;
                }
                return total;
            },
            
            // Cargar tienda
            loadShop: function() {
                var container = document.getElementById('packs-container');
                if (!container) return;
                
                container.innerHTML = '';
                
                for (var packId in this.packsData) {
                    var pack = this.packsData[packId];
                    var packElement = this.createPackElement(pack);
                    container.appendChild(packElement);
                }
            },
            
            // Crear elemento de pack
            createPackElement: function(pack) {
                var packDiv = document.createElement('div');
                packDiv.className = 'pack-item';
                
                var imageUrl = pack.image || 'https://i.imgur.com/baUibTp.png';
                
                packDiv.innerHTML = 
                    '<div class="pack-image-container">' +
                        '<img class="pack-image" src="' + imageUrl + '" alt="' + pack.name + '" onerror="this.src=&quot;https://i.imgur.com/baUibTp.png&quot;">' +
                    '</div>' +
                    '<div class="pack-name">' + pack.name + '</div>' +
                    '<div class="pack-price">' + pack.price.toLocaleString() + ' Berries</div>' +
                    '<div class="pack-description">' + pack.description + '</div>' +
                    '<button class="buy-pack-btn" data-pack-id="' + pack.id + '">Comprar Sobre</button>';
                
                return packDiv;
            },
            
            // Comprar pack
            buyPack: function(packId) {
                var pack = this.packsData[packId];
                if (!pack) {
                    this.showNotification('Pack no encontrado', 'error');
                    return;
                }
                
                if (this.playerData.money < pack.price) {
                    this.showNotification('No tienes suficientes Berries', 'error');
                    return;
                }
                
                this.playerData.money -= pack.price;
                
                if (!this.playerData.inventory[packId]) {
                    this.playerData.inventory[packId] = 0;
                }
                this.playerData.inventory[packId]++;
                
                this.savePlayerData();
                this.updateUI();
                this.showNotification('¡Sobre de ' + pack.name + ' comprado!', 'success');
                
                var inventarioSection = document.getElementById('inventario-section');
                if (inventarioSection && inventarioSection.classList.contains('active')) {
                    this.loadInventory();
                }
            },
            
            // Cargar inventario
            loadInventory: function() {
                var container = document.getElementById('inventory-grid');
                if (!container) return;
                
                container.innerHTML = '';
                
                var hasItems = false;
                for (var packId in this.playerData.inventory) {
                    if (this.playerData.inventory[packId] > 0) {
                        hasItems = true;
                        var pack = this.packsData[packId];
                        if (pack) {
                            var element = this.createInventoryElement(pack, this.playerData.inventory[packId]);
                            container.appendChild(element);
                        }
                    }
                }
                
                if (!hasItems) {
                    container.innerHTML = '<div class="empty-inventory">No tienes sobres en tu inventario</div>';
                }
            },
            
            // Crear elemento de inventario
            createInventoryElement: function(pack, count) {
                var div = document.createElement('div');
                div.className = 'inventory-pack';
                
                var imageUrl = pack.image || 'https://i.imgur.com/baUibTp.png';
                
                div.innerHTML = 
                    '<div class="inventory-pack-image-container">' +
                        '<img class="inventory-pack-image" src="' + imageUrl + '" alt="' + pack.name + '" onerror="this.src=&quot;https://i.imgur.com/baUibTp.png&quot;">' +
                    '</div>' +
                    '<div class="inventory-pack-name">' + pack.name + '</div>' +
                    '<div class="inventory-pack-count">Cantidad: ' + count + '</div>' +
                    '<button class="open-pack-btn" data-pack-id="' + pack.id + '">Abrir Sobre</button>';
                
                return div;
            },
            
            // Abrir pack
            openPack: function(packId) {
                var pack = this.packsData[packId];
                if (!pack) {
                    this.showNotification('Pack no encontrado', 'error');
                    return;
                }
                
                if (!this.playerData.inventory[packId] || this.playerData.inventory[packId] <= 0) {
                    this.showNotification('No tienes sobres de este tipo', 'error');
                    return;
                }
                
                this.executePackOpening(pack);
            },
            
            // Ejecutar apertura del pack
            executePackOpening: function(pack) {
                // Reducir inventario
                this.playerData.inventory[pack.id]--;
                
                // Obtener cartas
                var drawnCards = this.drawCards(pack);
                
                // Agregar cartas a la colección
                for (var i = 0; i < drawnCards.length; i++) {
                    var card = drawnCards[i];
                    if (!this.playerData.cards[card.id]) {
                        this.playerData.cards[card.id] = 0;
                    }
                    this.playerData.cards[card.id]++;
                }
                
                this.savePlayerData();
                this.updateUI();
                
                // Mostrar modal de apertura
                this.showPackOpeningModal(pack, drawnCards);
                
                // Actualizar inventario si está visible
                var inventarioSection = document.getElementById('inventario-section');
                if (inventarioSection && inventarioSection.classList.contains('active')) {
                    this.loadInventory();
                }
            },
            
            // Mostrar modal de apertura
            showPackOpeningModal: function(pack, drawnCards) {
                var modal = document.getElementById('pack-opening-modal');
                var title = document.getElementById('pack-opening-title');
                var packImage = document.getElementById('pack-opening-image');
                var packAnimationContainer = document.getElementById('pack-animation-container');
                var results = document.getElementById('pack-results');
                var cardsContainer = document.getElementById('cards-revealed');
                var packContainer = document.getElementById('pack-image-container');
                
                if (!modal || !title || !packImage || !results || !cardsContainer) return;
                
                // Limpiar completamente el contenedor del sobre (eliminar tapas anteriores)
                if (packContainer) {
                    var oldFlaps = packContainer.querySelectorAll('.pack-top-flap');
                    oldFlaps.forEach(function(flap) {
                        flap.remove();
                    });
                    packContainer.style.position = '';
                    packContainer.style.overflow = '';
                }
                
                // Resetear completamente el packImage
                packImage._animating = false;
                packImage.style.clipPath = 'none';
                packImage.src = pack.image || 'https://i.imgur.com/baUibTp.png';
                packImage.style.width = '200px';
                packImage.style.height = 'auto';
                packImage.style.cursor = 'pointer';
                packImage.style.transform = 'scale(1)';
                packImage.style.opacity = '1';
                packImage.style.transition = 'transform 0.3s ease, opacity 0.5s ease';
                
                // Resetear el contenedor de animación
                if (packAnimationContainer) {
                    packAnimationContainer.style.cssText = '';
                    packAnimationContainer.style.display = 'block';
                    packAnimationContainer.style.opacity = '1';
                }
                
                // Limpiar resultados
                title.textContent = '🎁 Abriendo ' + pack.name;
                results.style.display = 'none';
                cardsContainer.innerHTML = '';
                
                // Eliminar instrucciones antiguas si existen
                var oldInstructions = results.querySelector('.pack-instructions');
                if (oldInstructions) {
                    oldInstructions.remove();
                }
                
                modal.classList.add('show');
                
                // Configurar animación del pack
                this.setupPackAnimation(pack, drawnCards);
            },
            
            // Configurar animación del pack
            setupPackAnimation: function(pack, drawnCards) {
                var packImage = document.getElementById('pack-opening-image');
                var packContainer = document.getElementById('pack-image-container');
                var self = this;
                
                // Remover listener anterior si existe
                if (packImage._clickHandler) {
                    packImage.removeEventListener('click', packImage._clickHandler);
                }
                if (packContainer && packContainer._clickHandler) {
                    packContainer.removeEventListener('click', packContainer._clickHandler);
                }
                
                // Crear nuevo handler
                var clickHandler = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Click en pack detectado, iniciando animación...');
                    self.animatePackOpening(packImage, drawnCards);
                };
                
                // Guardar referencia y agregar listener
                packImage._clickHandler = clickHandler;
                packImage.addEventListener('click', clickHandler);
                
                // También agregar al contenedor por si acaso
                if (packContainer) {
                    packContainer._clickHandler = clickHandler;
                    packContainer.addEventListener('click', clickHandler);
                }
                
                console.log('Animación de pack configurada, esperando click...');
            },
            
            // Animar apertura del pack
            animatePackOpening: function(packImage, drawnCards) {
                var results = document.getElementById('pack-results');
                var cardsContainer = document.getElementById('cards-revealed');
                var packAnimationContainer = document.getElementById('pack-animation-container');
                var self = this;
                
                // Prevenir múltiples clicks
                if (packImage._animating) {
                    console.log('Animación ya en progreso, ignorando click');
                    return;
                }
                packImage._animating = true;
                
                console.log('Iniciando animación de apertura...');
                
                // Remover listeners para evitar múltiples clicks
                if (packImage._clickHandler) {
                    packImage.removeEventListener('click', packImage._clickHandler);
                }
                
                // Crear la tapa del sobre que se va a "cortar"
                var packContainer = document.getElementById('pack-image-container');
                packContainer.style.position = 'relative';
                packContainer.style.overflow = 'visible';
                
                // Recortar el sobre original para que solo se vea la parte inferior
                packImage.style.clipPath = 'polygon(0 8%, 100% 8%, 100% 100%, 0 100%)';
                packImage.style.transition = 'none';
                
                // Crear la tapa (parte superior del sobre)
                var packTop = document.createElement('div');
                packTop.className = 'pack-top-flap';
                packTop.style.cssText = 'position: absolute; top: 0; left: 0; width: 100%; height: 100%; overflow: hidden; z-index: 10; transform-origin: top center;';
                
                var topImg = document.createElement('img');
                topImg.src = packImage.src;
                topImg.style.cssText = 'width: 100%; height: 100%; object-fit: cover; clip-path: polygon(0 0, 100% 0, 100% 8%, 0 8%);';
                packTop.appendChild(topImg);
                packContainer.appendChild(packTop);
                
                // Animar el corte del sobre
                setTimeout(function() {
                    // La tapa se levanta
                    packTop.style.transition = 'transform 0.7s cubic-bezier(0.68, -0.55, 0.265, 1.55), opacity 0.7s ease';
                    packTop.style.transform = 'translateY(-120px) rotateX(-60deg) scale(0.95)';
                    packTop.style.opacity = '0';
                    
                    // El sobre tiembla
                    packImage.style.transition = 'transform 0.15s ease';
                    var shakeCount = 0;
                    var shakeInterval = setInterval(function() {
                        var baseTransform = 'translateX(' + (shakeCount % 2 === 0 ? '-5px' : '5px') + ') rotate(' + (shakeCount % 2 === 0 ? '-2deg' : '2deg') + ')';
                        packImage.style.transform = baseTransform;
                        shakeCount++;
                        if (shakeCount >= 6) {
                            clearInterval(shakeInterval);
                            packImage.style.transform = 'translateX(0) rotate(0)';
                        }
                    }, 100);
                }, 200);
                
                // Las cartas salen del sobre
                setTimeout(function() {
                    if (packAnimationContainer) {
                        packAnimationContainer.style.transition = 'opacity 0.5s ease';
                        packAnimationContainer.style.opacity = '0';
                    }
                    
                    setTimeout(function() {
                        if (packAnimationContainer) packAnimationContainer.style.display = 'none';
                        results.style.display = 'block';
                        cardsContainer.innerHTML = '';
                        
                        // Añadir instrucciones
                        var instructions = document.createElement('p');
                        instructions.className = 'pack-instructions';
                        instructions.style.cssText = 'text-align: center; margin: 20px 0 30px; font-size: 1.2em; color: #666; font-weight: bold; animation: pulse 2s infinite;';
                        instructions.innerHTML = '✨ ¡Haz clic en las cartas para descubrirlas! ✨';
                        // Solo agregar instrucciones si no existen ya
                        if (!results.querySelector('.pack-instructions')) {
                            results.insertBefore(instructions, cardsContainer);
                        }
                        
                        // Mostrar cartas boca abajo
                        var cardBackImage = self.config.cardBackImage || 'https://i.imgur.com/baUibTp.png';
                        
                        drawnCards.forEach(function(card, index) {
                            setTimeout(function() {
                                var cardElement = document.createElement('div');
                                cardElement.className = 'revealed-card-container';
                                cardElement.style.animation = 'cardFlyOut 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards';
                                cardElement.style.animationDelay = (index * 0.1) + 's';
                                cardElement.style.opacity = '0';
                                
                                // Estructura para flip manual
                                var flipWrapper = document.createElement('div');
                                flipWrapper.className = 'card-flip-wrapper';
                                flipWrapper.dataset.flipped = 'false';
                                flipWrapper.style.cursor = 'pointer';
                                
                                var flipInner = document.createElement('div');
                                flipInner.className = 'card-flip-inner';
                                
                                // Cara trasera (reverso - se ve primero)
                                var cardBack = document.createElement('div');
                                cardBack.className = 'card-back-side';
                                cardBack.innerHTML = '<img src="' + cardBackImage + '" alt="Reverso">';
                                
                                // Cara frontal (carta real)
                                var cardFront = document.createElement('div');
                                cardFront.className = 'card-front-side';
                                cardFront.innerHTML = '<img src="' + card.image + '" alt="' + card.name + '" onerror="this.src=\'https://i.imgur.com/baUibTp.png\'">' +
                                    '<div class="revealed-card-rarity">' + card.rarity + '</div>';
                                
                                flipInner.appendChild(cardBack);
                                flipInner.appendChild(cardFront);
                                flipWrapper.appendChild(flipInner);
                                cardElement.appendChild(flipWrapper);
                                
                                // Aplicar efectos especiales según rareza
                                if (card.rarity === 4) {
                                    cardElement.classList.add('rare-4-star');
                                }
                                
                                if (card.type === 'especial' && card.rarity === 5) {
                                    cardElement.classList.add('legendary-5-star');
                                }
                                
                                // Click para voltear la carta manualmente
                                flipWrapper.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    if (this.dataset.flipped === 'false') {
                                        this.classList.add('flipped');
                                        this.dataset.flipped = 'true';
                                        
                                        // Efecto sonoro/visual para cartas especiales
                                        if (card.rarity === 5 && card.type === 'especial') {
                                            self.showNotification('🌟 ¡CARTA LEGENDARIA! ' + card.name + ' 🌟', 'success');
                                        } else if (card.rarity === 4) {
                                            self.showNotification('⭐ ¡Carta Épica! ' + card.name, 'success');
                                        }
                                        
                                        // Después de voltear, permitir ver detalles al hacer clic
                                        var wrapper = this;
                                        setTimeout(function() {
                                            wrapper.style.cursor = 'pointer';
                                            wrapper.title = 'Clic para ver detalles';
                                        }, 600);
                                    } else {
                                        // Si ya está volteada, mostrar detalles
                                        window.CardGame.showCardDetail(card.id);
                                    }
                                });
                                
                                cardsContainer.appendChild(cardElement);
                                
                                // Hacer visible la carta
                                setTimeout(function() {
                                    cardElement.style.opacity = '1';
                                }, 50);
                                
                            }, index * 120);
                        });
                        
                    }, 400);
                }, 1000);
            },
            
            // Obtener cartas del pack
            drawCards: function(pack) {
                var drawnCards = [];
                var cards = pack.cards;
                
                for (var i = 0; i < this.config.cardsPerPack; i++) {
                    var card = this.drawCardByRarity(cards);
                    drawnCards.push(card);
                }
                
                return drawnCards;
            },
            
            // Obtener carta por rareza
            drawCardByRarity: function(cards) {
                var weights = this.config.rarityWeights;
                var totalWeight = 0;
                
                // Convertir objeto a array si es necesario
                var cardsArray = Array.isArray(cards) ? cards : Object.values(cards);
                
                for (var rarity in weights) {
                    totalWeight += weights[rarity];
                }
                
                var random = Math.random() * totalWeight;
                var currentWeight = 0;
                var selectedRarity = 1;
                
                for (var rarity in weights) {
                    currentWeight += weights[rarity];
                    if (random <= currentWeight) {
                        selectedRarity = parseInt(rarity);
                        break;
                    }
                }
                
                var cardsOfRarity = cardsArray.filter(function(card) {
                    return card.rarity === selectedRarity;
                });
                
                if (cardsOfRarity.length === 0) {
                    cardsOfRarity = cardsArray;
                }
                
                var randomIndex = Math.floor(Math.random() * cardsOfRarity.length);
                return cardsOfRarity[randomIndex];
            },
            
            // Cargar colección
            loadCollection: function() {
                var container = document.getElementById('collection-grid');
                if (!container) return;
                
                var allCards = this.getAllCards();
                var filteredCards = this.applyFilters(allCards);
                
                container.innerHTML = '';
                
                for (var i = 0; i < filteredCards.length; i++) {
                    var cardElement = this.createCollectionCardElement(filteredCards[i]);
                    container.appendChild(cardElement);
                }
            },
            
            // Obtener todas las cartas
            getAllCards: function() {
                var allCards = [];
                for (var packId in this.packsData) {
                    var pack = this.packsData[packId];
                    var cardsArray = Array.isArray(pack.cards) ? pack.cards : Object.values(pack.cards);
                    for (var i = 0; i < cardsArray.length; i++) {
                        var card = cardsArray[i];
                        card.packName = pack.name;
                        card.pack = packId;
                        allCards.push(card);
                    }
                }
                return allCards;
            },
            
            // Aplicar filtros
            applyFilters: function(cards) {
                var packFilter = document.getElementById('pack-filter').value;
                var rarityFilter = document.getElementById('rarity-filter').value;
                var typeFilter = document.getElementById('type-filter').value;
                var ownedFilter = document.getElementById('owned-filter').value;
                
                return cards.filter(function(card) {
                    var passesPackFilter = !packFilter || card.pack === packFilter;
                    var passesRarityFilter = !rarityFilter || card.rarity.toString() === rarityFilter;
                    var passesTypeFilter = !typeFilter || card.type === typeFilter;
                    
                    var isOwned = window.CardGame.playerData.cards[card.id] > 0;
                    var passesOwnedFilter = !ownedFilter || 
                        (ownedFilter === 'owned' && isOwned) || 
                        (ownedFilter === 'not-owned' && !isOwned);
                    
                    return passesPackFilter && passesRarityFilter && passesTypeFilter && passesOwnedFilter;
                });
            },
            
            // Crear elemento de carta de colección
            createCollectionCardElement: function(card) {
                var cardDiv = document.createElement('div');
                cardDiv.className = 'card-item';
                
                var isOwned = this.playerData.cards[card.id] > 0;
                var count = this.playerData.cards[card.id] || 0;
                var cardBackImage = this.config.cardBackImage || 'https://i.imgur.com/baUibTp.png';
                
                // Usar imagen del reverso si no está obtenida
                var displayImage = isOwned ? card.image : cardBackImage;
                var displayName = isOwned ? card.name : '???';
                var displayRarity = isOwned ? card.rarity : '?';
                
                if (!isOwned) {
                    cardDiv.classList.add('not-owned');
                }
                
                // Agregar clases según rareza
                if (isOwned && card.rarity === 4) {
                    cardDiv.classList.add('rare-4-star');
                }
                
                if (isOwned && card.type === 'especial' && card.rarity === 5) {
                    cardDiv.classList.add('legendary-5-star');
                }
                
                cardDiv.innerHTML = 
                    '<img class="card-image" src="' + displayImage + '" alt="' + displayName + '" onerror="this.src=&quot;https://i.imgur.com/baUibTp.png&quot;">' +
                    '<div class="card-name">' + displayName + '</div>' +
                    '<div class="card-rarity">' + displayRarity + '</div>' +
                    (isOwned ? '<div class="card-count">x' + count + '</div>' : '');
                
                var self = this;
                cardDiv.onclick = function() {
                    // Solo mostrar detalle si está obtenida
                    if (isOwned) {
                        self.showCardDetail(card.id);
                    } else {
                        self.showNotification('¡Aún no tienes esta carta!', 'warning');
                    }
                };
                
                return cardDiv;
            },
            
            // Cargar filtros de colección
            loadCollectionFilters: function() {
                var packFilter = document.getElementById('pack-filter');
                if (!packFilter) return;
                
                packFilter.innerHTML = '<option value="">Todos los Packs</option>';
                
                for (var packId in this.packsData) {
                    var pack = this.packsData[packId];
                    var option = document.createElement('option');
                    option.value = packId;
                    option.textContent = pack.name;
                    packFilter.appendChild(option);
                }
            },
            
            // Filtrar colección
            filterCollection: function() {
                this.loadCollection();
            },
            
            // Mostrar detalle de carta
            showCardDetail: function(cardId) {
                var card = this.findCardById(cardId);
                if (!card) return;
                
                var modal = document.getElementById('card-detail-modal');
                var image = document.getElementById('card-detail-image');
                var flipContainer = document.getElementById('card-detail-image').closest('.card-flip-container');
                var rarityVisual = document.getElementById('card-detail-rarity-visual');
                var name = document.getElementById('card-detail-name');
                var pack = document.getElementById('card-detail-pack');
                var rarity = document.getElementById('card-detail-rarity');
                var type = document.getElementById('card-detail-type');
                var description = document.getElementById('card-detail-description');
                var count = document.getElementById('card-detail-count');
                
                if (!modal || !image || !name || !pack || !rarity || !type || !description || !count) return;
                
                // Limpiar clases anteriores
                if (flipContainer) {
                    flipContainer.classList.remove('legendary-card');
                    flipContainer.classList.remove('rare-4-star-card');
                }
                
                image.src = card.image || 'https://i.imgur.com/baUibTp.png';
                name.textContent = card.name;
                pack.textContent = card.packName || 'Desconocido';
                rarity.innerHTML = '<span>⭐</span> ' + card.rarity;
                type.textContent = card.type;
                description.textContent = card.description || 'Sin descripción disponible';
                count.textContent = this.playerData.cards[cardId] || 0;
                
                // Actualizar rareza visual en el card-front
                if (rarityVisual) {
                    rarityVisual.textContent = card.rarity;
                }
                
                // Aplicar efectos especiales según rareza
                if (flipContainer) {
                    if (card.rarity === 4) {
                        flipContainer.classList.add('rare-4-star-card');
                    }
                    
                    if (card.type === 'especial' && card.rarity === 5) {
                        flipContainer.classList.add('legendary-card');
                    }
                }
                
                modal.classList.add('show');
            },
            
            // Encontrar carta por ID
            findCardById: function(cardId) {
                for (var packId in this.packsData) {
                    var pack = this.packsData[packId];
                    var cardsArray = Array.isArray(pack.cards) ? pack.cards : Object.values(pack.cards);
                    for (var i = 0; i < cardsArray.length; i++) {
                        if (cardsArray[i].id === cardId || cardsArray[i].id === String(cardId)) {
                            var card = cardsArray[i];
                            card.packName = pack.name;
                            card.pack = packId;
                            return card;
                        }
                    }
                }
                return null;
            },
            
            // Mostrar sección
            showSection: function(sectionName) {
                // Ocultar todas las secciones
                var sections = document.querySelectorAll('.game-section');
                for (var i = 0; i < sections.length; i++) {
                    sections[i].classList.remove('active');
                }
                
                // Remover clase active de todos los botones
                var buttons = document.querySelectorAll('.nav-btn');
                for (var i = 0; i < buttons.length; i++) {
                    buttons[i].classList.remove('active');
                }
                
                // Mostrar sección seleccionada
                var targetSection = document.getElementById(sectionName + '-section');
                if (targetSection) {
                    targetSection.classList.add('active');
                    
                    // Cargar contenido específico
                    if (sectionName === 'tienda') {
                        this.loadShop();
                    } else if (sectionName === 'inventario') {
                        this.loadInventory();
                    } else if (sectionName === 'coleccion') {
                        this.loadCollection();
                    }
                }
                
                // Activar botón correspondiente
                var buttons = document.querySelectorAll('.nav-btn');
                for (var i = 0; i < buttons.length; i++) {
                    if (buttons[i].textContent.includes(sectionName === 'tienda' ? 'Tienda' : 
                                                        sectionName === 'inventario' ? 'Inventario' : 
                                                        sectionName === 'coleccion' ? 'Colección' : '')) {
                        buttons[i].classList.add('active');
                        break;
                    }
                }
            },
            
            // Cerrar modal
            closeModal: function(modalId) {
                // Si se especifica un modal específico, cerrar solo ese
                if (modalId) {
                    var specificModal = document.getElementById(modalId);
                    if (specificModal) {
                        specificModal.classList.remove('show');
                    }
                    return;
                }
                
                // Si no se especifica, cerrar todos los modales
                var modals = document.querySelectorAll('.modal');
                for (var i = 0; i < modals.length; i++) {
                    modals[i].classList.remove('show');
                }
                
                // Resetear modal de apertura
                var packAnimationContainer = document.getElementById('pack-animation-container');
                var packResults = document.getElementById('pack-results');
                var packImage = document.getElementById('pack-opening-image');
                var packContainer = document.getElementById('pack-image-container');
                
                if (packAnimationContainer && packResults && packImage) {
                    packAnimationContainer.style.display = 'block';
                    packResults.style.display = 'none';
                    packImage.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                    packImage.style.transform = 'scale(1)';
                    packImage.style.opacity = '1';
                    packImage._animating = false;
                    
                    // Limpiar listeners
                    if (packImage._clickHandler) {
                        packImage.removeEventListener('click', packImage._clickHandler);
                        packImage._clickHandler = null;
                    }
                    if (packContainer && packContainer._clickHandler) {
                        packContainer.removeEventListener('click', packContainer._clickHandler);
                        packContainer._clickHandler = null;
                    }
                }
            },
            
            // Mostrar notificación
            showNotification: function(message, type) {
                var container = document.getElementById('notification-container');
                if (!container) return;
                
                var notification = document.createElement('div');
                notification.className = 'notification ' + type;
                notification.textContent = message;
                
                container.appendChild(notification);
                
                setTimeout(function() {
                    notification.classList.add('show');
                }, 100);
                
                setTimeout(function() {
                    notification.classList.remove('show');
                    setTimeout(function() {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }, 3000);
            }
        };
        
        // Event listeners
        function addEventListeners() {
            // Comprar sobres
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('buy-pack-btn')) {
                    var packId = e.target.getAttribute('data-pack-id');
                    CardGame.buyPack(packId);
                }
            });
            
            // Abrir sobres
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('open-pack-btn')) {
                    var packId = e.target.getAttribute('data-pack-id');
                    CardGame.openPack(packId);
                }
            });
            
            // Cerrar modales
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal')) {
                    // Cerrar solo el modal específico que fue clicado
                    var modalId = e.target.id;
                    CardGame.closeModal(modalId);
                }
            });
        }
        
        // Hacer CardGame global
        window.CardGame = CardGame;
        
        // Funciones de utilidad globales
        window.showSection = function(sectionName) {
            CardGame.showSection(sectionName);
        };
        
        window.closeModal = function(modalId) {
            CardGame.closeModal(modalId);
        };
        
        window.resetPlayerData = function() {
            if (confirm('¿Estás seguro de que quieres reiniciar todos tus datos?')) {
                CardGame.resetPlayerData();
            }
        };
        
        window.unlockAllCards = function() {
            if (confirm('¿Desbloquear todas las cartas de la colección?')) {
                CardGame.unlockAllCards();
            }
        };
        
        window.filterCollection = function() {
            CardGame.filterCollection();
        };
        
        // Inicializar
        addEventListeners();
        
        setTimeout(function() {
            CardGame.init();
        }, 200);
    }
    
    // Detectar cuando el DOM esté listo - Compatible con MyBB
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCardGame);
    } else {
        // Si el DOM ya está listo, inicializar inmediatamente
        initCardGame();
    }

    // Definición de emergencia de funciones críticas - SIEMPRE disponibles
    window.showSection = window.showSection || function(sectionName) {
        console.log('Función showSection de emergencia llamada:', sectionName);
        
        // Ocultar todas las secciones
        var sections = document.querySelectorAll('.game-section');
        sections.forEach(function(section) {
            section.classList.remove('active');
        });
        
        // Mostrar la sección solicitada
        var targetSection = document.getElementById(sectionName + '-section');
        if (targetSection) {
            targetSection.classList.add('active');
        }
        
        // Actualizar botones de navegación
        var navButtons = document.querySelectorAll('.nav-btn');
        navButtons.forEach(function(btn) {
            btn.classList.remove('active');
        });
        
        // Activar el botón correspondiente
        var activeButton = Array.from(navButtons).find(function(btn) {
            return btn.textContent.includes(
                sectionName === 'tienda' ? 'Tienda' :
                sectionName === 'inventario' ? 'Inventario' :
                sectionName === 'coleccion' ? 'Colección' : ''
            );
        });
        
        if (activeButton) {
            activeButton.classList.add('active');
        }
    };

    window.resetPlayerData = window.resetPlayerData || function() {
        console.log('Función resetPlayerData de emergencia llamada');
        if (confirm('¿Estás seguro de que quieres reiniciar todos tus datos?')) {
            localStorage.removeItem('cardGamePlayerData');
            location.reload();
        }
    };

    window.unlockAllCards = window.unlockAllCards || function() {
        console.log('Función unlockAllCards de emergencia llamada');
        if (confirm('¿Desbloquear todas las cartas de la colección?')) {
            if (window.CardGame && window.CardGame.unlockAllCards) {
                window.CardGame.unlockAllCards();
            } else {
                alert('El sistema de cartas no está disponible');
            }
        }
    };

    window.closeModal = window.closeModal || function(modalId) {
        console.log('Función closeModal de emergencia llamada');
        if (modalId) {
            var modal = document.getElementById(modalId);
            if (modal) modal.style.display = 'none';
        } else {
            var modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                modal.style.display = 'none';
            });
        }
    };

    // Verificación final
    console.log('Funciones globales definidas:', {
        showSection: typeof window.showSection,
        resetPlayerData: typeof window.resetPlayerData,
        unlockAllCards: typeof window.unlockAllCards,
        closeModal: typeof window.closeModal
    });

})();
//</script>
