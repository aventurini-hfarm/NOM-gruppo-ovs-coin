<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 23/04/15
 * Time: 11:20
 */


ini_set('memory_limit', '-1');
//error_reporting(E_ERROR );
require_once realpath(dirname(__FILE__))."/../../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/ItemObject.php";
require_once realpath(dirname(__FILE__))."/ShipmentObject.php";
require_once realpath(dirname(__FILE__))."/MagentoShipmentHelper.php";
require_once realpath(dirname(__FILE__))."/../../omdb/ShipmentDBHelperDM.php";
require_once realpath(dirname(__FILE__))."/../../omdb/DeliveryExportDBHelper.php";
require_once realpath(dirname(__FILE__))."/../../paymentgw/PaymentProcessor.php";
require_once realpath(dirname(__FILE__))."/../../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__)) . "/../../Utils/MailSender.php";

class ShipmentXMLAnalyzerDM {

    private $log;
    public function __construct($file)
    {
        $this->file = $file;
        $this->log = new KLogger('/var/log/nom/import_shipments.log',KLogger::DEBUG);
    }

    public function process()
    {
        $fh = fopen($this->file, 'r');
        $buffer = "";
        $counter = 0;
        while(!feof($fh)){
            $line = fgets($fh);
           // echo "\nLine: ".$line;
            $counter++;
            if ($counter<=2) {
                continue;
            }
            $line = preg_replace('/&(?!amp;)/','&amp;', $line); //RINO 25/07/2016   prevent special character & in firsrt-track
            $str = trim(substr($line,0, strlen($line)-1));
            //$str = $line;

            //echo "\n".$str;
            # do same stuff with the $line
            //echo "\n".substr($line,0, strlen($line)-2);
            //echo "\n!".$str."!";
            if ($str=="</shipment>") {
                //echo "\nFOUND";

                $xmlContent =$buffer.$str;
                //print_r($xmlContent);
                try {
                    $xml = new SimpleXMLElement($xmlContent);
                } catch (Exception $ex) {
                    echo "\nFile: ".$this->file;
                    echo "\nERRORE: ".$xmlContent;

                    $this->log->LogError("Shipment failed: ".$ex);
                    continue;
                }

                try {
                    $this->processShipmentSection($xml);
                }catch (Exception $ex) {
                    $order_no = $xml->{'order_num'};
                    $this->log->LogError("Shipment failed: ".$order_no);

                }
                sleep(1);
                //die;
                $buffer ="";
            } else $buffer .= $line;

        }
        fclose($fh);

    }




    private function getTrxLines($lista_nodi) {

        //$xml = $xmlContent;
        $lista = array();
        foreach ($lista_nodi as $nodo) {
            $xml = $nodo;
            $item = new ItemObject();
            $item->del_line_id = (string)$xml->{'del_line_id'};
            $item->ship_qty = (string)$xml->{'ship_qty'};
            $item->sku = (string)$xml->{'sku'};
            $item->unship_qty = (string)$xml->{'unship_qty'};
            $item->reason_code = (string)$xml->{'reason_code'};
            $item->line_note = (string)$xml->{'line_note'};
            $lista[] = $item;
        }
        //print_r($lista);
        return $lista;

    }





