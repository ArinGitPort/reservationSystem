/**
 * Menu Management JavaScript
 * Handles menu item operations and search functionality
 */

class MenuManager {
    constructor() {
        this.searchTimeout = null;
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.initializeFilters();
        this.setupAutoFeatures();
    }
    
    setupEventListeners() {
        // Filter form submission
        const filterForm = document.getElementById('filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
        
        // Real-time search (debounced)
        const searchInput = document.getElementById('search-input');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    // Uncomment for real-time search
                    // this.performSearch();
                }, 500);
            });
        }
    }
    
    initializeFilters() {
        // Set current filter values from URL
        const urlParams = new URLSearchParams(window.location.search);
        const search = urlParams.get('search') || '';
        const bestSeller = urlParams.get('best_seller') || 'all';
        
        const searchInput = document.getElementById('search-input');
        const bestSellerSelect = document.getElementById('best_seller-filter'); // Updated to match search_filter.php pattern
        
        if (searchInput) searchInput.value = search;
        if (bestSellerSelect) bestSellerSelect.value = bestSeller;
    }
    
    setupAutoFeatures() {
        // Auto-dismiss alerts
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    }
    
    performSearch() {
        const search = document.getElementById('search-input')?.value || '';
        const bestSeller = document.getElementById('best_seller-filter')?.value || 'all'; // Updated ID
        
        // Build query string
        const params = new URLSearchParams();
        if (search.trim()) params.append('search', search.trim());
        if (bestSeller !== 'all') params.append('best_seller', bestSeller);
        
        // Redirect with filters
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    }
    
    clearFilters() {
        // Clear all form inputs
        const searchInput = document.getElementById('search-input');
        const bestSellerSelect = document.getElementById('best_seller-filter'); // Updated ID
        
        if (searchInput) searchInput.value = '';
        if (bestSellerSelect) bestSellerSelect.value = 'all';
        
        // Redirect to clean URL
        window.location.href = window.location.pathname;
    }
    
    refreshData() {
        // Refresh with current filters
        this.performSearch();
    }
    
    editMenuItem(item) {
        document.getElementById('edit_menu_id').value = item.menu_id;
        document.getElementById('edit_name').value = item.name;
        document.getElementById('edit_price').value = item.price;
        document.getElementById('edit_is_best_seller').checked = item.is_best_seller == 1;
    }
    
    confirmDelete(menuId, imagePath) {
        document.getElementById('deleteMenuId').value = menuId;
        document.getElementById('deleteImagePath').value = imagePath || '';
    }
}

// Global functions for backward compatibility
function editMenuItem(item) {
    window.menuManager.editMenuItem(item);
}

function confirmDelete(menuId, imagePath) {
    window.menuManager.confirmDelete(menuId, imagePath);
}

function clearFilters() {
    window.menuManager.clearFilters();
}

function refreshData() {
    window.menuManager.refreshData();
}

function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('show');
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.menuManager = new MenuManager();
});