-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 06:28 PM
-- Wersja serwera: 10.4.32-MariaDB
-- Wersja PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `student_planner`
--

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kamienie_milowe`
--

CREATE TABLE `kamienie_milowe` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `projekt_id` int(10) UNSIGNED DEFAULT NULL,
  `tytul` varchar(150) NOT NULL,
  `opis` text DEFAULT NULL,
  `planowana_data` date NOT NULL,
  `osiagnieta_data` date DEFAULT NULL,
  `osiagniety` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kamienie_milowe`
--

INSERT INTO `kamienie_milowe` (`id`, `uzytkownik_id`, `projekt_id`, `tytul`, `opis`, `planowana_data`, `osiagnieta_data`, `osiagniety`) VALUES
(1, 1, 1, 'Pierwsze 50 gwiazdek na GitHubie', NULL, '2025-12-31', NULL, 0);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `kategorie`
--

CREATE TABLE `kategorie` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED DEFAULT NULL,
  `nazwa` varchar(80) NOT NULL,
  `kolor_hex` char(7) NOT NULL DEFAULT '#3B82F6',
  `ikona` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategorie`
--

INSERT INTO `kategorie` (`id`, `uzytkownik_id`, `nazwa`, `kolor_hex`, `ikona`) VALUES
(1, NULL, 'Szkoła', '#EF4444', 'school'),
(2, NULL, 'Projekt zawodowy', '#8B5CF6', 'briefcase'),
(3, NULL, 'Kurs online', '#10B981', 'monitor'),
(4, NULL, 'Rozwój osobisty', '#F59E0B', 'star'),
(5, NULL, 'Inne', '#6B7280', 'tag');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `logi_sukcesow`
--

CREATE TABLE `logi_sukcesow` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `zadanie_id` int(10) UNSIGNED DEFAULT NULL,
  `kamien_id` int(10) UNSIGNED DEFAULT NULL,
  `termin_id` int(10) UNSIGNED DEFAULT NULL,
  `tytul` varchar(200) NOT NULL,
  `opis` text DEFAULT NULL,
  `typ` enum('zadanie','kamien_milowy','termin_szkolny','kurs','inne') NOT NULL DEFAULT 'zadanie',
  `data_osiagniecia` datetime NOT NULL DEFAULT current_timestamp(),
  `punkty_xp` smallint(5) UNSIGNED NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `powiadomienia`
--

CREATE TABLE `powiadomienia` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `zadanie_id` int(10) UNSIGNED DEFAULT NULL,
  `termin_id` int(10) UNSIGNED DEFAULT NULL,
  `kamien_id` int(10) UNSIGNED DEFAULT NULL,
  `tresc` varchar(255) NOT NULL,
  `typ` enum('przypomnienie','ostrzezenie','sukces','info') NOT NULL DEFAULT 'przypomnienie',
  `zaplanowane_na` datetime NOT NULL,
  `wyslane` tinyint(1) NOT NULL DEFAULT 0,
  `wyslane_o` datetime DEFAULT NULL,
  `przeczytane` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `projekty`
--

CREATE TABLE `projekty` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `kategoria_id` int(10) UNSIGNED DEFAULT NULL,
  `nazwa` varchar(150) NOT NULL,
  `opis` text DEFAULT NULL,
  `data_start` date DEFAULT NULL,
  `deadline` date NOT NULL,
  `priorytet` enum('niski','sredni','wysoki','krytyczny') NOT NULL DEFAULT 'sredni',
  `status` enum('planowany','w_toku','zakończony','porzucony') NOT NULL DEFAULT 'planowany',
  `procent_ukonczenia` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `utworzony_o` datetime NOT NULL DEFAULT current_timestamp(),
  `zaktualizowany_o` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `projekty`
--

INSERT INTO `projekty` (`id`, `uzytkownik_id`, `kategoria_id`, `nazwa`, `opis`, `data_start`, `deadline`, `priorytet`, `status`, `procent_ukonczenia`, `utworzony_o`, `zaktualizowany_o`) VALUES
(1, 1, 2, 'Portfolio na GitHub', 'Zbudowanie publicznego portfolio z 3 projektami webowymi przed maturą.', NULL, '2025-06-30', 'wysoki', 'w_toku', 0, '2026-03-14 18:22:54', '2026-03-14 18:22:54');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `sesje`
--

CREATE TABLE `sesje` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `utworzona_o` datetime NOT NULL DEFAULT current_timestamp(),
  `wygasa_o` datetime NOT NULL,
  `aktywna` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `terminy_szkolne`
--

CREATE TABLE `terminy_szkolne` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `zadanie_id` int(10) UNSIGNED DEFAULT NULL,
  `przedmiot` varchar(100) NOT NULL,
  `typ` enum('sprawdzian','kartkówka','projekt','egzamin','prezentacja','inne') NOT NULL,
  `opis` text DEFAULT NULL,
  `data_termin` datetime NOT NULL,
  `sala` varchar(30) DEFAULT NULL,
  `nauczyciel` varchar(100) DEFAULT NULL,
  `ocena` decimal(3,1) DEFAULT NULL,
  `zaliczone` tinyint(1) NOT NULL DEFAULT 0,
  `utworzony_o` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `terminy_szkolne`
--

INSERT INTO `terminy_szkolne` (`id`, `uzytkownik_id`, `zadanie_id`, `przedmiot`, `typ`, `opis`, `data_termin`, `sala`, `nauczyciel`, `ocena`, `zaliczone`, `utworzony_o`) VALUES
(1, 1, NULL, 'Matematyka', 'sprawdzian', NULL, '2025-04-22 09:00:00', NULL, 'mgr Anna Nowak', NULL, 0, '2026-03-14 18:22:54');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `umiejetnosci`
--

CREATE TABLE `umiejetnosci` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `nazwa` varchar(100) NOT NULL,
  `kategoria` varchar(80) DEFAULT NULL,
  `poziom` tinyint(3) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `uzytkownicy`
--

CREATE TABLE `uzytkownicy` (
  `id` int(10) UNSIGNED NOT NULL,
  `imie` varchar(60) NOT NULL,
  `nazwisko` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `haslo_hash` varchar(255) NOT NULL,
  `avatar_url` varchar(255) DEFAULT NULL,
  `data_rejestracji` datetime NOT NULL DEFAULT current_timestamp(),
  `ostatnie_logowanie` datetime DEFAULT NULL,
  `aktywny` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `uzytkownicy`
--

INSERT INTO `uzytkownicy` (`id`, `imie`, `nazwisko`, `email`, `haslo_hash`, `avatar_url`, `data_rejestracji`, `ostatnie_logowanie`, `aktywny`) VALUES
(1, 'Jan', 'Kowalski', 'jan.kowalski@example.com', '$2y$12$placeholder_bcrypt_hash', NULL, '2026-03-14 18:22:54', NULL, 1);

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zadania`
--

