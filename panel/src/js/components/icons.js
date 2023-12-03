const cache = {};

export default {
    pass: function (icon, callback) {
        if (icon in cache) {
            callback(cache[icon]);
            return;
        }

        const request = new XMLHttpRequest();

        request.onload = function () {
            const data = this.status === 200 ? this.response : "";
            if (data !== "") {
                cache[icon] = data;
            }
            callback(data);
        };

        request.open("GET", `${Formwork.config.baseUri}assets/icons/svg/${icon}.svg`);
        request.send();
    },

    inject: function (icon, element, position = "afterBegin") {
        this.pass(icon, (data) => {
            element.insertAdjacentHTML(position, data);
        });
    },
};
