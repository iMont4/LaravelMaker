# LaravelMaker

[![stable][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Make and generate Laravel "Controller, Request, Model, Factory, Seed, Migration, Translation, Policy & Permissions" compatible.

## Installation

Via Composer

``` bash
$ composer require mont4/laravelmaker
$ php artisan vendor:publish --provider="Mont4\LaravelMaker\LaravelMakerServiceProvider" --tag="migrations"
```

## Usage

``` bash
$ php artisan make:all
$ php artisan permission:sync
$ php artisan make:method
```
## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [Mohammad Montazeri][link-author]
- [Mahdi Yousefi](https://github.com/MahdiY)
- [Contributors][link-contributors]

## License

This plugin is open-sourced package licensed under the [MIT license](https://opensource.org/licenses/MIT).

[ico-version]: https://img.shields.io/packagist/v/mont4/laravelmaker.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/mont4/laravelmaker.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/mont4/laravelmaker/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/146006126/shield

[link-packagist]: https://packagist.org/packages/mont4/laravelmaker
[link-downloads]: https://packagist.org/packages/mont4/laravelmaker
[link-travis]: https://travis-ci.org/mont4/laravelmaker
[link-styleci]: https://github.styleci.io/repos/146006126
[link-author]: https://github.com/Mont4
[link-contributors]: ../../contributors]