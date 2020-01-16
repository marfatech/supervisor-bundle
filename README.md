Supervisor Bundle
=============

Введение
--------

Бандл предоставляет возможность использовать любой класс для описания конфигурации супервизора.

Установка
---------

### Шаг 1: Загрузка бандла

Откройте консоль и, перейдя в директорию проекта, выполните следующую команду для загрузки наиболее подходящей
стабильной версии этого бандла:

```bash
    composer require wakeapp/supervisor-bundle
```
*Эта команда подразумевает что [Composer](https://getcomposer.org) установлен и доступен глобально.*

### Шаг 2: Подключение бандла

После включите бандл добавив его в список зарегистрированных бандлов в `app/AppKernel.php` файл вашего проекта:

```php
<?php declare(strict_types=1);
// app/AppKernel.php

class AppKernel extends Kernel
{
    // ...

    public function registerBundles()
    {
        $bundles = [
            // ...

            new Wakeapp\Bundle\SupervisorBundle\WakeappSupervisorBundle(),
        ];

        return $bundles;
    }

    // ...
}
```

Конфигурация
------------

Чтобы начать использовать бандл предварительная конфигурация **не** требуется и имеет следующее значение по умолчанию:

```yaml
wakeapp_supervisor:
    exporter:
        # Supervisor program options могут быть описаны в этом блоке
        program:
            autostart: 'true'
        
        # allows you to specify a program that all commands should be passed to
        executor: php 
        
        # allows you to specify the console that all commands should be passed to
        console: app/console

    # список директорий, в которых будет происходить поиск классов, реализующих аннотацию @Supervisor
    source_directories:
        - 'src'

``` 

Использование
-------------

```php
<?php declare(strict_types=1);

namespace Acme;

use Symfony\Component\Console\Command\Command;
use Wakeapp\Bundle\SupervisorBundle\Annotation\Supervisor;

/**
 * @Supervisor(processes=3, commandName="namespace:command", params="--send", delayBefore=3, delayAfter=5, server="web")
 */
class AcmeCommand extends Command
{
}
```

Лицензия
--------

[![license](https://img.shields.io/badge/License-MIT-green.svg?style=flat-square)](./LICENSE)
