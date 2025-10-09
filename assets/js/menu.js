// Shopping Cart Functionality
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let itemQuantities = {};

document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart
    updateCartDisplay();
    updateCartCount();
    
    // Setup modal click outside to close
    const checkoutModal = document.getElementById('checkoutModal');
    if (checkoutModal) {
        checkoutModal.addEventListener('click', function(e) {
            if (e.target === checkoutModal) {
                closeCheckoutModal();
            }
        });
    }
    
    // Setup order type change handler
    const orderTypeInputs = document.querySelectorAll('input[name="order_type"]');
    orderTypeInputs.forEach(input => {
        input.addEventListener('change', function() {
            const deliverySection = document.getElementById('deliveryAddress');
            if (this.value === 'delivery') {
                deliverySection.style.display = 'block';
                document.getElementById('address').required = true;
            } else {
                deliverySection.style.display = 'none';
                document.getElementById('address').required = false;
            }
        });
    });
    
    // Setup payment method change handlers
    const paymentInputs = document.querySelectorAll('input[name="payment_method"]');
    paymentInputs.forEach(input => {
        input.addEventListener('change', function() {
            const paymentDetails = document.getElementById('paymentDetails');
            const gcashDetails = document.getElementById('gcashDetails');
            const cardDetails = document.getElementById('cardDetails');
            const processBtn = document.getElementById('processPaymentBtn');
            
            // Hide all payment forms first
            gcashDetails.style.display = 'none';
            cardDetails.style.display = 'none';
            paymentDetails.style.display = 'none';
            
            // Update button text and show relevant form
            if (this.value === 'cash') {
                processBtn.innerHTML = '<i class="fas fa-hand-holding-usd me-2"></i>Place Order (Cash)';
            } else if (this.value === 'gcash') {
                processBtn.innerHTML = '<i class="fas fa-mobile-alt me-2"></i>Pay with GCash';
                paymentDetails.style.display = 'block';
                gcashDetails.style.display = 'block';
            } else if (this.value === 'card') {
                processBtn.innerHTML = '<i class="fas fa-credit-card me-2"></i>Pay with Card';
                paymentDetails.style.display = 'block';
                cardDetails.style.display = 'block';
            }
        });
    });
    const searchInput = document.getElementById('searchInput');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const menuItems = document.querySelectorAll('.menu-item');
    const menuGrid = document.getElementById('menuGrid');
    const noResults = document.getElementById('noResults');
    const paginationContainer = document.getElementById('paginationContainer');
    const pagination = document.getElementById('pagination');
    const pageInfo = document.getElementById('pageInfo');
    
    let currentFilter = 'all';
    let currentPage = 1;
    const itemsPerPage = 16;
    let filteredItems = [];
    
    // Initialize
    updateFilteredItems();
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        currentPage = 1;
        updateFilteredItems();
    });
    
    // Filter functionality
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            filterBtns.forEach(b => b.classList.remove('active'));
            // Add active class to clicked button
            this.classList.add('active');
            
            currentFilter = this.getAttribute('data-filter');
            currentPage = 1;
            updateFilteredItems();
        });
    });
    
    function updateFilteredItems() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        filteredItems = [];
        
        menuItems.forEach(item => {
            const itemName = item.getAttribute('data-name');
            const itemCategory = item.getAttribute('data-category');
            
            let matchesSearch = itemName.includes(searchTerm);
            let matchesFilter = currentFilter === 'all' || itemCategory === currentFilter;
            
            if (matchesSearch && matchesFilter) {
                filteredItems.push(item);
            }
        });
        
        displayItems();
        updatePagination();
    }
    
    function displayItems() {
        // Hide all items first
        menuItems.forEach(item => {
            item.style.display = 'none';
        });
        
        if (filteredItems.length === 0) {
            noResults.style.display = 'block';
            paginationContainer.style.display = 'none';
            return;
        }
        
        noResults.style.display = 'none';
        paginationContainer.style.display = 'flex';
        
        // Calculate start and end index for current page
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = Math.min(startIndex + itemsPerPage, filteredItems.length);
        
        // Show items for current page
        for (let i = startIndex; i < endIndex; i++) {
            filteredItems[i].style.display = 'block';
            filteredItems[i].style.animation = 'fadeIn 0.3s ease-in';
        }
        
        // Update page info
        const totalItems = filteredItems.length;
        const showingStart = startIndex + 1;
        const showingEnd = endIndex;
        pageInfo.textContent = `Showing ${showingStart}-${showingEnd} of ${totalItems} items`;
    }
    
    function updatePagination() {
        const totalPages = Math.ceil(filteredItems.length / itemsPerPage);
        pagination.innerHTML = '';
        
        if (totalPages <= 1) {
            paginationContainer.style.display = 'none';
            return;
        }
        
        paginationContainer.style.display = 'flex';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = currentPage === 1 ? 'disabled' : '';
        const prevLink = document.createElement('a');
        prevLink.href = '#';
        prevLink.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prevLink.onclick = (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                displayItems();
                updatePagination();
            }
        };
        prevLi.appendChild(prevLink);
        pagination.appendChild(prevLi);
        
        // Page numbers
        const maxVisiblePages = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
        
        if (endPage - startPage + 1 < maxVisiblePages) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        if (startPage > 1) {
            addPageButton(1);
            if (startPage > 2) {
                const ellipsis = document.createElement('li');
                ellipsis.innerHTML = '<span>...</span>';
                pagination.appendChild(ellipsis);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            addPageButton(i);
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('li');
                ellipsis.innerHTML = '<span>...</span>';
                pagination.appendChild(ellipsis);
            }
            addPageButton(totalPages);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = currentPage === totalPages ? 'disabled' : '';
        const nextLink = document.createElement('a');
        nextLink.href = '#';
        nextLink.innerHTML = '<i class="fas fa-chevron-right"></i>';
        nextLink.onclick = (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage++;
                displayItems();
                updatePagination();
            }
        };
        nextLi.appendChild(nextLink);
        pagination.appendChild(nextLi);
    }
    
    function addPageButton(pageNum) {
        const li = document.createElement('li');
        li.className = pageNum === currentPage ? 'active' : '';
        
        if (pageNum === currentPage) {
            li.innerHTML = `<span>${pageNum}</span>`;
        } else {
            const link = document.createElement('a');
            link.href = '#';
            link.textContent = pageNum;
            link.onclick = (e) => {
                e.preventDefault();
                currentPage = pageNum;
                displayItems();
                updatePagination();
            };
            li.appendChild(link);
        }
        
        pagination.appendChild(li);
    }
});

