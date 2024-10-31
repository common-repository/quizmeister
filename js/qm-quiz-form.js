// translation strings are passed as text...
/*global qfdata*/
/*global jQuery*/
/*global QuizMeister_Obj*/
var haspost = qfdata.haspost === 'true';
var minq = parseInt(qfdata.minq, 10);
var maxq = parseInt(qfdata.maxq, 10);
var minapq = parseInt(qfdata.minapq, 10);
var maxapq = parseInt(qfdata.maxapq, 10);
var f_numq = parseInt(qfdata.f_numq, 10);
var f_numapq = parseInt(qfdata.f_numapq, 10);
var q_text_maxtextlen = parseInt(qfdata.q_text_maxtextlen, 10); // max question text length, int
var q_sub_maxtextlen  = parseInt(qfdata.q_sub_maxtextlen, 10); // max question sub-text length, int
var q_explan_maxtextlen  = parseInt(qfdata.q_explan_maxtextlen, 10); // max question explan length, int
var q_embed_maxtextlen  = parseInt(qfdata.q_embed_maxtextlen, 10); // max question embed length, int
var q_a_text_maxtextlen = parseInt(qfdata.q_a_text_maxtextlen, 10); // max answer text length, int

jQuery(document).ready(function ($) {
	var npform = $('#qm-new-quiz-form');
	window.onbeforeunload = null; // reset...
	setUpForm();
	checkFormSubmit(null);
	npform.submit(checkFormSubmit);
	npform.on('change', 'input', function (e) {
		if($(this).val() !== '') {
			window.onbeforeunload = confirmOnPageExit;
		}
		checkFormSubmit(null);
	}).on('keyup', 'input', checkFormSubmit);
	if (haspost) {
		if (window.onbeforeunload == null) {
			// check current stuff
			npform.find('input').each(function(el) {
				if ($(this).val() === '') return;
				// if form submitted, always confirm when navigating away to be on the safe side
				window.onbeforeunload = confirmOnPageExit;
			});
		}
	} else {
		// hasn't posted, possibly refreshed, clear all fields...
		npform.find('input:text, input:password, input:file, textarea').val('');
		npform.find('input:radio, input:checkbox').removeAttr('checked').removeAttr('selected'); // TODO: fix for comments checkbox
		npform.find('select').find('option:first').prop('selected', 'selected');

		var radio_groups = {};
		npform.find('input:radio[name^="qm-q-"][name$="-rightans"]').each(function () {
			radio_groups[this.name] = true; // use as name set
		});
		// reset radio groups to first item checked
		for(group in radio_groups){
			npform.find('input:radio[name="'+group+'"]:first').attr('checked', true);
		}
		// reset numq to default
		$('#qm-numq').val(f_numq);
		npform.find('input:hidden[name^="qm-q-"][name$="-numa"]').val(f_numapq);
	}
	checkBtnEnablement(npform);
	QuizMeister_Obj.init();
});

function addExplanOnLi (li) {
	var $ = jQuery;
	li.each(function () {
		$(this).find('> label:first').each(function() {
			var title = $(this).attr('title');
			$(this).removeAttr('title');
			if ((title = $.trim(title)) !== '') {
				jQuery('<span class="explan-js">?</span>').insertAfter(
				jQuery(this).parent().find('.main-input:first'))
				.hover(function (e) {
					// hover in
					$('#tooltip').remove();
					$('<p id="tooltip"></p>').html(title).appendTo('body').show();
					$(this).mousemove(); // make sure this event is called
				}, function(e) {
					// hover out
					$('#tooltip').fadeOut('slow', $(this).remove);
				}).mousemove(function(e) {
					$('#tooltip').css({ top: e.pageY + 10, left: e.pageX + 20 });
				});
			}
		});
	});
}

function addRollupOnQHead (qhead) {
	var $ = jQuery;
	qhead.each(function () {
		var qhead_this = $(this);
		var rollup = $('<span class="qm-q-rollupdown">[<a href="#">roll up</a>]</span>');
		rollup.find('> a:first').click(function(e) {
			e.preventDefault();
			if (qhead_this.closest('.qm-q-li').find(':animated').not('.qm-q-rollupdown').length > 0) {
				return;
			}
			var a = $(this);
			a.text(a.text() === 'roll up' ? 'roll down' : 'roll up');
			qhead_this.next('.qm-q-ul-wrap:first').stop().slideToggle('slow', function() {
				a.text($(this).is(':visible') ? 'roll up' : 'roll down');
			});
			return false;
		}).click();
		qhead_this.append(rollup);
	});
	return qhead;
}

