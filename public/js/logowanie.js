function switchTab(tab) {
    const loginTab = document.getElementById('tab-login');
    const registerTab = document.getElementById('tab-register');
    const loginForm = document.getElementById('form-login');
    const registerForm = document.getElementById('form-register');

    if (tab === 'login') {
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
        loginForm.classList.add('active');
        registerForm.classList.remove('active');
    } else {
        loginTab.classList.remove('active');
        registerTab.classList.add('active');
        loginForm.classList.remove('active');
        registerForm.classList.add('active');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var klikniecia = 0;
    var trigger = document.getElementById('admin-trigger');
    if (trigger) {
        trigger.addEventListener('click', function (e) {
            if (e.target === this || e.target.tagName === 'H1') {
                klikniecia++;
                if (klikniecia >= 5) {
                    window.location.href = 'admin_login.php';
                }
            }
        });
    }
});

window.switchTab = switchTab;
