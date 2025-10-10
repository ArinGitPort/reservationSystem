<?php
/**
 * Reusable Search and Filter Component with Integrated PDF Generator
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
 *             'text' => 'Export PDF',
 *             'icon' => 'fas fa-file-pdf',
 *             'class' => 'btn-outline-danger',
 *             'type' => 'pdf_export',
 *             'data' => $dataArray,
 *             'report_title' => 'Customers Report',
 *             'company_name' => 'Ellen\'s Food House'
 *         ]
 *     ]
 * ]);
 */

/**
 * Integrated PDF Generator for Search Filter Component
 */
class SearchFilterPDFGenerator {
    
    /**
     * Generate PDF report from data array
     */
    public static function generatePDF($data, $config = []) {
        $defaults = [
            'report_title' => 'Report',
            'company_name' => 'Company Report',
            'columns' => [],
            'stats' => null,
            'filename' => 'report_' . date('Y-m-d_H-i-s')
        ];
        
        $config = array_merge($defaults, $config);
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title><?php echo htmlspecialchars($config['report_title']); ?> - <?php echo htmlspecialchars($config['company_name']); ?></title>
            <style>
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 20px; 
                    color: #333; 
                    line-height: 1.4;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 30px; 
                    border-bottom: 2px solid #333; 
                    padding-bottom: 20px; 
                }
                .header h1 {
                    margin: 0 0 10px 0;
                    font-size: 28px;
                    color: #333;
                }
                .header h2 {
                    margin: 0 0 15px 0;
                    font-size: 20px;
                    color: #666;
                }
                .stats { 
                    display: flex; 
                    justify-content: space-around; 
                    margin-bottom: 30px; 
                    flex-wrap: wrap;
                }
                .stat-box { 
                    text-align: center; 
                    padding: 15px; 
                    border: 1px solid #ddd; 
                    border-radius: 8px; 
                    margin: 5px;
                    min-width: 120px;
                }
                .stat-number { 
                    font-size: 24px; 
                    font-weight: bold; 
                    color: #007bff; 
                    margin-bottom: 5px;
                }
                .stat-label { 
                    font-size: 12px; 
                    color: #666; 
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 20px; 
                    font-size: 12px;
                }
                th, td { 
                    padding: 8px 6px; 
                    text-align: left; 
                    border-bottom: 1px solid #ddd; 
                    vertical-align: top;
                }
                th { 
                    background-color: #f8f9fa; 
                    font-weight: bold; 
                    font-size: 11px;
                    text-transform: uppercase;
                    letter-spacing: 0.3px;
                }
                .footer { 
                    margin-top: 30px; 
                    text-align: center; 
                    font-size: 11px; 
                    color: #666; 
                    border-top: 1px solid #ddd;
                    padding-top: 20px;
                }
                .no-data {
                    text-align: center;
                    color: #999;
                    font-style: italic;
                    padding: 30px;
                }
                @media print { 
                    body { margin: 0; }
                    .header { page-break-after: avoid; }
                    table { page-break-inside: avoid; }
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo htmlspecialchars($config['report_title']); ?></h1>
                <h2><?php echo htmlspecialchars($config['company_name']); ?></h2>
                <p>Generated on: <?php echo date('F d, Y H:i:s'); ?></p>
            </div>
            
            <?php if ($config['stats'] && is_array($config['stats'])): ?>
            <div class="stats">
                <?php foreach ($config['stats'] as $statKey => $statValue): ?>
                <div class="stat-box">
                    <div class="stat-number"><?php echo htmlspecialchars($statValue['value'] ?? $statValue); ?></div>
                    <div class="stat-label"><?php echo htmlspecialchars($statValue['label'] ?? ucwords(str_replace('_', ' ', $statKey))); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($data) && is_array($data)): ?>
            <table>
                <thead>
                    <tr>
                        <?php if (!empty($config['columns'])): ?>
                            <?php foreach ($config['columns'] as $column): ?>
                                <th><?php echo htmlspecialchars($column['label'] ?? ucwords(str_replace('_', ' ', $column['key']))); ?></th>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php 
                            // Auto-generate columns from first data row
                            $firstRow = reset($data);
                            if ($firstRow && is_array($firstRow)):
                                foreach (array_keys($firstRow) as $key): ?>
                                    <th><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?></th>
                                <?php endforeach;
                            endif; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data as $index => $row): ?>
                    <tr>
                        <?php if (!empty($config['columns'])): ?>
                            <?php foreach ($config['columns'] as $column): ?>
                                <td>
                                    <?php 
                                    $value = $row[$column['key']] ?? '';
                                    if (isset($column['format'])) {
                                        switch ($column['format']) {
                                            case 'date':
                                                echo $value ? date('M d, Y', strtotime($value)) : 'N/A';
                                                break;
                                            case 'datetime':
                                                echo $value ? date('M d, Y H:i', strtotime($value)) : 'N/A';
                                                break;
                                            case 'currency':
                                                echo $value ? '₱' . number_format($value, 2) : '₱0.00';
                                                break;
                                            case 'number':
                                                echo is_numeric($value) ? number_format($value) : $value;
                                                break;
                                            default:
                                                echo htmlspecialchars($value ?: 'N/A');
                                        }
                                    } else {
                                        echo htmlspecialchars($value ?: 'N/A');
                                    }
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php foreach ($row as $value): ?>
                                <td><?php echo htmlspecialchars($value ?: 'N/A'); ?></td>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="no-data">
                <p>No data available for this report.</p>
            </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>This report contains <?php echo count($data); ?> record(s).</p>
                <p><?php echo htmlspecialchars($config['company_name']); ?> - Management System</p>
            </div>
        </body>
        </html>
        <?php
        
        $html = ob_get_clean();
        
        // Set headers for HTML preview (which can be printed to PDF)
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="' . $config['filename'] . '.html"');
        
        echo $html;
        
        // Add JavaScript to automatically open print dialog
        echo '<script>
            window.onload = function() { 
                // Auto-print after a short delay to ensure page is fully loaded
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>';
        
        exit; // Important: Stop execution after PDF generation
    }
}

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
                        <?php if (isset($button['type']) && $button['type'] === 'pdf_export'): ?>
                            <!-- PDF Export Button with integrated functionality -->
                            <form method="POST" style="display: inline;" class="flex-fill">
                                <input type="hidden" name="pdf_export_action" value="generate">
                                <input type="hidden" name="pdf_config" value="<?php echo htmlspecialchars(json_encode($button)); ?>">
                                <button type="submit" 
                                        class="btn <?php echo $button['class'] ?? 'btn-outline-danger'; ?> btn-sm w-100">
                                    <?php if (isset($button['icon'])): ?>
                                        <i class="<?php echo $button['icon']; ?> me-1"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($button['text']); ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Regular Button -->
                            <button type="button" 
                                    class="btn <?php echo $button['class'] ?? 'btn-outline-primary'; ?> btn-sm flex-fill" 
                                    <?php echo isset($button['onclick']) ? 'onclick="' . $button['onclick'] . '"' : ''; ?>
                                    <?php echo isset($button['id']) ? 'id="' . $button['id'] . '"' : ''; ?>>
                                <?php if (isset($button['icon'])): ?>
                                    <i class="<?php echo $button['icon']; ?> me-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($button['text']); ?>
                            </button>
                        <?php endif; ?>
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

// Handle PDF export requests automatically
if (isset($_POST['pdf_export_action']) && $_POST['pdf_export_action'] === 'generate') {
    $pdfConfig = json_decode($_POST['pdf_config'], true);
    
    if ($pdfConfig && isset($pdfConfig['data'])) {
        $config = [
            'report_title' => $pdfConfig['report_title'] ?? 'Report',
            'company_name' => $pdfConfig['company_name'] ?? 'Company Report', 
            'columns' => $pdfConfig['columns'] ?? [],
            'stats' => $pdfConfig['stats'] ?? null,
            'filename' => $pdfConfig['filename'] ?? 'report_' . date('Y-m-d_H-i-s')
        ];
        
        SearchFilterPDFGenerator::generatePDF($pdfConfig['data'], $config);
    }
}
?>