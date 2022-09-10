<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/registrar.php');
require_once('funzioni/classi/contatti.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('CSV');

$formImportazione=new Campi('xa67v4e');
$formImportazione->campi['import-csv']=new Campo('import-csv', 'File CSV', TIPO_FILE, array());

class DocumentoCSV
{
    private $fileFolderRelative='file/';
    private $fileFolderAbsolute='';
    private $importFolder='imports/';
    private $fileName='';
    private $modalitaApertura='w+';
    private $fileHandler=null;
    private $headerCSV=[];
    private $contenutoCSV=[];
    private $chosenOption='';
    protected $fileToExport='';
    protected $fileToImport='';

    public function __construct()
    {
        global $_config;
        $this->fileFolderAbsolute=__DIR__.'/'.$this->fileFolderRelative;
        $this->fileFolderRelative=$_config['cPath'].'/'.$this->fileFolderRelative;
    }

    private function getFileHandler(string $filePath='')
    {
        global $_pagina;
        if($filePath) {
            $this->fileHandler=fopen($filePath, $this->modalitaApertura);
        } else {
            $this->fileHandler=fopen($this->fileFolderAbsolute.$this->fileName, $this->modalitaApertura);
        }
        if(!$this->fileHandler) {
            $_pagina->messaggi[]=new MessaggioErrore("Errore nell'apertura del file.");
            $_pagina->messaggi[]=new MessaggioDebug("Errore nell'apertura del file: Hai verificato i permessi?");
            return null;
        }
        return $this->fileHandler;
    }

    private function generaCSVClientiAnagrafica() : bool
    {
        global $_config;
        require_once('funzioni/classi/clienti.php');
        $this->fileName='clienti-anagrafica.csv';
        $this->getFileHandler();
        $this->headerCSV=['Nome cliente','Partita IVA','Indirizzo','Telefono','Fax','Email','Email Marketing','PEC','Stato'];
        fputcsv($this->fileHandler, $this->headerCSV);
        $_clienti=new Clienti();
        $_clienti->ottieniRecordTutti();
        foreach ($_clienti->lista as $cliente) {
            $this->contenutoCSV['nome']=$cliente->nomeAzienda;
            $this->contenutoCSV['piva']=$cliente->piva;
            $this->contenutoCSV['indirizzo']=$cliente->indirizzo;
            $this->contenutoCSV['telefono']=$cliente->telefono;
            $this->contenutoCSV['fax']=$cliente->fax;
            $this->contenutoCSV['email']=$cliente->email;
            $this->contenutoCSV['marketing_email']=$cliente->marketing_email;
            $this->contenutoCSV['pec']=$cliente->pec;
            $this->contenutoCSV['stato']=$_config['stato'][$cliente->statoGenerale];
            fputcsv($this->fileHandler, $this->contenutoCSV);
        }
        fclose($this->fileHandler);
        return true;
    }

    private function generaCSVClientiEmail() : bool
    {
        require_once('funzioni/classi/clienti.php');
        $this->fileName='clienti-email.csv';
        $this->getFileHandler();
        $this->headerCSV=['Nome cliente','Email','Email Marketing','PEC'];
        fputcsv($this->fileHandler, $this->headerCSV);
        $_clienti=new Clienti();
        $_clienti->ottieniRecordTutti();
        foreach ($_clienti->lista as $cliente) {
            $this->contenutoCSV['nome']=$cliente->nomeAzienda;
            $this->contenutoCSV['email']=$cliente->email;
            $this->contenutoCSV['marketing_email']=$cliente->marketing_email;
            $this->contenutoCSV['pec']=$cliente->pec;
            fputcsv($this->fileHandler, $this->contenutoCSV);
        }
        fclose($this->fileHandler);
        return true;
    }

    private function generaCSVCaselleEmailNonAssociateACliente() : bool
    {
        require_once('funzioni/classi/caselle-email.php');
        $this->fileName='caselle-email-non-associate.csv';
        $this->getFileHandler();
        $this->headerCSV=['Email','Attivo','Alias','Provider'];
        fputcsv($this->fileHandler, $this->headerCSV);
        $_caselle=new CaselleEmail();
        $_caselle->setWhere('cliente=0 OR cliente IS NULL');
        $_caselle->ottieniRecordTutti();
        foreach ($_caselle->lista as $casella) {
            $this->contenutoCSV['email']=$casella->email;
            $this->contenutoCSV['attivo']=($casella->attivo?'Si':'No');
            $this->contenutoCSV['alias']=$casella->aliasOf;
            $this->contenutoCSV['provider']=$casella->provider->nome;
            fputcsv($this->fileHandler, $this->contenutoCSV);
        }
        fclose($this->fileHandler);
        return true;
    }

    private function generaCSVFornitoriAnagrafica() : bool
    {
        global $_config;
        require_once('funzioni/classi/fornitori.php');
        $this->fileName='fornitori-anagrafica.csv';
        $this->getFileHandler();
        $this->headerCSV=['Nome fornitore','Partita IVA','Indirizzo','Telefono','Fax','Email','Email Marketing','PEC','Stato'];
        fputcsv($this->fileHandler, $this->headerCSV);
        $_fornitori=new Fornitori();
        $_fornitori->ottieniRecordTutti();
        foreach ($_fornitori->lista as $fornitore) {
            $this->contenutoCSV['nome']=$fornitore->nomeAzienda;
            $this->contenutoCSV['piva']=$fornitore->piva;
            $this->contenutoCSV['indirizzo']=$fornitore->indirizzo;
            $this->contenutoCSV['telefono']=$fornitore->telefono;
            $this->contenutoCSV['fax']=$fornitore->fax;
            $this->contenutoCSV['email']=$fornitore->email;
            $this->contenutoCSV['marketing_email']=$fornitore->marketing_email;
            $this->contenutoCSV['pec']=$fornitore->pec;
            $this->contenutoCSV['stato']=$_config['stato'][$fornitore->statoGenerale];
            fputcsv($this->fileHandler, $this->contenutoCSV);
        }
        fclose($this->fileHandler);
        return true;
    }

    private function setFileForImport(): void
    {
        if (isset($_GET['csv']) and $_GET['csv']) {
            $csv=$_GET['csv'];
        }

        return;
    }

    private function doExportByChosenOption() : bool
    {
        if ($this->chosenOption === 'clienti-anagrafica') {
            if($this->generaCSVClientiAnagrafica()) {
                return true;
            }
        }
        if ($this->chosenOption === 'fornitori-anagrafica') {
            if($this->generaCSVFornitoriAnagrafica()) {
                return true;
            }
        }
        if ($this->chosenOption === 'clienti-email') {
            if($this->generaCSVClientiEmail()) {
                return true;
            }
        }
        if ($this->chosenOption === 'caselle-email-non-associate') {
            if($this->generaCSVCaselleEmailNonAssociateACliente()) {
                return true;
            }
        }
        return false;
    }

    private function doImportByChosenOption() : bool
    {
        switch($this->chosenOption) {
            case 'caselle':
                return $this->importCaselle();
                break;
            case 'caselle-tutte':
                return $this->importCaselleDaZero();
            default:
                break;
        }
        return false;
    }

    private function importCaselle() : bool
    {
        global $_pagina;
        $caselleDaVerificare=[];
        $dominiDaVerificare=[];
        $i=0;

        while($csvRow=fgetcsv($this->fileHandler, 1000)) {
            $i++;
            $caselleDaVerificare[$i] = trim($csvRow[0]);
            $dominioCasella=explode('@', $csvRow[0])[1];
            if(!$key=array_search($dominioCasella, $dominiDaVerificare)) {
                $dominiDaVerificare[]=trim($dominioCasella);
            }
        }

        if($dominiDaVerificare) {
            $_pagina->messaggi[]=new MessaggioInfo('Verifico anche i domini presenti nel Database...');
            require_once 'funzioni/classi/domini.php';
            $_domini=new Domini();

            if($domainsNotFound = $_domini->returnNotFoundRecordsFromArray($dominiDaVerificare, $_domini->colonnaValorePerListaOpzioni)) {
                foreach($domainsNotFound as $domain)  {
                    $_pagina->messaggi[] = new MessaggioInfo('Record non presente in ' . Domini::$classNameReadable.': ' . htmlspecialchars($domain) . ' - Sto inserendo il record...');

                    if(!$_domini->insertRecordFromArray(['name' => mb_strtolower($domain)])) {
                        return false;
                    }
                }
            } else {
                $_pagina->messaggi[]=new MessaggioInfo('Nessun nuovo dominio da importare.');
            }
        }

        if($caselleDaVerificare) {
            $_pagina->messaggi[]=new MessaggioInfo('Ho letto tutte le caselle dal CSV. Eseguo riscontro con il Database...');
            require_once 'funzioni/classi/caselle-email.php';
            $_caselle=new CaselleEmail();
            if($emailsNotFound = $_caselle->returnNotFoundRecordsFromArray($caselleDaVerificare, $_caselle->colonnaValorePerListaOpzioni)) {
                foreach($emailsNotFound as $email)  {
                    $_pagina->messaggi[] = new MessaggioInfo('Record non presente in ' . CaselleEmail::$classNameReadable.': ' . htmlspecialchars($email) . ' - Sto inserendo il record...');

                    if(!$_caselle->insertRecordFromArray(['email' => mb_strtolower($email), 'provider' => CaselleEmail::EMAIL_PROVIDER, 'attivo' => 't'])) {
                        return false;
                    }
                }
            } else {
                $_pagina->messaggi[]=new MessaggioInfo('Nessuna nuova casella da importare.');
            }
        }

        return true;
    }

    private function importCaselleDaZero() : bool
    {
        global $_pagina;
        $caselleNelCsv = [];
        $dominiDaVerificare=[];
        $i=0;
        $caselleMancantiDalCsv = [];

        while($csvRow=fgetcsv($this->fileHandler, 1000)) {
            $i++;
            $caselleNelCsv[$i] = trim(mb_strtolower($csvRow[0]));
            $dominioCasella = explode('@', $csvRow[0])[1];

            if(!$key = array_search($dominioCasella, $dominiDaVerificare)) {
                $dominiDaVerificare[] = trim($dominioCasella);
            }
        }

        if($caselleNelCsv) {
            $_pagina->messaggi[]=new MessaggioInfo('Ho letto tutte le caselle dal CSV. Eseguo riscontro con il Database...');
            require_once 'funzioni/classi/caselle-email.php';

            $caselleDaRimuovere = new CaselleEmail();
            $caselleDaRimuovere->setWhere('provider = 5 and attivo');
            $caselleDaRimuovere->ottieniListaOpzioni();

            if ($caselleDaRimuovere->listaOpzioni) {
                $caselleDaRimuovereDalDb = array_diff($caselleDaRimuovere->listaOpzioni, $caselleNelCsv);
            }

            $_caselleDaAggiungere = new CaselleEmail();
            $_caselleDaAggiungere->setWhere('provider = 5');
            $_caselleDaAggiungere->ottieniListaOpzioni();

            if ($_caselleDaAggiungere->listaOpzioni) {
                $caselleDaAggiungereNelDb = array_diff($caselleNelCsv, $_caselleDaAggiungere->listaOpzioni);
            }
        }

        if ($caselleDaRimuovereDalDb) {
            $_pagina->messaggi[] = new MessaggioErrore('Attenzione: nel DB sono presenti le seguenti caselle, che non sono invece presenti in questo CSV');
            foreach ($caselleDaRimuovereDalDb as $id => $casella) {
                $_pagina->messaggi[] = new MessaggioInfo('Rimuovere dal DB: <strong><a href="casella-email.php?id=' . $id . '&amp;azione=modifica">' . htmlspecialchars($casella) . '</a></strong>');
            }
        }

        if ($caselleDaAggiungereNelDb) {
            $_pagina->messaggi[] = new MessaggioErrore('Attenzione: le seguenti caselle sono state trovate nel CSV, ma mancano nel DB');
            foreach ($caselleDaAggiungereNelDb as $casella) {
                $_pagina->messaggi[] = new MessaggioInfo('Aggiungere nel DB: <a href="casella-email.php?email=' . htmlspecialchars($casella) . '&amp;provider=5">' . htmlspecialchars($casella) . '</a></strong>');
            }
        }

        if($dominiDaVerificare) {
            $_pagina->messaggi[]=new MessaggioInfo('Verifico anche i domini presenti nel Database...');
            require_once 'funzioni/classi/domini.php';
            $_domini=new Domini();
            
            if($dominiNonTrovati = $_domini->returnNotFoundRecordsFromArray($dominiDaVerificare, $_domini->colonnaValorePerListaOpzioni)) {
                $_pagina->messaggi[] = new MessaggioErrore('I seguenti domini non sono stati trovati nel DB, verificare se sono da creare');
                foreach ($dominiNonTrovati as $dominio) {
                    $_pagina->messaggi[] = new MessaggioInfo('Da aggiungere <strong><a href="dominio.php?name=' . htmlspecialchars($dominio) . '">' . htmlspecialchars($dominio) . '</a></strong>');
                }
            }

        }

        return true;
    }

    public function getFileToImport() : string
    {
        return $this->fileToImport;
    }

    public function getFileToExport() : string
    {
        return $this->fileToExport;
    }

    public function setChosenOption(string $option): void
    {
        $this->chosenOption=$option;
        return;
    }

    public function getChosenOption() : string
    {
        return $this->chosenOption;
    }

    public function exportCSV() : bool
    {
        global $_pagina,$_config;
        $linkDownload='';
        $generatedCSV='';
        if(!$this->chosenOption) {
            if (isset($_GET['csv']) and $_GET['csv']) {
                $this->chosenOption=$_GET['csv'];
            }
        }
        $this->doExportByChosenOption();
        if (!$this->fileHandler) {
            $_pagina->messaggi[]=new MessaggioErrore('Impossibile creare file CSV');
            return false;
        } else {
            $this->fileToExport=$this->fileFolderRelative.$this->fileName;
            return true;
        }
        return false;
    }

    public function importCSV() : bool
    {
        global $_pagina;
        if (isset($_GET['csv']) and $_GET['csv']) {
            $this->chosenOption=$_GET['csv'];
        }

        if(isset($_FILES['import-csv']) and $_FILES['import-csv']['name']) {
            $fileExtension=returnFileExtension($_FILES['import-csv']['name']);
            if (!isFileExtensionAllowed($fileExtension, ['csv'])) {
                $_pagina->messaggi[]=new MessaggioErrore('Tipo di file non ammesso. È possibile importare solo CSV');
                return false;
            }
            if(!is_uploaded_file($_FILES['import-csv']['tmp_name'])) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nel caricamento del file');
                return false;
            }
            $this->setFileForImport();
            $filePath=$this->fileFolderAbsolute.$this->importFolder.$this->fileName;
            if(!move_uploaded_file($_FILES['import-csv']['tmp_name'], $filePath)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nel caricamento del file');
                $_pagina->messaggi[]=new MessaggioDebug('Errore nel caricamento del file. Hai verificato i permessi?');
                return false;
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('CSV Importato con successo.');
                $_pagina->messaggi[]=new MessaggioInfo('Lettura delle caselle in corso...');
                $this->modalitaApertura='r';
                $this->getFileHandler($filePath);
                $this->doImportByChosenOption();
                fclose($this->fileHandler);
                $_pagina->messaggi[]=new MessaggioConferma('Operazione completata.');
                return true;
            }
        }
        $_pagina->messaggi[]=new MessaggioErrore("Si sono verificati degli errori durante l'importazione del CSV.");
        return false;
    }
}

