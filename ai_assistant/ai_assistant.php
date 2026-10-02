<?php

/**
 * AI Assistant ("KI Antwort") plugin for Roundcube
 *
 * Adds an AI button to the compose toolbar that generates e-mail texts
 * via any OpenAI-compatible API (OpenAI, LocalAI, Ollama, vLLM, ...).
 *
 * Features:
 *  - 8 writing styles, 3 lengths, adjustable creativity (temperature)
 *  - compose in 38 languages (prompt language independent of output language)
 *  - reply mode: uses the original/quoted message as context
 *  - AI summary button in the message view (optional)
 *  - per-user default settings and customizable AI prompts
 *  - works with local LLMs for full privacy
 *
 * Installation: see README.md
 *
 * @author  Custom
 * @license GNU GPLv3+
 */
class ai_assistant extends rcube_plugin
{
    public $task = 'mail|settings';

    /** @var rcmail */
    private $rc;

    /** Available writing styles (keys are also used in prompts / localization) */
    private static $styles = [
        'assertive', 'casual', 'enthusiastic', 'funny',
        'informational', 'professional', 'urgent', 'witty',
    ];

    /** Length options => description that is inserted into the prompt */
    private static $lengths = [
        'short'  => 'short (2-4 sentences)',
        'medium' => 'medium (about one to two paragraphs)',
        'long'   => 'long and detailed (several paragraphs)',
    ];

    /**
     * Supported output languages: code => [native name, English name].
     * The native name is shown in the UI, the English name is used in the prompt.
     */
    private static $languages = [
        'de'    => ['Deutsch', 'German'],
        'en'    => ['English', 'English'],
        'es'    => ['Español', 'Spanish'],
        'fr'    => ['Français', 'French'],
        'it'    => ['Italiano', 'Italian'],
        'pt'    => ['Português', 'Portuguese'],
        'nl'    => ['Nederlands', 'Dutch'],
        'pl'    => ['Polski', 'Polish'],
        'ru'    => ['Русский', 'Russian'],
        'uk'    => ['Українська', 'Ukrainian'],
        'cs'    => ['Čeština', 'Czech'],
        'sk'    => ['Slovenčina', 'Slovak'],
        'hu'    => ['Magyar', 'Hungarian'],
        'ro'    => ['Română', 'Romanian'],
        'bg'    => ['Български', 'Bulgarian'],
        'el'    => ['Ελληνικά', 'Greek'],
        'tr'    => ['Türkçe', 'Turkish'],
        'ar'    => ['العربية', 'Arabic'],
        'he'    => ['עברית', 'Hebrew'],
        'hi'    => ['हिन्दी', 'Hindi'],
        'zh-CN' => ['中文（简体）', 'Simplified Chinese'],
        'zh-TW' => ['中文（繁體）', 'Traditional Chinese'],
        'ja'    => ['日本語', 'Japanese'],
        'ko'    => ['한국어', 'Korean'],
        'vi'    => ['Tiếng Việt', 'Vietnamese'],
        'th'    => ['ไทย', 'Thai'],
        'id'    => ['Bahasa Indonesia', 'Indonesian'],
        'ms'    => ['Bahasa Melayu', 'Malay'],
        'sv'    => ['Svenska', 'Swedish'],
        'da'    => ['Dansk', 'Danish'],
        'no'    => ['Norsk', 'Norwegian'],
        'fi'    => ['Suomi', 'Finnish'],
        'et'    => ['Eesti', 'Estonian'],
        'lv'    => ['Latviešu', 'Latvian'],
        'lt'    => ['Lietuvių', 'Lithuanian'],
        'hr'    => ['Hrvatski', 'Croatian'],
        'sl'    => ['Slovenščina', 'Slovenian'],
        'sr'    => ['Srpski', 'Serbian'],
    ];

