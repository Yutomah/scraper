<?php
require_once 'vendor/autoload.php';

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

    public static function getScrapedLots($howManyLots)
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

        //Ждём загрузки
        self::$client->waitFor(".block-item-row", 50);

        //Открываем dropdown menu
        self::$crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();


        //Ждём пока оно откроется и выбираем пункт
        self::$client->waitForVisibility("[data-text='ЖД, авиа, авто, контейнерные перевозки']", 5);
        self::$crawler->filter("[data-text='ЖД, авиа, авто, контейнерные перевозки']")->click();

        //Закрываем dropdown menu, чтобы оно не перекрывало кнопку поиска
        self::$crawler->filter("#ClassifiersFieldData_SiteSectionType")->eq(1)->click();

        //Нажимаем поиск
        self::$crawler->filter("div > div > [type='submit']")->click();

        //Ждём результатов поиска
        self::waitForLotUpdate();

        //избавляемся от кнопки cookie
        self::$crawler->filter('.btn-accept-cookie')->click();
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
        $scrapedLots = [];
        $lotCounter = 0;
        while (true) {

            $scrapedPageItems = self::scrapeResultPage($lotCounter, $howManyLots);
            $scrapedLots = array_merge($scrapedLots, $scrapedPageItems);


            $nextPageButton = self::$crawler->filter(".pagination")->last()->children()->last();
            $classStr = $nextPageButton->attr('class');
            if (strpos($classStr, 'disabled') === false && (is_null($howManyLots) || $lotCounter < $howManyLots)) {
                $nextPageButton->click();
                self::waitForLotUpdate();
            } else {
                break;
            }
        }
        return $scrapedLots;
    }

    private static function scrapeResultPage(&$lotCounter, $howManyLots)
    {
        //Под result page я имею ввиду страницу, которую можно перелистывать с помощью стрелок внизу сайта
        $scrapedLots = [];
        self::$crawler->filter(".block-item-row")->each(function ($lot) use (&$scrapedLots, &$lotCounter, $howManyLots) {

            if (!is_null($howManyLots) && $lotCounter >= $howManyLots) {
                return;
            }

            $lotNumber = $lot->filter("a")->eq(0)->text();
            $lotNumber = substr($lotNumber, 4);

            $organizer = $lot->filter("a")->eq(1)->text();

            $lotLink = $lot->filter("a")->eq(2)->attr("href");
            $lotLink = self::$websiteMainPage . $lotLink;

            $scrapedLot = [
                "lotNumber" => $lotNumber,
                "organizer" => $organizer,
                "lotLink" => $lotLink,
            ];

            $scrapedLots[] = $scrapedLot;

            $lotCounter++;
        });

        return $scrapedLots;
    }
    private static function scrapeLotPages($scrapedLots)
    {
        //Под lot page имеется ввиду страницу, с подробной информацией для конкретного лота
        foreach ($scrapedLots as &$scrapedLot) {
            $crawler = self::$client->request("GET", $scrapedLot["lotLink"]);

            $scrapedLot["beginDate"] = $crawler->filter("[data-field-name='Fields.QualificationBeginDate']")->text();

            $scrapedLot["files"] = self::scrapeFiles($crawler);
        }

        return $scrapedLots;
    }
    private static function scrapeFiles($crawler)
    {
        self::$client->waitFor(".document-list", 10);

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

    private static function waitForLotUpdate()
    {
        //Есть два варианта как понять, что лоты обновились 
        //1. Это что значение у лота что ты запомнил и лота который ты прочитал разные
        //2. Если при попытке чтения ты получил stale element reference error


        try {
            $lotNumber = self::$crawler->filter(".block-item-row")->eq(0)->filter("a")->eq(0)->text();
            while (true) {
                $newLotNumber = self::$crawler->filter(".block-item-row")->eq(0)->filter("a")->eq(0)->text();
                if ($lotNumber !== $newLotNumber) {
                    return;
                }
                sleep(0.1);
            }
        } catch (Throwable $err) {
            return;
        }
    }
}
