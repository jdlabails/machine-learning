<?php

namespace App\Command;

use App\UseCases\ParkingUseCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MainCommand extends Command
{
    use LockableTrait;

    protected function configure()
    {
        $this
            ->setName('ml:launch-parking')
            ->setDescription('Parking')
            ->addArgument(
                'nbLoop',
                InputArgument::OPTIONAL,
                'Learning deep, better if > 10000',
                1000
            );
    }

    /**
     * Launch by 6000 and 47 step reach
     * Launch by 60000 and 86 step reach
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        //todo loop here about 20 times to get the best result
        $output->writeln("Lets learn !");
        $parking = new ParkingUseCase();
        $parking->letsLearn($output, $input->getArgument('nbLoop'));

        $output->writeln(['', "Lets see how to park :"]);
        $parking->setCarPosition(30, 20, 0, 0);
        $nbMove = 0;
        $loopAvoid = 0;

        $badMoveAvoid = 0;
        $actions = [];
        while (!$parking->isParked() && $nbMove < 2000) {

            // we try to avoid loop
            $pos = $parking->getPosition();
            $action = $parking->getBestMove();
            if (isset($actions[$pos['X']][$pos['Y']][$pos['thetaIndex']][$action])) {
                $loopAvoid++;
                while ($action == $newAction = $parking->getActionByEgreedyPolicy(0)) {

                }

                $action = $newAction;
            }
            $actions[$pos['X']][$pos['Y']][$pos['thetaIndex']][$action] = true;

            //echo "Action $action ==> ".json_encode($parking->getPosition()).PHP_EOL;
            if (!$parking->move($action)) {
                $badMoveAvoid++;
            }
            $nbMove++;
        }

        $output->writeln("$nbMove moves to get parked : " . json_encode($parking->getPosition()));
        $output->writeln('bad move : ' . $badMoveAvoid);
        $output->writeln('loop avoided : ' . $loopAvoid);
    }
}
