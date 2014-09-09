<?php

namespace BiberLtd\Bundle\StockManagementBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('BiberLtdStockManagementBundle:Default:index.html.twig', array('name' => $name));
    }
}
