<?php

namespace App\UseCases;

//https://davidwalsh.name/canvas-demos for animation
//http://php.net/manual/en/imagickdraw.translate.php

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;

class ParkingUseCase
{
    //dim applet
    const WIDTH = 100;
    const HEIGHT = 50;

    //dim de la voiture
    const CAR_WIDHT = 5;
    const CAR_HEIGHT = 2;

    //ligne ou doit finir la voiture
    const HEIGHT_GOAL = 45; //self::HEIGHT - self::CAR_HEIGHT / 2 - 3;

    const GO_ACTION_DISTANCE = 1; // PAS D'AVANCE
    const TURN_ACTION_ANGLE = 0.1745; // 10 DEG --> ce doit etre une static pour avoir une vrai valeur a partir de pi
    const NB_ANGLE_AVAILABLE = 36;
    const NB_ACTION_AVAILABLE = 6;

    //Constantes de recompense
    const REWARD_K1 = 1;     //y0
    const REWARD_K2 = 40;    //goal (entre 20 et 40)
    const REWARD_K3 = 12;    //theta (entre 10 et 15)

//public static double axe_y = Math.cos(virage)*8; // AXE DE ROTATION DE LA VOITURE

//const CAR = new \imagerectangle(Lvoiture,Hvoiture);
    //dim terrain
//const Rectangle bord = new Rectangle(0,0,X,Y);
//const Rectangle voiG = new Rectangle(0,Y - Hvoiture - 2, 30,Hvoiture+2);
//const Rectangle voiD = new Rectangle(47,Y - Hvoiture -2, 33,Hvoiture+2);


    /** @var array $Q espace d'etats-actions double [][][][] */
    private $Q = [];

    /** @var array $states espace des etats approximés possibles boolean[][][] */
    private $state = [];

    /** @var array $rewards double [][] */
    private $rewards = [];

    // car position
    private $X, $Y, $thetaIndex, $Theta;


    public function __construct()
    {
        $this->X = $this->Y = $this->thetaIndex = $this->Theta = 0;

        $this->initR();

        $this->initState();

        $this->initQ();

        echo "Init ok" . PHP_EOL;
    }

    public function getPosition()
    {
        return [
            'X' => $this->X,
            'Y' => $this->Y,
            'thetaIndex' => $this->thetaIndex,
            'theta' => $this->Theta,
        ];
    }

    public function setCarPosition($x, $y, $theta, $thetaIndex)
    {
        $this->X = $x;
        $this->Y = $y;
        $this->Theta = $theta;
        $this->thetaIndex = $thetaIndex;
    }

    //Init etat (Si un etat est valable on met true, autrement on met faux)
    protected function initState()
    {
        for ($i = 0; $i < self::WIDTH; $i++) {
            for ($j = 0; $j < self::HEIGHT; $j++) {
                for ($k = 0; $k < self::NB_ANGLE_AVAILABLE; $k++) {
                    $this->state[$i][$j][$k] = $this->theCarIsInTheGame($i, $j, ($k * self::TURN_ACTION_ANGLE));
                }
            }
        }
    }

    //Renvoie vrai si la voiture en x,y theta(en radians) est entierement dans le terrain
    public function theCarIsInTheGame($x, $y, $theta)
    {

        // La Voiture
        // Supposons que la voiture est centrée sur l'origine de repere et sa direction est ->,
        // alors le coordonnes de la voiture sont les suivantes
        // A(-L/2,-H/2) _______ C (L/2,-H/2)
        //             |       |
        //             |   .c  |  c est le centre de la voiture (0,0)
        //             |_______|
        // B(-L/2,H/2)		   D(L/2,H/2)
        // Pour calculer Les valeurs exactes de les coordonnées on aplique:
        //               une rotation de theta et une translation de (x,y);
        $xA = (-self::CAR_WIDHT / 2) * cos($theta) + (self::CAR_HEIGHT / 2) * sin($theta) + $x;
        $yA = (-self::CAR_WIDHT / 2) * sin($theta) - (self::CAR_HEIGHT / 2) * cos($theta) + $y;
        if (!self::thePointIsInTheGame($xA, $yA)) {
            return false;
        }

        $xB = (-self::CAR_WIDHT / 2) * cos($theta) - (self::CAR_HEIGHT / 2) * sin($theta) + $x;
        $yB = (-self::CAR_WIDHT / 2) * sin($theta) + (self::CAR_HEIGHT / 2) * cos($theta) + $y;
        if (!self::thePointIsInTheGame($xB, $yB)) {
            return false;
        }

        $xC = (self::CAR_WIDHT / 2) * cos($theta) + (self::CAR_HEIGHT / 2) * sin($theta) + $x;
        $yC = (self::CAR_WIDHT / 2) * sin($theta) - (self::CAR_HEIGHT / 2) * cos($theta) + $y;
        if (!self::thePointIsInTheGame($xC, $yC)) {
            return false;
        }

        $xD = (self::CAR_WIDHT / 2) * cos($theta) - (self::CAR_HEIGHT / 2) * sin($theta) + $x;
        $yD = (self::CAR_WIDHT / 2) * sin($theta) + (self::CAR_HEIGHT / 2) * cos($theta) + $y;
        if (!self::thePointIsInTheGame($xD, $yD)) {
            return false;
        }

        return true;
    }

