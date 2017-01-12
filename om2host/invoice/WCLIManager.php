<?php
/**
 * Created by PhpStorm.
 * User: vsambucaro
 * Date: 3/18/14
 * Time: 12:05 AM
 */
require_once '../shell/abstract.php';
require_once('../app/Mage.php');
Mage::app();
require_once(Mage::getBaseDir('lib') . '/Borgione/DBUtils.php');
require_once(Mage::getBaseDir('lib') . '/Borgione/DirUtils.php');

require_once("WCLIFileWriter.php");

class WCLIManager {
	
	const DATE_FORMAT = 'dd/MM/yyyy'; #per la format dell'oggetto Date
	const DATE_FORMAT_SERVER = 'yyyy-MM-dd';	
	
	protected $_forceLog;

    public function __construct() {
        $this->_forceLog = Mage::getStoreConfig("sferpexport/general/debug");
        $this->_log("Logging enabled in Borgione_Erp");
    }

    protected function _log() {
        $args = func_get_args();
        $formattedMsg = call_user_func_array('sprintf', $args);
        Mage::log($formattedMsg, null, 'ExportWCLI.log', $this->_forceLog);
    }
	
	function replaceAccents($str)
	{
	  $a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ');
	  $b = array('A\'', 'A\'', 'A\'', 'A\'', 'A\'', 'A\'', 'AE', 'C\'', 'E\'', 'E\'', 'E\'', 'E\'', 'I\'', 'I\'', 'I\'', 'I\'', 'D\'', 'N\'', 'O\'', 'O\'', 'O\'', 'O\'', 'O\'', 'O\'', 'U\'', 'U\'', 'U\'', 'U\'', 'Y\'', 's\'', 'a\'', 'a\'', 'a\'', 'a\'', 'a\'', 'a\'', 'ae\'', 'c\'', 'e\'', 'e\'', 'e\'', 'e\'', 'i\'', 'i\'', 'i\'', 'i\'', 'n\'', 'o\'', 'o\'', 'o\'', 'o\'', 'o\'', 'o\'', 'u\'', 'u\'', 'u\'', 'u\'', 'y\'', 'y\'', 'A\'', 'a\'', 'A\'', 'a\'', 'A\'', 'a\'', 'C\'', 'c\'', 'C\'', 'c\'', 'C\'', 'c\'', 'C\'', 'c\'', 'D\'', 'd\'', 'D\'', 'd\'', 'E\'', 'e\'', 'E\'', 'e\'', 'E\'', 'e\'', 'E\'', 'e\'', 'E\'', 'e\'', 'G\'', 'g\'', 'G\'', 'g\'', 'G\'', 'g\'', 'G\'', 'g\'', 'H\'', 'h\'', 'H\'', 'h\'', 'I\'', 'i\'', 'I\'', 'i\'', 'I\'', 'i\'', 'I\'', 'i\'', 'I\'', 'i\'', 'IJ\'', 'ij\'', 'J\'', 'j\'', 'K\'', 'k\'', 'L\'', 'l\'', 'L\'', 'l\'', 'L\'', 'l\'', 'L\'', 'l\'', 'l\'', 'l\'', 'N\'', 'n\'', 'N\'', 'n\'', 'N\'', 'n\'', 'n\'', 'O\'', 'o\'', 'O\'', 'o\'', 'O\'', 'o\'', 'OE\'', 'oe\'', 'R\'', 'r\'', 'R\'', 'r\'', 'R\'', 'r\'', 'S\'', 's\'', 'S\'', 's\'', 'S\'', 's\'', 'S\'', 's\'', 'T\'', 't\'', 'T\'', 't\'', 'T', 't', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O\'', 'o\'', 'U\'', 'u\'', 'A\'', 'a\'', 'I\'', 'i\'', 'O\'', 'o\'', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'U\'', 'u\'', 'A\'', 'a\'', 'AE', 'ae', 'O\'', 'o\'');
	  return str_replace($a, $b, $str);
	}
	
