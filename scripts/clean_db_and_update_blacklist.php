<?php

/*
        * Copyright (C) 2007-2012 Frost Sapphire Studios <http://www.frostsapphirestudios.com/>
        * Copyright (C) 2012 Loïc BLOT, CNRS <http://www.frostsapphirestudios.com/>
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
        require_once(dirname(__FILE__)."/../lib/FSS/FS.main.php");

$snortDB = new FSPostgreSQLMgr();
$snortDB->setConfig("snort",5432,"localhost","snortuser","snort159");
$snortDB->Connect();

function cleanupSnortDB($snortDB) {
	echo "Cleaning up old records from snort\n";
        // On supprime tous les evenements de la base snort vieux de plus de 1 an

	$sql = "SELECT count(*) as ct from event";
        $count = $snortDB->Count("event","cid");
        $totalsnort = $count;
        echo "Total records : ".$totalsnort."\n";

        $year = date("Y");
        $month = date("m");
        $day = date("d");
        $sql_date = $year."-".$month."-".$day." 00:00:00";

        $snortDB->Delete("event","timestamp < (SELECT '".$sql_date."'::timestamp - '1 year'::interval)");

	$count = $snortDB->Count("event","cid");
        echo "Deleted records : ".($totalsnort-$count)."\n";
}

function deleteLocalAlerts($snortDB) {
	// count all records
	$count = $snortDB->Count("acid_event","cid");
        $totalacid = $count;
        echo "Total records : ".$totalacid."\n";

        echo "Cleaning up bad records...\n";

	$snortDB->Delete("acid_event","ip_src between 2175754240 and 2175754495");
        $snortDB->Delete("acid_event","ip_src between 2175779840 and 2175780863");
        $snortDB->Delete("acid_event","ip_src between 175439872 and 175505407");
        $snortDB->Delete("acid_event","ip_src between 2886729728 and 2887843839");
        $snortDB->Delete("acid_event","ip_src between 3232235520 and 3232301055");
	$snortDB->Delete("acid_event","ip_src between 2175762688 and 2175762943");
	$snortDB->Delete("acid_event","ip_src between 3258522368 and 3258522623");
	$snortDB->Delete("acid_event","sig_name = '(snort decoder) Bad Traffic Same Src/Dst IP'");

	$count = $snortDB->Count("acid_event","cid");
        echo "Deleted records : ".($totalacid-$count)."\n";
}

function cleanupAcidDB($snortDB,$cache_interval) {
	$year = date("Y");
	$month = date("m");
	$day = date("d");

        $sql_date = $year."-".$month."-".$day." 00:00:00";
	$sqlcalc = "(SELECT '".$sql_date."'::timestamp - '".$cache_interval." day'::interval)";
	echo "Cleaning up old records from acidbase\n";

	$count = $snortDB->Count("acid_event","cid");
	$totalacid = $count;
        // On nettoie le nmap du cache acidbase
        $snortDB->Delete("acid_event","timestamp < ".$sqlcalc." and sig_name in('SSH Connection Attempt','(snort_decoder) WARNING: ICMP Original IP Header Not IPv4!','(snort decoder) IPV6 truncated header','(snort_decoder) WARNING: TCP Data Offset is less than 5!','(snort_decoder): Truncated Tcp Options','WEB-MISC robots.txt access','MISC MS Terminal Server no encryption session initiation attempt','COMMUNITY WEB-MISC Cisco IOS HTTP Router Management Service Infinite Loop DoS','COMMUNITY WEB-PHP thinkWMS index.php SQL injection attempt','WEB-MISC ICQ Webfront HTTP DOS','WEB-ATTACKS cc command attempt','WEB-MISC /doc/ access','WEB-MISC apache ?M=D directory list attempt','WEB-PHP Setup.php access','WEB-ATTACKS mail command attempt','COMMUNITY WEB-PHP AppServ main.php appserv_root param access','WEB-PHP remote include path','WEB-ATTACKS rm command attempt','WEB-MISC /.... access','WEB-MISC /etc/passwd','COMMUNITY WEB-MISC mod_jrun overflow attempt','WEB-ATTACKS wget command attempt','WEB-ATTACKS ps command attempt','WEB-ATTACKS netcat command attempt','WEB-ATTACKS mail command attempt','WEB-MISC /etc/passwd','WEB-PHP /_admin access','WEB-PHP test.php access','WEB-PHP Mambo upload.php access','WEB-PHP Setup.php access','ICMP PING CyberKit 2.2 Windows','ICMP PING NMAP','(portscan) Open Port','MISC MS Terminal server request')");

	$count = $snortDB->Count("acid_event","cid");
        $totalafter = $count;
        echo "Deleted ".($totalacid-$totalafter)." records\n";
	echo "Total records: ".$totalafter."\n";
        // TODO : autres alertes
}


// CONFIGURATION
$cache_interval = 14; // Intervalle durant lequel les enregistrements sont conservés (jours)
$snort_interval = 1; // nombres d'années pendant lesquelles on garde les enregistrements SNORT en mémoire
// MAIN SCRIPT

	deleteLocalAlerts($snortDB);

	echo "Updating reports\n";

	// On genere une date de 3 mois anterieure
	$year = date("Y");
        $month = date("m");
        $day = date("d");

        $sql_date = $year."-".$month."-".$day." 00:00:00";

	$sqlcalc = "(SELECT '".$sql_date."'::timestamp - '".$cache_interval." day'::interval)";
	// mise à jour du total des anciens enregistrements
	$reportcount = 0;
	// TODO: autres types d'enregistrements
	$sql = "select ip_src,timestamp from acid_event where sig_name in('SSH Connection Attempt','(snort_decoder) WARNING: ICMP Original IP Header Not IPv4!','(snort decoder) IPV6 truncated header','(snort_decoder) WARNING: TCP Data Offset is less than 5!','(snort_decoder): Truncated Tcp Options','WEB-MISC robots.txt access','MISC MS Terminal Server no encryption session initiation attempt','COMMUNITY WEB-MISC Cisco IOS HTTP Router Management Service Infinite Loop DoS','COMMUNITY WEB-PHP thinkWMS index.php SQL injection attempt','WEB-MISC ICQ Webfront HTTP DOS','WEB-ATTACKS cc command attempt','WEB-MISC /doc/ access','WEB-MISC apache ?M=D directory list attempt','WEB-PHP Setup.php access','COMMUNITY WEB-PHP AppServ main.php appserv_root param access','WEB-PHP remote include path','WEB-ATTACKS rm command attempt','WEB-MISC /.... access','WEB-MISC /etc/passwd','COMMUNITY WEB-MISC mod_jrun overflow attempt','WEB-ATTACKS wget command attempt','WEB-ATTACKS ps command attempt','WEB-ATTACKS netcat command attempt','WEB-ATTACKS mail command attempt','WEB-MISC /etc/passwd','WEB-PHP /_admin access','WEB-PHP test.php access','WEB-PHP Mambo upload.php access','WEB-PHP Setup.php access','(portscan) Open Port','ICMP PING CyberKit 2.2 Windows','ICMP PING NMAP','MISC MS Terminal server request') and timestamp < (";
	$sql .= $sqlcalc.") group by ip_src,timestamp order by ip_src";
	$query = pg_query($sql);
        while($data = pg_fetch_array($query)) {
		$count = $snortDB->Count("acid_event","cid","ip_src = '".$data["ip_src"]."' and timestamp < '".$sql_date."' and sig_name in('SSH Connection Attempt')");
                $cssh = $count;

                $count = $snortDB->Count("acid_event","cid","ip_src = '".$data["ip_src"]."' and timestamp < '".$sql_date."' and sig_name in('ICMP PING CyberKit 2.2 Windows','ICMP PING NMAP','(portscan) Open Port')");
                $cscan = $count;

		$count = $snortDB->Count("acid_event","cid","ip_src = '".$data["ip_src"]."' and timestamp < '".$sql_date."' and sig_name in('MISC MS Terminal server request','MISC MS Terminal Server no encryption session initiation attempt')");
                $ctse = $count;

		$count = $snortDB->Count("acid_event","cid","ip_src = '".$data["ip_src"]."' and timestamp < '".$sql_date."' and sig_name in('WEB-MISC robots.txt access','WEB-ATTACKS ps command attempt','WEB-ATTACKS netcat command attempt','COMMUNITY WEB-MISC Cisco IOS HTTP Router Management Service Infinite Loop DoS','COMMUNITY WEB-PHP thinkWMS index.php SQL injection attempt','WEB-MISC ICQ Webfront HTTP DOS','WEB-ATTACKS cc command attempt','WEB-MISC /doc/ access','WEB-MISC apache ?M=D directory list attempt','WEB-PHP Setup.php access','WEB-MISC /etc/passwd','WEB-PHP /_admin access','WEB-PHP test.php access','WEB-PHP Setup.php access','WEB-PHP Mambo upload.php access','WEB-ATTACKS wget command attempt','WEB-ATTACKS mail command attempt','COMMUNITY WEB-PHP AppServ main.php appserv_root param access','WEB-PHP remote include path','WEB-ATTACKS rm command attempt','WEB-MISC /.... access','WEB-MISC /etc/passwd','COMMUNITY WEB-MISC mod_jrun overflow attempt')");
                $cweb = $count;

		$count = $snortDB->Count("acid_event","cid","ip_src = '".$data["ip_src"]."' and timestamp < '".$sql_date."' and sig_name in('(snort_decoder) WARNING: ICMP Original IP Header Not IPv4!','(snort decoder) IPV6 truncated header','(snort_decoder) WARNING: TCP Data Offset is less than 5!','(snort_decoder): Truncated Tcp Options')");
                $cnerr = $count;

		$lastdate = $snortDB->GetMax("acid_event","timestamp","ip_src = '".$data["ip_src"]."' and timestamp < '".$sql_date."' and sig_name in('WEB-MISC robots.txt access','MISC MS Terminal Server no encryption session initiation attempt','WEB-ATTACKS ps command attempt','WEB-ATTACKS netcat command attempt','COMMUNITY WEB-MISC Cisco IOS HTTP Router Management Service Infinite Loop DoS','COMMUNITY WEB-PHP thinkWMS index.php SQL injection attempt','WEB-MISC ICQ Webfront HTTP DOS','WEB-ATTACKS cc command attempt','WEB-MISC /doc/ access','WEB-MISC apache ?M=D directory list attempt','WEB-PHP Setup.php access','WEB-MISC /etc/passwd','WEB-PHP /_admin access','WEB-PHP test.php access','WEB-PHP Mambo upload.php access','WEB-PHP Setup.php access','ICMP PING CyberKit 2.2 Windows','ICMP PING NMAP','(portscan) Open Port','MISC MS Terminal server request','SSH Connection Attempt')");

		// TODO : compter autre chose
		$totalscan = 0;
		$totaltse = 0;
		$totalweb = 0;
		$totalnerr = 0;
		$totalssh = 0;

                $exist = false;
		$query2 = $snortDB->Select("collected_ips","ssh,webaccess,neterrors,tse,scans","ip = '".long2ip($data["ip_src"])."'");
                if($data2 = pg_fetch_array($query2)) {
                        $totalweb = $data2["webaccess"];
			$totalnerr = $data2["neterrors"];
			$totaltse = $data2["tse"];
			$totalscan = $data2["scans"];
			$totalssh = $data2["ssh"];
			$exist = true;
                }

		$totalscan += $cscan;
		$totaltse += $ctse;
		$totalweb += $cweb;
		$totalnerr += $cnerr;
		$totalssh += $cssh;

                if($exist) {
                        $sql2 = "UPDATE collected_ips set ssh = '".$totalssh."', neterrors = '".$totalnerr."', webaccess = '".$totalweb."', scans = '".$totalscan."', tse = '".$totaltse."', last_date = '".$lastdate."' WHERE ip = '".long2ip($data["ip_src"])."'";
                        pg_query($sql2);
                }
		else {
			$sql2 = "INSERT INTO collected_ips(ip,ssh,scans,tse,webaccess,neterrors,last_date) VALUES ('".long2ip($data["ip_src"])."','".$totalssh."','".$totalscan."','".$totaltse."','".$totalweb."','".$totalnerr."','".$lastdate."')";
			pg_query($sql2);
		}

		$reportcount+=($cscan+$ctse+$cweb+$cnerr+$cssh);
        }

	$query = $snortDB->Select("acid_event","timestamp","sig_name in('SSH Connection Attempt','(snort_decoder) WARNING: ICMP Original IP Header Not IPv4!','(snort decoder) IPV6 truncated header','(snort_decoder) WARNING: TCP Data Offset is less than 5!','(snort_decoder): Truncated Tcp Options','WEB-MISC robots.txt access','MISC MS Terminal Server no encryption session initiation attempt','WEB-ATTACKS ps command attempt','WEB-ATTACKS netcat command attempt','WEB-ATTACKS mail command attempt','COMMUNITY WEB-PHP AppServ main.php appserv_root param access','WEB-MISC /etc/passwd','WEB-PHP /_admin access','WEB-PHP test.php access','WEB-PHP Mambo upload.php access','WEB-PHP Setup.php access','ICMP PING CyberKit 2.2 Windows','ICMP PING NMAP','(portscan) Open Port','MISC MS Terminal server request','COMMUNITY WEB-MISC Cisco IOS HTTP Router Management Service Infinite Loop DoS','COMMUNITY WEB-PHP thinkWMS index.php SQL injection attempt','WEB-MISC ICQ Webfront HTTP DOS','WEB-ATTACKS cc command attempt','WEB-MISC /doc/ access','WEB-MISC apache ?M=D directory list attempt','WEB-PHP Setup.php access','WEB-PHP remote include path','WEB-ATTACKS rm command attempt','WEB-MISC /.... access','WEB-MISC /etc/passwd','COMMUNITY WEB-MISC mod_jrun overflow attempt') and timestamp < '".$sql_date."' group by timestamp order by timestamp");
	while($data = pg_fetch_array($query)) {
		$count = $snortDB->Count("acid_event","cid","timestamp = '".$data["timestamp"]."' and sig_name in('SSH Connection Attempt')");
                $nbssh = $count;

		$count = $snortDB->Count("acid_event","cid","timestamp = '".$data["timestamp"]."' and sig_name in('ICMP PING CyberKit 2.2 Windows','ICMP PING NMAP','(portscan) Open Port')");
                $nbscan = $count;

		$count = $snortDB->Count("acid_event","cid","timestamp = '".$data["timestamp"]."' and sig_name in('MISC MS Terminal server request','MISC MS Terminal Server no encryption session initiation attempt')");
                $nbtse = $count;

		$count = $snortDB->Count("acid_event","cid","timestamp = '".$data["timestamp"]."' and sig_name in('WEB-MISC robots.txt access','WEB-ATTACKS ps command attempt','WEB-ATTACKS netcat command attempt','WEB-MISC /etc/passwd','WEB-PHP Mambo upload.php access','WEB-PHP Setup.php access','WEB-PHP test.php access','WEB-PHP /_admin access','WEB-ATTACKS wget command attempt','WEB-ATTACKS mail command attempt','COMMUNITY WEB-PHP AppServ main.php appserv_root param access','WEB-PHP remote include path','WEB-ATTACKS rm command attempt','WEB-MISC /.... access','WEB-MISC /etc/passwd','COMMUNITY WEB-MISC mod_jrun overflow attempt','COMMUNITY WEB-MISC Cisco IOS HTTP Router Management Service Infinite Loop DoS','COMMUNITY WEB-PHP thinkWMS index.php SQL injection attempt','WEB-MISC ICQ Webfront HTTP DOS','WEB-ATTACKS cc command attempt','WEB-MISC /doc/ access','WEB-MISC apache ?M=D directory list attempt','WEB-PHP Setup.php access')");
                $nbweb = $count;

		$count = $snortDB->Count("acid_event","cid","timestamp = '".$data["timestamp"]."' and sig_name in('(snort_decoder) WARNING: ICMP Original IP Header Not IPv4!','(snort decoder) IPV6 truncated header','(snort_decoder) WARNING: TCP Data Offset is less than 5!','(snort_decoder): Truncated Tcp Options')");
                $nbnerr = $count;

		$data["timestamp"] = preg_replace("#[0-9]{2}:[0-9]{2}:[0-9]{2}#","00:00:00",$data["timestamp"]);
		$data["timestamp"] = preg_split("#\.#",$data["timestamp"]);
		$data["timestamp"] = $data["timestamp"][0];

		$exist = false;
		$query2 = $snortDB->Select("attack_stats","scans,tse,ssh,webaccess,neterrors","atkdate = '".$data["timestamp"]."'");
                if($data2 = pg_fetch_array($query2)) {
                        $exist = true;
			$nbscan+=$data2["scans"];
			$nbtse+=$data2["tse"];
			$nbssh+=$data2["ssh"];
			$nbweb+=$data2["webaccess"];
                        $nbnerr+=$data2["neterrors"];
                }
		if($exist) {
			$sql2 = "UPDATE attack_stats set ssh = '".$nbssh."', neterrors = '".$nbnerr."', webaccess = '".$nbweb."', scans = '".$nbscan."', tse = '".$nbtse."' WHERE atkdate = '".$data["timestamp"]."'";
			pg_query($sql2);
		}
		else {
			$sql2 = "INSERT INTO attack_stats(atkdate,ssh,scans,tse,webaccess,neterrors) VALUES ('".$data["timestamp"]."','".$nbssh."','".$nbscan."','".$nbtse."','".$nbweb."','".$nbnerr."')";
			pg_query($sql2);
		}
	}
	echo $reportcount." records reported into archive base\n";

	cleanupAcidDB($snortDB,$cache_interval);
	cleanupSnortDB($snortDB);

	echo "Script Finished !\n";
?>