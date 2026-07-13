<?php
declare(strict_types=1); namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class CreateApkRequest
{
 public function __construct(public string $appName,public string $baseType,public int $chargeType,public array $modelNameList,public array $categoryList,public string $shortDesc,public string $description,public UploadedFileContent $appFile,public UploadedFileContent $featuredImgFile,public array $screenshotFileList,public ?float $price=null,public ?string $appNameByVersion=null,public ?string $releaseNotes=null,public ?UploadedFileContent $iconFile=null,public ?UploadedFileContent $attachment=null,public array $paramTemplateFileList=[]){}
}
