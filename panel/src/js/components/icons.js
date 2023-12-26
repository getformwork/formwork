import { app } from "../app";

const cache = new Map();

export function passIcon(icon, callback) {
    if (cache.has(icon)) {
        callback(cache.get(icon));
        return;
    }

    const request = new XMLHttpRequest();

    request.onload = function () {
        const data = this.status === 200 ? this.response : "";
        if (data !== "") {
            cache.set(icon, data);
        }
        callback(data);
    };

    request.open("GET", `${app.config.baseUri}assets/icons/svg/${icon}.svg`);
    request.send();
}

export function insertIcon(icon, element, position = "afterBegin") {
    passIcon(icon, (data) => element.insertAdjacentHTML(position, data));
}
