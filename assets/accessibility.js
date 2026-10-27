/* SCAE: accesibilidad local, sin servicios externos ni lectura de valores de formularios. */
(() => {
  'use strict';
  const defaults = { voice: false, text: 0, contrast: false };
  let saved = {};
  try { saved = JSON.parse(localStorage.getItem('nobel-accessibility') || '{}') || {}; } catch (_) {}
  // La voz siempre requiere activación explícita en cada página.
  const settings = { voice: false, text: [0, 1, 2].includes(saved.text) ? saved.text : 0, contrast: saved.contrast === true };
  const supported = 'speechSynthesis' in window && 'SpeechSynthesisUtterance' in window;
  let timer = null, activeTarget = null, lastTarget = null, lastTime = 0;
  document.body.insertAdjacentHTML('beforeend', `
    <div id="a11y-tools">
      <button type="button" id="a11y-toggle" aria-expanded="false" aria-controls="a11y-panel" aria-label="Accesibilidad. Abrir opciones de ayuda">Accesibilidad</button>
      <section id="a11y-panel" aria-labelledby="a11y-title" hidden>
        <h2 id="a11y-title">Accesibilidad</h2>
        <p>Personaliza la lectura y la visualización del sistema.</p>
        <button type="button" id="a11y-voice" aria-pressed="false">Activar lectura por voz</button>
        <p id="a11y-voice-hint">Al activar, escucha las opciones al pasar el cursor o navegar con Tab. No se leen contraseñas ni datos escritos.</p>
        <label for="a11y-text">Tamaño del texto</label>
        <select id="a11y-text"><option value="0">Normal</option><option value="1">Grande</option><option value="2">Muy grande</option></select>
        <button type="button" id="a11y-contrast" aria-pressed="false">Activar alto contraste</button>
        <button type="button" id="a11y-stop" data-voice-skip>Detener lectura</button>
        <button type="button" id="a11y-reset" data-voice-skip>Restablecer opciones</button>
        <p id="a11y-status" role="status" aria-live="polite"></p>
        <button type="button" id="a11y-close" data-voice-skip>Cerrar opciones</button>
      </section>
    </div>`);
  const byId = id => document.getElementById(id);
  const toggle = byId('a11y-toggle'), panel = byId('a11y-panel');
  const voiceButton = byId('a11y-voice'), contrastButton = byId('a11y-contrast');
  const textSelect = byId('a11y-text'), status = byId('a11y-status');
  function cancel() {
    clearTimeout(timer); timer = null; activeTarget = null;
    if (supported) window.speechSynthesis.cancel();
  }
  function persist() {
    try { localStorage.setItem('nobel-accessibility', JSON.stringify({ text: settings.text, contrast: settings.contrast })); } catch (_) {}
  }
  function apply() {
    document.body.dataset.a11yText = String(settings.text);
    document.body.dataset.a11yContrast = String(settings.contrast);
    textSelect.value = String(settings.text);
    voiceButton.setAttribute('aria-pressed', String(settings.voice));
    voiceButton.textContent = settings.voice ? 'Desactivar lectura por voz' : 'Activar lectura por voz';
    contrastButton.setAttribute('aria-pressed', String(settings.contrast));
    contrastButton.textContent = settings.contrast ? 'Desactivar alto contraste' : 'Activar alto contraste';
    persist();
  }
  function speak(text) {
    if (!supported || !settings.voice || document.hidden || !text) return;
    window.speechSynthesis.cancel();
    const utterance = new window.SpeechSynthesisUtterance(text);
    const voices = window.speechSynthesis.getVoices();
    const voice = voices.find(v => /^es[-_]PE$/i.test(v.lang)) || voices.find(v => /^es(?:[-_]|$)/i.test(v.lang));
    if (voice) utterance.voice = voice;
    utterance.lang = voice ? voice.lang : 'es-PE';
    utterance.rate = 0.95;
    window.speechSynthesis.speak(utterance);
  }
  const descriptions = {
    dashboard: 'Inicio. Ver el resumen de tu cuenta.',
    students: 'Estudiantes. Consultar el padrón de estudiantes.',
    teachers: 'Docentes y clases. Consultar cursos y horarios.',
    attendance: 'Asistencia. Consultar los registros de asistencia.',
    scan: 'Registrar asistencia. Abrir el formulario del personal.',
    devices: 'Dispositivos. Consultar el estado de los lectores.',
    reports: 'Reportes. Consultar y exportar información.',
    users: 'Usuarios y permisos. Administrar cuentas y accesos.',
    audit: 'Bitácora. Consultar las operaciones realizadas.'
  };
  function labelFor(element) {
    if (element.hasAttribute('data-voice-skip')) return '';
    if (element.dataset.page && descriptions[element.dataset.page]) return descriptions[element.dataset.page];
    if (element.hasAttribute('data-selfmark')) return 'Registrar mi asistencia en esta clase. Confirmar mi asistencia con la fecha y hora del servidor.';
    if (element.id === 'password') return 'Contraseña. Campo protegido. Escribe tu contraseña.';
    // Solo nombres de controles: jamás value, opciones seleccionadas, códigos o texto de credenciales.
    let label = element.getAttribute('data-voice') || element.getAttribute('aria-label') || '';
    if (!label && element.labels && element.labels.length) {
      label = [...element.labels].map(l => {
        const copy = l.cloneNode(true);
        copy.querySelectorAll('input,select,textarea,button').forEach(c => c.remove());
        return copy.textContent;
      }).join(' ');
    }
    if (!label && element.getAttribute('aria-labelledby')) {
      label = element.getAttribute('aria-labelledby').split(/\s+/).map(id => byId(id)?.textContent || '').join(' ');
    }
    if (!label && element.matches('button,a')) label = element.textContent;
    if (!label) label = element.getAttribute('placeholder') || element.getAttribute('title') || 'Campo de formulario';
    label = label.replace(/[◎◉↗▦♙♧▤▣↪→↓+×]/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 220);
    if (element.type === 'password') label += '. Campo protegido.';
    if (element.disabled) label += '. No disponible.';
    return label;
  }
  const selector = 'button,a[href],input:not([type="hidden"]),select,textarea,[role="button"]';
  function targetFrom(event) {
    const element = event.target instanceof Element ? event.target.closest(selector) : null;
    if (!element || element.closest('[hidden],.credentials') || !element.getClientRects().length) return null;
    return element;
  }
  function queue(element) {
    if (!settings.voice || !element) return;
    if (activeTarget === element) return;
    cancel(); activeTarget = element;
    if (lastTarget === element && Date.now() - lastTime < 1500) return;
    timer = setTimeout(() => {
      if (activeTarget !== element || !element.isConnected || !element.getClientRects().length) return;
      const label = labelFor(element);
      if (label) { lastTarget = element; lastTime = Date.now(); speak(label); }
    }, 650);
  }
  document.addEventListener('pointerover', e => queue(targetFrom(e)));
  document.addEventListener('focusin', e => queue(targetFrom(e)));
  document.addEventListener('pointerout', e => {
    const target = targetFrom(e);
    if (target && target === activeTarget && !target.contains(e.relatedTarget)) cancel();
  });
  document.addEventListener('focusout', e => { if (targetFrom(e) === activeTarget) cancel(); });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cancel(); if (!panel.hidden) closePanel(); }
  });
  document.addEventListener('visibilitychange', () => { if (document.hidden) cancel(); });
  window.addEventListener('pagehide', cancel);
  function closePanel() { panel.hidden = true; toggle.setAttribute('aria-expanded', 'false'); toggle.focus(); }
  toggle.addEventListener('click', () => {
    panel.hidden = !panel.hidden;
    toggle.setAttribute('aria-expanded', String(!panel.hidden));
    if (!panel.hidden) voiceButton.focus();
  });
  byId('a11y-close').addEventListener('click', closePanel);
  voiceButton.addEventListener('click', () => {
    cancel(); settings.voice = !settings.voice; apply();
    status.textContent = settings.voice ? 'Lectura por voz activada.' : 'Lectura por voz desactivada.';
    if (settings.voice) speak('Lectura por voz activada. Pasa el cursor por una opción o utiliza la tecla Tab.');
  });
  textSelect.addEventListener('change', () => {
    settings.text = [0, 1, 2].includes(Number(textSelect.value)) ? Number(textSelect.value) : 0;
    apply(); status.textContent = 'Tamaño del texto actualizado.';
  });
  contrastButton.addEventListener('click', () => {
    settings.contrast = !settings.contrast; apply();
    status.textContent = settings.contrast ? 'Alto contraste activado.' : 'Alto contraste desactivado.';
  });
  byId('a11y-stop').addEventListener('click', () => { cancel(); status.textContent = 'Lectura detenida.'; });
  byId('a11y-reset').addEventListener('click', () => {
    cancel(); Object.assign(settings, defaults); apply(); status.textContent = 'Opciones restablecidas.';
  });
  if (!supported) {
    voiceButton.disabled = true;
    byId('a11y-voice-hint').textContent = 'Este navegador no ofrece lectura por voz. Puedes utilizar el tamaño de texto y el alto contraste.';
  }
  apply();
})();
