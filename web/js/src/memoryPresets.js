/**
 * NexviaCP memory / CPU preset buttons.
 *
 * Wires up every `.js-memory-preset` button. Each button carries:
 *   - data-target: the id of the input element to populate
 *   - data-value:  the value to write into that input
 *
 * When clicked, the preset value is written into the target input, the input
 * is enabled (in case it was disabled by the unlimited-toggle), and any
 * sibling `.js-unlimited-toggle` is deactivated so the two never conflict.
 *
 * Buttons are rendered in add_package.php / edit_package.php (RAM/CPU quota)
 * and in edit_web.php (per-domain baseline/peak/CPU fields).
 */
export default function handleMemoryPresets() {
	document.querySelectorAll(".js-memory-preset").forEach((presetBtn) => {
		presetBtn.addEventListener("click", () => {
			const targetId = presetBtn.dataset.target;
			if (!targetId) return;
			const input = document.getElementById(targetId);
			if (!input) return;

			input.value = presetBtn.dataset.value;
			input.disabled = false;

			// Deactivate any sibling unlimited toggle inside the same block
			// so the two never fight over the input value.
			const block = input.closest(".u-mb10");
			if (block) {
				const toggle = block.querySelector(".js-unlimited-toggle");
				if (toggle) toggle.classList.remove("active");
			}

			// Visual feedback: highlight the clicked preset briefly.
			presetBtn.classList.add("active");
			block
				.querySelectorAll(".js-memory-preset")
				.forEach((b) => {
					if (b !== presetBtn) b.classList.remove("active");
				});
		});
	});
}
