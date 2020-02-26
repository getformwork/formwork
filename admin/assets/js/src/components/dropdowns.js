Formwork.Dropdowns = {
    init: function () {
        if ($('.dropdown')) {
            document.addEventListener('click', function (event) {
                var button = event.target.closest('.dropdown-button');
                var dropdown, isVisible;
                if (button) {
                    dropdown = document.getElementById(button.getAttribute('data-dropdown'));
                    isVisible = getComputedStyle(dropdown).display !== 'none';
                    event.preventDefault();
                }
                $$('.dropdown-menu').forEach(function (element) {
                    element.style.display = '';
                });
                if (dropdown && !isVisible) {
                    dropdown.style.display = 'block';
                }
            });
        }
    }
};
