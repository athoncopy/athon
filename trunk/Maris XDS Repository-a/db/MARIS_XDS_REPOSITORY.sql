-- phpMyAdmin SQL Dump
-- version 2.10.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generato il: 06 Set, 2007 at 08:55 
-- Versione MySQL: 5.0.41
-- Versione PHP: 4.4.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- 
-- Database: 'XDS_REP_ORACLE'
-- 

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'ATNA'
-- 

CREATE TABLE ATNA (
  ID int(11) NOT NULL auto_increment,
  HOST varchar(100) NOT NULL default '',
  PORT varchar(20) NOT NULL default '',
  ACTIVE char(1) NOT NULL default 'A',
  DESCRIPTION varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID),
  KEY ACTIVE (ACTIVE)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'ATNA'
-- 

INSERT INTO ATNA VALUES (1, '10.135.0.91', '4000', 'O', 'ATNA NODE');

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'AUDITABLEEVENT'
-- 

CREATE TABLE AUDITABLEEVENT (
  ID int(64) NOT NULL auto_increment,
  OBJECTTYPE varchar(32) default 'AuditableEvent',
  EVENTTYPE varchar(128) NOT NULL default '',
  REGISTRYOBJECT varchar(255) NOT NULL default '',
  TIME_STAMP timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `SOURCE` varchar(64) NOT NULL default '',
  PRIMARY KEY  (ID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'AUDITABLEEVENT'
-- 


-- --------------------------------------------------------

-- 
-- Struttura della tabella `CONFIG`
-- 

CREATE TABLE `CONFIG` (
  `WWW` varchar(100) NOT NULL default '',
  `LOG` char(1) NOT NULL default '0',
  `CACHE` char(1) NOT NULL default '0',
  `FILES` char(1) NOT NULL default '0',
  `JAVA_PATH` varchar(255) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella `CONFIG`
-- 

INSERT INTO `CONFIG` (`WWW`, `LOG`, `CACHE`, `FILES`, `JAVA_PATH`) VALUES 
('/repository/', 'O', 'O', 'O', '/usr/lib/jvm/java-1.5.0-sun-1.5.0.11/bin/');


-- --------------------------------------------------------

-- 
-- Struttura della tabella 'DOCUMENTS'
-- 

CREATE TABLE DOCUMENTS (
  KEY_PROG int(11) NOT NULL auto_increment,
  XDSDOCUMENTENTRY_UNIQUEID varchar(255) NOT NULL default '',
  `DATA` datetime NOT NULL default '0000-00-00 00:00:00',
  URI varchar(128) NOT NULL,
  PRIMARY KEY  (KEY_PROG),
  KEY XDSDOCUMENTENTRY_UNIQUEID (XDSDOCUMENTENTRY_UNIQUEID)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'DOCUMENTS'
-- 


-- --------------------------------------------------------

-- 
-- Struttura della tabella 'HTTP'
-- 

CREATE TABLE HTTP (
  HTTPD varchar(20) NOT NULL default '',
  ACTIVE char(1) NOT NULL default 'O',
  KEY ACTIVE (ACTIVE)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'HTTP'
-- 

INSERT INTO HTTP VALUES ('TLS', 'O');
INSERT INTO HTTP VALUES ('NORMAL', 'A');

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'KNOWN_SOUCES_IDS'
-- 

CREATE TABLE KNOWN_SOUCES_IDS (
  ID int(20) NOT NULL auto_increment,
  XDSSUBMISSIONSET_SOURCEID varchar(255) NOT NULL default '',
  SOURCE_DESCRIPTION varchar(255) NOT NULL default '',
  PRIMARY KEY  (ID,XDSSUBMISSIONSET_SOURCEID),
  KEY `VALUE` (XDSSUBMISSIONSET_SOURCEID)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'KNOWN_SOUCES_IDS'
-- 

INSERT INTO KNOWN_SOUCES_IDS VALUES (31, 'DCM4CHEE', 'DCM4CHEE');
INSERT INTO KNOWN_SOUCES_IDS VALUES (32, 'testkit', 'testkit');
INSERT INTO KNOWN_SOUCES_IDS VALUES (33, 'Script_source', 'Script_source');

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'MIMETYPE'
-- 

CREATE TABLE MIMETYPE (
  `CODE` varchar(255) NOT NULL default '',
  EXTENSION varchar(125) NOT NULL default '',
  KEY `CODE` (`CODE`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'MIMETYPE'
-- 

INSERT INTO MIMETYPE VALUES ('application/pdf', 'pdf');
INSERT INTO MIMETYPE VALUES ('text/x-cda-r2+xml', 'xml');
INSERT INTO MIMETYPE VALUES ('text/xml', 'xml');
INSERT INTO MIMETYPE VALUES ('application/x-hl7', 'hl7');
INSERT INTO MIMETYPE VALUES ('application/dicom', 'dcm');
INSERT INTO MIMETYPE VALUES ('text/plain', 'txt');
INSERT INTO MIMETYPE VALUES ('multipart/related', 'mr');
INSERT INTO MIMETYPE VALUES ('text/x-cdar2+xml', 'xml');

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'REGISTRY'
-- 

CREATE TABLE REGISTRY (
  ID bigint(30) NOT NULL auto_increment,
  HOST varchar(250) NOT NULL default '',
  PORT int(100) NOT NULL default '0',
  PATH varchar(255) NOT NULL default '',
  ACTIVE char(1) NOT NULL default 'O',
  HTTP varchar(30) NOT NULL default 'NORMAL',
  SERVICE varchar(255) NOT NULL default 'SUBMISSION',
  DESCRIPTION text NOT NULL,
  PRIMARY KEY  (ID),
  KEY ACTIVE (ACTIVE),
  KEY SERVICE (SERVICE),
  KEY HTTP (HTTP)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'REGISTRY'
-- 

INSERT INTO REGISTRY VALUES (1, '10.135.0.92', 80, '/registry/registry.php', 'A', 'NORMAL', 'SUBMISSION', 'REGISTRY');

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'REPOSITORY'
-- 

CREATE TABLE REPOSITORY (
  ID bigint(30) NOT NULL auto_increment,
  HOST varchar(250) NOT NULL default '',
  PORT int(100) NOT NULL default '80',
  SERVICE varchar(255) NOT NULL default '',
  ACTIVE char(1) NOT NULL default 'A',
  HTTP varchar(30) NOT NULL default 'NORMAL',
  PRIMARY KEY  (ID),
  KEY SERVICE (SERVICE,ACTIVE),
  KEY HTTP (HTTP)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'REPOSITORY'
-- 

INSERT INTO REPOSITORY VALUES (1, '10.135.0.92', 80, 'SUBMISSION', 'A', 'NORMAL');

-- --------------------------------------------------------

-- 
-- Struttura della tabella 'USERS'
-- 

CREATE TABLE USERS (
  LOGIN varchar(30) NOT NULL default '',
  `PASSWORD` varchar(50) NOT NULL default '',
  PRIMARY KEY  (LOGIN)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- 
-- Dump dei dati per la tabella 'USERS'
-- 

INSERT INTO USERS VALUES ('marisxds', 'xdSwGC7.aBWxk');
