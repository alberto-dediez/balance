<?php

namespace App\Shared\Domain\Model;

trait Filter
{
    public int $page = 0;
    public int $limit = 10;
    public string $orderBy = 'default';
    public string $orderWay = 'ASC';
}