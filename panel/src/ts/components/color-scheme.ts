import { getCookies, setCookie } from "../utils/cookies";
import { $ } from "../utils/selectors";
import { app } from "../app";

type ColorSchemeName = "light" | "dark";
type ColorSchemePreference = ColorSchemeName | "light dark";

export class ColorScheme {
    constructor() {
        const getSupportedColorSchemes = (): ColorSchemePreference => {
            const meta = $("meta[name=color-scheme]") as HTMLMetaElement;
            if (!meta) {
                return "light dark";
            }
            switch (meta.content) {
                case "light":
                    return "light";
                case "dark":
                    return "dark";
                default:
                    return "light dark";
            }
        };

        const setColorScheme = (colorScheme: ColorSchemeName) => {
            document.documentElement.classList.remove("color-scheme-light", "color-scheme-dark");
            document.documentElement.classList.add(`color-scheme-${colorScheme}`);
        };

        const setPreferredColorScheme = (event: Event) => {
            const cookies = getCookies();
            const cookieName = "formwork_preferred_color_scheme";
            const oldValue = cookieName in cookies ? cookies[cookieName] : null;
            let colorScheme: ColorSchemeName = "light";

            if (window.matchMedia("(prefers-color-scheme: light)").matches) {
                colorScheme = "light";
            } else if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
                colorScheme = "dark";
            }

            if (colorScheme && colorScheme !== oldValue) {
                setCookie(cookieName, colorScheme, {
                    "max-age": 2592000, // 1 month
                    path: app.config.baseUri,
                    samesite: "strict",
                });

                if (event.type === "change" && getSupportedColorSchemes() === "light dark") {
                    setColorScheme(colorScheme);
                }
            }
        };

        window.addEventListener("beforeunload", setPreferredColorScheme);
        window.addEventListener("pagehide", setPreferredColorScheme);
        window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", setPreferredColorScheme);
    }
}
