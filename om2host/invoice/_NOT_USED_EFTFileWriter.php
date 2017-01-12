<?php
/**
 * Created by PhpStorm.
 * User: vsambucaro
 * Date: 3/17/14
 * Time: 4:29 PM
 */
require_once("AS400FileWriter.php");
require_once("AS400RecordStructure.php");
require_once("AS400Field.php");

class EFTFileWriter extends AS400FileWriter {

    public function __construct($_nomeFile) {
        echo "\nNome File: \n".$_nomeFile;
        $this->nomeFile = $_nomeFile;
        $this->strutturaRecord = new AS400RecordStructure();

        $this->strutturaRecord->addField(new AS400Field("SOCIETA", 1, 4 , 4));
        $this->strutturaRecord->addField(new AS400Field("CENTRO_COSTO", 5, 14 , 10));
        $this->strutturaRecord->addField(new AS400Field("DATA", 15, 22 , 8));
        $this->strutturaRecord->addField(new AS400Field("NUMERO_DOC", 23, 26 , 4));
        $this->strutturaRecord->addField(new AS400Field("RS_COGNOME", 27, 66 , 40));
        $this->strutturaRecord->addField(new AS400Field("RS_NOME", 67, 86 , 20));
        $this->strutturaRecord->addField(new AS400Field("INDIRIZZO", 87, 146 , 60));
        $this->strutturaRecord->addField(new AS400Field("CAP", 147, 151 , 5));
        $this->strutturaRecord->addField(new AS400Field("LOCALITA", 152, 186 , 35));
        $this->strutturaRecord->addField(new AS400Field("PROV", 187, 188 , 2));
        $this->strutturaRecord->addField(new AS400Field("NAZIONE", 189, 223 , 35));
        $this->strutturaRecord->addField(new AS400Field("PIVA", 224, 237 , 14));
        $this->strutturaRecord->addField(new AS400Field("CF", 238, 253 , 16));
        $this->strutturaRecord->addField(new AS400Field("TIPO_DOC", 254, 255 , 2));
        $this->strutturaRecord->addField(new AS400Field("TIPO_FATTURA", 256, 256 , 1));
        $this->strutturaRecord->addField(new AS400Field("DIVISA", 257, 259 , 3));
        $this->strutturaRecord->addField(new AS400Field("NUMERO_ALIQUOTE", 260, 261 , 2));

        $this->strutturaRecord->addField(new AS400Field("CODICE_IVA1", 262, 264 , 3));
        $this->strutturaRecord->addField(new AS400Field("IMPONIBILE1", 265, 277 , 13));
        $this->strutturaRecord->addField(new AS400Field("ALIQUOTA_IVA1", 278, 282 , 5));
        $this->strutturaRecord->addField(new AS400Field("IMPOSTA1", 283, 295 , 13));

        $this->strutturaRecord->addField(new AS400Field("CODICE_IVA2", 296, 298 , 3));
        $this->strutturaRecord->addField(new AS400Field("IMPONIBILE2", 299, 311 , 13));
        $this->strutturaRecord->addField(new AS400Field("ALIQUOTA_IVA2", 312, 316 , 5));
        $this->strutturaRecord->addField(new AS400Field("IMPOSTA2", 317, 329 , 13));

        $this->strutturaRecord->addField(new AS400Field("CODICE_IVA3", 330, 332 , 3));
        $this->strutturaRecord->addField(new AS400Field("IMPONIBILE3", 333, 345 , 13));
        $this->strutturaRecord->addField(new AS400Field("ALIQUOTA_IVA3", 346, 350 , 5));
        $this->strutturaRecord->addField(new AS400Field("IMPOSTA3", 351, 363 , 13));

        $this->strutturaRecord->addField(new AS400Field("CODICE_IVA4", 364, 366 , 3));
        $this->strutturaRecord->addField(new AS400Field("IMPONIBILE4", 367, 379 , 13));
        $this->strutturaRecord->addField(new AS400Field("ALIQUOTA_IVA4", 380, 384 , 5));
        $this->strutturaRecord->addField(new AS400Field("IMPOSTA4", 385, 397 , 13));

        $this->strutturaRecord->addField(new AS400Field("CODICE_IVA5", 398, 400 , 3));
        $this->strutturaRecord->addField(new AS400Field("IMPONIBILE5", 401, 413 , 13));
        $this->strutturaRecord->addField(new AS400Field("ALIQUOTA_IVA5", 414, 418 , 5));
        $this->strutturaRecord->addField(new AS400Field("IMPOSTA5", 419, 431 , 13));

        $this->strutturaRecord->addField(new AS400Field("CASSA1", 432, 435 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE1", 436, 439 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE_FISCALE1", 440, 443 , 4));

        $this->strutturaRecord->addField(new AS400Field("CASSA2", 444, 447 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE2", 448, 451 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE_FISCALE2", 452, 455 , 4));

        $this->strutturaRecord->addField(new AS400Field("CASSA3", 456, 459 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE3", 460, 463 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE_FISCALE3", 464, 467 , 4));

        $this->strutturaRecord->addField(new AS400Field("CASSA4", 468, 471 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE4", 472, 475 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE_FISCALE4", 476, 479 , 4));

        $this->strutturaRecord->addField(new AS400Field("CASSA5", 480, 483 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE5", 484, 487 , 4));
        $this->strutturaRecord->addField(new AS400Field("NUM_TRANSAZIONE_FISCALE5", 488, 491 , 4));

        $this->strutturaRecord->addField(new AS400Field("TIPO_PERSONA", 492, 492 , 1));
        $this->strutturaRecord->addField(new AS400Field("NAZIONE_NASCITA", 493, 527 , 35));
        $this->strutturaRecord->addField(new AS400Field("DATA_NASCITA", 528, 535 , 8));
        $this->strutturaRecord->addField(new AS400Field("CODICE_NAZIONE_ESTERA_ISO", 536, 538 , 3));
        $this->strutturaRecord->addField(new AS400Field("CODICE_NAZIONE_ESTERA_UNICO", 539, 541 , 3));
        $this->strutturaRecord->addField(new AS400Field("CODICE_NAZIONE_ESTERA_ISO_NASCITA", 542, 544 , 3));
        $this->strutturaRecord->addField(new AS400Field("CODICE_NAZIONE_ESTERA_UNICO_NASCITA", 545, 547 , 3));

        $this->setRecordSize($this->strutturaRecord->getRecordLength());


    }


} 