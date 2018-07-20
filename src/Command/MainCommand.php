<?php

namespace App\Command;

use App\Model\Car;
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
                'Learning deep, better if < 10000',
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


//        echo (string)ParkingUseCase::theCarIsInTheGame(10,10,pi()/4);
//        echo (int)ParkingUseCase::theCarIsInTheGame(20,20,0);
//        die;
        //todo loop here about 20 times to get the best result

        $output->writeln([
            '',
            "Parking use case",
            '********************************',
        ]);
        $parking = new ParkingUseCase();

        $output->writeln([
            '',
            "Learning",
            '********************************',
        ]);

        $parking->letsLearn($output, $input->getArgument('nbLoop'));


        $output->writeln([
            '',
            '********************************',
            "Lets see how to park ",
            '********************************',
        ]);

        $bestMoves = [];
        $bestPositions = [];

        for ($nbTry = 0; $nbTry < 10; $nbTry++) {
            $nbMove = 0;
            $loopAvoid = 0;
            $moves = [];
            $positions = [];
            $badMoveAvoid = 0;
            $actions = [];
            $parking->initCarPosition();
            while (!$parking->isParked() && $nbMove < 10000) {
                // we try to avoid loop
                $pos = $parking->getCarPosition();
                $action = $parking->getBestMove();
                if (isset($actions[$pos['X']][$pos['Y']][$pos['thetaIndex']][$action])) {
                    $loopAvoid++;
                    while ($action == $newAction = $parking->getActionByEgreedyPolicy(10)) {
                    }

                    $action = $newAction;
                }
                $actions[$pos['X']][$pos['Y']][$pos['thetaIndex']][$action] = true;

                if (!$parking->move($action)) {
                    $badMoveAvoid++;
                } else {
                    $moves[] = $action;
                    $positions[] = $parking->getCarPosition();
                    $nbMove++;
                }
            }
            $output->writeln($nbTry . " - " . count($moves)." moves to get parked");

            if ($nbTry == 0 || count($bestMoves) > count($moves)) {
                $bestMoves = $moves;
                $bestPositions = $positions;
            }
        }

        $fp = fopen(__DIR__.'/../../public/parkingMoves.js', 'w+');
        fwrite($fp, 'var moves = '.json_encode($bestMoves)."; \n");
        fwrite($fp, 'var positions = '.json_encode($bestPositions));
        fclose($fp);

        $output->writeln(count($bestMoves)." moves to get parked");
        $output->writeln('bad move : ' . $badMoveAvoid);
        $output->writeln('loop avoided : ' . $loopAvoid);
    }
}
