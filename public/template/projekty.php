<?php
require_once "polaczenie.php";

if (!isset($_SESSION['uzytkownik_id'])) {
    header("Location: logowanie.php");
    exit;
}

$uid = (int) $_SESSION['uzytkownik_id'];
$imie = $_SESSION['imie'];
$komunikat = "";

// Dodaj projekt
if (isset($_POST['dodaj_projekt'])) {
    $nazwa = mysqli_real_escape_string($polaczenie, $_POST['nazwa']);
    $opis = mysqli_real_escape_string($polaczenie, $_POST['opis']);
    $deadline = mysqli_real_escape_string($polaczenie, $_POST['deadline']);
    $priorytet = mysqli_real_escape_string($polaczenie, $_POST['priorytet']);
    $kategoria_id = (int) $_POST['kategoria_id'];
    $data_start = mysqli_real_escape_string($polaczenie, $_POST['data_start']);

    if ($nazwa != "" && $deadline != "") {
        $ds = $data_start != "" ? "'$data_start'" : "NULL";
        $sql = "INSERT INTO projekty (uzytkownik_id, kategoria_id, nazwa, opis, data_start, deadline, priorytet)
                VALUES ($uid, $kategoria_id, '$nazwa', '$opis', $ds, '$deadline', '$priorytet')";
        mysqli_query($polaczenie, $sql);
        $komunikat = "sukces:Projekt został dodany!";
    } else {
        $komunikat = "blad:Wypełnij nazwę i datę!";
    }
}

// Zmien status projektu
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $pid = (int) $_GET['id'];
    $nowy_status = mysqli_real_escape_string($polaczenie, $_GET['status']);
    $dozwolone = array('planowany', 'w_toku', 'zakończony', 'porzucony');
    if (in_array($nowy_status, $dozwolone)) {
        mysqli_query($polaczenie, "UPDATE projekty SET status = '$nowy_status' WHERE id = $pid AND uzytkownik_id = $uid");
    }
    header("Location: projekty.php?msg=sukces:Status zmieniony.");
    exit;
}

if (isset($_GET['msg']))
    $komunikat = $_GET['msg'];

$wynik_projektow = mysqli_query(
    $polaczenie,
    "SELECT p.*, k.nazwa AS kat_nazwa, k.kolor_hex
     FROM projekty p
     LEFT JOIN kategorie k ON p.kategoria_id = k.id
     WHERE p.uzytkownik_id = $uid
     ORDER BY p.deadline ASC"
);

$wynik_kategorii = mysqli_query($polaczenie, "SELECT * FROM kategorie ORDER BY nazwa");
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>EduŚcieżka - Projekty</title>
    <link rel="stylesheet" href="../style/projekty-style.css">
</head>

