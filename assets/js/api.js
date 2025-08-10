/**
 * EV Mobile Station - API Client
 * Handles all AJAX requests to the backend
 */

class EVMobileAPI {
    constructor() {
        this.baseUrl = '/evv/api/';
        this.csrfToken = this.getCSRFToken();
    }

    // Get CSRF token from meta tag or form
    getCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            return metaTag.getAttribute('content');
        }
        
        const form = document.querySelector('form input[name="csrf_token"]');
        if (form) {
            return form.value;
        }
        
        return '';
    }

    // Generic AJAX request method
    async request(action, data = {}, method = 'POST') {
        const url = this.baseUrl + 'index.php';
        
        const requestData = {
            action: action,
            csrf_token: this.csrfToken,
            ...data
        };

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: method === 'POST' ? new URLSearchParams(requestData) : undefined
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.error) {
                throw new Error(result.error);
            }
            
            return result;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    }

    // User Authentication
    async login(username, password) {
        return await this.request('login', { username, password });
    }

    async signup(userData) {
        return await this.request('signup', userData);
    }

    async logout() {
        window.location.href = '/evv/logout.php';
    }

    // Service Requests
    async createServiceRequest(requestData) {
        return await this.request('create_service_request', requestData);
    }

    async getServiceRequests() {
        return await this.request('get_service_requests', {}, 'GET');
    }

    async updateServiceRequest(requestId, status) {
        return await this.request('update_service_request', { 
            request_id: requestId, 
            status: status 
        });
    }

    // Available Resources
    async getAvailableVehicles(type = null) {
        const params = type ? `?action=get_available_vehicles&type=${type}` : '?action=get_available_vehicles';
        return await this.request('get_available_vehicles', {}, 'GET');
    }

    async getAvailableTechnicians(specialization = null) {
        const params = specialization ? `?action=get_available_technicians&specialization=${specialization}` : '?action=get_available_technicians';
        return await this.request('get_available_technicians', {}, 'GET');
    }

    // Payments
    async createPayment(paymentData) {
        return await this.request('create_payment', paymentData);
    }

    // User Profile
    async getUserProfile() {
        return await this.request('get_user_profile', {}, 'GET');
    }

    async updateUserProfile(profileData) {
        return await this.request('update_user_profile', profileData);
    }

    // Admin Functions
    async adminLogin(username, password) {
        return await this.request('admin_login', { username, password });
    }

    async getDashboardStats() {
        return await this.request('admin_get_dashboard_stats', {}, 'GET');
    }

    async getAllRequests(status = null, limit = 50) {
        const params = { status, limit };
        return await this.request('admin_get_all_requests', params, 'GET');
    }

    async assignRequest(requestId, vehicleId, technicianId) {
        return await this.request('admin_assign_request', {
            request_id: requestId,
            vehicle_id: vehicleId,
            technician_id: technicianId
        });
    }
}

// Utility functions for common operations
class EVMobileUtils {
    // Show notification
    static showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 10000;
            max-width: 300px;
            animation: slideIn 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
        
        // Close button functionality
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }

    // Show loading spinner
    static showLoading(element) {
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        spinner.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        spinner.style.cssText = `
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #666;
        `;
        
        element.appendChild(spinner);
        return spinner;
    }

    // Hide loading spinner
    static hideLoading(spinner) {
        if (spinner && spinner.parentNode) {
            spinner.remove();
        }
    }

    // Format currency
    static formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    // Format date
    static formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Get urgency color
    static getUrgencyColor(urgency) {
        const colors = {
            'low': '#4CAF50',
            'medium': '#FF9800',
            'high': '#f44336',
            'emergency': '#9C27B0'
        };
        return colors[urgency] || '#666';
    }

    // Get status color
    static getStatusColor(status) {
        const colors = {
            'pending': '#FF9800',
            'assigned': '#2196F3',
            'in_progress': '#9C27B0',
            'completed': '#4CAF50',
            'cancelled': '#f44336'
        };
        return colors[status] || '#666';
    }

    // Validate email
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    // Validate phone
    static validatePhone(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/[\s\-\(\)]/g, ''));
    }

    // Debounce function
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Throttle function
    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Initialize API instance
const api = new EVMobileAPI();

// Add CSS for notifications
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
    
    .notification-close {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        cursor: pointer;
        margin-left: 10px;
        padding: 0;
        line-height: 1;
    }
    
    .notification-close:hover {
        opacity: 0.8;
    }
`;
document.head.appendChild(style);
