/**
 * Enhanced Location Sharing System for Southrift Services
 * Real-time driver location sharing with passengers
 */

class LocationSharingManager {
    constructor() {
        this.watchId = null;
        this.isSharing = false;
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
        
        this.initializeUI();
    }

    initializeUI() {
        // Check if already sharing on page load
        this.checkCurrentSharingStatus();
        
        // Add event listeners
        const locationCard = document.getElementById('locationCard');
        if (locationCard) {
            locationCard.addEventListener('click', () => this.toggleLocationSharing());
        }
        
        // Add WhatsApp card event listener
        const whatsappCard = document.getElementById('whatsappCard');
        if (whatsappCard) {
            whatsappCard.addEventListener('click', () => this.handleWhatsAppClick());
        }
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
                this.updateUI(true);
                this.passengerCount = data.passenger_count || 0;
            }
        } catch (error) {
            console.error('Error checking sharing status:', error);
        }
    }

    async toggleLocationSharing() {
        if (this.isSharing) {
            await this.stopLocationSharing();
        } else {
            await this.startLocationSharing();
        }
    }

    async startLocationSharing() {
        if (!navigator.geolocation) {
            this.showError('Geolocation is not supported by your browser');
            return;
        }

        try {
            // First get current position to test permissions
            const position = await this.getCurrentPosition();
            
            // Notify passengers that sharing is starting
            const notifyResult = await this.notifyPassengers('start');
            if (!notifyResult.success) {
                throw new Error(notifyResult.error || 'Failed to notify passengers');
            }

            this.passengerCount = notifyResult.passengers_notified || 0;
            this.isSharing = true;
            this.updateUI(true);

            // Start watching position
            this.watchId = navigator.geolocation.watchPosition(
                (pos) => this.handleLocationUpdate(pos),
                (error) => this.handleLocationError(error),
                this.options
            );

            // Set up interval for database updates (every 10 seconds)
            this.updateInterval = setInterval(() => {
                if (this.lastKnownPosition) {
                    this.sendLocationUpdate(this.lastKnownPosition);
                }
            }, 10000);

            this.showSuccess(`Location sharing started! ${this.passengerCount} passengers notified.`);
            
        } catch (error) {
            this.showError('Failed to start location sharing: ' + error.message);
            await this.stopLocationSharing();
        }
    }

    async stopLocationSharing() {
        // Clear watch and interval
        if (this.watchId !== null) {
            navigator.geolocation.clearWatch(this.watchId);
            this.watchId = null;
        }
        
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }

        this.isSharing = false;
        this.updateUI(false);

        try {
            // Send stop signal to server
            await this.sendLocationUpdate(null, 'stopped');
            
            // Notify passengers that sharing stopped
            const notifyResult = await this.notifyPassengers('stop');
            this.showSuccess(`Location sharing stopped. ${notifyResult.passengers_notified || 0} passengers notified.`);
            
        } catch (error) {
            console.error('Error stopping location sharing:', error);
            this.showError('Location sharing stopped, but there was an error notifying passengers.');
        }

        this.lastKnownPosition = null;
        this.passengerCount = 0;
    }

    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            navigator.geolocation.getCurrentPosition(resolve, reject, this.options);
        });
    }

    handleLocationUpdate(position) {
        this.lastKnownPosition = position;
        
        // Update UI with current accuracy and timestamp
        this.updateLocationDisplay(position);
        
        // Send to server immediately for first update, then rely on interval
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
        
        // Don't auto-stop on timeout errors, just log them
        if (error.code !== error.TIMEOUT) {
            this.stopLocationSharing();
        }
    }

    async sendLocationUpdate(position, status = 'active') {
        const formData = new URLSearchParams();
        formData.append('csrf_token', this.csrfToken);
        
        if (status === 'stopped') {
            formData.append('status', 'stopped');
        } else if (position) {
            formData.append('latitude', position.coords.latitude.toString());
            formData.append('longitude', position.coords.longitude.toString());
            formData.append('accuracy', (position.coords.accuracy || '').toString());
            formData.append('speed', (position.coords.speed || '').toString());
            formData.append('heading', (position.coords.heading || '').toString());
        }

        try {
            const response = await fetch('update_location.php', {
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

    async notifyPassengers(action) {
        console.log(`Attempting to notify passengers with action: ${action}`);
        
        try {
            // Try enhanced notification system first
            console.log('Trying enhanced notification system...');
            let response = await fetch('enhanced_notify_passengers_location.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=${action}&csrf_token=${encodeURIComponent(this.csrfToken)}`
            });

            console.log('Enhanced notification response status:', response.status);
            let data = await response.json();
            console.log('Enhanced notification response data:', data);
            
            if (!data.success) {
                throw new Error(data.error || 'Enhanced notification failed');
            }
            
            console.log('Enhanced notification succeeded');
            return data;
        } catch (error) {
            console.warn('Enhanced notification failed, trying fallback:', error);
            
            // Fallback to original notification system
            try {
                console.log('Trying fallback notification system...');
                const response = await fetch('notify_passengers_location.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=${action}&csrf_token=${encodeURIComponent(this.csrfToken)}`
                });

                console.log('Fallback notification response status:', response.status);
                const data = await response.json();
                console.log('Fallback notification response data:', data);
                
                if (!data.success) {
                    throw new Error(data.error || 'Fallback notification failed');
                }
                
                console.log('Fallback notification succeeded');
                return data;
            } catch (fallbackError) {
                console.error('Both notification systems failed:', fallbackError);
                throw new Error('Failed to notify passengers: ' + fallbackError.message);
            }
        }
    }

    async sendWhatsAppLocation(customMessage = null) {
        if (!this.lastKnownPosition) {
            this.showError('No location available to share via WhatsApp');
            return;
        }

        try {
            const formData = new URLSearchParams();
            formData.append('csrf_token', this.csrfToken);
            formData.append('driver_id', this.driverId);
            formData.append('latitude', this.lastKnownPosition.coords.latitude.toString());
            formData.append('longitude', this.lastKnownPosition.coords.longitude.toString());
            
            if (customMessage) {
                formData.append('message', customMessage);
            }

            const response = await fetch('whatsapp_location_sender.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`Location sent via WhatsApp to ${data.sent_count} passengers!`);
                
                if (data.failed_numbers && data.failed_numbers.length > 0) {
                    this.showError(`Failed to send to ${data.failed_numbers.length} numbers`);
                }
            } else {
                throw new Error(data.message || 'Failed to send WhatsApp location');
            }
            
            return data;
        } catch (error) {
            console.error('WhatsApp location send error:', error);
            this.showError('Failed to send location via WhatsApp: ' + error.message);
            throw error;
        }
    }

    async sendWhatsAppLocationDirect(position, customMessage = null) {
        try {
            const formData = new URLSearchParams();
            formData.append('csrf_token', this.csrfToken);
            formData.append('driver_id', this.driverId);
            formData.append('latitude', position.coords.latitude.toString());
            formData.append('longitude', position.coords.longitude.toString());
            
            if (customMessage) {
                formData.append('message', customMessage);
            }

            const response = await fetch('whatsapp_location_sender.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: formData.toString()
            });

            const data = await response.json();
            
            if (data.success) {
                this.showSuccess(`Location sent via WhatsApp to ${data.sent_count} passengers!`);
                
                // Open WhatsApp URLs if provided
                if (data.whatsapp_urls && data.whatsapp_urls.length > 0) {
                    // Open each WhatsApp URL with a small delay between them
                    data.whatsapp_urls.forEach((url, index) => {
                        setTimeout(() => {
                            window.open(url, '_blank');
                        }, index * 1000); // 1 second delay between each
                    });
                }
                
                if (data.failed_numbers && data.failed_numbers.length > 0) {
                    this.showError(`Failed to send to ${data.failed_numbers.length} numbers`);
                }
            } else {
                throw new Error(data.message || 'Failed to send WhatsApp location');
            }
            
            return data;
        } catch (error) {
            console.error('WhatsApp location send error:', error);
            this.showError('Failed to send location via WhatsApp: ' + error.message);
            throw error;
        }
    }

    updateUI(isSharing) {
        const statusElement = document.getElementById('locationStatus');
        const indicatorElement = document.getElementById('locationStatusIndicator');
        const cardElement = document.getElementById('locationCard');

        if (isSharing) {
            statusElement.innerHTML = 'Sharing Live Location <i class="fas fa-location-dot" style="color: #e74c3c;"></i>';
            indicatorElement.style.display = 'block';
            cardElement.classList.add('sharing-active');
            
            // Update passenger count display
            if (this.passengerCount > 0) {
                const passengerInfo = document.createElement('div');
                passengerInfo.id = 'passengerInfo';
                passengerInfo.style.marginTop = '10px';
                passengerInfo.innerHTML = `<small>${this.passengerCount} passengers can track you</small>`;
                
                // Remove existing passenger info
                const existing = document.getElementById('passengerInfo');
                if (existing) existing.remove();
                
                indicatorElement.appendChild(passengerInfo);
            }
        } else {
            statusElement.innerHTML = 'Share Live Location';
            indicatorElement.style.display = 'none';
            cardElement.classList.remove('sharing-active');
            
            // Remove passenger info
            const passengerInfo = document.getElementById('passengerInfo');
            if (passengerInfo) passengerInfo.remove();
            
            // Remove location display
            const locationDisplay = document.getElementById('locationDisplay');
            if (locationDisplay) locationDisplay.remove();
        }
    }

    updateLocationDisplay(position) {
        const indicatorElement = document.getElementById('locationStatusIndicator');
        if (!indicatorElement) return;

        // Remove existing location display
        const existing = document.getElementById('locationDisplay');
        if (existing) existing.remove();

        // Create new location display
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
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 5px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            max-width: 300px;
            word-wrap: break-word;
            animation: slideIn 0.3s ease-out;
        `;
        
        // Set background color based on type
        switch (type) {
            case 'success':
                notification.style.backgroundColor = '#4CAF50';
                break;
            case 'error':
                notification.style.backgroundColor = '#f44336';
                break;
            default:
                notification.style.backgroundColor = '#2196F3';
        }
        
        notification.textContent = message;
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }

    async handleWhatsAppClick() {
        const whatsappCard = document.getElementById('whatsappCard');
        const whatsappStatus = document.getElementById('whatsappStatus');
        
        // Show loading state
        const originalContent = whatsappCard.innerHTML;
        whatsappCard.innerHTML = `
            <i class="fas fa-spinner fa-spin"></i>
            <h2>Getting Location...</h2>
            <p>Please wait while we get your current location</p>
        `;
        whatsappCard.style.pointerEvents = 'none';
        
        try {
            let position = this.lastKnownPosition;
            
            // If no location available or location sharing not active, get fresh location
            if (!position) {
                this.showNotification('Getting your current location...', 'info');
                position = await this.getCurrentPosition();
            }
            
            // Update loading message
            whatsappCard.innerHTML = `
                <i class="fas fa-spinner fa-spin"></i>
                <h2>Sending via WhatsApp...</h2>
                <p>Sending location to all passengers</p>
            `;
            
            // Send location via WhatsApp without prompting for custom message
            await this.sendWhatsAppLocationDirect(position, null);
            
            // Show success status
            whatsappStatus.style.display = 'block';
            whatsappStatus.innerHTML = '<span style="color: #4CAF50;">✓ Sent via WhatsApp!</span>';
            
            // Hide status after 5 seconds
            setTimeout(() => {
                whatsappStatus.style.display = 'none';
            }, 5000);
            
        } catch (error) {
            console.error('WhatsApp send failed:', error);
            this.showError('Failed to send location via WhatsApp: ' + error.message);
        } finally {
            // Restore original content
            whatsappCard.innerHTML = originalContent;
            whatsappCard.style.pointerEvents = 'auto';
        }
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
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
    
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .sharing-active {
        background: linear-gradient(135deg, #6A0DAD, #4CAF50) !important;
        box-shadow: 0 0 20px rgba(76, 175, 80, 0.4) !important;
    }
    
    .blinking-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        background-color: #4CAF50;
        border-radius: 50%;
        margin-right: 8px;
        animation: blink 1.5s infinite;
    }
    
    @keyframes blink {
        0% { opacity: 0.2; }
        50% { opacity: 1; }
        100% { opacity: 0.2; }
    }
    
    #whatsappCard:hover {
        background: linear-gradient(135deg, #25D366, #128C7E) !important;
        transform: translateY(-6px) scale(1.03);
        box-shadow: 0 14px 28px rgba(37, 211, 102, 0.3);
    }
    
    #whatsappCard .fab {
        font-size: 2.5rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
`;
document.head.appendChild(style);

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    window.locationManager = new LocationSharingManager();
});

// Make it available globally for debugging
window.LocationSharingManager = LocationSharingManager;