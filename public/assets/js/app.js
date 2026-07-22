// vote arrows
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.vote-btn');
  if (!btn) return;
  const col = btn.closest('.vote-col');
  const res = await fetch('/api/vote', {
    method: 'POST', headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ story_id: +col.dataset.story, value: +btn.dataset.value }),
  });
  const data = await res.json();
  if (!data.ok) { if (res.status === 401) location.href = '/login'; return; }
  col.querySelector('.vote-score').textContent = data.score;
  col.querySelector('.vote-up').classList.toggle('vote-on', data.my_vote === 1);
  col.querySelector('.vote-down').classList.toggle('vote-on', data.my_vote === -1);
});

// company typeahead on /post
const nameInput = document.getElementById('company-name');
if (nameInput) {
  const box = document.getElementById('company-suggest');
  const domainInput = document.getElementById('company-domain');
  let timer;
  nameInput.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(async () => {
      const q = nameInput.value.trim();
      box.innerHTML = '';
      if (q.length < 2) return;
      const res = await fetch('/api/companies?q=' + encodeURIComponent(q));
      if (!res.ok) return;
      const { companies } = await res.json();
      if (!Array.isArray(companies)) return;
      for (const c of companies) {
        const b = document.createElement('button');
        b.type = 'button'; b.className = 'suggest-item';
        b.textContent = c.name + ' — ' + c.domain;
        b.onclick = () => { nameInput.value = c.name; domainInput.value = c.domain; box.innerHTML = ''; };
        box.appendChild(b);
      }
    }, 200);
  });
}

// report modal
const modal = document.getElementById('report-modal');
if (modal) {
  const openBtn = document.getElementById('report-open');
  const err = document.getElementById('report-error');
  let reportId = null;
  const show = (el, on) => el && (el.hidden = !on);
  openBtn?.addEventListener('click', () => show(modal, true));
  document.getElementById('report-close').onclick = () => show(modal, false);
  document.getElementById('report-send').onclick = async () => {
    show(err, false);
    const res = await fetch('/api/report/start', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        story_id: +openBtn.dataset.story,
        reason: document.getElementById('report-reason').value,
        reason_text: document.getElementById('report-text').value,
        corp_email: document.getElementById('report-email').value,
      }),
    });
    if (res.status === 401) { location.href = '/login'; return; }
    const data = await res.json();
    if (!data.ok) { err.textContent = data.error; show(err, true); return; }
    reportId = data.report_id;
    show(modal.querySelector('[data-step="1"]'), false);
    show(modal.querySelector('[data-step="2"]'), true);
  };
  document.getElementById('report-confirm').onclick = async () => {
    show(err, false);
    const res = await fetch('/api/report/verify', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ report_id: reportId,
        code: document.getElementById('report-code').value.trim() }),
    });
    if (res.status === 401) { location.href = '/login'; return; }
    const data = await res.json();
    if (!data.ok) { err.textContent = data.error; show(err, true); return; }
    show(modal.querySelector('[data-step="2"]'), false);
    show(document.getElementById('report-done'), true);
    if (data.hidden) location.reload();
  };
}
