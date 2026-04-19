<?php
require_once "polaczenie.php";

if (isset($_SESSION['uzytkownik_id'])) {
    header("Location: dashboard.php");
    exit;
}

$blad = "";
$sukces = "";

// Logowanie
if (isset($_POST['akcja']) && $_POST['akcja'] == 'login') {
    $email = mysqli_real_escape_string($polaczenie, $_POST['email']);
    $haslo = $_POST['haslo'];

    $wynik = mysqli_query($polaczenie, "SELECT * FROM uzytkownicy WHERE email = '$email' AND aktywny = 1");

    if ($wynik && mysqli_num_rows($wynik) == 1) {
        $uzytkownik = mysqli_fetch_assoc($wynik);
        if (password_verify($haslo, $uzytkownik['haslo_hash'])) {
            $_SESSION['uzytkownik_id'] = $uzytkownik['id'];
            $_SESSION['imie'] = $uzytkownik['imie'];
            $_SESSION['nazwisko'] = $uzytkownik['nazwisko'];
            $_SESSION['email'] = $uzytkownik['email'];

            mysqli_query($polaczenie, "UPDATE uzytkownicy SET ostatnie_logowanie = NOW() WHERE id = " . $uzytkownik['id']);

            header("Location: dashboard.php");
            exit;
        } else {
            $blad = "Nieprawidlowy email lub haslo.";
        }
    } else {
        $blad = "Nieprawidlowy email lub haslo.";
    }
}

// Rejestracja
if (isset($_POST['akcja']) && $_POST['akcja'] == 'rejestracja') {
    $imie = mysqli_real_escape_string($polaczenie, $_POST['imie']);
    $nazwisko = mysqli_real_escape_string($polaczenie, $_POST['nazwisko']);
    $email = mysqli_real_escape_string($polaczenie, $_POST['email']);
    $haslo = $_POST['haslo'];

    if (strlen($haslo) < 8) {
        $blad = "Haslo musi miec minimum 8 znakow.";
    } else {
        $sprawdz = mysqli_query($polaczenie, "SELECT id FROM uzytkownicy WHERE email = '$email'");
        if (mysqli_num_rows($sprawdz) > 0) {
            $blad = "Ten email jest juz zajety.";
        } else {
            $haslo_hash = password_hash($haslo, PASSWORD_BCRYPT);
            $sql = "INSERT INTO uzytkownicy (imie, nazwisko, email, haslo_hash) VALUES ('$imie', '$nazwisko', '$email', '$haslo_hash')";
            if (mysqli_query($polaczenie, $sql)) {
                $sukces = "Konto zostalo utworzone! Mozesz sie zalogowac.";
            } else {
                $blad = "Blad przy rejestracji: " . mysqli_error($polaczenie);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>EduSciezka - Logowanie</title>
    <link rel="stylesheet" href="../style/index-style.css">
</head>

<body>


    <h2>
        <a href="./index.html" class="return"><button class="return-button">Wróć na stronę główną</button></a>
    </h2>
    <div class="box">
        <div class="naglowek">
            <h1 id="logo" onclick="tajneKliknięcie()" style="cursor:default">EduSciezka</h1>
            <p>Zaplanuj swoja kariere juz teraz!</p>
        </div>

        <div class="zakladki">
            <button class="zakladka aktywna" id="btn-login" onclick="pokazForm('login')">Logowanie</button>
            <button class="zakladka" id="btn-reg" onclick="pokazForm('rejestracja')">Rejestracja</button>
        </div>

        <!-- LOGOWANIE -->
        <div class="formularz aktywny" id="form-login">
            <?php if ($blad && isset($_POST['akcja']) && $_POST['akcja'] == 'login'): ?>
                <div class="blad"><?php echo $blad; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="akcja" value="login">
                <div class="pole">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="jan@example.pl" required>
                </div>
                <div class="pole">
                    <label>Haslo</label>
                    <input type="password" name="haslo" placeholder="Twoje haslo" required>
                </div>
                <button type="submit" class="btn">Zaloguj sie</button>
            </form>
        </div>

        <!-- REJESTRACJA -->
        <div class="formularz" id="form-rejestracja">
            <?php if ($blad && isset($_POST['akcja']) && $_POST['akcja'] == 'rejestracja'): ?>
                <div class="blad"><?php echo $blad; ?></div>
            <?php endif; ?>
            <?php if ($sukces): ?>
                <div class="sukces"><?php echo $sukces; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="akcja" value="rejestracja">
                <div class="pole">
                    <label>Imie</label>
                    <input type="text" name="imie" placeholder="Jan" required>
                </div>
                <div class="pole">
                    <label>Nazwisko</label>
                    <input type="text" name="nazwisko" placeholder="Kowalski" required>
                </div>
                <div class="pole">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="jan@example.pl" required>
                </div>
                <div class="pole">
                    <label>Haslo (min. 8 znakow)</label>
                    <input type="password" name="haslo" required>
                </div>
                <button type="submit" class="btn">Zarejestruj sie</button>
            </form>
        </div>
    </div>

    <script>
        function pokazForm(forma) {
            document.getElementById('form-login').className = 'formularz';
            document.getElementById('form-rejestracja').className = 'formularz';
            document.getElementById('btn-login').className = 'zakladka';
            document.getElementById('btn-reg').className = 'zakladka';
            document.getElementById('form-' + forma).className = 'formularz aktywny';
            document.getElementById(forma == 'login' ? 'btn-login' : 'btn-reg').className = 'zakladka aktywna';
        }
        <?php if ($sukces || (isset($_POST['akcja']) && $_POST['akcja'] == 'rejestracja' && $blad)): ?>
            pokazForm('rejestracja');
        <?php endif; ?>
    </script>

    <?php if (isset($_GET['secret']) && $_GET['secret'] === 'mojtajnykod'): ?>
        <script>window.location.href = 'admin_login.php';</script>
    <?php endif; ?>

    <script>
        var klikniecia = 0;
        function tajneKliknięcie() {
            klikniecia++;
            if (klikniecia >= 5) {
                klikniecia = 0;
                window.location.href = 'admin_login.php';
            }
        }
    </script>

</body>

</html>