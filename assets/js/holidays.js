// assets/js/holidays.js
document.addEventListener("DOMContentLoaded", () => {
  const holidaysUl = document.getElementById("holiday"); // Liste im Frontend
  const stateSelect = document.getElementById("state"); // Frontend-Auswahl
  const stateSettings = document.getElementById("state-settings"); // Admin-Einstellung

  // --- Aus PHP lokalisierte Daten (robust gegen unterschiedliche Handles)
  const HOLIDAYS = Array.isArray(window.OT_Holidays?.dates)
    ? window.OT_Holidays.dates
    : Array.isArray(window.holidays?.dates)
    ? window.holidays.dates
    : [];
  const savedRegion =
    window.OT_Holidays?.region ??
    window.holidays?.region ??
    stateSettings?.dataset?.region ??
    "nw";

  // Public Helper: 'YYYY-MM-DD' -> true/false
  const isHoliday = (isoDate) => HOLIDAYS.includes(String(isoDate));
  window.otIsHoliday = isHoliday;

  // Bundesländer (Fallback + Labels)
  const STATE_NAMES = {
    bw: "Baden-Württemberg",
    by: "Bayern",
    be: "Berlin",
    bb: "Brandenburg",
    hb: "Bremen",
    hh: "Hamburg",
    he: "Hessen",
    mv: "Mecklenburg-Vorpommern",
    ni: "Niedersachsen",
    nw: "Nordrhein-Westfalen",
    rp: "Rheinland-Pfalz",
    sl: "Saarland",
    sn: "Sachsen",
    st: "Sachsen-Anhalt",
    sh: "Schleswig-Holstein",
    th: "Thüringen",
  };

  const buildOptions = (codes) =>
    codes
      .map(
        (code) =>
          `<option value="${code}">${
            STATE_NAMES[code] ?? code.toUpperCase()
          }</option>`
      )
      .join("");

  function initSelect(selectEl, codes, preselect) {
    if (!selectEl) return;
    selectEl.innerHTML = buildOptions(codes);
    if (preselect) selectEl.value = String(preselect).toLowerCase();
    if (!selectEl.value) selectEl.value = "nw";
  }

  // Versuche die 2-Buchstaben-Keys aus der API zu lesen; sonst Fallback auf STATE_NAMES
  async function fillStates() {
    try {
      const res = await fetch("https://get.api-feiertage.de/?states=nw", {
        cache: "no-store",
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      const first = Array.isArray(data?.feiertage) ? data.feiertage[0] : null;
      const stateKeys = first
        ? Object.keys(first)
            .filter((k) => /^[a-z]{2}$/i.test(k))
            .map((k) => k.toLowerCase())
        : Object.keys(STATE_NAMES);

      initSelect(stateSettings, stateKeys, savedRegion);
      initSelect(stateSelect, stateKeys, savedRegion);
    } catch (e) {
      console.warn("Fallback auf statische Bundesländer:", e);
      const stateKeys = Object.keys(STATE_NAMES);
      initSelect(stateSettings, stateKeys, savedRegion);
      initSelect(stateSelect, stateKeys, savedRegion);
    }
  }

  // Feiertage der aktuellen Region laden + rendern
  async function loadHolidays(e) {
    if (!stateSelect || !holidaysUl) return;

    const code = (e?.target?.value ?? stateSelect.value ?? "")
      .trim()
      .toLowerCase();
    if (!code) return;

    holidaysUl.innerHTML = "<li>Lade …</li>";
    try {
      const res = await fetch(
        `https://get.api-feiertage.de/?states=${encodeURIComponent(code)}`,
        { cache: "no-store" }
      );
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      const list = Array.isArray(data?.feiertage) ? data.feiertage : [];

      holidaysUl.innerHTML = list.length
        ? list
            .map((h) => {
              const d = new Date(h.date);
              const formatted = isNaN(d)
                ? h.date
                : d.toLocaleDateString("de-DE", {
                    day: "2-digit",
                    month: "2-digit",
                    year: "numeric",
                  });
              return `<li>${formatted} - ${h.fname}</li>`;
            })
            .join("")
        : "<li>Keine Feiertage gefunden.</li>";
    } catch (err) {
      console.error("Fehler beim Laden:", err);
      holidaysUl.innerHTML = "<li>Fehler beim Laden</li>";
    }
  }

  // Init-Reihenfolge: Selects befüllen → initiale Liste laden → Listener setzen
  fillStates().then(() => {
    // Falls ein Frontend-Select existiert, initial rendern + bei Änderung neu laden
    if (stateSelect) {
      if (savedRegion) stateSelect.value = savedRegion.toLowerCase();
      loadHolidays();
      stateSelect.addEventListener("change", loadHolidays);
    }

    // Beispiel: „heute“ im DOM markieren, wenn Feiertag (basierend auf lokalisierten Daten)
    const today = new Date();
    const isoToday = [
      today.getFullYear(),
      String(today.getMonth() + 1).padStart(2, "0"),
      String(today.getDate()).padStart(2, "0"),
    ].join("-");
    if (isHoliday(isoToday)) {
      document.documentElement.classList.add("ot-holiday-today");
      // Optional: document.querySelector(".open-status")?.textContent = "Heute: geschlossen (Feiertag)";
    }
  });
});
