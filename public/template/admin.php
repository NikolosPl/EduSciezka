<?php
require_once "polaczenie.php";

if (!isset($_SESSION['admin'])) {
    header("Location: admin_login.php");
    exit;
}

$komunikat = "";

// Usun uzytkownika
if (isset($_GET['usun_uzyt']) && is_numeric($_GET['usun_uzyt'])) {
    $id = (int)$_GET['usun_uzyt'];
    mysqli_query($polaczenie, "DELETE FROM uzytkownicy WHERE id = $id");
    header("Location: admin.php?msg=sukces:Uzytkownik usuniety.");
    exit;
}

// Usun zadanie
if (isset($_GET['usun_zad']) && is_numeric($_GET['usun_zad'])) {
    $id = (int)$_GET['usun_zad'];
    mysqli_query($polaczenie, "DELETE FROM zadania WHERE id = $id");
    header("Location: admin.php?msg=sukces:Zadanie usuniete.");
    exit;
}

if (isset($_GET['msg'])) $komunikat = $_GET['msg'];

$wynik_uzyt = mysqli_query($polaczenie,
    "SELECT u.*, COUNT(z.id) AS liczba_zadan
     FROM uzytkownicy u
     LEFT JOIN zadania z ON u.id = z.uzytkownik_id
     GROUP BY u.id
     ORDER BY u.data_rejestracji DESC"
);

$wynik_zadania = mysqli_query($polaczenie,
    "SELECT z.*, u.imie, u.nazwisko, k.nazwa AS kat_nazwa
     FROM zadania z
     JOIN uzytkownicy u ON z.uzytkownik_id = u.id
     LEFT JOIN kategorie k ON z.kategoria_id = k.id
     ORDER BY z.deadline ASC
     LIMIT 30"
);

$wynik_logi = mysqli_query($polaczenie,
    "SELECT l.*, u.imie, u.nazwisko
     FROM logi_sukcesow l
     JOIN uzytkownicy u ON l.uzytkownik_id = u.id
     ORDER BY l.data_osiagniecia DESC
     LIMIT 20"
);

$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM uzytkownicy"));
$stat_uzyt = $r[0];
$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM zadania"));
$stat_zad = $r[0];
$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM zadania WHERE status = 'zakonczone'"));
$stat_done = $r[0];
$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT COUNT(*) FROM logi_sukcesow"));
$stat_logi = $r[0];
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>EduSciezka - Panel Admina</title>
<link rel="stylesheet" href="../style/admin-style.css">
</head>
<body>

<div class="pasek">
    <div class="logo">EduSciezka <span class="badge-admin">ADMIN</span></div>
    <div><a href="admin_login.php?wyloguj=1">Wyloguj sie</a></div>
</div>

<div class="menu">
    <a href="admin.php">Przeglad</a>
    <a href="admin.php#uzytkownicy">Uzytkownicy</a>
    <a href="admin.php#zadania">Zadania</a>
    <a href="admin.php#logi">Logi sukcesu</a>
</div>

