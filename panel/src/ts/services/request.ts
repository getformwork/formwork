import type { NotificationType } from "../components/ui/notification";
import { app } from "./app";

/**
 * Represents the JSON response of the panel
 */
export interface PanelResponse<Data = unknown> {
    status: NotificationType;
    message: string;
    code?: number | string;
    /**
     * Data of the response
     */
    data: Data;
}

/**
 * Holds the options of a request
 */
interface RequestOptions {
    method: string;
    url: string;
    data: Record<string, string | undefined> | FormData | URLSearchParams | Blob;
    headers: Record<string, string>;
}

const defaultOptions: RequestOptions = {
    method: "GET",
    url: "",
    data: {},
    headers: {},
};

/**
 * Sends an AJAX request to the panel
 *
 * The callback receives the JSON response, or an error response if the request fails. Responses
 * with code 400 or 403 are ignored
 */
export class Request<Data = unknown> {
    constructor(userOptions: Partial<RequestOptions>, callback: (response: PanelResponse<Data>, request: XMLHttpRequest) => void) {
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
                request.send(new URLSearchParams(options.data as Record<string, string>));
                break;
        }

        if (typeof callback === "function") {
            const handler = () => {
                let response: PanelResponse<Data>;

                try {
                    response = JSON.parse(request.response);
                } catch {
                    // Network errors and non-JSON responses
                    response = { status: "error", message: request.statusText || "The request failed", data: {} as Data };
                }

                const code = parseInt(String(response.code), 10) || request.status;
                if (code === 400 || code === 403) {
                    // location.reload();
                } else {
                    callback(response, request);
                }
            };
            request.onload = handler;
            request.onerror = handler;
        }
    }
}

/**
 * Send a POST request with the CSRF token of the page
 *
 * @param data     Data to send, the CSRF token is added to it
 * @param callback Function called with the response
 */
export function post<Data = unknown>(url: string, data: Record<string, string | undefined> | FormData, callback: (response: PanelResponse<Data>, request: XMLHttpRequest) => void) {
    const token = app.config.csrfToken as string;

    if (data instanceof FormData) {
        data.append("csrf-token", token);
    } else {
        data = { ...data, "csrf-token": token };
    }

    return new Request<Data>({ method: "POST", url, data }, callback);
}
