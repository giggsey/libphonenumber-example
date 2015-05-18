var elixir = require('laravel-elixir');

var paths = {
    'jquery': './bower_components/jquery/',
    'bootstrap': './bower_components/bootstrap-sass-official/assets/',
    'fontawesome': './bower_components/fontawesome/'
};

elixir.config.publicDir = './';

elixir(function(mix) {
    mix.sass('*', 'css/', {includePaths: [paths.bootstrap + 'stylesheets', paths.fontawesome + 'scss']})
        .copy(paths.bootstrap + 'fonts/bootstrap/**', 'fonts/bootstrap')
        .copy(paths.fontawesome + 'fonts/**', 'fonts/fontawesome')
        .scripts([
            paths.jquery + "dist/jquery.js",
            paths.bootstrap + "javascripts/bootstrap.js",
            './resources/javascripts/**/*.js',
        ], 'js/app.js', './')
        .version([
            'css/app.css',
            'js/app.js'
        ])
});