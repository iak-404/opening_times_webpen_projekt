const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));

function compileSCSS() {
    return gulp.src('assets/scss/**/*scss')
        .pipe(sass({ outputStyle: 'compressed' }).on('error', sass.logError))
        .pipe(gulp.dest('assets/css'));
}

function watchFiles() {
    gulp.watch('assets/scss/**/*.scss', compileSCSS);
}

exports.default = gulp.series(compileSCSS, watchFiles)