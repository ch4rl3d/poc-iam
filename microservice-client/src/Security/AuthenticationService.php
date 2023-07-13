<?php

namespace App\Security;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthenticationService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $clientId,
        private string $clientSecret,
        private string $iamDomain
    )
    {
    }

    public function getToken(): string
    {

        $res = $this->client->request(
            'POST',
            'http://nginx:80/am/'.$this->iamDomain.'/oauth/token',
            [
                'auth_basic' => [$this->clientId, $this->clientSecret], // credentials sent in Authorization header
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ],
                'body' => [
                    'grant_type' => 'client_credentials' // flow requested see client_credentials specification
                ]
            ]
        );
        return json_decode($res->getContent(), true)['access_token']; // TODO cache the token with expirancy
    }
}