$fileCSV=new DocumentoCSV;

if(isset($_GET['csv']) and $_GET['csv']) {
    $fileCSV->setChosenOption($_GET['csv']);
    if(isset($_POST['import'])) {
        $fileCSV->importCSV();
    }
    elseif(isset($_GET['operation']) and $_GET['operation']=='export') {
        $fileCSV->exportCSV();
        $linkDownload=$fileCSV->getFileToExport();
    }
}

$_pagina->creaTesta();
?>
<div class="container">
<?php if ($linkDownload):?>
<div class="conferma">
    <p><a href="<?=$linkDownload?>">Scarica il file CSV richiesto</a></p>
</div>
<?php endif;?>
 <h4>Per generare un CSV potrebbero volerci diversi secondi</h4>
 <ul>
     <li><a href="<?=$_SERVER['PHP_SELF']?>?csv=clienti-anagrafica&amp;operation=export">Genera file CSV Clienti - Anagrafica Completa</a></li>
     <li><a href="<?=$_SERVER['PHP_SELF']?>?csv=clienti-email&amp;operation=export">Genera file CSV Clienti - Email</a></li>
     <li><a href="<?=$_SERVER['PHP_SELF']?>?csv=fornitori-anagrafica&amp;operation=export">Genera file CSV Fornitori - Anagrafica Completa</a></li>
     <li><a href="<?=$_SERVER['PHP_SELF']?>?csv=caselle-email-non-associate&amp;operation=export">Genera file CSV Caselle email non associate a clienti</a></li>
 </ul>
 <h4>Importazione CSV</h4>
 <ul>
     <li><a href="<?=$_SERVER['PHP_SELF']?>?csv=caselle">Importa CSV Caselle</a></li>
     <li><a href="<?=$_SERVER['PHP_SELF']?>?csv=caselle-tutte">Controlla corrispondenza database sulle caselle da elenco CSV completo</a></li>
 </ul>

