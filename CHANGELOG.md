# Rejestr zmian

W tym pliku zapisujemy najważniejsze zmiany w projekcie.

## [0.1.0] - 2026-04-19

### Dodano

- panel planowania z zadaniami, projektami i logiem sukcesu
- planer przyszłości z kalendarzem, filtrami i edytowalnymi etapami
- generator autoplanu dla ścieżek przyszłości
- symulator scenariuszy kariery
- ostrzeżenia ryzyka i panel dziennych zadań
- widok portfolio i CV z możliwością wydruku do PDF
- strukturę dokumentacji repozytorium
- poradnik współtworzenia projektu
- rejestr zmian projektu
- licencję MIT

## [1.1.0] - 2026-04-30

### Dodano

- dodanie strony jako aktualnej witryny
- dodanie kontakt.html i kontakt-style.css
- zmiana polaczenie.php
- lekka poprawka w logowanie.php i menu-style.css

## [1.3.0] - 2026-05-09

### Zmieniono

- **logowanie.php** - całkowity redesign formularzy logowania i rejestracji, nowoczesny design z gradient header, animowanymi tabami, zaokrąglonym przyciskiem
- **admin_login.php** - redesign panelu admina, ciemny slate header z czerwonym badge, spójność z resztą aplikacji
- **index-style.css** - usunięto stare style auth (dublowane), kod uporządkowany

### Dodano

- **logowanie-style.css** - dedykowany plik CSS dla logowania/rejestracji, font Source Sans 3, responsywny design
- **admin_login-style.css** - dedykowany plik CSS dla panelu admina
- mechanizm ukrytego dostępu do panelu admina (5 kliknięć w logo)
- footer na stronach logowania spójny z resztą aplikacji

### Poprawki

- naprawiono błędy JavaScript w logowanie.php (id triggera)
- dodano obsługę polskich znaków w alertach (SVG ikony)
- wszystkie strony PHP przetestowane pod kątem błędów składniowych

## [1.2.0] - 2026-05-05

### Zmieniono

- dashboard zadań obsługuje datę startową i automatycznie przełącza zadanie na "w toku" po nadejściu dnia startu
- dodano ręczną akcję ustawienia zadania jako "w toku"
- połączenie z bazą wykonuje codzienny import struktury schematu z `baza.sql`, bez importowania danych przykładowych
- dokumentacja została zaktualizowana do aktualnego zachowania aplikacji
