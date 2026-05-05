<?php
require_once "polaczenie.php";

if (!isset($_SESSION['uzytkownik_id'])) {
    header("Location: logowanie.php");
    exit;
}

$uid = (int) $_SESSION['uzytkownik_id'];
$imie = $_SESSION['imie'];

$komunikat = "";

// Dodaj zadanie
if (isset($_POST['dodaj_zadanie'])) {
    $tytul = mysqli_real_escape_string($polaczenie, $_POST['tytul']);
    $opis = mysqli_real_escape_string($polaczenie, $_POST['opis']);
    $deadline = mysqli_real_escape_string($polaczenie, $_POST['deadline']);
    $priorytet = mysqli_real_escape_string($polaczenie, $_POST['priorytet']);
    $kategoria_id = (int) $_POST['kategoria_id'];

    if ($tytul != "" && $deadline != "") {
        $sql = "INSERT INTO zadania (uzytkownik_id, kategoria_id, tytul, opis, deadline, priorytet)
                VALUES ($uid, $kategoria_id, '$tytul', '$opis', '$deadline', '$priorytet')";
        mysqli_query($polaczenie, $sql);
        $komunikat = "sukces: Zadanie zostało dodane!";
    } else {
        $komunikat = "błąd: Wypełnij tytuł i datę!";
    }
}

// Dodaj termin szkolny
if (isset($_POST['dodaj_termin'])) {
    $przedmiot = mysqli_real_escape_string($polaczenie, $_POST['przedmiot']);
    $typ = mysqli_real_escape_string($polaczenie, $_POST['typ']);
    $opis = mysqli_real_escape_string($polaczenie, $_POST['opis']);
    $data_termin = mysqli_real_escape_string($polaczenie, $_POST['data_termin']);
    $nauczyciel = mysqli_real_escape_string($polaczenie, $_POST['nauczyciel']);

    if ($przedmiot != "" && $data_termin != "") {
        $sql = "INSERT INTO terminy_szkolne (uzytkownik_id, przedmiot, typ, opis, data_termin, nauczyciel)
                VALUES ($uid, '$przedmiot', '$typ', '$opis', '$data_termin', '$nauczyciel')";
        mysqli_query($polaczenie, $sql);
        $komunikat = "sukces: Termin został dodany!";
    } else {
        $komunikat = "błąd: Wypełnij przedmiot i datę!";
    }
}

