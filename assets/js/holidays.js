document.addEventListener("DOMContentLoaded", () => {
  const holidays = document.getElementById("holiday");

  fetch('https://get.api-feiertage.de/?states=nw', {
    method: 'GET',
  })
  .then((response) => response.json())
  .then((data) => {
    data['feiertage'].forEach((holiday) => {
        console.log(holiday);
    //   const option = document.createElement("option");
    //   option.value = holiday.date;
    //   option.textContent = `${holiday.fname} (${holiday.date})`;
    //   holidays.appendChild(option);
    });
    })
});
