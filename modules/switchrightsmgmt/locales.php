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

	class lSwitchRightsMgmt extends FSLocales {
		function lSwitchRightsMgmt() {
			parent::FSLocales();	
			$locales = array(
				"fr" => array(
					"Add" => "Ajouter",
					"All" => "Tous",
					"confirm-remove-backupsrv" => "Êtes vous sûr de vouloir supprimer le serveur de sauvegarde ",
					"confirm-remove-groupright" => "Êtes vous sûr de vouloir supprimer ce droit pour le groupe ",
					"confirm-remove-userright" => "Êtes vous sûr de vouloir supprimer ce droit pour l'utilisateur ",
					"device" => "Equipement",
					"DHCP-Snooping-mgmt" => "Gestion du DHCP Snooping (équipement)",
					"err-already-exist" => "Cet élément existe déjà", 
					"err-bad-datas" => "Erreur sur la requête: certains champs sont manquants ou invalides",
					"err-no-backup-found" => "Aucun serveur de sauvegarde trouvé",
					"err-no-rights" => "Vous n'avez pas le droit de faire cela",
					"err-no-snmp-community" => "Aucune communaut\xc3\xa9 SNMP renseign\xc3\xa9e. Veuillez les configurer",
					"err-not-found" => "Cet élément n'existe pas ou plus",
					"err-snmpgid-not-found" => "La communauté ou le groupe n'existe pas ou plus",
					"Export-cfg" => "Export de la configuration",
					"Filter" => "Filtrer",
					"Go" => "Aller",
					"group-rights" => "Droits des groupes",
					"Groups" => "Groupes",
					"ip-addr" => "Adresse IP",
					"Login" => "Identifiant",
					"menu-title" => "Equipements réseau (droits & sauvegarde)",
					"Modification" => "Modification",
					"New-Server" => "Nouveau serveur",
					"None" => "Aucun",
					"Password" => "Mot de passe",
					"Password-repeat" => "Répétez le mot de passe",
					"Portmod-cdp" => "Modifier l'état CDP (port)",
					"Portmod-dhcpsnooping" => "Modifier les sécurités DHCP (dhcp snooping) (port)",
					"Portmod-portsec" => "Modifier la sécurité (port security) (port)",
					"Portmod-voicevlan" => "Modifier le VLAN voix (port)",
					"Reading" => "Consultation (global)",
					"Read-port-stats" => "Statistiques des ports<br />(Consultation)",
					"Read-ssh-portinfos" => "Lire les informations d'un port (via SSH)",
					"Read-ssh-showstart" => "Lire la configuration de démarrage (via SSH)",
					"Read-ssh-showrun" => "Lire la configuration courante (via SSH)",
					"Read-switch-details" => "Caractéristiques de l'équipement<br />(Consultation)",
					"Read-switch-modules" => "Liste des modules<br />(Consultation)",
					"Read-switch-vlan" => "Liste des vlans d'un équipement<br />(Consultation)",
					"Restore-startup-cfg" => "Restaurer la configuration de démarrage",
					"Retag-vlan" => "Remplacer le tag d'un VLAN",
					"Return" => "Retour",
					"Right" => "Droit",
					"Save" => "Enregistrer",
					"Server" => "Serveur",
					"server-path" => "Chemin sur le serveur",
					"Set-switch-sshpwd" => "Modifier les mots de passe SSH de connexion (équipements)",
					"snmp-community" => "Communauté SNMP",
					"srv-type" => "Type de serveur",
					"title-device-backup" => "Serveurs de sauvegarde",
					"title-edit-backup-switch-server" => "Edition d'un serveur de sauvegarde",
					"title-rightsbysnmp" => "Par communauté SNMP", 
					"title-rightsbyswitch" => "Par équipement",
					"title-switchrightsmgmt" => "Gestion des droits sur les équipements réseau",
					"Type" => "Type",
					"User" => "Utilisateur",
					"user-rights" => "Droits des utilisateurs",
					"Users" => "Utilisateurs",
					"Writing" => "Modification (global)",
					"Write-port-mon" => "Monitoring des ports<br />(Modification)",
				),
				"en" => array(
					"Add" => "Add",
					"All" => "All",
					"confirm-remove-backupsrv" => "Are you sure you want to remove backup server ",
					"confirm-remove-groupright" => "Are you sure you want to remove right for group ",
					"confirm-remove-userright" => "Are you sure you want to remove right for user ",
					"device" => "Device",
					"DHCP-Snooping-mgmt" => "DHCP Snooping Mgmt (device)",
					"err-already-exist" => "This element already exists",
					"err-bad-datas" => "Bad request: some fields are missing or wrong",
					"err-no-backup-found" => "No backup server found",
					"err-no-rights" => "You don't have rights to do that",
					"err-no-snmp-community" => "No SNMP community found. Please configure them before use",
					"err-not-found" => "This element doesn't exist (anymore)",
					"err-snmpgid-not-found" => "This community/group doesn't exist (anymore)",
					"Export-cfg" => "Configuration export",
					"Filter" => "Filter",
					"Go" => "Go",
					"group-rights" => "Group rights",
					"Groups" => "Groups",
					"ip-addr" => "Adresse IP",
					"Login" => "Login",
					"menu-title" => "Network devices (rights & backup)",
					"Modification" => "Modifying",
					"New-Server" => "New Server",
					"None" => "Aucun",
					"Password" => "Password",
					"Password-repeat" => "Repeat password",
					"Portmod-cdp" => "Modify CDP (port)",
					"Portmod-dhcpsnooping" => "Modify DHCP snooping (port)",
					"Portmod-portsec" => "Modify port security (port)",
					"Portmod-voicevlan" => "Modify voice VLAN (port)",
					"Reading" => "Reading (global)",
					"Read-port-stats" => "Reading<br />(port stats)",
					"Read-ssh-portinfos" => "Reading port informations (via SSH)",
					"Read-ssh-showstart" => "Reading startup configuration (via SSH)",
					"Read-ssh-showstart" => "Reading running configuration (via SSH)",
					"Read-switch-details" => "Reading<br />(device features)",
					"Read-switch-modules" => "Reading<br />(device modules list)",
					"Read-switch-vlan" => "Reading<br />(device vlan list)",
					"Restore-startup-cfg" => "Restore startup config",
					"Retag-vlan" => "Replace VLAN tag",
					"Return" => "Return",
					"Right" => "Right",
					"Save" => "Save",
					"Server" => "Server",
					"server-path" => "Server path",
					"Set-switch-sshpwd" => "Modify ssh logins for connecting to devices",
					"snmp-community" => "SNMP community",
					"srv-type" => "Server type",
					"title-device-backup" => "Backup servers",
					"title-edit-backup-switch-server" => "Edit backup server",
					"title-rightsbysnmp" => "By SNMP community",
					"title-rightsbyswitch" => "By device",
					"title-switchrightsmgmt" => "Network devices rights management",
					"Type" => "Type",
					"User" => "User",
					"user-rights" => "User rights",
					"Users" => "Users",
					"Writing" => "Modification",
					"Write-port-mon" => "Modification (port monitoring)",
				)
			);
			$this->concat($locales);
		}
	};
?>