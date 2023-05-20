import js from '@eslint/js';
import globals from 'globals';

export default [
    js.configs.recommended,
    {
        'languageOptions': {
            'ecmaVersion': 6,
            'globals': {
                ...globals.browser,
                '$': 'readonly',
                '$$': 'readonly',
                'Formwork': 'readonly',
            },
            'sourceType': 'module',
        },
        'rules': {
            'arrow-body-style': ['error', 'as-needed'],
            'arrow-parens': ['error'],
            'arrow-spacing': ['error'],
            'brace-style': ['error', '1tbs'],
            'comma-dangle': ['error', { 'objects': 'always-multiline' }],
            'curly': ['error', 'all'],
            'eqeqeq': ['error', 'always'],
            'indent': ['error', 4],
            'keyword-spacing': ['error'],
            'linebreak-style': ['error', 'unix'],
            'new-parens': ['error'],
            'no-else-return': ['error'],
            'no-lonely-if': ['error'],
            'no-multi-spaces': ['error'],
            'no-multiple-empty-lines': ['error', { 'max': 1 }],
            'no-trailing-spaces': ['error'],
            'no-unneeded-ternary': ['error'],
            'no-var': ['error'],
            'object-curly-newline': ['error', { 'multiline': true }],
            'object-curly-spacing': ['error', 'always'],
            'object-property-newline': ['error'],
            'one-var': ['error', {
                'initialized': 'never',
                'uninitialized': 'always',
            }],
            'operator-assignment': ['error'],
            'prefer-arrow-callback': ['error'],
            'prefer-const': ['error'],
            'prefer-template': ['error'],
            'quotes': ['error', 'single'],
            'space-before-function-paren': ['error', {
                'anonymous': 'always',
                'named': 'never',
            }],
            'space-infix-ops': ['error'],
            'space-unary-ops': ['error', {
                'nonwords': false,
                'words': true,
            }],
            'spaced-comment': ['error'],
            'template-curly-spacing': ['error', 'never'],
        },
    }
];
