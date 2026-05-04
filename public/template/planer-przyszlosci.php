<?php
require_once "polaczenie.php";

if (!isset($_SESSION['uzytkownik_id'])) {
    header("Location: logowanie.php");
    exit;
}

$uid = (int) $_SESSION['uzytkownik_id'];
$imie = $_SESSION['imie'];
$komunikat = "";
$symulacja = null;

function esc($conn, $txt)
{
    return mysqli_real_escape_string($conn, (string) $txt);
}

function poprawna_data($tekst)
{
    return is_string($tekst) && preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $tekst) === 1;
}

function status_jest_zakonczony($status)
{
    return in_array($status, array('zakonczone', 'zakończone', 'zaliczone'), true);
}

function liczba_dni_do($data)
{
    $dzis = new DateTime(date('Y-m-d'));
    $cel = new DateTime($data);
    $diff = $dzis->diff($cel);
    return (int) $diff->format('%r%a');
}

function generuj_scenariusz_autoplanu($sciezka, $cel, $czy_certyfikat)
{
    $scenariusze = array(
        'studia' => array(
            array('etap' => 'szkola_koniec', 'tytul' => 'Matura i domkniecie szkoly', 'tyg' => 6),
            array('etap' => 'studia', 'tytul' => 'Rekrutacja na studia', 'tyg' => 6),
            array('etap' => 'studia', 'tytul' => 'Pierwszy semestr - fundamenty', 'tyg' => 20),
            array('etap' => 'praca', 'tytul' => 'Staz lub pierwsza praca kierunkowa', 'tyg' => 16),
        ),
        'praca' => array(
            array('etap' => 'szkola_koniec', 'tytul' => 'Matura i decyzja zawodowa', 'tyg' => 5),
            array('etap' => 'brak_studiow', 'tytul' => 'Plan wejscia na rynek pracy', 'tyg' => 4),
            array('etap' => 'praca', 'tytul' => 'Budowa CV i portfolio projektow', 'tyg' => 8),
            array('etap' => 'praca', 'tytul' => 'Pierwsza stabilna praca', 'tyg' => 18),
        ),
        'gap' => array(
            array('etap' => 'szkola_koniec', 'tytul' => 'Matura i plan roku rozwojowego', 'tyg' => 5),
            array('etap' => 'brak_studiow', 'tytul' => 'Rok rozwojowy: praktyki i wolontariat', 'tyg' => 24),
            array('etap' => 'certyfikat_szkolenie', 'tytul' => 'Mocny certyfikat potwierdzajacy kompetencje', 'tyg' => 10),
            array('etap' => 'praca', 'tytul' => 'Powrot z gotowym profilem zawodowym', 'tyg' => 14),
        ),
        'mieszana' => array(
            array('etap' => 'szkola_koniec', 'tytul' => 'Matura i domkniecie szkoly', 'tyg' => 6),
            array('etap' => 'studia', 'tytul' => 'Studia zaoczne lub online', 'tyg' => 18),
            array('etap' => 'praca', 'tytul' => 'Praca + nauka rownolegle', 'tyg' => 20),
            array('etap' => 'praca', 'tytul' => 'Specjalizacja i awans junior -> regular', 'tyg' => 16),
        ),
    );

    $plan = isset($scenariusze[$sciezka]) ? $scenariusze[$sciezka] : $scenariusze['mieszana'];
    if ($czy_certyfikat) {
        $plan[] = array(
            'etap' => 'certyfikat_szkolenie',
            'tytul' => 'Certyfikat strategiczny dla celu: ' . $cel,
            'tyg' => 8
        );
    }

    return $plan;
}

