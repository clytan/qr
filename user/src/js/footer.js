document.addEventListener('DOMContentLoaded', function () {
    function showToast(text) {
        var toast = document.getElementById('coming-soon-toast');
        var txt = document.getElementById('coming-soon-text');
        txt.textContent = text + ' coming soon';
        toast.style.display = 'block';
        toast.style.opacity = 1;
        setTimeout(function () {
            toast.style.transition = 'opacity 300ms ease';
            toast.style.opacity = 0;
            setTimeout(function () { toast.style.display = 'none'; toast.style.transition = ''; }, 300);
        }, 1500);
    }

    var coming = document.querySelectorAll('.coming-soon');
    coming.forEach(function (el) {
        el.addEventListener('click', function (e) {
            e.preventDefault();
            var name = el.getAttribute('data-name') || 'This feature';
            showToast(name);
        });
    });
    // More popover toggle
    var moreToggles = document.querySelectorAll('.nav-item-more');
    moreToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var container = toggle.closest('.more-container');
            var pop = container.querySelector('.more-popover');
            var expanded = toggle.getAttribute('aria-expanded') === 'true';
            if (!expanded) {
                pop.style.display = 'block';
                toggle.setAttribute('aria-expanded', 'true');
                pop.setAttribute('aria-hidden', 'false');
            } else {
                pop.style.display = 'none';
                toggle.setAttribute('aria-expanded', 'false');
                pop.setAttribute('aria-hidden', 'true');
            }
        });
    });

    // Close popovers when clicking outside and handle desktop "More"
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.more-popover').forEach(function (pop) {
            var container = pop.closest('.more-container');
            if (!container) return;
            if (!container.contains(e.target)) {
                pop.style.display = 'none';
                var toggle = container.querySelector('.nav-item-more');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
                pop.setAttribute('aria-hidden', 'true');
            }
        });

        // desktop more close
        document.querySelectorAll('.more-desktop').forEach(function (md) {
            var wrap = md.closest('.more-desktop-wrapper');
            if (!wrap) return;
            if (!wrap.contains(e.target)) {
                md.style.display = 'none';
                md.setAttribute('aria-hidden', 'true');
            }
        });
    });

    // desktop more toggle
    document.querySelectorAll('.desktop-more-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var wrap = btn.closest('.more-desktop-wrapper');
            var panel = wrap.querySelector('.more-desktop');
            if (panel.style.display === 'block') {
                panel.style.display = 'none'; panel.setAttribute('aria-hidden', 'true');
            } else { panel.style.display = 'block'; panel.setAttribute('aria-hidden', 'false'); }
        });
    });
});