    const DEFAULT_PROMPT_COMPOSE =
        "You are an AI assistant built into the Roundcube webmail client. "
        . "Write a complete, ready-to-send e-mail body based on the user's instructions.\n"
        . "- Write the e-mail in %language%.\n"
        . "- Use a %style% tone.\n"
        . "- The length of the e-mail should be %length%.\n"
        . "- Output ONLY the e-mail body: no subject line, no explanations, no markdown code fences.\n"
        . "- Use a fitting salutation and closing unless the user says otherwise.\n"
        . "- Do not invent facts; if information is missing, keep it generic instead of using placeholders.";

    const DEFAULT_PROMPT_REPLY =
        "You are an AI assistant built into the Roundcube webmail client. "
        . "The user wants to reply to an e-mail. Write a complete, ready-to-send reply based on the user's instructions.\n"
        . "- The original message is included after the user's instructions; write a suitable reply to it.\n"
        . "- Do not repeat or quote the original message in your reply.\n"
        . "- Write the reply in %language%.\n"
        . "- Use a %style% tone.\n"
        . "- The length of the reply should be %length%.\n"
        . "- Output ONLY the e-mail body: no subject line, no explanations, no markdown code fences.\n"
        . "- Use a fitting salutation and closing unless the user says otherwise.";

    const DEFAULT_PROMPT_SUMMARY =
        "You are an AI assistant built into the Roundcube webmail client. "
        . "Summarize the following e-mail concisely in %language%.\n"
        . "- Mention the key points, open questions and required actions or deadlines.\n"
        . "- Use markdown formatting for emphasis, lists, tables and structure.";

    /**
     * Plugin initialization
     */
    public function init()
    {
        $this->rc = rcmail::get_instance();

        $this->load_config();
        $this->add_texts('localization/', true);

        if ($this->rc->task == 'mail') {
            // AJAX endpoints
            $this->register_action('plugin.ai_assistant.generate', [$this, 'action_generate']);
            $this->register_action('plugin.ai_assistant.summarize', [$this, 'action_summarize']);

            // Compose window: add the "KI Antwort" button to the toolbar
            if ($this->rc->action == 'compose') {
                $this->include_assets();

                $this->add_button([
                    'type'       => 'link',
                    'command'    => 'plugin.ai_assistant.open',
                    'class'      => 'button ai-assistant disabled',
                    'classact'   => 'button ai-assistant',
                    'classsel'   => 'button ai-assistant pressed',
                    'label'      => 'ai_assistant.aibutton',
                    'title'      => 'ai_assistant.aibutton_title',
                    'innerclass' => 'inner',
                ], 'toolbar');

                $this->set_js_env();
            }

            // Message view + main list: "AI summary" button & context-menu entry
            if ($this->rc->config->get('ai_assistant_enable_summary', true)) {
                if (in_array($this->rc->action, ['show', 'preview'])) {
                    $this->include_assets();
                    $this->set_summary_env();
                    $this->add_button([
                        'type'       => 'link',
                        'command'    => 'plugin.ai_assistant.summarize',
                        'class'      => 'button ai-assistant ai-summary disabled',
                        'classact'   => 'button ai-assistant ai-summary',
                        'classsel'   => 'button ai-assistant ai-summary pressed',
                        'label'      => 'ai_assistant.summarizebutton',
                        'title'      => 'ai_assistant.summarizebutton_title',
                        'innerclass' => 'inner',
                    ], 'toolbar');
                }
                elseif ($this->rc->action == '' || $this->rc->action == 'list') {
                    // main mailbox view: command for the "More" menu / right-click menu
                    $this->include_assets();
                    $this->set_summary_env();
                    $this->add_button([
                        'type'       => 'link',
                        'command'    => 'plugin.ai_assistant.summarize',
                        'class'      => 'button ai-assistant ai-summary disabled',
                        'classact'   => 'button ai-assistant ai-summary',
                        'label'      => 'ai_assistant.summarizebutton',
                        'title'      => 'ai_assistant.summarizebutton_title',
                        'innerclass' => 'inner',
                    ], 'toolbar');
                }
            }
        }
        elseif ($this->rc->task == 'settings') {
            $this->include_stylesheet($this->local_skin_path() . '/ai_assistant.css');
            $this->include_script('ai_assistant.js');
            $this->register_action('plugin.ai_assistant.models', [$this, 'action_models']);
            $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
            $this->add_hook('preferences_list', [$this, 'prefs_list']);
            $this->add_hook('preferences_save', [$this, 'prefs_save']);
        }
    }

