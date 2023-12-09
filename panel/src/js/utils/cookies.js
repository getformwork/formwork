export function getCookies() {
    const result = [];
    const cookies = document.cookie.split(";");
    for (const cookie of cookies) {
        const nameAndValue = cookie.split("=", 2);
        if (nameAndValue.length === 2) {
            result[nameAndValue[0].trim()] = decodeURIComponent(nameAndValue[1].trim());
        }
    }
    return result;
}

export function setCookie(name, value, options) {
    let cookie = `${name}=${value}`;
    for (const option in options) {
        cookie += `;${option}=${options[option]}`;
    }
    document.cookie = cookie;
}
