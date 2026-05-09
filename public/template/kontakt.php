<?php
$komunikat_typ = "";
$komunikat_tresc = "";

$imie_nazwisko = "";
$email = "";
$tresc = "";

function zaladuj_env($sciezka)
{
    if (!is_file($sciezka) || !is_readable($sciezka)) {
        return;
    }

    $linie = file($sciezka, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$linie) {
        return;
    }

    foreach ($linie as $linia) {
        $linia = trim($linia);
        if ($linia === '' || strpos($linia, '#') === 0) {
            continue;
        }

        $pozycja = strpos($linia, '=');
        if ($pozycja === false) {
            continue;
        }

        $klucz = trim(substr($linia, 0, $pozycja));
        $wartosc = trim(substr($linia, $pozycja + 1));

        if ($klucz === '') {
            continue;
        }

        $obecna = getenv($klucz);
        if ($obecna !== false && $obecna !== '') {
            continue;
        }

        $wartosc = trim($wartosc, "\"'");
        putenv($klucz . '=' . $wartosc);
        $_ENV[$klucz] = $wartosc;
        $_SERVER[$klucz] = $wartosc;
    }
}

zaladuj_env(__DIR__ . '/../../.env');

function koduj_temat_utf8($temat)
{
    return '=?UTF-8?B?' . base64_encode((string) $temat) . '?=';
}

function smtp_czytaj_odpowiedz($socket)
{
    $odpowiedz = '';
    while (($linia = fgets($socket, 515)) !== false) {
        $odpowiedz .= $linia;
        if (preg_match('/^\d{3} /', $linia)) {
            break;
        }
    }
    return $odpowiedz;
}

function smtp_wyslij_komende($socket, $komenda, $oczekiwane_kody)
{
    fwrite($socket, $komenda . "\r\n");
    $odpowiedz = smtp_czytaj_odpowiedz($socket);
    $kod = (int) substr($odpowiedz, 0, 3);
    return in_array($kod, $oczekiwane_kody, true);
}

function wyslij_mail_smtp_yahoo($replyToEmail, $replyToName, $subject, $body)
{
    $smtpHost = getenv('EDUSCIEZKA_SMTP_HOST') ?: 'smtp.mail.yahoo.com';
    $smtpPort = (int) (getenv('EDUSCIEZKA_SMTP_PORT') ?: 587);
    $smtpUser = getenv('EDUSCIEZKA_SMTP_USER') ?: 'edusciezka@yahoo.com';
    $smtpPass = getenv('EDUSCIEZKA_SMTP_PASS') ?: '';
    $mailTo = getenv('EDUSCIEZKA_CONTACT_TO') ?: 'edusciezka@yahoo.com';
    $fromName = 'EduSciezka';

    if ($smtpPass === '') {
        return false;
    }

    $socket = @stream_socket_client(
        "tcp://{$smtpHost}:{$smtpPort}",
        $errno,
        $errstr,
        15,
        STREAM_CLIENT_CONNECT
    );

    if (!$socket) {
        return false;
    }

    stream_set_timeout($socket, 15);

    $powitanie = smtp_czytaj_odpowiedz($socket);
    if ((int) substr($powitanie, 0, 3) !== 220) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'EHLO edusciezka.local', array(250))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'STARTTLS', array(220))) {
        fclose($socket);
        return false;
    }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'EHLO edusciezka.local', array(250))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'AUTH LOGIN', array(334))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, base64_encode($smtpUser), array(334))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, base64_encode($smtpPass), array(235))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'MAIL FROM:<' . $smtpUser . '>', array(250))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'RCPT TO:<' . $mailTo . '>', array(250, 251))) {
        fclose($socket);
        return false;
    }

    if (!smtp_wyslij_komende($socket, 'DATA', array(354))) {
        fclose($socket);
        return false;
    }

    $headers = array(
        'Date: ' . date(DATE_RFC2822),
        'From: ' . $fromName . ' <' . $smtpUser . '>',
        'To: <' . $mailTo . '>',
        'Reply-To: ' . $replyToName . ' <' . $replyToEmail . '>',
        'Subject: ' . koduj_temat_utf8($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit'
    );

    $data = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $data = str_replace("\n.", "\n..", $data);

    fwrite($socket, $data . "\r\n.\r\n");
    $sentResp = smtp_czytaj_odpowiedz($socket);
    $ok = ((int) substr($sentResp, 0, 3) === 250);

    @smtp_wyslij_komende($socket, 'QUIT', array(221));
    fclose($socket);

    return $ok;
}