    /**
     * Include JS + skin CSS
     */
    private function include_assets()
    {
        $this->include_script('ai_assistant.js');
        $this->include_stylesheet($this->local_skin_path() . '/ai_assistant.css');
    }

    /**
     * Pass available options and user defaults to the JavaScript frontend
     */
    private function set_js_env()
    {
        $config = $this->rc->config;

        $languages = [['code' => 'auto', 'name' => '']];
        foreach (self::$languages as $code => $names) {
            $languages[] = ['code' => $code, 'name' => $names[0]];
        }

        $this->rc->output->set_env('ai_assistant', [
            'styles'    => self::$styles,
            'lengths'   => array_keys(self::$lengths),
            'languages' => $languages,
            'default_instruction' => (string) $config->get('ai_assistant_default_instruction', ''),
            'defaults'  => [
                'style'      => $this->valid_style($config->get('ai_assistant_default_style', 'professional')),
                'length'     => $this->valid_length($config->get('ai_assistant_default_length', 'medium')),
                'language'   => $this->valid_language($config->get('ai_assistant_default_language', 'auto')),
                'creativity' => $this->valid_creativity($config->get('ai_assistant_default_creativity', 50)),
            ],
        ]);
    }

    /**
     * Make the summarize command available (label) in the list / message view
     */
    private function set_summary_env()
    {
        $this->rc->output->add_label('ai_assistant.summarizebutton');
    }

    /**
     * AJAX handler: generate an e-mail text
     */
    public function action_generate()
    {
        $rc = $this->rc;

        $prompt     = trim((string) rcube_utils::get_input_value('_prompt', rcube_utils::INPUT_POST, true));
        $style      = $this->valid_style((string) rcube_utils::get_input_value('_style', rcube_utils::INPUT_POST));
        $length     = $this->valid_length((string) rcube_utils::get_input_value('_length', rcube_utils::INPUT_POST));
        $language   = $this->valid_language((string) rcube_utils::get_input_value('_language', rcube_utils::INPUT_POST));
        $creativity = $this->valid_creativity(rcube_utils::get_input_value('_creativity', rcube_utils::INPUT_POST));
        $mode       = rcube_utils::get_input_value('_mode', rcube_utils::INPUT_POST) === 'reply' ? 'reply' : 'compose';
        $subject    = trim((string) rcube_utils::get_input_value('_subject', rcube_utils::INPUT_POST, true));
        $context    = trim((string) rcube_utils::get_input_value('_context', rcube_utils::INPUT_POST, true));

        if ($prompt === '') {
            return $this->send_error($this->gettext('error_emptyprompt'));
        }

        if ($error = $this->check_configuration()) {
            return $this->send_error($error);
        }

        // map creativity (0-100) to temperature (0.0 - 1.5)
        $temperature = round($creativity / 100 * 1.5, 2);

        $system = strtr($this->get_prompt_template($mode), [
            '%style%'    => $style,
            '%length%'   => self::$lengths[$length],
            '%language%' => $this->language_name($language),
        ]);

        $user = '';
        if ($subject !== '') {
            $user .= 'Subject of the e-mail: ' . $subject . "\n\n";
        }
        $user .= "Instructions from the user:\n" . $prompt;

        if ($context !== '') {
            $max     = (int) $rc->config->get('ai_assistant_max_context_chars', 12000);
            $context = mb_substr($context, 0, max(500, $max));
            $marker  = $mode === 'reply'
                ? 'Original message the user is replying to'
                : 'Existing message text / additional context';
            $user .= "\n\n--- " . $marker . " ---\n" . $context;
        }

        $result = $this->api_request([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ], $temperature);

        if (!empty($result['error'])) {
            return $this->send_error($result['error']);
        }

        $rc->output->command('plugin.ai_assistant.result', ['text' => $result['text']]);
        $rc->output->send();
    }

