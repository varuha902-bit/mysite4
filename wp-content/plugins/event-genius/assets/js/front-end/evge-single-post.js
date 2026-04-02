jQuery(function($) {
	// Namespace our display element functionality
	window.EVGE = window.EVGE || {};
	window.EVGE.SinglePost = window.EVGE.SinglePost || {};

	// SinglePost element class
	window.EVGE.SinglePost.Element = function($self) {
		this.domElement = $self;
		this.category = typeof $self.attr('data-evge-type') !== 'undefined' ? $self.attr('data-evge-type') : 'standard';
	};

	window.EVGE.SinglePost.Element.prototype = {
		init: function() {
			this.setWidthClass();
			this.initRevealers();
		},

		afterResize: function() {
			this.setWidthClass();
		},

		setWidthClass: function() {
			var wideClass = 'evge-is-wide',
				smallClass = 'evge-is-small',
				narrowClass = 'evge-is-narrow',
				isSuperNarrow = 'evge-is-super-narrow';

			if (this.domElement.innerWidth() < 320) {
				this.domElement.removeClass(wideClass)
					.addClass(smallClass + ' ' + isSuperNarrow + ' ' + narrowClass);
			} else if (this.domElement.innerWidth() < 500) {
				this.domElement.removeClass(isSuperNarrow + ' ' + wideClass)
					.addClass(smallClass + ' ' + narrowClass);
			} else if (this.domElement.innerWidth() < 700) {
				this.domElement.removeClass(isSuperNarrow + ' ' + narrowClass + ' ' + wideClass)
					.addClass(smallClass);
			} else if (this.domElement.innerWidth() > 1000) {
				this.domElement.removeClass(smallClass + ' ' + isSuperNarrow + ' ' + narrowClass)
					.addClass(wideClass);
			} else {
				this.domElement.removeClass(smallClass + ' ' + isSuperNarrow + ' ' + narrowClass + ' ' + wideClass);
			}
		},

		initRevealers: function() {
			var self = this;
			this.domElement.find('.evge-show-link-wrap').each(function() {
				if ($(this).hasClass('evge-revealer-initted')) {
					return;
				}

				var $thisRevealer = $(this),
					$wrap = $(this).closest('.evge-has-hidden-content');

				$thisRevealer.addClass('evge-revealer-initted');

				$(this).on('click', function(event) {
					event.preventDefault();
					let $venueContext = $(this).closest('.evge-meta-venue-summary');

					if ($wrap.hasClass('evge-show-hidden-content')) {
						$wrap.removeClass('evge-show-hidden-content');
						$wrap.find('.evge-reveal-text, .evge-reveal-icon').show();
						$wrap.find('.evge-hide-text, .evge-hide-icon').hide();
						$wrap.find('.evge-hidden-content').slideUp();
					} else {
						$wrap.addClass('evge-show-hidden-content');
						$wrap.find('.evge-reveal-text, .evge-reveal-icon').hide();
						$wrap.find('.evge-hide-text, .evge-hide-icon').show();
						$wrap.find('.evge-hidden-content').slideDown(function() {
							self.handleMapIframe($venueContext);
						});
					}
				});
			});
		},

		handleMapIframe: function($venueContext) {
			if ($venueContext.find('.evge-map-placeholder').length) {
				let $placeholder = $venueContext.find('.evge-map-placeholder');
				let id = $placeholder.attr('data-id');

				if ($('#evge-venue-iframe-' + id).length) {
					$placeholder.before($('#evge-venue-iframe-' + id).clone());
					$placeholder.remove();
				} else {
					$placeholder.after('<iframe id="evge-venue-iframe-' + id + '" src="' + $placeholder.attr('data-src') + '" width="100%" height="100%" style="border:0;" allowfullscreen></iframe>');
					$('#evge-venue-iframe-' + id).on('load', function() {
						$placeholder.remove();
						$('#evge-venue-iframe-' + id).css('height', $venueContext.width() + 'px');
					});
				}
			}
		}
	};

	// SinglePost element initializer
	window.EVGE.SinglePost.Initializer = {
		init: function() {
			if (!this.checkDependencies()) return;
			window.EVGE.Hooks.addAction('evge_page_loading', this.handlePageLoading.bind(this));
		},

		checkDependencies: function() {
			if (typeof window.EVGE.Hooks === 'undefined') {
				console.error('EVGE SinglePost: Required dependencies not loaded');
				return false;
			}
			return true;
		},

		handlePageLoading: function($evge, index) {
			window.Evge.displayElements[index] = new window.EVGE.SinglePost.Element($evge);
			
			var evgeDelay = (function() {
				var evgeTimer = 0;
				return function(evgeCallback, evgeMs) {
					clearTimeout(evgeTimer);
					evgeTimer = setTimeout(evgeCallback, evgeMs);
				};
			})();

			$(window).on('resize', function() {
				evgeDelay(function() {
					window.Evge.displayElements[index].afterResize();
				}, 500);
			});

			window.Evge.displayElements[index].init();
		}
	};

	// Initialize the display element functionality
	window.EVGE.SinglePost.Initializer.init();

	// Make the display element class available globally for backward compatibility
	window.EvgeSinglePostElement = window.EVGE.SinglePost.Element;
});