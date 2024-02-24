import { serializeObject } from "./forms";

type RequestOptions = {
    method: string;
    url: string;
    data: Record<string, any>;
};

export class Request {
    constructor(options: RequestOptions, callback: (response: Record<string, any>, request: XMLHttpRequest) => void) {
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
