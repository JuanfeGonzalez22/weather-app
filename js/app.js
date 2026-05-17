const API = './api';

const selectUsuario = document.getElementById('usuario-select');
const sliderMood    = document.getElementById('mood-slider');
const moodValue     = document.getElementById('mood-value');
const btnRegistrar  = document.getElementById('btn-registrar');
const secResultado  = document.getElementById('resultado');

async function cargarUsuarios() {
  try {
    const res  = await fetch(`${API}/usuarios.php`);
    const data = await res.json();

    selectUsuario.innerHTML = '<option value="">— Selecciona tu nombre —</option>';
    data.usuarios.forEach(u => {
      const opt       = document.createElement('option');
      opt.value       = u.id;
      opt.textContent = `${u.nombre} — ${u.ciudad}`;
      selectUsuario.appendChild(opt);
    });
  } catch {
    selectUsuario.innerHTML = '<option value="">Error cargando usuarios</option>';
  }
}

sliderMood.addEventListener('input', () => {
  moodValue.textContent = sliderMood.value;
});

btnRegistrar.addEventListener('click', async () => {
  const usuario_id = selectUsuario.value;
  const mood       = parseInt(sliderMood.value);

  if (!usuario_id) {
    mostrarError('Selecciona un usuario primero.');
    return;
  }

  setLoading(true);
  limpiarError();

  try {
    const res  = await fetch(`${API}/score.php`, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ usuario_id: parseInt(usuario_id), mood }),
    });

    const data = await res.json();

    if (!data.ok) {
      mostrarError(data.error || 'Error al registrar.');
      return;
    }

    mostrarResultado(data);

  } catch {
    mostrarError('No se pudo conectar con el servidor.');
  } finally {
    setLoading(false);
  }
});

function mostrarResultado(data) {
  document.getElementById('res-score').textContent       = data.score;
  document.getElementById('res-nombre').textContent      = data.usuario;
  document.getElementById('res-ciudad-text').textContent = data.ciudad;
  document.getElementById('res-clima').textContent       = data.clima;
  document.getElementById('res-temp').textContent        = `${data.temperatura}°C`;

  const pct = (data.score / 120) * 100;
  document.getElementById('score-fill').style.width = pct + '%';

  secResultado.hidden = false;
  lucide.createIcons();
  secResultado.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function setLoading(on) {
  btnRegistrar.disabled                              = on;
  document.querySelector('.btn-text').hidden         = on;
  document.querySelector('.btn-loader').hidden       = !on;
}

function mostrarError(msg) {
  limpiarError();
  const div       = document.createElement('div');
  div.className   = 'error-msg';
  div.textContent = msg;
  btnRegistrar.insertAdjacentElement('afterend', div);
}

function limpiarError() {
  document.querySelector('.error-msg')?.remove();
}

cargarUsuarios();