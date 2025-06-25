<?php

namespace Dto\RatingRuneta;

use Spatie\DataTransferObject\DataTransferObject;

class CompanyDto extends DataTransferObject
{
    public int    $_agency_not_exists;
    public string $hr_clients_count;
    public int    $place;
    public string $portfolio_main_link;
    public bool   $is_middle;
    public string $name_lat;
    public string $name;
    public string $link_partners_rating;
    public string $CEO;
    public int    $cases_cnt;
    public int    $segment_symbols;
    public string $price_link;
    public string $portfolio_link;
    public bool   $is_hyperlink;
    public string $clients_link;
    public int    $responses;
    public string $score;
    public string $favicon;
    public string $clients_average_working_time;
    public string $clients_list;
    public string $segment_name;
    public int    $clients_count;
    public int    $projects;
    public string $link;
    public int    $id;
    public int    $count_partners;
}