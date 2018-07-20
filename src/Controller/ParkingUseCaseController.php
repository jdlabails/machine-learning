<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ParkingUseCaseController extends Controller
{
    /**
     * @Route("/parking", name="parking")
     */
    public function parking()
    {
        return $this->render('parking.html.twig', []);
    }
}