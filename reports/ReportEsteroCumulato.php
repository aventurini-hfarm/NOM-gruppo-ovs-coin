<?php
/**
 * Created by PhpStorm.
 * User: vincenzosambucaro
 * Date: 16/05/15
 * Time: 16:33
 */

require_once realpath(dirname(__FILE__))."/../common/OMDBManager.php";
require_once realpath(dirname(__FILE__))."/../common/ConfigManager.php";
require_once realpath(dirname(__FILE__))."/../common/KLogger.php";
require_once realpath(dirname(__FILE__))."/../common/CountersHelper.php";
require_once realpath(dirname(__FILE__))."/../common/FileGenerator.php";
require_once realpath(dirname(__FILE__))."/../omdb/OrderDBHelper.php";
require_once realpath(dirname(__FILE__))."/../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__))."/../dw2om/orders/MagentoOrderHelper.php";
require_once realpath(dirname(__FILE__)) . "/../omdb/PaymentDBHelper.php";
require_once realpath(dirname(__FILE__)) . "/../creditmemo/CreditMemoHelper.php";
require_once realpath(dirname(__FILE__)) .'/../Utils/PHPExcel/Classes/PHPExcel/IOFactory.php';
require_once realpath(dirname(__FILE__)) .'/../Utils/PHPExcel/Classes/PHPExcel.php';
require_once realpath(dirname(__FILE__)) . "/../Utils/mailer/PHPMailerAutoload.php";
require_once realpath(dirname(__FILE__)) . "/../Utils/pdf/dompdf_config.inc.php";
require_once realpath(dirname(__FILE__))."/../omdb/CountryDBHelper.php"; //ESTERO

require_once '/var/www/magento/shell/abstract.php';
require_once('/var/www/magento/app/Mage.php');


Mage::app();


class ReportEsteroCumulato {

    private $status_to_export = "complete";

    private $log;
    private $config;
    private $start_date;
    private $end_date;

    public function __construct()
    {
        $this->config = new ConfigManager();
        $this->log = new KLogger('/var/log/nom/reports.log',KLogger::DEBUG);


    }

    /**
     * Inizia export flusso scontrini in base al range temporale
     * @param $start data inizio
     * @param $end data fine
     */
    public function export($start, $end) {

        $this->start_date = $start;
        $this->end_date = $end;

        $date_tmp= $start;
        $start_date_tmp = $start;
        $end_date_tmp = $end;
       // echo "\nDATA: ".$start_date_tmp;

        $this->log->LogInfo("Start Generazione Report");
        $lista_record = $this->getListaRecordDaExportare($start_date_tmp, $end_date_tmp, $this->status_to_export);
        //print_r($lista_record);

        if ($lista_record) {

            $this->writeRecordToFile($lista_record);
        }
        else
            $this->log->LogInfo("\nNessun ordine includere nel report");



    }






