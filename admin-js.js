/**
 * File: public/assets/js/admin.js
 * Admin JavaScript for the PASHA Benefits Portal
 */

document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('toggled');
            
            // Store the sidebar state in localStorage
            const isSidebarToggled = document.getElementById('wrapper').classList.contains('toggled');
            localStorage.setItem('sidebarToggled', isSidebarToggled);
        });
        
        // Check for stored sidebar state
        const storedSidebarState = localStorage.getItem('sidebarToggled');
        if (storedSidebarState === 'true') {
            document.getElementById('wrapper').classList.add('toggled');
        }
    }
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Handle status toggle switches
    const statusSwitches = document.querySelectorAll('.status-switch');
    statusSwitches.forEach(function(statusSwitch) {
        statusSwitch.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Image preview on file input change
    const imageInputs = document.querySelectorAll('.image-input');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const preview = document.getElementById(this.dataset.preview);
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Delete confirmation
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Date range picker initialization
    const dateRangePickers = document.querySelectorAll('.date-range-picker');
    dateRangePickers.forEach(function(picker) {
        const startDate = picker.querySelector('.date-start');
        const endDate = picker.querySelector('.date-end');
        
        if (startDate && endDate) {
            // Set min date for end date based on start date
            startDate.addEventListener('change', function() {
                endDate.min = this.value;
                if (endDate.value && endDate.value < this.value) {
                    endDate.value = this.value;
                }
            });
            
            // Set max date for start date based on end date
            endDate.addEventListener('change', function() {
                startDate.max = this.value;
                if (startDate.value && startDate.value > this.value) {
                    startDate.value = this.value;
                }
            });
        }
    });
    
    // Dynamic form fields
    const addFieldButtons = document.querySelectorAll('.add-field-button');
    addFieldButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const container = document.getElementById(this.dataset.container);
            const template = document.getElementById(this.dataset.template);
            
            if (container && template) {
                const clone = template.content.cloneNode(true);
                container.appendChild(clone);
                
                // Initialize newly added elements
                const newRow = container.lastElementChild;
                const removeButton = newRow.querySelector('.remove-field-button');
                
                if (removeButton) {
                    removeButton.addEventListener('click', function() {
                        this.closest('.field-row').remove();
                    });
                }
            }
        });
    });
    
    // Initialize existing remove field buttons
    const removeFieldButtons = document.querySelectorAll('.remove-field-button');
    removeFieldButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            this.closest('.field-row').remove();
        });
    });
    
    // Data table search
    const tableSearchInputs = document.querySelectorAll('.table-search-input');
    tableSearchInputs.forEach(function(input) {
        input.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const tableId = this.dataset.tableId;
            const table = document.getElementById(tableId);
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(function(row) {
                    let match = false;
                    const cells = row.querySelectorAll('td');
                    
                    cells.forEach(function(cell) {
                        if (cell.textContent.toLowerCase().indexOf(searchText) > -1) {
                            match = true;
                        }
                    });
                    
                    row.style.display = match ? '' : 'none';
                });
            }
        });
    });
    
    // Partner role field toggle
    const roleSelects = document.querySelectorAll('.role-select');
    roleSelects.forEach(function(select) {
        const partnerField = document.getElementById(select.dataset.partnerField);
        
        if (partnerField) {
            // Initial state
            partnerField.style.display = select.value === 'partner' ? 'block' : 'none';
            
            // On change
            select.addEventListener('change', function() {
                partnerField.style.display = this.value === 'partner' ? 'block' : 'none';
            });
        }
    });
});
