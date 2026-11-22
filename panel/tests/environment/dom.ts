import { after, beforeEach } from "node:test";
import { GlobalRegistrator } from "@happy-dom/global-registrator";

// Following links and loading resources would make requests to the network
const options = {
    url: "http://example.test/",
    settings: {
        disableJavaScriptFileLoading: true,
        disableCSSFileLoading: true,
        disableIframePageLoading: true,
        navigation: {
            disableMainFrameNavigation: true,
            disableChildFrameNavigation: true,
            disableChildPageNavigation: true,
        },
    },
};

/**
 * Register a browser environment (happy-dom) for the tests of the importing file
 *
 * Imported before the modules under test, as they may use the globals when loaded. Each test gets
 * a new window, so the elements, the listeners and the cookies of the previous ones are gone.
 * Elements are compared with `assert.ok(a === b)`: a failing `assert.equal()` would print them,
 * walking the whole document. The last window is closed too, as its timers (e.g. the removal of the
 * notifications) would keep the process alive
 */
GlobalRegistrator.register(options);

beforeEach(async () => {
    await GlobalRegistrator.unregister();
    GlobalRegistrator.register(options);
});

after(async () => {
    await GlobalRegistrator.unregister();
});
