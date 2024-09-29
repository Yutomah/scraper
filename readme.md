# Запуск программы

На компьютере должны быть установлены php, composer, my_sql и google chrome.

Нужно открыть консоль, перейти в папку программы и ввести следующие команды:

```
composer update
vendor/bin/bdi detect drivers

$Env:PANTHER_NO_HEADLESS=1
$Env:PANTHER_DEVTOOLS=0

php main.php
```

`composer update` - скачивает все зависимости.

`vendor/bin/bdi detect drivers` - ищет драйверы, которые были скачаны командой `composer update`.


`$Env:PANTHER_NO_HEADLESS=1` - при запуске программы запускает браузер и показывает все действия программы в реальном времени. 

`$Env:PANTHER_DEVTOOLS=0` - заставляет программу не выводить ошибки самого сайта в консоль программы.

`php main.php` - запускает программу.


# Вывод информации в базу данных

Нужно поменять значения в файле config.php. На подходящие вам.
