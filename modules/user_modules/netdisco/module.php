<?php
	require_once(dirname(__FILE__)."/../generic_module.php");
	class iNetdisco extends genModule{
		function iNetdisco($iMgr) { parent::genModule($iMgr); }
		public function Load() {
			$output = "<div id=\"monoComponent\"><h4>Management du service de découverte Netdisco</h4>";
			$err = FS::$secMgr->checkAndSecuriseGetData("err");
			if($err == 1)
				$output .= FS::$iMgr->printError("Les données que vous avez entré ne sont pas valides !");
			$output .= $this->showMainConf();
			$output .= "</div>";
			return $output;
		}

		private function showMainConf() {
			$output = "";
			$output .= FS::$iMgr->addForm("index.php?mod=".$this.mod."&act=1");
			$output .= "<table class=\"standardTable\"><tr><th colspan=\"2\">Configuration globale</th></tr>";
			$file = file("/etc/netdisco/netdisco.conf");
			$dnssuffix = ".local";
			$netdiscodir = "/usr/lib/netdisco/";
			$nodetimeout = 60;
			$devicetimeout = 90;
			$pghost = "127.0.0.1";
			$dbname = "netdiscodb";
			$dbuser = "netdiscouser";
			$dbpwd = "password";
			$snmpro = "public";
			$snmprw = "private";
			$snmptimeout = 1;
			$snmptry = 3;
			$snmpmibs = "/usr/share/netdisco/mibs/";
			$snmpver = 2;

			if(!$file) {
				$output .= FS::$iMgr->printError("Impossible de libre le fichier /etc/netdisco/netdisco.conf");
			} else {
				foreach ($file as $lineNumber => $buf) {
					$buf = trim($buf);
					$buf = str_replace("\t", "", $buf);
					$buf = preg_replace("# #", "", $buf);
					$res = preg_split("#=#",$buf);

					if(count($res) == 2) {
						if(preg_match("#^domain$#",$res[0]))
							$dnssuffix = $res[1];
						else if(preg_match("#^home$#",$res[0]))
							$netdiscodir = $res[1];
						else if(preg_match("#^expire_nodes$#",$res[0]))
							$nodetimeout = $res[1];
						else if(preg_match("#^expire_devices$#",$res[0]))
							$devicetimeout = $res[1];
						else if(preg_match("#^db_Pg_user$#",$res[0]))
							$dbuser = $res[1];
						else if(preg_match("#^db_Pg_pw$#",$res[0]))
							$dbpwd = $res[1];
						else if(preg_match("#^community$#",$res[0]))
							$snmpro = $res[1];
						else if(preg_match("#^community_rw$#",$res[0]))
							$snmprw = $res[1];
						else if(preg_match("#^snmptimeout$#",$res[0]))
							$snmptimeout = $res[1]/1000000;
						else if(preg_match("#^snmpretries$#",$res[0]))
							$snmptry = $res[1];
						else if(preg_match("#^mibhome$#",$res[0]))
							$snmpmibs = $res[1];
						else if(preg_match("#^snmpver$#",$res[0]))
							$snmpver = $res[1];
					} else if(count($res) == 4) {
						if(preg_match("#^db_Pg$#",$res[0])) {
							$pghost = $res[3];
							$nsplit = preg_split("#;#",$res[2]);
							$dbname = $nsplit[0];
						}
					}
				}
				fclose($file);
			}
			$file = fopen("/etc/netdisco/netdisco-topology.txt","r");
			$firstnode = fread($file,filesize("/etc/netdisco/netdisco-topology.txt"));
			fclose($file);
			// @TODO: load configuration file
			$output .= "<tr><td>Suffixe DNS</td><td>".FS::$iMgr->addInput("suffix",$dnssuffix)."</td></tr>";
			$output .= "<tr><td>Répertoire de Netdisco</td><td>".FS::$iMgr->addInput("dir",$netdiscodir)."</td></tr>";
			$output .= "<tr><td>Noeud principal</td><td>".FS::$iMgr->addInput("fnode",$firstnode)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">Configuration des timers</th></tr>";
			$output .= "<tr><td>Expiration des noeuds</td><td>".FS::$iMgr->addInput("nodetimeout",$nodetimeout,4,4)."</td></tr>";
			$output .= "<tr><td>Expiration des périphériques</td><td>".FS::$iMgr->addInput("devicetimeout",$devicetimeout,4,4)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">Base de données</th></tr>";
			$output .= "<tr><td>Hôte PostGRESQL</td><td>".FS::$iMgr->addInput("pghost",$pghost)."</td></tr>";
			$output .= "<tr><td>Nom de la base de données</td><td>".FS::$iMgr->addInput("dbname",$dbname)."</td></tr>";
			$output .= "<tr><td>Utilisateur PostGRESQL</td><td>".FS::$iMgr->addInput("dbuser",$dbuser)."</td></tr>";
			$output .= "<tr><td>Mot de passe</td><td>".FS::$iMgr->addPasswdField("dbpwd",$dbpwd)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">Configuration SNMP</th></tr>";
			$output .= "<tr><td>Communautés en lecture</td><td>".FS::$iMgr->addInput("snmpro",$snmpro)."</td></tr>";
			$output .= "<tr><td>Communautés en écriture</td><td>".FS::$iMgr->addInput("snmprw",$snmprw)."</td></tr>";
			$output .= "<tr><td>Timeout des requêtes</td><td>".FS::$iMgr->addInput("snmptimeout",$snmptimeout,2,2)."</td></tr>";
			$output .= "<tr><td>Tentatives maximales</td><td>".FS::$iMgr->addInput("snmptry",$snmptry,2,2)."</td></tr>";
			$output .= "<tr><td>Version SNMP</td><td>";
			$output .= FS::$iMgr->addList("snmpver");
			$output .= FS::$iMgr->addElementToList("1","1",$snmpver == 1 ? true : false);
			$output .= FS::$iMgr->addElementToList("2c","2",$snmpver == 2 ? true : false);
			$output .= "</select></td></tr>";
			$output .= "<tr><td>Répertoire des MIBS</td><td>".FS::$iMgr->addInput("snmpmibs",$snmpmibs)."</td></tr>";
			$output .= "<tr><th colspan=\"2\">".FS::$iMgr->addSubmit("Enregistrer","Enregistrer")."</th></tr>";
			$output .= "</table></form>";
			return $output;
		}

		public function checkNetdiscoConf($dns,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$snmpmibs,$firstnode) {
			if(!FS::$secMgr->isNumeric($nodetimeout) || !FS::$secMgr->isNumeric($devicetimeout) || !FS::$secMgr->isNumeric($snmptimeout) ||
				!FS::$secMgr->isNumeric($snmptry) || !FS::$secMgr->isNumeric($snmpver))
				return false;

			if($dns == "" || $dir == "" || $pghost == "" || $dbname == "" || $dbuser == "" || $dbpwd == "" || $snmpro == "" || $snmprw == "" || $snmpmibs == "" || $firstnode == "")
				return false;

			if($nodetimeout > 3600 || $nodetimeout < 10 || $devicetimeout > 3600 || $devicetimeout < 10 || $snmptimeout > 30 || $snmptimeout < 1 || $snmptry > 10 ||
				$snmptry < 1 || ($snmpver != 1 && $snmpver != 2))
				return false;

			return true;
		}
		public function writeNetdiscoConf($dns,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$snmpmibs,$firstnode) {
			$file = fopen("/usr/local/etc/netdisco/netdisco.conf","w+");
			fwrite($file,"# ---- General Settings ----\n");
			fwrite($file,"domain = ".$dns."\nhome = ".$dir."\n");
			fwrite($file,"topofile        = /etc/netdisco/netdisco-topology.txt\ntimeout = 90\nmacsuck_timeout = 90\nmacsuck_all_vlans = true\n");
			fwrite($file,"arpnip          = true\n");
			fwrite($file,"\n# -- Database Maintenance and Data Removal --\nexpire_devices = ".$devicetimeout."expire_nodes = ".$nodetimeout."\n");
			fwrite($file,"expire_nodes_archive = 60\n");
			fwrite($file,"\n# ---- Admin Panel Daemon Settings ----\ndaemon_bg       = true\ndaemon_pid      = /var/run/netdisco_daemon.pid\n");
			fwrite($file,"daemon_poll     = 2\n");
			fwrite($file,"\n# ---- Port Control Settings ---vlanctl             = true,portctl_timeout      = 60\n");
			fwrite($file,"\n# Data Archiving and Logging\ncompresslogs    = true\ncompress        = /bin/gzip -f\ndatadir = /var/log/netdisco\n");
			fwrite($file,"logextension    = txt");
			fwrite($file,"\n# ---- Database Settings ----\ndb_Pg = dbi:Pg:dbname=".$dbname.";host=".$pghost."\ndb_Pg_user = ".$dbuser."\ndb_Pg_pw = ".$dbpwd."\n");
			fwrite($file,"db_Pg_opts      = PrintError => 1, AutoCommit => 1\n");
			fwrite($file,"\n# ---- SNMP Settings ----\ncommunity = ".$snmpro."\ncommunity_rw = ".$snmprw."\nsnmptimeout = ".($snmptimeout*1000000)."\n");
			fwrite($file,"snmpretries = ".$snmptry."\nsnmpver = ".$snmpver."\nmibhome = ".$snmpmibs.'\nmibdirs = $mibhome/allied,  $mibhome/asante, $mibhome/cisco, \\'."\n");
			fwrite($file,'$mibhome/foundry, $mibhome/hp,     $mibhome/nortel, $mibhome/extreme, $mibhome/rfc,     $mibhome/net-snmp'."\n".'bulkwalk_off = true'."\n");
			fclose($file);

			$file = fopen("/usr/local/etc/netdisco/netdisco-topology.txt","w+");
			fwrite($file,$firstnode."\n");
			fclose($file);
			return 0;
		}
		
		public function handlePostDatas($act) {
			switch($act) {
				case 1:
					$suffix = FS::$secMgr->checkAndSecurisePostData("suffix");
					$dir = FS::$secMgr->checkAndSecurisePostData("dir");
					$nodetimeout = FS::$secMgr->checkAndSecurisePostData("nodetimeout");
					$devicetimeout = FS::$secMgr->checkAndSecurisePostData("devicetimeout");
					$pghost = FS::$secMgr->checkAndSecurisePostData("pghost");
					$dbname = FS::$secMgr->checkAndSecurisePostData("dbname");
					$dbuser = FS::$secMgr->checkAndSecurisePostData("dbuser");
					$dbpwd = FS::$secMgr->checkAndSecurisePostData("dbpwd");
					$snmpro = FS::$secMgr->checkAndSecurisePostData("snmpro");
					$snmprw = FS::$secMgr->checkAndSecurisePostData("snmprw");
					$snmptimeout = FS::$secMgr->checkAndSecurisePostData("snmptimeout");
					$snmptry = FS::$secMgr->checkAndSecurisePostData("snmptry");
					$snmpver = FS::$secMgr->checkAndSecurisePostData("snmpver");
					$snmpmibs = FS::$secMgr->checkAndSecurisePostData("snmpmibs");
					$fnode = FS::$secMgr->checkAndSecurisePostData("fnode");
					if($this->checkNetdiscoConf($suffix,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$snmpmibs,$fnode) == true) {
						$this->writeNetdiscoConf($suffix,$dir,$nodetimeout,$devicetimeout,$pghost,$dbname,$dbuser,$dbpwd,$snmpro,$snmprw,$snmptimeout,$snmptry,$snmpver,$snmpmibs,$fnode);
						header("Location: m-".$this->mid.".html");
						
						return;
					}
					header("Location: index.php?m=".$this->mid."&err=1");	
					return;
				default: break;
			}
		}
	};
?>