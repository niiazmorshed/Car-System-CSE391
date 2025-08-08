// admin.js - SLOT-BASED ADMIN PANEL
console.log('🎰 SLOT-BASED ADMIN: Loading...');

const API_BASE_URL = 'https://car-system-hazel.vercel.app/api';

let allAppointments = [];
let allMechanics = [];
let filteredAppointments = [];

document.addEventListener('DOMContentLoaded', function() {
    console.log('✅ SLOT-BASED ADMIN: DOM Ready');
    init();
});

async function init() {
    await loadMechanics();
    await loadAppointments();
    setupEventListeners();
}

async function loadMechanics() {
    try {
        console.log('📡 SLOT-BASED ADMIN: Loading mechanics...');
        
        const response = await fetch(`${API_BASE_URL}/get_mechanics.php?v=${Date.now()}`);
        const data = await response.json();
        
        if (data.success && data.mechanics) {
            allMechanics = data.mechanics;
            console.log('✅ SLOT-BASED ADMIN: Mechanics loaded:', allMechanics.length);
            
            // Populate mechanic filter
            const mechanicFilter = document.getElementById('mechanicFilter');
            mechanicFilter.innerHTML = '<option value="">All Mechanics</option>';
            
            allMechanics.forEach(mechanic => {
                const option = document.createElement('option');
                option.value = mechanic._id;
                option.textContent = `${mechanic.name} (${mechanic.availableSlots}/${mechanic.totalSlots} slots)`;
                mechanicFilter.appendChild(option);
            });
        }
    } catch (error) {
        console.error('❌ Error loading mechanics:', error);
    }
}

async function loadAppointments() {
    try {
        console.log('📡 SLOT-BASED ADMIN: Loading appointments...');
        
        const response = await fetch(`${API_BASE_URL}/get_appointments.php?v=${Date.now()}`);
        const data = await response.json();
        
        if (data.success && data.appointments) {
            allAppointments = data.appointments;
            filteredAppointments = [...allAppointments];
            
            console.log('✅ SLOT-BASED ADMIN: Appointments loaded:', allAppointments.length);
            
            updateTable();
            updateStats();
        } else {
            console.error('❌ Failed to load appointments:', data.message);
            showNoAppointments();
        }
    } catch (error) {
        console.error('❌ Error loading appointments:', error);
        showError('Failed to load appointments: ' + error.message);
    }
}

function updateTable() {
    const tbody = document.getElementById('appointmentsTableBody');
    
    if (filteredAppointments.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 20px; color: #666;">
                    No appointments found matching the current filters.
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = '';
    
    filteredAppointments.forEach(appointment => {
        const row = document.createElement('tr');
        
        // Get mechanic name
        const mechanic = allMechanics.find(m => m._id === appointment.mechanicId);
        const mechanicName = mechanic ? mechanic.name : 'Unknown Mechanic';
        const mechanicSlots = mechanic ? `(${mechanic.availableSlots}/${mechanic.totalSlots})` : '';
        
        // Format date
        const appointmentDate = new Date(appointment.appointmentDate).toLocaleDateString();
        
        // Status badge
        const statusClass = `status-${appointment.status.toLowerCase()}`;
        
        row.innerHTML = `
            <td>${appointment.clientName}</td>
            <td>${appointment.clientPhone}</td>
            <td>${appointment.carLicense}</td>
            <td>${appointmentDate}</td>
            <td>${mechanicName} ${mechanicSlots}</td>
            <td><span class="status-badge ${statusClass}">${appointment.status}</span></td>
            <td class="action-buttons">
                <button class="action-btn edit-btn" onclick="openStatusModal('${appointment._id}', '${appointment.status}')">
                    Update
                </button>
                <button class="action-btn edit-btn" onclick="showDetails('${appointment._id}')">
                    Details
                </button>
            </td>
        `;
        
        tbody.appendChild(row);
    });
}

function updateStats() {
    const stats = {
        total: allAppointments.length,
        confirmed: allAppointments.filter(a => a.status === 'confirmed').length,
        pending: allAppointments.filter(a => a.status === 'pending').length,
        inProgress: allAppointments.filter(a => a.status === 'in-progress').length,
        completed: allAppointments.filter(a => a.status === 'completed').length,
        cancelled: allAppointments.filter(a => a.status === 'cancelled').length
    };
    
    // Calculate total available slots
    const totalAvailableSlots = allMechanics.reduce((sum, mechanic) => sum + (mechanic.availableSlots || 0), 0);
    const totalSlots = allMechanics.reduce((sum, mechanic) => sum + (mechanic.totalSlots || 4), 0);
    
    const statsContainer = document.getElementById('statsSummary');
    statsContainer.innerHTML = `
        <div class="stat-item">
            <span class="stat-number">${stats.total}</span>
            <span class="stat-label">Total</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">${stats.confirmed}</span>
            <span class="stat-label">Confirmed</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">${stats.pending}</span>
            <span class="stat-label">Pending</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">${stats.inProgress}</span>
            <span class="stat-label">In Progress</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">${stats.completed}</span>
            <span class="stat-label">Completed</span>
        </div>
        <div class="stat-item">
            <span class="stat-number">${totalAvailableSlots}/${totalSlots}</span>
            <span class="stat-label">🎰 Available Slots</span>
        </div>
    `;
}

function setupEventListeners() {
    // Filter event listeners
    document.getElementById('dateFilter').addEventListener('change', applyFilters);
    document.getElementById('mechanicFilter').addEventListener('change', applyFilters);
    document.getElementById('statusFilter').addEventListener('change', applyFilters);
}

function applyFilters() {
    const dateFilter = document.getElementById('dateFilter').value;
    const mechanicFilter = document.getElementById('mechanicFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    filteredAppointments = allAppointments.filter(appointment => {
        let matches = true;
        
        if (dateFilter) {
            const appointmentDate = new Date(appointment.appointmentDate).toISOString().split('T')[0];
            matches = matches && appointmentDate === dateFilter;
        }
        
        if (mechanicFilter) {
            matches = matches && appointment.mechanicId === mechanicFilter;
        }
        
        if (statusFilter) {
            matches = matches && appointment.status === statusFilter;
        }
        
        return matches;
    });
    
    updateTable();
}

