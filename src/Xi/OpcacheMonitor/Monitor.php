<?php

namespace Xi\OpcacheMonitor;

class Monitor
{

    private $extensionName;

    private $status = [];

    private $configuration = [];

    private $time;

    public function __construct()
    {
        $this->extensionName = 'Zend OPCache';

        $this->configuration = opcache_get_configuration();

        $this->status = opcache_get_status();

        $this->time = time();
    }

    public function getUptime()
    {
        $uptime=array();
        if ( !empty($this->status['opcache_statistics']['start_time']) ) {
            $uptime['uptime'] = $this->timeSince($this->time,$this->status['opcache_statistics']['start_time'],1,'');
        }
        if ( !empty($this->status['opcache_statistics']['last_restart_time']) ) {
            $uptime['last_restart'] = $this->timeSince($this->time,$this->status['opcache_statistics']['last_restart_time']);
        }

        return $uptime;
    }

    public function getGeneral()
    {
        $host = Monitor::getHost();
        $general = array('Host'=>$host);
        $general['PHP Version']='PHP '.(defined('PHP_VERSION')?PHP_VERSION:'???').' '.(defined('PHP_SAPI')?PHP_SAPI:'').' '.(defined('PHP_OS')?' '.PHP_OS:'');
        $general['Opcache Version']=empty($this->configuration['version']['version'])?'???':$this->configuration['version']['opcache_product_name'].' '.$this->configuration['version']['version'];

        ob_start(); phpinfo(8); $phpinfo = ob_get_contents(); ob_end_clean(); 		 // some info is only available via phpinfo? sadly buffering capture has to be used
        if ( !preg_match( '/module\_Zend (Optimizer\+|OPcache).+?(\<table[^>]*\>.+?\<\/table\>).+?(\<table[^>]*\>.+?\<\/table\>)/s', $phpinfo, $opcache) ) { }  // todo

        if ( !empty($opcache[2]) ) {
            $opcache[2] = preg_replace('/\<tr\>\<td class\="e"\>[^>]+\<\/td\>\<td class\="v"\>[0-9\,\. ]+\<\/td\>\<\/tr\>/','',$opcache[2]);
            preg_match_all('/\<tr\>\<td class\="e"\>([^>]+)\<\/td\>\<td class\="v"\>([^>]+)\<\/td\>\<\/tr\>/',$opcache[2], $lussis);
            foreach ($lussis[1] as $key => $lussi) {
                $general[trim($lussi)] = $lussis[2][$key];
            }
        }

        return $general;
    }


    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getStatus()
    {
        return $this->status;
    }


    public function getGraphData()
    {
        $graphs=array();

        $primes=array(223, 463, 983, 1979, 3907, 7963, 16229, 32531, 65407, 130987);

        $graphs['memory']['total']=$this->configuration['directives']['opcache.memory_consumption'];
        $graphs['memory']['free']=$this->status['memory_usage']['free_memory'];
        $graphs['memory']['used']=$this->status['memory_usage']['used_memory'];
        $graphs['memory']['wasted']=$this->status['memory_usage']['wasted_memory'];

        $graphs['keys']['total']=$this->status['opcache_statistics']['max_cached_keys'];
        foreach ($primes as $prime) {
            if ($prime >= $graphs['keys']['total']) {
                $graphs['keys']['total'] = $prime;
                break;
            }
        }
        $graphs['keys']['free']= $graphs['keys']['total'] - $this->status['opcache_statistics']['num_cached_keys'];
        $graphs['keys']['scripts'] = $this->status['opcache_statistics']['num_cached_scripts'];
        $graphs['keys']['wasted'] = $this->status['opcache_statistics']['num_cached_keys']-$this->status['opcache_statistics']['num_cached_scripts'];

        $graphs['hits']['total']=0;
        $graphs['hits']['hits']=$this->status['opcache_statistics']['hits'];
        $graphs['hits']['misses']=$this->status['opcache_statistics']['misses'];
        $graphs['hits']['blacklist']=$this->status['opcache_statistics']['blacklist_misses'];
        $graphs['hits']['total']=array_sum($graphs['hits']);

        $graphs['restarts']['total']=0;
        $graphs['restarts']['manual']=$this->status['opcache_statistics']['manual_restarts'];
        $graphs['restarts']['keys']=$this->status['opcache_statistics']['hash_restarts'];
        $graphs['restarts']['memory']=$this->status['opcache_statistics']['oom_restarts'];
        $graphs['restarts']['total']=array_sum($graphs['restarts']);

        return $graphs;
    }


    public function getGraphs()
    {
        $graphs = $this->getGraphData();

        foreach ($graphs['memory'] as $key => &$value) {

            switch ($value) {

                default:
                    $value = $value / 1024 / 1024;
            }

        }

        return $graphs;

    }

    public static function getHost()
    {
        $host = function_exists('gethostname')? @gethostname() :@php_uname('n');
        if (empty($host)) {
            $host=empty($_SERVER['SERVER_NAME'])?$_SERVER['HOST_NAME']:$_SERVER['SERVER_NAME'];
        }
        return $host;
    }

    public function getFunctions()
    {
        return get_extension_funcs($this->extensionName);
    }

    private function timeSince($time, $original, $extended=0, $text='ago')
    {
        $time =  $time - $original;
        $day = $extended? floor($time/86400) : round($time/86400,0);
        $amount=0; $unit='';
        if ( $time < 86400) {
            if ( $time < 60)		{ $amount=$time; $unit='second'; }
            elseif ( $time < 3600) { $amount=floor($time/60); $unit='minute'; }
            else				{ $amount=floor($time/3600); $unit='hour'; }
        }
        elseif ( $day < 14) 	{ $amount=$day; $unit='day'; }
        elseif ( $day < 56) 	{ $amount=floor($day/7); $unit='week'; }
        elseif ( $day < 672) { $amount=floor($day/30); $unit='month'; }
        else {			  $amount=intval(2*($day/365))/2; $unit='year'; }

        if ( $amount!=1) {$unit.='s';}
        if ($extended && $time>60) { $text=' and '. $this->timeSince($time,$time<86400?($time<3600?$amount*60:$amount*3600):$day*86400,0,'').$text; }

        return $amount.' '.$unit.' '.$text;

    }

    public function getFiles($group = 0, $sort = 0) {

        $status = $this->getStatus();

        $groupset=array_fill(0,9,'');
        $groupset[$group]=' class="b" ';

        if ( !$group ) {
            $files = $status['scripts'];
        } else {
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

            return $files;
        }

        if ( $sort ) {
            $keys=array(
                'full_path'=>SORT_STRING,
                'files'=>SORT_NUMERIC,
                'memory_consumption'=>SORT_NUMERIC,
                'hits'=>SORT_NUMERIC,
                'last_used_timestamp'=>SORT_NUMERIC,
                'timestamp'=>SORT_NUMERIC
            );
            $offsets=array_keys($keys);
            $key=intval($sort);
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

        return $files;
    }


}
