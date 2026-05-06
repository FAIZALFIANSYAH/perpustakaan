// Custom JavaScript for Perpustakaan Application

$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Initialize popovers
    $('[data-toggle="popover"]').popover();
    
    // Initialize select2 if available
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2();
    }
    
    // Initialize datepickers if available
    if (typeof $.fn.datepicker !== 'undefined') {
        $('.datepicker').datepicker({
            format: 'yyyy-mm-dd',
            autoclose: true
        });
    }
    
    // Initialize data tables if available
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 10
        });
    }
});

// Custom functions
function showLoading() {
    $('.preloader').show();
}

function hideLoading() {
    $('.preloader').fadeOut();
}

// AJAX setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Global AJAX error handler
$(document).ajaxError(function(event, xhr, settings, error) {
    console.error('AJAX Error:', error);
    
    if (xhr.status === 419) {
        alert('Session expired. Please refresh the page.');
        location.reload();
    } else if (xhr.status === 500) {
        alert('Server error. Please try again.');
    } else if (xhr.status === 404) {
        alert('Page not found.');
    }
});

// Form validation
function validateForm(formId) {
    var form = document.getElementById(formId);
    var isValid = true;
    
    $(form).find('input[required], select[required], textarea[required]').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    return isValid;
}

// SweetAlert helper (if available)
function showAlert(title, message, type) {
    if (typeof swal !== 'undefined') {
        swal(title, message, type);
    } else {
        alert(title + ': ' + message);
    }
}