/**
 * Serialize the fields of a form as a query string
 *
 * Disabled fields, buttons, file inputs, unchecked checkboxes and radios and fields with
 * `data-form-ignore="true"` are left out
 */
export function serializeForm(form: HTMLFormElement) {
    const serialized: string[] = [];

    const add = (name: string, value: string) => {
        serialized.push(`${encodeURIComponent(name)}=${encodeURIComponent(value)}`);
    };

    for (const field of Array.from(form.elements) as HTMLFormElement[]) {
        if (!isSerializable(field)) {
            continue;
        }

        if (field.type === "select-multiple") {
            for (const option of field.options) {
                if (option.selected) {
                    add(field.name, option.value);
                }
            }
        } else if (field.checked || (field.type !== "checkbox" && field.type !== "radio")) {
            add(field.name, field.value);
        }
    }

    return serialized.join("&");
}

/**
 * Return whether a field is sent with the form and is considered in its changes
 */
function isSerializable(field: HTMLFormElement) {
    return field.name && !field.disabled && field.dataset.formIgnore !== "true" && !["file", "reset", "submit", "button"].includes(field.type);
}

/**
 * Download a file sent by the server in answer to a POST request
 */
export function triggerDownload(uri: string, csrfToken: string) {
    const form = document.createElement("form");
    form.action = uri;
    form.method = "post";

    const input = document.createElement("input");
    input.type = "hidden";
    input.name = "csrf-token";
    input.value = csrfToken;

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
