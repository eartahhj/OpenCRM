<?php
require_once('funzioni/funzioni.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

if(isset($_GET['abilita']) and $_GET['abilita']=='1') {
    setcookie('debug', MODALITA_DEBUG_VALORE, time() + 6286400, '/', '', true, true);
    $msg='Debug abilitato';
}

if(isset($_GET['disabilita']) and $_GET['disabilita']=='1') {
    setcookie('debug', '', time() - 1, '/', '', true, true);
    $msg='Debug disabilitato';
 }

$_pagina=new Pagina('Modalità Debug',PAGINA_RISERVATA_ADMIN);
$_pagina->creaTesta();
?>

<div class="container">
    <?php if ($msg):?>
        <p><?=$msg?></p>
    <?php
    endif;

    if (isset($_COOKIE['debug']) and $_COOKIE['debug'] == MODALITA_DEBUG_VALORE):
    ?>
    <p><a href="<?=$_SERVER['PHP_SELF']?>?disabilita=1">Disabilita debug</a></p>
    <?php
    else:
    ?>
    <p><a href="<?=$_SERVER['PHP_SELF']?>?abilita=1">Abilita debug</a></p>
    <?php
    endif;
    ?>
</div>
<?php
$_pagina->creaFooter();
