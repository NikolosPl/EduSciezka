<?php
require_once "polaczenie.php";

if (isset($_GET['wyloguj'])) {
    unset($_SESSION['admin']);
    header("Location: admin_login.php");
    exit;
}

if (isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit;
}

$blad = "";
$csrf_token = edusciezka_csrf_token();

if (isset($_POST['zaloguj'])) {
    edusciezka_require_csrf();
    $limit = edusciezka_rate_limit('admin-login:' . edusciezka_request_ip(), 5, 900);
    if (!$limit['allowed']) {
        $blad = 'Za duzo prob logowania administratora. Sprobuj ponownie pozniej.';
    }

    $admin_haslo = edusciezka_env('EDUSCIEZKA_ADMIN_PASSWORD', '');
    if ($blad === '' && $_POST['haslo'] === $admin_haslo) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $blad = "Nieprawidłowe hasło administratora.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduŚcieżka - Panel Admina</title>
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../style/admin_login-style.css">
</head>

<body>
    <div class="admin-card">
        <div class="admin-header">
            <h1>EduŚcieżka</h1>
            <p>Panel Administratora</p>
            <span class="admin-badge">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                Dostęp tylko dla administratorów
            </span>
        </div>
        <div class="admin-body">
            <?php if ($blad): ?>
                <div class="alert alert-error">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <?php echo htmlspecialchars($blad); ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <?php echo edusciezka_csrf_input(); ?>
                <div class="form-group">
                    <label for="password">Hasło administratora</label>
                    <input type="password" id="password" name="haslo" placeholder="Wpisz hasło admina" required autofocus>
                </div>
                <button type="submit" name="zaloguj" class="btn-admin">Zaloguj się jako Admin</button>
            </form>
            <div class="link-back">
                <a href="logowanie.php">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                    </svg>
                    Powrót do logowania
                </a>
            </div>
        </div>
    </div>
</body>

</html>