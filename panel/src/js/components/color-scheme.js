import { getCookies, setCookie } from "../utils/cookies";
import { app } from "../app";

export class ColorScheme {
    constructor() {
        const setPreferredColorScheme = () => {
            const cookies = getCookies();
            const cookieName = "formwork_preferred_color_scheme";
            const oldValue = cookieName in cookies ? cookies[cookieName] : null;
            let value = null;

            if (window.matchMedia("(prefers-color-scheme: light)").matches) {
                value = "light";
            } else if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
                value = "dark";
            }

            if (value !== oldValue) {
                setCookie(cookieName, value, {
                    "max-age": 2592000, // 1 month
                    path: app.config.baseUri,
                    samesite: "strict",
                });
            }
        };

        window.addEventListener("beforeunload", setPreferredColorScheme);
        window.addEventListener("pagehide", setPreferredColorScheme);
    }
}
