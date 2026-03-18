<?php

$polaczenie = mysqli_connect("localhost","root","","student_planner");

if (!$polaczenie) {
    die("Blad polaczenia: " . mysqli_connect_error());
}
mysqli_set_charset($polaczenie, "utf8");

session_start();
?>
