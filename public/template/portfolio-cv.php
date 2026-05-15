<?php
require_once "polaczenie.php";

if (!isset($_SESSION['uzytkownik_id'])) {
    header("Location: logowanie.php");
    exit;
}

$uid = (int) $_SESSION['uzytkownik_id'];

$user_res = mysqli_query($polaczenie, "SELECT imie, nazwisko, email, data_rejestracji FROM uzytkownicy WHERE id = $uid LIMIT 1");
$user = $user_res && mysqli_num_rows($user_res) === 1 ? mysqli_fetch_assoc($user_res) : array(
    'imie' => $_SESSION['imie'] ?? 'Uzytkownik',
    'nazwisko' => '',
    'email' => '',
    'data_rejestracji' => date('Y-m-d H:i:s')
);

$planer = mysqli_query($polaczenie, "SELECT etap, tytul, opis, data_start, data_koniec, status FROM planer_przyszlosci WHERE uzytkownik_id = $uid ORDER BY data_start ASC");
$projekty = mysqli_query($polaczenie, "SELECT nazwa, opis, deadline, status, priorytet FROM projekty WHERE uzytkownik_id = $uid ORDER BY utworzony_o DESC LIMIT 8");
$logi = mysqli_query($polaczenie, "SELECT tytul, opis, data_osiagniecia, punkty_xp FROM logi_sukcesow WHERE uzytkownik_id = $uid ORDER BY data_osiagniecia DESC LIMIT 10");
$umiejetnosci = mysqli_query($polaczenie, "SELECT nazwa, kategoria, poziom FROM umiejetnosci WHERE uzytkownik_id = $uid ORDER BY poziom DESC, nazwa ASC LIMIT 20");

$etykiety_etapow = array(
    'szkola_koniec' => 'Skonczenie szkoly',
    'studia' => 'Studia',
    'brak_studiow' => 'Brak studiow',
    'praca' => 'Praca',
    'certyfikat_szkolenie' => 'Certyfikat / szkolenie'
);

$drukuj = isset($_GET['print']) && $_GET['print'] === '1';
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <title>EduSciezka - Portfolio i CV</title>
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../style/portfolio-cv-style.css">
</head>

<body>
    <div class="page">
        <div class="top">
            <div>
                <h1>Portfolio i CV</h1>
                <div><?php echo htmlspecialchars(trim($user['imie'] . ' ' . $user['nazwisko'])); ?></div>
                <small><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <div class="actions">
                <a class="btn" href="planer-przyszlosci.php">Wróć do Planera Przyszłości</a>
                <a class="btn" href="portfolio-cv.php?print=1">Drukuj / PDF</a>
            </div>
        </div>

        <div class="content">
            <div class="section">
                <h2>Profil</h2>
                <div class="section-body meta">
                    <div><strong>Imie i
                            nazwisko</strong><br><?php echo htmlspecialchars(trim($user['imie'] . ' ' . $user['nazwisko'])); ?>
                    </div>
                    <div><strong>Email</strong><br><?php echo htmlspecialchars($user['email']); ?></div>
                    <div><strong>Aktywny
                            od</strong><br><?php echo htmlspecialchars(date('d.m.Y', strtotime($user['data_rejestracji']))); ?>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2>Plan rozwoju</h2>
                <div class="section-body">
                    <?php if (!$planer || mysqli_num_rows($planer) === 0): ?>
                        <div class="muted">Brak etapow w planerze.</div>
                    <?php else: ?>
                        <table>
                            <tr>
                                <th>Etap</th>
                                <th>Tytul</th>
                                <th>Zakres dat</th>
                                <th>Status</th>
                            </tr>
                            <?php while ($p = mysqli_fetch_assoc($planer)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($etykiety_etapow[$p['etap']] ?? $p['etap']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($p['tytul']); ?></strong>
                                        <?php if (!empty($p['opis'])): ?>
                                            <br><span class="muted"><?php echo htmlspecialchars($p['opis']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars(date('d.m.Y', strtotime($p['data_start']))); ?>
                                        <?php if (!empty($p['data_koniec'])): ?>
                                            - <?php echo htmlspecialchars(date('d.m.Y', strtotime($p['data_koniec']))); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($p['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>Projekty</h2>
                <div class="section-body">
                    <?php if (!$projekty || mysqli_num_rows($projekty) === 0): ?>
                        <div class="muted">Brak projektow do wyswietlenia.</div>
                    <?php else: ?>
                        <table>
                            <tr>
                                <th>Nazwa</th>
                                <th>Opis</th>
                                <th>Deadline</th>
                                <th>Status</th>
                            </tr>
                            <?php while ($pr = mysqli_fetch_assoc($projekty)): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pr['nazwa']); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($pr['opis'] ?: '-')); ?></td>
                                    <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($pr['deadline']))); ?></td>
                                    <td><?php echo htmlspecialchars($pr['status']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>Osiagniecia</h2>
                <div class="section-body">
                    <?php if (!$logi || mysqli_num_rows($logi) === 0): ?>
                        <div class="muted">Brak osiagniec w logu.</div>
                    <?php else: ?>
                        <table>
                            <tr>
                                <th>Tytul</th>
                                <th>Data</th>
                                <th>XP</th>
                            </tr>
                            <?php while ($l = mysqli_fetch_assoc($logi)): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($l['tytul']); ?></strong>
                                        <?php if (!empty($l['opis'])): ?>
                                            <br><span class="muted"><?php echo htmlspecialchars($l['opis']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d.m.Y', strtotime($l['data_osiagniecia']))); ?></td>
                                    <td><?php echo (int) $l['punkty_xp']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <h2>Umiejetnosci</h2>
                <div class="section-body">
                    <?php if (!$umiejetnosci || mysqli_num_rows($umiejetnosci) === 0): ?>
                        <div class="muted">Brak wpisanych umiejetnosci.</div>
                    <?php else: ?>
                        <div class="skill-list">
                            <?php while ($u = mysqli_fetch_assoc($umiejetnosci)): ?>
                                <span class="skill"><?php echo htmlspecialchars($u['nazwa']); ?> (poziom
                                    <?php echo (int) $u['poziom']; ?>)</span>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/portfolio-cv.js"></script>
</body>

</html>