    /**
     * AJAX handler: summarize the currently displayed message
     */
    public function action_summarize()
    {
        $rc = $this->rc;

        if (!$rc->config->get('ai_assistant_enable_summary', true)) {
            return $this->send_error($this->gettext('error_generic'));
        }

        if ($error = $this->check_configuration()) {
            return $this->send_error($error);
        }

        $uid  = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $mbox = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);

        if ($mbox) {
            $rc->storage->set_folder($mbox);
        }

        $message = new rcube_message($uid, $mbox);

        if (empty($message->headers)) {
            return $this->send_error($this->gettext('error_nomessage'));
        }

        $text = (string) $message->first_text_part();

        if ($text === '' && ($html = $message->first_html_part())) {
            $h2t  = new rcube_html2text($html, false, true);
            $text = $h2t->get_text();
        }

        $text = trim($text);
        if ($text === '') {
            return $this->send_error($this->gettext('error_nomessage'));
        }

        $max  = (int) $rc->config->get('ai_assistant_max_context_chars', 12000);
        $text = mb_substr($text, 0, max(500, $max));

        $language = $this->valid_language($rc->config->get('ai_assistant_default_language', 'auto'));
        $system   = strtr($this->get_prompt_template('summary'), [
            '%language%' => $this->language_name($language, true),
        ]);

        $user = 'Subject: ' . $message->subject . "\n"
            . 'From: ' . $message->headers->from . "\n\n"
            . $text;

        $result = $this->api_request([
            ['role' => 'system', 'content' => $system],
            ['role' => 'user',   'content' => $user],
        ], 0.3);

        if (!empty($result['error'])) {
            return $this->send_error($result['error']);
        }

