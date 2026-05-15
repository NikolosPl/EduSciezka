function przelaczForm(id) {
  var el = document.getElementById(id);
  if (!el) return;
  if (el.className.indexOf('widoczny') === -1) {
    el.className = 'formularz-dodaj widoczny';
  } else {
    el.className = 'formularz-dodaj';
  }
}

window.przelaczForm = przelaczForm;
