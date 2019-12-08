<?php

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCmd extends Command
{

    protected static $defaultName='test';

    public function configure()
    {
        $this->setDescription('test command');

    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('the end.3453445');
    }
}