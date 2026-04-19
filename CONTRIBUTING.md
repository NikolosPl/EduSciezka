# Jak współtworzyć EduSciezkę

Jeśli chcesz coś dorzucić do EduSciezki, dzięki za pomoc.

## Jak zgłaszać zmiany

- Przy większych zmianach najlepiej najpierw otworzyć zgłoszenie.
- Trzymaj się jednego tematu na raz, zamiast mieszać kilka rzeczy w jednej prośbie o scalenie.
- Szanuj istniejący styl PHP, HTML i CSS w repo.
- Nie dokładaj nowych zależności bez wyraźnego powodu.
- Jeśli zmienia się działanie aplikacji, zaktualizuj też dokumentację.

## Zalecany przebieg pracy

1. Zrób osobny branch do swojej zmiany.
2. Wprowadzaj poprawki małymi krokami, żeby łatwo było je sprawdzić.
3. Przetestuj lokalnie strony, których dotyczy zmiana.
4. Jeśli trzeba, popraw `README.md` albo pliki w `docs/`.
5. Na końcu wyślij prośbę o scalenie z krótkim opisem, co się zmieniło.

## Styl kodu

- Używaj jasnych nazw zmiennych.
- Zostaw PHP proceduralne, chyba że naprawdę jest powód do refaktoru.
- Staraj się nie rozbijać struktury strony bez potrzeby.
- Lepiej zrobić małą, pewną poprawkę niż duży refactor bez potrzeby.

## Zgłaszanie błędów

Jeśli zgłaszasz błąd, dorzuć:

- nazwę strony
- oczekiwane zachowanie
- rzeczywiste zachowanie
- kroki do odtworzenia
- wersję przeglądarki i środowiska PHP/MySQL, jeśli ma to znaczenie

## Dokumentacja

Jeśli zmiana dotyczy działania aplikacji, bazy danych albo konfiguracji, popraw też odpowiedni plik w `docs/`.
