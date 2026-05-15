<?php
require_once "polaczenie.php";

if (isset($_SESSION['uzytkownik_id'])) {
    header("Location: dashboard.php");
    exit;
}

if (isset($_GET['secret']) && $_GET['secret'] === 'mojtajnykod') {
    header('Location: admin_login.php');
    exit;
}

$blad = "";
$sukces = "";

$csrf_token = edusciezka_csrf_token();

if (isset($_POST['akcja']) && $_POST['akcja'] == 'login') {
    edusciezka_require_csrf();
    $limit = edusciezka_rate_limit('login:' . edusciezka_request_ip(), 5, 900);
    if (!$limit['allowed']) {
        $blad = 'Za duzo prob logowania. Sprobuj ponownie za ' . (int) ceil($limit['retry_after'] / 60) . ' min.';
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    $haslo = $_POST['haslo'];
    if ($blad === '') {
        $stmt = mysqli_prepare($polaczenie, "SELECT id, imie, nazwisko, email, haslo_hash FROM uzytkownicy WHERE email = ? AND aktywny = 1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $wynik = mysqli_stmt_get_result($stmt);

        if ($wynik && mysqli_num_rows($wynik) == 1) {
            $uzytkownik = mysqli_fetch_assoc($wynik);
            if (password_verify($haslo, $uzytkownik['haslo_hash'])) {
                session_regenerate_id(true);
                $_SESSION['uzytkownik_id'] = $uzytkownik['id'];
                $_SESSION['imie'] = $uzytkownik['imie'];
                $_SESSION['nazwisko'] = $uzytkownik['nazwisko'];
                $_SESSION['email'] = $uzytkownik['email'];

                $stmt_update = mysqli_prepare($polaczenie, "UPDATE uzytkownicy SET ostatnie_logowanie = NOW() WHERE id = ?");
                mysqli_stmt_bind_param($stmt_update, 'i', $uzytkownik['id']);
                mysqli_stmt_execute($stmt_update);

                header("Location: dashboard.php");
                exit;
            } else {
                $blad = "Nieprawidłowy email lub hasło.";
            }
        } else {
            $blad = "Nieprawidłowy email lub hasło.";
        }
    }
    if ($blad === '' && !$limit['allowed']) {
        $blad = "Nieprawidłowy email lub hasło.";
    }
}

if (isset($_POST['akcja']) && $_POST['akcja'] == 'rejestracja') {
    edusciezka_require_csrf();
    $limit = edusciezka_rate_limit('register:' . edusciezka_request_ip(), 3, 3600);
    if (!$limit['allowed']) {
        $blad = 'Za duzo prob rejestracji. Sprobuj ponownie pozniej.';
    }

    $imie = trim((string) ($_POST['imie'] ?? ''));
    $nazwisko = trim((string) ($_POST['nazwisko'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $haslo = $_POST['haslo'];

    if ($blad !== '') {

    } elseif (strlen($haslo) < 8) {
        $blad = "Hasło musi mieć minimum 8 znaków.";
    } else {
        $sprawdz = mysqli_prepare($polaczenie, "SELECT id FROM uzytkownicy WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($sprawdz, 's', $email);
        mysqli_stmt_execute($sprawdz);
        $wynik_sprawdz = mysqli_stmt_get_result($sprawdz);
        if ($wynik_sprawdz && mysqli_num_rows($wynik_sprawdz) > 0) {
            $blad = "Ten email jest już zajęty.";
        } else {
            $haslo_hash = password_hash($haslo, PASSWORD_BCRYPT);
            $sql = mysqli_prepare($polaczenie, "INSERT INTO uzytkownicy (imie, nazwisko, email, haslo_hash) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($sql, 'ssss', $imie, $nazwisko, $email, $haslo_hash);
            if (mysqli_stmt_execute($sql)) {
                $sukces = "Konto zostało utworzone! Możesz się zalogować.";
            } else {
                $blad = "Błąd przy rejestracji: " . mysqli_error($polaczenie);
            }
        }
    }
}

$showRegister = $sukces || (isset($_POST['akcja']) && $_POST['akcja'] == 'rejestracja');
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduŚcieżka - Logowanie</title>
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../style/logowanie-style.css">
</head>

<body>
    <main class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <h1 id="admin-trigger">EduŚcieżka</h1>
                    <p>Twoja ścieżka po szkole</p>
                </div>

                <div class="auth-tabs">
                    <button type="button" class="auth-tab <?php echo !$showRegister ? 'active' : ''; ?>" id="tab-login"
                        onclick="switchTab('login')">
                        Logowanie
                    </button>
                    <button type="button" class="auth-tab <?php echo $showRegister ? 'active' : ''; ?>"
                        id="tab-register" onclick="switchTab('register')">
                        Rejestracja
                    </button>
                </div>

                <div class="auth-form <?php echo !$showRegister ? 'active' : ''; ?>" id="form-login">
                    <?php if ($blad && isset($_POST['akcja']) && $_POST['akcja'] == 'login'): ?>
                        <div class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <?php echo htmlspecialchars($blad); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="akcja" value="login">
                        <?php echo edusciezka_csrf_input(); ?>
                        <div class="form-group">
                            <label for="login-email">Email</label>
                            <input type="email" id="login-email" name="email" placeholder="jan@example.pl"
                                autocomplete="email" required>
                        </div>
                        <div class="form-group">
                            <label for="login-password">Hasło</label>
                            <input type="password" id="login-password" name="haslo" placeholder="Twoje hasło"
                                autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn-auth">Zaloguj się</button>
                    </form>
                </div>

                <div class="auth-form <?php echo $showRegister ? 'active' : ''; ?>" id="form-register">
                    <?php if ($blad && isset($_POST['akcja']) && $_POST['akcja'] == 'rejestracja'): ?>
                        <div class="alert alert-error">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            <?php echo htmlspecialchars($blad); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($sukces): ?>
                        <div class="alert alert-success">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <?php echo htmlspecialchars($sukces); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <input type="hidden" name="akcja" value="rejestracja">
                        <?php echo edusciezka_csrf_input(); ?>
                        <div class="form-group">
                            <label for="reg-name">Imię</label>
                            <input type="text" id="reg-name" name="imie" placeholder="Jan" autocomplete="given-name"
                                required>
                        </div>
                        <div class="form-group">
                            <label for="reg-surname">Nazwisko</label>
                            <input type="text" id="reg-surname" name="nazwisko" placeholder="Kowalski"
                                autocomplete="family-name" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-email">Email</label>
                            <input type="email" id="reg-email" name="email" placeholder="jan@example.pl"
                                autocomplete="email" required>
                        </div>
                        <div class="form-group">
                            <label for="reg-password">Hasło (min. 8 znaków)</label>
                            <input type="password" id="reg-password" name="haslo" placeholder="Minimum 8 znaków"
                                autocomplete="new-password" required minlength="8">
                        </div>
                        <button type="submit" class="btn-auth">Zarejestruj się</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="footer-left">
            <a href="../../index.html">Strona główna</a>
            <a href="./o_aplikacji.html">O aplikacji</a>
            <a href="./informacje.php">Informacje</a>
            <a href="./kontakt.php">Kontakt</a>
        </div>
        <div class="footer-right">
            <ul>
                <li>
                    <a href="" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="" aria-label="TikTok">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 18.75a6 6 0 006-6v-1.5m-6 7.5a6 6 0 01-6-6v-1.5m6 7.5v3.75m-3.75 0h.008v.008H12v-.008zM18.75 6a8.25 8.25 0 01-9 9m9-9a8.25 8.25 0 00-8.25 8.25m8.25-8.25h.008v.008H18.75V6z" />
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="./kontakt.php" aria-label="Kontakt">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </a>
                </li>
            </ul>
        </div>
    </footer>

    <script src="../js/logowanie.js"></script>
</body>

</html>