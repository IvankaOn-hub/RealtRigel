/**
 * Catalog single page interactions.
 */

(function () {
	const shareData = window.RRTCatalogShare && typeof window.RRTCatalogShare === 'object' ? window.RRTCatalogShare : {};
	const labels = shareData.labels && typeof shareData.labels === 'object' ? shareData.labels : {};
	const getLabel = (key, fallback) => String(labels[key] || fallback);
	const fitNegotiableText = (node, options = {}) => {
		if (!node || !node.classList || !node.classList.contains('is-negotiable') || !node.parentElement) {
			return;
		}

		const maxSize = Number(options.maxSize || node.dataset.fitMaxSize || 46);
		const minSize = Number(options.minSize || node.dataset.fitMinSize || 18);
		const availableWidth = node.parentElement.clientWidth;

		if (!availableWidth) {
			return;
		}

		node.style.fontSize = maxSize + 'px';

		if (node.scrollWidth <= availableWidth) {
			return;
		}

		node.style.fontSize = Math.max(minSize, Math.floor(maxSize * (availableWidth / node.scrollWidth))) + 'px';
	};
	const fitNegotiablePrices = () => {
		document.querySelectorAll('.rtg-summary-price.is-negotiable').forEach((node) => {
			fitNegotiableText(node, { maxSize: 46, minSize: 20 });
		});
	};
	const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (character) => {
		const entities = {
			'&': '&amp;',
			'<': '&lt;',
			'>': '&gt;',
			'"': '&quot;',
			"'": '&#039;'
		};

		return entities[character] || character;
	});
	const escapeAttribute = escapeHtml;

	const priceBox = document.querySelector('[data-rtg-price-box]');
	if (priceBox) {
		const priceValue = priceBox.querySelector('[data-rtg-price-value]');
		const priceSummary = document.querySelector('[data-rtg-price-summary]');
		const priceMeter = priceBox.querySelector('[data-rtg-price-meter]');
		const apiBase = String(priceBox.getAttribute('data-nbp-api-base') || 'https://api.nbp.pl/api').replace(/\/$/, '');
		const basePrice = Number(priceBox.getAttribute('data-base-price') || '0');
		const baseArea = Number(priceBox.getAttribute('data-base-area') || '0');
		const shouldCalculateMeter = String(priceBox.getAttribute('data-calculate-meter') || '1') === '1';
		const baseCurrency = String(priceBox.getAttribute('data-base-currency') || 'USD').toUpperCase();
		const getPreferredCurrency = () => String(document.documentElement.getAttribute('data-rtg-currency') || 'PLN').toUpperCase();
		let ratesPromise = null;

		const formatAmount = (amount, currencyCode) => {
			const safeAmount = Number.isFinite(amount) ? amount : 0;

			return formatPriceLabel(String(Math.round(safeAmount)) + ' ' + currencyCode);
		};

		const formatPriceLabel = (label) => {
			const source = String(label || '');
			const match = source.match(/\d+/);

			if (!match) {
				return source.replace(/\s+/g, '&nbsp;');
			}

			let digits = match[0];
			const groups = [];
			const startIndex = match.index || 0;
			const prefix = source.slice(0, startIndex);
			const suffix = source.slice(startIndex + digits.length).replace(/^\s+/, '');

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
		};

		const setPriceText = (text) => {
			if (priceValue) {
				priceValue.innerHTML = text;
			}

			if (priceSummary) {
				priceSummary.innerHTML = text;
			}
		};

		const setMeterText = (amount, currencyCode) => {
			if (!priceMeter) {
				return;
			}

			if (!shouldCalculateMeter || !Number.isFinite(amount) || amount <= 0) {
				priceMeter.hidden = true;
				return;
			}

			priceMeter.hidden = false;
			priceMeter.textContent = '\u2248 ' + String(Math.round(amount)) + ' ' + currencyCode + ' / ' + getLabel('meterUnit', '\u043c\u00b2');
		};

		const loadRates = () => {
			if (ratesPromise) {
				return ratesPromise;
			}

			ratesPromise = fetch(apiBase + '/exchangerates/tables/A/?format=json', {
				method: 'GET',
				headers: {
					Accept: 'application/json'
				}
			})
				.then((response) => {
					if (!response.ok) {
						throw new Error('NBP request failed');
					}

					return response.json();
				})
				.then((payload) => {
					const table = Array.isArray(payload) ? payload[0] : null;
					const rates = { PLN: 1 };

					if (!table || !Array.isArray(table.rates)) {
						throw new Error('Invalid NBP payload');
					}

					table.rates.forEach((rate) => {
						if (!rate || typeof rate.code !== 'string' || typeof rate.mid !== 'number') {
							return;
						}

						rates[String(rate.code).toUpperCase()] = rate.mid;
					});

					return rates;
				})
				.catch((error) => {
					ratesPromise = null;
					throw error;
				});

			return ratesPromise;
		};

		const renderPrice = (amount, currencyCode) => {
			setPriceText(formatAmount(amount, currencyCode));

			if (shouldCalculateMeter && Number.isFinite(baseArea) && baseArea > 0) {
				setMeterText(Math.ceil(amount / baseArea), currencyCode);
			} else {
				setMeterText(0, currencyCode);
			}
		};

		const convertPrice = (targetCurrency) => {
			if (!Number.isFinite(basePrice) || basePrice <= 0) {
				setPriceText(getLabel('negotiable', 'Negotiable'));
				if (priceValue) {
					priceValue.classList.add('is-negotiable');
				}
				if (priceSummary) {
					priceSummary.classList.add('is-negotiable');
				}
				fitNegotiablePrices();
				setMeterText(0, targetCurrency);
				return Promise.resolve();
			}

			if (targetCurrency === baseCurrency) {
				renderPrice(basePrice, baseCurrency);
				return Promise.resolve();
			}

			return loadRates()
				.then((rates) => {
					const sourceRate = rates[baseCurrency];
					const targetRate = rates[targetCurrency];

					if (typeof sourceRate !== 'number' || typeof targetRate !== 'number' || sourceRate <= 0 || targetRate <= 0) {
						throw new Error('Unsupported currency rate');
					}

					renderPrice(Math.ceil((basePrice * sourceRate) / targetRate), targetCurrency);
				})
				.catch(() => {
					renderPrice(basePrice, baseCurrency);
				});
		};

		window.addEventListener('realtrigel:currency-change', (event) => {
			const currency = event && event.detail && event.detail.currency ? event.detail.currency : getPreferredCurrency();
			void convertPrice(String(currency || 'PLN').toUpperCase());
		});
		window.addEventListener('resize', fitNegotiablePrices);

		void convertPrice(getPreferredCurrency());
		fitNegotiablePrices();
	}

	const mapNode = document.querySelector('[data-rtg-map]');
	const mapDataNode = document.querySelector('[data-rtg-map-data]');
	if (mapNode && mapDataNode && window.L) {
		let mapData = null;

		try {
			mapData = JSON.parse(mapDataNode.textContent || '{}');
		} catch (error) {
			mapData = null;
		}

		if (mapData && Number.isFinite(Number(mapData.lat)) && Number.isFinite(Number(mapData.lng))) {
			const leafletMap = window.L.map(mapNode, {
				scrollWheelZoom: false,
				zoomControl: true
			});

			window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
				attribution: '&copy; OpenStreetMap contributors',
				maxZoom: 19
			}).addTo(leafletMap);

			leafletMap.setView([Number(mapData.lat), Number(mapData.lng)], Number(mapData.zoom || 14));

			if (mapData.marker) {
				window.L.marker([Number(mapData.lat), Number(mapData.lng)]).addTo(leafletMap);
			}

			const syncMapSize = () => leafletMap.invalidateSize();
			window.addEventListener('resize', syncMapSize);
			window.setTimeout(syncMapSize, 180);
		}
	}

	const galleryRoot = document.querySelector('[data-rtg-gallery]');
	if (galleryRoot) {
		const slides = Array.from(galleryRoot.querySelectorAll('[data-slide-index]'));
		const dots = Array.from(galleryRoot.querySelectorAll('[data-gallery-dot]'));
		const prev = galleryRoot.querySelector('[data-gallery-prev]');
		const next = galleryRoot.querySelector('[data-gallery-next]');
		const openTrigger = galleryRoot.querySelector('[data-gallery-open]');
		const stage = galleryRoot.querySelector('.rtg-gallery-stage');
		let active = 0;

		const render = (index) => {
			active = (index + slides.length) % slides.length;

			slides.forEach((slide) => {
				const slideIndex = Number(slide.getAttribute('data-slide-index'));
				slide.classList.toggle('is-active', slideIndex === active);
			});

			dots.forEach((dot) => {
				const dotIndex = Number(dot.getAttribute('data-gallery-dot'));
				dot.classList.toggle('is-active', dotIndex === active);
			});
		};

		dots.forEach((dot) => {
			dot.addEventListener('click', () => {
				render(Number(dot.getAttribute('data-gallery-dot')));
			});
		});

		if (prev) {
			prev.addEventListener('click', () => render(active - 1));
		}

		if (next) {
			next.addEventListener('click', () => render(active + 1));
		}

		const lightbox = document.querySelector('[data-rtg-lightbox]');
		if (lightbox) {
			const lightboxSlides = Array.from(lightbox.querySelectorAll('[data-lightbox-slide]'));
			const lightboxDots = Array.from(lightbox.querySelectorAll('[data-lightbox-dot]'));
			const lightboxPrev = lightbox.querySelector('[data-lightbox-prev]');
			const lightboxNext = lightbox.querySelector('[data-lightbox-next]');
			const lightboxClose = lightbox.querySelectorAll('[data-lightbox-close]');
			let lightboxActive = 0;
			let returnFocusTo = null;

			const stopVideos = (scope) => {
				const root = scope || lightbox;
				const videos = root ? root.querySelectorAll('video') : [];

				videos.forEach((video) => {
					try {
						video.pause();
						video.currentTime = 0;
					} catch (error) {
						// Ignore media reset errors.
					}
				});
			};

			const renderLightbox = (index) => {
				lightboxActive = (index + lightboxSlides.length) % lightboxSlides.length;

				lightboxSlides.forEach((slide) => {
					const slideIndex = Number(slide.getAttribute('data-lightbox-slide'));
					if (slideIndex !== lightboxActive) {
						stopVideos(slide);
					}
					slide.classList.toggle('is-active', slideIndex === lightboxActive);
				});

				lightboxDots.forEach((dot) => {
					const dotIndex = Number(dot.getAttribute('data-lightbox-dot'));
					dot.classList.toggle('is-active', dotIndex === lightboxActive);
				});
			};

			const openLightbox = () => {
				returnFocusTo = openTrigger || document.activeElement;
				renderLightbox(active);
				lightbox.hidden = false;
				document.body.classList.add('rtg-lightbox-open');
			};

			const closeLightbox = () => {
				stopVideos();
				lightbox.hidden = true;
				document.body.classList.remove('rtg-lightbox-open');
				render(active);

				if (returnFocusTo && typeof returnFocusTo.focus === 'function') {
					returnFocusTo.focus();
				}
			};

			if (openTrigger) {
				openTrigger.addEventListener('click', openLightbox);
				openTrigger.addEventListener('keydown', (event) => {
					if (event.key === 'Enter' || event.key === ' ') {
						event.preventDefault();
						openLightbox();
					}
				});
			}

			if (stage) {
				stage.addEventListener('click', (event) => {
					if (event.target.closest('[data-gallery-open], [data-gallery-prev], [data-gallery-next], [data-gallery-dot]')) {
						return;
					}

					if (!event.target.closest('.rtg-slide')) {
						return;
					}

					openLightbox();
				});
			}

			lightboxDots.forEach((dot) => {
				dot.addEventListener('click', () => {
					renderLightbox(Number(dot.getAttribute('data-lightbox-dot')));
				});
			});

			if (lightboxPrev) {
				lightboxPrev.addEventListener('click', () => renderLightbox(lightboxActive - 1));
			}

			if (lightboxNext) {
				lightboxNext.addEventListener('click', () => renderLightbox(lightboxActive + 1));
			}

			lightboxClose.forEach((closeTrigger) => {
				closeTrigger.addEventListener('click', closeLightbox);
			});

			document.addEventListener('keydown', (event) => {
				if (lightbox.hidden) {
					return;
				}

				if (event.key === 'Escape') {
					closeLightbox();
					return;
				}

				if (event.key === 'ArrowLeft') {
					renderLightbox(lightboxActive - 1);
					return;
				}

				if (event.key === 'ArrowRight') {
					renderLightbox(lightboxActive + 1);
				}
			});
		}
	}

	const relatedSlider = document.querySelector('[data-rtg-related-slider]');
	if (relatedSlider) {
		const track = relatedSlider.querySelector('[data-rtg-related-track]');
		const cards = track ? Array.from(track.querySelectorAll('.rtg-related-card')) : [];
		const prev = relatedSlider.querySelector('[data-rtg-related-prev]');
		const next = relatedSlider.querySelector('[data-rtg-related-next]');
		const controls = relatedSlider.querySelector('.rtg-related-slider-controls');
		let index = 0;

		if (track && cards.length && prev && next) {
			const getVisibleCount = () => {
				if (window.innerWidth <= 575) {
					return 1;
				}

				if (window.innerWidth <= 767) {
					return 2;
				}

				if (window.innerWidth <= 1199) {
					return 3;
				}

				return 4;
			};

			const renderRelated = () => {
				const visibleCount = getVisibleCount();
				const maxIndex = Math.max(0, cards.length - visibleCount);
				const hasOverflow = cards.length > visibleCount;
				index = Math.max(0, Math.min(index, maxIndex));

				if (controls) {
					controls.style.display = hasOverflow ? '' : 'none';
				}

				prev.style.display = hasOverflow ? '' : 'none';
				next.style.display = hasOverflow ? '' : 'none';

				if (!hasOverflow) {
					track.style.transform = 'translateX(0)';
					prev.disabled = true;
					next.disabled = true;
					return;
				}

				const cardWidth = cards[0].getBoundingClientRect().width;
				const gap = parseFloat(window.getComputedStyle(track).columnGap || window.getComputedStyle(track).gap || '0') || 0;
				const offset = (cardWidth + gap) * index;

				track.style.transform = 'translateX(-' + offset + 'px)';
				prev.disabled = index <= 0;
				next.disabled = index >= maxIndex;
			};

			prev.addEventListener('click', () => {
				index -= 1;
				renderRelated();
			});

			next.addEventListener('click', () => {
				index += 1;
				renderRelated();
			});

			window.addEventListener('resize', renderRelated);
			renderRelated();
		}
	}

	const summaryActions = document.querySelector('.rtg-summary-actions');
	if (summaryActions && !summaryActions.querySelector('[data-rtg-share-open]')) {
		const shareButton = document.createElement('button');
		shareButton.type = 'button';
		shareButton.className = 'rtg-button rtg-button--secondary';
		shareButton.setAttribute('data-rtg-share-open', '');
		shareButton.textContent = getLabel('shareButton', 'Share');

		const contactButton = summaryActions.querySelector('[data-rtg-contact-open]');
		if (contactButton && contactButton.nextSibling) {
			summaryActions.insertBefore(shareButton, contactButton.nextSibling);
		} else {
			summaryActions.appendChild(shareButton);
		}
	}

	let shareModal = document.querySelector('[data-rtg-share-modal]');
	if (!shareModal && document.body) {
		const shareMarkup = [
			'<div class="rtg-share-modal" data-rtg-share-modal hidden>',
			'	<div class="rtg-share-backdrop" data-rtg-share-close></div>',
			'	<div class="rtg-share-dialog" role="dialog" aria-modal="true" aria-labelledby="rtg-share-title">',
			'		<div class="rtg-share-header">',
			'			<div>',
			'				<h3 id="rtg-share-title">' + escapeHtml(getLabel('shareTitle', 'Share property')) + '</h3>',
			'				<p>' + escapeHtml(getLabel('shareDescription', 'Choose a sharing method or copy the property link.')) + '</p>',
			'			</div>',
			'			<button type="button" class="rtg-share-close" data-rtg-share-close aria-label="' + escapeAttribute(getLabel('close', 'Close')) + '">&times;</button>',
			'		</div>',
			'		<div class="rtg-share-actions">',
			'			<button type="button" class="rtg-share-action" data-share-network="copy-link">' + escapeHtml(getLabel('copyLink', 'Copy link')) + '</button>',
			'			<button type="button" class="rtg-share-action" data-share-network="telegram">Telegram</button>',
			'			<button type="button" class="rtg-share-action" data-share-network="whatsapp">WhatsApp</button>',
			'			<button type="button" class="rtg-share-action" data-share-network="viber">Viber</button>',
			'			<button type="button" class="rtg-share-action" data-share-network="facebook">Facebook</button>',
			'			<button type="button" class="rtg-share-action" data-share-network="instagram">Instagram</button>',
			'		</div>',
			'		<p class="rtg-share-status" data-rtg-share-status hidden></p>',
			'	</div>',
			'</div>'
		].join('');

		document.body.insertAdjacentHTML('beforeend', shareMarkup);
		shareModal = document.querySelector('[data-rtg-share-modal]');
	}

	if (shareModal) {
		const openButtons = document.querySelectorAll('[data-rtg-share-open]');
		const closeButtons = shareModal.querySelectorAll('[data-rtg-share-close]');
		const actionButtons = shareModal.querySelectorAll('[data-share-network]');
		const statusNode = shareModal.querySelector('[data-rtg-share-status]');
		const shareTitle = String(shareData.title || document.title || '').trim();
		const shareDescription = String(shareData.description || '').trim();
		const shareUrl = String(shareData.url || window.location.href || '').trim();
		const shareText = [shareDescription, shareUrl].filter(Boolean).join('\n\n');
		let returnFocusTo = null;

		const setStatus = (message, isError) => {
			if (!statusNode) {
				return;
			}

			statusNode.textContent = message || '';
			statusNode.hidden = !message;
			statusNode.classList.toggle('is-error', Boolean(message) && Boolean(isError));
		};

		const openModal = (trigger) => {
			returnFocusTo = trigger || document.activeElement;
			shareModal.hidden = false;
			document.body.classList.add('rtg-share-modal-open');
			setStatus('');
		};

		const closeModal = () => {
			shareModal.hidden = true;
			document.body.classList.remove('rtg-share-modal-open');
			setStatus('');

			if (returnFocusTo && typeof returnFocusTo.focus === 'function') {
				returnFocusTo.focus();
			}
		};

		const copyText = (text, successMessage) => {
			if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') {
				setStatus(getLabel('copyFailed', 'Could not copy automatically. Copy the text manually.'), true);
				return Promise.resolve(false);
			}

			return navigator.clipboard.writeText(text)
				.then(() => {
					setStatus(successMessage, false);
					return true;
				})
				.catch(() => {
					setStatus(getLabel('copyFailed', 'Could not copy automatically. Copy the text manually.'), true);
					return false;
				});
		};

		const buildNetworkUrl = (network) => {
			switch (network) {
				case 'telegram':
					return 'https://t.me/share/url?url=' + encodeURIComponent(shareUrl) + '&text=' + encodeURIComponent(shareDescription || shareTitle);
				case 'whatsapp':
					return 'https://wa.me/?text=' + encodeURIComponent(shareText || shareUrl);
				case 'viber':
					return 'viber://forward?text=' + encodeURIComponent(shareText || shareUrl);
				case 'facebook':
					return 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl);
				case 'instagram':
					return 'https://www.instagram.com/';
				default:
					return '';
			}
		};

		openButtons.forEach((button) => {
			button.addEventListener('click', () => openModal(button));
		});

		closeButtons.forEach((button) => {
			button.addEventListener('click', closeModal);
		});

		actionButtons.forEach((button) => {
			button.addEventListener('click', () => {
				const network = String(button.getAttribute('data-share-network') || '').trim();

				if (network === 'copy-link') {
					void copyText(shareUrl, getLabel('linkCopied', 'Link copied.'));
					return;
				}

				if (network === 'instagram') {
					void copyText(shareText || shareUrl, getLabel('instagramCopied', 'Text copied. Open Instagram and paste it into your post.'));
					window.open(buildNetworkUrl(network), '_blank', 'noopener,noreferrer');
					closeModal();
					return;
				}

				const targetUrl = buildNetworkUrl(network);
				if (!targetUrl) {
					return;
				}

				window.open(targetUrl, '_blank', 'noopener,noreferrer');
				closeModal();
			});
		});

		document.addEventListener('keydown', (event) => {
			if (shareModal.hidden) {
				return;
			}

			if (event.key === 'Escape') {
				closeModal();
			}
		});
	}

	const contactModal = document.querySelector('[data-rtg-contact-modal]');
	if (contactModal) {
		const openButtons = document.querySelectorAll('[data-rtg-contact-open]');
		const closeButtons = contactModal.querySelectorAll('[data-rtg-contact-close]');
		const form = contactModal.querySelector('[data-rtg-contact-form]');
		const errorBox = contactModal.querySelector('[data-rtg-contact-error]');
		const successBox = contactModal.querySelector('[data-rtg-contact-success]');
		const submitButton = form ? form.querySelector('[type="submit"]') : null;
		const requiredFields = {
			name: form ? form.querySelector('[name="contact_name"]') : null,
			phone: form ? form.querySelector('[name="contact_phone"]') : null,
			email: form ? form.querySelector('[name="contact_email"]') : null,
			telegram: form ? form.querySelector('[name="contact_telegram"]') : null
		};
		let returnFocusTo = null;
		let isSubmitting = false;

		const setError = (message) => {
			if (!errorBox) {
				return;
			}

			errorBox.textContent = message || '';
			errorBox.hidden = !message;
		};

		const openModal = (trigger) => {
			returnFocusTo = trigger || document.activeElement;
			contactModal.hidden = false;
			document.body.classList.add('rtg-contact-modal-open');
			setError('');

			if (successBox) {
				successBox.hidden = true;
			}

			if (requiredFields.name) {
				window.setTimeout(() => requiredFields.name.focus(), 20);
			}
		};

		const closeModal = () => {
			contactModal.hidden = true;
			document.body.classList.remove('rtg-contact-modal-open');
			setError('');

			if (returnFocusTo && typeof returnFocusTo.focus === 'function') {
				returnFocusTo.focus();
			}
		};

		const hasContactMethod = () => {
			return ['phone', 'email', 'telegram'].some((key) => {
				const field = requiredFields[key];
				return field && String(field.value || '').trim() !== '';
			});
		};

		openButtons.forEach((button) => {
			button.addEventListener('click', () => openModal(button));
		});

		closeButtons.forEach((button) => {
			button.addEventListener('click', closeModal);
		});

		document.addEventListener('keydown', (event) => {
			if (contactModal.hidden) {
				return;
			}

			if (event.key === 'Escape') {
				closeModal();
			}
		});

		if (form) {
			form.addEventListener('submit', (event) => {
				if (isSubmitting) {
					event.preventDefault();
					return;
				}

				const nameValue = requiredFields.name ? String(requiredFields.name.value || '').trim() : '';

				if (!nameValue || !hasContactMethod()) {
					event.preventDefault();
					setError(getLabel('contactRequired', 'Enter your name and at least one contact method: phone, email, or Telegram.'));
					return;
				}

				setError('');
				isSubmitting = true;
				form.setAttribute('aria-busy', 'true');

				if (submitButton) {
					submitButton.disabled = true;
					submitButton.classList.add('is-loading');
				}
			});
		}

		const url = new URL(window.location.href);
		const status = url.searchParams.get('rtg_contact_status');
		if (status === 'success') {
			if (form) {
				form.reset();
			}
		}

		if (status === 'error') {
			openModal(null);
			setError(getLabel('contactError', 'The form was not sent. Enter your name and at least one contact method.'));
		}

		if (status === 'success' || status === 'error') {
			url.searchParams.delete('rtg_contact_status');
			url.searchParams.delete('rtg_contact_error');
			window.history.replaceState({}, '', url.toString());
		}
	}
})();
