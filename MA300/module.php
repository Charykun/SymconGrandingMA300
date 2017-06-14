<?php                                                                           
    class GrandingMA300 extends IPSModule                                  
    {
        /**
         * Log Message
         * @param string $Message
         */
        protected function Log($Message)
        {
            IPS_LogMessage(__CLASS__, $Message);
        }

        /**
         * Create
         */         
        public function Create()
        {
            //Never delete this line!
            parent::Create();   
            
            $this->RegisterPropertyInteger("Stamp", 0);
            $this->RegisterPropertyInteger("OpStamp", 0);  
            $this->RegisterPropertyString("CMD", "");              
        }

        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();   
            
            $this->RegisterVariableBoolean("Status", "Status", "~Alert.Reversed", -1);                         
            $this->RegisterVariableString("OPLOG", "OPLOG");            
            $sid = $this->RegisterScript("Hook", "Hook (iclock)", "<? //Do not delete or modify.\nGrandingMA300_ProcessHookData(".$this->InstanceID.");");
            IPS_SetHidden($sid, true);
            $this->RegisterHook("/hook/iclock", $sid);
        } 
        
        /**
         * GrandingMA300_ProcessHookData
         * @global string $HTTP_RAW_POST_DATA
         */
        public function ProcessHookData()
	{
            if($_IPS['SENDER'] == "Execute") 
            {
                echo "This script cannot be used this way.";
		return;
            }
            SetValueBoolean($this->GetIDForIdent("Status"), true);
            $eid = @IPS_GetObjectIDByIdent("Poller", $this->InstanceID);
            if($eid === false) { $eid = 0; } else if(IPS_GetEvent($eid)['EventType'] <> 1) { IPS_DeleteEvent($eid); $eid = 0; }
            if ($eid == 0) 
            {
                $eid = IPS_CreateEvent(1);
                IPS_SetParent($eid, $this->InstanceID);
                IPS_SetIdent($eid, "Poller");
                IPS_SetName($eid, "Poller");
                IPS_SetHidden($eid, true);
                IPS_SetEventScript($eid, "SetValueBoolean(IPS_GetObjectIDByIdent('Status', \$_IPS['TARGET']), false);");
            }                    
            IPS_SetEventCyclicTimeFrom($eid, date("H"), date("i"), date("s"));
            IPS_SetEventCyclic($eid, 0, 0, 0, 0, 1, 45);
            IPS_SetEventActive($eid, true);                        
            header("Content-Type: text/plain");
            switch(basename($_SERVER["REQUEST_URI"], "?" . $_SERVER["QUERY_STRING"]))
            {
                case "cdata":
		    $this->SendDebug("GET: cdata", print_r($_GET,true), false);		   
                    if(isset($_GET["pushver"]))
                    {    	    
                        $Stamp = $this->ReadPropertyInteger("Stamp");
                        $OpStamp = $this->ReadPropertyInteger("OpStamp");
                        echo "Stamp=$Stamp\r\nOpStamp=$OpStamp\r\nErrorDelay=30\r\nDelay=15\r\nRealtime=1\r\nEncrypt=0\r\nTimeZoneclock=1\r\nTimeZone=1\r\n";
                    }
                    else
                    if(isset($_GET["table"]))
                    {
                        echo "OK\r\n";
                        if($_GET["table"] == "ATTLOG")
                        {
                            if($_GET["Stamp"] > 0)
                            {
                                IPS_SetProperty($this->InstanceID, "Stamp", (int)$_GET["Stamp"]);
                                IPS_ApplyChanges($this->InstanceID);
                                $DATA_r = explode("\n", file_get_contents("php://input"));
                                for ($index = 0; $index < count($DATA_r) - 1; $index++) 
                                {
                                    $Value_r = explode("\t", $DATA_r[$index]);
                                    $User = $Value_r[0];
                                    $Time = strtotime($Value_r[1]);                                   
                                    $this->RegisterVariableInteger("User_" . $User, "User: " . $User, "~UnixTimestamp");
                                    SetValueInteger($this->GetIDForIdent("User_" . $User), (int)$Time);
                                }
                            }
                        }
                        else
                        if($_GET["table"] == "OPERLOG")
                        {
                            if($_GET["OpStamp"] > 0)
                            {
                                IPS_SetProperty($this->InstanceID, "OpStamp", (int)$_GET["OpStamp"]);
                                IPS_ApplyChanges($this->InstanceID);
                                $DATA_r = explode("\n", file_get_contents("php://input"));
                                for ($index = 0; $index < count($DATA_r) - 1; $index++) 
                                {
                                    $Value_r = explode("\t", $DATA_r[$index]);
                                    if($Value_r[0] == "OPLOG 3")
                                    {
                                        switch ($Value_r[3]) 
                                        {
                                            case "58":
                                                $OPLOG = "ALARM 58: Try Invalid Verification (" . $Value_r[2] . ")";
                                            break;
                                            default:
                                                $OPLOG = "ALARM " . $Value_r[3] . ": (" . $Value_r[2] . ")";
                                            break;
                                        }                                        
                                    }
                                    else
                                    {
                                        $OPLOG = $Value_r;
                                    }                                    
                                    $this->Log($OPLOG);
                                    SetValueString($this->GetIDForIdent("OPLOG"), $OPLOG);
                                }
                            }
                        }
                    }
                    else
                    {                        
                        echo "OK\r\n";
                    }
                break;
                case "getrequest":
		    $this->SendDebug("GET: getrequest", print_r($_GET,true), false); 	    
                    if($this->ReadPropertyString("CMD") == "")
                    {
                        echo "OK\r\n";
                    }
                    else 
                    {
                        echo "C:ID1:" . $this->ReadPropertyString("CMD") . "\r\n";
                        IPS_SetProperty($this->InstanceID, "CMD", "");
                        IPS_ApplyChanges($this->InstanceID);
                    }
                break;    
                default:
                    echo "OK\r\n";
                break;
            }
	    $this->SendDebug("DATA", file_get_contents("php://input"), false);	
        }
        
        /**
         * GrandingMA300_Reboot
         */
        public function Reboot() 
        {
            IPS_SetProperty($this->InstanceID, "CMD", "REBOOT");
            IPS_ApplyChanges($this->InstanceID);
        }
        
        /**
         * RegisterHook
         * @param string $Hook
         * @param integer $TargetID
         */
        private function RegisterHook($Hook, $TargetID)
	{
            $ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
            if(sizeof($ids) > 0) 
            {
                $hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
		$found = false;
		foreach($hooks as $index => $hook) 
                {
                    if($hook['Hook'] == $Hook) 
                    {
                        if($hook['TargetID'] == $TargetID)
                            return;
			$hooks[$index]['TargetID'] = $TargetID;
			$found = true;
                    }
		}
		if(!$found) 
                {
                    $hooks[] = Array("Hook" => $Hook, "TargetID" => $TargetID);
		}
		IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
		IPS_ApplyChanges($ids[0]);
            }
	}      
    }
