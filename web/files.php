<?php

require_once __DIR__ . '/../bootstrap.php';

$group = isset($_GET['group']) ? $_GET['group'] : 0;

echo $twig->render(
    'files.html.twig',
    array(
        'files' => $monitor->getFiles($group),
    )
);

die();

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

<?php

if ( !function_exists(CACHEPREFIX.'get_status') ) { echo '<h2>Opcache not detected?</h2>'; die; }

if ( !empty($_GET['FILES']) ) { echo '<h2>files cached</h2>'; files_display(); echo '</div></body></html>'; exit; }

if ( !(isset($_REQUEST['GRAPHS']) && !$_REQUEST['GRAPHS']) && CACHEPREFIX=='opcache_') { graphs_display(); if ( !empty($_REQUEST['GRAPHS']) ) { exit; } }

if ( function_exists(CACHEPREFIX.'get_configuration') ) { echo '<h2>general</h2>'; $configuration=call_user_func(CACHEPREFIX.'get_configuration'); }

print_table($version);



if ( function_exists(CACHEPREFIX.'get_status') && $status=call_user_func(CACHEPREFIX.'get_status') ) {

    if ( !empty($status['cache_full']) ) { $status['memory_usage']['cache_full']=$status['cache_full']; }

    echo '<h2 id="memory">memory</h2>';
    print_table($status['memory_usage']);
    unset($status['opcache_statistics']['start_time'],$status['opcache_statistics']['last_restart_time']);
    echo '<h2 id="statistics">statistics</h2>';
    print_table($status['opcache_statistics']);
}

if ( empty($_GET['ALL']) ) { meta_display(); exit; }

if ( !empty($configuration['blacklist']) ) { echo '<h2 id="blacklist">blacklist</h2>'; print_table($configuration['blacklist']); }

if ( !empty($opcache[3]) ) { echo '<h2 id="runtime">runtime</h2>'; echo $opcache[3]; }





$level=trim(CACHEPREFIX,'_').'.optimization_level';
if (isset($configuration['directives'][$level])) {
    echo '<h2 id="optimization">optimization levels</h2>';
    $levelset=strrev(base_convert($configuration['directives'][$level], 10, 2));
    $levels=array(
        1=>'<a href="http://wikipedia.org/wiki/Common_subexpression_elimination">Constants subexpressions elimination</a> (CSE) true, false, null, etc.<br />Optimize series of ADD_STRING / ADD_CHAR<br />Convert CAST(IS_BOOL,x) into BOOL(x)<br />Convert <a href="http://www.php.net/manual/internals2.opcodes.init-fcall-by-name.php">INIT_FCALL_BY_NAME</a> + <a href="http://www.php.net/manual/internals2.opcodes.do-fcall-by-name.php">DO_FCALL_BY_NAME</a> into <a href="http://www.php.net/manual/internals2.opcodes.do-fcall.php">DO_FCALL</a>',
        2=>'Convert constant operands to expected types<br />Convert conditional <a href="http://php.net/manual/internals2.opcodes.jmp.php">JMP</a>  with constant operands<br />Optimize static <a href="http://php.net/manual/internals2.opcodes.brk.php">BRK</a> and <a href="<a href="http://php.net/manual/internals2.opcodes.cont.php">CONT</a>',
        3=>'Convert $a = $a + expr into $a += expr<br />Convert $a++ into ++$a<br />Optimize series of <a href="http://php.net/manual/internals2.opcodes.jmp.php">JMP</a>',
        4=>'PRINT and ECHO optimization (<a href="https://github.com/zend-dev/ZendOptimizerPlus/issues/73">defunct</a>)',
        5=>'Block Optimization - most expensive pass<br />Performs many different optimization patterns based on <a href="http://wikipedia.org/wiki/Control_flow_graph">control flow graph</a> (CFG)',
        9=>'Optimize <a href="http://wikipedia.org/wiki/Register_allocation">register allocation</a> (allows re-usage of temporary variables)',
        10=>'Remove NOPs'
    );
    echo '<table width="600" border="0" cellpadding="3"><tbody><tr class="h"><th>Pass</th><th>Description</th></tr>';
    foreach ($levels as $pass=>$description) {
        $disabled=substr($levelset,$pass-1,1)!=='1' || $pass==4 ? ' white':'';
        echo '<tr><td class="v center middle'.$disabled.'">'.$pass.'</td><td class="v'.$disabled.'">'.$description.'</td></tr>';
    }
    echo '</table>';
}

