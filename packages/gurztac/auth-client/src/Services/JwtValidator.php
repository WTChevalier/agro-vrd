<?php

namespace Gurztac\AuthClient\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\PermittedFor;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;
use Lcobucci\Clock\SystemClock;
use Lcobucci\Clock\FrozenClock;
use Lcobucci\JWT\Validation\NoConstraintsGiven;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use Gurztac\AuthClient\Exceptions\InvalidJwtException;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;

class JwtValidator
{
    public function __construct(
        protected string $jwksUrl,
        protected int $cacheTtlMinutes,
        protected string $expectedIss,
        protected ?string $expectedAud,
        protected int $clockSkew,
    ) {}

    /**
     * Valida un JWT contra la JWKS del Hub.
     *
     * @return array Claims del token decodificado.
     * @throws InvalidJwtException
     */
    public function validate(string $jwt): array
    {
        if (empty($jwt)) {
            throw new InvalidJwtException('JWT vacío');
        }

        $publicKeyPem = $this->getPublicKeyPem();

        $config = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('dummy-private-key-not-used-for-validation'),
            InMemory::plainText($publicKeyPem)
        );

        try {
            /** @var Plain $token */
            $token = $config->parser()->parse($jwt);
        } catch (\Throwable $e) {
            throw new InvalidJwtException('JWT mal formado: ' . $e->getMessage());
        }

        if (!$token instanceof Plain) {
            throw new InvalidJwtException('JWT no es Plain');
        }

        // Constraints
        $constraints = [
            new SignedWith(new Sha256(), InMemory::plainText($publicKeyPem)),
            new IssuedBy($this->expectedIss),
            new StrictValidAt(
                SystemClock::fromUTC(),
                new DateInterval('PT' . $this->clockSkew . 'S')
            ),
        ];

        if (!empty($this->expectedAud)) {
            $constraints[] = new PermittedFor($this->expectedAud);
        }

        try {
            $config->validator()->assert($token, ...$constraints);
        } catch (RequiredConstraintsViolated $e) {
            $violations = array_map(
                fn($v) => $v->getMessage(),
                $e->violations()
            );
            throw new InvalidJwtException('JWT inválido: ' . implode('; ', $violations));
        } catch (NoConstraintsGiven $e) {
            throw new InvalidJwtException('No constraints given');
        }

        $claims = $token->claims()->all();

        // Convertir DateTimeImmutable a timestamps para serialización fácil
        foreach (['iat', 'exp', 'nbf'] as $field) {
            if (isset($claims[$field]) && $claims[$field] instanceof DateTimeImmutable) {
                $claims[$field] = $claims[$field]->getTimestamp();
            }
        }

        return $claims;
    }

    /**
     * Recupera la clave pública del Hub vía JWKS endpoint.
     * Cacheada N minutos.
     */
    protected function getPublicKeyPem(): string
    {
        return Cache::remember(
            'gurztac_auth.jwks_pem',
            now()->addMinutes($this->cacheTtlMinutes),
            function () {
                $resp = Http::timeout(5)->get($this->jwksUrl);
                if (!$resp->ok()) {
                    throw new InvalidJwtException('No se pudo obtener JWKS del Hub: HTTP ' . $resp->status());
                }
                $json = $resp->json();
                $keys = $json['keys'] ?? [];
                if (empty($keys)) {
                    throw new InvalidJwtException('JWKS sin claves');
                }
                $key = $keys[0];

                // JWK → PEM
                if (($key['kty'] ?? '') !== 'RSA') {
                    throw new InvalidJwtException('Key type no soportado: ' . ($key['kty'] ?? 'unknown'));
                }

                return $this->jwkToPem($key);
            }
        );
    }

    /**
     * Convierte un JWK RSA a PEM público.
     */
    protected function jwkToPem(array $jwk): string
    {
        $n = $this->base64UrlDecode($jwk['n']);
        $e = $this->base64UrlDecode($jwk['e']);

        // ASN.1 DER encoding manual
        $modulus = pack('Ca*a*', 0x02, $this->lengthEncode(strlen("\x00" . $n)), "\x00" . $n);
        $exponent = pack('Ca*a*', 0x02, $this->lengthEncode(strlen($e)), $e);
        $rsaKey = pack('Ca*a*', 0x30, $this->lengthEncode(strlen($modulus . $exponent)), $modulus . $exponent);

        $rsaOid = pack('H*', '300d06092a864886f70d0101010500');
        $bitString = pack('Ca*Ca*', 0x03, $this->lengthEncode(strlen($rsaKey) + 1), 0x00, $rsaKey);
        $spki = pack('Ca*a*', 0x30, $this->lengthEncode(strlen($rsaOid . $bitString)), $rsaOid . $bitString);

        $pem = "-----BEGIN PUBLIC KEY-----\n";
        $pem .= chunk_split(base64_encode($spki), 64, "\n");
        $pem .= "-----END PUBLIC KEY-----\n";

        return $pem;
    }

    protected function base64UrlDecode(string $data): string
    {
        $b64 = strtr($data, '-_', '+/');
        $pad = strlen($b64) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', 4 - $pad);
        }
        return base64_decode($b64);
    }

    protected function lengthEncode(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $temp = ltrim(pack('N', $length), "\x00");
        return chr(strlen($temp) | 0x80) . $temp;
    }
}
