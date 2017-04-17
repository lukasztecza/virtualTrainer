var gulp = require('gulp');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var concatCss = require('gulp-concat-css');
var cleanCss = require('gulp-clean-css');
var flatten = require('gulp-flatten');

var cssPath = './web/css';
var jsPath = './web/js';
var fontPath = './web/fonts';

gulp.task('process-css', function() {
    return gulp.src([
        './node_modules/bootstrap/dist/css/bootstrap.css',
        './node_modules/bootstrap/dist/css/bootstrap-theme.css',
        './src/**/*.css'
    ])
        .pipe(concatCss('minified.css'))
        .pipe(cleanCss())
        .pipe(flatten())
        .pipe(gulp.dest(cssPath))
    ;
});

gulp.task('process-js', function() {
    return gulp.src([
        './node_modules/jquery/dist/jquery.js',
        './node_modules/bootstrap/dist/js/bootstrap.js',
        './src/**/*.js'
    ])
        .pipe(concat('minified.js'))
        .pipe(uglify())
        .pipe(flatten())
        .pipe(gulp.dest(jsPath))
    ;
});

gulp.task('process-font', [], function() {
    return gulp.src('./node_modules/bootstrap/dist/fonts/glyphicons-halflings-regular.*')
        .pipe(flatten())
        .pipe(gulp.dest(fontPath))
    ;
});

gulp.task('watch', function() {
    gulp.watch('./src/**/*.js', ['process-js']);
    gulp.watch('./src/**/*.css', ['process-css']);
});

gulp.task('default', ['process-css', 'process-js', 'process-font']);
