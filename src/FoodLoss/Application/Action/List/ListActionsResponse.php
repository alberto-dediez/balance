<?php

namespace App\FoodLoss\Application\Action\List;

class ListActionsResponse
{
    public function __construct(public array $actions = [], public int $totalResults = 0)
    {
        
    }
}