/**
/**
 * Enhanced Location Sharing System v2.0 for Southrift Services
 * Real-time driver location sharing with duration selection and confirmation
 */

class EnhancedLocationSharingManager {
    constructor() {
        this.watchId = null;
        this.isSharing = false;
        this.isPaused = false;
        this.updateInterval = null;
        this.driverId = document.querySelector('meta[name="driver-id"]')?.content || '';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 5000
        };
        this.lastKnownPosition = null;
        this.passengerCount = 0;
        this.sessionData = null;
        this.countdownInterval = null;
        this.sessionEndTime = null;
        
        this.initializeUI();
        this.createModal();
    }

    initializeUI() {
        this.checkCurrentSharingStatus();
        
        const locationCard = document.getElementById('locationCard');
        if (locationCard) {
            locationCard.addEventListener('click', () => this.handleLocationCardClick());
        }
        
        const pauseBtn = document.getElementById('pauseBtn');
        const stopBtn = document.getElementById('stopBtn');
        
        if (pauseBtn) {
            pauseBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.togglePause();
            });
        }
        
        if (stopBtn) {
            stopBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.stopLocationSharing();
            });
        }
        
        const whatsappCard = document.getElementById('whatsappCard');
        if (whatsappCard) {
            whatsappCard.addEventListener('click', () => this.handleWhatsAppClick());
        }
    }

    createModal() {
        const modalHTML = `
            <div id="locationSharingModal" class="modal-overlay" style="display: none;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3><i class="fas fa-location-dot"></i> Share Live Location</h3>
                        <button id="closeModal" class="close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="duration-section">
                            <h4><i class="fas fa-clock"></i> Select Duration</h4>
                            <div class="duration-options">
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="30" checked>
                                    <span>30 minutes</span>
                                </label>
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="60">
                                    <span>1 hour</span>
                                </label>
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="120">
                                    <span>2 hours</span>
                                </label>
                                <label class="duration-option">
                                    <input type="radio" name="duration" value="until_trip_end">
                                    <span>Until trip ends</span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="passenger-section">
                            <h4><i class="fas fa-users"></i> Passengers to Notify</h4>
                            <div id="passengerList" class="passenger-list">
                                <div class="loading">Loading passengers...</div>
                            </div>
                        </div>
                        
                        <div class="consent-section">
                            <label class="consent-checkbox">
                                <input type="checkbox" id="consentCheck" required>
                                <span>I consent to sharing my live location with the selected passengers.</span>
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button id="cancelShare" class="btn-secondary">Cancel</button>
                        <button id="confirmShare" class="btn-primary" disabled>
                            <i class="fas fa-share-alt"></i> Start Sharing
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.attachModalEventListeners();
    }

    attachModalEventListeners() {
        document.getElementById('closeModal').addEventListener('click', () => this.hideModal());
        document.getElementById('cancelShare').addEventListener('click', () => this.hideModal());
        
        document.querySelectorAll('input[name="duration"]').forEach(radio => {
            radio.addEventListener('change', () => this.validateForm());
        });
        
        document.getElementById('consentCheck').addEventListener('change', () => this.validateForm());
        document.getElementById('confirmShare').addEventListener('click', () => this.confirmLocationSharing());
        
        document.getElementById('locationSharingModal').addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.hideModal();
            }
        });
    }

    async handleLocationCardClick() {
        if (this.isSharing) {
            return;
        } else {
            await this.showLocationSharingModal();
        }
    }

    async showLocationSharingModal() {
        await this.loadPassengers();
        document.querySelector('input[name="duration"][value="30"]').checked = true;
        document.getElementById('consentCheck').checked = false;
        document.getElementById('locationSharingModal').style.display = 'block';
        this.validateForm();
    }

    hideModal() {
        document.getElementById('locationSharingModal').style.display = 'none';
    }

    async loadPassengers() {
        const passengerList = document.getElementById('passengerList');
        passengerList.innerHTML = '<div class="loading">Loading passengers...</div>';
        
        try {
            const response = await fetch('get_assigned_passengers.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=${encodeURIComponent(this.csrfToken)}`
            });
            
            const data = await response.json();
            
            if (data.success && data.passengers && data.passengers.length > 0) {
                this.renderPassengerList(data.passengers);
            } else {
                passengerList.innerHTML = `
                    <div class="no-passengers">
                        <i class="fas fa-info-circle"></i>
                        <p>No passengers assigned for today.</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading passengers:', error);
            passengerList.innerHTML = `
                <div class="error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Error loading passengers. Please try again.</p>
                </div>
            `;
        }
    }

    renderPassengerList(passengers) {
        const passengerList = document.getElementById('passengerList');
        
        const html = passengers.map(passenger => `
            <div class="passenger-item" data-user-id="${passenger.user_id}">
                <div class="passenger-info">
                    <div class="passenger-name">
                        <i class="fas fa-user"></i>
                        ${passenger.fullname || passenger.name || 'Unknown'}
                    </div>
                    <div class="passenger-details">
                        <span class="phone"><i class="fas fa-phone"></i> ${passenger.phone}</span>
                    </div>
                </div>
                <div class="passenger-select">
                    <input type="checkbox" id="passenger_${passenger.user_id}" value="${passenger.user_id}" checked>
                    <label for="passenger_${passenger.user_id}">Notify</label>
                </div>
            </div>
        `).join('');
        
        passengerList.innerHTML = html || '<div class="no-passengers">No passengers found</div>';
        this.passengerCount = passengers.length;
    }

    validateForm() {
        const consentChecked = document.getElementById('consentCheck').checked;
        document.getElementById('confirmShare').disabled = !consentChecked;
    }

    async confirmLocationSharing() {
        try {
            const selectedDuration = document.querySelector('input[name="duration"]:checked').value;
            let durationMinutes = selectedDuration === 'until_trip_end' ? null : parseInt(selectedDuration);
            
            const selectedPassengers = Array.from(document.querySelectorAll('.passenger-item input[type="checkbox"]:checked'))
                .map(cb => cb.value);
            
            this.hideModal();
            this.showNotification('Starting location sharing...', 'info');
            
            await this.startLocationSharingWithConfig(durationMinutes, selectedPassengers);
            
        } catch (error) {
            this.showError('Failed to start location sharing: ' + error.message);
        }
    }

    async confirmLocationSharing() {
        try {
            const selectedDuration = document.querySelector('input[name="duration"]:checked').value;
            let durationMinutes = selectedDuration === 'until_trip_end' ? null : parseInt(selectedDuration);
            
            const selectedPassengers = Array.from(document.querySelectorAll('.passenger-item input[type="checkbox"]:checked'))
                .map(cb => cb.value);
            
            this.hideModal();
            this.showNotification('Starting location sharing...', 'info');
            
            await this.startLocationSharingWithConfig(durationMinutes, selectedPassengers);
            
        } catch (error) {
            this.showError('Failed to start location sharing: ' + error.message);
        }
    }

    async startLocationSharingWithConfig(durationMinutes, selectedPassengers) {
        if (!navigator.geolocation) {
            this.showError('Geolocation is not supported by your browser');
            return;
        }

        try {
            // First get current position to test permissions
            const position = await this.getCurrentPosition();
            
            // Create sharing session
            const sessionResult = await this.createSharingSession(durationMinutes, selectedPassengers);
            if (!sessionResult.success) {
                throw new Error(sessionResult.error || 'Failed to create sharing session');
            }

            this.sessionData = sessionResult.session;
            this.isSharing = true;
            this.updateUI(true);

            // Set session end time if duration is specified
            if (durationMinutes) {
                this.sessionEndTime = new Date(Date.now() + (durationMinutes * 60 * 1000));
                this.startCountdown();
            }

            // Start watching position
            this.watchId = navigator.geolocation.watchPosition(
                (pos) => this.handleLocationUpdate(pos),
                (error) => this.handleLocationError(error),
                this.options
            );

            // Set up interval for database updates (every 10 seconds)
            this.updateInterval = setInterval(() => {
                if (this.lastKnownPosition && !this.isPaused) {
                    this.sendLocationUpdate(this.lastKnownPosition);
                }
            }, 10000);

            this.showSuccess(`Location sharing started! ${selectedPassengers.length} passengers will be notified.`);
            
        } catch (error) {
            this.showError('Failed to start location sharing: ' + error.message);
            await this.stopLocationSharing();
        }
    }

    async createSharingSession(durationMinutes, selectedPassengers) {
        const formData = new URLSearchParams();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'create_session');
        formData.append('duration_minutes', durationMinutes || '');
        formData.append('selected_passengers', JSON.stringify(selectedPassengers));

        const response = await fetch('manage_sharing_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        });

        return await response.json();
    }

    startCountdown() {
        if (!this.sessionEndTime) return;
        
        this.countdownInterval = setInterval(() => {
            const now = new Date();
            const timeLeft = this.sessionEndTime - now;
            
            if (timeLeft <= 0) {
                this.stopLocationSharing();
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);
            
            const countdownElement = document.getElementById('countdownTimer');
            if (countdownElement) {
                countdownElement.innerHTML = `<i class="fas fa-clock"></i> Time remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    }

    togglePause() {
        this.isPaused = !this.isPaused;
        const pauseBtn = document.getElementById('pauseBtn');
        
        if (this.isPaused) {
            pauseBtn.innerHTML = '<i class="fas fa-play"></i> Resume';
            pauseBtn.style.background = '#4CAF50';
            this.showNotification('Location sharing paused', 'info');
        } else {
            pauseBtn.innerHTML = '<i class="fas fa-pause"></i> Pause';
            pauseBtn.style.background = '#ff9800';
            this.showNotification('Location sharing resumed', 'info');
        }
    }

    async stopLocationSharing() {
        // Clear intervals and watches
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
        
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
            this.countdownInterval = null;
        }

        this.isSharing = false;
        this.isPaused = false;
        this.updateUI(false);

        try {
            // End sharing session
            if (this.sessionData) {
                await this.endSharingSession();
            }
            
            this.showSuccess('Location sharing stopped successfully.');
            
        } catch (error) {
            console.error('Error stopping location sharing:', error);
            this.showError('Location sharing stopped, but there was an error updating the system.');
        }

        this.lastKnownPosition = null;
        this.passengerCount = 0;
        this.sessionData = null;
        this.sessionEndTime = null;
    }

    async endSharingSession() {
        const formData = new URLSearchParams();
        formData.append('csrf_token', this.csrfToken);
        formData.append('action', 'end_session');
        formData.append('session_token', this.sessionData.token);

        const response = await fetch('manage_sharing_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        });

        return await response.json();
    }

    async checkCurrentSharingStatus() {
        try {
            const response = await fetch('check_sharing_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `csrf_token=${encodeURIComponent(this.csrfToken)}`
            });
            
            const data = await response.json();
            if (data.success && data.is_sharing) {
                this.isSharing = true;
                this.sessionData = data.session;
                this.passengerCount = data.passenger_count || 0;
                
                if (data.session && data.session.expires_at) {
                    this.sessionEndTime = new Date(data.session.expires_at);
                    this.startCountdown();
                }
                
                this.updateUI(true);
            }
        } catch (error) {
            console.error('Error checking sharing status:', error);
        }
    }

    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, this.options);
        });
    }

    handleLocationUpdate(position) {
        this.lastKnownPosition = position;
        this.updateLocationDisplay(position);
        
        if (!this.updateInterval) {
            this.sendLocationUpdate(position);
        }
    }

    handleLocationError(error) {
        let errorMessage = 'Location error: ';
        switch (error.code) {
            case error.PERMISSION_DENIED:
                errorMessage += 'Location access denied by user.';
                break;
            case error.POSITION_UNAVAILABLE:
                errorMessage += 'Location information unavailable.';
                break;
            case error.TIMEOUT:
                errorMessage += 'Location request timed out.';
                break;
            default:
                errorMessage += 'An unknown error occurred.';
                break;
        }
        
        console.error('Location error:', error);
        this.showError(errorMessage);
        
        if (error.code !== error.TIMEOUT) {
            this.stopLocationSharing();
        }
    }

    async sendLocationUpdate(position) {
        const formData = new URLSearchParams();
        formData.append('csrf_token', this.csrfToken);
        formData.append('session_token', this.sessionData?.token || '');
        
        if (position) {
            formData.append('latitude', position.coords.latitude.toString());
            formData.append('longitude', position.coords.longitude.toString());
            formData.append('accuracy', (position.coords.accuracy || '').toString());
            formData.append('speed', (position.coords.speed || '').toString());
            formData.append('heading', (position.coords.heading || '').toString());
        }

        try {
            const response = await fetch('update_location_v2.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to update location');
            }
            
            return data;
        } catch (error) {
            console.error('Error updating location:', error);
            throw error;
        }
    }

    updateUI(isSharing) {
        const statusElement = document.getElementById('locationStatus');
        const indicatorElement = document.getElementById('locationStatusIndicator');
        const cardElement = document.getElementById('locationCard');
        const controlsElement = document.getElementById('sharingControls');

        if (isSharing) {
            statusElement.innerHTML = 'Sharing Live Location <i class="fas fa-location-dot" style="color: #e74c3c;"></i>';
            indicatorElement.style.display = 'block';
            controlsElement.style.display = 'block';
            cardElement.classList.add('sharing-active');
            
            // Update passenger count display
            if (this.passengerCount > 0) {
                const passengerInfo = document.createElement('div');
                passengerInfo.id = 'passengerInfo';
                passengerInfo.style.marginTop = '10px';
                passengerInfo.innerHTML = `<small>${this.passengerCount} passengers tracking you</small>`;
                
                const existing = document.getElementById('passengerInfo');
                if (existing) existing.remove();
                
                indicatorElement.appendChild(passengerInfo);
            }
        } else {
            statusElement.innerHTML = 'Share Live Location';
            indicatorElement.style.display = 'none';
            controlsElement.style.display = 'none';
            cardElement.classList.remove('sharing-active');
            
            const passengerInfo = document.getElementById('passengerInfo');
            if (passengerInfo) passengerInfo.remove();
            
            const locationDisplay = document.getElementById('locationDisplay');
            if (locationDisplay) locationDisplay.remove();
        }
    }

    updateLocationDisplay(position) {
        const indicatorElement = document.getElementById('locationStatusIndicator');
        if (!indicatorElement) return;

        const existing = document.getElementById('locationDisplay');
        if (existing) existing.remove();

        const locationDisplay = document.createElement('div');
        locationDisplay.id = 'locationDisplay';
        locationDisplay.style.marginTop = '8px';
        locationDisplay.style.fontSize = '0.85rem';
        locationDisplay.style.opacity = '0.8';
        
        const timestamp = new Date().toLocaleTimeString();
        const accuracy = position.coords.accuracy ? Math.round(position.coords.accuracy) + 'm' : 'Unknown';
        
        locationDisplay.innerHTML = `
            <div>Last update: ${timestamp}</div>
            <div>Accuracy: ${accuracy}</div>
        `;
        
        indicatorElement.appendChild(locationDisplay);
    }

    showSuccess(message) {
        this.showNotification(message, 'success');
    }

    showError(message) {
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 15px 20px;
            border-radius: 5px; color: white; font-weight: 500; z-index: 1000;
            max-width: 300px; animation: slideIn 0.3s ease-out;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        `;
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }
}

// Add essential CSS styles
const style = document.createElement('style');
style.textContent = `
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center;
        align-items: center; z-index: 1000;
    }
    
    .modal-content {
        background: white; border-radius: 10px; max-width: 500px; width: 90%;
        max-height: 80vh; overflow-y: auto; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .modal-header, .modal-footer {
        padding: 20px; border-bottom: 1px solid #eee; display: flex;
        justify-content: space-between; align-items: center;
    }
    
    .modal-body { padding: 20px; }
    
    .duration-options { display: flex; flex-direction: column; gap: 10px; }
    
    .duration-option {
        display: flex; align-items: center; padding: 10px; border: 2px solid #eee;
        border-radius: 8px; cursor: pointer; transition: all 0.3s;
    }
    
    .duration-option:hover { border-color: #6A0DAD; background: #f8f9ff; }
    
    .passenger-list { max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 8px; }
    
    .passenger-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 10px; border-bottom: 1px solid #f0f0f0;
    }
    
    .btn-primary {
        background: #6A0DAD; color: white; border: none; padding: 10px 20px;
        border-radius: 5px; cursor: pointer;
    }
    
    .btn-secondary {
        background: #6c757d; color: white; border: none; padding: 10px 20px;
        border-radius: 5px; cursor: pointer;
    }
    
    .close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #999; }
    
    @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
`;
document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.enhancedLocationManager = new EnhancedLocationSharingManager();
});

// Make it available globally
window.EnhancedLocationSharingManager = EnhancedLocationSharingManager;