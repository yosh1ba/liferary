// npx gulp で監視開始

var gulp = require( 'gulp' );
var sass = require( 'gulp-sass' );
var plumber = require( 'gulp-plumber' );
var notify = require( 'gulp-notify' );
var sassGlob = require( 'gulp-sass-glob' );
var mmq = require( 'gulp-merge-media-queries' );
var gulpStylelint = require( 'gulp-stylelint' );
var postcss = require( 'gulp-postcss' );
var autoprefixer = require( 'autoprefixer' );
var cssdeclsort = require( 'css-declaration-sorter' );
var wait  = require('gulp-wait');

gulp.task( 'default', function() {
    return gulp.watch('scss/**/*.scss', function() {
        return (
      gulp
        .src( 'scss/style.scss' )
        .pipe( wait( 1000 ))
        .pipe( plumber({ errorHandler: notify.onError( 'Error: <%= error.message %>' ) }) )
        .pipe( sassGlob() )
        .pipe( sass({ outputStyle: 'expanded' }) )
        .pipe( postcss([ autoprefixer() ]) )
        //.pipe( postcss([ cssdeclsort({ order: 'alphabetically' }) ]) )
        .pipe( mmq() )
        .pipe( gulp.dest( 'css/' ) )
        );
    });
});
