<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/registrar.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$azione='crea';
if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione']=='modifica') {
        $azione='modifica';
    }
}

if (isset($_REQUEST['id']) and $_REQUEST['id']) {
    $_registrar=new Registrar((int)$_REQUEST['id']);
} else {
    $_registrar=new Registrar();
}

if ($_registrar->getId() and $azione!='modifica') {
    $azione='visualizza';
}

if ($azione=='modifica' or $azione=='crea') {
    $form=new Campi(FORM_MODIFICA_REGISTRAR);
    $form->campi['name']=new Campo('name', 'Nome registrar', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['aggiornamento']=new Campo('aggiornamento', 'Ultimo aggiornamento', TIPO_STRINGA, array());
}

$_pagina=new Pagina('Registrar '.$_registrar->nome);

if (isset($_POST['crea'])) {
    if (!$id=$_database->generaNuovoIDTabella($_registrar->getDbTable())) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del registrar. ID non settato.');
    } else {
        if(!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del registrar.');
        } else {
            $form->valoriDB['aggiornamento']="'now'";
            if (!$_registrar->crea($form->valoriDB)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del registrar.');
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('Registrar creato');
                $_registrar=new Registrar($id);
            }
        }
    }
} elseif (isset($_POST['salva'])) {
    if(!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del registrar.');
    } else {
        $form->valoriDB['aggiornamento']="'now'";
        if (!$_registrar->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del registrar.');
        } else {
            $_registrar=new Registrar($_registrar->getId());
            $_pagina->title='Registrar '.$_registrar->nome;
            $_pagina->messaggi[]=new MessaggioConferma('Registrar aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_registrar->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione del registrar.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Registrar eliminato.');
        $_registrar=new Registrar();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-registrar">
 <?php if ($azione=='visualizza'):?>
    <h2>Registrar: <?=$_registrar->nome?></h2>
    <a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$_registrar->getId()?>&amp;azione=modifica">Modifica registrar</a>
    <div id="visualizzazione-dati" class="clearfix">
    <?=$_registrar?>
   </div>
   <?php elseif ($azione=='modifica' or $azione=='crea'): ?>
   <h2><?=($azione=='modifica'?'Modifica del registrar '.$_registrar->nome:'Creazione nuovo registrar')?></h2>
   <?php
   $parametriAction=[];
   $parametriAction['id']=$_registrar->getId();
   $parametriAction['azione']=$azione;
   $action=creaHttpQueryDaParametri($parametriAction);
   ?>
   <form method="post" action="<?=$action?>">
     <?php
     $form->creaCampoDIV('name', CAMPO_INPUTTEXT, $_registrar->getRecord());
     ?>
     <input type="hidden" name="formID" value="<?=$form->formID?>" />
     <div class="campi-bottoni">
       <a href="<?=$_SERVER['PHP_SELF'].($_registrar->getId()?'?id='.$_registrar->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
       <input type="submit" name="<?=$_registrar->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
    <?php if ($_registrar->getId()): ?>
       <input type="submit" name="elimina" value="Elimina" class="btn btn-delete" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo registrar?');" />
       <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo registrar</a>
    <?php endif; ?>
   </div>
   </form>
   <?php endif; ?>
</section>
<?php
$_pagina->creaFooter();