<body>

    <div class="pasek">
        <div class="logo">EduŚcieżka</div>
        <div>Witaj, <strong><?php echo htmlspecialchars($imie); ?></strong><a href="wyloguj.php">Wyloguj się</a></div>
    </div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="logi.php">Log Sukcesu</a>
        <a href="projekty.php" class="aktywny">Projekty</a>
        <a href="planer-przyszlosci.php">Planer przyszłości</a>
    </div>

    <div class="tresc">

        <?php
        if ($komunikat != "") {
            $czesci = explode(":", $komunikat, 2);
            $typ = $czesci[0];
            $tresc = isset($czesci[1]) ? $czesci[1] : $komunikat;
            if ($typ == "sukces")
                echo '<div class="kom-sukces">' . htmlspecialchars($tresc) . '</div>';
            else
                echo '<div class="kom-blad">' . htmlspecialchars($tresc) . '</div>';
        }
        ?>

        <div class="naglowek-sekcji">
            <h2>Moje Projekty</h2>
            <button class="btn-pokaz" onclick="przelaczForm('form-projekt')">+ Dodaj projekt</button>
        </div>

        <div class="formularz-dodaj" id="form-projekt">
            <h3>Nowy projekt</h3>
            <form method="POST">
                <div class="rzad-pol">
                    <div class="pole">
                        <label>Nazwa projektu *</label>
                        <input type="text" name="nazwa" placeholder="np. Portfolio na GitHub" required>
                    </div>
                    <div class="pole">
                        <label>Deadline *</label>
                        <input type="date" name="deadline" required>
                    </div>
                </div>
                <div class="rzad-pol">
                    <div class="pole">
                        <label>Data startu</label>
                        <input type="date" name="data_start">
                    </div>
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
                            <option value="sredni" selected>Średni</option>
                            <option value="wysoki">Wysoki</option>
                            <option value="krytyczny">Krytyczny</option>
                        </select>
                    </div>
                </div>
                <div class="rzad-pol">
                    <div class="pole">
                        <label>Opis</label>
                        <textarea name="opis" rows="2" placeholder="Krotki opis projektu..."></textarea>
                    </div>
                </div>
                <button type="submit" name="dodaj_projekt" class="btn-dodaj">Dodaj projekt</button>
                <button type="button" class="btn-anuluj" onclick="przelaczForm('form-projekt')">Anuluj</button>
            </form>
        </div>

        <?php if (mysqli_num_rows($wynik_projektow) == 0): ?>
            <div class="pusta">Brak projektów. Dodaj swój pierwszy projekt!</div>
        <?php else: ?>
            <div class="projekty-lista">
                <?php while ($proj = mysqli_fetch_assoc($wynik_projektow)): ?>
                    <?php
                    $s = $proj['status'];
                    $s_css = str_replace(array('ó', 'ń', 'ą', 'ę', 'ś', 'ż', 'ź', 'ć', 'ł'), array('o', 'n', 'a', 'e', 's', 'z', 'z', 'c', 'l'), $s);
                    ?>
                    <div class="projekt-karta status-<?php echo $s_css; ?>">
                        <div class="projekt-gora">
                            <div>
                                <span class="projekt-nazwa"><?php echo htmlspecialchars($proj['nazwa']); ?></span>
                                <span class="status-badge sb-<?php echo $s_css; ?>"><?php echo $s; ?></span>
                            </div>
                            <span
                                class="priorytet priorytet-<?php echo $proj['priorytet']; ?>"><?php echo ucfirst($proj['priorytet']); ?></span>
                        </div>

                        <?php if ($proj['opis']): ?>
                            <div class="projekt-opis"><?php echo htmlspecialchars($proj['opis']); ?></div>
                        <?php endif; ?>

                        <div class="projekt-meta">
                            <?php if ($proj['kat_nazwa']): ?>
                                <span class="kropka" style="background:<?php echo $proj['kolor_hex']; ?>"></span>
                                <?php echo htmlspecialchars($proj['kat_nazwa']); ?> &nbsp;|&nbsp;
                            <?php endif; ?>
                            Deadline: <strong><?php echo date('d.m.Y', strtotime($proj['deadline'])); ?></strong>
                            <?php
                            $dni = round((strtotime($proj['deadline']) - time()) / 86400);
                            if ($s != 'zakończony' && $s != 'porzucony') {
                                if ($dni < 0)
                                    echo ' <span style="color:#b91c1c">— po terminie!</span>';
                                elseif ($dni <= 7)
                                    echo ' <span style="color:#f59e0b">— zostalo ' . $dni . ' dni</span>';
                            }
                            ?>
                        </div>

                        <div class="projekt-akcje">
                            <span style="font-size:12px;color:#6b7280">Zmień status: </span>
                            <?php if ($s != 'w_toku'): ?>
                                <a href="projekty.php?id=<?php echo $proj['id']; ?>&status=w_toku" class="a-w-toku">W toku</a>
                            <?php endif; ?>
                            <?php if ($s != 'zakończony'): ?>
                                <a href="projekty.php?id=<?php echo $proj['id']; ?>&status=zakończony" class="a-zakonczony"
                                    onclick="return confirm('Oznaczyc jako zakonczone?')">Zakończ</a>
                            <?php endif; ?>
                            <?php if ($s != 'porzucony'): ?>
                                <a href="projekty.php?id=<?php echo $proj['id']; ?>&status=porzucony" class="a-porzucony"
                                    onclick="return confirm('Oznaczyc jako porzucone?')">Porzuć</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>

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