window.addEventListener('load', function() {
    /** Toggleable menus handler **/
    var menuToggles = document.getElementsByClassName('menu-toggle');
    for (var i = 0; i < menuToggles.length; i++) {
        var button = menuToggles[i];
        button.addEventListener('click', function() {
            var id = this.getAttribute('data-toggle');
            var element = document.getElementById(id);
            helpers.toggleElement(element, 250);
            element.classList.toggle('menu-expanded');
            if (this.getAttribute('aria-expanded') !== 'true') {
                this.setAttribute('aria-expanded', 'true');
            } else {
                this.setAttribute('aria-expanded', 'false');
            }
        });
    }
});

var helpers = {
    /**
     * Measures real element height as if it was rendered with
     * `display: block` and `height: auto` CSS properties
     */
    measureElementHeight: function (element) {
        var styleHeight = element.style.height;
        var styleDisplay = element.style.height;
        element.style.height = '';
        element.style.display = 'block';
        var height = element.clientHeight;
        element.style.height = styleHeight;
        element.style.display = styleDisplay;
        return height;
    },

    /**
     * Toggles an element animating its height
     */
    toggleElement: function (element, duration) {
        var direction = element.clientHeight === 0 ? 1 : -1;
        var measuredHeight = helpers.measureElementHeight(element);
        var steps = Math.floor(duration / 10);
        var delta = measuredHeight / steps * direction;
        if (direction > 0) {
            element.style.height = 0;
        } else {
            element.style.display = 'block';
            element.style.height = measuredHeight + 'px';
        }
        var interval = window.setInterval(function() {
            if (steps-- >= 0) {
                element.style.height = (parseInt(element.style.height) + delta) + 'px';
            } else {
                element.style.height = '';
                element.style.display = '';
                window.clearInterval(interval);
            }
        }, 10);
    }
};
