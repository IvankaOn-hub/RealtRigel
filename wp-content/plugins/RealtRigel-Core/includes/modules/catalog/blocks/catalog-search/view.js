(function () {
	function initFloatingSearchPopup(root) {
		var scope = root || document;
		var blocks = scope.querySelectorAll('.rr-catalog-search-block');

		Array.prototype.slice.call(blocks).forEach(function (block, blockIndex) {
			if (block.dataset.rrFloatingSearchInit === '1') {
				return;
			}

			var blockInner = block.querySelector('.rr-catalog-search-block__inner');
			if (!blockInner) {
				return;
			}

			block.dataset.rrFloatingSearchInit = '1';

			var originalParent = blockInner.parentNode;
			var originalNextSibling = blockInner.nextSibling;
			var searchForm = blockInner.querySelector('[data-rr-search-form]');
			var searchIconUrl = String(block.getAttribute('data-search-icon-url') || '');
			var openSearchLabel = String(block.getAttribute('data-open-search-label') || 'Open search');
			var closeSearchLabel = String(block.getAttribute('data-close-search-label') || 'Close search');
			var placeholder = document.createComment('rr-search-block-inner-placeholder-' + blockIndex);
			var floatingButton = document.createElement('button');
			var floatingIcon = document.createElement('span');
			var modal = document.createElement('div');
			var modalBackdrop = document.createElement('div');
			var modalDialog = document.createElement('div');
			var modalClose = document.createElement('button');
			var isBlockVisible = true;
			var isModalOpen = false;
			var visibilityRaf = 0;
			var delayedVisibilityTimer = 0;
			var onWindowScroll = null;
			var onWindowResize = null;
			var onWindowLoad = null;
			var onDocumentKeydown = null;

			floatingButton.type = 'button';
			floatingButton.className = 'rr-search-floating-trigger';
			floatingButton.setAttribute('aria-label', openSearchLabel);
			floatingButton.hidden = true;

			floatingIcon.className = 'rr-search-floating-trigger__icon';
			if (searchIconUrl) {
				var floatingIconImage = document.createElement('img');
				floatingIconImage.src = searchIconUrl;
				floatingIconImage.alt = '';
				floatingIcon.appendChild(floatingIconImage);
			} else {
				floatingIcon.textContent = '⌕';
			}
			floatingButton.appendChild(floatingIcon);

			modal.className = 'rr-search-floating-modal';
			modal.hidden = true;

			modalBackdrop.className = 'rr-search-floating-modal__backdrop';
			modalDialog.className = 'rr-search-floating-modal__dialog';
			modalClose.type = 'button';
			modalClose.className = 'rr-search-floating-modal__close';
			modalClose.setAttribute('aria-label', closeSearchLabel);
			modalClose.textContent = '×';

			modalDialog.appendChild(modalClose);
			modal.appendChild(modalBackdrop);
			modal.appendChild(modalDialog);

			document.body.appendChild(floatingButton);
			document.body.appendChild(modal);

			var moveBlockInnerToOriginalPosition = function () {
				if (!placeholder.parentNode) {
					return;
				}

				placeholder.parentNode.insertBefore(blockInner, placeholder);
				placeholder.parentNode.removeChild(placeholder);
			};

			var ensurePlaceholder = function () {
				if (placeholder.parentNode) {
					return;
				}

				if (originalNextSibling && originalNextSibling.parentNode === originalParent) {
					originalParent.insertBefore(placeholder, originalNextSibling);
					return;
				}

				originalParent.appendChild(placeholder);
			};

			var closeModal = function () {
				if (!isModalOpen) {
					return;
				}

				isModalOpen = false;
				modal.hidden = true;
				document.body.classList.remove('rr-search-floating-modal-open');
				moveBlockInnerToOriginalPosition();
			};

			var openModal = function () {
				if (isModalOpen) {
					return;
				}

				isModalOpen = true;
				ensurePlaceholder();
				modalDialog.appendChild(blockInner);
				modal.hidden = false;
				document.body.classList.add('rr-search-floating-modal-open');
			};

			var syncFloatingButton = function () {
				floatingButton.hidden = isBlockVisible;

				if (isBlockVisible && isModalOpen) {
					closeModal();
				}
			};

			var computeVisibility = function () {
				var visibilityTarget = isModalOpen ? block : blockInner;
				var rect = visibilityTarget.getBoundingClientRect();
				var viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
				var viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
				var visibleWidth = Math.max(0, Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0));
				var visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
				var visibleArea = visibleWidth * visibleHeight;
				var totalArea = Math.max(rect.width * rect.height, 1);
				var visibleRatio = visibleArea / totalArea;

				isBlockVisible = visibleRatio > 0.1;
				syncFloatingButton();
			};

			var requestVisibilityCheck = function () {
				if (visibilityRaf) {
					return;
				}

				visibilityRaf = window.requestAnimationFrame(function () {
					visibilityRaf = 0;
					computeVisibility();
				});
			};

			var scheduleVisibilityChecks = function () {
				requestVisibilityCheck();

				if (delayedVisibilityTimer) {
					window.clearTimeout(delayedVisibilityTimer);
				}

				delayedVisibilityTimer = window.setTimeout(function () {
					requestVisibilityCheck();
				}, 150);
			};

			var destroyFloatingSearchPopup = function () {
				closeModal();

				if (visibilityRaf) {
					window.cancelAnimationFrame(visibilityRaf);
					visibilityRaf = 0;
				}

				if (delayedVisibilityTimer) {
					window.clearTimeout(delayedVisibilityTimer);
					delayedVisibilityTimer = 0;
				}

				if (onWindowScroll) {
					window.removeEventListener('scroll', onWindowScroll, { passive: true });
				}

				if (onWindowResize) {
					window.removeEventListener('resize', onWindowResize);
				}

				if (onWindowLoad) {
					window.removeEventListener('load', onWindowLoad);
				}

				if (onDocumentKeydown) {
					document.removeEventListener('keydown', onDocumentKeydown);
				}

				if (floatingButton.parentNode) {
					floatingButton.parentNode.removeChild(floatingButton);
				}

				if (modal.parentNode) {
					modal.parentNode.removeChild(modal);
				}

				delete block.__rrFloatingSearchDestroy;
				delete block.dataset.rrFloatingSearchInit;
			};

			block.__rrFloatingSearchDestroy = destroyFloatingSearchPopup;

			floatingButton.addEventListener('click', openModal);
			modalClose.addEventListener('click', closeModal);
			modalBackdrop.addEventListener('click', closeModal);

			if (searchForm) {
				searchForm.addEventListener('submit', function () {
					if (isModalOpen) {
						closeModal();
					}
				});
			}

			onDocumentKeydown = function (event) {
				if (event.key === 'Escape') {
					closeModal();
				}
			};

			onWindowScroll = requestVisibilityCheck;
			onWindowResize = scheduleVisibilityChecks;
			onWindowLoad = scheduleVisibilityChecks;

			document.addEventListener('keydown', onDocumentKeydown);
			window.addEventListener('scroll', onWindowScroll, { passive: true });
			window.addEventListener('resize', onWindowResize);
			window.addEventListener('load', onWindowLoad);
			scheduleVisibilityChecks();
		});
	}

	function initSearchForms(root) {
		var scope = root || document;
		var forms = scope.querySelectorAll('[data-rr-search-form]');

		Array.prototype.slice.call(forms).forEach(function (form) {
			if (form.dataset.rrSearchInit === '1') {
				return;
			}

			form.dataset.rrSearchInit = '1';

			var toggle = form.querySelector('[data-rr-extra-toggle]');
			var extraRoot = form.querySelector('[data-rr-extra-filters]');
			var extraGrid = extraRoot ? extraRoot.querySelector('.rr-catalog-search-form__extra-grid') : null;

			if (toggle && extraRoot && extraGrid) {
				var applyExpandedState = function (expanded) {
					extraRoot.setAttribute('data-expanded', expanded ? '1' : '0');
					extraGrid.hidden = !expanded;
					toggle.textContent = expanded
						? String(toggle.getAttribute('data-less-label') || '')
						: String(toggle.getAttribute('data-more-label') || '');
				};

				applyExpandedState(extraRoot.getAttribute('data-expanded') === '1');

				toggle.addEventListener('click', function () {
					applyExpandedState(extraRoot.getAttribute('data-expanded') !== '1');
				});
			}

			form.addEventListener('submit', function (event) {
				if (!window.RRCatalogPage || typeof window.RRCatalogPage.navigate !== 'function' || typeof window.RRCatalogPage.buildFormUrl !== 'function') {
					return;
				}

				var autocompleteRoot = form.querySelector('[data-rr-location-autocomplete]');

				if (!autocompleteRoot) {
					event.preventDefault();
					window.RRCatalogPage.navigate(
						window.RRCatalogPage.buildFormUrl(form),
						{ pushState: true, scrollToResults: true }
					);
					return;
				}

				event.preventDefault();

				var input = autocompleteRoot.querySelector('[data-rr-location-input]');
				var typedLocation = input ? String(input.value || '').trim() : '';
				var latestSuggestions = autocompleteRoot.__rrLatestSuggestions || [];
				var addSelection = autocompleteRoot.__rrAddSelection;
				var fetchSuggestions = autocompleteRoot.__rrFetchSuggestions;

				if (typedLocation.length < 2) {
					window.RRCatalogPage.navigate(
						window.RRCatalogPage.buildFormUrl(form),
						{ pushState: true, scrollToResults: true }
					);
					return;
				}

				if (latestSuggestions.length && typeof addSelection === 'function') {
					addSelection(latestSuggestions[0]);
					window.RRCatalogPage.navigate(
						window.RRCatalogPage.buildFormUrl(form),
						{ pushState: true, scrollToResults: true }
					);
					return;
				}

				if (typeof fetchSuggestions !== 'function') {
					window.RRCatalogPage.navigate(
						window.RRCatalogPage.buildFormUrl(form),
						{ pushState: true, scrollToResults: true }
					);
					return;
				}

				fetchSuggestions(typedLocation).then(function (items) {
					if (Array.isArray(items) && items.length && typeof addSelection === 'function') {
						addSelection(items[0]);
					}

					window.RRCatalogPage.navigate(
						window.RRCatalogPage.buildFormUrl(form),
						{ pushState: true, scrollToResults: true }
					);
				});
			});
		});
	}

	function initLocationAutocomplete(root) {
		var scope = root || document;
		var roots = scope.querySelectorAll('[data-rr-location-autocomplete]');

		Array.prototype.slice.call(roots).forEach(function (autocompleteRoot) {
			if (autocompleteRoot.dataset.rrLocationInit === '1') {
				return;
			}

			autocompleteRoot.dataset.rrLocationInit = '1';

			var form = autocompleteRoot.closest('form');
			var endpoint = String(autocompleteRoot.getAttribute('data-endpoint') || '');
			var input = autocompleteRoot.querySelector('[data-rr-location-input]');
			var hiddenInputsWrap = autocompleteRoot.querySelector('[data-rr-location-hidden-inputs]');
			var selectedWrap = autocompleteRoot.querySelector('[data-rr-location-selected]');
			var resultsWrap = autocompleteRoot.querySelector('[data-rr-location-results]');
			var clearButton = autocompleteRoot.querySelector('[data-rr-location-clear]');
			var initialPathRaw = String(autocompleteRoot.getAttribute('data-initial-selections') || '[]');
			var removeLocationLabel = String(autocompleteRoot.getAttribute('data-remove-location-label') || 'Remove location');
			var emptyResultsLabel = String(autocompleteRoot.getAttribute('data-empty-results-label') || 'Nothing found');
			var selectedItems = [];
			var latestSuggestions = [];
			var requestId = 0;

			try {
				selectedItems = JSON.parse(initialPathRaw);
			} catch (error) {
				selectedItems = [];
			}

			var rebuildHiddenInputs = function () {
				hiddenInputsWrap.innerHTML = '';

				selectedItems.forEach(function (item) {
					var hidden = document.createElement('input');
					hidden.type = 'hidden';
					hidden.name = 'catalog_location[]';
					hidden.value = String(item.slug || '');
					hiddenInputsWrap.appendChild(hidden);
				});
			};

			var hideResults = function () {
				latestSuggestions = [];
				resultsWrap.hidden = true;
				resultsWrap.innerHTML = '';
			};

			var removeSelection = function (item) {
				selectedItems = selectedItems.filter(function (selectedItem) {
					return String(selectedItem.slug || '') !== String(item.slug || '');
				});

				rebuildHiddenInputs();
				renderSelectedPath();
				hideResults();
				input.focus();
			};

			var appendChip = function (labelText, item, removable) {
				var chip = document.createElement('span');

				chip.className = 'rr-location-search__selected-item';
				chip.textContent = String(labelText || '');

				if (removable && item) {
					var remove = document.createElement('button');

					remove.type = 'button';
					remove.className = 'rr-location-search__selected-remove';
					remove.setAttribute('aria-label', removeLocationLabel);
					remove.textContent = '×';
					remove.addEventListener('click', function (event) {
						event.preventDefault();
						event.stopPropagation();
						removeSelection(item);
					});

					chip.appendChild(remove);
				}

				selectedWrap.appendChild(chip);
			};

			var renderSelectedPath = function () {
				selectedWrap.innerHTML = '';

				if (!selectedItems.length) {
					clearButton.hidden = true;
					return;
				}

				if (selectedItems.length === 1) {
					var singleItem = selectedItems[0];
					var singlePath = Array.isArray(singleItem.path) ? singleItem.path : [];
					var singleParent = singlePath.length > 1 ? singlePath[singlePath.length - 2] : null;
					var singleLabel = singleParent ? String(singleParent.name || '') + ', ' + String(singleItem.name || '') : String(singleItem.name || '');

					appendChip(singleLabel, singleItem, true);
					clearButton.hidden = false;
					return;
				}

				var groups = new Map();

				selectedItems.forEach(function (item) {
					var path = Array.isArray(item.path) ? item.path : [];
					var parent = path.length > 1 ? path[path.length - 2] : null;
					var groupKey = parent ? String(parent.slug || parent.id || '') : '__root__';

					if (!groups.has(groupKey)) {
						groups.set(groupKey, {
							parent: parent,
							items: []
						});
					}

					groups.get(groupKey).items.push(item);
				});

				groups.forEach(function (group) {
					if (group.parent && group.items.length > 1) {
						appendChip(String(group.parent.name || ''), null, false);

						group.items.forEach(function (item) {
							appendChip(String(item.name || ''), item, true);
						});

						return;
					}

					group.items.forEach(function (item) {
						var path = Array.isArray(item.path) ? item.path : [];
						var parent = path.length > 1 ? path[path.length - 2] : null;
						var label = parent ? String(parent.name || '') + ', ' + String(item.name || '') : String(item.name || '');

						appendChip(label, item, true);
					});
				});

				clearButton.hidden = false;
			};

			var isAncestorOf = function (maybeAncestor, maybeDescendant) {
				if (!maybeAncestor || !maybeDescendant || !Array.isArray(maybeDescendant.path)) {
					return false;
				}

				return maybeDescendant.path.some(function (pathItem) {
					return Number(pathItem.id || 0) === Number(maybeAncestor.id || 0);
				});
			};

			var addSelection = function (item) {
				if (!item || !item.slug) {
					return;
				}

				selectedItems = selectedItems.filter(function (existingItem) {
					if (String(existingItem.slug || '') === String(item.slug || '')) {
						return false;
					}

					if (isAncestorOf(existingItem, item)) {
						return false;
					}

					if (isAncestorOf(item, existingItem)) {
						return false;
					}

					return true;
				});

				selectedItems.push(item);
				rebuildHiddenInputs();
				input.value = '';
				renderSelectedPath();
				hideResults();
			};

			var renderResults = function (items) {
				latestSuggestions = Array.isArray(items) ? items : [];
				autocompleteRoot.__rrLatestSuggestions = latestSuggestions;
				resultsWrap.innerHTML = '';

				if (!latestSuggestions.length) {
					var empty = document.createElement('div');
					empty.className = 'rr-location-search__empty';
					empty.textContent = emptyResultsLabel;
					resultsWrap.appendChild(empty);
					resultsWrap.hidden = false;
					return;
				}

				latestSuggestions.forEach(function (item) {
					var button = document.createElement('button');
					var label = document.createElement('span');
					var meta = document.createElement('span');

					button.type = 'button';
					button.className = 'rr-location-search__option';
					label.className = 'rr-location-search__option-label';
					meta.className = 'rr-location-search__option-meta';
					label.textContent = String(item.name || '');
					meta.textContent = String(item.label || '');

					button.appendChild(label);
					button.appendChild(meta);
					button.addEventListener('click', function () {
						addSelection(item);
					});

					resultsWrap.appendChild(button);
				});

				resultsWrap.hidden = false;
			};

			var fetchSuggestions = function (search) {
				if (!endpoint) {
					return Promise.resolve([]);
				}

				var trimmed = String(search || '').trim();

				if (trimmed.length < 2) {
					hideResults();
					return Promise.resolve([]);
				}

				requestId += 1;
				var currentRequestId = requestId;
				var url = new URL(endpoint, window.location.origin);

				url.searchParams.set('search', trimmed);

				return window.fetch(url.toString(), {
					headers: {
						Accept: 'application/json'
					}
				})
					.then(function (response) {
						if (!response.ok) {
							throw new Error('Request failed');
						}

						return response.json();
					})
					.then(function (items) {
						if (currentRequestId !== requestId) {
							return [];
						}

						var normalizedItems = Array.isArray(items) ? items : [];
						renderResults(normalizedItems);
						return normalizedItems;
					})
					.catch(function () {
						if (currentRequestId !== requestId) {
							return [];
						}

						hideResults();
						return [];
					});
			};

			input.addEventListener('input', function () {
				fetchSuggestions(input.value);
			});

			input.addEventListener('focus', function () {
				if (String(input.value || '').trim().length >= 2) {
					fetchSuggestions(input.value);
				}
			});

			clearButton.addEventListener('click', function () {
				selectedItems = [];
				rebuildHiddenInputs();
				renderSelectedPath();
				hideResults();
				input.focus();
			});

			document.addEventListener('click', function (event) {
				if (autocompleteRoot.contains(event.target)) {
					return;
				}

				hideResults();
			});

			autocompleteRoot.__rrLatestSuggestions = latestSuggestions;
			autocompleteRoot.__rrAddSelection = addSelection;
			autocompleteRoot.__rrFetchSuggestions = fetchSuggestions;

			rebuildHiddenInputs();
			renderSelectedPath();
		});
	}

	window.RRInitCatalogSearch = function (root) {
		initSearchForms(root || document);
		initLocationAutocomplete(root || document);
		initFloatingSearchPopup(root || document);
	};

	document.addEventListener('change', function (event) {
		var sortSelect = event.target.closest('.rr-properties-block__sort select');

		if (!sortSelect) {
			return;
		}

		var form = sortSelect.form;
		if (!form) {
			return;
		}

		if (!window.RRCatalogPage || typeof window.RRCatalogPage.navigate !== 'function' || typeof window.RRCatalogPage.buildFormUrl !== 'function') {
			return;
		}

		window.RRCatalogPage.navigate(
			window.RRCatalogPage.buildFormUrl(form),
			{ pushState: true, scrollToResults: true }
		);
	});

	document.addEventListener('submit', function (event) {
		var sortForm = event.target.closest('.rr-properties-block__sort');

		if (!sortForm) {
			return;
		}

		if (!window.RRCatalogPage || typeof window.RRCatalogPage.navigate !== 'function' || typeof window.RRCatalogPage.buildFormUrl !== 'function') {
			return;
		}

		event.preventDefault();
		window.RRCatalogPage.navigate(
			window.RRCatalogPage.buildFormUrl(sortForm),
			{ pushState: true, scrollToResults: true }
		);
	});

	document.addEventListener('DOMContentLoaded', function () {
		window.RRInitCatalogSearch(document);
	});
})();
