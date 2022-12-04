import Utils from './utils';

export default function Request(options, callback) {

    var request = new XMLHttpRequest();

    var handler, response, code;

    request.open(options.method, options.url, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.send(Utils.serializeObject(options.data));

    if (typeof callback === 'function') {
        handler = function () {
            response = JSON.parse(this.response);
            code = response.code || this.status;
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
