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
- [ ] Serverseitiges Logging einrichten (z. B. JSON-Zeilen in Datei oder DB-Tabelle)
- [ ] Zu erfassende Events:
  - Seitenaufruf (Timestamp, anonymisierte/gekürzte IP oder Session-ID, Referrer)
  - Bild hochgeladen (Pferd/Pad)
  - Zuschneiden/Drehen benutzt
  - Fullscreen aktiviert
  - Export/Speichern ausgeführt
  - Verweildauer / Session-Ende
- [ ] DSGVO-konform umsetzen (IP-Adressen kürzen/anonymisieren, kein Cookie-Consent-Zwang wenn möglich)

## 6. Feedback-Funktion
- [ ] Floating Action Button (Fragezeichen-/Sprechblasen-Icon, fixiert unten rechts, dezente Akzentfarbe)
- [ ] Klick öffnet Modal mit:
  - Freitextfeld
  - Optional: Kategorie (Bug / Idee / Sonstiges)
  - Optional: Screenshot-Anhang
- [ ] Absenden speichert Feedback (Mail oder gleiche Log-Struktur wie Tracking)

## 7. Admin-Bereich
- [ ] Passwortgeschützter Admin-Bereich (Login, Session-basiert)
- [ ] Passwort als Hash in `.env` speichern (nicht im Code/DB im Klartext):
  - `ADMIN_PASSWORD_HASH` in Forge über Environment-Tab eintragen
  - Hash generieren mit `Hash::make('passwort')` (Laravel Tinker)
  - Login-Check mit `Hash::check($request->password, config(...))`
  - `.env` in `.gitignore` verifizieren (Laravel-Standard, aber gegenchecken)
- [ ] Übersichtliche Liste des User-Logs (Zeitpunkt, Aktionen, ggf. gefiltert/sortierbar)
- [ ] Übersicht aller eingegangenen Feedback-Einträge (Datum, Kategorie, Text, ggf. Screenshot)


## 8
- [ ] Leichter Schatten fürs Pad-Overlay für realistischeren Look (CSS `filter: drop-shadow()`, an Lichtquelle von oben orientiert)

## 9
- [ ] Horizontal-Flip-Funktion fürs Pad (Toggle-Button, `transform: scaleX(-1)`), falls Pferd und Pad nicht in dieselbe Richtung schauen
