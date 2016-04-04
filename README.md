# Container
Yet another service container

Container **DOES NOT** use reflection and type hint information in any form.

## Features
Container keep two kind of values: services and parameters.

Service will instantiated when you call call it by name. _Usually_ service is object type, but not nesessary. Mind it as
"product" of some routine.
Methods: `get()` and `set()`.

Parameters are not instantiating, they just stored and retrieved.
Methods: `getParameter()` and `setParameter()`.

## Basic Usage

There are special configuraton sections:

- `parameters` - used to store basic values, which can be substituted into service definition.
- `shared` - defines shared services. after it created once it can be retrieved many times.
- `multiple` - new instance created on every get() call

In other sections you can store any kind of information and retrieve it in dot notation (see below).

Simple service definition:
```php
use R2\DependancyInjection\Container;

$config = [
  'shared' => [
    'view' => R2\Templating\Dirk::class
  ]
];
$c = new Container($config);
$c->get('view')->render('index');
```
Parameters substitution:
```php
$config = [
  'parameters' => [
    'root' => '\var\www\mysite',
  ],
  'shared' => [
    'view' => [
      'class=' => R2\Templating\Dirk::class,
      'options' => [
          'views' => '%root%/views',
          'cache' => '%root%/cache',
      ],
      ...
  ]
];
...
$c->get('view')->render('index');
```
Factory method:
```php
$config = [
  'shared' => [
    'userManager' => App\UserManager::class,
    'user' => '@userManager:getCurrentUser',
    ...
  ]
]
...
echo $c->get('user')->username;
```

Extra data accessible in dot notation:
```php
$config = [
  'options' => [
    'cookie' => [
      'name' => 'the-cookie',
      'domain' => '.example.com'
    ]
];
...
setcookie(
  $c->getParameter('cookie.name'),
  $value,
  0,
  '/',
  $c->getParameter('cookie.domain')
);

```