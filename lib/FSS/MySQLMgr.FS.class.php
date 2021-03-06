<?php
        /*
        * Copyright (c) 2010-2013, Loïc BLOT, CNRS <http://www.unix-experience.fr>
        * All rights reserved.
        *
        * Redistribution and use in source and binary forms, with or without
        * modification, are permitted provided that the following conditions are met:
        *
        * 1. Redistributions of source code must retain the above copyright notice, this
        *    list of conditions and the following disclaimer.
        * 2. Redistributions in binary form must reproduce the above copyright notice,
        *    this list of conditions and the following disclaimer in the documentation
        *    and/or other materials provided with the distribution.
        *
        * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
        * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
        * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
        * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
        * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
        * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
        * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
        * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
        * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
        * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
        *
        * The views and conclusions contained in the software and documentation are those
        * of the authors and should not be interpreted as representing official policies,
        * either expressed or implied, of the FreeBSD Project.
        */
	require_once(dirname(__FILE__)."/../../config/global.conf.php");
	class FSMySQLMgr {
		function FSMySQLMgr() {
			$this->dbName = "";
			$this->dbPort = 3306;
			$this->dbHost = "";
			$this->dbPass = "";
			$this->dbUser = "";
			$this->dbConn = NULL;
		}

		public function Connect() {
			$this->Close();

			$result = mysql_connect($this->dbHost.":".$this->dbPort,$this->dbUser,$this->dbPass);
			if(!$result) {
				$iMgr = new FSInterfaceMgr($this);
				$iMgr->printError("Unable to connect to MySQL database");
				return 1; 
			}
			$this->dbConn = $result;
			$result = mysql_select_db($this->dbName,$this->dbConn);
			if(!$result) {
				$iMgr = new FSInterfaceMgr($this);
				echo $iMgr->printError("Unable to use database '".$this->dbName."' for host '".$this->dbHost."'");
				return 1;
			}
			return 0;
		}

		public function Select($table,$fields,$cond = "",$options = array()) {
			$sql = "SELECT ".$fields." FROM ".$table."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			if(isset($options["group"]) && strlen($options["group"]) > 0)
				$sql .= " GROUP BY ".$options["group"];
			if(isset($options["order"]) && strlen($options["order"]) > 0) {
				$sql .= " ORDER BY ".$options["order"];
				if(isset($options["ordersens"])) {
					if($options["ordersens"] == 1)
						$sql .= " DESC";
					else if($options["ordersens"] == 2)
						$sql .= " ASC";	
				}
			}
			if(isset($options["limit"]) && $options["limit"] > 0) {
				if(isset($options["startidx"]) && $options["startidx"] > 0)
					$sql .= " LIMIT ".$options["startidx"].",".($options["startidx"]+$options["limit"]);
				else
					$sql .= " LIMIT ".$options["limit"];
				
			}
			return mysql_query($sql);
		}
		
		public function GetOneData($table,$field,$cond = "",$options = array()) {
			$query = $this->Select($table,$field,$cond,$options);
			if($data = mysql_fetch_array($query)) {
				$splstr = preg_split("#[\.]#",$field);
				$splstr = preg_replace("#`#","",$splstr);
				return $data[$splstr[count($splstr)-1]];
			}
			return NULL;
		}
		
		public function GetMax($table,$field,$cond = "") {
			$max = $this->GetOneData($table,"MAX(".$field.")",$cond);
			if($max == NULL)
				return 1;
			return $max;
		}

		public function Count($table,$field,$cond = "") {
			$count = $this->GetOneData($table,"COUNT(".$field.")",$cond);
			if($count == NULL)
				return NULL;
			return $count;
		}

		public function Sum($table,$field,$cond = "") {
			$count = $this->GetOneData($table,"SUM(".$field.")",$cond);
			if($count == NULL)
				return 0;
			return $count;
		}

		public function Fetch(&$query) {
			return mysql_fetch_array($query);
		}

		public function Insert($table,$keys,$values) {
			$sql = "INSERT INTO ".$table."(".$keys.") VALUES (".$values.");";
			mysql_query($sql);
		}

		public function InsertIgnore($table,$keys,$values) {
			$sql = "INSERT IGNORE INTO ".$table."(".$keys.") VALUES (".$values.");";
			mysql_query($sql);
		}

		public function Delete($table,$cond = "") {
			$sql = "DELETE FROM ".$table."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			mysql_query($sql);
		}

		public function Update($table,$mods,$cond = "") {
			$sql = "UPDATE ".$table." SET ".$mods."";
			if(strlen($cond) > 0)
				$sql .= " WHERE ".$cond;
			mysql_query($sql);
		}

		public function setConfig($dbn,$dbport,$dbh,$dbu,$dbp) {
			if($dbn == $this->dbName && $dbport == $this->dbPort && $dbh == $this->dbHost &&
				$dbu == $this->dbUser && $this->dbConn)
				return 1;
			$this->dbName = $dbn;
			$this->dbPort = $dbport;
			$this->dbHost = $dbh;
			$this->dbUser = $dbu;
			$this->dbPass = $dbp;
			return 0;
		}

		public function Close() {
			if($this->dbConn)
				mysql_close($this->dbConn);
		}

		public function BeginTr() {
			mysql_query("BEGIN;");
		}

		public function CommitTr() {
			mysql_query("COMMIT;");
		}

		public function RollbackTr() {
			mysql_query("ROLLBACK;");
		}

		private $dbName;
		private $dbPort;
		private $dbHost;
		private $dbPass;
		private $dbUser;
		private $dbConn;
	};
?>
