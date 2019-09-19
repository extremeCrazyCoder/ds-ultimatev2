const mix = require('laravel-mix');
require('laravel-mix-purgecss');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .copy('resources/sass/plugins/flag-icon-css/flags', 'public/images/flags', false)
    .options({
        processCssUrls: false
    })
    .purgeCss({
        whitelistPatterns: [/(flag-icon.*|col-.*|.*pagination.*|.*page.*)/],
    });


/*
 * jqueryUi + jquery.countdown
 */
mix.copy('node_modules/jquery-ui-dist/jquery-ui.min.css', 'public/plugin/jquery-ui/jquery-ui.min.css');
mix.copy('node_modules/jquery-ui-dist/jquery-ui.min.js', 'public/plugin/jquery-ui/jquery-ui.min.js');

mix.js('node_modules/jquery.countdown/jquery.countdown.js', 'public/plugin/jquery.countdown/jquery.countdown.min.js');

/*
 * Tinymce (wysiwyg editor for backend)
 */
mix.copyDirectory('node_modules/tinymce/plugins', 'public/plugin/tinymce/plugins');
mix.copyDirectory('node_modules/tinymce/skins', 'public/plugin/tinymce/skins');
mix.copyDirectory('node_modules/tinymce/themes', 'public/plugin/tinymce/themes');
mix.copy('node_modules/tinymce/jquery.tinymce.min.js', 'public/plugin/tinymce/jquery.tinymce.min.js');
mix.copy('node_modules/tinymce/tinymce.min.js', 'public/plugin/tinymce/tinymce.min.js');

/*
 * Fonticonpicker
 */
mix.copy('node_modules/@fonticonpicker/fonticonpicker/dist/css/base/jquery.fonticonpicker.min.css', 'public/plugin/fontIconPicker/jquery.fonticonpicker.min.css');
mix.copy('node_modules/@fonticonpicker/fonticonpicker/dist/css/themes/bootstrap-theme/jquery.fonticonpicker.bootstrap.min.css', 'public/plugin/fontIconPicker/jquery.fonticonpicker.bootstrap.min.css');
mix.copy('node_modules/@fonticonpicker/fonticonpicker/dist/js/jquery.fonticonpicker.min.js', 'public/plugin/fontIconPicker/jquery.fonticonpicker.min.js');
mix.copyDirectory('node_modules/@fonticonpicker/fonticonpicker/dist/fonts', 'public/fonts');


/*
 * Colour Picker
 */
mix.copy('node_modules/bootstrap-colorpicker/dist/css/bootstrap-colorpicker.min.css', 'public/plugin/bootstrap-colorpicker/bootstrap-colorpicker.min.css');
mix.copy('node_modules/bootstrap-colorpicker/dist/js/bootstrap-colorpicker.min.js', 'public/plugin/bootstrap-colorpicker/bootstrap-colorpicker.min.js');

/*
 * Select2 + theme
 */
mix.copy('node_modules/select2/dist/css/select2.min.css', 'public/plugin/select2/select2.min.css');
mix.copy('node_modules/select2/dist/js/select2.full.min.js', 'public/plugin/select2/select2.full.min.js');
mix.copy('node_modules/@ttskch/select2-bootstrap4-theme/dist/select2-bootstrap4.min.css', 'public/plugin/select2/select2-bootstrap4.min.css');

/*
 * Datatables
 */

mix.copy('node_modules/datatables.net/js/jquery.dataTables.min.js', 'public/plugin/datatables/jquery.datatables.min.js');
mix.copy('node_modules/datatables.net-bs4/js/dataTables.bootstrap4.min.js', 'public/plugin/datatables/dataTables.bootstrap4.min.js');
mix.copy('node_modules/datatables.net-bs4/css/dataTables.bootstrap4.min.css', 'public/plugin/datatables/dataTables.bootstrap4.min.css');
mix.copy('node_modules/datatables.net-responsive/js/dataTables.responsive.min.js', 'public/plugin/datatables/dataTables.responsive.min.js');
mix.copy('node_modules/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js', 'public/plugin/datatables/dataTables.responsive.bootstrap.min.js');
mix.copy('node_modules/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css', 'public/plugin/datatables/dataTables.responsive.bootstrap4.min.css');