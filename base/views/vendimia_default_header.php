<?php use Vendimia\view, Vendimia\html;

// Si no hay un título, creamos uno por defecto
if (!$this['_vendimia_page_title']) {

    // Ignoramos el controller si es 'default'
    $this['_vendimia_page_title'] = ucfirst (Vendimia::$application);

    if (Vendimia::$controller != 'default') {
        $this['_vendimia_page_title'] .= ' / ' . ucfirst(Vendimia::$controller);
    }
}

// Modificamos el título con un callable, si queremos
if (is_callable( $this['_vendimia_page_title_callable'])) {

    // PHP quirk...
    $page_title = $this['_vendimia_page_title_callable'];
    $this['_vendimia_page_title'] = $page_title();
}

// Charset por defecto
$this->html->addMeta('charset', 'utf-8');

// Token de seguridad
$this->html->addMeta(['vendimia-security-token' => Vendimia::$csrf->getToken()]);

// Un meta de debug, si estamos en debug
if (Vendimia::$debug) {
    $this->html->addMeta(['vendimia-debug' => 'true']);
}

// Assets por defecto
$this->html->addDefaultAssets($this->name);

// Y añadimos el CSS del Vendimia AL INICIO
$this->html->prependCss ('vendimia');

// También un javascript al inicio
$this->html->prependScript ('vendimia');

?>
<!DOCTYPE html>
<html>
<head>
<base href="<?=Vendimia::$base_url?>" />
<title><?=$this['_vendimia_page_title']?></title>
<?=$this->html->drawMeta()?>
<?=$this->html->drawLink()?>
<?=$this->html->drawScripts()?>
</head>
<body>
