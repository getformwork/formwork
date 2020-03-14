import commonjs from '@rollup/plugin-commonjs';
import resolve from '@rollup/plugin-node-resolve';

export default {
    input: 'assets/js/src/main.js',
    output: {
        file: 'assets/js/app.min.js',
        format: 'iife',
        name: 'Formwork'
    },
    plugins: [resolve(), commonjs()]
};
