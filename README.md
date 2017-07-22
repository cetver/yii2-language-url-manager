Language Url Manager
=====


[![Build Status](https://travis-ci.org/cetver/yii2-language-url-manager.svg?branch=master)](https://travis-ci.org/cetver/yii2-language-url-manager)
[![Coverage Status](https://coveralls.io/repos/github/cetver/yii2-language-url-manager/badge.svg?branch=master)](https://coveralls.io/github/cetver/yii2-language-url-manager?branch=master)

Parses and creates URLs containing languages

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist cetver/yii2-language-url-manager
```

or add

```
"cetver/yii2-language-url-manager": "^1.0"
```

to the require section of your `composer.json` file.


Usage
-----

Update the web-application configuration file

```php
return [
    'components' => [
        'urlManager' => [
            'class' => 'cetver\LanguageUrlManager\UrlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            /*
             * The list of available languages.
             */
            'languages' => ['en', 'ru'],
            /*
            or
            'languages' => function () {
                return \app\models\Language::find()->select('code')->column();
            },
            */
            /*
             * - true: processes the URL like "en.example.com"
             * - false: processes the URL like "example.com/en"
             * NOTE: If this property set to true, the domain containing a language, must be the first on the left side,
             * for example:
             * - en.it.example.com - is valid
             * - it.en.example.com - is invalid
             */
            'existsLanguageSubdomain' => false,
            /*
             * The regular expression patterns list, applied to path info, if there are matches, the request,
             * containing a language, will not be processed.
             * For performance reasons, the blacklist does not applied for URL creation (Take a look at an example).
             * @see \yii\web\Request::getPathInfo()
             * An example:
             * ```php
             * [
             *     '/^api.*$/'
             * ]
             * ```
             * - Requesting the blacklisted URL
             *   - $existsLanguageSubdomain = true
             *     - en.example.com/api (404 Not Found)
             *     - en.example.com/api/create (404 Not Found)
             *   - $existsLanguageSubdomain = false
             *     - example.com/en/api (404 Not Found)
             *     - example.com/en/api/create (404 Not Found)
             * - Creating the blacklisted URL
             *   - echo \yii\helpers\Html::a('API', ['api/index', Yii::$app->urlManager->queryParam => null]);
             */
            'blacklist' => [],
            /*
             * The query parameter name that contains a language.
             */
            'queryParam' => 'language'
        ],
    ]
];
```

Tests
-----

Run the following commands

```
composer create-project --prefer-source cetver/yii2-language-url-manager
cd yii2-language-url-manager
vendor/bin/codecept run unit
```

For I18N support, take a look at
-------------------------------------
- [https://github.com/cetver/yii2-languages-dispatcher](https://github.com/cetver/yii2-languages-dispatcher) - Sets the web-application language
- [https://github.com/cetver/yii2-language-url-manager](https://github.com/cetver/yii2-language-url-manager) - Parses and creates URLs containing languages
- [https://github.com/cetver/yii2-language-selector](https://github.com/cetver/yii2-language-selector) - Provides the configuration for the language selector
- [https://github.com/creocoder/yii2-translateable](https://github.com/creocoder/yii2-translateable) - The translatable behavior (Active Record)