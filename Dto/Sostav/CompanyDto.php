<?php

namespace Dto\Sostav;

use Spatie\DataTransferObject\DataTransferObject;

class CompanyDto extends DataTransferObject
{
    public string  $name;
    public string  $url;
    public string  $address;
    public ?string $phone;
    public ?string $email;
    public string  $desc;
}