import Utils from './utils';

export default function Request(options, callback) {

    const request = new XMLHttpRequest();

    request.open(options.method, options.url, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.send(Utils.serializeObject(options.data));

    if (typeof callback === 'function') {
        const handler = function () {
            const response = JSON.parse(this.response);
            const code = response.code || this.status;
            if (parseInt(code) === 400) {
                location.reload();
            } else {
                callback(response, request);
            }
        };
        request.onload = handler;
        request.onerror = handler;
    }

    return request;
}
