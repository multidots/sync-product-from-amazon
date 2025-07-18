(function ($) {
	'use strict';
	document.addEventListener('DOMContentLoaded', function () {
		// Select all "Show more" links
		const toggleLinks = document.querySelectorAll(
			'.sync-product-from-amazon__feature_item_show_more'
		);

		toggleLinks.forEach((toggleLink) => {
			// Find the corresponding feature items within the same container
			const featureItems = toggleLink
				.closest('.sync-product-from-amazon__features')
				.querySelectorAll('.sync-product-from-amazon__feature_item');

			// Add click event listener to each "Show more" link
			toggleLink.addEventListener('click', function (event) {
				event.preventDefault();

				// Toggle the visibility of feature items beyond the second one
				featureItems.forEach((item, index) => {
					if (index >= 3) {
						if (
							item.style.display === 'none' ||
							item.style.display === ''
						) {
							item.style.display = 'list-item';
						} else {
							item.style.display = 'none';
						}
					}
				});

				// Toggle the text content of the link
				if (toggleLink.textContent === 'Show more') {
					toggleLink.textContent = 'Show less';
				} else {
					toggleLink.textContent = 'Show more';
				}
			});
		});
	});
})(jQuery);
