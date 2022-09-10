<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/classi/utenti.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina = new Pagina('Utente', PAGINA_RISERVATA_ADMIN);
$_pagina->javascript[] = '/js/password-generator-api.js';

$utente = null;
$utenteSelezionato = $_REQUEST['id'] ?? 0;

$azione = 'crea';

if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione'] == 'modifica') {
        $azione='modifica';
    }
}

if ($utenteSelezionato) {
    $utente = new Utente($utenteSelezionato);
    if (!$utente->getId()) {
        $azione = 'crea';
    } else {
        $utente->loadAssociatedData();
    }
} else {
    $utente = new Utente();
}

if ($utente->getId() and $azione != 'modifica') {
    $azione = 'visualizza';
}

$titleDefault = '';
if ($utente->getFullName()) {
    $titleDefault = htmlspecialchars($utente->getFullName());
}

if ($azione=='modifica' or $azione=='crea') {
    $form = new Campi(FORM_ADMIN_GESTIONEUTENTI);
    $form->campi['username']=new Campo('username', 'Nome utente', TIPO_STRINGA, array('obbligatorio'=>true, 'autocomplete' => 'new-password'));
    $form->campi['password']=new Campo('password', 'Password', TIPO_STRINGA, array('obbligatorio'=>($azione=='crea' ? true : false), 'autocomplete' => 'new-password', 'aiuto' => '<a href="#" onclick="generateNewPasswordAPI(\'#password\')">Genera nuova password</a> - <a href="#" onclick="toggleShowHidePasswordField(\'#password\')">Mostra/nascondi password</a>', 'default' => ''));
    $form->campi['email']=new Campo('email', 'Email', TIPO_EMAIL, array('obbligatorio'=>false));
    $form->campi['livello']=new Campo('livello', 'Livello permessi', TIPO_INTERO, array('valori'=>Config::$userLevels, 'obbligatorio'=>true));
    $form->campi['nome']=new Campo('nome', 'Nome', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['cognome']=new Campo('cognome', 'Cognome', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_BOOLEANO, array('obbligatorio'=>true));
    $form->campi['timesheet_abilitato']=new Campo('timesheet_abilitato', 'Timesheet abilitato', TIPO_BOOLEANO, array('obbligatorio'=>true));
    $form->campi['costo']=new Campo('costo', 'Costo orario', TIPO_STRINGA, array('obbligatorio'=>true));
}

if (isset($_POST['crea'])) {
    if (!$id = $_database->generaNuovoIDTabella($utente->getDbTable())) {
        $_pagina->messaggi[] = new MessaggioErrore("Errore nella creazione dell'utente. ID non settato.");
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[] = new MessaggioErrore("Errore nella creazione dell'utente.");
        } else {
            $messaggioErroreUsername='';
            $messaggioErrorePassword='';

            $passwordNuovoUtente = $form->valoreAttuale('password');

            if ($passwordNuovoUtente) {
                $passwordNuovoUtenteCriptata = cryptString($passwordNuovoUtente, '', true);
            }

            if ($passwordNuovoUtente) {
                if (!$messaggioErrorePassword=controllaRequisitiPassword($passwordNuovoUtente)) {

                    $form->valoriDB['password_hash']=pg_escape_literal($passwordNuovoUtenteCriptata['hash']);
                    $form->valoriDB['password_salt']=pg_escape_literal($passwordNuovoUtenteCriptata['salt']);
                    if (isset($form->valoriDB['password'])) {
                        unset($form->valoriDB['password']);
                    }
                    $nuovoUsername=$form->valoreAttuale('username');
                    if ($messaggioErroreUsername = Utenti::controllaSeUsernameEsiste($nuovoUsername)) {
                        $_pagina->messaggi[]=new MessaggioErrore($messaggioErroreUsername);
                        $form->err['username']=$messaggioErroreUsername;
                    } else {
                        if (!$utente->crea($form->valoriDB)) {
                            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione dell\'utente.');
                        } else {
                            $_pagina->messaggi[]=new MessaggioConferma('Utente creato');
                            $utente = new Utente($id);
                            $_pagina->title='Utente ' . htmlspecialchars($utente->getUsername());
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
} elseif (isset($_POST['salva'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[] = new MessaggioErrore("Errore nell'aggiornamento dell'utente.");
    } else {
        $messaggioErroreUsername='';
        $messaggioErrorePassword='';

        $nuovaPassword = $form->valoreAttuale('password');

        if ($nuovaPassword) {
            $passwordUgualeAllaPrecedente = verificaHashConSalt($nuovaPassword, $utente->getPasswordHash(), $utente->getPasswordSalt());
            if ($passwordUgualeAllaPrecedente) {
                $nuovaPassword = '';
            }
        }

        if ($nuovaPassword) {
            if (!$messaggioErrorePassword=controllaRequisitiPassword($nuovaPassword)) {
                $nuovaPasswordCriptata=cryptString($nuovaPassword, '', true);
                $form->valoriDB['password_hash']=pg_escape_literal($nuovaPasswordCriptata['hash']);
                $form->valoriDB['password_salt']=pg_escape_literal($nuovaPasswordCriptata['salt']);
            } else {
                $_pagina->messaggi[]=new MessaggioErrore($messaggioErrorePassword);
            }
        }
        if (isset($form->valoriDB['password'])) {
            unset($form->valoriDB['password']);
        }
        if ($messaggioErrorePassword or $messaggioErroreUsername or !$utente->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento dell\'utente.');
        } else {
            $_pagina->messaggi[]=new MessaggioConferma('Utente aggiornato');
            $utente=new Utente($utente->getId());
            $_pagina->title = 'Utente ' . htmlspecialchars($utente->getUsername());
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$utente->elimina()) {
        $_pagina->messaggi[] = new MessaggioErrore("Errore nell'eliminazione dell'utente.");
    } else {
        $_pagina->messaggi[] = new MessaggioConferma('Utente eliminato.');
        $utente = new Utente();
        $azione = 'crea';
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-progetto">
<?php
if ($azione=='visualizza'):
?>
<h2>Utente: <?=htmlspecialchars($utente->getFullName())?></h2>
<div id="visualizzazione-dati" class="clearfix">
    <div class="col-l-4">
        <h4>Username</h4>
        <p><?=htmlspecialchars($utente->getUsername())?></p>
    </div>
    <div class="col-l-4">
        <h4>Email</h4>
        <p><a href="mailto:<?=htmlspecialchars($utente->getEmail())?>"><?=htmlspecialchars($utente->getEmail())?></a></p>
    </div>
    <div class="col-l-4">
        <h4>Livello</h4>
        <p><?=Config::$userLevels[$utente->getLevel()]?></p>
    </div>
    <div class="col-l-4">
        <h4>Nome</h4>
        <p><?=htmlspecialchars($utente->getFirstName())?></p>
    </div>
    <div class="col-l-4">
        <h4>Cognome</h4>
        <p><?=htmlspecialchars($utente->getLastName())?></p>
    </div>
    <div class="col-l-4">
        <h4>Costo orario</h4>
        <p><?=($utente->getCost() ? '&euro; ' . $utente->getCost() . '\h' : '')?></p>
    </div>
    <div class="col-l-4">
        <h4>Data creazione</h4>
        <p><?=$utente->getDateCreated()?></p>
    </div>
    <div class="col-l-4">
        <h4>Data ultima modifica</h4>
        <p><?=$utente->getDateModified()?></p>
    </div>
    <div class="col-l-4">
        <h4>Attivo</h4>
        <p><?=($utente->isActive() ? 'Si' : 'No')?></p>
    </div>
    <div class="col-l-4">
        <h4>Timesheet abilitato</h4>
        <p><?=($utente->isTimesheetEnabled() ? 'Si' : 'No')?></p>
    </div>
</div>
<a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$utente->getId()?>&amp;azione=modifica">Modifica utente</a>
<!-- <a class="btn btn-report" href="progetto-report.php?id=<?=$utente->getId()?>">Report progetto</a> -->
<?php
endif;

if ($azione=='modifica' or $azione=='crea'): ?>
<h2><?=($azione=='modifica' ? 'Modifica del utente ' . htmlspecialchars($utente->getFullName()) : 'Creazione nuovo utente')?></h2>
<?php
$parametriAction=[];
$parametriAction['id'] = $utente->getId();
$parametriAction['azione'] = $azione;
$action = creaHttpQueryDaParametri($parametriAction);
?>
<form method="post" action="<?=$action?>" class="form-standard">
    <fieldset>
        <legend>Informazioni utente</legend>
        <div class="clearfix">
            <?php
            $form->creaCampoDivCustom('username', CAMPO_INPUTTEXT, 'col-m-4', $utente->getRecord());
            $form->creaCampoDivCustom('password', CAMPO_INPUTPASSWORD, 'col-m-4', null);
            $form->creaCampoDivCustom('email', CAMPO_INPUTTEXT, 'col-m-4', $utente->getRecord());
            ?>
        </div>
        <div class="clearfix">
            <?php
            $form->creaCampoDivCustom('livello', CAMPO_SELECT, 'col-m-4', $utente->getRecord());
            $form->creaCampoDivCustom('nome', CAMPO_INPUTTEXT, 'col-m-4', $utente->getRecord());
            $form->creaCampoDivCustom('cognome', CAMPO_INPUTTEXT, 'col-m-4', $utente->getRecord());
            $form->creaCampoDivCustom('costo', CAMPO_INPUTTEXT, 'col-m-4', $utente->getRecord());
            $form->creaCampoDivCustom('attivo', CAMPO_RADIOSINO, 'col-m-4', $utente->getRecord(), 't', 'f');
            $form->creaCampoDivCustom('timesheet_abilitato', CAMPO_RADIOSINO, 'col-m-4', $utente->getRecord(), 't', 'f');
            ?>
        </div>
    </fieldset>
    <input type="hidden" name="formID" value="<?=$form->formID?>" />
    <div class="campi-bottoni">
    <a href="<?=$_SERVER['PHP_SELF'].($utente->getId() ? '?id=' . $utente->getId() : '')?>" class="btn btn-annulla">Annulla modifiche</a>
    <input type="submit" name="<?=$utente->getId() ? 'salva' : 'crea'?>" value="Salva" class="btn btn-save" />
    <?php if ($utente->getId()): ?>
    <input type="submit" name="elimina" value="Elimina" class="btn btn-elimina" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo progetto?');" />
    <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo utente</a>
    </div>
    <?php endif; ?>
</form>
<?php endif;?>
</section>
<?php
$_pagina->creaFooter();
