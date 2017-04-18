var gulp = require('gulp');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var sass = require('gulp-sass');
var merge = require('merge-stream');
var concatCss = require('gulp-concat-css');
var cleanCss = require('gulp-clean-css');
var flatten = require('gulp-flatten');
var bust = require('gulp-buster');

var cssPath = './web/css';
var jsPath = './web/js';
var fontPath = './web/fonts';

gulp.task('process-css', function() {
    var sassStream,
        cssStream;

    sassStream = gulp.src('./src/**/*.scss')
        .pipe(sass({errLogToConsole: true}))
    ;

    cssStream = gulp.src([
        './node_modules/bootstrap/dist/css/bootstrap.css',
        './node_modules/bootstrap/dist/css/bootstrap-theme.css',
    ]);

    return merge(cssStream, sassStream)
        .pipe(concatCss('minified.css'))
        .pipe(cleanCss())
        .pipe(flatten())
        .pipe(gulp.dest(cssPath))
        .pipe(bust())
        .pipe(gulp.dest('.'));
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
        .pipe(bust())
        .pipe(gulp.dest('.'));
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
