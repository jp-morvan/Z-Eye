#! python
# -*- coding: utf-8 -*-

"""
* Copyright (C) 2011-2013 Loïc BLOT, CNRS <http://www.unix-experience.fr/>
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


def getCIDR(netmask):
	netmask = netmask.split('.')
	binary_str = ''
	for octet in netmask:
		binary_str += bin(int(octet))[2:].zfill(8)
	return str(len(binary_str.rstrip('0')))

def addslashes(s):
	l = ["\\", '"', "'", "\0", ]
	for i in l:
		if i in s:
			s = s.replace(i, '\\'+i)
	return s