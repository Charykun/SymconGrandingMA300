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
        }

        /**
         * ApplyChanges
         */
        public function ApplyChanges()
        {
            //Never delete this line!
            parent::ApplyChanges();   
            
            $this->RegisterVariableInteger("ATTLOG", "ATTLOG");
            $this->RegisterVariableInteger("OPERLOG", "OPERLOG");            
            $this->RegisterVariableString("OPLOG", "OPLOG");
            
            $sid = $this->RegisterScript("Hook", "Hook (iclock)", "<? //Do not delete or modify.\nGrandingMA300_ProcessHookData(".$this->InstanceID.");");
            IPS_SetHidden($sid, true);
            $this->RegisterHook("/hook/iclock", $sid);
        } 
        
        public function ProcessHookData()
	{
            global $HTTP_RAW_POST_DATA;
            if($_IPS['SENDER'] == "Execute") 
            {
                echo "This script cannot be used this way.";
		return;
            }
            header("Content-Type: text/plain");
            switch(basename($_SERVER["REQUEST_URI"], "?" . $_SERVER["QUERY_STRING"]))
            {
                case "cdata":
                    if(isset($_GET["pushver"]))
                    {                        
                        $Stamp = GetValueInteger($this->GetIDForIdent("ATTLOG"));
                        $OpStamp = GetValueInteger($this->GetIDForIdent("OPERLOG"));
                        echo "Stamp=$Stamp\r\nOpStamp=$OpStamp\r\nErrorDelay=30\r\nDelay=15\r\nRealtime=1\r\nEncrypt=0\r\nTimeZoneclock=1\r\nTimeZone=1\r\n";
                    }
                    else
                    if(isset($_GET["table"]))
                    {
                        echo "OK\n";
                        if($_GET["table"] == "ATTLOG")
                        {
                            if($_GET["Stamp"] > 0)
                            {
                                SetValueInteger($this->GetIDForIdent("ATTLOG"), (int)$_GET["Stamp"]);
                                $DATA_r = explode("\n", $HTTP_RAW_POST_DATA);
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
                                SetValueInteger($this->GetIDForIdent("OPERLOG"), (int)$_GET["OpStamp"]);
                                $DATA_r = explode("\n", $HTTP_RAW_POST_DATA);
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
                        echo "OK\n";
                    }
                break;
                default:
                    echo "OK\n";
                break;
            }
        }
        
        /**
         * RegisterHook
         * @param type $Hook
         * @param type $TargetID
         * @return type
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