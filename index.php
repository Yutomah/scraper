<?php
require 'vendor/autoload.php';

$scrolling_script = <<<EOD
window.scrollTo(0, document.body.scrollHeight)
EOD;

use Symfony\Component\Panther\Client;

$client = Client::createChromeClient();
$crawler = $client->request("GET", 'https://tender.rusal.ru/Tenders');

filterWebsite();
scrapWebsite();

function filterWebsite()
{
    global $client, $crawler, $scrolling_script;
    //открываем доп параметры
    $crawler->filter("#additional-fields-trigger")->eq(0)->click();

    //Прокручиваем страницу, чтобы кнопки были на экране, иначе всё ломается
    $client->executeScript($scrolling_script);

    //Открываем dropdown menu
    $crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();

    //Ждём пока оно откроется и выбираем пункт
    $client->waitForVisibility("[data-text='ЖД, авиа, авто, контейнерные перевозки']", 5);
    $crawler->filter("[data-text='ЖД, авиа, авто, контейнерные перевозки']")->click();

    //Закрываем dropdown menu, чтобы оно не перекрывало кнопку поиска
    $crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();

    //Нажимаем поиск
    $crawler->filter("div > div > [type='submit']")->click();

    //избавляемся от кнопки cookie
    $crawler->filter('.btn-accept-cookie')->click();
}


function scrapWebsite()
{
    sleep(2);
    global $client, $crawler, $scrolling_script;
    $client->executeScript($scrolling_script);


    while (true) {

        try {
            scrapPage();
        } catch (Throwable $error) {
            echo  "stale element error-------------------------------------------------\n";
            continue;
        }

        $nextPageButton = $crawler->filter(".pagination")->last()->children()->last();
        $classStr = $nextPageButton->attr('class');
        if (strpos($classStr, 'disabled') === false) {
            $nextPageButton->click();
        } else {
            break;
        }
    }
}

function scrapPage()
{
    global $client, $crawler;

    $crawler->filter(".block-item-row")->each(function ($item) {
        $lotNumber = $item->filter("a")->eq(0)->text();
        $organizer = $item->filter("a")->eq(1)->text();
        $lotLink = $item->filter("a")->eq(2)->attr("href");

        echo "$lotNumber $organizer $lotLink------------------------------------\n";
    });



    // foreach ($items as $item) {
    //     scrapItem($item);
    // }


    //Для каждого item на странице вызвать handleItem
}

//создать базу данных
//

function scrapItem($item)
{
    global $client, $crawler;
    echo var_dump($item);
    // $name = $item->eq(1)->children()->eq(0)->children()->eq(1)->text();
    $name = $item->text();

    echo $name . "\n";
    //Вывести на экран и добавить в дб
    //Номер лота
    //Организатор
    //Ссылку на страницу процедуры

    //Страница процедуры:
    //Дата начала подачи заявок
    //Имя файла и ссылку на него

}

sleep(100);



// echo $html;


//sending form
// $form_opener = $crawler->filter("#additional-fields-trigger")->eq(0);
// echo var_dump($form_opener);
// $dropdown_opener = $crawler->filter("#ClassifiersFieldData_SiteSectionType")->text();
// echo $dropdown_opener . "-----------------------------------------------------\n";
// $crawler->click();
// sleep(5);
// $buttons = $crawler->filter("#ClassifiersFieldData_SiteSectionType");
// echo var_dump($buttons);
// foreach ($buttons as $button) {
//     $button->click();
// }
// $buttons = $crawler->filter(".mtree-button-collapse");

// echo count($buttons) . "----------------------++++++++++++++++++++++++++++++++---------------------\n\n\n";
// foreach ($buttons as $button) {
//     $button->click();
//     sleep(1);
// }
// $crawler->filter("#ClassifiersFieldData_SiteSectionType_browseButton div:nth-child(1)");
// $crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();
// echo count($crawler->filter("div > div > [type='submit']")) . "--------------------------------------";
// sleep(5);
// $crawler->filter(".mtree-button-collapse")->last()->click();

// $crawler->filter("#ClassifiersFieldData_SiteSectionType_browseButton > div")->children()->eq(0)->click();
// sleep(5);
// sleep(5);
// $client->waitForVisibility("[type='submit']", 5);