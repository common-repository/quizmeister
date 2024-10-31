if (typeof String.prototype.startsWith != 'function') {
	String.prototype.startsWith = function(str) {
		return this.slice(0, str.length) == str;
	};
}

if (typeof String.prototype.endsWith !== 'function') {
	String.prototype.endsWith = function(suffix) {
		return this.indexOf(suffix, this.length - suffix.length) !== -1;
	};
}

function isset(object) {
	return (typeof object !== 'undefined');
}

var QuizMeister_Obj = {
	init: function() {
		// init the featured image uploader and cat selector
		this.featImgUploader();
		this.catSelector();
	},

	featImgUploader: function() {
		var pthis = this;
		jQuery(function($) {
			var uploader = new plupload.Uploader(qm.plupload);

			$('#qm-ft-upload-pickfiles').click(function(e) {
				e.preventDefault();
				uploader.start();
			});

			uploader.init();

			uploader.bind('FilesAdded', function(up, files) {
				window.console && console.log("fa1");
				$.each(files, function(i, file) {
					$(
						'#qm-ft-upload-filelist'
					).append(
						'<span id="qm-upl-' +
						file.id +
						'" class="qm-upl-file">' +
						file.name + ' (' +
						plupload.formatSize(
							file.size) +
						') <span class="qm-upl-perc"></span>' +
						'<img class="qm-spinner-img" src="' +
						qm.plugin_base +
						'/images/spinner.gif"></span>'
					);
				});
				up.refresh();
				uploader.start();
			});

			uploader.bind('UploadProgress', function(up, file) {
				$('#qm-upl-' + file.id +
					" .qm-upl-perc").html(
					file.percent +
					"%");
			});
			uploader.bind('Error', function(up, err) {
				$('<span id="qm-upl-err">Error: ' + err
						.message +
						"</span>").appendTo($(
						"#qm-ft-upload-pickfiles").parent())
					.fadeOut(5000);
				up.refresh(); // Reposition Flash/Silverlight
			});

			uploader.bind('FileUploaded', function(up, file,
				response) {
				var resp = $.parseJSON(response.response);
				$('#qm-upl-' + file.id).remove();
				if (resp.success) {
					$('#qm-ft-upload-pickfiles').hide();
					$(resp.html).appendTo('#qm-ft-upload-filelist').hide().fadeIn('slow').find('> .qm-del-ft-image').click(pthis.removeFeatImg);
					window.console && console.log("resp " + resp.html);
				}
			});
		});
	},
	// may accept element or click event (yielding element as target)
	removeFeatImg: function(e) {
		e.preventDefault();
		jQuery(function($) {
			var el   = e instanceof jQuery ? e : $(e.target),
				data = {
					'attach_id': el.data('id'),
					'nonce'    : qm.nonce,
					'action'   : 'quizmeister_feat_img_del'
				}
			;
			$.post(qm.ajaxurl, data, function() {
				el.parent().fadeOut('slow', function() {
					$(this).remove();
					$('#quizmeister_featured_img').remove();
					$('#qm-ft-upload-pickfiles').show();
				});
			});
		});
	},

	addCatSelectorEvent: function(dropdown) {
		jQuery(function($) {
			dropdown.on('change', function() {
				currentLevel = parseInt($(dropdown).parent()
					.data('level'));

				if ($(this).val() <= 1 || !$(this).find(':selected').hasClass('has-child-cats')) {
					$(this).parent().removeClass(
						'has-child-cats');

					// find and remove all cat selectors after the selected category level
					$(this).parent().nextAll()
						.fadeOut('fast', function() {
								$(this).remove();
							}).find('select').unbind()
						.attr(
							'disabled', true).addClass('disabled').animate({
							width: 0
						}, 'fast');
				} else {
					QuizMeister_Obj.getChildCats($(this),
						'cat-wrap-lvl-',
						currentLevel + 1);
				}
			});
		});
	},

	catSelector: function() {
		jQuery(function($) {
			$('.cat-ajax').each(function(index, el) {
				QuizMeister_Obj.addCatSelectorEvent($(this))
			});
		});
	},

	getChildCats: function(dropdown, result_div, level) {
		jQuery(function($) {
			cat = dropdown.val();
			results_div = result_div + level;
			taxonomy = typeof taxonomy !== 'undefined' ?
				taxonomy : 'category';

			if (QuizMeister_Obj.cat_req_ajax) {
				QuizMeister_Obj.cat_req_ajax.abort();
				QuizMeister_Obj.cat_req_ajax = null;
			}
			QuizMeister_Obj.cat_req_ajax = $.ajax({
				type: 'post',
				url: qm.ajaxurl,
				data: {
					action: 'quizmeister_get_ajax_quiz_child_cats',
					catID: cat,
					nonce: qm.nonce
				},
				beforeSend: function() {
					dropdown.parent().nextAll()
						.fadeOut(
							'fast',
							function() {
								$(this).remove();
							}).find(
							'select').unbind()
						.attr(
							'disabled',
							true).addClass('disabled').animate({
							width: 0
						}, 'fast');

					dropdown.parent().parent()
						.next(
							'.cat-loading').html(
							'<img class="qm-spinner-img" src="' +
							qm.plugin_base +
							'/images/spinner.gif">'
						);
				},
				complete: function() {
					// ensure we are clean from the fadeout
					dropdown.parent().parent()
						.next(
							'.cat-loading').find(
							'.qm-spinner-img'
						).fadeOut(
							'slow',
							function() {
								dropdown
									.parent()
									.parent()
									.next(
										'.cat-loading'
									)
									.empty()
							})
				},
				success: function(html) {
					if (html != '') {
						dropdown.parent()
							.nextAll(
								':animated'
							).remove();
						dropdown.parent()
							.addClass(
								'has-child-cats'
							);
						dropdown.parent()
							.parent()
							.append(
								'<img class="qm-cat-arrow-img" src="' +
								qm.plugin_base +
								'/images/arrow-right.png"><span id="' +
								results_div +
								'" class="cat-ajax-wrap" style="display: none;" data-level="' +
								level +
								'">' + html +
								'</span>'
							);
						QuizMeister_Obj.addCatSelectorEvent(
							$('#' +
							results_div +
							' .cat-ajax')
						);
						var el = dropdown
							.parent()
							.parent()
							.find(
								'#' +
								results_div
							);
						var awidth = el.width();
						el.fadeIn(
							'fast').find(
							'select').width(
							0).animate({
							width: awidth
						}, 'fast');
					}
				}
			});
		});
	}
};
