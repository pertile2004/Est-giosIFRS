// ── Mobile menu ──
const menuBtn = document.getElementById('menu-btn');
const mobileNav = document.getElementById('mobile-nav');
if (menuBtn && mobileNav) {
  menuBtn.addEventListener('click', () => {
    mobileNav.classList.toggle('open');
    menuBtn.textContent = mobileNav.classList.contains('open') ? '✕' : '☰';
  });
}

// ── Auth tabs ──
document.querySelectorAll('.auth-tab').forEach(tab => {
  tab.addEventListener('click', () => {
    const target = tab.dataset.tab;
    document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.auth-form').forEach(f => f.classList.remove('active'));
    tab.classList.add('active');
    const form = document.getElementById(target);
    if (form) form.classList.add('active');
  });
});

// ── Tipo select (register) ──
document.querySelectorAll('.tipo-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const group = btn.closest('.tipo-select');
    group.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    const input = group.nextElementSibling;
    if (input && input.type === 'hidden') input.value = btn.dataset.tipo;
    // Show/hide conditional fields
    const tipo = btn.dataset.tipo;
    document.querySelectorAll('[data-show-for]').forEach(el => {
      el.style.display = (el.dataset.showFor === tipo || el.dataset.showFor === 'all') ? '' : 'none';
    });
  });
});

// ── Counter animation ──
function animateCounter(el) {
  const target = parseInt(el.dataset.target, 10);
  const duration = 1500;
  const step = target / (duration / 16);
  let current = 0;
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = Math.floor(current).toLocaleString('pt-BR') + (el.dataset.suffix || '');
    if (current >= target) clearInterval(timer);
  }, 16);
}

const counterObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting && !entry.target.dataset.animated) {
      entry.target.dataset.animated = '1';
      animateCounter(entry.target);
    }
  });
}, { threshold: 0.5 });

document.querySelectorAll('[data-target]').forEach(el => counterObserver.observe(el));

// ── Fade-in on scroll ──
const fadeObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'none';
    }
  });
}, { threshold: 0.1 });

document.querySelectorAll('.job-card, .feature-card, .stat-card').forEach(el => {
  el.style.opacity = '0';
  el.style.transform = 'translateY(20px)';
  el.style.transition = 'opacity .4s ease, transform .4s ease';
  fadeObserver.observe(el);
});

// ── Toast notifications ──
function showToast(message, type = 'success') {
  const toast = document.createElement('div');
  toast.style.cssText = `
    position:fixed;bottom:28px;right:28px;z-index:9999;
    padding:14px 20px;border-radius:10px;
    font-size:.9rem;font-weight:600;
    box-shadow:0 8px 24px rgba(0,0,0,.15);
    animation:fadeInUp .3s ease;
    display:flex;align-items:center;gap:10px;
    max-width:360px;
  `;
  const colors = { success: '#10B981', error: '#EF4444', info: '#3B82F6', warning: '#F59E0B' };
  const icons  = { success: '✓', error: '✗', info: 'ℹ', warning: '⚠' };
  toast.style.background = colors[type] || colors.success;
  toast.style.color = '#fff';
  toast.innerHTML = `<span>${icons[type]}</span><span>${message}</span>`;
  document.body.appendChild(toast);
  setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '.3s'; setTimeout(() => toast.remove(), 300); }, 3500);
}
window.showToast = showToast;

// ── Modal ──
document.querySelectorAll('[data-modal]').forEach(btn => {
  btn.addEventListener('click', () => {
    const modal = document.getElementById(btn.dataset.modal);
    if (modal) modal.classList.add('open');
  });
});
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});
document.querySelectorAll('.modal-close').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.closest('.modal-overlay').classList.remove('open');
  });
});

// ── Hero search redirect ──
const heroSearch = document.getElementById('hero-search');
if (heroSearch) {
  heroSearch.addEventListener('keydown', e => {
    if (e.key === 'Enter' && heroSearch.value.trim()) {
      window.location.href = '/teste/vagas.php?q=' + encodeURIComponent(heroSearch.value.trim());
    }
  });
}

// ── Highlight active nav ──
const currentPath = window.location.pathname;
document.querySelectorAll('.navbar-nav a, .sidebar-nav a').forEach(link => {
  if (link.getAttribute('href') && currentPath.endsWith(link.getAttribute('href').split('?')[0])) {
    link.classList.add('active');
  }
});
