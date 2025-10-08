<?php
/**
 * Reusable Search and Filter Component
 * 
 * Usage:
 * include '../../includes/search_filter.php';
 * renderSearchFilter([
 *     'placeholder' => 'Search by customer, phone, email...',
 *     'filters' => [
 *         'status' => [
 *             'label' => 'Filter by Status',
 *             'options' => [
 *                 '' => 'All Statuses',
 *                 'pending' => 'Pending',
 *                 'confirmed' => 'Confirmed'
 *             ]
 *         ]
 *     ],
 *     'additional_buttons' => [
 *         [
 *             'text' => 'Export',
 *             'icon' => 'fas fa-download',
 *             'class' => 'btn-outline-success',
 *             'onclick' => 'exportData()'
 *         ]
 *     ]
 * ]);
 */

function renderSearchFilter($config = []) {
    // Default configuration
    $defaults = [
        'placeholder' => 'Search...',
        'search_label' => 'Search',
        'filters' => [],
        'additional_buttons' => [],
        'form_id' => 'filter-form',
        'search_input_id' => 'search-input',
        'clear_function' => 'clearFilters()',
        'refresh_function' => 'refreshData()'
    ];
    
    $config = array_merge($defaults, $config);
    
    // Better responsive layout calculation
    $filterCount = count($config['filters']);
    $additionalButtonsCount = count($config['additional_buttons']);
    
    // Dynamic column sizing for better UX
    if ($filterCount === 0) {
        // Search + buttons only
        $searchCol = 'col-md-6';
        $buttonGroupCol = 'col-md-6';
    } elseif ($filterCount === 1) {
        // Search + 1 filter + buttons
        $searchCol = 'col-md-5';
        $filterCol = 'col-md-3';
        $buttonGroupCol = 'col-md-4';
    } else {
        // Search + multiple filters + buttons
        $searchCol = 'col-md-4';
        $filterCol = 'col-md-2';
        $buttonGroupCol = 'col-md-4';
    }
    ?>
    
    <!-- Filters Section -->
    <div class="filters-section">
        <form id="<?php echo $config['form_id']; ?>" class="row g-2 align-items-end">
            <!-- Search Input -->
            <div class="<?php echo $searchCol; ?>">
                <label class="form-label mb-1"><?php echo htmlspecialchars($config['search_label']); ?></label>
                <input type="text" 
                       class="form-control form-control-sm" 
                       id="<?php echo $config['search_input_id']; ?>" 
                       placeholder="<?php echo htmlspecialchars($config['placeholder']); ?>">
            </div>
            
            <!-- Dynamic Filters -->
            <?php foreach ($config['filters'] as $filterId => $filter): ?>
                <div class="<?php echo $filterCol; ?>">
                    <label class="form-label mb-1"><?php echo htmlspecialchars($filter['label']); ?></label>
                    <?php if ($filter['type'] === 'select' || !isset($filter['type'])): ?>
                        <select class="form-select form-select-sm" id="<?php echo $filterId; ?>-filter" name="<?php echo $filterId; ?>">
                            <?php foreach ($filter['options'] as $value => $label): ?>
                                <option value="<?php echo htmlspecialchars($value); ?>"><?php echo htmlspecialchars($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($filter['type'] === 'date'): ?>
                        <input type="date" 
                               class="form-control form-control-sm" 
                               id="<?php echo $filterId; ?>-filter" 
                               name="<?php echo $filterId; ?>">
                    <?php elseif ($filter['type'] === 'daterange'): ?>
                        <div class="input-group input-group-sm">
                            <input type="date" 
                                   class="form-control" 
                                   id="<?php echo $filterId; ?>-from" 
                                   name="<?php echo $filterId; ?>_from" 
                                   placeholder="From">
                            <input type="date" 
                                   class="form-control" 
                                   id="<?php echo $filterId; ?>-to" 
                                   name="<?php echo $filterId; ?>_to" 
                                   placeholder="To">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <!-- Action Buttons Group -->
            <div class="<?php echo $buttonGroupCol; ?>">
                <label class="form-label mb-1 d-block">&nbsp;</label>
                <div class="btn-group-custom d-flex gap-1 flex-wrap">
                    <!-- Filter Button -->
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    
                    <!-- Clear Button -->
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" onclick="<?php echo $config['clear_function']; ?>">
                        <i class="fas fa-times me-1"></i>Clear
                    </button>
                    
                    <!-- Refresh Button -->
                    <button type="button" class="btn btn-outline-info btn-sm flex-fill" onclick="<?php echo $config['refresh_function']; ?>">
                        <i class="fas fa-refresh me-1"></i>Refresh
                    </button>
                    
                    <!-- Additional Buttons -->
                    <?php foreach ($config['additional_buttons'] as $button): ?>
                        <button type="button" 
                                class="btn <?php echo $button['class'] ?? 'btn-outline-primary'; ?> btn-sm flex-fill" 
                                <?php echo isset($button['onclick']) ? 'onclick="' . $button['onclick'] . '"' : ''; ?>
                                <?php echo isset($button['id']) ? 'id="' . $button['id'] . '"' : ''; ?>>
                            <?php if (isset($button['icon'])): ?>
                                <i class="<?php echo $button['icon']; ?> me-1"></i>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($button['text']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Add default CSS if not already included -->
    <style>
        .filters-section {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .filters-section .form-label {
            font-weight: 600;
            color: #495057;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .filters-section .form-control,
        .filters-section .form-select {
            border: 1px solid #ced4da;
            transition: all 0.15s ease-in-out;
            border-radius: 6px;
        }
        
        .filters-section .form-control:focus,
        .filters-section .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }
        
        .filters-section .btn {
            font-weight: 500;
            transition: all 0.15s ease-in-out;
            border-radius: 6px;
            white-space: nowrap;
            min-height: 32px;
        }
        
        .btn-group-custom {
            min-height: 32px;
        }
        
        .btn-group-custom .btn {
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
        }
        
        .btn-group-custom .btn i {
            font-size: 0.8rem;
        }
        
        @media (max-width: 992px) {
            .filters-section .row > div {
                margin-bottom: 0.75rem;
            }
            
            .btn-group-custom {
                flex-direction: column;
                gap: 0.5rem !important;
            }
            
            .btn-group-custom .btn {
                flex: 1 1 auto;
                min-width: 100px;
            }
        }
        
        @media (max-width: 768px) {
            .filters-section {
                padding: 1rem;
            }
            
            .filters-section .row {
                --bs-gutter-x: 0.5rem;
            }
            
            .btn-group-custom .btn {
                font-size: 0.8rem;
                padding: 0.25rem 0.5rem;
                min-width: 80px;
            }
            
            .btn-group-custom .btn i {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 576px) {
            .filters-section .row > div {
                flex: 0 0 100%;
                max-width: 100%;
            }
            
            .btn-group-custom {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }
        }
    </style>
    
    <?php
}
?>