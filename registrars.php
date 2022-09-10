<?php
require_once('funzioni/funzioni.php');
require_once('funzioni/controlli.php');
require_once('funzioni/classi/clienti.php');
require_once('funzioni/classi/registrar.php');
require_once('funzioni/classi/domini.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina=new Pagina('Elenco Registrars');

$_registrars=new Registrars;
$_registrars->ottieniRisultatiFiltrati();

if(count($_registrars)==0) {
 $_pagina->creaTesta();
 echo 'Nessun registrar trovato';
 $_pagina->creaFooter();
 exit();
}
 $form=new Campi(FORM_FILTRA_REGISTRAR);
 $form->campi['name']=new Campo('name','Nome Registrar',TIPO_STRINGA);
 $form->campi['rpp']=new Campo('rpp','Risultati per pagina',TIPO_INTERO,array('valori'=>$_config['risultatiPerPaginaAmmessi']));
 if(isset($_REQUEST['name'])) $form->campi['name']->default=$_REQUEST['name'];
 if(isset($_REQUEST['rpp'])) $form->campi['rpp']->default=$_REQUEST['rpp'];

 $_pagina->creaTesta();
 ?>
 <div class="container">
 <form action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
  <h2>Filtra registrar per:</h2>
  <?php
  $form->creaCampoDiv('name',CAMPO_INPUTTEXT);
  $form->creaCampoDiv('rpp',CAMPO_SELECT);
  ?>
  <input type="hidden" value="<?=$form->formID?>" name="formID" />
  <div class="campi-bottoni">
   <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
   <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
   <a href="registrar.php" class="btn btn-nuovo">Nuovo registrar</a>
  </div>
 </form>
</div>
 <?php
 echo $_registrars->buildHtml();

$_pagina->creaFooter();
