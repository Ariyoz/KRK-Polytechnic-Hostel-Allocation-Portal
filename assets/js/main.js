// KRK Hostel Portal — shared page script.
// Only what pages actually need: nav/sidebar toggles, password show/hide,
// and the two AJAX lookups used by the registration and application forms.

document.addEventListener('DOMContentLoaded', function () {

    // ---- Public site nav (off-canvas on small screens) ----
    const navToggle = document.getElementById('navToggle');
    const siteNav    = document.getElementById('siteNav');
    const navOverlay = document.getElementById('navOverlay');
    if (navToggle && siteNav) {
        navToggle.addEventListener('click', () => {
            const open = siteNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (navOverlay) navOverlay.classList.toggle('is-open', open);
            document.body.style.overflow = open ? 'hidden' : '';
        });
        if (navOverlay) navOverlay.addEventListener('click', () => {
            siteNav.classList.remove('is-open');
            navOverlay.classList.remove('is-open');
            navToggle.setAttribute('aria-expanded', 'false');
            document.body.style.overflow = '';
        });
    }

    // ---- Portal sidebar (off-canvas on small screens) ----
    // Any element with [data-sidebar-toggle] opens/closes .sidebar.
    // Add a .portal-overlay element in the page to close it on outside click.
    const sidebar = document.querySelector('.sidebar');
    const sidebarOverlay = document.querySelector('.portal-overlay');
    document.querySelectorAll('[data-sidebar-toggle]').forEach(btn => {
        btn.addEventListener('click', () => {
            if (!sidebar) return;
            const open = sidebar.classList.toggle('is-open');
            if (sidebarOverlay) sidebarOverlay.classList.toggle('is-open', open);
        });
    });
    if (sidebarOverlay && sidebar) {
        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('is-open');
            sidebarOverlay.classList.remove('is-open');
        });
    }

    // ---- Faculty → Department AJAX (registration form) ----
    const facultySelect = document.getElementById('faculty_id');
    const deptSelect    = document.getElementById('department_id');
    if (facultySelect && deptSelect) {
        facultySelect.addEventListener('change', function () {
            const fid = this.value;
            deptSelect.innerHTML = '<option value="">Loading…</option>';
            deptSelect.disabled = true;
            if (!fid) { deptSelect.innerHTML = '<option value="">— Select faculty first —</option>'; return; }
            const base = document.querySelector('meta[name="base_url"]');
            fetch((base ? base.content : '') + '/ajax/get_departments.php?faculty_id=' + encodeURIComponent(fid))
                .then(r => r.json())
                .then(data => {
                    deptSelect.innerHTML = '<option value="">— Select department —</option>';
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

    // ---- Password match check (registration form) ----
    const pwd  = document.getElementById('password');
    const cpwd = document.getElementById('confirm_password');
    const form = document.getElementById('registerForm');
    if (form && pwd && cpwd) {
        form.addEventListener('submit', e => {
            if (pwd.value !== cpwd.value) {
                e.preventDefault();
                cpwd.classList.add('has-error');
                cpwd.setCustomValidity('Passwords do not match.');
                cpwd.reportValidity();
            }
        });
        cpwd.addEventListener('input', () => { cpwd.classList.remove('has-error'); cpwd.setCustomValidity(''); });
    }

});

// ---- Password show/hide ----
// Call with the input's id and the button element: onclick="togglePwd('password', this)"
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    btn.classList.toggle('is-shown', show);
    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
}
