<html>

<head>
  <title>Generador de Personajes</title>
  {$headerinclude}
  <script type="text/javascript" src="{$mybb->asset_url}/jscripts/generadorpj.js"></script>
  <style>
    .generador-container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
      background-color: #f5f5f5;
      border-radius: 10px;
    }
    
    .seccion {
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .titulo-seccion {
      font-family: moonGetHeavy;
      font-size: 20px;
      color: #0055bb;
      margin-bottom: 15px;
      padding-bottom: 10px;
      border-bottom: 2px solid #0055bb;
    }
    
    .campo-grupo {
      display: flex;
      gap: 15px;
      margin-bottom: 15px;
      flex-wrap: wrap;
    }
    
    .campo {
      flex: 1;
      min-width: 200px;
    }
    
    .campo label {
      display: block;
      font-family: moonGetHeavy;
      font-size: 14px;
      margin-bottom: 5px;
      color: #333;
    }
    
    .campo input, .campo select, .campo textarea {
      width: 100%;
      padding: 8px;
      border: 2px solid #ddd;
      border-radius: 4px;
      font-family: InterRegular;
      font-size: 14px;
    }
    
    .campo textarea {
      resize: vertical;
      min-height: 80px;
    }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
      gap: 15px;
      margin-bottom: 20px;
    }
    
    .stat-item {
      text-align: center;
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      border: 2px solid #e9ecef;
    }
    
    .stat-label {
      font-family: moonGetHeavy;
      font-size: 12px;
      color: #666;
      margin-bottom: 5px;
    }
    
    .stat-value {
      font-family: moonGetHeavy;
      font-size: 24px;
      color: #0055bb;
      margin-bottom: 5px;
    }
    
    .stat-input {
      width: 60px !important;
      text-align: center;
      font-size: 16px;
      font-weight: bold;
    }
    
    .virtudes-defectos {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    
    .virtudes-lista, .defectos-lista {
      max-height: 300px;
      overflow-y: auto;
      border: 2px solid #ddd;
      border-radius: 8px;
      padding: 10px;
      background-color: #fafafa;
    }
    
    .virtud-item, .defecto-item {
      background-color: #f8f9fa;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      border: 2px solid transparent;
      user-select: none;
    }
    
    .virtud-item {
      border-left: 4px solid #28a745;
    }
    
    .defecto-item {
      border-left: 4px solid #dc3545;
    }
    
    .virtud-item:hover {
      background-color: #e9f5ec;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .defecto-item:hover {
      background-color: #f5e9e9;
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .virtud-item.selected {
      background-color: #d4edda;
      border-color: #28a745;
      border-width: 2px;
      box-shadow: 0 3px 6px rgba(40, 167, 69, 0.2);
    }
    
    .defecto-item.selected {
      background-color: #f8d7da;
      border-color: #dc3545;
      border-width: 2px;
      box-shadow: 0 3px 6px rgba(220, 53, 69, 0.2);
    }
    
    .puntos-virtud {
      color: #28a745;
      font-weight: bold;
      font-size: 14px;
    }
    
    .puntos-defecto {
      color: #dc3545;
      font-weight: bold;
      font-size: 14px;
    }
    
    .seleccionados-container {
      margin-top: 20px;
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      border: 2px solid #e9ecef;
    }
    
    .seleccionados-titulo {
      font-family: moonGetHeavy;
      font-size: 16px;
      color: #333;
      margin-bottom: 10px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 5px;
    }
    
    .seleccionados-lista {
      font-family: InterRegular;
      font-size: 14px;
      line-height: 1.5;
      color: #555;
    }
    
    .seleccionados-virtudes {
      color: #28a745;
      font-weight: bold;
    }
    
    .seleccionados-defectos {
      color: #dc3545;
      font-weight: bold;
    }
    
    .balance-info {
      background-color: #e9ecef;
      padding: 15px;
      border-radius: 8px;
      text-align: center;
      margin-bottom: 20px;
    }
    
    .balance-valor {
      font-family: moonGetHeavy;
      font-size: 32px;
      margin-bottom: 10px;
    }
    
    .balance-positivo { color: #28a745; }
    .balance-negativo { color: #dc3545; }
    .balance-neutro { color: #666; }
    
    .armas-selector {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 20px;
    }
    
    .arma-slot {
      background-color: #f8f9fa;
      padding: 15px;
      border-radius: 8px;
      border: 2px solid #e9ecef;
    }
    
    .arma-info {
      margin-top: 10px;
      padding: 10px;
      background-color: white;
      border-radius: 4px;
      border: 1px solid #ddd;
    }
    
    .tecnicas-container {
      max-height: 400px;
      overflow-y: auto;
      border: 2px solid #ddd;
      border-radius: 8px;
      padding: 15px;
    }
    
    .tecnica-item {
      background-color: #f8f9fa;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    
    .tecnica-item:hover {
      background-color: #e9ecef;
    }
    
    .tecnica-item.selected {
      background-color: #d4edda;
      border: 2px solid #28a745;
    }
    
    .filtros-tecnicas {
      display: flex;
      gap: 10px;
      margin-bottom: 15px;
      flex-wrap: wrap;
    }
    
    .filtro-btn {
      padding: 5px 10px;
      background-color: #6c757d;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-family: moonGetHeavy;
      font-size: 12px;
    }
    
    .filtro-btn.active {
      background-color: #0055bb;
    }
    
    .resumen-combate {
      background-color: #e3f2fd;
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
    }
    
    .combate-stats {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
    }
    
    .combate-stat {
      text-align: center;
      background-color: white;
      padding: 15px;
      border-radius: 8px;
      border: 2px solid #0055bb;
    }
    
    .combate-valor {
      font-family: moonGetHeavy;
      font-size: 28px;
      color: #0055bb;
      margin-bottom: 5px;
    }
    
    .combate-label {
      font-family: moonGetHeavy;
      font-size: 12px;
      color: #666;
    }
    
    .btn-generar {
      background-color: #ff7e00;
      color: white;
      border: none;
      padding: 15px 30px;
      font-family: moonGetHeavy;
      font-size: 18px;
      border-radius: 8px;
      cursor: pointer;
      display: block;
      margin: 20px auto;
      transition: background-color 0.3s ease;
    }
    
    .btn-generar:hover {
      background-color: #e66900;
    }

    .temporada, .raza, .faccion, .subraza {
      filter: none;
      transition: 0.50s ease;	
    }
      
    .temporada:hover, .raza:hover, .faccion:hover, .subraza:hover, .subraza-hover {
      filter: drop-shadow(0px 0px 10px #000);
    }
      
    #raza_descripcion {
      font-family: InterRegular; 
      text-align: justify; 
      letter-spacing: 0px; 
      padding: 6px; 
      font-size: 14px;
    }
      
    #raza_caracteristicas {
      font-family: InterRegular; 
      text-align: left; 
      letter-spacing: 0px; 
      padding: 6px; 
      font-size: 14px;
    }
      
    .raza {
      border-left: 2px solid black; 
      border-bottom: 2px solid black; 
      width: 110px;
      height:148px;
      filter: grayscale(0.9);
    }
      
    .raza_end {
      border-right: 2px solid black;
    }
      
    .subraza {
      border-left: 2px solid black; border-bottom: 2px solid black; height: 126px;width: 110px;filter: grayscale(0.9);border-right: 2px solid black;
    }
      
    .subraza_empty {
      border-left: 2px solid black; border-bottom: 2px solid black; height: 126px;width: 110px;filter: grayscale(0.9);cursor: auto;
    }
      
    .subraza_end {
      border-right: 2px solid black;
    }
      
    #balance {
      background-color: #a0a0a0;
      height: 30px;
      font-size: 20px;
      text-shadow: 1px 1px 1px black;
      color: white;
    }
      
    #balance_positivo, #balance_negativo {
      height: 40px;
      font-size: 20px;
      text-shadow: 1px 1px 1px black;
      color: white;
      width: 50%;
    }
      
    #balance_positivo {
      background: linear-gradient(180deg, rgba(56, 200, 66, 1) 0%, rgba(17, 68, 21, 1) 100%);
    }
      
    #balance_negativo {
      background: linear-gradient(180deg, rgba(200, 56, 56, 1) 0%, rgba(68, 17, 17, 1) 100%);
    }
      
    #balance_positivo span, #balance_negativo span {
      position: relative;
      top: 5px;
    }
  </style>
