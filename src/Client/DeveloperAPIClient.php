<?php
declare(strict_types=1);
namespace PinVandaag\PMarketAPIDevelop\Client;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use PinVandaag\PMarketAPIDevelop\Exception\PMarketAPIDevelopException;
use PinVandaag\PMarketAPIDevelop\Model\{
    ApkInfo, ApkOfflineRequest, ApkVersion, AppDetail, AppKeySecret, CodeInfo, CreateApkRequest,
    CreateMultipleApkRequest, CreateSingleAppRequest, CreateSingleApkRequest, EditAppKeySecretRequest,
    EditSingleApkRequest, PageResult, UploadedFileContent
};
use PinVandaag\PMarketAPIDevelop\Support\AesEncryptor;
use SensitiveParameter;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class DeveloperAPIClient
{
    private ClientInterface $http;
    private SerializerInterface $serializer;
    private string $baseUri = '';
    private string $apiKey = '';
    private string $apiSecret = '';

    public function __construct(?ClientInterface $http = null, ?SerializerInterface $serializer = null)
    {
        $this->http = $http ?? new Client();
        $this->serializer = $serializer ?? new Serializer([new ArrayDenormalizer(), new ObjectNormalizer()], [new JsonEncoder()]);
    }

    public function configure(string $baseUri, #[SensitiveParameter] string $apiKey, #[SensitiveParameter] string $apiSecret): self
    {
        $this->baseUri = rtrim($baseUri, '/');
        $this->apiKey = trim($apiKey);
        $this->apiSecret = trim($apiSecret);
        if ($this->baseUri === '' || $this->apiKey === '' || $this->apiSecret === '') {
            throw new PMarketAPIDevelopException('baseUri, apiKey and apiSecret are required.');
        }
        return $this;
    }

    public function createApp(CreateSingleAppRequest $request): int|string
    {
        if (trim($request->appName) === '' || mb_strlen($request->appName) > 64) throw new PMarketAPIDevelopException('appName is required and may contain at most 64 characters.');
        if ($request->appKey !== null && !preg_match('/^[A-Za-z0-9]{20}$/', $request->appKey)) throw new PMarketAPIDevelopException('appKey must be exactly 20 letters and numbers.');
        return $this->data('POST', '/v1/developer/apps', $request);
    }

    public function getAppInfoByName(?string $packageName = null, ?string $appName = null): AppDetail
    {
        if (($packageName === null || trim($packageName)==='') && ($appName === null || trim($appName)==='')) throw new PMarketAPIDevelopException('packageName and appName cannot both be empty.');
        return $this->object('GET', '/v1/developer/apps/info', AppDetail::class, query: array_filter(['packageName'=>$packageName,'appName'=>$appName]));
    }

    public function uploadApk(CreateApkRequest $request): int|string { $this->validateApk($request); return $this->data('POST','/v1/developer/apks/upload',$request); }
    public function createApk(CreateSingleApkRequest $request): int|string { $this->validateApk($request); return $this->data('POST','/v1/developer/apks',$request); }
    public function createMultipleApk(CreateMultipleApkRequest $request): int|string { $this->validateApk($request, true); return $this->data('POST','/v1/developer/apks/multiple',$request); }
    public function editApk(EditSingleApkRequest $request): bool { $this->validateApk($request); $this->success('PUT',sprintf('/v1/developer/apks/%s',rawurlencode((string)$request->apkId)),$request); return true; }
    public function submitApk(int|string $apkId): bool { $this->positive($apkId,'apkId'); $this->success('POST',sprintf('/v1/developer/apks/%s/submit',$apkId)); return true; }
    public function deleteApk(int|string $apkId): bool { $this->positive($apkId,'apkId'); $this->success('DELETE',sprintf('/v1/developer/apks/%s',$apkId)); return true; }
    public function deleteApp(int|string $appId): bool { $this->positive($appId,'appId'); $this->success('DELETE',sprintf('/v1/developer/apps/%s',$appId)); return true; }

    public function getAppCategory(): PageResult { return $this->page('GET','/v1/developer/app-categories',CodeInfo::class); }
    public function getApkById(int|string $apkId): ApkInfo { $this->positive($apkId,'apkId'); return $this->object('GET',sprintf('/v1/developer/apks/%s',$apkId),ApkInfo::class); }
    public function getAppKeySecret(int|string $appId): AppKeySecret { $this->positive($appId,'appId'); return $this->object('GET',sprintf('/v1/developer/apps/%s/key-secret',$appId),AppKeySecret::class); }
    public function updateAppKeySecret(int|string $appId, EditAppKeySecretRequest $request, bool $encrypt = true): bool
    {
        $this->positive($appId,'appId');
        $payload = $encrypt ? new EditAppKeySecretRequest(AesEncryptor::encrypt($request->appKey,$this->apiSecret),AesEncryptor::encrypt($request->appSecret,$this->apiSecret)) : $request;
        $this->success('PUT',sprintf('/v1/developer/apps/%s/key-secret',$appId),$payload); return true;
    }
    public function getApkVersionList(int|string $appId): PageResult { $this->positive($appId,'appId'); return $this->page('GET',sprintf('/v1/developer/apps/%s/apks',$appId),ApkVersion::class); }
    public function offlineApkById(int|string $apkId, ApkOfflineRequest $request): bool { $this->positive($apkId,'apkId'); if(trim($request->comment)==='') throw new PMarketAPIDevelopException('comment is required.'); $this->success('POST',sprintf('/v1/developer/apks/%s/offline',$apkId),$request); return true; }

    private function validateApk(object $r, bool $multiple=false): void
    {
        foreach (['apkName','apkType','modelNameList','categoryList','shortDesc','description','screenshotFileList'] as $p) if (!property_exists($r,$p) || $r->$p === '' || $r->$p === []) throw new PMarketAPIDevelopException("$p is required.");
        if (!in_array($r->apkType,['P','N'],true)) throw new PMarketAPIDevelopException('apkType must be P or N.');
        if (count($r->screenshotFileList) < 3) throw new PMarketAPIDevelopException('At least 3 screenshots are required.');
        if ($r->apkType === 'P' && $r->paramTemplateFileList === []) throw new PMarketAPIDevelopException('Parameter template is mandatory for a parameter app.');
        if ($multiple && $r->multipleAppFile === []) throw new PMarketAPIDevelopException('multipleAppFile is required.');
    }

    private function positive(int|string $v,string $n): void { if(!ctype_digit((string)$v) || (int)$v<1) throw new PMarketAPIDevelopException("$n must be a positive integer."); }

    private function data(string $method,string $endpoint,?object $body=null): mixed { $r=$this->call($method,$endpoint,$body); return $r['data'] ?? null; }
    private function success(string $method,string $endpoint,?object $body=null): array { return $this->call($method,$endpoint,$body); }
    private function object(string $method,string $endpoint,string $class,array $query=[]): object { $r=$this->call($method,$endpoint,null,$query); return $this->serializer->denormalize($r['data'] ?? [],$class); }
    private function page(string $method,string $endpoint,string $class): PageResult {
        $r=$this->call($method,$endpoint); $p=$r['pageInfo'] ?? $r; $items=[];
        foreach(($p['dataSet'] ?? $p['dataset'] ?? []) as $row) $items[]=$this->serializer->denormalize($row,$class);
        return new PageResult((int)($p['pageNo']??1),(int)($p['limit']??count($items)),(int)($p['totalCount']??count($items)),(bool)($p['hasNext']??false),$items);
    }
    private function call(string $method,string $endpoint,?object $body=null,array $query=[]): array
    {
        $opts=['headers'=>$this->headers(),'http_errors'=>false,'timeout'=>120,'connect_timeout'=>10];
        if($query!==[]) $opts['query']=$query;
        if($body!==null) $opts['json']=$this->normalize($body);
        try { $res=$this->http->request($method,$this->baseUri.$endpoint,$opts); } catch(\Throwable $e){ throw new PMarketAPIDevelopException('P Market Developer API request failed: '.$e->getMessage(),0,$e); }
        $raw=(string)$res->getBody(); $decoded=$raw===''?[]:json_decode($raw,true);
        if(!is_array($decoded)) throw new PMarketAPIDevelopException('Could not decode P Market Developer API response.', $res->getStatusCode());
        if($res->getStatusCode()<200 || $res->getStatusCode()>=300 || ($decoded['businessCode']??0)!==0){
            $message=(string)($decoded['message'] ?? implode('; ',(array)($decoded['validationErrors']??[])) ?: sprintf('Request failed with HTTP %d.',$res->getStatusCode()));
            throw new PMarketAPIDevelopException($message,(int)($decoded['businessCode']??$res->getStatusCode()),response:$decoded);
        }
        return $decoded;
    }
    private function normalize(mixed $v): mixed
    {
        if($v instanceof UploadedFileContent) return $v->toArray();
        if(is_array($v)){ $o=[]; foreach($v as $k=>$x)$o[$k]=$this->normalize($x); return $o; }
        if(is_object($v)){ $a=$this->serializer->normalize($v); return array_filter($this->normalize($a),fn($x)=>$x!==null); }
        return $v;
    }
    private function headers(): array
    {
        $now=time();
        $jwt=JWT::encode(['iss'=>$this->apiKey,'iat'=>$now,'exp'=>$now+300],$this->apiSecret,'HS256');
        return ['Accept'=>'application/json','Content-Type'=>'application/json','Authorization'=>'Bearer '.$jwt];
    }
}
