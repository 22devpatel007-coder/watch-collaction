/**
 * Watch Collection - Global Client-Side Behavior
 */

document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.querySelector('.navbar__toggle');
    var menu = document.querySelector('.navbar__menu');

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            menu.classList.toggle('is-open');
        });
    }

    // Auto-dismiss flash messages after 4 seconds
    var alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.display = 'none';
        }, 4000);
    });
});