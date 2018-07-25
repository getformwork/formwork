var gulp   = require('gulp');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var sass   = require('gulp-sass');
var cssmin = require('gulp-cssmin');
var uglify = require('gulp-uglify');

gulp.task('css', function() {
    return gulp.src('./assets/scss/admin.scss')
        .pipe(sass())
        .pipe(gulp.dest('./assets/css'))
        .pipe(rename('admin.min.css'))
        .pipe(cssmin())
        .pipe(gulp.dest('./assets/css'));
});

gulp.task('js-app', function() {
    return gulp.src('./assets/js/src/**/*.js')
        .pipe(concat('app.js'))
        .pipe(gulp.dest('./assets/js'))
        .pipe(rename('app.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('./assets/js'));
});

gulp.task('js-vendor', function() {
    return gulp.src('./assets/js/vendor/*.min.js')
        .pipe(concat('vendor.min.js'))
        .pipe(gulp.dest('./assets/js'));
});

gulp.task('js', gulp.series('js-app', 'js-vendor'));

gulp.task('default', gulp.series('css', 'js'));
