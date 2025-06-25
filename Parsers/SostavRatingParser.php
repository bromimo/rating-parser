<?php

namespace Parsers;

use DOMNode;
use DOMXPath;
use Exception;
use DOMElement;
use DOMDocument;
use DOMNodeList;
use Dto\Sostav\RowDto;
use Dto\Sostav\TableDto;
use Dto\Sostav\CompanyDto;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class SostavRatingParser extends AbstractParser
{
    public function parse(): array
    {
        echo "Парсинг {$this->url}" . PHP_EOL;
        $result = [];
        $ratings = $this->getRatings();
        echo 'Найдено ' . count($ratings) . ' рейтингов.' . PHP_EOL;
        foreach ($ratings as $rating) {
            $result[] = $this->parseTable($rating);
        }

        return $result;
    }

    public function getRatings(): DomNodeList
    {
        $html = $this->getPageHtml($this->url);
        $html = $this->fixHtml($html);

        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $xpath = new DOMXPath($dom);

        return $xpath->query('//div[contains(@class, "page-tables__table")]');
    }

    /** Возвращает таблицу рейтинга.
     * @throws UnknownProperties
     */
    protected function parseTable(DOMNode $container): TableDto
    {
        $dom = $container->ownerDocument;
        $xpath = new DOMXPath($dom);

        $title = '';
        $url = '';
        $aNode = $xpath->query('.//h3[contains(@class, "r-table-header")]/a', $container)->item(0);
        if ($aNode) {
            $textParts = [];
            foreach ($aNode->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $textParts[] = trim($child->nodeValue);
                }
            }
            $title = implode(' ', array_filter($textParts));
            $url = $aNode->getAttribute('href') ?? '';
            echo " + {$title} ";
        }

        $table = $xpath->query('.//table[contains(@class, "r-table")]', $container)->item(0);
        if (!$table) {
            throw new Exception('Не найдена таблица внутри контейнера рейтинга');
        }

        $rows = [];
        $trs = $table->getElementsByTagName('tr');

        foreach ($trs as $index => $tr) {
            if ($index === 0) {
                continue;
            }
            $rows[] = $this->parseRow($tr);
            echo '.';
        }
        echo PHP_EOL;

        return new TableDto(compact("title", "url", "rows"));
    }

    /** Возвращает строку таблицы.
     * @throws UnknownProperties
     */
    protected function parseRow(DOMElement $tr): RowDto
    {
        $tds = $tr->getElementsByTagName('td');
        $place = intval($tds[0]->textContent);
        $a = $tds[1]->getElementsByTagName('a')->item(0);
        $name = '';
        if ($a) {
            $profile = $this->getProfileLink($a);
            $name = trim($a->getElementsByTagName('span')->item(1)->textContent ?? '');
        } else {
            $profile = '';
            foreach ($tds[1]->getElementsByTagName('span') as $span) {
                if ($span->getAttribute('class') === 'name') {
                    $name = trim($span->textContent);
                    break;
                }
            }
        }

        $score = intval(str_replace(' ', '', $tds[2]->textContent));
        $company = $this->getProfile($profile);

        return new RowDto(compact('place', 'name', 'profile', 'score', 'company'));
    }

    /**
     * @throws UnknownProperties
     */
    protected function getProfile(string $url): ?CompanyDto
    {
        if (empty($url)) {
            return null;
        }

        $html = $this->getPageHtml("{$this->baseUrl}{$url}");
        if (!$html) {
            return null;
        }
        $html = $this->fixHtml($html);

        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        $data = [];

        $node = $xpath->query('//h1[contains(@class, "b-title")]')->item(0);
        $data['name'] = trim($node->textContent ?? '');

        $node = $xpath->query('//p[contains(@class, "company-info")]')->item(0);
        $data['desc'] = trim(preg_replace('/\s+/u', ' ', html_entity_decode($node->textContent ?? '')));

        $nodes = $xpath->query('//div[contains(@class, "sub") and contains(@class, "with-icon")]');
        if ($nodes->length === 0) {
            return null;
        }

        foreach ($nodes as $node) {
            $icon = $xpath->query('.//span[contains(@class, "icon")]', $node)->item(0);
            $iconClass = $icon?->getAttribute('class') ?? '';

            $type = match (true) {
                str_contains($iconClass, 'addr') => 'address',
                str_contains($iconClass, 'phone') => 'phone',
                str_contains($iconClass, 'site') => 'url',
                str_contains($iconClass, 'mail') => 'email',
                default => 'unknown',
            };

            $phDiv = $xpath->query('.//div[contains(@class, "ph")]', $node)->item(0);

            $value = match ($type) {
                'url' => $xpath->query('.//a', $phDiv)->item(0)?->getAttribute('href') ?? '',
                'email' => $this->extractCfEmail($phDiv),
                default => trim($phDiv?->textContent ?? ''),
            };

            $data[$type] = $value;
        }

        return new CompanyDto($data);
    }

    protected function extractCfEmail(?DOMElement $context): string
    {
        if (!$context) {
            return '';
        }

        $span = (new DOMXPath($context->ownerDocument))->query('.//span[@class="__cf_email__"]', $context)->item(0);
        if (!$span) {
            return '';
        }

        $encoded = $span->getAttribute('data-cfemail');
        return $this->decodeCloudflareEmail($encoded);
    }

    protected function decodeCloudflareEmail(string $encoded): string
    {
        $r = hexdec(substr($encoded, 0, 2));
        $email = '';
        for ($i = 2; $i < strlen($encoded); $i += 2) {
            $charCode = hexdec(substr($encoded, $i, 2)) ^ $r;
            $email .= chr($charCode);
        }
        return $email;
    }

    protected function getProfileLink(DOMElement $a): string
    {
        $profile = $a?->getAttribute('href');
        if ($profile === '/') {
            $img = $a->getElementsByTagName('img')->item(0);
            $logo = $img->getAttribute('src') ?? '';
            $filename = basename($logo);
            $id = pathinfo($filename, PATHINFO_FILENAME);
            $profile = "/advmap/agency/{$id}";
        }

        return $profile;
    }

    public function fixHtml(string $html): string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);

        $html = $dom->saveHTML();
        $html = str_replace('class="c-name""', 'class="c-name" rel="nofollow noopener"', $html);
        $html = str_replace('<!-->', '', $html);

        return $html;
    }
}