</head>

<body>
  {$header}
  <div class="generador-container">
    <h1 style="text-align: center; font-family: moonGetHeavy; color: #0055bb; font-size: 32px; margin-bottom: 30px;">
      GENERADOR DE PERSONAJES
    </h1>
    
    <!-- Información Básica -->
    <div class="seccion">
      <div class="titulo-seccion">INFORMACIÓN BÁSICA</div>
      <div class="campo-grupo">
        <div class="campo">
          <label>Nombre del Personaje</label>
          <input type="text" id="nombre-personaje" placeholder="Ingresa el nombre">
        </div>
        <div class="campo">
          <label>Nivel</label>
          <input type="number" id="nivel-personaje" value="1" min="1" max="999" onchange="actualizarNivel()">
        </div>
        <div class="campo">
          <label>Oficio</label>
          <select id="oficio-personaje">
            <option value="Navegante">Navegante</option>
            <option value="Cocinero">Cocinero</option>
            <option value="Medico">Medico</option>
            <option value="Explorador">Explorador</option>
            <option value="Artesano">Artesano</option>
            <option value="Inventor">Inventor</option>
            <option value="Carpintero">Carpintero</option>
            <option value="Mercader">Mercader</option>
          </select>
        </div>
        <div class="campo">
          <label>Disciplina Bélica</label>
          <select id="disciplina-personaje">
            <option value="Combatiente">Combatiente</option>
            <option value="Espadachin">Espadachin</option>
            <option value="Contundente">Contundente</option>
            <option value="Tirador">Tirador</option>
            <option value="Especialista">Especialista</option>
            <option value="Artillero">Artillero</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Estadísticas -->
    <div class="seccion">
      <div class="titulo-seccion">ESTADÍSTICAS</div>
      <div class="stats-grid">
        <div class="stat-item">
          <div class="stat-label">NIVEL</div>
          <div class="stat-value" id="stat-display-NIV">1</div>
          <input type="number" class="stat-input" id="stat-NIV" value="1" min="1" max="999" onchange="actualizarEstadistica('NIV')">
        </div>
        <div class="stat-item">
          <div class="stat-label">FUERZA</div>
          <div class="stat-value" id="stat-display-FUE">0</div>
          <input type="number" class="stat-input" id="stat-FUE" value="0" min="0" max="999" onchange="actualizarEstadistica('FUE')">
        </div>
        <div class="stat-item">
          <div class="stat-label">AGILIDAD</div>
          <div class="stat-value" id="stat-display-AGI">0</div>
          <input type="number" class="stat-input" id="stat-AGI" value="0" min="0" max="999" onchange="actualizarEstadistica('AGI')">
        </div>
        <div class="stat-item">
          <div class="stat-label">PUNTERÍA</div>
          <div class="stat-value" id="stat-display-PUN">0</div>
          <input type="number" class="stat-input" id="stat-PUN" value="0" min="0" max="999" onchange="actualizarEstadistica('PUN')">
        </div>
        <div class="stat-item">
          <div class="stat-label">DESTREZA</div>
          <div class="stat-value" id="stat-display-DES">0</div>
          <input type="number" class="stat-input" id="stat-DES" value="0" min="0" max="999" onchange="actualizarEstadistica('DES')">
        </div>
        <div class="stat-item">
          <div class="stat-label">RESISTENCIA</div>
          <div class="stat-value" id="stat-display-RES">0</div>
          <input type="number" class="stat-input" id="stat-RES" value="0" min="0" max="999" onchange="actualizarEstadistica('RES')">
        </div>
        <div class="stat-item">
          <div class="stat-label">REFLEJOS</div>
          <div class="stat-value" id="stat-display-REF">0</div>
          <input type="number" class="stat-input" id="stat-REF" value="0" min="0" max="999" onchange="actualizarEstadistica('REF')">
        </div>
        <div class="stat-item">
          <div class="stat-label">VOLUNTAD</div>
          <div class="stat-value" id="stat-display-VOL">0</div>
          <input type="number" class="stat-input" id="stat-VOL" value="0" min="0" max="999" onchange="actualizarEstadistica('VOL')">
        </div>
      </div>
    </div>

    <!-- Virtudes y Defectos -->
    <div class="seccion">
      <div class="titulo-seccion">VIRTUDES Y DEFECTOS</div>
      
      <div class="balance-info">
        <div class="balance-valor" id="balance-total">0</div>
        <div>Balance de Puntos</div>
        <div style="display: flex; gap: 20px; margin-top: 10px; justify-content: center;">
          <div>Virtudes: <span id="puntos-virtudes" style="color: #28a745; font-weight: bold;">0</span></div>
          <div>Defectos: <span id="puntos-defectos" style="color: #dc3545; font-weight: bold;">0</span></div>
        </div>
      </div>

      <div class="virtudes-defectos">
        <div>
          <h4 style="color: #28a745; margin-bottom: 15px;">VIRTUDES</h4>
          <div class="virtudes-lista" id="virtudes-lista"></div>
        </div>
        <div>
          <h4 style="color: #dc3545; margin-bottom: 15px;">DEFECTOS</h4>
          <div class="defectos-lista" id="defectos-lista"></div>
        </div>
      </div>

      <!-- Campo de Virtudes y Defectos Seleccionados -->
      <div class="seleccionados-container">
        <div class="seleccionados-titulo">VIRTUDES Y DEFECTOS SELECCIONADOS</div>
        <div class="seleccionados-lista" id="seleccionados-display">
          <div class="seleccionados-virtudes" id="virtudes-seleccionadas-texto">Virtudes: Ninguna seleccionada</div>
          <div class="seleccionados-defectos" id="defectos-seleccionados-texto" style="margin-top: 8px;">Defectos: Ninguno seleccionado</div>
        </div>
      </div>
    </div>

    <!-- Armas -->
    <div class="seccion">
      <div class="titulo-seccion">ARMAS EQUIPADAS</div>
      <div class="armas-selector">
        <div class="arma-slot">
          <label>Arma Principal</label>
          <select id="arma-principal" onchange="seleccionarArma(this.value, 0)">
            <option value="">Sin arma</option>
          </select>
          <div id="arma-principal-info" class="arma-info" style="display: none;"></div>
        </div>
        
        <div class="arma-slot">
          <label>Arma Secundaria</label>
          <select id="arma-secundaria" onchange="seleccionarArma(this.value, 1)">
            <option value="">Sin arma</option>
          </select>
          <div id="arma-secundaria-info" class="arma-info" style="display: none;"></div>
        </div>
        
        <div class="arma-slot">
          <label>Arma Terciaria</label>
          <select id="arma-terciaria" onchange="seleccionarArma(this.value, 2)">
            <option value="">Sin arma</option>
          </select>
          <div id="arma-terciaria-info" class="arma-info" style="display: none;"></div>
        </div>
      </div>
    </div>

    <!-- Técnicas -->
    <div class="seccion">
      <div class="titulo-seccion">TÉCNICAS</div>
      
      <div class="filtros-tecnicas">
        <div style="margin-right: 20px;">
          <strong>Tiers:</strong>
          <button class="filtro-btn" onclick="filtrarTier(1)" id="filtro-t1">T1</button>
          <button class="filtro-btn" onclick="filtrarTier(2)" id="filtro-t2">T2</button>
          <button class="filtro-btn" onclick="filtrarTier(3)" id="filtro-t3">T3</button>
          <button class="filtro-btn" onclick="filtrarTier(4)" id="filtro-t4">T4</button>
          <button class="filtro-btn" onclick="filtrarTier(5)" id="filtro-t5">T5</button>
        </div>
        <div>
          <strong>Tipos:</strong>
          <button class="filtro-btn" onclick="filtrarTipo('ofensiva')" id="filtro-ofensiva">Ofensiva</button>
          <button class="filtro-btn" onclick="filtrarTipo('defensiva')" id="filtro-defensiva">Defensiva</button>
          <button class="filtro-btn" onclick="filtrarTipo('utilidad')" id="filtro-utilidad">Utilidad</button>
          <button class="filtro-btn" onclick="filtrarTipo('pasiva')" id="filtro-pasiva">Pasiva</button>
        </div>
      </div>
      
      <div class="campo">
        <label>Técnica Seleccionada</label>
        <select id="tecnica-seleccionada" onchange="seleccionarTecnica(this.value)">
          <option value="">Sin técnica</option>
        </select>
      </div>
      
      <div id="tecnica-info" style="display: none; margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-radius: 8px;">
        <div id="tecnica-detalles"></div>
      </div>
    </div>

    <!-- Resumen de Combate -->
    <div class="resumen-combate">
      <div class="titulo-seccion" style="color: white; border-bottom-color: white;">RESUMEN DE COMBATE</div>
      
      <div style="text-align: center; margin-bottom: 20px;">
        <label style="margin-right: 20px; color: white;">
          <input type="radio" name="modo-combate" value="ataque" checked onchange="cambiarModoCombate('ataque')">
          <span style="font-family: moonGetHeavy; margin-left: 5px;">MODO ATAQUE</span>
        </label>
        <label style="color: white;">
          <input type="radio" name="modo-combate" value="defensa" onchange="cambiarModoCombate('defensa')">
          <span style="font-family: moonGetHeavy; margin-left: 5px;">MODO DEFENSA</span>
        </label>
      </div>
      
      <div class="combate-stats">
        <div class="combate-stat">
          <div class="combate-valor" id="combate-golpe-basico">0</div>
          <div class="combate-label" id="combate-golpe-label">GOLPE BÁSICO</div>
        </div>
        <div class="combate-stat">
          <div class="combate-valor" id="combate-tecnica">0</div>
          <div class="combate-label">DAÑO TÉCNICA</div>
        </div>
        <div class="combate-stat">
          <div class="combate-valor" id="combate-total">0</div>
          <div class="combate-label">TOTAL</div>
        </div>
      </div>
    </div>

    <!-- Descripción del Personaje -->
    <div class="seccion">
      <div class="titulo-seccion">DESCRIPCIÓN DEL PERSONAJE</div>
      <div class="campo-grupo">
        <div class="campo">
          <label>Apariencia</label>
          <textarea id="apariencia-personaje" placeholder="Describe la apariencia del personaje..."></textarea>
        </div>
        <div class="campo">
          <label>Personalidad</label>
          <textarea id="personalidad-personaje" placeholder="Describe la personalidad del personaje..."></textarea>
        </div>
      </div>
      <div class="campo">
        <label>Historia</label>
        <textarea id="historia-personaje" placeholder="Cuenta la historia del personaje..." style="min-height: 120px;"></textarea>
      </div>
    </div>

    <button class="btn-generar" onclick="generarResumen()">GENERAR RESUMEN COMPLETO</button>
    
    <!-- Botón de Debug -->
    <button class="btn-generar" onclick="debug()" style="background-color: #dc3545; margin-top: 10px;">DEBUG: Verificar Estado</button>
    
    <div id="resumen-final" style="display: none;" class="seccion">
      <div class="titulo-seccion">RESUMEN DEL PERSONAJE</div>
      <div id="contenido-resumen"></div>
    </div>
  </div>
  
  <!-- Contenedor oculto para técnicas -->
  <div style="display: none;" id="tecnicas-ocultas">
    {$tecnicas_contenido}
  </div>
  
  <!-- Variables del sistema -->
  <script type="text/javascript">
    // Variables que deben ser proporcionadas por el sistema
    var objetos_array_json = {$objetos_json};
    var virtudes_json = {$virtudes_json};
    var defectos_json = {$defectos_json};
  </script>
  
  {$footer}
</body>

</html>