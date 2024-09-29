<?php
require 'vendor/autoload.php';

use Symfony\Component\Panther\Client;

class Scraper
{

    private static $websiteMainPage;
    private static $websiteTendersPage;

    private static $client;
    private static $crawler;

    private static $scrolling_script;


    public static function initialize()
    {
        self::$websiteMainPage = 'https://tender.rusal.ru';
        self::$websiteTendersPage = 'https://tender.rusal.ru/Tenders';

        self::$client = Client::createChromeClient();
        self::$crawler = self::$client->request("GET", self::$websiteTendersPage);

        self::$scrolling_script = <<<EOD
        window.scrollTo(0, document.body.scrollHeight)
        EOD;
    }

    public static function getScrapedData($howManyLots)
    {
        self::filterWebsite();
        return self::scrapeWebsite($howManyLots);
    }

    private static function filterWebsite()
    {

        //открываем доп параметры
        self::$crawler->filter("#additional-fields-trigger")->eq(0)->click();

        //Прокручиваем страницу, чтобы кнопки были на экране, иначе всё ломается
        self::$client->executeScript(self::$scrolling_script);

        //Открываем dropdown menu
        self::$crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();

        //Ждём пока оно откроется и выбираем пункт
        self::$client->waitForVisibility("[data-text='ЖД, авиа, авто, контейнерные перевозки']", 5);
        self::$crawler->filter("[data-text='ЖД, авиа, авто, контейнерные перевозки']")->click();

        //Закрываем dropdown menu, чтобы оно не перекрывало кнопку поиска
        self::$crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();

        //Нажимаем поиск
        self::$crawler->filter("div > div > [type='submit']")->click();

        //избавляемся от кнопки cookie
        self::$crawler->filter('.btn-accept-cookie')->click();

        //Ждём загрузки
        sleep(3);
    }

    private static function scrapeWebsite($howManyLots)
    {
        self::$client->executeScript(self::$scrolling_script);

        $scrapedItems = self::scrapeTendersPage($howManyLots);
        $scrapedItems = self::scrapeLotPages($scrapedItems);
        return $scrapedItems;
    }

    private static function scrapeTendersPage($howManyLots)
    {
        $scrapedItems = [];
        $lotCounter = 0;
        while (true) {
            try {
                $scrapedPageItems = self::scrapeResultPage($lotCounter, $howManyLots);
                $scrapedItems = array_merge($scrapedItems, $scrapedPageItems);
            } catch (Throwable $error) {
                //Время от времени может происходить ошибка StaleElementReference или как-то так, 
                //Этот try catch нужен, для того чтобы запускать обработку страницы заново в этом случае
                continue;
            }

            $nextPageButton = self::$crawler->filter(".pagination")->last()->children()->last();
            $classStr = $nextPageButton->attr('class');
            if (strpos($classStr, 'disabled') === false && (is_null($howManyLots) || $lotCounter < $howManyLots)) {
                $nextPageButton->click();
            } else {
                break;
            }
        }
        return $scrapedItems;
    }

    private static function scrapeResultPage(&$lotCounter, $howManyLots)
    {

        $scrapedItems = [];
        self::$crawler->filter(".block-item-row")->each(function ($item) use (&$scrapedItems, &$lotCounter, $howManyLots) {

            if (!is_null($howManyLots) && $lotCounter >= $howManyLots) {
                return;
            }

            $lotNumber = $item->filter("a")->eq(0)->text();
            $lotNumber = substr($lotNumber, 2);

            $organizer = $item->filter("a")->eq(1)->text();

            $lotLink = $item->filter("a")->eq(2)->attr("href");
            $lotLink = self::$websiteMainPage . $lotLink;

            $scrapedItem = [
                "lotNumber" => $lotNumber,
                "organizer" => $organizer,
                "lotLink" => $lotLink,
            ];

            $scrapedItems[] = $scrapedItem;

            $lotCounter++;
        });

        return $scrapedItems;
    }
    private static function scrapeLotPages($scrapedItems)
    {
        foreach ($scrapedItems as &$scrapedItem) {
            $crawler = self::$client->request("GET", $scrapedItem["lotLink"]);

            $scrapedItem["beginDate"] = $crawler->filter("[data-field-name='Fields.QualificationBeginDate']")->text();
            $scrapedItem["files"] = self::scrapeFiles($crawler);
        }

        return $scrapedItems;
    }
    private static function scrapeFiles($crawler)
    {
        self::$client->waitFor(".document-list", 2);

        $scrapedFiles = [];
        $crawler->filter(".file-download-link")->each(function ($link) use (&$scrapedFiles) {
            $scrapedFileLink = self::$websiteMainPage . $link->attr("href");
            $scrapedFileName = $link->text();

            $scrapedFile = [
                "name" => $scrapedFileName,
                "link" => $scrapedFileLink,
            ];

            $scrapedFiles[] = $scrapedFile;
        });
        return $scrapedFiles;
    }
}
