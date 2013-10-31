# -*- coding: utf-8 -*-

"""
* Copyright (C) 2010-2012 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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
"""

import os,re,time,threading,logging,ZEyeUtil

class ZEyeServiceMgr(ZEyeUtil.Thread):

	def __init__(self):
		self.sleepingTimer = 15
		ZEyeUtil.Thread.__init__(self)

	def run(self):
		self.logger.write("ServiceMgr launched")
		while True:
			self.restartIcinga()
			self.restartSnort()
			time.sleep(self.sleepingTimer)

	def restartIcinga(self):
		if os.path.exists("/tmp/icinga_restart"):
			icingaFile = open("/tmp/icinga_restart", 'r')
			if icingaFile:
				rf = icingaFile.read()
				rf = re.sub("\n","",rf)
				if rf == "1":
					self.logger.write("ServiceMgr restarted Icinga")
					cmd = "service icinga restart"
					pipe = os.popen(cmd, 'r')
					text = pipe.read()
					pipe.close()
					os.remove("/tmp/icinga_restart")
				icingaFile.close()
			
	def restartSnort(self):
		if os.path.exists("/tmp/snort_restart"):
			snortFile = open("/tmp/snort_restart", 'r')
			if snortFile:
				rf = snortFile.read()
				rf = re.sub("\n","",rf)
				if rf == "1":
					self.logger.write("ServiceMgr restarted SNORT")
					cmd = "service snort restart"
					pipe = os.popen(cmd, 'r')
					text = pipe.read()
					pipe.close()
					os.remove("/tmp/snort_restart")
				snortFile.close()
