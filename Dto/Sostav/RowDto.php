<?php

namespace Dto\Sostav;

use Spatie\DataTransferObject\DataTransferObject;

class RowDto extends DataTransferObject
{
    public int         $place;
    public string      $name;
    public string      $profile;
    public int         $score;
    public ?CompanyDto $company;
}