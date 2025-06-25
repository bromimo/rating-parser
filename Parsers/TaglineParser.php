<?php

namespace Parsers;

use DOMNode;
use DOMXPath;
use DOMDocument;
use Dto\Tagline\RowDto;
use Enums\PriceRangeEnum;
use Dto\Tagline\CompanyDto;

class TaglineParser extends AbstractParser
{
    public function parse(): array
    {
        echo "Парсинг {$this->url}" . PHP_EOL;
        $html = $this->getPageHtml($this->url);
        $html = $this->fixHtml($html);

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        $companies = $xpath->query('//tr[contains(concat(" ", normalize-space(@class), " "), " n-rating_table-row ") and not(@id="stripe_advert")]');


        echo 'Найдено ' . count($companies) . ' компании.' . PHP_EOL;
        $result = [];
        foreach ($companies as $company) {
            $result[] = $this->parseCompany($company);
            echo '|';
        }
        echo PHP_EOL;

        return $result;
    }

    public function parseCompany(DOMNode $company): RowDto
    {
        $tds = $company->getElementsByTagName('td');

        $a = $tds[0]->getElementsByTagName('a')->item(0);
        if ($a) {
            $place = trim($a->textContent);
        } else {
            $nobr = $tds[0]->getElementsByTagName('nobr')->item(0);
            $place = trim($nobr->textContent);
        }

        $a = $tds[1]->getElementsByTagName('a')->item(0);
        $profile = $a->getAttribute('href');

        $name = $tds[2]->getAttribute('data-cmpname');
        $price_range = PriceRangeEnum::from($tds[2]->getAttribute('data-cost'))->label();

        $staff = trim($tds[3]->textContent);

        $year_foundation = trim($tds[6]->textContent);

        $company = $this->getProfile($profile);

        return new RowDto(compact('place', 'profile', 'name', 'staff', 'price_range', 'year_foundation', 'company'));
    }

    protected function getProfile(string $url): ?CompanyDto
    {
        if (empty($url)) {
            return null;
        }

        $html = $this->getPageHtml($url);
        if (!$html) {
            return null;
        }

        $html = $this->fixHtml($html);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $name = '';
        $node = $xpath->query('//div[contains(@class, "b-company-header")]/h1')->item(0);
        if ($node) {
            $name = trim($node->textContent);
        }

        $url = '';
        $node = $xpath->query('//div[contains(@class, "b-company-info-stripe")][.//span[contains(text(), "Сайт")]]//a')->item(0);
        if ($node) {
            $url = $node->getAttribute('href');
        }

        $address = '';
        $content = $xpath->query('//div[contains(@class, "b-company-info-stripe")][.//span[contains(text(), "Адрес")]]//span[contains(@class, "b-company-info-stripe__content")]')->item(0);
        if ($content) {
            foreach ($content->getElementsByTagName('span') as $child) {
                if (str_contains($child->getAttribute('class'), 'caption')) {
                    $content->removeChild($child);
                    break;
                }
            }
            $address = trim($content->textContent);
        }

        $desc = '';
        $descNode = $xpath->query('//div[contains(@class, "b-description") and contains(@class, "js-description")]')->item(0);
        if ($descNode) {
            $rawHtml = $dom->saveHTML($descNode);
            $innerHtml = strip_tags($rawHtml, '<br>');
            $innerHtml = str_replace('<br>', "\n", $innerHtml);
            $desc = trim(html_entity_decode(strip_tags($innerHtml)));
        }

        $clients = '';
        $content = $xpath->query('//div[contains(@class, "b-company-info-stripe")][.//span[contains(text(), "Клиенты")]]//span[contains(@class, "b-company-info-stripe__content")]')->item(0);
        if ($content) {
            foreach ($content->getElementsByTagName('span') as $child) {
                if (str_contains($child->getAttribute('class'), 'caption')) {
                    $content->removeChild($child);
                    break;
                }
            }
            $clients = trim($content->textContent);
        }

        return new CompanyDto(compact('name', 'url', 'address', 'desc', 'clients'));
    }


    public function fixHtml(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $styles = $dom->getElementsByTagName('style');
        for ($i = $styles->length - 1; $i >= 0; $i--) {
            $style = $styles->item($i);
            $style->parentNode->removeChild($style);
        }
        $html = $dom->saveHTML();

        return $html;
    }
}