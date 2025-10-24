/**
 * Lazy Circulation Loading Module
 * - Handles lazy loading of checkout and holds data.
 */
AspenDiscovery.LazyCirculation = {
	/**
	 * Initialize lazy circulation loading.
	 * Called on page load to detect and refresh stale circulation buttons.
	 */
	init() {
		const $staleButtons = $('[data-needs-refresh="true"]');
		if ($staleButtons.length > 0) {
			this.refreshStaleButtons($staleButtons);
		}
	},

	/**
	 * Refresh circulation buttons with stale cache data
	 *
	 * @param $buttons
	 */
	refreshStaleButtons($buttons) {
		const recordData = [];
		const buttonMap = new Map();

		$buttons.each((index, button) => {
			const $button = $(button);
			const recordId = $button.attr('data-record-id');
			const source = $button.attr('data-record-source');

			if (recordId && source) {
				const key = `${source}:${recordId}`;
				if (!buttonMap.has(key)) {
					recordData.push({
						recordId: recordId,
						source: source
					});
					buttonMap.set(key, []);
				}
				buttonMap.get(key).push(button);
			}
		});

		if (recordData.length === 0) {
			return;
		}

		$.getJSON(`${Globals.path}/MyAccount/AJAX?method=refreshUserCirculationCache`, (response) => {
			if (response.success) {
				this.getUpdatedButtons(recordData, buttonMap);
			} else {
				console.error('Failed to refresh circulation cache: ', response.message || 'Unknown error');
			}
		});
	},

	/**
	 * Get updated button data and refresh the UI.
	 *
	 * @param {Array} recordData Array of record objects.
	 * @param {Map} buttonMap Map of record keys to button elements.
	 */
	getUpdatedButtons(recordData, buttonMap) {
		const url = `${Globals.path}/MyAccount/AJAX?method=getUpdatedCirculationButtons`;
		const data = {
			recordData: recordData || []
		};

		// Use $.post instead of $.getJSON because recordData is sent in the request body.
		$.post(url, data, (response) => {
			if (response.success && response.buttons) {
				this.updateButtonsInDOM(response.buttons, buttonMap);
			} else {
				console.error('Failed to get updated buttons:', response.message || 'Unknown error');
			}
		}, 'json');
	},

	/**
	 * Update button elements in the DOM with fresh circulation data.
	 *
	 * @param {Array} buttonData Array of button data.
	 * @param {Map} buttonMap Map of record keys to button elements.
	 */
	updateButtonsInDOM(buttonData, buttonMap) {
		buttonData = buttonData || [];
		buttonData.forEach(record => {
			const { source = '', recordId = '', actions = [], clearAllButtons = false } = record || {};
			const key = `${source}:${recordId}`;
			const buttons = buttonMap.get(key);

			if (buttons && buttons.length > 0) {
				buttons.forEach(button => {
					this.updateSingleButton(button, actions, clearAllButtons);
				});
			}
		});
	},

	/**
	 * Update a single button element with new action data.
	 *
	 * @param {Element} button The button element to update.
	 * @param {Array} actions New action data.
	 * @param {boolean} clearAllButtons Whether to clear all buttons.
	 */
	updateSingleButton(button, actions, clearAllButtons) {
		const $button = $(button);

		$button.addClass('shimmer-fade-out');
		// Remove shimmer attributes and class after transition completes.
		setTimeout(() => {
			$button.removeAttr('data-needs-refresh');
			$button.removeAttr('data-record-id');
			$button.removeAttr('data-record-source');
			$button.removeClass('shimmer-fade-out');
		}, 500); // Match the CSS transition duration.

		actions = actions || [];

		// If there are circulation actions (checked out or on hold),
		// replace the button with the circulation action.
		if (actions && actions.length > 0) {
			const $buttonContainer = $button.closest('.btn-group, .btn-toolbar, .manifestation-actions');
			if ($buttonContainer.length > 0) {
				// Clear all existing buttons in the container to avoid duplicates.
				if (clearAllButtons) {
					$buttonContainer.empty();
					actions.forEach(action => {
						const $newButton = this.createButtonElement(action);
						if ($newButton) {
							$buttonContainer.append($newButton);
						}
					});
				} else {
					// Insert new actions at the position of the old button.
					actions.forEach(action => {
						const $newButton = this.createButtonElement(action);
						if ($newButton) {
							$button.before($newButton);
						}
					});
					$button.remove();
				}
			}
		} else {
			// If no actions, just remove the stale button.
			$button.remove();
		}
	},

	/**
	 * Create a button element from action data.
	 *
	 * @param {Object} action Action data object.
	 * @return {Element|null} Created button element or null if creation failed.
	 */
	createButtonElement(action) {
		action = action || {};
		const {
			title = '', url = '#', btnType = 'btn-action', id = null,
			onclick = null, target = null, requireLogin = false
		} = action;
		if (!title) return null;
		const $button = $('<a>').attr('href', url).addClass(`btn btn-sm ${btnType} btn-wrap`.trim()).text(title);

		if (id) {
			$button.attr('id', id);
		}

		if (onclick) {
			$button.attr('onclick', onclick);
		}

		if (target) {
			$button.attr('target', target);
		}

		if (requireLogin) {
			$button.attr('data-require-login', 'true');
		}

		return $button;
	},

	/**
	 * Scan a specific container for stale circulation buttons and refresh them.
	 * Used for dynamically loaded content like modals, AJAX responses, etc.
	 *
	 * @param {Element|string} container Container element or selector to scan.
	 */
	scanAndRefresh(container) {
		const $container = typeof container === 'string' ? $(container) : $(container);
		if ($container.length === 0) {
			return;
		}
		const $staleButtons = $container.find('[data-needs-refresh="true"]');

		if ($staleButtons.length > 0) {
			this.refreshStaleButtons($staleButtons);
		}
	}
};

$(() => {
	AspenDiscovery.LazyCirculation.init();

	$(document).on('shown.bs.modal', '.modal', (event) => {
		const modalElement = event.target;
		let mutationTimeout = null;

		const observer = new MutationObserver((mutations, observer) => {
			if (mutationTimeout) {
				clearTimeout(mutationTimeout);
			}

			mutationTimeout = setTimeout(() => {
				const refreshButtons = modalElement.querySelectorAll('[data-needs-refresh]');
				if (refreshButtons.length > 0) {
					AspenDiscovery.LazyCirculation.scanAndRefresh(modalElement);
				}

				observer.disconnect();
			}, 300);
		});

		observer.observe(modalElement, {
			childList: true,
			subtree: true
		});
	});
});