<?php
require 'vendor/autoload.php';

$scrolling_script = <<<EOD
window.scrollTo(0, document.body.scrollHeight)
EOD;

use Symfony\Component\Panther\Client;


$websiteTendersPage = 'https://tender.rusal.ru/Tenders';

$client = Client::createChromeClient();

$crawler = $client->request("GET", $websiteTendersPage);
// getScrapedFiles($crawler);

filterWebsite();
scrapWebsite(100);

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


function scrapWebsite($howManyPages = null)
{
    sleep(2);
    global $client, $crawler, $scrolling_script;
    $client->executeScript($scrolling_script);

    $scrapedItems = scrapTendersPage($howManyPages);
    $scrapedItems = scrapeLotPages($scrapedItems);
}

function scrapTendersPage($howManyPages)
{
    global $crawler;
    $scrapedItems = [];
    $i = 0;
    while (true) {
        try {
            $scrapedPageItems = scrapeResultPage();
            $scrapedItems = array_merge($scrapedItems, $scrapedPageItems);
        } catch (Throwable $error) {
            echo  "stale element error-------------------------------------------------\n";
            continue;
        }

        $i++;
        $nextPageButton = $crawler->filter(".pagination")->last()->children()->last();
        $classStr = $nextPageButton->attr('class');
        if (strpos($classStr, 'disabled') === false && (is_null($howManyPages) || $i < $howManyPages)) {
            $nextPageButton->click();
        } else {
            break;
        }
    }

    echo var_dump($scrapedItems);
    echo count($scrapedItems) . "----------------------";
    return $scrapedItems;
}

function scrapeResultPage()
{
    global $client, $crawler;

    $scrapedItems = [];
    $crawler->filter(".block-item-row")->each(function ($item) use (&$scrapedItems) {

        $lotNumber = $item->filter("a")->eq(0)->text();
        $lotNumber = substr($lotNumber, 2);

        $organizer = $item->filter("a")->eq(1)->text();

        $websiteMainPage = 'https://tender.rusal.ru';
        $lotLink = $item->filter("a")->eq(2)->attr("href");
        $lotLink = $websiteMainPage . $lotLink;

        $scrapedItem = [
            "lotNumber" => $lotNumber,
            "organizer" => $organizer,
            "lotLink" => $lotLink,
        ];

        $scrapedItems[] = $scrapedItem;
    });


    return $scrapedItems;
}

function scrapeLotPages($scrapedItems)
{
    global $client;

    foreach ($scrapedItems as &$scrapedItem) {
        $crawler = $client->request("GET", $scrapedItem["lotLink"]);

        $scrapedItem["beginDate"] = $crawler->filter("[data-field-name='Fields.QualificationBeginDate']")->text();
        $scrapedItem["files"] = getScrapedFiles($crawler);
    }

    echo var_dump($scrapedItems) . '---------------------------------\n';
    return $scrapedItems;
}

function getScrapedFiles($crawler)
{
    global $client;
    $client->waitFor(".document-list", 2);

    $scrapedFiles = [];
    $crawler->filter(".file-download-link")->each(function ($link) use (&$scrapedFiles) {
        $websiteMainPage = 'https://tender.rusal.ru';
        $scrapedFileLink = $websiteMainPage . $link->attr("href");
        $scrapedFileName = $link->text();

        $scrapedFile = [
            "fileName" => $scrapedFileName,
            "link" => $scrapedFileLink,
        ];

        $scrapedFiles[] = $scrapedFile;
    });
    echo var_dump($scrapedFiles);
    return $scrapedFiles;
}


//создать базу данных
//


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