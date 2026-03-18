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

if (isset($_POST['zaloguj'])) {
    $admin_haslo = "admin123";
    if ($_POST['haslo'] === $admin_haslo) {
        $_SESSION['admin'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $blad = "Nieprawidlowe haslo admina.";
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<title>EduSciezka - Logowanie Admina</title>
<link rel="stylesheet" href="../style/admin_login-style.css">
</head>
<body>
<div class="box">
    <div class="naglowek">
        <h1>EduSciezka</h1>
        <p>Panel Administratora</p>
        <div class="badge">Dostep tylko dla administratorow</div>
    </div>
    <div class="tresc">
        <?php if ($blad): ?>
            <div class="blad"><?php echo htmlspecialchars($blad); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="pole">
                <label>Haslo administratora</label>
                <input type="password" name="haslo" placeholder="Wpisz haslo admina" required autofocus>
            </div>
            <button type="submit" name="zaloguj" class="btn">Zaloguj jako Admin</button>
        </form>
        <div class="link-powrot"><a href="index.php">&larr; Powrot do logowania</a></div>
    </div>
</div>
</body>
</html>
