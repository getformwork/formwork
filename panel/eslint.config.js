import eslintConfigPrettier from "eslint-config-prettier";
import globals from "globals";
import js from "@eslint/js";

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 13,
            globals: {
                ...globals.browser,
                Formwork: "readonly",
            },
            sourceType: "module",
        },
        ignorePaterns: ["*.min.js"],
        rules: {
            "arrow-body-style": ["error", "as-needed"],
            curly: ["error", "all"],
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
            "sort-imports": [
                "warn",
                {
                    ignoreCase: true,
                    allowSeparatedGroups: true,
                },
            ],
        },
    },
    eslintConfigPrettier,
];