// Modal functionality
function openModal(imagePath, title, price) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const modalPrice = document.getElementById('modalPrice');
    
    // Clear previous image to prevent flickering
    modalImage.src = '';
    modalImage.style.opacity = '0';
    
    // Set new image source
    modalImage.src = '../uploads/menu/' + imagePath;
    modalTitle.textContent = title;
    modalPrice.textContent = '₱' + price;
    
    // Show modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    // Fade in image when loaded
    modalImage.onload = function() {
        modalImage.style.opacity = '1';
        modalImage.style.transition = 'opacity 0.3s ease';
    };
}

function closeModal() {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    
    // Clear image source to free memory
    modalImage.src = '';
    modalImage.onload = null;
}

// Close modal when clicking outside the image content
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-content')) {
        closeModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Cart Functions
function changeQuantity(menuId, change) {
    const currentQty = itemQuantities[menuId] || 0;
    const newQty = Math.max(0, currentQty + change);
    
    itemQuantities[menuId] = newQty;
    document.getElementById(`qty-${menuId}`).textContent = newQty;
    
    // Update add to cart button state
    const addBtn = document.querySelector(`[data-menu-id="${menuId}"] .add-to-cart-btn`);
    if (newQty > 0) {
        addBtn.classList.add('has-quantity');
    } else {
        addBtn.classList.remove('has-quantity');
    }
}

