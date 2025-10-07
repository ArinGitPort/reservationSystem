// Order Management JavaScript - Handles all client-side logic for admin order management

// Sidebar toggle function
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Load page data when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadOrdersTable();
    
    // Handle filter form submission
    document.getElementById('filter-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const searchTerm = document.getElementById('search-input').value;
        const statusFilter = document.getElementById('status-filter').value;
        loadOrdersTable(searchTerm, statusFilter);
    });
});

// Refresh orders function
function refreshOrders() {
    loadDashboardData();
    loadOrdersTable();
}

// Load dashboard statistics
function loadDashboardData() {
    fetch('../../controllers/OrderController.php?action=get_dashboard_data')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('pending-count').textContent = data.statusCounts.pending || 0;
            
            const totalOrders = Object.values(data.statusCounts).reduce((sum, count) => sum + count, 0);
            document.getElementById('total-orders').textContent = totalOrders;
            
            document.getElementById('today-revenue').textContent = '₱' + (data.todaysData.total_revenue || 0).toFixed(2);
        } else {
            console.error('Error loading dashboard data:', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

// Load orders table
function loadOrdersTable(searchTerm = '', statusFilter = '') {
    const tableBody = document.getElementById('orders-table-body');
    
    // Show loading state
    tableBody.innerHTML = `
        <tr>
            <td colspan="9" class="empty-state">
                <div class="empty-content">
                    <i class="fas fa-spinner fa-spin"></i>
                    <h5>Loading orders...</h5>
                    <p>Please wait while we fetch your order data.</p>
                </div>
            </td>
        </tr>
    `;
    
    // Build query parameters
    const params = new URLSearchParams();
    params.append('action', 'get_orders');
    if (searchTerm) params.append('search', searchTerm);
    if (statusFilter) params.append('status', statusFilter);
    
    // Fetch orders from controller
    fetch(`../../controllers/OrderController.php?${params.toString()}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.orders.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="9" class="empty-state">
                            <div class="empty-content">
                                <i class="fas fa-shopping-bag"></i>
                                <h5>No orders found</h5>
                                <p>Try adjusting your search or filter criteria.</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                let rowsHtml = '';
                data.orders.forEach((order, index) => {
                    const orderTypeIcon = order.order_type === 'dine-in' ? 'utensils' : 
                                         (order.order_type === 'takeout' ? 'shopping-bag' : 'truck');
                    
                    rowsHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong class="order-id">EFH-${String(order.order_id).padStart(6, '0')}</strong></td>
                            <td class="customer-info">
                                <div class="fw-bold">${escapeHtml(order.customer_name)}</div>
                                ${order.customer_email ? `<small class="text-muted">${escapeHtml(order.customer_email)}</small>` : ''}
                            </td>
                            <td>${escapeHtml(order.customer_phone)}</td>
                            <td>
                                <span class="badge badge-${order.order_type}">
                                    <i class="fas fa-${orderTypeIcon} me-1"></i>
                                    ${order.order_type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                </span>
                            </td>
                            <td><strong>₱${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                            <td>
                                <select class="form-select form-select-sm status-select status-${order.order_status}" 
                                        data-order-id="${order.order_id}" 
                                        onchange="updateOrderStatus(this)">
                                    <option value="pending" ${order.order_status === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="confirmed" ${order.order_status === 'confirmed' ? 'selected' : ''}>Confirmed</option>
                                    <option value="preparing" ${order.order_status === 'preparing' ? 'selected' : ''}>Preparing</option>
                                    <option value="ready" ${order.order_status === 'ready' ? 'selected' : ''}>Ready</option>
                                    <option value="delivered" ${order.order_status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                    <option value="cancelled" ${order.order_status === 'cancelled' ? 'selected' : ''}>Cancelled</option>
                                </select>
                            </td>
                            <td class="order-date">
                                <div>${formatDate(order.order_date)}</div>
                                <small class="text-muted">${formatTime(order.order_date)}</small>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-sm" 
                                            style="background-color: #343A40; color: white; border-color: #343A40;"
                                            onclick="viewOrderDetails(${order.order_id})" 
                                            title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                });
                tableBody.innerHTML = rowsHtml;
            }
        } else {
            console.error('Error loading orders:', data.error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="9" class="empty-state">
                        <div class="empty-content">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                            <h5>Error loading orders</h5>
                            <p>Please try refreshing the page.</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tableBody.innerHTML = `
            <tr>
                <td colspan="9" class="empty-state">
                    <div class="empty-content">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <h5>Network error</h5>
                        <p>Please check your connection and try again.</p>
                    </div>
                </td>
            </tr>
        `;
    });
}

// Clear filters function
function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('status-filter').value = '';
    loadOrdersTable();
}

// Update order status
function updateOrderStatus(selectElement) {
    const orderId = selectElement.dataset.orderId;
    const newStatus = selectElement.value;
    const oldStatus = selectElement.dataset.currentStatus || '';
    
    // Disable the select while updating
    selectElement.disabled = true;
    
    fetch('../../controllers/OrderController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_order_status&order_id=${orderId}&status=${newStatus}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update the select element classes
            selectElement.className = `form-select form-select-sm status-select status-${newStatus}`;
            selectElement.dataset.currentStatus = newStatus;
            
            // Show success message
            showToast('Order status updated successfully', 'success');
            
            // Refresh dashboard data
            loadDashboardData();
        } else {
            // Revert to old status
            selectElement.value = oldStatus;
            showToast('Failed to update order status: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.value = oldStatus;
        showToast('Network error. Please try again.', 'error');
    })
    .finally(() => {
        selectElement.disabled = false;
    });
}

// View order details in modal
function viewOrderDetails(orderId) {
    // Show loading state
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="text-center p-4">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Loading order details...</p>
        </div>
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
    
    // Fetch order details
    fetch(`../../controllers/OrderController.php?action=get_order_details&order_id=${orderId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayOrderDetails(data.order);
        } else {
            document.getElementById('orderDetailsContent').innerHTML = `
                <div class="text-center p-4 text-danger">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                    <h5>Error loading order details</h5>
                    <p>${data.error || 'Unknown error occurred'}</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('orderDetailsContent').innerHTML = `
            <div class="text-center p-4 text-danger">
                <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                <h5>Network error</h5>
                <p>Please check your connection and try again.</p>
            </div>
        `;
    });
}

// Display order details in modal
function displayOrderDetails(order) {
    let itemsHtml = '';
    if (order.items && order.items.length > 0) {
        order.items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="../../uploads/menu/${item.image || 'default.jpg'}" 
                                 alt="${escapeHtml(item.name)}" 
                                 class="me-3" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                            <div>
                                <h6 class="mb-0">${escapeHtml(item.name)}</h6>
                                <small class="text-muted">₱${parseFloat(item.price).toFixed(2)} each</small>
                            </div>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-primary rounded-pill">${item.quantity}</span>
                    </td>
                    <td class="text-end">
                        <strong>₱${(parseFloat(item.price) * parseInt(item.quantity)).toFixed(2)}</strong>
                    </td>
                </tr>
            `;
        });
    } else {
        itemsHtml = `
            <tr>
                <td colspan="3" class="text-center text-muted">
                    <i class="fas fa-shopping-basket me-2"></i>
                    No items found for this order
                </td>
            </tr>
        `;
    }
    
    const orderTypeIcon = order.order_type === 'dine-in' ? 'utensils' : 
                         (order.order_type === 'takeout' ? 'shopping-bag' : 'truck');
    
    document.getElementById('orderDetailsContent').innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Order Information</h6>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="text-muted" style="width: 120px;">Order ID:</td>
                        <td><strong>EFH-${String(order.order_id).padStart(6, '0')}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Order Type:</td>
                        <td>
                            <span class="badge badge-${order.order_type}">
                                <i class="fas fa-${orderTypeIcon} me-1"></i>
                                ${order.order_type.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status:</td>
                        <td><span class="badge bg-${getStatusColor(order.order_status)}">${order.order_status.toUpperCase()}</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Order Date:</td>
                        <td>${formatDate(order.order_date)} at ${formatTime(order.order_date)}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Amount:</td>
                        <td><strong class="text-success">₱${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Customer Information</h6>
                <table class="table table-borderless table-sm">
                    <tr>
                        <td class="text-muted" style="width: 80px;">Name:</td>
                        <td><strong>${escapeHtml(order.customer_name)}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phone:</td>
                        <td>${escapeHtml(order.customer_phone)}</td>
                    </tr>
                    ${order.customer_email ? `
                    <tr>
                        <td class="text-muted">Email:</td>
                        <td>${escapeHtml(order.customer_email)}</td>
                    </tr>
                    ` : ''}
                    ${order.special_requests ? `
                    <tr>
                        <td class="text-muted">Special Requests:</td>
                        <td class="text-wrap">${escapeHtml(order.special_requests)}</td>
                    </tr>
                    ` : ''}
                </table>
            </div>
        </div>
        
        <hr>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Order Items</h6>
            <span class="text-muted">${order.items ? order.items.length : 0} item(s)</span>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead class="table-light">
                    <tr>
                        <th>Item</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="2" class="text-end"><strong>Total Amount:</strong></td>
                        <td class="text-end"><strong>₱${parseFloat(order.total_amount).toFixed(2)}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <button type="button" class="btn btn-warning w-100" onclick="showCancelOrderConfirmation(${order.order_id})">
                    <i class="fas fa-ban me-2"></i>Cancel Order
                </button>
            </div>
            <div class="col-md-6">
                <button type="button" class="btn btn-danger w-100" onclick="showDeleteOrderConfirmation(${order.order_id})">
                    <i class="fas fa-trash me-2"></i>Delete Order
                </button>
            </div>
        </div>
    `;
}

// Cancel order with styled confirmation
function showCancelOrderConfirmation(orderId) {
    showConfirmation(
        'Cancel Order',
        'Are you sure you want to cancel this order? This action cannot be undone.',
        'warning',
        function() {
            cancelOrder(orderId);
        }
    );
}

// Delete order with styled confirmation
function showDeleteOrderConfirmation(orderId) {
    showConfirmation(
        'Delete Order',
        'Are you sure you want to permanently delete this order? This action cannot be undone and will remove all order data from the system.',
        'danger',
        function() {
            deleteOrder(orderId);
        }
    );
}

// Cancel order function
function cancelOrder(orderId) {
    fetch('../../controllers/OrderController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=cancel_order&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Order cancelled successfully', 'success');
            
            // Close the order details modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal'));
            if (modal) {
                modal.hide();
            }
            
            // Refresh the orders table and dashboard
            loadOrdersTable();
            loadDashboardData();
        } else {
            showToast('Failed to cancel order: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    });
}

// Delete order function
function deleteOrder(orderId) {
    fetch('../../controllers/OrderController.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete_order&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Order deleted successfully', 'success');
            
            // Close the order details modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('orderDetailsModal'));
            if (modal) {
                modal.hide();
            }
            
            // Refresh the orders table and dashboard
            loadOrdersTable();
            loadDashboardData();
        } else {
            showToast('Failed to delete order: ' + (data.error || 'Unknown error'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Network error. Please try again.', 'error');
    });
}

// Utility Functions

// Escape HTML to prevent XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
}

// Format date to readable format
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

// Format time to readable format
function formatTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Get status color for badges
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'confirmed': 'info',
        'preparing': 'primary',
        'ready': 'success',
        'delivered': 'success',
        'cancelled': 'danger'
    };
    return colors[status] || 'secondary';
}

// Enhanced styled confirmation modal
function showConfirmation(title, message, type = 'warning', onConfirm = null, onCancel = null) {
    // Remove any existing confirmation modal
    const existingModal = document.getElementById('confirmationModal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Create modal HTML
    const iconClass = type === 'danger' ? 'fa-exclamation-triangle text-danger' : 
                      type === 'warning' ? 'fa-exclamation-circle text-warning' : 
                      'fa-question-circle text-primary';
    
    const confirmBtnClass = type === 'danger' ? 'btn-danger' : 
                           type === 'warning' ? 'btn-warning' : 
                           'btn-primary';
    
    const modalHtml = `
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-light border-0">
                        <h5 class="modal-title d-flex align-items-center" id="confirmationModalLabel">
                            <i class="fas ${iconClass} me-2 fa-lg"></i>
                            ${escapeHtml(title)}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <div class="text-center mb-4">
                            <i class="fas ${iconClass} fa-3x mb-3"></i>
                            <p class="mb-0 text-dark fs-6">${escapeHtml(message)}</p>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 px-4 py-3">
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i>Cancel
                        </button>
                        <button type="button" class="btn ${confirmBtnClass}" id="confirmButton">
                            <i class="fas fa-check me-1"></i>Confirm
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Initialize and show modal
    const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    modal.show();
    
    // Handle confirm button click
    document.getElementById('confirmButton').onclick = function() {
        modal.hide();
        if (onConfirm) {
            onConfirm();
        }
    };
    
    // Handle modal hidden event (cleanup)
    document.getElementById('confirmationModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
        if (onCancel) {
            onCancel();
        }
    }, { once: true });
}

// Enhanced toast notification system
function showToast(message, type = 'info', duration = 4000) {
    // Remove any existing toast
    const existingToast = document.getElementById('dynamicToast');
    if (existingToast) {
        existingToast.remove();
    }
    
    // Determine toast styling based on type
    const toastConfig = {
        success: {
            bgClass: 'bg-success',
            icon: 'fa-check-circle',
            iconColor: 'text-white'
        },
        error: {
            bgClass: 'bg-danger', 
            icon: 'fa-exclamation-circle',
            iconColor: 'text-white'
        },
        warning: {
            bgClass: 'bg-warning',
            icon: 'fa-exclamation-triangle', 
            iconColor: 'text-dark'
        },
        info: {
            bgClass: 'bg-info',
            icon: 'fa-info-circle',
            iconColor: 'text-white'
        }
    };
    
    const config = toastConfig[type] || toastConfig.info;
    
    // Create toast HTML
    const toastHtml = `
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 11050;">
            <div id="dynamicToast" class="toast ${config.bgClass} border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-body d-flex align-items-center ${config.iconColor}">
                    <i class="fas ${config.icon} me-3 fa-lg"></i>
                    <div class="flex-grow-1">
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-2 me-1" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>
    `;
    
    // Add toast to page
    document.body.insertAdjacentHTML('beforeend', toastHtml);
    
    // Initialize and show toast
    const toast = new bootstrap.Toast(document.getElementById('dynamicToast'), {
        delay: duration,
        autohide: true
    });
    
    toast.show();
    
    // Remove toast element after it's hidden
    document.getElementById('dynamicToast').addEventListener('hidden.bs.toast', function() {
        this.parentElement.remove();
    }, { once: true });
}
