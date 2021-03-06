<?php
	/*
	* Copyright (C) 2010-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
	*
	* This program is free software; you can redistribute it and/or modify
	* it under the terms of the GNU General Public License as published by
	* the Free Software Foundation; either version 2 of the License, or
	* (at your option) any later version.
	*
	* This program is distributed in the hope that it will be useful,
	* but WITHOUT ANY WARRANTY; without even the implied warranty of
	* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	* GNU General Public License for more details.
	*
	* You should have received a copy of the GNU General Public License
	* along with this program; if not, write to the Free Software
	* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
	*/

	class iSecReport extends FSModule {
		function iSecReport($locales) { parent::FSModule($locales); }

		public function Load() {
			FS::$iMgr->setTitle($this->loc->s("title-attack-report"));

			// Load snort keys for db config
			$dbname = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbname'");
			if($dbname == "") $dbname = "snort";
			$dbhost = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbhost'");
			if($dbhost == "") $dbhost = "localhost";
			$dbuser = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbuser'");
			if($dbuser == "") $dbuser = "snort";
			$dbpwd = FS::$dbMgr->GetOneData(PGDbConfig::getDbPrefix()."snortmgmt_keys","val","mkey = 'dbpwd'");
			if($dbpwd == "") $dbpwd = "snort";

			$this->snortDB = new AbstractSQLMgr();
			$this->snortDB->setConfig("pg",$dbname,5432,$dbhost,$dbuser,$dbpwd);
			$this->snortDB->Connect();
			$output = "";
			if(!FS::isAjaxCall())
				$output .= FS::$iMgr->h1("title-attack-report");
			$output .= $this->loadAttackGraph();
			return $output;
		}

		private function loadAttackGraph() {
			$output = "";
			$showmodule = FS::$secMgr->checkAndSecuriseGetData("sh");
			$ech = FS::$secMgr->checkAndSecuriseGetData("ech");
			if($ech == NULL) $ech = 7;
			
			$ec = FS::$secMgr->checkAndSecuriseGetData("ec");
			if(!FS::$secMgr->isNumeric($ec)) $ec = 365;
			if($ec == NULL) $ec = 365;
			
			$shscan = FS::$secMgr->checkAndSecuriseGetData("sc");
			if($shscan == NULL) $shscan = true;
			else if($shscan > 0) $shscan = true;
			else $shscan = false;
			
			$shtse = FS::$secMgr->checkAndSecuriseGetData("tse");
			if($shtse == NULL) $shtse = true;
			else if($shtse > 0) $shtse = true;
			else $shtse = false;
			
			$shssh = FS::$secMgr->checkAndSecuriseGetData("ssh");
			if($shssh == NULL) $shssh = true;
			else if($shssh > 0) $shssh = true;
			else $shssh = false;
			
			$topmax = FS::$secMgr->checkAndSecuriseGetData("max");
			if($topmax == NULL || !FS::$secMgr->isNumeric($topmax) || $topmax < 1) $topmax = 10;
			
			if(!FS::isAjaxCall()) {
				$output .= FS::$iMgr->tabPan(array(
					array(1,"mod=".$this->mid."&max=".$topmax."&ec=".$ec."&ech=".$ech."&ssh=".($shssh ? 1 : 0)."&tse=".($shtse ? 1 : 0)."&scan=".($shscan ? 1 : 0),$this->loc->s("General")),
					array(5,"mod=".$this->mid."&max=".$topmax,$this->loc->s("Last-logs")),
					array(2,"mod=".$this->mid."&max=".$topmax,$this->loc->s("Scans")),
					array(3,"mod=".$this->mid."&max=".$topmax,$this->loc->s("TSE")),
					array(4,"mod=".$this->mid."&max=".$topmax,$this->loc->s("SSH"))),$showmodule);
			}
			else {
				if(!$showmodule || $showmodule == 1) {
					$output .= FS::$iMgr->h3("title-z-eye-report");
					$totalips = $this->snortDB->Count(PGDbConfig::getDbPrefix()."collected_ips","ip");
					$totalscan = $this->snortDB->Sum(PGDbConfig::getDbPrefix()."collected_ips","scans");
					$totaltse = $this->snortDB->Sum(PGDbConfig::getDbPrefix()."collected_ips","tse");
					$totalssh = $this->snortDB->Sum(PGDbConfig::getDbPrefix()."collected_ips","ssh");
					$totalatk = $totalscan + $totaltse + $totalssh;
					
					$output .= $this->loc->s("total-atk").": ".$totalatk."<br />";
					$output .= $this->loc->s("nb-ip-atk").": ".$totalips."<br />";
					$output .= $this->loc->s("nb-scan-port").": ".$totalscan."<br />";
					$output .= $this->loc->s("nb-tse-atk").": ".$totaltse."<br />";
					$output .= $this->loc->s("nb-ssh-atk").": ".$totalssh."<br /><hr>";
					
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=1");
					$output .= FS::$iMgr->hidden("mod",$this->mid);
					$output .= "Pas: ".FS::$iMgr->numInput("ech",$ech,array("size" => 2, "length" => 2))." jours <br />";
					$output .= "Echelle: ".FS::$iMgr->numInput("ec",$ec,array("size" => 3, "length" => 3))." jours <br />";
		
					$output .= FS::$iMgr->submit("",$this->loc->s("Update"))."<br />";
					$output .= "</form>";
					$output .= "<div id=\"atkst\"></div>";
					$year = date("Y");
					$month = date("m");
					$day = date("d");
		
					$sql_date = $year."-".$month."-".$day." 00:00:00";
					$fields = "";
					
					$fields .= ",scans";
					$fields .= ",tse";
					$fields .= ",ssh";
					
					$sqlcalc = "(SELECT '".$sql_date."'::timestamp - '".($ec+15)." day'::interval)";
					$sql = "select atkdate".$fields." from z_eye_attack_stats where atkdate > ".$sqlcalc." ORDER BY atkdate";
					$query = pg_query($sql);
					$labels = $scans = $tse = $ssh = "[";
					$cursor = 0;
					$temp1 = $temp2 = $temp3 = $temp4 = 0;
					while($data = FS::$dbMgr->Fetch($query)) {
						if($cursor != $ech || $ech == 1) {
							$cursor++;
							$temp1 = substr($data["atkdate"],8,2)."/".substr($data["atkdate"],5,2);
							$temp2 += $data["scans"];
							$temp3 += $data["tse"];
							$temp4 += $data["ssh"];
						}

						if($cursor == $ech) {
							$labels .= "'".$temp1."',";
							$scans .= $temp2.",";
							$tse .= $temp3.",";
							$ssh .= $temp4.",";
							$cursor = $temp1 = $temp2 = $temp3 = $temp4 = 0;
						}
					}

					$labels .= "]";
					$scans .= "]";
					$tse .= "]";
					$ssh .= "]";

					$output .= FS::$iMgr->js("(function($){ var hchart;
							hchart = new Highcharts.Chart({
							chart: { renderTo: 'atkst', type: 'line' },
							title: { text: 'Graphique d\'attaques SNORT' },
							xAxis: { categories: ".$labels." },
							yAxis: { title: { text: 'Nombre d\'attaques' } },
							legend: { layout: 'vertical', align: 'right', verticalAlign: 'top',
									x: -10, y: 100 },
							series: [ { name: 'Scans', data: ".$scans." },
									{ name: '".$this->loc->s("TSE-atk")."', data: ".$tse." },
									{ name: '".$this->loc->s("SSH-atk")."', data: ".$ssh." }]
							});
						})(jQuery);");
				}
				else if($showmodule == 2) {
					$found = 0;
					
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=2");
					$output .= $this->loc->s("Maximum").": ".FS::$iMgr->numInput("max",$topmax,array("size" => 3, "length" => 3))." <br />";
					$output .= FS::$iMgr->submit("",$this->loc->s("Update"))."<br />";
					$output .= "</form>";
					
					$tmpoutput = FS::$iMgr->h3("Top ".$topmax." (".$this->loc->s("Scans").")",true)."<table><tr><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("Last-visit")."</th><th>".$this->loc->s("Action-nb")."</th></tr>";
					
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."collected_ips","ip,last_date,scans","",array("order" => "scans","ordersens" => 1,"limit" => $topmax));
					while($data = $this->snortDB->Fetch($query)) {
						if($found == 0) $found = 1;
						$tmpoutput .= "<tr><td>".$data["ip"]."</td><td>".$data["last_date"]."</td><td>".$data["scans"]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
						
					$found = 0;
					$tmpoutput = FS::$iMgr->h3($this->loc->s("The")." ".$topmax." ".$this->loc->s("violent-days"),true)."<table><tr><th>Date</th><th>".$this->loc->s("Action-nb")."</th></tr>";
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."attack_stats","atkdate,scans","",array("order" => "scans","ordersens" => 1,"limit" => $topmax));
					while($data = $this->snortDB->Fetch($query)) {
						if($found == 0) $found = 1;
						$date = preg_split("# #",$data["atkdate"]);
						$tmpoutput .= "<tr><td>".$date[0]."</td><td>".$data["scans"]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
				}
				else if($showmodule == 3) {
					$found = 0;
					
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=3");
					$output .= "Maximum: ".FS::$iMgr->numInput("max",$topmax,array("size" => 3, "length" => 3))." <br />";
					$output .= FS::$iMgr->submit("",$this->loc->s("Update"))."<br />";
					$output .= "</form>";
					
					$tmpoutput = FS::$iMgr->h3("Top ".$topmax." (".$this->loc->s("TSE-atk").")",true)."<table><tr><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("Last-visit")."</th><th>".$this->loc->s("Action-nb")."</th></tr>";
					
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."collected_ips","ip,last_date,tse","",array("order" => "tse","ordersens" => 1,"limit" => $topmax));
					while($data = $this->snortDB->Fetch($query)) {
						if($found == 0) $found = 1;
						$tmpoutput .= "<tr><td>".$data["ip"]."</td><td>".$data["last_date"]."</td><td>".$data["tse"]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
						
					$found = 0;
					$tmpoutput = FS::$iMgr->h3($this->loc->s("The")." ".$topmax." ".$this->loc->s("violent-days"),true)."<table><tr><th>".$this->loc->s("Date")."<th>".$this->loc->s("Action-nb")."</th></tr>";
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."attack_stats","atkdate,tse","",array("order" => "tse","ordersens" => 1,"limit" => $topmax));
					while($data = $this->snortDB->Fetch($query)) {
						if($found == 0) $found = 1;
						$date = preg_split("# #",$data["atkdate"]);
						$tmpoutput .= "<tr><td>".$date[0]."</td><td>".$data["tse"]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
				}
				else if($showmodule == 4) {
					$found = 0;
					
					$output .= FS::$iMgr->form("index.php?mod=".$this->mid."&act=4");
					$output .= $this->loc->s("Maximum").": ".FS::$iMgr->numInput("max",$topmax,array("size" => 3, "length" => 3))." <br />";
					$output .= FS::$iMgr->submit("",$this->loc->s("Update"))."<br />";
					$output .= "</form>";
					
					$tmpoutput = FS::$iMgr->h3("Top ".$topmax." (".$this->loc->s("SSH-atk").")",true)."<table><tr><th>".$this->loc->s("IP-addr")."</th><th>".$this->loc->s("Last-visit")."</th><th>".$this->loc->s("Action-nb")."</th></tr>";
					
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."collected_ips","ip,last_date,ssh","",array("order" => "ssh","ordersens" => 1,"limit" => $topmax));
					while($data = $this->snortDB->Fetch($query)) {
						if($found == 0) $found = 1;
						$tmpoutput .= "<tr><td>".$data["ip"]."</td><td>".$data["last_date"]."</td><td>".$data["ssh"]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
						
					$found = 0;
					$tmpoutput = FS::$iMgr->h3($this->loc->s("The")." ".$topmax." ".$this->loc->s("violent-days"),true)."<table><tr><th>".$this->loc->s("Date")."</th><th>".$this->loc->s("Action-nb")."</th></tr>";
					$query = $this->snortDB->Select(PGDbConfig::getDbPrefix()."attack_stats","atkdate,ssh","",array("order" => "ssh","ordersens" => 1,"limit" => $topmax));
					while($data = $this->snortDB->Fetch($query)) {
						if($found == 0) $found = 1;
						$date = preg_split("# #",$data["atkdate"]);
						$tmpoutput .= "<tr><td>".$date[0]."</td><td>".$data["ssh"]."</td></tr>";
					}
					if($found)
						$output .= $tmpoutput."</table>";
				}
				else if($showmodule == 5) {
					$found = false;
					$output .= FS::$iMgr->h3("last-100");
					$query = $this->snortDB->Select("acid_event","sig_name,timestamp,ip_src,ip_dst,ip_proto,layer4_sport,layer4_dport","",array("order" => "timestamp","ordersens" => 1,"limit" => 100));
					while($data = $this->snortDB->Fetch($query)) {
						if(!$found) {
							$found = true;
							$output .= "<table><tr><th>".$this->loc->s("Date")."</th><th>".$this->loc->s("Source")."</th><th>".$this->loc->s("Destination")."</th><th>".$this->loc->s("Alert")."</th></tr>";
						}
						$output .= "<tr><td>".$data["timestamp"]."</td><td>".long2ip($data["ip_src"]).":".$data["layer4_sport"]."</td><td>".long2ip($data["ip_dst"]).":".$data["layer4_dport"].
							"</td><td>".$data["sig_name"]."</td></tr>";
					}
					if($found) $output .= "</table>";
					else $output .= FS::$iMgr->printError($this->loc->s("No-alert-found"));
				}
			}
			return $output;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$ech = FS::$secMgr->checkAndSecurisePostData("ech");
					$ec = FS::$secMgr->checkAndSecurisePostData("ec");
					FS::$iMgr->redir("mod=".$this->mid."&sh=1&ech=".$ech."&ec=".$ec);
					break;
				case 2: case 3: case 4:
					$topmax = FS::$secMgr->checkAndSecurisePostData("max");
					FS::$iMgr->redir("mod=".$this->mid."&sh=".$act."&max=".$topmax."");
					break;
				default: break;
			}
		}

		private $snortDB;
	};
?>
