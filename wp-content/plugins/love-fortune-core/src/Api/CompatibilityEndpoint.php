<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Support\RegistrationInterface;

class CompatibilityEndpoint implements RegistrationInterface
{
    public const ROUTE='/love-fortune/v1/compatibility/calculate';
    public function __construct(private readonly ?\Closure $calculate=null,private readonly ?\Closure $rate=null,private readonly ?InterpretationContext $signer=null,private readonly ?\Closure $clock=null) {}
    public function register(): void
    {
        register_rest_route('love-fortune/v1',substr(static::ROUTE,strlen('/love-fortune/v1')),[
            'methods'=>'POST','permission_callback'=>'__return_true','callback'=>[$this,'handle'],
        ]);
        // WP may reject malformed JSON before invoking the callback. Own this route's transport gate.
        add_filter('rest_pre_dispatch',[$this,'dispatch'],10,3);
        add_filter('rest_pre_serve_request',[$this,'cors'],20,4);
        add_filter('rest_pre_serve_request',[$this,'serve'],30,4);
    }
    public function dispatch(mixed $result,\WP_REST_Server $server,\WP_REST_Request $request): mixed
    {
        return $request->get_route()===static::ROUTE && $request->get_method()==='POST'?$this->handle($request):$result;
    }
    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $headers=['Cache-Control'=>'no-store'];
        try{
            $now=$this->clock===null?time():($this->clock)();
            if($this->rate!==null){($this->rate)();}else{(new CoreRateLimit((string)getenv('LOVE_FORTUNE_RATE_SECRET'),\LoveFortune\Core\Infrastructure\Rate\PrivateRateStorage::configured($this->rateDirectory())->directory))->consume((string)($_SERVER['REMOTE_ADDR']??''),$now);}
            if($request->get_query_params()!==[]){throw new PublicError(400,'INVALID_REQUEST');}
            $requestHeaders=$request->get_headers();
            $hasEncoding=array_key_exists('content_encoding',$requestHeaders)||array_key_exists('content-encoding',$requestHeaders);
            $input=$this->parseInput((string)$request->get_body(),(string)$request->get_header('content-type'),$hasEncoding?(string)$request->get_header('content-encoding'):null);
            $id=bin2hex(random_bytes(16));
            $result=$this->calculate===null?$this->calculateResult($input,$id):($this->calculate)($input,$id);
            $this->validateResult($result);
            if($this->needsSignature($result)){
                try{$result['signedInterpretationContext']=($this->signer??InterpretationContext::environment())->issue($result,$input['locale'],$now);}
                catch(\Throwable){throw new PublicError(503,'SERVICE_UNAVAILABLE');}
            }
            return new \WP_REST_Response($result,200,$headers);
        }catch(PublicError $e){return new \WP_REST_Response($e->body(),$e->status,$headers+$e->headers);}
        catch(\Throwable){$e=new PublicError(500,'CALCULATION_FAILED');return new \WP_REST_Response($e->body(),500,$headers);}
    }
    protected function calculateResult(array $input,string $id): array { return (new CompatibilityCalculation())->calculate($input,$id); }
    protected function rateDirectory(): string { return 'core'; }
    protected function parseInput(string $body,string $type,?string $encoding): array { return CompatibilityInput::parse($body,$type,$encoding,static::ROUTE!==self::ROUTE); }
    protected function validateResult(array $result): void { PublicResultValidation::validate($result); }
    protected function needsSignature(array $result): bool { return $result['overallScore']!==null; }
    public function serve(bool $served,\WP_HTTP_Response $response,\WP_REST_Request $request,\WP_REST_Server $server): bool
    {
        if($served||$request->get_route()!==static::ROUTE||$request->get_method()!=='POST'){return $served;}
        echo CanonicalJson::encode($response->get_data());
        return true;
    }
    public static function allowsOrigin(string $origin,string $site): bool
    {
        $p=parse_url($site);
        if(!is_array($p)||!isset($p['scheme'],$p['host'])||!in_array($p['scheme'],['http','https'],true)){return false;}
        return $origin===$p['scheme'].'://'.$p['host'].(isset($p['port'])?':'.$p['port']:'');
    }
    public function cors(bool $served,\WP_HTTP_Response $response,\WP_REST_Request $request,\WP_REST_Server $server): bool
    {
        if($request->get_route()!==static::ROUTE||headers_sent()){return $served;}
        // Override WordPress's reflected-origin default only for this public route.
        header_remove('Access-Control-Allow-Origin');header_remove('Access-Control-Allow-Credentials');
        $origin=(string)$request->get_header('origin');
        if(self::allowsOrigin($origin,home_url())){header('Access-Control-Allow-Origin: '.$origin);}
        return $served;
    }
}