	public function export() {
		
		//ottiene la lista indirizzi da esportare
		$lista_clienti_fatturazione = $this->getListaClientiFatturazioneDaExportare();
		$lista_clienti_fatturazione_non_esportare = array();
		$lista_clienti_spedizione_non_esportare = array();
		$lista_righe_da_esportare = array();
		$this->_log("Prepara Export Clienti Fatturazione: ");

		//echo "\nPrepara Export Clienti Fatturazione: \n";		
		foreach ($lista_clienti_fatturazione as $id_customer) {
			
			//echo "\n:".$id_customer;
			$campi_da_esportare=array();
			$customer = Mage::getModel('customer/customer')->load($id_customer);
			$customer_addresses = $customer->getAddresses();
			foreach ($customer_addresses as $address) {
					//print_r($address->getData());
					$is_default_billing = ($address->getData('is_default_billing') == '1') || (sizeof($customer_addresses==1)) ? true:false;
					if ($is_default_billing) {
							$billing_address = $address;
							break;
					}
			}

			if (!$billing_address) {
				$this->_log('Errore: $billing_address not found: '.$id_customer);
				continue;
			}	
				
			$campi_da_esportare['CLICDCLI']=$customer->getData("as400");
			
			$this->_log("ID CLIENTE Fatturazione: ".$id_customer." , AS400: ".$customer->getData("as400"));
			
			if ($customer->getData("as400")=='' || $customer->getData("as400")=='-' )
			{
				$lista_clienti_fatturazione_non_esportare[] = $id_customer;
				$this->_log("ID CLIENTE Fatturazione: ".$id_customer." , AS400: ".$customer->getData("as400")." rimesso in coda");
				continue;
			}
			
			$nome_t = strtoupper($customer->getData("firstname"));
			$nome_t = strtoupper($this->replaceAccents($nome_t)); 
			
			//$campi_da_esportare['CLIRASCL']=strtoupper($customer->getData("firstname"));
			$campi_da_esportare['CLIRASCL']=$nome_t;

			$nome_t = strtoupper($customer->getData("lastname"));
			$nome_t = strtoupper($this->replaceAccents($nome_t)); 
			
			//$campi_da_esportare['CLIRASC2']=strtoupper($customer->getData("lastname"));			
			$campi_da_esportare['CLIRASC2']=$nome_t;
			
			$indirizzo = $billing_address->getData("street");
		
			$campi_da_esportare['CLIINDCL']=strtoupper($this->replaceAccents($billing_address->getData("street")));					
			$campi_da_esportare['CLILOCCL']=strtoupper($this->replaceAccents($billing_address->getData("city")));
			if ($billing_address->getData("provincia")!='') 
				$campi_da_esportare['CLIPROCL']=strtoupper($billing_address->getData("provincia"));
			else 
				$campi_da_esportare['CLIPROCL']=strtoupper($billing_address->getData("region"));
			
			//$campi_da_esportare['CLIPROCL']=strtoupper($billing_address->getData("provincia"));
			$campi_da_esportare['CLICZONA']=strtoupper($customer->getData("codice_zona"));				
			$campi_da_esportare['CLICAPCL']=strtoupper($billing_address->getData("postcode"));																
			$campi_da_esportare['CLICDFIS']=strtoupper($customer->getData("codice_fiscale"));				
			$campi_da_esportare['CLICPAIV']=strtoupper($customer->getData("taxvat"));
			$campi_da_esportare['CLIPACDM']=strtoupper($customer->getData("codice_ministeriale"));
			$campi_da_esportare['CLIPACEM']=strtoupper($customer->getEmail());
			//$campi_da_esportare['CLINTECL']=strtoupper($customer->getData("taxvat"));						
			$campi_da_esportare['CLINTECL']=strtoupper($billing_address->getData("telephone"));
			$campi_da_esportare['CLICATVE']=strtoupper($customer->getData("categoria_vendita"));	
			$campi_da_esportare['CLICATEG']=$customer->getData("codice_contabile");			
			$campi_da_esportare['CLICLIFA']=$customer->getData("as400");
			$campi_da_esportare['CLICOPAG']=strtoupper($customer->getData("tipo_pagamento"));
			//$campi_da_esportare['CLICOPAG']='E01';	//PER TEST					
			$campi_da_esportare['CLIUSR']=strtoupper($customer->getEmail());
			$campi_da_esportare['CLIANW']=$customer->getData("agree_newsletter")=='1' ? 'S':'N';			
			$campi_da_esportare['CLICDU']=$customer->getData("cod_uni_uff");					
																																																																												
			array_push($lista_righe_da_esportare, $campi_da_esportare);	
							
		}//ciclo for su lista indirizzi
		
		//processa adesso i clienti di spedizione
		
		$this->_log("Prepara Export Clienti Spedizione: ");
		//echo "\nPrepara Export Clienti Spedizione: \n";
		$lista_clienti_spedizione = $this->getListaClientiSpedizioneDaExportare();


		foreach ($lista_clienti_spedizione as $id_indirizzo) {
			$this->_log("ID CLIENTE Spedizione: ".$id_indirizzo);
			$campi_da_esportare=array();
			$address = Mage::getModel('customer/address')->load($id_indirizzo);	
			if (!$address) {
				
				$this->_log("Skipping Address ID : ".$id_indirizzo);	
				continue;		
			}

			if ( ($address->getData("codice_contabile")=='CP') || ($address->getData("codice_contabile")=='RI') ) {
				$this->_log("skipping CP : ".$id_indirizzo);
				continue;
			}
			
			$codice_cliente_fatturazione = $address->getData("codice_cliente_fatturazione");


						
			$data = Mage::getModel('customer/customer')
			              ->getCollection()
			              ->addAttributeToSelect('customer_id')
			              ->addAttributeToFilter('as400',$codice_cliente_fatturazione)->load()->getData();				
			
			$id_customer = $data[0]['entity_id'];
			
			$this->_log("Id customer fatturazione:".$id_customer);
			
			$customer = Mage::getModel('customer/customer')->load($id_customer);

				
			$campi_da_esportare['CLICDCLI']=$address->getData("as400");
			
			$this->_log("Cliente Fatturazione : ".$codice_cliente_fatturazione.", AS400:".$address->getData("as400").", CFatturazione:".$customer->getData("as400"));	

			if ($customer->getData("as400")=='' || $customer->getData("as400")=='-' || $address->getData("as400")=='' || $address->getData("as400")=='-')
			{
				$lista_clienti_spedizione_non_esportare[] = $id_indirizzo;
				$this->_log("ID CLIENTE Spedizione: ".$id_indirizzo." , AS400: ".$customer->getData("as400")." , CFatturazione: ". $$codice_cliente_fatturazione." rimesso in coda");
				continue;
			}

			$nome_t = strtoupper($address->getData("firstname"));
			$nome_t = strtoupper($this->replaceAccents($nome_t)); 
			
			//$campi_da_esportare['CLIRASCL']=strtoupper($address->getData("firstname"));
			$campi_da_esportare['CLIRASCL']=$nome_t;
			
			$nome_t = strtoupper($address->getData("lastname"));
			$nome_t = strtoupper($this->replaceAccents($nome_t)); 
			
			//$campi_da_esportare['CLIRASC2']=strtoupper($address->getData("lastname"));
			$campi_da_esportare['CLIRASC2']=$nome_t;
											
			$campi_da_esportare['CLIINDCL']=strtoupper($this->replaceAccents($address->getData("street")));					
			$campi_da_esportare['CLILOCCL']=strtoupper($this->replaceAccents($address->getData("city")));
			//if ($address->getData("region")!='') 
			//	$campi_da_esportare['CLIPROCL']=strtoupper($address->getData("region"));
			//else
				$campi_da_esportare['CLIPROCL']=strtoupper($address->getData("provincia"));
				
				if ($address->getData("provincia")!='') 
					$campi_da_esportare['CLIPROCL']=strtoupper($address->getData("provincia"));
				else 
					$campi_da_esportare['CLIPROCL']=strtoupper($address->getData("region"));
				
				
			//$campi_da_esportare['CLICZONA']=$address->getData("codice_zona");	 
			$campi_da_esportare['CLICAPCL']=$address->getData("postcode");																
			//$campi_da_esportare['CLICDFIS']=$customer->getData("codice_fiscale");				
			//$campi_da_esportare['CLICPAIV']=$customer->getData("taxvat");
			$campi_da_esportare['CLIPACDM']=$address->getData("codice_ministeriale");
			//$campi_da_esportare['CLIPACEM']=$customer->getEmail();					
			$campi_da_esportare['CLINTECL']=$address->getData("telephone");
			$campi_da_esportare['CLICATVE']=$address->getData("categoria_vendita");	
			$campi_da_esportare['CLICATEG']=$address->getData("codice_contabile");			
			$campi_da_esportare['CLICLIFA']=$customer->getData("as400");
			$campi_da_esportare['CLICOPAG']=strtoupper($customer->getData("tipo_pagamento"));
			//$campi_da_esportare['CLICOPAG']='E01';	//PER TEST		
			//$campi_da_esportare['CLIUSR']=$customer->getEmail();
			//$campi_da_esportare['CLIANW']=$customer->getData("agree_newsletter");			
			$campi_da_esportare['CLICDU']=$address->getData("codice_ministeriale");					
																																																																												
			array_push($lista_righe_da_esportare, $campi_da_esportare);	
							
		}//ciclo for su lista indirizzi		
		$this->_log("Crezione file WCLI.txt");
		//echo "\nCreazione file";
		$this->createFileClienti($lista_righe_da_esportare);
		//Da scommentare in produzione
		$this->cancellaListaClientiFatturazioneDaExportare($lista_clienti_fatturazione, $lista_clienti_fatturazione_non_esportare);
		$this->cancellaListaClientiSpedizioneDaExportare($lista_clienti_spedizione, $lista_clienti_spedizione_non_esportare);		
	}
	
	
	