<div class="tresc">

    <?php
    if ($komunikat != "") {
        $czesci = explode(":", $komunikat, 2);
        $typ = $czesci[0];
        $tresc = isset($czesci[1]) ? $czesci[1] : $komunikat;
        if ($typ == "sukces") echo '<div class="kom-sukces">' . htmlspecialchars($tresc) . '</div>';
        else echo '<div class="kom-blad">' . htmlspecialchars($tresc) . '</div>';
    }
    ?>

    <div class="statystyki">
        <div class="karta-stat">
            <div class="liczba"><?php echo $stat_uzyt; ?></div>
            <div class="etykieta">Uzytkownikow</div>
        </div>
        <div class="karta-stat n2">
            <div class="liczba"><?php echo $stat_zad; ?></div>
            <div class="etykieta">Zadan lacznie</div>
        </div>
        <div class="karta-stat n3">
            <div class="liczba"><?php echo $stat_done; ?></div>
            <div class="etykieta">Ukonczonych</div>
        </div>
        <div class="karta-stat n4">
            <div class="liczba"><?php echo $stat_logi; ?></div>
            <div class="etykieta">Logow sukcesu</div>
        </div>
    </div>

    <!-- UZYTKOWNICY -->
    <div class="sekcja" id="uzytkownicy">
        <div class="sekcja-naglowek"><h2>Uzytkownicy systemu</h2></div>
        <?php if (mysqli_num_rows($wynik_uzyt) == 0): ?>
            <div class="pusta">Brak uzytkownikow.</div>
        <?php else: ?>
        <table>
            <tr>
                <th>Imie i nazwisko</th>
                <th>Email</th>
                <th>Liczba zadan</th>
                <th>Data rejestracji</th>
                <th>Ostatnie logowanie</th>
                <th>Akcje</th>
            </tr>
            <?php while ($u = mysqli_fetch_assoc($wynik_uzyt)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($u['imie'] . ' ' . $u['nazwisko']); ?></strong></td>
                <td style="color:#6b7280;font-size:12px"><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo $u['liczba_zadan']; ?></td>
                <td style="font-size:12px;color:#6b7280"><?php echo date('d.m.Y', strtotime($u['data_rejestracji'])); ?></td>
                <td style="font-size:12px;color:#6b7280">
                    <?php echo $u['ostatnie_logowanie'] ? date('d.m.Y H:i', strtotime($u['ostatnie_logowanie'])) : 'Nigdy'; ?>
                </td>
                <td>
                    <a href="admin.php?usun_uzyt=<?php echo $u['id']; ?>" class="akcja akcja-usun"
                       onclick="return confirm('Usunac uzytkownika i wszystkie jego dane?')">Usun</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

    <!-- ZADANIA -->
    <div class="sekcja" id="zadania">
        <div class="sekcja-naglowek"><h2>Zadania wszystkich uzytkownikow (ostatnie 30)</h2></div>
        <?php if (mysqli_num_rows($wynik_zadania) == 0): ?>
            <div class="pusta">Brak zadan.</div>
        <?php else: ?>
        <table>
            <tr>
                <th>Zadanie</th>
                <th>Uzytkownik</th>
                <th>Kategoria</th>
                <th>Priorytet</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Akcje</th>
            </tr>
            <?php while ($z = mysqli_fetch_assoc($wynik_zadania)): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($z['tytul']); ?></strong></td>
                <td style="font-size:12px"><?php echo htmlspecialchars($z['imie'] . ' ' . $z['nazwisko']); ?></td>
                <td style="font-size:12px;color:#6b7280"><?php echo $z['kat_nazwa'] ? htmlspecialchars($z['kat_nazwa']) : '—'; ?></td>
                <td><span class="priorytet priorytet-<?php echo $z['priorytet']; ?>"><?php echo ucfirst($z['priorytet']); ?></span></td>
                <td>
                    <span class="status status-<?php echo $z['status']; ?>">
                        <?php
                        $s = array('do_zrobienia' => 'Do zrobienia', 'w_toku' => 'W toku', 'zakonczone' => 'Ukonczone');
                        echo isset($s[$z['status']]) ? $s[$z['status']] : $z['status'];
                        ?>
                    </span>
                </td>
                <td style="font-size:12px;color:#6b7280"><?php echo date('d.m.Y', strtotime($z['deadline'])); ?></td>
                <td>
                    <a href="admin.php?usun_zad=<?php echo $z['id']; ?>" class="akcja akcja-usun"
                       onclick="return confirm('Usunac to zadanie?')">Usun</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

    <!-- LOGI SUKCESU -->
    <div class="sekcja" id="logi">
        <div class="sekcja-naglowek"><h2>Logi sukcesu (ostatnie 20)</h2></div>
        <?php if (mysqli_num_rows($wynik_logi) == 0): ?>
            <div class="pusta">Brak logow sukcesu.</div>
        <?php else: ?>
        <table>
            <tr>
                <th>Tytul</th>
                <th>Uzytkownik</th>
                <th>Typ</th>
                <th>Punkty XP</th>
                <th>Data</th>
            </tr>
            <?php while ($log = mysqli_fetch_assoc($wynik_logi)): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['tytul']); ?></td>
                <td style="font-size:12px"><?php echo htmlspecialchars($log['imie'] . ' ' . $log['nazwisko']); ?></td>
                <td><span class="typ-badge"><?php echo $log['typ']; ?></span></td>
                <td class="xp">+<?php echo $log['punkty_xp']; ?> XP</td>
                <td style="font-size:12px;color:#6b7280"><?php echo date('d.m.Y H:i', strtotime($log['data_osiagniecia'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>
