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

from pyPgSQL import PgSQL
import datetime,re,sys,time,thread,threading,subprocess
from threading import Lock

import Logger
import netdiscoCfg

class ZEyeMRTGDiscoverer(threading.Thread):
	sleepingTimer = 0
	defaultSNMPRO = "public"
	startTime = 0
	threadCounter = 0
	tc_mutex = Lock()

	def __init__(self):
		""" 30 mins between two discover """
		self.sleepingTimer = 30*60
		threading.Thread.__init__(self)

	def run(self):
		Logger.ZEyeLogger().write("MRTG Config discoverer launched")
		while True:
			self.launchCfgGenerator()
			time.sleep(self.sleepingTimer)

	def incrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter = self.threadCounter + 1
		self.tc_mutex.release()

	def decrThreadNb(self):
		self.tc_mutex.acquire()
		self.threadCounter = self.threadCounter - 1
		self.tc_mutex.release()

	def getThreadNb(self):
		val = 0
		self.tc_mutex.acquire()
		val = self.threadCounter
		self.tc_mutex.release()
		return val

	def launchCfgGenerator(self):
		Logger.ZEyeLogger().write("MRTG configuration discovery started")
		starttime = datetime.datetime.now()
		try:
			pgsqlCon = PgSQL.connect(host=netdiscoCfg.pgHost,user=netdiscoCfg.pgUser,password=netdiscoCfg.pgPwd,database=netdiscoCfg.pgDB)
			pgcursor = pgsqlCon.cursor()
			pgcursor.execute("SELECT ip,name FROM device ORDER BY ip")
			try:
				pgres = pgcursor.fetchall()
				for idx in pgres:
					pgcursor2 = pgsqlCon.cursor()
					pgcursor2.execute("SELECT snmpro FROM z_eye_snmp_cache where device = '%s'" % idx[1])
					pgres2 = pgcursor2.fetchone()
			
					devip = idx[0]
					devname = idx[1]
					if pgres2:
						devcom = pgres2[0]
					else:
						devcom = self.defaultSNMPRO
					thread.start_new_thread(self.fetchMRTGInfos,(devip,devname,devcom))
			except StandardError, e:
				Logger.ZEyeLogger().write("MRTG-Config-Discovery: FATAL %s" % e)
				return
				
		except PgSQL.Error, e:
			Logger.ZEyeLogger().write("MRTG-Config-Discovery: FATAL PgSQL %s" % e)
			sys.exit(1);	

		finally:
			if pgsqlCon:
				pgsqlCon.close()

		# We must wait 1 sec, because fast it's a fast algo and threadCounter hasn't increased. Else function return whereas it runs
		time.sleep(1)
		while self.getThreadNb() > 0:
			Logger.ZEyeLogger().write("MRTG configuration discovery waiting %d threads" % self.getThreadNb())
			time.sleep(1)

		totaltime = datetime.datetime.now() - starttime
		Logger.ZEyeLogger().write("MRTG configuration discovery done (time: %s)" % totaltime)

	def fetchMRTGInfos(self,ip,devname,devcom):
		self.incrThreadNb()

		try:
			text = subprocess.check_output(["/usr/local/bin/perl","/usr/local/bin/cfgmaker", "%s@%s" % (devcom,ip)])
			text += "\nWorkDir: /usr/local/www/z-eye/datas/rrd"
			cfgfile = open("/usr/local/www/z-eye/datas/mrtg-config/mrtg-%s.cfg" % devname,"w")
			cfgfile.writelines(text)
			cfgfile.close()
		except Exception, e:
			Logger.ZEyeLogger().write("MRTG-Config-Discovery: FATAL %s" % e)
		self.decrThreadNb()
