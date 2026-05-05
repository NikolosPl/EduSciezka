# Architektura aplikacji

## Spis treści

- [Model aplikacji](#model-aplikacji)
- [Warstwy rozwiązania](#warstwy-rozwiązania)
- [Autoryzacja](#autoryzacja)
- [Organizacja plików](#organizacja-plików)
- [Komunikacja między modułami](#komunikacja-między-modułami)
- [Kluczowe decyzje architektoniczne](#kluczowe-decyzje-architektoniczne)
- [Integracje](#integracje)

## Model aplikacji

EduSciezka działa jako klasyczna aplikacja webowa renderowana po stronie serwera. Logika biznesowa znajduje się w plikach PHP, a widoki są generowane bezpośrednio po stronie serwera.

## Warstwy rozwiązania

### 1. Warstwa prezentacji

Odpowiada za widoki HTML, układ stron i podstawowe interakcje JavaScript.

Główne pliki:

- `public/template/dashboard.php`
- `public/template/logi.php`
- `public/template/projekty.php`
- `public/template/planer-przyszlosci.php`
- `public/template/portfolio-cv.php`

### 2. Warstwa logiki aplikacyjnej

Realizuje obsługę formularzy, walidację danych, filtrowanie wyników i przekierowania.

Przykłady:

- dodawanie i edycja rekordów
- zmiana statusów
- generowanie danych do kalendarza
- przygotowanie danych do portfolio

### 3. Warstwa danych

Odpowiada za komunikację z MySQL / MariaDB poprzez MySQLi.

Pliki i elementy:

- `public/template/polaczenie.php`
- `baza.sql`
- zapytania SQL w poszczególnych widokach

W `polaczenie.php` działa też codzienny mechanizm synchronizacji struktury bazy z `baza.sql`, który dopisuje brakujące elementy schematu bez dotykania danych użytkowników.

## Autoryzacja

Aplikacja korzysta z sesji PHP:

- po zalogowaniu zapisuje identyfikator użytkownika w `$_SESSION`
- widoki chronione sprawdzają obecność sesji
- wylogowanie czyści sesję i przekierowuje do ekranu logowania

## Organizacja plików

- `public/template/` - logika i widoki PHP
- `public/style/` - arkusze stylów
- `public/img/` - zasoby graficzne
- `docs/` - dokumentacja projektu
- `baza.sql` - schemat i dane bazy

## Komunikacja między modułami

Moduły są luźno powiązane i korzystają z tej samej bazy danych oraz tej samej sesji użytkownika. Dzięki temu:

- dashboard widzi zadania i terminy
- planer agreguje dane z kilku tabel
- portfolio pobiera podsumowanie z wielu modułów

## Kluczowe decyzje architektoniczne

- prosty stack bez dodatkowego frameworka
- czytelne rozdzielenie widoków na osobne pliki
- brak skomplikowanego backendu API
- łatwe uruchomienie na XAMPP lub podobnym środowisku

## Integracje

Integracja Google Calendar została całkowicie usunięta. System działa lokalnie na danych własnych aplikacji.

Synchronizacja bazy nie jest zewnętrzną integracją, tylko lokalnym mechanizmem utrzymania zgodności schematu.
