<?php

namespace App\UseCases;

use App\Model\Car;
use App\Model\Parking;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ParkingUseCase
{
    //ligne ou doit finir la voiture
    const HEIGHT_GOAL = Parking::HEIGHT - Car::WIDTH / 2 - 7;

    const NB_ACTION_AVAILABLE = 6;

    //Constantes de recompense
    const REWARD_K1 = 1;     //y0
    const REWARD_K2 = 30;    //goal (entre 20 et 40)
    const REWARD_K3 = 9;    //theta (entre 10 et 15)

    /** @$array $Q espace d'etats-actions double [][][][] */
    private $Q = [];

    /** @$array $states espace des etats approximés possibles boolean[][][] */
    private $state = [];

    /** @$array $rewards double [][] */
    private $rewards = [];

    /** @var  Car $car */
    private $car;

    private $verbose;


    public function __construct($verbose = false)
    {
        $this->car = new Car();
        $this->verbose = $verbose;
    }

    public function initCarPosition()
    {
        $this->car->setPosition(Car::INITIAL_POSITION_LEFT, Car::INITIAL_POSITION_TOP, 0, 0);
    }

    public function getCarPosition()
    {
        return $this->car->getPosition();
    }

    //Renvoie vrai si la voiture en x,y theta(en radians) est entierement dans le terrain
    public function theCarIsInTheGame()
    {
        //http://debart.pagesperso-orange.fr/1s/angle_trigo.html

        $x = $this->car->getX();
        $y = $this->car->getY();
        $theta = $this->car->getTheta();

        // back left
        if (!self::thePointIsInTheGame($x, $y)) {
            return false;
        }

        // back right
        $xbr = Car::WIDTH * cos(pi() / 2 + $theta) + $x;
        $ybr = Car::WIDTH * sin(pi() / 2 + $theta) + $y;
        if (!self::thePointIsInTheGame($xbr, $ybr)) {
            return false;
        }

        // front left
        $xfl = Car::LENGHT * cos($theta) + $x;
        $yfl = Car::LENGHT * sin($theta) + $y;
        if (!self::thePointIsInTheGame($xfl, $yfl)) {
            return false;
        }

        // front right
        $xfr = Car::LENGHT * cos($theta) + $xbr;
        $yfr = Car::LENGHT * sin($theta) + $ybr;
        if (!self::thePointIsInTheGame($xfr, $yfr)) {
            return false;
        }

        return true;
    }

    //Fonction verifiant si un point ne sort pas du terrain
    public static function thePointIsInTheGame($x, $y)
    {
        if (!($x > 0 && $x < Parking::WIDTH && $y > 0 && $y < Parking::HEIGHT)) {
            return false;
        }

        //rangé de voiture avec la place libre
        if ($y > Parking::PLACE_Y && $x > Parking::PLACE_X) {
            return false;
        }

        return true;
    }

    public function getRewards()
    {
        $position = $this->car->getCenterPosition();

        $i = round($position['x'] * 100) / 100;
        $j = round($position['y'] * 100) / 100;
        $k = $this->car->getThetaIndex();

        if (isset($this->rewards[$i][$j][$k])) {
            return $this->rewards[$i][$j][$k];
        }
        $this->rewards[$i][$j][$k] = 0;

        $center = $j + cos($k) * Car::WIDTH / 2 + sin($k) * Car::LENGHT / 2;

        //On recompense l'arrivee en place -- indispensable
        if ($center >= Parking::HEIGHT_GOAL) {
            $this->rewards[$i][$j][$k] += self::REWARD_K2;//*$j/10;
        }

        //On recompense le deplacement vers le bas
        if ($center < Parking::HEIGHT_GOAL) {
            $this->rewards[$i][$j][$k] = self::REWARD_K1 / (1 + pow($j - Parking::HEIGHT_GOAL, 2));
        }

        //On recompense l'horizontalité
//        if ($center >= Parking::HEIGHT_GOAL-3){
//            $this->rewards[$i][$j][$k] += self::REWARD_K3 * abs(cos($k * Car::TURN_ACTION_ANGLE));
//        }
//
//        //On recompense la marche arriere pour le creneau
//        if ($center >= Parking::HEIGHT- 2.3*Car::WIDTH && in_array($k, [1, 3, 5])){
//            $this->rewards[$i][$j][$k] += self::REWARD_K3;
//        }

        return $this->rewards[$i][$j][$k];
    }

    //Renvoie vrai si la voiture est garee en analysant o
    public function isParked()
    {
        $centerPosition = $this->car->getCenterY();

        if ($centerPosition < Parking::HEIGHT_GOAL) {
            return false;
        }

        if (sin($this->car->getTheta()) > 0.05) {
            return false;
        }

        return true;
    }

    public function getQ($x, $y, $thetaIndex, $action)
    {
        if (!isset($this->Q[round($x)][round($y)][$thetaIndex][$action])) {
            $this->Q[round($x)][round($y)][$thetaIndex][$action] = 0;
            //throw new \Exception("pas q [round($x)][round($y)][$thetaIndex][$action]");
        }

        return $this->Q[round($x)][round($y)][$thetaIndex][$action];
    }

    // on test si le mvt ne nous ammene pas dans le mur
    public function testMove(int $action)
    {
        $formerPosition = $this->car->getPosition();

        $this->car->move($action);

        if ($this->theCarIsInTheGame()) {
            $this->car->setPosition(
                $formerPosition['X'],
                $formerPosition['Y'],
                $formerPosition['theta'],
                $formerPosition['thetaIndex']
            );
            return true;
        }

        $this->car->setPosition(
            $formerPosition['X'],
            $formerPosition['Y'],
            $formerPosition['theta'],
            $formerPosition['thetaIndex']
        );

        return false;
    }

    /**
     * Make $action to the car
     * @param int $action
     * @return bool
     */
    public function move(int $action)
    {
        $formerPosition = $this->car->getPosition();

        $this->car->move($action);

        if ($this->theCarIsInTheGame()) {
            return true;
        }

        $this->car->setPosition(
            $formerPosition['X'],
            $formerPosition['Y'],
            $formerPosition['theta'],
            $formerPosition['thetaIndex']
        );

        return false;
    }

    /**
     * get optimal move from s according Q
     * @return int
     */
    public function getBestMove()
    {
        $max = $this->getQ($this->car->getX(), $this->car->getY(), $this->car->getThetaIndex(), 0);
        $bestMove = 0; //check if allowed

        for ($m = 1; $m < self::NB_ACTION_AVAILABLE; $m++) {
            if ($max < $this->getQ($this->car->getX(), $this->car->getY(), $this->car->getThetaIndex(), $m)) {
                if ($this->testMove($m)) {
                    $max = $this->getQ($this->car->getX(), $this->car->getY(), $this->car->getThetaIndex(), $m);
                    $bestMove = $m;
                }
            }
        }

        return $bestMove;
    }

    /**
     * choose action to do with e-greedy policy
     * @param int $i between 0 et 100
     * @return int
     * @throws \Exception
     */
    public function getActionByEgreedyPolicy($i)
    {
        $test = [];
        do {
            if (count($test) == self::NB_ACTION_AVAILABLE) {
                throw new \Exception('No mo action to try');
            }

            $a = rand(0, self::NB_ACTION_AVAILABLE - 1);
            if (rand(0, 100) < $i) { // $i between 5 and 95
                $a = $this->getBestMove(); //todo prendre le second best si echec
            }

            // on ne recalcule pas, ce que l'on sait
            if (isset($test[$a])) {
                continue;
            }

            $test[$a] = $this->move($a);
        } while (!$test[$a]);//tt que mvt pas valide

        return $a;
    }

    //learning process
    public function letsLearn(OutputInterface $output, $maxI = 100)
    {
        $progressBar = new ProgressBar($output, $maxI);
        $progressBar->start();
        $progressBar->setRedrawFrequency(1);
        $progressBar->setMessage('');
        $format = '%message% %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%';
        $progressBar->setFormat($format);

        for ($time = 0; $time < $maxI; $time++) {
            $progressBar->advance();

            // random initial car position
            $i = 20;//rand(5, Parking::WIDTH - Car::LENGHT - 5);
            $j = 20;//rand(5, Parking::HEIGHT - Car::WIDTH - 15);
            $i = rand(5, Parking::WIDTH - Car::LENGHT - 5);
            $j = rand(5, Parking::HEIGHT / 2);
            //echo "$i, $j \n\n";

            $this->car->setPosition($i, $j, 0, 0);
            if (!$this->theCarIsInTheGame()) {
                continue;
            }

            // counter strike
            $nbiter = 0;
            $moves = [];

            // learning factor
            $initFactor = 0.5;
            $finalFactor = 0.3;
            $learningFactor = $initFactor - (($initFactor - $finalFactor) / $maxI) * $time;

            do {
                $nbiter++;

                // Current state
                $x = $this->car->getX();
                $y = $this->car->getY();
                $tIndex = $this->car->getThetaIndex();

                // new action by e-greedy policy
                $a = $this->getActionByEgreedyPolicy(min(0.5, $time / $maxI) * 100);
                // the car is now in a new position

                // Q evolution by bellman equation
                $rewardNewPosition = $this->getRewards();
                $bestQNewPosition = $this->getQ(
                    $this->car->getX(),
                    $this->car->getY(),
                    $this->car->getThetaIndex(),
                    $this->getBestMove()
                );
                $oldQ = $this->getQ($x, $y, $tIndex, $a);

                $this->Q[round($x)][round($y)][$tIndex][$a] =
                    $oldQ + $learningFactor * ($rewardNewPosition + $bestQNewPosition - $oldQ);

                $moves [] = [round($x), round($y), $tIndex, $a];
            } while (!$this->isParked() && $nbiter < 20000);

            // stop loop if lost
            if ($nbiter >= 20000) {
                if ($this->verbose) {
                    $progressBar->setMessage("<error>Too long for $i, $j</error> : " .
                        json_encode($this->car->getPosition()) . PHP_EOL);
                }

            } else {
                if ($this->verbose) {
                    $centerPosition = $this->car->getCenterPosition();
                    $progressBar->setMessage("<info>$time - parked from $i, $j in $nbiter steps. x</info> : " .
                        $centerPosition['x'] . ", y: " . $centerPosition['y'] . ", theta : " .
                        $this->car->getTheta() . PHP_EOL);
                }

                // boost of last 20 moves - end of markov ? no, only learning take care of the past.
                // learning is then reinforced again
                for ($ii = count($moves) - 1; $ii > count($moves) - 10; $ii--) {
                    //var_dump($moves[$ii]);
                    $this->Q[$moves[$ii][0]][$moves[$ii][1]][$moves[$ii][2]][$moves[$ii][3]] +=
                        $ii - count($moves) + 70;
                }
            }
        }

        $progressBar->finish();
    }

    public function makeStandard(OutputInterface $output, $maxI = 100)
    {
        $progressBar = new ProgressBar($output, $maxI);
        $progressBar->start();
        $progressBar->setRedrawFrequency(1);
        $progressBar->setMessage('');
        $format = '%message% %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%';
        $progressBar->setFormat($format);
        $bestMoves = [];

        for ($time = 0; $time < $maxI; $time++) {
            $progressBar->advance();

            $this->initCarPosition();

            // counter strike
            $nbiter = 0;
            $moves = [];

            do {
                $nbiter++;

                // Current state
                $x = $this->car->getX();
                $y = $this->car->getY();
                $tIndex = $this->car->getThetaIndex();

                // new action random
                $a = $this->getActionByEgreedyPolicy(-1);
                // the car is now in a new position

                $moves [] = [round($x), round($y), $tIndex, $a];
            } while (!$this->isParked() && $nbiter < 20000);

            // stop loop if lost
            if ($nbiter >= 20000) {
                if ($this->verbose) {
                    $progressBar->setMessage("<error>Too long for </error> : " .
                        json_encode($this->car->getPosition()) . PHP_EOL);
                }
            } else {
                if ($this->verbose) {
                    $centerPosition = $this->car->getCenterPosition();
                    $progressBar->setMessage("<info>$time - standard calculation - car parked in $nbiter steps. </info> " .
                        " : " . $centerPosition['x'] . ", y: " . $centerPosition['y'] . ", theta : " .
                        $this->car->getTheta() . PHP_EOL);
                }

                if ($time == 0 || count($bestMoves) > count($moves)) {
                    $bestMoves = $moves;
                }
            }
        }

        $progressBar->finish();

        return $bestMoves;
    }
}
