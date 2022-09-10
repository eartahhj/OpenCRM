<?php
require_once('../funzioni/funzioni.php');
require_once('../funzioni/controlli.php');
require_once('../funzioni/classi/filtri.php');
require_once('../funzioni/classi/utenti.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Gestione Utenti', PAGINA_RISERVATA_ADMIN);

$_utenti=new Utenti();
$_utenti->ottieniRisultatiFiltrati();
$_utenti->ottieniListaOpzioni();

foreach ($_utenti->lista as $username=>$utente) {
    if ($_utente->livello<$utente->livello) {
        unset($_utenti->lista[$username]);
    }
}

$permessiUtente=$_config['permessiUtente'];
foreach ($permessiUtente as $livello=>$tipoPermesso) {
    if ($_utente->livello<$livello) {
        unset($permessiUtente[$livello]);
    }
}

$azione='';
$record=null;
$utenteDaModificare='';

if (isset($_GET['azione'])) {
    $azione=$_GET['azione'];
}
if (isset($_GET['id']) and (int)$_GET['id']) {
    $utenteDaModificare=$_utenti->listaOpzioni[(int)$_GET['id']];
    if(!$utenteDaModificare) {
        $_pagina->messaggi[]=new MessaggioErrore('Utente non trovato.');
        $_pagina->creaTesta();
        $_pagina->creaFooter();
        exit();
    } else {
        $utenteDaModificare=new Utente($utenteDaModificare);
        if (!$utenteDaModificare->getId() or $utenteDaModificare->getLevel() > $_utente->getLevel()) {
            $_pagina->messaggi[]=new MessaggioErrore('Utente non trovato.');
            $_pagina->creaTesta();
            $_pagina->creaFooter();
            exit();
        }
    }
    if ($azione!='modifica') {
        $azione='visualizza';
        $_pagina->title='Gestione utente: '.$utenteDaModificare->getUsername();
    } else {
        $_pagina->title='Modifica utente: '.$utenteDaModificare->getUsername();
    }
}

if ($azione=='modifica' or $azione=='crea') {
    $form=new Campi(FORM_ADMIN_GESTIONEUTENTI);
    $form->campi['username']=new Campo('username', 'Nome utente', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['password']=new Campo('password', 'Password', TIPO_STRINGA, array('obbligatorio'=>($azione=='crea'?true:false)));
    $form->campi['email']=new Campo('email', 'Email', TIPO_EMAIL, array('obbligatorio'=>true));
    $form->campi['livello']=new Campo('livello', 'Livello permessi', TIPO_INTERO, array('valori'=>$permessiUtente, 'obbligatorio'=>true));
    $form->campi['nome']=new Campo('nome', 'Nome', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['cognome']=new Campo('cognome', 'Cognome', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_BOOLEANO, array('obbligatorio'=>true));
    $form->campi['timesheet_abilitato']=new Campo('timesheet_abilitato', 'Timesheet abilitato', TIPO_BOOLEANO, array('obbligatorio'=>true));
}

if (isset($_POST['crea']) and $_POST['formID']==$form->formID) {
    $id=$_database->generaNuovoIDTabella($_utenti->getDbTable());
    if (!$id) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione dell\'utente. ID non settato.');
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione dell\'utente.');
        } else {
            $messaggioErroreUsername='';
            $messaggioErrorePassword='';
            if ($nuovaPassword=$form->valoreAttuale('password')) {
                if (!$messaggioErrorePassword=controllaRequisitiPassword($nuovaPassword)) {
                    $nuovaPasswordCriptata=cryptString($nuovaPassword, '', true);
                    $form->valoriDB['password_hash']=pg_escape_literal($nuovaPasswordCriptata['hash']);
                    $form->valoriDB['password_salt']=pg_escape_literal($nuovaPasswordCriptata['salt']);
                    $form->valoriDB['data_modifica']="'now'";
                    if (isset($form->valoriDB['password'])) {
                        unset($form->valoriDB['password']);
                    }
                    $nuovoUsername=$form->valoreAttuale('username');
                    if ($messaggioErroreUsername=$_utenti->controllaSeUsernameEsiste($nuovoUsername)) {
                        $_pagina->messaggi[]=new MessaggioErrore($messaggioErroreUsername);
                        $form->err['username']=$messaggioErroreUsername;
                    } else {
                        if (!$_utenti->crea($form->valoriDB)) {
                            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione dell\'utente.');
                        } else {
                            $_pagina->messaggi[]=new MessaggioConferma('Utente creato');
                            $utenteDaModificare=new Utente($form->valoreAttuale('username'));
                            $_pagina->title='Utente '.$utenteDaModificare->getUsername();
                            $azione='modifica';
                        }
                    }
                } else {
                    $_pagina->messaggi[]=new MessaggioErrore($messaggioErrorePassword);
                    $form->err['password']=$messaggioErrorePassword;
                }
            }
        }
    }
} elseif (isset($_POST['salva']) and $_POST['formID']==$form->formID) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento dell\'utente.');
    } else {
        $messaggioErroreUsername='';
        $messaggioErrorePassword='';
        if ($nuovaPassword=$form->valoreAttuale('password')) {
            if (!$messaggioErrorePassword=controllaRequisitiPassword($nuovaPassword)) {
                $nuovaPasswordCriptata=cryptString($nuovaPassword, '', true);
                $form->valoriDB['password_hash']=pg_escape_literal($nuovaPasswordCriptata['hash']);
                $form->valoriDB['password_salt']=pg_escape_literal($nuovaPasswordCriptata['salt']);
            } else {
                $_pagina->messaggi[]=new MessaggioErrore($messaggioErrorePassword);
            }
        }
        $form->valoriDB['data_modifica']="'now'";
        if (isset($form->valoriDB['password'])) {
            unset($form->valoriDB['password']);
        }
        if ($messaggioErrorePassword or $messaggioErroreUsername or !$utenteDaModificare->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento dell\'utente.');
        } else {
            $_pagina->messaggi[]=new MessaggioConferma('Utente aggiornato');
            $utenteDaModificare=new Utente($utenteDaModificare->getUsername());
            $_pagina->title='Utente '.$utenteDaModificare->getUsername();
        }
    }
} elseif (isset($_POST['elimina']) and $_POST['formID']==$form->formID) {
    if ($utenteDaModificare->elimina()) {
        $_pagina->messaggi[]=new MessaggioConferma('Utente eliminato.');
        $utenteDaModificare='';
        $azione='crea';
    } else {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione dell\'utente.');
    }
}

