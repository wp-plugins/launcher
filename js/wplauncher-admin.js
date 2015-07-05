(function($){
	$(document).ready(function() {
		$('.wplauncher-nav-tab-wrapper a').click(function(e) {
			e.preventDefault();
			var $this = $(this);
			$this.addClass('nav-tab-active').siblings().removeClass('nav-tab-active');
			$($this.data('rel')).show().siblings().hide();
			window.location.hash = $this.data('rel').replace('wplauncher-settings-', '');
		});
		if (window.location.hash) {
			var $nav_tabs = $('.wplauncher-nav-tab-wrapper');
			$nav_tabs.find('#wplauncher-tab-'+window.location.hash.substring(1)).click();
		}

		$('.wplauncher-template-select').change(function(event) {
			var possible_features = ['countdown', 'subscribe', 'twitter', 'contact', 'social'];
			var template_features = $(this).next('label').find('.wplauncher-template-supports').val();
			$.each(possible_features, function(index, feature) {
				 if (template_features.indexOf(feature) > -1) {
				 	// supports
				 	$('#wplauncher-settings-'+feature).find('.wplauncher-error').hide();
				 	$('#wplauncher-tab-'+feature).removeClass('wplauncher-not-supported');
				 } else {
				 	// doesn't support
				 	$('#wplauncher-settings-'+feature).find('.wplauncher-error').show();
				 	$('#wplauncher-tab-'+feature).addClass('wplauncher-not-supported');
				 }
			});
		});

		$('#wplauncher_options-countdown-date_formatted').datetimepicker({
			timeFormat: 'HH:mm',
			stepHour: 1,
			stepMinute: 1,
			showMicrosec: false,
			showMillisec: false,
			onSelect: function() {
				$('#wplauncher_options-countdown-date').val($(this).datetimepicker("getDate").getTime() / 1000);
			}
		});
		$(window).load(function() {
		   $('#ui-datepicker-div').wrap('<div class="ll-skin-melon"></div>');
		});

		$('#wplauncher_options-subscribe-service').change(function(event) {
			$('.show-if-subscribe').hide();
			$('.if-'+$(this).val()).show();
		}).change();


		// Subscribe stuff is hard :'(

		// Get all MailChimp Lists
		$('#wplauncher_options-subscribe-mailchimp-api_key').keyup(function(){

			// Do nothing if we are already retrieve the lists
			if ($('#wplauncher_options-subscribe-mailchimp-list-spinner').length != 0) {
				return;
			}

			$('<i id="wplauncher_options-subscribe-mailchimp-list-spinner" class="wplauncher-icon-spin animate-spin"></i>').insertAfter('#wplauncher_options-subscribe-mailchimp-list');

			var data = {
				'action': 'wplauncher_get_mailchimp_lists',
				'api_key': $(this).val()
			};
			
			$.post( ajaxurl, data, function(response) {
				$('#wplauncher_options-subscribe-mailchimp-list-spinner').remove();
				$('#wplauncher_options-subscribe-mailchimp-list').html(response)
					.find('option[value="'+$('#wplauncher_options-subscribe-mailchimp-list').data('selected')+'"]').prop('selected', true);
			});
		});

		// Get Aweber Lists
		$('.wplauncher-aweber-connect').click(function(){
			$('tr.wplauncher_options-subscribe-aweber-code').removeClass('hidden');
		});

		$('#wplauncher_options-subscribe-aweber-code').keyup(function (){

			// Do nothing if we are already retrieve the lists
			if ($('#wplauncher_options-subscribe-aweber-list-spinner').length != 0) {
				return;
			}

			// Do nothing if the user did not input a code
			if ($('#wplauncher_options-subscribe-aweber-code').val() == '') {
				return;
			}

			$('#wplauncher_options-subscribe-aweber-list').html('');

			$('<i id="wplauncher_options-subscribe-aweber-list-spinner" class="wplauncher-icon-spin animate-spin"></i>').insertAfter('#wplauncher_options-subscribe-aweber-list');

			var data = {
				'action': 'wplauncher_get_aweber_lists',
				'consumer_key': $('#wplauncher_options-subscribe-aweber-consumer_key').val(),
				'consumer_secret': $('#wplauncher_options-subscribe-aweber-consumer_secret').val(),
				'access_key': $('#wplauncher_options-subscribe-aweber-access_key').val(),
				'access_secret': $('#wplauncher_options-subscribe-aweber-access_secret').val(),
				'code': $('#wplauncher_options-subscribe-aweber-code').val(),
			};
			if (aweber_init)
				data.list = $('#wplauncher_options-subscribe-aweber-list').data('selected');
			
			$.post( ajaxurl, data, function(response) {

				response = $.parseJSON(response);

				$('#wplauncher_options-subscribe-aweber-list-spinner').remove();
				$('#wplauncher_options-subscribe-aweber-list').html(response.html)
					.find('option[value="'+$('#wplauncher_options-subscribe-aweber-list').data('selected')+'"]').prop('selected', true);
				$('#wplauncher_options-subscribe-aweber-consumer_key').val(response.consumer_key);
				$('#wplauncher_options-subscribe-aweber-consumer_secret').val(response.consumer_secret);
				$('#wplauncher_options-subscribe-aweber-access_key').val(response.access_key);
				$('#wplauncher_options-subscribe-aweber-access_secret').val(response.access_secret);

				if (response.consumer_key != '') {
					$('tr.wplauncher_options-subscribe-aweber-code').addClass('hidden');
				}

			});
		});

		// Get all Get Response Lists
		$('#wplauncher_options-subscribe-getresponse-api_key').keyup(function(){

			// Do nothing if we are already retrieve the lists
			if ($('#wplauncher_options-subscribe-getresponse-list-spinner').length != 0) {
				return;
			}

			$('<i id="wplauncher_options-subscribe-getresponse-list-spinner" class="wplauncher-icon-spin animate-spin"></i>').insertAfter('#wplauncher_options-subscribe-getresponse-campaign');

			var data = {
				'action': 'wplauncher_get_getresponse_lists',
				'api_key': $('#wplauncher_options-subscribe-getresponse-api_key').val()
			};
			
			$.post( ajaxurl, data, function(response) {
				$('#wplauncher_options-subscribe-getresponse-list-spinner').remove();
				$('#wplauncher_options-subscribe-getresponse-campaign').html(response)
					.find('option[value="'+$('#wplauncher_options-subscribe-getresponse-campaign').data('selected')+'"]').prop('selected', true);
			});
		});

		// Get all Campaign Monitor Clients and Lists
		$('#wplauncher_options-subscribe-campaignmonitor-api_key').keyup(function(){

			// Do nothing if we are already retrieve the lists
			if ($('.wplauncher_options-subscribe-campaignmonitor-list-spinner').length != 0) {
				return;
			}

			$('<i class="wplauncher_options-subscribe-campaignmonitor-list-spinner wplauncher-icon-spin animate-spin"></i>').insertAfter('#wplauncher_options-subscribe-campaignmonitor-list, #wplauncher_options-subscribe-campaignmonitor-client');

			var data = {
				'action': 'wplauncher_get_campaignmonitor_lists',
				'api_key': $('#wplauncher_options-subscribe-campaignmonitor-api_key').val(),
			};
			
			$.post( ajaxurl, data, function(response) {

				response = $.parseJSON(response);

				$('.wplauncher_options-subscribe-campaignmonitor-list-spinner').remove();
				$('#wplauncher_options-subscribe-campaignmonitor-client').html(response.clients)
					.find('option[value="'+$('#wplauncher_options-subscribe-campaignmonitor-client').data('selected')+'"]').prop('selected', true);
				$('#wplauncher_options-subscribe-campaignmonitor-list').html(response.lists)
					.find('option[value="'+$('#wplauncher_options-subscribe-campaignmonitor-list').data('selected')+'"]').prop('selected', true);
			});
		});

		// Update lists for Campaign Monitor
		$('#wplauncher_options-subscribe-campaignmonitor-client').change(function(){

			// Do nothing if we have already retrieved the lists
			if ($('.wplauncher_options-subscribe-campaignmonitor-list-spinner').length != 0) {
				return;
			}

			$('<i class="wplauncher_options-subscribe-campaignmonitor-list-spinner wplauncher-icon-spin animate-spin"></i>').insertAfter('#wplauncher_options-subscribe-campaignmonitor-list');

			var data = {
				'action': 'wplauncher_update_campaignmonitor_lists',
				'api_key': $('#wplauncher_options-subscribe-campaignmonitor-api_key').val(),
				'client_id': $(this).val(),
			};
			
			$.post( ajaxurl, data, function(response) {
				$('.wplauncher_options-subscribe-campaignmonitor-list-spinner').remove();
				$('#wplauncher_options-subscribe-campaignmonitor-list').html(response)
					.find('option[value="'+$('#wplauncher_options-subscribe-campaignmonitor-list').data('selected')+'"]').prop('selected', true);
			});
		});

		// Get all Mad Mimi Lists
		$('#wplauncher_options-subscribe-madmimi-api_key').keyup(function(){

			// Do nothing if we are already retrieve the lists
			if ($('#wplauncher_options-subscribe-madmimi-list-spinner').length != 0) {
				return;
			}

			$('<i class="wplauncher_options-subscribe-madmimi-list-spinner wplauncher-icon-spin animate-spin"></i>').insertAfter('#wplauncher_options-subscribe-madmimi-list');

			var data = {
				'action': 'wplauncher_get_madmimi_lists',
				'api_key': $('#wplauncher_options-subscribe-madmimi-api_key').val(),
				'username': $('#wplauncher_options-subscribe-madmimi-username').val(),
				'list': $('#wplauncher_options-subscribe-madmimi-list').val()
			};
			
			$.post( ajaxurl, data, function(response) {
				$('.wplauncher_options-subscribe-madmimi-list-spinner').remove();
				$('#wplauncher_options-subscribe-madmimi-list').html(response)
					.find('option[value="'+$('#wplauncher_options-subscribe-madmimi-list').data('selected')+'"]').prop('selected', true);
			});
		});

		// Async get subscribe data
		var aweber_init = true;
		setTimeout(function() {
			if ($('#wplauncher_options-subscribe-mailchimp-api_key').val() != '')
				$('#wplauncher_options-subscribe-mailchimp-api_key').trigger('keyup');

			if ($('#wplauncher_options-subscribe-aweber-code').val() != '') {
				$('#wplauncher_options-subscribe-aweber-code').trigger('keyup');
				aweber_init = false;
			} else {
				aweber_init = false;
			}

			if ($('#wplauncher_options-subscribe-getresponse-api_key').val() != '')
				$('#wplauncher_options-subscribe-getresponse-api_key').trigger('keyup');

			if ($('#wplauncher_options-subscribe-campaignmonitor-api_key').val() != '')
				$('#wplauncher_options-subscribe-campaignmonitor-api_key').trigger('keyup');

			if ($('#wplauncher_options-subscribe-madmimi-api_key').val() != '')
				$('#wplauncher_options-subscribe-madmimi-api_key').trigger('keyup');
		}, 100);
	});
})(jQuery);