    //Fonction verifiant si un point ne sort pas du terrain - uiliser dans get_gamma()
    public static function thePointIsInTheGame($x, $y)
    {
        //@todo mettre la rangé de voiture avec la place libre

        return $x > 0 && $x < self::WIDTH && $y > 0 && $y < self::HEIGHT;
    }

    //Init la fonction de l'utilite Q (a priori on initialise avec 0)
    //i,j definissent les coordonnees de la voiture,k l'angle et m defini l'action
    protected function initQ()
    {
        for ($i = 0; $i < self::WIDTH; $i++) {
            for ($j = 0; $j < self::HEIGHT; $j++) {
                for ($k = 0; $k < self::NB_ANGLE_AVAILABLE; $k++) {
                    if ($this->state[$i][$j][$k]) {
                        for ($a = 0; $a < self::NB_ACTION_AVAILABLE; $a++) {
                            $this->Q[$i][$j][$k][$a] = 0;
                        }
                    }
                }
            }
        }
    }

    //Init a les recompense des etat en fct de leur proximite du but
    protected function initR()
    {
        for ($i = 0; $i < self::WIDTH; $i++) {
            for ($j = 0; $j < self::HEIGHT; $j++) {
                for ($k = 0; $k < self::NB_ANGLE_AVAILABLE; $k++) {
                    //On recompense le deplacement vers le bas
                    $this->rewards[$i][$j][$k] = self::REWARD_K1 / (1 + pow($j - self::HEIGHT_GOAL, 2));

                    //On recompense l'arrivee en place
                    if ($j >= self::HEIGHT_GOAL) {
                        $this->rewards[$i][$j][$k] += self::REWARD_K2;
                    }

                    //On recompense l'horizontalité
                    if ($j > self::HEIGHT_GOAL - 5) {
                        $this->rewards[$i][$j][$k] += self::REWARD_K3 * abs(cos($k * self::TURN_ACTION_ANGLE));
                    }
                }
            }
        }
    }

    //Renvoie vrai si la voiture est garee en analysant o
    public function isParked()
    {
        //si la voiture n'a pas atteint y0 alors elle n'est pas garée
        if ($this->Y < self::HEIGHT_GOAL) {
            return false;
        }

        //si la voiture n'est pas a l'horizontale alors elle n'est pas garee
        $maxAngle = 3 * self::TURN_ACTION_ANGLE;
        if (abs($this->Theta) > $maxAngle && abs($this->Theta - pi()) > $maxAngle) {
            return false;
        }

        return true;
    }

    //rotation de la voiture
    public function turn(int $sens_r, int $sens_y)
    {
        // premier approx, elle tourne sur elle meme
        $this->thetaIndex += $sens_r;
        $this->Theta += $sens_r * self::TURN_ACTION_ANGLE;
        $this->X += $sens_y * cos($this->Theta);
        $this->Y += $sens_y * sin($this->Theta);
    }

    public function goForward()
    {
        $this->X += cos($this->Theta);
        $this->Y += sin($this->Theta);
    }

    public function goBackward()
    {
        $this->X -= cos($this->Theta);
        $this->Y -= sin($this->Theta);
    }

