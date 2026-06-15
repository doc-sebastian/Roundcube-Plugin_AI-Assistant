# AI Assistant ("AI Reply") – Roundcube Plugin

An AI assistant for Roundcube that works with any OpenAI-compatible API
(OpenAI, **LocalAI**, **Ollama**, vLLM, LM Studio, ...).

The plugin adds a new **"AI Reply"** button to the message compose window –
in the top toolbar next to "Send" and "Save".

---

## Features

- **8 writing styles**: Direct, Casual, Enthusiastic, Humorous, Informative,
  Professional, Urgent, Witty
- **3 lengths**: Short, Medium, Long
- **Adjustable creativity**: Slider from factual to creative
  (mapped to the model's temperature)
- **38 languages**: Enter instructions in one language, generate the e-mail in
  another (e.g. German instructions → Spanish e-mail)
- **Reply mode**: When replying/forwarding, the original message can be sent
  automatically as context
- **Preview**: Generated text can be edited or regenerated before inserting;
  works in both HTML and plain-text editors
- **AI summary**: Button in the message view, in the context menu and in the
  "More" actions of the main message list (can be disabled)
- **Per-user provider**: API URL, model and API key — stored per user in the
  settings in addition to the global config; the key is encrypted (like the
  mail password) and never sent to the browser
- **Default instruction**: A text stored in the settings that is automatically
  placed in the "Instruction to the AI" field when the assistant is opened
- **Customisable prompts**: Administrators (config) and users (settings) can
  customise the AI prompts for e-mails and summaries
- **Privacy**: Works with local LLMs — the API key stays on the server only,
  the browser never communicates directly with the AI

## Requirements

| | |
|---|---|
| Roundcube | 1.5 or newer (Elastic and Larry) |
| PHP | 7.3+ with **curl** extension |
| API | Access to an OpenAI-compatible Chat Completions API |

## Installation

1. Copy the `ai_assistant` folder into the plugin directory of your
   Roundcube installation:

   ```bash
   cp -r ai_assistant /var/www/roundcube/plugins/
   ```

2. Create and edit the configuration file:

   ```bash
   cd /var/www/roundcube/plugins/ai_assistant
   cp config.inc.php.dist config.inc.php
   nano config.inc.php
   ```

3. Enable the plugin in the Roundcube configuration
   (`/var/www/roundcube/config/config.inc.php`):

   ```php
   $config['plugins'][] = 'ai_assistant';
   ```

4. Clear the browser cache / reload Roundcube — done.

## Configuration

**OpenAI:**

```php
$config['ai_assistant_api_url'] = 'https://api.openai.com/v1';
$config['ai_assistant_api_key'] = 'sk-...';
$config['ai_assistant_model']   = 'gpt-4o-mini';
```

**LocalAI (local, without API key):**

```php
$config['ai_assistant_api_url'] = 'http://localhost:8080/v1';
$config['ai_assistant_api_key'] = '';
$config['ai_assistant_model']   = 'mistral-7b-instruct';
```

**Ollama:**

```php
$config['ai_assistant_api_url'] = 'http://localhost:11434/v1';
$config['ai_assistant_api_key'] = '';
$config['ai_assistant_model']   = 'llama3.1:8b';
$config['ai_assistant_timeout'] = 300; // local models may take longer
```

All further options are documented in `config.inc.php.dist`
(summary on/off, allow custom prompts, context limit, SSL verification,
additional API parameters, etc.).

## Usage

1. **Compose** a new message or **reply** to an e-mail
2. Click **"AI Reply"** in the toolbar
3. Enter an instruction (e.g. *"Politely accept the appointment and ask for
   the agenda"*), choose style, length, language and creativity
4. When replying: keep "Send existing message text as context" checked so the
   AI can respond to the original message
5. **Generate** → review/edit preview → **Insert**

The prompts and default values can be adjusted per user under
**Settings → AI Assistant**.
Available placeholders in the prompts: `%style%`, `%length%`, `%language%`.

## Troubleshooting

- Errors are logged to `logs/ai_assistant.log` (in the Roundcube log
  directory).
- For local endpoints with self-signed certificates, set
  `$config['ai_assistant_verify_ssl'] = false;`.
- If the button does not appear: check whether the plugin is enabled in
  `config/config.inc.php` and clear the browser cache.

## Author

**Sebastian Fischer** post@scriptometer.de;

## License

MIT License with Non-Commercial restriction – see [LICENSE](LICENSE).
