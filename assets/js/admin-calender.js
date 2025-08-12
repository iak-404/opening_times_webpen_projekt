document.addEventListener('DOMContentLoaded', function () {
    var calendarEl = document.getElementById('ot_calendar');
    if (!calendarEl) {
        console.error('Kalender-Element nicht gefunden!');
        return;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        aspectRatio: 3,
        timeZone: 'local',
        initialView: 'dayGridMonth',
        events: otCalendarEvents || [], 

        }
    );
    
    calendar.render();    

});
