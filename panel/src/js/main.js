import Chart from "./components/chart";
import Dashboard from "./components/dashboard";
import Dropdowns from "./components/dropdowns";
import Forms from "./components/forms";
import Modals from "./components/modals";
import Notification from "./components/notification";
import Pages from "./components/pages";
import Tooltips from "./components/tooltips";
import Updates from "./components/updates";
import Utils from "./components/utils";

let Formwork;

export default Formwork = {
    config: {},
    init: function () {
        Modals.init();
        Forms.init();
        Dropdowns.init();
        Tooltips.init();

        Dashboard.init();
        Pages.init();
        Updates.init();

        if ($(".toggle-navigation")) {
            $(".toggle-navigation").addEventListener("click", () => {
                $(".sidebar").classList.toggle("show");
            });
        }

        $$("[data-chart-data]").forEach((element) => {
            const data = JSON.parse(element.dataset.chartData);
            Chart(element, data);
        });

        $$("meta[name=notification]").forEach((element) => {
            const data = JSON.parse(element.getAttribute("content"))[0];
            const notification = new Notification(data.text, data.type, {
                interval: data.interval,
                icon: data.icon,
            });
            notification.show();
            element.parentNode.removeChild(element);
        });

        $$(".collapsible .section-header").forEach((element) => {
            element.addEventListener("click", () => {
                const section = element.parentNode;
                section.classList.toggle("collapsed");
            });
        });

        if ($("[data-command=save]")) {
            document.addEventListener("keydown", (event) => {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.which === 83) {
                        // ctrl/cmd + S
                        $("[data-command=save]").click();
                        event.preventDefault();
                    }
                }
            });
        }

        window.addEventListener("beforeunload", setPreferredColorScheme);
        window.addEventListener("pagehide", setPreferredColorScheme);

        function setPreferredColorScheme() {
            const cookies = Utils.getCookies();
            const cookieName = "formwork_preferred_color_scheme";
            const oldValue = cookieName in cookies ? cookies[cookieName] : null;
            let value = null;

            if (window.matchMedia("(prefers-color-scheme: light)").matches) {
                value = "light";
            } else if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
                value = "dark";
            }

            if (value !== oldValue) {
                Utils.setCookie(cookieName, value, {
                    "max-age": 2592000, // 1 month
                    path: Formwork.config.baseUri,
                    samesite: "strict",
                });
            }
        }
    },

    initGlobals: function (global) {
        global.$ = function (selector, parent = document) {
            return parent.querySelector(selector);
        };

        global.$$ = function (selector, parent = document) {
            return parent.querySelectorAll(selector);
        };

        // HTMLFormElement.prototype.requestSubmit polyfill
        // see https://github.com/javan/form-request-submit-polyfill
        if (!("requestSubmit" in global.HTMLFormElement.prototype)) {
            global.HTMLFormElement.prototype.requestSubmit = function (submitter) {
                if (submitter) {
                    if (!(submitter instanceof HTMLElement)) {
                        raise(TypeError, "parameter 1 is not of type 'HTMLElement'");
                    }
                    if (submitter.type !== "submit") {
                        raise(TypeError, "The specified element is not a submit button");
                    }
                    if (submitter.form !== this) {
                        raise(DOMException, "The specified element is not owned by this form element", "NotFoundError");
                    }
                    submitter.click();
                } else {
                    submitter = document.createElement("input");
                    submitter.type = "submit";
                    submitter.hidden = true;
                    this.appendChild(submitter);
                    submitter.click();
                    this.removeChild(submitter);
                }

                function raise(error, message, name) {
                    throw new error(`Failed to execute 'requestSubmit' on 'HTMLFormElement': ${message}.`, name);
                }
            };
        }
    },
};

document.addEventListener("DOMContentLoaded", () => {
    Formwork.init();
});

Formwork.initGlobals(window);
