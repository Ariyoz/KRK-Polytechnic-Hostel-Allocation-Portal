// KRK Polytechnic Hostel Portal — Main JS

document.addEventListener('DOMContentLoaded', function () {

    // ── Faculty → Department AJAX ────────────────────────────
    const facultySelect = document.getElementById('faculty_id');
    const deptSelect    = document.getElementById('department_id');
    if (facultySelect && deptSelect) {
        facultySelect.addEventListener('change', function () {
            const fid = this.value;
            deptSelect.innerHTML = '<option value="">Loading…</option>';
            deptSelect.disabled = true;
            if (!fid) { deptSelect.innerHTML = '<option value="">— Select Faculty first —</option>'; return; }
            const base = document.querySelector('meta[name="base_url"]');
            fetch((base ? base.content : '') + '/ajax/get_departments.php?faculty_id=' + encodeURIComponent(fid))
                .then(r => r.json())
                .then(data => {
                    deptSelect.innerHTML = '<option value="">— Select Department —</option>';
                    data.forEach(d => {
                        const o = document.createElement('option');
                        o.value = d.id; o.textContent = d.name;
                        deptSelect.appendChild(o);
                    });
                    deptSelect.disabled = false;
                })
                .catch(() => { deptSelect.innerHTML = '<option value="">Error loading</option>'; deptSelect.disabled = false; });
        });
    }

// ── Password match ───────────────────────────────────────
    const pwd  = document.getElementById('password');
    const cpwd = document.getElementById('confirm_password');
    const form = document.getElementById('registerForm');
    if (form && pwd && cpwd) {
        // Password strength bar
        const strengthBar = document.createElement('div');
        strengthBar.innerHTML = '<div style="margin-top:7px;"><div style="height:4px;border-radius:99px;background:var(--n200);overflow:hidden;"><div id="pwdStrengthFill" style="height:100%;border-radius:99px;width:0;transition:width .3s,background .3s;"></div></div><div id="pwdStrengthLabel" style="font-size:11px;margin-top:4px;color:var(--n400);"></div></div>';
        pwd.closest('.input-pwd-wrap')?.after(strengthBar.firstElementChild) || pwd.after(strengthBar.firstElementChild);

        pwd.addEventListener('input', () => {
            const v = pwd.value;
            let score = 0;
            if (v.length >= 6)  score++;
            if (v.length >= 10) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const fill  = document.getElementById('pwdStrengthFill');
            const label = document.getElementById('pwdStrengthLabel');
            if (!fill || !label) return;
            const levels = [
                {w:'0%',  c:'var(--n200)',   t:''},
                {w:'25%', c:'var(--danger)', t:'Weak'},
                {w:'50%', c:'var(--warning)',t:'Fair'},
                {w:'75%', c:'var(--blue-500)',t:'Good'},
                {w:'100%',c:'var(--success)',t:'Strong'},
            ];
            const l = levels[Math.min(score, 4)];
            fill.style.width = l.w; fill.style.background = l.c; label.textContent = l.t; label.style.color = l.c;
        });

        form.addEventListener('submit', e => {
            if (pwd.value !== cpwd.value) {
                e.preventDefault();
                cpwd.style.borderColor = 'var(--danger)';
                cpwd.setCustomValidity('Passwords do not match.');
                cpwd.reportValidity();
            }
        });
        cpwd.addEventListener('input', () => { cpwd.style.borderColor = ''; cpwd.setCustomValidity(''); });
    }

    // ── Auto-dismiss alerts ──────────────────────────────────
    document.querySelectorAll('.alert').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .5s, transform .5s';
            el.style.opacity = '0'; el.style.transform = 'translateY(-8px)';
            setTimeout(() => el.remove(), 500);
        }, 6000);
    });

    // ── Stat counter animation (IntersectionObserver) ────────
    // Only animate .counter-num and .stat-num inside the counter strip
    // and admin/student stat cards — NOT hero glass cards or pills
    const obs = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (!e.isIntersecting) return;
            animateCount(e.target, 0, parseInt(e.target.dataset.target || e.target.textContent, 10), 900);
            obs.unobserve(e.target);
        });
    }, { threshold: 0.3 });
    document.querySelectorAll('.stat-num, .counter-num').forEach(el => {
        // Skip any elements inside hero-cards-col, hero-stat-pills, or glass-card
        if (el.closest('.hero-cards-col, .hero-stat-pills, .glass-card')) return;
        const v = parseInt(el.textContent.trim(), 10);
        if (!isNaN(v) && v > 0) { el.dataset.target = v; el.textContent = '0'; obs.observe(el); }
    });

    // ── Progress bar animation ───────────────────────────────
    document.querySelectorAll('.progress-bar-fill').forEach(el => {
        const w = el.style.width; el.style.width = '0';
        setTimeout(() => { el.style.width = w; }, 300);
    });

    // ── Active nav link ──────────────────────────────────────
    const cur = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-links a, .sidebar a').forEach(a => {
        const h = (a.getAttribute('href') || '').split('/').pop().split('?')[0];
        if (h && h === cur) a.classList.add('active');
    });

    // ── Close mobile nav on outside click ───────────────────
    document.addEventListener('click', e => {
        const nav     = document.getElementById('mainNavLinks');
        const btn     = document.getElementById('hamburgerBtn');
        const overlay = document.getElementById('navOverlay');
        if (!nav || !btn) return;
        if (!nav.contains(e.target) && !btn.contains(e.target)) {
            nav.classList.remove('open');
            btn.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    });

    // ── Scroll: make header solid once user scrolls on hero page ─
    const header = document.querySelector('.site-header:not(.solid)');
    if (header) {
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 80);
        }, { passive: true });
    }

});

// ── Mobile nav toggle ────────────────────────────────────────
function toggleMobileNav() {
    const nav     = document.getElementById('mainNavLinks');
    const btn     = document.getElementById('hamburgerBtn');
    const overlay = document.getElementById('navOverlay');
    if (!nav || !btn) return;
    const isOpen = nav.classList.toggle('open');
    btn.classList.toggle('open', isOpen);
    if (overlay) overlay.classList.toggle('open', isOpen);
    document.body.style.overflow = isOpen ? 'hidden' : '';
}

// ── Password show/hide ───────────────────────────────────────
function togglePwd(id, btn) {
    const inp = document.getElementById(id);
    if (!inp) return;
    const show = inp.type === 'password';
    inp.type = show ? 'text' : 'password';
    const icon = btn.querySelector('i');
    if (icon) { icon.classList.toggle('fa-eye', !show); icon.classList.toggle('fa-eye-slash', show); }
}

// ── Counter animation ────────────────────────────────────────
function animateCount(el, start, end, dur) {
    if (isNaN(end) || end === 0) return;
    const t0 = performance.now();
    function step(now) {
        const p = Math.min((now - t0) / dur, 1);
        const e = 1 - Math.pow(1 - p, 3);
        el.textContent = Math.round(start + (end - start) * e);
        if (p < 1) requestAnimationFrame(step); else el.textContent = end;
    }
    requestAnimationFrame(step);
}

// ── Email body viewer ────────────────────────────────────────
function showEmailBody(id) {
    const r = document.getElementById('body-' + id);
    if (r) r.style.display = (r.style.display === 'none' || !r.style.display) ? 'table-row' : 'none';
}
