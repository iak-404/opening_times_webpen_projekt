document.addEventListener("DOMContentLoaded", () => {
  const holidays = document.getElementById("holiday");
  const stateSelect = document.getElementById("state");

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

  // Dropdown mit Kürzel (value) und Namen (Text) befüllen
  fetch("https://get.api-feiertage.de/?states=nw")
    .then((response) => response.json())
    .then((data) => {
      const keys = Object.keys(data.feiertage[0]);
      const stateKeys = keys.filter((k) => /^[a-z]{2}$/i.test(k));

      stateKeys.forEach((k) => {
        stateSelect.innerHTML += `<option value="${k}">${STATE_NAMES[k]}</option>`;
      });

      // beim ersten Mal gleich laden
      loadHolidays(stateSelect.value);
    });

  function loadHolidays(selectedState) {
    holidays.innerHTML = "<li>Lade …</li>";

    fetch(`https://get.api-feiertage.de/?states=${selectedState}`)
      .then((response) => response.json())
      .then((data) => {
        holidays.innerHTML = data.feiertage
          .map((holiday) => {
            const date = new Date(holiday.date);
            const formattedDate = date.toLocaleDateString("de-DE", {
              day: "2-digit",
              month: "2-digit",
              year: "numeric",
            });
            return `<li>${formattedDate} - ${holiday.fname}</li>`;
          })
          .join("");
      });
  }

  // neu laden, wenn Auswahl geändert wird
  stateSelect.addEventListener("change", () => {
    loadHolidays(stateSelect.value);
  });
});
