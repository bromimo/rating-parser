<?php

namespace Dto\Tagline;

use Spatie\DataTransferObject\DataTransferObject;

class RowDto extends DataTransferObject
{
    public string      $place;
    public string      $profile;
    public string      $name;
    public int         $staff;
    public string      $price_range;
    public string      $year_foundation;
    public ?CompanyDto $company;
}