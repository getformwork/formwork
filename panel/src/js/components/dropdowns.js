export default {
    init: function () {
        if ($('.dropdown')) {
            document.addEventListener('click', (event) => {
                $$('.dropdown-menu').forEach((element) => {
                    element.style.display = '';
                });

                const button = event.target.closest('.dropdown-button');

                if (button) {
                    const dropdown = document.getElementById(button.getAttribute('data-dropdown'));
                    const isVisible = getComputedStyle(dropdown).display !== 'none';
                    event.preventDefault();

                    if (dropdown && !isVisible) {
                        dropdown.style.display = 'block';
                    }
                }
            });
        }
    },
};

