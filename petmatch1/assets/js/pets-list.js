async function deletePet(petId) {
    try {
        const response = await fetch(`${BASE_URL}api/delete_pet.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ pet_id: petId }),
            credentials: 'include'
        });

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error en la respuesta del servidor');
        }

        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message, 'success');
            // Eliminar elemento del DOM
            const petElement = document.querySelector(`[data-pet-id="${petId}"]`).closest('.pet-container');
            if (petElement) {
                petElement.style.opacity = '0';
                setTimeout(() => petElement.remove(), 300);
            }
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        console.error('Error al eliminar mascota:', error);
        showAlert(error.message || 'Error al contactar con el servidor', 'danger');
    }
}

// Asignar eventos a los botones
document.querySelectorAll('.delete-pet').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        const petId = this.dataset.petId;
        
        if (confirm('¿Estás seguro de eliminar esta mascota? Esta acción no se puede deshacer.')) {
            deletePet(petId);
        }
    });
});