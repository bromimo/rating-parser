<?php

use Parsers\TaglineParser;
use Parsers\RatingRunetaParser;
use Parsers\SostavRatingParser;
use Services\GoogleSheetService;

function autoloadMainClasses(string $class_name): void
{
    $class_name = str_replace('\\', '/', $class_name) . '.php';
    $file = __DIR__ . '/' . $class_name;
    if (file_exists($file)) {
        include_once $file;
    }
}

spl_autoload_register('autoloadMainClasses');

require 'vendor/autoload.php';

$parser = new RatingRunetaParser('https://ratingruneta.ru/digital-agencies/');
$data = $parser->parse();

$excel = new GoogleSheetService();
$excel->exportRatingRuneta($data);

$parser = new SostavRatingParser('https://www.sostav.ru/ratings/digital');
$data = $parser->parse();
$excel->exportSostav($data);

$parser = new TaglineParser('https://tagline.ru/digital-agencies-rating/');
$data = $parser->parse();
$excel->exportTagline($data);