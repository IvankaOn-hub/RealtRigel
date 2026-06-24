(function () {
	var requestInFlight = false;
	var ratesPromise = null;

	function getPreferredCurrency() {
		return String(document.documentElement.getAttribute('data-rtg-currency') || 'PLN').toUpperCase();
	}

	function formatAmount(amount, currencyCode) {
		var safeAmount = Number.isFinite(amount) ? amount : 0;

		return formatPriceLabel(String(Math.round(safeAmount)) + ' ' + currencyCode);
	}

	function formatPriceLabel(label) {
		var source = String(label || '');
		var match = source.match(/\d+/);

		if (!match) {
			return source.replace(/ /g, '&nbsp;');
		}

		var digits = match[0];
		var groups = [];
		var startIndex = match.index || 0;
		var prefix = source.slice(0, startIndex);
		var suffixStart = startIndex + digits.length;
		var suffix = source.slice(suffixStart).replace(/^\s+/, '');

		while (digits.length > 3) {
			groups.unshift(digits.slice(-3));
			digits = digits.slice(0, -3);
		}

		if (digits.length) {
			groups.unshift(digits);
		}

		if (suffix) {
			groups.push(suffix);
		}

		return prefix + groups.join('&nbsp;');
	}

	function fitNegotiableText(node) {
		var parent;
		var maxSize = 24;
		var minSize = 13;
		var availableWidth;

		if (!node || !node.classList || !node.classList.contains('is-negotiable')) {
			return;
		}

		parent = node.parentElement;
		if (!parent) {
			return;
		}

		availableWidth = parent.clientWidth;
		if (!availableWidth) {
			return;
		}

		node.style.fontSize = maxSize + 'px';

		if (node.scrollWidth <= availableWidth) {
			return;
		}

		node.style.fontSize = Math.max(minSize, Math.floor(maxSize * (availableWidth / node.scrollWidth))) + 'px';
	}

	function fitNegotiablePrices(root) {
		var scope = root || document;
		var prices = scope.querySelectorAll('.rr-properties-card__price.is-negotiable');

		Array.prototype.slice.call(prices).forEach(fitNegotiableText);
	}

	function loadRates(apiBase) {
		if (ratesPromise) {
			return ratesPromise;
		}

		ratesPromise = window.fetch(String(apiBase || 'https://api.nbp.pl/api').replace(/\/$/, '') + '/exchangerates/tables/A/?format=json', {
			method: 'GET',
			headers: {
				Accept: 'application/json'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('NBP request failed');
				}

				return response.json();
			})
			.then(function (payload) {
				var table = Array.isArray(payload) ? payload[0] : null;
				var rates = { PLN: 1 };

				if (!table || !Array.isArray(table.rates)) {
					throw new Error('Invalid NBP payload');
				}

				table.rates.forEach(function (rate) {
					if (!rate || typeof rate.code !== 'string' || typeof rate.mid !== 'number') {
						return;
					}

					rates[String(rate.code).toUpperCase()] = rate.mid;
				});

				return rates;
			})
			.catch(function (error) {
				ratesPromise = null;
				throw error;
			});

		return ratesPromise;
	}

	function renderCardPrice(priceBox, targetCurrency) {
		var priceValue = priceBox.querySelector('[data-rr-card-price-value]');
		var rateValue = priceBox.closest('.rr-properties-card__content')
			? priceBox.closest('.rr-properties-card__content').querySelector('[data-rr-card-rate-value]')
			: null;
		var apiBase = String(priceBox.getAttribute('data-nbp-api-base') || 'https://api.nbp.pl/api').replace(/\/$/, '');
		var basePrice = Number(priceBox.getAttribute('data-base-price') || '0');
		var baseArea = Number(priceBox.getAttribute('data-base-area') || '0');
		var baseCurrency = String(priceBox.getAttribute('data-base-currency') || 'USD').toUpperCase();
		var rateUnitLabel = String(priceBox.getAttribute('data-rate-unit-label') || 'sq m');

		function setTexts(amount, currencyCode) {
			if (priceValue) {
				priceValue.innerHTML = formatAmount(amount, currencyCode);
			}

			if (rateValue && Number.isFinite(baseArea) && baseArea > 0) {
				rateValue.textContent = '≈ ' + String(Math.ceil(amount / baseArea)) + ' ' + currencyCode + ' / ' + rateUnitLabel;
			}
		}

		if (!Number.isFinite(basePrice) || basePrice <= 0) {
			fitNegotiableText(priceValue);
			return Promise.resolve();
		}

		if (targetCurrency === baseCurrency) {
			setTexts(basePrice, baseCurrency);
			return Promise.resolve();
		}

		return loadRates(apiBase)
			.then(function (rates) {
				var sourceRate = rates[baseCurrency];
				var destinationRate = rates[targetCurrency];

				if (typeof sourceRate !== 'number' || typeof destinationRate !== 'number' || sourceRate <= 0 || destinationRate <= 0) {
					throw new Error('Unsupported currency');
				}

				setTexts(Math.ceil((basePrice * sourceRate) / destinationRate), targetCurrency);
			})
			.catch(function () {
				setTexts(basePrice, baseCurrency);
			});
	}

	function initCardCurrencies(root) {
		var scope = root || document;
		var boxes = scope.querySelectorAll('[data-rr-card-price-box]');
		var targetCurrency = getPreferredCurrency();

		Array.prototype.slice.call(boxes).forEach(function (box) {
			void renderCardPrice(box, targetCurrency);
		});
		fitNegotiablePrices(scope);
	}

	function replaceSearchBlocks(doc) {
		var currentBlocks = document.querySelectorAll('.rr-catalog-search-block');
		var nextBlocks = doc.querySelectorAll('.rr-catalog-search-block');

		if (!currentBlocks.length || !nextBlocks.length) {
			return;
		}

		Array.prototype.slice.call(currentBlocks).forEach(function (block, index) {
			if (!nextBlocks[index]) {
				return;
			}

			if (typeof block.__rrFloatingSearchDestroy === 'function') {
				block.__rrFloatingSearchDestroy();
			}

			block.replaceWith(nextBlocks[index]);
		});
	}

	function replacePropertiesBlocks(doc) {
		var currentBlocks = document.querySelectorAll('[data-rr-properties-block-id]');

		Array.prototype.slice.call(currentBlocks).forEach(function (block) {
			var blockId = block.getAttribute('data-rr-properties-block-id');
			var nextBlock = doc.querySelector('[data-rr-properties-block-id="' + blockId + '"]');

			if (!nextBlock) {
				return;
			}

			block.replaceWith(nextBlock);
		});
	}

	function setPropertiesLoadingState(isLoading) {
		var blocks = document.querySelectorAll('[data-rr-properties-block]');

		Array.prototype.slice.call(blocks).forEach(function (block) {
			block.classList.toggle('is-loading', !!isLoading);
			block.setAttribute('aria-busy', isLoading ? 'true' : 'false');
		});
	}

	function navigate(url, options) {
		var settings = options || {};

		if (requestInFlight) {
			return Promise.resolve();
		}

		requestInFlight = true;
		document.documentElement.classList.add('rr-catalog-loading');
		setPropertiesLoadingState(true);

		return window.fetch(url, {
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('Catalog request failed');
				}

				return response.text();
			})
			.then(function (html) {
				var parser = new window.DOMParser();
				var doc = parser.parseFromString(html, 'text/html');

				replaceSearchBlocks(doc);
				replacePropertiesBlocks(doc);

				if (settings.pushState !== false) {
					window.history.pushState({}, '', url);
				}

				if (typeof window.RRInitCatalogSearch === 'function') {
					window.RRInitCatalogSearch(document);
				}

				if (typeof window.RRInitCatalogProperties === 'function') {
					window.RRInitCatalogProperties(document);
				}

				if (settings.scrollToResults) {
					var firstBlock = document.querySelector('[data-rr-properties-block]');
					if (firstBlock) {
						firstBlock.scrollIntoView({ behavior: 'smooth', block: 'start' });
					}
				}
			})
			.catch(function () {
				window.location.href = url;
			})
			.finally(function () {
				requestInFlight = false;
				document.documentElement.classList.remove('rr-catalog-loading');
				setPropertiesLoadingState(false);
			});
	}

	function buildFormUrl(form) {
		var action = form.getAttribute('action') || window.location.href;
		var url = new URL(action, window.location.origin);
		var elements = form.elements ? Array.prototype.slice.call(form.elements) : [];

		url.search = '';

		elements.forEach(function (field) {
			if (!field || !field.name || field.disabled) {
				return;
			}

			var tagName = String(field.tagName || '').toLowerCase();
			var type = String(field.type || '').toLowerCase();

			if ((type === 'checkbox' || type === 'radio') && !field.checked) {
				return;
			}

			if (tagName === 'select' && field.multiple) {
				Array.prototype.slice.call(field.options || []).forEach(function (option) {
					if (!option.selected) {
						return;
					}

					var optionValue = String(option.value || '').trim();

					if (optionValue !== '') {
						url.searchParams.append(field.name, optionValue);
					}
				});
				return;
			}

			var normalizedValue = String(field.value || '').trim();

			if (normalizedValue === '') {
				return;
			}

			url.searchParams.append(field.name, normalizedValue);
		});

		return url.toString();
	}

	function initInfiniteBlock(block) {
		if (!block || block.dataset.rrPropertiesInit === '1') {
			return;
		}

		block.dataset.rrPropertiesInit = '1';

		var mode = block.getAttribute('data-navigation-mode');
		if (mode !== 'infinite') {
			return;
		}

		var loadMore = block.querySelector('[data-rr-properties-load-more]');
		var grid = block.querySelector('[data-rr-properties-grid]');
		var blockId = block.getAttribute('data-rr-properties-block-id');
		var loading = false;

		if (!loadMore || !grid || !blockId) {
			return;
		}

		function updateLoadMore(nextBlock) {
			var nextLoadMore = nextBlock.querySelector('[data-rr-properties-load-more]');

			if (!nextLoadMore) {
				loadMore.remove();
				return;
			}

			loadMore.setAttribute('data-next-page-url', nextLoadMore.getAttribute('data-next-page-url') || '');
		}

		function appendItems(nextBlock) {
			var nextGrid = nextBlock.querySelector('[data-rr-properties-grid]');

			if (!nextGrid) {
				loadMore.remove();
				return;
			}

			Array.prototype.slice.call(nextGrid.children).forEach(function (child) {
				grid.appendChild(child);
			});
		}

		function loadNextPage() {
			var nextUrl = loadMore.getAttribute('data-next-page-url');

			if (loading || !nextUrl) {
				return;
			}

			loading = true;
			loadMore.classList.add('is-loading');

			window.fetch(nextUrl, { credentials: 'same-origin' })
				.then(function (response) {
					return response.text();
				})
				.then(function (html) {
					var parser = new window.DOMParser();
					var doc = parser.parseFromString(html, 'text/html');
					var selector = '[data-rr-properties-block-id="' + blockId + '"]';
					var nextBlock = doc.querySelector(selector);

					if (!nextBlock) {
						loadMore.remove();
						return;
					}

					appendItems(nextBlock);
					updateLoadMore(nextBlock);
				})
				.catch(function () {
					loadMore.classList.remove('is-loading');
				})
				.finally(function () {
					loading = false;
					if (document.body.contains(loadMore)) {
						loadMore.classList.remove('is-loading');
					}
				});
		}

		var button = loadMore.querySelector('[data-rr-properties-load-more-button]');
		if (button) {
			button.addEventListener('click', loadNextPage);
		}

		if ('IntersectionObserver' in window) {
			var observer = new window.IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (entry.isIntersecting) {
						loadNextPage();
					}
				});
			}, { rootMargin: '200px 0px' });

			observer.observe(loadMore);
		}
	}

	function initProperties(root) {
		var scope = root || document;
		var blocks = scope.querySelectorAll('[data-rr-properties-block]');

		Array.prototype.slice.call(blocks).forEach(initInfiniteBlock);
		initCardCurrencies(scope);
	}

	window.RRCatalogPage = {
		navigate: navigate,
		buildFormUrl: buildFormUrl
	};

	window.RRInitCatalogProperties = initProperties;

	window.addEventListener('realtrigel:currency-change', function (event) {
		var currency = event && event.detail && event.detail.currency ? event.detail.currency : getPreferredCurrency();
		initCardCurrencies(document);
		if (currency) {
			document.documentElement.setAttribute('data-rtg-currency', String(currency).toUpperCase());
		}
	});

	window.addEventListener('resize', function () {
		fitNegotiablePrices(document);
	});

	document.addEventListener('click', function (event) {
		var paginationLink = event.target.closest('.rr-properties-block__pagination a.page-numbers');

		if (paginationLink) {
			event.preventDefault();
			navigate(paginationLink.href, { pushState: true, scrollToResults: true });
		}
	});

	window.addEventListener('popstate', function () {
		navigate(window.location.href, { pushState: false, scrollToResults: false });
	});

	document.addEventListener('DOMContentLoaded', function () {
		initProperties(document);
	});
})();