function addRollupOnAHead (ahead) {
	var $ = jQuery;
	ahead.each(function () {
		var ahead_this = $(this);
		var rollup = $('<span class="qm-q-a-rollupdown">[<a href="#">roll up</a>]</span>');
		rollup.find('> a:first').click(function(e) {
			e.preventDefault();
			if (ahead_this.closest('.qm-q-li').find(':animated').not('.qm-q-a-rollupdown').length > 0) {
				return;
			}
			var a = $(this);
			a.text(a.text() === 'roll up' ? 'roll down' : 'roll up');
			ahead_this.next('.qm-q-a-ul-wrap:first').stop().slideToggle('slow', function() {
				a.text($(this).is(':visible') ? 'roll up' : 'roll down');
			});
			return false;
		});
		ahead_this.append(rollup);
	});
	return ahead;
}

function addAAddBtns (qmqaulwrap) {
	var $ = jQuery;
	qmqaulwrap.each(function() {
		$('<input type="button" class="qm-small-button qm-q-add-a-btn" '+
			'value="Add New Answer" />'
		).click(function (e) {
			e.preventDefault();
			addAnswer($(this).closest('.qm-q-li'));
			return false;
		}).appendTo($(this));
	});
	return qmqaulwrap;
}

function addQDelBtns (qmqhead) {
	var $ = jQuery;
	qmqhead.each(function() {
		var html = $('<input type="button" class="qm-small-button qm-q-del-btn" value="X" />').click(function (e) {
			e.preventDefault();
			delQuestion($(this).closest('.qm-q-li'));
			return false;
		}).appendTo($(this));
	});
	return qmqhead;
}

function addADelBtns (qmqatextli) {
	var $ = jQuery;
	qmqatextli.each(function() {
		var html = $('<!-- nbsp -->&nbsp;<input type="button" class="qm-small-button qm-q-a-del-btn" value="X" />').click(function (e) {
			e.preventDefault();
			delAnswer($(this).closest('.qm-q-a-text-li'));
			return false;
		}).insertAfter($(this).find('> input[type=radio]'));
	});
	return qmqatextli;
}

function setUpForm () {
	var $ = jQuery;
	var npform = $('#qm-new-quiz-form');
	// set up form for dynamic stuff
	addAAddBtns(npform.find('.qm-q-a-ul-wrap'));
	addQDelBtns(addRollupOnQHead(npform.find('.qm-q-head')));
	addRollupOnAHead(npform.find('.qm-q-a-head'));
	addADelBtns(npform.find('.qm-q-a-text-li'));
	addExplanOnLi(npform.find('ul > li'));

	// add 'add new question' button
	$('<li id="qm-add-q-li">'+
		'<input type="button" id="qm-add-q-btn" class="qm-small-button" '+
		'value="Add New Question" />'+
		'<div class="clear"></div>'+
		'</li>'
	).insertBefore('#qm-submit-li').find('#qm-add-q-btn').click(function (e) {
		addQuestion(npform);
	});
}

var lastunq = null;
function getLabelUnqIDs () {
    var ret = null;
    if (!lastunq) {
        lastunq = guid();
        ret = lastunq;
    } else {
        ret = lastunq;
        lastunq = null;
    }
    return ret;
}

function guid () {
  function s4() {
    return Math.floor((1 + Math.random()) * 0x10000)
      .toString(16)
      .substring(1);
  }
  return s4() + s4() + '-' + s4() + '-' + s4() + '-' +
    s4() + '-' + s4() + s4() + s4();
}

function confirmOnPageExit (e) {
	// If we haven't been passed the event get the window.event
	e = e || window.event;
	var message = 'Are you sure you wish to navigate away from this page? Any entered infomation will be lost.';

	// For IE6-8 and Firefox prior to version 4
	if (e) e.returnValue = message;

	// For Chrome, Safari, IE8+ and Opera 12+
	return message;
}

