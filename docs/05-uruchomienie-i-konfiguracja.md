# Uruchomienie i konfiguracja

## Spis treści

- [Wymagania](#wymagania)
- [Kroki uruchomienia](#kroki-uruchomienia)
- [Ważne pliki konfiguracyjne](#ważne-pliki-konfiguracyjne)
- [Zasady działania lokalnego](#zasady-działania-lokalnego)
- [Konta testowe](#konta-testowe)
- [Typowe problemy](#typowe-problemy)
- [Weryfikacja poprawnej instalacji](#weryfikacja-poprawnej-instalacji)
- [Rekomendacje dla wdrożenia](#rekomendacje-dla-wdrożenia)

## Wymagania

- PHP 8.x
- MySQL lub MariaDB
- lokalny serwer WWW, np. XAMPP
- przeglądarka internetowa

## Kroki uruchomienia

1. Skopiuj projekt do katalogu serwera WWW.
2. Utwórz bazę danych o nazwie `student_planner`.
3. Zaimportuj plik `baza.sql`.
4. Sprawdź konfigurację połączenia w `public/template/polaczenie.php`.
5. Uruchom aplikację przez lokalny adres serwera.

Po uruchomieniu aplikacja codziennie synchronizuje strukturę schematu z pliku `baza.sql`, więc przy zmianach tabel nie trzeba usuwać bazy i importować jej od nowa.

## Ważne pliki konfiguracyjne

- `public/template/polaczenie.php` - połączenie z bazą i start sesji
- `baza.sql` - struktura i dane przykładowe
- `README.md` - główny opis projektu
- `docs/README.md` - indeks dokumentacji

## Zasady działania lokalnego

- aplikacja korzysta z sesji PHP
- widoki chronione wymagają zalogowania
- przekierowania prowadzą do `logowanie.php`
- integracja Google Calendar nie jest aktywna

## Konta testowe

Jeśli w bazie znajdują się dane przykładowe, można ich użyć do szybkiego sprawdzenia działania widoków.

## Typowe problemy

- brak połączenia z bazą
- niezaimportowana struktura tabel
- różnice między aktualnym schematem a starszą bazą, które aplikacja zwykle naprawia przy codziennym syncu
- nieprawidłowa ścieżka do projektu w katalogu WWW
- wyłączone rozszerzenie MySQLi w PHP

## Weryfikacja poprawnej instalacji

Po poprawnym uruchomieniu powinny działać:

- logowanie
- dashboard
- projekty
- log sukcesu
- planer przyszłości
- portfolio / CV

## Rekomendacje dla wdrożenia

- trzymać bazę danych w osobnym środowisku
- regularnie wykonywać kopie zapasowe
- po większych zmianach sprawdzać spójność przekierowań i nazw plików
