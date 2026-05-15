const wydarzenia = (function () {
    const el = document.getElementById('kalendarz-data');
    try {
        return el ? JSON.parse(el.textContent || '[]') : [];
    } catch (e) {
        return [];
    }
})();

const nazwyMiesiecy = [
    'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec',
    'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
];

let widok = new Date();

function pad(v) { return String(v).padStart(2, '0'); }
function dataKlucz(y, m, d) { return y + '-' + pad(m + 1) + '-' + pad(d); }

function wydarzeniaDnia(klucz) {
    const fPlaner = document.getElementById('filtr-planer').checked;
    const fZadanie = document.getElementById('filtr-zadanie').checked;
    const fTermin = document.getElementById('filtr-termin').checked;
    const fZak = document.getElementById('filtr-zakonczone').checked;

    return wydarzenia.filter(function (ev) {
        if (ev.source === 'planer' && !fPlaner) return false;
        if (ev.source === 'zadanie' && !fZadanie) return false;
        if (ev.source === 'termin' && !fTermin) return false;
        if (!fZak && ev.status === 'zakonczone') return false;
        const start = ev.start;
        const end = ev.end || ev.start;
        return klucz >= start && klucz <= end;
    });
}

function renderKalendarz() {
    const rok = widok.getFullYear();
    const miesiac = widok.getMonth();

    const monthLabel = document.getElementById('monthLabel');
    if (monthLabel) monthLabel.textContent = nazwyMiesiecy[miesiac] + ' ' + rok;

    const grid = document.getElementById('calendarGrid');
    if (!grid) return;
    grid.innerHTML = '';

    ['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Niedż'].forEach(function (dzien) {
        const nag = document.createElement('div');
        nag.className = 'dow';
        nag.textContent = dzien;
        grid.appendChild(nag);
    });

    const pierwszy = new Date(rok, miesiac, 1);
    const przesuniecie = (pierwszy.getDay() + 6) % 7;
    const dniWmiesiacu = new Date(rok, miesiac + 1, 0).getDate();

    for (let i = 0; i < przesuniecie; i++) {
        const pusty = document.createElement('div');
        pusty.className = 'day empty';
        grid.appendChild(pusty);
    }

    const dzisiaj = new Date();
    const dzKlucz = dataKlucz(dzisiaj.getFullYear(), dzisiaj.getMonth(), dzisiaj.getDate());

    for (let d = 1; d <= dniWmiesiacu; d++) {
        const klucz = dataKlucz(rok, miesiac, d);
        const komorka = document.createElement('div');
        komorka.className = 'day';
        if (klucz === dzKlucz) komorka.className += ' today';

        const numer = document.createElement('div');
        numer.className = 'day-number';
        numer.textContent = d;
        komorka.appendChild(numer);

        const lista = document.createElement('div');
        lista.className = 'events';

        const evDnia = wydarzeniaDnia(klucz);
        evDnia.slice(0, 3).forEach(function (ev) {
            const e = document.createElement('div');
            e.className = 'event event-' + ev.source;
            e.textContent = ev.title;
            lista.appendChild(e);
        });

        if (evDnia.length > 3) {
            const wiecej = document.createElement('div');
            wiecej.className = 'more-events';
            wiecej.textContent = '+' + (evDnia.length - 3) + ' wiecej';
            lista.appendChild(wiecej);
        }

        komorka.appendChild(lista);
        grid.appendChild(komorka);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const prev = document.getElementById('prevMonth');
    const next = document.getElementById('nextMonth');
    if (prev) prev.addEventListener('click', function () { widok = new Date(widok.getFullYear(), widok.getMonth() - 1, 1); renderKalendarz(); });
    if (next) next.addEventListener('click', function () { widok = new Date(widok.getFullYear(), widok.getMonth() + 1, 1); renderKalendarz(); });

    ['filtr-planer', 'filtr-zadanie', 'filtr-termin', 'filtr-zakonczone'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', renderKalendarz);
    });

    renderKalendarz();
});
