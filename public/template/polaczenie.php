<?php

$polaczenie = mysqli_connect("localhost","nikolospl_edusciezka","dmjf2eeo0opayrar","nikolospl_edusciezka");

if (!$polaczenie) {
    die("Blad polaczenia: " . mysqli_connect_error());
}
mysqli_set_charset($polaczenie, "utf8mb4");

session_start();
?>
