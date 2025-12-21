import { ColorScheme } from "./components/color-scheme";
import { Dropdowns } from "./components/dropdowns";
import { Files } from "./components/files";
import { Forms } from "./components/forms";
import { Modals } from "./components/modals";
import { Navigation } from "./components/navigation";
import { Notifications } from "./components/notifications";
import { Sections } from "./components/sections";
import { Tabs } from "./components/tabs";
import { Tooltips } from "./components/tooltips";

import { Backups } from "./components/views/backups";
import { Dashboard } from "./components/views/dashboard";
import { Pages } from "./components/views/pages";
import { Plugins } from "./components/views/plugins";
import { Statistics } from "./components/views/statistics";
import { Updates } from "./components/views/updates";

interface AppConfig {
    siteUri: string;
    baseUri: string;
    csrfToken?: string;
    colorScheme?: string;
    DateInput?: any;
    DurationInput?: any;
    EditorInput?: any;
    SelectInput?: any;
    TagsInput?: any;
    Backups?: any;
}

interface Component {
    new (app: App): void;
}

interface ComponentConfig {
    globalAlias?: string;
}

class App {
    config: AppConfig = {
        siteUri: "/",
        baseUri: "/",
    };

    modals: Modals = {};

    forms: Forms = {};

    [alias: string]: any;

    load(config: AppConfig) {
        this.loadConfig(config);

        this.loadComponent(Modals, {
            globalAlias: "modals",
        });

        this.loadComponent(Forms, {
            globalAlias: "forms",
        });

        this.loadComponent(Tabs);
        this.loadComponent(Dropdowns);
        this.loadComponent(Tooltips);
        this.loadComponent(Navigation);
        this.loadComponent(ColorScheme);
        this.loadComponent(Notifications);
        this.loadComponent(Sections);

        this.loadComponent(Dashboard);
        this.loadComponent(Pages);
        this.loadComponent(Files);
        this.loadComponent(Statistics);
        this.loadComponent(Backups);
        this.loadComponent(Updates);
        this.loadComponent(Plugins);
    }

    loadConfig(config: AppConfig) {
        Object.assign(this.config, config);
    }

    loadComponent(
        component: Component,
        options: ComponentConfig = {
            globalAlias: undefined,
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
