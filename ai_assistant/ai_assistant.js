/**
 * AI Assistant ("KI Antwort") plugin for Roundcube - client script
 *
 * Adds the AI compose dialog to the message composition screen and the
 * AI summary action to the message view.
 *
 * @license GNU GPLv3+
 */

(function () {
    'use strict';

    if (!window.rcmail) {
        return;
    }

    var ai_popup = null;

    rcmail.addEventListener('init', function () {
        if (rcmail.env.action === 'compose') {
            rcmail.register_command('plugin.ai_assistant.open', ai_open_dialog, true);
        }

        if (rcmail.env.task === 'mail' && rcmail.env.action !== 'compose') {
            rcmail.register_command('plugin.ai_assistant.summarize', ai_summarize);

            if (rcmail.env.action === 'show' || rcmail.env.action === 'preview') {
                rcmail.enable_command('plugin.ai_assistant.summarize', true);
            } else if (rcmail.message_list) {
                // main mailbox view: enable when a message is selected
                rcmail.message_list.addEventListener('select', function (list) {
                    rcmail.enable_command('plugin.ai_assistant.summarize', list.get_selection(false).length > 0);
                });
            }
        }

        if (rcmail.env.task === 'settings') {
            mark_settings_section();
        }

        rcmail.addEventListener('plugin.ai_assistant.result', ai_on_result);
        rcmail.addEventListener('plugin.ai_assistant.summary', ai_on_summary);
        rcmail.addEventListener('plugin.ai_assistant.error', ai_on_error);
    });

    // Add an icon class to our settings section row (done in JS to avoid
    // fighting Elastic's CSS specificity).
    function mark_settings_section() {
        var apply = function () {
            var row = $('#rcmrowai_assistant, tr.ai_assistant, li.ai_assistant');
            
            // Add icon to context menu items
            if (rcmail.env.task === 'mail') {
                setTimeout(function() {
                    $('a[command="plugin.ai_assistant.summarize"]').addClass('ai-assistant-menu-icon');
                }, 100);
            }
            if (!row.length) {
                row = $('a').filter(function () {
                    var oc = ($(this).attr('onclick') || '') + ($(this).attr('href') || '');
                    return oc.indexOf('ai_assistant') > -1 && oc.indexOf('preferences') > -1;
                }).closest('tr, li');
            }
            row.addClass('ai-section-icon');
        };
        apply();
        setTimeout(apply, 300);
        setTimeout(apply, 1000);
    }

    function t(name) {
        return rcmail.gettext(name, 'ai_assistant');
    }

    function conf() {
        return rcmail.env.ai_assistant || {};
    }

    function is_reply_mode() {
        return rcmail.env.compose_mode === 'reply' || rcmail.env.compose_mode === 'forward';
    }

    // ------------------------------------------------------------------
    // Compose dialog
    // ------------------------------------------------------------------

    function ai_open_dialog() {
        var cfg = conf(),
            defaults = cfg.defaults || {},
            form = $('<div class="ai-assistant-form">');

        // user instruction (pre-filled with the configured default instruction)
        var prompt = $('<textarea id="ai-prompt" class="form-control" rows="3">')
            .attr('placeholder', t('prompt_placeholder'));

        if (cfg.default_instruction) {
            prompt.val(cfg.default_instruction);
        }

        form.append(
            field('ai-prompt', t('prompt_label'), prompt)
        );

        // style / length / language
        var style = build_select('ai-style', $.map(cfg.styles || [], function (s) {
            return { value: s, label: t('style_' + s) };
        }), defaults.style);

        var length = build_select('ai-length', $.map(cfg.lengths || [], function (l) {
            return { value: l, label: t('length_' + l) };
        }), defaults.length);

        var language = build_select('ai-language', $.map(cfg.languages || [], function (l) {
            return { value: l.code, label: l.code === 'auto' ? t('lang_auto') : l.name };
        }), defaults.language);

        form.append(
            $('<div class="ai-assistant-row">')
                .append(field('ai-style', t('style_label'), style))
                .append(field('ai-length', t('length_label'), length))
                .append(field('ai-language', t('language_label'), language))
        );

        // creativity slider
        var creativity = $('<input type="range" id="ai-creativity" min="0" max="100" step="5">')
            .val(defaults.creativity != null ? defaults.creativity : 50);

        form.append(
            $('<div class="form-group">')
                .append($('<label for="ai-creativity">').text(t('creativity_label')))
                .append(creativity)
                .append(
                    $('<div class="ai-creativity-labels">')
                        .append($('<span>').text(t('creativity_low')))
                        .append($('<span>').text(t('creativity_high')))
                )
        );

        // context checkbox (pre-checked for replies/forwards)
        var has_body = $.trim(get_body_text()).length > 0;
        var context = $('<input type="checkbox" id="ai-context" class="form-check-input">')
            .prop('checked', is_reply_mode() && has_body)
            .prop('disabled', !has_body);

        form.append(
            $('<div class="form-check ai-context-check">')
                .append(context)
                .append($('<label for="ai-context" class="form-check-label">').text(t('usecontext')))
        );

        // preview (hidden until the first result arrives)
        form.append(
            $('<div class="form-group ai-preview" style="display:none">')
                .append($('<label for="ai-preview-text">').text(t('preview_label')))
                .append($('<textarea id="ai-preview-text" class="form-control" rows="10">'))
        );

        var buttons = [
            { text: t('generate'), 'class': 'mainaction generate', click: do_generate },
            { text: t('insert'),   'class': 'insert',              click: do_insert },
            { text: t('close'),    'class': 'cancel',              click: function () { ai_popup.dialog('close'); } }
        ];

        ai_popup = rcmail.show_popup_dialog(form, t('dialog_title'), buttons, {
            width: 640,
            resizable: true,
            classes: { 'ui-dialog': 'ai-assistant-dialog' },
            close: function () { ai_popup = null; }
        });

        btn('insert').prop('disabled', true);
        prompt.focus();
    }

    function field(id, label, input) {
        return $('<div class="form-group">')
            .append($('<label>').attr('for', id).text(label))
            .append(input);
    }

    function build_select(id, options, selected) {
        var select = $('<select class="form-control custom-select">').attr('id', id);

        $.each(options, function (i, option) {
            select.append($('<option>').val(option.value).text(option.label));
        });

        if (selected) {
            select.val(selected);
        }

        return select;
    }

    function btn(cls) {
        return ai_popup
            ? ai_popup.parent().find('.ui-dialog-buttonpane button.' + cls + ', .ui-dialog-buttonset button.' + cls)
            : $();
    }

    function do_generate() {
        var prompt = $.trim($('#ai-prompt').val());

        if (!prompt) {
            rcmail.display_message(t('error_emptyprompt'), 'warning');
            $('#ai-prompt').focus();
            return;
        }

        var params = {
            _prompt:     prompt,
            _style:      $('#ai-style').val(),
            _length:     $('#ai-length').val(),
            _language:   $('#ai-language').val(),
            _creativity: $('#ai-creativity').val(),
            _mode:       is_reply_mode() ? 'reply' : 'compose',
            _subject:    $('#compose-subject').val() || $('input[name="_subject"]').val() || ''
        };

        if ($('#ai-context').prop('checked')) {
            params._context = get_body_text();
        }

        btn('generate').prop('disabled', true);

        var lock = rcmail.set_busy(true, 'ai_assistant.generating');
        rcmail.http_post('plugin.ai_assistant.generate', params, lock);
    }

    function ai_on_result(data) {
        btn('generate').prop('disabled', false);

        if (!ai_popup) {
            return;
        }

        $('#ai-preview-text').val(data && data.text ? data.text : '');
        ai_popup.find('.ai-preview').show();
        btn('insert').prop('disabled', false);
        btn('generate').text(t('regenerate'));
    }

    function ai_on_error(data) {
        btn('generate').prop('disabled', false);
        rcmail.display_message((data && data.message) || t('error_generic'), 'error');
    }

    function do_insert() {
        var text = $('#ai-preview-text').val();

        if (!text) {
            return;
        }

        insert_into_editor(text);

        if (ai_popup) {
            ai_popup.dialog('close');
        }

        rcmail.display_message(t('inserted'), 'confirmation');
    }

    // ------------------------------------------------------------------
    // Editor helpers
    // ------------------------------------------------------------------

    function insert_into_editor(text) {
        // HTML mode (TinyMCE)
        if (rcmail.editor && rcmail.editor.editor) {
            var html = $('<div>').text(text).html().replace(/\r?\n/g, '<br>');
            rcmail.editor.editor.execCommand('mceInsertContent', false, html + '<br>');
            rcmail.editor.focus();
            return;
        }

        // plain text mode: insert at cursor position
        var el = document.getElementById('composebody')
            || (rcmail.env.composebody && document.getElementById(rcmail.env.composebody));

        if (!el) {
            return;
        }

        var start = el.selectionStart != null ? el.selectionStart : el.value.length,
            end = el.selectionEnd != null ? el.selectionEnd : start;

        el.value = el.value.substring(0, start) + text + el.value.substring(end);
        el.selectionStart = el.selectionEnd = start + text.length;

        $(el).trigger('change');
        el.focus();
    }

    function get_body_text() {
        try {
            if (rcmail.editor) {
                return rcmail.editor.get_content({ format: 'text', nosig: false }) || '';
            }
        } catch (e) {
            // fall through to the plain textarea
        }

        var el = document.getElementById('composebody');
        return el ? el.value : '';
    }

    // ------------------------------------------------------------------
    // Message summary
    // ------------------------------------------------------------------

    function ai_summarize() {
        var uid = rcmail.env.uid,
            mbox = rcmail.env.mailbox;

        // in the main mailbox view, fall back to the selected message
        if (!uid && rcmail.message_list) {
            var sel = rcmail.message_list.get_selection(false);
            if (sel.length) {
                uid = sel[0];
            }
        }

        if (!uid) {
            return;
        }

        var lock = rcmail.set_busy(true, 'ai_assistant.summarizing');
        rcmail.http_post('plugin.ai_assistant.summarize', { _uid: uid, _mbox: mbox }, lock);
    }

    // Simple Markdown parser for basic formatting
    function simpleMarkdownParse(text) {
        if (!text) return '';
        
        // Convert headers
        text = text.replace(/^### (.*$)/gim, '<h3>$1</h3>');
        text = text.replace(/^## (.*$)/gim, '<h2>$1</h2>');
        text = text.replace(/^# (.*$)/gim, '<h1>$1</h1>');
        
        // Convert bold and italic
        text = text.replace(/\*\*(.*)\*\*/g, '<strong>$1</strong>');
        text = text.replace(/\*(.*)\*/g, '<em>$1</em>');
        
        // Convert links
        text = text.replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2">$1</a>');
        
        // Convert code blocks
        text = text.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
        text = text.replace(/`([^`]+)`/g, '<code>$1</code>');
        
        // Convert unordered lists
        text = text.replace(/^\* (.*$)/gim, '<li>$1</li>');
        text = text.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        
        // Convert ordered lists
        text = text.replace(/^\d+\. (.*$)/gim, '<li>$1</li>');
        
        // Convert paragraphs (double line breaks)
        text = text.replace(/\n\n/g, '</p><p>');
        text = '<p>' + text + '</p>';
        
        // Fix HTML tags that might be broken
        text = text.replace(/<p><h([1-6])>/g, '<h$1>');
        text = text.replace(/<\/h([1-6])><\/p>/g, '</h$1>');
        text = text.replace(/<p><ul>/g, '<ul>');
        text = text.replace(/<\/ul><\/p>/g, '</ul>');
        text = text.replace(/<p><\/p>/g, '<br>');
        text = text.replace(/<p><pre>/g, '<pre>');
        text = text.replace(/<\/pre><\/p>/g, '</pre>');
        
        return text;
    }

    function ai_on_summary(data) {
        var text = (data && data.text) || '';
        var content = $('<div class="ai-summary-text">').html(simpleMarkdownParse(text));

        rcmail.show_popup_dialog(content, t('summary_title'), [
            { text: t('close'), 'class': 'cancel', click: function () { $(this).dialog('close'); } }
        ], { width: 560 });
    }
})();
