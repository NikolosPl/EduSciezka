document.addEventListener('DOMContentLoaded', function () {
  var openBtn = document.getElementById('open-calendar');
  var modal = document.getElementById('calendar-modal');
  var closeBtn = document.getElementById('close-calendar');
  var taskList = document.getElementById('calendar-task-list');
  var grid = document.getElementById('calendar-grid');
  var viewSelect = document.getElementById('cal-view');
  var prevBtn = document.getElementById('cal-prev');
  var nextBtn = document.getElementById('cal-next');

  var currentDate = new Date();
  var viewMode = 'month';
  var assignFormModal = document.getElementById('assign-form-modal');
  var assignForm = document.getElementById('assign-form');
  var assignZid = document.getElementById('assign-zid');
  var assignDateInput = document.getElementById('assign-date');
  var assignTime = document.getElementById('assign-time');
  var assignField = document.getElementById('assign-field');
  var assignCancel = document.getElementById('assign-cancel');
  var assignUndo = document.getElementById('assign-undo');
  var activeDropCell = null;

  if (!openBtn || !modal || !taskList || !grid) return;

  function showModal(el) {
    if (!el) return;
    el.style.display =
      el.style.display || (el.id === 'calendar-modal' ? 'flex' : 'block');
    el.setAttribute('aria-hidden', 'false');
    window.requestAnimationFrame(function () {
      el.classList.add('open');
    });
  }
  function hideModal(el) {
    if (!el) return;
    el.classList.remove('open');
    el.setAttribute('aria-hidden', 'true');
    setTimeout(function () {
      try {
        el.style.display = 'none';
      } catch (e) {}
    }, 260);
  }

  function openModal() {
    buildTaskList();
    currentDate = new Date();
    viewMode = viewSelect ? viewSelect.value : 'month';
    renderCalendar();
    showModal(modal);
  }
  function closeModal() {
    hideModal(modal);
  }

  openBtn.addEventListener('click', openModal);
  closeBtn.addEventListener('click', closeModal);

  function buildTaskList() {
    taskList.innerHTML = '';
    document.querySelectorAll('tr[data-zid]').forEach(function (tr) {
      var zid = tr.getAttribute('data-zid');
      var title = tr.querySelector('strong')
        ? tr.querySelector('strong').textContent
        : 'Zadanie';
      var node = document.createElement('div');
      node.className = 'cal-task';
      node.draggable = true;
      node.dataset.zid = zid;
      node.textContent = title;
      node.addEventListener('dragstart', function (e) {
        e.dataTransfer.setData('text/plain', zid);
        e.dataTransfer.effectAllowed = 'move';
      });
      taskList.appendChild(node);
    });
  }

  function renderCalendar() {
    if (viewMode === 'month') buildMonthGrid(currentDate);
    else if (viewMode === 'week') buildWeekGrid(currentDate);
    else buildDayView(currentDate);
  }

  function buildMonthGrid(date) {
    grid.innerHTML = '';
    var year = date.getFullYear();
    var month = date.getMonth();
    var first = new Date(year, month, 1);
    var startDay = first.getDay();
    var days = new Date(year, month + 1, 0).getDate();

    var header = document.createElement('div');
    header.className = 'cal-grid-header';
    for (var i = 0; i < 7; i++) {
      var d = ['Nd', 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So'][i];
      var h = document.createElement('div');
      h.className = 'cal-cell cal-cell-head';
      h.textContent = d;
      header.appendChild(h);
    }
    grid.appendChild(header);

    var total = startDay + days;
    var rows = Math.ceil(total / 7);
    var day = 1;
    for (var r = 0; r < rows; r++) {
      var row = document.createElement('div');
      row.className = 'cal-row';
      for (var c = 0; c < 7; c++) {
        var cell = document.createElement('div');
        cell.className = 'cal-cell';
        var cellIndex = r * 7 + c;
        if (cellIndex >= startDay && day <= days) {
          var y = year;
          var m = month;
          var dnum = day;
          var dd = new Date(y, m, dnum);
          cell.dataset.date =
            y +
            '-' +
            String(m + 1).padStart(2, '0') +
            '-' +
            String(dnum).padStart(2, '0');
          var top = document.createElement('div');
          top.className = 'cal-cell-date';
          top.textContent = dnum;
          cell.appendChild(top);
          cell.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            this.classList.add('cal-cell-over');
          });
          cell.addEventListener('dragleave', function (e) {
            this.classList.remove('cal-cell-over');
          });
          cell.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('cal-cell-over');
            var zid = e.dataTransfer.getData('text/plain');
            var date = this.dataset.date;
            if (!zid || !date) return;
            openAssignForm(zid, date, this);
          });
          day++;
        }
        row.appendChild(cell);
      }
      grid.appendChild(row);
    }
    renderAssignedTasks();
  }

  function buildWeekGrid(date) {
    grid.innerHTML = '';
    var start = new Date(date);
    var day = start.getDay();
    var diff = (day + 6) % 7;
    start.setDate(start.getDate() - diff);
    var header = document.createElement('div');
    header.className = 'cal-grid-header';
    for (var i = 0; i < 7; i++) {
      var d = new Date(start);
      d.setDate(start.getDate() + i);
      var h = document.createElement('div');
      h.className = 'cal-cell cal-cell-head';
      h.textContent = d.toLocaleDateString();
      header.appendChild(h);
    }
    grid.appendChild(header);
    var row = document.createElement('div');
    row.className = 'cal-row';
    for (var i = 0; i < 7; i++) {
      var d = new Date(start);
      d.setDate(start.getDate() + i);
      var cell = document.createElement('div');
      cell.className = 'cal-cell';
      cell.dataset.date =
        d.getFullYear() +
        '-' +
        String(d.getMonth() + 1).padStart(2, '0') +
        '-' +
        String(d.getDate()).padStart(2, '0');
      var top = document.createElement('div');
      top.className = 'cal-cell-date';
      top.textContent = d.getDate();
      cell.appendChild(top);
      cell.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        this.classList.add('cal-cell-over');
      });
      cell.addEventListener('dragleave', function (e) {
        this.classList.remove('cal-cell-over');
      });
      cell.addEventListener('drop', function (e) {
        e.preventDefault();
        this.classList.remove('cal-cell-over');
        var zid = e.dataTransfer.getData('text/plain');
        var date = this.dataset.date;
        if (!zid || !date) return;
        openAssignForm(zid, date, this);
      });
      row.appendChild(cell);
    }
    grid.appendChild(row);
    renderAssignedTasks();
  }

  function buildDayView(date) {
    grid.innerHTML = '';
    var d = new Date(date);
    var header = document.createElement('div');
    header.className = 'cal-grid-header';
    var h = document.createElement('div');
    h.className = 'cal-cell cal-cell-head';
    h.textContent = d.toLocaleDateString();
    header.appendChild(h);
    grid.appendChild(header);
    var row = document.createElement('div');
    row.className = 'cal-row';
    var cell = document.createElement('div');
    cell.className = 'cal-cell';
    cell.dataset.date =
      d.getFullYear() +
      '-' +
      String(d.getMonth() + 1).padStart(2, '0') +
      '-' +
      String(d.getDate()).padStart(2, '0');
    var top = document.createElement('div');
    top.className = 'cal-cell-date';
    top.textContent = d.getDate();
    cell.appendChild(top);
    cell.addEventListener('dragover', function (e) {
      e.preventDefault();
      e.dataTransfer.dropEffect = 'move';
      this.classList.add('cal-cell-over');
    });
    cell.addEventListener('dragleave', function (e) {
      this.classList.remove('cal-cell-over');
    });
    cell.addEventListener('drop', function (e) {
      e.preventDefault();
      this.classList.remove('cal-cell-over');
      var zid = e.dataTransfer.getData('text/plain');
      var date = this.dataset.date;
      if (!zid || !date) return;
      openAssignForm(zid, date, this);
    });
    row.appendChild(cell);
    grid.appendChild(row);
    renderAssignedTasks();
  }
  function openAssignForm(zid, date, cell) {
    if (!assignFormModal) return assignDateToTaskFallback(zid, date, cell);
    assignZid.value = zid;
    assignDateInput.value = date;
    assignTime.value = '12:00';
    assignField.value = 'data_start';
    showModal(assignFormModal);
    activeDropCell = cell;
  }

  function assignDateToTaskFallback(zid, date, cell) {
    var time = prompt(
      'Wprowadź godzinę (HH:MM) lub pozostaw puste dla całodniowego przypisania',
      '12:00',
    );
    var setDeadline = confirm(
      'Ustaw jako deadline? (OK = tak, Anuluj = data start)',
    );
    var field = setDeadline ? 'deadline' : 'data_start';
    var payloadDate = date;
    if (setDeadline) {
      var t = time && time.match(/^\d{1,2}:\d{2}$/) ? time : '12:00';
      payloadDate = date + ' ' + t + ':00';
    }
    doAssign(zid, payloadDate, field, cell);
  }

  function doAssign(zid, payloadDate, field, cell) {
    var fd = new FormData();
    fd.append('action', 'assign_date');
    fd.append('zid', zid);
    fd.append('date', payloadDate);
    fd.append('field', field);
    var csrf = document.querySelector('#csrf-root input');
    if (csrf) fd.append(csrf.name, csrf.value);
    fetch(window.location.pathname, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        if (j && j.ok) {
          var taskNode = document.createElement('div');
          taskNode.className = 'cal-cell-task';
          taskNode.dataset.zid = zid;
          var tr = document.querySelector('tr[data-zid="' + zid + '"]');
          var title =
            tr && tr.querySelector('strong')
              ? tr.querySelector('strong').textContent
              : 'Zadanie';
          taskNode.textContent = title;
          if (
            !cell ||
            !cell.classList ||
            !cell.classList.contains('cal-cell')
          ) {
            var dpart = payloadDate.split(' ')[0];
            cell = document.querySelector(
              '.cal-cell[data-date="' + dpart + '"]',
            );
          }
          if (cell) cell.appendChild(taskNode);
          if (tr) {
            if (field === 'deadline') {
              var deadlineTd = tr.querySelector('td:nth-child(6)');
              if (deadlineTd) deadlineTd.textContent = payloadDate;
            } else {
              var startTd = tr.querySelector('td:nth-child(5)');
              if (startTd) startTd.textContent = payloadDate;
            }
          }
        } else {
          alert('Błąd zapisu daty');
        }
      })
      .catch(function () {
        alert('Błąd połączenia');
      });
  }

  function renderAssignedTasks() {
    document.querySelectorAll('.cal-cell-task').forEach(function (el) {
      el.remove();
    });
    document.querySelectorAll('tr[data-zid]').forEach(function (tr) {
      var zid = tr.getAttribute('data-zid');
      var title = tr.querySelector('strong')
        ? tr.querySelector('strong').textContent
        : 'Zadanie';
      var startTd = tr.querySelector('td:nth-child(5)');
      if (startTd) {
        var v = startTd.textContent.trim();
        if (v && v !== '—') {
          var d = v.split(' ')[0];
          var cell = document.querySelector('.cal-cell[data-date="' + d + '"]');
          if (cell) {
            var node = document.createElement('div');
            node.className = 'cal-cell-task';
            node.dataset.zid = zid;
            node.textContent = title;
            cell.appendChild(node);
          }
        }
      }
      var dlTd = tr.querySelector('td:nth-child(6)');
      if (dlTd) {
        var v2 = dlTd.textContent.trim();
        if (v2 && v2 !== '—') {
          var d2 = v2.split(' ')[0];
          var cell2 = document.querySelector(
            '.cal-cell[data-date="' + d2 + '"]',
          );
          if (cell2) {
            var node2 = document.createElement('div');
            node2.className = 'cal-cell-task cal-deadline';
            node2.dataset.zid = zid;
            node2.textContent = title + ' (DL)';
            cell2.appendChild(node2);
          }
        }
      }
    });
  }

  if (assignForm) {
    assignForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var zid = assignZid.value;
      var date = assignDateInput.value;
      var time = assignTime.value;
      var field = assignField.value;
      var payload = date;
      if (field === 'deadline')
        payload = date + ' ' + (time || '12:00') + ':00';
      doAssign(
        zid,
        payload,
        field,
        activeDropCell || document.querySelector('[data-zid="' + zid + '"]'),
      );
      hideModal(assignFormModal);
      activeDropCell = null;
    });
    assignCancel.addEventListener('click', function () {
      hideModal(assignFormModal);
      activeDropCell = null;
    });
    assignUndo.addEventListener('click', function () {
      var zid = assignZid.value;
      var field = assignField.value;
      var fd = new FormData();
      fd.append('action', 'undo_assign');
      fd.append('zid', zid);
      fd.append('field', field);
      var csrf = document.querySelector('#csrf-root input');
      if (csrf) fd.append(csrf.name, csrf.value);
      fetch(window.location.pathname, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (j) {
          if (j && j.ok) {
            document
              .querySelectorAll('.cal-cell-task[data-zid="' + zid + '"]')
              .forEach(function (el) {
                el.remove();
              });
            var tr = document.querySelector('tr[data-zid="' + zid + '"]');
            if (tr) {
              if (field === 'deadline') {
                var dt = tr.querySelector('td:nth-child(6)');
                if (dt) dt.textContent = '—';
              } else {
                var st = tr.querySelector('td:nth-child(5)');
                if (st) st.textContent = '—';
              }
            }
          } else alert('Błąd cofnięcia');
        })
        .catch(function () {
          alert('Błąd połączenia');
        });
      hideModal(assignFormModal);
      activeDropCell = null;
    });
  }

  if (viewSelect) {
    viewSelect.addEventListener('change', function () {
      viewMode = this.value;
      renderCalendar();
    });
  }
  if (prevBtn)
    prevBtn.addEventListener('click', function () {
      shiftDate(-1);
    });
  if (nextBtn)
    nextBtn.addEventListener('click', function () {
      shiftDate(1);
    });

  function shiftDate(dir) {
    if (viewMode === 'month')
      currentDate.setMonth(currentDate.getMonth() + dir);
    else if (viewMode === 'week')
      currentDate.setDate(currentDate.getDate() + dir * 7);
    else currentDate.setDate(currentDate.getDate() + dir);
    renderCalendar();
  }
});
