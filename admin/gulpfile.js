/* eslint-env node */
var gulp = require('gulp');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var cleanCSS = require('gulp-clean-css');
var rollup = require('rollup');
var resolve = require('@rollup/plugin-node-resolve');
var commonjs = require('@rollup/plugin-commonjs');
var uglify = require('gulp-uglify');

gulp.task('css', function () {
    return gulp.src('./assets/scss/admin.scss')
        .pipe(sass())
        .pipe(gulp.dest('./assets/css'))
        .pipe(rename('admin.min.css'))
        .pipe(cleanCSS())
        .pipe(gulp.dest('./assets/css'));
});

gulp.task('js-rollup', function () {
    return rollup.rollup({
        input: './assets/js/src/main.js',
        plugins: [resolve(), commonjs()]
    }).then(function (bundle) {
        return bundle.write({
            file: './assets/js/app.js',
            format: 'iife',
            name: 'Formwork'
        });
    });
});

gulp.task('js-minify', function () {
    return gulp.src('./assets/js/app.js')
        .pipe(rename('app.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('./assets/js'));
});

gulp.task('js', gulp.series('js-rollup', 'js-minify'));
gulp.task('default', gulp.parallel('css', 'js'));