// Ukoncz zadanie
if (isset($_GET['ukoncz']) && is_numeric($_GET['ukoncz'])) {
    $zid = (int) $_GET['ukoncz'];
    $wynik = mysqli_query($polaczenie, "SELECT id, tytul FROM zadania WHERE id = $zid AND uzytkownik_id = $uid");
    if (mysqli_num_rows($wynik) == 1) {
        $zad = mysqli_fetch_assoc($wynik);
        mysqli_query($polaczenie, "UPDATE zadania SET status = 'zakończone', ukonczone_o = NOW() WHERE id = $zid");
        $tytul_log = mysqli_real_escape_string($polaczenie, $zad['tytul']);
        mysqli_query($polaczenie, "INSERT INTO logi_sukcesow (uzytkownik_id, zadanie_id, tytul, typ, punkty_xp)
                                   VALUES ($uid, $zid, '$tytul_log', 'zadanie', 10)");
        header("Location: dashboard.php?msg=sukces: Zadanie ukończone! Log sukcesu zapisany.");
        exit;
    }
}

// Usun zadanie
if (isset($_GET['usun']) && is_numeric($_GET['usun'])) {
    $zid = (int) $_GET['usun'];
    mysqli_query($polaczenie, "DELETE FROM zadania WHERE id = $zid AND uzytkownik_id = $uid");
    header("Location: dashboard.php?msg=sukces: Zadanie zostało usunięte.");
    exit;
}

// Zalicz termin szkolny
if (isset($_GET['zalicz_termin']) && is_numeric($_GET['zalicz_termin'])) {
    $tid = (int) $_GET['zalicz_termin'];
    $wynik = mysqli_query($polaczenie, "SELECT id, przedmiot FROM terminy_szkolne WHERE id = $tid AND uzytkownik_id = $uid");
    if (mysqli_num_rows($wynik) == 1) {
        $ter = mysqli_fetch_assoc($wynik);
        mysqli_query($polaczenie, "UPDATE terminy_szkolne SET zaliczone = 1 WHERE id = $tid");
        $tytul_log = mysqli_real_escape_string($polaczenie, $ter['przedmiot']);
        mysqli_query($polaczenie, "INSERT INTO logi_sukcesow (uzytkownik_id, termin_id, tytul, typ, punkty_xp)
                                   VALUES ($uid, $tid, 'Zaliczono: $tytul_log', 'termin_szkolny', 15)");
        header("Location: dashboard.php?msg=sukces: Termin zaliczony!");
        exit;
    }
}

if (isset($_GET['msg']))
    $komunikat = $_GET['msg'];

// Pobierz zadania
$wynik_zadan = mysqli_query(
    $polaczenie,
    "SELECT z.*, k.nazwa AS kategoria_nazwa, k.kolor_hex
     FROM zadania z
     LEFT JOIN kategorie k ON z.kategoria_id = k.id
     WHERE z.uzytkownik_id = $uid
     ORDER BY z.deadline ASC"
);

// Pobierz terminy szkolne
$wynik_terminow = mysqli_query(
    $polaczenie,
    "SELECT * FROM terminy_szkolne
     WHERE uzytkownik_id = $uid
     ORDER BY data_termin ASC"
);

// Pobierz ostatnie logi sukcesu
$wynik_logow = mysqli_query(
    $polaczenie,
    "SELECT * FROM logi_sukcesow
     WHERE uzytkownik_id = $uid
     ORDER BY data_osiagniecia DESC
     LIMIT 5"
);

// Pobierz kategorie do formularza
$wynik_kategorii = mysqli_query($polaczenie, "SELECT * FROM kategorie ORDER BY nazwa");

// Statystyki
$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM zadania WHERE uzytkownik_id = $uid AND status = 'do_zrobienia'"));
$stat_todo = $r[0];

$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM zadania WHERE uzytkownik_id = $uid AND status = 'w_toku'"));
$stat_w_toku = $r[0];

$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM zadania WHERE uzytkownik_id = $uid AND status = 'zakończone'"));
$stat_ukonczone = $r[0];

$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM logi_sukcesow WHERE uzytkownik_id = $uid"));
$stat_logi = $r[0];
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>EduŚcieżka - Dashboard</title>
    <link rel="stylesheet" href="../style/dashboard-style.css">
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon">
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
        <a href="dashboard.php" class="aktywny">Dashboard</a>
        <a href="logi.php">Log Sukcesu</a>
        <a href="projekty.php">Projekty</a>
        <a href="planer-przyszlosci.php">Planer przyszłości</a>
    </div>

    <div class="tresc">

        <?php
        if ($komunikat != "") {
            $czesci = explode(":", $komunikat, 2);
            $typ_kom = $czesci[0];
            $tresc_kom = isset($czesci[1]) ? $czesci[1] : $komunikat;
            if ($typ_kom == "sukces")
                echo '<div class="kom-sukces">' . htmlspecialchars($tresc_kom) . '</div>';
            else
                echo '<div class="kom-blad">' . htmlspecialchars($tresc_kom) . '</div>';
        }
        ?>

        <!-- STATYSTYKI -->
        <div class="statystyki">
            <div class="karta-stat">
                <div class="liczba"><?php echo $stat_todo; ?></div>
                <div class="etykieta">Do zrobienia</div>
            </div>
            <div class="karta-stat pomaranczowa">
                <div class="liczba"><?php echo $stat_w_toku; ?></div>
                <div class="etykieta">W toku</div>
            </div>
            <div class="karta-stat zielona">
                <div class="liczba"><?php echo $stat_ukonczone; ?></div>
                <div class="etykieta">Ukończone</div>
            </div>
            <div class="karta-stat fioletowa">
                <div class="liczba"><?php echo $stat_logi; ?></div>
                <div class="etykieta">Logi sukcesu</div>
            </div>
        </div>

        <!-- ZADANIA -->
        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Moje Zadania</h2>
                <button class="btn-pokaz" onclick="przelaczForm('form-zadanie')">+ Dodaj zadanie</button>
            </div>

            <div class="formularz-dodaj" id="form-zadanie">
                <h3>Nowe zadanie</h3>
                <form method="POST">
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Tytuł <span class="req">*</span></label>
                            <input type="text" name="tytul" placeholder="np. Sprawdzian z matematyki" required>
                        </div>
                        <div class="pole">
                            <label>Deadline <span class="req">*</span></label>
                            <input type="datetime-local" name="deadline" required>
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Kategoria</label>
                            <select name="kategoria_id">
                                <?php while ($kat = mysqli_fetch_assoc($wynik_kategorii)): ?>
                                    <option value="<?php echo $kat['id']; ?>"><?php echo htmlspecialchars($kat['nazwa']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="pole">
                            <label>Priorytet</label>
                            <select name="priorytet">
                                <option value="niski">Niski</option>
                                <option value="średni" selected>Średni</option>
                                <option value="wysoki">Wysoki</option>
                                <option value="krytyczny">Krytyczny</option>
                            </select>
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Opis (opcjonalny)</label>
                            <textarea name="opis" rows="2" placeholder="Dodatkowe informacje..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="dodaj_zadanie" class="btn-dodaj">Dodaj zadanie</button>
                    <button type="button" class="btn-anuluj" onclick="przelaczForm('form-zadanie')">Anuluj</button>
                </form>
            </div>

            <div style="padding:0">
                <?php if (mysqli_num_rows($wynik_zadan) == 0): ?>
                    <div class="pusta-tabela">Brak zadań. Dodaj swoje pierwsze zadanie!</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Tytuł</th>
                            <th>Kategoria</th>
                            <th>Priorytet</th>
                            <th>Status</th>
                            <th>Deadline</th>
                            <th>Akcje</th>
                        </tr>
                        <?php while ($zad = mysqli_fetch_assoc($wynik_zadan)): ?>
                            <?php
                            $klasy_priorytetu = ['niski'=>'niski','średni'=>'sredni','wysoki'=>'wysoki','krytyczny'=>'krytyczny'];
                            $klasa = isset($klasy_priorytetu[$zad['priorytet']]) ? $klasy_priorytetu[$zad['priorytet']] : 'niski';

                            $klasy_statusow = [
                            'do_zrobienia' => 'do_zrobienia',
                            'w_toku'       => 'w_toku',
                            'zakończone'   => 'zakonczone',
                            ];
                            $klasa_statusu = isset($klasy_statusow[$zad['status']]) ? $klasy_statusow[$zad['status']] : $zad['status'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($zad['tytul']); ?></strong>
                                    <?php if ($zad['opis']): ?>
                                        <br><small
                                            style="color:#9ca3af"><?php echo htmlspecialchars(substr($zad['opis'], 0, 60)); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($zad['kategoria_nazwa']): ?>
                                        <span class="kropka-kategorii" style="background:<?php echo $zad['kolor_hex']; ?>"></span>
                                        <?php echo htmlspecialchars($zad['kategoria_nazwa']); ?>
                                    <?php else:
                                        echo '—'; endif; ?>
                                </td>
                                <td>
                                    <span class="priorytet priorytet-<?php echo $klasa; ?>">
                                        <?php echo ucfirst($zad['priorytet']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status status-<?php echo $klasa_statusu; ?>">
                                        <?php
                                        $statusy = array('do_zrobienia' => 'Do zrobienia', 'w_toku' => 'W toku', 'zakończone' => 'Ukonczone');
                                        echo isset($statusy[$zad['status']]) ? $statusy[$zad['status']] : $zad['status'];
                                        ?>
                                    </span>
                                </td>
                                <td style="font-size:12px;color:#6b7280">
                                    <?php
                                    $ts = strtotime($zad['deadline']);
                                    echo date('d.m.Y H:i', $ts);
                                    $roznica = $ts - time();
                                    if ($zad['status'] != 'zakończone') {
                                        if ($roznica < 0)
                                            echo '<br><span style="color:#b91c1c;font-weight:bold">Po terminie!</span>';
                                        elseif ($roznica < 86400)
                                            echo '<br><span style="color:#f59e0b;font-weight:bold">Dzisiaj!</span>';
                                        elseif ($roznica < 86400 * 3)
                                            echo '<br><span style="color:#f59e0b">Za ' . round($roznica / 86400) . ' dni</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($zad['status'] != 'zakończone'): ?>
                                        <a href="dashboard.php?ukoncz=<?php echo $zad['id']; ?>" class="akcja akcja-ukoncz"
                                            onclick="return confirm('Oznaczyć jako ukończone?')">Ukończ</a>
                                            <a href="dashboard.php?usun=<?php echo $zad['id']; ?>" class="akcja akcja-usun"
                                            onclick="return confirm('Czy na pewno usunąć to zadanie?')">Usuń</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- TERMINY SZKOLNE -->
        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Terminy Szkolne</h2>
                <button class="btn-pokaz" onclick="przelaczForm('form-termin')">+ Dodaj termin</button>
            </div>

            <div class="formularz-dodaj" id="form-termin">
                <h3>Nowy termin szkolny</h3>
                <form method="POST">
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Przedmiot <span class="req">*</span></label>
                            <input type="text" name="przedmiot" placeholder="np. Matematyka" required>
                        </div>
                        <div class="pole">
                            <label>Typ</label>
                            <select name="typ">
                                <option value="sprawdzian">Sprawdzian</option>
                                <option value="kartkówka">Kartkówka</option>
                                <option value="projekt">Projekt</option>
                                <option value="egzamin">Egzamin</option>
                                <option value="prezentacja">Prezentacja</option>
                                <option value="inne">Inne</option>
                            </select>
                        </div>
                        <div class="pole">
                            <label>Data i godzina <span class="req">*</span></label>
                            <input type="datetime-local" name="data_termin" required>
                        </div>
                    </div>
                    <div class="rzad-pol">
                        <div class="pole">
                            <label>Nauczyciel</label>
                            <input type="text" name="nauczyciel" placeholder="np. mgr Anna Nowak">
                        </div>
                        <div class="pole">
                            <label>Opis / temat</label>
                            <input type="text" name="opis" placeholder="np. Rozdzial 5 - calki">
                        </div>
                    </div>
                    <button type="submit" name="dodaj_termin" class="btn-dodaj">Dodaj termin</button>
                    <button type="button" class="btn-anuluj" onclick="przelaczForm('form-termin')">Anuluj</button>
                </form>
            </div>

            <div style="padding:0">
                <?php if (mysqli_num_rows($wynik_terminow) == 0): ?>
                    <div class="pusta-tabela">Brak terminów szkolnych.</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Przedmiot</th>
                            <th>Typ</th>
                            <th>Nauczyciel</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Akcje</th>
                        </tr>
                        <?php while ($ter = mysqli_fetch_assoc($wynik_terminow)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($ter['przedmiot']); ?></strong>
                                    <?php if ($ter['opis']): ?>
                                        <br><small style="color:#9ca3af"><?php echo htmlspecialchars($ter['opis']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><span class="typ-terminu"><?php echo htmlspecialchars($ter['typ']); ?></span></td>
                                <td style="font-size:12px">
                                    <?php echo htmlspecialchars($ter['nauczyciel'] ? $ter['nauczyciel'] : '—'); ?></td>
                                <td style="font-size:12px;color:#6b7280">
                                    <?php echo date('d.m.Y H:i', strtotime($ter['data_termin'])); ?></td>
                                <td>
                                    <?php if ($ter['zaliczone']): ?>
                                        <span style="color:#065f46;font-size:12px;font-weight:bold">&#10003; Zaliczone</span>
                                    <?php else: ?>
                                        <span style="color:#9ca3af;font-size:12px">Oczekujący</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$ter['zaliczone']): ?>
                                        <a href="dashboard.php?zalicz_termin=<?php echo $ter['id']; ?>" class="akcja akcja-zalicz"
                                            onclick="return confirm('Oznaczyc jako zaliczony?')">Zalicz</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- OSTATNIE LOGI SUKCESU -->
        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Ostatnie sukcesy</h2>
                <a href="logi.php" style="font-size:13px;color:#2563EB;text-decoration:none">Zobacz wszystkie &rarr;</a>
            </div>
            <div style="padding:0">
                <?php if (mysqli_num_rows($wynik_logow) == 0): ?>
                    <div class="pusta-tabela">Brak logów sukcesu. Ukończ zadanie, aby je dodać!</div>
                <?php else: ?>
                    <table>
                        <tr>
                            <th>Tytuł</th>
                            <th>Typ</th>
                            <th>Punkty XP</th>
                            <th>Data</th>
                        </tr>
                        <?php while ($log = mysqli_fetch_assoc($wynik_logow)): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($log['tytul']); ?></strong></td>
                                <td><span class="typ-badge"><?php echo $log['typ']; ?></span></td>
                                <td class="xp">+<?php echo $log['punkty_xp']; ?> XP</td>
                                <td style="font-size:12px;color:#6b7280">
                                    <?php echo date('d.m.Y H:i', strtotime($log['data_osiagniecia'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script>
        function przelaczForm(id) {
            var el = document.getElementById(id);
            if (el.className.indexOf('widoczny') === -1) {
                el.className = 'formularz-dodaj widoczny';
            } else {
                el.className = 'formularz-dodaj';
            }
        }
    </script>

</body>

</html>