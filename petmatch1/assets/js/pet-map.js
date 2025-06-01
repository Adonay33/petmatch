document.addEventListener('DOMContentLoaded', function() {
    const petMapElement = document.getElementById('petMap');
    if (petMapElement) {
        const latitude = parseFloat(petMapElement.dataset.lat);
        const longitude = parseFloat(petMapElement.dataset.lng);
        const petName = petMapElement.dataset.petName;
        
        initPetMap(latitude, longitude, petName);
    }
});