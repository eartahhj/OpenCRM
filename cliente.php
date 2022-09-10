<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/clienti.php');
require_once('funzioni/classi/registrar.php');
require_once('funzioni/classi/domini.php');
require_once('funzioni/classi/contatti.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$azione='crea';
if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione']=='modifica') {
        $azione='modifica';
    }
}

$_cliente=new AnagraficaCliente();

if (isset($_REQUEST['id']) and $_REQUEST['id']) {
    $_cliente->ottieniDaId((int)$_REQUEST['id']);
    $_cliente->associaDomini();
    $_cliente->associaContatti();
    $_cliente->associaCaselleEmail();
}

if ($_cliente->getId() and $azione!='modifica') {
    $azione='visualizza';
}

if ($azione=='modifica' or $azione=='crea') {
    $form=new Campi(FORM_MODIFICA_CLIENTE);
    $form->campi['nome']=new Campo('nome', 'Nome cliente', TIPO_STRINGA, array('obbligatorio'=>true));
    $form->campi['telex']=new Campo('telex', 'Email', TIPO_STRINGA, array());
    $form->campi['pec']=new Campo('pec', 'Email PEC', TIPO_STRINGA, array());
    $form->campi['marketing_email']=new Campo('marketing_email', 'Email scopi promozionali', TIPO_STRINGA, array());
    $form->campi['marketing_note']=new Campo('marketing_note', 'Note di contatto marketing (Nome, numero di tel.)', TIPO_STRINGA, array());
    $form->campi['piva']=new Campo('piva', 'Partita IVA', TIPO_STRINGA, array());
    $form->campi['telefono']=new Campo('telefono', 'Telefono', TIPO_STRINGA, array());
    $form->campi['fax']=new Campo('fax', 'Fax', TIPO_STRINGA, array());
    $form->campi['indirizzo']=new Campo('indirizzo', 'Indirizzo', TIPO_STRINGA, array());
    $form->campi['localita']=new Campo('localita', 'Località', TIPO_STRINGA, array());
    $form->campi['provincia']=new Campo('provincia', 'Provincia', TIPO_STRINGA, array());
    $form->campi['cap']=new Campo('cap', 'CAP', TIPO_STRINGA, array());
    $form->campi['nomeresp']=new Campo('nomeresp', 'Nome e Cognome referente domini', TIPO_STRINGA, array());
    $form->campi['cfresp']=new Campo('cfresp', 'Codice Fiscale referente domini', TIPO_STRINGA, array());
    $form->campi['aggiornamento']=new Campo('aggiornamento', 'Ultimo aggiornamento', TIPO_STRINGA, array());
    $form->campi['attivo']=new Campo('attivo', 'Attivo', TIPO_BOOLEANO);
}

$_pagina=new Pagina('Cliente '.$_cliente->nomeAzienda);