	private function exportRows($records) {
		echo "\nDump:\n";
		foreach ($records as $key=>$value) {
			echo "\n$key:$value";
		}
		echo "\n";
	}
	
	private function createFileClienti($lista_righe_da_esportare) {
				if ((!$lista_righe_da_esportare) && !(sizeof($lista_righe_da_esportare)>0)) { return; }
		$numero_riga = 1;
		//print_r($lista_righe_da_esportare);
		$fileName = DirUtils::getWCLIExportDir();
		$fileWriter = new WCLIFileWriter($fileName);
		$fileWriter->createFile();		
		
		foreach ($lista_righe_da_esportare as $record) {
				foreach ($record as $key=>$value) {
					$fileWriter->setField($key,$value);
					//echo "\n".$key.":".$value;				
				}
				$fileWriter->writeRecord();
				//print_r($record);	
		}
		$fileWriter->closeFile();
	}	
	private function getListaClientiFatturazioneDaExportare() {
		$con = DBUtil::getConnection();
		$sql ="SELECT * FROM clienti_fatturazione_da_esportare";
		$res = mysql_query($sql);
		$ret = array();
		while ($row = mysql_fetch_object($res)) {
			$ret[] = $row->id_magento;
        }
        DBUtil::closeConnection($con);
		
		return $ret;
	}

