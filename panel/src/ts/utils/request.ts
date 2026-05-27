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
    constructor(userOptions: Partial<RequestOptions>, callback?: (response: Record<string, any>, request: XMLHttpRequest) => void) {
        const request = new XMLHttpRequest();

        const options: RequestOptions = { ...defaultOptions, ...userOptions };

        if (!options.headers["X-Requested-With"]) {
            options.headers["X-Requested-With"] = "XMLHttpRequest";
        }

        request.open(options.method, options.url, true);

        for (const [key, value] of Object.entries(options.headers)) {
            request.setRequestHeader(key, value);
        }

        if (options.data instanceof FormData || options.data instanceof URLSearchParams || options.data instanceof Blob) {
            request.send(options.data);
        } else {
            request.send(new URLSearchParams(options.data));
        }

        if (typeof callback === "function") {
            const handler = () => {
                const response = JSON.parse(request.response);
                const code = parseInt(response.code) || request.status;
                if (code !== 400 && code !== 403) {
                    callback(response, request);
                }
            };
            request.onload = handler;
            request.onerror = handler;
        }

        return request;
    }
}