    public function processShipmentSection(SimpleXMLElement $xmlContent = null)
    {
        //$doc = new DOMDocument();
        //$doc->load($this->file);

        $xml = $xmlContent;
        $shipment = new ShipmentObject();

        $order_no = $xml->{'order_num'};
        $shipment->order_no = str_pad((string)$order_no, 8, "0", STR_PAD_LEFT);


        $ncolli = $xml->{'ncolli'};
        $shipment->ncolli = (string)$ncolli;

        $order_date = $xml->{'order_date'};
        $shipment->order_date = (string)$order_date;

        $delivery_id = $xml->delivery_id;
        $shipment->delivery_id = (string)$delivery_id;

        $delivery_date = $xml->{'delivery_date'};
        $shipment->delivery_date = (string)$delivery_date;

        $shipping_date = $xml->{'shipping_date'};
        $shipment->shipping_date = (string)$shipping_date;

        $subinventory = $xml->{'subinventory'};
        $shipment->subinventory = (string)$subinventory;

        $lettera_vettura = $xml->{'lettera_vettura'};
        $shipment->lettera_vettura = (string)$lettera_vettura;

        $esito = $xml->{'esito'};
        $shipment->esito = (string)$esito;

        $first_track = $xml->{'first_track'};
        $shipment->first_track = (string)$first_track;

        $last_track = $xml->{'last_track'};
        $shipment->last_track = (string)$last_track;

        $list_track = $xml->{'list_track'};
        $shipment->list_track = (string)$list_track;

        $shipment_note = $xml->{'shipment_note'};
        $shipment->shipment_note = (string)$shipment_note;



        $trx_lines = $this->getTrxLines($xml->xpath('trx_line'));
        $shipment->trx_lines = $trx_lines;



       // print_r($shipment);

        //aggiorno db
        $shipmentDbHelper = new ShipmentDBHelperDM();
        $shipmentDbHelper->addShipment($shipment);
        $this->log->LogDebug("Shipment Added to DB");

        if ($shipment->esito == '1') {
            echo "\nEsito: ".$shipment->esito;

            $magHelper = new MagentoOrderHelper();
            $status = $magHelper->getOrderStatus($shipment->order_no);

            //invio primo tracking
            $deliveryExportHelper = new DeliveryExportDBHelper();
            $deliveryInfo = $deliveryExportHelper->getDeliveryInfo($shipment->order_no, $shipment->delivery_id);
            $numero_delivery = $deliveryExportHelper->getNumberOfDeliveries($shipment->order_no);


            if ($status == 'complete') {
                echo "\nOrdine già complete: ".$shipment->order_no;
                //MailSender::sendEmail("Attenzione arrivato shipment per ordine COMPLETE: ".$shipment->order_no,'nomovs@gmail.com','Warning NOM');
                $error = 'Ordine già completo';
                throw new Exception($error);
                return;
            }



            if ($shipmentDbHelper->isOrderShipped($shipment->order_no)) {
                $magOrderHelper = new MagentoOrderHelper();
                $increment_id = $magOrderHelper->getOrderIdByDWId($shipment->order_number);
                $magOrderHelper->setStatusProcessing($increment_id);


                echo "\nShipping Full Order";
                $helper = new MagentoShipmentHelper();
                $helper->shippingFullOrder($shipment);

                //esegui la capture
                echo "\nEsegui Capture";
                $payment = new PaymentProcessor($shipment->order_no);
                $result = $payment->executePayment();
                //$result = true;

                if (!$result) {
                    echo "\nMetto ordine in pending payment: ".$shipment->order_number;
                    $magOrderHelper = new MagentoOrderHelper();
                    $increment_id = $magOrderHelper->getOrderIdByDWId($shipment->order_number);
                    $magOrderHelper->setStatusPendingPayment($increment_id);
                    return;

                }
                echo "\nOrdine pronto per essere completato\n";
                $magHelper = new MagentoOrderHelper();
                echo "\nCrea parte fiscale ovvero scontrino o fatturazione";
                $magHelper->createFiscalInfo($shipment->order_no);
                echo "\nOrdine pronto per invio email conferma";

                $magHelper->prepareConfirmOrder($shipment->order_no, $shipment->delivery_id, 2); //notifica complessiva

                echo "\nCrea Invoice su OM";
                $magHelper->doInvoice($shipment->order_no);

                echo "\nShipment OK: ".$shipment->order_no;
                //$magHelper->setStatusComplete($increment_id);

            } else {
                /**
                 * INVIO solo la parte di ordine per cui lo shipment è positivo
                 */

                echo "\nInvio tracking parziale\n";
                $magHelper = new MagentoOrderHelper();

                $magHelper->prepareConfirmOrder($shipment->order_no, $shipment->delivery_id, 1); //notifica parziale
                $increment_id = $magHelper->getOrderIdByDWId($shipment->order_number);
                $magHelper->setStatusProcessing($increment_id);


            }
        } else {
            echo "\nEsito negativo Ordine: ".$shipment->order_no;
            $magHelper = new MagentoOrderHelper();
            $increment_id = $magHelper->getOrderIdByDWId($shipment->order_no);
            $magHelper->setStatusOnHold($increment_id);
            echo "\nOrdine messo on Hold: ".$shipment->order_no." , MAGENTO ID: ($increment_id)";
            //MailSender::sendEmail("Attenzione arrivato shipment negativo: ".$shipment->order_no,'nomovs@gmail.com','Warning NOM');
        }

    }
}

$t = new ShipmentXMLAnalyzerDM('/home/OrderManagement/testFiles/shipments_export/inbound/shipments_ov_it_WH_SG_20161206180031.xml.error');
$t->process();