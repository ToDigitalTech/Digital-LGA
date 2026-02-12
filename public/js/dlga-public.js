/**
 * Digital LGA - Public JavaScript
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    var DLGA = {
        init: function () {
            this.initSelectAll();
            this.initPayrollCalculator();
            this.initFormValidation();
            this.initFileUploadPreview();
            this.initOnlineCheck();
        },

        /**
         * Select all checkbox for payroll table.
         */
        initSelectAll: function () {
            $('#dlga-select-all').on('change', function () {
                var checked = $(this).prop('checked');
                $('input[name="selected_workers[]"]').prop('checked', checked);
            });
        },

        /**
         * Live payroll calculation preview.
         */
        initPayrollCalculator: function () {
            var $form = $('#dlga-payroll-form');
            if (!$form.length) return;

            $form.on('change', 'input[name="selected_workers[]"]', function () {
                DLGA.updatePayrollSummary();
            });

            DLGA.updatePayrollSummary();
        },

        /**
         * Update payroll summary totals.
         */
        updatePayrollSummary: function () {
            var $summary = $('.dlga-payroll-summary');
            if (!$summary.length) return;

            var selected = $('input[name="selected_workers[]"]:checked').length;
            var total = $('input[name="selected_workers[]"]').length;

            $summary.find('.dlga-selected-count').text(selected + ' / ' + total);
        },

        /**
         * Client-side form validation.
         */
        initFormValidation: function () {
            $('.dlga-form').on('submit', function (e) {
                var $form = $(this);
                var isValid = true;

                // Remove previous errors
                $form.find('.dlga-field-error').remove();
                $form.find('.dlga-input-error').removeClass('dlga-input-error');

                // Check required fields
                $form.find('[required]').each(function () {
                    var $field = $(this);
                    if (!$field.val() || ($field.is('select') && !$field.val())) {
                        isValid = false;
                        $field.addClass('dlga-input-error');
                        $field.after('<span class="dlga-field-error">This field is required</span>');
                    }
                });

                // Check email fields
                $form.find('input[type="email"]').each(function () {
                    var $field = $(this);
                    var email = $field.val();
                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        isValid = false;
                        $field.addClass('dlga-input-error');
                        $field.after('<span class="dlga-field-error">Please enter a valid email</span>');
                    }
                });

                // Check account number (10 digits)
                $form.find('#account_number').each(function () {
                    var $field = $(this);
                    var val = $field.val();
                    if (val && !/^\d{10}$/.test(val)) {
                        isValid = false;
                        $field.addClass('dlga-input-error');
                        $field.after('<span class="dlga-field-error">Account number must be 10 digits</span>');
                    }
                });

                // Check password length
                $form.find('input[type="password"]').each(function () {
                    var $field = $(this);
                    if ($field.val() && $field.val().length < 8) {
                        isValid = false;
                        $field.addClass('dlga-input-error');
                        $field.after('<span class="dlga-field-error">Password must be at least 8 characters</span>');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    // Scroll to first error
                    var $firstError = $form.find('.dlga-input-error').first();
                    if ($firstError.length) {
                        $('html, body').animate({
                            scrollTop: $firstError.offset().top - 100
                        }, 300);
                    }
                }
            });
        },

        /**
         * Preview file uploads.
         */
        initFileUploadPreview: function () {
            $('input[type="file"]').on('change', function () {
                var $input = $(this);
                var $preview = $input.siblings('.dlga-file-preview');

                if (!$preview.length) {
                    $preview = $('<div class="dlga-file-preview"></div>');
                    $input.after($preview);
                }

                $preview.empty();

                var files = this.files;
                if (!files || !files.length) return;

                for (var i = 0; i < files.length && i < 5; i++) {
                    var file = files[i];

                    // File size check (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        $preview.append('<span class="dlga-field-error">File "' + file.name + '" is too large (max 5MB)</span>');
                        continue;
                    }

                    if (file.type.startsWith('image/')) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            $preview.append(
                                '<img src="' + e.target.result + '" style="max-width:100px;max-height:80px;margin:4px;border-radius:4px;">'
                            );
                        };
                        reader.readAsDataURL(file);
                    } else {
                        $preview.append('<span style="display:block;font-size:0.85em;color:#6B7280;">' + file.name + '</span>');
                    }
                }
            });
        },

        /**
         * Check online status before form submission.
         */
        initOnlineCheck: function () {
            $('form').on('submit', function (e) {
                if (!navigator.onLine) {
                    e.preventDefault();
                    alert('No internet connection. Please check your connection and try again.');
                    return false;
                }
            });
        }
    };

    $(document).ready(function () {
        DLGA.init();
    });

})(jQuery);
