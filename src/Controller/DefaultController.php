<?php

namespace Starfruit\PostBundle\Controller;

use Pimcore\Controller\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends FrontendController
{
    /**
     * @Route("/starfruit_post")
     */
    public function indexAction(Request $request): Response
    {
        return new Response('Hello world from starfruit_post');
    }
}
