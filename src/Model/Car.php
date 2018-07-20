<?php
namespace App\Model;

class Car
{
    const LENGHT = 50;
    const WIDTH = 20;

    const INITIAL_POSITION_LEFT = 20;
    const INITIAL_POSITION_TOP = 20;

    const TURN_FACTOR = 70;

    const GO_ACTION_DISTANCE = 1; // PAS D'AVANCE
    const TURN_ACTION_ANGLE = 0.08725; // 5 DEG --> ce doit etre une static pour avoir une vrai valeur a partir de pi
    const NB_ANGLE_AVAILABLE = 72;


    /** @var double  */
    protected $x = 0;

    /** @var double  */
    protected $y = 0;

    /** @var double  */
    protected $theta = 0;
    protected $thetaIndex = 0;

    public function move(int $action)
    {
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
                $this->turn(-1, -1);
                break;
        }

        if ($this->thetaIndex >= Car::NB_ANGLE_AVAILABLE) {
            $this->thetaIndex = $this->thetaIndex % Car::NB_ANGLE_AVAILABLE;
        }
        if ($this->thetaIndex < 0) {
            $this->thetaIndex = Car::NB_ANGLE_AVAILABLE + $this->thetaIndex;
        }

        $this->theta = $this->thetaIndex * Car::TURN_ACTION_ANGLE;
    }

    //rotation de la voiture
    public function turn(int $sens_r, int $sens_y)
    {
        // on se met sur le centre de rotation
        $this->x -= $sens_r*sin($this->theta) * self::TURN_FACTOR;
        $this->y += $sens_r*cos($this->theta) * self::TURN_FACTOR;

        // on tourne
        $this->thetaIndex += $sens_y;
        $this->theta += $sens_y * Car::TURN_ACTION_ANGLE;

        // on se met au left arriere
        $this->x += $sens_r*sin($this->theta) * self::TURN_FACTOR;
        $this->y -= $sens_r*cos($this->theta) * self::TURN_FACTOR;
    }

    public function goForward()
    {
        $this->x += cos($this->theta);
        $this->y += sin($this->theta);
    }

    public function goBackward()
    {
        $this->x -= cos($this->theta);
        $this->y -= sin($this->theta);
    }

    public function getCenterPosition()
    {
        return [
            'x' => $this->x + sin($this->theta) * Car::LENGHT / 2 + cos($this->theta) * Car::WIDTH / 2,
            'y' => $this->getCenterY()
        ];
    }

    public function getCenterY()
    {
        return $this->y + cos($this->theta) * Car::WIDTH / 2 + sin($this->theta) * Car::LENGHT / 2;
    }

    public function getPosition()
    {
        $center = $this->getCenterPosition();

        return [
            'X' => $this->x,
            'Y' => $this->y,
            'thetaIndex' => $this->thetaIndex,
            'theta' => $this->theta,
            'centerX' => $center['x'],
            'centerY' => $center['y'],
        ];
    }

    public function setPosition($x, $y, $theta, $thetaIndex)
    {
        $this->x = $x;
        $this->y = $y;
        $this->theta = $theta;
        $this->thetaIndex = $thetaIndex;
    }

    /**
     * @return double
     */
    public function getTheta()
    {
        return $this->theta;
    }

    /**
     * @return int
     */
    public function getX()
    {
        return $this->x;
    }

    /**
     * @return float
     */
    public function getY()
    {
        return $this->y;
    }

    /**
     * @return int
     */
    public function getThetaIndex()
    {
        return $this->thetaIndex;
    }
}
