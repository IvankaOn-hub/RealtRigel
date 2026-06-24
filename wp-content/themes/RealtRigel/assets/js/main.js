/**
 * Main theme script.
 */

( function () {
	const root = document.documentElement;
	const header = document.querySelector( '[data-rtg-header]' );
	const menuToggle = document.querySelector( '[data-rtg-header-toggle]' );
	const menuPanel = document.querySelector( '[data-rtg-header-panel]' );
	const languageSelects = Array.from( document.querySelectorAll( '[data-rtg-language-select]' ) );
	const currencySelects = Array.from( document.querySelectorAll( '[data-rtg-currency-select]' ) );
	const storageKey = 'rtgPreferredCurrency';

	const applyCurrency = ( currencyCode ) => {
		const normalized = String( currencyCode || 'PLN' ).toUpperCase();

		root.setAttribute( 'data-rtg-currency', normalized );

		currencySelects.forEach( ( select ) => {
			select.value = normalized;
		} );

		try {
			window.localStorage.setItem( storageKey, normalized );
		} catch ( error ) {
			// Ignore storage errors.
		}

		window.dispatchEvent(
			new CustomEvent( 'realtrigel:currency-change', {
				detail: { currency: normalized }
			} )
		);
	};

	const openMenu = () => {
		if ( ! menuToggle || ! menuPanel ) {
			return;
		}

		menuToggle.setAttribute( 'aria-expanded', 'true' );
		menuPanel.hidden = false;
		menuPanel.classList.add( 'is-open' );
		document.body.classList.add( 'rtg-mobile-menu-open' );
	};

	const closeMenu = () => {
		if ( ! menuToggle || ! menuPanel ) {
			return;
		}

		menuToggle.setAttribute( 'aria-expanded', 'false' );
		menuPanel.hidden = true;
		menuPanel.classList.remove( 'is-open' );
		document.body.classList.remove( 'rtg-mobile-menu-open' );
	};

	if ( menuToggle && menuPanel ) {
		menuToggle.addEventListener( 'click', () => {
			if ( menuPanel.hidden ) {
				openMenu();
				return;
			}

			closeMenu();
		} );

		document.addEventListener( 'click', ( event ) => {
			if ( menuPanel.hidden || ! header ) {
				return;
			}

			if ( header.contains( event.target ) ) {
				return;
			}

			closeMenu();
		} );

		window.addEventListener( 'resize', () => {
			if ( window.innerWidth > 960 ) {
				closeMenu();
			}
		} );
	}

	currencySelects.forEach( ( select ) => {
		select.addEventListener( 'change', () => {
			applyCurrency( select.value );
		} );
	} );

	languageSelects.forEach( ( select ) => {
		select.addEventListener( 'change', () => {
			const nextUrl = String( select.value || '' );

			if ( nextUrl && nextUrl !== window.location.href ) {
				window.location.href = nextUrl;
			}
		} );
	} );

	try {
		applyCurrency( window.localStorage.getItem( storageKey ) || 'PLN' );
	} catch ( error ) {
		applyCurrency( 'PLN' );
	}
}() );


// SWIPER SLIDER
function initSwiperSliders() {
  const tabletBreakpoint = window.matchMedia("(max-width: 1024px)");
  const sliders = new Map();

  const sliderSettings = {

    featured: {
		always: true,
		slidesPerView: 1,
		spaceBetween: 16,
		breakpoints: {
			768: {
			slidesPerView: 1,
			spaceBetween: 20,
			},
			1024: {
			slidesPerView: 2,
			spaceBetween: 24,
			},
			1200: {
			slidesPerView: 3,
			spaceBetween: 32,
			},
		},
    },

	catalog: {
		always: true,
		slidesPerView: 1,
		spaceBetween: 16,
		breakpoints: {
			768: {
				slidesPerView: 2,
				spaceBetween: 20,
			},
			1024: {
			slidesPerView: 2,
			spaceBetween: 24,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 32,
			},
			1400: {
				slidesPerView: 4,
				spaceBetween: 32,
			},
		},
	},
    
    testimonials: {
		always: true,
		slidesPerView: 1,
		spaceBetween: 16,
		breakpoints: {
			768: {
				slidesPerView: 2,
				spaceBetween: 20,
			},
			1024: {
			slidesPerView: 2,
			spaceBetween: 24,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 32,
			},
			1400: {
				slidesPerView: 3,
				spaceBetween: 32,
			},
		},
	},
	blog: {
		always: true,
		slidesPerView: 1,
		spaceBetween: 16,
		breakpoints: {
			768: {
				slidesPerView: 2,
				spaceBetween: 24,
			},
			1200: {
				slidesPerView: 3,
				spaceBetween: 32,
			},
		},
	},
	team: {
		always: true,
		slidesPerView: 2,
		spaceBetween: 16,
		breakpoints: {
			768: {
				slidesPerView: 3,
				spaceBetween: 24,
			}
		},
		
	},
    
  };

  function initSliders() {
    const allSliders = document.querySelectorAll("[data-slider]");

    if (!allSliders.length || typeof Swiper === "undefined") return;

    allSliders.forEach((slider) => {
      const sliderName = slider.dataset.slider;
      const config = sliderSettings[sliderName];

      if (!config) return;

      const shouldAlwaysRun = config.always === true;
      const shouldRun = shouldAlwaysRun || tabletBreakpoint.matches;
	  const wrapper = slider.closest('.rtg-slider-wrap') || slider;



      if (shouldRun && !sliders.has(slider)) {
        const { always, ...swiperConfig } = config;

        const swiper = new Swiper(slider, {
          ...swiperConfig,
          speed: 600,
          grabCursor: true,
          watchOverflow: true,
          observer: true,
          observeParents: true,

          pagination: {
			el: wrapper.querySelector(".swiper-pagination"),
			clickable: true,
		},

		
		navigation: {
			nextEl: wrapper.querySelector('[data-slider-next]'),
			prevEl: wrapper.querySelector('[data-slider-prev]'),
		},
        });
		

        sliders.set(slider, swiper);
      }

      if (!shouldRun && sliders.has(slider)) {
        sliders.get(slider).destroy(true, true);
        sliders.delete(slider);
      }
    });
  }

  window.addEventListener("load", initSliders);
  window.addEventListener("resize", initSliders);

  initSliders();
}

initSwiperSliders();

