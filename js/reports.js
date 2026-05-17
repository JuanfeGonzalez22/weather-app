const API = './api';

let tipoActivo    = 'todo';
let usuarioActivo = 0;

async function cargarUsuariosFiltro() {
  try {
    const res  = await fetch(`${API}/usuarios.php`);
    const data = await res.json();
    const sel  = document.getElementById('filtro-usuario');

    data.usuarios.forEach(u => {
      const opt       = document.createElement('option');
      opt.value       = u.id;
      opt.textContent = u.nombre;
      sel.appendChild(opt);
    });
  } catch(e) {
    console.error('Error cargando usuarios:', e);
  }
}

async function cargarReporte() {
  mostrarSkeletons();

  try {
    const url  = `${API}/reports.php?tipo=${tipoActivo}&usuario_id=${usuarioActivo}`;
    const res  = await fetch(url);

    if (!res.ok) throw new Error('HTTP ' + res.status);

    const data = await res.json();

    if (!data.ok) throw new Error('API error');

    renderResumen(data.resumen);
    renderTabla(data.detalle);
    lucide.createIcons();

  } catch(e) {
    console.error('Error cargando reporte:', e);
    document.getElementById('resumen-grid').innerHTML =
      '<p class="table-empty">Error cargando reportes: ' + e.message + '</p>';
    document.getElementById('tabla-body').innerHTML =
      '<tr><td colspan="6" class="table-empty">Error cargando datos.</td></tr>';
  }
}

function renderResumen(resumen) {
  const grid = document.getElementById('resumen-grid');

  if (!resumen || !resumen.length) {
    grid.innerHTML = '<p class="table-empty">Sin registros para este período.</p>';
    return;
  }

  grid.innerHTML = resumen.map(r => `
    <div class="resumen-card">
      <div class="resumen-card__name">${r.nombre}</div>
      <div class="resumen-card__city">
        <i data-lucide="map-pin"></i> ${r.ciudad}
      </div>
      <div class="resumen-card__score">${r.score_promedio}</div>
      <div class="resumen-card__label">score promedio</div>
      <div class="resumen-card__stats">
        <span><strong>${r.score_maximo}</strong> máx</span>
        <span><strong>${r.score_minimo}</strong> mín</span>
        <span><strong>${r.total_registros}</strong> registros</span>
        <span><strong>${r.mood_promedio}</strong> mood</span>
      </div>
    </div>
  `).join('');
}

function renderTabla(detalle) {
  const tbody = document.getElementById('tabla-body');

  if (!detalle || !detalle.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="table-empty">Sin registros.</td></tr>';
    return;
  }

  tbody.innerHTML = detalle.map(r => `
    <tr>
      <td>${r.nombre}</td>
      <td class="td-mood">${r.mood}/10</td>
      <td class="td-score">${r.score} pts</td>
      <td class="td-clima">${r.clima}</td>
      <td class="td-fecha">${r.temperatura}°C</td>
      <td class="td-fecha">${r.fecha}</td>
    </tr>
  `).join('');
}

function mostrarSkeletons() {
  document.getElementById('resumen-grid').innerHTML = `
    <div class="skeleton-card"></div>
    <div class="skeleton-card"></div>
    <div class="skeleton-card"></div>
  `;
  document.getElementById('tabla-body').innerHTML =
    '<tr><td colspan="6" class="table-empty">Cargando...</td></tr>';
}

document.querySelectorAll('.btn-filter').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    tipoActivo = btn.dataset.tipo;
    cargarReporte();
  });
});

document.getElementById('filtro-usuario').addEventListener('change', e => {
  usuarioActivo = parseInt(e.target.value);
  cargarReporte();
});

cargarUsuariosFiltro();
cargarReporte();