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
    
	class lIcinga extends FSLocales {
		function lIcinga() {
			parent::FSLocales();
			$locales = array(
				"fr" => array(
					"active-check-en" => "Vérifications actives",
					"Add" => "Ajouter",
					"Address" => "Adresse",
					"Alias" => "Alias",
					"alivecommand" => "Commande de vérification (présence)",
					"checkcmd" => "Commande de vérification",
					"check-freshness" => "Vérifier la fraîcheur",
					"check-interval" => "Vérification (min)",
					"checkperiod" => "Période de vérification",
					"Command" => "Commande",
					"Commands" => "Commandes",
					"confirm-remove-command" => "Êtes vous sûr de vouloir supprimer la commande ",
					"confirm-remove-contact" => "Êtes vous sûr de vouloir supprimer le contact ",
					"confirm-remove-contactgroup" => "Êtes vous sûr de vouloir supprimer le groupe de contacts ",
					"confirm-remove-host" => "Êtes vous sûr de vouloir supprimer l'hôte ",
					"confirm-remove-hostgroup" => "Êtes vous sûr de vouloir supprimer le groupe d'hôtes ",
					"confirm-remove-service" => "Êtes vous sûr de vouloir supprimer le service ",
					"confirm-remove-timeperiod" => "Êtes vous sur de vouloir supprimer la période temporelle ",
					"Contactgroups" => "Groupes de contacts",
					"Contacts" => "Contacts",
					"Description" => "Description",
					"DisplayName" => "Nom d'étiquette",
					"Email" => "E-Mail",
					"err-bad-data" => "Données manquantes ou invalides",
					"err-binary-not-found" => "Le binaire spécifié n'existe pas ou n'est pas utilisable",
					"err-binary-used" => "La commande est utilisée, retirez les liens avant de la supprimer",
					"err-contact-used" => "Le contact est utilisé autre part. Retirez le lien avant de le supprimer.",
					"err-ctg-used" => "Le groupe de contacts est utilisé autre part. Retirez les liens avant de le supprimer",
					"err-data-exist" => "La donnée existe déjà",
					"err-data-not-exist" => "La donnée n'existe pas",
					"err-fail-writecfg" => "Impossible d'écrire la configuration d'Icinga. Les droits sont peut être incorrects sur le serveur.",
					"err-hg-used" => "Le groupe d'hôtes est utilisé autre part. Retirez les liens avant de le supprimer.",
					"err-not-found" => "Non trouvé",
					"err-no-cmd" => "Aucune commande spécifiée",
					"err-no-contact" => "Ce contact n'existe pas ou est invalide",
					"err-no-contactgroups" => "Aucun groupe de contacts configuré ! Il n'est pas possible de créer cet élément",
					"err-no-host" => "Cet hôte n'existe pas ou est invalide",
					"err-no-hostgroup" => "Ce groupe d'hôtes n'existe pas ou est invalide",
					"err-no-hosts" => "Aucun hôte configuré ! Il n'est pas possible de créer cet élément",
					"err-no-right" => "Vous n'avez pas le droit de faire cela !",
					"err-no-service" => "Ce service n'existe pas ou est invalide",
					"err-no-timeperiod" => "Aucune période temporelle configurée ! Il n'est pas possible de créer cet élément",
					"eventhdl-en" => "Gestionnaire d'évènements",
					"failpredict-en" => "Prévision de panne",
					"fail-tab" => "Impossible de charger l'onglet, le lien peut être faux ou la page indisponible",
					"flap-en" => "Détection d'instabilité",
					"Friday" => "Vendredi",
					"From" => "De",
					"General" => "Général",
					"hostnotifcmd" => "Commande de notification (hôtes)",
					"hostnotifperiod" => "Période de notification (hôtes)",
					"hostoptdown" => "Notification sur évènement (Hôte Down)",
					"hostoptflap" => "Notification sur évènement (Hôte Instable)",
					"hostoptrec" => "Notification sur évènement (Hôte de retour)",
					"hostoptsched" => "Notification sur évènement (Hôte Up/Down planifié)",
					"hostoptunreach" => "Notification sur évènement (Hôte non joignable)",
					"Host" => "Hôte",
					"Hosts" => "Hôtes",
					"Hostgroup" => "Groupe d'hôtes",
					"Hostgroups" => "Groupes d'hôtes",
					"Hosttype" => "Type d'hôte",
					"Icon" => "Icône",
					"is-template" => "Modèle ?",
					"max-check" => "Vérifications max (avant notification)",
					"Members" => "Membres",
					"menu-name" => "Moteur Z-Eye",
					"menu-title" => "Moteur de supervision Icinga",
					"Modification" => "Modification",
					"Monday" => "Lundi",
					"new-cmd" => "Nouvelle commande",
					"new-contact" => "Nouveau contact",
					"new-contactgroup" => "Nouveau groupe de contacts",
					"new-host" => "Nouvel hôte",
					"new-hostgroup" => "Nouveau groupe d'hôtes",
					"new-service" => "Nouveau service",
					"new-timeperiod" => "Nouvelle période temporelle",
					"No" => "Non",
					"None" => "Aucun",
					"not-implemented" => "Pas encore implémenté !",
					"notif-en" => "Notifications",
					"notif-interval" => "Intervalle de notification",
					"notifperiod" => "Période de notification",
					"obs-over-srv" => "Service nécessaire",
					"Option" => "Option",
					"parallel-check" => "Vérifications en parallèle",
					"Parent" => "Parent",
					"passive-check-en" => "Vérifications passives",
					"perfdata" => "Vérification des performances",
					"Periods" => "Périodes",
					"retainstatus" => "Garder les infos de status (reboot Z-Eye)",
					"retainnonstatus" => "Garder les infos hors status (reboot Z-Eye)",
					"retry-check-interval" => "Revérification (min)",
					"rule-modify-cmd" => "Modifier les commandes",
					"rule-modify-contact" => "Modifier les contacts",
					"rule-modify-ctg" => "Modifier les groupes de contacts",
					"rule-modify-hg" => "Modifier les groupes d'hôtes",
					"rule-modify-host" => "Modifier les hôtes",
					"rule-modify-service" => "Modifier les services",
					"rule-modify-timeperiod" => "Modifier les périodes temporelles",
					"Saturday" => "Samedi",
					"Save" => "Enregistrer",
					"Services" => "Services",
					"srvnotifcmd" => "Commande de notification (services)",
					"srvnotifperiod" => "Période de notification (services)",
					"srvoptcrit" => "Notification sur évènement (Service critique)",
					"srvoptflap" => "Notification sur évènement (Service instable)",
					"srvoptrec" => "Notification sur évènement (Service de retour)",
					"srvoptsched" => "Notification sur évènement (Service Up/Down planifié)",
					"srvoptunreach" => "Notification sur évènement (service non joignable)",
					"srvoptwarn" => "Notification sur évènement (Service en alerte)",
					"Sunday" => "Dimanche",
					"Template" => "Modèle",
					"Thursday" => "Jeudi",
					"Timeperiods" => "Périodes temporelles",
					"title-cmd-edit" => "Edition d'une commande",
					"title-edit-contact" => "Edition d'un contact",
					"title-edit-contactgroup" => "Edition d'un groupe de contacts",
					"title-edit-service" => "Edition d'un service",
					"title-host-edit" => "Edition d'hôte",
					"title-hostgroup-edit" => "Edition d'un groupe d'hôtes",
					"title-icinga" => "Gestion du moniteur de services Icinga",
					"To" => "à",
					"tooltip-cmd" => "Commande systeme.<br />Nécessite le chemin absolu du binaire.<br />\$USER1\$ correspond au chemin /usr/local/libexec/nagios/.<br />\$ARG1\$ au premier argument<br />\$HOSTNAME\$ au nom d'hôte, ou à l'adresse IP",
					"tooltip-cmdname" => "Un nom de commande (chiffres, lettres, '-_' uniquement)",
					"Tuesday" => "Mardi",
					"Value" => "Valeur",
					"Wednesday" => "Mercreci",
					"Yes" => "Oui",
				),
				"en" => array(
					"active-check-en" => "Active checks",
					"Add" => "Add",
					"Address" => "Address",
					"Alias" => "Alias",
					"alivecommand" => "Alive presence command",
					"checkcmd" => "Check command",
					"check-freshness" => "Check freshness",
					"check-interval" => "Check interval (min)",
					"checkperiod" => "Check period",
					"Commands" => "Commands",
					"Confirm" => "Confirm",
					"confirm-remove-command" => "Are you sure you want to remove command ",
					"confirm-remove-contact" => "Are you sure you want to remove contact ",
					"confirm-remove-contactgroup" => "Are you sure you want to remove contactgroup ",
					"confirm-remove-host" => "Are you sure you want to remove host ",
					"confirm-remove-hostgroup" => "Are you sure you want to remove hostgroup ",
					"confirm-remove-service" => "Are you sure you want to remove service ",
					"confirm-remove-timeperiod" => "Are you sure you want to remove timeperiod ",
					"Contactgroups" => "Contact Groups",
					"Contacts" => "Contacts",
					"Description" => "Description",
					"DisplayName" => "Display Name",
					"Email" => "E-Mail",
					"err-bad-data" => "Bad/Missing datas",
					"err-binary-not-found" => "Binary not found or not usable",
					"err-binary-used" => "Command in use. Please remove links with this command.",
					"err-contact-used" => "Contact in use. Please remove links with this contact.",
					"err-ctg-used" => "Contactgroup in use. Please remove links with this contactgroup.",
					"err-data-exist" => "Data already exists",
					"err-data-not-exist" => "Data doesn't exist",
					"err-fail-writecfg" => "Unable to write icinga configuration. Write rights may be wrong on the server.",
					"err-hg-used" => "Hostgroup in use. Please remove links with this hostgroup.",
					"err-not-found" => "Not found",
					"err-no-cmd" => "No command specified",
					"err-no-contact" => "This contact doesn't exist or is invalid",
					"err-no-contactgroups" => "No contactgroup configured ! It's impossible to create this element",
					"err-no-host" => "This host doesn't exist or is invalid",
					"err-no-hostgroup" => "This hostgroup doesn't exist or is invalid",
					"err-no-hosts" => "No hosts configured ! It's impossible to create this element",
					"err-no-right" => "You cannot do that !",
					"err-no-service" => "This service doesn't exist or is invalid",
					"err-no-timeperiod" => "No timeperiod configured ! It's impossible to create this element",
					"eventhdl-en" => "Event handler",
					"failpredict-en" => "Fail prediction",
					"fail-tab" => "Unable to load tab, link may be wrong or page unavailable",
					"flap-en" => "Flap enable",
					"Friday" => "Friday",
					"From" => "From",
					"General" => "General",
					"hostnotifcmd" => "Notification command (hosts)",
					"hostnotifperiod" => "Notification period (hosts)",
					"hostoptdown" => "Notification (Host down)",
					"hostoptflap" => "Notification (Host flapping)",
					"hostoptrec" => "Notification (Host recovery)",
					"hostoptsched" => "Notification (Host schedule Up/Down)",
					"hostoptunreach" => "Notification (Host unreachable)",
					"Host" => "Hôte",
					"Hosts" => "Hôtes",
					"Hostgroup" => "Hostgroup",
					"Hostgroups" => "Hostgroups",
					"Hosttype" => "Host type",
					"Icon" => "Icon",
					"is-template" => "Template ?",
					"max-check" => "Max checks (before notification)",
					"Members" => "Membres",
					"menu-title" => "Z-Eye Engine",
					"menu-title" => "Icinga supervision engine",
					"Modification" => "Modification",
					"Monday" => "Monday",
					"new-cmd" => "New command",
					"new-contact" => "New contact",
					"new-contactgroup" => "New contact group",
					"new-host" => "New host",
					"new-hostgroup" => "New hostgroup",
					"new-service" => "New service",
					"new-timeperiod" => "New timeperiod",
					"No" => "No",
					"None" => "None",
					"not-implemented" => "Not implemented yet !",
					"notif-en" => "Notifications",
					"notif-interval" => "Notification interval",
					"notifperiod" => "Notification period",
					"obs-over-srv" => "Necessary service",
					"Option" => "Option",
					"parallel-check" => "Parallel checks",
					"Parent" => "Parent",
					"passive-check-en" => "Passive checks",
					"perfdata" => "Check performance data",
					"Periods" => "Periods",
					"retainstatus" => "Keep status infos (Z-Eye reboot)",
					"retainnonestatus" => "Keep non-status infos (Z-Eye reboot)",
					"retry-check-interval" => "Retry (min)",
					"rule-modify-cmd" => "Modify commands",
					"rule-modify-ctg" => "Modify contact groups",
					"rule-modify-contact" => "Modify contacts",
					"rule-modify-hg" => "Modify hostgroups",
					"rule-modify-host" => "Modify hosts",
					"rule-modify-service" => "Modify services",
					"rule-modify-timeperiod" => "Modify timeperiods",
					"Saturday" => "Saturday",
					"Save" => "Save",
					"Services" => "Services",
					"srvnotifcmd" => "Notification command (services)",
					"srvnotifperiod" => "Notification period (services)",
					"srvoptcrit" => "Notification (Service is critical)",
					"srvoptflap" => "Notification (Service flapping)",
					"srvoptrec" => "Notification (Service ecovery)",
					"srvoptsched" => "Notification (Service schedule Up/Down)",
					"srvoptunreach" => "Notification (Service unreachable)",
					"srvoptwarn" => "Notification (Service warning)",
					"Sunday" => "Sunday",
					"Template" => "Template",
					"Thursday" => "Thursday",
					"Timeperiods" => "Timeperiods",
					"title-cmd-edit" => "Command edit",
					"title-edit-contact" => "Contact edit",
					"title-edit-contactgroup" => "Contactgroup edit",
					"title-edit-service" => "Service edit",
					"title-host-edit" => "Host edit",
					"title-hostgroup-edit" => "Hostgroup edit",
					"title-icinga" => "Icinga services monitor management",
					"To" => "To",
					"tooltip-cmd" => "System command.<br />Needs absolute binary path.<br />\$USER1\$ is path: /usr/local/libexec/nagios/.<br />\$ARG1\$ is the first arg...<br />\$HOSTNAME\$ is host name or IP address",
					"tooltip-cmdname" => "Command name with numbers, letters and '-_' only",
					"Tuesday" => "Tuesday",
					"Value" => "Value",
					"Wednesday" => "Wednesday",
					"Yes" => "Yes",
				)
			);
			$this->concat($locales);
		}
	};
?>
