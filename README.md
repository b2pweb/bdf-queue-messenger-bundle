
[![Build Status](https://app.travis-ci.com/b2pweb/bdf-queue-messenger-bundle.svg?branch=master)](https://app.travis-ci.com/b2pweb/bdf-queue-messenger-bundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/b2pweb/bdf-queue-messenger-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/b2pweb/bdf-queue-messenger-bundle/?branch=master)
[![Packagist Version](https://img.shields.io/packagist/v/b2pweb/bdf-queue-messenger-bundle.svg)](https://packagist.org/packages/b2pweb/bdf-queue-messenger-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/b2pweb/bdf-queue-messenger-bundle.svg)](https://packagist.org/packages/b2pweb/bdf-queue-messenger-bundle)

Installation
============

1 Download the Bundle
---------------------

Download the latest stable version of this bundle with composer:

```bash
    $ composer require b2pweb/bdf-queue-messenger-bundle
```

2 Enable the Bundle
-------------------

Adding the following line in the ``config/bundles.php`` file of your project::

```php
<?php
// config/bundles.php

return [
    // ...
    Bdf\QueueMessengerBundle\BdfQueueMessengerBundle::class => ['all' => true],
    // ...
];
```

3 Add configuration
-------------------

Edit the config file to `./config/packages/messenger.yaml`

```yaml
framework:
    messenger:
        transports:
             async: 'bdfqueue://my_bus?consumer_timeout=1'
```