function openStatusModal(appointmentId, currentStatus) {
    document.getElementById('currentAppointmentId').value = appointmentId;
    document.getElementById('statusSelect').value = currentStatus;
    showModal('statusModal');
}

async function updateStatus() {
    const appointmentId = document.getElementById('currentAppointmentId').value;
    const newStatus = document.getElementById('statusSelect').value;
    
    try {
        console.log(`🎰 SLOT-BASED ADMIN: Updating status for ${appointmentId} to ${newStatus}`);
        
        const response = await fetch(`${API_BASE_URL}/update_appointment.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                appointmentId: appointmentId,
                status: newStatus
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ SLOT-BASED ADMIN: Status updated successfully');
            
            // Update local data
            const appointment = allAppointments.find(a => a._id === appointmentId);
            if (appointment) {
                appointment.status = newStatus;
            }
            
            closeModal('statusModal');
            
            // Show success message with slot information if available
            let message = 'Status updated successfully!';
            if (result.slotUpdate) {
                message += `\n\n🎰 Slot Update:\n${result.slotUpdate.mechanicName}: ${result.slotUpdate.currentAvailableSlots}/${result.slotUpdate.totalSlots} slots available`;
            }
            
            document.getElementById('successMessage').textContent = message;
            showModal('successModal');
            
            // Refresh data to show updated slots
            await loadMechanics();
            applyFilters();
            updateStats();
            
        } else {
            console.error('❌ Status update failed:', result.message);
            showModal('errorModal');
        }
        
    } catch (error) {
        console.error('❌ Error updating status:', error);
        showModal('errorModal');
    }
}

function showDetails(appointmentId) {
    const appointment = allAppointments.find(a => a._id === appointmentId);
    if (!appointment) return;
    
    const mechanic = allMechanics.find(m => m._id === appointment.mechanicId);
    const mechanicName = mechanic ? mechanic.name : 'Unknown Mechanic';
    const mechanicSlots = mechanic ? `${mechanic.availableSlots}/${mechanic.totalSlots} slots` : 'N/A';
    
    const detailsContent = document.getElementById('appointmentDetailsContent');
    detailsContent.innerHTML = `
        <div class="appointment-details">
            <div class="form-group">
                <strong>Client Information:</strong>
                <p>Name: ${appointment.clientName}</p>
                <p>Phone: ${appointment.clientPhone}</p>
                <p>Address: ${appointment.clientAddress}</p>
            </div>
            
            <div class="form-group">
                <strong>Vehicle Information:</strong>
                <p>License: ${appointment.carLicense}</p>
                <p>Engine: ${appointment.carEngine}</p>
            </div>
            
            <div class="form-group">
                <strong>Appointment Information:</strong>
                <p>Date: ${new Date(appointment.appointmentDate).toLocaleDateString()}</p>
                <p>Mechanic: ${mechanicName}</p>
                <p>🎰 Mechanic Slots: ${mechanicSlots}</p>
                <p>Status: <span class="status-badge status-${appointment.status}">${appointment.status}</span></p>
            </div>
            
            ${appointment.notes ? `
            <div class="form-group">
                <strong>Notes:</strong>
                <p>${appointment.notes}</p>
            </div>
            ` : ''}
            
            <div class="form-group">
                <strong>System Information:</strong>
                <p>Created: ${new Date(appointment.createdAt).toLocaleString()}</p>
                <p>Updated: ${new Date(appointment.updatedAt).toLocaleString()}</p>
                <p>🎰 System: SLOT-BASED</p>
            </div>
        </div>
    `;
    
    showModal('detailsModal');
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('show');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.remove('show');
}

async function refreshAppointments() {
    console.log('🔄 SLOT-BASED ADMIN: Refreshing data...');
    await loadMechanics();
    await loadAppointments();
}

function showNoAppointments() {
    const tbody = document.getElementById('appointmentsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" style="text-align: center; padding: 20px;">
                <div class="no-data">
                    <p>📝 No appointments found</p>
                    <button class="btn-primary" onclick="refreshAppointments()">Refresh</button>
                </div>
            </td>
        </tr>
    `;
}

function showError(message) {
    console.error('🎰 SLOT-BASED ADMIN Error:', message);
    const tbody = document.getElementById('appointmentsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="7" style="text-align: center; padding: 20px; color: #e53e3e;">
                <div class="error">
                    <p>❌ ${message}</p>
                    <button class="btn-primary" onclick="refreshAppointments()">Try Again</button>
                </div>
            </td>
        </tr>
    `;
}

console.log('✅ 🎰 SLOT-BASED ADMIN: Script loaded successfully!');