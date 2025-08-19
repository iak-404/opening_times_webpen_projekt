window.loadOpeningTimesInto = async function(targetEl, setName, tzFixed = '', useClientTz = true) {
  if (!targetEl || !setName) return;
  if (!window.OpeningTimesData) {
    targetEl.innerHTML = '<div class="error">Config fehlt.</div>';
    return;
  }

  function detectTz() {
    if (tzFixed) return tzFixed;
    if (useClientTz && window.Intl && Intl.DateTimeFormat) {
      try { return Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch(e) {}
    }
    return '';
  }

  const formData = new FormData();
  formData.append('action', OpeningTimesData.action);
  formData.append('set_name', setName);
  formData.append('nonce', OpeningTimesData.nonce);
  const tz = detectTz();
  if (tz) formData.append('tz', tz);

  targetEl.innerHTML = '<div class="loading">Lade Öffnungszeiten …</div>';

  try {
    const res = await fetch(OpeningTimesData.ajax_url, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    });
    const html = await res.text();
    targetEl.innerHTML = html;
  } catch (e) {
    console.error(e);
    targetEl.innerHTML = '<div class="error">Fehler beim Laden.</div>';
  }
};

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.opening-times-auto').forEach(el => {
    const setName     = el.dataset.set || '';
    const tzFixed     = el.dataset.tz || '';
    const useClientTz = (el.dataset.useClientTz || '1') === '1';
    if (setName) window.loadOpeningTimesInto(el, setName, tzFixed, useClientTz);
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const select = document.getElementById('selected_set');
  const target = document.getElementById('opening_times_result');
  if (select && target) {
    const trigger = () => {
      const val = select.value || '';
      if (val) window.loadOpeningTimesInto(target, val, '', true);
      else target.innerHTML = '';
    };
    trigger();
    select.addEventListener('change', trigger);
  }
});
