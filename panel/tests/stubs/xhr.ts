/**
 * Stub of `XMLHttpRequest` recording the requests and answering them on demand
 *
 * Installed with `installXMLHttpRequestStub()`, after the window of happy-dom is registered, which
 * has its own implementation making requests to the network
 */
export class XMLHttpRequestStub {
    /**
     * Requests sent since the stub was installed, in order
     */
    static requests: XMLHttpRequestStub[] = [];

    static get last() {
        return XMLHttpRequestStub.requests[XMLHttpRequestStub.requests.length - 1];
    }

    method = "";
    url = "";
    async = false;
    headers: Record<string, string> = {};
    body: unknown;

    status = 0;
    statusText = "";
    response = "";

    onload: (() => void) | null = null;
    onerror: (() => void) | null = null;

    constructor() {
        XMLHttpRequestStub.requests.push(this);
    }

    open(method: string, url: string, async: boolean) {
        this.method = method;
        this.url = url;
        this.async = async;
    }

    setRequestHeader(name: string, value: string) {
        this.headers[name] = value;
    }

    send(body: unknown) {
        this.body = body;
    }

    /**
     * Answer the request with a response
     */
    respond(status: number, response: string, statusText = "") {
        this.status = status;
        this.statusText = statusText;
        this.response = response;
        this.onload?.();
    }

    /**
     * Answer the request with a JSON response of the panel
     */
    respondJson(status: string, message: string, data: unknown = {}) {
        this.respond(status === "success" ? 200 : 500, JSON.stringify({ status, message, data }));
    }

    /**
     * Fail the request as on network errors
     */
    fail() {
        this.onerror?.();
    }
}

/**
 * Replace `XMLHttpRequest` with the stub, forgetting the previous requests
 */
export function installXMLHttpRequestStub() {
    XMLHttpRequestStub.requests = [];
    Object.assign(globalThis, { XMLHttpRequest: XMLHttpRequestStub });
}
