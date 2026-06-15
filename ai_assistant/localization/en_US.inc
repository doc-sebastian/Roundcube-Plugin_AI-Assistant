<?php

/**
 * AI Assistant plugin - English localization (default)
 */

$labels = [];
$labels['aibutton'] = 'AI Reply';
$labels['aibutton_title'] = 'Compose an e-mail with AI';
$labels['summarizebutton'] = 'AI Summary';
$labels['summarizebutton_title'] = 'Summarize this message with AI';

$labels['dialog_title'] = 'AI Assistant – Compose e-mail';
$labels['summary_title'] = 'AI Summary';

$labels['prompt_label'] = 'Your instructions for the AI';
$labels['prompt_placeholder'] = 'e.g. "Politely confirm the meeting on Friday and suggest Monday 10 am as an alternative"';

$labels['style_label'] = 'Style';
$labels['style_assertive'] = 'Assertive';
$labels['style_casual'] = 'Casual';
$labels['style_enthusiastic'] = 'Enthusiastic';
$labels['style_funny'] = 'Funny';
$labels['style_informational'] = 'Informational';
$labels['style_professional'] = 'Professional';
$labels['style_urgent'] = 'Urgent';
$labels['style_witty'] = 'Witty';

$labels['length_label'] = 'Length';
$labels['length_short'] = 'Short';
$labels['length_medium'] = 'Medium';
$labels['length_long'] = 'Long';

$labels['language_label'] = 'Language';
$labels['lang_auto'] = 'Automatic (same as input)';

$labels['creativity_label'] = 'Creativity';
$labels['creativity_low'] = 'Straightforward';
$labels['creativity_high'] = 'Imaginative';

$labels['usecontext'] = 'Send existing message text as context';
$labels['preview_label'] = 'Preview (can be edited before inserting)';

$labels['generate'] = 'Generate';
$labels['regenerate'] = 'Regenerate';
$labels['insert'] = 'Insert';
$labels['close'] = 'Close';

$labels['generating'] = 'Generating AI text...';
$labels['summarizing'] = 'Creating summary...';
$labels['inserted'] = 'The text has been inserted.';

$labels['settings_section'] = 'AI Assistant';
$labels['provider_block'] = 'AI provider (credentials)';
$labels['api_url'] = 'API URL';
$labels['api_model'] = 'Model';
$labels['api_key'] = 'API key';
$labels['api_key_hint'] = 'Stored encrypted. Usually empty for local providers (LocalAI, Ollama). Leave empty to keep the stored key.';
$labels['defaults_block'] = 'Default settings';
$labels['default_style'] = 'Default style';
$labels['default_length'] = 'Default length';
$labels['default_language'] = 'Default language';
$labels['default_creativity'] = 'Default creativity (0–100)';
$labels['default_instruction'] = 'Default instruction';
$labels['default_instruction_placeholder'] = 'e.g. "Write a polite, professional reply."';
$labels['default_instruction_hint'] = 'This text is pre-filled as the instruction when the AI assistant opens.';

$labels['prompts_block'] = 'AI prompts (advanced)';
$labels['prompt_compose'] = 'Prompt: New e-mail';
$labels['prompt_reply'] = 'Prompt: Reply';
$labels['prompt_summary'] = 'Prompt: Summary';
$labels['prompts_hint'] = 'Available placeholders: %style%, %length%, %language%. Leave fields empty to use the default prompts.';

$messages = [];
$messages['error_generic'] = 'An error occurred during the AI request.';
$messages['error_api'] = 'Error from the AI service:';
$messages['error_connection'] = 'The AI service could not be reached.';
$messages['error_emptyprompt'] = 'Please enter your instructions first.';
$messages['error_notconfigured'] = 'The AI plugin is not configured yet. Please contact your administrator.';
$messages['error_nomessage'] = 'The message could not be read.';
