import js from "@eslint/js";
import globals from "globals";
import eslintConfigPrettier from "eslint-config-prettier";

export default [
    js.configs.recommended,
    {
        languageOptions: {
            ecmaVersion: 11,
            globals: {
                ...globals.browser,
                $: "readonly",
                $$: "readonly",
                Formwork: "readonly",
            },
            sourceType: "module",
        },
        rules: {
            "arrow-body-style": ["error", "as-needed"],
            curly: ["error", "all"],
            eqeqeq: ["error", "always"],
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
        },
    },
    eslintConfigPrettier,
];
