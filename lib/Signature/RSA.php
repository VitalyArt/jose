<?php

namespace SpomkyLabs\JOSE\Signature;

use SpomkyLabs\JOSE\JWK;
use SpomkyLabs\JOSE\JWKInterface;
use SpomkyLabs\JOSE\JWKSignInterface;
use SpomkyLabs\JOSE\JWKVerifyInterface;
use SpomkyLabs\JOSE\Util\RSAConverter;

/**
 * This class handles signatures using RSA SSA PKCS1 and PSS.
 * It supports algorithms PS256/RS256, PS384/RS384 and PS512/RS512;
 */
class RSA implements JWKInterface, JWKSignInterface, JWKVerifyInterface
{
    use JWK;

    protected $values = array('kty' => 'RSA');

    public function __toString()
    {
        return json_encode($this->getValues());
    }

    public function toPublic()
    {
        $values = $this->getValues();

        $keys = array('p', 'd', 'q', 'dp', 'dq', 'qi');
        foreach ($keys as $key) {
            if ( isset($values[$key])) {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * @inheritdoc
     */
    public function verify($data, $signature)
    {
        $rsa = RSAConverter::fromArrayToRSA_Crypt($this->getKeyData(false));

        $rsa->setHash($this->getHashAlgorithm());
        if ($this->getSignatureMethod() === CRYPT_RSA_SIGNATURE_PSS) {
            $rsa->setMGFHash($this->getHashAlgorithm());
            $rsa->setSaltLength(0);
        }
        $rsa->setSignatureMode($this->getSignatureMethod());

        return $rsa->verify($data, $signature);
    }

    /**
     * @inheritdoc
     */
    public function sign($data)
    {
        $rsa = RSAConverter::fromArrayToRSA_Crypt($this->getKeyData(true));
        if (!$this->isPrivate()) {
            throw new \Exception("The private key is missing");
        }

        $rsa->setHash($this->getHashAlgorithm());
        if ($this->getSignatureMethod() === CRYPT_RSA_SIGNATURE_PSS) {
            $rsa->setMGFHash($this->getHashAlgorithm());
            $rsa->setSaltLength(0);
        }
        $rsa->setSignatureMode($this->getSignatureMethod());

        return $rsa->sign($data);
    }

    public function isPrivate()
    {
        $rsa = RSAConverter::fromArrayToRSA_Crypt($this->getKeyData(true));

        return $rsa->getPrivateKey() !== null;
    }

    protected function getHashAlgorithm()
    {
        $alg = $this->getValue('alg');
        switch ($alg) {
            case 'RS256':
            case 'PS256':
                return 'sha256';
            case 'RS384':
            case 'PS384':
                return 'sha384';
            case 'RS512':
            case 'PS512':
                return 'sha512';
            default:
                throw new \Exception("Algorithm $alg is not supported");
        }
    }

    protected function getSignatureMethod()
    {
        $alg = $this->getValue('alg');
        switch ($alg) {
            case 'RS256':
            case 'RS384':
            case 'RS512':
                return CRYPT_RSA_SIGNATURE_PKCS1;
            case 'PS256':
            case 'PS384':
            case 'PS512':
                return CRYPT_RSA_SIGNATURE_PSS;
            default:
                throw new \Exception("Algorithm $alg is not supported");
        }
    }

    protected function getKeyData($all = false)
    {
        $result = array();
        if ($all === true) {
            $keys = array('n','e','p','d','q','dp','dq','qi');
        } else {
            $keys = array('n','e');
        }
        foreach ($keys as $key) {
            $value = $this->getValue($key);
            if (null !== $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}