function addToCart(menuId, name, price, imagePath) {
    const quantity = itemQuantities[menuId] || 1;
    
    if (quantity <= 0) {
        itemQuantities[menuId] = 1;
        document.getElementById(`qty-${menuId}`).textContent = 1;
        return;
    }
    
    // Check if item already in cart
    const existingItemIndex = cart.findIndex(item => item.menu_id === menuId);
    
    if (existingItemIndex > -1) {
        // Update existing item
        cart[existingItemIndex].quantity += quantity;
    } else {
        // Add new item
        cart.push({
            menu_id: menuId,
            name: name,
            price: price,
            quantity: quantity,
            image_path: imagePath
        });
    }
    
    // Reset quantity counter
    itemQuantities[menuId] = 0;
    document.getElementById(`qty-${menuId}`).textContent = 0;
    document.querySelector(`[data-menu-id="${menuId}"] .add-to-cart-btn`).classList.remove('has-quantity');
    
    // Update cart display
    updateCartDisplay();
    updateCartCount();
    saveCart();
    
    // Show success feedback
    showNotification(`Added ${name} to cart!`, 'success');
}

function removeFromCart(menuId) {
    cart = cart.filter(item => item.menu_id !== menuId);
    updateCartDisplay();
    updateCartCount();
    saveCart();
    showNotification('Item removed from cart', 'info');
}

function updateCartItemQuantity(menuId, newQuantity) {
    const itemIndex = cart.findIndex(item => item.menu_id === menuId);
    if (itemIndex > -1) {
        if (newQuantity <= 0) {
            removeFromCart(menuId);
        } else {
            cart[itemIndex].quantity = newQuantity;
            updateCartDisplay();
            updateCartCount();
            saveCart();
        }
    }
}

function updateCartDisplay() {
    const cartItems = document.getElementById('cartItems');
    const emptyCart = document.getElementById('emptyCart');
    const checkoutBtn = document.getElementById('checkoutBtn');
    const cartTotal = document.getElementById('cartTotal');
    
    if (cart.length === 0) {
        cartItems.style.display = 'none';
        emptyCart.style.display = 'block';
        checkoutBtn.disabled = true;
        cartTotal.textContent = '0.00';
        return;
    }
    
    cartItems.style.display = 'block';
    emptyCart.style.display = 'none';
    checkoutBtn.disabled = false;
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <div class="cart-item" data-menu-id="${item.menu_id}">
                <div class="cart-item-image">
                    <img src="../uploads/menu/${item.image_path}" alt="${item.name}">
                </div>
                <div class="cart-item-details">
                    <h4>${item.name}</h4>
                    <p class="cart-item-price">₱${item.price.toFixed(2)}</p>
                    <div class="cart-quantity-controls">
                        <button onclick="updateCartItemQuantity(${item.menu_id}, ${item.quantity - 1})">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateCartItemQuantity(${item.menu_id}, ${item.quantity + 1})">+</button>
                    </div>
                </div>
                <div class="cart-item-actions">
                    <div class="cart-item-subtotal">₱${subtotal.toFixed(2)}</div>
                    <button class="remove-btn" onclick="removeFromCart(${item.menu_id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    cartItems.innerHTML = html;
    cartTotal.textContent = total.toFixed(2);
}

