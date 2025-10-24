/**
 * Form Fields Module
 * - Character counter for maxlength attributes.
 * - Others could be added later....
**/

AspenDiscovery.FormFields = (function() {
	/**
	 * Initialize character counters on elements with maxLength in the specified container.
	 *
	 * @param {jQuery|HTMLElement|string} container
	 */
	function initializeCharacterCounters(container = 'body') {
		const $container = $(container);
		if (!$container.length) return;

		// Canvas for text-width measuring (reuse for all fields).
		const ccCanvas = document.createElement('canvas');
		const ccCtx = ccCanvas.getContext('2d');
		const buffer = 8;
		const maxlenFieldsSelector = 'textarea[maxlength], input[maxlength][type=text], input[maxlength][type=search], ' +
											'input[maxlength][type=tel], input[maxlength][type=url], input[maxlength][type=email], ' +
											'input[maxlength][type=email2], input[maxlength][type=password], input[maxlength][type=storedPassword],' +
											'input[maxlength]:not([type])';

		if ($.validator) {
			$.validator.setDefaults({
				errorPlacement: function(error, element) {
					if (element.is(maxlenFieldsSelector)) {
						// Place jQuery's validation error after the wrapper, so it doesn't grow the wrapper height.
						element.closest('.field-wrapper').after(error);
					} else {
						error.insertAfter(element);
					}
				}
			});
		}

		// Helper to wrap and inject the counter.
		function initCharCounterField($f) {
			if (!$f.parent().hasClass('field-wrapper')) {
				$f.wrap('<div class="field-wrapper"></div>');
			}
			if (!$f.next('.char-counter').length) {
				$f.after('<span class="char-counter"></span>');
			}
		}

		// Initialize on page-load for all existing fields.
		$container.find(maxlenFieldsSelector).each(function() {
			initCharCounterField($(this));
		});

		// Observe DOM for any new fields added under container.
		// Needed otherwise the dynamic injection of the counter will
		// cause the user to lose focus on the field.
		const observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (mutation) {
				Array.prototype.forEach.call(mutation.addedNodes, function (node) {
					let $n = $(node);
					if ($n.is(maxlenFieldsSelector)) {
						initCharCounterField($n);
					}
					$n.find(maxlenFieldsSelector).each(function () {
						initCharCounterField($(this));
					});
				});
			});
		});
		observer.observe($container[0], { childList: true, subtree: true });

		// Handle input events on fields with maxlength.
		$container.on('input', maxlenFieldsSelector, function() {
			const $f = $(this);
			const fld = $f[0];
			const $ctr = $f.closest('.field-wrapper').find('.char-counter');
			const max = parseInt($f.attr('maxlength'), 10);
			if (isNaN(max) || max <= 0) return;
			const val = $f.val();

			$ctr.text(val.length + '/' + max).addClass('visible');
			$f.toggleClass('field-error', val.length >= max);

			// Measure rendered text width.
			const style = window.getComputedStyle(fld);
			ccCtx.font = style.font;
			const rawW = ccCtx.measureText(val).width;
			const ls = style.letterSpacing === 'normal' ? 0 : parseFloat(style.letterSpacing);
			const textW = rawW + ls * Math.max(0, val.length - 1);

			// Compute truly available width inside the field.
			const paddingLeft = parseFloat(style.paddingLeft);
			const paddingRight = parseFloat(style.paddingRight);
			const avail = fld.clientWidth - paddingLeft - paddingRight - $ctr[0].offsetWidth - buffer;

			// Switch between "inside" vs "outside" modes.
			const $wrap = $f.parent();
			if (textW < avail) {
				$wrap.removeClass('outside');
				$ctr.removeClass('outside').addClass('inside');
			} else {
				$wrap.addClass('outside');
				$ctr.removeClass('inside').addClass('outside');
			}

			// Setup timer to hide counter after delay.
			clearTimeout($f.data('ccTimer'));
			const tid = setTimeout(function () {
				if (val.length < max) {
					$ctr.removeClass('visible');
				}
			}, 2000);
			$f.data('ccTimer', tid);
		});

		// On focus: if already at max, show the counter; otherwise, hide it immediately.
		$container.on('focus', maxlenFieldsSelector, function() {
			const $f = $(this);
			const max = parseInt($f.attr('maxlength'), 10);
			const len = $f.val().length;
			const $ctr = $f.closest('.field-wrapper').find('.char-counter');

			if (len >= max) {
				$ctr.text(len + '/' + max).addClass('visible');
			} else {
				$ctr.removeClass('visible');
			}
		});

		// On blur: always hide the counter after a short interval.
		$container.on('blur', maxlenFieldsSelector, function() {
			const $f = $(this);
			const $ctr = $f.closest('.field-wrapper').find('.char-counter');
			clearTimeout($f.data('ccTimer'));
			const tid = setTimeout(function() {
				$ctr.removeClass('visible');
			}, 2000);
			$f.data('ccTimer', tid);
		});
	}

	return {
		initializeCharacterCounters: initializeCharacterCounters
	};
}());