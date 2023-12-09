import { serializeObject } from "./forms";

export class Request {
    constructor(options, callback) {
        const request = new XMLHttpRequest();

        request.open(options.method, options.url, true);
        request.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        request.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        request.send(serializeObject(options.data));

        if (typeof callback === "function") {
            const handler = () => {
                const response = JSON.parse(request.response);
                const code = response.code || request.status;
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
}
