# MedLit AI — WordPress Plugin

## Ordnerstruktur

```
medlit-ai/
├── medlit-ai.php        ← Haupt-Plugin-Datei
└── app/
    └── index.html       ← Die App (wird per Shortcode eingebettet)
```

## Installation

### 1. Plugin in WordPress hochladen

Den Ordner `medlit-ai/` in das Verzeichnis kopieren:
```
/wp-content/plugins/medlit-ai/
```

Alternativ: In WordPress-Admin → Plugins → Neu hinzufügen → Plugin hochladen (als ZIP).

### 2. API-Key in wp-config.php eintragen

```php
define('OPENAI_API_KEY', 'sk-...');
```

Diese Zeile **vor** dem `require_once ABSPATH . 'wp-settings.php';` einfügen.

**Wichtig:** Den Key niemals in öffentlichem Code (GitHub etc.) speichern!

### 3. Plugin aktivieren

WordPress-Admin → Plugins → "MedLit AI" aktivieren.

### 4. Shortcode auf eine Seite einbetten

Eine neue (passwortgeschützte oder nur für Mitglieder sichtbare) Seite erstellen und diesen Shortcode einfügen:

```
[medlit_search]
```

### 5. Seite schützen

**Option A – WordPress-Mitgliederbereich:**  
Die Seite auf "Privat" oder "Passwortgeschützt" stellen.  
Das Plugin prüft zusätzlich `is_user_logged_in()` und zeigt unangemeldeten Nutzern einen Login-Link.

**Option B – Membership-Plugin (empfohlen):**  
Mit Plugins wie *MemberPress*, *Restrict Content Pro* oder *Ultimate Member* die Seite auf bestimmte Benutzerrollen einschränken.

---

## Kosten & Modelle

| Modell | Geschwindigkeit | Qualität | ca. Kosten/Suche |
|---|---|---|---|
| GPT-3.5 Turbo | schnell | gut | ~$0.002 |
| GPT-4o Mini | schnell | sehr gut | ~$0.005 |
| GPT-4o | mittel | exzellent | ~$0.01–0.03 |
| GPT-4 Turbo | langsamer | exzellent | ~$0.03–0.06 |

$5 Guthaben reichen für mehrere hundert Suchen (je nach Modell).

API-Key erstellen: **platform.openai.com/api-keys**

---

## Sicherheitskonzept

- Der API-Key liegt **nur** in `wp-config.php` (serverseitig) — nie im Browser-Code
- Alle OpenAI-Anfragen laufen über den WordPress-AJAX-Proxy (`admin-ajax.php`)
- Nonce-Validierung verhindert CSRF-Angriffe
- Nur eingeloggte Benutzer können den AJAX-Endpoint aufrufen
- Eingaben werden serverseitig bereinigt (`wp_strip_all_tags`)
- Nur zugelassene Modellnamen werden akzeptiert