<?php if($fileCSV->getChosenOption() == 'caselle'):?>
<form method="post" action="<?=$_SERVER['PHP_SELF'].($fileCSV->getChosenOption()?'?csv='.$fileCSV->getChosenOption():'')?>" enctype="multipart/form-data" id="<?=$formImportazione->formID?>">
    <?php
    $formImportazione->creaCampoDIV('import-csv', CAMPO_FILE);
    ?>
    <input type="hidden" name="formID" value="<?=$formImportazione->formID?>" />
    <input type="submit" name="import" value="Importa CSV" />
</form>
<?php endif?>

<?php if($fileCSV->getChosenOption() == 'caselle-tutte'):?>
<form method="post" action="<?=$_SERVER['PHP_SELF'].($fileCSV->getChosenOption()?'?csv='.$fileCSV->getChosenOption():'')?>" enctype="multipart/form-data" id="<?=$formImportazione->formID?>">
    <p>Attenzione: caricare il file <strong>CSV</strong> con l'elenco di <strong>TUTTE le caselle</strong></p>
    <?php
    $formImportazione->creaCampoDIV('import-csv', CAMPO_FILE);
    ?>
    <input type="hidden" name="formID" value="<?=$formImportazione->formID?>" />
    <input type="submit" name="import" value="Importa CSV" />
</form>
<?php endif?>

</div>
<?php
$_pagina->creaFooter();
