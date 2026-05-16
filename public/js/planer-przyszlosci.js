document.addEventListener('DOMContentLoaded', function () {
  var STATUS_LABELS = {
    plan: 'Plan',
    w_toku: 'W toku',
    zakonczone: 'Zakonczone',
  };

  var ETAP_LABELS = {
    szkola_koniec: 'Skonczenie szkoly',
    studia: 'Studia',
    brak_studiow: 'Brak studiow',
    praca: 'Praca',
    certyfikat_szkolenie: 'Certyfikat / szkolenie',
  };

  var EMPTY_MESSAGE =
    'Brak etapów. Dodaj pierwszy krok i rozpisz swoją ścieżkę.';
  var form = document.getElementById('planer-form');
  var container = document.getElementById('planer-plans-container');
  var cancelButton = document.getElementById('anuluj-edycje-plan');

  if (cancelButton) {
    cancelButton.addEventListener('click', function () {
      resetFormToAddMode();
    });
  }

  document.body.addEventListener('click', function (event) {
    var actionLink =
      event.target && event.target.closest
        ? event.target.closest(
            '.akcja-edytuj, .akcja-wtoku, .akcja-zakoncz, .akcja-plan, .akcja-usun',
          )
        : null;
    if (!actionLink) return;
    if (event.defaultPrevented) return;

    var row = actionLink.closest('tr[data-plan-id]');
    if (!row) return;

    if (actionLink.classList.contains('akcja-edytuj')) {
      event.preventDefault();
      fillFormFromRow(row);
      return;
    }

    event.preventDefault();
    if (actionLink.classList.contains('akcja-usun')) {
      showConfirmModal('Usunąć ten etap?', function () {
        sendStageAction(actionLink.getAttribute('href'), 'delete');
      });
      return;
    }

    sendStageAction(actionLink.getAttribute('href'), 'status');
  });

  if (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      submitPlanForm();
    });

    if (
      form.querySelector('input[name="plan_id"]') &&
      form.querySelector('input[name="plan_id"]').value
    ) {
      refreshFormButtonLabel();
      if (window.location.search.indexOf('edytuj=') === -1) {
        updateFormMode(true);
      }
    }
  }

  function getCsrfToken() {
    var tokenInput = document.querySelector('input[name="csrf_token"]');
    return tokenInput ? tokenInput.value : '';
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function sanitizeClass(value) {
    return String(value == null ? '' : value).replace(/[^a-z0-9_\-]/gi, '');
  }

  function formatDate(value) {
    if (!value) return '';
    var parts = String(value).split('-');
    return parts.length === 3
      ? parts[2] + '.' + parts[1] + '.' + parts[0]
      : String(value);
  }

  function buildActionUrl(query) {
    var base = window.location.pathname;
    var token = getCsrfToken();
    var suffix = 'csrf_token=' + encodeURIComponent(token);
    return base + '?' + query + (query ? '&' : '') + suffix;
  }

  function addAjaxParam(url) {
    if (!url) return url;
    return url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1';
  }

  function parseResponseText(text) {
    try {
      return JSON.parse(text);
    } catch (error) {
      return null;
    }
  }

  function buildPlanRow(plan) {
    var id = String(plan.id == null ? '' : plan.id);
    var etap = String(plan.etap || '');
    var title = String(plan.tytul || '');
    var opis = String(plan.opis || '');
    var dataStart = String(plan.data_start || '');
    var dataKoniec = String(plan.data_koniec || '');
    var status = String(plan.status || 'plan');
    var etapLabel = ETAP_LABELS[etap] || etap;
    var statusLabel = STATUS_LABELS[status] || status;
    var row = '';

    row += '<tr data-plan-id="' + escapeHtml(id) + '"';
    row += ' data-etap="' + escapeHtml(etap) + '"';
    row += ' data-tytul="' + escapeHtml(title) + '"';
    row += ' data-opis="' + escapeHtml(opis) + '"';
    row += ' data-data-start="' + escapeHtml(dataStart) + '"';
    row += ' data-data-koniec="' + escapeHtml(dataKoniec) + '"';
    row += ' data-status="' + escapeHtml(status) + '">';
    row +=
      '<td><span class="etap-badge etap-' +
      sanitizeClass(etap) +
      '">' +
      escapeHtml(etapLabel) +
      '</span></td>';
    row += '<td><strong>' + escapeHtml(title) + '</strong>';
    if (opis) {
      row += '<br><small class="opis">' + escapeHtml(opis) + '</small>';
    }
    row += '</td>';
    row += '<td class="termin-komorka">' + escapeHtml(formatDate(dataStart));
    if (dataKoniec) {
      row += '<br>do ' + escapeHtml(formatDate(dataKoniec));
    }
    row += '</td>';
    row +=
      '<td><span class="status status-' +
      sanitizeClass(status) +
      '">' +
      escapeHtml(statusLabel) +
      '</span></td>';
    row += '<td>';
    row +=
      '<a class="akcja akcja-edytuj" href="' +
      escapeHtml(buildActionUrl('edytuj=' + encodeURIComponent(id))) +
      '">Edytuj</a>';
    if (status !== 'w_toku') {
      row +=
        '<a class="akcja akcja-wtoku" href="' +
        escapeHtml(
          buildActionUrl(
            'zmien_status=' + encodeURIComponent(id) + '&status=w_toku',
          ),
        ) +
        '">W toku</a>';
    }
    if (status !== 'zakonczone') {
      row +=
        '<a class="akcja akcja-zakoncz" href="' +
        escapeHtml(
          buildActionUrl(
            'zmien_status=' + encodeURIComponent(id) + '&status=zakonczone',
          ),
        ) +
        '">Zakończ</a>';
    }
    if (status !== 'plan') {
      row +=
        '<a class="akcja akcja-plan" href="' +
        escapeHtml(
          buildActionUrl(
            'zmien_status=' + encodeURIComponent(id) + '&status=plan',
          ),
        ) +
        '">Plan</a>';
    }
    row +=
      '<a class="akcja akcja-usun" href="' +
      escapeHtml(buildActionUrl('usun=' + encodeURIComponent(id))) +
      '">Usuń</a>';
    row += '</td></tr>';
    return row;
  }

  function getPlanTable() {
    if (!container) return null;
    var table = container.querySelector('table');
    if (!table) {
      container.innerHTML =
        '<table id="planer-plans-table"><tr><th>Etap</th><th>Tytuł</th><th>Termin</th><th>Status</th><th>Akcje</th></tr></table>';
      table = container.querySelector('table');
    }
    return table;
  }

  function setEmptyState() {
    if (!container) return;
    container.innerHTML =
      '<div class="pusta-tabela">' + EMPTY_MESSAGE + '</div>';
  }

  function sortPlanRows(table) {
    if (!table) return;
    var rows = Array.prototype.slice.call(
      table.querySelectorAll('tr[data-plan-id]'),
    );
    rows.sort(function (left, right) {
      var leftDate = left.getAttribute('data-data-start') || '';
      var rightDate = right.getAttribute('data-data-start') || '';
      if (leftDate === rightDate) {
        return (
          (parseInt(left.getAttribute('data-plan-id'), 10) || 0) -
          (parseInt(right.getAttribute('data-plan-id'), 10) || 0)
        );
      }
      return leftDate < rightDate ? -1 : 1;
    });

    rows.forEach(function (row) {
      table.appendChild(row);
    });
  }

  function upsertPlanRow(plan) {
    if (!plan || !plan.id) return;
    var table = getPlanTable();
    if (!table) return;

    var existing = table.querySelector(
      'tr[data-plan-id="' + String(plan.id) + '"]',
    );
    var rowHtml = buildPlanRow(plan);
    if (existing) {
      existing.outerHTML = rowHtml;
    } else {
      table.insertAdjacentHTML('beforeend', rowHtml);
    }

    sortPlanRows(table);
  }

  function removePlanRow(planId) {
    if (!container) return;
    var table = container.querySelector('table');
    if (!table) return;

    var existing = table.querySelector(
      'tr[data-plan-id="' + String(planId) + '"]',
    );
    if (existing) {
      existing.remove();
    }

    if (!table.querySelector('tr[data-plan-id]')) {
      setEmptyState();
    }
  }

  function refreshFormButtonLabel() {
    if (!form) return;
    var submitButton = form.querySelector('button[type="submit"]');
    if (!submitButton) return;
    submitButton.textContent = form.querySelector('input[name="plan_id"]').value
      ? 'Zapisz zmiany'
      : 'Dodaj etap';
  }

  function updateFormMode(isEditMode) {
    if (!form) return;
    var cancel = document.getElementById('anuluj-edycje-plan');
    if (cancel) {
      cancel.classList.toggle('is-hidden', !isEditMode);
    }
    refreshFormButtonLabel();
  }

  function resetFormToAddMode() {
    if (!form) return;
    form.reset();
    var planIdField = form.querySelector('input[name="plan_id"]');
    if (planIdField) {
      planIdField.value = '';
    }
    updateFormMode(false);
  }

  function fillFormFromRow(row) {
    if (!form || !row) return;
    var planIdField = form.querySelector('input[name="plan_id"]');
    var etapField = form.querySelector('select[name="etap"]');
    var titleField = form.querySelector('input[name="tytul"]');
    var startField = form.querySelector('input[name="data_start"]');
    var endField = form.querySelector('input[name="data_koniec"]');
    var opisField = form.querySelector('textarea[name="opis"]');

    if (planIdField) planIdField.value = row.getAttribute('data-plan-id') || '';
    if (etapField)
      etapField.value = row.getAttribute('data-etap') || etapField.value;
    if (titleField) titleField.value = row.getAttribute('data-tytul') || '';
    if (startField)
      startField.value = row.getAttribute('data-data-start') || '';
    if (endField) endField.value = row.getAttribute('data-data-koniec') || '';
    if (opisField) opisField.value = row.getAttribute('data-opis') || '';

    updateFormMode(true);
  }

  function sendStageAction(url, actionType) {
    if (!url) return;
    fetch(addAjaxParam(url), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (text) {
        var payload = parseResponseText(text);
        if (!payload || !payload.ok) {
          alert(
            payload && payload.message
              ? payload.message
              : 'Nie udało się wykonać akcji.',
          );
          return;
        }

        if (actionType === 'delete') {
          removePlanRow(payload.id);
          return;
        }

        if (payload.plan) {
          upsertPlanRow(payload.plan);
        }
      })
      .catch(function () {
        alert('Błąd połączenia');
      });
  }

  function submitPlanForm() {
    if (!form) return;
    var data = new FormData(form);
    data.set('ajax', '1');
    data.set(
      'plan_action',
      form.querySelector('input[name="plan_id"]').value ? 'edit' : 'add',
    );

    fetch(window.location.pathname, {
      method: 'POST',
      body: data,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (response) {
        return response.text();
      })
      .then(function (text) {
        var payload = parseResponseText(text);
        if (!payload || !payload.ok) {
          alert(
            payload && payload.message
              ? payload.message
              : 'Błąd podczas zapisu etapu.',
          );
          return;
        }

        if (payload.plan) {
          upsertPlanRow(payload.plan);
        }
        resetFormToAddMode();
      })
      .catch(function () {
        alert('Błąd połączenia');
      });
  }

  function showConfirmModal(message, onConfirm) {
    var modal = document.getElementById('confirm-modal');
    if (!modal) {
      console.warn('Confirm modal not found');
      if (confirm(message)) {
        onConfirm && onConfirm();
      }
      return;
    }
    var msg = modal.querySelector('.confirm-message');
    var ok = modal.querySelector('.confirm-ok');
    var cancel = modal.querySelector('.confirm-cancel');
    if (msg) msg.textContent = message;

    try {
      var bsModal = new bootstrap.Modal(modal);
    } catch (err) {
      if (confirm(message)) {
        onConfirm && onConfirm();
      }
      return;
    }

    function cleanup() {
      ok.removeEventListener('click', onOk);
      cancel.removeEventListener('click', onCancel);
      modal.removeEventListener('hidden.bs.modal', onHidden);
    }

    function onOk(e) {
      e && e.preventDefault();
      bsModal.hide();
      cleanup();
      onConfirm && onConfirm();
    }

    function onCancel(e) {
      e && e.preventDefault();
      bsModal.hide();
    }

    function onHidden() {
      cleanup();
    }

    ok.addEventListener('click', onOk);
    cancel.addEventListener('click', onCancel);
    modal.addEventListener('hidden.bs.modal', onHidden);
    bsModal.show();
  }
});
