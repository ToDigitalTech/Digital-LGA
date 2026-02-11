/**
 * Digital LGA - Admin JavaScript
 *
 * @package DigitalLGA
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // Auto-calculate business split
        $('#dlga_worker_split').on('input', function () {
            var workerSplit = parseInt($(this).val()) || 0;
            workerSplit = Math.min(100, Math.max(0, workerSplit));
            $('#dlga_business_split').val(100 - workerSplit);
        });

        // Pool allocation total validation
        function updatePoolTotal() {
            var personnel = parseInt($('input[name="dlga_personnel_pool"]').val()) || 0;
            var infrastructure = parseInt($('input[name="dlga_infrastructure_pool"]').val()) || 0;
            var emergency = parseInt($('input[name="dlga_emergency_pool"]').val()) || 0;
            var total = personnel + infrastructure + emergency;

            var $totalSpan = $('#dlga-pool-total');
            $totalSpan.text(total);

            if (total === 100) {
                $totalSpan.removeClass('invalid').addClass('valid');
            } else {
                $totalSpan.removeClass('valid').addClass('invalid');
            }
        }

        $('.dlga-pool-input').on('input', updatePoolTotal);
        updatePoolTotal();

        // Confirm destructive actions
        $('button[name="review_action"][value="reject"]').on('click', function (e) {
            var $form = $(this).closest('form');
            var reason = $form.find('textarea[name="reject_reason"]').val();
            if (!reason.trim()) {
                e.preventDefault();
                alert('Please provide a reason for rejection.');
                return false;
            }
        });

        $('form:has([name="dlga_blacklist_nonce"])').on('submit', function (e) {
            if (!confirm('Are you sure you want to blacklist this company? This action will be publicly visible.')) {
                e.preventDefault();
                return false;
            }
        });

        $('button[name="milestone_action"][value="approve"]').on('click', function (e) {
            if (!confirm('Approve this milestone and release payment?')) {
                e.preventDefault();
                return false;
            }
        });

    });

})(jQuery);
