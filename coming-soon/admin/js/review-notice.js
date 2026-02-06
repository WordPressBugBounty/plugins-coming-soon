/**
 * SeedProd Review Notice Handler
 *
 * Handles the 3-step review request flow for WordPress admin notices.
 * Loaded only when review notice is displayed.
 *
 * @package    SeedProd
 * @since      7.0.0
 */

(function($) {
	'use strict';

	/**
	 * Review Notice Handlers (V2 Admin)
	 * Handles the 3-step review request flow
	 */

	// Handle WordPress native dismiss button (the X button)
	$(document).on('click', '.seedprod-v2-review-notice .notice-dismiss', function(e) {
		e.preventDefault();

		// Temporary dismissal when using the X button
		$.post(ajaxurl, {
			action: 'seedprod_v2_review_dismiss',
			permanent: 'false',
			nonce: seedprodReviewNotice.nonce
		});

		$('.seedprod-v2-review-notice').fadeOut();
	});

	// Handle custom dismiss buttons
	$(document).on('click', '.seedprod-v2-review-notice .seedprod-dismiss-review-notice', function(event) {
		if (!$(this).hasClass('seedprod-review-out')) {
			event.preventDefault();
		}

		// Check if this is a permanent dismissal (already reviewed)
		var isPermanent = $(this).hasClass('seedprod-dismiss-review-notice-permanent');

		$.post(ajaxurl, {
			action: 'seedprod_v2_review_dismiss',
			permanent: isPermanent ? 'true' : 'false',
			nonce: seedprodReviewNotice.nonce
		});

		$('.seedprod-v2-review-notice').fadeOut();
	});

	// Handle step switching in review notice
	$(document).on('click', '.seedprod-v2-review-notice .seedprod-review-switch-step', function(e) {
		e.preventDefault();
		var targetStep = $(this).attr('data-step');

		if (targetStep) {
			var $notice = $(this).closest('.seedprod-v2-review-notice');
			var $targetStepDiv = $notice.find('.seedprod-review-step-' + targetStep);

			if ($targetStepDiv.length > 0) {
				$notice.find('.seedprod-review-step:visible').fadeOut(function() {
					$targetStepDiv.fadeIn();
				});
			}
		}
	});

	// Handle "Ask me later" link
	$(document).on('click', '.seedprod-v2-review-notice .seedprod-review-dismiss-link', function(e) {
		e.preventDefault();

		$.post(ajaxurl, {
			action: 'seedprod_v2_review_dismiss',
			permanent: 'false',
			nonce: seedprodReviewNotice.nonce
		});

		$('.seedprod-v2-review-notice').fadeOut();
	});

})(jQuery);
