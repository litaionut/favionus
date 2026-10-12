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
	 * Two-step availability flow backed by the real fleet calendar
	 * (GET /wp-json/favionus/v1/availability). The loading state is shown only
	 * while the request is running; the result is whatever the server returns.
	 * If no units are configured, the modal is a plain request form (no claim).
	 */
	var t = cfg.i18n || {};
	var configured = !!cfg.configured;
	var currentStep = 'ask';
	var inflight = null;
	var detailFields = ['#fleet_name', '#fleet_email', '#fleet_company', '#fleet_country'];

	function q(sel) {
		return form.querySelector(sel);
	}

	function showStep(name) {
		currentStep = name;
		form.querySelectorAll('[data-fleet-step]').forEach(function (step) {
			var key = step.getAttribute('data-fleet-step');
			// In request mode (no fleet configured) the dates/units and details are shown together.
			var on = key === name || (!configured && name === 'details' && key === 'ask');
			step.hidden = !on;
			step.classList.toggle('is-active', on);
		});
		var needDetails = name === 'details';
		detailFields.forEach(function (sel) {
			var el = q(sel);
			if (el) {
				el.required = needDetails;
			}
		});
		if (needDetails && configured) {
			var n = q('#fleet_name');
			if (n) {
				n.focus();
			}
		}
	}

	function setText(sel, text) {
		var el = q(sel);
		if (el) {
			el.textContent = text || '';
		}
	}

	function setError(sel, message) {
		var box = q(sel);
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

	function fmt(str, a, b) {
		return String(str || '').replace('%1$d', a).replace('%2$d', b).replace('%d', a);
	}

	function showResult(kind, title, text) {
		var wrap = q('[data-fleet-result]');
		var box = q('[data-fleet-result-box]');
		if (!wrap) {
			return;
		}
		wrap.hidden = !title && !text;
		if (box) {
			box.className = 'wd-fleet-status' + (kind === 'ok' ? ' wd-fleet-status--ok' : ' wd-fleet-status--custom');
		}
		setText('[data-fleet-result-title]', title);
		setText('[data-fleet-result-text]', text);
	}

	function summary() {
		var s = q('#fleet_start').value;
		var e = q('#fleet_end').value;
		var u = q('#fleet_units').value;
		setText('[data-fleet-summary]', s && e && u ? s + ' – ' + e + ' · ' + u + ' ' + (t.units || 'units') : '');
	}

	function applyMode() {
		var check = form.querySelector('[data-fleet-intro="check"]');
		var request = form.querySelector('[data-fleet-intro="request"]');
		var checkWrap = q('[data-fleet-check-wrap]');
		var back = q('[data-fleet-back]');
		if (check) {
			check.hidden = !configured;
		}
		if (request) {
			request.hidden = configured;
		}
		if (checkWrap) {
			checkWrap.hidden = !configured;
		}
		if (back) {
			back.hidden = !configured;
		}
		showResult('', '', '');
		showStep(configured ? 'ask' : 'details');
	}

	function runCheck() {
		var params = new URLSearchParams({
			start: q('#fleet_start').value,
			end: q('#fleet_end').value,
			units: q('#fleet_units').value,
			nonce: cfg.nonce || ''
		});
		var checkBtn = q('[data-fleet-check]');
		if (checkBtn) {
			checkBtn.disabled = true;
		}
		setError('[data-fleet-check-error]', '');
		showStep('loading');

		var controller = typeof AbortController === 'function' ? new AbortController() : null;
		inflight = controller;

		fetch((cfg.restUrl || '/wp-json/favionus/v1/availability') + '?' + params.toString(), {
			method: 'GET',
			credentials: 'same-origin',
			headers: { Accept: 'application/json' },
			signal: controller ? controller.signal : undefined
		})
			.then(function (response) {
				return response.json().then(function (data) {
					return { ok: response.ok, data: data };
				});
			})
			.then(function (res) {
				if (!res.ok) {
					// 400: bad dates → stay on step 1 with the server message.
					if (res.data && res.data.code === 'favionus_bad_dates') {
						showStep('ask');
						setError('[data-fleet-check-error]', res.data.message);
						return;
					}
					throw new Error('check failed');
				}
				var d = res.data || {};
				summary();
				if (!d.configured) {
					configured = false;
					applyMode();
					return;
				}
				if (d.enough) {
					showResult('ok', t.availableTitle, d.available === 1 ? t.availableOne : fmt(t.availableText, d.available));
				} else if (d.available > 0) {
					showResult('custom', t.partialTitle, fmt(t.partialText, d.available, d.requested));
				} else {
					showResult('custom', t.noneTitle, t.noneText);
				}
				showStep('details');
			})
			.catch(function (err) {
				if (err && err.name === 'AbortError') {
					return;
				}
				// Honest fallback: no result shown, just the request form.
				summary();
				showResult('custom', t.requestTitle, t.checkFailed);
				showStep('details');
			})
			.finally(function () {
				inflight = null;
				if (checkBtn) {
					checkBtn.disabled = false;
				}
			});
	}

	function openModal(opener) {
		lastOpener = opener || null;
		setError('[data-fleet-error]', '');
		setError('[data-fleet-check-error]', '');
		closeSelects();
		applyMode();
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
		if (inflight) {
			inflight.abort();
			inflight = null;
		}
		closeSelects();
		if (lastOpener && typeof lastOpener.focus === 'function') {
			lastOpener.focus();
		}
	});

	form.querySelectorAll('[data-fleet-back]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			setError('[data-fleet-error]', '');
			showResult('', '', '');
			showStep('ask');
		});
	});

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		closeSelects();
		if (!form.reportValidity()) {
			return;
		}

		if (currentStep === 'ask') {
			if (q('#fleet_end').value < q('#fleet_start').value) {
				setError('[data-fleet-check-error]', cfg.badDates || 'The end date must be after the start date.');
				return;
			}
			runCheck();
			return;
		}
		if (currentStep !== 'details') {
			return;
		}

		var sendBtn = q('[data-fleet-send]');
		if (sendBtn) {
			sendBtn.disabled = true;
		}
		setError('[data-fleet-error]', '');

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
					showStep('sent');
					return;
				}
				setError(
					'[data-fleet-error]',
					(payload && payload.data && payload.data.message) || cfg.error || 'Please check your details and try again.'
				);
			})
			.catch(function () {
				setError('[data-fleet-error]', cfg.error || 'Please check your details and try again.');
			})
			.finally(function () {
				if (sendBtn) {
					sendBtn.disabled = false;
				}
			});
	});
})();
