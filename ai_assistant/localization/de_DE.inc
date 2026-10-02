<?php

/**
 * AI Assistant plugin - German localization
 */

$labels = [];
$labels['aibutton'] = 'KI Antwort';
$labels['aibutton_title'] = 'E-Mail-Text mit KI erstellen';
$labels['summarizebutton'] = 'KI-Zusammenfassung';
$labels['summarizebutton_title'] = 'Diese Nachricht mit KI zusammenfassen';

$labels['dialog_title'] = 'KI-Assistent – E-Mail verfassen';
$labels['summary_title'] = 'KI-Zusammenfassung';

$labels['prompt_label'] = 'Ihre Anweisung an die KI';
$labels['prompt_placeholder'] = 'z. B. „Sage den Termin am Freitag höflich zu und schlage Montag 10 Uhr als Alternative vor“';

$labels['style_label'] = 'Stil';
$labels['style_assertive'] = 'Bestimmt';
$labels['style_casual'] = 'Locker';
$labels['style_enthusiastic'] = 'Begeistert';
$labels['style_funny'] = 'Humorvoll';
$labels['style_informational'] = 'Informativ';
$labels['style_professional'] = 'Professionell';
$labels['style_urgent'] = 'Dringend';
$labels['style_witty'] = 'Geistreich';

$labels['length_label'] = 'Länge';
$labels['length_short'] = 'Kurz';
$labels['length_medium'] = 'Mittel';
$labels['length_long'] = 'Lang';

$labels['language_label'] = 'Sprache';
$labels['lang_auto'] = 'Automatisch (wie Eingabe)';

$labels['creativity_label'] = 'Kreativität';
$labels['creativity_low'] = 'Sachlich';
$labels['creativity_high'] = 'Kreativ';

$labels['usecontext'] = 'Vorhandenen Nachrichtentext als Kontext mitsenden';
$labels['preview_label'] = 'Vorschau (kann vor dem Einfügen bearbeitet werden)';

$labels['generate'] = 'Generieren';
$labels['regenerate'] = 'Neu generieren';
$labels['insert'] = 'Einfügen';
$labels['close'] = 'Schließen';

$labels['generating'] = 'KI-Text wird generiert...';
$labels['summarizing'] = 'Zusammenfassung wird erstellt...';
$labels['inserted'] = 'Der Text wurde eingefügt.';

$labels['settings_section'] = 'KI-Assistent';
$labels['provider_block'] = 'KI-Anbieter (Zugangsdaten)';
$labels['model_search'] = 'Suchen';
$labels['model_search_title'] = 'Verfügbare Modelle beim Anbieter abrufen';
$labels['model_search_dialog_title'] = 'Verfügbare Modelle';
$labels['model_filter_placeholder'] = 'Modell filtern …';
$labels['model_filter_nomatch'] = 'Kein passendes Modell gefunden.';
$labels['model_selected'] = 'Das Modell wurde übernommen.';
$labels['api_url'] = 'API-URL';
$labels['api_model'] = 'Modell';
$labels['api_key'] = 'API-Schlüssel';
$labels['api_key_hint'] = 'Wird verschlüsselt gespeichert. Für lokale Anbieter (LocalAI, Ollama) meist leer lassen. Feld leer lassen, um den gespeicherten Schlüssel beizubehalten.';
$labels['defaults_block'] = 'Standardeinstellungen';
$labels['default_style'] = 'Standard-Stil';
$labels['default_length'] = 'Standard-Länge';
$labels['default_language'] = 'Standard-Sprache';
$labels['default_creativity'] = 'Standard-Kreativität (0–100)';
$labels['default_instruction'] = 'Standard-Anweisung';
$labels['default_instruction_placeholder'] = 'z. B. „Schreibe eine höfliche, professionelle Antwort.“';
$labels['default_instruction_hint'] = 'Dieser Text wird beim Öffnen des KI-Assistenten automatisch als Anweisung vorausgefüllt.';

$labels['prompts_block'] = 'KI-Prompts (für Fortgeschrittene)';
$labels['prompt_compose'] = 'Prompt: Neue E-Mail';
$labels['prompt_reply'] = 'Prompt: Antwort';
$labels['prompt_summary'] = 'Prompt: Zusammenfassung';
$labels['prompts_hint'] = 'Verfügbare Platzhalter: %style%, %length%, %language%. Felder leer lassen, um die Standard-Prompts zu verwenden.';

$messages = [];
$messages['error_generic'] = 'Bei der KI-Anfrage ist ein Fehler aufgetreten.';
$messages['error_api'] = 'Fehler vom KI-Dienst:';
$messages['error_connection'] = 'Der KI-Dienst konnte nicht erreicht werden.';
$messages['error_emptyprompt'] = 'Bitte geben Sie zuerst eine Anweisung ein.';
$messages['error_notconfigured'] = 'Das KI-Plugin ist noch nicht konfiguriert. Bitte wenden Sie sich an den Administrator.';
$messages['error_nomessage'] = 'Die Nachricht konnte nicht gelesen werden.';
$messages['searchingmodels'] = 'Verfügbare Modelle werden geladen ...';
$messages['model_search_empty'] = 'Der Anbieter hat keine Modelle zurückgegeben.';
