<?php

namespace ACore\System\Utils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

abstract class ApiController extends Controller {

    public function serialize($res) {
        return new Response($this->get('serializer')->serialize($res, 'json'),Response::HTTP_OK,array('content-type' => 'application/json'));
    }

}
