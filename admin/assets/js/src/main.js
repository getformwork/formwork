var Formwork = {
    baseUri: '',
    config: {},

    init: function () {
        this.baseUri = $('meta[name=base-uri]').getAttribute('content');

        Formwork.Modals.init();
        Formwork.Forms.init();
        Formwork.Dropdowns.init();
        Formwork.Tooltips.init();

        Formwork.Dashboard.init();
        Formwork.Pages.init();
        Formwork.Updates.init();

        $('.toggle-navigation').addEventListener('click', function () {
            $('.sidebar').classList.toggle('show');
        });

        $$('[data-chart-data]').forEach(function (element) {
            var data = JSON.parse(element.getAttribute('data-chart-data'));
            Formwork.Chart(element, data);
        });

        $$('meta[name=notification]').forEach(function (element) {
            var notification = new Formwork.Notification(element.getAttribute('content'), element.getAttribute('data-type'), element.getAttribute('data-interval'));
            notification.show();
            element.parentNode.removeChild(element);
        });

        if ($('[data-command=save]')) {
            document.addEventListener('keydown', function (event) {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.which === 83) { // ctrl/cmd + S
                        $('[data-command=save]').click();
                        event.preventDefault();
                    }
                }
            });
        }

    },

    initGlobals: function (global) {
        global.$ = function (selector, parent) {
            if (typeof parent === 'undefined') {
                parent = document;
            }
            return parent.querySelector(selector);
        };

        global.$$ = function (selector, parent) {
            if (typeof parent === 'undefined') {
                parent = document;
            }
            return parent.querySelectorAll(selector);
        };

        // NodeList.prototype.forEach polyfill
        if (!('forEach' in global.NodeList.prototype)) {
            global.NodeList.prototype.forEach = global.Array.prototype.forEach;
        }

        // Element.prototype.matches polyfill
        if (!('matches' in global.Element.prototype)) {
            global.Element.prototype.matches = global.Element.prototype.msMatchesSelector || global.Element.prototype.webkitMatchesSelector;
        }

        // Element.prototype.closest polyfill
        if (!('closest' in global.Element.prototype)) {
            global.Element.prototype.closest = function (selectors) {
                var element = this;
                do {
                    if (element.matches(selectors)) {
                        return element;
                    }
                    element = element.parentElement || element.parentNode;
                } while (element !== null && element.nodeType === 1);
                return null;
            };
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    Formwork.init();
});

Formwork.initGlobals(window);
