<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model\Material;

enum MaterialCategory: string
{
    case Construction = 'construction';
    case PaperCardboard = 'paper_cardboard';
    case Plastics = 'plastics';
    case Metal = 'metal';
    case Electricals = 'electricals';
    case Furniture = 'furniture';
    case Glass = 'glass';
    case Others = 'others';
}
