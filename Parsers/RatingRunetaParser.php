<?php

namespace Parsers;

use Exception;
use DOMDocument;
use Dto\RatingRuneta\CompanyDto;
use Dto\RatingRuneta\RatingRunetaDto;
use Spatie\DataTransferObject\Exceptions\UnknownProperties;

class RatingRunetaParser extends AbstractParser
{
    public function parse(): array
    {
        echo "Парсинг {$this->url}" . PHP_EOL;
        $companies = $this->getCompanies();

        echo 'Найдено ' . count($companies) . ' компаний.' . PHP_EOL;
        $result = [];
        foreach ($companies as $company) {
            $result[] = $this->getDetails($company);
            echo '|';
        }
        echo PHP_EOL;

        return $result;
    }

    /** Возвращает массив компаний.
     * @return CompanyDto[]
     * @throws Exception
     */
    public function getCompanies(): array
    {

        $html = $this->getPageHtml($this->url);
        $initialState = $this->extractInitialState($html);
        if (!$initialState) {
            throw new Exception("Отсутствуют данные на странице {$this->url}");
        }
        $data = json_decode($initialState, true);
        $result = [];
        foreach ($data['rating_page_new']['page']['rating']['rating_tables'][0]['rows'] ?? [] as $item) {
            try {
                $result[] = new CompanyDto($item);
            } catch (\Throwable $e) {
            }
        }

        return $result;
    }

    /** Возвращает детальную информацию о компании.
     * @param CompanyDto $company
     * @return RatingRunetaDto
     * @throws UnknownProperties
     */
    public function getDetails(CompanyDto $company): RatingRunetaDto
    {
        $name = $company->name;
        $portfolioLink = $company->portfolio_link;
        $details = $this->parseCompanyDetails($portfolioLink);

        return new RatingRunetaDto([
            'place'            => $company->place,
            'name'             => $name,
            'desc'             => $details['desc'],
            'site'             => $company->link,
            'address'          => $this->whenEmpty($details['office']['address']),
            'phone'            => $this->whenEmpty($details['office']['phone']),
            'clients_count'    => $company->clients_count,
            'hr_clients_count' => $company->hr_clients_count,
            'price_range'      => $details['price_range'],
        ]);
    }

    protected function extractInitialState(string $html): ?string
    {
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $scripts = $dom->getElementsByTagName('script');

        foreach ($scripts as $script) {
            if (str_contains($script->textContent, 'window.__INITIAL_STATE__')) {
                return $this->extractJsonFromScript(str_replace(
                    ['\<', '\.', '\,', '\&', '\","'],
                    ['<', '.', ',', '&', '","'],
                    $script->textContent
                ));
            }
        }

        return null;
    }

    protected function extractJsonFromScript(string $scriptContent): ?string
    {
        $start = strpos($scriptContent, '{');
        $end = strrpos($scriptContent, '}');

        if ($start === false || $end === false || $start >= $end) {
            return null;
        }

        return substr($scriptContent, $start, $end - $start + 1);
    }

    protected function parseCompanyDetails(string $relativeUrl): array
    {
        if (empty($relativeUrl)) {
            return [];
        }

        try {
            $html = $this->getPageHtml('https://ratingruneta.ru' . $relativeUrl);
            $json = $this->extractInitialState($html);
            if (!$json) {
                return [];
            }

            $data = json_decode($json, true);

            $desc = $data['agency']['about']['data']['desc'] ?? '';
            $office = $data['agency']['about']['data']['cities'][0]['offices'][0] ?? [];
            $collection = $data['agencyWall']['collection'] ?? [];
            $agency = array_shift($collection);
            $price_range = $agency['header']['data']['info']['price_range'] ?? '';

            return compact('desc', 'office', 'price_range');
        } catch (\Exception $e) {
            return [];
        }
    }
}
