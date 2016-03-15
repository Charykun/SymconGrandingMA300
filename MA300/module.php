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
            
            $sid = $this->RegisterScript("Hook", "Hook (iclock)", "<? //Do not delete or modify.\nGrandingMA300_ProcessHookData(".$this->InstanceID.");");
            IPS_SetHidden($sid, true);
            $this->RegisterHook("/hook/iclock", $sid);
        } 
        
        public function ProcessHookData()
	{
            if($_IPS['SENDER'] == "Execute") 
            {
                echo "This script cannot be used this way.";
		return;
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