function checkFormSubmit (e = null) {
	var $ = jQuery;
	var nqf = $('#qm-new-quiz-form');
	if (e != null && e.originalEvent.explicitOriginalTarget.id == 'qm-submit') {
		window.onbeforeunload = null;
	}
	nqf.find('input').each(function () {
		if ($(this).hasClass('invalid')) {
			$(this).removeClass('invalid');
		}
	});
	var hasError = false;
	nqf.find('input').each(function () {
		var element = $(this);
		if (element.hasClass('required-field')) {
			if (element.prop('type') === 'radio') {
				// all radio buttons of same name invalid if none of them are checked
				var checked = nqf.find('input[name="'+element.prop('name')+'"]:checked:first');
				if (checked.length === 0) {
					element.addClass('invalid');
					hasError = true;
				}
			} else {
				val = element.val();
				if (element.hasClass('richtext')) {
					val = $.trim(tinyMCE.get(
						element.id()).getContent());
				}
				if ((val = $.trim(val)) === '') {
					element.addClass('invalid');
					hasError = true;
				}
				if (element.hasClass('cat')) {
					if (isNaN(val) || val <= 0) {
						element.addClass('invalid');
						hasError = true;
					}
				}
			}
		}
		if (element.hasClass('email')) {
			if (!filter_var(val), FILTER_VALIDATE_EMAIL) {
				element.addClass('invalid');
				hasError = true;
			}
		}
	});
	if (hasError) {
		if (e != null) {
			e.preventDefault();
		}
		$('#qm-submit').prop('disabled', true).addClass('disabled');
		return false;
	}
	$('#qm-submit').prop('disabled', false).removeClass('disabled');
	return true;
}

function addQuestion (npform) {
	if (npform.find('[data-deleting="true"]').length > 0 || npform.find(':animated').not('[class$="-rollupdown"]').length > 0) {
		return;
	}
	var res = false;
	var numqe = npform.find("> #qm-numq");
	var numq  = parseInt(numqe.val(),10);
	var baseq = "qm-q-"+numq;
	var html = get_question_li(numq, f_numapq);
	addExplanOnLi(html.find('ul > li'));
	addAAddBtns(html.find('.qm-q-a-ul-wrap:first'));
	addQDelBtns(addRollupOnQHead(html.find('.qm-q-head:first')));
	addRollupOnAHead(html.find('.qm-q-a-head:first'));
	addADelBtns(html.find('.qm-q-a-text-li'));
	html.insertAfter(npform.find('.qm-q-li:last'));
	html.hide().slideDown('slow')
	numqe.val(res = ++numq);
	checkBtnEnablement(npform);
	checkFormSubmit(null);
	return res;
}

function addAnswer (qli) {
	var npform = qli.closest('#qm-new-quiz-form');
	if (npform.find('[data-deleting="true"]').length > 0 || npform.find(':animated').not('[class$="-rollupdown"]').length > 0) {
		return;
	}
	var res = false;
	var currqid = qli.data('qnum');
	var numae = qli.find('> .qm-q-numa:first');
	var numa = parseInt(numae.val(),10);
	var last = qli.find('.qm-q-a-ul > .qm-q-a-text-li:last');
	var lastaid = last.data('anum');
	var li = get_ans_li(currqid, lastaid+1, null, false);
	addADelBtns(li);
	addExplanOnLi(li);
	li.insertAfter(last).hide().slideDown('slow');
	numae.val(res = ++numa);
	checkBtnEnablement(npform);
	checkFormSubmit(null);
	return res;
}

// qli is the li of the question to be deleted
function delQuestion (qli) {
	var npform = qli.closest('#qm-new-quiz-form');
	if (npform.find('[data-deleting="true"]').length > 0 || npform.find(':animated').not('[class$="-rollupdown"]').length > 0) {
		return;
	}
	if (qli.data('deleting')) return;
	qli.data('deleting', true);
	var numqe   = npform.find('> #qm-numq');
	var numq    = parseInt(numqe.val(),10);
	var newnumq = numq-1;
	var q_index = parseInt(qli.data('qnum'));

	qli.nextAll('.qm-q-li').each(function () {
		var item    = jQuery(this);
		var currqid = parseInt(item.data('qnum'));
		var newqid  = currqid-1;
		item.data('qnum', newqid); // update qnum data
		item.find('.qm-q-formel').each(function () {
			var eitem = jQuery(this);
			// update element names using fname data
			eitem.attr('name', 'qm-q-'+newqid+'-'+eitem.data('fname'));
		});
		item.find('.qm-q-a-formel').each(function () {
			var eitem = jQuery(this);
			// update element names using fname data
			var anum = eitem.closest('.qm-q-a-text-li').data('anum');
			eitem.attr('name', 'qm-q-'+newqid+'-a-'+anum+'-'+eitem.data('fname'));
		});
		item.find('.qm-qlab').text(''+(newqid+1));
	});
	numqe.val(newnumq);
	qli.animate({ height: 0, opacity: 0 }, 'slow', function () {
		qli.remove();
	});
	// check and update delete buttons
	checkBtnEnablement(npform);
	checkFormSubmit(null);
}

