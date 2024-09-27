<?php
require 'vendor/autoload.php';

$scrolling_script = <<<EOD
window.scrollTo(0, document.body.scrollHeight)
EOD;

use Symfony\Component\Panther\Client;

$client = Client::createChromeClient();
$crawler = $client->request("GET", 'https://tender.rusal.ru/Tenders');

$html = $crawler->html();

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



//создать базу данных
//

function handlePages()
{
    //Для каждой страницы вызвать handlePage;
}
function handlePage()
{
    //Для каждого item на странице вызвать handleItem
}

function handleItem($item)
{
    //Вывести на экран и добавить в дб
    //Номер лота
    //Организатор
    //Ссылку на страницу процедуры

    //Страница процедуры:
    //Дата начала подачи заявок
    //Имя файла и ссылку на него

}

sleep(100);
