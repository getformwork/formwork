var gulp   = require('gulp');
var concat = require('gulp-concat');
var rename = require('gulp-rename');
var sass   = require('gulp-sass');
var cssmin = require('gulp-cssmin');
var uglify = require('gulp-uglify');

gulp.task('css', function() {
    return gulp.src('admin/assets/scss/admin.scss')
        .pipe(sass())
        .pipe(gulp.dest('admin/assets/css'))
        .pipe(rename('admin.min.css'))
        .pipe(cssmin())
        .pipe(gulp.dest('admin/assets/css'));
});

gulp.task('js', function() {
    return gulp.src('admin/assets/js/src/**/*.js')
        .pipe(concat('app.js'))
        .pipe(gulp.dest('admin/assets/js'))
        .pipe(rename('app.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest('admin/assets/js'));
});

gulp.task('default', gulp.series('css', 'js'));
