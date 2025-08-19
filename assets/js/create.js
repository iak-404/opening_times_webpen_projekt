document.addEventListener('click', (e) => {
  // ---------- ZEILE HINZUFÜGEN (Create & Edit) ----------
  const addBtn = e.target.closest('.add_row');
  if (addBtn) {
    e.preventDefault();

    const dayKey  = addBtn.dataset.day;                  // z.B. "Monday"
    const daysBox = addBtn.closest('.days');             // Container dieses Tages
    if (!daysBox) return;

    // Nächsten Index aus data-next-index holen (Create: startet bei 1, Edit: bei count($times))
    let idx = parseInt(daysBox.dataset.nextIndex || '1', 10);

    // Anker: <span class="new_time_row" data-day="...">
    const anchor = daysBox.querySelector(`.new_time_row[data-day="${dayKey}"]`);
    if (!anchor) return;

    // Wichtig: [times][idx][open_time] / [close_time] + data-day am Row-Wrapper
    const rowHtml = `
      <div class="time_row" data-day="${dayKey}">
        <input class="time_input" type="time" style="width:100px"
               name="opening_times[${dayKey}][times][${idx}][open_time]">
        <input class="time_input" type="time" style="width:100px"
               name="opening_times[${dayKey}][times][${idx}][close_time]">
      </div>
    `;
    anchor.insertAdjacentHTML('beforebegin', rowHtml);

    // Index hochzählen
    daysBox.dataset.nextIndex = String(idx + 1);
    return;
  }

  if (e.target.classList.contains("edit_delete_row")) {
    e.preventDefault();
    const key = e.target.dataset.day;

    const dayContainer = e.target.closest(".days");

    const timeRows = dayContainer.querySelectorAll(
      `.time_row[data-day="${key}"], .new_time_row[data-day="${key}"] .time_row`
    );

    // Mindestens eine Zeile behalten
    if (timeRows.length > 1) {
      timeRows[timeRows.length - 1].remove();
    }
  }

  if (e.target.classList.contains("delete_row")) {
    e.preventDefault();
    const key = e.target.dataset.day;
    const new_row = document.querySelector(`.new_time_row[data-day="${key}"]`);
    const timeRows = new_row.querySelectorAll(".time_row");

    // Mindestens eine Zeile behalten
    if (timeRows.length > 0) {
      timeRows[timeRows.length - 1].remove();
    }
  }

  
});
