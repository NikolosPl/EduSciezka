-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Maj 08, 2026 at 05:19 PM
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
-- Database: `nikolospl_edusciezka`
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
-- Struktura tabeli dla tabeli `ogloszenia`
--

CREATE TABLE `ogloszenia` (
  `id` int(11) NOT NULL,
  `tytul` varchar(100) NOT NULL,
  `tresc` varchar(3000) NOT NULL,
  `krotki_opis` varchar(100) NOT NULL,
  `zdjecie` text NOT NULL,
  `data_dodania` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ogloszenia`
--

INSERT INTO `ogloszenia` (`id`, `tytul`, `tresc`, `krotki_opis`, `zdjecie`, `data_dodania`) VALUES
(1, 'Mega', 'Mega super haha śmieszne beka. Inwokacja:if (isset($_GET[\'usun_ogloszenie\']) && is_numeric($_GET[\'usun_ogloszenie\'])) {\r\n    $id = (int)$_GET[\'usun_ogloszenie\'];\r\n    \r\n    // Najpierw sprawdzamy czy jest zdjęcie, żeby je skasować z dysku\r\n    $stare_foto = mysqli_query($polaczenie, \"SELECT zdjecie FROM ogloszenia WHERE id = $id\");\r\n    $foto_data = mysqli_fetch_assoc($stare_foto);\r\n    \r\n    if ($foto_data && !empty($foto_data[\'zdjecie\'])) {\r\n        $sciezka_do_pliku = \"../uploads/ogloszenia/\" . $foto_data[\'zdjecie\'];\r\n        if (file_exists($sciezka_do_pliku)) {\r\n            unlink($sciezka_do_pliku);\r\n        }\r\n    }\r\n    \r\n    mysqli_query($polaczenie, \"DELETE FROM ogloszenia WHERE id = $id\");\r\n    header(\"Location: admin.php?msg=sukces:Ogłoszenie usunięte!\");\r\n    exit;\r\n}\r\n\r\n// 2. LOGIKA DODAWANIA OGŁOSZENIA\r\nif (isset($_POST[\'dodaj_ogloszenie\'])) {\r\n    // Zabezpieczamy tekst\r\n    $tytul = mysqli_real_escape_string($polaczenie, $_POST[\'ogloszenie_tytul\']);\r\n    $tresc_raw = $_POST[\'ogloszenie_tresc\'];\r\n    $tresc = mysqli_real_escape_string($polaczenie, $tresc_raw);\r\n    \r\n    // AUTOMATYCZNY KRÓTKI OPIS (skracanie do 100 znaków z treści głównej)\r\n    $skrocony = mb_substr(strip_tags($tresc_raw), 0, 100);\r\n    $krotki_opis = mysqli_real_escape_string($polaczenie, $skrocony);\r\n    \r\n    $data_dodania = date(\"Y-m-d\");\r\n    $nazwa_foto = \"\"; // Domyślnie puste\r\n    $blad_pliku = false;\r\n\r\n    // OBSŁUGA ZDJĘCIA + ZABEZPIECZENIA\r\n    if (isset($_FILES[\'ogloszenie_foto\']) && $_FILES[\'ogloszenie_foto\'][\'error\'] == 0) {\r\n        $file = $_FILES[\'ogloszenie_foto\'];\r\n        $upload_dir = \"../uploads/ogloszenia/\";\r\n        \r\n        // Sprawdzamy typ MIME (czy to na pewno obrazek)\r\n        $finfo = finfo_open(FILEINFO_MIME_TYPE);\r\n        $mime = finfo_file($finfo, $file[\'tmp_name\']);\r\n        $dozwolone_mimes = [\'image/jpeg\', \'image/png\', \'image/gif\', \'image/webp\'];\r\n        \r\n        // Sprawdzamy rozszerzenie\r\n        $ext = strtolower(pathinfo($file[\'name\'], PATHINFO_EXTENSION));\r\n        $dozwolone_ext = [\'jpg\', \'jpeg\', \'png\', \'gif\', \'webp\'];\r\n\r\n        if (!in_array($mime, $dozwolone_mimes) || !in_array($ext, $dozwolone_ext)) {\r\n            $komunikat = \"blad:Nieprawidłowy format zdjęcia (tylko JPG, PNG, WEBP).\";\r\n            $blad_pliku = true;\r\n        } elseif ($file[\'size\'] > 2 * 1024 * 1024) { // Max 2MB\r\n            $komunikat = \"blad:Zdjęcie jest za duże (max 2MB).\";\r\n            $blad_pliku = true;\r\n        } else {\r\n            // Generujemy unikalną, bezpieczną nazwę\r\n            $nazwa_foto = bin2hex(random_bytes(8)) . \".\" . $ext;\r\n            \r\n            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);\r\n            \r\n            if (!move_uploaded_file($file[\'tmp_name\'], $upload_dir . $nazwa_foto)) {\r\n                $komunikat = \"blad:Błąd podczas zapisywania pliku na serwerze.\";\r\n                $blad_pliku = true;\r\n            }\r\n        }\r\n        finfo_close($finfo);\r\n    }\r\n\r\n    // ZAPIS DO BAZY (jeśli nie był', 'Mega super haha śmieszne beka. Inwokacja:if (isset($_GET[\'usun_ogloszenie\']) && is_numeric($_GET[\'us', '510f5aa970573e58.png', '2026-05-08');

-- --------------------------------------------------------

--
-- Struktura tabeli dla tabeli `planer_przyszlosci`
--

CREATE TABLE `planer_przyszlosci` (
  `id` int(10) UNSIGNED NOT NULL,
  `uzytkownik_id` int(10) UNSIGNED NOT NULL,
  `etap` enum('szkola_koniec','studia','brak_studiow','praca','certyfikat_szkolenie') NOT NULL,
  `tytul` varchar(150) NOT NULL,
  `opis` text DEFAULT NULL,
  `data_start` date NOT NULL,
  `data_koniec` date DEFAULT NULL,
  `status` enum('plan','w_toku','zakonczone') NOT NULL DEFAULT 'plan',
  `utworzony_o` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `planer_przyszlosci`
--

INSERT INTO `planer_przyszlosci` (`id`, `uzytkownik_id`, `etap`, `tytul`, `opis`, `data_start`, `data_koniec`, `status`, `utworzony_o`) VALUES
(1, 2, 'szkola_koniec', 'Matura i domkniecie szkoly', 'Autoplan AI dla celu: wafafa. Szacowany czas: 6 tygodni.', '2026-05-23', '2026-07-03', 'plan', '2026-05-08 16:27:28'),
(2, 2, 'studia', 'Studia zaoczne lub online', 'Autoplan AI dla celu: wafafa. Szacowany czas: 18 tygodni.', '2026-07-04', '2026-11-06', 'plan', '2026-05-08 16:27:28'),
(3, 2, 'praca', 'Praca + nauka rownolegle', 'Autoplan AI dla celu: wafafa. Szacowany czas: 20 tygodni.', '2026-11-07', '2027-03-26', 'plan', '2026-05-08 16:27:28'),
(4, 2, 'praca', 'Specjalizacja i awans junior -> regular', 'Autoplan AI dla celu: wafafa. Szacowany czas: 16 tygodni.', '2027-03-27', '2027-07-16', 'plan', '2026-05-08 16:27:28'),
(5, 2, 'certyfikat_szkolenie', 'Certyfikat strategiczny dla celu: wafafa', 'Autoplan AI dla celu: wafafa. Szacowany czas: 8 tygodni.', '2027-07-17', '2027-09-10', 'plan', '2026-05-08 16:27:28');

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
-- Struktura tabeli dla tabeli `system_meta`
--

CREATE TABLE `system_meta` (
  `klucz` varchar(100) NOT NULL,
  `wartosc` text DEFAULT NULL,
  `zaktualizowano_o` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `system_meta`
--

INSERT INTO `system_meta` (`klucz`, `wartosc`, `zaktualizowano_o`) VALUES
('schema_sync_last_run', '2026-05-08', '2026-05-08 16:17:43');

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
(1, 'Jan', 'Kowalski', 'jan.kowalski@example.com', '$2y$12$placeholder_bcrypt_hash', NULL, '2026-03-14 18:22:54', NULL, 1),
(2, 'Wiktoria', 'Chojnacka', 'wiktoria@example.com', '$2y$10$dRGnlBYGms89DMFclLOSdOq6pCUNBVuO.fvBb95TneReN4jqiIWNi', NULL, '2026-05-08 16:18:13', '2026-05-08 16:18:21', 1);

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
  `data_start` date DEFAULT NULL,
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

INSERT INTO `zadania` (`id`, `uzytkownik_id`, `projekt_id`, `kategoria_id`, `tytul`, `opis`, `data_start`, `deadline`, `czas_szacowany`, `priorytet`, `status`, `ukonczone_o`, `powtarzalne`, `regula_powtorzen`, `utworzony_o`) VALUES
(1, 1, 1, 2, 'Zaprojektuj strukturę repozytoriów', NULL, NULL, '2025-04-10 23:59:00', NULL, 'sredni', 'do_zrobienia', NULL, 0, NULL, '2026-03-14 18:22:54'),
(2, 1, 1, 2, 'Ukończ projekt nr 1 – kalkulator', NULL, NULL, '2025-04-30 23:59:00', NULL, 'wysoki', 'do_zrobienia', NULL, 0, NULL, '2026-03-14 18:22:54'),
(3, 1, 1, 2, 'Napisz README dla każdego projektu', NULL, NULL, '2025-05-15 23:59:00', NULL, 'niski', 'do_zrobienia', NULL, 0, NULL, '2026-03-14 18:22:54');

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
-- Indeksy dla tabeli `ogloszenia`
--
ALTER TABLE `ogloszenia`
  ADD PRIMARY KEY (`id`);

--
-- Indeksy dla tabeli `planer_przyszlosci`
--
ALTER TABLE `planer_przyszlosci`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_planer_user_data` (`uzytkownik_id`,`data_start`);

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
-- Indeksy dla tabeli `system_meta`
--
ALTER TABLE `system_meta`
  ADD PRIMARY KEY (`klucz`);

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
  ADD KEY `idx_zadania_data_start` (`data_start`),
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
-- AUTO_INCREMENT for table `ogloszenia`
--
ALTER TABLE `ogloszenia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `planer_przyszlosci`
--
ALTER TABLE `planer_przyszlosci`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
-- Constraints for table `planer_przyszlosci`
--
ALTER TABLE `planer_przyszlosci`
  ADD CONSTRAINT `planer_przyszlosci_ibfk_1` FOREIGN KEY (`uzytkownik_id`) REFERENCES `uzytkownicy` (`id`) ON DELETE CASCADE;

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
