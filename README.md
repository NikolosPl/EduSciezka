# EduSciezka

EduSciezka to webowa aplikacja do planowania nauki, rozwoju i kariery. Zamiast trzymać zadania, projekty i cele w kilku miejscach, łączy je w jeden panel, który pomaga ogarnąć bieżącą pracę i dłuższą drogę rozwoju.

## Co tu znajdziesz

To repozytorium zawiera gotową aplikację webową opartą na PHP i MySQL. Projekt był tworzony jako praktyczne narzędzie do organizacji nauki, planowania rozwoju i budowania portfolio na przyszłość.

## Najważniejsze funkcje

- panel użytkownika z zadaniami, projektami i logiem sukcesu
- planer przyszłości z kalendarzem, filtrami i edycją etapów
- autoplan generowany z celu i wybranej ścieżki
- symulator scenariuszy kariery i panel ryzyka
- automatyczne portfolio i CV do podglądu i wydruku
- panel administratora do przeglądu danych aplikacji

## Struktura repozytorium

```text
EduSciezka/
├── baza.sql
├── public/
│   ├── template/
│   └── style/
├── docs/
└── README.md
```

## Technologia

- PHP proceduralne
- MySQL / MariaDB
- MySQLi
- HTML, CSS i JavaScript
- sesje PHP do autoryzacji

## Szybki start

1. Zaimportuj [baza.sql](baza.sql) do bazy `student_planner`.
2. Ustaw połączenie z bazą w [public/template/polaczenie.php](public/template/polaczenie.php).
3. Uruchom projekt przez lokalny serwer, np. XAMPP.
4. Otwórz ekran logowania przez [public/template/logowanie.php](public/template/logowanie.php).

Po uruchomieniu aplikacja codziennie synchronizuje strukturę bazy z [baza.sql](baza.sql), więc nie trzeba ponownie usuwać i importować całej bazy przy zmianach schematu.

## Dokumentacja i pliki pomocnicze

Jeśli chcesz wejść głębiej w szczegóły, zajrzyj do [docs/README.md](docs/README.md).

## Główne obszary dokumentacji

- [Opis projektu](docs/01-opis-projektu.md)
- [Architektura aplikacji](docs/02-architektura-aplikacji.md)
- [Baza danych](docs/03-baza-danych.md)
- [Moduły aplikacji](docs/04-moduly-aplikacji.md)
- [Uruchomienie i konfiguracja](docs/05-uruchomienie-i-konfiguracja.md)
- [Zrzuty ekranu](docs/06-screenshots.md)

## Pliki repozytoryjne

- [CONTRIBUTING.md](CONTRIBUTING.md)
- [Rejestr zmian](CHANGELOG.md)
- [LICENSE](LICENSE)

## Status projektu

- aktywnie rozwijany
- działa bez zewnętrznych integracji Google Calendar
- po pierwszym imporcie bazy i uruchomieniu aplikacji sam aktualizuje strukturę schematu
