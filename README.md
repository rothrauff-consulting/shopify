# A PHP wrapper for the Shopify API

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rothrauff-consulting/shopify.svg?style=flat-square)](https://packagist.org/packages/rothrauff-consulting/shopify)
[![Build Status](https://img.shields.io/travis/rothrauff-consulting/shopify/master.svg?style=flat-square)](https://travis-ci.org/rothrauff-consulting/shopify)
[![Quality Score](https://img.shields.io/scrutinizer/g/rothrauff-consulting/shopify.svg?style=flat-square)](https://scrutinizer-ci.com/g/rothrauff-consulting/shopify)
[![Total Downloads](https://img.shields.io/packagist/dt/rothrauff-consulting/shopify.svg?style=flat-square)](https://packagist.org/packages/rothrauff-consulting/shopify)

Currently supports version 2019-10 of the api, and will support all released versions.

## Installation

You can install the package via composer:

```bash
composer require rothrauff-consulting/shopify
```

## Usage

``` php
use RothrauffConsulting\Shopify\Shopify;

$shopify = new Shopify('store_url', 'app_api_key', 'app_password');
```

or to specify an api version:

``` php
use RothrauffConsulting\Shopify\Shopify;

$shopify = new Shopify('store_url', 'app_api_key', 'app_password', 'api_version');
```

Examples:

``` php
$shopify->get('products');
$shopify->get('products', ['fields' => 'id,title']);

$shopify->post('products', [
    'product' => [
        //new product
    ]
]);

$shopify->put('products/{id}', [
    'product' => [
        //update product
    ]
]);

$shopify->delete('products/{id}');
```

Some delete requests require parameters, e.g., themes:

``` php
$shopify->delete('themes/{theme_id}/assets', ['asset[key]' =>  'asset_key']);
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email developers@rothrauffconsulting.com instead of using the issue tracker.

## Credits

- [Matthew Myers](https://github.com/mxm1070)
- [Developers](https://github.com/rothrauff-consulting)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## PHP Package Boilerplate

This package was generated using the [PHP Package Boilerplate](https://laravelpackageboilerplate.com).