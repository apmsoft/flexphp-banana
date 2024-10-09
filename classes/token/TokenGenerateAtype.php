<?php
namespace Flex\Banana\Token;

use Flex\Banana\Cipher\CipherGeneric;
use Flex\Banana\Cipher\HashEncoder;
use Flex\Banana\Cipher\Base64UrlEncoder;
use Flex\Banana\Token\TokenAbstract;


class TokenGenerateAtype extends TokenAbstract
{
    public const __version = '1.2.1';

    public function __construct(string|null $generate_string, int $length=50){
        $this->value = $generate_string ?? parent::generateRandomString($length);
    }

    # @abstract 해시키 : SHA512 | SHA256
    public function generateHashKey(string $encrypt_type ='sha512') : TokenGenerateAtype
    {
        $this->value = match ($encrypt_type) {
            'sha256','sha512' => (new CipherGeneric(new HashEncoder($this->value)))->hash($encrypt_type)
        };
    return $this;
    }

    # @abstract 토큰생성 : _base64_urlencode
    public function generateToken(string $hash) : TokenGenerateAtype {
        $this->value = (new CipherGeneric(new Base64UrlEncoder()))->encode(sprintf("%s%s",$hash,$this->value));
    return $this;
    }

    # @abstract 토큰 디코드 : _base64_urldecode
    public function decodeToken(string $token) : TokenGenerateAtype {
        $this->value = (new CipherGeneric(new Base64UrlEncoder()))->decode($token);
    return $this;
    }

    public function __get(string $propertyName){
        $result = '';
        if(property_exists($this,$propertyName)){
            $result = $this->{$propertyName};
        }
    return $result;
    }
}
?>