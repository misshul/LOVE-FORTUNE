<?php
declare(strict_types=1);
namespace LoveFortune\Core\Infrastructure\Interpretation;

use LoveFortune\Core\Application\Interpretation\{ProviderInterface,ProviderFailure,InterpretationPrompt};

/** Vendor-neutral HTTPS JSON gateway. No SDK, persistence, logging or adapter retries. */
final class JsonGatewayProvider implements ProviderInterface
{
    public function __construct(private readonly string $url,private readonly string $key,private readonly string $provider,private readonly string $model) {}
    public static function environment(): ?self
    {
        if(getenv('LOVE_FORTUNE_AI_ENABLED')!=='1'){return null;}
        $url=(string)getenv('LOVE_FORTUNE_AI_ENDPOINT');$key=(string)getenv('LOVE_FORTUNE_AI_KEY');$provider=(string)getenv('LOVE_FORTUNE_AI_PROVIDER');$model=(string)getenv('LOVE_FORTUNE_AI_MODEL');
        $parts=parse_url($url);
        if(!is_array($parts)||($parts['scheme']??null)!=='https'||empty($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['fragment'])||trim($key)===''||trim($provider)===''||trim($model)===''){return null;}
        return new self($url,$key,$provider,$model);
    }
    public function identity(): array { return ['provider'=>$this->provider,'model'=>$this->model]; }
    public function generate(array $dto,float $timeout,array $errors=[]): string
    {
        try{
            $r=wp_safe_remote_post($this->url,['timeout'=>$timeout,'redirection'=>0,'sslverify'=>true,'limit_response_size'=>65537,
                'headers'=>['Content-Type'=>'application/json','Authorization'=>'Bearer '.$this->key],
                'body'=>json_encode(['model'=>$this->model,'system'=>InterpretationPrompt::system(),'input'=>$dto,'validationErrors'=>$errors],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);
        }catch(\Throwable){throw new ProviderFailure('NETWORK');}
        if(is_wp_error($r)){throw new ProviderFailure('NETWORK');}
        $status=wp_remote_retrieve_response_code($r);
        if($status!==200){throw new ProviderFailure($status>=500?'SERVER':($status===429?'RATE':'RESPONSE'));}
        $body=wp_remote_retrieve_body($r);if(strlen($body)>65536){throw new ProviderFailure('RESPONSE');}
        return $body;
    }
}
