<?php

declare(strict_types=1);

namespace App\Service\Shikimori;

use App\Service\Crypto\Encryptor;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class TokenDataEncryptor
{
    private const SERIALIZER_FORMAT = 'json';

    private Encryptor $encryptor;
    private SerializerInterface $serializer;

    public function __construct(Encryptor $encryptor, SerializerInterface $serializer)
    {
        $this->encryptor = $encryptor;
        $this->serializer = $serializer;
    }

    public function encrypt(TokenData $data): string
    {
        $raw = $this->serializer->serialize($data, self::SERIALIZER_FORMAT);

        return $this->encryptor->encrypt($raw);
    }

    public function decrypt(string $ciphertext): TokenData
    {
        $raw = $this->encryptor->decrypt($ciphertext);

        return $this->serializer->deserialize($raw, TokenData::class, self::SERIALIZER_FORMAT);
    }
}