    //On passe d'un etat a un autre en faisant a
    public function move(int $action)
    {
        //Sauvegarde etat initial
        $exThetaIndex = $this->thetaIndex;
        $exTheta = $this->Theta;
        $exX = $this->X;
        $exY = $this->Y;

        //Exectution de l'action
        switch ($action) {
            case 0:
                $this->goForward();
                break;
            case 1:
                $this->goBackward();
                break;
            case 2:
                $this->turn(1, 1);
                break;
            case 3:
                $this->turn(1, -1);
                break;
            case 4:
                $this->turn(-1, 1);
                break;
            case 5:
                $this->turn(-1, -1);;
                break;
        }

        if ($this->thetaIndex >= self::NB_ANGLE_AVAILABLE) {
            $this->thetaIndex = $this->thetaIndex % self::NB_ANGLE_AVAILABLE;
        }

        $this->Theta = $this->thetaIndex * self::TURN_ACTION_ANGLE;

        // round because states are integer
        $this->X = round($this->X);
        $this->Y = round($this->Y);

        // if car is in the game, then it is ok
        if (isset($this->state[$this->X][$this->Y][$this->thetaIndex]) &&
            true === $this->state[$this->X][$this->Y][$this->thetaIndex]) {
            //echo "mvt $action autorisé --> x : $this->X, y : $this->Y".PHP_EOL;
            return true;
        }

        //echo "mvt $action non autorisé --> x : $this->X, y : $this->Y".PHP_EOL;

        // very bad Q if the choosen action is forbidden
        $this->Q[$exX][$exY][$exThetaIndex][$action] = -10000;

        // else return in previous position
        $this->setCarPosition($exX, $exY, $exTheta, $exThetaIndex);

        return false;
    }

    //Action optimale à partir de s
    public function getBestMove()
    {
        $Tindex = $this->thetaIndex;
//        if (!isset($this->Q[$this->X][$this->Y][$Tindex][0])) {
//            echo "this->Q[$this->X][$this->Y][$Tindex][0]";
//        }
        $max = $this->Q[$this->X][$this->Y][$Tindex][0];
        $bestMove = 0;

        for ($m = 1; $m < self::NB_ACTION_AVAILABLE; $m++) {
            if ($max < $this->Q[$this->X][$this->Y][$Tindex][$m]) {
                $max = $this->Q[$this->X][$this->Y][$Tindex][$m];
                $bestMove = $m;
            }
        }

        return $bestMove;
    }

    // choose action to do with e-greedy policy
    public function getActionByEgreedyPolicy($i)
    {
        $test = [];
        do {
            if (count($test) == self::NB_ACTION_AVAILABLE) {
                throw new \Exception('No mo action to try');
            }

            $a = rand(0, self::NB_ACTION_AVAILABLE - 1);
            if (rand(0, 100) < $i / 2) { // $i between 5 and 95
                $a = $this->getBestMove();
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
        $progressBar->setRedrawFrequency($maxI / 100);
        $progressBar->setMessage('');
        $progressBar->setFormat(' %message% %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        for ($time = 0; $time < $maxI; $time++) {
            $progressBar->advance();

            // random initial car position
            $i = rand(5, self::WIDTH - self::CAR_WIDHT - 1);
            $j = rand(5, self::HEIGHT - 10);

            // episod start if the intial state is valid
            if ($this->state[$i][$j][0]) {
                // counter strike
                $nbiter = 0;

                // learning factor
                $initFactor = 0.7;
                $finalFactor = 0.2;
                $learningFactor = $initFactor - (($initFactor - $finalFactor) / 1000) * $i;

                // the intial car position
                $this->X = $i;
                $this->Y = $j;
                $this->Theta = 0;

                do {
                    $nbiter++;

                    // stop loop if lost
                    if ($nbiter > 50000) {
                        $progressBar->setMessage("Too long for $i, $j : " . json_encode($this->getPosition()) . PHP_EOL);
                        break;
                    }

                    // Current state
                    $x = $this->X;
                    $y = $this->Y;
                    $tIndex = $this->thetaIndex;

                    // new action by e-greedy policy
                    $a = $this->getActionByEgreedyPolicy($i);
                    // the car is now in a new position

                    // Q evolution by bellman equation
                    $rewardNewPosition = $this->rewards[$this->X][$this->Y][$this->thetaIndex];
                    $bestQNewPosition = $this->Q[$this->X][$this->Y][$this->thetaIndex][$this->getBestMove()];
                    $oldQ = $this->Q[$x][$y][$tIndex][$a];

                    $this->Q[$x][$y][$tIndex][$a] = $oldQ + $learningFactor * ($rewardNewPosition + $bestQNewPosition - $oldQ);

                } while (!$this->isParked());
            }
        }

        $progressBar->finish();
    }
}
