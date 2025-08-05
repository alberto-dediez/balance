<?php 

namespace App\Waste\Infrastructure\QrCode;

use iio\libmergepdf\Merger;
use iio\libmergepdf\Driver\TcpdiDriver;
use App\Waste\Domain\Model\Container\QrPdfMerger;

class IioQrPdfMerger implements QrPdfMerger
{
    public function merge(string $pathToPdf, string $otherPathToPdf): string
    {
        $merger = new Merger(new TcpdiDriver);
        $merger->addFile($pathToPdf);
        $merger->addFile($otherPathToPdf);

        $mergedPath = sprintf("/tmp/%s.pdf", md5($pathToPdf . $otherPathToPdf));

        $mergedPdf = $merger->merge();

        file_put_contents($mergedPath, $mergedPdf);

        return $mergedPath;
    }
}