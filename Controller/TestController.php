<?php

/**
 * TestController
 *
 * This controller is used to install default / test values to the system.
 * The controller can only be accessed from allowed IP address.
 *
 * @package		MemberManagementBundleBundle
 * @subpackage	Controller
 * @name	    TestController
 *
 * @author		Can Berkol
 *
 * @copyright   Biber Ltd. (www.biberltd.com)
 *
 * @version     1.0.0
 *
 */

namespace BiberLtd\Bundle\StockManagementBundle\Controller;
use BiberLtd\Core\CoreController;


class TestController extends CoreController {

    public function testAction(){
        $stockModel = $this->get('stockmanagement.model');
        $stock = new \stdClass();
        $stock->id = 4;
        $stock->product = 1;
        $stock->sku = 'TEST-2';
        $stock->quantity = 10;

//        $response = $stockModel->insertStock($stock);
        $response = $stockModel->updateStock($stock);
        if ($response['error']) {
            exit('Kaydedilmedi');
        }
        foreach ($response['result']['set'] as $item) {
            echo $item->getId();die;
        }
//
//        $response = $stockModel->getStock(10,'id');
//        if ($response['error']) {
//            exit('tax bulunamadı0');
//        }
//        echo $response['result']['set']->getId();die;

//        $response = $stockModel->deleteStock(7);
//        if ($response['error']) {
//            exit('tax bulunamadı0');
//        }
//        echo $response['result']['total_rows'].' row(s) deleted.';die;

    }
}
