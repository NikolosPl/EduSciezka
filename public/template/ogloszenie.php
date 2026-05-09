<?php
require_once "polaczenie.php"; // Twoje połączenie z bazą

function oczysc_tresc_ogloszenia($tekst)
{
    $tekst = (string) $tekst;
    $tekst = preg_replace('/<\?(?:php)?[\s\S]*?\?>/iu', '', $tekst);

    $markery_kodu = ['if (isset($_', '$_GET[', '$_POST[', 'mysqli_', 'header("Location:', 'require_once ', '<?php'];
    $pierwsza_pozycja = false;

    foreach ($markery_kodu as $marker) {
        $pozycja = strpos($tekst, $marker);
        if ($pozycja !== false && ($pierwsza_pozycja === false || $pozycja < $pierwsza_pozycja)) {
            $pierwsza_pozycja = $pozycja;
        }
    }

    if ($pierwsza_pozycja !== false) {
        $tekst = mb_substr($tekst, 0, $pierwsza_pozycja);
    }

    $tekst = strip_tags($tekst);
    $tekst = preg_replace('/\s+/u', ' ', $tekst);
    return trim($tekst);
}

// Sprawdzamy czy ID zostało przekazane w adresie URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int) $_GET['id'];

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
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../style/informacje-style.css">
    <style>
        .full-article {
            max-width: 1180px;
            margin: 40px auto;
            padding: 24px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
        }

        .article-grid {
            display: grid;
            grid-template-columns: minmax(320px, 42%) minmax(360px, 58%);
            gap: 24px;
            align-items: start;
        }

        .article-image-wrap {
            position: sticky;
            top: 16px;
        }

        .full-article img {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #d1e1f5;
            object-fit: cover;
        }

        .full-article h1 {
            font-size: 2.1rem;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .full-article .date {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .full-article .content {
            line-height: 1.8;
            font-size: 1.1rem;
            text-align: justify;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #2A95FF;
            text-decoration: none;
            font-weight: bold;
        }

        .article-grid.bez-zdjecia {
            grid-template-columns: 1fr;
        }

        .article-grid.bez-zdjecia .article-content-wrap {
            max-width: 860px;
        }

        @media (max-width: 960px) {
            .article-grid {
                grid-template-columns: 1fr;
            }

            .article-image-wrap {
                position: static;
            }

            .full-article h1 {
                font-size: 1.7rem;
            }
        }
    </style>
</head>

<body>
    <main class="tresc">
        <div class="full-article">
            <a href="informacje.php" class="back-link">← Wróć do aktualności</a>

            <div class="article-grid <?php echo empty($o['zdjecie']) ? 'bez-zdjecia' : ''; ?>">
                <?php if (!empty($o['zdjecie'])): ?>
                    <div class="article-image-wrap">
                        <img src="../uploads/ogloszenia/<?php echo $o['zdjecie']; ?>"
                            alt="<?php echo htmlspecialchars($o['tytul']); ?>">
                    </div>
                <?php endif; ?>

                <div class="article-content-wrap">
                    <h1><?php echo htmlspecialchars($o['tytul']); ?></h1>
                    <div class="date">Opublikowano: <?php echo date('d.m.Y', strtotime($o['data_dodania'])); ?></div>

                    <div class="line"></div>

                    <div class="content">
                        <?php echo nl2br(htmlspecialchars(oczysc_tresc_ogloszenia($o['tresc']))); ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>

</html>