<?php


namespace App;


use PHP\Math\BigInteger\BigInteger;

/**
 * Class EGPrivateKey
 * @package App
 * @property EGPublicKey $publicKey
 * @property BigInteger $x
 */
class EGPrivateKey
{

    public $publicKey;
    public $x;

    /**
     * EGPrivateKey constructor.
     * @param EGPublicKey $pk
     * @param BigInteger $x
     */
    public function __construct(EGPublicKey $pk, BigInteger $x)
    {
        $this->publicKey = $pk;
        $this->x = $x;
    }

    /**
     * @param \stdClass $data
     * @return EGPrivateKey
     */
    public static function fromArray(\stdClass $data): EGPrivateKey
    {
        return new EGPrivateKey(
            EGPublicKey::fromArray($data->pk),
            new BigInteger($data->x)
        );
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            "pk" => [
                $this->publicKey->toArray(),
            ],
            "x" => $this->x->getValue()
        ];
    }

}
