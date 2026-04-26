/* global wpforo */
var $wpf = Object.assign(window.jQuery);

// Mute jQuery Migrate Warnings
$wpf.migrateMute = true;

$wpf.fn.extend({
	visible: function () {
		return this.css('visibility', 'visible');
	},
	invisible: function () {
		return this.css('visibility', 'hidden');
	},
	visibilityToggle: function () {
		return this.css('visibility', function (i, visibility) {
			return (visibility === 'visible') ? 'hidden' : 'visible';
		});
	},
	showFlex: function () {
		return this.css('display', 'flex');
	},
	wpfInsertAtCaret: function (myValue) {
		return this.each(function () {
			if (document.selection) {
				//For browsers like Internet Explorer
				this.focus();
				var sel = document.selection.createRange();
				sel.text = myValue;
			} else if (this.selectionStart || this.selectionStart === 0) {
				//For browsers like Firefox and Webkit based
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				this.value = this.value.substring(0, startPos) + myValue + this.value.substring(endPos, this.value.length);
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
			} else {
				this.value += myValue;
			}
		});
	},
	isOnScreen: function (cbTrue, cbFalse) {
		var win = $wpf(window);
		
		var viewport = {
			top: win.scrollTop(),
			left: win.scrollLeft(),
		};
		viewport.right = viewport.left + win.width();
		viewport.bottom = viewport.top + win.height();
		
		var bounds = this.offset();
		bounds.right = bounds.left + this.outerWidth();
		bounds.bottom = bounds.top + this.outerHeight();
		
		var isOnScreen = (!(viewport.right < bounds.left || viewport.left > bounds.right || viewport.bottom < bounds.top || viewport.top > bounds.bottom));
		
		if (!cbTrue && !cbFalse) {
			return isOnScreen;
		} else {
			return isOnScreen
				? cbTrue && cbTrue(viewport, bounds)
				: cbFalse && cbFalse(viewport, bounds);
		}
	},
});

// returns the cookie with the given name,
// or undefined if not found
function wpforo_getCookie (name) {
	let matches = document.cookie.match(new RegExp(
		'(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)',
	));
	return matches ? decodeURIComponent(matches[1]) : undefined;
}

function wpforo_setCookie (name, value, options = {}) {
	
	options = {
		path: '/',
		// add other defaults here if necessary
		...options,
	};
	
	if (options.expires instanceof Date) {
		options.expires = options.expires.toUTCString();
	}
	
	let updatedCookie = encodeURIComponent(name) + '=' + encodeURIComponent(value);
	
	for (let optionKey in options) {
		updatedCookie += '; ' + optionKey;
		let optionValue = options[optionKey];
		if (optionValue !== true) {
			updatedCookie += '=' + optionValue;
		}
	}
	
	document.cookie = updatedCookie;
}

function wpforo_deleteCookie (name) {
	wpforo_setCookie(name, '', {
		'max-age': -1,
	});
}

function wpforo_get_browser_timezone () {
	if (typeof Intl === 'object' && typeof Intl.DateTimeFormat === 'function') {
		try {
			const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;
			if (timeZone !== undefined && timeZone !== null) return timeZone;
		} catch (error) {
			console.error('Error accessing time zone:', error);
		}
	} else {
		console.error('Intl.DateTimeFormat is not supported in this environment.');
	}
	
	return '';
}

/**
 * Trigger a custom event.
 *
 * @param {Element|Document} target HTML element to dispatch the event on.
 * @param {string} name Event name.
 * @param [detail = null] Event addintional data information.
 */
function wpforo_trigger_custom_event (target, name, detail) {
	if (typeof detail === 'undefined') detail = null;
	var event;
	if (typeof CustomEvent === 'function') {
		event = new CustomEvent(name, { bubbles: true, cancelable: true, detail: detail });
	} else {
		event = document.createEvent('Event');
		event.initEvent(name, true, true);
		event.detail = detail;
	}
	
	target.dispatchEvent(event);
	window['wpforo_break_after_custom_event_'.name] = false;
}

function wpforo_tinymce_initializeIt (selector, do_not_focus) {
	if (wpforo_editor.is_tinymce_loaded()) {
		$wpf(selector).each(function () {
			if (this.id) {
				var editor_settings = window['wpforo_editor_settings_' + this.id] || wpforo['editor_settings'];
				var settings = {
					selector: '#' + this.id,
					relative_urls: false,
					remove_script_host: false,
					convert_urls: false,
					keep_styles: false,
					end_container_on_empty_block: true,
					wpeditimage_html5_captions: true,
					force_br_newlines: false,
					force_p_newlines: true,
					menubar: false,
					branding: false,
					elementpath: true,
					statusbar: true,
					fix_list_elements: true,
					browser_spellcheck: true,
					entities: '38,amp,60,lt,62,gt',
					entity_encoding: 'raw',
					resize: 'vertical',
					preview_styles: 'font-family font-size font-weight font-style text-decoration text-transform',
					forced_root_block: '',
					plugins: editor_settings['plugins'],
					external_plugins: editor_settings['external_plugins'],
					min_height: editor_settings['editor_height'],
					indent: editor_settings['indent'],
					height: editor_settings['editor_height'],
					wpautop: editor_settings['wpautop'],
					wp_keep_scroll_position: editor_settings['wp_keep_scroll_position'],
					add_unload_trigger: editor_settings['add_unload_trigger'],
				};
				settings = Object.assign(settings, editor_settings['tinymce']);
				tinymce.init(settings).then(function (e) {
					if (!do_not_focus && e.length) {
						wpforo_editor.focus(e[0].id);
						wpforo_editor.set_active(e[0].id);
					}
				});
				
			}
		});
	}
}

function wpforo_tinymce_setup (editor) {
	editor.on('init', function (e) {
		wpforo_trigger_custom_event(document, 'wpforo_tinymce_init', e);
	});
	editor.on('focus', function (e) {
		wpforo_trigger_custom_event(document, 'wpforo_tinymce_focus', e);
		wpforo_editor.set_active(editor.id);
	});
	editor.on('Dirty ExecCommand KeyPress SetContent', function (e) {
		wpforo_trigger_custom_event(document, 'wpforo_tinymce_content_changed', e);
	});
	editor.on('paste', function (e) {
		wpforo_trigger_custom_event(document, 'wpforo_tinymce_paste', e);
	});
	editor.shortcuts.add('ctrl+13', 'submit', function (e) {
		wpforo_trigger_custom_event(document, 'wpforo_tinymce_ctrl_enter', e);
		$wpf('form[data-textareaid="' + editor.id + '"]').find('[type=submit]').trigger('click');
	});
	editor.shortcuts.add('ctrl+s', 'Save Draft', function (e) {
		wpforo_trigger_custom_event(document, 'wpforo_tinymce_ctrl_s', e);
	});
}

