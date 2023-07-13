<?php

namespace App\Command;

use App\Security\AuthenticationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'app:order:create',
    description: 'Add a short description for your command',
)]
class OrderCreateCommand extends Command
{
    public function __construct(
        private AuthenticationService $authenticationService,
        private HttpClientInterface $client,
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $token = $this->authenticationService->getToken();

        $reference = (new \DateTime())->format('Y-m-d H:i:s');

        $this->client->request(
            'POST',
            'http://microservice_server_nginx:80/api/orders',
            [
                'auth_bearer' => $token,
                'json' => [
                    'reference' => $reference
                ]
            ]
        );

        $io->success(sprintf('Order with reference %s created!', $reference));

        return Command::SUCCESS;
    }
}
