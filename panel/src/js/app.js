import { ColorScheme } from "./components/color-scheme";
import { Dropdowns } from "./components/dropdowns";
import { Files } from "./components/files";
import { Forms } from "./components/forms";
import { Modals } from "./components/modals";
import { Navigation } from "./components/navigation";
import { Notifications } from "./components/notifications";
import { Sections } from "./components/sections";
import { Tooltips } from "./components/tooltips";

import { Dashboard } from "./components/views/dashboard";
import { Pages } from "./components/views/pages";
import { Updates } from "./components/views/updates";

class App {
    config = {};

    modals = {};

    forms = {};

    load(config) {
        this.loadConfig(config);

        this.loadComponent(Modals, {
            globalAlias: "modals",
        });

        this.loadComponent(Forms, {
            globalAlias: "forms",
        });

        this.loadComponent(Dropdowns);
        this.loadComponent(Tooltips);
        this.loadComponent(Navigation);
        this.loadComponent(ColorScheme);
        this.loadComponent(Notifications);
        this.loadComponent(Sections);
        this.loadComponent(Files);

        this.loadComponent(Dashboard);
        this.loadComponent(Pages);
        this.loadComponent(Updates);
    }

    loadConfig(config) {
        Object.assign(this.config, config);
    }

    loadComponent(
        component,
        options = {
            globalAlias: null,
        },
    ) {
        const instance = new component(this);
        const { globalAlias } = options;
        if (globalAlias) {
            this[globalAlias] = instance;
        }
    }
}

export const app = new App();