        private function writeRecordToFile($lista_record) {


            $path_template = realpath(dirname(__FILE__)) .'/template';
            $excel = PHPExcel_IOFactory::createReader('Excel2007');
            $excel = $excel->load($path_template."/report_estero_cumulato.xlsx");
            $excel->setActiveSheetIndex(0);

            $objWorksheet = $excel->getActiveSheet();
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                ),
                'font'  => array(
                    'size'  => 7,
                    'name'  => 'Times New Roman'
                )
            );

            $styleBoldArray = array(
                'borders' => array(
                    'allborders' => array(
                        'style' => PHPExcel_Style_Border::BORDER_THIN
                    )
                ),
                'font'  => array(
                    'bold' => true,
                    'size'  => 10,
                    'name'  => 'Times New Roman'
                )
            );




            $riga = 12;
            $totale_g=0;
            $totale_h=0;
            $totale_i=0;
            $totale_j=0;
            $totale_k=0;
            $totale_l=0;
            foreach ($lista_record as $record) {
                $cella = "A".$riga;
                $xfIndex = $objWorksheet->getCell('A12')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue($record->country_id);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);


                $cella = "B".$riga;
                $cella_estesa = 'B'.$riga.':C'.$riga;
                $objWorksheet->mergeCells($cella_estesa);
                $xfIndex = $objWorksheet->getCell('B12')->getXfIndex();
                $objWorksheet->getCell($cella)->setValue(round($record->totale, 2));
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);
                $objWorksheet->getStyle($cella_estesa)->applyFromArray($styleArray);


                $cella = "D".$riga;
                $xfIndex = $objWorksheet->getCell('D12')->getXfIndex();
                $cDetails = CountryDBHelper::getCountryDetails($record->country_id);
                $objWorksheet->getCell($cella)->setValue($cDetails->soglia);
                $objWorksheet->getCell($cella)->setXfIndex($xfIndex);



                $riga++;
            }



            $timestamp = date('Ymd');
            $tmp_date = date("d/m/Y");
            $cella = "C7";
            $objWorksheet->getCell($cella)->setValue("Data: ".$tmp_date);

            $cella = "A8";
            $tmp_date = date("01/01/Y ");
            $objWorksheet->getCell($cella)->setValue("Da: ".$tmp_date);
            $cella = "A9";
            $tmp_date = date("31/12/Y ");
            $objWorksheet->getCell($cella)->setValue("A: ".$tmp_date);


            $file_name = "REPORT_ESTERO_CUMULATO_".$timestamp.".xlsx";
            $directory = '/tmp';
            $full_name = $directory."/".$file_name;

            $objWriter = PHPExcel_IOFactory::createWriter($excel,'Excel2007');
            $objWriter->save($full_name);


            //CREA HTML
            $objWriter = new PHPExcel_Writer_HTML($excel);
            $html_file_name=  $directory."/REPORT_ESTERO_CUMULATO_".$timestamp.".html";
            $objWriter->save($html_file_name);


            //CREA PDF
            $rendererName = PHPExcel_Settings::PDF_RENDERER_MPDF;
            $rendererLibrary = 'mpdf60';
            $rendererLibraryPath = "/home/OrderManagement/Utils/".$rendererLibrary;
            //echo "\nPATH:".$rendererLibraryPath;
            #$rendererLibraryPath = dirname(__FILE__).'/PHPExcel/Tests/PDF/' . $rendererLibrary;

            if (!PHPExcel_Settings::setPdfRenderer(
                $rendererName,
                $rendererLibraryPath
            )) {
                die(
                    'Please set the $rendererName and $rendererLibraryPath values' .
                    PHP_EOL .
                    ' as appropriate for your directory structure'
                );
            }
            $objWriter = new PHPExcel_Writer_PDF($excel);

            //$objWorksheet->setShowGridlines(true);
            $objWriter->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
            $pdf_file_name=  $directory."/REPORT_ESTERO_CUMULATO_".$timestamp.".pdf";
            $objWriter->save($pdf_file_name);


           $this->inviaEmail($full_name, $pdf_file_name);



        unset($content);
    }

    /**
     * Estrae la lista ordini direttamente da magento
     * @param null $start
     * @param null $end
     * @return mixed
     */
    private function getListaRecordDaExportare($start = null, $end = null, $status='complete') {

        $con = OMDBManager::getMagentoConnection();

        $sql ="SELECT  sum(o.base_grand_total) as totale, a.country_id FROM sales_flat_order o , sales_flat_order_address a
              WHERE o.entity_id = a.parent_id
              AND (o.bill_date between '$start' AND '$end')
              AND o.status='$status'
              AND a.address_type='shipping'
            GROUP BY a.country_id
         ";

        $start = date('Y-01-01');
        $end = date('Y-12-31');

        $sql ="SELECT  sum(o.base_grand_total) as totale, a.country_id FROM sales_flat_order o , sales_flat_order_address a
              WHERE o.entity_id = a.parent_id
              AND (str_to_date(o.bill_date,'%d/%m/%Y') BETWEEN '$start' AND '$end')
              AND o.status='$status'
              AND a.address_type='shipping'
            GROUP BY a.country_id
         ";



        //echo "\nLog: ".$sql;
        $res = mysql_query($sql);
        $lista = array();
        while ($row = mysql_fetch_object($res)) {
            $record = new stdClass();
            $record->totale = $row->totale;
            $record->country_id = $row->country_id;
            $lista[] = $record;
        }
        OMDBManager::closeConnection($con);

        $this->log->LogDebug("Record trovati:");
        //print_r($lista);
        return $lista;
    }


    private function inviaEmail($nome_file, $pdf_file_name = null) {

        //return;
        //info ordine
        $invoicepath = $nome_file;
        $message = "Report Automatico";

        $email_array=array(
            //"ecommerce.tracking@ovs.it",
            "nomovs@gmail.com",
            //"ovs.support@everis.com",
            //"support.ovs@nuvo.it"
        );

        $mail = new PHPMailer;
        $mail->Mailer   = 'sendmail';           //settare sendmail come mailer Rino 30/06/2016
        $mail->Sender   = 'noreply@ovs.it';     //settare anche il Sender per sendmail
        $mail->From 	= 'noreply@ovs.it';
        $mail->FromName = 'OVS Online Store';
        $mail->Subject 	= 'Report Automatico: Estero Cumulato';
        $mail->Body		= $message;


        $mail->addAttachment($invoicepath);
        if ($pdf_file_name)
            $mail->addAttachment($pdf_file_name);

        $mail->isHTML(false);
        foreach($email_array as $email_addr) {
            $mail->addAddress($email_addr);
        }

        $mail->send();

    }
}

//TODO METTERE LA DATA AUTOMATICA
$t = new ReportEsteroCumulato();
$start_date="2016-08-04 00:00:00";
$end_date="2016-08-04 23:59:59";

$date= date('Y-m-d');
$start_date = date('01/01/Y');
$end_date = date('31/12/Y');

$t->export($start_date, $end_date);
