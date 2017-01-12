<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 08/06/15
 * Time: 16:44
 */

require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../../common/FileGenerator.php";
require_once realpath(dirname(__FILE__))."/../../common/CountersHelper.php";

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class CompleteOrders {



    public function __construct()    {
        $this->count=0;
    }




    function orderCallback($args)  {
        $product = Mage::getModel('sales/order');

        $increment_id = $args['row']['increment_id'];
        $order = Mage::getModel('sales/order')->load($increment_id, 'increment_id');

        $dw_order_id=ltrim($order->getDwOrderNumber(),'0');

        //$shipped = array("264244","264675","264714","264754","264832","264836","264844","264846","264847","264849","264850","264851","264852","264853","264854","264856","264857","264859","264860","264861","264862","264868","264870","264871","264872","264873","264874","264875","264876","264877","264878","264879","264880","264881","264882","264884","264889","264893","264894","264895","264896","264897","264898","264899","264900","264904","264908","264909","264910","264911","264912","264913","264914","264915","264916","264917","264918","264928","264929","264930","264932","264933","264934","264935","264936","264938","264939","264940","264941","264942","264945","264946","264947","264948","264950","264951","264952","264960","264962","264965","264966","264967","264968","264969","264970","264971","264972","264973","264974","264975","264976","265019","265020","265021","265022","265023","265024","265025","265026","265032","265119","265120","265121","265122","265123","265124","265125","265126","265127","265128","265129");
        $in_processing= array("00265472","00265334", "00265436", "00265237", "00265079", "00265160", "00265159", "00265153", "00265142",    "00265033","00265034","00265035","00265036","00265037","00265038","00265039","00265040","00265041","00265042","00265043","00265044","00265045","00265047","00265048","00265054","00265056","00265057","00265058","00265059","00265060","00265061","00265062","00265063","00265064","00265065","00265066","00265067","00265068","00265069","00265072","00265074","00265075","00265076","00265077","00265078","00265080","00265081","00265082","00265083","00265084","00265085","00265086","00265087","00265089","00265090","00265091","00265092","00265093","00265094","00265095","00265096","00265130","00265131","00265132","00265133","00265134","00265135","00265136","00265138","00265140","00265141","00265143","00265144","00265145","00265146","00265147","00265148","00265149","00265150","00265151","00265152","00265154","00265155","00265156","00265157","00265158","00265161","00265169","00265177","00265182","00265183","00265184","00265185","00265186","00265187","00265219","00265220","00265222","00265223","00265224","00265226","00265227","00265228","00265229","00265230","00265231","00265232","00265233","00265234","00265235","00265236","00265238");

        if ( ! in_array($dw_order_id, $in_processing)) {
            $a=0;
            $order->setData('state', "complete");
            $order->setStatus("complete");
            $order->save();
        } else {
            $this->count++;
            echo "\n".$this->count." Remain processing : ".$dw_order_id;
        }


    }

    public function execute() {


        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addAttributeToSelect('increment_id');
        $collection->addAttributeToSelect('status');
        $collection->addAttributeToSelect('state');
        $collection->addAttributeToFilter('state','processing');


        Mage::getSingleton('core/resource_iterator')->walk($collection->getSelect(), array(array($this,'orderCallback')));


    }



}



$completeOrders = new CompleteOrders();
$completeOrders->execute();


