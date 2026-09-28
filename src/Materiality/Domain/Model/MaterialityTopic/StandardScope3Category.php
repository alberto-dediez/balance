<?php

declare(strict_types=1);

namespace App\Materiality\Domain\Model\MaterialityTopic;

use App\Shared\Domain\Model\Locale\Locale;

enum StandardScope3Category: int
{
    case PurchasedGoodsAndServices = 1;

    case CapitalGoods = 2;

    case FuelAndEnergyRelatedActivities = 3;

    case UpstreamTransportationAndDistribution = 4;

    case WasteGeneratedInOperations = 5;

    case BusinessTravel = 6;

    case EmployeeCommuting = 7;

    case UpstreamLeasedAssets = 8;

    case DownstreamTransportationAndDistribution = 9;

    case ProcessingOfSoldProducts = 10;

    case UseOfSoldProducts = 11;

    case EndOfLifeTreatmentOfSoldProducts = 12;

    case DownstreamLeasedAssets = 13;

    case Franchises = 14;

    case Investments = 15;

    public function direction(): MaterialityTopicDirection
    {
        return $this->value <= 8 ? MaterialityTopicDirection::Upstream : MaterialityTopicDirection::Downstream;
    }

    public function name(Locale $locale): ?string
    {
        return match ($locale) {
            Locale::Spanish => match ($this) {
                self::PurchasedGoodsAndServices => 'Bienes y servicios adquiridos',
                self::CapitalGoods => 'Bienes de capital',
                self::FuelAndEnergyRelatedActivities => 'Actividades relacionadas con combustibles y energía (no incluidas en los alcances 1 y 2)',
                self::UpstreamTransportationAndDistribution => 'Transporte y distribución aguas arriba',
                self::WasteGeneratedInOperations => 'Residuos generados en las operaciones',
                self::BusinessTravel => 'Viajes de negocios',
                self::EmployeeCommuting => 'Desplazamientos de los empleados',
                self::UpstreamLeasedAssets => 'Activos arrendados aguas arriba',
                self::DownstreamTransportationAndDistribution => 'Transporte y distribución aguas abajo',
                self::ProcessingOfSoldProducts => 'Procesamiento de los productos vendidos',
                self::UseOfSoldProducts => 'Uso de los productos vendidos',
                self::EndOfLifeTreatmentOfSoldProducts => 'Tratamiento al final de la vida útil de los productos vendidos',
                self::DownstreamLeasedAssets => 'Activos arrendados aguas abajo',
                self::Franchises => 'Franquicias',
                self::Investments => 'Inversiones',
            },
            Locale::English => match ($this) {
                self::PurchasedGoodsAndServices => 'Purchased goods and services',
                self::CapitalGoods => 'Capital goods',
                self::FuelAndEnergyRelatedActivities => 'Fuel- and energy-related activities (not included in scope 1 or scope 2)',
                self::UpstreamTransportationAndDistribution => 'Upstream transportation and distribution',
                self::WasteGeneratedInOperations => 'Waste generated in operations',
                self::BusinessTravel => 'Business travel',
                self::EmployeeCommuting => 'Employee commuting',
                self::UpstreamLeasedAssets => 'Upstream leased assets',
                self::DownstreamTransportationAndDistribution => 'Downstream transportation and distribution',
                self::ProcessingOfSoldProducts => 'Processing of sold products',
                self::UseOfSoldProducts => 'Use of sold products',
                self::EndOfLifeTreatmentOfSoldProducts => 'End-of-life treatment of sold products',
                self::DownstreamLeasedAssets => 'Downstream leased assets',
                self::Franchises => 'Franchises',
                self::Investments => 'Investments',
            },
            default => null,
        };
    }

    public function description(Locale $locale): ?string
    {
        return match ($locale) {
            Locale::Spanish => match ($this) {
                self::PurchasedGoodsAndServices => 'Extracción, producción y transporte de los bienes y servicios adquiridos por la organización.',
                self::CapitalGoods => 'Extracción, producción y transporte de los bienes de capital adquiridos (edificios, maquinaria, vehículos).',
                self::FuelAndEnergyRelatedActivities => 'Emisiones de los combustibles y la energía adquiridos no incluidas en los alcances 1 y 2 (extracción, refinado, pérdidas de red).',
                self::UpstreamTransportationAndDistribution => 'Transporte y distribución de los productos adquiridos entre los proveedores y la organización.',
                self::WasteGeneratedInOperations => 'Tratamiento y eliminación de los residuos generados en las operaciones de la organización.',
                self::BusinessTravel => 'Transporte de los empleados en desplazamientos de trabajo en vehículos ajenos a la organización.',
                self::EmployeeCommuting => 'Transporte de los empleados entre su domicilio y el centro de trabajo.',
                self::UpstreamLeasedAssets => 'Operación de los activos arrendados por la organización no incluidos en los alcances 1 y 2.',
                self::DownstreamTransportationAndDistribution => 'Transporte y distribución de los productos vendidos entre la organización y el consumidor final.',
                self::ProcessingOfSoldProducts => 'Procesamiento de los productos intermedios vendidos por terceros.',
                self::UseOfSoldProducts => 'Uso final de los bienes y servicios vendidos por la organización.',
                self::EndOfLifeTreatmentOfSoldProducts => 'Tratamiento y eliminación de los productos vendidos al final de su vida útil.',
                self::DownstreamLeasedAssets => 'Operación de los activos propiedad de la organización arrendados a terceros.',
                self::Franchises => 'Operación de las franquicias no incluidas en los alcances 1 y 2.',
                self::Investments => 'Operación de las inversiones (participaciones, deuda, financiación de proyectos).',
            },
            Locale::English => match ($this) {
                self::PurchasedGoodsAndServices => 'Extraction, production and transportation of the goods and services purchased by the organisation.',
                self::CapitalGoods => 'Extraction, production and transportation of the capital goods purchased (buildings, machinery, vehicles).',
                self::FuelAndEnergyRelatedActivities => 'Emissions from purchased fuels and energy not included in scope 1 or scope 2 (extraction, refining, grid losses).',
                self::UpstreamTransportationAndDistribution => 'Transportation and distribution of purchased products between suppliers and the organisation.',
                self::WasteGeneratedInOperations => 'Treatment and disposal of the waste generated in the organisation\'s operations.',
                self::BusinessTravel => 'Transportation of employees for business-related activities in vehicles not owned by the organisation.',
                self::EmployeeCommuting => 'Transportation of employees between their homes and their workplaces.',
                self::UpstreamLeasedAssets => 'Operation of assets leased by the organisation not included in scope 1 or scope 2.',
                self::DownstreamTransportationAndDistribution => 'Transportation and distribution of sold products between the organisation and the end consumer.',
                self::ProcessingOfSoldProducts => 'Processing of intermediate products sold, carried out by third parties.',
                self::UseOfSoldProducts => 'End use of the goods and services sold by the organisation.',
                self::EndOfLifeTreatmentOfSoldProducts => 'Waste disposal and treatment of the products sold at the end of their life.',
                self::DownstreamLeasedAssets => 'Operation of assets owned by the organisation and leased to third parties.',
                self::Franchises => 'Operation of franchises not included in scope 1 or scope 2.',
                self::Investments => 'Operation of investments (equity, debt, project finance).',
            },
            default => null,
        };
    }
}
