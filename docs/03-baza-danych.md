# Baza danych

## Spis treści

- [Ogólny opis](#ogólny-opis)
- [Główne encje](#główne-encje)
- [Relacje między tabelami](#relacje-między-tabelami)
- [Charakterystyka schematu](#charakterystyka-schematu)
- [Uwagi techniczne](#uwagi-techniczne)

## Ogólny opis

Projekt korzysta z relacyjnej bazy danych MySQL / MariaDB. Schemat znajduje się w pliku `baza.sql`. Baza przechowuje dane użytkowników, zadania, projekty, terminy szkolne, logi sukcesu, umiejętności oraz dane planera przyszłości.

Aplikacja przy starcie wykonuje codzienny import struktury z `baza.sql`, dzięki czemu nowe kolumny i indeksy mogą być dopisywane bez ręcznego kasowania bazy.

## Główne encje

### `uzytkownicy`

Przechowuje konta użytkowników aplikacji.

Najważniejsze pola:

- `imie`
- `nazwisko`
- `email`
- `haslo_hash`
- `data_rejestracji`
- `ostatnie_logowanie`
- `aktywny`

### `sesje`

Przechowuje informacje o aktywnych sesjach i tokenach logowania.

### `zadania`

Reprezentuje bieżące zadania użytkownika.

Najważniejsze pola:

- `tytul`
- `opis`
- `data_start`
- `deadline`
- `priorytet`
- `status`
- `powtarzalne`

### `projekty`

Przechowuje projekty rozwojowe i zadaniowe.

Najważniejsze pola:

- `nazwa`
- `opis`
- `data_start`
- `deadline`
- `priorytet`
- `status`
- `procent_ukonczenia`

### `kategorie`

Służy do grupowania projektów i zadań.

### `terminy_szkolne`

Przechowuje terminy szkolne, takie jak sprawdziany, projekty i egzaminy.

Najważniejsze pola:

- `przedmiot`
- `typ`
- `opis`
- `data_termin`
- `zaliczone`

### `logi_sukcesow`

Zapisuje osiągnięcia użytkownika, punkty XP i powiązanie z zadaniem lub terminem.

### `umiejetnosci`

Przechowuje kompetencje użytkownika i ich poziom.

### `kamienie_milowe`

Przechowuje ważne kamienie milowe projektów.

### `powiadomienia`

Przechowuje przypomnienia, ostrzeżenia i komunikaty systemowe.

### `planer_przyszlosci`

Najważniejsza tabela dla długoterminowego planowania.

Najważniejsze pola:

- `etap`
- `tytul`
- `opis`
- `data_start`
- `data_koniec`
- `status`
- `utworzony_o`

### `system_meta`

Przechowuje techniczne znaczniki działania aplikacji, w tym datę ostatniego dziennego syncu schematu.

## Relacje między tabelami

- `uzytkownicy` jest tabelą nadrzędną dla większości rekordów
- `zadania`, `projekty`, `terminy_szkolne`, `logi_sukcesow`, `umiejetnosci` i `planer_przyszlosci` odnoszą się do użytkownika przez `uzytkownik_id`
- `logi_sukcesow` może wskazywać na zadanie, kamień milowy lub termin szkolny
- `zadania` i `projekty` mogą być powiązane z kategoriami

## Charakterystyka schematu

- schemat jest prosty i czytelny
- większość tabel posiada klucz główny `id`
- dane są filtrowane po `uzytkownik_id`
- używane są indeksy wspierające typowe zapytania po dacie i użytkowniku
- schemat może być rozszerzany automatycznie przez aplikację bez ręcznej reinstalacji bazy

## Uwagi techniczne

- w projekcie nie ma już integracji Google Calendar
- wszystkie dane planera są przechowywane lokalnie
- baza jest gotowa do importu przez phpMyAdmin lub konsolę MySQL