CREATE TABLE `zadania` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `projekt_id` int(10) UNSIGNED DEFAULT NULL,
  `kategoria_id` int(10) UNSIGNED DEFAULT NULL,
  `tytul` varchar(200) NOT NULL,
  `opis` text DEFAULT NULL,
  `deadline` datetime NOT NULL,
  `czas_szacowany` smallint(5) UNSIGNED DEFAULT NULL,
  `priorytet` enum('niski','sredni','wysoki','krytyczny') NOT NULL DEFAULT 'sredni',
  `status` enum('do_zrobienia','w_toku','zakończone','anulowane') NOT NULL DEFAULT 'do_zrobienia',
  `ukonczone_o` datetime DEFAULT NULL,
  `powtarzalne` tinyint(1) NOT NULL DEFAULT 0,
  `regula_powtorzen` varchar(100) DEFAULT NULL,
  `utworzony_o` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `zadania`
--

INSERT INTO `zadania` (`id`, `uzytkownik_id`, `projekt_id`, `kategoria_id`, `tytul`, `opis`, `deadline`, `czas_szacowany`, `priorytet`, `status`, `ukonczone_o`, `powtarzalne`, `regula_powtorzen`, `utworzony_o`) VALUES
(1, 1, 1, 2, 'Zaprojektuj strukturę repozytoriów', NULL, '2025-04-10 23:59:00', NULL, 'sredni', 'do_zrobienia', NULL, 0, NULL, '2026-03-14 18:22:54'),
(2, 1, 1, 2, 'Ukończ projekt nr 1 – kalkulator', NULL, '2025-04-30 23:59:00', NULL, 'wysoki', 'do_zrobienia', NULL, 0, NULL, '2026-03-14 18:22:54'),
(3, 1, 1, 2, 'Napisz README dla każdego projektu', NULL, '2025-05-15 23:59:00', NULL, 'niski', 'do_zrobienia', NULL, 0, NULL, '2026-03-14 18:22:54');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `zadania_umiejetnosci`
--

