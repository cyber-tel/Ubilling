<?php

/*
 * SNMP switch polling API
 */


     /*
     * Raw SNMP data parser
     * @return string
     */
    function sp_parse_raw($data) {
        if (!empty($data)) {
            $data=  explode('=', $data);
            $result=$data[1].'<br>';
            return ($result);
        } else {
            return (__('Empty reply received'));
        }
    }
    
    /*
     * Zyxel Port state data parser
     * 
     * @return string
     */
    function sp_parse_zyportstates($data) {
        if (!empty($data)) {
            $data=  explode('=', $data);
            $data[0]=trim($data[0]);
            $portnum=  substr($data[0],-2);
            $portnum=  str_replace('.', '', $portnum);
            
            if (ispos($data[1], '1')) {
                $cells=  wf_TableCell($portnum,'24');
                $cells.= wf_TableCell(web_bool_led(true));
                $rows=  wf_TableRow($cells,'row3');
                $result= wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells=  wf_TableCell($portnum,'24');
                $cells.= wf_TableCell(web_bool_led(false));
                $rows=  wf_TableRow($cells,'row3');
                $result= wf_TableBody($rows, '100%', 0, '');
            }
            return ($result);
            
        } else {
            return (__('Empty reply received'));
        }
    }
 
    
    /*
     * Zyxel Port byte counters data parser
     * 
     * @return string
     */
    function sp_parse_zyportbytes($data) {
        if (!empty($data)) {
            $data=  explode('=', $data);
            $data[0]=trim($data[0]);
            $portnum=  substr($data[0],-2);
            $portnum=  str_replace('.', '', $portnum);
            
            $bytes=  str_replace('Counter32:', '', $data[1]);
            $bytes=  trim($bytes);
            
            if (ispos($data[1], 'up')) {
                $cells=  wf_TableCell($portnum,'24');
                $cells.= wf_TableCell($bytes);
                $rows=  wf_TableRow($cells,'row3');
                $result= wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells=  wf_TableCell($portnum,'24');
                $cells.= wf_TableCell($bytes);
                $rows=  wf_TableRow($cells,'row3');
                $result= wf_TableBody($rows, '100%', 0, '');
            }
            return ($result);
            
        } else {
            return (__('Empty reply received'));
        }
    }
    
     /*
     * Zyxel Port description data parser
     * 
     * @return string
     */
    function sp_parse_zyportdesc($data) {
        if (!empty($data)) {
            $data=  explode('=', $data);
            $data[0]=trim($data[0]);
            $portnum=  substr($data[0],-2);
            $portnum=  str_replace('.', '', $portnum);
            if (ispos($data[1], 'NULL')) {
              $desc=__('No');
            } else {
              $desc=  str_replace('STRING:', '', $data[1]);
              $desc=  trim($desc);
            }
            if (ispos($data[1], 'up')) {
                $cells=  wf_TableCell($portnum,'24');
                $cells.= wf_TableCell($desc);
                $rows=  wf_TableRow($cells,'row3');
                $result= wf_TableBody($rows, '100%', 0, '');
            } else {
                $cells=  wf_TableCell($portnum,'24');
                $cells.= wf_TableCell($desc);
                $rows=  wf_TableRow($cells,'row3');
                $result= wf_TableBody($rows, '100%', 0, '');
            }
            return ($result);
            
        } else {
            return (__('Empty reply received'));
        }
    }
    
    /*
     * Cisco memory usage data parser
     * 
     * @return string
     */
    function sp_parse_ciscomemory($data) {
         if (!empty($data)) {
            $data=  explode('=', $data);
            $result=  vf($data[1],3);
            $result=  trim($result);
            $result=  stg_convert_size($result);
            return ($result);
        } else {
            return (__('Empty reply received'));
        }
    }
    
    /*
     * Cisco memory usage data parser
     * 
     * @return string
     */
    function sp_parse_ciscocpu($data) {
         if (!empty($data)) {
            $data=  explode('=', $data);
            $result=  vf($data[1],3);
            $result=trim($result);
            $result=$result.'%';
            return ($result);
        } else {
            return (__('Empty reply received'));
        }
    }
    
    /*
     * Gets associated list of SNMP templates and switch models
     * 
     * @return array
     */
    function sp_SnmpGetModelTemplatesAssoc() {
        $query="SELECT `id`,`snmptemplate` from `switchmodels` WHERE `snmptemplate`!=''";
        $all=  simple_queryall($query);
        $result=array();
        if (!empty($all)) {
            foreach ($all as $io=>$each) {
                $result[$each['id']]=$each['snmptemplate'];
            }
        }
        return ($result);
    }

    /* 
     * Returns raw SNMP data from device with caching
     * @param   $ip         device IP
     * @param   $community  SNMP community
     * @param   $oid        OID which will be polled
     * @param   $cache      cache results
     * 
     * @return   string
     */
    
    function sp_SnmpPollData($ip,$community,$oid,$cache=true) {
        $altercfg= rcms_parse_ini_file(CONFIG_PATH."alter.ini");
        $snmpwalk=$altercfg['SNMPWALK_PATH'];
        $command=$snmpwalk.' -c '.$community.' '.$ip.' '.$oid;
        $cachetimeout=($altercfg['SNMPCACHE_TIME']*60); //in minutes
        $cachetime=time()-$cachetimeout;
        $cachepath='exports/';
        $cacheFile=$cachepath.$ip.'_'.$oid;
        
        //cache handling
        if (file_exists($cacheFile)) {
        //cache not expired
        if ((filemtime($cacheFile)>$cachetime) AND ($cache==true)) {
            $result=  file_get_contents($cacheFile);
        } else {
            //cache expired - refresh data
            $result=  shell_exec($command);
            file_put_contents($cacheFile, $result);
        }
            
        } else {
        //no cached file exists
        $result=  shell_exec($command);
        file_put_contents($cacheFile, $result);
        }
        
        return ($result);
    }
    


    /*
     * Returns list of all monitored devices
     * 
     * @return array
     */
    function sp_SnmpGetAllDevices() {
        $query="SELECT * from `switches` WHERE `snmp`!='' AND `desc` LIKE '%SWPOLL%'";
        $result=  simple_queryall($query);
        return ($result);
    }
    
    
    /*
     * Returns list of all available SNMP device templates
     * 
     * @return array
     */
    function sp_SnmpGetAllModelTemplates() {
        $path=CONFIG_PATH."snmptemplates/";
        $alltemplates=  rcms_scandir($path);
        $result=array();
        if (!empty($alltemplates)) {
            foreach ($alltemplates as $each) {
                $result[$each]=  rcms_parse_ini_file($path.$each, true);
            }
        }
        return ($result);
    }
    
    /*
     * Parsing of FDB port table SNMP raw data
     * 
     * @param   $portTable raw SNMP data
     * 
     * @return  array
     */

    function sp_SnmpParseFdb($portTable) {
     $portData=array();
     $arr_PortTable=  explodeRows($portTable);
                        if (!empty($arr_PortTable)) {
                            foreach ($arr_PortTable as $eachEntry) {
                                if (!empty($eachEntry)) {
                                $eachEntry=  str_replace('.1.3.6.1.2.1.17.4.3.1.2', '', $eachEntry);
                                $cleanMac='';
                                $rawMac=  explode('=', $eachEntry);
                                //this part of code designed by Han and here is some magic, which we do not understand :)
                                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                                if(count($parts) == 7) {
                                $cleanMac=call_user_func_array('sprintf', $parts);
                                $portData[strtolower($cleanMac)]=vf($rawMac[1],3);
                                }
                                
                                }
                            }
                        }
     return ($portData);
    }
    
     /*
     * Parsing of FDB port table SNMP raw data for some exotic Dlink switches
     * 
     * @param   $portTable raw SNMP data
     * 
     * @return  array
     */

    function sp_SnmpParseFdbDl($portTable) {
     $portData=array();
     $arr_PortTable=  explodeRows($portTable);
                        if (!empty($arr_PortTable)) {
                            foreach ($arr_PortTable as $eachEntry) {
                                if (!empty($eachEntry)) {
                                $eachEntry=  str_replace('.1.3.6.1.2.1.17.7.1.2.2.1.2', '', $eachEntry);
                                $cleanMac='';
                                $rawMac=  explode('=', $eachEntry);
                                $parts = array('format' => '%02X:%02X:%02X:%02X:%02X:%02X') + explode('.', trim($rawMac[0], '.'));
                                unset($parts[0]);
                                if(count($parts) == 7) {
                                $cleanMac=call_user_func_array('sprintf', $parts);
                                $portData[strtolower($cleanMac)]=vf($rawMac[1],3);
                                }
                                
                                }
                            }
                        }
     return ($portData);
    }
  
    /*
     * Show data for some device
     * 
     * @param   $ip device ip
     * @param   $community snmp community
     * @param   $alltemplates all of snmp templates
     * @param   $quiet  no output
     * 
     * @return  void
     */
    
    function sp_SnmpPollDevice($ip,$community,$alltemplates,$deviceTemplate,$allusermacs,$alladdress,$quiet=false)  {
        if (isset($alltemplates[$deviceTemplate])) {
            $currentTemplate=$alltemplates[$deviceTemplate];
            if (!empty($currentTemplate)) {
                $deviceDescription=$currentTemplate['define']['DEVICE'];
                $deviceFdb=$currentTemplate['define']['FDB'];
                $sectionResult='';
                $sectionName='';
                $finalResult='';
                $tempArray=array();
                //selecting FDB processing mode
                if (isset($currentTemplate['define']['FDB_MODE'])) {
                  $deviceFdbMode=$currentTemplate['define']['FDB_MODE'];
                } else {
                  $deviceFdbMode='default'; 
                }
                
                //selecting FDB ignored port for skipping MAC-s on it
                if (isset($currentTemplate['define']['FDB_IGNORE_PORTS'])) {
                    $deviceFdbIgnore=$currentTemplate['define']['FDB_IGNORE_PORTS'];
                    $deviceFdbIgnore=  explode(',', $deviceFdbIgnore);
                    $deviceFdbIgnore=  array_flip($deviceFdbIgnore);
                } else {
                    $deviceFdbIgnore=array();
                }
                
                //parse each section of template
                foreach ($alltemplates[$deviceTemplate] as $section=>$eachpoll) {
                   if ($section!='define') {
                       if (!$quiet) {
                       $finalResult.=  wf_tag('div', false, 'dashboard', '');
                       }
                       $sectionName=$eachpoll['NAME'];
                       $sectionOids=explode(',',$eachpoll['OIDS']);
                       $sectionParser=$eachpoll['PARSER'];
                       $sectionResult='';
                       //now parse each oid
                       foreach ($sectionOids as $eachOid) {
                           $eachOid=trim($eachOid);
                           $rawData=sp_SnmpPollData($ip, $community, $eachOid, true);
                           $rawData=str_replace('"', '`', $rawData);
                           $parseCode='$sectionResult.='.$sectionParser.'("'.$rawData.'");';
                           eval($parseCode);
                       }
                       
                       if (!$quiet) {
                       $finalResult.=wf_tag('div', false, 'dashtask', ''). wf_tag('strong').__($sectionName). wf_tag('strong', true).'<br>';
                       $finalResult.=$sectionResult.  wf_tag('div', true);
                    }
                   }
                   
                }
                $finalResult.=wf_tag('div', true);
                $finalResult.=wf_tag('div', false, '', 'style="clear:both;"');
                $finalResult.=wf_tag('div',true);
                if (!$quiet) {
                    show_window('',$finalResult);
                }
                //
                //parsing data from FDB table
                //
                if ($deviceFdb=='true') {
                    //$macTable=  sp_SnmpPollData($ip, $community, '.1.3.6.1.2.1.17.4.3.1.1', true);
                    $portData=array();
                    if ($deviceFdbMode=='default') {
                    //default zyxel & cisco port table
                    $portTable= sp_SnmpPollData($ip, $community, '.1.3.6.1.2.1.17.4.3.1.2', true); 
                    } else {
                      if ($deviceFdbMode=='dlp') {
                          //custom dlink port table with VLANS
                          $portTable= sp_SnmpPollData($ip, $community, '.1.3.6.1.2.1.17.7.1.2.2.1.2', true); 
                          }
                    }
                    if (!empty($portTable)) {
                        if ($deviceFdbMode=='default') { 
                        //default FDB parser
                        $portData=sp_SnmpParseFDB($portTable);
                        } else {
                            if ($deviceFdbMode=='dlp') {
                            //exotic dlink parser
                            $portData=  sp_SnmpParseFdbDl($portTable);
                            }
                        }
                       
                       //skipping some port data if FDB_IGNORE_PORTS option is set
                       if (!empty($deviceFdbIgnore)) {
                           if (!empty($portData)) {
                               foreach ($portData as $some_mac=>$some_port) {
                                   if (!isset($deviceFdbIgnore[$some_port])) {
                                   $tempArray[$some_mac]=$some_port;
                                   }
                               }
                               $portData=$tempArray;
                           }
                       }
                        
                       $fdbCache=  serialize($portData);
                       file_put_contents('exports/'.$ip.'_fdb', $fdbCache);
                    }
                    
                    
                    //show port data User friendly :)
                    if (!empty($portData)) {
                        $allusermacs=  array_flip($allusermacs);
                        
                            $cells=  wf_TableCell(__('User'),'30%');
                            $cells.= wf_TableCell(__('MAC'));
                            $cells.= wf_TableCell(__('Ports'));
                            $rows=   wf_TableRow($cells, 'row1');
                        foreach ($portData as $eachMac=>$eachPort) {
                            //user detection
                            if (isset($allusermacs[$eachMac])) {
                                $userLogin=$allusermacs[$eachMac];
                                @$useraddress=$alladdress[$userLogin];
                                $userlink=  wf_Link('?module=userprofile&username='.$userLogin, web_profile_icon().' '.$useraddress, false);
                            } else {
                                $userlink='';
                            }
                            $cells=   wf_TableCell($userlink);
                            $cells.=  wf_TableCell($eachMac);
                            $cells.=  wf_TableCell($eachPort);
                            $rows.=   wf_TableRow($cells, 'row3');
                        }
                        if (!$quiet) {
                        show_window(__('FDB'),wf_TableBody($rows, '100%', '0', 'sortable'));
                        }
                    }
                    
                    
                }
                
            }
        }
    }
   
    
        /*
     * function that display JSON data for display FDB cache
     * 
     * @param $fdbData_raw - array of existing cache _fdb files
     * 
     * @return string
     */
    function sn_SnmpParseFdbCacheJson($fdbData_raw) {
        $allusermacs=zb_UserGetAllMACs();
        $allusermacs=  array_flip($allusermacs);
        $alladdress= zb_AddressGetFulladdresslist();
        $allswitches=  zb_SwitchesGetAll();
        $switchdata=array();
        
        if (!empty($allswitches)) {
            foreach ($allswitches as $io=>$eachswitch) {
                $switchdata[$eachswitch['ip']]=$eachswitch['location'];
            }
        }
        
          $result='{ 
                  "aaData": [';
          
          foreach ($fdbData_raw as $each_raw) {
              $nameExplode=  explode('_', $each_raw);
              if (sizeof($nameExplode)==2) {
                  $switchIp=$nameExplode[0];
                  $eachfdb_raw=  file_get_contents('exports/'.$each_raw);
                  $eachfdb= unserialize($eachfdb_raw);
                  if (!empty($eachfdb_raw)) {
                      foreach ($eachfdb as $mac=>$port) {
                          //detecting user login by his mac
                          if (isset($allusermacs[$mac])) {
                              $userlogin=$allusermacs[$mac];
                          } else {
                              $userlogin=false;
                          }
                          
                      if ($userlogin) {
                          $userlink= '<a href=?module=userprofile&username='.$userlogin.'><img src=skins/icon_user.gif> '.@$alladdress[$userlogin].'</a>';
                      } else {
                          $userlink='';
                      }
                            $result.='
                    [
                    "'.$switchIp.'",
                    "'.$port.'",
                    "'.@$switchdata[$switchIp].'",
                    "'.$mac.'",
                    "'.$userlink.'"
                    ],';
                      }
                  }
              }
          }
          
          $result=substr($result, 0, -1);
          
          $result.='] 
        }';
          
        return($result);
    }
    

?>