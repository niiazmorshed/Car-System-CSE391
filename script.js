// script.js - SLOT-BASED FRONTEND SYSTEM
console.log('🎰 SLOT-BASED FRONTEND: Loading...');

const API_BASE_URL = '/api';

// Global variables
let mechanics = [];
let selectedMechanic = null;

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ SLOT-BASED FRONTEND: DOM Ready');
    init();
});

async function init() {
    // Set minimum date
    const dateInput = document.getElementById('appointmentDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        dateInput.addEventListener('change', function() {
            if (this.value) {
                loadMechanics(this.value);
            }
        });
    }
    
    await loadMechanics();
    setupForm();
    showUI();
}

async function loadMechanics(specificDate = null) {
    console.log('🎰 SLOT-BASED FRONTEND: Loading mechanics...');
    
    try {
        let url = `${API_BASE_URL}/get_mechanics.php`;
        if (specificDate) {
            url += `?date=${specificDate}`;
        }
        
        // 🎰 CRITICAL: Add timestamp to prevent caching
        url += (url.includes('?') ? '&' : '?') + 'v=' + Date.now();
        
        console.log('🎰 SLOT-BASED: Fetching from:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache',
                'Expires': '0'
            }
        });
        
        const data = await response.json();
        console.log('🎰 SLOT-BASED API Response:', data);
        
        if (data.success && data.mechanics) {
            mechanics = data.mechanics;
            console.log('✅ SLOT-BASED: Mechanics loaded:', mechanics.length);
            
            // Log each mechanic to verify correct data
            mechanics.forEach(mechanic => {
                console.log(`🎰 ${mechanic.name}: ${mechanic.availableSlots}/${mechanic.totalSlots} slots (${mechanic.systemType || 'SLOT_BASED'})`);
            });
            
            updateMechanicsGrid(mechanics);
            
        } else {
            console.error('❌ Failed to load mechanics:', data.message);
            showError('Failed to load mechanics: ' + data.message);
        }
    } catch (error) {
        console.error('❌ Error loading mechanics:', error);
        showError('Connection error: ' + error.message);
    }
}

function updateMechanicsGrid(mechanicsData) {
    console.log('🎰 SLOT-BASED: Updating mechanics grid...');
    
    const mechanicsGrid = document.getElementById('mechanicsGrid');
    if (!mechanicsGrid) {
        console.error('❌ mechanicsGrid not found!');
        return;
    }
    
    mechanicsGrid.innerHTML = '';
    
    mechanicsData.forEach(mechanic => {
        // 🎰 CRITICAL: Use the correct field names from SLOT-BASED API
        const availableSlots = mechanic.availableSlots || 0;
        const totalSlots = mechanic.totalSlots || 4;
        
        console.log(`🎰 Creating card for ${mechanic.name}: ${availableSlots}/${totalSlots} slots`);
        
        const mechanicCard = document.createElement('div');
        mechanicCard.className = `mechanic-card ${availableSlots <= 0 ? 'unavailable' : 'available'}`;
        mechanicCard.dataset.mechanicId = mechanic._id;
        
        const availabilityText = `${availableSlots}/${totalSlots} slots available`;
        const availabilityClass = availableSlots > 0 ? 'available' : 'fully-booked';
        
        mechanicCard.innerHTML = `
            <h3 class="mechanic-name">${mechanic.name}</h3>
            <p class="mechanic-specialization">${mechanic.specialization}</p>
            <p class="mechanic-experience">${mechanic.experience} years experience</p>
            <p class="availability ${availabilityClass}">${availabilityText}</p>
            ${availableSlots <= 0 ? '<div class="unavailable-overlay">Fully Booked</div>' : ''}
        `;
        
        if (availableSlots > 0) {
            mechanicCard.addEventListener('click', function() {
                selectMechanic(mechanic);
            });
        }
        
        mechanicsGrid.appendChild(mechanicCard);
        
        // Special logging for David Wilson
        if (mechanic.name === 'David Wilson') {
            console.log(`🎯 DAVID WILSON SLOT-BASED CARD: ${availabilityText}`);
        }
    });
    
    console.log('✅ SLOT-BASED: Grid updated successfully');
}

function selectMechanic(mechanic) {
    if (mechanic.availableSlots <= 0) {
        showError('This mechanic is fully booked.');
        return;
    }
    
    selectedMechanic = mechanic;
    
    // Update UI
    document.querySelectorAll('.mechanic-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    const selectedCard = document.querySelector(`[data-mechanic-id="${mechanic._id}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
    }
    
    // Show form
    const form = document.getElementById('appointmentForm');
    if (form) {
        form.style.display = 'block';
        form.scrollIntoView({ behavior: 'smooth' });
    }
    
    console.log(`✅ SLOT-BASED: Selected ${mechanic.name} (${mechanic.availableSlots}/${mechanic.totalSlots} slots)`);
}