var wpforo_editor = {
	active_textareaid: '',
	main_textareaid: '',
	fix_textareaid: function (textareaid) {
		if (typeof textareaid !== 'undefined') {
			return textareaid;
		} else if (this.active_textareaid) {
			return this.active_textareaid;
		} else {
			var tinymce_active_editor_id = this.get_tinymce_active_editor_id();
			if (tinymce_active_editor_id) {
				this.active_textareaid = tinymce_active_editor_id;
				return tinymce_active_editor_id;
			}
		}
		return '';
	},
	get_active_textareaid: function () {
		return this.fix_textareaid();
	},
	set_active: function (textareaid) {
		if (this.is_exists(textareaid)) {
			this.active_textareaid = textareaid;
			if (this.is_tinymce(textareaid)) tinymce.setActive(tinymce.get(textareaid));
		}
	},
	clear_active: function () {
		this.active_textareaid = '';
	},
	set_main: function (textareaid, also_set_active) {
		if (!textareaid) {
			var wpforo_main_form = $wpf('form.wpforo-main-form[data-textareaid]');
			if (wpforo_main_form.length) textareaid = wpforo_main_form.data('textareaid');
		}
		if (this.is_exists(textareaid)) {
			this.main_textareaid = textareaid;
			if (also_set_active) this.set_active(textareaid);
		}
	},
	get_main: function () {
		if (!this.main_textareaid) this.set_main();
		return this.main_textareaid;
	},
	clear_main: function () {
		this.main_textareaid = '';
	},
	get_tinymce_active_editor_id: function () {
		if (this.is_tinymce_loaded() && typeof tinymce.activeEditor === 'object' && tinymce.activeEditor && tinymce.activeEditor.id) {
			return tinymce.activeEditor.id;
		}
		return '';
	},
	is_tinymce_loaded: function () {
		return typeof tinymce !== 'undefined';
	},
	is_tinymce: function (textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		return !!(textareaid && this.is_tinymce_loaded() && tinymce.get(textareaid));
	},
	is_textarea: function (textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		return !!(textareaid && !this.is_tinymce(textareaid) && $wpf('textarea#' + textareaid).length);
	},
	is_exists: function (textareaid) {
		return !!(textareaid && this.is_tinymce(textareaid) || this.is_textarea(textareaid));
	},
	get_form: function (textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		var textarea = $wpf('textarea[id="' + textareaid + '"]');
		if (textarea.length) {
			var form = textarea.closest('form');
			if (form.length) return form;
		}
		return null;
	},
	get_form_body_wrap: function (textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		var ed;
		var body_wrap = null;
		if (this.is_tinymce(textareaid)) {
			ed = tinymce.get(textareaid);
			if (ed && ed.iframeElement) {
				body_wrap = ed.iframeElement.parentElement;
			}
		} else {
			ed = document.getElementById(textareaid);
			if (ed) {
				body_wrap = ed.parentElement;
			}
		}
		return body_wrap ? $wpf(body_wrap) : null;
	},
	scrollto: function (textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		var scrollto = null;
		var bodyWrap = this.get_form_body_wrap(textareaid);
		if (bodyWrap !== null) scrollto = bodyWrap.offset().top - 100;
		if (scrollto !== null && !bodyWrap.isOnScreen()) {
			$wpf('html').scrollTop(scrollto);
		}
	},
	tinymce_focus: function (textareaid, caret_to_end) {
		textareaid = this.fix_textareaid(textareaid);
		if (this.is_tinymce(textareaid)) {
			this.scrollto(textareaid);
			var focus_mce = tinymce.get(textareaid);
			focus_mce.focus();
			if (caret_to_end) {
				focus_mce.selection.select(focus_mce.getBody(), true);
				focus_mce.selection.collapse(false);
			}
		}
	},
	textarea_focus: function (textareaid, caret_to_end) {
		textareaid = this.fix_textareaid(textareaid);
		if (this.is_textarea(textareaid)) {
			this.scrollto(textareaid);
			var textarea = $wpf('textarea#' + textareaid);
			var textarea_val = textarea.val();
			textarea.trigger('focus');
			if (caret_to_end) {
				textarea.val('');
				textarea.val(textarea_val);
			}
		}
	},
	focus: function (textareaid, caret_to_end) {
		textareaid = this.fix_textareaid(textareaid);
		if (this.is_tinymce(textareaid)) {
			this.tinymce_focus(textareaid, caret_to_end);
		} else if (this.is_textarea(textareaid)) {
			this.textarea_focus(textareaid, caret_to_end);
		}
	},
	insert_content: function (content, textareaid, format, focus) {
		textareaid = this.fix_textareaid(textareaid);
		format = format ? format : 'raw';
		if (focus === undefined) focus = true;
		if (this.is_tinymce(textareaid)) {
			tinymce.get(textareaid).insertContent(content, { format: format });
			if (focus) this.tinymce_focus(textareaid);
		} else if (this.is_textarea(textareaid)) {
			$wpf('textarea#' + textareaid).wpfInsertAtCaret(content);
			if (focus) this.textarea_focus(textareaid);
		}
	},
	set_content: function (content, textareaid, format, focus) {
		textareaid = this.fix_textareaid(textareaid);
		format = format ? format : 'raw';
		if (focus === undefined) focus = true;
		if (this.is_tinymce(textareaid)) {
			tinymce.get(textareaid).setContent(content, { format: format });
			if (focus) this.tinymce_focus(textareaid, true);
		} else if (this.is_textarea(textareaid)) {
			$wpf('textarea#' + textareaid).val(content);
			if (focus) this.textarea_focus(textareaid, true);
		}
	},
	get_content: function (format, textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		format = format ? format : 'text';
		var content = '';
		if (this.is_tinymce(textareaid)) {
			content = tinymce.get(textareaid).getContent({ format: format });
		} else if (this.is_textarea(textareaid)) {
			content = $wpf('textarea#' + textareaid).val();
			if (format === 'text' && content) {
				content = content.replace(/<(iframe|embed)[^<>]*?>.*?<\/\1>/gi, '');
				content = content.replace(/(<([^<>]+?)>)/gi, '');
			}
		}
		return content.trim();
	},
	get_stats: function (textareaid) {
		textareaid = this.fix_textareaid(textareaid);
		
		var text = this.get_content('text', textareaid);
		var raw_text = this.get_content('raw', textareaid);
		var chars = text.length;
		var words = text.split(/[\w\u2019'-]+/).length - 1;
		var imgs = (raw_text.match(/<img[^<>]*?src=['"][^'"]+?['"][^<>]*?>/gi) || []).length;
		var links = (raw_text.match(/<a[^<>]*?href=['"][^'"]+?['"][^<>]*?>.+?<\/a>/gi) || []).length;
		var embeds = (raw_text.match(/<(iframe|embed)[^<>]*?>.*?<\/\1>/gi) || []).length;
		
		return {
			chars: chars,
			words: words,
			imgs: imgs,
			links: links,
			embeds: embeds,
			has_content: !!(chars || imgs || links || embeds),
		};
	},
};

function wpforo_notice_get_timeout (type) {
	if (!type) type = 'neutral';
	if (wpforo.notice.timeouts[type] !== undefined) return parseInt(wpforo.notice.timeouts[type]);
	return 8000;
}

function wpforo_notice_clear () {
	var msg_box = $wpf('#wpf-msg-box');
	msg_box.hide();
	msg_box.empty();
}

function wpforo_notice_show (notice, type) {
	if (!notice) return;
	type = (type === 'success' || type === 'error' ? type : 'neutral');
	
	var n = notice.search(/<p(?:\s[^<>]*?)?>/i);
	if (n < 0) {
		var phrase = wpforo_phrase(notice);
		if (arguments.length > 2) {
			for (var i = 2; i < arguments.length; i++) {
				if (arguments[i] !== undefined) phrase = phrase.replace(/%[dfs]/, arguments[i]);
			}
		}
		notice = '<p class="' + type + '">' + phrase + '</p>';
	}
	
	notice = $wpf(notice);
	$wpf('#wpforo-notifications-bar').appendTo('body');
	var msg_box = $wpf('#wpf-msg-box');
	msg_box.append(notice);
	msg_box.show(150);
	var timeout = wpforo_notice_get_timeout(type);
	if (timeout) {
		notice.delay(timeout).fadeOut(200, function () {
			$wpf(this).remove();
		});
	}
}

function wpforo_notice_hide () {
	$wpf('#wpf-msg-box').hide();
}

function wpforo_load_show (msg) {
	msg = typeof msg !== 'undefined' ? msg : 'Working';
	msg = String(msg);
	msg = wpforo_phrase(msg);
	$wpf('#wpforo-notifications-bar').appendTo('body');
	var load = $wpf('#wpforo-load');
	$wpf('.loadtext', load).text(msg);
	load.showFlex();
}

function wpforo_load_hide () {
	$wpf('#wpforo-load').hide();
}

function wpforo_init_dialog () {
	$wpf('#wpforo-dialog-extra-wrap').on('click', '#wpforo-dialog-close', function () {
		wpforo_dialog_hide();
	});
	$wpf(document).on('mousedown', '#wpforo-dialog-extra-wrap', function (e) {
		if (!$wpf(e.target).closest('#wpforo-dialog').length) wpforo_dialog_hide();
	});
	$wpf(document).on('keydown', function (e) {
		if (e.code === 'Escape') wpforo_dialog_hide();
	});
}

function wpforo_dialog_show (title, content, w, h) {
	var dialog = $wpf('#wpforo-dialog');
	if (content) {
		var dialog_body = $wpf('#wpforo-dialog-body', dialog);
		dialog_body.children().appendTo('#wpforo-dialog-backups');
		dialog_body.empty();
		if (content instanceof $wpf || content instanceof Element) {
			content = $wpf(content);
			content.appendTo(dialog_body);
			content.show();
			content.css('visibility', 'visible');
			if (!title) title = content.data('title');
		} else if (typeof content === 'string') {
			dialog_body.html(content);
		}
	}
	if (title) $wpf('#wpforo-dialog-title', dialog).html(wpforo_phrase(title));
	if (w) dialog.css('width', w);
	if (h) dialog.css('height', h);
	$wpf('#wpforo-dialog-extra-wrap').appendTo('body');
	$wpf('html').addClass('wpforo-dialog-visible');
	$wpf('body').addClass('wpforo-dialog-visible animated fadeIn');
}

function wpforo_dialog_hide () {
	$wpf('html').removeClass('wpforo-dialog-visible');
	$wpf('body').removeClass('wpforo-dialog-visible animated fadeIn');
}

function wpforo_phrase (phrase_key) {
	// if( !(window.wpforo_phrases && typeof window.wpforo_phrases === 'object' && Object.keys(window.wpforo_phrases).length) ) wpforo_init_phrases();
	if (window.wpforo_phrases && typeof window.wpforo_phrases === 'object' && Object.keys(window.wpforo_phrases).length) {
		var phrase_key_lower = phrase_key.toLowerCase();
		if (window.wpforo_phrases[phrase_key_lower] !== undefined) phrase_key = window.wpforo_phrases[phrase_key_lower];
	}
	return phrase_key;
}

function wpforo_getTextSelection () {
	$wpf('#wpf_multi_quote').remove();
	if (window.getSelection) {
		var sel = window.getSelection();
		if (sel && sel.anchorNode && sel.anchorNode.parentNode && sel.anchorNode.parentNode.tagName !== 'A') {
			var selectedText = sel.toString().trim();
			if (sel.rangeCount && selectedText.length) {
				var getRangeAt_0 = sel.getRangeAt(0);
				var rangeBounding = getRangeAt_0.getBoundingClientRect();
				var bodyBounding = document.documentElement.getBoundingClientRect();
				var left = rangeBounding.left + rangeBounding.width / 2 + Math.abs(bodyBounding.left) - 15;
				var top = rangeBounding.bottom + Math.abs(bodyBounding.top) + 50;
				
				var parent = $wpf(getRangeAt_0.commonAncestorContainer).closest('.wpforo-post-content, .wpforo-comment-content');
				var noNeedParent = $wpf(getRangeAt_0.commonAncestorContainer).closest('.wpforo-post-signature, .wpforo-post-content-bottom, .wpf-post-button-actions');
				var noNeedChild = $wpf(getRangeAt_0.endContainer).closest('.wpforo-post-signature, .wpforo-post-content-bottom, .wpf-post-button-actions');
				
				if (parent.length && !noNeedParent.length && !noNeedChild.length) {
					var toolTip = $wpf('<div id="wpf_multi_quote"></div>');
					toolTip.css({ top: top, left: left });
					var link = $wpf('<span class="wpf-multi-quote" title="' + wpforo_phrase('Quote this text') + '"><i class="fas fa-quote-left"></i></span>').on('mousedown touchstart', function () {
						var container = document.createElement('div');
						for (var i = 0; i < sel.rangeCount; ++i) container.appendChild(sel.getRangeAt(i).cloneContents());
						var post_wrap = $wpf(getRangeAt_0.startContainer).parents('[data-postid]');
						var userid = post_wrap.data('userid');
						if (!userid) userid = 0;
						var postid = post_wrap.data('postid');
						if (!postid) postid = 0;
						var editorContent = '';
						if (wpforo_editor.is_tinymce()) {
							editorContent = '[quote data-userid="' + userid + '" data-postid="' + postid + '"]<p>' + container.innerHTML.replace(/\s*data-[\w-]+="[^"]*?"/gi, '') + '</p>[/quote]<p></p>';
						} else {
							editorContent = '[quote data-userid="' + userid + '" data-postid="' + postid + '"]' + container.innerHTML.replace(/\s*data-[\w-]+="[^"]*?"/gi, '') + '[/quote]';
						}
						wpforo_editor.insert_content(editorContent);
						$wpf(this).remove();
					});
					toolTip.append(link);
					$wpf('body').append(toolTip);
				}
			}
			
		}
	}
}

window.wpforo_fix_form_data_attributes = function () {
	$wpf('form textarea[data-isbody=1]:first').each(function () {
		var form = $wpf(this).closest('form');
		var id = $wpf(this).attr('id');
		form.attr('data-textareaid', id);
		form.prop('data-textareaid', id);
		form.data('textareaid', id);
	});
};

$wpf(document).ready(function ($) {
	var iwpfp = $('body.is_wpforo_page-1');
	if (iwpfp.length) $('html').addClass('is_wpforo_page-1');
	
	var wpforo_wrap = $('#wpforo-wrap');
	
	wpforo_setCookie('wpforo_browser_timezone', wpforo_get_browser_timezone(), { secure: true, 'max-age': 86400 });
	
	var scroll_to;
	var exreg = new RegExp('\/' + wpforo.settings_slugs['postid'] + '\/(\\d+)\/?$', 'i');
	var match = location.pathname.match(exreg);
	if (match) {
		scroll_to = $('#post-' + match[1]);
	} else {
		//scroll_to = $("#m_, .wpforo-members-content, .wpforo-search-content", wpforo_wrap);
	}
	if (scroll_to !== undefined && scroll_to.length) {
		$('html, body').scrollTop(scroll_to.offset().top - 25);
	}
	
	wpforo_init_dialog();
	
	if ($('form.wpforo-main-form').length) {
		document.onselectionchange = function () {
			wpforo_getTextSelection();
		};
	}
	
	window.onbeforeunload = function (e) {
		var forms = $('form[data-textareaid]').not(':hidden');
		if (forms.length) {
			var i, textareaid;
			for (i = 0; i < forms.length; i++) {
				textareaid = $(forms[i]).data('textareaid');
				if (wpforo_editor.get_stats(textareaid).has_content) {
					e = e || window.event;
					e.returnValue = wpforo_phrase('Write something clever here..');
					return wpforo_phrase('Write something clever here..');
				}
			}
		}
	};
	
	window.wpforo_fix_form_data_attributes();
	wpforo_tinymce_initializeIt('[data-wpforoeditor="tinymce"]', true);
	
	setTimeout(function () {
		wpforo_editor.fix_textareaid();
		wpforo_editor.set_main('', true);
		
		var forum_sels = $('.wpf-topic-form-extra-wrap .wpf-topic-form-forumid', wpforo_wrap);
		if (forum_sels.length) {
			forum_sels.each(function (i, f) {
				f = $(f);
				var forum_opts = $('option:not([value="0"]):not([disabled])', f);
				if (forum_opts.length === 1) {
					
					var wpf_topic_form_extra_wrap = f.closest('.wpf-topic-form-extra-wrap');
					wpf_topic_form_extra_wrap.attr('data-is_just_one_forum', true);
					wpf_topic_form_extra_wrap.prop('data-is_just_one_forum', true);
					wpf_topic_form_extra_wrap.data('is_just_one_forum', true);
					
					f.val(forum_opts[0].getAttribute('value')).trigger('change');
				}
			});
		}
		
	}, 1000);
	
	wpforo_wrap.on('click drop', 'form[data-textareaid]', function () {
		var textareaid = $(this).data('textareaid');
		wpforo_editor.set_active(textareaid);
	});
	
	wpforo_wrap.on('focus', 'form[data-textareaid] textarea', function () {
		var textareaid = $(this).parents('form[data-textareaid]').data('textareaid');
		if (textareaid === this.id) wpforo_editor.set_active(this.id);
	});
	
	wpforo_wrap.on('keydown', 'form[data-textareaid]', function (e) {
		if ((e.ctrlKey || e.metaKey) && (e.code === 'Enter' || e.code === 'NumpadEnter')) {
			$('[type=submit]', $(this)).trigger('click');
		} else if (((e.ctrlKey || e.metaKey) && e.code === 'KeyS') || e.code === 'Pause' || e.code === 'MediaPlayPause') {
			wpforo_trigger_custom_event(document, 'wpforo_textarea_ctrl_s', e);
			e.preventDefault();
			return false;
		}
	});
	
	if ($('.wpforo-recent-content .wpf-p-error', wpforo_wrap).length) { $('.wpf-navi', wpforo_wrap).remove(); }
	
	/**
	 * prevent multi submitting
	 * disable form elements for 10 seconds
	 */
	window.wpforo_prev_submit_time = 0;
	wpforo_wrap.on('submit', 'form', function (e) {
		if (window.wpforo_prev_submit_time) {
			if (Date.now() - window.wpforo_prev_submit_time < 10000) return false;
		} else {
			var textareaid = $(this).data('textareaid');
			if (textareaid) {
				var bodyminlength = $(this).data('bodyminlength');
				var bodymaxlength = $(this).data('bodymaxlength');
				if (bodyminlength || bodymaxlength) {
					var body_stat = wpforo_editor.get_stats(textareaid);
					if (bodyminlength) {
						if (body_stat.chars < bodyminlength && !body_stat.embeds && !body_stat.links && !body_stat.imgs) {
							wpforo_notice_show('Content characters length must be greater than %d', 'error', bodyminlength);
							return false;
						}
					}
					if (bodymaxlength) {
						if (body_stat.chars > bodymaxlength) {
							wpforo_notice_show('Content characters length must be smaller than %d', 'error', bodymaxlength);
							return false;
						}
					}
				}
				
				wpforo_trigger_custom_event(document, 'wpforo_post_submit', { e, textareaid });
				if (window['wpforo_break_after_custom_event_wpforo_post_submit']) {
					window['wpforo_break_after_custom_event_wpforo_post_submit'] = false;
					return false;
				}
			}
			
			wpforo_load_show();
			window.wpforo_prev_submit_time = Date.now();
			window.onbeforeunload = null;
			setTimeout(function () {
				window.wpforo_prev_submit_time = 0;
				wpforo_load_hide();
			}, 10000);
		}
	});
	
	wpforo_wrap.on('click', '.wpf-spoiler-head', function () {
		var spoiler_wrap = $(this).parents('.wpf-spoiler-wrap');
		if (spoiler_wrap.length) {
			spoiler_wrap = $(spoiler_wrap[0]);
			if (!spoiler_wrap.hasClass('wpf-spoiler-processing')) {
				spoiler_wrap.toggleClass('wpf-spoiler-open').addClass('wpf-spoiler-processing');
				var spoiler_body = $('.wpf-spoiler-body', spoiler_wrap);
				if (spoiler_body.length) {
					var spoiler_chevron = $('.wpf-spoiler-chevron', spoiler_wrap);
					$(spoiler_chevron[0]).toggleClass('fa-chevron-down fa-chevron-up');
					$(spoiler_body[0]).slideToggle(500, function () {
						spoiler_wrap.removeClass('wpf-spoiler-processing');
						if (!spoiler_wrap.hasClass('wpf-spoiler-open')) {
							$('.wpf-spoiler-wrap.wpf-spoiler-open .wpf-spoiler-head', spoiler_wrap).trigger('click');
						}
					});
				}
			}
		}
	});
	
	wpforo_wrap.on('click', '#add_wpftopic:not(.not_reg_user)', function () {
		var form = $('.wpf-topic-create');
		var stat = form.is(':hidden');
		form.slideToggle('slow');
		wpforo_editor.set_content('');
		$('[name="thread[title]"]').trigger('focus');
		var add_wpftopic = '<i class="fas fa-times" aria-hidden="true"></i>';
		if (!stat) add_wpftopic = $('input[type="submit"]', form).val();
		$(this).html(add_wpftopic);
		$('html').scrollTop(($(this).offset().top - 35));
	});
	
	wpforo_wrap.on('click', '.wpf-answer-button .wpf-button:not(.not_reg_user)', function () {
		$(this).closest('.wpf-bottom-bar').hide();
	});
	
	wpforo_wrap.on('click', '.wpforo-section .add_wpftopic:not(.not_reg_user)', function () {
		var wrap = $(this).parents('div.wpforo-section');
		var form_wrap = $('.wpf-topic-form-extra-wrap', wrap);
		
		var is_just_one_forum = form_wrap.data('is_just_one_forum');
		if (!is_just_one_forum) $('.wpf-topic-form-ajax-wrap').empty();
		var stat = form_wrap.is(':hidden');
		$('.wpf-topic-form-extra-wrap').slideUp('slow');
		var add_wpftopic;
		if (stat) {
			add_wpftopic = '<i class="fas fa-times" aria-hidden="true"></i>';
			form_wrap.slideDown('slow');
		} else {
			add_wpftopic = '<i class="fas fa-feather-alt" aria-hidden="true"></i>' + $(this).data('phrase');
			form_wrap.slideUp('slow');
		}
		if (!is_just_one_forum) {
			var option_no_selected = $('option.wpf-topic-form-no-selected-forum');
			option_no_selected.show();
			option_no_selected.prop('selected', true);
		}
		$(this).html(add_wpftopic);
		$('html').scrollTop((wrap.offset().top - 30));
	});
	
	wpforo_wrap.on('click', '.not_reg_user', function () {
		wpforo_load_show();
		wpforo_notice_show(wpforo.notice.login_or_register);
		wpforo_load_hide();
	});
	
	$(document).on('click', '#wpf-msg-box p', function () {
		$(this).remove();
	});
	
	/* Home page loyouts toipcs toglle */
	wpforo_wrap.on('click', '.topictoggle', function () {
		wpforo_load_show();
		
		var id = $(this).attr('id');
		
		id = id.replace('img-arrow-', '');
		$('.wpforo-last-topics-' + id).slideToggle('slow');
		if ($(this).hasClass('topictoggle') && $(this).hasClass('fa-chevron-down')) {
			$('#img-arrow-' + id).removeClass('fa-chevron-down').addClass('fa-chevron-up');
		} else {
			$('#img-arrow-' + id).removeClass('fa-chevron-up').addClass('fa-chevron-down');
		}
		
		id = id.replace('button-arrow-', '');
		$('.wpforo-last-posts-' + id).slideToggle('slow');
		if ($(this).hasClass('topictoggle') && $(this).hasClass('wpfcl-a') && $(this).hasClass('fa-chevron-down')) {
			$('#button-arrow-' + id).removeClass('fa-chevron-down').addClass('fa-chevron-up');
		} else {
			$('#button-arrow-' + id).removeClass('fa-chevron-up').addClass('fa-chevron-down');
		}
		
		wpforo_load_hide();
	});
	
	/* Home page loyouts toipcs toglle */
	wpforo_wrap.on('click', '.wpforo-membertoggle', function () {
		var id = $(this).attr('id');
		id = id.replace('wpforo-memberinfo-toggle-', '');
		$('#wpforo-memberinfo-' + id).slideToggle('slow');
		if ($(this).find('i').hasClass('fa-caret-down')) {
			$(this).find('i').removeClass('fa-caret-down').addClass('fa-caret-up');
		} else {
			$(this).find('i').removeClass('fa-caret-up').addClass('fa-caret-down');
		}
	});
	
	/* Threaded Layout Hide Replies */
	wpforo_wrap.on('click', '.wpf-post-replies-bar', function () {
		var id = $(this).attr('id');
		id = id.replace('wpf-ttgg-', '');
		$('#wpf-post-replies-' + id).slideToggle('slow');
		if ($(this).find('i').hasClass('fa-angle-down')) {
			$(this).find('i').removeClass('fa-angle-down').addClass('fa-angle-up');
			$(this).find('.wpforo-ttgg').attr('wpf-tooltip', wpforo_phrase('Hide Replies'));
		} else {
			$(this).find('i').removeClass('fa-angle-up').addClass('fa-angle-down');
			$(this).find('.wpforo-ttgg').attr('wpf-tooltip', wpforo_phrase('Show Replies'));
		}
	});
	
	//Reply
	wpforo_wrap.on('click', '.wpforo-reply:not(.wpforo_layout_4)', function () {
		wpforo_load_show();
		
		var main_form = $('form.wpforo-main-form[data-textareaid]');
		var wrap = main_form.closest('.wpf-form-wrapper');
		wrap.show();
		
		$('.wpf-reply-form-title').html(wpforo_phrase('Leave a reply'));
		
		var post_wrap = $(this).closest('[id^=post-][data-postid]');
		var parentpostid = post_wrap.data('postid');
		if (!parentpostid) parentpostid = 0;
		$('.wpf-form-post-parentid', main_form).val(parentpostid);
		
		var userid = parseInt(post_wrap.data('userid'));
		var mention = post_wrap.data('mention');
		var isowner = parseInt(post_wrap.data('isowner'));
		var content = (!isowner && userid && mention ? '@' + mention + (wpforo_editor.is_tinymce(wpforo_editor.get_main()) ? '&nbsp;' : ' ') : '');
		
		wpforo_editor.set_content(content, wpforo_editor.get_main());
		
		$('html').scrollTop(wrap.offset().top);
		
		wpforo_load_hide();
		
	});
	
	//Answer
	wpforo_wrap.on('click', '.wpforo-answer', function () {
		wpforo_load_show();
		
		var main_form = $('form.wpforo-main-form[data-textareaid]');
		var wrap = main_form.closest('.wpf-form-wrapper');
		wrap.show();
		
		$('.wpf-reply-form-title').html(wpforo_phrase('Your answer'));
		
		$('.wpf-form-postid', main_form).val(0);
		$('.wpf-form-post-parentid', main_form).val(0);
		
		var post_wrap = $(this).closest('[id^=post-][data-postid]');
		
		var userid = parseInt(post_wrap.data('userid'));
		var mention = post_wrap.data('mention');
		var isowner = parseInt(post_wrap.data('isowner'));
		var content = (!isowner && userid && mention ? '@' + mention + (wpforo_editor.is_tinymce(wpforo_editor.get_main()) ? '&nbsp;' : ' ') : '');
		
		wpforo_editor.set_content(content, wpforo_editor.get_main());
		
		$('html').scrollTop(wrap.offset().top);
		
		wpforo_load_hide();
		
	});
	
	wpforo_wrap.on('click', '.wpforo-qa-comment, .wpforo-reply.wpf-action.wpforo_layout_4', function () {
		var wrap = $(this).parents('.reply-wrap,.wpforo-qa-item-wrap');
		var post_wrap = $('.post-wrap', wrap);
		if (!post_wrap.length) post_wrap = wrap;
		var parentid = post_wrap.data('postid');
		if (!parentid) parentid = post_wrap.attr('id').replace('post-', '');
		if (!parentid) parentid = 0;
		var form = $('.wpforo-post-form');
		var textareaid = form.data('textareaid');
		var textarea_wrap = $('.wpf_post_form_textarea_wrap', form);
		var textarea = $('#' + textareaid, textarea_wrap);
		var textareatype = textarea_wrap.data('textareatype');
		$('.wpf_post_parentid').val(parentid);
		$('.wpforo-qa-comment,.wpforo-reply.wpf-action.wpforo_layout_4').show();
		$(this).hide();
		$('.wpforo-portable-form-wrap', wrap).show();
		if (!$('.wpforo-post-form', wrap).length) form.appendTo($('.wpforo-portable-form-wrap', wrap));
		
		form.show();
		if (textareatype && textareatype === 'rich_editor') {
			textarea_wrap.html('<textarea id="' + textareaid + '" class="wpf_post_body" name="post[body]"></textarea>');
			wpforo_tinymce_initializeIt('#' + textareaid);
		} else {
			textarea.val('');
			textarea.trigger('focus');
		}
		
		var userid = parseInt(post_wrap.data('userid'));
		var mention = post_wrap.data('mention');
		var isowner = parseInt(post_wrap.data('isowner'));
		var content = (!isowner && userid && mention ? '@' + mention + (wpforo_editor.is_tinymce(textareaid) ? '&nbsp;' : ' ') : '');
		
		wpforo_editor.set_content(content, textareaid);
	});
	
	wpforo_wrap.on('click', '.wpf-button-close-form', function () {
		$(this).parents('.wpforo-portable-form-wrap').hide();
		$('.wpforo-post-form').hide();
		$('.wpforo-qa-comment,.wpforo-reply.wpf-action.wpforo_layout_4').show();
		wpforo_editor.set_content('');
	});
	
	//mobile menu responsive toggle
	wpforo_wrap.on('click', '#wpforo-menu .wpf-res-menu', function () {
		$('#wpforo-menu .wpf-menu').toggle();
	});
	var wpfwin = $(window).width();
	var wpfwrap = wpforo_wrap.width();
	if (wpfwin >= 602 && wpfwrap < 800) {
		wpforo_wrap.on('focus', '#wpforo-menu .wpf-search-field', function () {
			$('#wpforo-menu .wpf-menu li').hide();
			wpforo_wrap.find('#wpforo-menu .wpf-res-menu').show();
			$('#wpforo-menu .wpf-search-field').css('transition-duration', '0s');
		});
		wpforo_wrap.on('blur', '#wpforo-menu .wpf-search-field', function () {
			wpforo_wrap.find('#wpforo-menu .wpf-res-menu').hide();
			$('#wpforo-menu .wpf-menu li').show();
			$('#wpforo-menu .wpf-search-field').css('transition-duration', '0.4s');
		});
	}
	
	// password show/hide switcher */
	wpforo_wrap.on('click', '.wpf-show-password', function () {
		var btn = $(this);
		var parent = btn.parents('.wpf-field-wrap');
		var input = $(':input', parent);
		if (input.attr('type') === 'password') {
			input.attr('type', 'text');
			btn.removeClass('fa-eye-slash');
			btn.addClass('fa-eye');
		} else {
			input.attr('type', 'password');
			btn.removeClass('fa-eye');
			btn.addClass('fa-eye-slash');
		}
	});
	
	//Turn off on dev mode
	//$(window).on('resize', function(){ if (window.RT) { clearTimeout(window.RT); } window.RT = setTimeout(function(){ this.location.reload(false);}, 100); });
	
	wpforo_wrap.on('change', '#wpforo_split_form #wpf_split_create_new', function () {
		var checked    = $('#wpf_split_create_new').is(':checked'),
			target_url = $('#wpf_split_target_url'),
			append     = $('#wpf_split_append'),
			new_title  = $('#wpf_split_new_title'),
			forumid    = $('#wpf_split_forumid');
		if (checked) {
			target_url.children('input').prop('disabled', true);
			target_url.hide();
			append.children('input').prop('disabled', true);
			append.hide();
			new_title.children('input').prop('disabled', false);
			new_title.show();
			forumid.children('select').prop('disabled', false);
			forumid.show();
		} else {
			target_url.children('input').prop('disabled', false);
			target_url.show();
			append.children('input').prop('disabled', false);
			append.show();
			new_title.children('input').prop('disabled', true);
			new_title.hide();
			forumid.children('select').prop('disabled', true);
			forumid.hide();
		}
	});
	
	//Facebook Share Buttons
	wpforo_wrap.on('click', '.wpf-fb', function () {
		var item_url = $(this).data('wpfurl');
		var item_quote = $(this).parents('.post-wrap').find('.wpforo-post-content').text();
		FB.ui({
			method: 'share',
			href: item_url,
			quote: item_quote,
			hashtag: null,
		}, function (response) {});
	});
	//Share Buttons Toggle
	wpforo_wrap.on('mouseover', '.wpf-sb', function () {
		$(this).find('.wpf-sb-toggle').find('i').addClass('wpfsa');
		$(this).find('.wpf-sb-buttons').show();
	});
	wpforo_wrap.on('mouseout', '.wpf-sb', function () {
		$(this).find('.wpf-sb-toggle').find('i').removeClass('wpfsa');
		$(this).find('.wpf-sb-buttons').hide();
	});
	wpforo_wrap.on('mouseover', '.wpf-sb-toggle', function () {
		$(this).next().filter('.wpf-sb-buttons').parent().find('i').addClass('wpfsa');
	});
	wpforo_wrap.on('mouseout', '.wpf-sb-toggle', function () {
		$(this).next().filter('.wpf-sb-buttons').parent().find('i').removeClass('wpfsa');
	});
	
	//Forum Rules
	wpforo_wrap.on('click', '#wpf-open-rules', function () {
		$('.wpforo-legal-rules').toggle();
		return false;
	});
	wpforo_wrap.on('click', '#wpflegal-rules-yes', function () {
		$('#wpflegal_rules').prop('checked', true);
		$('#wpflegal-rules-not').removeClass('wpflb-active-not');
		$(this).addClass('wpflb-active-yes');
		setTimeout(function () { $('.wpforo-legal-rules').slideToggle('slow'); }, 500);
	});
	wpforo_wrap.on('click', '#wpflegal-rules-not', function () {
		$('#wpflegal_rules').prop('checked', false);
		$('#wpflegal-rules-yes').removeClass('wpflb-active-yes');
		$(this).addClass('wpflb-active-not');
	});
	
	//Forum Privacy Buttons
	wpforo_wrap.on('click', '#wpf-open-privacy', function () {
		$('.wpforo-legal-privacy').toggle();
		return false;
	});
	wpforo_wrap.on('click', '#wpflegal-privacy-yes', function () {
		$('#wpflegal_privacy').prop('checked', true);
		$('#wpflegal-privacy-not').removeClass('wpflb-active-not');
		$(this).addClass('wpflb-active-yes');
		setTimeout(function () { $('.wpforo-legal-privacy').slideToggle('slow'); }, 500);
	});
	wpforo_wrap.on('click', '#wpflegal-privacy-not', function () {
		$('#wpflegal_privacy').prop('checked', false);
		$('#wpflegal-privacy-yes').removeClass('wpflb-active-yes');
		$(this).addClass('wpflb-active-not');
	});
	
	//Facebook Login Button
	wpforo_wrap.on('click', '#wpflegal_fblogin', function () {
		if ($(this).is(':checked')) {
			$('.wpforo_fb-button').attr('style', 'pointer-events:auto; opacity:1;');
		} else {
			$('.wpforo_fb-button').attr('style', 'pointer-events: none; opacity:0.6;');
		}
	});
	
	wpforo_wrap.on('click', '.wpf-load-threads .wpf-forums', function () {
		$('.wpf-cat-forums', $(this).parents('div.wpfl-4')).slideToggle('slow');
		$('i', $(this)).toggleClass('fa-chevron-down fa-chevron-up');
	});
	
	wpforo_wrap.on('click', '[data-copy-wpf-furl], [data-copy-wpf-shurl]', function () {
		var urls = [];
		var full_url = $(this).data('copy-wpf-furl');
		if (full_url) urls.push(decodeURIComponent(full_url));
		var short_url = $(this).data('copy-wpf-shurl');
		if (short_url) urls.push(decodeURIComponent(short_url));
		if (urls.length) {
			var label = '';
			var html = '';
			urls.forEach(function (url, i) {
				label = (urls.length === 2 && i === 1) ? wpforo_phrase('Short') : wpforo_phrase('Full');
				html += '<div class="wpforo-copy-url-wrap">' +
						'<div class="wpforo-copy-input">' +
						'<div class="wpforo-copy-input-header">' +
						'<label class="wpforo-copy-url-label">' +
						'<i class="fas fa-link wpfsx"></i>' +
						'<span class="wpforo-copy-url-label-txt">' + label + '</span>' +
						'</label>' +
						'</div>' +
						'<div class="wpforo-copy-input-body">' +
						'<input dir="ltr" readonly class="wpforo-copy-url" type="text" value="' + url + '">' +
						'</div>' +
						'</div>' +
						'<div class="wpforo-copied-txt"><span>' + wpforo_phrase('Copied') + '</span></div>' +
						'</div>';
			});
			var title = wpforo_phrase('Share Urls');
			wpforo_dialog_show(title, html, '40%', '260px');
		}
	});
	
	$(document).on('click', '.wpforo-copy-url-wrap', function () {
		var wrap = $(this);
		var input = $('input.wpforo-copy-url', wrap);
		if (input.length) {
			input[0].select();
			if (document.execCommand('copy')) {
				wrap.addClass('wpforo-copy-animate');
				setTimeout(function () {
					wrap.removeClass('wpforo-copy-animate');
				}, 1000);
			}
		}
	});
	
	wpforo_wrap.on('click', '.wpf-toggle .wpf-toggle-advanced', function () {
		var wrap = $(this).closest('.wpf-toggle-wrap');
		$('.wpf-ico', $(this)).toggleClass('fa-chevron-down fa-chevron-up');
		$('.wpf-search-advanced-fields', wrap).slideToggle(350);
	});
	
	wpforo_wrap.on('click', '.wpf-toggle .wpf-toggle-custom', function () {
		var wrap = $(this).closest('.wpf-toggle-wrap');
		$('.wpf-ico', $(this)).toggleClass('fa-chevron-down fa-chevron-up');
		$('.wpf-search-custom-fields', wrap).slideToggle(350);
	});
	
	wpforo_wrap.on('click', 'form[data-textareaid] .wpforo-delete-custom-file', function () {
		if (confirm(wpforo_phrase('Are you sure you want to delete this file?'))) {
			var wrap = $(this).closest('.wpf-field-file-wrap');
			var fieldKey = $(this).data('fieldkey');
			if (fieldKey) wrap.html('<input type="hidden" name="wpftcf_delete[]" value="' + fieldKey + '">');
		}
	});
	
	wpforo_wrap.on('change', '#wpf-profile-action', function () {
		var val = $(this).val();
		var exreg = new RegExp('^https?://', 'i');
		if (val.match(exreg)) location.href = val;
	});
});

/**
 *
 * @param { jQuery|Element|Document } element
 * @param { string } name
 * @param { string|number|integer } value
 */
function wpforo_setAttr (element, name, value) {
	if (element instanceof $wpf || element instanceof Element) {
		element = $wpf(element);
		element.prop(name, value);
		element.attr(name, value);
		
		var match = name.match(new RegExp('^data-(.+)', 'i'));
		if (match) element.data(match[1], value);
	}
}

function wpforo_confirm (msg, e) {
	if (!confirm(msg)) {
		e = e || window.event;
		e.stopPropagation();
		e.preventDefault();
		return false;
	}
	
	return true;
}

class wpfMultiInput extends HTMLElement {
	constructor () {
		super();
		this.innerHTML += `<style>
            wpf-multi-input input::-webkit-calendar-picker-indicator,
            wpf-multi-input datalist{
              display: none !important;
            }
            #wpforo #wpforo-wrap wpf-multi-input .wpf-invalid-msg{
              display: none;
              color: red;
              font-weight: bold;
            }
            #wpforo #wpforo-wrap .wpf-multi-item > .wpf-multi-item-action{
                margin: 0 3px;
                cursor: pointer;
            }
            #wpforo #wpforo-wrap .wpf-multi-item > .wpf-multi-item-action:hover > i{
                color: black;
                transform: scale(1.2);
            }
            #wpforo #wpforo-wrap .wpf-multi-item.wpf-multi-item-known-1 > .wpf-save-variant{
                display: none !important;
            }
        </style>`;
		this._shadowRoot = this.attachShadow({ mode: 'open' });
		this._shadowRoot.innerHTML = `<style>
            /* NB use of pointer-events to only allow events from the × icon */
            ::slotted(div.wpf-multi-item) {
              background-color: var(--wpf-multi-input-item-bg-color, #dedede) !important;
              border: var(--wpf-multi-input-item-border, 1px solid #ccc) !important;
              border-radius: 2px !important;
              color: #222 !important;
              display: inline-block !important;
              font-size: var(--wpf-multi-input-item-font-size, 14px) !important;
              margin: 5px !important;
              padding: 2px 5px 2px 5px !important;
              position: relative !important;
              top: -1px !important;
            }
            ::slotted(div.wpf-multi-item:hover) {
              background-color: #eee !important;
              color: black !important;
            }
            ::slotted(div.wpf-multi-item > .wpf-multi-item-action){
                margin: 0 3px;
                cursor: pointer;
            }
            ::slotted(.wpf-multi-item.wpf-multi-item-known-1 > .wpf-save-variant){
                display: none !important;
            }
            ::slotted(div.wpf-multi-item > .wpf-multi-item-action:hover > i){
                color: black;
                transform: scale(1.2);
            }
            ::slotted(input){
                border: 2px solid transparent !important;
            }
            ::slotted(input.wpf-invalid){
                border-color: red !important;
            }
        </style><slot></slot>`;
		
		this._datalist = this.querySelector('datalist');
		this._dbVariants = [];
		const dbvariants = this.dataset['dbvariants'];
		if (dbvariants) this._dbVariants = JSON.parse(dbvariants);
		
		this._input = this.querySelector('input');
		this._input.onblur = this._handleBlur.bind(this);
		this._input.oninput = this._handleInput.bind(this);
		this._input.onkeyup = (event) => {
			this._handleKeyup(event);
		};
		this._input.form.onsubmit = (event) => {
			this._handleSubmit(event);
		};
		this._items = [...this.querySelectorAll('.wpf-multi-item')];
		this._items.forEach((item) => {
			item.querySelector('.wpf-del').onclick = () => {
				this._deleteItem(item);
			};
			const save_button = item.querySelector('.wpf-save-variant');
			if (save_button) {
				save_button.onclick = () => {
					this._saveItem(item);
				};
			}
		});
		
		this._allowDuplicates = this.hasAttribute('allow-duplicates') || this._input.hasAttribute('allow-duplicates');
		this._allowCustomValues = this.hasAttribute('allow-custom-values') || this._input.hasAttribute('allow-custom-values');
		this._isRequired = this.hasAttribute('required') || this._input.hasAttribute('required');
		this._input.removeAttribute('required');
		
		this._invalidMsg = this.querySelector('.wpf-invalid-msg');
	}
	
	// Called by _handleKeydown() when the value of the input is an allowed value.
	_addItem (value) {
		if (!value.trim()) return;
		if (!this._allowCustomValues && !this._dbVariants.includes(value)) return;
		if (!this._allowDuplicates && this.getValues().includes(value)) return;
		this._input.value = '';
		const item = document.querySelector('template#wpf-multi-item-template').cloneNode(true).content.firstChild;
		item.classList.remove('wpf-multi-item-known-0', 'wpf-multi-item-known-1');
		item.classList.add((this._dbVariants.includes(value) ? 'wpf-multi-item-known-1' : 'wpf-multi-item-known-0'));
		item.querySelector('.wpf-multi-item-value').textContent = value;
		item.querySelector('.wpf-del').onclick = () => {
			this._deleteItem(item);
		};
		const save_button = item.querySelector('.wpf-save-variant');
		if (save_button) {
			save_button.onclick = () => {
				this._saveItem(item);
			};
		}
		this.insertBefore(item, this._input);
		
		// Remove value from datalist options and from _allowedValues array.
		// Value is added back if an item is deleted (see _deleteItem()).
		if (!this._allowDuplicates && this._dbVariants.includes(value)) {
			for (const option of this._datalist.options) {
				if (option.value === value) {
					option.remove();
				}
			}
		}
	}
	
	// Called when the × icon is tapped/clicked or
	// by _handleKeydown() when Backspace is entered.
	_deleteItem (item) {
		if (!item.classList.contains('wpf-multi-item')) return;
		const value = item.querySelector('.wpf-multi-item-value').textContent;
		item.remove();
		// If duplicates aren't allowed, value is removed (in _addItem())
		// as a datalist option and from the _allowedValues array.
		// So — need to add it back here.
		if (!this._allowDuplicates && this._dbVariants.includes(value)) {
			const option = document.createElement('option');
			option.value = value;
			// Insert as first option seems reasonable...
			this._datalist.insertBefore(option, this._datalist.firstChild);
		}
	}
	
	_saveItem (item) {
		if (!item.classList.contains('wpf-multi-item')) return;
		const value = item.querySelector('.wpf-multi-item-value').textContent;
		
		if (typeof value === 'string') {
			if (item.parentNode instanceof Node && item.parentNode.dataset && typeof item.parentNode.dataset['fieldkey'] === 'string') {
				this._ajaxSaveItem(+item.parentNode.dataset['formid'], item.parentNode.dataset['fieldkey'].trim(), value, () => {
					item.classList.remove('wpf-multi-item-known-0');
					item.classList.add('wpf-multi-item-known-1');
				});
			}
		}
	}
	
	_ajaxSaveItem (formid, fieldKey, value, resolve, reject) {
		if (!value.trim()) return;
		wpforo_load_show('Saving...');
		
		$wpf.ajax({
			type: 'POST',
			data: {
				formid,
				fieldKey,
				value,
				action: 'wpforotcf_append_field_values',
				_wpfnonce: wpforo['nonces']['wpforotcf_append_field_values'],
			},
		}).done(function (r) {
			if (r.success) {
				if (resolve) resolve();
			} else {
				if (reject) reject();
			}
		}).always(function () {
			wpforo_load_hide();
		});
	}
	
	// Avoid stray text remaining in the input element that's not in a div.item.
	_handleBlur () {
		this._input.value = '';
	}
	
	// Called when input text changes,
	// either by entering text or selecting a datalist option.
	_handleInput () {
		// Add a div.item, but only if the current value
		// of the input is an allowed value
		const match = this._dbVariants.find(v => v.toLowerCase() === this._input.value.toLowerCase());
		if (match !== undefined) this._addItem(match);
	}
	
	// Called when text is entered or keys pressed in the input element.
	_handleKeyup (event) {
		const itemToDelete = event.target.previousElementSibling;
		const value = this._input.value;
		
		// On Backspace, delete the div.item to the left of the input
		if (value === '' && event.key === 'Backspace' && itemToDelete) {
			this._deleteItem(itemToDelete);
			// Add a div.item, but only if the current value
			// of the input is an allowed value
		} else if (event.key === 'Enter' && value.trim()) {
			if (this._allowCustomValues) {
				event.preventDefault();
				this._addItem(value);
			} else if (this._dbVariants.includes(value) && !this.getValues().includes(value)) {
				event.preventDefault();
				this._addItem(value);
			}
		}
	}
	
	_handleSubmit (event) {
		if (this._input.value) {
			event.preventDefault();
			setTimeout(wpforo_load_hide, 100);
			if (this._allowCustomValues) this._addItem(this._input.value);
			this._input.value = '';
			return;
		}
		
		const values = this.getValues();
		if (this._isRequired && !values.length) {
			event.preventDefault();
			this._input.classList.add('wpf-invalid');
			this._invalidMsg.style = 'display: inline-block';
			let i = 0;
			const intervalHendler = setInterval(() => {
				this._input.classList.toggle('wpf-invalid');
				if (i++ >= 4) {
					this._input.classList.add('wpf-invalid');
					wpforo_load_hide();
					clearInterval(intervalHendler);
					setTimeout(() => {
						this._input.classList.remove('wpf-invalid');
						this._invalidMsg.style = 'display: none;';
					}, 3000);
				}
			}, 50);
		} else if (values.length) {
			const name = this._input.name;
			this._input.removeAttribute('name');
			values.forEach((val) => {
				const hidden = document.createElement('input');
				hidden.type = 'hidden';
				hidden.name = name;
				hidden.value = val;
				this._input.form.append(hidden);
			});
		}
	}
	
	// Public method for getting item values as an array.
	getValues () {
		const values = [];
		const items = this.querySelectorAll('.wpf-multi-item');
		for (const item of items) {
			values.push(item.querySelector('.wpf-multi-item-value').textContent);
		}
		return values;
	}
}

window.customElements.define('wpf-multi-input', wpfMultiInput);

class wpfAutocompleteInput extends HTMLElement {
	constructor () {
		super();
		this.innerHTML += `<style>
            wpf-autocomplete-input input::-webkit-calendar-picker-indicator,
            wpf-autocomplete-input datalist{
              display: none !important;
            }
            #wpforo #wpforo-wrap wpf-autocomplete-input .wpf-invalid-msg{
              display: none;
              color: red;
              font-weight: bold;
            }
        </style>`;
		this._shadowRoot = this.attachShadow({ mode: 'open' });
		this._shadowRoot.innerHTML = `<style>
            ::slotted(input){
                border: 2px solid transparent !important;
            }
            ::slotted(input.wpf-invalid){
                border-color: red !important;
            }
        </style><slot></slot>`;
		this._datalist = this.querySelector('datalist');
		this._dbVariants = [];
		const dbvariants = this.dataset['dbvariants'];
		if (dbvariants) this._dbVariants = JSON.parse(dbvariants);
		
		this._input = this.querySelector('input');
		this._input.form.onsubmit = (event) => {
			this._handleSubmit(event);
		};
		
		this._allowCustomValues = this.hasAttribute('allow-custom-values') || this._input.hasAttribute('allow-custom-values');
		this._isRequired = this.hasAttribute('required') || this._input.hasAttribute('required');
		this._invalidMsgRequired = this.querySelector('.wpf-invalid-msg.wpf-required');
		this._invalidMsgNotAllowedValue = this.querySelector('.wpf-invalid-msg.wpf-not-allowed-value');
	}
	
	_handleSubmit (event) {
		const value = this._input.value;
		if (!this._isRequired && !value) return;
		let prevent = false;
		if (this._isRequired && !value) {
			this._invalidMsg = this._invalidMsgRequired;
			prevent = true;
		} else if (!this._allowCustomValues && !this._dbVariants.includes(value)) {
			this._invalidMsg = this._invalidMsgNotAllowedValue;
			prevent = true;
		}
		if (prevent) {
			event.preventDefault();
			wpforo_load_hide();
			this._input.classList.add('wpf-invalid');
			this._invalidMsg.style = 'display: inline-block';
			let i = 0;
			const intervalHendler = setInterval(() => {
				this._input.classList.toggle('wpf-invalid');
				if (i++ >= 4) {
					this._input.classList.add('wpf-invalid');
					wpforo_load_hide();
					clearInterval(intervalHendler);
					setTimeout(() => {
						this._input.classList.remove('wpf-invalid');
						this._invalidMsg.style = 'display: none;';
					}, 3000);
				}
			}, 50);
		}
	}
}

window.customElements.define('wpf-autocomplete-input', wpfAutocompleteInput);


