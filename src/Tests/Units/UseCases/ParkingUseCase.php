<?php

namespace App\Tests\Units\UseCases;

use App\Model\Car;
use atoum;

class ParkingUseCase extends atoum
{
    /*
         * This method is dedicated to the getHiAtoum() method
         */
    public function testCarPosition($x, $y, $theta, $xCenter, $yCenter)
    {
        $this
            // creation of a new instance of the tested class
            ->given($this->newTestedInstance)
            ->if($this->testedInstance->setCarPosition($x, $y, $theta, 0))
            ->then
            ->array($carPosition = $this->testedInstance->getCarPosition())->hasSize(4)
            ->variable($carPosition['X'])->isEqualTo($x)
            ->variable($carPosition['Y'])->isEqualTo($y)
            ->variable($carPosition['theta'])->isEqualTo($theta)
            ->variable($carPosition['thetaIndex'])->isEqualTo(0)
            ->array($carCenterPosition = $this->testedInstance->getCarCenterPosition())->hasSize(2)
            ->float(round($carCenterPosition['x']))->isEqualTo(round($xCenter))
            ->float(round($carCenterPosition['y']))->isEqualTo(round($yCenter));
    }

    protected function testCarPositionDataProvider()
    {
        return [
            [10, 10, 0, 10 + Car::LENGHT / 2, 10 + Car::WIDTH / 2],
            [10, 10, pi()/2, 10 + Car::WIDTH / 2, 10 + Car::LENGHT / 2],
            [10.0, 10.0, pi(), 10 - Car::LENGHT / 2, 10 - Car::WIDTH / 2],
            [10.0, 10.0, pi()/4, 10 + sqrt(2)*Car::LENGHT / 2, 10 + sqrt(2)*Car::WIDTH / 2],
        ];
    }
}
