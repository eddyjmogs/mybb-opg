(function (w) {
  if (w.OPG) return;

  var OPG = {
    version: '1.0.0',
    env: {
      CURRENT_UID: typeof w.CURRENT_UID === 'number' ? w.CURRENT_UID : 0,
      IS_STAFF: !!w.IS_STAFF
    },
    q: function(s){ return document.querySelector(s); },
    qa: function(s){ return document.querySelectorAll(s); },
    utils: {
      toInt: function(v, d){ var n = parseInt(v,10); return isNaN(n) ? (d||0) : n; },
      clamp: function(n,min,max){ return Math.max(min, Math.min(max, n)); },
      debounce: function(fn, ms){ var t; return function(){ var c=this,a=arguments; clearTimeout(t); t=setTimeout(function(){ fn.apply(c,a); }, ms); }; }
    }
  };

  // ID compartida por URL (?peticion=123)
  (function(){
    var qs = (w.location && w.location.search) ? w.location.search : '';
    if (qs.charAt(0)==='?') qs = qs.substring(1);
    var share = null;
    if (typeof URLSearchParams === 'function') {
      var params = new URLSearchParams(w.location.search || '');
      share = params.get('peticion');
    } else if (qs) {
      var p = qs.split('&');
      for (var i=0;i<p.length;i++){
        var kv = p[i].split('=');
        if (kv[0]==='peticion'){ share = decodeURIComponent(kv[1]||''); break; }
      }
    }
    var parsed = parseInt(share,10);
    OPG.shareId = isNaN(parsed) ? null : parsed;
  })();

  // Helpers compartidos
  OPG.escapeHtml = function (t) {
    return (t||'').replace(/[&<>"']/g,function(c){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c];});
  };
  OPG.formatearFechaRegistro = function (ts) {
    if (!ts) return '-';
    var d = new Date(parseInt(ts,10)*1000);
    if (isNaN(d.getTime())) return '-';
    return d.toLocaleString('es-ES',{weekday:'short',year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'});
  };

  // Catálogo de Tiers (compartido)
  OPG.tiersInfo = {
    1:{nivelMin:1,posts:4,descripcion:'Aventuras poco relevantes y puramente narrativas. Tareas simples como ayudar a aldeanos, resolver malentendidos, o pequeñas peleas sin riesgo real.'},
    2:{nivelMin:5,posts:5,descripcion:'Misiones con uso de estadísticas y sistema de combate. Enfrentamientos contra rivales ligeramente más peligrosos, como pandillas locales o disputas que podrían escalar.'},
    3:{nivelMin:10,posts:6,descripcion:'Eventos con cierta influencia en la trama pudiendo usar NPCs oficiales. Pueden afectar la vida de varios habitantes o sectores de una isla pequeña con riesgo moderado.'},
    4:{nivelMin:15,posts:7,descripcion:'Aventuras relacionadas con mandatarios de islas menores. Interacción con figuras de poder local que pueden cambiar la política o seguridad de la isla.'},
    5:{nivelMin:20,posts:8,descripcion:'Conflictos que ponen en riesgo poblaciones enteras. Amenazas que afectan a cientos o miles de personas como plagas, invasiones o catástrofes naturales.'},
    6:{nivelMin:25,posts:9,descripcion:'Acciones que cambian significativamente aspectos de las tramas de islas o mares. Desmantelamiento de organizaciones con consecuencias geopolíticas notables.'},
    7:{nivelMin:30,posts:10,descripcion:'Contacto directo con reyes de grandes naciones y máximos cargos de facciones. Asuntos diplomáticos o militares de gran escala que influyen el futuro de naciones.'},
    8:{nivelMin:35,posts:10,descripcion:'Sucesos de gran impacto que cambian radicalmente el futuro de alguna nación. Desestabilización de gobiernos con consecuencias irreversibles para el poder mundial.'},
    9:{nivelMin:40,posts:11,descripcion:'Grandes sucesos que mueven facciones enteras. El destino de la Marina, Piratas Yonkou o el Ejército Revolucionario podría cambiar drásticamente.'},
    10:{nivelMin:45,posts:12,descripcion:'Eventos de relevancia mundial que cambiarán la historia. Conflictos globales o secretos que transforman la percepción del mundo entero.'}
  };

  // Navegación de pestañas (no rompe si falta Registro/Consola)
  document.addEventListener('DOMContentLoaded', function(){
    var tabs = OPG.qa('.tabButton');
    var panels = OPG.qa('.tabPanel');
    if (!tabs.length || !panels.length) return;
    tabs.forEach(function(tab){
      tab.addEventListener('click', function(){
        var dest = tab.getAttribute('data-tab');
        tabs.forEach(function(b){ b.classList.toggle('active', b===tab); });
        panels.forEach(function(p){ p.classList.toggle('active', p.getAttribute('data-tab')===dest); });
        if ((dest==='registro' || dest==='consola') && typeof w.cargarRegistroPeticiones === 'function'){
          w.cargarRegistroPeticiones(false);
        }
      });
    });
  });

  w.OPG = OPG;
})(window);
