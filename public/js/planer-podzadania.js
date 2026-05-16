document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.toggle-podzadania').forEach(function (btn) {
    btn.addEventListener('click', function (ev) {
      var self = ev.currentTarget || btn;
      var tgtId = self.getAttribute && self.getAttribute('data-target');
      var tgt = tgtId ? document.getElementById(tgtId) : null;
      if (!tgt) return;
      tgt.classList.toggle('collapsed');
    });
  });

  document.body.addEventListener('click', function (e) {
    var el =
      e.target && e.target.closest
        ? e.target.closest('.ajax-sub-action')
        : null;
    if (!el) return;
    var action = el.getAttribute('data-action');
    var id = el.getAttribute('data-id');
    var url = el.getAttribute('href');

    if (action === 'edit') {
      if (!url) return;
      fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (r) {
          return r.text().then(function (t) {
            try {
              return JSON.parse(t);
            } catch (e) {
              return null;
            }
          });
        })
        .then(function (j) {
          if (j && j.ok && j.html) {
            var node = document.querySelector(
              '.ajax-sub-action[data-action="edit"][data-id="' + id + '"]',
            );
            if (node) {
              var container = node.closest('.podzadanie-item');
              if (container) {
                var wrapper = document.createElement('div');
                wrapper.className = 'podzadanie-edytuj';
                wrapper.innerHTML = j.html;
                container.parentNode.insertBefore(
                  wrapper,
                  container.nextSibling,
                );
              }
            }
          }
        })
        .catch(function () {});
      return;
    }

    e.preventDefault();
    if (!id) return;

    if (action === 'status') {
      if (!url) return;
      fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (r) {
          return r.text().then(function (t) {
            try {
              return JSON.parse(t);
            } catch (e) {
              return null;
            }
          });
        })
        .then(function (j) {
          if (j && j.ok) {
            var node = document.querySelector(
              'a.ajax-sub-action[data-id="' + id + '"]',
            );
            if (node) {
              var container = node.closest('.podzadanie-item');
              if (container) {
                var statusSpan = container.querySelector('.podzadanie-status');
                if (statusSpan) {
                  statusSpan.textContent = ' | ' + (j.label || j.status);
                  statusSpan.className =
                    'podzadanie-status sub-status-' +
                    (j.status || '').replace(/[^a-z0-9_\-]/gi, '');
                }
              }
            }
          }
        })
        .catch(function () {});
    } else if (action === 'delete') {
      if (!confirm('Usunąć to podzadanie?')) return;
      if (!url) return;
      fetch(url + (url.indexOf('?') === -1 ? '?' : '&') + 'ajax=1', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then(function (r) {
          return r.text().then(function (t) {
            try {
              return JSON.parse(t);
            } catch (e) {
              return null;
            }
          });
        })
        .then(function (j) {
          if (j && j.ok) {
            var node = document.querySelector(
              '.ajax-sub-action[data-action="delete"][data-id="' + id + '"]',
            );
            if (node) {
              var item = node.closest('.podzadanie-item');
              if (item) {
                var list = item.closest('.podzadania-lista');
                item.remove();
                if (list && list.previousElementSibling) {
                  var header =
                    list.previousElementSibling.querySelector('.pod-count');
                  if (header) {
                    var parts = header.textContent.split(':');
                    var num = parseInt(parts[1]) || 0;
                    header.textContent = 'Podzadań: ' + Math.max(0, num - 1);
                  }
                }
              }
            }
          }
        })
        .catch(function () {});
    }
  });

  document.body.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || !form.classList || !form.classList.contains('ajax-sub-form'))
      return;
    e.preventDefault();
    var data = new FormData(form);
    data.set('ajax', '1');
    fetch(form.action || window.location.href, {
      method: 'POST',
      body: data,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) {
        return r.text().then(function (t) {
          try {
            return JSON.parse(t);
          } catch (e) {
            return null;
          }
        });
      })
      .then(function (j) {
        if (!j) return;
        if (j.ok) {
          var newId = j.id;
          var planId = form.querySelector('input[name="plan_id"]')
            ? form.querySelector('input[name="plan_id"]').value
            : null;
          var title = form.querySelector('input[name="s_tytul"]')
            ? form.querySelector('input[name="s_tytul"]').value
            : '';
          var deadline = form.querySelector('input[name="s_deadline"]')
            ? form.querySelector('input[name="s_deadline"]').value
            : '';
          var status = form.querySelector('select[name="s_status"]')
            ? form.querySelector('select[name="s_status"]').value
            : 'plan';
          if (newId && planId) {
            var list = document.getElementById('pid-' + planId);
            if (list) {
              var div = document.createElement('div');
              div.className = 'podzadanie-item';
              div.innerHTML =
                '<div class="podzadanie-tytul">' +
                escapeHtml(title) +
                ' <span class="podzadanie-deadline"> | termin: ' +
                escapeHtml(formatDate(deadline)) +
                '</span> <span class="podzadanie-status sub-status-' +
                escapeHtml(status) +
                '"> | ' +
                escapeHtml(status) +
                '</span></div>' +
                '<div class="podzadanie-akcje">' +
                '<a class="akcja ajax-sub-action" data-action="edit" data-id="' +
                newId +
                '" href="?sub_edytuj=' +
                newId +
                '">Edytuj</a>' +
                '<a class="akcja ajax-sub-action" data-action="status" data-id="' +
                newId +
                '" data-status="w_toku" href="?sub_zmien_status=' +
                newId +
                '&status=w_toku">W toku</a>' +
                '<a class="akcja ajax-sub-action" data-action="status" data-id="' +
                newId +
                '" data-status="zakonczone" href="?sub_zmien_status=' +
                newId +
                '&status=zakonczone">Zakończ</a>' +
                '<a class="akcja ajax-sub-action" data-action="delete" data-id="' +
                newId +
                '" href="?sub_usun=' +
                newId +
                '">Usuń</a>' +
                '</div>';
              list.appendChild(div);
              var header =
                list.previousElementSibling &&
                list.previousElementSibling.querySelector('.pod-count');
              if (header) {
                var parts = header.textContent.split(':');
                var num = parseInt(parts[1]) || 0;
                header.textContent = 'Podzadań: ' + (num + 1);
              }
            }
          }
          if (form.closest('.podzadanie-edytuj')) {
            form.closest('.podzadanie-edytuj').remove();
          } else {
            form.reset();
          }
        } else {
          alert('Błąd podczas zapisu podzadania');
        }
      })
      .catch(function () {
        alert('Błąd połączenia');
      });
  });

  document.body.addEventListener('click', function (e) {
    var btn =
      e.target && e.target.closest ? e.target.closest('.add-podzadanie') : null;
    if (!btn) return;
    var pid = btn.getAttribute('data-pid');
    if (!pid) return;
    var list = document.getElementById('pid-' + pid);
    if (!list) return;
    var wrapper = document.createElement('div');
    wrapper.className = 'podzadanie-edytuj';
    wrapper.innerHTML =
      '<form method="POST" class="ajax-sub-form">' +
      '<input type="hidden" name="ajax" value="1">' +
      (function () {
        var node = document.querySelector('#csrf-root input[name]');
        return node ? node.outerHTML : '';
      })() +
      '<input type="hidden" name="plan_id" value="' +
      pid +
      '">' +
      '<div><input type="text" name="s_tytul" placeholder="Tytuł podzadania" required></div>' +
      '<div><input type="date" name="s_deadline"></div>' +
      '<div><select name="s_status"><option value="plan">Plan</option><option value="w_toku">W toku</option><option value="zakonczone">Zakonczone</option></select></div>' +
      '<div><textarea name="s_opis" rows="2" placeholder="Opis (opcjonalnie)"></textarea></div>' +
      '<div><button type="submit" name="dodaj_podzadanie" class="btn-dodaj">Dodaj</button> <button type="button" class="btn-drugi cancel-add">Anuluj</button></div>' +
      '</form>';
    list.insertBefore(wrapper, list.firstChild);
    var cancelBtn = wrapper.querySelector('.cancel-add');
    if (cancelBtn)
      cancelBtn.addEventListener('click', function () {
        wrapper.remove();
      });
  });

  function enableDragDrop() {
    document.querySelectorAll('.podzadania-lista').forEach(function (list) {
      list.querySelectorAll('.podzadanie-item').forEach(function (it) {
        it.setAttribute('draggable', 'true');
        it.dataset.sid = it.querySelector('.ajax-sub-action')
          ? it.querySelector('.ajax-sub-action').getAttribute('data-id')
          : '';
      });

      list.addEventListener('dragstart', function (e) {
        var it =
          e.target && e.target.closest
            ? e.target.closest('.podzadanie-item')
            : null;
        if (!it) return;
        var aid = it.querySelector('.ajax-sub-action');
        var aidVal = aid ? aid.getAttribute('data-id') : '';
        if (aidVal) {
          e.dataTransfer.setData('text/plain', aidVal);
          e.dataTransfer.effectAllowed = 'move';
        }
      });

      list.addEventListener('dragover', function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
      });

      list.addEventListener('drop', function (e) {
        e.preventDefault();
        var id = e.dataTransfer.getData('text/plain');
        var target =
          e.target && e.target.closest
            ? e.target.closest('.podzadanie-item')
            : null;
        var draggedAnchor = list.querySelector(
          '.ajax-sub-action[data-id="' + id + '"]',
        );
        var dragged = draggedAnchor
          ? draggedAnchor.closest('.podzadanie-item')
          : null;
        if (!dragged) return;
        if (target && target !== dragged) {
          list.insertBefore(dragged, target.nextSibling);
        }
        sendOrder(list);
      });
    });
  }

  function sendOrder(list) {
    var items = [];
    list
      .querySelectorAll('.podzadanie-item .ajax-sub-action[data-id]')
      .forEach(function (a) {
        items.push(a.getAttribute('data-id'));
      });
    if (items.length === 0) return;
    var planId = list.id.replace('pid-', '');
    var fd = new FormData();
    fd.append('action', 'reorder_subtasks');
    fd.append('plan_id', planId);
    items.forEach(function (v, i) {
      fd.append('items[]', v);
    });
    var csrf = document.querySelector('#csrf-root input');
    if (csrf) {
      fd.append(csrf.name, csrf.value);
    }
    fetch(window.location.pathname, {
      method: 'POST',
      body: fd,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then(function (r) {
        return r.text().then(function (t) {
          try {
            return JSON.parse(t);
          } catch (e) {
            return null;
          }
        });
      })
      .then(function (j) {
        if (j && j.ok) {
          console.log('Order saved');
        }
      })
      .catch(function () {
        console.log('Order save failed');
      });
  }

  enableDragDrop();

  var mo = new MutationObserver(function (mutations) {
    mutations.forEach(function (m) {
      if (m.type === 'childList' && m.addedNodes.length > 0) {
        enableDragDrop();
      }
    });
  });
  document.querySelectorAll('.podzadania-lista').forEach(function (el) {
    mo.observe(el, { childList: true, subtree: false });
  });

  function escapeHtml(s) {
    if (!s) return '';
    return s
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
  function formatDate(d) {
    if (!d) return '';
    try {
      var parts = d.split('-');
      return parts.length === 3
        ? parts[2] + '.' + parts[1] + '.' + parts[0]
        : d;
    } catch (e) {
      return d;
    }
  }
});
