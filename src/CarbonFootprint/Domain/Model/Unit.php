<?php

declare(strict_types=1);

namespace App\CarbonFootprint\Domain\Model;

enum Unit: string
{
    case Kilogram  = 'kg';
    case Gram      = 'g';
    case Tonne     = 't';
    case Piece     = 'ud';
    case Liter     = 'l';
    case CubicMeter   = 'm3';
    case KWhPCS    = 'kWhPCS';
    case KWh       = 'kWh';
    case Kilometer    = 'km';
    case Pernoctacion = 'pernoctacion';
    case SquareMeter = 'm2';
    case Euro = 'EUR';
    case UsDollar = 'USD';
    case JapaneseYen = 'JPY';
    case CzechKoruna = 'CZK';
    case DanishKrone = 'DKK';
    case PoundSterling = 'GBP';
    case HungarianForint = 'HUF';
    case PolishZloty = 'PLN';
    case RomanianLeu = 'RON';
    case SwedishKrona = 'SEK';
    case SwissFranc = 'CHF';
    case IcelandicKrona = 'ISK';
    case NorwegianKrone = 'NOK';
    case TurkishLira = 'TRY';
    case AustralianDollar = 'AUD';
    case BrazilianReal = 'BRL';
    case CanadianDollar = 'CAD';
    case ChineseYuan = 'CNY';
    case HongKongDollar = 'HKD';
    case IndonesianRupiah = 'IDR';
    case IsraeliShekel = 'ILS';
    case IndianRupee = 'INR';
    case SouthKoreanWon = 'KRW';
    case MexicanPeso = 'MXN';
    case MalaysianRinggit = 'MYR';
    case NewZealandDollar = 'NZD';
    case PhilippinePeso = 'PHP';
    case SingaporeDollar = 'SGD';
    case ThaiBaht = 'THB';
    case SouthAfricanRand = 'ZAR';

    public function magnitude(): Magnitude
    {
        return match ($this) {
            self::Kilogram     => Magnitude::Mass,
            self::Gram         => Magnitude::Mass,
            self::Tonne        => Magnitude::Mass,
            self::Piece        => Magnitude::Pieces,
            self::Liter        => Magnitude::Volume,
            self::CubicMeter   => Magnitude::Volume,
            self::KWhPCS       => Magnitude::Energy,
            self::KWh          => Magnitude::Energy,
            self::Kilometer    => Magnitude::Distance,
            self::Pernoctacion => Magnitude::Stay,
            self::SquareMeter  => Magnitude::Area,
            self::Euro,
            self::UsDollar,
            self::JapaneseYen,
            self::CzechKoruna,
            self::DanishKrone,
            self::PoundSterling,
            self::HungarianForint,
            self::PolishZloty,
            self::RomanianLeu,
            self::SwedishKrona,
            self::SwissFranc,
            self::IcelandicKrona,
            self::NorwegianKrone,
            self::TurkishLira,
            self::AustralianDollar,
            self::BrazilianReal,
            self::CanadianDollar,
            self::ChineseYuan,
            self::HongKongDollar,
            self::IndonesianRupiah,
            self::IsraeliShekel,
            self::IndianRupee,
            self::SouthKoreanWon,
            self::MexicanPeso,
            self::MalaysianRinggit,
            self::NewZealandDollar,
            self::PhilippinePeso,
            self::SingaporeDollar,
            self::ThaiBaht,
            self::SouthAfricanRand => Magnitude::Currency,
        };
    }

    public function fixedFactorToBase(): ?float
    {
        if (Magnitude::Currency === $this->magnitude()) {
            return null;
        }

        return match ($this) {
            self::Gram       => 0.001,
            self::Tonne      => 1000.0,
            self::CubicMeter => 1000.0,
            self::KWhPCS     => 1.0,
            default          => 1.0,
        };
    }

    public function isCurrency(): bool
    {
        return Magnitude::Currency === $this->magnitude();
    }

    /** @return list<self> */
    public static function currencies(): array
    {
        return array_values(array_filter(self::cases(), static fn(self $unit): bool => $unit->isCurrency()));
    }
}
