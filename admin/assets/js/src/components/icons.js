var cache = {};

export default {
    pass: function (icon, callback) {
        var request;
        if (icon in cache) {
            callback(cache[icon]);
            return;
        }
        request = new XMLHttpRequest();
        request.onload = function () {
            var data = this.status === 200 ? this.response : '';
            if (data !== '') {
                cache[icon] = data;
            }
            callback(data);
        };
        request.open('GET', Formwork.config.baseUri + 'assets/icons/svg/' + icon + '.svg');
        request.send();
    },

    inject: function (icon, element, position) {
        if (typeof position === 'undefined') {
            position = 'afterBegin';
        }
        this.pass(icon, function (data) {
            element.insertAdjacentHTML(position, data);
        });
    }
};
