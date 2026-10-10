(function () {
	'use strict';

	var cfg = window.dopplerLidarFleet || {};

	function scrollToContact() {
		var section = document.getElementById('contact');
		if (!section) {
			return;
		}
		section.scrollIntoView({ behavior: 'auto', block: 'start' });
	}

	if (/[?&]campaign=(sent|error)\b/.test(window.location.search)) {
		scrollToContact();
	}

	var campaignForm = document.getElementById('campaign-form');
	if (campaignForm) {
		campaignForm.addEventListener('submit', function (event) {
			event.preventDefault();
			if (!campaignForm.reportValidity()) {
				return;
			}

			var submitBtn = campaignForm.querySelector('[type="submit"]');
			var success = document.getElementById('campaign-success');
			var errorBox = document.getElementById('campaign-error');
			if (submitBtn) {
				submitBtn.disabled = true;
			}
			if (errorBox) {
				errorBox.hidden = true;
			}

			var data = new FormData(campaignForm);
			data.append('action', 'doppler_lidar_campaign');

			fetch(cfg.ajaxUrl || campaignForm.getAttribute('action'), {
				method: 'POST',
				body: data,
				credentials: 'same-origin'
			})
				.then(function (response) {
					return response.json().then(function (payload) {
						return { ok: response.ok, payload: payload };
					});
				})
				.then(function (result) {
					if (result.payload && result.payload.success) {
						campaignForm.hidden = true;
						if (success) {
							success.hidden = false;
							success.focus();
						}
						scrollToContact();
						return;
					}
					if (errorBox) {
						errorBox.textContent =
							(result.payload && result.payload.data && result.payload.data.message) ||
							cfg.campaignError ||
							'Please fill in your name and a valid work email, then try again.';
						errorBox.hidden = false;
					}
				})
				.catch(function () {
					if (errorBox) {
						errorBox.textContent = cfg.campaignError || 'Please fill in your name and a valid work email, then try again.';
						errorBox.hidden = false;
					}
				})
				.finally(function () {
					if (submitBtn) {
						submitBtn.disabled = false;
					}
				});
		});
	}

	var modal = document.getElementById('fleet-modal');
	var form = document.getElementById('fleet-form');
	if (!modal || !form || typeof modal.showModal !== 'function') {
		return;
	}

	var cfg = window.dopplerLidarFleet || {};
	var lastOpener = null;
	var loadTimer = null;

	var durationLabels = cfg.durations || {
		lt1: '< 1 month',
		'2-3': '2–3 months',
		gt3: '> 3 months'
	};

	function enhanceSelect(select) {
		if (!select || select.dataset.enhanced === '1') {
			return;
		}

		var wrap = select.closest('[data-wd-select]');
		if (!wrap) {
			return;
		}

		select.dataset.enhanced = '1';
		select.setAttribute('tabindex', '-1');
		select.setAttribute('aria-hidden', 'true');
		wrap.classList.add('is-enhanced');

		var input = document.createElement('input');
		input.type = 'text';
		input.className = 'wd-select__toggle';
		input.id = select.id + '-toggle';
		input.setAttribute('role', 'combobox');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-expanded', 'false');
		input.setAttribute('autocomplete', 'off');
		input.setAttribute('autocapitalize', 'off');
		input.setAttribute('spellcheck', 'false');

		var list = document.createElement('ul');
		list.className = 'wd-select__list';
		list.id = select.id + '-list';
		list.setAttribute('role', 'listbox');
		list.hidden = true;
		input.setAttribute('aria-controls', list.id);

		var empty = document.createElement('li');
		empty.className = 'wd-select__empty';
		empty.setAttribute('aria-hidden', 'true');
		empty.textContent = cfg.noCountry || 'No matching country';
		empty.hidden = true;

		var activeItem = null;

		function placeholder() {
			var first = select.options[0];
			return first && !first.value ? first.textContent.trim() : '';
		}

		function selectedLabel() {
			var opt = select.selectedOptions[0];
			return opt && opt.value ? opt.textContent.trim() : '';
		}

		function optionItems() {
			return Array.prototype.slice.call(list.querySelectorAll('[role="option"]'));
		}

		function visibleItems() {
			return optionItems().filter(function (item) {
				return !item.hidden;
			});
		}

		function syncInput() {
			var label = selectedLabel();
			input.value = label;
			input.classList.toggle('is-placeholder', !label);
		}

		function setActive(item) {
			if (activeItem) {
				activeItem.classList.remove('is-active');
			}
			activeItem = item || null;
			if (activeItem) {
				activeItem.classList.add('is-active');
				input.setAttribute('aria-activedescendant', activeItem.id);
				if (typeof activeItem.scrollIntoView === 'function') {
					activeItem.scrollIntoView({ block: 'nearest' });
				}
			} else {
				input.removeAttribute('aria-activedescendant');
			}
		}

		function filterList(query) {
			var q = query.toLowerCase().trim();
			var visible = [];
			optionItems().forEach(function (item) {
				var label = item.textContent.toLowerCase();
				var match = !q || label.indexOf(q) === 0;
				item.hidden = !match;
				if (match) {
					visible.push(item);
				}
			});
			empty.hidden = visible.length > 0;
			setActive(visible[0] || null);
			return visible;
		}

		function closeList(restore) {
			wrap.classList.remove('is-open');
			input.setAttribute('aria-expanded', 'false');
			list.hidden = true;
			setActive(null);
			if (restore) {
				commitTypedValue();
				syncInput();
			}
		}

		function openList() {
			wrap.classList.add('is-open');
			input.setAttribute('aria-expanded', 'true');
			list.hidden = false;
			filterList(input.value);
		}

		function choose(item) {
			select.value = item.getAttribute('data-value');
			select.dispatchEvent(new Event('change', { bubbles: true }));
			wrap.classList.remove('is-invalid');
			input.value = item.textContent.trim();
			input.classList.remove('is-placeholder');
			closeList(false);
			input.focus();
		}

		function commitTypedValue() {
			var typed = input.value.toLowerCase().trim();
			if (!typed) {
				select.value = '';
				return;
			}
			var exact = optionItems().filter(function (item) {
				return item.textContent.trim().toLowerCase() === typed;
			});
			if (exact.length === 1) {
				select.value = exact[0].getAttribute('data-value');
				wrap.classList.remove('is-invalid');
				return;
			}
			var starts = optionItems().filter(function (item) {
				return item.textContent.trim().toLowerCase().indexOf(typed) === 0;
			});
			if (starts.length === 1) {
				select.value = starts[0].getAttribute('data-value');
				wrap.classList.remove('is-invalid');
			}
		}

		Array.prototype.forEach.call(select.options, function (opt, index) {
			if (!opt.value) {
				return;
			}
			var item = document.createElement('li');
			item.setAttribute('role', 'option');
			item.id = select.id + '-opt-' + index;
			item.setAttribute('data-value', opt.value);
			item.textContent = opt.textContent.trim();
			item.addEventListener('mousedown', function (event) {
				event.preventDefault();
			});
			item.addEventListener('click', function () {
				choose(item);
			});
			list.appendChild(item);
		});
		list.appendChild(empty);

		input.placeholder = placeholder();

		input.addEventListener('focus', function () {
			openList();
		});

		input.addEventListener('input', function () {
			select.value = '';
			if (list.hidden) {
				openList();
			} else {
				filterList(input.value);
			}
		});

		input.addEventListener('keydown', function (event) {
			var items = visibleItems();
			var index = activeItem ? items.indexOf(activeItem) : -1;

			if (event.key === 'Escape') {
				event.preventDefault();
				closeList(true);
				return;
			}
			if (event.key === 'ArrowDown') {
				event.preventDefault();
				if (list.hidden) {
					openList();
					return;
				}
				setActive(items[Math.min(index + 1, items.length - 1)] || items[0]);
				return;
			}
			if (event.key === 'ArrowUp') {
				event.preventDefault();
				if (list.hidden) {
					openList();
					return;
				}
				setActive(items[Math.max(index - 1, 0)] || items[0]);
				return;
			}
			if (event.key === 'Enter' && !list.hidden && activeItem) {
				event.preventDefault();
				choose(activeItem);
			}
		});

		document.addEventListener('click', function (event) {
			if (!wrap.contains(event.target)) {
				closeList(true);
			}
		});

		select.addEventListener('invalid', function () {
			wrap.classList.add('is-invalid');
		});

		wrap.appendChild(input);
		wrap.appendChild(list);
		syncInput();

		wrap._wdClose = function () {
			closeList(true);
		};
	}

	form.querySelectorAll('[data-wd-select] select').forEach(enhanceSelect);

	/*
	 * Honest "Request availability" form: one step, no simulated check.
	 * The request is emailed to Favionus, who confirm availability by email.
	 */
	var body = form.querySelector('[data-fleet-step="form"]');
	var sent = form.querySelector('[data-fleet-step="sent"]');

	function setError(message) {
		var box = form.querySelector('[data-fleet-error]');
		if (!box) {
			return;
		}
		box.hidden = !message;
		box.textContent = message || '';
	}

	function closeSelects() {
		form.querySelectorAll('[data-wd-select]').forEach(function (wrap) {
			if (typeof wrap._wdClose === 'function') {
				wrap._wdClose();
			}
		});
	}

	function showStep(step) {
		[body, sent].forEach(function (el) {
			if (!el) {
				return;
			}
			var on = el === step;
			el.hidden = !on;
			el.classList.toggle('is-active', on);
		});
	}

	function showForm() {
		showStep(body);
	}

	function openModal(opener) {
		lastOpener = opener || null;
		setError('');
		closeSelects();
		showForm();
		modal.showModal();
	}

	function closeModal() {
		if (modal.open) {
			modal.close();
		}
	}

	document.querySelectorAll('[data-wd-modal="fleet-modal"]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			openModal(btn);
		});
	});

	modal.querySelectorAll('[data-wd-modal-close]').forEach(function (el) {
		el.addEventListener('click', closeModal);
	});

	modal.addEventListener('click', function (event) {
		if (event.target === modal) {
			closeModal();
		}
	});

	modal.addEventListener('close', function () {
		closeSelects();
		if (lastOpener && typeof lastOpener.focus === 'function') {
			lastOpener.focus();
		}
	});

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		closeSelects();
		if (!form.reportValidity()) {
			return;
		}

		var sendBtn = form.querySelector('[data-fleet-send]');
		if (sendBtn) {
			sendBtn.disabled = true;
		}
		setError('');

		fetch(cfg.ajaxUrl || form.getAttribute('action'), {
			method: 'POST',
			body: new FormData(form),
			credentials: 'same-origin'
		})
			.then(function (response) {
				return response.json();
			})
			.then(function (payload) {
				if (payload && payload.success) {
					form.reset();
					showStep(sent);
					return;
				}
				setError(
					(payload && payload.data && payload.data.message) ||
						cfg.error ||
						'Please check your details and try again.'
				);
			})
			.catch(function () {
				setError(cfg.error || 'Please check your details and try again.');
			})
			.finally(function () {
				if (sendBtn) {
					sendBtn.disabled = false;
				}
			});
	});
})();
