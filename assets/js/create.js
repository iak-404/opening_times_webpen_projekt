document.addEventListener("click", function (e) {
  if (e.target.classList.contains("add_row")) {
    e.preventDefault();
    const key = e.target.dataset.day;
    const new_row = document.querySelector(`.new_time_row[data-day="${key}"]`);
    const index = new_row.querySelectorAll(".new_time_row").length + 1;

    new_row.insertAdjacentHTML(
      "beforeend",
      `<div class="time_row">
         <input type='time' style="width:100px" name='opening_times[${key}][${index}][open_time]'>
         <input type='time' style="width:100px" name='opening_times[${key}][${index}][close_time]'>
       </div>`
    );
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
