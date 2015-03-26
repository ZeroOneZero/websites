<?php
/*
 * blocklist.php
 * Processes blocks list, handles block queries
 */
class BlockList {
	var $blockArray = array(); // Array containing id as key, name as value


	/*
	 * Constructor
	 */
	public function __construct($filename) {
		$this->openFile($filename);
	}

	/* 
	 * Open file, store contents in blockArray
	 */
	private function openFile($filename) {
		$file = fopen($filename,"r");
		if(!$file){
			if(!file_exists($filename))
				throw new Exception("$this->FILENAME does not exist.");
			else
				throw new Exception("Error opening $this->FILENAME.");
		}

		$lineCount = 1;
		while(!feof($file)){
			$line = fgetcsv($file);
			
			if(trim($line[0]) == "") 
				continue;
			if(count($line) != 2) {
				throw new Exception("
					Incorrect formatting in $this->FILENAME line $lineCount<br>" . 
					implode(",", $line) . "<br>" .
					"Expected id,blockname"
				);
			}
			
			$this->blockArray[$line[0]] = $line[1];
			$lineCount++;
		}

		fclose($file);
	}

	/* 
	 * Input block id number, return block name if found
	 * Returns Unknown block id if not found
	 */
	public function getBlockName($id) {
		if(array_key_exists($id, $this->blockArray))
			return $this->blockArray[$id];
		else
			return "Unknown block id: $id";
	}
	
	/* 
	 * Input array of names or partial names or ids
	 * Return array of block ids
	 */
	public function getBlockIdList($rawBlockArray) {
		if($rawBlockArray[0] == "")
			return null;

		$finalIdArray = array();
		
		// Loop through each element in rawBlockArray
		for($i = 0; $i < count($rawBlockArray); $i++) {
			$currEle = $rawBlockArray[$i];
			
			if($currEle == "")
				continue;

			// If element is a number and valid block id, push to finalIdArray
			if(is_numeric($currEle) && $this->validBlockId($currEle)) {
				array_push($finalIdArray, $currEle);
			} else { 
				// If element is a string, search block list and merge with final array
				$ids = $this->getBlockList($currEle);
				if(count($ids) > 0){
					$finalIdArray = array_merge($finalIdArray, $ids);
				}			
			}
		}

		// Remove duplicates and return
		$finalIdArray = array_unique($finalIdArray);
		return $finalIdArray;
	}

	/*
	 * Check if given id is valid block id
	 * Returns true if valid, false if invalid
	 */
	private function validBlockId($id) {
		if(array_key_exists($id, $this->blockArray))
			return true;
		return false;
	}

	/*
	 * Input name or partial name, check if in block list
	 * Returns array of ids
	 */
	private function getBlockList($name) {
		$pattern = "/$name/i";
		$blockIds = array();
		
		// Loop through blockArray
		foreach($this->blockArray as $blockId=>$blockName) {
			// Try to match pattern with block name, push valid block id into blockIds
			if(preg_match($pattern, $blockName)) {
				array_push($blockIds, $blockId);
			}
		}
		
		return $blockIds;
	}
}
?>