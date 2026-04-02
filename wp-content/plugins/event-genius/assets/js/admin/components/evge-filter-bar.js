jQuery(document).ready(function($) {
	window.evgeAdminFilterBarInit = function() {
		window.EvgeAdminFilterBar = new EvgeAdminFilterBar();
		window.EvgeAdminFilterBar.init();
	};

	function EvgeAdminFilterBar() {
		this.$element = $('.evge-toolbar');

	}

	EvgeAdminFilterBar.prototype = {
		init: function () {
			this.addFilterListeners();
		},
		addFilterListeners() {
			var self = this;
			self.$element.find('select[name=qtype]').on('change', function() {
				if ( $(this).val() === 'custom' ) {
					self.$element.find('input[name=start]').show();
				} else {
					self.$element.find('input[name=start]').hide();
				}
			});

			self.$element.find('#evge-search-input').on('focus',function() {
				self.$element.find('.evge-search-type-wrap').slideDown();
			});

		}
	};

	window.evgeAdminFilterBarInit();
});