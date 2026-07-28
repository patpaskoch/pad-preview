# Feature Backlog – Pad/Pferd-Connect App

## 1. Bild-Handling & Vorschau
- [x] Vorschau beim Hochladen begrenzen (max-height ~60-70vh, max-width 100%, object-fit: contain), damit der Save-Button ohne Scrollen erreichbar ist
- [x] Save/Speichern-Button als sticky/fixed positionieren (immer sichtbar, unabhängig von Bildgröße)
- [x] Pad-Overlay-Bild abhängig von Orientierung skalieren — via freiem Drag/Pinch-Resize abgedeckt, kein separates Orientierungs-Handling nötig
- [x] Zuschneiden-Funktion für hochgeladene Bilder (Pferd & Pad)
- [x] Reset-Button (isoliert platziert, mit "Tap again"-Bestätigung gegen Fehlklicks)

## 2. Bearbeitungs-Werkzeuge
- [x] Rotation fürs Pad-Bild — per Drag-Handle & Zwei-Finger-Pinch statt Regler (fühlt sich mobil direkter an)
- [x] Skalieren & Verschieben des Pad-Bildes per Drag/Pinch
- [x] Fullscreen-artiges Verhalten — Dock lässt sich ein-/ausklappen, Bild geht im fertigen Zustand auf volle Größe

## 3. Menüführung / UI-Struktur
- [x] Zweistufiges Bottom-Toolbar-Pattern (Navbar + Kontext-Panel je nach aktivem Modus)
- [x] Icons in den Buttons (mit Text-Label statt Tooltip, für bessere Lesbarkeit auf Anhieb)

## 4. Zusatz-Features
- [x] Vorher-Nachher-Wisch-Slider (nur Pferd vs. mit Pad) — "Compare"-Button in der Navbar
- [x] Presets/Verlauf, um mehrere Pad-Varianten auf demselben Pferdebild durchzuprobieren
- [x] Farbfilter/Tönung fürs Pad-Bild (Tint-Regler, Farbvarianten ohne neuen Upload testen)
- [x] Export/Speichern des fertigen Overlays als Bild (z. B. für Social Media oder Sattler/Verkäufer)

## 5. User-Tracking / Logging
- [x] Serverseitiges Logging eingerichtet — YAML-Datei (`storage/app/data/log.yaml`) statt DB-Tabelle, mit Filelock gegen Race Conditions bei gleichzeitigen Requests
- [x] Erfasste Events: Seitenaufruf, Bild hochgeladen (Pferd/Pad), Zuschneiden benutzt, Verschieben/Skalieren/Drehen benutzt, Fullscreen (Dock eingeklappt), Export/Speichern, Session-Ende (Dauer via `sendBeacon`)
- [x] DSGVO: IP-Adresse wird vor dem Speichern gekürzt (letztes Oktett/Gruppe genullt), kein Tracking-Cookie/Consent nötig

## 6. Feedback-Funktion
- [x] Floating Action Button (❓, unten rechts, Akzentfarbe)
- [x] Modal mit Freitext, Kategorie (Bug/Idee/Sonstiges), optionalem Screenshot-Anhang
- [x] Absenden speichert Feedback in `storage/app/data/feedback.yaml`; Screenshot landet auf dem privaten Storage-Disk (nicht öffentlich verlinkt)

## 7. Admin-Bereich
- [x] Passwortgeschützter Admin-Bereich unter `/admin` (Session-basiert, kein User-Table nötig)
- [x] Passwort als Hash in `.env` (`ADMIN_PASSWORD_HASH`), `Hash::check` beim Login — siehe Deploy-Hinweise unten
- [x] Übersichtliche Liste des Logs (`/admin`, neueste zuerst, letzte 300 Einträge)
- [x] Übersicht aller Feedback-Einträge inkl. Screenshot-Link (nur eingeloggt einsehbar)

## Deploy-Hinweise für Forge (wichtig, einmalig)
Die App ist jetzt eine echte Laravel-Anwendung, kein statisches HTML mehr. In Forge muss dafür einmalig eingerichtet werden:
- **Deploy-Script** um `composer install --no-dev` und `php artisan migrate --force` ergänzen (Standard-Laravel-Deploy-Script in Forge macht das meist schon automatisch)
- **`.env` auf dem Server** anlegen (Forge → Environment-Tab), Werte wie lokal in `.env.example`, plus:
  - `APP_KEY` generieren (`php artisan key:generate` einmalig auf dem Server, oder Wert aus lokaler `.env` übernehmen)
  - `ADMIN_PASSWORD_HASH` setzen — Hash lokal erzeugen mit:
    `php artisan tinker --execute="echo Hash::make('deinpasswort');"`
- **Storage-Verzeichnis beschreibbar machen**: `storage/` und `bootstrap/cache/` brauchen Schreibrechte für den Webserver-User (Forge macht das i. d. R. automatisch beim Deploy)
- Webroot in Forge bleibt `public/` (Standard-Laravel-Konvention, unverändert)


## 8
- [ ] Leichter Schatten fürs Pad-Overlay für realistischeren Look (CSS `filter: drop-shadow()`, an Lichtquelle von oben orientiert)

## 9
- [ ] Horizontal-Flip-Funktion fürs Pad (Toggle-Button, `transform: scaleX(-1)`), falls Pferd und Pad nicht in dieselbe Richtung schauen
