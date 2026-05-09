function oczyscTrescOgloszenia(tekst) {
  let wynik = String(tekst || '');
  wynik = wynik.replace(/<\?(?:php)?[\s\S]*?\?>/giu, '');

  const markery = [
    'if (isset($_',
    '$_GET[',
    '$_POST[',
    'mysqli_',
    'header("Location:',
    'require_once ',
    '<?php',
  ];

  let pierwszaPozycja = -1;
  for (const marker of markery) {
    const pozycja = wynik.indexOf(marker);
    if (
      pozycja !== -1 &&
      (pierwszaPozycja === -1 || pozycja < pierwszaPozycja)
    ) {
      pierwszaPozycja = pozycja;
    }
  }

  if (pierwszaPozycja !== -1) {
    wynik = wynik.slice(0, pierwszaPozycja);
  }

  wynik = wynik.replace(/<[^>]*>/g, ' ');
  wynik = wynik.replace(/\s+/g, ' ').trim();
  return wynik;
}

const poleTytul = document.getElementById('ogloszenie-tytul');
const poleTresc = document.getElementById('ogloszenie-tresc');
const poleFoto = document.getElementById('ogloszenie-foto');

const podgladTytul = document.getElementById('podglad-tytul');
const podgladSkrot = document.getElementById('podglad-skrot');
const podgladTresc = document.getElementById('podglad-tresc');
const podgladZnaki = document.getElementById('podglad-znaki');
const podgladFoto = document.getElementById('podglad-foto');

function odswiezPodglad() {
  const tytul = (poleTytul.value || '').trim();
  const trescCzysta = oczyscTrescOgloszenia(poleTresc.value || '');

  podgladTytul.textContent = tytul || 'Tytul ogloszenia';
  podgladSkrot.textContent = trescCzysta
    ? trescCzysta.slice(0, 140)
    : 'Krotki opis pojawi sie tutaj (maks. 140 znakow).';
  podgladTresc.textContent =
    trescCzysta || 'Tresc ogloszenia po publikacji pojawi sie tutaj.';
  podgladZnaki.textContent = trescCzysta.length + ' znakow';
}

function odswiezPodgladZdjecia() {
  const plik = poleFoto.files && poleFoto.files[0];
  if (!plik) {
    podgladFoto.src = '../img/prototyp-zdjecia.png';
    return;
  }

  const reader = new FileReader();
  reader.onload = function (e) {
    podgladFoto.src = e.target.result;
  };
  reader.readAsDataURL(plik);
}

if (poleTytul && poleTresc && poleFoto) {
  poleTytul.addEventListener('input', odswiezPodglad);
  poleTresc.addEventListener('input', odswiezPodglad);
  poleFoto.addEventListener('change', odswiezPodgladZdjecia);
  odswiezPodglad();
}