	private function getListaClientiSpedizioneDaExportare() {
		$con = DBUtil::getConnection();
		$sql ="SELECT * FROM clienti_spedizione_da_esportare";
		$res = mysql_query($sql);
		$ret = array();
		while ($row = mysql_fetch_object($res)) {
			$ret[] = $row->id_magento;
        }
        DBUtil::closeConnection($con);
		
		return $ret;
	}

	private function cancellaListaClientiFatturazioneDaExportare($lista_clienti, $lista_clienti_fatturazione_non_esportare) {
		$con = DBUtil::getConnection();
		foreach ($lista_clienti as $id_magento) {
			if (in_array($id_magento, $lista_clienti_fatturazione_non_esportare))
				continue;
			$sql ="DELETE FROM clienti_fatturazione_da_esportare WHERE id_magento='$id_magento'";
			$this->_log("SQL DELETE FROM clienti_fatturazione_da_esportare WHERE id_magento='$id_magento'");			
			$res = mysql_query($sql);
		}

        DBUtil::closeConnection($con);
	}

	private function cancellaListaClientiSpedizioneDaExportare($lista_clienti, $lista_clienti_spedizione_non_esportare) {
		$con = DBUtil::getConnection();
		foreach ($lista_clienti as $id_magento) {
			if (in_array($id_magento, $lista_clienti_spedizione_non_esportare))
				continue;
			
			$sql ="DELETE FROM clienti_spedizione_da_esportare WHERE id_magento='$id_magento'";
			$this->_log("SQL DELETE FROM clienti_spedizione_da_esportare WHERE id_magento='$id_magento'");			
			$res = mysql_query($sql);
		}

        DBUtil::closeConnection($con);
	}
	
}

//$windManager = new WCLIManager();
//$windManager->export();


?>