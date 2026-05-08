<?php
require_once "polaczenie.php"; // Twoje połączenie z bazą

// Sprawdzamy czy ID zostało przekazane w adresie URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Pobieramy dane konkretnego ogłoszenia
    $sql = "SELECT * FROM ogloszenia WHERE id = $id";
    $wynik = mysqli_query($polaczenie, $sql);
    $o = mysqli_fetch_assoc($wynik);

    // Jeśli ogłoszenie nie istnieje w bazie
    if (!$o) {
        die("Ogłoszenie nie istnieje.");
    }
} else {
    // Jeśli ktoś wszedł na stronę bez ID
    header("Location: informacje.php");
    exit;
}
?>

<!doctype html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($o['tytul']); ?> - EduŚcieżka</title>
    <link rel="stylesheet" href="../style/informacje-style.css">
    <style>
        .full-article { max-width: 800px; margin: 40px auto; padding: 20px; background: white; border-radius: 15px; }
        .full-article img { width: 100%; border-radius: 10px; margin-bottom: 20px; }
        .full-article h1 { font-size: 2rem; margin-bottom: 10px; color: #2c3e50; }
        .full-article .date { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 20px; }
        .full-article .content { line-height: 1.8; font-size: 1.1rem; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #2A95FF; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <main class="tresc">
        <div class="full-article">
            <a href="informacje.php" class="back-link">← Wróć do aktualności</a>
            
            <?php if(!empty($o['zdjecie'])): ?>
                <img src="../uploads/ogloszenia/<?php echo $o['zdjecie']; ?>" alt="">
            <?php endif; ?>

            <h1><?php echo htmlspecialchars($o['tytul']); ?></h1>
            <div class="date">Opublikowano: <?php echo date('d.m.Y', strtotime($o['data_dodania'])); ?></div>
            
            <div class="line"></div>
            
            <div class="content">
                <?php echo nl2br(htmlspecialchars($o['tresc'])); ?>
            </div>
        </div>
    </main>

    </body>
</html>