if ( isset($_GET['DUMP']) ) {
    if ($name) { echo '<h2 id="ini">ini</h2>'; print_table(ini_get_all($name,true)); }
    foreach ($configuration as $key=>$value) { echo '<h2>',$key,'</h2>'; print_table($configuration[$key]); }
    exit;
}

meta_display();

echo '</div></body></html>';

exit;


function print_table($array,$headers=false) {
    if ( empty($array) || !is_array($array) ) {return;}
    echo '<table class="table table-striped">';
    if (!empty($headers)) {
        if (!is_array($headers)) {$headers=array_keys(reset($array));}
        echo '<tr class="h">';
        foreach ($headers as $value) { echo '<th>',$value,'</th>'; }
        echo '</tr>';
    }
    foreach ($array as $key=>$value) {
        echo '<tr>';
        if ( !is_numeric($key) ) {
            $key=ucwords(str_replace('_',' ',$key));
            echo '<td class="e">',$key,'</td>';
            if ( is_numeric($value) ) {
                if ( $value>1048576) { $value=round($value/1048576,1).'M'; }
                elseif ( is_float($value) ) { $value=round($value,1); }
            }
        }
        if ( is_array($value) ) {
            foreach ($value as $column) {
                echo '<td class="v">',$column,'</td>';
            }
            echo '</tr>';
        }
        else { echo '<td class="v">',$value,'</td></tr>'; }
    }
    echo '</table>';
}

