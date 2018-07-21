<?php
namespace App\Model;

class Parking
{
    const WIDTH = 250;
    const HEIGHT = 100;

    //ligne ou doit finir la voiture
    const HEIGHT_GOAL = self::HEIGHT - Car::WIDTH / 2 - 4;

    const PLACE_X = 90;
    const PLACE_Y = self::HEIGHT - Car::WIDTH;
}
