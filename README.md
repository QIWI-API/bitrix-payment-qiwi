# QIWI payment gateway for 1C Bitrix

This [1C Bitrix](https://www.1c-bitrix.ru/) module allows store accept payments over [QIWI universal payments protocol](https://developer.qiwi.com/en/bill-payments/).

## Installation

This module depends on 1C Bitrix 18.0 and minimum requires [PHP](https://php.net/) 7.1 witch [cURL extension](https://secure.php.net/manual/en/book.curl.php).

### Manual installation

Please, [check release archive](https://github.com/QIWI-API/bitrix-payment-qiwi/releases), or build package which [Composer from source](#from-source).
The 1С Bitrix documentation contains [instructions how to setup modules on you site](https://dev.1c-bitrix.ru/learning/course/?COURSE_ID=43&LESSON_ID=3475&LESSON_PATH=3913.4609.3475).

### Composer installation

This package provides automatic installation over [Composer](https://getcomposer.org/): 

```bash
composer require qiwi/bitrix-payment-qiwi
```

This method use [Installers extension](http://composer.github.io/installers/) to menage source of your site.

#### From source

Another way is setup module from source.
Get package witch Composer into modules directory of yours site source.

```bash
git clone git@github.com:QIWI-API/bitrix-payment-qiwi.git
cd bitrix-payment-qiwi
composer install --no-dev
``` 
