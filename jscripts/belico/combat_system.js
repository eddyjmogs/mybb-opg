/**
 * Sistema de Combate por Turnos - One Piece Gaiden
 * Versión 1.0 - Base funcional
 */

var CombatSystem = {
    // Estado del sistema
    isOpen: false,
    currentTid: null,
    currentUid: null,
    combatData: null,
    myTechniques: [],
    myPasivas: [],
    myWeapons: [],
    selectedWeapons: [null, null, null], // Hasta 3 armas simultáneas
    selectedTAStat: null, // Stat elegido para TA cuando hay opciones
    selectedCharacter: null, // Personaje o NPC actualmente seleccionado (null = personaje principal)
    cooldowns: {},
    resourcesUsed: { energia: 0, haki: 0 },
    currentResources: null, // Recursos actuales leídos del último post {energia, haki, vida}
    selectedTargets: [],
    pendingActions: [],
    // Modificadores aplicados (stats o recursos temporales)
    modifiers: [],
    
    // Estado de técnicas mantenidas
    activeMaintained: [], // Técnicas mantenidas activas [{tid, nombre, turnosActivos, energiaTurno, hakiTurno}]
    maintainedThisTurn: [], // Mantenidas que se continúan este turno
    releasedMaintained: [], // Mantenidas que se sueltan (inician cooldown)
    
    // Estado del modo reacción
    reactionMode: false,
    reactionData: {
        attackerTA: 0,
        attackerDamage: 0,
        attackerName: '',
        reactionResult: 0,
        reactionType: null,
        reactionOptions: [],
        selectedReaction: null,
        selectedTechnique: null,
        finalDamage: 0,
        damageReceived: 0, // Daño final a descontar de la vida
        reflejosModifier: 0, // Modificador de reflejos
        reflejosModifierReason: '' // Motivo del modificador
    },
    
    // Configuración
    config: {
        apiUrl: '/op/api_combate.php',
        maxTargets: 5
    },
    
    // Ataques detectados en el tema (para auto-completar reacción)
    detectedAttacks: [],
    
    // Tabla de reacciones según resultado (Reflejos - TA)
    reactionTable: [
        { min: 30, max: 999, type: 'esquiva_absoluta', name: 'Esquiva Absoluta', effect: 'Esquivas el 100% del daño', percent: 100, category: 'esquiva' },
        { min: 21, max: 29, type: 'esquiva_efectiva', name: 'Esquiva Efectiva', effect: 'Esquivas el 75% del daño', percent: 75, category: 'esquiva' },
        { min: 11, max: 20, type: 'esquiva_torpe', name: 'Esquiva Torpe', effect: 'Esquivas el 50% del daño', percent: 50, category: 'esquiva' },
        { min: 0, max: 10, type: 'choque_absoluto', name: 'Choque Absoluto', effect: 'Aplicas el 100% del daño de tu técnica ofensiva', percent: 100, category: 'choque' },
        { min: -15, max: -1, type: 'choque_efectivo', name: 'Choque Efectivo', effect: 'Aplicas el 75% del daño de tu técnica ofensiva', percent: 75, category: 'choque' },
        { min: -30, max: -16, type: 'choque_torpe', name: 'Choque Torpe', effect: 'Aplicas el 50% del daño de tu técnica ofensiva', percent: 50, category: 'choque' },
        { min: -45, max: -31, type: 'bloqueo_absoluto', name: 'Bloqueo Absoluto', effect: 'Mitigas el 100% con técnica defensiva', percent: 100, category: 'bloqueo' },
        { min: -60, max: -46, type: 'bloqueo_efectivo', name: 'Bloqueo Efectivo', effect: 'Mitigas el 75% con técnica defensiva', percent: 75, category: 'bloqueo' },
        { min: -75, max: -61, type: 'bloqueo_torpe', name: 'Bloqueo Torpe', effect: 'Mitigas el 50% con técnica defensiva', percent: 50, category: 'bloqueo' },
        { min: -999, max: -76, type: 'sin_reaccion', name: 'Sin Reacción', effect: 'Recibes el daño y efectos completos', percent: 0, category: 'none' }
    ],

    /**
     * Inicializa el sistema de combate
     */
    init: function() {
        this.createModal();
        this.bindEvents();
        console.log('[CombatSystem] Sistema inicializado');
    },

    /**
     * Crea el modal principal de combate
     */
    createModal: function() {
        if (document.getElementById('combat-system-modal')) return;

        var modalHTML = `
        <div id="combat-system-modal" class="combat-modal" style="display:none;">
            <div class="combat-modal-content">
                <div class="combat-header">
                    <h2>⚔️ Sistema de Combate</h2>
                    <span class="combat-close" onclick="CombatSystem.close()">&times;</span>
                </div>
                
                <div class="combat-body">
                    <!-- Panel izquierdo: Mi personaje -->
                    <div class="combat-panel combat-panel-left">
                        <h3>📊 Mi Personaje</h3>
                        
                        <!-- Selector de personaje/NPC -->
                        <div class="combat-section" id="combat-character-selector-container" style="display:none;">
                            <label>👤 Controlas:</label>
                            <select id="combat-character-selector" onchange="CombatSystem.switchCharacter(this.value)">
                                <option value="main">Mi Personaje</option>
                            </select>
                        </div>
                        
                        <div id="combat-my-character">
                            <div class="combat-loading">Cargando...</div>
                        </div>
                        
                        <h3>📋 Recursos Actuales</h3>
                        <div id="combat-resources">
                            <div class="resource-bar resource-vida">
                                <span class="resource-label">Vida</span>
                                <span id="combat-vida-current">0</span> / <span id="combat-vida-max">0</span>
                            </div>
                            <div class="resource-bar resource-energia">
                                <span class="resource-label">Energía</span>
                                <span id="combat-energia-current">0</span> / <span id="combat-energia-max">0</span>
                            </div>
                            <div class="resource-bar resource-haki">
                                <span class="resource-label">Haki</span>
                                <span id="combat-haki-current">0</span> / <span id="combat-haki-max">0</span>
                            </div>
                        </div>
                        
                        <h3>🔄 Turno Actual: <span id="combat-current-turn">0</span></h3>

                        <div class="combat-section">
                            <h4>🔧 Modificadores</h4>
                            <div id="combat-modifier-form">
                                <label>Objetivo:</label>
                                <select id="modifier-target">
                                    <option value="fuerza">Fuerza</option>
                                    <option value="resistencia">Resistencia</option>
                                    <option value="destreza">Destreza</option>
                                    <option value="punteria">Puntería</option>
                                    <option value="agilidad">Agilidad</option>
                                    <option value="reflejos">Reflejos</option>
                                    <option value="voluntad">Voluntad</option>
                                    <option value="vida">Vida (recurso)</option>
                                    <option value="energia">Energía (recurso)</option>
                                    <option value="haki">Haki (recurso)</option>
                                </select>
                                <label>Valor (+ / -):</label>
                                <input type="number" id="modifier-value" value="0" step="1">
                                <label>Aplicar a:</label>
                                <select id="modifier-scope">
                                    <option value="current">Actual</option>
                                    <option value="max">Máximo</option>
                                </select>
                                <label>Turnos (0 = permanente):</label>
                                <input type="number" id="modifier-turns" value="0" min="0" step="1">
                                <small style="display:block;color:#ccc;font-size:11px;margin-top:4px;">Se decrementa 1 turno al generar código (fin de turno)</small>
                                <label>Mensaje:</label>
                                <input type="text" id="modifier-message" placeholder="Motivo del modificador">
                                <button onclick="CombatSystem.addModifier()" class="btn-add-modifier">Añadir Modificador</button>
                            </div>
                            <div id="combat-modifiers-list" style="margin-top:8px;">
                                <em>Sin modificadores activos</em>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Panel central: Acciones -->
                    <div class="combat-panel combat-panel-center">
                        <h3>⚔️ Acciones de Combate</h3>
                        
                        <!-- Selector de armas (hasta 3) -->
                        <div class="combat-section">
                            <label>🗡️ Armas Equipadas:</label>
                            <div id="combat-weapon-select">
                                <!-- Los selectores de armas se generarán dinámicamente -->
                            </div>
                        </div>
                        
                        <!-- Selector de técnicas -->
                        <div class="combat-section">
                            <label>Técnica:</label>
                            <select id="combat-technique-select" onchange="CombatSystem.selectTechnique(this.value)">
                                <option value="">-- Seleccionar técnica --</option>
                            </select>
                            <div id="combat-technique-info" class="technique-info" style="display:none;"></div>
                        </div>
                        
                        <!-- Filtros de técnicas -->
                        <div class="combat-filters">
                            <button onclick="CombatSystem.filterTechniques('all')" class="filter-btn active">Todas</button>
                            <button onclick="CombatSystem.filterTechniques('ofensiva')" class="filter-btn">Ofensivas</button>
                            <button onclick="CombatSystem.filterTechniques('defensiva')" class="filter-btn">Defensivas</button>
                            <button onclick="CombatSystem.filterTechniques('utilidad')" class="filter-btn">Utilidad</button>
                            <button onclick="CombatSystem.filterTechniques('mantenida')" class="filter-btn" style="border-color: #ff9800; color: #ff9800;">🔄 Mantenidas</button>
                        </div>
                        
                        <!-- Técnicas en cooldown -->
                        <div class="combat-section">
                            <h4>🕐 Técnicas en Enfriamiento</h4>
                            <div id="combat-cooldowns">
                                <em>Sin técnicas en enfriamiento</em>
                            </div>
                        </div>
                        
                        <!-- Técnicas mantenidas activas -->
                        <div class="combat-section maintained-section">
                            <h4>🔄 Técnicas Mantenidas Activas</h4>
                            <div id="combat-maintained-techniques">
                                <em>Sin técnicas mantenidas</em>
                            </div>
                            <div id="maintained-costs-summary" style="display: none; margin-top: 10px; padding: 2px; background: rgba(255,152,0,0.15); border-radius: 4px;">
                                <strong>💫 Coste de mantener este turno:</strong>
                                <div id="maintained-costs-detail"></div>
                            </div>
                        </div>
                        
                        <!-- Acciones pendientes -->
                        <div class="combat-section">
                            <h4>📝 Acciones a Realizar</h4>
                            <div id="combat-pending-actions">
                                <em>Sin acciones pendientes</em>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="combat-actions-buttons">
                            <button onclick="CombatSystem.addAction()" class="btn-add-action">➕ Añadir Acción</button>
                            <button onclick="CombatSystem.addBasicAttack()" class="btn-basic-attack">⚔️ Golpe Básico</button>
                            <button onclick="CombatSystem.addBasicBlock()" class="btn-basic-block">🛡️ Bloqueo Básico</button>
                            <button onclick="CombatSystem.clearActions()" class="btn-clear">🗑️ Limpiar</button>
                        </div>
                    </div>
                    
                    <!-- Panel derecho: Objetivos -->
                    <div class="combat-panel combat-panel-right">
                        <h3>🎯 Objetivos Detectados</h3>
                        <div id="combat-targets">
                            <div class="combat-loading">Cargando participantes...</div>
                        </div>
                        
                        <h3>➕ Añadir Objetivo Manual</h3>
                        <div class="add-target-form">
                            <input type="number" id="combat-manual-uid" placeholder="UID del personaje">
                            <button onclick="CombatSystem.addManualTarget()">Añadir</button>
                        </div>
                        
                        <h3>🎭 NPCs Detectados</h3>
                        <div id="combat-npcs">
                            <em>Sin NPCs detectados</em>
                        </div>
                        
                        <h3>📜 Historial de Combate</h3>
                        <div id="combat-history">
                            <em>Sin acciones previas</em>
                        </div>
                    </div>
                </div>
                
                <div class="combat-footer">
                    <span id="combat-status">Listo</span>
                    <button onclick="CombatSystem.generateCode()" class="btn-generate">📄 Generar Código</button>
                    <button onclick="CombatSystem.openReactionMode()" class="btn-reaction">🛡️ Reaccionar a Ataque</button>
                    <button onclick="CombatSystem.refresh()" class="btn-refresh">🔄 Actualizar</button>
                </div>
            </div>
        </div>
        
        <!-- Modal de Reacción Defensiva -->
        <div id="combat-reaction-modal" class="combat-modal" style="display:none;">
            <div class="combat-modal-content reaction-modal-content">
                <div class="combat-header reaction-header">
                    <h2>🛡️ Reacción Defensiva</h2>
                    <span class="combat-close" onclick="CombatSystem.closeReactionMode()">&times;</span>
                </div>
                
                <div class="reaction-body">
                    <!-- Paso 1: Datos del ataque -->
                    <div id="reaction-step-1" class="reaction-step">
                        <h3>📊 Paso 1: Seleccionar Ataque a Reaccionar</h3>
                        
                        <!-- Ataques detectados automáticamente -->
                        <div id="detected-attacks-section" class="detected-attacks-section" style="display:none;">
                            <h4>⚔️ Ataques Detectados en el Tema</h4>
                            <div id="detected-attacks-list" class="detected-attacks-list"></div>
                            <hr style="border-color: rgba(255,255,255,0.2); margin: 15px 0;">
                            <p style="text-align: center; color: #888; font-size: 12px;">O introduce los datos manualmente:</p>
                        </div>
                        
                        <div class="reaction-form">
                            <div class="reaction-input-group">
                                <label>👤 Nombre del Atacante:</label>
                                <input type="text" id="reaction-attacker-name" placeholder="Nombre del atacante">
                            </div>
                            <div class="reaction-input-group">
                                <label>🎯 TA del Atacante (Tasa de Acierto):</label>
                                <input type="number" id="reaction-attacker-ta" placeholder="Ej: 45" min="0" max="200">
                                <small>La TA viene indicada en el post del atacante</small>
                            </div>
                            <div class="reaction-input-group">
                                <label>💥 Daño del Ataque:</label>
                                <input type="number" id="reaction-attacker-damage" placeholder="Ej: 150" min="0">
                                <small>El daño total del ataque enemigo</small>
                            </div>
                            <div class="reaction-my-stats">
                                <h4>Tus Estadísticas</h4>
                                <div id="reaction-my-reflejos">
                                    <span class="stat-label">Reflejos:</span>
                                    <span class="stat-value" id="reaction-reflejos-value">0</span>
                                </div>
                            </div>
                            <button onclick="CombatSystem.calculateReaction()" class="btn-calculate">🔮 Calcular Reacción</button>
                        </div>
                    </div>
                    
                    <!-- Paso 2: Resultado y opciones -->
                    <div id="reaction-step-2" class="reaction-step" style="display:none;">
                        <h3>⚖️ Resultado del Cálculo</h3>
                        
                        <!-- Resumen del ataque seleccionado -->
                        <div id="reaction-attack-summary" class="reaction-attack-summary">
                            <div class="attack-summary-header">
                                <span>⚔️ Ataque de: <strong id="summary-attacker-name">-</strong></span>
                                <button onclick="CombatSystem.goToReactionStep(1)" class="btn-change-attack">🔄 Cambiar ataque</button>
                            </div>
                            <div class="attack-summary-stats">
                                <span>🎯 TA: <strong id="summary-ta">0</strong></span>
                                <span>💥 Daño: <strong id="summary-damage">0</strong></span>
                                <span>🛡️ Tus Reflejos: <strong id="summary-reflejos">0</strong></span>
                            </div>
                            <div style="margin-top: 8px; text-align: center;">
                                <button onclick="CombatSystem.showReflejosModifierDialog()" class="btn-reflejos-modifier">⚡ Modificador de Reflejos</button>
                            </div>
                            <div id="reflejos-modifier-info" style="display:none; margin-top: 8px; padding: 6px; background: rgba(255,193,7,0.15); border-radius: 4px; font-size: 12px;">
                                <strong>Modificador:</strong> <span id="reflejos-modifier-value">0</span><br>
                                <strong>Motivo:</strong> <span id="reflejos-modifier-reason">-</span>
                            </div>
                        </div>
                        
                        <div class="reaction-result-box">
                            <div class="reaction-formula">
                                <span>Reflejos</span> - <span>TA Atacante</span> = <span id="reaction-result-value" class="result-value">0</span>
                            </div>
                            <div id="reaction-result-text" class="reaction-result-text"></div>
                        </div>
                        
                        <h3>🎮 Opciones Disponibles</h3>
                        <div id="reaction-options" class="reaction-options"></div>
                    </div>
                    
                    <!-- Paso 3: Seleccionar técnica (para choque/bloqueo) -->
                    <div id="reaction-step-3" class="reaction-step" style="display:none;">
                        <h3 id="reaction-step-3-title">🗡️ Paso 3: Seleccionar Técnica</h3>
                        <div id="reaction-technique-section">
                            <div class="combat-section">
                                <label>Arma:</label>
                                <select id="reaction-weapon-select" onchange="CombatSystem.selectReactionWeapon(this.value)">
                                    <option value="">-- Seleccionar arma --</option>
                                </select>
                            </div>
                            <div class="combat-section">
                                <label id="reaction-technique-label">Técnica:</label>
                                <select id="reaction-technique-select" onchange="CombatSystem.selectReactionTechnique(this.value)">
                                    <option value="">-- Seleccionar técnica --</option>
                                </select>
                            </div>
                            <div id="reaction-technique-info" class="technique-info" style="display:none;"></div>
                        </div>
                        
                        <div id="reaction-damage-preview" class="reaction-damage-preview"></div>
                        
                        <div class="reaction-buttons">
                            <button onclick="CombatSystem.goToReactionStep(2)" class="btn-back">← Volver</button>
                            <button onclick="CombatSystem.generateReactionCode()" class="btn-generate">Añadir Acción Defensiva</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .combat-modal {
            display: none;
            position: fixed;
            z-index: 99999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            overflow: auto;
        }
        
        .combat-modal-content {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            margin: 0% auto;
            padding: 0;
            border: 3px solid #ffd700;
            border-radius: 15px;
            width: 95%;
            max-width: 1400px;
            color: white;
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
        }
        
        /* Estilos específicos del modal de reacción */
        .reaction-modal-content {
            max-width: 700px;
        }
        
        .reaction-header {
            background: linear-gradient(90deg, #4a90d9, #2c5aa0) !important;
        }
        
        .reaction-header h2 {
            color: white !important;
        }
        
        .reaction-body {
            padding: 20px;
        }
        
        .reaction-step {
            background: rgba(255,255,255,0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .reaction-step h3 {
            color: #ffd700;
            margin: 0 0 15px 0;
            border-bottom: 1px solid #ffd700;
            padding-bottom: 8px;
        }
        
        .reaction-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .reaction-input-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .reaction-input-group label {
            color: #ccc;
            font-size: 14px;
        }
        
        .reaction-input-group input {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #555;
            background: #2a2a4a;
            color: white;
            font-size: 16px;
        }
        
        .reaction-input-group small {
            color: #888;
            font-size: 11px;
        }
        
        .reaction-my-stats {
            background: rgba(0,100,255,0.2);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #4a90d9;
        }
        
        .reaction-my-stats h4 {
            margin: 0 0 10px 0;
            color: #87ceeb;
        }
        
        .btn-calculate {
            background: linear-gradient(90deg, #ffd700, #ff8c00);
            color: #1a1a2e;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .btn-calculate:hover {
            transform: scale(1.02);
        }
        
        .btn-reaction {
            background: #4a90d9;
            color: white;
            border: none;
            padding: 2px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .reaction-result-box {
            background: rgba(0,0,0,0.3);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .reaction-formula {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .reaction-formula span {
            background: rgba(255,255,255,0.1);
            padding: 5px 10px;
            border-radius: 5px;
            margin: 0 5px;
        }
        
        .result-value {
            font-size: 24px !important;
            font-weight: bold !important;
            color: #ffd700 !important;
        }
        
        .reaction-result-text {
            padding: 15px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .reaction-result-text.esquiva { background: rgba(0,200,100,0.3); border: 2px solid #00c864; }
        .reaction-result-text.choque { background: rgba(255,150,0,0.3); border: 2px solid #ff9600; }
        .reaction-result-text.bloqueo { background: rgba(100,100,255,0.3); border: 2px solid #6464ff; }
        .reaction-result-text.none { background: rgba(200,50,50,0.3); border: 2px solid #c83232; }
        
        /* Estilos para técnicas mantenidas */
        .maintained-section { border-left: 3px solid #ff9800; }
        
        .maintained-technique-item {
            gap: 10px;
            padding: 10px;
            background: rgba(255,152,0,0.1);
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid rgba(255,152,0,0.3);
        }
        
        .maintained-technique-item.releasing {
            background: rgba(255,50,50,0.1);
            border-color: rgba(255,50,50,0.3);
            opacity: 0.7;
        }
        
        .maintained-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .maintained-info {
            flex: 1;
        }
        
        .maintained-name {
            font-weight: bold;
            color: #ff9800;
        }
        
        .maintained-turns {
            font-size: 12px;
            color: #aaa;
        }
        
        .maintained-costs {
            font-size: 11px;
            color: #87ceeb;
        }
        
        .maintained-status {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 4px;
        }
        
        .maintained-status.active {
            background: rgba(0,200,100,0.2);
            color: #00c864;
        }
        
        .maintained-status.releasing {
            background: rgba(255,50,50,0.2);
            color: #ff5050;
        }
        
        .damage-received-section {
            background: rgba(255,0,0,0.15);
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
            border: 2px solid rgba(255,0,0,0.4);
        }
        
        .damage-received-section h4 {
            color: #ff4444;
            margin: 0 0 10px 0;
        }
        
        .damage-received-value {
            font-size: 24px;
            font-weight: bold;
            color: #ff4444;
        }
        
        .detected-attacks-section {
            background: rgba(255,100,100,0.1);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,100,100,0.3);
        }
        
        .detected-attacks-section h4 {
            color: #ff6b6b;
            margin: 0 0 10px 0;
        }
        
        .detected-attacks-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .detected-attack-item {
            background: rgba(255,255,255,0.1);
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .detected-attack-item:hover {
            background: rgba(255,100,100,0.2);
            border-color: #ff6b6b;
        }
        
        .detected-attack-item.selected {
            background: rgba(255,100,100,0.3);
            border-color: #ff6b6b;
        }
        
        .detected-attack-attacker {
            font-weight: bold;
            color: #ffd700;
            font-size: 14px;
        }
        
        .detected-attack-technique {
            color: #aaa;
            font-size: 12px;
        }
        
        .detected-attack-stats {
            display: flex;
            gap: 15px;
            margin-top: 5px;
        }
        
        .detected-attack-ta {
            color: #87ceeb;
            font-size: 13px;
        }
        
        .detected-attack-damage {
            color: #ff6b6b;
            font-size: 13px;
        }
        
        .reaction-attack-summary {
            background: rgba(255,100,100,0.15);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,100,100,0.3);
        }
        
        .attack-summary-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .attack-summary-header span {
            font-size: 16px;
            color: #ff6b6b;
        }
        
        .btn-change-attack {
            background: rgba(255,255,255,0.1);
            border: 1px solid #888;
            color: #ccc;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-change-attack:hover {
            background: rgba(255,255,255,0.2);
            border-color: #ffd700;
            color: #ffd700;
        }
        
        .btn-reflejos-modifier {
            background: rgba(255,193,7,0.2);
            border: 1px solid #ffc107;
            color: #ffc107;
            padding: 6px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn-reflejos-modifier:hover {
            background: rgba(255,193,7,0.3);
            border-color: #ffeb3b;
            color: #ffeb3b;
        }
        
        .attack-summary-stats {
            display: flex;
            gap: 20px;
            font-size: 14px;
        }
        
        .attack-summary-stats span {
            color: #aaa;
        }
        
        .attack-summary-stats strong {
            color: #ffd700;
        }
        
        .reaction-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .reaction-option {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        
        .reaction-option:hover {
            background: rgba(255,215,0,0.2);
            border-color: #ffd700;
        }
        
        .reaction-option.selected {
            background: rgba(255,215,0,0.3);
            border-color: #ffd700;
        }
        
        .reaction-option.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .reaction-option-name {
            font-size: 16px;
            font-weight: bold;
            color: #ffd700;
        }
        
        .reaction-option-effect {
            font-size: 13px;
            color: #ccc;
            margin-top: 5px;
        }
        
        .reaction-damage-preview {
            background: rgba(255,100,100,0.2);
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid #ff6b6b;
            margin: 15px 0;
        }
        
        .reaction-buttons {
            display: flex;
            gap: 10px;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .btn-back {
            background: #555;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .combat-header {
            background: linear-gradient(90deg, #ffd700, #ff8c00);
            padding: 0px 20px;
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .combat-header h2 {
            margin: 0;
            color: #1a1a2e;
            font-family: 'moonGetHeavy', sans-serif;
            text-shadow: 1px 1px 2px rgba(255,255,255,0.3);
        }
        
        .combat-close {
            font-size: 32px;
            font-weight: bold;
            cursor: pointer;
            color: #1a1a2e;
            transition: transform 0.2s;
        }
        
        .combat-close:hover {
            transform: scale(1.2);
        }
        
        .combat-body {
            display: flex;
            padding: 0px;
            gap: 0px;
            min-height: 500px;
        }
        
        .combat-panel {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            backdrop-filter: blur(5px);
        }
        
        .combat-panel h3 {
            color: #ffd700;
            margin: 0 0 0px 0;
            padding-bottom: 0px;
            border-bottom: 1px solid #ffd700;
            font-size: 14px;
        }
        
        .combat-panel h4 {
            color: #87ceeb;
            margin: 10px 0 5px 0;
            font-size: 12px;
        }
        
        .combat-panel-left {
            flex: 1;
            min-width: 250px;
        }
        
        .combat-panel-center {
            flex: 2;
            min-width: 400px;
        }
        
        .combat-panel-right {
            flex: 1;
            min-width: 280px;
        }
        
        .combat-loading {
            text-align: center;
            padding: 20px;
            color: #888;
        }
        
        .resource-bar {
            display: flex;
            justify-content: space-between;
            padding: 2px 12px;
            margin: 5px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .resource-vida { background: linear-gradient(90deg, #00aa00, #006600); }
        .resource-energia { background: linear-gradient(90deg, #ff8800, #cc6600); }
        .resource-haki { background: linear-gradient(90deg, #0066ff, #0044aa); }
        
        .combat-section {
            margin: 15px 0;
        }
        
        .combat-section label {
            display: block;
            margin-bottom: 5px;
            color: #ccc;
        }
        
        .combat-section select,
        .combat-section input {
            width: 100%;
            padding: 2px;
            border-radius: 5px;
            border: 1px solid #555;
            background: #2a2a4a;
            color: white;
            font-size: 14px;
        }
        
        .technique-info {
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .weapon-info {
            background: rgba(139,69,19,0.3);
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            border-left: 3px solid #ffd700;
        }
        
        .weapon-stats {
            display: flex;
            gap: 15px;
            margin-top: 8px;
        }
        
        .weapon-stat {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .weapon-damage { background: rgba(255,100,100,0.3); border: 1px solid #ff6b6b; }
        .weapon-block { background: rgba(100,100,255,0.3); border: 1px solid #6b6bff; }
        
        .technique-info .tec-cost {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            margin: 2px;
            font-weight: bold;
        }
        
        .tec-cost-energia { background: #ff8800; }
        .tec-cost-haki { background: #0066ff; }
        .tec-cost-cd { background: #888; }
        
        .combat-filters {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
            margin: 10px 0;
        }
        
        .filter-btn {
            padding: 5px 10px;
            border: 1px solid #555;
            background: #333;
            color: white;
            border-radius: 3px;
            cursor: pointer;
            font-size: 11px;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: #ffd700;
            color: #1a1a2e;
        }
        
        .combat-actions-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
        }
        
        .combat-actions-buttons button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
        }
        
        .btn-add-action { background: #28a745; color: white; }
        .btn-basic-attack { background: #ff6b6b; color: white; }
        .btn-basic-block { background: #6b6bff; color: white; }
        .btn-clear { background: #dc3545; color: white; }
        .btn-generate { background: #ffd700; color: #1a1a2e; }
        .btn-refresh { background: #17a2b8; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; }
        .btn-add-modifier { background: #ffc107; color: #1a1a2e; border: none; padding: 6px 10px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top:6px; }
        
        .add-target-form {
            display: flex;
            gap: 5px;
            margin: 10px 0;
        }
        
        .add-target-form input {
            flex: 1;
            padding: 5px;
            border-radius: 3px;
            border: 1px solid #555;
            background: #2a2a4a;
            color: white;
        }
        
        .add-target-form button {
            padding: 5px 10px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .target-item, .npc-item {
            background: rgba(255,255,255,0.1);
            padding: 2px;
            margin: 5px 0;
            border-radius: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .target-item:hover, .npc-item:hover {
            background: rgba(255,215,0,0.2);
        }
        
        .target-item.selected, .npc-item.selected {
            background: rgba(255,215,0,0.3);
            border: 1px solid #ffd700;
        }
        
        .target-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #555;
        }
        
        .target-info {
            flex: 1;
        }
        
        .target-name {
            font-weight: bold;
            color: #ffd700;
        }
        
        .target-stats {
            font-size: 11px;
            color: #aaa;
        }
        
        .action-item {
            background: rgba(40, 167, 69, 0.2);
            padding: 2px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 3px solid #28a745;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .action-item .remove-action {
            cursor: pointer;
            color: #dc3545;
            font-weight: bold;
        }
        
        .cooldown-item {
            background: rgba(136, 136, 136, 0.2);
            padding: 5px 10px;
            margin: 3px 0;
            border-radius: 3px;
            font-size: 12px;
            display: flex;
            justify-content: space-between;
        }
        
        .cooldown-turns {
            color: #ffd700;
            font-weight: bold;
        }
        
        .history-item {
            padding: 5px;
            margin: 3px 0;
            border-radius: 3px;
            font-size: 11px;
            border-left: 2px solid #555;
            padding-left: 8px;
        }
        
        .history-item.my-action { border-left-color: #28a745; }
        .history-item.enemy-action { border-left-color: #dc3545; }
        
        .combat-footer {
            background: rgba(0,0,0,0.3);
            padding: 3px 25px;
            border-radius: 0 0 12px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        #combat-status {
            color: #87ceeb;
            font-size: 12px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
            font-size: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .stat-label { color: #aaa; }
        .stat-value { color: #fff; font-weight: bold; }
        </style>`;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    },

    /**
     * Bindea eventos
     */
    bindEvents: function() {
        // Cerrar modal al hacer clic fuera
        var modal = document.getElementById('combat-system-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    CombatSystem.close();
                }
            });
        }

        // Tecla ESC para cerrar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && CombatSystem.isOpen) {
                CombatSystem.close();
            }
        });
    },

    /**
     * Abre el panel de combate
     */
    open: function() {
        var self = this;
        this.detectThreadId();
        
        // Primero verificar si el usuario puede usar el sistema de combate
        fetch(this.config.apiUrl + '?action=check_combat_ready&tid=' + this.currentTid)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (!data.ready) {
                    alert('⚠️ Sistema de Combate no disponible\n\n' + data.reason);
                    return;
                }
                
                // Si está listo, abrir el modal
                var modal = document.getElementById('combat-system-modal');
                if (modal) {
                    modal.style.display = 'block';
                    self.isOpen = true;
                    self.loadData();
                }
            })
            .catch(function(error) {
                console.error('[CombatSystem] Error verificando estado:', error);
                alert('Error al verificar el estado del combate. Por favor, intenta de nuevo.');
            });
    },

    /**
     * Cierra el panel de combate
     */
    close: function() {
        var modal = document.getElementById('combat-system-modal');
        if (modal) {
            modal.style.display = 'none';
            this.isOpen = false;
        }
    },

    /**
     * Detecta el TID del tema actual
     */
    detectThreadId: function() {
        // Intentar obtener de la URL
        var urlParams = new URLSearchParams(window.location.search);
        var tid = urlParams.get('tid');
        
        // Si estamos en newthread, buscar en formulario
        if (!tid) {
            var tidInput = document.querySelector('input[name="tid"]');
            if (tidInput) {
                tid = tidInput.value;
            }
        }

        // Buscar en URL de newreply
        if (!tid && window.location.href.includes('newreply.php')) {
            tid = urlParams.get('tid');
        }

        this.currentTid = tid || 0;
        
        // Obtener UID actual
        if (typeof userUid !== 'undefined') {
            this.currentUid = userUid;
        }
        
        console.log('[CombatSystem] TID detectado:', this.currentTid);
    },

    /**
     * Carga todos los datos necesarios
     */
    loadData: function() {
        this.setStatus('Cargando datos de combate...');
        
        // Cargar datos del tema, técnicas y armas en paralelo
        Promise.all([
            this.fetchCombatData(),
            this.fetchMyTechniques(),
            this.fetchMyWeapons()
        ]).then(function() {
            CombatSystem.setStatus('Datos cargados');
            CombatSystem.calculateCooldowns();
            CombatSystem.calculateResourcesUsed();
            CombatSystem.detectMaintainedTechniques();
        }).catch(function(error) {
            CombatSystem.setStatus('Error: ' + error.message);
            console.error('[CombatSystem] Error:', error);
        });
    },

    /**
     * Obtiene datos de combate del tema
     */
    fetchCombatData: function() {
        var self = this;
        return fetch(this.config.apiUrl + '?action=get_thread_combat_data&tid=' + this.currentTid)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    var errorMsg = data.error;
                    if (data.message) errorMsg += ': ' + data.message;
                    if (data.file) errorMsg += ' (' + data.file + ':' + data.line + ')';
                    console.error('[CombatSystem] API Error:', data);
                    throw new Error(errorMsg);
                }
                self.combatData = data;
                self.renderMyCharacter();
                self.renderTargets();
                self.renderNpcs();
                self.renderHistory();
                return data;
            });
    },

    /**
     * Obtiene mis técnicas
     */
    fetchMyTechniques: function() {
        var self = this;
        return fetch(this.config.apiUrl + '?action=get_my_techniques')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    var errorMsg = data.error;
                    if (data.message) errorMsg += ': ' + data.message;
                    if (data.file) errorMsg += ' (' + data.file + ':' + data.line + ')';
                    console.error('[CombatSystem] Techniques API Error:', data);
                    throw new Error(errorMsg);
                }
                if (data.techniques) {
                    self.myTechniques = data.techniques;
                    self.renderTechniqueSelect();
                }
                if (data.pasivas) {
                    self.myPasivas = data.pasivas;
                }
                return data;
            });
    },

    /**
     * Obtiene mis armas del inventario
     */
    fetchMyWeapons: function() {
        var self = this;
        return fetch(this.config.apiUrl + '?action=get_my_weapons')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.error) {
                    var errorMsg = data.error;
                    if (data.message) errorMsg += ': ' + data.message;
                    console.error('[CombatSystem] Weapons API Error:', data);
                    throw new Error(errorMsg);
                }
                if (data.weapons) {
                    self.myWeapons = data.weapons;
                    self.renderWeaponSelect();
                }
                return data;
            });
    },

    /**
     * Renderiza el selector de armas (hasta 3 armas)
     */
    renderWeaponSelect: function() {
        var container = document.getElementById('combat-weapon-select');
        if (!container) return;
        
        var html = '<div style="display: flex; flex-direction: column; gap: 10px;">';
        
        // Selectores para cada arma
        var armaLabels = ['PRINCIPAL', 'SECUNDARIA', 'TERCIARIA'];
        for (var slotIdx = 0; slotIdx < 3; slotIdx++) {
            var visible = slotIdx === 0 || this.selectedWeapons[slotIdx - 1] !== null;
            html += '<div id="weapon-slot-' + slotIdx + '" style="display: ' + (visible ? 'block' : 'none') + ';">';
            html += '<label style="display: block; margin-bottom: 4px; font-weight: bold; color: #333;">' + armaLabels[slotIdx] + ':</label>';
            html += '<select id="weapon-selector-' + slotIdx + '" style="width: 100%; padding: 2px; border-radius: 4px; border: 1px solid #ddd;">';
            html += '<option value="">Sin arma</option>';
            
            for (var i = 0; i < this.myWeapons.length; i++) {
                var weapon = this.myWeapons[i];
                var selected = this.selectedWeapons[slotIdx] && this.selectedWeapons[slotIdx].objeto_id === weapon.objeto_id;
                var tierText = weapon.tier > 0 ? ' (T' + weapon.tier + ')' : '';
                html += '<option value="' + weapon.objeto_id + '"' + (selected ? ' selected' : '') + '>';
                html += weapon.nombre + tierText;
                html += '</option>';
            }
            
            html += '</select>';
            html += '</div>';
        }
        
        // Indicador de armas equipadas
        html += '<div id="weapon-indicator" style="margin-top: 10px; padding: 2px; background: #f0f0f0; border-radius: 4px; display: none;">';
        html += '<strong style="color: #0055bb;">Armas equipadas:</strong> <span id="weapon-count">0</span>';
        html += '<div style="font-size: 11px; color: #666; margin-top: 4px;">Multiplicador: <span id="weapon-multiplier">100%</span></div>';
        html += '</div>';
        
        // Contenedor para información detallada de armas
        html += '<div id="combat-weapon-info" style="margin-top: 10px; display: none;"></div>';
        
        html += '</div>';
        container.innerHTML = html;
        
        // Agregar eventos a cada selector
        for (var j = 0; j < 3; j++) {
            (function(index) {
                var selector = document.getElementById('weapon-selector-' + index);
                if (selector) {
                    selector.addEventListener('change', function(e) {
                        CombatSystem.selectWeaponAtSlot(index, e.target.value);
                    });
                }
            })(j);
        }
    },

    /**
     * Selecciona un arma en un slot específico
     */
    selectWeaponAtSlot: function(slotIndex, weaponId) {
        if (!weaponId) {
            this.selectedWeapons[slotIndex] = null;
        } else {
            const weapon = this.myWeapons.find(w => w.objeto_id === weaponId);
            if (weapon) {
                this.selectedWeapons[slotIndex] = weapon;
            }
        }
        
        // Mostrar/ocultar slots siguientes
        this.updateWeaponSlotsVisibility();
        
        // Actualizar indicador
        this.updateWeaponIndicator();
        
        // Mostrar información de armas
        this.displayWeaponsInfo();
        
        // Recalcular TA y recursos
        this.updateTADisplay();
        this.calculateResourcesUsed();
    },
    
    /**
     * Actualiza la visibilidad de los slots de armas
     */
    updateWeaponSlotsVisibility: function() {
        for (let i = 1; i < 3; i++) {
            const slot = document.getElementById(`weapon-slot-${i}`);
            if (slot) {
                // Mostrar slot si el anterior tiene un arma
                const visible = this.selectedWeapons[i - 1] !== null;
                slot.style.display = visible ? 'block' : 'none';
            }
        }
    },
    
    /**
     * Muestra información detallada de las armas seleccionadas
     */
    displayWeaponsInfo: function() {
        var container = document.getElementById('combat-weapon-info');
        if (!container) return;
        
        var activeWeapons = this.selectedWeapons.filter(function(w) { return w !== null; });
        
        if (activeWeapons.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        var html = '';
        var char = this.getEffectiveCharacter();
        
        for (var i = 0; i < activeWeapons.length; i++) {
            var weapon = activeWeapons[i];
            var label = i === 0 ? 'Principal' : (i === 1 ? 'Secundaria' : 'Terciaria');
            
            html += '<div style="margin-bottom: 10px; padding: 2px; background: rgba(0,0,0,0.5); border-radius: 4px; border-left: 3px solid #0055bb;">';
            html += '<strong style="color: #0055bb;">🗡️ ' + label + ': ' + weapon.nombre + '</strong>';
            if (weapon.tier > 0) {
                html += ' - Tier ' + weapon.tier;
            }
            html += '<br>';
            
            if (weapon.subcategoria) {
                html += '<span style="color: #aaa; font-size: 11px;">' + weapon.subcategoria + '</span><br>';
            }
            
            html += '<div style="margin-top: 5px;">';
            
            // Calcular daño del arma
            if (weapon.dano && char) {
                var damageResult = this.parseWeaponFormula(weapon.dano, char);
                html += '<div style="color: #ff4444; font-size: 12px;">';
                html += '⚔️ Daño: <strong>' + damageResult.total + '</strong>';
                if (damageResult.type) {
                    html += ' <span style="font-size: 10px;">(' + damageResult.type + ')</span>';
                }
                html += '</div>';
            }
            
            // Calcular bloqueo del arma
            if (weapon.bloqueo && char) {
                var blockResult = this.parseWeaponFormula(weapon.bloqueo, char);
                html += '<div style="color: #5bc0de; font-size: 12px;">';
                html += '🛡️ Bloqueo: <strong>' + blockResult.total + '</strong>';
                if (blockResult.type) {
                    html += ' <span style="font-size: 10px;">(' + blockResult.type + ')</span>';
                }
                html += '</div>';
            }
            
            html += '</div>';
            html += '</div>';
        }
        
        // Mostrar TA (solo para la primera arma)
        if (activeWeapons.length > 0 && char) {
            var weapon = activeWeapons[0];
            html += '<div style="margin-top: 10px; padding: 2px; background: rgba(255,193,7,0.15); border-radius: 4px;">';
            html += '<div style="font-weight: bold; color: #ffc107; margin-bottom: 5px;">🎯 Tasa de Acierto</div>';
            
            if (weapon.escalado) {
                var taScaling = this.parseWeaponTAScaling(weapon.escalado);
                var taModifier = this.parseWeaponTAModifier(weapon.efecto);
                
                if (taScaling.stats.length === 0) {
                    var punteria = parseInt(char.stats.punteria) || 0;
                    var destreza = parseInt(char.stats.destreza) || 0;
                    var ta = punteria + destreza;
                    html += '<div>Puntería + Destreza = <strong id="weapon-ta-value">' + ta + '</strong></div>';
                } else if (taScaling.hasChoice) {
                    html += '<div style="margin-bottom: 5px;">Elige el stat para TA:</div>';
                    html += '<div class="ta-stat-buttons" style="display: flex; gap: 8px; flex-wrap: wrap;">';
                    
                    var statLabels = {
                        'fuerza': 'Fuerza',
                        'resistencia': 'Resistencia', 
                        'destreza': 'Destreza',
                        'punteria': 'Puntería',
                        'agilidad': 'Agilidad',
                        'reflejos': 'Reflejos',
                        'voluntad': 'Voluntad'
                    };
                    
                    for (var j = 0; j < taScaling.stats.length; j++) {
                        var stat = taScaling.stats[j];
                        var statValue = parseInt(char.stats[stat]) || 0;
                        var totalWithMod = statValue + taModifier;
                        var isFirst = j === 0;
                        
                        html += '<button type="button" class="ta-stat-option' + (isFirst ? ' selected' : '') + '" ';
                        html += 'data-stat="' + stat + '" ';
                        html += 'onclick="CombatSystem.selectTAStat(\'' + stat + '\')" ';
                        html += 'style="padding: 6px 12px; border: 1px solid #ffc107; background: ' + (isFirst ? '#ffc107' : 'transparent') + '; ';
                        html += 'color: ' + (isFirst ? '#000' : '#ffc107') + '; border-radius: 4px; cursor: pointer;">';
                        html += statLabels[stat] + ': ' + statValue;
                        if (taModifier !== 0) {
                            html += ' (' + (taModifier > 0 ? '+' : '') + taModifier + ')';
                        }
                        html += ' = <strong>' + totalWithMod + '</strong>';
                        html += '</button>';
                    }
                    
                    html += '</div>';
                    html += '<div style="margin-top: 8px;">TA Final: <strong id="weapon-ta-value">' + (parseInt(char.stats[taScaling.stats[0]]) + taModifier) + '</strong></div>';
                    this.selectedTAStat = taScaling.stats[0];
                } else {
                    var stat = taScaling.stats[0];
                    var statValue = parseInt(char.stats[stat]) || 0;
                    var ta = statValue + taModifier;
                    var statLabels = {
                        'fuerza': 'Fuerza',
                        'resistencia': 'Resistencia', 
                        'destreza': 'Destreza',
                        'punteria': 'Puntería',
                        'agilidad': 'Agilidad',
                        'reflejos': 'Reflejos',
                        'voluntad': 'Voluntad'
                    };
                    html += '<div>' + statLabels[stat] + ': ' + statValue;
                    if (taModifier !== 0) {
                        html += ' ' + (taModifier > 0 ? '+' : '') + taModifier + ' (efecto)';
                    }
                    html += ' = <strong id="weapon-ta-value">' + ta + '</strong></div>';
                    this.selectedTAStat = stat;
                }
            } else if (weapon.objeto_id === 'UNARMED') {
                var destreza = parseInt(char.stats.destreza) || 0;
                html += '<div>Destreza = <strong id="weapon-ta-value">' + destreza + '</strong></div>';
            }
            
            html += '</div>';
        }
        
        container.innerHTML = html;
        container.style.display = 'block';
    },
    
    /**
     * Actualiza el indicador de armas equipadas
     */
    updateWeaponIndicator: function() {
        const indicator = document.getElementById('weapon-indicator');
        const countEl = document.getElementById('weapon-count');
        const multiplierEl = document.getElementById('weapon-multiplier');
        
        if (!indicator || !countEl || !multiplierEl) return;
        
        const activeWeapons = this.selectedWeapons.filter(w => w !== null);
        const count = activeWeapons.length;
        
        if (count > 0) {
            indicator.style.display = 'block';
            countEl.textContent = count;
            
            // Calcular multiplicador
            let multiplier = 1.0;
            if (count === 2) multiplier = 0.75;
            else if (count === 3) multiplier = 0.60;
            
            multiplierEl.textContent = Math.round(multiplier * 100) + '%';
        } else {
            indicator.style.display = 'none';
        }
    },
    
    /**
     * Selecciona un arma (compatibilidad con código anterior)
     */
    selectWeapon: function(weaponId) {
        var container = document.getElementById('combat-weapon-info');
        if (!container) return;

        if (!weaponId) {
            container.style.display = 'none';
            this.selectedWeapon = null;
            return;
        }

        var weapon = this.myWeapons.find(function(w) { return w.objeto_id === weaponId; });
        if (!weapon) {
            container.style.display = 'none';
            this.selectedWeapon = null;
            return;
        }

        this.selectedWeapon = weapon;

        var html = '<strong>🗡️ ' + weapon.nombre + '</strong>';
        if (weapon.tier > 0) {
            html += ' - Tier ' + weapon.tier;
        }
        html += '<br>';
        
        if (weapon.subcategoria) {
            html += '<span style="color: #aaa; font-size: 11px;">' + weapon.subcategoria + '</span><br>';
        }
        
        html += '<div class="weapon-stats">';
        
        // Calcular daño del arma
        var char = this.getEffectiveCharacter();
        if (weapon.dano && char) {
            var damageResult = this.parseWeaponFormula(weapon.dano, char);
            html += '<div class="weapon-stat weapon-damage">';
            html += '⚔️ Daño: <strong>' + damageResult.total + '</strong>';
            if (damageResult.type) {
                html += ' <span style="font-size: 10px;">(' + damageResult.type + ')</span>';
            }
            html += '</div>';
        }
        
        // Calcular bloqueo del arma
        if (weapon.bloqueo && char) {
            var blockResult = this.parseWeaponFormula(weapon.bloqueo, char);
            html += '<div class="weapon-stat weapon-block">';
            html += '🛡️ Bloqueo: <strong>' + blockResult.total + '</strong>';
            if (blockResult.type) {
                html += ' <span style="font-size: 10px;">(' + blockResult.type + ')</span>';
            }
            html += '</div>';
        }
        
        html += '</div>';
        
        // Calcular y mostrar TA basada en escalado
        if (char) {
            var taScaling = this.parseWeaponTAScaling(weapon.escalado);
            var taModifier = this.parseWeaponTAModifier(weapon.efecto);
            
            html += '<div class="weapon-ta-section" style="margin-top: 10px; padding: 2px; background: rgba(255,193,7,0.15); border-radius: 4px;">';
            html += '<div style="font-weight: bold; color: #ffc107; margin-bottom: 5px;">🎯 Tasa de Acierto</div>';
            
            if (taScaling.stats.length === 0) {
                // Sin escalado definido, usar fallback
                var punteria = parseInt(char.stats.punteria) || 0;
                var destreza = parseInt(char.stats.destreza) || 0;
                var ta = punteria + destreza;
                html += '<div>Puntería + Destreza = <strong id="weapon-ta-value">' + ta + '</strong></div>';
                this.selectedTAStat = null;
            } else if (taScaling.hasChoice) {
                // Hay que elegir entre stats
                html += '<div style="margin-bottom: 5px;">Elige el stat para TA:</div>';
                html += '<div class="ta-stat-buttons" style="display: flex; gap: 8px;">';
                
                var statLabels = {
                    'fuerza': 'Fuerza',
                    'resistencia': 'Resistencia', 
                    'destreza': 'Destreza',
                    'punteria': 'Puntería',
                    'agilidad': 'Agilidad',
                    'reflejos': 'Reflejos',
                    'voluntad': 'Voluntad'
                };
                
                for (var i = 0; i < taScaling.stats.length; i++) {
                    var stat = taScaling.stats[i];
                    var statValue = parseInt(char.stats[stat]) || 0;
                    var totalWithMod = statValue + taModifier;
                    var isFirst = i === 0;
                    
                    html += '<button type="button" class="ta-stat-option' + (isFirst ? ' selected' : '') + '" ';
                    html += 'data-stat="' + stat + '" ';
                    html += 'onclick="CombatSystem.selectTAStat(\'' + stat + '\')" ';
                    html += 'style="padding: 6px 12px; border: 1px solid #ffc107; background: ' + (isFirst ? '#ffc107' : 'transparent') + '; ';
                    html += 'color: ' + (isFirst ? '#000' : '#ffc107') + '; border-radius: 4px; cursor: pointer;">';
                    html += statLabels[stat] + ': ' + statValue;
                    if (taModifier !== 0) {
                        html += ' (' + (taModifier > 0 ? '+' : '') + taModifier + ')';
                    }
                    html += ' = <strong>' + totalWithMod + '</strong>';
                    html += '</button>';
                }
                
                html += '</div>';
                html += '<div style="margin-top: 8px;">TA Final: <strong id="weapon-ta-value">' + (parseInt(char.stats[taScaling.stats[0]]) + taModifier) + '</strong></div>';
                this.selectedTAStat = taScaling.stats[0]; // Seleccionar el primero por defecto
            } else {
                // Solo un stat
                var stat = taScaling.stats[0];
                var statValue = parseInt(char.stats[stat]) || 0;
                var ta = statValue + taModifier;
                var statLabels = {
                    'fuerza': 'Fuerza',
                    'resistencia': 'Resistencia', 
                    'destreza': 'Destreza',
                    'punteria': 'Puntería',
                    'agilidad': 'Agilidad',
                    'reflejos': 'Reflejos',
                    'voluntad': 'Voluntad'
                };
                html += '<div>' + statLabels[stat] + ': ' + statValue;
                if (taModifier !== 0) {
                    html += ' ' + (taModifier > 0 ? '+' : '') + taModifier + ' (efecto)';
                }
                html += ' = <strong id="weapon-ta-value">' + ta + '</strong></div>';
                this.selectedTAStat = stat;
            }
            
            html += '</div>';
        }
        
        if (weapon.efecto) {
            html += '<div style="margin-top: 8px; font-size: 11px; color: #87ceeb;">';
            html += '✨ ' + weapon.efecto;
            html += '</div>';
        }

        container.innerHTML = html;
        container.style.display = 'block';
    },

    /**
     * Parsea la fórmula de daño/bloqueo del arma
     * Formato: 40+[PUNx2]+[DESx1] de [Daño perforante]
     */
    parseWeaponFormula: function(formula, char) {
        // Usar personaje efectivo si no se pasa explícitamente
        if (!char) char = this.getEffectiveCharacter();
        if (!formula || !char) return { total: 0, type: '', breakdown: [] };
        
        var result = {
            total: 0,
            type: '',
            base: 0,
            breakdown: []
        };
        
        var stats = char.stats || {};
        
        // Mapeo de abreviaturas a stats
        var statMap = {
            'FUE': stats.fuerza || 0,
            'RES': stats.resistencia || 0,
            'DES': stats.destreza || 0,
            'PUN': stats.punteria || 0,
            'AGI': stats.agilidad || 0,
            'REF': stats.reflejos || 0,
            'VOL': stats.voluntad || 0
        };
        
        // Obtener tipo de daño: "de [Daño X]"
        var typeMatch = formula.match(/de\s*\[([^\]]+)\]/i);
        if (typeMatch) {
            result.type = typeMatch[1];
        }
        
        // Obtener base numérica: "40+..."
        var baseMatch = formula.match(/^(\d+)/);
        if (baseMatch) {
            result.base = parseInt(baseMatch[1]);
            result.total += result.base;
            result.breakdown.push({ label: 'Base', value: result.base });
        }
        
        // Parsear escalados: [PUNx2], [DESx1,5], etc.
        var scalingRegex = /\[([A-Z]{3})x(\d+[,.]?\d*)\]/gi;
        var match;
        while ((match = scalingRegex.exec(formula)) !== null) {
            var statAbbr = match[1].toUpperCase();
            var multiplier = parseFloat(match[2].replace(',', '.'));
            var statValue = statMap[statAbbr] || 0;
            var scalingValue = Math.floor(multiplier * statValue);
            
            result.total += scalingValue;
            result.breakdown.push({
                label: statAbbr + 'x' + match[2],
                multiplier: multiplier,
                stat: statAbbr,
                statValue: statValue,
                value: scalingValue
            });
        }
        
        return result;
    },

    /**
     * Renderiza información de mi personaje
     */
    renderMyCharacter: function() {
        if (!this.combatData || !this.combatData.my_character) return;
        
        // Actualizar selector de personaje/NPC si hay NPCs controlados
        this.updateCharacterSelector();
        
        // Mostrar info del personaje activo (principal o NPC seleccionado)
        this.renderActiveCharacterInfo();

        // Actualizar turno actual
        document.getElementById('combat-current-turn').textContent = this.combatData.my_turns + 1;
    },

    /**
     * Actualiza el selector de personaje/NPC con los NPCs controlados por el jugador
     */
    updateCharacterSelector: function() {
        var selectorContainer = document.getElementById('combat-character-selector-container');
        var selector = document.getElementById('combat-character-selector');
        if (!selector || !this.combatData) return;
        
        var currentUid = this.combatData.current_uid;
        var npcs = this.combatData.npcs || [];
        
        // Filtrar NPCs controlados por el jugador
        var myNpcs = [];
        for (var i = 0; i < npcs.length; i++) {
            if (npcs[i].controller_uid == currentUid) {
                myNpcs.push(npcs[i]);
            }
        }
        
        // Solo mostrar selector si hay NPCs controlados
        if (myNpcs.length > 0) {
            selectorContainer.style.display = 'block';
            
            // Construir opciones
            var html = '<option value="main">Mi Personaje</option>';
            for (var i = 0; i < myNpcs.length; i++) {
                var npc = myNpcs[i];
                var npcName = npc.data.nombre || npc.npc_id;
                html += '<option value="npc_' + i + '">🎭 ' + npcName + '</option>';
            }
            
            selector.innerHTML = html;
            
            // Restaurar selección si hay una activa
            if (this.selectedCharacter) {
                selector.value = this.selectedCharacter;
            }
        } else {
            selectorContainer.style.display = 'none';
        }
    },
    
    /**
     * Cambia el personaje activo (personaje principal o NPC)
     */
    switchCharacter: function(value) {
        this.selectedCharacter = value;
        
        // Limpiar estado actual
        this.selectedWeapons = [null, null, null];
        this.myWeapons = [];
        this.myTechniques = [];
        this.myPasivas = [];
        this.pendingActions = [];
        
        // Recargar datos del personaje/NPC seleccionado
        this.loadCharacterData();
        this.renderPendingActions();
        
        console.log('[CombatSystem] Cambiado a:', value);
    },
    
    /**
     * Carga datos (técnicas, armas) del personaje/NPC activo
     */
    loadCharacterData: function() {
        var self = this;
        var char = this.getEffectiveCharacter();
        
        if (!char) return;
        
        // Renderizar info del personaje activo
        this.renderActiveCharacterInfo();
        
        // Cargar técnicas y armas (siempre del UID del jugador)
        Promise.all([
            this.fetchMyTechniques(),
            this.fetchMyWeapons()
        ]).then(function() {
            self.calculateResourcesUsed();
        }).catch(function(err) {
            console.error('[CombatSystem] Error loading character data:', err);
            alert('Error al cargar datos del personaje: ' + err.message);
        });
    },
    
    /**
     * Renderiza la información del personaje/NPC activo
     */
    renderActiveCharacterInfo: function() {
        var container = document.getElementById('combat-my-character');
        if (!container) return;
        
        var char = this.getEffectiveCharacter();
        if (!char) {
            container.innerHTML = '<em>No se detectó personaje.</em>';
            return;
        }

        var html = '<div class=\"character-card\">';
        html += '<div class=\"stat-row\"><span class=\"stat-label\">Nombre:</span><span class=\"stat-value\">' + char.nombre + '</span></div>';
        html += '<div class=\"stat-row\"><span class=\"stat-label\">Nivel:</span><span class=\"stat-value\">' + char.nivel + '</span></div>';
        html += '<hr style=\"border-color: rgba(255,255,255,0.2); margin: 10px 0;\">';
        
        for (var stat in char.stats) {
            html += '<div class=\"stat-row\"><span class=\"stat-label\">' + stat.toUpperCase() + ':</span><span class=\"stat-value\">' + char.stats[stat] + '</span></div>';
        }
        
        html += '</div>';
        container.innerHTML = html;

        // Actualizar recursos: máximos desde char.resources, actuales desde getters que aplican modificadores
        document.getElementById('combat-vida-max').textContent = char.resources.vitalidad;
        document.getElementById('combat-energia-max').textContent = char.resources.energia;
        document.getElementById('combat-haki-max').textContent = char.resources.haki;
        
        document.getElementById('combat-vida-current').textContent = this.getCharacterCurrentHP();
        document.getElementById('combat-energia-current').textContent = this.getCharacterCurrentEnergia();
        document.getElementById('combat-haki-current').textContent = this.getCharacterCurrentHaki();

        // Renderizar lista de modificadores
        this.renderModifiers();
    },
    
    /**
     * Obtiene el personaje actualmente seleccionado (principal o NPC)
     */
    getActiveCharacter: function() {
        if (!this.combatData) return null;
        
        // Si no hay selección o es 'main', devolver personaje principal
        if (!this.selectedCharacter || this.selectedCharacter === 'main') {
            return this.combatData.my_character;
        }
        
        // Si es un NPC, buscar en la lista de NPCs
        if (this.selectedCharacter.indexOf('npc_') === 0) {
            var npcIndex = parseInt(this.selectedCharacter.replace('npc_', ''));
            var currentUid = this.combatData.current_uid;
            var npcs = this.combatData.npcs || [];
            
            // Filtrar NPCs controlados por el jugador
            var myNpcs = [];
            for (var i = 0; i < npcs.length; i++) {
                if (npcs[i].controller_uid == currentUid) {
                    myNpcs.push(npcs[i]);
                }
            }
            
            if (myNpcs[npcIndex]) {
                return myNpcs[npcIndex].data;
            }
        }
        
        return this.combatData.my_character;
    },
    
    /**
     * Obtiene el UID del personaje activo (para cargar técnicas y armas)
     */
    getActiveCharacterUid: function() {
        if (!this.combatData) return null;
        
        // Si no hay selección o es 'main', devolver UID del jugador
        if (!this.selectedCharacter || this.selectedCharacter === 'main') {
            return this.combatData.current_uid;
        }
        
        // Si es un NPC, los NPCs usan las técnicas/armas del controlador
        // pero con las stats del NPC
        return this.combatData.current_uid;
    },

    /**
     * Añade un modificador temporal (stat o recurso)
     */
    addModifier: function() {
        var target = document.getElementById('modifier-target');
        var valueEl = document.getElementById('modifier-value');
        var msgEl = document.getElementById('modifier-message');
        if (!target || !valueEl) return;
        var key = target.value;
        var value = parseInt(valueEl.value, 10) || 0;
        var message = msgEl ? msgEl.value : '';
        if (!key) return;
        var targetType = ['vida','energia','haki'].indexOf(key) !== -1 ? 'resource' : 'stat';
        var scopeEl = document.getElementById('modifier-scope');
        var scope = scopeEl ? scopeEl.value : 'current';
        var turnsEl = document.getElementById('modifier-turns');
        var turns = turnsEl ? Math.max(0, parseInt(turnsEl.value,10) || 0) : 0;
        var id = 'mod_' + Date.now() + '_' + Math.floor(Math.random()*1000);
        this.modifiers.push({ id: id, targetType: targetType, key: key, value: value, message: message, scope: scope, turns: turns });
        // reset inputs
        valueEl.value = 0;
        if (msgEl) msgEl.value = '';
        this.renderModifiers();
        this.renderActiveCharacterInfo();
        this.displayWeaponsInfo();
        this.renderTechniqueSelect('all');
        if (this.reactionMode) {
            var reflejos = this.getEffectiveCharacter().stats.reflejos || 0;
            var el = document.getElementById('reaction-reflejos-value');
            if (el) el.textContent = reflejos;
        }
        this.calculateResourcesUsed();
        this.renderPendingActions();
        this.setStatus('Modificador añadido: ' + (value >= 0 ? '+'+value : value) + ' a ' + key);
    },

    removeModifier: function(id) {
        this.modifiers = this.modifiers.filter(function(m){ return m.id !== id; });
        this.renderModifiers();
        this.renderActiveCharacterInfo();
        this.displayWeaponsInfo();
        this.renderTechniqueSelect('all');
        if (this.reactionMode) {
            var reflejos = this.getEffectiveCharacter().stats.reflejos || 0;
            var el = document.getElementById('reaction-reflejos-value');
            if (el) el.textContent = reflejos;
        }
        this.calculateResourcesUsed();
        this.renderPendingActions();
        this.setStatus('Modificador eliminado');
    },

    /**
     * Decrementa la duración en turnos de los modificadores y elimina los expirados
     */
    tickModifiers: function() {
        if (!this.modifiers || this.modifiers.length === 0) return;
        var expired = [];
        for (var i = this.modifiers.length - 1; i >= 0; i--) {
            var m = this.modifiers[i];
            if (m.turns && m.turns > 0) {
                m.turns = m.turns - 1;
                if (m.turns <= 0) {
                    expired.push(m);
                    this.modifiers.splice(i, 1);
                }
            }
        }
        if (expired.length > 0) {
            this.renderModifiers();
            this.renderActiveCharacterInfo();
            this.displayWeaponsInfo();
            this.renderTechniqueSelect('all');
            if (this.reactionMode) {
                var reflejos = this.getEffectiveCharacter().stats.reflejos || 0;
                var el = document.getElementById('reaction-reflejos-value');
                if (el) el.textContent = reflejos;
            }
            this.calculateResourcesUsed();
            this.renderPendingActions();
            this.setStatus('Modificadores expirados: ' + expired.map(function(m){ return m.key + ' ' + (m.value >= 0 ? '+'+m.value : m.value); }).join(', '));
        }
    },

    renderModifiers: function() {
        var container = document.getElementById('combat-modifiers-list');
        if (!container) return;
        if (!this.modifiers || this.modifiers.length === 0) {
            container.innerHTML = '<em>Sin modificadores activos</em>';
            return;
        }
        var html = '';
        for (var i=0;i<this.modifiers.length;i++) {
            var m = this.modifiers[i];
            var label = (m.targetType === 'resource') ? (m.key.toUpperCase() + ' (recurso)') : m.key.toUpperCase();
            html += '<div class="modifier-item" style="display:flex;justify-content:space-between;align-items:center;padding:6px;background:rgba(255,255,255,0.03);margin:4px 0;border-radius:4px;">';
            var turnsText = m.turns && m.turns > 0 ? (' - ' + m.turns + ' turno' + (m.turns>1?'s':'') + ' restantes') : ' - Permanente';
            html += '<div><strong>' + label + '</strong>: ' + (m.value >= 0 ? '+'+m.value : m.value) + turnsText + ' <span style="color:#aaa;margin-left:8px;">' + (m.message || '') + '</span> <em style="margin-left:8px;color:#ffd700;">(' + (m.scope === 'max' ? 'máx' : 'actual') + ')</em></div>';
            html += '<div><button onclick="CombatSystem.removeModifier(\''+m.id+'\')" style="background:#dc3545;color:white;border:none;padding:4px 8px;border-radius:4px;cursor:pointer;">Eliminar</button></div>';
            html += '</div>';
        }
        container.innerHTML = html;
    },

    getEffectiveCharacter: function() {
        var base = this.getActiveCharacter();
        if (!base) return null;
        var char = JSON.parse(JSON.stringify(base)); // clone
        // aplicar modificadores sobre stats
        for (var i=0;i<this.modifiers.length;i++) {
            var m = this.modifiers[i];
            if (m.targetType === 'stat' && char.stats && typeof char.stats[m.key] !== 'undefined') {
                char.stats[m.key] = (parseInt(char.stats[m.key]) || 0) + (parseInt(m.value) || 0);
            }
        }

        // aplicar modificadores sobre recursos máximos (scope=max)
        for (var j=0;j<this.modifiers.length;j++) {
            var mr = this.modifiers[j];
            if (mr.targetType === 'resource' && mr.scope === 'max' && char.resources) {
                // Normalizar clave: 'vida' -> 'vitalidad'
                var resourceKey = mr.key === 'vida' ? 'vitalidad' : mr.key;
                if (typeof mr.key !== 'undefined' && typeof char.resources[resourceKey] !== 'undefined') {
                    char.resources[resourceKey] = (parseInt(char.resources[resourceKey]) || 0) + (parseInt(mr.value) || 0);
                    if (char.resources[resourceKey] < 0) char.resources[resourceKey] = 0;
                }
            }
        }

        return char;
    },

    /**
     * Renderiza los objetivos detectados
     */
    renderTargets: function() {
        var container = document.getElementById('combat-targets');
        if (!container || !this.combatData) return;

        var participants = this.combatData.participants;
        if (!participants || participants.length === 0) {
            container.innerHTML = '<em>No se detectaron otros participantes</em>';
            return;
        }

        var html = '';
        for (var i = 0; i < participants.length; i++) {
            var p = participants[i];
            if (p.uid == this.combatData.current_uid) continue; // Excluir a mí mismo

            var isSelected = this.selectedTargets.indexOf(String(p.uid)) !== -1;
            html += '<div class="target-item' + (isSelected ? ' selected' : '') + '" onclick="CombatSystem.toggleTarget(\'' + p.uid + '\')" data-uid="' + p.uid + '">';
            html += '<img src="' + (p.avatar || '/images/default_avatar.png') + '" class="target-avatar" onerror="this.src=\'/images/default_avatar.png\'">';
            html += '<div class="target-info">';
            html += '<div class="target-name">' + (p.character ? p.character.nombre : p.username) + '</div>';
            html += '<div class="target-stats">UID: ' + p.uid;
            if (p.character) {
                html += ' | Nv.' + p.character.nivel;
            }
            html += '</div>';
            html += '</div>';
            html += '</div>';
        }

        if (html === '') {
            html = '<em>No hay otros participantes (solo tú)</em>';
        }

        container.innerHTML = html;
    },

    /**
     * Renderiza NPCs detectados
     */
    renderNpcs: function() {
        var container = document.getElementById('combat-npcs');
        if (!container || !this.combatData) return;

        var npcs = this.combatData.npcs;
        if (!npcs || npcs.length === 0) {
            container.innerHTML = '<em>Sin NPCs detectados</em>';
            return;
        }

        var html = '';
        for (var i = 0; i < npcs.length; i++) {
            var npc = npcs[i];
            var npcId = 'npc_' + i;
            var isSelected = this.selectedTargets.indexOf(npcId) !== -1;
            
            html += '<div class="npc-item' + (isSelected ? ' selected' : '') + '" onclick="CombatSystem.toggleTarget(\'' + npcId + '\')">';
            html += '<div class="target-info">';
            html += '<div class="target-name">🎭 ' + (npc.data.nombre || npc.npc_id) + '</div>';
            html += '<div class="target-stats">Controlado por UID: ' + npc.controller_uid + '</div>';
            html += '</div>';
            html += '</div>';
        }

        container.innerHTML = html;
    },

    /**
     * Renderiza el historial de combate
     */
    renderHistory: function() {
        var container = document.getElementById('combat-history');
        if (!container || !this.combatData) return;

        var log = this.combatData.combat_log;
        if (!log || log.length === 0) {
            container.innerHTML = '<em>Sin acciones previas en este tema</em>';
            return;
        }

        var html = '';
        var currentUid = this.combatData.current_uid;
        
        // Mostrar últimas 10 acciones
        var recentLog = log.slice(-10);
        for (var i = 0; i < recentLog.length; i++) {
            var action = recentLog[i];
            var isMyAction = action.user_uid == currentUid;
            var className = isMyAction ? 'my-action' : 'enemy-action';
            
            html += '<div class="history-item ' + className + '">';
            
            if (action.type === 'technique') {
                html += 'T' + action.turn + ': ' + (isMyAction ? 'Usaste' : 'UID ' + action.user_uid + ' usó') + ' [' + action.technique_id + ']';
            } else if (action.type === 'vida_change') {
                html += 'T' + action.turn + ': Cambio vida: ' + action.value;
            } else if (action.type === 'energia_change') {
                html += 'T' + action.turn + ': Cambio energía: ' + action.value;
            } else if (action.type === 'haki_change') {
                html += 'T' + action.turn + ': Cambio haki: ' + action.value;
            }
            
            html += '</div>';
        }

        container.innerHTML = html;
    },

    /**
     * Renderiza el selector de técnicas
     */
    renderTechniqueSelect: function(filter) {
        var select = document.getElementById('combat-technique-select');
        if (!select) return;
        
        var self = this;
        var html = '<option value="">-- Seleccionar técnica --</option>';
        
        for (var i = 0; i < this.myTechniques.length; i++) {
            var tec = this.myTechniques[i];
            
            // Aplicar filtro
            if (filter && filter !== 'all') {
                if (filter === 'mantenida') {
                    // Filtro especial para mantenidas
                    if (!self.isMaintainedTechnique(tec)) continue;
                } else {
                    var tipo = (tec.tipo || tec.clase || '').toLowerCase();
                    if (tipo.indexOf(filter) === -1) continue;
                }
            }
            
            // Verificar cooldown
            var onCooldown = this.cooldowns[tec.tid] && this.cooldowns[tec.tid] > 0;
            var cdText = onCooldown ? ' [CD: ' + this.cooldowns[tec.tid] + ']' : '';
            var disabled = onCooldown ? ' disabled' : '';
            
            // Indicador de mantenida
            var maintainedIcon = self.isMaintainedTechnique(tec) ? '🔄 ' : '';
            
            html += '<option value="' + tec.tid + '"' + disabled + '>';
            html += maintainedIcon + tec.nombre + ' (T' + tec.tier + ')' + cdText;
            html += '</option>';
        }

        select.innerHTML = html;
    },

    /**
     * Filtra técnicas
     */
    filterTechniques: function(filter) {
        // Actualizar botones activos
        var buttons = document.querySelectorAll('.filter-btn');
        buttons.forEach(function(btn) { btn.classList.remove('active'); });
        event.target.classList.add('active');
        
        this.renderTechniqueSelect(filter);
    },

    /**
     * Selecciona una técnica
     */
    selectTechnique: function(tecId) {
        var container = document.getElementById('combat-technique-info');
        if (!container) return;

        if (!tecId) {
            container.style.display = 'none';
            return;
        }

        var tec = this.myTechniques.find(function(t) { return t.tid === tecId; });
        if (!tec) {
            container.style.display = 'none';
            return;
        }

        var html = '<strong>' + tec.nombre + '</strong> - Tier ' + tec.tier + '<br>';
        html += '<span class="stat-label">' + (tec.clase || 'N/A') + ' | ' + (tec.tipo || 'N/A') + '</span><br>';
        
        // Detectar si es técnica mantenida
        var esMantenida = this.isMaintainedTechnique(tec);
        if (esMantenida) {
            html += '<div style="margin: 5px 0; padding: 5px 10px; background: rgba(255,152,0,0.2); border-radius: 4px; border-left: 3px solid #ff9800;">';
            html += '🔄 <strong style="color: #ff9800;">Técnica Mantenida</strong>';
            html += '</div>';
        }
        
        // Costes para técnicas mantenidas
        if (esMantenida) {
            // Primer turno: energia_turno + haki
            html += '<div style="margin-top: 5px;">';
            html += '<span style="color: #00c864; font-size: 11px; font-weight: bold;">Coste 1er turno:</span> ';
            if (tec.energia_turno > 0) {
                html += '<span class="tec-cost tec-cost-energia">⚡ ' + tec.energia_turno + '</span>';
            }
            if (tec.haki > 0) {
                html += '<span class="tec-cost tec-cost-haki">🔮 ' + tec.haki + '</span>';
            }
            html += '</div>';
            
            // Turnos siguientes: energia_turno + haki_turno
            if (tec.energia_turno > 0 || tec.haki_turno > 0) {
                html += '<div style="margin-top: 3px;">';
                html += '<span style="color: #ff9800; font-size: 11px;">Turnos siguientes:</span> ';
                if (tec.energia_turno > 0) {
                    html += '<span class="tec-cost tec-cost-energia">⚡ ' + tec.energia_turno + '/turno</span>';
                }
                if (tec.haki_turno > 0) {
                    html += '<span class="tec-cost tec-cost-haki">🔮 ' + tec.haki_turno + '/turno</span>';
                }
                html += '</div>';
            }
        } else {
            // Costes normales para técnicas no mantenidas
            html += '<div style="margin-top: 5px;">';
            html += '<span style="color: #aaa; font-size: 11px;">Coste:</span> ';
            if (tec.energia > 0) {
                html += '<span class="tec-cost tec-cost-energia">⚡ ' + tec.energia + '</span>';
            }
            if (tec.haki > 0) {
                html += '<span class="tec-cost tec-cost-haki">🔮 ' + tec.haki + '</span>';
            }
            html += '</div>';
        }
        
        if (tec.enfriamiento > 0) {
            html += '<span class="tec-cost tec-cost-cd">🕐 ' + tec.enfriamiento + ' turnos</span>';
        }
        
        // Detectar si es técnica de utilidad/pasiva (no usa daño de arma)
        var tipoTec = (tec.tipo || tec.clase || tec.estilo || '').toLowerCase();
        var esUtilidadOPasiva = tipoTec.indexOf('utilidad') !== -1 || 
                                tipoTec.indexOf('pasiv') !== -1 || 
                                tipoTec.indexOf('apoyo') !== -1 || 
                                tipoTec.indexOf('buff') !== -1 ||
                                tipoTec.indexOf('curacion') !== -1 ||
                                tipoTec.indexOf('mejora') !== -1;
        
        // Mostrar previsualización del daño
        var char = this.getEffectiveCharacter();
        if (tec.efectos && char) {
            var effectResult = this.parseAndCalculateEffect(tec.efectos, char);
            
            // Sumar daño de las armas si hay armas seleccionadas y no es utilidad/pasiva
            var weaponDamage = 0;
            var weaponDamageResults = [];
            var activeWeapons = this.selectedWeapons.filter(function(w) { return w !== null; });
            
            if (activeWeapons.length > 0 && !esUtilidadOPasiva) {
                // Calcular multiplicador según número de armas
                var multiplier = 1.0;
                if (activeWeapons.length === 2) multiplier = 0.75;
                else if (activeWeapons.length === 3) multiplier = 0.60;
                
                for (var i = 0; i < activeWeapons.length; i++) {
                    var weapon = activeWeapons[i];
                    if (weapon.dano) {
                        var weaponDamageResult = this.parseWeaponFormula(weapon.dano, char);
                        weaponDamage += Math.floor(weaponDamageResult.total * multiplier);
                        weaponDamageResults.push({
                            weapon: weapon,
                            result: weaponDamageResult,
                            damageWithMultiplier: Math.floor(weaponDamageResult.total * multiplier)
                        });
                    }
                }
            }
            
            var totalDamageWithWeapon = (effectResult ? effectResult.totalDamage : 0) + weaponDamage;
            
            if (totalDamageWithWeapon > 0) {
                html += '<div style="margin-top: 10px; padding: 2px; background: rgba(255,100,100,0.2); border-radius: 5px; border-left: 3px solid #ff6b6b;">';
                html += '<strong style="color: #ff6b6b;">⚔️ Daño Total Calculado:</strong><br>';
                html += '<span style="font-size: 18px; color: #ffd700; font-weight: bold;">' + totalDamageWithWeapon + '</span>';
                if (effectResult && effectResult.damageType) {
                    html += ' <span style="color: #aaa;">(' + effectResult.damageType + ')</span>';
                } else if (weaponDamageResult && weaponDamageResult.type) {
                    html += ' <span style="color: #aaa;">(' + weaponDamageResult.type + ')</span>';
                }
                
                // Desglose
                html += '<div style="font-size: 11px; color: #888; margin-top: 5px;">';
                
                // Daño de técnica
                if (effectResult && effectResult.totalDamage > 0) {
                    html += 'Técnica: ' + effectResult.damage;
                    if (effectResult.bonusFromPasivas > 0) {
                        html += ' + ' + effectResult.bonusFromPasivas + ' (pasivas)';
                    }
                    html += ' = ' + effectResult.totalDamage;
                }
                
                // Daño de arma
                if (weaponDamage > 0) {
                    if (effectResult && effectResult.totalDamage > 0) {
                        html += '<br>';
                    }
                    if (activeWeapons.length === 1) {
                        html += '🗡️ Arma (' + activeWeapons[0].nombre + '): ' + weaponDamage;
                    } else {
                        html += '🗡️ Armas (' + activeWeapons.length + ' × ' + Math.round(multiplier * 100) + '%): ' + weaponDamage;
                        html += '<div style="font-size: 10px; margin-left: 10px;">';
                        for (var i = 0; i < weaponDamageResults.length; i++) {
                            var wr = weaponDamageResults[i];
                            html += '• ' + wr.weapon.nombre + ': ' + wr.result.total + ' × ' + multiplier + ' = ' + wr.damageWithMultiplier + '<br>';
                        }
                        html += '</div>';
                    }
                }
                
                html += '</div>';
                
                // Pasivas aplicadas
                if (effectResult && effectResult.pasivaBonuses && effectResult.pasivaBonuses.length > 0) {
                    html += '<div style="font-size: 10px; color: #87ceeb; margin-top: 3px;">';
                    html += '📜 Pasivas: ';
                    var pasivaNames = effectResult.pasivaBonuses.map(function(p) { return p.nombre; });
                    html += pasivaNames.join(', ');
                    html += '</div>';
                }
                
                html += '</div>';
            }
        } else if (activeWeapons.length > 0 && !esUtilidadOPasiva && char) {
            // Solo daño de armas si la técnica no tiene efectos de daño
            var multiplier = 1.0;
            if (activeWeapons.length === 2) multiplier = 0.75;
            else if (activeWeapons.length === 3) multiplier = 0.60;
            
            var totalWeaponDamage = 0;
            var weaponDetails = [];
            
            for (var i = 0; i < activeWeapons.length; i++) {
                var weapon = activeWeapons[i];
                if (weapon.dano) {
                    var weaponDamageResult = this.parseWeaponFormula(weapon.dano, char);
                    var damageWithMult = Math.floor(weaponDamageResult.total * multiplier);
                    totalWeaponDamage += damageWithMult;
                    weaponDetails.push({
                        weapon: weapon,
                        result: weaponDamageResult,
                        damageWithMultiplier: damageWithMult
                    });
                }
            }
            
            if (totalWeaponDamage > 0) {
                html += '<div style="margin-top: 10px; padding: 2px; background: rgba(255,100,100,0.2); border-radius: 5px; border-left: 3px solid #ff6b6b;">';
                html += '<strong style="color: #ff6b6b;">⚔️ Daño de Arma' + (activeWeapons.length > 1 ? 's' : '') + ':</strong><br>';
                html += '<span style="font-size: 18px; color: #ffd700; font-weight: bold;">' + totalWeaponDamage + '</span>';
                if (weaponDetails[0].result.type) {
                    html += ' <span style="color: #aaa;">(' + weaponDetails[0].result.type + ')</span>';
                }
                html += '<div style="font-size: 11px; color: #888; margin-top: 5px;">';
                if (activeWeapons.length === 1) {
                    html += '🗡️ ' + activeWeapons[0].nombre;
                } else {
                    html += '🗡️ ' + activeWeapons.length + ' armas × ' + Math.round(multiplier * 100) + '%<br>';
                    for (var i = 0; i < weaponDetails.length; i++) {
                        var wd = weaponDetails[i];
                        html += '• ' + wd.weapon.nombre + ': ' + wd.result.total + ' × ' + multiplier + ' = ' + wd.damageWithMultiplier + '<br>';
                    }
                }
                html += '</div>';
                html += '</div>';
            }
        }
        
        // Mostrar efecto original si existe
        if (tec.efectos) {
            html += '<div style="margin-top: 8px; font-size: 11px; color: #aaa; font-style: italic;">';
            html += '📋 ' + tec.efectos;
            html += '</div>';
        }

        container.innerHTML = html;
        container.style.display = 'block';
    },

    /**
     * Alterna selección de objetivo
     */
    toggleTarget: function(targetId) {
        targetId = String(targetId); // Asegurar que siempre sea string
        var idx = this.selectedTargets.indexOf(targetId);
        if (idx !== -1) {
            this.selectedTargets.splice(idx, 1);
        } else {
            if (this.selectedTargets.length < this.config.maxTargets) {
                this.selectedTargets.push(targetId);
            }
        }
        this.renderTargets();
        this.renderNpcs();
    },

    /**
     * Añade objetivo manual
     */
    addManualTarget: function() {
        var input = document.getElementById('combat-manual-uid');
        var uid = parseInt(input.value);
        
        if (!uid || uid <= 0) {
            alert('Por favor, introduce un UID válido');
            return;
        }

        // Verificar si ya existe
        var exists = this.combatData.participants.some(function(p) { return p.uid == uid; });
        if (!exists) {
            // Añadir participante manual
            this.combatData.participants.push({
                uid: uid,
                username: 'UID ' + uid,
                avatar: null,
                character: null,
                type: 'manual'
            });
        }

        this.selectedTargets.push(String(uid));
        this.renderTargets();
        input.value = '';
        
        this.setStatus('Objetivo UID ' + uid + ' añadido');
    },

    /**
     * Calcula los cooldowns activos
     */
    calculateCooldowns: function() {
        if (!this.combatData || !this.combatData.combat_log) return;

        var currentTurn = this.combatData.my_turns;
        this.cooldowns = {};

        // Buscar técnicas usadas por mí
        var myActions = this.combatData.combat_log.filter(function(a) {
            return a.type === 'technique' && a.user_uid == CombatSystem.combatData.current_uid;
        });

        for (var i = 0; i < myActions.length; i++) {
            var action = myActions[i];
            var tec = this.myTechniques.find(function(t) { return t.tid === action.technique_id; });
            
            if (tec && tec.enfriamiento > 0) {
                var turnsElapsed = currentTurn - action.turn;
                var remainingCd = tec.enfriamiento - turnsElapsed;
                
                if (remainingCd > 0) {
                    // Guardar el mayor cooldown si la técnica se usó varias veces
                    if (!this.cooldowns[tec.tid] || this.cooldowns[tec.tid] < remainingCd) {
                        this.cooldowns[tec.tid] = remainingCd;
                    }
                }
            }
        }

        this.renderCooldowns();
    },

    /**
     * Renderiza cooldowns activos
     */
    renderCooldowns: function() {
        var container = document.getElementById('combat-cooldowns');
        if (!container) return;

        var hasCD = Object.keys(this.cooldowns).length > 0;
        if (!hasCD) {
            container.innerHTML = '<em>Sin técnicas en enfriamiento</em>';
            return;
        }

        var html = '';
        for (var tecId in this.cooldowns) {
            var tec = this.myTechniques.find(function(t) { return t.tid === tecId; });
            if (tec) {
                html += '<div class="cooldown-item">';
                html += '<span>' + tec.nombre + '</span>';
                html += '<span class="cooldown-turns">' + this.cooldowns[tecId] + ' turnos</span>';
                html += '</div>';
            }
        }

        container.innerHTML = html;
    },

    /**
     * Calcula recursos usados
     */
    calculateResourcesUsed: function() {
        var effectiveChar = this.getEffectiveCharacter();
        if (!effectiveChar) return;

        var energiaMax = effectiveChar.resources.energia || 0;
        var hakiMax = effectiveChar.resources.haki || 0;
        var vidaMax = effectiveChar.resources.vitalidad || 0;
        
        // Por defecto, recursos completos
        var energiaActual = energiaMax;
        var hakiActual = hakiMax;
        var vidaActual = vidaMax;
        
        // Determinar qué identificador buscar en los posts
        var searchId = null;
        if (this.selectedCharacter && this.selectedCharacter !== 'main') {
            // Si es un NPC, buscar por npc_id
            searchId = effectiveChar.npc_id || null;
        }
        
        // Buscar el último post del usuario con [extra=Resumen Personaje] o tags individuales
        if (!searchId) {
            // No es NPC: usar getters que ya aplican modificadores
            energiaActual = this.getCharacterCurrentEnergia();
            hakiActual = this.getCharacterCurrentHaki();
            vidaActual = this.getCharacterCurrentHP();
        } else if (this.combatData.posts) {
            var currentUid = this.combatData.current_uid;
            var foundEnergia = false;
            var foundHaki = false;
            var foundVida = false;
            
            for (var i = this.combatData.posts.length - 1; i >= 0; i--) {
                var post = this.combatData.posts[i];
                if (post.uid != currentUid) continue;
                
                var content = post.message || '';
                
                // Si es un NPC, verificar que el post menciona a ese NPC
                if (searchId) {
                    var npcRegex = new RegExp('\\[npc=' + searchId.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\]', 'i');
                    if (!npcRegex.test(content)) {
                        continue; // Este post no menciona al NPC, saltar
                    }
                }
                
                // Primero buscar en [extra=Resumen Personaje] o [extra=Resumen NPC] (más confiable)
                var resumenPattern = searchId ? 
                    /\[extra=Resumen (?:NPC|Personaje)\]([\s\S]*?)\[\/extra\]/i :
                    /\[extra=Resumen Personaje\]([\s\S]*?)\[\/extra\]/i;
                    
                var resumenMatch = content.match(resumenPattern);
                if (resumenMatch) {
                    var resumenContent = resumenMatch[1];
                    
                    // Buscar energía en resumen
                    if (!foundEnergia) {
                        var energiaResumen = resumenContent.match(/\[energia=(-?\d+)\s*,\s*(\d+)\]/i);
                        if (energiaResumen) {
                            energiaActual = parseInt(energiaResumen[1]);
                            foundEnergia = true;
                        }
                    }
                    
                    // Buscar haki en resumen
                    if (!foundHaki) {
                        var hakiResumen = resumenContent.match(/\[haki=(-?\d+)\s*,\s*(\d+)\]/i);
                        if (hakiResumen) {
                            hakiActual = parseInt(hakiResumen[1]);
                            foundHaki = true;
                        }
                    }
                    
                    // Buscar vida en resumen
                    if (!foundVida) {
                        var vidaResumen = resumenContent.match(/\[vida=(-?\d+)\s*,\s*(\d+)\]/i);
                        if (vidaResumen) {
                            vidaActual = parseInt(vidaResumen[1]);
                            foundVida = true;
                        }
                    }
                }
                
                // Fallback: buscar tags individuales si no se encontraron en resumen
                if (!foundEnergia) {
                    var energiaMatch = content.match(/\[energia=(-?\d+)\s*,\s*(\d+)\]/i);
                    if (energiaMatch) {
                        energiaActual = parseInt(energiaMatch[1]);
                        foundEnergia = true;
                    }
                }
                
                if (!foundHaki) {
                    var hakiMatch = content.match(/\[haki=(-?\d+)\s*,\s*(\d+)\]/i);
                    if (hakiMatch) {
                        hakiActual = parseInt(hakiMatch[1]);
                        foundHaki = true;
                    }
                }
                
                if (!foundVida) {
                    var vidaMatch = content.match(/\[vida=(-?\d+)\s*,\s*(\d+)\]/i);
                    if (vidaMatch) {
                        vidaActual = parseInt(vidaMatch[1]);
                        foundVida = true;
                    }
                }
                
                // Si encontramos todos, dejamos de buscar
                if (foundEnergia && foundHaki && foundVida) {
                    break;
                }
            }
        }
        
        // Si era NPC, aplicar modificadores scope=current detectados
        if (searchId) {
            for (var mi=0; mi < this.modifiers.length; mi++) {
                var mm = this.modifiers[mi];
                if (mm.targetType === 'resource' && mm.scope === 'current') {
                    if (mm.key === 'energia') energiaActual += parseInt(mm.value) || 0;
                    if (mm.key === 'haki') hakiActual += parseInt(mm.value) || 0;
                    if (mm.key === 'vida') vidaActual += parseInt(mm.value) || 0;
                }
            }
        }
        
        // Guardar valores actuales para uso posterior (NO recortarlos por encima del máximo cuando provienen de 'current' modifiers)
        this.currentResources = {
            energia: Math.max(0, energiaActual),
            haki: Math.max(0, hakiActual),
            vida: Math.max(0, vidaActual)
        };
        
        // Calcular recursos gastados como diferencia entre máximo y actual (permitir valores negativos si current > max)
        this.resourcesUsed = { 
            energia: energiaMax - this.currentResources.energia, 
            haki: hakiMax - this.currentResources.haki 
        };

        // Actualizar display de recursos actuales
        document.getElementById('combat-energia-current').textContent = this.currentResources.energia;
        document.getElementById('combat-haki-current').textContent = this.currentResources.haki;
        document.getElementById('combat-vida-current').textContent = this.currentResources.vida;
    },

    /**
     * Añade una acción pendiente
     */
    addAction: function() {
        var tecSelect = document.getElementById('combat-technique-select');
        var tecId = tecSelect ? tecSelect.value : null;

        if (!tecId) {
            alert('Selecciona una técnica primero');
            return;
        }

        var tec = this.myTechniques.find(function(t) { return t.tid === tecId; });
        if (!tec) return;
        
        // Detectar si es técnica de utilidad o pasiva (no requiere objetivo ni arma)
        var tipoTec = (tec.tipo || tec.clase || tec.estilo || '').toLowerCase();
        var esUtilidadOPasiva = tipoTec.indexOf('utilidad') !== -1 || 
                                tipoTec.indexOf('pasiv') !== -1 || 
                                tipoTec.indexOf('apoyo') !== -1 || 
                                tipoTec.indexOf('buff') !== -1 ||
                                tipoTec.indexOf('curacion') !== -1 ||
                                tipoTec.indexOf('mejora') !== -1;
        
        // Obtener armas activas
        var activeWeapons = this.selectedWeapons.filter(function(w) { return w !== null; });
        
        // Requerir arma solo si NO es utilidad/pasiva
        if (activeWeapons.length === 0 && !esUtilidadOPasiva) {
            alert('Selecciona al menos un arma antes de añadir una acción de combate (las técnicas de utilidad/pasivas no lo requieren)');
            return;
        }
        
        // Requerir objetivo solo si NO es utilidad/pasiva
        if (this.selectedTargets.length === 0 && !esUtilidadOPasiva) {
            alert('Selecciona al menos un objetivo (las técnicas de utilidad/pasivas no lo requieren)');
            return;
        }

        // Verificar recursos
        var currentEnergia = parseInt(document.getElementById('combat-energia-current').textContent);
        var currentHaki = parseInt(document.getElementById('combat-haki-current').textContent);

        if (tec.energia > currentEnergia) {
            alert('No tienes suficiente energía para esta técnica');
            return;
        }
        if (tec.haki > currentHaki) {
            alert('No tienes suficiente haki para esta técnica');
            return;
        }

        // Añadir acción con armas seleccionadas
        this.pendingActions.push({
            technique: tec,
            targets: this.selectedTargets.slice(),
            weapons: esUtilidadOPasiva ? [] : activeWeapons.slice(),
            actionType: 'technique'
        });
        
        // Si es técnica mantenida, iniciar el tracking
        if (this.isMaintainedTechnique(tec)) {
            this.startMaintainedTechnique(tec);
        }

        // Actualizar recursos temporalmente
        document.getElementById('combat-energia-current').textContent = currentEnergia - tec.energia;
        document.getElementById('combat-haki-current').textContent = currentHaki - tec.haki;

        // Limpiar selección
        this.selectedTargets = [];
        tecSelect.value = '';
        document.getElementById('combat-technique-info').style.display = 'none';

        this.renderPendingActions();
        this.renderTargets();
        this.renderNpcs();
        this.setStatus('Acción añadida: ' + tec.nombre);
    },

    /**
     * Añade un golpe básico (usa daño del arma)
     */
    addBasicAttack: function() {
        var activeWeapons = this.selectedWeapons.filter(function(w) { return w !== null; });
        
        if (activeWeapons.length === 0) {
            alert('Selecciona al menos un arma antes de realizar un golpe básico');
            return;
        }
        
        if (this.selectedTargets.length === 0) {
            alert('Selecciona al menos un objetivo para el golpe básico');
            return;
        }

        // Calcular multiplicador según número de armas
        var multiplier = 1.0;
        if (activeWeapons.length === 2) multiplier = 0.75;
        else if (activeWeapons.length === 3) multiplier = 0.60;

        // Calcular daño total de todas las armas
        var totalDamage = 0;
        var damageResults = [];
        var damageTypes = [];
        
        for (var i = 0; i < activeWeapons.length; i++) {
            var weapon = activeWeapons[i];
            var damageResult = null;
            
            var char = this.getEffectiveCharacter();
            if (weapon.dano && char) {
                damageResult = this.parseWeaponFormula(weapon.dano, char);
                totalDamage += Math.floor(damageResult.total * multiplier);
                damageResults.push(damageResult);
                
                // Extraer tipo de daño
                if (damageResult.type && damageTypes.indexOf(damageResult.type) === -1) {
                    damageTypes.push(damageResult.type);
                }
            }
        }

        // Añadir acción de golpe básico
        this.pendingActions.push({
            technique: null,
            targets: this.selectedTargets.slice(),
            weapons: activeWeapons.slice(),
            actionType: 'basic_attack',
            damageResults: damageResults,
            totalDamage: totalDamage,
            damageTypes: damageTypes,
            weaponMultiplier: multiplier
        });

        // Limpiar selección de objetivos
        this.selectedTargets = [];

        this.renderPendingActions();
        this.renderTargets();
        this.renderNpcs();
        this.setStatus('Golpe básico añadido con ' + activeWeapons.length + ' arma' + (activeWeapons.length > 1 ? 's' : ''));
    },

    /**
     * Añade un bloqueo básico (usa bloqueo del arma)
     */
    addBasicBlock: function() {
        var activeWeapons = this.selectedWeapons.filter(function(w) { return w !== null; });
        
        if (activeWeapons.length === 0) {
            alert('Selecciona al menos un arma antes de realizar un bloqueo básico');
            return;
        }
        
        if (this.selectedTargets.length === 0) {
            alert('Selecciona al menos un objetivo del que te defiendes');
            return;
        }

        // Calcular multiplicador según número de armas
        var multiplier = 1.0;
        if (activeWeapons.length === 2) multiplier = 0.75;
        else if (activeWeapons.length === 3) multiplier = 0.60;

        // Calcular bloqueo total de todas las armas
        var totalBlock = 0;
        var blockResults = [];
        
        for (var i = 0; i < activeWeapons.length; i++) {
            var weapon = activeWeapons[i];
            var blockResult = null;
            
            var char = this.getEffectiveCharacter();
            if (weapon.bloqueo && char) {
                blockResult = this.parseWeaponFormula(weapon.bloqueo, char);
                totalBlock += Math.floor(blockResult.total * multiplier);
                blockResults.push(blockResult);
            }
        }

        // Añadir acción de bloqueo básico
        this.pendingActions.push({
            technique: null,
            targets: this.selectedTargets.slice(),
            weapons: activeWeapons.slice(),
            actionType: 'basic_block',
            blockResults: blockResults,
            totalBlock: totalBlock,
            weaponMultiplier: multiplier
        });

        // Limpiar selección de objetivos
        this.selectedTargets = [];

        this.renderPendingActions();
        this.renderTargets();
        this.renderNpcs();
        this.setStatus('Bloqueo básico añadido con ' + activeWeapons.length + ' arma' + (activeWeapons.length > 1 ? 's' : ''));
    },

    /**
     * Renderiza acciones pendientes
     */
    renderPendingActions: function() {
        var container = document.getElementById('combat-pending-actions');
        if (!container) return;

        if (this.pendingActions.length === 0) {
            container.innerHTML = '<em>Sin acciones pendientes</em>';
            return;
        }

        var html = '';
        for (var i = 0; i < this.pendingActions.length; i++) {
            var action = this.pendingActions[i];
            var actionName = '';
            var weaponText = '';
            var targetText = '';
            
            // Determinar nombre de la acción
            if (action.actionType === 'reaction') {
                actionName = '🛡️ ' + action.reaction.name;
                targetText = 'vs ' + action.attackerName;
                if (action.technique) {
                    actionName += ' + ' + action.technique.nombre;
                }
                if (action.finalDamage > 0) {
                    actionName += ' (-' + action.finalDamage + ' HP)';
                }
            } else if (action.actionType === 'basic_attack') {
                actionName = '⚔️ Golpe Básico';
                targetText = action.targets.length + ' objetivo(s)';
                if (action.totalDamage) {
                    actionName += ' (' + action.totalDamage + ' daño)';
                }
                // Mostrar múltiples armas
                if (action.weapons && action.weapons.length > 0) {
                    if (action.weapons.length === 1) {
                        weaponText = ' [' + action.weapons[0].nombre + ']';
                    } else {
                        weaponText = ' [' + action.weapons.length + ' armas × ' + Math.round(action.weaponMultiplier * 100) + '%]';
                    }
                }
            } else if (action.actionType === 'basic_block') {
                actionName = '🛡️ Bloqueo Básico';
                targetText = action.targets.length + ' objetivo(s)';
                if (action.totalBlock) {
                    actionName += ' (' + action.totalBlock + ' mitigación)';
                }
                // Mostrar múltiples armas
                if (action.weapons && action.weapons.length > 0) {
                    if (action.weapons.length === 1) {
                        weaponText = ' [' + action.weapons[0].nombre + ']';
                    } else {
                        weaponText = ' [' + action.weapons.length + ' armas × ' + Math.round(action.weaponMultiplier * 100) + '%]';
                    }
                }
            } else {
                actionName = action.technique.nombre;
                targetText = action.targets.length + ' objetivo(s)';
            }
            
            // Mostrar arma si existe
            if (action.weapon) {
                weaponText = ' [' + action.weapon.nombre + ']';
            }
            
            html += '<div class="action-item">';
            html += '<span>' + actionName + weaponText + ' → ' + targetText + '</span>';
            html += '<span class="remove-action" onclick="CombatSystem.removeAction(' + i + ')">✕</span>';
            html += '</div>';
        }

        container.innerHTML = html;
    },

    /**
     * Elimina una acción pendiente
     */
    removeAction: function(index) {
        var action = this.pendingActions[index];
        if (!action) return;

        // Restaurar recursos según tipo de acción
        if (action.actionType === 'reaction') {
            // Restaurar recursos de la reacción
            if (action.technique) {
                var esMantenida = this.isMaintainedTechnique(action.technique);
                if (esMantenida) {
                    this.resourcesUsed.energia -= (action.technique.energia_turno || 0);
                    this.resourcesUsed.haki -= (action.technique.haki || 0);
                    // Eliminar de mantenidas si se inició
                    for (var i = 0; i < this.maintainedThisTurn.length; i++) {
                        if (this.maintainedThisTurn[i].tid === action.technique.tid) {
                            this.maintainedThisTurn.splice(i, 1);
                            break;
                        }
                    }
                } else {
                    this.resourcesUsed.energia -= (action.technique.energia || 0);
                    this.resourcesUsed.haki -= (action.technique.haki || 0);
                }
            }
        } else if (action.technique) {
            // Restaurar recursos de técnicas normales
            var currentEnergia = parseInt(document.getElementById('combat-energia-current').textContent);
            var currentHaki = parseInt(document.getElementById('combat-haki-current').textContent);
            document.getElementById('combat-energia-current').textContent = currentEnergia + (action.technique.energia || 0);
            document.getElementById('combat-haki-current').textContent = currentHaki + (action.technique.haki || 0);
        }

        this.pendingActions.splice(index, 1);
        this.renderPendingActions();
        this.setStatus('Acción eliminada');
    },

    /**
     * Limpia todas las acciones
     */
    clearActions: function() {
        // Restaurar todos los recursos (usar máximos con modificadores)
        if (this.combatData && this.combatData.my_character) {
            var char = this.getEffectiveCharacter();
            document.getElementById('combat-energia-current').textContent = 
                (char.resources.energia || 0) - this.resourcesUsed.energia;
            document.getElementById('combat-haki-current').textContent = 
                (char.resources.haki || 0) - this.resourcesUsed.haki;
        }

        this.pendingActions = [];
        this.selectedTargets = [];
        this.renderPendingActions();
        this.renderTargets();
        this.renderNpcs();
        this.setStatus('Acciones limpiadas');
    },

    /**
     * Genera el código BBCode para el post
     */
    generateCode: function() {
        if (this.pendingActions.length === 0) {
            alert('No hay acciones pendientes para generar código');
            return;
        }

        var code = '[extra=Belico]\n';
        var char = this.getEffectiveCharacter(); // Usar personaje o NPC activo (con modificadores)
        var self = this;
        
        // Calcular recursos actuales después de todas las acciones
        var energiaMax = char ? char.resources.energia : 0;
        var hakiMax = char ? char.resources.haki : 0;
        var vidaMax = char ? char.resources.vitalidad : 0;
        
        // Recursos ya gastados en el tema
        var energiaGastada = this.resourcesUsed.energia;
        var hakiGastado = this.resourcesUsed.haki;
        
        // Calcular costos de las nuevas acciones
        var nuevaEnergiaGastada = 0;
        var nuevoHakiGastado = 0;
        
        // Tracking de daño recibido total (para reacciones)
        var totalDamageReceived = 0;
        
        for (var i = 0; i < this.pendingActions.length; i++) {
            var action = this.pendingActions[i];
            
            // Obtener nombres de objetivos
            var targetNames = this.getTargetNames(action.targets);
            
            // Generar código según tipo de acción
            if (action.actionType === 'reaction') {
                // REACCIÓN DEFENSIVA
                code += '[extra=Reacción Defensiva]\n';
                
                // Si la reacción es de un NPC, añadir el tag
                var isNpc = this.selectedCharacter && this.selectedCharacter !== 'main';
                if (isNpc && char && char.npc_id) {
                    code += '[npc=' + char.npc_id + ']\n\n';
                }
                
                code += '[b]REACCIÓN A ATAQUE[/b]\n\n';
                
                // Si es NPC, indicarlo
                if (isNpc && char) {
                    code += '[b]Reaccionando:[/b] ' + char.nombre + ' (NPC)\n\n';
                }
                
                // Datos del ataque
                code += '[b]Ataque recibido de:[/b] ' + action.attackerName + '\n';
                code += 'TA del atacante: ' + action.attackerTA + '\n';
                code += 'Daño del ataque: ' + action.attackerDamage + '\n\n';
                
                // Cálculo con modificador de reflejos
                code += '[b]Cálculo de reacción:[/b]\n';
                code += 'Reflejos: ' + action.reflejos;
                
                // Mostrar modificador si existe
                if (action.reflejosModifier && action.reflejosModifier !== 0) {
                    code += ' ' + (action.reflejosModifier > 0 ? '+' : '') + action.reflejosModifier;
                    code += ' (' + action.reflejosModifierReason + ')';
                    code += ' = ' + (action.reflejos + action.reflejosModifier);
                }
                
                var reflejosTotal = action.reflejos + (action.reflejosModifier || 0);
                code += '\n' + reflejosTotal + ' - TA (' + action.attackerTA + ') = [b]' + action.reactionResult + '[/b]\n\n';
                
                // Tipo de reacción
                code += '[b]Tipo de reacción:[/b] ' + action.reaction.name + '\n';
                code += action.reaction.effect + '\n\n';
                
                // Según el tipo de reacción
                if (action.reaction.category === 'esquiva') {
                    var damageEvaded = Math.floor(action.attackerDamage * (action.reaction.percent / 100));
                    code += '[b]Resultado:[/b]\n';
                    code += 'Daño esquivado (' + action.reaction.percent + '%): ' + damageEvaded + '\n';
                    code += '[b]DAÑO RECIBIDO: ' + action.finalDamage + '[/b]\n';
                    totalDamageReceived += action.finalDamage;
                    
                } else if (action.reaction.category === 'none') {
                    code += '[b]Resultado:[/b]\n';
                    code += '[color=red]No puedes reaccionar al ataque.[/color]\n';
                    code += '[b]DAÑO RECIBIDO: ' + action.attackerDamage + '[/b]\n';
                    code += 'Los efectos del ataque se aplican completamente.\n';
                    totalDamageReceived += action.attackerDamage;
                    
                } else if (action.reaction.category === 'choque') {
                    var tec = action.technique;
                    if (tec) {
                        // Si es mantenida (por tipo o por estar en activeMaintained), añadir tag
                        var estaEnMaintained = this.activeMaintained.some(function(m) { return m.tid === tec.tid; });
                        if (this.isMaintainedTechnique(tec) || estaEnMaintained) {
                            code += '[mantenida=' + tec.tid + ']\n';
                        }
                        code += '[tecnica=' + tec.tid + ']\n\n';
                    }
                    
                    if (action.weapon) {
                        code += 'Arma: ' + action.weapon.nombre;
                        if (action.weapon.tier > 0) {
                            code += ' (Tier ' + action.weapon.tier + ')';
                        }
                        code += '\n';
                    }
                    
                    // Mostrar desglose del cálculo de daño
                    code += '\n[b]Cálculo de Daño de Choque:[/b]\n';
                    var totalDamageBase = 0;
                    
                    // Daño de la técnica
                    if (action.effectResult && action.effectResult.totalDamage > 0) {
                        if (action.effectResult.breakdown && action.effectResult.breakdown.length > 0) {
                            var breakdownTexts = [];
                            for (var j = 0; j < action.effectResult.breakdown.length; j++) {
                                var b = action.effectResult.breakdown[j];
                                breakdownTexts.push(b.text);
                            }
                            code += 'Técnica: ' + breakdownTexts.join(' + ') + '\n';
                        }
                        code += 'Daño técnica: ' + action.effectResult.damage;
                        if (action.effectResult.bonusFromPasivas > 0) {
                            code += ' + ' + action.effectResult.bonusFromPasivas + ' (pasivas) = ' + action.effectResult.totalDamage;
                        }
                        code += '\n';
                        totalDamageBase += action.effectResult.totalDamage;
                        
                        // Pasivas aplicadas
                        if (action.effectResult.pasivaBonuses && action.effectResult.pasivaBonuses.length > 0) {
                            code += '--- Bonificadores de Pasivas ---\n';
                            for (var j = 0; j < action.effectResult.pasivaBonuses.length; j++) {
                                var p = action.effectResult.pasivaBonuses[j];
                                code += '• ' + p.nombre + ' (' + p.valor + '): +' + p.bonus + '\n';
                            }
                        }
                    }
                    
                    // Daño del arma
                    if (action.weaponDamageResult && action.weaponDamageResult.total > 0 && action.weapon) {
                        code += '\n🗡️ Arma: ' + action.weapon.nombre + '\n';
                        if (action.weaponDamageResult.breakdown && action.weaponDamageResult.breakdown.length > 0) {
                            var weaponBreakdown = [];
                            for (var j = 0; j < action.weaponDamageResult.breakdown.length; j++) {
                                var b = action.weaponDamageResult.breakdown[j];
                                if (b.label === 'Base') {
                                    weaponBreakdown.push('Base: ' + b.value);
                                } else {
                                    weaponBreakdown.push(b.label + ' (' + b.multiplier + ' × ' + b.statValue + ' = ' + b.value + ')');
                                }
                            }
                            code += 'Cálculo arma: ' + weaponBreakdown.join(' + ') + '\n';
                        }
                        code += 'Daño arma: ' + action.weaponDamageResult.total;
                        if (action.weaponDamageResult.type) {
                            code += ' de ' + action.weaponDamageResult.type;
                        }
                        code += '\n';
                        totalDamageBase += action.weaponDamageResult.total;
                    }
                    
                    // Total base y efectivo
                    code += '\nDaño base total: ' + totalDamageBase + '\n';
                    code += '[b]Daño de choque (' + action.reaction.percent + '%):[/b] ' + action.counterDamage + '\n\n';
                    
                    // Resultado final
                    code += '[b]Resultado:[/b]\n';
                    code += 'Daño del atacante: ' + action.attackerDamage + '\n';
                    code += 'Tu contraataque: -' + action.counterDamage + '\n';
                    code += '[b]DAÑO RECIBIDO:[/b] ' + action.finalDamage + '\n';
                    if (action.counterDamage > 0) {
                        code += '[b]DAÑO AL OPONENTE:[/b] ' + action.counterDamage + '\n';
                    }
                    totalDamageReceived += action.finalDamage;
                    
                    // Costo de técnica (ya se añadió en generateReactionCode)
                    
                } else if (action.reaction.category === 'bloqueo') {
                    var tec = action.technique;
                    if (tec) {
                        // Si es mantenida (por tipo o por estar en activeMaintained), añadir tag
                        var estaEnMaintained = this.activeMaintained.some(function(m) { return m.tid === tec.tid; });
                        if (this.isMaintainedTechnique(tec) || estaEnMaintained) {
                            code += '[mantenida=' + tec.tid + ']\n';
                        }
                        code += '[tecnica=' + tec.tid + ']\n\n';
                    }
                    
                    if (action.weapon) {
                        code += 'Arma: ' + action.weapon.nombre;
                        if (action.weapon.tier > 0) {
                            code += ' (Tier ' + action.weapon.tier + ')';
                        }
                        code += '\n';
                    }
                    
                    // Mostrar desglose del cálculo de mitigación
                    code += '\n[b]Cálculo de Mitigación de Bloqueo:[/b]\n';
                    var totalMitigationBase = 0;
                    
                    // Mitigación de la técnica
                    if (action.effectResult && action.effectResult.totalDamage > 0) {
                        if (action.effectResult.breakdown && action.effectResult.breakdown.length > 0) {
                            var breakdownTexts = [];
                            for (var j = 0; j < action.effectResult.breakdown.length; j++) {
                                var b = action.effectResult.breakdown[j];
                                breakdownTexts.push(b.text);
                            }
                            code += 'Técnica: ' + breakdownTexts.join(' + ') + '\n';
                        }
                        code += 'Mitigación técnica: ' + action.effectResult.damage;
                        if (action.effectResult.bonusFromPasivas > 0) {
                            code += ' + ' + action.effectResult.bonusFromPasivas + ' (pasivas) = ' + action.effectResult.totalDamage;
                        }
                        code += '\n';
                        totalMitigationBase += action.effectResult.totalDamage;
                        
                        // Pasivas aplicadas
                        if (action.effectResult.pasivaBonuses && action.effectResult.pasivaBonuses.length > 0) {
                            code += '--- Bonificadores de Pasivas ---\n';
                            for (var j = 0; j < action.effectResult.pasivaBonuses.length; j++) {
                                var p = action.effectResult.pasivaBonuses[j];
                                code += '• ' + p.nombre + ' (' + p.valor + '): +' + p.bonus + '\n';
                            }
                        }
                    }
                    
                    // Bloqueo del arma
                    if (action.weaponBlockResult && action.weaponBlockResult.total > 0 && action.weapon) {
                        code += '\nBloqueo de Arma: ' + action.weapon.nombre + '\n';
                        if (action.weaponBlockResult.breakdown && action.weaponBlockResult.breakdown.length > 0) {
                            var weaponBreakdown = [];
                            for (var j = 0; j < action.weaponBlockResult.breakdown.length; j++) {
                                var b = action.weaponBlockResult.breakdown[j];
                                if (b.label === 'Base') {
                                    weaponBreakdown.push('Base: ' + b.value);
                                } else {
                                    weaponBreakdown.push(b.label + ' (' + b.multiplier + ' × ' + b.statValue + ' = ' + b.value + ')');
                                }
                            }
                            code += 'Cálculo bloqueo: ' + weaponBreakdown.join(' + ') + '\n';
                        }
                        code += 'Mitigación arma: ' + action.weaponBlockResult.total;
                        if (action.weaponBlockResult.type) {
                            code += ' de ' + action.weaponBlockResult.type;
                        }
                        code += '\n';
                        totalMitigationBase += action.weaponBlockResult.total;
                    }
                    
                    // Total base y efectivo
                    code += '\nMitigación base total: ' + totalMitigationBase + '\n';
                    code += '[b]Mitigación efectiva (' + action.reaction.percent + '%):[/b] ' + action.mitigationTotal + '\n\n';
                    
                    // Resultado final
                    code += '[b]Resultado:[/b]\n';
                    code += 'Daño del atacante: ' + action.attackerDamage + '\n';
                    code += 'Tu mitigación: -' + action.mitigationTotal + '\n';
                    code += '[b]DAÑO RECIBIDO:[/b] ' + action.finalDamage + '\n';
                    totalDamageReceived += action.finalDamage;
                }
                
                code += '[/extra]\n\n';
                
                // Acumular costos de la técnica de reacción
                if (action.technique) {
                    var tecReac = action.technique;
                    if (this.isMaintainedTechnique(tecReac)) {
                        // Mantenida: primer turno usa energia_turno + haki
                        if (tecReac.energia_turno > 0) {
                            nuevaEnergiaGastada += tecReac.energia_turno;
                        }
                        if (tecReac.haki > 0) {
                            nuevoHakiGastado += tecReac.haki;
                        }
                    } else {
                        // Normal: usa energia + haki
                        if (tecReac.energia > 0) {
                            nuevaEnergiaGastada += tecReac.energia;
                        }
                        if (tecReac.haki > 0) {
                            nuevoHakiGastado += tecReac.haki;
                        }
                    }
                }
                
            } else if (action.actionType === 'basic_attack') {
                // GOLPE BÁSICO CON MÚLTIPLES ARMAS
                var extraTitulo = 'Ataque contra ' + targetNames.join(', ');
                code += '[extra=' + extraTitulo + ']\n';
                code += '[b]Golpe Básico[/b]\n';
                
                // Mostrar armas usadas
                if (action.weapons && action.weapons.length > 0) {
                    if (action.weapons.length === 1) {
                        code += 'Arma: ' + action.weapons[0].nombre;
                        if (action.weapons[0].tier > 0) {
                            code += ' (Tier ' + action.weapons[0].tier + ')';
                        }
                        code += '\n';
                    } else {
                        code += 'Armas (' + action.weapons.length + '):\n';
                        for (var j = 0; j < action.weapons.length; j++) {
                            var w = action.weapons[j];
                            code += '  • ' + w.nombre;
                            if (w.tier > 0) {
                                code += ' (Tier ' + w.tier + ')';
                            }
                            code += '\n';
                        }
                        code += 'Multiplicador por múltiples armas: ' + Math.round(action.weaponMultiplier * 100) + '%\n';
                    }
                }
                
                // Calcular y mostrar TA
                var ta = this.calculateTA(char);
                code += '\n[b]TA (Tasa de Acierto): ' + ta + '[/b]\n';
                
                // Desglose de daño
                if (action.damageResults && action.damageResults.length > 0) {
                    code += '\n[b]Cálculo de Daño:[/b]\n';
                    for (var j = 0; j < action.damageResults.length; j++) {
                        var dr = action.damageResults[j];
                        var weaponName = action.weapons[j] ? action.weapons[j].nombre : 'Arma ' + (j + 1);
                        code += weaponName + ': ';
                        
                        if (dr.breakdown && dr.breakdown.length > 0) {
                            var parts = [];
                            for (var k = 0; k < dr.breakdown.length; k++) {
                                var b = dr.breakdown[k];
                                if (b.label === 'Base') {
                                    parts.push('Base: ' + b.value);
                                } else {
                                    parts.push(b.label + ' (' + b.multiplier + ' × ' + b.statValue + ' = ' + b.value + ')');
                                }
                            }
                            code += parts.join(' + ');
                        }
                        code += ' = ' + dr.total;
                        if (action.weapons.length > 1) {
                            code += ' × ' + action.weaponMultiplier + ' = ' + Math.floor(dr.total * action.weaponMultiplier);
                        }
                        code += '\n';
                    }
                    
                    code += '\n[b]DAÑO TOTAL: ' + action.totalDamage + '[/b]';
                    if (action.damageTypes && action.damageTypes.length > 0) {
                        code += ' de ' + action.damageTypes.join('/');
                    }
                    code += '\n';
                }
                
                // Añadir tag oculto para detección automática
                var totalDamage = action.totalDamage || 0;
                code += '[combatdata ta="' + ta + '" damage="' + totalDamage + '" attacker="' + (char ? char.nombre : '') + '"][/combatdata]\n';
                
                code += '[/extra]\n\n';
                
            } else if (action.actionType === 'basic_block') {
                // BLOQUEO BÁSICO CON MÚLTIPLES ARMAS
                var extraTitulo = 'Defensa contra ' + targetNames.join(', ');
                code += '[extra=' + extraTitulo + ']\n';
                code += '[b]Bloqueo Básico[/b]\n';
                
                // Mostrar armas usadas
                if (action.weapons && action.weapons.length > 0) {
                    if (action.weapons.length === 1) {
                        code += 'Arma: ' + action.weapons[0].nombre;
                        if (action.weapons[0].tier > 0) {
                            code += ' (Tier ' + action.weapons[0].tier + ')';
                        }
                        code += '\n';
                    } else {
                        code += 'Armas (' + action.weapons.length + '):\n';
                        for (var j = 0; j < action.weapons.length; j++) {
                            var w = action.weapons[j];
                            code += '  • ' + w.nombre;
                            if (w.tier > 0) {
                                code += ' (Tier ' + w.tier + ')';
                            }
                            code += '\n';
                        }
                        code += 'Multiplicador por múltiples armas: ' + Math.round(action.weaponMultiplier * 100) + '%\n';
                    }
                }
                
                // Desglose de bloqueo
                if (action.blockResults && action.blockResults.length > 0) {
                    code += '\n[b]Cálculo de Mitigación:[/b]\n';
                    for (var j = 0; j < action.blockResults.length; j++) {
                        var br = action.blockResults[j];
                        var weaponName = action.weapons[j] ? action.weapons[j].nombre : 'Arma ' + (j + 1);
                        code += weaponName + ': ';
                        
                        if (br.breakdown && br.breakdown.length > 0) {
                            var parts = [];
                            for (var k = 0; k < br.breakdown.length; k++) {
                                var b = br.breakdown[k];
                                if (b.label === 'Base') {
                                    parts.push('Base: ' + b.value);
                                } else {
                                    parts.push(b.label + ' (' + b.multiplier + ' × ' + b.statValue + ' = ' + b.value + ')');
                                }
                            }
                            code += parts.join(' + ');
                        }
                        code += ' = ' + br.total;
                        if (action.weapons.length > 1) {
                            code += ' × ' + action.weaponMultiplier + ' = ' + Math.floor(br.total * action.weaponMultiplier);
                        }
                        code += '\n';
                    }
                    
                    code += '\n[b]MITIGACIÓN TOTAL: ' + action.totalBlock + '[/b]\n';
                }
                
                code += '[/extra]\n\n';
                
            } else {
                // TÉCNICA
                var tec = action.technique;
                if (!tec) continue;
                
                // Determinar tipo de acción
                var tipoAccion = 'Ataque';
                var tipoTec = (tec.tipo || tec.clase || tec.estilo || '').toLowerCase();
                var esUtilidadOPasiva = false;
                
                if (tipoTec.indexOf('defens') !== -1 || tipoTec.indexOf('escudo') !== -1 || tipoTec.indexOf('protec') !== -1) {
                    tipoAccion = 'Defensa';
                } else if (tipoTec.indexOf('pasiv') !== -1) {
                    tipoAccion = 'Pasiva';
                    esUtilidadOPasiva = true;
                } else if (tipoTec.indexOf('utilidad') !== -1 || tipoTec.indexOf('apoyo') !== -1 || 
                           tipoTec.indexOf('curacion') !== -1 || tipoTec.indexOf('buff') !== -1 || 
                           tipoTec.indexOf('mejora') !== -1) {
                    tipoAccion = 'Utilidad';
                    esUtilidadOPasiva = true;
                }
                
                // Construir título del extra según tipo
                var extraTitulo;
                if (targetNames.length > 0) {
                    extraTitulo = tipoAccion + ' contra ' + targetNames.join(', ');
                } else {
                    extraTitulo = tipoAccion;
                }
                
                // Generar código con [extra=]
                code += '[extra=' + extraTitulo + ']\n';
                
                // Mostrar armas usadas si existen
                if (action.weapons && action.weapons.length > 0 && !esUtilidadOPasiva) {
                    if (action.weapons.length === 1) {
                        code += 'Arma: ' + action.weapons[0].nombre;
                        if (action.weapons[0].tier > 0) {
                            code += ' (Tier ' + action.weapons[0].tier + ')';
                        }
                        code += '\n';
                    } else {
                        code += 'Armas (' + action.weapons.length + '):\n';
                        for (var j = 0; j < action.weapons.length; j++) {
                            var w = action.weapons[j];
                            code += '  • ' + w.nombre;
                            if (w.tier > 0) {
                                code += ' (Tier ' + w.tier + ')';
                            }
                            code += '\n';
                        }
                    }
                }
                
                // Si es mantenida (por tipo o por estar en activeMaintained), añadir tag de mantenida
                var estaEnMaintained = this.activeMaintained.some(function(m) { return m.tid === tec.tid; });
                if (this.isMaintainedTechnique(tec) || estaEnMaintained) {
                    code += '[mantenida=' + tec.tid + ']\n';
                }
                code += '[tecnica=' + tec.tid + ']\n';
                
                // Calcular y añadir daño combinado (técnica + armas)
                var effectResult = null;
                var weaponDamageResults = [];
                var totalCombinedDamage = 0;
                
                if (tec.efectos && char) {
                    effectResult = this.parseAndCalculateEffect(tec.efectos, char);
                    if (effectResult) {
                        totalCombinedDamage += effectResult.totalDamage || 0;
                    }
                }
                
                // Sumar daño de las armas si no es utilidad/pasiva
                if (action.weapons && action.weapons.length > 0 && !esUtilidadOPasiva && char) {
                    // Calcular multiplicador
                    var multiplier = 1.0;
                    if (action.weapons.length === 2) multiplier = 0.75;
                    else if (action.weapons.length === 3) multiplier = 0.60;
                    
                    for (var j = 0; j < action.weapons.length; j++) {
                        var weapon = action.weapons[j];
                        if (weapon.dano) {
                            var weaponDamageResult = this.parseWeaponFormula(weapon.dano, char);
                            if (weaponDamageResult) {
                                var damageWithMult = Math.floor(weaponDamageResult.total * multiplier);
                                totalCombinedDamage += damageWithMult;
                                weaponDamageResults.push({
                                    weapon: weapon,
                                    result: weaponDamageResult,
                                    damageWithMultiplier: damageWithMult
                                });
                            }
                        }
                    }
                }
                
                // Calcular y mostrar TA para ataques
                var esAtaque = tipoAccion === 'Ataque';
                var ta = 0;
                if (esAtaque) {
                    ta = this.calculateTA(char);
                    code += '\n[b]TA (Tasa de Acierto): ' + ta + '[/b]\n';
                }
                
                if (totalCombinedDamage > 0) {
                    code += '\n';
                    code += this.generateCombinedDamageText(effectResult, weaponDamageResults, totalCombinedDamage);
                    code += '\n';
                }
                
                // Añadir tag oculto para detección automática (solo ataques)
                if (esAtaque && totalCombinedDamage > 0) {
                    code += '[combatdata ta="' + ta + '" damage="' + totalCombinedDamage + '" attacker="' + (char ? char.nombre : '') + '" technique="' + tec.nombre + '"][/combatdata]\n';
                }
                
                code += '[/extra]\n\n';
                
                // Acumular costos según tipo de técnica
                if (this.isMaintainedTechnique(tec)) {
                    // Técnica mantenida: primer turno usa energia_turno + haki
                    if (tec.energia_turno > 0) {
                        nuevaEnergiaGastada += tec.energia_turno;
                    }
                    if (tec.haki > 0) {
                        nuevoHakiGastado += tec.haki;
                    }
                } else {
                    // Técnica normal: usa energia + haki
                    if (tec.energia > 0) {
                        nuevaEnergiaGastada += tec.energia;
                    }
                    if (tec.haki > 0) {
                        nuevoHakiGastado += tec.haki;
                    }
                }
            }
        }
        
        // Calcular costes de técnicas mantenidas
        var maintainedCosts = this.calculateMaintainedCosts();
        nuevaEnergiaGastada += maintainedCosts.energia;
        nuevoHakiGastado += maintainedCosts.haki;
        
        // Obtener recursos actuales (del último post o máximo si es primer turno)
        var energiaInicial = this.currentResources ? this.currentResources.energia : energiaMax;
        var hakiInicial = this.currentResources ? this.currentResources.haki : hakiMax;
        var vidaInicial = this.currentResources ? this.currentResources.vida : vidaMax;
        
        // Calcular recursos finales después de este turno
        var energiaFinal = Math.max(0, energiaInicial - nuevaEnergiaGastada);
        var hakiFinal = Math.max(0, hakiInicial - nuevoHakiGastado);
        var vidaFinal = Math.max(0, vidaInicial - totalDamageReceived);
        
        // Determinar el tipo de resumen según si es personaje o NPC
        var isNpc = this.selectedCharacter && this.selectedCharacter !== 'main';
        var resumenTipo = isNpc ? 'Resumen NPC' : 'Resumen Personaje';
        var resumenTitulo = isNpc ? ('ESTADO FINAL DEL NPC: ' + char.nombre) : 'ESTADO FINAL DEL TURNO';
        
        // Siempre añadir [extra=Resumen Personaje/NPC] al final del Belico con todos los recursos
        console.log('[CombatSystem] Añadiendo ' + resumenTipo + ' - Vida:', vidaFinal, '/', vidaMax, '- Energia:', energiaFinal, '/', energiaMax, '- Haki:', hakiFinal, '/', hakiMax);
        code += '\n[extra=' + resumenTipo + ']\n';
        code += '[b]' + resumenTitulo + '[/b]\n\n';
        
        // Si es NPC, añadir tag de NPC
        if (isNpc) {
            code += '[npc=' + char.npc_id + ']\n\n';
        }
        
        // Vida
        code += '❤️ Vida: ';
        if (totalDamageReceived > 0) {
            code += vidaInicial + ' - ' + totalDamageReceived + ' = ';
        }
        code += '[vida=' + vidaFinal + ', ' + vidaMax + ']\n';
        
        // Energía
        code += '⚡ Energía: ';
        if (nuevaEnergiaGastada > 0) {
            code += energiaInicial + ' - ' + nuevaEnergiaGastada + ' = ';
        }
        code += '[energia=' + energiaFinal + ', ' + energiaMax + ']\n';
        
        // Haki
        code += '🔮 Haki: ';
        if (nuevoHakiGastado > 0) {
            code += hakiInicial + ' - ' + nuevoHakiGastado + ' = ';
        }
        code += '[haki=' + hakiFinal + ', ' + hakiMax + ']\n';
        
        code += '[/extra]\n';
        
        // Cerrar el extra=Belico
        code += '[/extra]\n';
        
        // Añadir código de técnicas mantenidas
        var maintainedCode = this.generateMaintainedCode();
        if (maintainedCode) {
            code += '\n' + maintainedCode;
        }
        
        console.log('[CombatSystem] Código generado completo:', code);

        // Insertar en el editor
        if (typeof MyBBEditor !== 'undefined') {
            MyBBEditor.insertText(code);
            this.setStatus('Código insertado en el editor');
            this.close();
        } else {
            // Fallback: mostrar en textarea
            var textarea = document.getElementById('message');
            if (textarea) {
                textarea.value += code;
                this.setStatus('Código añadido al mensaje');
                this.close();
            } else {
                // Copiar al portapapeles
                navigator.clipboard.writeText(code).then(function() {
                    alert('Código copiado al portapapeles:\n\n' + code);
                    CombatSystem.close();
                });
            }
        }

        // Limpiar acciones después de generar
        this.pendingActions = [];
        this.selectedTargets = [];

        // Avanzar turnos: decrementar duración de modificadores y limpiar los expirados
        this.tickModifiers();
        this.renderModifiers();
        this.renderPendingActions();
        this.calculateResourcesUsed();
    },

    /**
     * Actualiza los datos
     */
    refresh: function() {
        this.pendingActions = [];
        this.selectedTargets = [];
        this.loadData();
    },

    /**
     * Actualiza el estado
     */
    setStatus: function(message) {
        var status = document.getElementById('combat-status');
        if (status) {
            status.textContent = message;
        }
        console.log('[CombatSystem]', message);
    },

    /**
     * Parsea el campo escalado del arma para extraer stats de TA
     * Formato: [Tasa de Acierto = Agilidad o Fuerza] o [Tasa de Acierto = Fuerza]
     * Retorna: { stats: ['agilidad', 'fuerza'], hasChoice: true/false }
     */
    parseWeaponTAScaling: function(escalado) {
        if (!escalado) return { stats: [], hasChoice: false };
        
        // Buscar [Tasa de Acierto = ...]
        var taMatch = escalado.match(/\[Tasa de Acierto\s*=\s*([^\]]+)\]/i);
        if (!taMatch) return { stats: [], hasChoice: false };
        
        var statsText = taMatch[1].trim();
        var hasChoice = statsText.toLowerCase().indexOf(' o ') !== -1;
        
        // Mapeo de nombres a keys de stats
        var statNameMap = {
            'fuerza': 'fuerza',
            'resistencia': 'resistencia',
            'destreza': 'destreza',
            'punteria': 'punteria',
            'puntería': 'punteria',
            'agilidad': 'agilidad',
            'reflejos': 'reflejos',
            'voluntad': 'voluntad'
        };
        
        var stats = [];
        if (hasChoice) {
            var parts = statsText.split(/\s+o\s+/i);
            for (var i = 0; i < parts.length; i++) {
                var statName = parts[i].trim().toLowerCase();
                if (statNameMap[statName]) {
                    stats.push(statNameMap[statName]);
                }
            }
        } else {
            var statName = statsText.trim().toLowerCase();
            if (statNameMap[statName]) {
                stats.push(statNameMap[statName]);
            }
        }
        
        return { stats: stats, hasChoice: hasChoice };
    },

    /**
     * Parsea el campo efecto del arma para extraer modificador de TA
     * Busca: +5 Tasa de Acierto, -5 Tasa de Acierto, + 5 Tasa de Acierto
     * Retorna: número (positivo o negativo) o 0
     */
    parseWeaponTAModifier: function(efecto) {
        if (!efecto) return 0;
        
        // Buscar +/- X Tasa de Acierto
        var modMatch = efecto.match(/([+-])\s*(\d+)\s*Tasa de Acierto/i);
        if (modMatch) {
            var modifier = parseInt(modMatch[2]);
            return modMatch[1] === '-' ? -modifier : modifier;
        }
        
        return 0;
    },

    /**
     * Selecciona el stat para TA cuando hay opciones
     */
    selectTAStat: function(stat) {
        this.selectedTAStat = stat;
        
        // Actualizar UI
        var buttons = document.querySelectorAll('.ta-stat-option');
        buttons.forEach(function(btn) {
            btn.classList.remove('selected');
        });
        
        var selectedBtn = document.querySelector('.ta-stat-option[data-stat="' + stat + '"]');
        if (selectedBtn) {
            selectedBtn.classList.add('selected');
        }
        
        // Recalcular y mostrar TA
        this.updateTADisplay();
    },

    /**
     * Actualiza la visualización de la TA
     */
    updateTADisplay: function() {
        var taDisplay = document.getElementById('weapon-ta-value');
        if (!taDisplay) return;
        
        var ta = this.calculateTA(this.combatData ? this.combatData.my_character : null);
        taDisplay.textContent = ta;
    },

    /**
     * Calcula la Tasa de Acierto (TA) del personaje
     * Basado en el arma seleccionada (usa la primera si hay múltiples): stat del escalado + modificador del efecto
     */
    calculateTA: function(char) {
        // Si no se pasa un personaje, usar la versión efectiva (con modificadores)
        if (!char || !char.stats) char = this.getEffectiveCharacter();
        if (!char || !char.stats) return 0;
        
        // Obtener primera arma seleccionada (o usar compatibilidad con selectedWeapon)
        var weapon = this.selectedWeapons && this.selectedWeapons[0] ? this.selectedWeapons[0] : this.selectedWeapon;
        var ta = 0;
        
        // Si hay arma seleccionada, calcular basándose en ella
        if (weapon && weapon.escalado) {
            var taScaling = this.parseWeaponTAScaling(weapon.escalado);
            
            if (taScaling.stats.length > 0) {
                var statKey;
                if (taScaling.hasChoice && this.selectedTAStat) {
                    // Usar el stat elegido por el usuario
                    statKey = this.selectedTAStat;
                } else {
                    // Usar el primer (o único) stat
                    statKey = taScaling.stats[0];
                }
                
                ta = parseInt(char.stats[statKey]) || 0;
            }
            
            // Añadir modificador del efecto
            if (weapon.efecto) {
                ta += this.parseWeaponTAModifier(weapon.efecto);
            }
        } else if (weapon && weapon.objeto_id === 'UNARMED') {
            // Desarmado: usar destreza por defecto
            ta = parseInt(char.stats.destreza) || 0;
        } else {
            // Sin arma o arma sin escalado: Puntería + Destreza como fallback
            var punteria = parseInt(char.stats.punteria) || 0;
            var destreza = parseInt(char.stats.destreza) || 0;
            ta = punteria + destreza;
        }
        
        return ta;
    },

    /**
     * Detecta ataques en el tema desde los posts
     * Busca tags [combatdata] en los posts del combate
     */
    detectAttacksInThread: function() {
        var self = this;
        this.detectedAttacks = [];
        
        console.log('[CombatSystem] combatData:', this.combatData);
        console.log('[CombatSystem] posts:', this.combatData ? this.combatData.posts : 'no combatData');
        
        if (!this.combatData || !this.combatData.posts || this.combatData.posts.length === 0) {
            console.log('[CombatSystem] No hay posts para analizar');
            return;
        }
        
        var currentUid = this.combatData.current_uid;
        
        // Buscar [combatdata] en cada post que NO sea del usuario actual
        for (var i = 0; i < this.combatData.posts.length; i++) {
            var post = this.combatData.posts[i];
            
            // Solo procesar ataques de otros usuarios
            if (post.uid == currentUid) continue;
            
            var content = post.message || '';
            
            console.log('[CombatSystem] Analizando post de uid:', post.uid, '(current:', currentUid, ')');
            console.log('[CombatSystem] Contenido:', content.substring(0, 500));
            
            // Buscar tags [combatdata ...] con atributos en cualquier orden
            var combatDataBlockRegex = /\[combatdata\s+([^\]]+)\]\[\/combatdata\]/gi;
            var match;
            
            while ((match = combatDataBlockRegex.exec(content)) !== null) {
                var attrs = match[1];
                console.log('[CombatSystem] combatdata encontrado, atributos:', attrs);
                
                // Extraer atributos individualmente
                var taMatch = attrs.match(/ta="(\d+)"/i);
                var damageMatch = attrs.match(/damage="(\d+)"/i);
                var attackerMatch = attrs.match(/attacker="([^"]+)"/i);
                var techniqueMatch = attrs.match(/technique="([^"]+)"/i);
                
                if (taMatch && damageMatch) {
                    self.detectedAttacks.push({
                        ta: parseInt(taMatch[1]),
                        damage: parseInt(damageMatch[1]),
                        attacker: attackerMatch ? attackerMatch[1] : 'Atacante',
                        technique: techniqueMatch ? techniqueMatch[1] : 'Golpe Básico',
                        postId: post.pid,
                        uid: post.uid
                    });
                    console.log('[CombatSystem] Ataque añadido desde combatdata');
                }
            }
            
            // También buscar formato legible [b]TA (Tasa de Acierto): X[/b] como fallback
            // El formato es: TA (Tasa de Acierto): X
            var taMatchFallback = content.match(/TA\s*\(Tasa de Acierto\)\s*:\s*(\d+)/i);
            if (!taMatchFallback) {
                // Intentar formato alternativo sin paréntesis
                taMatchFallback = content.match(/TA[:\s]+(\d+)/i);
            }
            var damageMatchFallback = content.match(/DAÑO\s*(?:TOTAL(?:\s*COMBINADO)?)?\s*:\s*(\d+)/i);
            
            console.log('[CombatSystem] Fallback - TA match:', taMatchFallback);
            console.log('[CombatSystem] Fallback - Damage match:', damageMatchFallback);
            
            // Si hay daño pero no TA, intentar calcular TA del atacante
            if (damageMatchFallback) {
                var ta = 0;
                var damage = parseInt(damageMatchFallback[1]);
                
                if (taMatchFallback) {
                    ta = parseInt(taMatchFallback[1]);
                } else {
                    // Calcular TA del atacante basándonos en sus stats
                    var attackerParticipant = null;
                    if (this.combatData && this.combatData.participants) {
                        for (var p = 0; p < this.combatData.participants.length; p++) {
                            if (this.combatData.participants[p].uid == post.uid) {
                                attackerParticipant = this.combatData.participants[p];
                                break;
                            }
                        }
                    }
                    
                    if (attackerParticipant && attackerParticipant.character && attackerParticipant.character.stats) {
                        var stats = attackerParticipant.character.stats;
                        var punteria = parseInt(stats.punteria) || 0;
                        var destreza = parseInt(stats.destreza) || 0;
                        ta = punteria + destreza;
                        console.log('[CombatSystem] TA calculado del atacante:', ta, '(punteria:', punteria, '+ destreza:', destreza, ')');
                    }
                }
                
                // Verificar que no esté ya añadido por combatdata
                var alreadyExists = self.detectedAttacks.some(function(a) {
                    return a.ta === ta && a.damage === damage && a.uid === post.uid;
                });
                
                if (!alreadyExists) {
                    // Buscar nombre del atacante
                    var participant = null;
                    if (this.combatData && this.combatData.participants) {
                        for (var p = 0; p < this.combatData.participants.length; p++) {
                            if (this.combatData.participants[p].uid == post.uid) {
                                participant = this.combatData.participants[p];
                                break;
                            }
                        }
                    }
                    var attackerName = participant && participant.character ? participant.character.nombre : 'Atacante';
                    
                    // Buscar nombre de técnica si existe
                    var techniqueMatch = content.match(/\[tecnica=([^\]]+)\]/i);
                    var techniqueName = techniqueMatch ? techniqueMatch[1] : 'Ataque detectado';
                    
                    // Buscar objetivo en [extra=Ataque contra X]
                    var targetMatch = content.match(/\[extra=Ataque contra ([^\]]+)\]/i);
                    if (targetMatch) {
                        techniqueName += ' contra ' + targetMatch[1];
                    }
                    
                    self.detectedAttacks.push({
                        ta: ta,
                        damage: damage,
                        attacker: attackerName,
                        technique: techniqueName,
                        postId: post.pid,
                        uid: post.uid,
                        calculated: !taMatchFallback // Indicar si el TA fue calculado
                    });
                    console.log('[CombatSystem] Ataque añadido desde fallback - TA:', ta, 'Daño:', damage);
                }
            }
        }
        
        console.log('[CombatSystem] Ataques detectados:', this.detectedAttacks);
    },

    /**
     * Parsea y calcula el efecto de una técnica
     * Formato esperado: [5,8xNivel] de [Daño contundente]
     *                   [2xFuerza + 3xDestreza] de [Daño cortante]
     */
    parseAndCalculateEffect: function(efectos, char) {
        // Usar personaje efectivo si no se pasa explícitamente
        if (!char) char = this.getEffectiveCharacter();
        if (!efectos || !char) return null;
        
        var result = {
            formula: efectos,
            damage: 0,
            damageType: '',
            breakdown: [],
            pasivaBonuses: []
        };
        
        // Obtener stats del personaje
        var stats = char.stats || {};
        var nivel = char.nivel || 1;
        
        // Mapeo de nombres a valores
        var statValues = {
            'nivel': nivel,
            'fuerza': stats.fuerza || 0,
            'resistencia': stats.resistencia || 0,
            'destreza': stats.destreza || 0,
            'punteria': stats.punteria || 0,
            'agilidad': stats.agilidad || 0,
            'reflejos': stats.reflejos || 0,
            'voluntad': stats.voluntad || 0
        };
        
        // Buscar tipo de daño: [Daño X] o de [X]
        var damageTypeMatch = efectos.match(/\[(?:Daño\s+)?([^\]]+)\](?:\s*$)/i) || 
                              efectos.match(/de\s+\[([^\]]+)\]/i);
        if (damageTypeMatch) {
            result.damageType = damageTypeMatch[1];
        }
        
        // Buscar fórmulas: [NxStat], [N,NxStat], [NxStat + NxStat]
        var formulaMatch = efectos.match(/\[([^\]]+x[^\]]+)\]/i);
        if (formulaMatch) {
            var formula = formulaMatch[1];
            var totalDamage = 0;
            
            // Separar por + para múltiples términos
            var terms = formula.split(/\s*\+\s*/);
            
            for (var i = 0; i < terms.length; i++) {
                var term = terms[i].trim();
                // Parsear: N,NxStat o NxStat
                var termMatch = term.match(/(\d+[,.]?\d*)\s*x\s*(\w+)/i);
                if (termMatch) {
                    var multiplier = parseFloat(termMatch[1].replace(',', '.'));
                    var statName = termMatch[2].toLowerCase();
                    var statValue = statValues[statName] || 0;
                    var termDamage = Math.floor(multiplier * statValue);
                    
                    totalDamage += termDamage;
                    result.breakdown.push({
                        multiplier: multiplier,
                        stat: statName,
                        statValue: statValue,
                        result: termDamage,
                        text: multiplier + 'x' + statName.charAt(0).toUpperCase() + statName.slice(1) + ' (' + multiplier + ' × ' + statValue + ' = ' + termDamage + ')'
                    });
                }
            }
            
            result.damage = totalDamage;
        }
        
        // Aplicar bonificadores de pasivas
        result = this.applyPassiveBonuses(result);
        
        return result;
    },

    /**
     * Aplica bonificadores de técnicas pasivas al daño
     */
    applyPassiveBonuses: function(result) {
        if (!this.myPasivas || this.myPasivas.length === 0) return result;
        
        var baseDamage = result.damage;
        var bonusTotal = 0;
        var char = this.combatData ? this.combatData.my_character : null;
        var nivel = char ? char.nivel : 1;
        var stats = char ? char.stats : {};
        
        for (var i = 0; i < this.myPasivas.length; i++) {
            var pasiva = this.myPasivas[i];
            var efectos = pasiva.efectos || '';
            
            // Buscar bonificadores de daño en las pasivas
            // Ejemplos: +10% daño, +5 daño, +1xNivel daño
            
            // Bonificador porcentual: +X% daño
            var porcentajeMatch = efectos.match(/\+\s*(\d+)\s*%\s*(?:de\s+)?da[ñn]o/i);
            if (porcentajeMatch) {
                var porcentaje = parseInt(porcentajeMatch[1]);
                var bonus = Math.floor(baseDamage * porcentaje / 100);
                bonusTotal += bonus;
                result.pasivaBonuses.push({
                    nombre: pasiva.nombre,
                    tipo: 'porcentaje',
                    valor: porcentaje + '%',
                    bonus: bonus
                });
                continue;
            }
            
            // Bonificador fijo por nivel: +NxNivel daño
            var nivelMatch = efectos.match(/\+\s*(\d+[,.]?\d*)\s*x\s*nivel\s*(?:de\s+)?da[ñn]o/i);
            if (nivelMatch) {
                var mult = parseFloat(nivelMatch[1].replace(',', '.'));
                var bonus = Math.floor(mult * nivel);
                bonusTotal += bonus;
                result.pasivaBonuses.push({
                    nombre: pasiva.nombre,
                    tipo: 'nivel',
                    valor: mult + 'xNivel',
                    bonus: bonus
                });
                continue;
            }
            
            // Bonificador fijo por stat: +NxFuerza daño
            var statBonusMatch = efectos.match(/\+\s*(\d+[,.]?\d*)\s*x\s*(fuerza|destreza|punteria|agilidad|voluntad|resistencia|reflejos)\s*(?:de\s+)?da[ñn]o/i);
            if (statBonusMatch) {
                var mult = parseFloat(statBonusMatch[1].replace(',', '.'));
                var statName = statBonusMatch[2].toLowerCase();
                var statValue = stats[statName] || 0;
                var bonus = Math.floor(mult * statValue);
                bonusTotal += bonus;
                result.pasivaBonuses.push({
                    nombre: pasiva.nombre,
                    tipo: 'stat',
                    valor: mult + 'x' + statName,
                    bonus: bonus
                });
                continue;
            }
            
            // Bonificador fijo: +N daño
            var fijoMatch = efectos.match(/\+\s*(\d+)\s*(?:de\s+)?da[ñn]o/i);
            if (fijoMatch) {
                var bonus = parseInt(fijoMatch[1]);
                bonusTotal += bonus;
                result.pasivaBonuses.push({
                    nombre: pasiva.nombre,
                    tipo: 'fijo',
                    valor: '+' + bonus,
                    bonus: bonus
                });
            }
        }
        
        result.bonusFromPasivas = bonusTotal;
        result.totalDamage = baseDamage + bonusTotal;
        
        return result;
    },

    /**
     * Genera texto de daño formateado para el código BBCode
     */
    generateDamageText: function(effectResult) {
        if (!effectResult || effectResult.damage === 0) return '';
        
        var text = '';
        
        // Desglose de la fórmula base
        if (effectResult.breakdown.length > 0) {
            var breakdownTexts = [];
            for (var i = 0; i < effectResult.breakdown.length; i++) {
                var b = effectResult.breakdown[i];
                breakdownTexts.push(b.text);
            }
            text += 'Cálculo: ' + breakdownTexts.join(' + ') + '\n';
        }
        
        text += 'Daño base: ' + effectResult.damage;
        
        // Bonificadores de pasivas
        if (effectResult.pasivaBonuses.length > 0) {
            text += '\n--- Bonificadores de Pasivas ---';
            for (var i = 0; i < effectResult.pasivaBonuses.length; i++) {
                var p = effectResult.pasivaBonuses[i];
                text += '\n• ' + p.nombre + ' (' + p.valor + '): +' + p.bonus;
            }
            text += '\nBonus total pasivas: +' + effectResult.bonusFromPasivas;
        }
        
        text += '\n[b]DAÑO TOTAL: ' + effectResult.totalDamage + '[/b]';
        
        if (effectResult.damageType) {
            text += ' de ' + effectResult.damageType;
        }
        
        return text;
    },

    /**
     * Obtiene los nombres de los objetivos a partir de sus IDs
     */
    getTargetNames: function(targets) {
        var self = this;
        var targetNames = [];
        
        if (!targets || targets.length === 0) return targetNames;
        
        for (var j = 0; j < targets.length; j++) {
            var targetId = targets[j];
            if (String(targetId).indexOf('npc_') === 0) {
                // Es un NPC
                var npcIndex = parseInt(targetId.replace('npc_', ''));
                var npc = this.combatData.npcs[npcIndex];
                targetNames.push(npc ? (npc.data.nombre || npc.npc_id) : targetId);
            } else {
                // Es un jugador
                var participant = this.combatData.participants.find(function(p) { 
                    return String(p.uid) === String(targetId); 
                });
                if (participant && participant.character) {
                    targetNames.push(participant.character.nombre);
                } else if (participant) {
                    targetNames.push(participant.username);
                } else {
                    targetNames.push('UID ' + targetId);
                }
            }
        }
        
        return targetNames;
    },

    /**
     * Genera texto de daño/bloqueo de arma para el código BBCode
     */
    generateWeaponDamageText: function(result, tipo) {
        if (!result) return '';
        
        var text = '';
        
        // Desglose de la fórmula
        if (result.breakdown.length > 0) {
            var breakdownTexts = [];
            for (var i = 0; i < result.breakdown.length; i++) {
                var b = result.breakdown[i];
                if (b.label === 'Base') {
                    breakdownTexts.push('Base: ' + b.value);
                } else {
                    breakdownTexts.push(b.label + ' (' + b.multiplier + ' × ' + b.statValue + ' = ' + b.value + ')');
                }
            }
            text += 'Cálculo: ' + breakdownTexts.join(' + ') + '\n';
        }
        
        text += '[b]' + tipo.toUpperCase() + ' TOTAL: ' + result.total + '[/b]';
        
        if (result.type) {
            text += ' de ' + result.type;
        }
        
        return text;
    },

    /**
     * Genera texto de daño combinado (técnica + armas) para el código BBCode
     */
    generateCombinedDamageText: function(effectResult, weaponDamageResults, totalDamage) {
        var text = '';
        
        // Daño de técnica
        if (effectResult && effectResult.damage > 0) {
            if (effectResult.breakdown.length > 0) {
                var breakdownTexts = [];
                for (var i = 0; i < effectResult.breakdown.length; i++) {
                    var b = effectResult.breakdown[i];
                    breakdownTexts.push(b.text);
                }
                text += 'Técnica: ' + breakdownTexts.join(' + ') + '\n';
            }
            text += 'Daño técnica: ' + effectResult.damage;
            if (effectResult.bonusFromPasivas > 0) {
                text += ' + ' + effectResult.bonusFromPasivas + ' (pasivas) = ' + effectResult.totalDamage;
            }
            text += '\n';
            
            // Pasivas aplicadas
            if (effectResult.pasivaBonuses && effectResult.pasivaBonuses.length > 0) {
                text += '--- Bonificadores de Pasivas ---\n';
                for (var i = 0; i < effectResult.pasivaBonuses.length; i++) {
                    var p = effectResult.pasivaBonuses[i];
                    text += '• ' + p.nombre + ' (' + p.valor + '): +' + p.bonus + '\n';
                }
            }
        }
        
        // Daño de armas
        if (weaponDamageResults && weaponDamageResults.length > 0) {
            var totalWeaponDamage = 0;
            
            if (weaponDamageResults.length === 1) {
                // Una sola arma
                var wr = weaponDamageResults[0];
                text += '\n🗡️ Arma: ' + wr.weapon.nombre + '\n';
                if (wr.result.breakdown.length > 0) {
                    var weaponBreakdown = [];
                    for (var i = 0; i < wr.result.breakdown.length; i++) {
                        var b = wr.result.breakdown[i];
                        if (b.label === 'Base') {
                            weaponBreakdown.push('Base: ' + b.value);
                        } else {
                            weaponBreakdown.push(b.label + ' (' + b.multiplier + ' × ' + b.statValue + ' = ' + b.value + ')');
                        }
                    }
                    text += 'Cálculo arma: ' + weaponBreakdown.join(' + ') + '\n';
                }
                text += 'Daño arma: ' + wr.result.total;
                if (wr.result.type) {
                    text += ' de ' + wr.result.type;
                }
                text += '\n';
                totalWeaponDamage = wr.result.total;
            } else {
                // Múltiples armas
                var multiplier = 1.0;
                if (weaponDamageResults.length === 2) multiplier = 0.75;
                else if (weaponDamageResults.length === 3) multiplier = 0.60;
                
                text += '\n🗡️ Armas (' + weaponDamageResults.length + ' × ' + Math.round(multiplier * 100) + '%):\n';
                
                for (var i = 0; i < weaponDamageResults.length; i++) {
                    var wr = weaponDamageResults[i];
                    text += '  • ' + wr.weapon.nombre + ': ';
                    
                    if (wr.result.breakdown.length > 0) {
                        var parts = [];
                        for (var j = 0; j < wr.result.breakdown.length; j++) {
                            var b = wr.result.breakdown[j];
                            if (b.label === 'Base') {
                                parts.push(b.value);
                            } else {
                                parts.push('[' + b.label + ']');
                            }
                        }
                        text += parts.join(' + ') + ' = ';
                    }
                    
                    text += wr.result.total + ' × ' + multiplier + ' = ' + wr.damageWithMultiplier + '\n';
                    totalWeaponDamage += wr.damageWithMultiplier;
                }
                
                text += 'Daño total armas: ' + totalWeaponDamage + '\n';
            }
        }
        
        // Total combinado
        var damageType = '';
        if (effectResult && effectResult.damageType) {
            damageType = effectResult.damageType;
        } else if (weaponDamageResults && weaponDamageResults.length > 0 && weaponDamageResults[0].result.type) {
            damageType = weaponDamageResults[0].result.type;
        }
        
        text += '\n[b]DAÑO TOTAL COMBINADO: ' + totalDamage + '[/b]';
        if (damageType) {
            text += ' de ' + damageType;
        }
        
        return text;
    },

    // =====================================================
    // SISTEMA DE REACCIÓN DEFENSIVA
    // =====================================================

    /**
     * Abre el modo de reacción defensiva
     */
    openReactionMode: function() {
        var self = this;
        this.detectThreadId();
        
        // Verificar si puede usar el sistema
        fetch(this.config.apiUrl + '?action=check_combat_ready&tid=' + this.currentTid)
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (!data.ready) {
                    alert('⚠️ Sistema de Combate no disponible\n\n' + data.reason);
                    return;
                }
                
                // Cargar datos necesarios
                self.reactionMode = true;
                self.resetReactionData();
                
                Promise.all([
                    self.fetchCombatData(),
                    self.fetchMyTechniques(),
                    self.fetchMyWeapons()
                ]).then(function() {
                    self.showReactionModal();
                });
            })
            .catch(function(error) {
                console.error('[CombatSystem] Error:', error);
                alert('Error al verificar el estado del combate.');
            });
    },

    /**
     * Muestra el modal de reacción
     */
    showReactionModal: function() {
        var modal = document.getElementById('combat-reaction-modal');
        if (modal) {
            modal.style.display = 'block';
            
            // Mostrar reflejos del personaje/NPC activo (usando modificadores si aplican)
            var char = this.getEffectiveCharacter();
            console.log('[CombatSystem] showReactionModal - Personaje activo:', char ? char.nombre : 'null', 'selectedCharacter:', this.selectedCharacter);
            if (char && char.stats) {
                var reflejos = char.stats.reflejos || 0;
                var charName = char.nombre || 'Personaje';
                console.log('[CombatSystem] showReactionModal - Mostrando reflejos (con modificadores):', reflejos, 'para:', charName);
                document.getElementById('reaction-reflejos-value').textContent = reflejos;
                
                // Actualizar el título si es un NPC
                if (this.selectedCharacter && this.selectedCharacter !== 'main') {
                    var modalTitle = document.querySelector('#combat-reaction-modal .combat-header h2');
                    if (modalTitle) {
                        modalTitle.textContent = '🛡️ Reacción Defensiva - ' + charName;
                    }
                }
            }
            
            // Detectar ataques en el tema
            this.detectAttacksInThread();
            
            // Mostrar ataques detectados si hay
            this.renderDetectedAttacks();
            
            // Si hay ataques detectados, seleccionar el último automáticamente y calcular
            if (this.detectedAttacks.length > 0) {
                var lastAttackIndex = this.detectedAttacks.length - 1;
                this.selectDetectedAttack(lastAttackIndex);
                
                // Calcular automáticamente después de un pequeño delay para que la UI se actualice
                var self = this;
                setTimeout(function() {
                    self.calculateReaction();
                }, 100);
            } else {
                // Si no hay ataques, mostrar paso 1 para entrada manual
                this.goToReactionStep(1);
            }
        }
    },

    /**
     * Renderiza los ataques detectados en el tema
     */
    renderDetectedAttacks: function() {
        var section = document.getElementById('detected-attacks-section');
        var list = document.getElementById('detected-attacks-list');
        
        if (!section || !list) return;
        
        if (this.detectedAttacks.length === 0) {
            section.style.display = 'none';
            return;
        }
        
        section.style.display = 'block';
        
        var html = '';
        for (var i = 0; i < this.detectedAttacks.length; i++) {
            var attack = this.detectedAttacks[i];
            html += '<div class="detected-attack-item" onclick="CombatSystem.selectDetectedAttack(' + i + ')" data-index="' + i + '">';
            html += '<div class="detected-attack-attacker">⚔️ ' + attack.attacker + '</div>';
            html += '<div class="detected-attack-technique">' + attack.technique + '</div>';
            html += '<div class="detected-attack-stats">';
            html += '<span class="detected-attack-ta">🎯 TA: <strong>' + attack.ta + '</strong>';
            if (attack.calculated) {
                html += ' <small style="color:#f0ad4e;">(calculado)</small>';
            }
            html += '</span>';
            html += '<span class="detected-attack-damage">💥 Daño: <strong>' + attack.damage + '</strong></span>';
            html += '</div>';
            html += '</div>';
        }
        
        list.innerHTML = html;
    },

    /**
     * Selecciona un ataque detectado y rellena los campos
     */
    selectDetectedAttack: function(index) {
        var attack = this.detectedAttacks[index];
        if (!attack) return;
        
        // Rellenar campos del formulario
        document.getElementById('reaction-attacker-name').value = attack.attacker;
        document.getElementById('reaction-attacker-ta').value = attack.ta;
        document.getElementById('reaction-attacker-damage').value = attack.damage;
        
        // Marcar como seleccionado
        var allItems = document.querySelectorAll('.detected-attack-item');
        allItems.forEach(function(el) { el.classList.remove('selected'); });
        
        var selectedItem = document.querySelector('.detected-attack-item[data-index="' + index + '"]');
        if (selectedItem) selectedItem.classList.add('selected');
    },

    /**
     * Cierra el modo de reacción
     */
    closeReactionMode: function() {
        var modal = document.getElementById('combat-reaction-modal');
        if (modal) {
            modal.style.display = 'none';
        }
        this.reactionMode = false;
        this.resetReactionData();
    },

    /**
     * Muestra diálogo para configurar modificador de reflejos
     */
    showReflejosModifierDialog: function() {
        var modifier = prompt(
            'Introduce el modificador de Reflejos (positivo o negativo):\n' +
            'Ejemplo: +5 o -3\n\n' +
            'Actual: ' + (this.reactionData.reflejosModifier > 0 ? '+' : '') + this.reactionData.reflejosModifier,
            this.reactionData.reflejosModifier || '0'
        );
        
        if (modifier === null) return; // Cancelado
        
        // Parsear el modificador
        var modifierValue = parseInt(modifier);
        if (isNaN(modifierValue)) {
            alert('Valor inválido. Debe ser un número.');
            return;
        }
        
        // Pedir motivo
        var reason = prompt(
            'Introduce el motivo del modificador:\n' +
            'Ejemplo: "Técnica de apoyo", "Debuff enemigo", etc.',
            this.reactionData.reflejosModifierReason || ''
        );
        
        if (reason === null) return; // Cancelado
        
        // Guardar datos
        this.reactionData.reflejosModifier = modifierValue;
        this.reactionData.reflejosModifierReason = reason.trim() || 'Sin especificar';
        
        // Actualizar UI
        this.updateReflejosModifierDisplay();
        
        // Recalcular reacción con el nuevo modificador
        this.calculateReaction();
    },
    
    /**
     * Actualiza la visualización del modificador de reflejos
     */
    updateReflejosModifierDisplay: function() {
        var infoDiv = document.getElementById('reflejos-modifier-info');
        var valueSpan = document.getElementById('reflejos-modifier-value');
        var reasonSpan = document.getElementById('reflejos-modifier-reason');
        
        if (!infoDiv || !valueSpan || !reasonSpan) return;
        
        if (this.reactionData.reflejosModifier !== 0) {
            var modText = this.reactionData.reflejosModifier > 0 ? 
                '+' + this.reactionData.reflejosModifier : 
                this.reactionData.reflejosModifier.toString();
            
            valueSpan.textContent = modText;
            valueSpan.style.color = this.reactionData.reflejosModifier > 0 ? '#4caf50' : '#f44336';
            reasonSpan.textContent = this.reactionData.reflejosModifierReason;
            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    },
    
    /**
     * Resetea los datos de reacción
     */
    resetReactionData: function() {
        this.reactionData = {
            attackerTA: 0,
            attackerDamage: 0,
            attackerName: '',
            reactionResult: 0,
            reactionType: null,
            reactionOptions: [],
            selectedReaction: null,
            selectedTechnique: null,
            selectedWeapon: null,
            finalDamage: 0,
            reflejosModifier: 0,
            reflejosModifierReason: ''
        };
        
        // Ocultar info de modificador
        var infoDiv = document.getElementById('reflejos-modifier-info');
        if (infoDiv) infoDiv.style.display = 'none';
    },

    /**
     * Muestra diálogo para configurar modificador de reflejos
     */
    showReflejosModifierDialog: function() {
        var modifier = prompt(
            'Introduce el modificador de Reflejos (positivo o negativo):\n' +
            'Ejemplo: +5 o -3\n\n' +
            'Actual: ' + (this.reactionData.reflejosModifier > 0 ? '+' : '') + this.reactionData.reflejosModifier,
            this.reactionData.reflejosModifier || '0'
        );
        
        if (modifier === null) return; // Cancelado
        
        // Parsear el modificador
        var modifierValue = parseInt(modifier);
        if (isNaN(modifierValue)) {
            alert('Valor inválido. Debe ser un número.');
            return;
        }
        
        // Pedir motivo
        var reason = prompt(
            'Introduce el motivo del modificador:\n' +
            'Ejemplo: "Técnica de apoyo", "Debuff enemigo", etc.',
            this.reactionData.reflejosModifierReason || ''
        );
        
        if (reason === null) return; // Cancelado
        
        // Guardar datos
        this.reactionData.reflejosModifier = modifierValue;
        this.reactionData.reflejosModifierReason = reason.trim() || 'Sin especificar';
        
        // Recalcular reacción con el nuevo modificador
        this.calculateReaction();
    },
    
    /**
     * Actualiza la visualización del modificador de reflejos
     */
    updateReflejosModifierDisplay: function() {
        var infoDiv = document.getElementById('reflejos-modifier-info');
        var valueSpan = document.getElementById('reflejos-modifier-value');
        var reasonSpan = document.getElementById('reflejos-modifier-reason');
        
        if (!infoDiv || !valueSpan || !reasonSpan) return;
        
        if (this.reactionData.reflejosModifier !== 0) {
            var modText = this.reactionData.reflejosModifier > 0 ? 
                '+' + this.reactionData.reflejosModifier : 
                this.reactionData.reflejosModifier.toString();
            
            valueSpan.textContent = modText;
            valueSpan.style.color = this.reactionData.reflejosModifier > 0 ? '#4caf50' : '#f44336';
            reasonSpan.textContent = this.reactionData.reflejosModifierReason;
            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    },
    
    /**
     * Navega entre pasos del wizard de reacción
     */
    goToReactionStep: function(step) {
        // Ocultar todos los pasos
        document.getElementById('reaction-step-1').style.display = 'none';
        document.getElementById('reaction-step-2').style.display = 'none';
        document.getElementById('reaction-step-3').style.display = 'none';
        
        // Mostrar el paso solicitado
        document.getElementById('reaction-step-' + step).style.display = 'block';
    },

    /**
     * Calcula el resultado de la reacción
     */
    calculateReaction: function() {
        var attackerName = document.getElementById('reaction-attacker-name').value.trim();
        var attackerTA = parseInt(document.getElementById('reaction-attacker-ta').value) || 0;
        var attackerDamage = parseInt(document.getElementById('reaction-attacker-damage').value) || 0;
        
        if (attackerTA <= 0) {
            alert('Por favor, introduce la TA del atacante');
            return;
        }
        
        if (attackerDamage <= 0) {
            alert('Por favor, introduce el daño del ataque');
            return;
        }
        
        // Obtener reflejos del personaje/NPC activo (usar modificadores aplicados)
        var reflejosBase = 0;
        var char = this.getEffectiveCharacter();
        console.log('[CombatSystem] calculateReaction - Personaje activo:', char ? char.nombre : 'null', 'selectedCharacter:', this.selectedCharacter);
        if (char && char.stats) {
            reflejosBase = parseInt(char.stats.reflejos) || 0;
            console.log('[CombatSystem] calculateReaction - Reflejos base (con modificadores):', reflejosBase);
        }
        
        // Aplicar modificador de reflejos
        var modifier = parseInt(this.reactionData.reflejosModifier) || 0;
        var reflejos = reflejosBase + modifier;
        console.log('[CombatSystem] calculateReaction - Modificador:', modifier, 'Reflejos total:', reflejos);
        
        // Calcular resultado: Reflejos (+ modificador) - TA
        var result = reflejos - attackerTA;
        console.log('[CombatSystem] calculateReaction - Cálculo:', reflejos, '-', attackerTA, '=', result);
        
        // Guardar datos
        this.reactionData.attackerName = attackerName || 'Atacante';
        this.reactionData.attackerTA = attackerTA;
        this.reactionData.attackerDamage = attackerDamage;
        this.reactionData.reactionResult = result;
        this.reactionData.reflejosBase = reflejosBase; // Guardar reflejos base para usar después
        
        // Determinar opciones disponibles ANTES de actualizar la UI
        this.determineReactionOptions(result);
        
        // Actualizar resumen del ataque en paso 2
        document.getElementById('summary-attacker-name').textContent = this.reactionData.attackerName;
        document.getElementById('summary-ta').textContent = attackerTA;
        document.getElementById('summary-damage').textContent = attackerDamage;
        
        // Mostrar reflejos con modificador si existe
        var reflejosText = reflejosBase.toString();
        if (modifier !== 0) {
            reflejosText += ' ' + (modifier > 0 ? '+' : '') + modifier + ' = ' + reflejos;
        }
        document.getElementById('summary-reflejos').textContent = reflejosText;
        
        // Actualizar display del modificador
        this.updateReflejosModifierDisplay();
        
        // Mostrar resultado
        document.getElementById('reaction-result-value').textContent = result;
        
        // Mostrar texto de resultado
        var resultText = document.getElementById('reaction-result-text');
        var bestOption = this.reactionData.reactionOptions[0];
        
        if (bestOption) {
            resultText.className = 'reaction-result-text ' + bestOption.category;
            resultText.innerHTML = '<strong>' + bestOption.name + '</strong><br>' + bestOption.effect;
        }
        
        // Renderizar opciones
        this.renderReactionOptions();
        
        // Ir al paso 2
        this.goToReactionStep(2);
    },

    /**
     * Determina las opciones de reacción disponibles según el resultado
     */
    determineReactionOptions: function(result) {
        var options = [];
        
        // Buscar la opción principal según el resultado
        for (var i = 0; i < this.reactionTable.length; i++) {
            var row = this.reactionTable[i];
            if (result >= row.min && result <= row.max) {
                // Esta es la opción principal
                options.push(row);
                this.reactionData.reactionType = row;
                
                // También añadir opciones inferiores disponibles
                for (var j = i + 1; j < this.reactionTable.length; j++) {
                    options.push(this.reactionTable[j]);
                }
                break;
            }
        }
        
        this.reactionData.reactionOptions = options;
    },

    /**
     * Renderiza las opciones de reacción
     */
    renderReactionOptions: function() {
        var container = document.getElementById('reaction-options');
        if (!container) return;
        
        var html = '';
        var options = this.reactionData.reactionOptions;
        
        for (var i = 0; i < options.length; i++) {
            var opt = options[i];
            var isFirst = i === 0;
            var categoryClass = opt.category;
            
            html += '<div class="reaction-option' + (isFirst ? ' selected' : '') + '" ';
            html += 'data-type="' + opt.type + '" ';
            html += 'onclick="CombatSystem.selectReactionOption(\'' + opt.type + '\')">';
            html += '<div class="reaction-option-name">';
            if (isFirst) html += '⭐ ';
            html += opt.name;
            if (isFirst) html += ' (Mejor opción)';
            html += '</div>';
            html += '<div class="reaction-option-effect">' + opt.effect + '</div>';
            html += '</div>';
        }
        
        container.innerHTML = html;
        
        // Seleccionar la primera opción por defecto
        if (options.length > 0) {
            this.reactionData.selectedReaction = options[0];
        }
    },

    /**
     * Selecciona una opción de reacción
     */
    selectReactionOption: function(type) {
        var options = this.reactionData.reactionOptions;
        var selected = null;
        
        for (var i = 0; i < options.length; i++) {
            if (options[i].type === type) {
                selected = options[i];
                break;
            }
        }
        
        if (!selected) return;
        
        this.reactionData.selectedReaction = selected;
        
        // Actualizar UI
        var allOptions = document.querySelectorAll('.reaction-option');
        allOptions.forEach(function(el) { el.classList.remove('selected'); });
        
        var selectedEl = document.querySelector('.reaction-option[data-type="' + type + '"]');
        if (selectedEl) selectedEl.classList.add('selected');
        
        // Si es esquiva o sin reacción, ir directamente al paso 3
        // Si es choque o bloqueo, necesita seleccionar técnica
        if (selected.category === 'esquiva' || selected.category === 'none') {
            this.prepareStep3ForSimple(selected);
        } else {
            this.prepareStep3ForTechnique(selected);
        }
        
        this.goToReactionStep(3);
    },

    /**
     * Prepara el paso 3 para reacciones simples (esquiva/sin reacción)
     */
    prepareStep3ForSimple: function(reaction) {
        var title = document.getElementById('reaction-step-3-title');
        var techniqueSection = document.getElementById('reaction-technique-section');
        var damagePreview = document.getElementById('reaction-damage-preview');
        
        if (reaction.category === 'esquiva') {
            title.textContent = '✅ Paso 3: Confirmar Esquiva';
            techniqueSection.style.display = 'none';
            
            var damageEvaded = Math.floor(this.reactionData.attackerDamage * (reaction.percent / 100));
            var damageTaken = this.reactionData.attackerDamage - damageEvaded;
            
            this.reactionData.finalDamage = damageTaken;
            
            var html = '<h4>' + reaction.name + '</h4>';
            html += '<p>Daño del ataque: <strong>' + this.reactionData.attackerDamage + '</strong></p>';
            html += '<p>Daño esquivado (' + reaction.percent + '%): <strong style="color: #00c864;">-' + damageEvaded + '</strong></p>';
            html += '<p style="font-size: 18px;">Daño recibido: <strong style="color: #ff6b6b;">' + damageTaken + '</strong></p>';
            
            damagePreview.innerHTML = html;
            damagePreview.style.display = 'block';
            
        } else if (reaction.category === 'none') {
            title.textContent = '❌ Paso 3: Sin Reacción';
            techniqueSection.style.display = 'none';
            
            this.reactionData.finalDamage = this.reactionData.attackerDamage;
            
            var html = '<h4>Sin Reacción</h4>';
            html += '<p>No puedes reaccionar al ataque.</p>';
            html += '<p style="font-size: 18px;">Daño recibido: <strong style="color: #ff6b6b;">' + this.reactionData.attackerDamage + '</strong></p>';
            html += '<p style="color: #ff6b6b;">Los efectos del ataque se aplican completamente.</p>';
            
            damagePreview.innerHTML = html;
            damagePreview.style.display = 'block';
        }
    },

    /**
     * Prepara el paso 3 para reacciones con técnica (choque/bloqueo)
     */
    prepareStep3ForTechnique: function(reaction) {
        var title = document.getElementById('reaction-step-3-title');
        var techniqueSection = document.getElementById('reaction-technique-section');
        var damagePreview = document.getElementById('reaction-damage-preview');
        var techniqueLabel = document.getElementById('reaction-technique-label');
        
        techniqueSection.style.display = 'block';
        damagePreview.style.display = 'none';
        
        if (reaction.category === 'choque') {
            title.textContent = '⚔️ Paso 3: Seleccionar Técnica Ofensiva (Choque)';
            techniqueLabel.textContent = 'Técnica Ofensiva para el Choque:';
            this.renderReactionTechniqueSelect('ofensiva');
        } else if (reaction.category === 'bloqueo') {
            title.textContent = '🛡️ Paso 3: Seleccionar Técnica Defensiva (Bloqueo)';
            techniqueLabel.textContent = 'Técnica Defensiva para el Bloqueo:';
            this.renderReactionTechniqueSelect('defensiva');
        }
        
        // Renderizar armas
        this.renderReactionWeaponSelect();
    },

    /**
     * Renderiza selector de armas para reacción
     */
    renderReactionWeaponSelect: function() {
        var select = document.getElementById('reaction-weapon-select');
        if (!select) return;

        var html = '<option value="">-- Seleccionar arma --</option>';
        
        for (var i = 0; i < this.myWeapons.length; i++) {
            var weapon = this.myWeapons[i];
            var tierText = weapon.tier > 0 ? ' (T' + weapon.tier + ')' : '';
            html += '<option value="' + weapon.objeto_id + '">';
            html += weapon.nombre + tierText;
            html += '</option>';
        }

        select.innerHTML = html;
    },

    /**
     * Renderiza selector de técnicas para reacción
     */
    renderReactionTechniqueSelect: function(tipo) {
        var select = document.getElementById('reaction-technique-select');
        if (!select) return;

        var html = '<option value="">-- Seleccionar técnica --</option>';
        
        for (var i = 0; i < this.myTechniques.length; i++) {
            var tec = this.myTechniques[i];
            var tecTipo = (tec.tipo || tec.clase || '').toLowerCase();
            
            // Filtrar según tipo
            if (tipo === 'ofensiva' && tecTipo.indexOf('ofensiv') === -1) continue;
            if (tipo === 'defensiva' && tecTipo.indexOf('defens') === -1) continue;
            
            // Verificar cooldown
            var onCooldown = this.cooldowns[tec.tid] && this.cooldowns[tec.tid] > 0;
            if (onCooldown) continue;
            
            html += '<option value="' + tec.tid + '">';
            html += tec.nombre + ' (T' + tec.tier + ')';
            html += '</option>';
        }

        select.innerHTML = html;
    },

    /**
     * Selecciona arma para reacción
     */
    selectReactionWeapon: function(weaponId) {
        if (!weaponId) {
            this.reactionData.selectedWeapon = null;
            return;
        }
        
        var weapon = this.myWeapons.find(function(w) { return w.objeto_id === weaponId; });
        this.reactionData.selectedWeapon = weapon;
        
        // Recalcular preview si hay técnica seleccionada
        if (this.reactionData.selectedTechnique) {
            this.updateReactionDamagePreview();
        }
    },

    /**
     * Selecciona técnica para reacción
     */
    selectReactionTechnique: function(tecId) {
        if (!tecId) {
            this.reactionData.selectedTechnique = null;
            document.getElementById('reaction-technique-info').style.display = 'none';
            document.getElementById('reaction-damage-preview').style.display = 'none';
            return;
        }
        
        var tec = this.myTechniques.find(function(t) { return t.tid === tecId; });
        this.reactionData.selectedTechnique = tec;
        
        // Mostrar info de técnica
        var infoContainer = document.getElementById('reaction-technique-info');
        if (tec && infoContainer) {
            var html = '<strong>' + tec.nombre + '</strong> - Tier ' + tec.tier + '<br>';
            html += '<span class="stat-label">' + (tec.clase || 'N/A') + ' | ' + (tec.tipo || 'N/A') + '</span><br>';
            
            // Detectar si es técnica mantenida
            var esMantenida = this.isMaintainedTechnique(tec);
            
            if (esMantenida) {
                // Mantenida: primer turno usa energia_turno + haki
                html += '<span style="color: #ff9800; font-size: 11px;">🔄 Mantenida - Coste:</span> ';
                if (tec.energia_turno > 0) {
                    html += '<span class="tec-cost tec-cost-energia">⚡ ' + tec.energia_turno + '</span>';
                }
                if (tec.haki > 0) {
                    html += '<span class="tec-cost tec-cost-haki">🔮 ' + tec.haki + '</span>';
                }
            } else {
                // Normal: usa energia + haki
                if (tec.energia > 0) {
                    html += '<span class="tec-cost tec-cost-energia">⚡ ' + tec.energia + '</span>';
                }
                if (tec.haki > 0) {
                    html += '<span class="tec-cost tec-cost-haki">🔮 ' + tec.haki + '</span>';
                }
            }
            
            infoContainer.innerHTML = html;
            infoContainer.style.display = 'block';
        }
        
        // Actualizar preview de daño
        this.updateReactionDamagePreview();
    },

    /**
     * Actualiza la previsualización de daño en reacción
     */
    updateReactionDamagePreview: function() {
        var damagePreview = document.getElementById('reaction-damage-preview');
        if (!damagePreview) return;
        
        var reaction = this.reactionData.selectedReaction;
        var tec = this.reactionData.selectedTechnique;
        var weapon = this.reactionData.selectedWeapon;
        var char = this.getEffectiveCharacter(); // Usar personaje/NPC activo (con modificadores)
        
        if (!reaction) {
            damagePreview.style.display = 'none';
            return;
        }
        
        var html = '<h4>' + reaction.name + ' (' + reaction.percent + '%)</h4>';
        
        if (reaction.category === 'choque') {
            // Calcular daño ofensivo
            var effectResult = null;
            var weaponDamageResult = null;
            var totalDamage = 0;
            
            if (tec && tec.efectos && char) {
                effectResult = this.parseAndCalculateEffect(tec.efectos, char);
                if (effectResult) totalDamage += effectResult.totalDamage || 0;
            }
            
            if (weapon && weapon.dano && char) {
                weaponDamageResult = this.parseWeaponFormula(weapon.dano, char);
                if (weaponDamageResult) totalDamage += weaponDamageResult.total || 0;
            }
            
            // Aplicar porcentaje del choque
            var effectiveDamage = Math.floor(totalDamage * (reaction.percent / 100));
            var damageReduction = totalDamage - effectiveDamage;
            
            // Calcular daño neto (ataque enemigo - choque propio)
            var netDamage = this.reactionData.attackerDamage - effectiveDamage;
            if (netDamage < 0) netDamage = 0;
            
            this.reactionData.finalDamage = netDamage;
            this.reactionData.counterDamage = effectiveDamage;
            
            html += '<p><strong>Tu daño de choque:</strong></p>';
            if (effectResult && effectResult.totalDamage > 0) {
                html += '<p>Técnica: ' + effectResult.totalDamage + '</p>';
            }
            if (weaponDamageResult && weaponDamageResult.total > 0) {
                html += '<p>Arma: ' + weaponDamageResult.total + '</p>';
            }
            html += '<p>Daño base total: ' + totalDamage + '</p>';
            html += '<p>Daño efectivo (' + reaction.percent + '%): <strong style="color: #ff9600;">' + effectiveDamage + '</strong></p>';
            html += '<hr style="border-color: rgba(255,255,255,0.2);">';
            html += '<p>Daño del atacante: ' + this.reactionData.attackerDamage + '</p>';
            html += '<p>Tu contraataque: -' + effectiveDamage + '</p>';
            html += '<p style="font-size: 18px;">Daño que recibes: <strong style="color: #ff6b6b;">' + netDamage + '</strong></p>';
            if (effectiveDamage > 0) {
                html += '<p style="color: #ff9600;">⚔️ Tu oponente recibe: <strong>' + effectiveDamage + '</strong> de daño</p>';
            }
            
        } else if (reaction.category === 'bloqueo') {
            // Calcular mitigación defensiva
            var blockResult = null;
            var weaponBlockResult = null;
            var totalMitigation = 0;
            
            if (tec && tec.efectos && char) {
                var effectResult = this.parseAndCalculateEffect(tec.efectos, char);
                if (effectResult) totalMitigation += effectResult.totalDamage || 0;
            }
            
            if (weapon && weapon.bloqueo && char) {
                weaponBlockResult = this.parseWeaponFormula(weapon.bloqueo, char);
                if (weaponBlockResult) totalMitigation += weaponBlockResult.total || 0;
            }
            
            // Aplicar porcentaje del bloqueo
            var effectiveMitigation = Math.floor(totalMitigation * (reaction.percent / 100));
            
            // Calcular daño final
            var finalDamage = this.reactionData.attackerDamage - effectiveMitigation;
            if (finalDamage < 0) finalDamage = 0;
            
            this.reactionData.finalDamage = finalDamage;
            this.reactionData.mitigationTotal = effectiveMitigation;
            
            html += '<p><strong>Tu mitigación de bloqueo:</strong></p>';
            html += '<p>Mitigación base: ' + totalMitigation + '</p>';
            html += '<p>Mitigación efectiva (' + reaction.percent + '%): <strong style="color: #6464ff;">' + effectiveMitigation + '</strong></p>';
            html += '<hr style="border-color: rgba(255,255,255,0.2);">';
            html += '<p>Daño del atacante: ' + this.reactionData.attackerDamage + '</p>';
            html += '<p>Tu mitigación: -' + effectiveMitigation + '</p>';
            html += '<p style="font-size: 18px;">Daño que recibes: <strong style="color: #ff6b6b;">' + finalDamage + '</strong></p>';
        }
        
        damagePreview.innerHTML = html;
        damagePreview.style.display = 'block';
    },

    /**
     * Añade la reacción como primera acción pendiente
     */
    generateReactionCode: function() {
        var reaction = this.reactionData.selectedReaction;
        if (!reaction) {
            alert('Selecciona una opción de reacción');
            return;
        }
        
        // Verificar técnica si es necesaria
        if ((reaction.category === 'choque' || reaction.category === 'bloqueo') && !this.reactionData.selectedTechnique) {
            var reactionType = reaction.category === 'choque' ? 'choque' : 'bloqueo';
            var confirmed = confirm(
                '⚠️ No has seleccionado ninguna técnica\n\n' +
                '¿Quieres reaccionar sin usar técnica?\n\n' +
                '• Confirmar: Reaccionar solo con el porcentaje del ' + reactionType + ' (' + reaction.percent + '%)\n' +
                '• Cancelar: Volver a seleccionar técnica'
            );
            
            if (!confirmed) {
                return; // Volver al paso 3 para seleccionar técnica
            }
        }
        
        var char = this.getEffectiveCharacter(); // Usar personaje/NPC activo (con modificadores)
        var tec = this.reactionData.selectedTechnique;
        var weapon = this.reactionData.selectedWeapon;
        
        // Calcular y almacenar los resultados detallados para el código
        var effectResult = null;
        var weaponDamageResult = null;
        var weaponBlockResult = null;
        
        if (tec && tec.efectos && char) {
            effectResult = this.parseAndCalculateEffect(tec.efectos, char);
        }
        
        if (reaction.category === 'choque') {
            // Para choque, calcular daño del arma
            if (weapon && weapon.dano && char) {
                weaponDamageResult = this.parseWeaponFormula(weapon.dano, char);
            }
        } else if (reaction.category === 'bloqueo') {
            // Para bloqueo, calcular mitigación del arma
            if (weapon && weapon.bloqueo && char) {
                weaponBlockResult = this.parseWeaponFormula(weapon.bloqueo, char);
            }
        }
        
        // Recalcular mitigaciones/daños si no fueron calculados en la vista previa
        if (reaction.category === 'bloqueo') {
            var totalMitigation = 0;
            if (effectResult && effectResult.totalDamage) totalMitigation += effectResult.totalDamage || 0;
            if (weaponBlockResult && weaponBlockResult.total) totalMitigation += weaponBlockResult.total || 0;
            var effectiveMitigation = Math.floor(totalMitigation * (reaction.percent / 100));
            // Guardar en reactionData para que el resto del flujo lo use
            this.reactionData.mitigationTotal = effectiveMitigation;
            // Calcular daño final
            var finalDamageCalc = this.reactionData.attackerDamage - effectiveMitigation;
            if (finalDamageCalc < 0) finalDamageCalc = 0;
            this.reactionData.finalDamage = finalDamageCalc;
        } else if (reaction.category === 'choque') {
            var totalDamageCalc = 0;
            if (effectResult && effectResult.totalDamage) totalDamageCalc += effectResult.totalDamage || 0;
            if (weaponDamageResult && weaponDamageResult.total) totalDamageCalc += weaponDamageResult.total || 0;
            var effectiveDamage = Math.floor(totalDamageCalc * (reaction.percent / 100));
            this.reactionData.counterDamage = effectiveDamage;
            var netDamageCalc = this.reactionData.attackerDamage - effectiveDamage;
            if (netDamageCalc < 0) netDamageCalc = 0;
            this.reactionData.finalDamage = netDamageCalc;
        }

        // Crear acción de reacción
        var reactionAction = {
            actionType: 'reaction',
            reaction: reaction,
            attackerName: this.reactionData.attackerName,
            attackerTA: this.reactionData.attackerTA,
            attackerDamage: this.reactionData.attackerDamage,
            reactionResult: this.reactionData.reactionResult,
            finalDamage: this.reactionData.finalDamage,
            counterDamage: this.reactionData.counterDamage || 0,
            mitigationTotal: this.reactionData.mitigationTotal || 0,
            technique: tec,
            weapon: weapon,
            reflejos: this.reactionData.reflejosBase || 0, // Usar el valor guardado
            reflejosModifier: this.reactionData.reflejosModifier || 0,
            reflejosModifierReason: this.reactionData.reflejosModifierReason || '',
            // Datos detallados del cálculo
            effectResult: effectResult,
            weaponDamageResult: weaponDamageResult,
            weaponBlockResult: weaponBlockResult
        };
        
        // Insertar como PRIMERA acción
        this.pendingActions.unshift(reactionAction);
        
        // Cerrar modal de reacción y abrir el principal
        this.closeReactionMode();
        this.open();
        
        // Si es técnica mantenida, iniciar tracking (después de abrir para que se vea)
        if (tec && this.isMaintainedTechnique(tec)) {
            this.startMaintainedTechnique(tec);
        }
        
        // Renderizar acciones pendientes
        this.renderPendingActions();
        this.setStatus('Reacción añadida: ' + reaction.name);
    },

    // ============================================
    // SISTEMA DE TÉCNICAS MANTENIDAS
    // ============================================

    /**
     * Detecta técnicas mantenidas en los posts del tema
     * Busca tags [mantenida=TID] o patrones de activación de mantenidas
     */
    detectMaintainedTechniques: function() {
        // Preservar técnicas nuevas añadidas esta sesión (ej: desde reacción)
        var newThisSession = this.activeMaintained.filter(function(m) { return m.esNueva; });
        
        this.activeMaintained = [];
        
        if (!this.combatData || !this.combatData.posts) {
            // Restaurar las nuevas de esta sesión
            this.activeMaintained = newThisSession;
            this.renderMaintainedTechniques();
            return;
        }
        
        var currentUid = this.combatData.current_uid;
        var turnCount = 0;
        
        // Mapa para rastrear mantenidas activas: {tid: {turnIniciado, turnosActivos}}
        var maintainedMap = {};
        
        for (var i = 0; i < this.combatData.posts.length; i++) {
            var post = this.combatData.posts[i];
            
            // Solo posts del usuario actual
            if (post.uid != currentUid) continue;
            
            turnCount++;
            var content = post.message || '';
            
            // Buscar inicio de mantenidas: 
            // - BBCode: [mantenida=TID] o [mantenida_inicio=TID]
            // - Comentario HTML: <!-- mantenida:TID -->
            var maintainedStartRegex = /\[mantenida(?:_inicio)?=([^\]]+)\]/gi;
            var maintainedHtmlRegex = /<!-- mantenida:([^\s]+) -->/gi;
            var match;
            
            // Buscar formato BBCode
            while ((match = maintainedStartRegex.exec(content)) !== null) {
                var tid = match[1];
                if (!maintainedMap[tid]) {
                    maintainedMap[tid] = {
                        turnIniciado: turnCount,
                        turnosActivos: 0
                    };
                }
            }
            
            // Buscar formato comentario HTML (generado por PHP)
            while ((match = maintainedHtmlRegex.exec(content)) !== null) {
                var tid = match[1];
                if (!maintainedMap[tid]) {
                    maintainedMap[tid] = {
                        turnIniciado: turnCount,
                        turnosActivos: 0
                    };
                }
            }
            
            // Buscar fin de mantenidas: 
            // - BBCode: [mantenida_fin=TID]
            // - Comentario HTML: <!-- mantenida_fin:TID -->
            var maintainedEndRegex = /\[mantenida_fin=([^\]]+)\]/gi;
            var maintainedEndHtmlRegex = /<!-- mantenida_fin:([^\s]+) -->/gi;
            
            while ((match = maintainedEndRegex.exec(content)) !== null) {
                var tid = match[1];
                delete maintainedMap[tid];
            }
            while ((match = maintainedEndHtmlRegex.exec(content)) !== null) {
                var tid = match[1];
                delete maintainedMap[tid];
            }
            
            // Incrementar turnos activos para todas las mantenidas
            for (var tid in maintainedMap) {
                if (maintainedMap.hasOwnProperty(tid)) {
                    maintainedMap[tid].turnosActivos++;
                }
            }
        }
        
        // Convertir el mapa a array de mantenidas activas
        for (var tid in maintainedMap) {
            if (maintainedMap.hasOwnProperty(tid)) {
                var tec = this.myTechniques.find(function(t) { return t.tid === tid; });
                if (tec) {
                    this.activeMaintained.push({
                        tid: tid,
                        nombre: tec.nombre,
                        turnosActivos: maintainedMap[tid].turnosActivos,
                        energiaTurno: tec.energia_turno || 0,
                        hakiTurno: tec.haki_turno || 0,
                        enfriamiento: tec.enfriamiento || 0,
                        continuar: true // Por defecto continuar manteniendo
                    });
                }
            }
        }
        
        // Restaurar técnicas nuevas añadidas esta sesión (ej: desde reacción)
        // Solo si no están ya en la lista
        for (var i = 0; i < newThisSession.length; i++) {
            var newTec = newThisSession[i];
            var alreadyExists = this.activeMaintained.some(function(m) { return m.tid === newTec.tid; });
            if (!alreadyExists) {
                this.activeMaintained.push(newTec);
            }
        }
        
        this.renderMaintainedTechniques();
    },

    /**
     * Renderiza la lista de técnicas mantenidas activas
     */
    renderMaintainedTechniques: function() {
        var container = document.getElementById('combat-maintained-techniques');
        var costsContainer = document.getElementById('maintained-costs-summary');
        var costsDetail = document.getElementById('maintained-costs-detail');
        
        if (!container) return;
        
        if (this.activeMaintained.length === 0) {
            container.innerHTML = '<em>Sin técnicas mantenidas</em>';
            if (costsContainer) costsContainer.style.display = 'none';
            return;
        }
        
        var html = '';
        var totalEnergia = 0;
        var totalHaki = 0;
        
        for (var i = 0; i < this.activeMaintained.length; i++) {
            var m = this.activeMaintained[i];
            var isReleasing = !m.continuar;
            
            html += '<div class="maintained-technique-item' + (isReleasing ? ' releasing' : '') + '">';
            html += '<input type="checkbox" class="maintained-checkbox" ';
            html += 'id="maintained-' + m.tid + '" ';
            html += (m.continuar ? 'checked' : '') + ' ';
            html += 'onchange="CombatSystem.toggleMaintained(\'' + m.tid + '\')">';
            
            html += '<div class="maintained-info">';
            html += '<div class="maintained-name">' + m.nombre + '</div>';
            html += '<div class="maintained-turns">Turnos activos: ' + m.turnosActivos + '</div>';
            html += '<div class="maintained-costs">';
            if (m.energiaTurno > 0) html += '⚡ ' + m.energiaTurno + '/turno ';
            if (m.hakiTurno > 0) html += '💜 ' + m.hakiTurno + ' haki/turno';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="maintained-status ' + (isReleasing ? 'releasing' : 'active') + '">';
            html += isReleasing ? '🔓 Finalizar' : '🔒 Mantenener';
            html += '</div>';
            
            html += '</div>';
            
            if (m.continuar) {
                totalEnergia += m.energiaTurno;
                totalHaki += m.hakiTurno;
            }
        }
        
        container.innerHTML = html;
        
        // Mostrar resumen de costes
        if (costsContainer && costsDetail) {
            if (totalEnergia > 0 || totalHaki > 0) {
                var costsHtml = '';
                if (totalEnergia > 0) costsHtml += '⚡ Energía: <strong>' + totalEnergia + '</strong> ';
                if (totalHaki > 0) costsHtml += '💜 Haki: <strong>' + totalHaki + '</strong>';
                costsDetail.innerHTML = costsHtml;
                costsContainer.style.display = 'block';
            } else {
                costsContainer.style.display = 'none';
            }
        }
    },

    /**
     * Alterna el estado de una técnica mantenida
     */
    toggleMaintained: function(tid) {
        for (var i = 0; i < this.activeMaintained.length; i++) {
            if (this.activeMaintained[i].tid === tid) {
                this.activeMaintained[i].continuar = !this.activeMaintained[i].continuar;
                
                // Si se suelta, añadir al cooldown
                if (!this.activeMaintained[i].continuar) {
                    this.releasedMaintained.push(this.activeMaintained[i]);
                    // Añadir enfriamiento
                    if (this.activeMaintained[i].enfriamiento > 0) {
                        this.cooldowns[tid] = this.activeMaintained[i].enfriamiento;
                    }
                } else {
                    // Si se reactiva, quitar del released
                    this.releasedMaintained = this.releasedMaintained.filter(function(m) {
                        return m.tid !== tid;
                    });
                    delete this.cooldowns[tid];
                }
                break;
            }
        }
        
        this.renderMaintainedTechniques();
        this.renderCooldowns();
    },

    /**
     * Calcula el coste total de mantener técnicas este turno
     */
    calculateMaintainedCosts: function() {
        var costs = { energia: 0, haki: 0 };
        
        for (var i = 0; i < this.activeMaintained.length; i++) {
            var m = this.activeMaintained[i];
            // Excluir técnicas nuevas (esNueva) - su costo ya se cuenta en la acción que las usó
            if (m.continuar && !m.esNueva) {
                costs.energia += m.energiaTurno;
                costs.haki += m.hakiTurno;
            }
        }
        
        return costs;
    },

    /**
     * Genera el código BBCode para las técnicas mantenidas
     */
    generateMaintainedCode: function() {
        var code = '';
        // Excluir técnicas nuevas (esNueva) - ya se muestran en la acción que las usó
        var maintained = this.activeMaintained.filter(function(m) { return m.continuar && !m.esNueva; });
        var released = this.activeMaintained.filter(function(m) { return !m.continuar && !m.esNueva; });
        
        if (maintained.length > 0) {
            code += '[extra=Técnicas Mantenidas]\n';
            code += '[b]TÉCNICAS EN MANTENIMIENTO[/b]\n\n';
            
            for (var i = 0; i < maintained.length; i++) {
                var m = maintained[i];
                code += '[mantenida=' + m.tid + ']\n';
                code += '[tecnica=' + m.tid + ']\n';
                code += '• ' + m.nombre + ' (Turno ' + (m.turnosActivos + 1) + ')\n';
                if (m.energiaTurno > 0 || m.hakiTurno > 0) {
                    code += '  Coste: ';
                    if (m.energiaTurno > 0) code += m.energiaTurno + ' energía ';
                    if (m.hakiTurno > 0) code += m.hakiTurno + ' haki';
                    code += '\n';
                }
            }
            
            code += '[/extra]\n\n';
        }
        
        if (released.length > 0) {
            code += '[extra=Técnicas Finalizadas]\n';
            code += '[b]TÉCNICAS QUE DEJAS DE MANTENER[/b]\n\n';
            
            for (var i = 0; i < released.length; i++) {
                var m = released[i];
                code += '[mantenida_fin=' + m.tid + ']\n';
                code += '• ' + m.nombre + ' - Finalizada\n';
                if (m.enfriamiento > 0) {
                    code += '  ⏱️ Enfriamiento: ' + m.enfriamiento + ' turnos\n';
                }
            }
            
            code += '[/extra]\n\n';
        }
        
        return code;
    },

    /**
     * Inicia una nueva técnica mantenida
     * Se llama cuando se usa una técnica de tipo mantenida
     */
    startMaintainedTechnique: function(tec) {
        // Verificar si ya está activa
        var exists = this.activeMaintained.some(function(m) { return m.tid === tec.tid; });
        if (exists) return;
        
        this.activeMaintained.push({
            tid: tec.tid,
            nombre: tec.nombre,
            turnosActivos: 0, // Es el primer turno
            energiaTurno: tec.energia_turno || 0,
            hakiTurno: tec.haki_turno || 0,
            haki: tec.haki || 0, // Haki inicial (solo primer turno)
            enfriamiento: tec.enfriamiento || 0,
            continuar: true,
            esNueva: true // Marca que es nueva este turno
        });
        
        this.renderMaintainedTechniques();
    },

    /**
     * Determina si una técnica es de tipo mantenida
     */
    isMaintainedTechnique: function(tec) {
        if (!tec) return false;
        var tipo = (tec.tipo || tec.clase || '').toLowerCase();
        return tipo.indexOf('mantenida') !== -1 || 
               tipo.indexOf('sostenida') !== -1 ||
               (tec.energia_turno && tec.energia_turno > 0);
    },

    /**
     * Obtiene la vida actual del personaje basándose en los posts del tema
     * Busca el último tag [vida=X, Y] del usuario
     */
    getCharacterCurrentHP: function() {
        if (!this.combatData || !this.combatData.my_character) return 0;
        
        var effectiveChar = this.getEffectiveCharacter();
        var vidaMax = (effectiveChar && effectiveChar.resources && effectiveChar.resources.vitalidad) ? effectiveChar.resources.vitalidad : (this.combatData.my_character.resources.vitalidad || 0);
        var vidaActual = vidaMax; // Por defecto, vida completa
        
        if (!this.combatData.posts) return vidaActual;
        
        var currentUid = this.combatData.current_uid;
        
        // Buscar el último tag [vida=X, Y] del usuario
        for (var i = this.combatData.posts.length - 1; i >= 0; i--) {
            var post = this.combatData.posts[i];
            if (post.uid != currentUid) continue;
            
            var content = post.message || '';
            var vidaMatch = content.match(/\[vida=(-?\d+)\s*,\s*(\d+)\]/i);
            
            if (vidaMatch) {
                vidaActual = parseInt(vidaMatch[1]);
                break;
            }
        }
        
        // Aplicar modificadores de recurso (vida) solo scope=current
        var modSum = 0;
        for (var i = 0; i < this.modifiers.length; i++) {
            var m = this.modifiers[i];
            if (m.targetType === 'resource' && m.key === 'vida' && m.scope === 'current') modSum += parseInt(m.value) || 0;
        }
        vidaActual = vidaActual + modSum;
        // No recortar por encima del máximo: permitir valores temporales superiores al máximo si aplica un modificador 'current'
        if (vidaActual < 0) vidaActual = 0;
        return vidaActual;
    },

    /**
     * Obtiene el daño total recibido en el tema
     */
    getTotalDamageReceived: function() {
        var totalDamage = 0;
        
        if (!this.combatData || !this.combatData.posts) return totalDamage;
        
        var currentUid = this.combatData.current_uid;
        
        // Buscar todos los posts de otros usuarios que contengan ataques contra este usuario
        for (var i = 0; i < this.combatData.posts.length; i++) {
            var post = this.combatData.posts[i];
            if (post.uid == currentUid) continue;
            
            var content = post.message || '';
            
            // Buscar daño recibido
            var damageMatch = content.match(/DAÑO\s*RECIBIDO\s*:\s*(\d+)/i);
            if (damageMatch) {
                totalDamage += parseInt(damageMatch[1]);
            }
        }
        
        return totalDamage;
    },

    /**
     * Obtiene la energía actual del personaje basándose en los posts del tema
     * Busca el último tag [energia=X, Y] del usuario
     */
    getCharacterCurrentEnergia: function() {
        if (!this.combatData || !this.combatData.my_character) return 0;
        
        var effectiveChar = this.getEffectiveCharacter();
        var energiaMax = (effectiveChar && effectiveChar.resources && effectiveChar.resources.energia) ? effectiveChar.resources.energia : (this.combatData.my_character.resources.energia || 0);
        var energiaActual = energiaMax; // Por defecto, energía completa
        
        if (!this.combatData.posts) return energiaActual;
        
        var currentUid = this.combatData.current_uid;
        
        // Buscar el último tag [energia=X, Y] del usuario
        for (var i = this.combatData.posts.length - 1; i >= 0; i--) {
            var post = this.combatData.posts[i];
            if (post.uid != currentUid) continue;
            
            var content = post.message || '';
            var energiaMatch = content.match(/\[energia=(-?\d+)\s*,\s*(\d+)\]/i);
            
            if (energiaMatch) {
                energiaActual = parseInt(energiaMatch[1]);
                break;
            }
        }
        
        // Aplicar modificadores de recurso (energia, scope=current)
        var modSumE = 0;
        for (var j = 0; j < this.modifiers.length; j++) {
            var me = this.modifiers[j];
            if (me.targetType === 'resource' && me.key === 'energia' && me.scope === 'current') modSumE += parseInt(me.value) || 0;
        }
        energiaActual = energiaActual + modSumE;
        // No recortar por encima del máximo para current (permitir aumento temporal)
        if (energiaActual < 0) energiaActual = 0;
        return energiaActual;
    },

    /**
     * Obtiene el haki actual del personaje basándose en los posts del tema
     * Busca el último tag [haki=X, Y] del usuario
     */
    getCharacterCurrentHaki: function() {
        if (!this.combatData || !this.combatData.my_character) return 0;
        
        var hakiMax = this.combatData.my_character.resources.haki || 0;
        var hakiActual = hakiMax; // Por defecto, haki completo
        
        if (!this.combatData.posts) return hakiActual;
        
        var currentUid = this.combatData.current_uid;
        
        // Buscar el último tag [haki=X, Y] del usuario
        for (var i = this.combatData.posts.length - 1; i >= 0; i--) {
            var post = this.combatData.posts[i];
            if (post.uid != currentUid) continue;
            
            var content = post.message || '';
            var hakiMatch = content.match(/\[haki=(-?\d+)\s*,\s*(\d+)\]/i);
            
            if (hakiMatch) {
                hakiActual = parseInt(hakiMatch[1]);
                break;
            }
        }
        
        // Aplicar modificadores de recurso (haki) solo scope=current
        var modSumH = 0;
        for (var k = 0; k < this.modifiers.length; k++) {
            var mh = this.modifiers[k];
            if (mh.targetType === 'resource' && mh.key === 'haki' && mh.scope === 'current') modSumH += parseInt(mh.value) || 0;
        }
        hakiActual = hakiActual + modSumH;
        // No recortar por encima del máximo para current (permitir aumento temporal)
        if (hakiActual < 0) hakiActual = 0;
        return hakiActual;
    },

    // Registrar comando para SCEditor




};

// Registrar comando para SCEditor
if (typeof $.sceditor !== 'undefined') {
    $.sceditor.command.set('combate', {
        exec: function() {
            CombatSystem.open();
        },
        tooltip: "Sistema de Combate"
    });
    
    $.sceditor.command.set('reaccion', {
        exec: function() {
            CombatSystem.openReactionMode();
        },
        tooltip: "Reacción Defensiva"
    });
}

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    CombatSystem.init();
});
