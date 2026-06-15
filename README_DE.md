# AI Assistant („KI Antwort") – Roundcube-Plugin

Ein KI-Assistent für Roundcube, der über jede OpenAI-kompatible API funktioniert
(OpenAI, **LocalAI**, **Ollama**, vLLM, LM Studio, ...).

Das Plugin fügt im Fenster der Nachrichtenerstellung – in der oberen Leiste neben
„Senden" und „Speichern" – einen neuen Button **„KI Antwort"** ein.

---

## Funktionen

- **8 Schreibstile**: Bestimmt, Locker, Begeistert, Humorvoll, Informativ,
  Professionell, Dringend, Geistreich
- **3 Längen**: Kurz, Mittel, Lang
- **Einstellbare Kreativität**: Schieberegler von sachlich bis kreativ
  (wird auf die Temperatur des Modells abgebildet)
- **38 Sprachen**: Anweisung in einer Sprache eingeben, E-Mail in einer anderen
  generieren lassen (z. B. deutsche Anweisung → spanische E-Mail)
- **Antwort-Modus**: Beim Antworten/Weiterleiten kann die Originalnachricht
  automatisch als Kontext mitgesendet werden
- **Vorschau**: Der generierte Text kann vor dem Einfügen bearbeitet oder
  neu generiert werden; das Einfügen funktioniert im HTML- und im Text-Editor
- **KI-Zusammenfassung**: Button in der Nachrichtenansicht sowie im Kontextmenü
  / den „Mehr"-Aktionen der Nachrichtenliste der Hauptansicht (abschaltbar)
- **Anbieter pro Benutzer**: API-URL, Modell und API-Schlüssel zusätzlich zur
  Config pro Benutzer in den Einstellungen; der Schlüssel wird verschlüsselt
  gespeichert (wie das Mail-Passwort) und nie an den Browser übertragen
- **Standard-Anweisung**: in den Einstellungen hinterlegbarer Text, der beim
  Öffnen des KI-Assistenten automatisch im Feld „Anweisung an die KI" steht
- **Anpassbare Prompts**: Administratoren (Config) und Benutzer (Einstellungen)
  können die KI-Prompts für E-Mails und Zusammenfassungen anpassen
- **Datenschutz**: Funktioniert mit lokalen LLMs – der API-Schlüssel bleibt
  ausschließlich auf dem Server, der Browser kommuniziert nie direkt mit der KI

## Voraussetzungen

| | |
|---|---|
| Roundcube | 1.5 oder neuer (Elastic und Larry) |
| PHP | 7.3+ mit **curl**-Erweiterung |
| API | Zugang zu einer OpenAI-kompatiblen Chat-Completions-API |

## Installation

1. Den Ordner `ai_assistant` in das Plugin-Verzeichnis der
   Roundcube-Installation kopieren:

   ```bash
   cp -r ai_assistant /var/www/roundcube/plugins/
   ```

2. Konfigurationsdatei anlegen und anpassen:

   ```bash
   cd /var/www/roundcube/plugins/ai_assistant
   cp config.inc.php.dist config.inc.php
   nano config.inc.php
   ```

3. Das Plugin in der Roundcube-Konfiguration
   (`/var/www/roundcube/config/config.inc.php`) aktivieren:

   ```php
   $config['plugins'][] = 'ai_assistant';
   ```

4. Browser-Cache leeren bzw. Roundcube neu laden – fertig.

## Konfiguration

**OpenAI:**

```php
$config['ai_assistant_api_url'] = 'https://api.openai.com/v1';
$config['ai_assistant_api_key'] = 'sk-...';
$config['ai_assistant_model']   = 'gpt-4o-mini';
```

**LocalAI (lokal, ohne API-Schlüssel):**

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
$config['ai_assistant_timeout'] = 300; // lokale Modelle brauchen ggf. länger
```

Alle weiteren Optionen sind in `config.inc.php.dist` dokumentiert
(Zusammenfassung an/aus, eigene Prompts erlauben, Kontext-Limit, SSL-Prüfung,
zusätzliche API-Parameter usw.).

## Benutzung

1. **Neue Nachricht** verfassen oder auf eine E-Mail **antworten**
2. In der Toolbar auf **„KI Antwort"** klicken
3. Anweisung eingeben (z. B. *„Sage den Termin höflich zu und frage nach
   der Agenda"*), Stil, Länge, Sprache und Kreativität wählen
4. Bei Antworten: Häkchen „Vorhandenen Nachrichtentext als Kontext mitsenden"
   lassen, damit die KI auf die Originalnachricht eingehen kann
5. **Generieren** → Vorschau prüfen/bearbeiten → **Einfügen**

Die Prompts und Standardwerte können unter
**Einstellungen → KI-Assistent** pro Benutzer angepasst werden.
Verfügbare Platzhalter in den Prompts: `%style%`, `%length%`, `%language%`.

## Fehlersuche

- Fehler werden in `logs/ai_assistant.log` (im Roundcube-Log-Verzeichnis)
  protokolliert.
- Bei lokalen Endpunkten mit selbstsigniertem Zertifikat ggf.
  `$config['ai_assistant_verify_ssl'] = false;` setzen.
- Erscheint der Button nicht: Prüfen, ob das Plugin in
  `config/config.inc.php` aktiviert ist und der Browser-Cache geleert wurde.

## Autor

**Sebastian Fischer** post@scriptometer.de

## Lizenz

MIT-Lizenz mit Zusatz zur nicht-kommerziellen Nutzung – siehe [LICENSE_DE](LICENSE_DE).