function wyslij_mail_fallback($replyToEmail, $subject, $body)
{
    $odbiorca = getenv('EDUSCIEZKA_CONTACT_TO') ?: 'edusciezka@yahoo.com';
    $from = getenv('EDUSCIEZKA_MAIL_FROM') ?: 'no-reply@edusciezka.local';

    $naglowki = array(
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: EduSciezka <' . $from . '>',
        'Reply-To: ' . $replyToEmail,
        'X-Mailer: PHP/' . phpversion()
    );

    return @mail($odbiorca, koduj_temat_utf8($subject), $body, implode("\r\n", $naglowki));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $imie_nazwisko = trim((string) ($_POST['iminaz'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $tresc = trim((string) ($_POST['tresc'] ?? ''));
    $website = trim((string) ($_POST['website'] ?? ''));

    $bledy = array();

    if ($imie_nazwisko === '' || $email === '' || $tresc === '') {
        $bledy[] = 'Wypelnij wszystkie wymagane pola.';
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $bledy[] = 'Podaj poprawny adres e-mail.';
    }

    if (preg_match('/[\r\n]/', $imie_nazwisko . $email)) {
        $bledy[] = 'Wykryto niedozwolone znaki w danych kontaktowych.';
    }

    if (mb_strlen($tresc) < 10) {
        $bledy[] = 'Wiadomosc powinna miec minimum 10 znakow.';
    }

    if ($website !== '') {
        $bledy[] = 'Wiadomosc zostala odrzucona przez filtr antyspamowy.';
    }

    if (empty($bledy)) {
        $temat = '[EduSciezka] Nowa wiadomosc z formularza kontaktowego';

        $bezpieczne_imie = preg_replace('/\s+/u', ' ', strip_tags($imie_nazwisko));
        $bezpieczny_email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $bezpieczna_tresc = preg_replace('/\r\n|\r|\n/u', "\n", strip_tags($tresc));

        $wiadomosc = "Nowa wiadomosc z formularza kontaktowego EduSciezka\n\n";
        $wiadomosc .= "Imie i nazwisko: " . $bezpieczne_imie . "\n";
        $wiadomosc .= "Email: " . $bezpieczny_email . "\n\n";
        $wiadomosc .= "Tresc wiadomosci:\n" . $bezpieczna_tresc . "\n";

        $wyslano = wyslij_mail_smtp_yahoo($bezpieczny_email, $bezpieczne_imie, $temat, $wiadomosc);
        if (!$wyslano) {
            $wyslano = wyslij_mail_fallback($bezpieczny_email, $temat, $wiadomosc);
        }

        if ($wyslano) {
            $komunikat_typ = 'sukces';
            $komunikat_tresc = 'Dziekujemy! Wiadomosc zostala wyslana.';
            $imie_nazwisko = '';
            $email = '';
            $tresc = '';
        } else {
            $komunikat_typ = 'blad';
            $komunikat_tresc = 'Nie udalo sie wyslac wiadomosci. Sprobuj ponownie za chwile.';
        }
    } else {
        $komunikat_typ = 'blad';
        $komunikat_tresc = implode(' ', $bledy);
    }
}
?>
<!doctype html>
<html lang="pl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EduŚcieżka</title>
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../style/kontakt-style.css" />
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
        <div class="contact-info">
            <div class="contact-info-left">
                <img src="../img/logo.png" alt="Logo EduSciezka" class="contact-logo" />
                <h2>Kontakt</h2>
                <div class="line"></div>
                <p>Numer telefonu: <span>+48 000 000 000</span></p>
                <p>Adres e-mail: <span>edusciezka@yahoo.com</span></p>
            </div>
            <div class="contact-info-right">
                <h2>Napisz do nas</h2>
                <div class="line"></div>

                <?php if ($komunikat_tresc !== ''): ?>
                    <div
                        class="komunikat <?php echo $komunikat_typ === 'sukces' ? 'komunikat-sukces' : 'komunikat-blad'; ?>">
                        <?php echo htmlspecialchars($komunikat_tresc); ?>
                    </div>
                <?php endif; ?>

                <div class="contact-info-form">
                    <form action="kontakt.php" method="post" novalidate>
                        <div class="hp-wrap" aria-hidden="true">
                            <input type="text" name="website" id="website" tabindex="-1" autocomplete="off"
                                aria-label="Pole techniczne" />
                        </div>
                        <label for="iminaz">Imię i nazwisko <span>*</span></label><br />
                        <input type="text" name="iminaz" id="iminaz" placeholder="np. Jan Kowalski"
                            value="<?php echo htmlspecialchars($imie_nazwisko); ?>" required /><br />
                        <label for="email">Adres e-mail <span>*</span></label><br />
                        <input type="email" name="email" id="email" placeholder="np. jan@example.pl"
                            value="<?php echo htmlspecialchars($email); ?>" required /><br />
                        <label for="tresc">Wiadomość <span>*</span></label><br />
                        <textarea name="tresc" id="tresc" placeholder="Treść wiadomości.." rows="10"
                            required><?php echo htmlspecialchars($tresc); ?></textarea><br />
                        <input type="submit" name="btnsubmit" id="btnsubmit" value="Wyślij" />
                    </form>
                </div>
            </div>
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
                <li>
                    <a href="" style="color: #2a95ff"><svg xmlns="http://www.w3.org/2000/svg" stroke="currentColor"
                            fill="none" viewBox="0 0 24 24" id="Instagram-Logo-2--Streamline-Logos" height="30"
                            width="35">
                            <path stroke-linejoin="round" d="M18 6.5a0.5 0.5 0 0 1 0 -1" stroke-width="1"></path>
                            <path stroke-linejoin="round" d="M18 6.5a0.5 0.5 0 0 0 0 -1" stroke-width="1"></path>
                            <path stroke-linejoin="round" d="M7 12a5 5 0 1 0 10 0 5 5 0 1 0 -10 0" stroke-width="1">
                            </path>
                            <path d="M16.5 1.5h-9a6 6 0 0 0 -6 6v9a6 6 0 0 0 6 6h9a6 6 0 0 0 6 -6v-9a6 6 0 0 0 -6 -6Z"
                                stroke-width="1"></path>
                        </svg></a>
                </li>
                <li>
                    <a href="" style="color: #2a95ff"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24" id="Tiktok-Logo--Streamline-Logos" height="30"
                            width="35">
                            <path stroke-linejoin="round"
                                d="M16 1.5h-3.5V16c0 1.5 -1.5 3 -3 3s-3 -0.5 -3 -3c0 -2 1.899 -3.339 3.5 -3V9.5c-6.12 0 -7 5 -7 6.5s0.977 6.5 6.5 6.5c4.522 0 6.5 -3.5 6.5 -6v-8c1.146 1.018 2.922 1.357 5 1.5V6.5c-3.017 0 -5 -2.654 -5 -5Z"
                                stroke-width="1"></path>
                        </svg></a>
                </li>
                <li>
                    <a href="" style="color: #2a95ff"><svg xmlns="http://www.w3.org/2000/svg" fill="none"
                            stroke="currentColor" viewBox="0 0 48 48" id="Contact-Phonebook--Streamline-Plump"
                            height="30" width="35">
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
                                <path id="Subtract_2" stroke-linejoin="round"
                                    d="M7.109 32a261.062 261.062 0 0 1 -0.095 -5" stroke-width="2"></path>
                                <path id="Subtract_3" stroke-linejoin="round"
                                    d="M7.014 21c0.017 -1.824 0.05 -3.488 0.095 -5" stroke-width="2"></path>
                                <path id="Union" stroke-linejoin="round"
                                    d="M30.209 25.071a6 6 0 1 0 -6.438 -0.014c-2.862 1.034 -5.054 3.416 -5.769 6.356 -0.312 1.28 0.438 2.498 1.713 2.834 1.425 0.376 3.732 0.753 7.255 0.753 3.524 0 5.83 -0.377 7.255 -0.753 1.275 -0.336 2.025 -1.553 1.713 -2.834 -0.712 -2.927 -2.887 -5.3 -5.73 -6.342Z"
                                    stroke-width="2"></path>
                            </g>
                        </svg>
                    </a>
                </li>
            </ul>
        </div>
    </footer>
</body>

</html>