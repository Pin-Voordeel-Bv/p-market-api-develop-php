<?php
declare(strict_types=1); namespace PinVandaag\PMarketAPIDevelop\Model;
final readonly class EditSingleApkRequest
{
 public function __construct(public int|string $apkId,public string $apkName,public string $apkType,public array $modelNameList,public array $categoryList,public string $shortDesc,public string $description,public array $screenshotFileList,public UploadedFileContent $iconFile,public ?string $releaseNotes=null,public ?UploadedFileContent $appFile=null,public ?UploadedFileContent $featuredImgFile=null,public ?UploadedFileContent $attachment=null,public ?string $accessUrl=null,public array $paramTemplateFileList=[],public ?bool $allowUploadLocalParameter=null,public ?bool $allowParamPartialPush=null){}
}
