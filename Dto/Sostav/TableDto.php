<?php

namespace Dto\Sostav;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Casters\ArrayCaster;

class TableDto extends DataTransferObject
{
    public string $title;
    public string $url;
    /** @var RowDto[] */
    #[CastWith(ArrayCaster::class, RowDto::class)]
    public array $rows;
}