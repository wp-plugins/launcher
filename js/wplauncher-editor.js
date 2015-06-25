(function($){

	$(document).ready(function() {
		$('body').addClass('wplauncher-editor');
		$('.wplauncher-editable').each(function(index, el) {
			var $this = $(this);

			// Text
			if ($this.hasClass('wplauncher-editable-text')) {
				var edit_type = 'text';
				if ($this.hasClass('wplauncher-editable-textarea'))
					edit_type = 'textarea';
				$this.click(function() {
					wplauncher_cancel_color($('.wplauncher-color-wrapper'));
				}).editable(ajax_save_url, {
					submitdata : function(value, settings) {
						return { template: wplauncher_template };
					},
					callback    : function(value, settings) {
						if (value == '') {
							$this.addClass('wplauncher-text-placeholder');
						} else {
							$this.removeClass('wplauncher-text-placeholder');
						}
						return value;
					},
					// needs i18n
					indicator : 'Saving...',
					submit: '<div class="wplauncher-clear"></div><a class="wplauncher-editable-save"><i class="wplauncher-icon-ok"></i></a>',
					cancel: '<a class="wplauncher-editable-cancel"><i class="wplauncher-icon-cancel"></i></a>',
					tooltip   : 'Click to edit',
					placeholder: 'Click to edit',
					type: edit_type
				});
			}

			// Hideable
			if ($this.hasClass('wplauncher-hideable')) {
				$this.wrap('<span class="wplauncher-hideable-wrapper"></span>');
				var $wrap_hideable = $this.closest('.wplauncher-hideable-wrapper');
				
				// account for block element
				if ($this.css('display') == 'block') $wrap_hideable.css('display', 'block');
				
				// needs i18n				
				$wrap_hideable.addClass($this.data('hideid')).append('<a class="wplauncher-hideable-toggle" href="#" title="Show/hide element"><i class="wplauncher-icon-eye"></i></a>');
				var is_hidden = $this.hasClass('wplauncher-hidden');
				if (is_hidden) {
					$wrap_hideable.addClass('wplauncher-wrapper-hidden').find('.wplauncher-hideable-toggle i').removeClass('wplauncher-icon-eye').addClass('wplauncher-icon-eye-off');
				}
				$wrap_hideable.find('.wplauncher-hideable-toggle').click(function(event) {
					event.preventDefault();
					wplauncher_cancel_color($('.wplauncher-color-wrapper'));
					if ($(this).find('i').hasClass('wplauncher-icon-eye')) {
						wplauncher_hide($wrap_hideable);
					} else {
						wplauncher_show($wrap_hideable);
					}
				});
			}
			
			// Section
			if ($this.hasClass('wplauncher-section')) {
				var is_hidden = $this.hasClass('wplauncher-hidden-section');
				$this.find('.wplauncher-hideable-toggle').click(function(event) {
					event.preventDefault();
					wplauncher_cancel_color($('.wplauncher-color-wrapper'));
					if ($(this).find('i').hasClass('wplauncher-icon-eye')) {
						wplauncher_hide_section($this);
					} else {
						wplauncher_show_section($this);
					}
				});
				$this.find('.wplauncher-bg-edit').click(function (e) {
			    	if ($this.css('background-image') != '' && $this.css('background-image') != 'none') {
			    		$this.css('background-image', 'none');
			    		// clear stored data
			    		$.ajax({
							url: ajax_save_url,
							type: 'POST',
							data: { 
								id: $this.data('backgroundid'), 
								value: '',
								template: wplauncher_template },
						});
			    	} else {
			    		media_upload_open = true;
				    	media_element_type = 'background';
				    	$selected_media = $this;
						tb_show('', wplauncher_media_upload_url);
			    		$('#TB_iframeContent').on('load', function() {
							$('#TB_iframeContent').contents().find('#tab-gallery').hide();
						});
			    	}
			    	
					e.stopPropagation();
					e.preventDefault();
			    });
			}

			// Color
			if ($this.hasClass('wplauncher-editable-color')) {
				$this.wrap('<span class="wplauncher-color-wrapper"></span>');
				var $wrap_color = $this.closest('.wplauncher-color-wrapper');
				var original_value = $wrap_color.find('.wplauncher-editable-color').data('color');
				if ($this.css('display') == 'block') $wrap_color.css('display', 'block');
				// needs i18n
				$wrap_color.addClass($this.data('colorid')).append('<span class="wplauncher-color-inputs"><input type="text" class="wplauncher-color-value" value="'+original_value+'" /><button class="wplauncher-color-ok"><i class="wplauncher-icon-ok"></i></button> <button class="wplauncher-color-cancel"><i class="wplauncher-icon-cancel"></i></button></span>');
				$wrap_color.append('<a class="wplauncher-color-edit" href="#"><i class="wplauncher-icon-brush"></i></a>');
				$wrap_color.find('input.wplauncher-color-value').wpColorPicker({
					change: function(event, ui) {
						$wrap_color.find('.wplauncher-editable-color').css('color', ui.color.toString());
					}
				});
				$wrap_color.find('.wplauncher-color-edit').click(function(event) {
					event.preventDefault();
					$wrap_color.toggleClass('inputs-visible');
					$wrap_color.data('originalval', $wrap_color.find('.wplauncher-color-value').val());
				});
				$wrap_color.find('.wplauncher-color-ok').click(function(event) {
					event.preventDefault();
					wplauncher_save_color($wrap_color);
				});
				$wrap_color.find('.wplauncher-color-cancel').click(function(event) {
					event.preventDefault();
					wplauncher_cancel_color($wrap_color);
				});
			}
			
			// Image
			if ($this.hasClass('wplauncher-editable-image')) {
				var $img = $this;
				$img.wrap('<span class="wplauncher-editable-image-wrapper"></span>');
				if ($this.css('display') == 'block') $img.parent().css('display', 'block');
				$img.parent().addClass($this.attr('src'));
				$img.click(function (e) {
			    	// set selected media & media upload open
			    	media_upload_open = true;
			    	media_element_type = 'image';
			    	$selected_media = $img;
					tb_show('', wplauncher_media_upload_url);
					$('#TB_iframeContent').on('load', function() {
						$('#TB_iframeContent').contents().find('#tab-gallery').hide();
					});
					e.stopPropagation();
					e.preventDefault();
			    });
			}

			// Background
			if ($this.hasClass('wplauncher-editable-background')) {
				$this.wrap('<span class="wplauncher-bg-wrapper"></span>');
				var $wrap_bg = $this.closest('.wplauncher-bg-wrapper');
				$wrap_bg.addClass($this.data('backgroundid')).append('<a class="wplauncher-bg-edit" href="#"><i class="wplauncher-icon-picture"></i></a>');
				if ($this.css('display') == 'block') $wrap_bg.css('display', 'block');

				$wrap_bg.find('.wplauncher-bg-edit').click(function (e) {
			    	if ($this.css('background-image') != '' && $this.css('background-image') != 'none') {
			    		$this.css('background-image', 'none');
			    		// clear stored data
			    		$.ajax({
							url: ajax_save_url,
							type: 'POST',
							data: { 
								id: $this.data('backgroundid'), 
								value: '',
								template: wplauncher_template },
						});
			    	} else {
			    		media_upload_open = true;
				    	media_element_type = 'background';
				    	$selected_media = $this;
						tb_show('', wplauncher_media_upload_url);
			    		$('#TB_iframeContent').on('load', function() {
							$('#TB_iframeContent').contents().find('#tab-gallery').hide();
						});
			    	}
			    	
					e.stopPropagation();
					e.preventDefault();
			    });
			}
		});

		// Hideable
		var wplauncher_hide = function( $hideable_wrapper ) {
			$hideable_wrapper.each(function(index, el) {
				var $wrap_hideable = $(this);
				$wrap_hideable.addClass('wplauncher-wrapper-hidden').find('.wplauncher-hideable-toggle i').removeClass('wplauncher-icon-eye').addClass('wplauncher-icon-eye-off');
				$wrap_hideable.find('.wplauncher-hideable').addClass('wplauncher-hidden');
				// submit data
				$.ajax({
					url: ajax_save_url,
					type: 'POST',
					data: { 
						id: $wrap_hideable.find('.wplauncher-hideable').data('hideid'), 
						value: 1,
						template: wplauncher_template },
				});
			});
		};
		var wplauncher_show = function( $hideable_wrapper ) {
			$hideable_wrapper.each(function(index, el) {
				var $wrap_hideable = $(this);
				$wrap_hideable.removeClass('wplauncher-wrapper-hidden').find('.wplauncher-hideable').removeClass('wplauncher-hidden');
				$wrap_hideable.find('.wplauncher-hideable-toggle i').removeClass('wplauncher-icon-eye-off').addClass('wplauncher-icon-eye');
				// submit data
				$.ajax({
					url: ajax_save_url,
					type: 'POST',
					data: { 
						id: $wrap_hideable.find('.wplauncher-hideable').data('hideid'), 
						value: 0,
						template: wplauncher_template },
				});
			});
		};

		// Section
		var wplauncher_hide_section = function( $section ) {
			$section.each(function(index, el) {
				var $wrap_hideable = $(this);
				$wrap_hideable.find('.wplauncher-hideable-toggle i').removeClass('wplauncher-icon-eye').addClass('wplauncher-icon-eye-off');
				$wrap_hideable.addClass('wplauncher-hidden-section');
				$wrap_hideable.find('.wplauncher-section-editor-wrap').slideUp();
				// submit data
				$.ajax({
					url: ajax_save_url,
					type: 'POST',
					data: { 
						id: $wrap_hideable.data('sectionid'), 
						value: 1,
						template: wplauncher_template },
				});
			});
		};
		var wplauncher_show_section = function( $section ) {
			$section.each(function(index, el) {
				var $wrap_hideable = $(this);
				$wrap_hideable.find('.wplauncher-hideable-toggle i').removeClass('wplauncher-icon-eye-off').addClass('wplauncher-icon-eye');
				$wrap_hideable.removeClass('wplauncher-hidden-section');
				$wrap_hideable.find('.wplauncher-section-editor-wrap').slideDown();
				// submit data
				$.ajax({
					url: ajax_save_url,
					type: 'POST',
					data: { 
						id: $wrap_hideable.data('sectionid'), 
						value: 0,
						template: wplauncher_template },
				});
			});
		};

		// Color
		var wplauncher_cancel_color = function( $color_wrapper ) {
			$color_wrapper.each(function(index, el) {
				var $wrap_color = $(this);
				if ($wrap_color.hasClass('inputs-visible')) {
					$wrap_color.removeClass('inputs-visible');
					var original = $wrap_color.data('originalval');
					$wrap_color.find('.wplauncher-editable-color').css('color', original);
					$wrap_color.find('.wplauncher-color-value').val(original).trigger('change');
				}
			});
		};
		var wplauncher_save_color = function( $color_wrapper ) {
			$color_wrapper.each(function(index, el) {
				var $wrap_color = $(this);
				if ($wrap_color.hasClass('inputs-visible')) {
					$wrap_color.removeClass('inputs-visible');
				}
				// submit data
				$.ajax({
					url: ajax_save_url,
					type: 'POST',
					data: { 
						id: $wrap_color.find('.wplauncher-editable-color').data('colorid'), 
						value: $wrap_color.find('.wplauncher-color-value').val(),
						template: wplauncher_template },
				});
			});
		};
		$(document).click(function(event) {
			var $target = $(event.target);
			if (! $target.is('.wplauncher-color-wrapper') && ! $target.closest('.wplauncher-color-wrapper').length) {
				wplauncher_cancel_color($('.wplauncher-color-wrapper'));
			} else {
				var $this_wrap = ($target.is('.wplauncher-color-wrapper') ? $target : $target.closest('.wplauncher-color-wrapper'));
				wplauncher_cancel_color($('.wplauncher-color-wrapper').not($this_wrap));
			}
		});


		// Media uploader
			var media_upload_open = false;
			var media_element_type = 'image';
			var $selected_media = '';
			window.original_send_to_editor = window.send_to_editor;
			window.send_to_editor = function(html) {
				if (media_upload_open) {
					var imgurl = jQuery('img',html).attr('src');
		            
		            var classes = jQuery('img', html).attr('class');
		            var regex = /wp-image-([0-9]+)/g;
		            var imgid = regex.exec(classes);
		                imgid = imgid[1];

		            var fieldid = '';

		            // send 'imgurl' to ajax save
		            if (media_element_type == 'image') {
						fieldid = $selected_media.prop('id');
		            } else if (media_element_type == 'background') {
						fieldid = $selected_media.data('backgroundid');
		            }

		            $.ajax({
						url: ajax_save_url,
						type: 'POST',
						data: { 
							id: fieldid, 
							value: imgurl,
							template: wplauncher_template },
					});
			    	
		            if (media_element_type == 'image') {
						$selected_media.attr('src' , imgurl);
		            } else if (media_element_type == 'background') {
		            	$selected_media.css('background-image' , 'url('+imgurl+')');
		            }
					tb_remove();
					media_upload_open = false;
				} else {
					window.original_send_to_editor(html);
				}
			}

		// Admin bar
		$('#wp-admin-bar-wplauncher-reset-template a').click(function(event) {
			if (!confirm('Are you sure you want to reset all template data to defaults? This includes any custom text & styling.')) {
				event.preventDefault();
				return false;
			}
		});

	});
})(jQuery);