CREATE TABLE `zadania_umiejetnosci` (
  `zadanie_id` int(10) UNSIGNED NOT NULL,
  `umiejetnosc_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indeksy dla zrzutów tabel
--

--
-- Indeksy dla tabeli `kamienie_milowe`
--
ALTER TABLE `kamienie_milowe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `projekt_id` (`projekt_id`);

--
-- Indeksy dla tabeli `kategorie`
--
ALTER TABLE `kategorie`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `logi_sukcesow`
--
ALTER TABLE `logi_sukcesow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `zadanie_id` (`zadanie_id`),
  ADD KEY `kamien_id` (`kamien_id`),
  ADD KEY `termin_id` (`termin_id`),
  ADD KEY `idx_logi_data` (`data_osiagniecia`);

--
-- Indeksy dla tabeli `powiadomienia`
--
ALTER TABLE `powiadomienia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `zadanie_id` (`zadanie_id`),
  ADD KEY `termin_id` (`termin_id`),
  ADD KEY `kamien_id` (`kamien_id`),
  ADD KEY `idx_powiadomienia_zaplan` (`zaplanowane_na`,`wyslane`);

--
-- Indeksy dla tabeli `projekty`
--
ALTER TABLE `projekty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `kategoria_id` (`kategoria_id`),
  ADD KEY `idx_projekty_deadline` (`deadline`);

--
-- Indeksy dla tabeli `sesje`
--
ALTER TABLE `sesje`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `terminy_szkolne`
--
ALTER TABLE `terminy_szkolne`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `zadanie_id` (`zadanie_id`),
  ADD KEY `idx_terminy_data` (`data_termin`);

--
-- Indeksy dla tabeli `umiejetnosci`
--
ALTER TABLE `umiejetnosci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`);

--
-- Indeksy dla tabeli `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeksy dla tabeli `zadania`
--
ALTER TABLE `zadania`
  ADD PRIMARY KEY (`id`),
  ADD KEY `uzytkownik_id` (`uzytkownik_id`),
  ADD KEY `projekt_id` (`projekt_id`),
  ADD KEY `kategoria_id` (`kategoria_id`),
  ADD KEY `idx_zadania_deadline` (`deadline`),
  ADD KEY `idx_zadania_status` (`status`);

--
-- Indeksy dla tabeli `zadania_umiejetnosci`
--
ALTER TABLE `zadania_umiejetnosci`
  ADD PRIMARY KEY (`zadanie_id`,`umiejetnosc_id`),
  ADD KEY `umiejetnosc_id` (`umiejetnosc_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kamienie_milowe`
--
ALTER TABLE `kamienie_milowe`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `kategorie`
--
ALTER TABLE `kategorie`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `logi_sukcesow`
--
ALTER TABLE `logi_sukcesow`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `powiadomienia`
--
ALTER TABLE `powiadomienia`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `projekty`
--
ALTER TABLE `projekty`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sesje`
--
ALTER TABLE `sesje`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `terminy_szkolne`
--
ALTER TABLE `terminy_szkolne`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `umiejetnosci`
--
ALTER TABLE `umiejetnosci`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uzytkownicy`
--
ALTER TABLE `uzytkownicy`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `zadania`
--
ALTER TABLE `zadania`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kamienie_milowe`
--
ALTER TABLE `kamienie_milowe`
  ADD CONSTRAINT `kamienie_milowe_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `kamienie_milowe_ibfk_2` FOREIGN KEY (`projekt_id`) REFERENCES `projekty` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `kategorie`
--
ALTER TABLE `kategorie`
  ADD CONSTRAINT `kategorie_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `logi_sukcesow`
--
ALTER TABLE `logi_sukcesow`
  ADD CONSTRAINT `logi_sukcesow_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `logi_sukcesow_ibfk_2` FOREIGN KEY (`zadanie_id`) REFERENCES `zadania` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `logi_sukcesow_ibfk_3` FOREIGN KEY (`kamien_id`) REFERENCES `kamienie_milowe` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `logi_sukcesow_ibfk_4` FOREIGN KEY (`termin_id`) REFERENCES `terminy_szkolne` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `powiadomienia`
--
ALTER TABLE `powiadomienia`
  ADD CONSTRAINT `powiadomienia_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `powiadomienia_ibfk_2` FOREIGN KEY (`zadanie_id`) REFERENCES `zadania` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `powiadomienia_ibfk_3` FOREIGN KEY (`termin_id`) REFERENCES `terminy_szkolne` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `powiadomienia_ibfk_4` FOREIGN KEY (`kamien_id`) REFERENCES `kamienie_milowe` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `projekty`
--
ALTER TABLE `projekty`
  ADD CONSTRAINT `projekty_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `projekty_ibfk_2` FOREIGN KEY (`kategoria_id`) REFERENCES `kategorie` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sesje`
--
ALTER TABLE `sesje`
  ADD CONSTRAINT `sesje_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `terminy_szkolne`
--
ALTER TABLE `terminy_szkolne`
  ADD CONSTRAINT `terminy_szkolne_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `terminy_szkolne_ibfk_2` FOREIGN KEY (`zadanie_id`) REFERENCES `zadania` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `umiejetnosci`
--
ALTER TABLE `umiejetnosci`
  ADD CONSTRAINT `umiejetnosci_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `zadania`
--
ALTER TABLE `zadania`
  ADD CONSTRAINT `zadania_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zadania_ibfk_2` FOREIGN KEY (`projekt_id`) REFERENCES `projekty` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zadania_ibfk_3` FOREIGN KEY (`kategoria_id`) REFERENCES `kategorie` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `zadania_umiejetnosci`
--
ALTER TABLE `zadania_umiejetnosci`
  ADD CONSTRAINT `zadania_umiejetnosci_ibfk_1` FOREIGN KEY (`zadanie_id`) REFERENCES `zadania` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `zadania_umiejetnosci_ibfk_2` FOREIGN KEY (`umiejetnosc_id`) REFERENCES `umiejetnosci` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
