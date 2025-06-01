document.addEventListener('DOMContentLoaded', function() {
    // Coordenadas del centro de El Salvador
    const defaultLat = 13.7942;
    const defaultLng = -88.8965;
    
    // Límites geográficos de El Salvador
    const southWest = L.latLng(13.149, -90.095);
    const northEast = L.latLng(14.445, -87.691);
    const bounds = L.latLngBounds(southWest, northEast);
    
    // Elementos del formulario
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const cityInput = document.getElementById('city'); // Si tienes campo de ciudad
    
    // Inicializar mapa
    const map = L.map('map', {
        center: [defaultLat, defaultLng],
        zoom: 8,
        maxBounds: bounds,
        maxZoom: 18,
        minZoom: 7
    });
    
    // Capa base de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    
    // Crear marcador arrastrable
    const marker = L.marker([defaultLat, defaultLng], {
        draggable: true
    }).addTo(map);
    
    // Actualizar coordenadas al mover el marcador
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        latInput.value = position.lat.toFixed(6);
        lngInput.value = position.lng.toFixed(6);
    });
    
    // Geocodificación al cambiar ciudad (opcional)
    if (cityInput) {
        cityInput.addEventListener('change', function() {
            const city = this.value + ', El Salvador';
            if (city) {
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(city)}&countrycodes=sv`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const lat = parseFloat(data[0].lat);
                            const lng = parseFloat(data[0].lon);
                            
                            // Verificar que esté dentro de El Salvador
                            if (bounds.contains([lat, lng])) {
                                map.setView([lat, lng], 14);
                                marker.setLatLng([lat, lng]);
                                latInput.value = lat;
                                lngInput.value = lng;
                            }
                        }
                    })
                    .catch(error => console.error('Error en geocodificación:', error));
            }
        });
    }
    
    // Cargar coordenadas existentes si existen
    if (latInput.value && lngInput.value) {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        
        if (!isNaN(lat) && !isNaN(lng)) {
            map.setView([lat, lng], 14);
            marker.setLatLng([lat, lng]);
        }
    }
    
    // Control para buscar ubicaciones (opcional)
    const searchControl = new L.Control.Search({
        position: 'topright',
        layer: L.layerGroup(),
        initial: false,
        zoom: 14,
        bounds: bounds,
        filterData: function(text, records) {
            return records.filter(r => r.properties.country_code === 'sv');
        }
    });
    map.addControl(searchControl);
});