function files_display() {
    $status=call_user_func(CACHEPREFIX.'get_status');
    if ( empty($status['scripts']) ) {return;}
    if ( isset($_GET['DUMP']) ) { print_table($status['scripts']); exit;}
    $time=time(); $sort=0;
    $nogroup=preg_replace('/\&?GROUP\=[\-0-9]+/','',$_SERVER['REQUEST_URI']);
    $nosort=preg_replace('/\&?SORT\=[\-0-9]+/','',$_SERVER['REQUEST_URI']);
    $group=empty($_GET['GROUP'])?0:intval($_GET['GROUP']); if ( $group<0 || $group>9) { $group=1;}
    $groupset=array_fill(0,9,''); $groupset[$group]=' class="b" ';

    echo '<div class="meta">
		<a ',$groupset[0],'href="',$nogroup,'">ungroup</a> |
		<a ',$groupset[1],'href="',$nogroup,'&GROUP=1">1</a> |
		<a ',$groupset[2],'href="',$nogroup,'&GROUP=2">2</a> |
		<a ',$groupset[3],'href="',$nogroup,'&GROUP=3">3</a> |
		<a ',$groupset[4],'href="',$nogroup,'&GROUP=4">4</a> |
		<a ',$groupset[5],'href="',$nogroup,'&GROUP=5">5</a>
	</div>';

    if ( !$group ) { $files =& $status['scripts']; }
    else {
        $files=array();
        foreach ($status['scripts'] as $data) {
            if ( preg_match('@^[/]([^/]+[/]){'.$group.'}@',$data['full_path'],$path) ) {
                if ( empty($files[$path[0]])) { $files[$path[0]]=array('full_path'=>'','files'=>0,'hits'=>0,'memory_consumption'=>0,'last_used_timestamp'=>'','timestamp'=>''); }
                $files[$path[0]]['full_path']=$path[0];
                $files[$path[0]]['files']++;
                $files[$path[0]]['memory_consumption']+=$data['memory_consumption'];
                $files[$path[0]]['hits']+=$data['hits'];
                if ( $data['last_used_timestamp']>$files[$path[0]]['last_used_timestamp']) {$files[$path[0]]['last_used_timestamp']=$data['last_used_timestamp'];}
                if ( $data['timestamp']>$files[$path[0]]['timestamp']) {$files[$path[0]]['timestamp']=$data['timestamp'];}
            }
        }
    }

    if ( !empty($_GET['SORT']) ) {
        $keys=array(
            'full_path'=>SORT_STRING,
            'files'=>SORT_NUMERIC,
            'memory_consumption'=>SORT_NUMERIC,
            'hits'=>SORT_NUMERIC,
            'last_used_timestamp'=>SORT_NUMERIC,
            'timestamp'=>SORT_NUMERIC
        );
        $titles=array('','path',$group?'files':'','size','hits','last used','created');
        $offsets=array_keys($keys);
        $key=intval($_GET['SORT']);
        $direction=$key>0?1:-1;
        $key=abs($key)-1;
        $key=isset($offsets[$key])&&!($key==1&&empty($group))?$offsets[$key]:reset($offsets);
        $sort=array_search($key,$offsets)+1;
        $sortflip=range(0,7); $sortflip[$sort]=-$direction*$sort;
        if ( $keys[$key]==SORT_STRING) {$direction=-$direction; }
        $arrow=array_fill(0,7,''); $arrow[$sort]=$direction>0?' &#x25BC;':' &#x25B2;';
        $direction=$direction>0?SORT_DESC:SORT_ASC;
        $column=array(); foreach ($files as $data) { $column[]=$data[$key]; }
        array_multisort($column, $keys[$key], $direction, $files);
    }

    echo '<table border="0" cellpadding="3" width="960" id="files">
         		<tr class="h">';
    foreach ($titles as $column=>$title) {
        if ($title) echo '<th><a href="',$nosort,'&SORT=',$sortflip[$column],'">',$title,$arrow[$column],'</a></th>';
    }
    echo '	</tr>';
    foreach ($files as $data) {
        echo '<tr>
    				<td class="v" nowrap><a title="recheck" href="?RECHECK=',rawurlencode($data['full_path']),'">x</a>',$data['full_path'],'</td>',
        ($group?'<td class="vr">'.number_format($data['files']).'</td>':''),
        '<td class="vr">',number_format(round($data['memory_consumption']/1024)),'K</td>',
        '<td class="vr">',number_format($data['hits']),'</td>',
        '<td class="vr">',time_since($time,$data['last_used_timestamp']),'</td>',
        '<td class="vr">',empty($data['timestamp'])?'':time_since($time,$data['timestamp']),'</td>
         		</tr>';
    }
    echo '</table>';
}

function graphs_display() {


    foreach ( $graphs as $caption=>$graph) {
        echo '<div class="graph"><div class="h">',$caption,'</div><table border="0" cellpadding="0" cellspacing="0">';
        foreach ($graph as $label=>$value) {
            if ($label=='total') { $key=0; $total=$value; $totaldisplay='<td rowspan="3" class="total"><span>'.($total>999999?round($total/1024/1024).'M':($total>9999?round($total/1024).'K':$total)).'</span><div></div></td>'; continue;}
            $percent=$total?floor($value*100/$total):''; $percent=!$percent||$percent>99?'':$percent.'%';
            echo '<tr>',$totaldisplay,'<td class="actual">', ($value>999999?round($value/1024/1024).'M':($value>9999?round($value/1024).'K':$value)),'</td><td class="bar ',$colors[$key],'" height="',$percent,'">',$percent,'</td><td>',$label,'</td></tr>';
            $key++; $totaldisplay='';
        }
        echo '</table></div>',"\n";
    }
}

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
