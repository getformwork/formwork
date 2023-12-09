export function serializeObject(object) {
    const serialized = [];
    for (const property in object) {
        serialized.push(`${encodeURIComponent(property)}=${encodeURIComponent(object[property])}`);
    }
    return serialized.join("&");
}

export function serializeForm(form) {
    const serialized = [];
    for (const field of form.elements) {
        if (field.name && !field.disabled && field.dataset.formIgnore !== "true" && field.type !== "file" && field.type !== "reset" && field.type !== "submit" && field.type !== "button") {
            if (field.type === "select-multiple") {
                for (const option of field.options) {
                    if (option.selected) {
                        serialized.push(`${encodeURIComponent(field.name)}=${encodeURIComponent(option.value)}`);
                    }
                }
            } else if ((field.type !== "checkbox" && field.type !== "radio") || field.checked) {
                serialized.push(`${encodeURIComponent(field.name)}=${encodeURIComponent(field.value)}`);
            }
        }
    }
    return serialized.join("&");
}

export function triggerDownload(uri, csrfToken) {
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
