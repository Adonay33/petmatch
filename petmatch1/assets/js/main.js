// Funciones generales para el sitio
document.addEventListener('DOMContentLoaded', function() {
    // Manejar favoritos
    document.querySelectorAll('.toggle-favorite').forEach(button => {
        button.addEventListener('click', function() {
            const petId = this.dataset.petId;
            toggleFavorite(petId, this);
        });
    });
    
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Función para manejar favoritos
function toggleFavorite(petId, button) {
    fetch(`${BASE_URL}api/toggle_favorite.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `pet_id=${petId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Actualizar el botón
            const icon = button.querySelector('i');
            if (data.is_favorite) {
                button.classList.remove('btn-outline-danger');
                button.classList.add('btn-danger');
                icon.classList.remove('bi-heart');
                icon.classList.add('bi-heart-fill');
                
                if (button.textContent.includes('Añadir')) {
                    button.innerHTML = '<i class="bi bi-heart-fill"></i> Favorito';
                }
            } else {
                button.classList.remove('btn-danger');
                button.classList.add('btn-outline-danger');
                icon.classList.remove('bi-heart-fill');
                icon.classList.add('bi-heart');
                
                if (button.textContent.includes('Favorito')) {
                    button.innerHTML = '<i class="bi bi-heart"></i> Añadir a favoritos';
                }
            }
            
            // Mostrar notificación
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error al procesar la solicitud', 'danger');
    });
}

// Mostrar alertas
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '1100';
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto cerrar después de 5 segundos
    setTimeout(() => {
        const bsAlert = new bootstrap.Alert(alertDiv);
        bsAlert.close();
    }, 5000);
}

// Función para inicializar mapas
function initPetMap(latitude, longitude, petName) {
    const map = L.map('petMap').setView([latitude, longitude], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    L.marker([latitude, longitude]).addTo(map)
        .bindPopup(petName)
        .openPopup();
}

// Función para inicializar el mapa de creación
function initCreationMap(defaultLat, defaultLng) {
    const map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    const marker = L.marker([defaultLat, defaultLng], {draggable: true}).addTo(map);
    
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        document.getElementById('latitude').value = position.lat;
        document.getElementById('longitude').value = position.lng;
    });
    
    // Actualizar marcador si se cambian los valores manualmente
    document.getElementById('latitude').addEventListener('change', updateMarker);
    document.getElementById('longitude').addEventListener('change', updateMarker);
    
    function updateMarker() {
        const lat = parseFloat(document.getElementById('latitude').value) || defaultLat;
        const lng = parseFloat(document.getElementById('longitude').value) || defaultLng;
        marker.setLatLng([lat, lng]);
        map.setView([lat, lng]);
    }
}