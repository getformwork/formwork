import { defineConfig } from "eslint/config";
import eslintConfigPrettier from "eslint-config-prettier";
import globals from "globals";
import js from "@eslint/js";
import tseslint from "typescript-eslint";

export default defineConfig([
    js.configs.recommended,
    ...tseslint.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 2020,
            globals: {
                ...globals.browser,
            },
            sourceType: "module",
        },
        ignores: ["*.min.js"],
        rules: {
            "arrow-body-style": ["error", "as-needed"],
            eqeqeq: ["error", "always"],
            "no-console": ["warn"],
            "no-else-return": ["error"],
            "no-lonely-if": ["error"],
            "no-unneeded-ternary": ["error"],
            "no-var": ["error"],
            "one-var": [
                "error",
                {
                    initialized: "never",
                    uninitialized: "always",
                },
            ],
            "operator-assignment": ["error"],
            "prefer-arrow-callback": ["error"],
            "prefer-const": ["error"],
            "prefer-template": ["error"],
            "require-await": ["error"],
            "sort-imports": [
                "warn",
                {
                    ignoreCase: true,
                    allowSeparatedGroups: true,
                },
            ],
            "@typescript-eslint/consistent-type-exports": ["error"],
            "@typescript-eslint/consistent-type-imports": ["error"],
            "@typescript-eslint/no-explicit-any": "off",
            "@typescript-eslint/no-redundant-type-constituents": "error",
            "@typescript-eslint/no-unnecessary-type-assertion": "error",
            "@typescript-eslint/no-unnecessary-type-constraint": "error",
            "@typescript-eslint/typedef": [
                "warn",
                {
                    parameter: true,
                },
            ],
        },
    },
    {
        files: ["**/*.ts"],
        languageOptions: {
            parserOptions: {
                projectService: true,
                tsconfigRootDir: import.meta.dirname,
            },
        },
    },
    eslintConfigPrettier,
    {
        rules: {
            curly: ["error", "all"],
        },
    },
]);
