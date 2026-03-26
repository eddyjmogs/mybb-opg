/* OPG Core — Plantilla reutilizable (ES5/ACP-safe) */
(function (w) {
  if (w.OPG) return;

  var OPG = {
    version: 'core-1.0.0',
    flags: { debug: false },
    env: {
      CURRENT_UID: (typeof w.CURRENT_UID === 'number') ? w.CURRENT_UID : 0,
      IS_STAFF: !!w.IS_STAFF,
      CSRF_TOKEN: (typeof w.CSRF_TOKEN === 'string') ? w.CSRF_TOKEN : ''
    },
    // Selectores
    q: function (s) { return document.querySelector(s); },
    qa: function (s) { return document.querySelectorAll(s); }
  };

  /* ---------- Utils ---------- */
  OPG.utils = {
    toInt: function (v, d) { var n = parseInt(v, 10); return isNaN(n) ? (d || 0) : n; },
    clamp: function (n, min, max) { return Math.max(min, Math.min(max, n)); },
    uid: (function(){ var i = 0; return function(prefix){ i++; return (prefix||'uid') + '_' + (Date.now()) + '_' + i; }; })(),
    debounce: function (fn, ms) {
      var t; return function () { var c = this, a = arguments; clearTimeout(t); t = setTimeout(function () { fn.apply(c, a); }, ms); };
    },
    throttle: function (fn, ms) {
      var last = 0; return function () { var now = Date.now(); if (now - last >= ms) { last = now; fn.apply(this, arguments); } };
    },
    escapeHtml: function (s) {
      return (s || '').toString().replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
      });
    },
    safeJSON: {
      parse: function (t, fb) { try { return JSON.parse(t); } catch (e) { return (typeof fb === 'undefined' ? null : fb); } },
      stringify: function (o, fb) { try { return JSON.stringify(o); } catch (e) { return (typeof fb === 'undefined' ? '' : fb); } }
    },
    formatDate: function (ts) {
      if (!ts) return '-';
      var d = new Date(OPG.utils.toInt(ts, 0) * 1000);
      if (isNaN(d.getTime())) return '-';
      return d.toLocaleString('es-ES', { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    }
  };

  /* ---------- URL helpers ---------- */
  OPG.url = {
    params: function () {
      var out = {}; var s = (w.location && w.location.search) ? w.location.search : '';
      if (s.charAt(0) === '?') s = s.substring(1);
      if (!s) return out;
      var p = s.split('&'), i, kv;
      for (i = 0; i < p.length; i++) {
        kv = p[i].split('=');
        out[decodeURIComponent(kv[0] || '')] = decodeURIComponent(kv[1] || '');
      }
      return out;
    },
    get: function (key, dflt) {
      var p = OPG.url.params(); return (typeof p[key] !== 'undefined') ? p[key] : (typeof dflt === 'undefined' ? null : dflt);
    },
    build: function (basePath, objParams) {
      var k, arr = [];
      for (k in objParams) if (objParams.hasOwnProperty(k) && objParams[k] !== null && typeof objParams[k] !== 'undefined') {
        arr.push(encodeURIComponent(k) + '=' + encodeURIComponent(String(objParams[k])));
      }
      return basePath + (arr.length ? ('?' + arr.join('&')) : '');
    }
  };

  /* Captura id compartida si existe (?peticion=123) */
  (function(){
    var pid = OPG.utils.toInt(OPG.url.get('peticion', null), null);
    OPG.shareId = (pid === null) ? null : pid;
  })();

  /* ---------- Mini Event Bus (pub/sub) ---------- */
  (function () {
    var topics = {};
    OPG.bus = {
      on: function (topic, handler) {
        if (!topics[topic]) topics[topic] = [];
        topics[topic].push(handler);
        return function unsubscribe() {
          var i = topics[topic].indexOf(handler);
          if (i >= 0) topics[topic].splice(i, 1);
        };
      },
      emit: function (topic, payload) {
        var list = topics[topic] || [], i;
        for (i = 0; i < list.length; i++) {
          try { list[i](payload); } catch (e) { if (OPG.flags.debug) console && console.error && console.error(e); }
        }
      },
      clear: function (topic) {
        if (topics[topic]) topics[topic] = [];
      }
    };
  })();

  /* ---------- Storage con prefijo ---------- */
  OPG.storage = (function () {
    var prefix = 'opg::';
    function k(key) { return prefix + key; }
    return {
      get: function (key, dflt) {
        try {
          var v = w.localStorage.getItem(k(key));
          return (v === null) ? (typeof dflt === 'undefined' ? null : dflt) : OPG.utils.safeJSON.parse(v, v);
        } catch (e) { return (typeof dflt === 'undefined' ? null : dflt); }
      },
      set: function (key, value) {
        try { w.localStorage.setItem(k(key), (typeof value === 'string' ? value : OPG.utils.safeJSON.stringify(value, ''))); } catch (e) {}
      },
      remove: function (key) { try { w.localStorage.removeItem(k(key)); } catch (e) {} },
      clearAll: function () {
        try {
          var i, toDel = [];
          for (i = 0; i < w.localStorage.length; i++) {
            var kk = w.localStorage.key(i);
            if (kk && kk.indexOf(prefix) === 0) toDel.push(kk);
          }
          for (i = 0; i < toDel.length; i++) w.localStorage.removeItem(toDel[i]);
        } catch (e) {}
      }
    };
  })();

  /* ---------- HTTP helpers (fetch con fallback XHR) ---------- */
  OPG.http = (function () {
    function headers(xhr) {
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); // CSRF-friendly
      if (OPG.env.CSRF_TOKEN) xhr.setRequestHeader('X-CSRF-Token', OPG.env.CSRF_TOKEN);
      xhr.setRequestHeader('Accept', 'application/json');
    }
    function get(url) {
      if (w.fetch) {
        return w.fetch(url, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-Token': OPG.env.CSRF_TOKEN } })
          .then(function (r) { return r.text(); })
          .then(function (t) { return { ok: true, text: t, json: OPG.utils.safeJSON.parse(t, null) }; })
          .catch(function (e) { return { ok: false, error: e }; });
      }
      return new Promise(function (resolve) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true); headers(xhr);
        xhr.onreadystatechange = function () {
          if (xhr.readyState !== 4) return;
          var text = xhr.responseText || '';
          resolve({ ok: (xhr.status >= 200 && xhr.status < 300), text: text, json: OPG.utils.safeJSON.parse(text, null), status: xhr.status });
        };
        xhr.send();
      });
    }
    function postJSON(url, bodyObj) {
      var payload = OPG.utils.safeJSON.stringify(bodyObj || {}, '{}');
      if (w.fetch) {
        return w.fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-Token': OPG.env.CSRF_TOKEN
          },
          body: payload
        }).then(function (r) { return r.text(); })
          .then(function (t) { return { ok: true, text: t, json: OPG.utils.safeJSON.parse(t, null) }; })
          .catch(function (e) { return { ok: false, error: e }; });
      }
      return new Promise(function (resolve) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true); headers(xhr);
        xhr.setRequestHeader('Content-Type', 'application/json;charset=UTF-8');
        xhr.onreadystatechange = function () {
          if (xhr.readyState !== 4) return;
          var text = xhr.responseText || '';
          resolve({ ok: (xhr.status >= 200 && xhr.status < 300), text: text, json: OPG.utils.safeJSON.parse(text, null), status: xhr.status });
        };
        xhr.send(payload);
      });
    }
    return { get: get, postJSON: postJSON };
  })();

  /* ---------- Tabs genérico ---------- */
  OPG.tabs = (function () {
    function init(btnSelector, panelSelector, onChange) {
      var btns = document.querySelectorAll(btnSelector || '.tabButton');
      var panels = document.querySelectorAll(panelSelector || '.tabPanel');
      if (!btns.length || !panels.length) return;
      var activate = function (dest) {
        var i;
        for (i = 0; i < btns.length; i++) btns[i].classList.toggle('active', btns[i].getAttribute('data-tab') === dest);
        for (i = 0; i < panels.length; i++) panels[i].classList.toggle('active', panels[i].getAttribute('data-tab') === dest);
        if (typeof onChange === 'function') { try { onChange(dest); } catch (e) {} }
        OPG.bus.emit('tabs:change', { tab: dest });
      };
      var i;
      for (i = 0; i < btns.length; i++) (function (btn) {
        btn.addEventListener('click', function () { activate(btn.getAttribute('data-tab')); });
      })(btns[i]);
      // Auto: si ya hay .active, emite evento
      var active = null;
      for (i = 0; i < btns.length; i++) { if (btns[i].classList.contains('active')) { active = btns[i].getAttribute('data-tab'); break; } }
      if (active) { activate(active); }
    }
    return { init: init };
  })();

  /* ---------- Ready queue ---------- */
  (function () {
    var q = [];
    OPG.onReady = function (fn) {
      if (document.readyState === 'complete' || document.readyState === 'interactive') { try { fn(); } catch (e) {} }
      else { q.push(fn); }
    };
    document.addEventListener('DOMContentLoaded', function () {
      var i; for (i = 0; i < q.length; i++) { try { q[i](); } catch (e) {} }
      q = [];
    });
  })();

  // Exponer
  w.OPG = OPG;
})(window);
