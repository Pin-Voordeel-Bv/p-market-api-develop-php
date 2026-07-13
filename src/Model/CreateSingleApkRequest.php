<?php
declare(strict_types=1); namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class CreateSingleApkRequest
{
 public function __construct(public int|string $appId,public string $apkName,public string $apkType,public array $modelNameList,public array $categoryList,public string $shortDesc,public string $description,public UploadedFileContent $appFile,public array $screenshotFileList,public ?string $releaseNotes=null,public ?UploadedFileContent $iconFile=null,public ?UploadedFileContent $featuredImgFile=null,public ?UploadedFileContent $attachment=null,public ?string $accessUrl=null,public array $paramTemplateFileList=[],public ?bool $allowUploadLocalParameter=null,public ?bool $allowParamPartialPush=null){}
}
