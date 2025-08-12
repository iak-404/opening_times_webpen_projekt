document.getElementById('selected_set').addEventListener('change', function () {
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

    fetch(MyAjax.ajaxurl, {  // ajaxurl wird via wp_localize_script übergeben
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