        $rc->output->command('plugin.ai_assistant.summary', ['text' => $result['text']]);
        $rc->output->send();
    }

    /**
     * AJAX handler: list the models available at the configured API endpoint.
     *
     * The (possibly unsaved) values from the open settings form take
     * precedence so a new provider can be tested before saving; empty
     * fields fall back to the stored configuration.
     */
    public function action_models()
    {
        $config = $this->rc->config;

        if (!$config->get('ai_assistant_allow_user_provider', true)) {
            return $this->send_error($this->gettext('error_generic'));
        }

        $url = trim((string) rcube_utils::get_input_value('_api_url', rcube_utils::INPUT_POST));
        $key = trim((string) rcube_utils::get_input_value('_api_key', rcube_utils::INPUT_POST));

        if ($url === '') {
            $url = trim((string) $config->get('ai_assistant_api_url', ''));
        }
        if ($key === '') {
            $key = $this->resolve_api_key();
        }

        $result = $this->api_list_models($url, $key);

        if (!empty($result['error'])) {
            return $this->send_error($result['error']);
        }

        $this->rc->output->command('plugin.ai_assistant.models', ['models' => $result['models']]);
        $this->rc->output->send();
    }

    /**
     * Query the OpenAI-compatible /models endpoint
     *
     * @return array ['models' => array] on success, ['error' => string] on failure
     */
    private function api_list_models($url, $key)
    {
        $config = $this->rc->config;

        if ($url === '') {
            $url = 'https://api.openai.com/v1';
        }

        // normalize: strip a trailing /chat/completions, then append /models
        $url = rtrim($url, '/');
        $url = preg_replace('#/chat/completions$#', '', $url);
        $url .= '/models';

        $headers = [];
        if ($key !== '') {
            $headers[] = 'Authorization: Bearer ' . $key;
        }

        $verify = (bool) $config->get('ai_assistant_verify_ssl', true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            $this->log_error("cURL error #$errno: $error (URL: $url)");
            return ['error' => $this->gettext('error_connection')];
        }

        $data = json_decode((string) $response, true);

        if (!is_array($data)) {
            $this->log_error("Invalid JSON response (HTTP $code): " . substr((string) $response, 0, 500));
            return ['error' => $this->gettext('error_generic')];
        }

        if (isset($data['error'])) {
            $msg = is_array($data['error'])
                ? (isset($data['error']['message']) ? $data['error']['message'] : 'API error')
                : (string) $data['error'];
            $this->log_error("API error (HTTP $code): $msg");
            return ['error' => $this->gettext('error_api') . ' ' . $msg];
        }

        if ($code >= 400) {
            $this->log_error("HTTP error $code: " . substr((string) $response, 0, 500));
            return ['error' => $this->gettext('error_generic')];
        }

        // OpenAI-compatible: {"object":"list","data":[{"id":"...",...}]};
        // some providers use "models" instead of "data"
        $raw = null;
        if (isset($data['data']) && is_array($data['data'])) {
            $raw = $data['data'];
        }
        elseif (isset($data['models']) && is_array($data['models'])) {
            $raw = $data['models'];
        }

        $list = [];
        if (is_array($raw)) {
            foreach ($raw as $item) {
                if (is_string($item)) {
                    $list[] = $item;
                }
                elseif (is_array($item)) {
                    foreach (['id', 'name', 'model'] as $field) {
                        if (!empty($item[$field]) && is_string($item[$field])) {
                            $list[] = $item[$field];
                            break;
                        }
                    }
                }
            }
        }

        $list = array_values(array_unique($list));
        natcasesort($list);

        return ['models' => array_values($list)];
    }

    /**
     * Call the OpenAI-compatible chat completions API
     *
     * @return array ['text' => string] on success, ['error' => string] on failure
     */
    private function api_request(array $messages, $temperature)
    {
        $config = $this->rc->config;

        $url = trim((string) $config->get('ai_assistant_api_url', 'https://api.openai.com/v1'));
        if (!preg_match('#/chat/completions/?$#', $url)) {
            $url = rtrim($url, '/') . '/chat/completions';
        }

        $payload = [
            'model'       => $config->get('ai_assistant_model', 'gpt-4o-mini'),
            'messages'    => $messages,
            'temperature' => max(0.0, min(2.0, (float) $temperature)),
            'max_tokens'  => (int) $config->get('ai_assistant_max_tokens', 1500),
        ];

        // allow arbitrary extra parameters, e.g. for local models
        $extra = $config->get('ai_assistant_extra_params');
        if (is_array($extra)) {
            $payload = array_merge($payload, $extra);
        }

        $headers = ['Content-Type: application/json'];
        if ($key = $this->resolve_api_key()) {
            $headers[] = 'Authorization: Bearer ' . $key;
        }

        $verify = (bool) $config->get('ai_assistant_verify_ssl', true);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => (int) $config->get('ai_assistant_timeout', 120),
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => $verify,
            CURLOPT_SSL_VERIFYHOST => $verify ? 2 : 0,
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $code     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            $this->log_error("cURL error #$errno: $error (URL: $url)");
            return ['error' => $this->gettext('error_connection')];
        }

        $data = json_decode((string) $response, true);

        if (!is_array($data)) {
            $this->log_error("Invalid JSON response (HTTP $code): " . substr((string) $response, 0, 500));
            return ['error' => $this->gettext('error_generic')];
        }

        if (isset($data['error'])) {
            $msg = is_array($data['error'])
                ? (isset($data['error']['message']) ? $data['error']['message'] : 'API error')
                : (string) $data['error'];
            $this->log_error("API error (HTTP $code): $msg");
            return ['error' => $this->gettext('error_api') . ' ' . $msg];
        }

        if ($code >= 400) {
            $this->log_error("HTTP error $code: " . substr((string) $response, 0, 500));
            return ['error' => $this->gettext('error_generic')];
        }

        $text = isset($data['choices'][0]['message']['content'])
            ? trim((string) $data['choices'][0]['message']['content'])
            : '';

        if ($text === '') {
            $this->log_error("Empty/unexpected API response (HTTP $code)");
            return ['error' => $this->gettext('error_generic')];
        }

        // strip accidental markdown code fences
        $text = preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $text);

        return ['text' => trim($text)];
    }

    /**
     * Settings: register the preferences section
     */
    public function prefs_section($p)
    {
        $p['list']['ai_assistant'] = [
            'id'      => 'ai_assistant',
            'section' => $this->gettext('settings_section'),
            'class'   => 'ai_assistant',
        ];

        return $p;
    }

    /**
     * Settings: build the preferences form
     */
    public function prefs_list($p)
    {
        if ($p['section'] != 'ai_assistant') {
            return $p;
        }

        $config = $this->rc->config;

        // --- block: AI provider (per user) ------------------------------
        if ($config->get('ai_assistant_allow_user_provider', true)) {
            $options = [];

            $url = new html_inputfield([
                'name' => '_ai_api_url', 'id' => 'ai-pref-url', 'size' => 45,
                'placeholder' => 'https://api.openai.com/v1',
            ]);
            $options['url'] = [
                'title'   => html::label('ai-pref-url', $this->gettext('api_url')),
                'content' => $url->show((string) $config->get('ai_assistant_api_url', '')),
            ];

            $model = new html_inputfield([
                'name' => '_ai_model', 'id' => 'ai-pref-model', 'size' => 30,
                'placeholder' => 'gpt-4o-mini',
            ]);

            // "Suchen" button: fetches the available models from the
            // provider so the user can pick one instead of typing it
            $search = html::tag('button', [
                'type'  => 'button',
                'id'    => 'ai-pref-model-search',
                'class' => 'button ai-model-search',
                'title' => $this->gettext('model_search_title'),
            ], rcube::Q($this->gettext('model_search')));

            $options['model'] = [
                'title'   => html::label('ai-pref-model', $this->gettext('api_model')),
                'content' => $model->show((string) $config->get('ai_assistant_model', '')) . $search,
            ];

            // never echo the stored key; show a placeholder if one is set
            $has_key = (string) $config->get('ai_assistant_user_api_key', '') !== '';
            $key = new html_passwordfield([
                'name' => '_ai_api_key', 'id' => 'ai-pref-key', 'size' => 45,
                'autocomplete' => 'new-password',
                'placeholder'  => $has_key ? str_repeat('•', 10) : '',
            ]);
            $options['key'] = [
                'title'   => html::label('ai-pref-key', $this->gettext('api_key')),
                'content' => $key->show() . html::span('ai-prompts-hint', rcube::Q($this->gettext('api_key_hint'))),
            ];

            $p['blocks']['ai_provider'] = [
                'name'    => rcube::Q($this->gettext('provider_block')),
                'options' => $options,
            ];
        }

        // --- block: defaults --------------------------------------------
        $options = [];

        $select = new html_select(['name' => '_ai_default_style', 'id' => 'ai-pref-style']);
        foreach (self::$styles as $style) {
            $select->add($this->gettext('style_' . $style), $style);
        }
        $options['style'] = [
            'title'   => html::label('ai-pref-style', $this->gettext('default_style')),
            'content' => $select->show($this->valid_style($config->get('ai_assistant_default_style', 'professional'))),
        ];

        $select = new html_select(['name' => '_ai_default_length', 'id' => 'ai-pref-length']);
        foreach (array_keys(self::$lengths) as $length) {
            $select->add($this->gettext('length_' . $length), $length);
        }
        $options['length'] = [
            'title'   => html::label('ai-pref-length', $this->gettext('default_length')),
            'content' => $select->show($this->valid_length($config->get('ai_assistant_default_length', 'medium'))),
        ];

        $select = new html_select(['name' => '_ai_default_language', 'id' => 'ai-pref-language']);
        $select->add($this->gettext('lang_auto'), 'auto');
        foreach (self::$languages as $code => $names) {
            $select->add($names[0], $code);
        }
        $options['language'] = [
            'title'   => html::label('ai-pref-language', $this->gettext('default_language')),
            'content' => $select->show($this->valid_language($config->get('ai_assistant_default_language', 'auto'))),
        ];

        $input = new html_inputfield([
            'name' => '_ai_default_creativity', 'id' => 'ai-pref-creativity',
            'type' => 'number', 'min' => 0, 'max' => 100, 'step' => 5, 'size' => 5,
        ]);
        $options['creativity'] = [
            'title'   => html::label('ai-pref-creativity', $this->gettext('default_creativity')),
            'content' => $input->show($this->valid_creativity($config->get('ai_assistant_default_creativity', 50))),
        ];

        $instruction = new html_textarea([
            'name' => '_ai_default_instruction', 'id' => 'ai-pref-instruction',
            'rows' => 3, 'cols' => 60, 'class' => 'form-control',
            'placeholder' => $this->gettext('default_instruction_placeholder'),
        ]);
        $options['instruction'] = [
            'title'   => html::label('ai-pref-instruction', $this->gettext('default_instruction')),
            'content' => $instruction->show((string) $config->get('ai_assistant_default_instruction', ''))
                . html::span('ai-prompts-hint', rcube::Q($this->gettext('default_instruction_hint'))),
        ];

        $p['blocks']['ai_defaults'] = [
            'name'    => rcube::Q($this->gettext('defaults_block')),
            'options' => $options,
        ];

        // --- block: custom prompts ---------------------------------------
        if ($config->get('ai_assistant_allow_custom_prompts', true)) {
            $options = [];

            foreach (['compose', 'reply', 'summary'] as $type) {
                $textarea = new html_textarea([
                    'name' => '_ai_prompt_' . $type,
                    'id'   => 'ai-pref-prompt-' . $type,
                    'rows' => 5, 'cols' => 60, 'class' => 'form-control',
                    'placeholder' => $this->get_default_prompt($type),
                ]);
                $options[$type] = [
                    'title'   => html::label('ai-pref-prompt-' . $type, $this->gettext('prompt_' . $type)),
                    'content' => $textarea->show((string) $config->get('ai_assistant_user_prompt_' . $type, '')),
                ];
            }

            $options['hint'] = [
                'title'   => '',
                'content' => html::span('ai-prompts-hint', rcube::Q($this->gettext('prompts_hint'))),
            ];

            $p['blocks']['ai_prompts'] = [
                'name'    => rcube::Q($this->gettext('prompts_block')),
                'options' => $options,
            ];
        }

        return $p;
    }

    /**
     * Settings: save preferences
     */
    public function prefs_save($p)
    {
        if ($p['section'] != 'ai_assistant') {
            return $p;
        }

        $p['prefs']['ai_assistant_default_style'] =
            $this->valid_style((string) rcube_utils::get_input_value('_ai_default_style', rcube_utils::INPUT_POST));
        $p['prefs']['ai_assistant_default_length'] =
            $this->valid_length((string) rcube_utils::get_input_value('_ai_default_length', rcube_utils::INPUT_POST));
        $p['prefs']['ai_assistant_default_language'] =
            $this->valid_language((string) rcube_utils::get_input_value('_ai_default_language', rcube_utils::INPUT_POST));
        $p['prefs']['ai_assistant_default_creativity'] =
            $this->valid_creativity(rcube_utils::get_input_value('_ai_default_creativity', rcube_utils::INPUT_POST));

        $p['prefs']['ai_assistant_default_instruction'] =
            trim((string) rcube_utils::get_input_value('_ai_default_instruction', rcube_utils::INPUT_POST, true));

        // per-user AI provider settings
        if ($this->rc->config->get('ai_assistant_allow_user_provider', true)) {
            $p['prefs']['ai_assistant_api_url'] =
                rtrim(trim((string) rcube_utils::get_input_value('_ai_api_url', rcube_utils::INPUT_POST)), '/');
            $p['prefs']['ai_assistant_model'] =
                trim((string) rcube_utils::get_input_value('_ai_model', rcube_utils::INPUT_POST));

            // only update the key if a new one was entered; store it encrypted
            $key = (string) rcube_utils::get_input_value('_ai_api_key', rcube_utils::INPUT_POST);
            if ($key !== '') {
                $p['prefs']['ai_assistant_user_api_key'] = $this->rc->encrypt($key);
            }
        }

        if ($this->rc->config->get('ai_assistant_allow_custom_prompts', true)) {
            foreach (['compose', 'reply', 'summary'] as $type) {
                $p['prefs']['ai_assistant_user_prompt_' . $type] =
                    trim((string) rcube_utils::get_input_value('_ai_prompt_' . $type, rcube_utils::INPUT_POST, true));
            }
        }

        return $p;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Return the prompt template for the given type
     * (user pref > config > built-in default)
     */
    private function get_prompt_template($type)
    {
        $config = $this->rc->config;

        if ($config->get('ai_assistant_allow_custom_prompts', true)) {
            $user = trim((string) $config->get('ai_assistant_user_prompt_' . $type, ''));
            if ($user !== '') {
                return $user;
            }
        }

        $conf = trim((string) $config->get('ai_assistant_prompt_' . $type, ''));
        if ($conf !== '') {
            return $conf;
        }

        return $this->get_default_prompt($type);
    }

    private function get_default_prompt($type)
    {
        switch ($type) {
            case 'reply':   return self::DEFAULT_PROMPT_REPLY;
            case 'summary': return self::DEFAULT_PROMPT_SUMMARY;
            default:        return self::DEFAULT_PROMPT_COMPOSE;
        }
    }

    /**
     * English language name for the prompt
     */
    private function language_name($code, $for_summary = false)
    {
        if ($code === 'auto' || !isset(self::$languages[$code])) {
            return $for_summary
                ? 'the same language as the original message'
                : "the same language as the user's instructions";
        }

        return self::$languages[$code][1];
    }

    private function valid_style($style)
    {
        return in_array($style, self::$styles, true) ? $style : 'professional';
    }

    private function valid_length($length)
    {
        return isset(self::$lengths[$length]) ? $length : 'medium';
    }

    private function valid_language($language)
    {
        return ($language === 'auto' || isset(self::$languages[$language])) ? $language : 'auto';
    }

    private function valid_creativity($value)
    {
        return max(0, min(100, (int) $value));
    }

    /**
     * Returns an error message string if the plugin is not configured, null otherwise
     */
    private function check_configuration()
    {
        $url = (string) $this->rc->config->get('ai_assistant_api_url', 'https://api.openai.com/v1');
        $key = $this->resolve_api_key();

        // a key is required for the official OpenAI endpoint
        if (strpos($url, 'api.openai.com') !== false && $key === '') {
            return $this->gettext('error_notconfigured');
        }

        return null;
    }

    /**
     * Resolve the API key: a user-provided (encrypted) key takes precedence
     * over the server-wide plaintext config key.
     */
    private function resolve_api_key()
    {
        $config = $this->rc->config;

        $enc = (string) $config->get('ai_assistant_user_api_key', '');
        if ($enc !== '') {
            $dec = $this->rc->decrypt($enc);
            if ($dec !== false && $dec !== '') {
                return $dec;
            }
        }

        return (string) $config->get('ai_assistant_api_key', '');
    }

    private function send_error($message)
    {
        $this->rc->output->command('plugin.ai_assistant.error', ['message' => $message]);
        $this->rc->output->send();
    }

    private function log_error($message)
    {
        rcube::write_log('ai_assistant', $message);
    }
}
