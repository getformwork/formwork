import type { DateInputOptions } from "../components/inputs/date-input";
import type { DurationInputOptions } from "../components/inputs/duration-input";
import type { SelectInputOptions } from "../components/inputs/select-input";
import type { TagsInputOptions } from "../components/inputs/tags-input";
import { $, $$ } from "../utils/selectors";
import type { Forms } from "./forms";
import type { Modal } from "./modal";
import type { Modals } from "./modals";
import type { Translation } from "./translation";

/**
 * Holds the panel configuration passed by the PHP views to `app.load()`
 */
interface AppConfig {
    siteUri: string;
    baseUri: string;
    translation: {
        code: string;
        data: { [key: string]: string | string[] };
    };
    csrfToken?: string;
    colorScheme?: string;
    DateInput?: Partial<DateInputOptions>;
    DurationInput?: Partial<DurationInputOptions>;
    SelectInput?: Partial<SelectInputOptions>;
    TagsInput?: Partial<TagsInputOptions>;
}

/**
 * Represents a component started with the application
 */
interface Component {
    // biome-ignore lint/suspicious/noConfusingVoidType: The constructor returns void, not undefined
    new (app: App): void;
}

/**
 * Describes the services available as properties of the application after `load()`
 */
interface Services {
    translation: Translation;
    modals: Modals;
    forms: Forms;
}

/**
 * Represents the panel application
 *
 * This module imports no component, so that components can import `app` without circular
 * dependencies. The components are registered in `index.ts`
 */
class App implements Services {
    config: AppConfig = {
        siteUri: "/",
        baseUri: "/",
        translation: {
            code: "en",
            data: {},
        },
    };

    translation: Translation = {} as Translation;

    modals: Modals = {};

    forms: Forms = {};

    private readonly initializers: (() => void)[] = [];

    /**
     * Register a service
     *
     * The service is created when the application is loaded and stored in the property with the
     * same name. Services and components are started in the order they are registered
     *
     * @param name    Name of the property of the application
     * @param service Class of the service
     */
    service<Name extends keyof Services>(name: Name, service: new (app: App) => Services[Name]) {
        this.initializers.push(() => {
            (this as Services)[name] = new service(this);
        });
    }

    /**
     * Register a component
     */
    component(component: Component) {
        this.initializers.push(() => this.loadComponent(component));
    }

    /**
     * Run an initializer with the first element matching a selector, if any
     *
     * @param selector Selector of the element
     * @param init     Function called with the element
     */
    view<Element extends HTMLElement = HTMLElement>(selector: string, init: (element: Element) => void) {
        this.initializers.push(() => {
            const element = $<Element>(selector);
            if (element) {
                init(element);
            }
        });
    }

    /**
     * Run an initializer with each element matching a selector
     *
     * @param selector Selector of the elements
     * @param init     Function called with each element
     */
    views<Element extends HTMLElement = HTMLElement>(selector: string, init: (element: Element) => void) {
        this.initializers.push(() => {
            for (const element of $$<Element>(selector)) {
                init(element);
            }
        });
    }

    /**
     * Run an initializer with the modal with the given id, if any
     *
     * @param id   Id of the modal
     * @param init Function called with the modal
     */
    modal(id: string, init: (modal: Modal) => void) {
        this.initializers.push(() => {
            const modal = this.modals[id];
            if (modal) {
                init(modal);
            }
        });
    }

    /**
     * Load the configuration and start the registered services and components
     */
    load(config: AppConfig) {
        this.loadConfig(config);

        for (const initialize of this.initializers) {
            initialize();
        }
    }

    /**
     * Merge a configuration into the current one
     */
    loadConfig(config: AppConfig) {
        Object.assign(this.config, config);
    }

    /**
     * Start a component immediately
     */
    loadComponent(component: Component) {
        new component(this);
    }
}

export type { App };
/**
 * Panel application instance
 */
export const app = new App();
