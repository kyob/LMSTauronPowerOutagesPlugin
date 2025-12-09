# Plugin TauronPowerOutage dla LMS

Pokazuje planowane/przyszłe wyłączenia prądu na obszarze Tauron.

![](tauron-power-outages.png?raw=true)

## Wymagania

Zainstalowany [LMS](https://lms.org.pl/) lub [LMS-PLUS](https://lms-plus.org) (zalecany).

## Instalacja

* Skopiuj pliki do `<path-to-lms>/plugins/`
* Uruchom `composer update` lub `composer update --no-dev`
* W panelu LMS aktywuj w `Konfiguracja => Wtyczki`


## Jak dodać swój obszar?

Musimy podejrzeć parametry zapytań JSON.

1. Wejdź na https://www.tauron-dystrybucja.pl/wylaczenia/wylaczenia-oddzialy
2. Otwórz narzędzia deweloperskie (F12) → zakładka Network.
3. Na stronie wybierz województwo, powiat i opcjonalnie gminę, kliknij „Sprawdź”.
4. W Network znajdziesz zapytanie GET podobne do:  
   `https://www.tauron-dystrybucja.pl/waapi/outages/area?provinceGAID=24&districtGAID=6&fromDate=...&toDate=...&communeGAID=502`
5. Zanotuj `provinceGAID`, `districtGAID`, `communeGAID` – to wartości do konfiguracji.

Przykłady:
1) miasto Knurów → `communeGAID: 502` → typ: gmina  
2) powiat gliwicki → `districtGAID: 6` → typ: powiat  
3) województwo śląskie → `provinceGAID: 24` → typ: województwo  

Plugin nie obsługuje wszystkich możliwych parametrów API.

## Konfiguracja

* Zaimportuj domyślne ustawienia `configexport-tauron-wartoscglobalna.ini`
* Przejdź do `<path-to-lms>/?m=configlist` i dostosuj wartości

## Odświeżanie cache (ważne – brak blokowania strony)

Render strony nie powinien czekać na API Taurona. Dane odświeżamy w tle:

1. Cron/CLI: uruchom co 5–10 minut (dostosuj do potrzeb):
   ```
   php /path/to/lms/plugins/LMSTauronPowerOutagesPlugin/bin/tauron-refresh-cache.php
   ```
   lub wyczyść cache ręcznie:
   ```
   php /path/to/lms/plugins/LMSTauronPowerOutagesPlugin/bin/tauron-refresh-cache.php --clear
   ```
2. Domyślna ścieżka cache: `plugins/LMSTauronPowerOutagesPlugin/var/tauron.json`
   (zmienisz przez `tauron.filename`; możesz podać ścieżkę absolutną).
3. Konfiguracja:
   - `tauron.inline_refresh` (bool, domyślnie false) – jeśli true, plugin spróbuje krótkiego (timeout) odświeżenia w trakcie renderu; błędy nie blokują strony.
   - `tauron.time_in_cache` (sekundy, domyślnie 300) – TTL cache.
   - `tauron.timeout` (sekundy, domyślnie 5) – timeout zapytań do API.
   - `tauron.user_agent` – własny UA dla zapytań.

Efekt: strona zawsze się wczyta, nawet jeśli API Taurona nie odpowiada. W najgorszym wypadku zobaczysz ostatnio zapisaną datę cache albo `-` (brak danych).

## Powiadomienia Telegram

Plugin obsługuje powiadomienia o awariach przez Telegram Bot API.

### Konfiguracja

1. **Utwórz bota Telegram:**
   - Napisz do [@BotFather](https://t.me/BotFather) na Telegram
   - Wyślij `/newbot` i postępuj zgodnie z instrukcjami
   - Zapisz otrzymany **token bota** (np. `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

2. **Uzyskaj Chat ID:**
   - Napisz do swojego bota na Telegram
   - Odwiedź: `https://api.telegram.org/bot<TOKEN>/getUpdates`
   - Znajdź `"chat":{"id":123456789}` – to Twój Chat ID
   - Możesz podać wiele Chat ID oddzielonych przecinkami

3. **Skonfiguruj w LMS:**
   - W `Konfiguracja => Wartości globalne` ustaw:
     - `tauron.telegram_bot_token` = token bota (np. `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)
     - `tauron.telegram_chat_ids` = Chat ID lub wiele ID oddzielonych przecinkami (np. `123456789` lub `123456789,987654321`)

4. **Zaktualizuj bazę danych:**
   ```sql
   -- Uruchom plik SQL:
   psql -U lms -d lms < plugins/LMSTauronPowerOutagesPlugin/bin/lms-notify-telegram-tauron.sql
   ```

5. **Ustaw cron dla powiadomień:**
   ```bash
   # Co 10-15 minut sprawdza nowe awarie i wysyła powiadomienia
   */10 * * * * php /path/to/lms/plugins/LMSTauronPowerOutagesPlugin/bin/lms-notify-telegram-tauron.php
   ```

### Jak to działa

- Skrypt `lms-notify-telegram-tauron.php` sprawdza w bazie awarie z dzisiejszą datą, które jeszcze nie zostały wysłane (`telegram_notify=false`)
- Dla każdej nowej awarii wysyła sformatowaną wiadomość HTML na Telegram
- Po udanym wysłaniu oznacza awarię jako wysłaną (`telegram_notify=true`)
- Błędy są logowane, ale nie przerywają działania skryptu

### Format wiadomości

```
⚡ Tauron - Wyłączenie prądu

Od: 2024-01-15 10:00
Do: 2024-01-15 14:00
Obszar: ul. Przykładowa 1-10, miasto Knurów
```

