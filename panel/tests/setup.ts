import { registerHooks } from "node:module";

/**
 * Use the same timezone on every machine, as dates are created and formatted in local time
 */
process.env.TZ = "UTC";

/**
 * Resolve extensionless relative imports (e.g. `./math`) to their `.ts` file, as the bundler does
 */
registerHooks({
    resolve(specifier, context, nextResolve) {
        try {
            return nextResolve(specifier, context);
        } catch (error) {
            if ((error as NodeJS.ErrnoException).code === "ERR_MODULE_NOT_FOUND" && /^\.{1,2}\//.test(specifier)) {
                return nextResolve(`${specifier}.ts`, context);
            }
            throw error;
        }
    },
});