function delAnswer (atli) {
	var qli = atli.closest('.qm-q-li');
	var npform = qli.closest('#qm-new-quiz-form');
	if (npform.find('[data-deleting="true"]').length > 0 || npform.find(':animated').not('[class$="-rollupdown"]').length > 0) {
		return;
	}
	if (atli.data('deleting')) return;
	atli.data('deleting', true);

	var rightans = atli.find('.qm-q-rightans:first');

	// check if radio is on an element to be deleted and move it
	if (atli.find('.qm-q-rightans:first').is(':checked')) {
		if (atli.next('.qm-q-a-text-li').find('.qm-q-rightans:first').prop('checked', true).length === 0) {
			atli.prev('.qm-q-a-text-li').find('.qm-q-rightans:first').prop('checked', true);
		}
	}
	atli.animate({ height: 0, opacity: 0 }, 'slow', function () {
		var qid = qli.data('qnum');
		var numae = qli.find('> .qm-q-numa:first');
		var numa = parseInt(numae.val(),10);
		var newnuma = numa-1;
		atli.nextAll('.qm-q-a-text-li').each(function () {
			var item = jQuery(this);
			var curranum = item.data('anum');
			var newanum  = curranum-1;
			item.data('anum', newanum); // update anum data
			item.find('.qm-q-a-formel').each(function () {
				var eitem = jQuery(this);
				// update element names using fname data
				eitem.attr('name', 'qm-q-'+qid+'-a-'+newanum+'-'+eitem.data('fname'));
			});
			item.find('.qm-alab').text(''+(newanum+1));
		});
		numae.val(newnuma);
		atli.remove();
		// check and update delete buttons
		checkBtnEnablement(npform);
		checkFormSubmit(null);
	});
}

function checkBtnEnablement (npform) {
	var numqe = npform.find("> #qm-numq");
	var numq = parseInt(numqe.val(),10);

	// disable or enable buttons
	var els = npform.find('.qm-q-del-btn');
	if (numq <= minq) {
		els.prop('disabled', true).addClass('disabled');
	} else {
		els.prop('disabled', false).removeClass('disabled');
	}
	var els2 = npform.find("#qm-q-add-btn");
	if (numq >= maxq) {
		els2.prop('disabled', true).addClass('disabled');
	} else {
		els2.prop('disabled', false).removeClass('disabled');
	}

	// disable or enable buttons
	var els3 = npform.find('.qm-q-li');
	els3.each(function () {
		var item = jQuery(this);
		var numae = item.find("> .qm-q-numa:first");
		var numa = parseInt(numae.val(),10);
		var els4 = item.find('.qm-q-a-del-btn');
		if (numa <= minapq) {
			els4.prop('disabled', true).addClass('disabled');
		} else {
			els4.prop('disabled', false).removeClass('disabled');
		}
		var els5 = npform.find('.qm-q-add-a-btn');
		if (numa >= maxapq) {
			els5.prop('disabled', true).addClass('disabled');
		} else {
			els5.prop('disabled', false).removeClass('disabled');
		}
	});
}

