/*
 * Reusable inline date-range picker on the Cally web components. The shell is
 * authored in Record/date-range-picker.tpl; this module imports Cally and drives
 * the per-selection state. Cally was chosen over flatpickr for accessibility:
 * an ARIA grid with roving tabindex, fully keyboard operable, screen-reader friendly
 *
 * An ES module, so it is deliberately absent from javascript_files.txt / aspen.js:
 * callers import it on demand from the URL the template puts on the picker's
 * container (data-picker-module, versioned for cache busting).
 *
 * Usage:
 *   const {DateRangePicker} = await import(root.dataset.pickerModule);
 *   const picker = await DateRangePicker.create(rangeEl, {minDate});
 *   picker.update(availability);   // per selection
 * create() wires the fields, then loads Cally; update() mutates the selectable
 * window in place — no teardown/re-render.
 *
 * The From/To fields are native <input type="date">: the browser renders and
 * parses them in the user's own locale while the submitted value stays ISO
 * (Y-m-d), so the pair stays usable — typed by hand, min/max enforced — even
 * when the calendar never loads. That is why the constructor wires them before
 * create() awaits Cally.
 *
 * create(config):     minDate         earliest selectable (Date|ISO); default today
 * update(window):     maxDate         latest selectable (Date|ISO)
 *                     disabledRanges  [{start, end}] (Y-m-d), unavailable
 *                     maxRangeDays    cap on a single selection's length
 *
 * disabledRanges / maxRangeDays are client-side UX guardrails only — the server
 * must re-validate. maxRangeDays is a commit-time backstop, not a live cap.
 * A date input can't express disabledRanges: only the calendar strikes those
 * out, so a typed date can sit inside one until the server rejects it.
 * create() rejects if Cally is unreachable; callers surface that.
 */

// Vendored build; see js/lib/cally/. Swap for an npm dependency once Aspen has one.
const CALLY_MODULE_URL = '../lib/cally/cally-0.9.2.js';

const toIsoDate = (value) => {
	if (!value) {
		return '';
	}
	const date = value instanceof Date ? value : new Date(value);
	if (Number.isNaN(date.getTime())) {
		return '';
	}
	const month = String(date.getMonth() + 1).padStart(2, '0');
	const day = String(date.getDate()).padStart(2, '0');
	return `${date.getFullYear()}-${month}-${day}`;
};

const parseIsoDate = (iso) => new Date(`${iso}T00:00:00`);

const clampRangeEnd = (startIso, endIso, maxRangeDays) => {
	if (maxRangeDays <= 0 || !startIso || !endIso) {
		return endIso;
	}
	const start = parseIsoDate(startIso);
	const spanDays = Math.round((parseIsoDate(endIso) - start) / 86400000);
	if (spanDays <= maxRangeDays) {
		return endIso;
	}
	const capped = new Date(start.getTime());
	capped.setDate(capped.getDate() + maxRangeDays);
	return toIsoDate(capped);
};

export class DateRangePicker {
	#range;
	#startInput;
	#endInput;
	#minIso;
	#maxIso = '';
	#disabledRanges = [];
	#maxRangeDays = 0;

	static async create(range, config = {}) {
		const picker = new DateRangePicker(range, config);
		await picker.#attachCalendar();
		return picker;
	}

	constructor(range, {minDate} = {}) {
		const root = range.closest('.date-range-picker');
		this.#range = range;
		this.#startInput = root.querySelector('[data-date-role="start"]');
		this.#endInput = root.querySelector('[data-date-role="end"]');
		this.#minIso = toIsoDate(minDate) || toIsoDate(new Date());
		this.#applyInputBounds();
		this.#wireInputs();
	}

	update({maxDate, disabledRanges, maxRangeDays}) {
		this.#disabledRanges = disabledRanges ?? [];
		this.#maxRangeDays = maxRangeDays ?? 0;
		this.#maxIso = maxDate ? toIsoDate(maxDate) : '';

		// Reassigning the property re-runs Cally's disable check and re-renders.
		this.#range.isDateDisallowed = (date) => {
			const iso = toIsoDate(date);
			return this.#disabledRanges.some(disabled => iso >= disabled.start && iso <= disabled.end);
		};

		if (this.#maxIso) {
			this.#range.setAttribute('max', this.#maxIso);
		} else {
			this.#range.removeAttribute('max');
		}
		this.#applyInputBounds();
	}

	clear() {
		this.#range.value = '';
		[this.#startInput, this.#endInput].forEach(input => input && (input.value = ''));
		this.#applyInputBounds();
	}

	// The module map caches and de-duplicates this, so repeat pickers pay nothing.
	async #attachCalendar() {
		await import(CALLY_MODULE_URL);

		this.#range.setAttribute('min', this.#minIso);
		this.#range.isDateDisallowed = () => false;

		const startIso = this.#startInput?.value;
		const endIso = this.#endInput?.value;
		if (startIso && endIso) {
			this.#range.value = `${startIso}/${endIso}`;
		}

		this.#range.addEventListener('change', () => this.#onCalendarChange());
	}

	// Native validation for the part the attributes can carry: the selectable
	// window, and To never preceding From.
	#applyInputBounds() {
		if (this.#startInput) {
			this.#startInput.min = this.#minIso;
			this.#startInput.max = this.#maxIso;
		}
		if (this.#endInput) {
			this.#endInput.min = this.#startInput?.value || this.#minIso;
			this.#endInput.max = this.#maxIso;
		}
	}

	#wireInputs() {
		[[this.#startInput, 'start'], [this.#endInput, 'end']]
			.filter(([input]) => input)
			.forEach(([input, role]) => input.addEventListener('change', () => this.#onInputChange(role)));
	}

	#onInputChange(role) {
		const startIso = this.#startInput?.value || '';
		let endIso = this.#endInput?.value || '';
		// A From typed past the To restarts the range, the way picking a start on
		// the calendar does.
		if (role === 'start' && startIso && endIso && startIso > endIso) {
			endIso = startIso;
		}
		endIso = clampRangeEnd(startIso, endIso, this.#maxRangeDays);
		if (this.#endInput && this.#endInput.value !== endIso) {
			this.#endInput.value = endIso;
		}
		this.#applyInputBounds();
		this.#range.value = startIso && endIso && startIso <= endIso ? `${startIso}/${endIso}` : '';
	}

	#onCalendarChange() {
		const [startIso, rawEndIso] = this.#range.value ? this.#range.value.split('/') : [];
		const endIso = clampRangeEnd(startIso, rawEndIso, this.#maxRangeDays);
		if (endIso !== rawEndIso) {
			this.#range.value = `${startIso}/${endIso}`;
		}
		if (this.#startInput) {
			this.#startInput.value = startIso || '';
		}
		if (this.#endInput) {
			this.#endInput.value = endIso || '';
		}
		this.#applyInputBounds();
	}
}