if (!$azione) {
    $form=new Campi(FORM_FILTRA_UTENTI);
    $form->campi['username']=new Campo('username', 'Username', TIPO_STRINGA);
    $form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));
    $form->campi['attivo']=new Campo('attivo', 'Utente attivo', TIPO_INTERO, array('valori'=>['t'=>'Si','f'=>'No']));

    if (isset($_GET['username']) and $_GET['username']) {
        $form->campi['username']->default=$_REQUEST['nome'];
    }
    if (isset($_REQUEST['risultatiPerPagina']) and $_REQUEST['risultatiPerPagina']) {
        $form->campi['risultatiPerPagina']->default=(int)$_REQUEST['risultatiPerPagina'];
    }
    if (isset($_GET['attivo']) and $_GET['stato']!=='') {
        $form->campi['attivo']->default=$_GET['attivo'];
    }
}

$_pagina->creaTesta();
?>
<div class="container">
<?php if ($azione=='visualizza'): ?>
<h2>Utente: <?=$utenteDaModificare->getUsername()?></h2>
<div id="visualizzazione-dati" class="clearfix">
 <?=$utenteDaModificare?>
</div>
<a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$utenteDaModificare->getId()?>&amp;azione=modifica">Modifica utente</a>
<?php
elseif ($azione=='modifica' or $azione=='crea'):
    if ($azione=='modifica' and $utenteDaModificare) {
        $parametriAction=[];
        $parametriAction['id']=$utenteDaModificare->getId();
        $parametriAction['azione']=$azione;
        $action=creaHttpQueryDaParametri($parametriAction);
    }
    ?>
    <h2><?=($azione=='modifica'?'Modifica utente '.$utenteDaModificare->getUsername():'Creazione nuovo utente')?></h2>
    <form method="post" action="<?=$action?>" class="form-standard">
    <?php
    $form->creaCampoDIV('username', CAMPO_INPUTTEXT, $utenteDaModificare->record);
    $form->creaCampoDIV('password', CAMPO_INPUTPASSWORD);
    $form->creaCampoDIV('email', CAMPO_INPUTTEXT, $utenteDaModificare->record);
    $form->creaCampoDIV('livello', CAMPO_SELECT, $utenteDaModificare->record);
    $form->creaCampoDIV('nome', CAMPO_INPUTTEXT, $utenteDaModificare->record);
    $form->creaCampoDIV('cognome', CAMPO_INPUTTEXT, $utenteDaModificare->record);
    $form->creaCampoDIV('attivo', CAMPO_RADIOSINO, $utenteDaModificare->record, 't', 'f');
    $form->creaCampoDIV('timesheet_abilitato', CAMPO_RADIOSINO, $utenteDaModificare->record, 't', 'f');
    ?>
    <input type="hidden" name="formID" value="<?=$form->formID?>" />
     <div class="campi-bottoni">
      <a href="<?=$_SERVER['PHP_SELF'].($utenteDaModificare->getId()?'?id='.$utenteDaModificare->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
      <input type="submit" name="<?=$utenteDaModificare->getId()?'salva':'crea'?>" value="Salva" class="btn btn-invia" />
      <?php if ($utenteDaModificare->getId()): ?>
      <input type="submit" name="elimina" value="Elimina" class="btn btn-elimina" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo utente?');" />
      <a href="<?=$_SERVER['PHP_SELF']?>?azione=crea" class="btn btn-nuovo">Nuovo utente</a>
     <?php endif; ?>
    </div>
    </form>
    <?php else:?>
        <form action="<?=$_SERVER['PHP_SELF']?>" method="get" class="container form-inline">
            <h2>Filtra utenti per:</h2>
            <?php
            $form->creaCampoDiv('username', CAMPO_INPUTTEXT);
            $form->creaCampoDiv('attivo', CAMPO_SELECT, null, 'Selezionare');
            $form->creaCampoDiv('risultatiPerPagina', CAMPO_SELECT);
            ?>
            <input type="hidden" value="<?=$form->formID?>" name="formID" />
            <div class="campi-bottoni">
                <input type="submit" name="filtra" value="Cerca" class="btn btn-invia" />
                <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
                <a href="<?=$_SERVER['PHP_SELF']?>?azione=crea" class="btn btn-nuovo">Nuovo utente</a>
            </div>
        </form>
    <?php
    echo $_utenti;
endif;
?>
</div>
<?php
$_pagina->creaFooter();
