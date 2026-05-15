<!doctype html>
<html lang="pl">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EduŚcieżka</title>
  <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon" />
  <link rel="stylesheet" href="../style/informacje-style.css" />
  <link rel="stylesheet" href="../style/nav.css" />
</head>

<body>
  <nav class="navigation">
    <ul>
      <li id="nav-title-flex">
        <a href="../../index.html" id="nav-title">EduŚcieżka</a>
      </li>
      <li><a href="./o_aplikacji.html">O aplikacji</a></li>
      <li><a href="./informacje.php">Informacje</a></li>
      <li><a href="./kontakt.php">Kontakt</a></li>
      <li>
        <a href="./logowanie.php" class="nav-button">Zaloguj się</a>
      </li>
    </ul>
  </nav>
  <main>
    <div class="info">
      <div class="info-title">
        <h2>Co nowego?</h2>
        <div class="line"></div>
      </div>
      <?php
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

      require_once "polaczenie.php";
      $sql = "SELECT * FROM ogloszenia ORDER BY data_dodania DESC, id DESC LIMIT 7";
      $wynik = mysqli_query($polaczenie, $sql);

      if (mysqli_num_rows($wynik) > 0):


        $najnowszy = mysqli_fetch_assoc($wynik);


        $foto_glowne = !empty($najnowszy['zdjecie']) ? "../uploads/ogloszenia/" . $najnowszy['zdjecie'] : "../img/prototyp-zdjecia.png";
        $opis_glowny = oczysc_tresc_ogloszenia($najnowszy['krotki_opis']);
        if ($opis_glowny === '') {
          $opis_glowny = mb_substr(oczysc_tresc_ogloszenia($najnowszy['tresc']), 0, 140);
        }
        ?>

        <div class="latest-info">
          <img src="<?php echo edusciezka_e($foto_glowne); ?>" alt="<?php echo htmlspecialchars($najnowszy['tytul']); ?>">
          <div class="latest-info-text">
            <h3><?php echo htmlspecialchars($najnowszy['tytul']); ?></h3>
            <div class="line"></div>
            <p><?php echo htmlspecialchars($opis_glowny); ?></p>
            <a class="czytaj-dalej" href="ogloszenie.php?id=<?php echo (int) $najnowszy['id']; ?>">Czytaj dalej</a>
          </div>
        </div>

        <div class="the-rest">
          <?php


          while ($o = mysqli_fetch_assoc($wynik)):
            $foto_karta = !empty($o['zdjecie']) ? "../uploads/ogloszenia/" . $o['zdjecie'] : "../img/prototyp-zdjecia.png";
            $opis_karty = oczysc_tresc_ogloszenia($o['krotki_opis']);
            if ($opis_karty === '') {
              $opis_karty = mb_substr(oczysc_tresc_ogloszenia($o['tresc']), 0, 120);
            }
            ?>
            <div class="info-card">
              <img src="<?php echo edusciezka_e($foto_karta); ?>" alt="<?php echo htmlspecialchars($o['tytul']); ?>">
              <h4><?php echo htmlspecialchars($o['tytul']); ?></h4>
              <p><?php echo htmlspecialchars($opis_karty); ?></p>
              <a class="czytaj-dalej" href="ogloszenie.php?id=<?php echo (int) $o['id']; ?>">Czytaj dalej</a>
            </div>
          <?php endwhile; ?>
        </div>

      <?php else: ?>
        <p style="text-align: center; padding: 50px;">Obecnie nie ma żadnych nowych informacji.</p>
      <?php endif; ?>
    </div>
  </main>
  <footer class="footer">
    <div class="footer-left">
      <a href="../../index.html">
        <h3>EduŚcieżka</h3>
      </a>
      <ul>
        <li><a href="./o_aplikacji.html">O aplikacji</a></li>
        <li><a href="./informacje.php">Informacje</a></li>
        <li><a href="./kontakt.php">Kontakt</a></li>
      </ul>
    </div>
    <div class="footer-right">
      <ul>
        <li><a href="" style="color: #2A95FF;"><svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor" fill="none"
              viewBox="0 0 24 24" id="Instagram-Logo-2--Streamline-Logos" height="30" width="35">
              <path stroke-linejoin="round" d="M18 6.5a0.5 0.5 0 0 1 0 -1" stroke-width="1"></path>
              <path stroke-linejoin="round" d="M18 6.5a0.5 0.5 0 0 0 0 -1" stroke-width="1"></path>
              <path stroke-linejoin="round" d="M7 12a5 5 0 1 0 10 0 5 5 0 1 0 -10 0" stroke-width="1"></path>
              <path d="M16.5 1.5h-9a6 6 0 0 0 -6 6v9a6 6 0 0 0 6 6h9a6 6 0 0 0 6 -6v-9a6 6 0 0 0 -6 -6Z"
                stroke-width="1"></path>
            </svg></a></li>
        <li><a href="" style="color: #2A95FF;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
              viewBox="0 0 24 24" id="Tiktok-Logo--Streamline-Logos" height="30" width="35">
              <path stroke-linejoin="round"
                d="M16 1.5h-3.5V16c0 1.5 -1.5 3 -3 3s-3 -0.5 -3 -3c0 -2 1.899 -3.339 3.5 -3V9.5c-6.12 0 -7 5 -7 6.5s0.977 6.5 6.5 6.5c4.522 0 6.5 -3.5 6.5 -6v-8c1.146 1.018 2.922 1.357 5 1.5V6.5c-3.017 0 -5 -2.654 -5 -5Z"
                stroke-width="1"></path>
            </svg></a></li>
        <li><a href="" style="color: #2A95FF;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor"
              viewBox="0 0 48 48" id="Contact-Phonebook--Streamline-Plump" height="30" width="35">
              <g id="contact-phonebook--phonebook-phone-number-books-book">
                <path id="rectangle-1098" stroke-linejoin="round"
                  d="M5.489 37.978c-1.36 -0.04 -2.433 -0.913 -2.478 -2.272a21.258 21.258 0 0 1 0 -1.412c0.045 -1.36 1.118 -2.232 2.478 -2.272a51.773 51.773 0 0 1 3.022 0c1.36 0.04 2.433 0.913 2.478 2.272a21.148 21.148 0 0 1 0 1.412c-0.045 1.36 -1.118 2.232 -2.478 2.272a51.772 51.772 0 0 1 -3.022 0Z"
                  stroke-width="2"></path>
                <path id="rectangle-1099" stroke-linejoin="round"
                  d="M5.489 26.978c-1.36 -0.04 -2.433 -0.913 -2.478 -2.272a21.258 21.258 0 0 1 0 -1.412c0.045 -1.36 1.118 -2.232 2.478 -2.272a51.773 51.773 0 0 1 3.022 0c1.36 0.04 2.433 0.913 2.478 2.272a21.148 21.148 0 0 1 0 1.412c-0.045 1.36 -1.118 2.232 -2.478 2.272a51.772 51.772 0 0 1 -3.022 0Z"
                  stroke-width="2"></path>
                <path id="rectangle-1100" stroke-linejoin="round"
                  d="M5.489 15.978c-1.36 -0.04 -2.433 -0.913 -2.478 -2.272a21.258 21.258 0 0 1 0 -1.412c0.045 -1.36 1.118 -2.232 2.478 -2.272a51.773 51.773 0 0 1 3.022 0c1.36 0.04 2.433 0.913 2.478 2.272a21.148 21.148 0 0 1 0 1.412c-0.045 1.36 -1.118 2.232 -2.478 2.272a51.772 51.772 0 0 1 -3.022 0Z"
                  stroke-width="2"></path>
                <path id="Subtract" stroke-linejoin="round"
                  d="M7.371 37.999c0.039 0.634 0.078 1.216 0.117 1.746 0.188 2.553 2.113 4.533 4.664 4.75 2.94 0.252 7.479 0.505 13.848 0.505 6.37 0 10.909 -0.253 13.848 -0.504 2.551 -0.218 4.477 -2.198 4.664 -4.75C44.75 36.516 45 31.365 45 24s-0.25 -12.517 -0.488 -15.745c-0.188 -2.554 -2.113 -4.532 -4.664 -4.75C36.908 3.252 32.37 3 26 3c-6.37 0 -10.909 0.253 -13.848 0.504 -2.55 0.219 -4.476 2.197 -4.664 4.75 -0.04 0.53 -0.078 1.112 -0.117 1.747"
                  stroke-width="2"></path>
                <path id="Subtract_2" stroke-linejoin="round" d="M7.109 32a261.062 261.062 0 0 1 -0.095 -5"
                  stroke-width="2"></path>
                <path id="Subtract_3" stroke-linejoin="round" d="M7.014 21c0.017 -1.824 0.05 -3.488 0.095 -5"
                  stroke-width="2"></path>
                <path id="Union" stroke-linejoin="round"
                  d="M30.209 25.071a6 6 0 1 0 -6.438 -0.014c-2.862 1.034 -5.054 3.416 -5.769 6.356 -0.312 1.28 0.438 2.498 1.713 2.834 1.425 0.376 3.732 0.753 7.255 0.753 3.524 0 5.83 -0.377 7.255 -0.753 1.275 -0.336 2.025 -1.553 1.713 -2.834 -0.712 -2.927 -2.887 -5.3 -5.73 -6.342Z"
                  stroke-width="2"></path>
              </g>
            </svg>
          </a></li>
      </ul>
    </div>
  </footer>
</body>

</html>