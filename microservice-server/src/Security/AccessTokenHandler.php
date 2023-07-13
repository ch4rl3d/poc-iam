<?php

namespace App\Security;

use App\Repository\AccessTokenRepository;
use Jose\Component\Checker\AlgorithmChecker;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\HeaderCheckerManager;
use Jose\Component\Checker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Core\JWK;
use Jose\Component\Core\JWKSet;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSTokenSupport;
use Jose\Component\Signature\JWSVerifier;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Jose\Component\Signature\Serializer\JWSSerializerManager;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class AccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private string $jwkPath,
        private string $iamDomain,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        $serializerManager = new JWSSerializerManager([
            new CompactSerializer(),
        ]);
        $jws = $serializerManager->unserialize($accessToken);


        // Verify header
        $headerCheckerManager = new HeaderCheckerManager(
            [
                new AlgorithmChecker(['RS256']),
                // We want to verify that the header "alg" (algorithm)
                // is present and contains "HS256"
            ],
            [
                new JWSTokenSupport(), // Adds JWS token type support
            ]
        );

        $headerCheckerManager->check($jws, 0);

        $jwksUrl = 'http://nginx:80/am/'.$this->iamDomain.'/oidc/.well-known/jwks.json'; // this is the key used to sign th jwt
        if (!file_exists($this->jwkPath)) { // do not download every time
            file_put_contents($this->jwkPath, file_get_contents($jwksUrl));
        }
        $algorithmManager = new AlgorithmManager([
            new RS256(), // same algorithm used in IAM
        ]);
        
        // We instantiate our JWS Verifier.
        $jwsVerifier = new JWSVerifier(
            $algorithmManager
        );

        $jwks = JWKSet::createFromJson(file_get_contents($this->jwkPath));
        $isVerified = $jwsVerifier->verifyWithKeySet($jws, $jwks, 0); // verify token signature

        if (!$isVerified) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        // Check claims validity
        $claimCheckerManager = new ClaimCheckerManager(
            [
                new Checker\IssuedAtChecker(),
                new Checker\NotBeforeChecker(),
                new Checker\ExpirationTimeChecker(),
                new Checker\AudienceChecker('microservice_client'),
            ]
        );
        $claims = json_decode($jws->getPayload(), true);
        try {
            $claimCheckerManager->check($claims);
        } catch (\Exception $e) {
            throw new BadCredentialsException('Invalid token');
        }

        // and return a UserBadge object containing the user identifier with the client_id
        return new UserBadge(
            $claims['sub'], 
            function (string $userIdentifier): UserInterface { // user loader not working, need custom user provider
                return new MicroserviceUser($userIdentifier);
            }
        );
    }
}