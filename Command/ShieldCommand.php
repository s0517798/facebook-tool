<?php

namespace Gingdev\Facebook\Command;

use Facebook\FacebookRequest;
use Facebook\FacebookSession;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

class ShieldCommand extends Command
{
    protected static $defaultName = 'facebook:shield';

    protected function configure()
    {
        FacebookSession::enableAppSecretProof(false);
        $this->setDescription('Activate avatar protection')
            ->addArgument('token', InputArgument::REQUIRED, 'Access token')
            ->addOption('off', null, InputOption::VALUE_NONE, 'Turn off');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            /** @var FacebookSession */
            $session = new FacebookSession($input->getArgument('token'));

            /** @var FacebookRequest */
            $user = (new FacebookRequest($session, 'GET', '/me'))->execute()
                ->getGraphObject()
                ->asArray();
        } catch (\Throwable $e) {
            $output->writeln('<fg=red>'.$e->getMessage().'</>');

            return Command::FAILURE;
        }

        $client = HttpClient::create();

        $mode = $input->getOption('off') ? false : true;

        $data = [
            [
                'is_shielded' => $mode,
                'actor_id' => $user['id'],
                'client_mutation_id' => 'b0316dd6-3fd6-4beb-aed4-bb29c5dc64b0',
            ],
        ];

        $client->request('POST', 'https://graph.facebook.com/graphql', [
            'headers' => [
                'Authorization' => 'OAuth '.$input->getArgument('token'),
            ],
            'body' => [
                'variables' => json_encode($data),
                'doc_id' => '1477043292367183',
            ],
        ]);

        $output->writeln('<info>Shielded successfully turned '.($mode ? 'on' : 'off').'</>');

        return Command::SUCCESS;
    }
}
