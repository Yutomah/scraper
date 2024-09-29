<?php

require_once('scraper.class.php');
require_once('model.class.php');
require_once('config.php');

echo "Это приложение для скрапинга страницы https://tender.rusal.ru/Tenders\n";
echo "Ведите число лотов, которое вы хотите соскрапить.\n";
echo "Введите слово 'все', для того чтобы соскрапить все лоты.\n";
while (true) {

    $lotAmount = readline("Число лотов: ");

    if (strtolower($lotAmount) === 'все') {
        $lotAmount = null;
        break;
    }

    if (is_numeric($lotAmount) && ctype_digit($lotAmount)) {
        $lotAmount = (int)$lotAmount;
        if ($lotAmount > 0) {
            break;
        }
    }

    echo "Неверные входные данные \n";
}

echo 'Скрапинг начался';

Scraper::initialize();
$scrapedLots = Scraper::getScrapedLots($lotAmount);

echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++\n";

foreach ($scrapedLots as $lot) {
    echo 'Номер лота: ' . $lot['lotNumber'] . "\n";
    echo 'Организатор: ' . $lot['organizer'] . "\n";
    echo 'Ссылка на страницу процедуры: ' . $lot['lotLink'] . "\n";
    echo 'Дата подачи заявок: ' . $lot['beginDate'] . "\n";

    echo "Прикреплённые файлы: \n";
    foreach ($lot['files'] as $file) {
        echo '----Название: ' . $file['name'] . "\n";
        echo '----Ссылка: ' . $file['link'] . "\n\n";
    }
    echo "\n++++++++++++++++++++++++++++++++++++++++++++++++++++++\n\n";
}
echo "Количество найденых лотов: " . count($scrapedLots) . "\n";

Model::initialize(CONFIG);
$result = Model::add_items($scrapedLots);

echo $result ? "Данные были успешно добавлены в базу данных \n" : "Данные не удалось добавить в базу данных\n";
