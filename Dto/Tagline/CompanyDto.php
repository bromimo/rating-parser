<?php

namespace Dto\Tagline;

use Spatie\DataTransferObject\DataTransferObject;

class CompanyDto extends DataTransferObject
{
    public string  $name;
    public string  $url;
    public string  $address;
    public string  $desc;
    public string  $clients;
}