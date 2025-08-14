document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('selected_set');
    if (!select) return; // nur weiter, wenn es das Element gibt

    select.addEventListener('change', function () {
        var selectedSet = this.value;
        var container = document.getElementById('opening_times_result');

        if (selectedSet === '') {
            container.innerHTML = '';
            return;
        }

        var data = new FormData();
        data.append('action', 'load_opening_times');
        data.append('set_name', selectedSet);
        data.append('nonce', MyAjax.nonce);

        fetch(MyAjax.ajaxurl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data
        })
        .then(response => response.text())
        .then(html => {
            container.innerHTML = html;
        })
        .catch(error => {
            container.innerHTML = '<p>Fehler beim Laden der Öffnungszeiten.</p>';
            console.error(error);
        });
    });
});
