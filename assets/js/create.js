document.addEventListener("click", function(e) {
  if (e.target.classList.contains("add_row")) {
    e.preventDefault();
    const key = e.target.dataset.day;
    const new_row = document.querySelector(`.new_row[data-day="${key}"]`);
    const index = new_row.querySelectorAll(".time_row").length + 1;

    new_row.insertAdjacentHTML(
      "beforeend",
      `<div class="time_row">
         <input type='time' name='opening_times[${key}][${index}][open_time]'>
         <input type='time' name='opening_times[${key}][${index}][close_time]'>
       </div>`
    );
  }
});
