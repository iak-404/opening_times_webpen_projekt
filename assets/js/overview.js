// Einmal global bereitstellen (fehlt bei dir):
function getClientTZ() {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone || "";
  } catch (e) {
    return "";
  }
}

// ► Kernloader: nimm die Action aus OpeningTimesData (Fallback: 'load_opening_times')
window.loadOpeningTimesInto = async function (
  targetEl,
  setName,
  tzFixed = "",
  useClientTz = true
) {
  if (!targetEl || !setName) return;
  if (!window.OpeningTimesData) {
    targetEl.innerHTML = '<div class="error">Config fehlt.</div>';
    return;
  }

  function detectTz() {
    if (tzFixed) return tzFixed;
    if (useClientTz && window.Intl && Intl.DateTimeFormat) {
      try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone || "";
      } catch (e) {}
    }
    return "";
  }

  const action =
    OpeningTimesData && OpeningTimesData.action
      ? OpeningTimesData.action
      : "load_opening_times";

  const formData = new FormData();
  formData.append("action", action);
  formData.append("set_name", setName);
  formData.append("nonce", OpeningTimesData.nonce);
  const tz = detectTz();
  if (tz) formData.append("tz", tz);

  targetEl.innerHTML = '<div class="loading">Lade Öffnungszeiten …</div>';

  try {
    const res = await fetch(OpeningTimesData.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body: formData,
    });
    const html = await res.text();
    targetEl.innerHTML = html;
  } catch (e) {
    console.error(e);
    targetEl.innerHTML = '<div class="error">Fehler beim Laden.</div>';
  }
};

// Frontend-Auto-Init (Woche)
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".opening-times-auto").forEach((el) => {
    const setName = el.dataset.set || "";
    const tzFixed = el.dataset.tz || "";
    const useClientTz = (el.dataset.useClientTz || "1") === "1";
    if (setName) window.loadOpeningTimesInto(el, setName, tzFixed, useClientTz);
  });
});

// Backend-Ansicht (Select → Woche)
document.addEventListener("DOMContentLoaded", () => {
  const select = document.getElementById("selected_set");
  const target = document.getElementById("opening_times_result");
  if (select && target) {
    const trigger = () => {
      const val = select.value || "";
      if (val) window.loadOpeningTimesInto(target, val, "", true);
      else target.innerHTML = "";
    };
    trigger();
    select.addEventListener("change", trigger);
  }
});

// Shortcode [tl] → "Heute"-Status per AJAX (immer Browser-TZ)
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".ot-today-auto").forEach((el) => {
    const set = el.dataset.set || "";
    if (!set) {
      el.innerHTML = '<div class="error">Set fehlt.</div>';
      return;
    }

    let tz = el.dataset.tz || getClientTZ();

    if (!window.OpeningTimesData) {
      el.innerHTML = '<div class="error">Config fehlt.</div>';
      return;
    }

    const action =
      OpeningTimesData && OpeningTimesData.action
        ? OpeningTimesData.action
        : "load_opening_times";

    const fd = new FormData();
    fd.append("action", action);
    fd.append("mode", "today"); // PHP rendert nur den Kurzstatus für heute
    fd.append("set_name", set);
    fd.append("nonce", OpeningTimesData.nonce);
    if (tz) fd.append("tz", tz);

    fetch(OpeningTimesData.ajax_url, {
      method: "POST",
      credentials: "same-origin",
      body: fd,
    })
      .then((r) => r.text())
      .then((html) => {
        el.innerHTML = html;
      })
      .catch(() => {
        el.innerHTML = '<div class="error">Fehler beim Laden.</div>';
      });
  });
});
