(function () {
  function initCalendar() {
    console.log('[OT] initCalendar');

    const day         = document.querySelector('.calendar-dates');
    const currdate    = document.querySelector('.calendar-current-date');
    const prenexIcons = document.querySelectorAll('.calendar-navigation span');

    console.log('[OT] elements', { day: !!day, curr: !!currdate, nav: prenexIcons.length });
    if (!day || !currdate || prenexIcons.length === 0) {
      console.warn('[OT] Calendar: Markup nicht gefunden.');
      return;
    }

    let date  = new Date();
    let year  = date.getFullYear();
    let month = date.getMonth();

    const months = [
      'January','February','March','April','May','June',
      'July','August','September','October','November','December'
    ];

    let clickedDay = null;
    let selectedDayElement = null;

    const manipulate = () => {
      try {
        const dayone        = new Date(year, month, 1).getDay();
        const lastdate      = new Date(year, month + 1, 0).getDate();
        const dayend        = new Date(year, month, lastdate).getDay();
        const monthlastdate = new Date(year, month, 0).getDate();

        let lit = '';
        for (let i = dayone; i > 0; i--) {
          lit += `<li class="inactive">${monthlastdate - i + 1}</li>`;
        }
        for (let i = 1; i <= lastdate; i++) {
          const isToday = (i === date.getDate() &&
                           month === new Date().getMonth() &&
                           year  === new Date().getFullYear()) ? 'active' : '';
          const highlight = (clickedDay === i) ? 'highlight' : '';
          lit += `<li class="${isToday} ${highlight}" data-day="${i}">${i}</li>`;
        }
        for (let i = dayend; i < 6; i++) {
          lit += `<li class="inactive">${i - dayend + 1}</li>`;
        }

        currdate.innerText = `${months[month]} ${year}`;
        day.innerHTML = lit;
        console.log('[OT] filled', day.children.length, 'items');

        const allDays = day.querySelectorAll('li:not(.inactive)');
        allDays.forEach(li => {
          li.addEventListener('click', () => {
            if (selectedDayElement) selectedDayElement.classList.remove('highlight');
            li.classList.add('highlight');
            selectedDayElement = li;
            clickedDay = parseInt(li.getAttribute('data-day'), 10);
          });
        });
      } catch (err) {
        console.error('[OT] manipulate error', err);
      }
    };

    manipulate();

    prenexIcons.forEach(icon => {
      icon.addEventListener('click', () => {
        month = icon.id === 'calendar-prev' ? month - 1 : month + 1;

        if (month < 0 || month > 11) {
          date  = new Date(year, month, new Date().getDate());
          year  = date.getFullYear();
          month = date.getMonth();
        } else {
          date  = new Date();
        }
        clickedDay = null;
        selectedDayElement = null;
        manipulate();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCalendar, { once: true });
  } else {
    initCalendar();
  }
})();