function setupForm() {
    const form = document.getElementById('appointmentForm');
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!selectedMechanic) {
            showError('Please select a mechanic first.');
            return;
        }
        
        if (selectedMechanic.availableSlots <= 0) {
            showError('Selected mechanic is fully booked.');
            return;
        }
        
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        
        try {
            submitButton.disabled = true;
            submitButton.textContent = 'Booking...';
            
            const formData = new FormData(form);
            const appointmentData = {
                clientName: formData.get('clientName'),
                clientPhone: formData.get('clientPhone'),
                clientAddress: formData.get('clientAddress'),
                carLicense: formData.get('carLicense'),
                carEngine: formData.get('carEngine'),
                appointmentDate: formData.get('appointmentDate'),
                mechanicId: selectedMechanic._id,
                notes: formData.get('notes') || ''
            };
            
            console.log('📤 SLOT-BASED: Booking appointment:', appointmentData);
            
            const response = await fetch(`${API_BASE_URL}/book_appointment.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(appointmentData)
            });
            
            const result = await response.json();
            console.log('📥 SLOT-BASED: Booking response:', result);
            
            if (result.success) {
                showSuccess(`🎰 SLOT-BASED: Appointment booked successfully!
                
Appointment ID: ${result.appointmentId}
Mechanic: ${selectedMechanic.name}
${result.slotUpdate ? `
Slots updated: ${result.slotUpdate.currentAvailableSlots}/${result.slotUpdate.totalSlots} remaining` : ''}`);
                
                // 🎰 CRITICAL: Immediately reload mechanics to show updated slots
                form.reset();
                form.style.display = 'none';
                selectedMechanic = null;
                
                // Clear selections
                document.querySelectorAll('.mechanic-card').forEach(card => {
                    card.classList.remove('selected');
                });
                
                // Reload mechanics to show updated slot counts
                const appointmentDate = formData.get('appointmentDate');
                console.log('🎰 SLOT-BASED: Reloading mechanics after booking...');
                await loadMechanics(appointmentDate);
                
            } else {
                showError(result.message || 'Failed to book appointment');
            }
            
        } catch (error) {
            console.error('❌ Booking error:', error);
            showError('Network error: ' + error.message);
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = originalText;
        }
    });
}

function showUI() {
    const loadingIndicator = document.getElementById('loadingIndicator');
    const mechanicsGrid = document.getElementById('mechanicsGrid');
    
    if (loadingIndicator) {
        loadingIndicator.style.display = 'none';
    }
    
    if (mechanicsGrid) {
        mechanicsGrid.style.display = 'grid';
    }
}

function showError(message) {
    console.error('🎰 SLOT-BASED Error:', message);
    
    // Remove any existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-error';
    alert.innerHTML = `
        <span>❌ ${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.insertBefore(alert, document.body.firstChild);
    
    // Auto-remove after 10 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 10000);
}

function showSuccess(message) {
    console.log('🎰 SLOT-BASED Success:', message);
    
    // Remove any existing alerts
    const existingAlert = document.querySelector('.alert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    const alert = document.createElement('div');
    alert.className = 'alert alert-success';
    alert.innerHTML = `
        <span>✅ ${message}</span>
        <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    document.body.insertBefore(alert, document.body.firstChild);
    
    // Auto-remove after 8 seconds
    setTimeout(() => {
        if (alert.parentElement) {
            alert.remove();
        }
    }, 8000);
}

// Add CSS for improved alerts and slot display
if (!document.querySelector('#slot-based-styles')) {
    const style = document.createElement('style');
    style.id = 'slot-based-styles';
    style.textContent = `
        .alert {
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            padding: 15px 40px 15px 15px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            font-family: inherit;
            animation: slideIn 0.3s ease-out;
            white-space: pre-line;
        }
        
        .alert-error {
            background-color: #fee;
            border-left: 4px solid #e53e3e;
            color: #c53030;
        }
        
        .alert-success {
            background-color: #f0fff4;
            border-left: 4px solid #38a169;
            color: #2f855a;
        }
        
        .alert-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 0;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .mechanic-card.unavailable {
            opacity: 0.6;
            cursor: not-allowed;
            position: relative;
        }
        
        .mechanic-card.available {
            cursor: pointer;
            border: 2px solid #38a169;
        }
        
        .mechanic-card.unavailable .unavailable-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            border-radius: inherit;
        }
        
        .mechanic-card.selected {
            border-color: #3182ce !important;
            box-shadow: 0 0 0 2px rgba(49, 130, 206, 0.2);
        }
        
        .availability.available {
            color: #38a169;
            font-weight: 500;
        }
        
        .availability.fully-booked {
            color: #e53e3e;
            font-weight: 500;
        }
        
        .mechanic-card {
            transition: all 0.3s ease;
        }
        
        .mechanic-card:hover.available {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}

console.log('✅ 🎰 SLOT-BASED FRONTEND: Script loaded successfully!');