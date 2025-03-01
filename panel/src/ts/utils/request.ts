interface RequestOptions {
    method: string;
    url: string;
    data: Record<string, any> | FormData;
    headers: Record<string, string>;
}

const defaultOptions: RequestOptions = {
    method: "GET",
    url: "",
    data: {},
    headers: {},
};

export class Request {
    constructor(userOptions: Partial<RequestOptions>, callback: (response: Record<string, any>, request: XMLHttpRequest) => void) {
        const request = new XMLHttpRequest();

        const options: RequestOptions = Object.assign({}, defaultOptions, userOptions);

        if (!options.headers["X-Requested-With"]) {
            options.headers["X-Requested-With"] = "XMLHttpRequest";
        }

        request.open(options.method, options.url, true);

        for (const key in options.headers) {
            request.setRequestHeader(key, options.headers[key]);
        }

        switch (true) {
            case options.data instanceof FormData:
            case options.data instanceof URLSearchParams:
            case options.data instanceof Blob:
                request.send(options.data);
                break;

            default:
                request.send(new URLSearchParams(options.data));
                break;
        }

        if (typeof callback === "function") {
            const handler = () => {
                const response = JSON.parse(request.response);
                const code = response.code || request.status;
                if (parseInt(code) === 400) {
                    // location.reload();
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
