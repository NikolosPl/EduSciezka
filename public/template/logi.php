<?php
require_once "polaczenie.php";

if (!isset($_SESSION['uzytkownik_id'])) {
    header("Location: logowanie.php");
    exit;
}

$uid = (int) $_SESSION['uzytkownik_id'];
$imie = $_SESSION['imie'];

$wynik = mysqli_query(
    $polaczenie,
    "SELECT * FROM logi_sukcesow WHERE uzytkownik_id = $uid ORDER BY data_osiagniecia DESC"
);

$r = mysqli_fetch_row(mysqli_query($polaczenie, "SELECT SUM(punkty_xp) FROM logi_sukcesow WHERE uzytkownik_id = $uid"));
$suma_xp = $r[0] ? $r[0] : 0;

$liczba_logow = mysqli_num_rows($wynik);
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>EduŚcieżka - Log Sukcesu</title>
    <link rel="stylesheet" href="../style/logi-style.css">
</head>

<body>

    <div class="pasek">
        <div class="logo">EduŚcieżka</div>
        <div>Witaj, <strong><?php echo htmlspecialchars($imie); ?></strong><a href="wyloguj.php">Wyloguj się</a></div>
    </div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="logi.php" class="aktywny">Log Sukcesu</a>
        <a href="projekty.php">Projekty</a>
        <a href="planer-przyszlosci.php">Planer przyszłości</a>
    </div>

    <div class="tresc">
        <div class="statystyki">
            <div class="karta-stat">
                <div class="liczba"><?php echo $liczba_logow; ?></div>
                <div class="etykieta">Wszystkich sukcesów</div>
            </div>
            <div class="karta-stat">
                <div class="liczba"><?php echo $suma_xp; ?></div>
                <div class="etykieta">Łącznie punktów XP</div>
            </div>
        </div>

        <div class="sekcja">
            <div class="sekcja-naglowek">
                <h2>Historia Sukcesów</h2>
            </div>
            <?php if ($liczba_logow == 0): ?>
                <div class="pusta">Brak logów sukcesu. Ukończ zadanie lub termin, aby coś się tu pojawiło!</div>
            <?php else: ?>
                <table>
                    <tr>
                        <th>Tytuł sukcesu</th>
                        <th>Typ</th>
                        <th>Punkty XP</th>
                        <th>Data osiągnięcia</th>
                    </tr>
                    <?php while ($log = mysqli_fetch_assoc($wynik)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($log['tytul']); ?></strong>
                                <?php if ($log['opis']): ?>
                                    <br><small style="color:#9ca3af"><?php echo htmlspecialchars($log['opis']); ?></small>
                                <?php endif; ?>
                            </td>
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

</body>

</html>