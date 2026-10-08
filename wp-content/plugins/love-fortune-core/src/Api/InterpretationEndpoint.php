<?php
declare(strict_types=1);
namespace LoveFortune\Core\Api;

use LoveFortune\Core\Application\Interpretation\InterpretationService;
use LoveFortune\Core\Infrastructure\Interpretation\JsonGatewayProvider;

final class InterpretationEndpoint extends CompatibilityEndpoint
{
    public const ROUTE='/love-fortune/v1/interpretation/generate';
    public function __construct(private readonly ?InterpretationService $service=null,private readonly ?\Closure $rate=null,private readonly ?InterpretationContext $verifier=null,private readonly ?\Closure $clock=null) {}
    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $headers=['Cache-Control'=>'no-store'];
        try{
            if($request->get_query_params()!==[]){throw new PublicError(400,'INVALID_REQUEST');}
            $h=$request->get_headers();$encoding=array_key_exists('content_encoding',$h)||array_key_exists('content-encoding',$h);
            $input=InterpretationInput::parse((string)$request->get_body(),(string)$request->get_header('content-type'),$encoding?(string)$request->get_header('content-encoding'):null);
            $now=$this->clock===null?time():($this->clock)();
            if($this->rate!==null){($this->rate)();}else{
                try{(new CoreRateLimit((string)getenv('LOVE_FORTUNE_RATE_SECRET'),\LoveFortune\Core\Infrastructure\Rate\PrivateRateStorage::configured('interpretation')->directory,10,'Interpretation'))->consume((string)($_SERVER['REMOTE_ADDR']??''),$now);}
                catch(PublicError $e){if($e->status===429){throw $e;}throw new PublicError(503,'INTERPRETATION_UNAVAILABLE');}
            }
            $verified=($this->verifier??InterpretationContext::environment())->verify($input['signedContext'],$input['locale'],$now);
            $service=$this->service??new InterpretationService(JsonGatewayProvider::environment());
            $result=$service->interpret($verified,bin2hex(random_bytes(16)));
            return new \WP_REST_Response($result,200,$headers);
        }catch(PublicError $e){return new \WP_REST_Response($e->body(),$e->status,$headers+$e->headers);}
        catch(\Throwable){$e=new PublicError(503,'INTERPRETATION_UNAVAILABLE');return new \WP_REST_Response($e->body(),503,$headers);}
    }
}