mysqli_query(
    $polaczenie,
    "CREATE TABLE IF NOT EXISTS planer_przyszlosci (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        uzytkownik_id INT(10) UNSIGNED NOT NULL,
        etap ENUM('szkola_koniec','studia','brak_studiow','praca','certyfikat_szkolenie') NOT NULL,
        tytul VARCHAR(150) NOT NULL,
        opis TEXT DEFAULT NULL,
        data_start DATE NOT NULL,
        data_koniec DATE DEFAULT NULL,
        status ENUM('plan','w_toku','zakonczone') NOT NULL DEFAULT 'plan',
        utworzony_o DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_planer_user_data (uzytkownik_id, data_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$etapy_dozwolone = array('szkola_koniec', 'studia', 'brak_studiow', 'praca', 'certyfikat_szkolenie');
$statusy_dozwolone = array('plan', 'w_toku', 'zakonczone');

if (isset($_POST['generuj_autoplan'])) {
    $auto_cel = trim((string) ($_POST['auto_cel'] ?? ''));
    $auto_sciezka = (string) ($_POST['auto_sciezka'] ?? 'mieszana');
    $auto_start = (string) ($_POST['auto_start'] ?? '');
    $auto_tempo = (string) ($_POST['auto_tempo'] ?? 'standard');
    $auto_certyfikat = isset($_POST['auto_certyfikat']) ? 1 : 0;

    $dozwolone_sciezki = array('studia', 'praca', 'gap', 'mieszana');
    $dozwolone_tempo = array('standard', 'intensywne');

    if ($auto_cel === '') {
        $komunikat = 'blad:Podaj cel glownego planu.';
    } elseif (!in_array($auto_sciezka, $dozwolone_sciezki, true)) {
        $komunikat = 'blad:Niepoprawny typ sciezki.';
    } elseif (!in_array($auto_tempo, $dozwolone_tempo, true)) {
        $komunikat = 'blad:Niepoprawne tempo planu.';
    } elseif (!poprawna_data($auto_start)) {
        $komunikat = 'blad:Podaj poprawna date startu autoplanu.';
    } else {
        $szablon = generuj_scenariusz_autoplanu($auto_sciezka, $auto_cel, $auto_certyfikat === 1);
        $mnoznik = $auto_tempo === 'intensywne' ? 0.75 : 1;
        $start_ts = strtotime($auto_start . ' 00:00:00');
        $aktualny_ts = $start_ts;
        $dodane = 0;

        foreach ($szablon as $krok) {
            $tyg = max(2, (int) round($krok['tyg'] * $mnoznik));
            $dni = $tyg * 7;
            $d_start = date('Y-m-d', $aktualny_ts);
            $d_koniec = date('Y-m-d', strtotime('+' . ($dni - 1) . ' day', $aktualny_ts));

            $etap_sql = esc($polaczenie, $krok['etap']);
            $tytul_sql = esc($polaczenie, $krok['tytul']);
            $opis_sql = esc($polaczenie, 'Autoplan AI dla celu: ' . $auto_cel . '. Szacowany czas: ' . $tyg . ' tygodni.');

            $sql_auto = "INSERT INTO planer_przyszlosci (uzytkownik_id, etap, tytul, opis, data_start, data_koniec, status)
                         VALUES ($uid, '$etap_sql', '$tytul_sql', '$opis_sql', '$d_start', '$d_koniec', 'plan')";
            if (mysqli_query($polaczenie, $sql_auto)) {
                $dodane++;
            }

            $aktualny_ts = strtotime('+1 day', strtotime($d_koniec . ' 00:00:00'));
        }

        if ($dodane > 0) {
            header('Location: planer-przyszlosci.php?msg=sukces: Autoplan wygenerował ' . $dodane . ' etapów.');
            exit;
        }
        $komunikat = 'błąd: Nie udało się wygenerować autoplanu.';
    }
}

if (isset($_POST['licz_symulacje'])) {
    $horyzont_lat = max(2, min(10, (int) ($_POST['sim_horyzont_lat'] ?? 5)));
    $lata_studiow = max(1, min(6, (int) ($_POST['sim_lata_studiow'] ?? 3)));
    $koszt_studia_msc = max(0, (float) ($_POST['sim_koszt_studia_msc'] ?? 1200));
    $kursy_rocznie = max(0, (float) ($_POST['sim_kursy_rocznie'] ?? 3000));
    $pensja_studia = max(0, (float) ($_POST['sim_pensja_studia'] ?? 6500));
    $pensja_praca = max(0, (float) ($_POST['sim_pensja_praca'] ?? 4500));
    $wzrost_studia = max(0, min(100, (float) ($_POST['sim_wzrost_studia'] ?? 9))) / 100;
    $wzrost_praca = max(0, min(100, (float) ($_POST['sim_wzrost_praca'] ?? 5))) / 100;

    $scenariusze = array();

    $studia_bilans = 0.0;
    for ($r = 1; $r <= $horyzont_lat; $r++) {
        if ($r <= $lata_studiow) {
            $studia_bilans -= ($koszt_studia_msc * 12) + $kursy_rocznie;
        } else {
            $rok_pracy = $r - $lata_studiow - 1;
            $studia_bilans += ($pensja_studia * pow(1 + $wzrost_studia, max(0, $rok_pracy))) * 12;
            $studia_bilans -= ($kursy_rocznie * 0.5);
        }
    }
    $scenariusze[] = array('nazwa' => 'Studia -> praca', 'bilans' => $studia_bilans, 'ryzyko' => 35, 'czas_do_dochodu' => $lata_studiow);

    $praca_bilans = 0.0;
    for ($r = 1; $r <= $horyzont_lat; $r++) {
        $praca_bilans += ($pensja_praca * pow(1 + $wzrost_praca, $r - 1)) * 12;
        $praca_bilans -= ($kursy_rocznie * 0.4);
    }
    $scenariusze[] = array('nazwa' => 'Praca od razu', 'bilans' => $praca_bilans, 'ryzyko' => 45, 'czas_do_dochodu' => 0);

    $gap_bilans = 0.0;
    for ($r = 1; $r <= $horyzont_lat; $r++) {
        if ($r === 1) {
            $gap_bilans -= ($kursy_rocznie * 2.5);
            $gap_bilans += ($pensja_praca * 6);
        } else {
            $gap_bilans += ($pensja_praca * pow(1 + ($wzrost_praca + 0.01), $r - 1)) * 12;
            $gap_bilans -= ($kursy_rocznie * 0.7);
        }
    }
    $scenariusze[] = array('nazwa' => 'Gap year + kursy', 'bilans' => $gap_bilans, 'ryzyko' => 55, 'czas_do_dochodu' => 1);

    foreach ($scenariusze as $i => $s) {
        $scenariusze[$i]['score'] = $s['bilans'] - ($s['ryzyko'] * 1800);
    }

    usort($scenariusze, function ($a, $b) {
        if ($a['score'] === $b['score']) {
            return 0;
        }
        return ($a['score'] > $b['score']) ? -1 : 1;
    });

    $symulacja = array(
        'horyzont' => $horyzont_lat,
        'scenariusze' => $scenariusze,
        'rekomendacja' => $scenariusze[0]['nazwa']
    );
}

$plan_do_edycji = null;
if (isset($_GET['edytuj']) && is_numeric($_GET['edytuj'])) {
    $eid = (int) $_GET['edytuj'];
    $res_ed = mysqli_query($polaczenie, "SELECT * FROM planer_przyszlosci WHERE id = $eid AND uzytkownik_id = $uid LIMIT 1");
    if ($res_ed && mysqli_num_rows($res_ed) === 1) {
        $plan_do_edycji = mysqli_fetch_assoc($res_ed);
    }
}

if (isset($_POST['dodaj_plan']) || isset($_POST['zapisz_edycje'])) {
    $etap = isset($_POST['etap']) ? mysqli_real_escape_string($polaczenie, $_POST['etap']) : '';
    $tytul = isset($_POST['tytul']) ? mysqli_real_escape_string($polaczenie, trim($_POST['tytul'])) : '';
    $opis = isset($_POST['opis']) ? mysqli_real_escape_string($polaczenie, trim($_POST['opis'])) : '';
    $data_start = isset($_POST['data_start']) ? mysqli_real_escape_string($polaczenie, $_POST['data_start']) : '';
    $data_koniec = isset($_POST['data_koniec']) ? mysqli_real_escape_string($polaczenie, $_POST['data_koniec']) : '';

    if (!in_array($etap, $etapy_dozwolone, true)) {
        $komunikat = 'błąd: Wybrano niepoprawny etap.';
    } elseif ($tytul === '') {
        $komunikat = 'błąd: Wpisz tytuł etapu.';
    } elseif (!poprawna_data($data_start)) {
        $komunikat = 'błąd: Podaj poprawną datę startu.';
    } elseif ($data_koniec !== '' && !poprawna_data($data_koniec)) {
        $komunikat = 'błąd: Podaj poprawną datę końca albo zostaw puste.';
    } elseif ($data_koniec !== '' && $data_koniec < $data_start) {
        $komunikat = 'błąd: Data końca nie może być wcześniej niż data startu.';
    } else {
        $dk_sql = $data_koniec !== '' ? "'" . $data_koniec . "'" : 'NULL';

        if (isset($_POST['zapisz_edycje']) && isset($_POST['plan_id']) && is_numeric($_POST['plan_id'])) {
            $pid = (int) $_POST['plan_id'];
            $sql = "UPDATE planer_przyszlosci
                    SET etap = '$etap', tytul = '$tytul', opis = '$opis', data_start = '$data_start', data_koniec = $dk_sql
                    WHERE id = $pid AND uzytkownik_id = $uid";

            if (mysqli_query($polaczenie, $sql)) {
                header('Location: planer-przyszlosci.php?msg=sukces: Etap został zaktualizowany.');
                exit;
            } else {
                $komunikat = 'błąd: Nie udało się zapisać zmian.';
            }
        } else {
            $sql = "INSERT INTO planer_przyszlosci (uzytkownik_id, etap, tytul, opis, data_start, data_koniec)
                    VALUES ($uid, '$etap', '$tytul', '$opis', '$data_start', $dk_sql)";

            if (mysqli_query($polaczenie, $sql)) {
                header('Location: planer-przyszlosci.php?msg=sukces: Etap został dodany.');
                exit;
            } else {
                $komunikat = 'błąd: Nie udało się dodać etapu.';
            }
        }
    }
}

if (isset($_GET['zmien_status']) && is_numeric($_GET['zmien_status']) && isset($_GET['status'])) {
    $id = (int) $_GET['zmien_status'];
    $status = mysqli_real_escape_string($polaczenie, $_GET['status']);

    if (in_array($status, $statusy_dozwolone, true)) {
        mysqli_query($polaczenie, "UPDATE planer_przyszlosci SET status = '$status' WHERE id = $id AND uzytkownik_id = $uid");
        header('Location: planer-przyszlosci.php?msg=sukces: Status etapu został zmieniony.');
        exit;
    }
}

if (isset($_GET['usun']) && is_numeric($_GET['usun'])) {
    $id = (int) $_GET['usun'];
    mysqli_query($polaczenie, "DELETE FROM planer_przyszlosci WHERE id = $id AND uzytkownik_id = $uid");
    header('Location: planer-przyszlosci.php?msg=sukces: Etap został usunięty.');
    exit;
}

if (isset($_GET['msg'])) {
    $komunikat = $_GET['msg'];
}

$wynik_planera = mysqli_query(
    $polaczenie,
    "SELECT * FROM planer_przyszlosci WHERE uzytkownik_id = $uid ORDER BY data_start ASC, id ASC"
);

$stat_all = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM planer_przyszlosci WHERE uzytkownik_id = $uid"));
$stat_done = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM planer_przyszlosci WHERE uzytkownik_id = $uid AND status = 'zakonczone'"));
$stat_study = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM planer_przyszlosci WHERE uzytkownik_id = $uid AND etap = 'studia'"));
$stat_cert = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM planer_przyszlosci WHERE uzytkownik_id = $uid AND etap = 'certyfikat_szkolenie'"));

$stat_all = (int) $stat_all[0];
$stat_done = (int) $stat_done[0];
$stat_study = (int) $stat_study[0];
$stat_cert = (int) $stat_cert[0];

$kalendarz_wydarzenia = array();

$wynik_kalendarz_planer = mysqli_query(
    $polaczenie,
    "SELECT id, etap, tytul, opis, data_start, data_koniec, status
     FROM planer_przyszlosci
     WHERE uzytkownik_id = $uid"
);

while ($w = mysqli_fetch_assoc($wynik_kalendarz_planer)) {
    $kalendarz_wydarzenia[] = array(
        'source' => 'planer',
        'id' => (int) $w['id'],
        'title' => $w['tytul'],
        'start' => $w['data_start'],
        'end' => $w['data_koniec'] ? $w['data_koniec'] : $w['data_start'],
        'status' => $w['status']
    );
}

$wynik_kalendarz_zadania = mysqli_query(
    $polaczenie,
    "SELECT id, tytul, deadline, status
     FROM zadania
     WHERE uzytkownik_id = $uid"
);

while ($z = mysqli_fetch_assoc($wynik_kalendarz_zadania)) {
    $d = date('Y-m-d', strtotime($z['deadline']));
    $kalendarz_wydarzenia[] = array(
        'source' => 'zadanie',
        'id' => (int) $z['id'],
        'title' => 'Zadanie: ' . $z['tytul'],
        'start' => $d,
        'end' => $d,
        'status' => $z['status']
    );
}

$wynik_kalendarz_terminy = mysqli_query(
    $polaczenie,
    "SELECT id, przedmiot, data_termin, zaliczone
     FROM terminy_szkolne
     WHERE uzytkownik_id = $uid"
);

while ($t = mysqli_fetch_assoc($wynik_kalendarz_terminy)) {
    $d = date('Y-m-d', strtotime($t['data_termin']));
    $kalendarz_wydarzenia[] = array(
        'source' => 'termin',
        'id' => (int) $t['id'],
        'title' => 'Termin: ' . $t['przedmiot'],
        'start' => $d,
        'end' => $d,
        'status' => $t['zaliczone'] ? 'zakonczone' : 'plan'
    );
}

$kalendarz_json = json_encode($kalendarz_wydarzenia, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$alarmy_ryzyka = array();
$risk_score = 0;

$przeterminowane_etapy = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM planer_przyszlosci WHERE uzytkownik_id = $uid AND status <> 'zakonczone' AND data_koniec IS NOT NULL AND data_koniec < CURDATE()"));
$przeterminowane_etapy = (int) ($przeterminowane_etapy[0] ?? 0);
if ($przeterminowane_etapy > 0) {
    $risk_score += $przeterminowane_etapy * 12;
    $alarmy_ryzyka[] = array(
        'typ' => 'wysoki',
        'tytul' => 'Masz przeterminowane etapy planu: ' . $przeterminowane_etapy,
        'akcja' => 'Przejrzyj statusy i przesuń terminy lub zakończ ukończone etapy.'
    );
}

$przeterminowane_zadania = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM zadania WHERE uzytkownik_id = $uid AND deadline < NOW() AND status NOT IN ('zakończone','zakonczone')"));
$przeterminowane_zadania = (int) ($przeterminowane_zadania[0] ?? 0);
if ($przeterminowane_zadania > 0) {
    $risk_score += $przeterminowane_zadania * 8;
    $alarmy_ryzyka[] = array(
        'typ' => 'sredni',
        'tytul' => 'Zadania po terminie: ' . $przeterminowane_zadania,
        'akcja' => 'Skup się na zadaniach z najbliższym deadlinem i odblokuj zaległości.'
    );
}

$bez_postepu = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM planer_przyszlosci WHERE uzytkownik_id = $uid AND status = 'plan' AND data_start < DATE_SUB(CURDATE(), INTERVAL 14 DAY)"));
$bez_postepu = (int) ($bez_postepu[0] ?? 0);
if ($bez_postepu > 0) {
    $risk_score += $bez_postepu * 5;
    $alarmy_ryzyka[] = array(
        'typ' => 'sredni',
        'tytul' => 'Etapy bez postępu ponad 14 dni: ' . $bez_postepu,
        'akcja' => 'Przełącz choć 1 etap na "w toku" i przypisz do niego najbliższy krok.'
    );
}

$obciazenie_miesiecy = array();
foreach ($kalendarz_wydarzenia as $ev) {
    if (isset($ev['status']) && status_jest_zakonczony($ev['status'])) {
        continue;
    }
    $mkey = substr((string) $ev['start'], 0, 7);
    if ($mkey !== '') {
        if (!isset($obciazenie_miesiecy[$mkey])) {
            $obciazenie_miesiecy[$mkey] = 0;
        }
        $obciazenie_miesiecy[$mkey]++;
    }
}

$przeciazony_miesiac = null;
foreach ($obciazenie_miesiecy as $m => $ile) {
    if ($ile >= 10) {
        $przeciazony_miesiac = array('miesiac' => $m, 'ile' => $ile);
        break;
    }
}
if ($przeciazony_miesiac) {
    $risk_score += 16;
    $alarmy_ryzyka[] = array(
        'typ' => 'wysoki',
        'tytul' => 'Przeciążony miesiąc ' . $przeciazony_miesiac['miesiac'] . ': ' . $przeciazony_miesiac['ile'] . ' wydarzeń',
        'akcja' => 'Przesuń mniej pilne elementy i zostaw max 8-9 kluczowych aktywności.'
    );
}

if (count($alarmy_ryzyka) === 0) {
    $alarmy_ryzyka[] = array(
        'typ' => 'niskie',
        'tytul' => 'Brak krytycznych sygnałów ryzyka.',
        'akcja' => 'Utrzymuj rytm: aktualizuj statusy i pilnuj tygodniowych priorytetów.'
    );
}

$risk_score = min(100, max(0, $risk_score));

$misje_dnia = array();

$res_m_plan = mysqli_query($polaczenie, "SELECT id, tytul, status, data_start, data_koniec FROM planer_przyszlosci WHERE uzytkownik_id = $uid AND status <> 'zakonczone'");
while ($row = mysqli_fetch_assoc($res_m_plan)) {
    $data_docelowa = $row['data_koniec'] ? $row['data_koniec'] : $row['data_start'];
    $dni = liczba_dni_do($data_docelowa);
    $score = 100 - $dni;
    if ($dni < 0) {
        $score += 160;
    }
    if ($row['status'] === 'w_toku') {
        $score += 25;
    }
    $misje_dnia[] = array(
        'zrodlo' => 'Planer',
        'tytul' => $row['tytul'],
        'termin' => $data_docelowa,
        'powod' => $dni < 0 ? 'Po terminie' : ($dni === 0 ? 'Dziś termin' : 'Najbliższy krok planu'),
        'score' => $score,
        'link' => 'planer-przyszlosci.php?edytuj=' . (int) $row['id']
    );
}

$res_m_zad = mysqli_query($polaczenie, "SELECT id, tytul, status, deadline FROM zadania WHERE uzytkownik_id = $uid AND status NOT IN ('zakończone','zakonczone')");
while ($row = mysqli_fetch_assoc($res_m_zad)) {
    $termin = date('Y-m-d', strtotime($row['deadline']));
    $dni = liczba_dni_do($termin);
    $score = 120 - $dni;
    if ($dni < 0) {
        $score += 180;
    }
    $misje_dnia[] = array(
        'zrodlo' => 'Zadanie',
        'tytul' => $row['tytul'],
        'termin' => $termin,
        'powod' => $dni < 0 ? 'Zaległe zadanie' : ($dni === 0 ? 'Deadline dziś' : 'Nadchodzący deadline'),
        'score' => $score,
        'link' => 'dashboard.php'
    );
}

$res_m_ter = mysqli_query($polaczenie, "SELECT id, przedmiot, zaliczone, data_termin FROM terminy_szkolne WHERE uzytkownik_id = $uid AND zaliczone = 0");
while ($row = mysqli_fetch_assoc($res_m_ter)) {
    $termin = date('Y-m-d', strtotime($row['data_termin']));
    $dni = liczba_dni_do($termin);
    $score = 110 - $dni;
    if ($dni < 0) {
        $score += 120;
    }
    $misje_dnia[] = array(
        'zrodlo' => 'Termin',
        'tytul' => $row['przedmiot'],
        'termin' => $termin,
        'powod' => $dni < 0 ? 'Termin szkolny miniony' : ($dni === 0 ? 'Termin dziś' : 'Nadchodzący termin'),
        'score' => $score,
        'link' => 'dashboard.php'
    );
}

usort($misje_dnia, function ($a, $b) {
    if ($a['score'] === $b['score']) {
        return strcmp($a['termin'], $b['termin']);
    }
    return ($a['score'] > $b['score']) ? -1 : 1;
});

$misje_dnia = array_slice($misje_dnia, 0, 3);

$etykiety_etapow = array(
    'szkola_koniec' => 'Skonczenie szkoly',
    'studia' => 'Studia',
    'brak_studiow' => 'Brak studiow',
    'praca' => 'Praca',
    'certyfikat_szkolenie' => 'Certyfikat / szkolenie'
);
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>EduŚcieżka - Planer przyszlosci</title>
    <link rel="stylesheet" href="../style/planer-przyszlosci-style.css">
</head>

<body>

    <div class="pasek">
        <div class="logo">EduŚcieżka</div>
        <div>
            Witaj, <strong><?php echo htmlspecialchars($imie); ?></strong>
            <a href="wyloguj.php">Wyloguj się</a>
        </div>
    </div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="logi.php">Log Sukcesu</a>
        <a href="projekty.php">Projekty</a>
        <a href="planer-przyszlosci.php" class="aktywny">Planer przyszłości</a>
    </div>

    <div class="tresc">
        <?php
        if ($komunikat != "") {
            $czesci = explode(":", $komunikat, 2);
            $typ_kom = $czesci[0];
            $tresc_kom = isset($czesci[1]) ? $czesci[1] : $komunikat;
            if ($typ_kom == "sukces") {
                echo '<div class="kom-sukces">' . htmlspecialchars($tresc_kom) . '</div>';
            } else {
                echo '<div class="kom-blad">' . htmlspecialchars($tresc_kom) . '</div>';
            }
        }
        ?>

        <div class="statystyki">
            <div class="karta-stat">
                <div class="liczba"><?php echo $stat_all; ?></div>
                <div class="etykieta">Wszystkie etapy</div>
            </div>
            <div class="karta-stat zielona">
                <div class="liczba"><?php echo $stat_done; ?></div>
                <div class="etykieta">Etapy zakończone</div>
            </div>
            <div class="karta-stat granatowa">
                <div class="liczba"><?php echo $stat_study; ?></div>
                <div class="etykieta">Plany studiów</div>
            </div>
            <div class="karta-stat fioletowa">
                <div class="liczba"><?php echo $stat_cert; ?></div>
                <div class="etykieta">Certyfikaty / szkolenia</div>
            </div>
            <div class="karta-stat pomaranczowa">
                <div class="liczba"><?php echo $risk_score; ?>/100</div>
                <div class="etykieta">Poziom ryzyka planu</div>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Autoplan: generowanie realnego harmonogramu</h2>
            </div>
            <div class="formularz-dodaj">
                <form method="POST">
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Główny cel *</label>
                            <input type="text" name="auto_cel"
                                placeholder="np. Wejście do branży IT do końca przyszłego roku" required>
                        </div>
                        <div class="pole">
                            <label>Ścieżka *</label>
                            <select name="auto_sciezka" required>
                                <option value="studia">Studia -> praca</option>
                                <option value="praca">Praca od razu</option>
                                <option value="gap">Gap year + rozwój</option>
                                <option value="mieszana" selected>Mieszana (nauka + praca)</option>
                            </select>
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Start planu *</label>
                            <input type="date" name="auto_start" required>
                        </div>
                        <div class="pole">
                            <label>Tempo</label>
                            <select name="auto_tempo">
                                <option value="standard" selected>Standardowe</option>
                                <option value="intensywne">Intensywne</option>
                            </select>
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <label class="checkbox-line"><input type="checkbox" name="auto_certyfikat" value="1" checked>
                            Dodaj etap certyfikatu strategicznego</label>
                    </div>
                    <button type="submit" name="generuj_autoplan" class="btn-dodaj">Generuj autoplan</button>
                </form>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Wczesne ostrzeganie ryzyka</h2>
            </div>
            <div class="risk-wrap">
                <?php foreach ($alarmy_ryzyka as $alarm): ?>
                    <div class="risk-item risk-<?php echo $alarm['typ']; ?>">
                        <strong><?php echo htmlspecialchars($alarm['tytul']); ?></strong>
                        <div><?php echo htmlspecialchars($alarm['akcja']); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Co robić dziś: 3 najważniejsze kroki</h2>
            </div>
            <div class="mission-wrap">
                <?php if (count($misje_dnia) === 0): ?>
                    <div class="pusta-tabela">Brak pilnych kroków na dziś. Możesz zaplanować nowe etapy.</div>
                <?php else: ?>
                    <?php foreach ($misje_dnia as $misja): ?>
                        <div class="mission-item">
                            <div>
                                <span class="mission-source"><?php echo htmlspecialchars($misja['zrodlo']); ?></span>
                                <strong><?php echo htmlspecialchars($misja['tytul']); ?></strong>
                                <div class="mission-meta"><?php echo htmlspecialchars($misja['powod']); ?> | termin:
                                    <?php echo htmlspecialchars(date('d.m.Y', strtotime($misja['termin']))); ?>
                                </div>
                            </div>
                            <a href="<?php echo htmlspecialchars($misja['link']); ?>" class="btn-mission">Otwórz</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Symulator ścieżek życia</h2>
            </div>
            <div class="formularz-dodaj">
                <form method="POST">
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Horyzont (lata)</label>
                            <input type="number" name="sim_horyzont_lat" min="2" max="10" value="5">
                        </div>
                        <div class="pole">
                            <label>Lata studiów</label>
                            <input type="number" name="sim_lata_studiow" min="1" max="6" value="3">
                        </div>
                        <div class="pole">
                            <label>Koszt studiów / miesiąc (PLN)</label>
                            <input type="number" name="sim_koszt_studia_msc" min="0" step="50" value="1200">
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Kursy / rok (PLN)</label>
                            <input type="number" name="sim_kursy_rocznie" min="0" step="100" value="3000">
                        </div>
                        <div class="pole">
                            <label>Start pensji po studiach (PLN)</label>
                            <input type="number" name="sim_pensja_studia" min="0" step="100" value="6500">
                        </div>
                        <div class="pole">
                            <label>Start pensji praca od razu (PLN)</label>
                            <input type="number" name="sim_pensja_praca" min="0" step="100" value="4500">
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Wzrost pensji po studiach (%/rok)</label>
                            <input type="number" name="sim_wzrost_studia" min="0" max="100" step="0.5" value="9">
                        </div>
                        <div class="pole">
                            <label>Wzrost pensji praca od razu (%/rok)</label>
                            <input type="number" name="sim_wzrost_praca" min="0" max="100" step="0.5" value="5">
                        </div>
                    </div>
                    <button type="submit" name="licz_symulacje" class="btn-dodaj">Porównaj scenariusze</button>
                </form>

                <?php if ($symulacja): ?>
                    <div class="sim-wynik">
                        <div class="sim-reko">Rekomendacja na <?php echo (int) $symulacja['horyzont']; ?> lat:
                            <strong><?php echo htmlspecialchars($symulacja['rekomendacja']); ?></strong>
                        </div>
                        <table>
                            <tr>
                                <th>Ścieżka</th>
                                <th>Bilans finansowy (PLN)</th>
                                <th>Ryzyko</th>
                                <th>Czas do uzyskania regularnych dochodów</th>
                            </tr>
                            <?php foreach ($symulacja['scenariusze'] as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['nazwa']); ?></td>
                                    <td><?php echo number_format((float) $s['bilans'], 0, ',', ' '); ?></td>
                                    <td><?php echo (int) $s['ryzyko']; ?>/100</td>
                                    <td><?php echo (int) $s['czas_do_dochodu']; ?> lat</td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Automatyczne portfolio i CV</h2>
            </div>
            <div class="cv-actions">
                <a class="btn-dodaj" href="portfolio-cv.php" target="_blank" rel="noopener">Podgląd portfolio/CV</a>
                <a class="btn-drugi" href="portfolio-cv.php?print=1" target="_blank" rel="noopener">Pobierz PDF
                    (drukuj)</a>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2><?php echo $plan_do_edycji ? 'Edytuj etap przyszłości' : 'Dodaj etap przyszłości'; ?></h2>
            </div>

            <div class="formularz-dodaj widoczny">
                <form method="POST">
                    <?php if ($plan_do_edycji): ?>
                        <input type="hidden" name="plan_id" value="<?php echo (int) $plan_do_edycji['id']; ?>">
                    <?php endif; ?>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Etap *</label>
                            <select name="etap" required>
                                <option value="szkola_koniec" <?php echo ($plan_do_edycji && $plan_do_edycji['etap'] === 'szkola_koniec') ? 'selected' : ''; ?>>Skończenie szkoły
                                </option>
                                <option value="studia" <?php echo ($plan_do_edycji && $plan_do_edycji['etap'] === 'studia') ? 'selected' : ''; ?>>Studia</option>
                                <option value="brak_studiow" <?php echo ($plan_do_edycji && $plan_do_edycji['etap'] === 'brak_studiow') ? 'selected' : ''; ?>>Brak studiów
                                </option>
                                <option value="praca" <?php echo ($plan_do_edycji && $plan_do_edycji['etap'] === 'praca') ? 'selected' : ''; ?>>Praca</option>
                                <option value="certyfikat_szkolenie" <?php echo ($plan_do_edycji && $plan_do_edycji['etap'] === 'certyfikat_szkolenie') ? 'selected' : ''; ?>>Certyfikat /
                                    szkolenie</option>
                            </select>
                        </div>
                        <div class="pole">
                            <label>Tytuł etapu *</label>
                            <input type="text" name="tytul" placeholder="np. Matura i koniec liceum"
                                value="<?php echo $plan_do_edycji ? htmlspecialchars($plan_do_edycji['tytul']) : ''; ?>"
                                required>
                        </div>
                    </div>

                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Data startu *</label>
                            <input type="date" name="data_start"
                                value="<?php echo $plan_do_edycji ? htmlspecialchars($plan_do_edycji['data_start']) : ''; ?>"
                                required>
                        </div>
                        <div class="pole">
                            <label>Data końca (opcjonalnie)</label>
                            <input type="date" name="data_koniec"
                                value="<?php echo ($plan_do_edycji && $plan_do_edycji['data_koniec']) ? htmlspecialchars($plan_do_edycji['data_koniec']) : ''; ?>">
                        </div>
                    </div>

                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Opis</label>
                            <textarea name="opis" rows="2"
                                placeholder="Co chcesz osiagnac na tym etapie?"><?php echo $plan_do_edycji ? htmlspecialchars($plan_do_edycji['opis']) : ''; ?></textarea>
                        </div>
                    </div>

                    <?php if ($plan_do_edycji): ?>
                        <button type="submit" name="zapisz_edycje" class="btn-dodaj">Zapisz zmiany</button>
                        <a href="planer-przyszlosci.php" class="btn-drugi">Anuluj edycje</a>
                    <?php else: ?>
                        <button type="submit" name="dodaj_plan" class="btn-dodaj">Dodaj etap</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Twój plan przyszłości</h2>
            </div>
            <div style="padding:0">
                <?php if (!$wynik_planera || mysqli_num_rows($wynik_planera) == 0): ?>
                    <div class="pusta-tabela">Brak etapów. Dodaj pierwszy krok i rozpisz swoją ścieżkę.</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Etap</th>
                            <th>Tytuł</th>
                            <th>Termin</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                        <?php while ($plan = mysqli_fetch_assoc($wynik_planera)): ?>
                            <?php
                            $statusy = array('plan' => 'Plan', 'w_toku' => 'W toku', 'zakonczone' => 'Zakonczone');
                            ?>
                            <tr>
                                <td>
                                    <span class="etap-badge etap-<?php echo $plan['etap']; ?>">
                                        <?php echo htmlspecialchars($etykiety_etapow[$plan['etap']]); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($plan['tytul']); ?></strong>
                                    <?php if ($plan['opis']): ?>
                                        <br><small class="opis"><?php echo htmlspecialchars($plan['opis']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="termin-komorka">
                                    <?php echo date('d.m.Y', strtotime($plan['data_start'])); ?>
                                    <?php if ($plan['data_koniec']): ?>
                                        <br>do <?php echo date('d.m.Y', strtotime($plan['data_koniec'])); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status status-<?php echo $plan['status']; ?>">
                                        <?php echo $statusy[$plan['status']]; ?>
                                    </span>
                                </td>
                                <td>
                                    <a class="akcja akcja-edytuj"
                                        href="planer-przyszlosci.php?edytuj=<?php echo $plan['id']; ?>">Edytuj</a>
                                    <?php if ($plan['status'] != 'w_toku'): ?>
                                        <a class="akcja akcja-wtoku"
                                            href="planer-przyszlosci.php?zmien_status=<?php echo $plan['id']; ?>&status=w_toku">W
                                            toku</a>
                                    <?php endif; ?>
                                    <?php if ($plan['status'] != 'zakonczone'): ?>
                                        <a class="akcja akcja-zakoncz"
                                            href="planer-przyszlosci.php?zmien_status=<?php echo $plan['id']; ?>&status=zakonczone">Zakończ</a>
                                    <?php endif; ?>
                                    <?php if ($plan['status'] != 'plan'): ?>
                                        <a class="akcja akcja-plan"
                                            href="planer-przyszlosci.php?zmien_status=<?php echo $plan['id']; ?>&status=plan">Plan</a>
                                    <?php endif; ?>
                                    <a class="akcja akcja-usun" href="planer-przyszlosci.php?usun=<?php echo $plan['id']; ?>"
                                        onclick="return confirm('Usunac ten etap?')">Usuń</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek kalendarz-top">
                <h2>Kalendarz planu, zadań i terminów</h2>
                <div class="kalendarz-sterowanie">
                    <button type="button" id="prevMonth" class="btn-nav">&lt;</button>
                    <strong id="monthLabel"></strong>
                    <button type="button" id="nextMonth" class="btn-nav">&gt;</button>
                </div>
            </div>

            <div class="filtry-kalendarza">
                <label><input type="checkbox" id="filtr-planer" checked> Planer przyszłości</label>
                <label><input type="checkbox" id="filtr-zadanie" checked> Zadania</label>
                <label><input type="checkbox" id="filtr-termin" checked> Terminy szkolne</label>
                <label><input type="checkbox" id="filtr-zakonczone" checked> Pokaż zakończone</label>
            </div>

            <div class="legend">
                <span class="legend-item legend-planer">Planer przyszłości</span>
                <span class="legend-item legend-zadanie">Zadanie</span>
                <span class="legend-item legend-termin">Termin szkolny</span>
            </div>

            <div id="calendarGrid" class="calendar-grid"></div>
        </div>
    </div>

    <script>
        const wydarzenia = <?php echo $kalendarz_json ? $kalendarz_json : '[]'; ?>;
        const nazwyMiesiecy = [
            'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
            'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
        ];

        let widok = new Date();

        function pad(v) {
            return String(v).padStart(2, '0');
        }

        function dataKlucz(y, m, d) {
            return y + '-' + pad(m + 1) + '-' + pad(d);
        }

        function wydarzeniaDnia(klucz) {
            const fPlaner = document.getElementById('filtr-planer').checked;
            const fZadanie = document.getElementById('filtr-zadanie').checked;
            const fTermin = document.getElementById('filtr-termin').checked;
            const fZak = document.getElementById('filtr-zakonczone').checked;

            return wydarzenia.filter(function (ev) {
                if (ev.source === 'planer' && !fPlaner) return false;
                if (ev.source === 'zadanie' && !fZadanie) return false;
                if (ev.source === 'termin' && !fTermin) return false;
                if (!fZak && ev.status === 'zakonczone') return false;
                const start = ev.start;
                const end = ev.end || ev.start;
                return klucz >= start && klucz <= end;
            });
        }

        function renderKalendarz() {
            const rok = widok.getFullYear();
            const miesiac = widok.getMonth();

            document.getElementById('monthLabel').textContent = nazwyMiesiecy[miesiac] + ' ' + rok;

            const grid = document.getElementById('calendarGrid');
            grid.innerHTML = '';

            ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Niedż'].forEach(function (dzien) {
                const nag = document.createElement('div');
                nag.className = 'dow';
                nag.textContent = dzien;
                grid.appendChild(nag);
            });

            const pierwszy = new Date(rok, miesiac, 1);
            const przesuniecie = (pierwszy.getDay() + 6) % 7;
            const dniWmiesiacu = new Date(rok, miesiac + 1, 0).getDate();

            for (let i = 0; i < przesuniecie; i++) {
                const pusty = document.createElement('div');
                pusty.className = 'day empty';
                grid.appendChild(pusty);
            }

            const dzisiaj = new Date();
            const dzKlucz = dataKlucz(dzisiaj.getFullYear(), dzisiaj.getMonth(), dzisiaj.getDate());

            for (let d = 1; d <= dniWmiesiacu; d++) {
                const klucz = dataKlucz(rok, miesiac, d);
                const komorka = document.createElement('div');
                komorka.className = 'day';
                if (klucz === dzKlucz) {
                    komorka.className += ' today';
                }

                const numer = document.createElement('div');
                numer.className = 'day-number';
                numer.textContent = d;
                komorka.appendChild(numer);

                const lista = document.createElement('div');
                lista.className = 'events';

                const evDnia = wydarzeniaDnia(klucz);
                evDnia.slice(0, 3).forEach(function (ev) {
                    const e = document.createElement('div');
                    e.className = 'event event-' + ev.source;
                    e.textContent = ev.title;
                    lista.appendChild(e);
                });

                if (evDnia.length > 3) {
                    const wiecej = document.createElement('div');
                    wiecej.className = 'more-events';
                    wiecej.textContent = '+' + (evDnia.length - 3) + ' wiecej';
                    lista.appendChild(wiecej);
                }

                komorka.appendChild(lista);
                grid.appendChild(komorka);
            }
        }

        document.getElementById('prevMonth').addEventListener('click', function () {
            widok = new Date(widok.getFullYear(), widok.getMonth() - 1, 1);
            renderKalendarz();
        });

        document.getElementById('nextMonth').addEventListener('click', function () {
            widok = new Date(widok.getFullYear(), widok.getMonth() + 1, 1);
            renderKalendarz();
        });

        ['filtr-planer', 'filtr-zadanie', 'filtr-termin', 'filtr-zakonczone'].forEach(function (id) {
            document.getElementById(id).addEventListener('change', renderKalendarz);
        });

        renderKalendarz();
    </script>

</body>

</html>