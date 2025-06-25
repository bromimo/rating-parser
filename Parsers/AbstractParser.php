<?php

namespace Parsers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

abstract class AbstractParser
{
    protected Client $client;
    protected string $baseUrl;

    public function __construct(protected string $url)
    {
        $parts = parse_url($url);
        $this->baseUrl = $parts['scheme'] . '://' . $parts['host'] . '/';
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Wayland like X11; U; Linux; rv:132.0esr) Gecko/20170501 Firefox/132.0esr',
                'Accept'     => 'text/html,application/xhtml+xml',
            ],
            'timeout' => 10,
            'verify' => false,
        ]);
    }

    protected function whenEmpty(mixed $source, mixed $default = ''): mixed
    {
        return empty($source) ? $default : $source;
    }

    protected function getPageHtml(string $url): ?string
    {
        try {
            $response = $this->client->get($url);
            return $response->getBody()->getContents();
        } catch (RequestException $e) {
            if ($e->hasResponse() && $e->getResponse()->getStatusCode() === 404) {
                return null;
            }

            throw $e;
        }
    }
}