function updateCartCount() {
    const cartCount = document.getElementById('cartCount');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    if (totalItems > 0) {
        cartCount.style.opacity = '1';
        cartCount.style.visibility = 'visible';
    } else {
        cartCount.style.opacity = '0';
        cartCount.style.visibility = 'hidden';
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function clearCart() {
    cart = [];
    itemQuantities = {};
    updateCartDisplay();
    updateCartCount();
    saveCart();
}

function toggleCart() {
    const sidebar = document.getElementById('cartSidebar');
    sidebar.classList.toggle('active');
}

// Checkout Functions
function openCheckoutModal() {
    if (cart.length === 0) {
        showNotification('Your cart is empty!', 'warning');
        return;
    }
    
    const modal = document.getElementById('checkoutModal');
    modal.style.display = 'flex';
    
    // Prevent background scrolling
    document.body.style.overflow = 'hidden';
    
    // Populate checkout items
    updateCheckoutSummary();
}

function closeCheckoutModal() {
    const modal = document.getElementById('checkoutModal');
    modal.style.display = 'none';
    
    // Restore background scrolling
    document.body.style.overflow = 'auto';
}

function showOrderSuccessModal(orderNumber, customerName, total) {
    const modal = document.getElementById('orderSuccessModal');
    const orderNumberEl = document.getElementById('orderNumber');
    const customerNameEl = document.getElementById('customerName');
    const orderTotalEl = document.getElementById('orderTotal');
    
    // Set order details
    orderNumberEl.textContent = orderNumber;
    customerNameEl.textContent = customerName;
    orderTotalEl.textContent = total.toFixed(2);
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    const modal = document.getElementById('orderSuccessModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

function updateCheckoutSummary() {
    const checkoutItems = document.getElementById('checkoutItems');
    const checkoutTotal = document.getElementById('checkoutTotal');
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        
        html += `
            <div class="checkout-item">
                <span class="item-name">${item.name}</span>
                <span class="item-details">₱${item.price.toFixed(2)} × ${item.quantity}</span>
                <span class="item-subtotal">₱${subtotal.toFixed(2)}</span>
            </div>
        `;
    });
    
    checkoutItems.innerHTML = html;
    checkoutTotal.textContent = total.toFixed(2);
}

async function processOrder(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
        const paymentMethod = formData.get('payment_method');
        
        // Simulate payment processing for non-cash payments
        if (paymentMethod !== 'cash') {
            const processBtn = document.getElementById('processPaymentBtn');
            const originalText = processBtn.innerHTML;
            
            processBtn.disabled = true;
            processBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing Payment...';
            
            // Simulate payment processing delay
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            processBtn.innerHTML = originalText;
            processBtn.disabled = false;
            
            // Simulate random payment failure (10% chance for demo)
            if (Math.random() < 0.1) {
                throw new Error('Payment failed. Please try again or use a different payment method.');
            }
        }
        
        // Calculate total amount from cart
        let totalAmount = 0;
        cart.forEach(item => {
            totalAmount += item.price * item.quantity;
        });

        const orderData = {
        customer_name: formData.get('customer_name'),
        customer_phone: formData.get('customer_phone'),
        customer_email: formData.get('customer_email'),
        order_type: formData.get('order_type'),
        delivery_address: formData.get('delivery_address'),
        special_instructions: formData.get('special_instructions'),
        payment_method: paymentMethod,
        cart_items: cart,
        total_amount: totalAmount
    };    try {
        // Show loading
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        const response = await fetch('../controllers/OrderController.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        // Check if response is ok and content-type is JSON
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            console.error('Non-JSON response received:', responseText);
            throw new Error('Server returned non-JSON response. Check console for details.');
        }
        
        const result = await response.json();
        
        if (result.success) {
            // Get customer name from form
            const customerName = formData.get('customer_name');
            const cartTotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            
            clearCart();
            closeCheckoutModal();
            form.reset();
            
            // Show success modal with order details
            showOrderSuccessModal(result.order_number, customerName, cartTotal);
        } else {
            throw new Error(result.message || 'Failed to place order');
        }
        
    } catch (error) {
        showNotification(error.message, 'error');
    } finally {
        // Reset button
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-check"></i> Place Order';
        submitBtn.disabled = false;
    }
}

// Utility Functions
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => notification.classList.add('show'), 100);
    
    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => document.body.removeChild(notification), 300);
    }, 3000);
}

function showOrderSuccessModal(orderNumber, customerName, total) {
    const modal = document.getElementById('orderSuccessModal');
    const orderNumberEl = document.getElementById('orderNumber');
    const customerNameEl = document.getElementById('customerName');
    const orderTotalEl = document.getElementById('orderTotal');
    
    // Set order details
    orderNumberEl.textContent = orderNumber;
    customerNameEl.textContent = customerName;
    orderTotalEl.textContent = total.toFixed(2);
    
    // Show modal
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeSuccessModal() {
    const modal = document.getElementById('orderSuccessModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}
