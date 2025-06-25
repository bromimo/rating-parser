<?php

namespace Services;

use Google\Client;
use Dto\Tagline\RowDto;
use Dto\Sostav\TableDto;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Dto\RatingRuneta\RatingRunetaDto;

class GoogleSheetService
{
    protected Client $client;
    protected string $spreadsheetId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(__DIR__ . '/../credentials.json');
        $this->client->addScope(Sheets::SPREADSHEETS);
        $this->spreadsheetId = '1YUdq500TfA2wiCEnPWMzROzUSXHyyb__nDWFL4UcmTg';
    }

    public function exportRatingRuneta(array $data, string $sheetName = 'ratingruneta'): void
    {
        $service = new Sheets($this->client);
        $headers = [
            'Место',
            'Название',
            'Описание',
            'Сайт',
            'Адрес',
            'Телефон',
            'Заказчиков',
            'Крупных заказчиков',
            'Ценовой диапазон',
        ];
        $values = [$headers];

        foreach ($data as $row) {
            if (!$row instanceof RatingRunetaDto) {
                continue;
            }
            $values[] = [
                $row->place,
                $row->name,
                $row->desc,
                $row->site,
                $row->address,
                $row->phone,
                $row->clients_count,
                $row->hr_clients_count,
                $row->price_range,
            ];
        }

        $range = $sheetName . '!A1';

        $body = new ValueRange([
            'range'  => $range,
            'values' => $values,
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
    }

    public function exportSostav(array $data, string $sheetName = 'sostav'): void
    {
        $service = new Sheets($this->client);
        $headers = [
            'Рейтинг',
            'Ссылка на рейтинг',
            'Место',
            'Компания',
            'Описание',
            'Сайт',
            'Адрес',
            'Телефон',
            'Email',
            'Баллы',
        ];
        $values = [$headers];

        foreach ($data as $row) {
            if (!$row instanceof TableDto) {
                continue;
            }
            foreach ($row->rows as $company) {
                $values[] = [
                    $row->title ?? '',
                    $row->url ?? '',
                    $company->place ?? '',
                    $company->name ?? '',
                    $company->company?->desc ?? '',
                    $company->company?->url ?? '',
                    $company->company?->address ?? '',
                    $company->company?->phone ?? '',
                    $company->company?->email ?? '',
                    $company->score ?? '',
                ];
            }
        }

        $range = $sheetName . '!A1';

        $body = new ValueRange([
            'range'  => $range,
            'values' => $values,
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
    }

    public function exportTagline(array $data, string $sheetName = 'tagline'): void
    {
        $service = new Sheets($this->client);
        $headers = [
            'Место',
            'Компания',
            'Описание',
            'Сайт',
            'Адрес',
            'Год основания',
            'Количество сотрудников',
            'Клиенты',
            'Ценовой диапазон',
        ];
        $values = [$headers];

        foreach ($data as $company) {
            if (!$company instanceof RowDto) {
                continue;
            }
            $values[] = [
                $company->place ?? '',
                $company->name ?? '',
                $company->company->desc ?? '',
                $company->company->url ?? '',
                $company->company->address ?? '',
                $company->year_foundation ?? '',
                $company->staff ?? '',
                $company->company->clients ?? '',
                $company->price_range ?? '',
            ];
        }

        $range = $sheetName . '!A1';

        $body = new ValueRange([
            'range'  => $range,
            'values' => $values,
        ]);

        $params = ['valueInputOption' => 'RAW'];

        $service->spreadsheets_values->update($this->spreadsheetId, $range, $body, $params);
    }
}