if (isset($_POST['crea'])) {
    if (!$id=$_database->generaNuovoIDTabella($_cliente->getDbTable())) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del cliente. ID non settato.');
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del cliente.');
        } else {
            $form->valoriDB['codconto']="'C $id'";
            $form->valoriDB['aggiornamento']="'now'";
            if (!$_cliente->crea($form->valoriDB)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del cliente.');
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('Cliente creato');
                $_cliente=new AnagraficaCliente($id);
            }
        }
    }
} elseif (isset($_POST['salva'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del cliente.');
    } else {
        $form->valoriDB['aggiornamento']="'now'";
        if (!$_cliente->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del cliente.');
        } else {
            $_cliente=new AnagraficaCliente($_cliente->getId());
            $_cliente->associaDomini();
            $_cliente->associaContatti();
            $_cliente->associaCaselleEmail();
            $_pagina->title='Cliente '.$_cliente->nomeAzienda;
            $_pagina->messaggi[]=new MessaggioConferma('Cliente aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$_cliente->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione del cliente.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Cliente eliminato.');
        $_cliente=new AnagraficaCliente();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-cliente">
  <div class="sezione sezione-cliente">
  <?php if ($azione=='visualizza'):?>
   <h2>Cliente: <?=$_cliente->nomeAzienda?> <span class="small">(<?=$_cliente->codconto?>)</span> <a class="icona icona-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$_cliente->getId()?>&amp;azione=modifica"><img src='/img/svg/edit.svg' /></a></h2>
   <div id="visualizzazione-dati" class="row">
    <?=$_cliente->buildHtml()?>
   </div>
  <?php elseif ($azione=='modifica' or $azione=='crea'): ?>
   <h2><?=($azione=='modifica'?'Modifica del cliente '.$_cliente->nomeAzienda:'Creazione nuovo cliente')?></h2>
   <?php
   $parametriAction=[];
   $parametriAction['id']=$_cliente->getId();
   $parametriAction['azione']=$azione;
   $action=creaHttpQueryDaParametri($parametriAction);
   ?>
   <form method="post" action="<?=$action?>" class="form-standard">
   <?php
   $form->creaCampoDIV('nome', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('telex', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('pec', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('marketing_email', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('marketing_note', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('piva', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('telefono', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('fax', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('indirizzo', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('localita', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('provincia', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('cap', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('nomeresp', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('cfresp', CAMPO_INPUTTEXT, $_cliente->getRecord());
   $form->creaCampoDIV('attivo', CAMPO_RADIOSINO, $_cliente->getRecord());
   ?>
   <input type="hidden" name="formID" value="<?=$form->formID?>" />
    <div class="campi-bottoni">
     <a href="<?=$_SERVER['PHP_SELF'].($_cliente->getId()?'?id='.$_cliente->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
     <input type="submit" name="<?=$_cliente->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
     <?php if ($_cliente->getId()): ?>
     <input type="submit" name="elimina" value="Elimina" class="btn btn-delete" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo cliente?');" />
     <a href="<?=$_SERVER['PHP_SELF']?>?azione=crea" class="btn btn-nuovo">Nuovo cliente</a>
    <?php endif;?>
   </div>
  </form>
 <?php endif;?>
 </div>
 <div id="lista-domini-associati" class="lista-risultati">
     <?php
      if (empty($_cliente->domini)) {
          echo "<h4>Nessun dominio associato a questo cliente</h4>\n";
      } else {
          echo '<h4>'.count($_cliente->domini).' domini'.(count($_cliente->domini)==1?'o associato':' associati')." a $_cliente->nomeAzienda:</h4>\n";
          ?>
          <table class="table">
              <thead>
                  <tr>
                      <th>Dominio</th>
                      <th>Attivo</th>
                      <th>Registrar</th>
                  </tr>
              </thead>
              <tbody>
              <?php foreach ($_cliente->domini as $dominio): ?>
                  <?php $dominio->registrar = new Registrar($dominio->registrar->getId()); ?>
                  <tr class="<?=$dominio->isActive() ? 'active' : 'inactive'?>">
                      <td><a href="dominio.php?id=<?=$dominio->getId()?>"><?=$dominio->nome?></a></td>
                      <td><?=$dominio->isActive() ? 'Si' : 'No'?></td>
                      <td><a href="registrar.php?id=<?=$dominio->registrar->getId()?>"><?=$dominio->registrar->nome?></a></td>
                  </tr>
              <?php endforeach; ?>
            </tbody>
        </table>
        <?php
      }
        if ($_cliente->getId()):?>
        <p><a href="dominio.php?cliente=<?=$_cliente->getId()?>" class="btn btn-nuovo">Nuovo dominio</a></p>
        <?php endif;?>
    </div>
    <div id="lista-caselle-associate" class="lista-risultati">
        <?php
        if (empty($_cliente->caselleEmailAssociate->lista)) {
            echo "<h4>Nessuna casella email associata a questo cliente</h4>\n";
        } else {
            echo '<h4>'.count($_cliente->caselleEmailAssociate->lista).' casell'.(count($_cliente->caselleEmailAssociate->lista)==1?'a associata':'e associate'). " a $_cliente->nomeAzienda:\n</h4>";
            echo $_cliente->caselleEmailAssociate->buildHtmlAssociatedResults();
        }
        if ($_cliente->getId()):?>
        <p><a href="casella-email.php?cliente=<?=$_cliente->getId()?>" class="btn btn-nuovo">Nuova casella email</a></p>
        <?php endif;?>
    </div>
    <div id="lista-contatti-associati">
    <?php
    if (empty($_cliente->contatti->lista)) {
        echo "<h4>Nessun contatto associato a questo cliente</h4>\n";
    } else {
        echo '<h4>'.count($_cliente->contatti->lista).' contatt'.(count($_cliente->contatti->lista)==1?'o associato':'i associati'). " a $_cliente->nomeAzienda:\n</h4>";
        echo $_cliente->contatti->buildHtmlAssociatedResults();
    }
    if ($_cliente->getId()):?>
    <p><a href="contatto.php?cliente=<?=$_cliente->getId()?>" class="btn btn-nuovo">Nuovo contatto</a></p>
    <?php endif;?>
    </div>
</section>
<?php
$_pagina->creaFooter();
