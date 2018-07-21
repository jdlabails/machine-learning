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
                500
            );
    }


    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->lock()) {
            $output->writeln('The command is already running in another process.');

            return 0;
        }

        $output->writeln([
            '',
            "Parking use case",
            '********************************',
        ]);
        $parking = new ParkingUseCase($output->isVerbose());


        $output->writeln([
            '',
            "Standard building : ",
        ]);
        $standard = $parking->makeStandard($output, $input->getArgument('nbLoop') * 2);
        $output->writeln([
            '',
            "Standard : " . count($standard),
            '********************************',
        ]);

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

        for ($nbTry = 0; $nbTry < 500; $nbTry++) {
            $nbMove = 0;
            $loopAvoid = 0;
            $moves = [];
            $positions = [];
            $badMoveAvoid = 0;
            $actions = [];
            $parking->initCarPosition();
            while (!$parking->isParked() && $nbMove < 1000) {
                $pos = $parking->getCarPosition();
                $action = $parking->getBestMove();
                // we try to avoid loop
                if (isset($actions[$pos['X']][$pos['Y']][$pos['thetaIndex']][$action])) {
                    $loopAvoid++;
                    while ($action == $newAction = $parking->getActionByEgreedyPolicy(-1)) {
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
            if ($output->isVerbose()) {
                $output->writeln($nbTry . " - " . count($moves) .
                    " moves to get parked ($badMoveAvoid bad move, $loopAvoid loops avoided");
            }

            if ($nbTry == 0 || count($bestMoves) > count($moves)) {
                $bestMoves = $moves;
                $bestPositions = $positions;
            }
        }

        $fp = fopen(__DIR__ . '/../../public/parkingMoves.js', 'w+');
        fwrite($fp, 'var moves = ' . json_encode($bestMoves) . "; \n");
        fwrite($fp, 'var positions = ' . json_encode($bestPositions));
        fclose($fp);

        $output->writeln(count($bestMoves) . " moves to get parked on visualization");
    }
}
