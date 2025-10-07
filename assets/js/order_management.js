// Order Management JavaScript - Handles all client-side logic

// Initialize event listeners when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Handle status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            const currentStatus = this.dataset.currentStatus;
            
            if (newStatus === currentStatus) return;
            
            updateOrderStatus(orderId, newStatus, this);
        });
    });
});

/**
 * Update order status via AJAX
 */
function updateOrderStatus(orderId, newStatus, selectElement) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', newStatus);
    
    fetch('order_management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            selectElement.dataset.currentStatus = newStatus;
            showNotification('Order status updated successfully', 'success');
        } else {
            // Revert selection
            selectElement.value = selectElement.dataset.currentStatus;
            showNotification(data.message || 'Failed to update order status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        selectElement.value = selectElement.dataset.currentStatus;
        showNotification('An error occurred', 'error');
    });
}

/**
 * View order details in modal
 */
function viewOrderDetails(orderId) {
    fetch(`order_management.php?action=get_order&id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayOrderDetails(data.data);
            } else {
                showNotification(data.message || 'Failed to load order details', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load order details', 'error');
        });
}

/**
 * Display order details in modal
 */
function displayOrderDetails(orderData) {
    const order = orderData.order;
    const items = orderData.items;
    
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
            <tr>
                <td>${item.menu_name}</td>
                <td>${item.quantity}</td>
                <td>₱${parseFloat(item.price).toFixed(2)}</td>
                <td>₱${parseFloat(item.subtotal).toFixed(2)}</td>
            </tr>
        `;
    });
    
    const modalContent = `
        <div class="order-details">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6>Order Information</h6>
                    <p><strong>Order ID:</strong> EFH-${String(order.order_id).padStart(6, '0')}</p>
                    <p><strong>Status:</strong> <span class="badge bg-primary">${order.order_status}</span></p>
                    <p><strong>Type:</strong> ${order.order_type}</p>
                    <p><strong>Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
                </div>
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <p><strong>Name:</strong> ${order.customer_name}</p>
                    <p><strong>Phone:</strong> ${order.customer_phone}</p>
                    <p><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
                </div>
            </div>
            
            ${order.delivery_address ? `<p><strong>Delivery Address:</strong> ${order.delivery_address}</p>` : ''}
            ${order.special_instructions ? `<p><strong>Special Instructions:</strong> ${order.special_instructions}</p>` : ''}
            
            <h6 class="mt-3">Order Items</h6>
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    ${itemsHtml}
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Total:</th>
                        <th>₱${parseFloat(order.total_amount).toFixed(2)}</th>
                    </tr>
                </tfoot>
            </table>
            
            <div class="mt-3 d-flex gap-2">
                ${order.order_status !== 'cancelled' && order.order_status !== 'delivered' ? `
                    <button class="btn btn-warning" onclick="cancelOrder(${order.order_id})">
                        <i class="fas fa-times me-2"></i>Cancel Order
                    </button>
                ` : ''}
                ${order.order_status === 'cancelled' ? `
                    <button class="btn btn-danger" onclick="deleteOrder(${order.order_id})">
                        <i class="fas fa-trash me-2"></i>Delete Order
                    </button>
                ` : ''}
            </div>
        </div>
    `;
    
    document.getElementById('orderDetailsContent').innerHTML = modalContent;
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    modal.show();
}

/**
 * Cancel order
 */
function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order?')) {
        const formData = new FormData();
        formData.append('action', 'update_status');
        formData.append('order_id', orderId);
        formData.append('status', 'cancelled');
        
        fetch('order_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order cancelled successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Failed to cancel order', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
    }
}

/**
 * Delete order
 */
function deleteOrder(orderId) {
    if (confirm('Are you sure you want to delete this order? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('action', 'delete_order');
        formData.append('order_id', orderId);
        
        fetch('order_management.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Order deleted successfully', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message || 'Failed to delete order', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
    }
}

/**
 * Refresh orders page
 */
function refreshOrders() {
    window.location.reload();
}

/**
 * Show notification toast
 */
function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}