// q_text, q_sub_text, q_sub_text, q_explan, answers stuff *must* be html encoded (no ", etc)
// answers as array, answers[n].ans_text
function get_question_li (q_index, num_answers) {
	var lis = [];
	var baseq = "qm-q-"+q_index;
	var numaval = 0;
	var html = '<li class="qm-q-li" data-qnum="'+q_index+'"><h2 class="qm-q-head">Question #<span class="qm-qlab">'+(q_index+1)+'</span></h2><div class="qm-q-ul-wrap"><ul class="qm-q-ul">';
	html += '<input name="'+baseq+'-numa" type="hidden" class="qm-q-numa qm-q-formel main-input" data-fname="numa" value="'+num_answers+'">';
	var textli = '<li class="qm-q-text-li">'+
		'<label for="'+getLabelUnqIDs()+'" title="The main text for this question.">Text <span class="qm-req-indicator">*</span></label>'+
		'<input id="'+getLabelUnqIDs()+'" name="'+baseq+'-text" class="required-field qm-q-formel main-input" data-fname="text" type="text" maxlength="'+q_text_maxtextlen+'">'+
		'<div id="clear"></div>'+
	'</li>';
	html += textli;
	var subtextli = '<li class="qm-q-sub-li">'+
		'<label for="'+getLabelUnqIDs()+'" title="The sub-text for this question that goes under the main text.">Sub-Text</label>'+
		'<input id="'+getLabelUnqIDs()+'" name="'+baseq+'-sub" class="qm-q-formel main-input" data-fname="sub" type="text" maxlength="'+q_sub_maxtextlen+'">'+
		'<div id="clear"></div>'+
	'</li>';
	html += subtextli;
	var explanli = '<li class="qm-q-sub-li">'+
		'<label for="'+getLabelUnqIDs()+'" title="The explanation for the correct answer, displayed on the following page.">Explanation</label>'+
		'<input id="'+getLabelUnqIDs()+'" name="'+baseq+'-explan" class="qm-q-formel main-input" data-fname="explan" type="text" maxlength="'+q_explan_maxtextlen+'">'+
		'<div id="clear"></div>'+
	'</li>';
	html += explanli;
	var embedli = '<li class="qm-q-embed-li">'+
		'<label for="'+getLabelUnqIDs()+'" title="Any oEmbed-enabled link can go here. oEmbed-enabled sites include Imgur, YouTube, Tumblr, Twitter, Vine, Flickr and Vimeo, amongst others. Example: https://www.youtube.com/watch?v=FTQbiNvZqaY.">Embed</label>'+
		'<input id="'+getLabelUnqIDs()+'" name="'+baseq+'-embed" class="qm-q-formel main-input" data-fname="embed" type="text" maxlength="'+q_embed_maxtextlen+'" placeholder="YouTube, Imgur, Vimeo URL, etc.">'+
		'<div id="clear"></div>'+
	'</li>';
	html += embedli;
	html += '</ul></div></li>';
	var q_html = jQuery(html);

	var qa_html = jQuery('<li class="qm-q-a-li"><h3 id="' + baseq + '-a-head" class="qm-q-a-head">Answers</h3><div class="qm-q-a-ul-wrap"><ul class="qm-q-a-ul"></ul></div></li>');
	if (num_answers > 0) {
		var qmqaul = qa_html.find('.qm-q-a-ul:first');
		for (var i = 0; i < num_answers; i++) {
			qmqaul.append(get_ans_li(q_index, i, i === 0));
		}
		var qmqul = q_html.find('.qm-q-ul:first');
		qmqul.after(qa_html);
	}
	return q_html;
}

// ans_text *must* be html encoded (no ", etc)
function get_ans_li (q_index, a_index, is_corr_ans) {
	var baseq = 'qm-q-' + q_index;
	var basea = baseq + '-a-' + a_index;
	var ntext = '<li class="qm-q-a-text-li" data-anum="'+a_index+'">' +
		'<label for="'+getLabelUnqIDs()+'" title="The answer text.">Answer #<span class="qm-alab">' + (a_index + 1) + '</span> Text <span class="qm-req-indicator">*</span></label>' +
		'<input id="'+getLabelUnqIDs()+'" name="' + basea + '-text" class="required-field qm-q-a-formel main-input" data-fname="text" type="text" maxlength="'+q_a_text_maxtextlen+'">&nbsp;' +
		'<input type="radio" class="required-field qm-q-formel qm-q-rightans" name="' + baseq + '-rightans" data-fname="rightans" class="qm-q-rightans" value="' + a_index + '"' + (is_corr_ans ? ' checked' : '') + '>' +
		'<div id="clear"></div>' +
	'</li>';
	return jQuery(ntext);
}
