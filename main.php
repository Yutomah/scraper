<?php

require('scraper.class.php');

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
$scrapedData = Scraper::getScrapedData($lotAmount);

echo "++++++++++++++++++++++++++++++++++++++++++++++++++++++\n";

foreach ($scrapedData as $item) {
    echo 'Номер лота: ' . $item['lotNumber'] . "\n";
    echo 'Организатор: ' . $item['organizer'] . "\n";
    echo 'Ссылка на страницу процедуры: ' . $item['lotLink'] . "\n";
    echo 'Дата подачи заявок: ' . $item['beginDate'] . "\n";

    echo "Прикреплённые файлы: \n";
    foreach ($item['files'] as $file) {
        echo '----Название: ' . $file['name'] . "\n";
        echo '----Ссылка: ' . $file['link'] . "\n\n";
    }
    echo "\n++++++++++++++++++++++++++++++++++++++++++++++++++++++\n\n";
}
