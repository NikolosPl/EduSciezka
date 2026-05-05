# Moduły aplikacji

## Spis treści

- [Strona główna](#1-strona-główna)
- [Logowanie i rejestracja](#2-logowanie-i-rejestracja)
- [Dashboard](#3-dashboard)
- [Projekty](#4-projekty)
- [Log sukcesu](#5-log-sukcesu)
- [Planer przyszłości](#6-planer-przyszłości)
- [Portfolio / CV](#7-portfolio--cv)
- [Panel administratora](#8-panel-administratora)
- [Style i zasoby wspólne](#9-style-i-zasoby-wspólne)
- [Dokumentacja](#10-dokumentacja)

## 1. Strona główna

Plik:

- `public/template/index.html`

Funkcja:

- prezentuje aplikację użytkownikowi niezalogowanemu
- prowadzi do logowania
- stanowi stronę wejściową projektu

## 2. Logowanie i rejestracja

Pliki:

- `public/template/logowanie.php`
- `public/template/wyloguj.php`
- `public/template/admin_login.php`

Funkcja:

- logowanie użytkownika i rejestracja konta
- obsługa sesji
- wylogowanie
- osobny dostęp administracyjny

## 3. Dashboard

Plik:

- `public/template/dashboard.php`

Funkcja:

- centralny panel użytkownika
- bieżące zadania
- terminy szkolne
- statystyki postępów
- szybki podgląd logów sukcesu
- obsługa daty startowej zadań
- automatyczne przełączanie zadania na "w toku" po nadejściu dnia startu
- ręczna zmiana statusu zadania na "w toku"

## 4. Projekty

Plik:

- `public/template/projekty.php`

Funkcja:

- tworzenie i przegląd projektów
- zarządzanie statusem projektu
- przypisywanie priorytetów i kategorii

## 5. Log sukcesu

Plik:

- `public/template/logi.php`

Funkcja:

- historia ukończonych działań
- zliczanie punktów XP
- wizualizacja postępów użytkownika

## 6. Planer przyszłości

Plik:

- `public/template/planer-przyszlosci.php`

Funkcja:

- planowanie długoterminowe
- ręczne zarządzanie etapami życia i rozwoju
- kalendarz z filtrami
- szybkie akcje na etapach
- autoplan AI
- symulator ścieżek życia
- panel ryzyka
- panel „Co robić dziś”

## 7. Portfolio / CV

Plik:

- `public/template/portfolio-cv.php`

Funkcja:

- przygotowanie widoku pod CV i portfolio
- agregacja danych z kilku modułów
- druk / zapis do PDF przez przeglądarkę

## 8. Panel administratora

Pliki:

- `public/template/admin.php`
- `public/template/admin_login.php`

Funkcja:

- przegląd użytkowników
- przegląd zadań
- przegląd logów sukcesu
- proste operacje administracyjne

## 9. Style i zasoby wspólne

Pliki:

- `public/style/*.css`
- `public/img/`

Funkcja:

- warstwa wizualna aplikacji
- osobne style dla głównych widoków
- zasoby graficzne i ikony

## 10. Dokumentacja

Pliki:

- `docs/README.md`
- `docs/01-opis-projektu.md`
- `docs/02-architektura-aplikacji.md`
- `docs/03-baza-danych.md`
- `docs/04-moduly-aplikacji.md`
- `docs/05-uruchomienie-i-konfiguracja.md`

Funkcja:

- opis całości projektu
- pomoc przy wdrażaniu i rozwijaniu aplikacji
