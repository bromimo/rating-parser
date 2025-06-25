<?php

namespace Dto\RatingRuneta;

use Spatie\DataTransferObject\DataTransferObject;

class RatingRunetaDto extends DataTransferObject
{
    public int    $place;
    public string $name;
    public string $desc;
    public string $site;
    public string $address;
    public string $phone;
    public int    $clients_count;
    public int    $hr_clients_count;
    public string $price_range;
}