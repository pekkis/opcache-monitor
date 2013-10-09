<?php

require_once __DIR__ . '/../bootstrap.php';

echo $twig->render(
    'dashboard.html.twig',
    array(
        'functions' => $monitor->getFunctions(),
        'graphs' => $monitor->getGraphs(),
        'general' => $monitor->getGeneral(),
        'configuration' => $monitor->getConfiguration(),
        'uptime' => $monitor->getUptime(),
        'status' => $monitor->getStatus(),
    )
);

die();

/*

if ( !empty($_GET['RECHECK']) ) {
    if ( function_exists(CACHEPREFIX.'invalidate') ) {
        $recheck=trim($_GET['RECHECK']); $files=call_user_func(CACHEPREFIX.'get_status');
        if (!empty($files['scripts'])) {
            foreach ($files['scripts'] as $file=>$value) {
                if ( $recheck==='1' || strpos($file,$recheck)===0 )  call_user_func(CACHEPREFIX.'invalidate',$file);
            }
        }
        header( 'Location: '.str_replace('?'.$_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']) );
    } else { echo 'Sorry, this feature requires Zend Opcache newer than April 8th 2013'; }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>OCP - Opcache Control Panel</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap.min.css">
    <!-- Optional theme -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.0.0/css/bootstrap-theme.min.css">
    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>

    <style type="text/css">



        h1 {font-size: 150%;}
        h2 {font-size: 125%;}
        img {float: right; border: 0px;}
        hr {width: 600px; background-color: #cccccc; border: 0px; height: 1px; color: #000;}
        .meta, .small {font-size: 75%; }
        .meta {margin: 2em 0;}
        .meta a, th a {padding: 10px; white-space:nowrap; }
        .buttons {margin:0 0 1em;}
        .buttons a {margin:0 15px; background-color: #9999cc; color:#fff; text-decoration:none; padding:1px; border:1px solid #000; display:inline-block; width:5em; text-align:center;}
        #files td.v a {font-weight:bold; color:#9999cc; margin:0 10px 0 5px; text-decoration:none; font-size:120%;}
        #files td.v a:hover {font-weight:bold; color:#ee0000;}
        .graph {display:inline-block; width:145px; margin:1em 0 1em 1px; border:0; vertical-align:top;}
        .graph table {width:100%; height:150px; border:0; padding:0; margin:5px 0 0 0; position:relative;}
        .graph td {vertical-align:middle; border:0; padding:0 0 0 5px;}
        .graph .bar {width:25px; text-align:right; padding:0 2px; color:#fff;}
        .graph .total {width:34px; text-align:center; padding:0 5px 0 0;}
        .graph .total div {border:1px dashed #888; border-right:0; height:99%; width:12px; position:absolute; bottom:0; left:17px; z-index:-1;}
        .graph .total span {background:#fff; font-weight:bold;}
        .graph .actual {text-align:right; font-weight:bold; padding:0 5px 0 0;}
        .graph .red {background:#ee0000;}
        .graph .green {background:#00cc00;}
        .graph .brown {background:#8B4513;}
    </style>
</head>

<body>

    <div class="container">



<h1><a href="?">Opcache Control Panel</a></h1>

<div class="buttons">
    <a href="?ALL=1">Details</a>
    <a href="?FILES=1&GROUP=2&SORT=3">Files</a>
    <a href="?RESET=1" onclick="return confirm('RESET cache ?')">Reset</a>
    <?php if ( function_exists(CACHEPREFIX.'invalidate') ) { ?>
        <a href="?RECHECK=1" onclick="return confirm('Recheck all files in the cache ?')">Recheck</a>
    <?php } ?>
    <a href="?" onclick="window.location.reload(true); return false">Refresh</a>
</div>



function meta_display() {
?>
<div class="meta">
    <a href="http://files.zend.com/help/Zend-Server-6/content/zendoptimizerplus.html">directives guide</a> |
    <a href="http://files.zend.com/help/Zend-Server-6/content/zend_optimizer+_-_php_api.htm">functions guide</a> |
    <a href="https://wiki.php.net/rfc/optimizerplus">wiki.php.net</a> |
    <a href="http://pecl.php.net/package/ZendOpcache">pecl</a> |
    <a href="https://github.com/zend-dev/ZendOptimizerPlus/">Zend source</a> |
    <a href="https://gist.github.com/ck-on/4959032/?ocp.php">OCP latest</a>
</div>
<?php
}
*/
