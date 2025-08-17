document.addEventListener('DOMContentLoaded', function() {
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
