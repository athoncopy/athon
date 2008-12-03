CREATE SEQUENCE "ASSOCIATION_0";
CREATE SEQUENCE "ATNA_0";
CREATE SEQUENCE "AUDITABLEEVENT_0";
CREATE SEQUENCE "CLASSIFICATION_0";
CREATE SEQUENCE "DESCRIPTION_0";
CREATE SEQUENCE "EXTERNALIDENTIFIER_0";
CREATE SEQUENCE "EXTRINSICOBJECT_0";
CREATE SEQUENCE "HL7_MESSAGES_0";
CREATE SEQUENCE "NAME_0";
CREATE SEQUENCE "PATIENT_0";
CREATE SEQUENCE "REGISTRYPACKAGE_0";
CREATE SEQUENCE "REGISTRY_0";
CREATE SEQUENCE "SLOT_0";
CREATE SEQUENCE "STATS_PK_SEQ";

CREATE TABLE "ASSOCIATION" (
    "KEY_PROG" NUMBER(19, 0),
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(128) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "ASSOCIATIONTYPE" VARCHAR2(128) NOT NULL ENABLE,
    "SOURCEOBJECT" VARCHAR2(64) NOT NULL ENABLE,
    "TARGETOBJECT" VARCHAR2(128) NOT NULL ENABLE,
    "ISCONFIRMEDBYSOURCEOWNER" NUMBER(3, 0) NOT NULL ENABLE,
    "ISCONFIRMEDBYTARGETOWNER" NUMBER(3, 0) NOT NULL ENABLE
);

ALTER TABLE "ASSOCIATION" MODIFY ("OBJECTTYPE" DEFAULT 'Association');

ALTER TABLE "ASSOCIATION" MODIFY ("ISCONFIRMEDBYSOURCEOWNER" DEFAULT 0 );

ALTER TABLE "ASSOCIATION" MODIFY ("ISCONFIRMEDBYTARGETOWNER" DEFAULT 0 );

CREATE UNIQUE INDEX "PRIMARY00000" ON "ASSOCIATION" ("KEY_PROG" , "ID" );

CREATE INDEX "TARGETOBJECT" ON "ASSOCIATION" ("TARGETOBJECT" );

CREATE INDEX "SOURCEOBJECT" ON "ASSOCIATION" ("SOURCEOBJECT" );

CREATE UNIQUE INDEX "ID" ON "ASSOCIATION" ("ID" );

ALTER TABLE "ASSOCIATION" ADD  CONSTRAINT "PRIMARY00000" PRIMARY KEY ("KEY_PROG", "ID");

CREATE TABLE "ATNA" (
    "ID" NUMBER(11, 0),
    "HOST" VARCHAR2(100) NOT NULL ENABLE,
    "PORT" VARCHAR2(20) NOT NULL ENABLE,
    "ACTIVE" VARCHAR2(1) NOT NULL ENABLE,
    "DESCRIPTION" VARCHAR2(255) NOT NULL ENABLE
);

ALTER TABLE "ATNA" MODIFY ("ACTIVE" DEFAULT 'A' );

CREATE UNIQUE INDEX "PRIMARY00001" ON "ATNA" ("ID" );

CREATE INDEX "ACTIVE" ON "ATNA" ("ACTIVE" );

ALTER TABLE "ATNA" ADD  CONSTRAINT "PRIMARY00001" PRIMARY KEY ("ID");

CREATE TABLE "AUDITABLEEVENT" (
    "ID" NUMBER(11, 0),
    "OBJECTTYPE" VARCHAR2(32),
    "EVENTTYPE" VARCHAR2(128) NOT NULL ENABLE,
    "REGISTRYOBJECT" VARCHAR2(64) NOT NULL ENABLE,
    "TIMESTAMP" DATE NOT NULL ENABLE,
    "USER" VARCHAR2(64) NOT NULL ENABLE
);


ALTER TABLE "AUDITABLEEVENT" MODIFY ("OBJECTTYPE" DEFAULT 'AuditableEvent');

CREATE UNIQUE INDEX "PRIMARY00002" ON "AUDITABLEEVENT" ("ID" );

ALTER TABLE "AUDITABLEEVENT" ADD  CONSTRAINT "PRIMARY00002" PRIMARY KEY ("ID");

CREATE TABLE "CLASSCODE" (
    "CODE" VARCHAR2(255) NOT NULL ENABLE,
    "DISPLAY" VARCHAR2(255) NOT NULL ENABLE,
    "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE
);

CREATE TABLE "CLASSIFICATION" (
    "KEY_PROG" NUMBER(19, 0),
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(128) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "CLASSIFICATIONNODE" VARCHAR2(128),
    "CLASSIFICATIONSCHEME" VARCHAR2(128),
    "CLASSIFIEDOBJECT" VARCHAR2(128) NOT NULL ENABLE,
    "NODEREPRESENTATION" VARCHAR2(128)
);

ALTER TABLE "CLASSIFICATION" MODIFY ("OBJECTTYPE" DEFAULT 'Classification');

ALTER TABLE "CLASSIFICATION" MODIFY ("NODEREPRESENTATION" DEFAULT NULL );

CREATE UNIQUE INDEX "PRIMARY00003" ON "CLASSIFICATION" ("KEY_PROG" );

CREATE INDEX "CLASSIFIEDOBJECT" ON "CLASSIFICATION" ("CLASSIFIEDOBJECT" , "NODEREPRESENTATION" );

ALTER TABLE "CLASSIFICATION" ADD  CONSTRAINT "PRIMARY00003" PRIMARY KEY ("KEY_PROG");

CREATE TABLE "CLASSIFICATIONNODE" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "CODE" VARCHAR2(64),
    "PARENT" VARCHAR2(64),
    "PATH" VARCHAR2(255),
    "NAME_VALUE" CLOB);

ALTER TABLE "CLASSIFICATIONNODE" MODIFY ("OBJECTTYPE" DEFAULT 'ClassificationNode');

CREATE UNIQUE INDEX "PRIMARY00004" ON "CLASSIFICATIONNODE" ("ID" );

ALTER TABLE "CLASSIFICATIONNODE" ADD  CONSTRAINT "PRIMARY00004" PRIMARY KEY ("ID");

CREATE TABLE "CLASSIFICATIONSCHEME" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "EXPIRATION" DATE NOT NULL ENABLE,
    "MAJORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "MINORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "STABILITY" VARCHAR2(128),
    "STATUS" VARCHAR2(128) NOT NULL ENABLE,
    "USERVERSION" VARCHAR2(64),
    "ISINTERNAL" NUMBER(3, 0) NOT NULL ENABLE,
    "NODETYPE" VARCHAR2(32) NOT NULL ENABLE,
    "NAME_VALUE" VARCHAR2(255) NOT NULL ENABLE,
    "DESCRIPTION_VALUE" CLOB
);

ALTER TABLE "CLASSIFICATIONSCHEME" MODIFY ("OBJECTTYPE" DEFAULT 'ClassificationScheme');

ALTER TABLE "CLASSIFICATIONSCHEME" MODIFY ("MAJORVERSION" DEFAULT 1 );

ALTER TABLE "CLASSIFICATIONSCHEME" MODIFY ("MINORVERSION" DEFAULT 0 );

ALTER TABLE "CLASSIFICATIONSCHEME" MODIFY ("ISINTERNAL" DEFAULT 0 );

ALTER TABLE "CLASSIFICATIONSCHEME" MODIFY ("NODETYPE" DEFAULT 'UniqueCode' );

CREATE UNIQUE INDEX "PRIMARY00005" ON "CLASSIFICATIONSCHEME" ("ID" );

CREATE INDEX "NAME_VALUE" ON "CLASSIFICATIONSCHEME" ("NAME_VALUE" );

ALTER TABLE "CLASSIFICATIONSCHEME" ADD  CONSTRAINT "PRIMARY00005" PRIMARY KEY ("ID");

CREATE TABLE "CLASSSCHEME" ("CLASS_SCHEME" VARCHAR2(255) NOT NULL ENABLE, "NAME" VARCHAR2(255) NOT NULL ENABLE);

CREATE UNIQUE INDEX "PRIMARY00006" ON "CLASSSCHEME" ("CLASS_SCHEME" );

CREATE INDEX "NAME" ON "CLASSSCHEME" ("NAME" );

ALTER TABLE "CLASSSCHEME" ADD  CONSTRAINT "PRIMARY00006" PRIMARY KEY ("CLASS_SCHEME");

CREATE TABLE "CODELIST" ("CODE" VARCHAR2(255) NOT NULL ENABLE, "DISPLAY" VARCHAR2(255) NOT NULL ENABLE, "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE);

CREATE TABLE "CONFIDENTIALITYCODE" ("CODE" VARCHAR2(255) NOT NULL ENABLE, "DISPLAY" VARCHAR2(255) NOT NULL ENABLE, "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE);

CREATE TABLE "CONFIG_A" (
    "CACHE" VARCHAR2(1) NOT NULL ENABLE,
    "PATIENTID" VARCHAR2(1) NOT NULL ENABLE,
    "LOG" VARCHAR2(1),
    "STAT" VARCHAR2(1),
    "FOLDER" VARCHAR2(1),
    "STATUS" VARCHAR2(1)
);

ALTER TABLE "CONFIG_A" MODIFY ("STATUS" DEFAULT 'A' );

CREATE TABLE "CONFIG_B" (
    "CACHE" VARCHAR2(1) NOT NULL ENABLE,
    "PATIENTID" VARCHAR2(1) NOT NULL ENABLE,
    "LOG" VARCHAR2(1),
    "STAT" VARCHAR2(1),
    "FOLDER" VARCHAR2(1),
    "STATUS" VARCHAR2(1)
);

ALTER TABLE "CONFIG_B" MODIFY ("STATUS" DEFAULT 'A' );

CREATE TABLE "CONTENTTYPECODE" (
    "CODE" VARCHAR2(255) NOT NULL ENABLE,
    "DISPLAY" VARCHAR2(255) NOT NULL ENABLE,
    "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE
);

CREATE TABLE "COUNTERS" (
    "ID" NUMBER(19, 0) NOT NULL ENABLE
);

CREATE TABLE "DESCRIPTION" (
    "KEY_PROG" NUMBER(19, 0),
    "CHARSET" VARCHAR2(32),
    "LANG" VARCHAR2(32) NOT NULL ENABLE,
    "VALUE" VARCHAR2(255) NOT NULL ENABLE,
    "PARENT" VARCHAR2(64) NOT NULL ENABLE
);

ALTER TABLE "DESCRIPTION" MODIFY ("LANG" DEFAULT 'it-it' );

CREATE UNIQUE INDEX "PRIMARY00007" ON "DESCRIPTION" ("KEY_PROG" , "LANG" , "VALUE" , "PARENT" );

CREATE INDEX "INDEX4" ON "DESCRIPTION" ("PARENT" );

ALTER TABLE "DESCRIPTION" ADD  CONSTRAINT "PRIMARY00007" PRIMARY KEY ("KEY_PROG", "LANG", "VALUE", "PARENT");

CREATE TABLE "EMAILADDRESS" (
    "ADDRESS" VARCHAR2(64) NOT NULL ENABLE,
    "TYPE" VARCHAR2(32),
    "PARENT" VARCHAR2(64) NOT NULL ENABLE
);

CREATE TABLE "EXTERNALIDENTIFIER" (
    "KEY_PROG" NUMBER(19, 0),
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(128) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "REGISTRYOBJECT" VARCHAR2(128) NOT NULL ENABLE,
    "IDENTIFICATIONSCHEME" VARCHAR2(128) NOT NULL ENABLE,
    "VALUE" VARCHAR2(255) NOT NULL ENABLE
);

ALTER TABLE "EXTERNALIDENTIFIER" MODIFY ("OBJECTTYPE" DEFAULT 'ExternalIdentifier');

CREATE UNIQUE INDEX "PRIMARY00008" ON "EXTERNALIDENTIFIER" ("KEY_PROG" );

CREATE INDEX "VALUE" ON "EXTERNALIDENTIFIER" ("VALUE" );

CREATE INDEX "INDEX5" ON "EXTERNALIDENTIFIER" ("REGISTRYOBJECT" );

ALTER TABLE "EXTERNALIDENTIFIER" ADD  CONSTRAINT "PRIMARY00008" PRIMARY KEY ("KEY_PROG");

CREATE TABLE "EXTERNALLINK" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "EXTERNALURI" VARCHAR2(255) NOT NULL ENABLE
);

ALTER TABLE "EXTERNALLINK" MODIFY ("OBJECTTYPE" DEFAULT 'ExternalLink');

CREATE UNIQUE INDEX "PRIMARY00009" ON "EXTERNALLINK" ("ID" );

ALTER TABLE "EXTERNALLINK" ADD  CONSTRAINT "PRIMARY00009" PRIMARY KEY ("ID");

CREATE TABLE "EXTRINSICOBJECT" (
    "KEY_PROG" NUMBER(19, 0),
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(128) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(128),
    "EXPIRATION" DATE NOT NULL ENABLE,
    "MAJORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "MINORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "STABILITY" VARCHAR2(128),
    "STATUS" VARCHAR2(128) NOT NULL ENABLE,
    "USERVERSION" VARCHAR2(64),
    "ISOPAQUE" NUMBER(3, 0) NOT NULL ENABLE,
    "MIMETYPE" VARCHAR2(128) NOT NULL ENABLE
);

ALTER TABLE "EXTRINSICOBJECT" MODIFY ("OBJECTTYPE" DEFAULT 'text/xml');

ALTER TABLE "EXTRINSICOBJECT" MODIFY ("MAJORVERSION" DEFAULT 0 );

ALTER TABLE "EXTRINSICOBJECT" MODIFY ("MINORVERSION" DEFAULT 1 );

ALTER TABLE "EXTRINSICOBJECT" MODIFY ("ISOPAQUE" DEFAULT 0 );

CREATE UNIQUE INDEX "PRIMARY00010" ON "EXTRINSICOBJECT" ("KEY_PROG" , "ID" );

CREATE INDEX "INDEX1" ON "EXTRINSICOBJECT" ("ID" );

ALTER TABLE "EXTRINSICOBJECT" ADD  CONSTRAINT "PRIMARY00010" PRIMARY KEY ("KEY_PROG", "ID");

CREATE TABLE "FORMATCODE" (
    "CODE" VARCHAR2(255) NOT NULL ENABLE,
    "DISPLAY" VARCHAR2(255) NOT NULL ENABLE,
    "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE
);

CREATE TABLE "HEALTHCAREFACILITYTYPECODE" (
    "CODE" VARCHAR2(255) NOT NULL ENABLE,
    "DISPLAY" VARCHAR2(255) NOT NULL ENABLE,
    "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE
);

CREATE TABLE "HL7_MESSAGES" (
    "IDMESSAGE" NUMBER(19, 0),
    "TEXT" CLOB, "ACK" VARCHAR2(255),
    "RECEIVED" DATE NOT NULL ENABLE,
    "STATUS" VARCHAR2(1) NOT NULL ENABLE,
    "ACKED" DATE);

ALTER TABLE "HL7_MESSAGES" MODIFY ("STATUS" DEFAULT 'R' );

CREATE UNIQUE INDEX "PRIMARY00011" ON "HL7_MESSAGES" ("IDMESSAGE" );

ALTER TABLE "HL7_MESSAGES" ADD  CONSTRAINT "PRIMARY00011" PRIMARY KEY ("IDMESSAGE");

CREATE TABLE "HTTP" (
    "HTTPD" VARCHAR2(20) NOT NULL ENABLE,
    "ACTIVE" VARCHAR2(1) NOT NULL ENABLE
);

ALTER TABLE "HTTP" MODIFY ("ACTIVE" DEFAULT 'O' );

CREATE INDEX "ACTIVE00000" ON "HTTP" ("ACTIVE" );

CREATE TABLE "MIMETYPE" ("CODE" VARCHAR2(255) NOT NULL ENABLE);

CREATE INDEX "CODE" ON "MIMETYPE" ("CODE" );

CREATE TABLE "NAME" (
    "KEY_PROG" NUMBER(19, 0),
    "CHARSET" VARCHAR2(32),
    "LANG" VARCHAR2(32) NOT NULL ENABLE,
    "VALUE" VARCHAR2(255) NOT NULL ENABLE,
    "PARENT" VARCHAR2(128) NOT NULL ENABLE
);

ALTER TABLE "NAME" MODIFY ("LANG" DEFAULT 'it-it' );

CREATE UNIQUE INDEX "PRIMARY00012" ON "NAME" ("KEY_PROG" );

CREATE INDEX "INDEX3" ON "NAME" ("PARENT" );

ALTER TABLE "NAME" ADD  CONSTRAINT "PRIMARY00012" PRIMARY KEY ("KEY_PROG");

CREATE TABLE "NAV" (
    "NAV" VARCHAR2(1) NOT NULL ENABLE,
    "NAV_FROM" VARCHAR2(100) NOT NULL ENABLE,
    "NAV_TO" VARCHAR2(100) NOT NULL ENABLE
);

CREATE TABLE "ORGANIZATION" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "PARENT" VARCHAR2(64),
    "PRIMARYCONTACT" VARCHAR2(64) NOT NULL ENABLE
);

ALTER TABLE "ORGANIZATION" MODIFY ("OBJECTTYPE" DEFAULT 'Organization');

CREATE UNIQUE INDEX "PRIMARY00013" ON "ORGANIZATION" ("ID" );

ALTER TABLE "ORGANIZATION" ADD  CONSTRAINT "PRIMARY00013" PRIMARY KEY ("ID");

CREATE TABLE "PATIENT" (
    "ID" NUMBER(11, 0),
    "PID3" VARCHAR2(255) NOT NULL ENABLE,
    "INSERTDATE" DATE,
    "UPDATEDATE" DATE,
    "PRIORPID3" VARCHAR2(128)
);

CREATE UNIQUE INDEX "PRIMARY00014" ON "PATIENT" ("ID" );

CREATE INDEX "PID3" ON "PATIENT" ("PID3" );

CREATE INDEX "PRIORPID3" ON "PATIENT" ("PRIORPID3" );

ALTER TABLE "PATIENT" ADD  CONSTRAINT "PRIMARY00014" PRIMARY KEY ("ID");


CREATE TABLE "POSTALADDRESS" (
    "CITY" VARCHAR2(64),
    "COUNTRY" VARCHAR2(64),
    "POSTALCODE" VARCHAR2(64),
    "STATE" VARCHAR2(64),
    "STREET" VARCHAR2(64),
    "STREETNUMBER" VARCHAR2(32),
    "PARENT" VARCHAR2(64) NOT NULL ENABLE
);

CREATE TABLE "PRACTICESETTINGCODE" (
    "CODE" VARCHAR2(255) NOT NULL ENABLE,
    "DISPLAY" VARCHAR2(255) NOT NULL ENABLE,
    "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE
);

CREATE TABLE "REGISTRYPACKAGE" (
    "KEY_PROG" NUMBER(19, 0),
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(64),
    "EXPIRATION" DATE NOT NULL ENABLE,
    "MAJORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "MINORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "STABILITY" VARCHAR2(128),
    "STATUS" VARCHAR2(128) NOT NULL ENABLE,
    "USERVERSION" VARCHAR2(64)
);

ALTER TABLE "REGISTRYPACKAGE" MODIFY ("OBJECTTYPE" DEFAULT 'RegistryPackage');

ALTER TABLE "REGISTRYPACKAGE" MODIFY ("MAJORVERSION" DEFAULT 0 );

ALTER TABLE "REGISTRYPACKAGE" MODIFY ("MINORVERSION" DEFAULT 1 );

CREATE UNIQUE INDEX "PRIMARY00016" ON "REGISTRYPACKAGE" ("KEY_PROG" );

CREATE INDEX "INDEX2" ON "REGISTRYPACKAGE" ("ID" );

ALTER TABLE "REGISTRYPACKAGE" ADD  CONSTRAINT "PRIMARY00016" PRIMARY KEY ("KEY_PROG");

CREATE TABLE "REPOSITORY" (
    "REP_HOST" VARCHAR2(100) NOT NULL ENABLE,
    "REP_UNIQUEID" VARCHAR2(100) NOT NULL ENABLE
);

CREATE UNIQUE INDEX "PRIMARY00024" ON "REPOSITORY" ("REP_HOST" );

CREATE TABLE "SERVICE" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "EXPIRATION" DATE NOT NULL ENABLE,
    "MAJORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "MINORVERSION" NUMBER(11, 0) NOT NULL ENABLE,
    "STABILITY" VARCHAR2(128),
    "STATUS" VARCHAR2(128) NOT NULL ENABLE,
    "USERVERSION" VARCHAR2(64)
);

ALTER TABLE "SERVICE" MODIFY ("OBJECTTYPE" DEFAULT 'Service');

ALTER TABLE "SERVICE" MODIFY ("MAJORVERSION" DEFAULT 0 );

ALTER TABLE "SERVICE" MODIFY ("MINORVERSION" DEFAULT 1 );

CREATE UNIQUE INDEX "PRIMARY00017" ON "SERVICE" ("ID" );

ALTER TABLE "SERVICE" ADD  CONSTRAINT "PRIMARY00017" PRIMARY KEY ("ID");

CREATE TABLE "SERVICEBINDING" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "SERVICE" VARCHAR2(64) NOT NULL ENABLE,
    "ACCESSURI" VARCHAR2(255),
    "TARGETBINDING" VARCHAR2(64)
);

ALTER TABLE "SERVICEBINDING" MODIFY ("OBJECTTYPE" DEFAULT 'ServiceBinding');

CREATE UNIQUE INDEX "PRIMARY00018" ON "SERVICEBINDING" ("ID" );

ALTER TABLE "SERVICEBINDING" ADD  CONSTRAINT "PRIMARY00018" PRIMARY KEY ("ID");

CREATE TABLE "SLOT" (
    "KEY_PROG" NUMBER(19, 0),
    "NAME" VARCHAR2(128) NOT NULL ENABLE,
    "SLOTTYPE" VARCHAR2(128),
    "VALUE" VARCHAR2(255) NOT NULL ENABLE,
    "PARENT" VARCHAR2(128) NOT NULL ENABLE
);

CREATE UNIQUE INDEX "PRIMARY00019" ON "SLOT" ("KEY_PROG" );

CREATE INDEX "PARENT" ON "SLOT" ("PARENT" );

CREATE INDEX "VALUE00000" ON "SLOT" ("VALUE" );

ALTER TABLE "SLOT" ADD  CONSTRAINT "PRIMARY00019" PRIMARY KEY ("KEY_PROG");

CREATE TABLE "SPECIFICATIONLINK" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "SERVICE" VARCHAR2(64) NOT NULL ENABLE,
    "SERVICEBINDING" VARCHAR2(64) NOT NULL ENABLE,
    "SPECIFICATIONOBJECT" VARCHAR2(64) NOT NULL ENABLE
);

ALTER TABLE "SPECIFICATIONLINK" MODIFY ("OBJECTTYPE" DEFAULT 'SpecificationLink');

CREATE UNIQUE INDEX "PRIMARY00020" ON "SPECIFICATIONLINK" ("ID" );

ALTER TABLE "SPECIFICATIONLINK" ADD  CONSTRAINT "PRIMARY00020" PRIMARY KEY ("ID");

CREATE TABLE "STATS" (
    "ID" NUMBER(*,0) NOT NULL ENABLE,
    "REPOSITORY" VARCHAR2(20) NOT NULL ENABLE,
    "DATA" DATE,
    "EXECUTION_TIME" VARCHAR2(20),
    "OPERATION" VARCHAR2(50)
);

CREATE UNIQUE INDEX "TABLE1_PK" ON "STATS" ("ID" );

ALTER TABLE "STATS" ADD  CONSTRAINT "TABLE1_PK" PRIMARY KEY ("ID");

CREATE TABLE "TELEPHONENUMBER" (
    "AREACODE" VARCHAR2(4),
    "COUNTRYCODE" VARCHAR2(4),
    "EXTENSION" VARCHAR2(8),
    "NUMBER" VARCHAR2(16),
    "PHONETYPE" VARCHAR2(32),
    "URL" VARCHAR2(255),
    "PARENT" VARCHAR2(64) NOT NULL ENABLE
);

CREATE TABLE "TYPECODE" (
    "CODE" VARCHAR2(255) NOT NULL ENABLE,
    "DISPLAY" VARCHAR2(255) NOT NULL ENABLE,
    "CODINGSCHEME" VARCHAR2(255) NOT NULL ENABLE
);

CREATE TABLE "USAGEDESCRIPTION" (
    "CHARSET" VARCHAR2(32),
    "LANG" VARCHAR2(32) NOT NULL ENABLE,
    "VALUE" VARCHAR2(255) NOT NULL ENABLE,
    "PARENT" VARCHAR2(64) NOT NULL ENABLE
);

CREATE UNIQUE INDEX "PRIMARY00021" ON "USAGEDESCRIPTION" ("PARENT" , "LANG" , "VALUE" );

ALTER TABLE "USAGEDESCRIPTION" ADD  CONSTRAINT "PRIMARY00021" PRIMARY KEY ("PARENT", "LANG", "VALUE");

CREATE TABLE "USAGEPARAMETER" (
    "VALUE" VARCHAR2(255) NOT NULL ENABLE,
    "PARENT" VARCHAR2(64) NOT NULL ENABLE
);

CREATE TABLE "USERS" (
    "LOGIN" VARCHAR2(30) NOT NULL ENABLE,
    "PASSWORD" VARCHAR2(50) NOT NULL ENABLE
);

CREATE UNIQUE INDEX "PRIMARY00023" ON "USERS" ("LOGIN" );

ALTER TABLE "USERS" ADD  CONSTRAINT "PRIMARY00023" PRIMARY KEY ("LOGIN");

CREATE TABLE "USER_" (
    "ACCESSCONTROLPOLICY" VARCHAR2(64),
    "ID" VARCHAR2(64) NOT NULL ENABLE,
    "OBJECTTYPE" VARCHAR2(32),
    "EMAIL" VARCHAR2(128) NOT NULL ENABLE,
    "ORGANIZATION" VARCHAR2(64) NOT NULL ENABLE,
    "PERSONNAME_FIRSTNAME" VARCHAR2(64),
    "PERSONNAME_MIDDLENAME" VARCHAR2(64),
    "PERSONNAME_LASTNAME" VARCHAR2(64),
    "URL" VARCHAR2(255)
);

ALTER TABLE "USER_" MODIFY ("OBJECTTYPE" DEFAULT 'User');

CREATE UNIQUE INDEX "PRIMARY00022" ON "USER_" ("ID" );

ALTER TABLE "USER_" ADD  CONSTRAINT "PRIMARY00022" PRIMARY KEY ("ID");

CREATE TRIGGER "ASSOCIATION" BEFORE INSERT ON "ASSOCIATION" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "ASSOCIATION_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('ASSOCIATION_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "ASSOCIATION_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "ASSOCIATION"  ENABLE;

CREATE TRIGGER "ATNA" BEFORE INSERT ON "ATNA" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."ID" IS NULL) THEN SELECT "ATNA_0".NEXTVAL INTO :NEW."ID" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('ATNA_0'); SELECT :NEW."ID" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "ATNA_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "ATNA"  ENABLE;

CREATE TRIGGER "AUDITABLEEVENT" BEFORE INSERT ON "AUDITABLEEVENT" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."ID" IS NULL) THEN SELECT "AUDITABLEEVENT_0".NEXTVAL INTO :NEW."ID" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('AUDITABLEEVENT_0'); SELECT :NEW."ID" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "AUDITABLEEVENT_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "AUDITABLEEVENT"  ENABLE;

CREATE TRIGGER "CLASSIFICATION" BEFORE INSERT ON "CLASSIFICATION" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "CLASSIFICATION_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('CLASSIFICATION_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "CLASSIFICATION_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "CLASSIFICATION"  ENABLE;

CREATE TRIGGER "DESCRIPTION" BEFORE INSERT ON "DESCRIPTION" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "DESCRIPTION_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('DESCRIPTION_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "DESCRIPTION_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "DESCRIPTION"  ENABLE;

CREATE TRIGGER "EXTERNALIDENTIFIER" BEFORE INSERT ON "EXTERNALIDENTIFIER" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "EXTERNALIDENTIFIER_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('EXTERNALIDENTIFIER_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "EXTERNALIDENTIFIER_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "EXTERNALIDENTIFIER"  ENABLE

CREATE TRIGGER "EXTRINSICOBJECT" BEFORE INSERT ON "EXTRINSICOBJECT" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "EXTRINSICOBJECT_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('EXTRINSICOBJECT_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "EXTRINSICOBJECT_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "EXTRINSICOBJECT"  ENABLE;

CREATE TRIGGER "HL7_MESSAGES" BEFORE INSERT ON "HL7_MESSAGES" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."IDMESSAGE" IS NULL) THEN SELECT "HL7_MESSAGES_0".NEXTVAL INTO :NEW."IDMESSAGE" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('HL7_MESSAGES_0'); SELECT :NEW."IDMESSAGE" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "HL7_MESSAGES_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "HL7_MESSAGES"  ENABLE;

CREATE TRIGGER "NAME" BEFORE INSERT ON "NAME" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "NAME_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('NAME_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "NAME_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "NAME"  ENABLE;

CREATE TRIGGER "PATIENT" BEFORE INSERT ON "PATIENT" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."ID" IS NULL) THEN SELECT "PATIENT_0".NEXTVAL INTO :NEW."ID" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('PATIENT_0'); SELECT :NEW."ID" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "PATIENT_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "PATIENT"  ENABLE;

CREATE TRIGGER "REGISTRYPACKAGE" BEFORE INSERT ON "REGISTRYPACKAGE" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "REGISTRYPACKAGE_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('REGISTRYPACKAGE_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "REGISTRYPACKAGE_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "REGISTRYPACKAGE"  ENABLE;

CREATE TRIGGER "SLOT" BEFORE INSERT ON "SLOT" FOR EACH ROW DECLARE last_Sequence NUMBER; last_InsertID NUMBER; BEGIN IF (:NEW."KEY_PROG" IS NULL) THEN SELECT "SLOT_0".NEXTVAL INTO :NEW."KEY_PROG" FROM DUAL; ELSE SELECT Last_Number-1 INTO last_Sequence FROM User_Sequences WHERE UPPER(Sequence_Name) = UPPER('SLOT_0'); SELECT :NEW."KEY_PROG" INTO last_InsertID FROM DUAL; WHILE (last_InsertID > last_Sequence) LOOP SELECT "SLOT_0".NEXTVAL INTO last_Sequence FROM DUAL; END LOOP; END IF; END;  

ALTER TRIGGER "SLOT"  ENABLE;

CREATE TRIGGER STATS  before insert on "STATS"    for each row begin     if inserting then       if :NEW."ID" is null then          select STATS_PK_SEQ.nextval into :NEW."ID" from dual;       end if;    end if; end;  

ALTER TRIGGER "STATS"  ENABLE;


INSERT INTO ATNA (ID, host, port, ACTIVE, DESCRIPTION) VALUES
(1, '172.18.8.67', '4000', 'O', 'ATNA REGISTRY');

INSERT INTO classCode (code, display, codingScheme) VALUES('Communication', 'Communication', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Evaluation and management', 'Evaluation and management', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Conference', 'Conference', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Case conference', 'Case conference', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Consult', 'Consult', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Confirmatory consultation', 'Confirmatory consultation', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Counseling', 'Counseling', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Group counseling', 'Group counseling', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Education', 'Education', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('History and Physical', 'History and Physical', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Admission history and physical', 'Admission history and physical', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Comprehensive history and physical', 'Comprehensive history and physical', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Targeted history and physical', 'Targeted history and physical', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Initial evaluation', 'Initial evaluation', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Admission evaluation', 'Admission evaluation', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Pre-operative evaluation and management', 'Pre-operative evaluation and management', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Subsequent evaluation', 'Subsequent evaluation', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Summarization of episode', 'Summarization of episode', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Transfer summarization', 'Transfer summarization', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Discharge summarization', 'Discharge summarization', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Summary of death', 'Summary of death', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Transfer of care referral', 'Transfer of care referral', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Supervisory direction', 'Supervisory direction', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Telephone encounter', 'Telephone encounter', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Interventional Procedure', 'Interventional Procedure', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Operative', 'Operative', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Pathology Procedure', 'Pathology Procedure', 'Connect-a-thon classCodes');
INSERT INTO classCode (code, display, codingScheme) VALUES('Autopsy', 'Autopsy', 'Connect-a-thon classCodes');


INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6', 'ClassificationNode', 'XDS', 'urn:uuid:3188a449-18ac-41fb-be9f-99a1adca02cb', '/urn:uuid:3188a449-18ac-41fb-be9f-99a1adca02cb/XDS', 'XDS object type');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:7edca82f-054d-47f2-a032-9b2a5b5186c1', 'ClassificationNode', 'XDSDocumentEntry', 'urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6', '/urn:uuid:3188a449-18ac-41fb-be9f-99a1adca02cb/XDS/XDSDocumentEntry', 'XDSDocumentEntry');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:a54d6aa5-d40d-43f9-88c5-b4633d873bdd', 'ClassificationNode', 'XDSSubmissionSet', 'urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6', '/urn:uuid:3188a449-18ac-41fb-be9f-99a1adca02cb/XDS/XDSSubmissionSet', 'XDSSubmissionSet');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:d9d542f3-6cc4-48b6-8870-ea235fbc94c2', 'ClassificationNode', 'XDSFolder', 'urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6', '/urn:uuid:3188a449-18ac-41fb-be9f-99a1adca02cb/XDS/XDSFolder', 'XDSFolder');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:10aa1a4b-715a-4120-bfd0-9760414112c8', 'ClassificationNode', 'XDSDocumentEntryStub', 'urn:uuid:415715f1-fc0b-47c4-90e5-c180b7b82db6', '/urn:uuid:3188a449-18ac-41fb-be9f-99a1adca02cb/XDS/XDSDocumentEntryStub', 'XDSDocumentEntryStub');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:f9653189-fdd2-4c31-afbc-86c96ac8f0ad', 'ClassificationNode', 'XDS', 'urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d', '/urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d/XDS', 'XDS');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:917dc511-f7da-4417-8664-de25b34d3def', 'ClassificationNode', 'APND', 'urn:uuid:f9653189-fdd2-4c31-afbc-86c96ac8f0ad', '/urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d/XDS/APND', 'APND');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:60fd13eb-b8f6-4f11-8f28-9ee000184339', 'ClassificationNode', 'RPLC', 'urn:uuid:f9653189-fdd2-4c31-afbc-86c96ac8f0ad', '/urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d/XDS/RPLC', 'RPLC');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:ede379e6-1147-4374-a943-8fcdcf1cd620', 'ClassificationNode', 'XFRM', 'urn:uuid:f9653189-fdd2-4c31-afbc-86c96ac8f0ad', '/urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d/XDS/XFRM', 'XFRM');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:b76a27c7-af3c-4319-ba4c-b90c1dc45408', 'ClassificationNode', 'XFRM_RPLC', 'urn:uuid:f9653189-fdd2-4c31-afbc-86c96ac8f0ad', '/urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d/XDS/XFRM_RPLC', 'XFRM_RPLC');
INSERT INTO ClassificationNode (accessControlPolicy, id, objectType, code, parent, path, Name_value) VALUES(NULL, 'urn:uuid:8ea93462-ad05-4cdc-8e54-a8084f6aff94', 'ClassificationNode', 'signs', 'urn:uuid:f9653189-fdd2-4c31-afbc-86c96ac8f0ad', '/urn:uuid:6902675f-2f18-44b8-888b-c91db8b96b4d/XDS/signs', 'signs');

INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:554ac39e-e3fe-47fe-b233-965d2a147832', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSSubmissionSet.sourceId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:6b5aea1a-874d-4603-a4bc-96a0a7b38446', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSSubmissionSet.patientId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:58a6f841-87b3-4a3e-92fd-a8ffeff98427', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSDocumentEntry.patientId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:f64ffdf0-4b97-4e06-b79f-a52b38ec2f8a', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSFolder.patientId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:2e82c1f6-a085-4c72-9da3-8640a32e42ab', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSDocumentEntry.uniqueId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:75df8f67-9973-4fbe-a900-df66cefecc5a', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSFolder.uniqueId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:96fdda7c-d067-4183-912e-bf5ee74998a8', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 1, 'EmbeddedPath', 'XDSSubmissionSet.uniqueId', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:41a5887f-8865-4c09-adf7-e362475b143a', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.classCode', 'An XDSDocumentEntry must have exactly one Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:f4f85eac-e6cb-4883-b524-f2705394840f', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.confidentialityCode', 'An XDSDocumentEntry must have exactly one Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:2c6b8cb7-8b2a-4051-b291-b1ae6a575ef4', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.eventCodeList', 'An XDSDocumentEntry may have zero or more Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:a09d5840-386c-46f2-b5ad-9c3699a4309d', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.formatCode', 'An XDSDocumentEntry must have exactly one Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:f33fb8ac-18af-42cc-ae0e-ed0b0bdb91e1', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.healthCareFacilityTypeCode', 'An XDSDocumentEntry must have exactly one Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:cccf5598-8b07-4b77-a05e-ae952c785ead', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.practiceSettingCode', 'An XDSDocumentEntry must have exactly one Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:f0306f51-975f-434e-a61c-c59651d33983', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.typeCode', 'An XDSDocumentEntry must have exactly one Classification of this type.');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:aa543740-bdda-424e-8c96-df4873be8500', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSSubmissionSet.contentTypeCode', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:1ba97051-7806-41a8-a48b-8fce7af683c5', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSFolder.codeList', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:a7058bb9-b4e4-4307-ba5b-e3f0ab85e12d', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSSubmissionSet.authorDescription', '');
INSERT INTO ClassificationScheme (accessControlPolicy, id, objectType, expiration, majorVersion, minorVersion, stability, status, userVersion, isInternal, nodeType, Name_value, Description_value) VALUES(NULL, 'urn:uuid:93606bcf-9494-43ec-9b4e-a7748d1a838d', 'ClassificationScheme', CURRENT_TIMESTAMP, 1, 0, NULL, '1', NULL, 0, 'EmbeddedPath', 'XDSDocumentEntry.authorDescription', '');

INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:aa543740-bdda-424e-8c96-df4873be8500', 'contentTypeCode');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:41a5887f-8865-4c09-adf7-e362475b143a', 'classCode');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:f4f85eac-e6cb-4883-b524-f2705394840f', 'confidentialityCode');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:a09d5840-386c-46f2-b5ad-9c3699a4309d', 'formatCode');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:f33fb8ac-18af-42cc-ae0e-ed0b0bdb91e1', 'healthcareFacilityTypeCode');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:cccf5598-8b07-4b77-a05e-ae952c785ead', 'practiceSettingCode');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:1ba97051-7806-41a8-a48b-8fce7af683c5', 'codeList');
INSERT INTO classScheme (class_Scheme, name) VALUES('urn:uuid:f0306f51-975f-434e-a61c-c59651d33983', 'typeCode');

INSERT INTO codeList (code, display, codingScheme) VALUES('Anesthesia', 'Anesthesia', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Cardiology', 'Cardiology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Case Manager', 'Case Manager', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Chaplain', 'Chaplain', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Chemotherapy', 'Chemotherapy', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Chiropractic', 'Chiropractic', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Critical Care', 'Critical Care', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Dentistry', 'Dentistry', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Diabetology', 'Diabetology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Dialysis', 'Dialysis', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Emergency', 'Emergency', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Endocrinology', 'Endocrinology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Gastroenterology', 'Gastroenterology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('General Medicine', 'General Medicine', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('General Surgery', 'General Surgery', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Gynecology', 'Gynecology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Labor and Delivery', 'Labor and Delivery', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Laboratory', 'Laboratory', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Multidisciplinary', 'Multidisciplinary', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Neonatal Intensive Care', 'Neonatal Intensive Care', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Neurosurgery', 'Neurosurgery', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Nursery', 'Nursery', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Nursing', 'Nursing', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Obstetrics', 'Obstetrics', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Occupational Therapy', 'Occupational Therapy', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Ophthalmology', 'Ophthalmology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Optometry', 'Optometry', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Orthopedics', 'Orthopedics', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Otorhinolaryngology', 'Otorhinolaryngology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Pathology', 'Pathology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Perioperative', 'Perioperative', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Pharmacacy', 'Pharmacacy', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Physical Medicine', 'Physical Medicine', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Plastic Surgery', 'Plastic Surgery', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Podiatry', 'Podiatry', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Psychiatry', 'Psychiatry', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Pulmonary', 'Pulmonary', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Radiology', 'Radiology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Social Services', 'Social Services', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Speech Therapy', 'Speech Therapy', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Thyroidology', 'Thyroidology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Tumor Board', 'Tumor Board', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Urology', 'Urology', 'Connect-a-thon codeList');
INSERT INTO codeList (code, display, codingScheme) VALUES('Veterinary Medicine', 'Veterinary Medicine', 'Connect-a-thon codeList');

INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('C', 'Celebrity', 'Connect-a-thon confidentialityCodes');
INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('D', 'Clinician', 'Connect-a-thon confidentialityCodes');
INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('I', 'Individual', 'Connect-a-thon confidentialityCodes');
INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('N', 'Normal', 'Connect-a-thon confidentialityCodes');
INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('R', 'Restricted', 'Connect-a-thon confidentialityCodes');
INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('S', 'Sensitive', 'Connect-a-thon confidentialityCodes');
INSERT INTO confidentialityCode (code, display, codingScheme) VALUES('T', 'Taboo', 'Connect-a-thon confidentialityCodes');

INSERT INTO CONFIG_A (CACHE, PATIENTID, LOG, STAT, FOLDER, STATUS) VALUES('H', 'O', 'O', 'A', 'O', 'A');

INSERT INTO CONFIG_B (CACHE, PATIENTID, LOG, STAT, FOLDER, STATUS) VALUES('H', 'O', 'O', 'A', 'O', 'A');


INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Communication', 'Communication', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Evaluation and management', 'Evaluation and management', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Conference', 'Conference', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Case conference', 'Case conference', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Consult', 'Consult', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Confirmatory consultation', 'Confirmatory consultation', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Counseling', 'Counseling', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Group counseling', 'Group counseling', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Education', 'Education', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('History and Physical', 'History and Physical', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Admission history and physical', 'Admission history and physical', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Comprehensive history and physical', 'Comprehensive history and physical', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Targeted history and physical', 'Targeted history and physical', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Initial evaluation', 'Initial evaluation', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Admission evaluation', 'Admission evaluation', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Pre-operative evaluation and management', 'Pre-operative evaluation and management', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Subsequent evaluation', 'Subsequent evaluation', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Summarization of episode', 'Summarization of episode', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Transfer summarization', 'Transfer summarization', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Discharge summarization', 'Discharge summarization', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Summary of death', 'Summary of death', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Transfer of care referral', 'Transfer of care referral', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Supervisory direction', 'Supervisory direction', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Telephone encounter', 'Telephone encounter', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Interventional Procedure', 'Interventional Procedure', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Operative', 'Operative', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Pathology Procedure', 'Pathology Procedure', 'Connect-a-thon contentTypeCodes');
INSERT INTO contentTypeCode (code, display, codingScheme) VALUES('Autopsy', 'Autopsy', 'Connect-a-thon contentTypeCodes');

INSERT INTO formatCode (code, display, codingScheme) VALUES('PDF/IHE 1.x', 'PDF/IHE 1.x', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('CDA/IHE 1.0', 'CDA/IHE 1.0', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('CDAR2/IHE 1.0', 'CDAR2/IHE 1.0', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('CCR/IHE 0.9', 'CCR/IHE 0.9', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('HL7/Lab 2.5', 'HL7/Lab 2.5', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('IHE/PCC/MS/1.0', 'XDS-MS', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('IHE/multipart', 'multipart', 'Connect-a-thon formatCodes');
INSERT INTO formatCode (code, display, codingScheme) VALUES('1.2.840.10008.5.1.4.1.1.88.59', 'Key Object Selection Document', '1.2.840.10008.2.6.1');

INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Home', 'Home', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Assisted Living', 'Assisted Living', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Home Health Care', 'Home Health Care', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Hospital Setting', 'Hospital Setting', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Acute care hospital', 'Acute care hospital', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Hospital Unit', 'Hospital Unit', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Critical Care Unit', 'Critical Care Unit', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Emergency Department', 'Emergency Department', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Observation Ward', 'Observation Ward', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Rehabilitation hospital', 'Rehabilitation hospital', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Nursing Home', 'Nursing Home', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Skilled Nursing Facility', 'Skilled Nursing Facility', 'Connect-a-thon healthcareFacilityTypeCodes');
INSERT INTO healthcareFacilityTypeCode (code, display, codingScheme) VALUES('Outpatient', 'Outpatient', 'Connect-a-thon healthcareFacilityTypeCodes');

INSERT INTO HTTP (HTTPD, ACTIVE) VALUES ('NORMAL', 'A');

INSERT INTO mimeType (code) VALUES('application/dicom');
INSERT INTO mimeType (code) VALUES('application/pdf');
INSERT INTO mimeType (code) VALUES('application/x-hl7');
INSERT INTO mimeType (code) VALUES('application/x-pkcs7-mime');
INSERT INTO mimeType (code) VALUES('multipart/related');
INSERT INTO mimeType (code) VALUES('text/plain');
INSERT INTO mimeType (code) VALUES('text/x-cda-r2+xml');
INSERT INTO mimeType (code) VALUES('text/xml');

INSERT INTO NAV (NAV, NAV_FROM, NAV_TO) VALUES ('O', 'xdsfrom@email.com', 'xdsto@email.com');

INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Anesthesia', 'Anesthesia', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Cardiology', 'Cardiology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Case Manager', 'Case Manager', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Chaplain', 'Chaplain', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Chemotherapy', 'Chemotherapy', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Chiropractic', 'Chiropractic', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Critical Care', 'Critical Care', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Dentistry', 'Dentistry', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Diabetology', 'Diabetology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Dialysis', 'Dialysis', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Emergency', 'Emergency', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Endocrinology', 'Endocrinology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Gastroenterology', 'Gastroenterology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('General Medicine', 'General Medicine', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('General Surgery', 'General Surgery', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Gynecology', 'Gynecology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Labor and Delivery', 'Labor and Delivery', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Laboratory', 'Laboratory', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Multidisciplinary', 'Multidisciplinary', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Neonatal Intensive Care', 'Neonatal Intensive Care', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Neurosurgery', 'Neurosurgery', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Nursery', 'Nursery', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Nursing', 'Nursing', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Obstetrics', 'Obstetrics', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Occupational Therapy', 'Occupational Therapy', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Ophthalmology', 'Ophthalmology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Optometry', 'Optometry', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Orthopedics', 'Orthopedics', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Otorhinolaryngology', 'Otorhinolaryngology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Pathology', 'Pathology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Perioperative', 'Perioperative', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Pharmacacy', 'Pharmacacy', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Physical Medicine', 'Physical Medicine', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Plastic Surgery', 'Plastic Surgery', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Podiatry', 'Podiatry', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Psychiatry', 'Psychiatry', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Pulmonary', 'Pulmonary', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Radiology', 'Radiology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Social Services', 'Social Services', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Speech Therapy', 'Speech Therapy', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Thyroidology', 'Thyroidology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Tumor Board', 'Tumor Board', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Urology', 'Urology', 'Connect-a-thon practiceSettingCodes');
INSERT INTO practiceSettingCode (code, display, codingScheme) VALUES('Veterinary Medicine', 'Veterinary Medicine', 'Connect-a-thon practiceSettingCodes');

INSERT INTO REPOSITORY (REP_HOST, REP_UNIQUEID) VALUES('127.0.0.1', '1.3.6.1.4.1.21367.2008.2.5.115');
INSERT INTO REPOSITORY (REP_HOST, REP_UNIQUEID) VALUES('10.135.0.95', '1.3.6.1.4.1.21367.2008.2.5.115');
INSERT INTO REPOSITORY (REP_HOST, REP_UNIQUEID) VALUES('localhost', '1.3.6.1.4.1.21367.2008.2.5.115');

INSERT INTO typeCode (code, display, codingScheme) VALUES('34096-8', 'Nursing Home Comprehensive History and Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34121-4', 'Interventional Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18743-5', 'Autopsy Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34095-0', 'Comprehensive History and Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34098-4', 'Conference Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11488-4', 'Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28574-2', 'Discharge Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18842-5', 'Discharge Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34109-9', 'Evaluation And Management Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34117-2', 'History And Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28636-9', 'Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28570-0', 'Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11506-3', 'Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34133-9', 'Summarization of Episode Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11504-8', 'Surgical Operation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34138-8', 'Targeted History And Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34140-4', 'Transfer of Care Referral Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18761-7', 'Transfer Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34100-8', 'Critical Care Unit Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34126-3', 'Critical Care Unit Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34111-5', 'Emergency Department Evaluation And Management Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('15507-7', 'Emergency Department Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34107-3', 'Home Health Education Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34118-0', 'Home Health Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34129-7', 'Home Health Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34104-0', 'Hospital Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34105-7', 'Hospital Discharge Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34114-9', 'Hospital Group Counseling Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11492-6', 'Hospital History and Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34130-5', 'Hospital Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34112-3', 'Inpatient Evaluation And Management Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34097-6', 'Nursing Home Conference Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34113-1', 'Nursing Home Evaluation And Management Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34119-8', 'Nursing Home Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('24611-6', 'Outpatient Confirmatory Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34108-1', 'Outpatient Evaluation And Management', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34120-6', 'Outpatient Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34131-3', 'Outpatient Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34137-0', 'Outpatient Surgical Operation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34123-0', 'Anesthesia Hospital Pre-Operative Evaluation And Management Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28655-9', 'Attending Physician Discharge Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28654-2', 'Attending Physician Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18733-6', 'Attending Physician Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34134-7', 'Attending Physician Outpatient Supervisory Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34135-4', 'Attending Physician Cardiology Outpatient Supervisory Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34136-2', 'Attending Physician Gastroenterology Outpatient Supervisory Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34099-2', 'Cardiology Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34094-3', 'Cardiology Hospital Admission History And Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34124-8', 'Cardiology Outpatient Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34125-5', 'Case Manager Home Health Care Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28581-7', 'Chiropractor Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18762-5', 'Chiropractor Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18763-3', 'Consulting Physician Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28569-2', 'Consulting Physician Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34127-1', 'Dental Hygienist Outpatient Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('29761-4', 'Dentistry Discharge Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28572-6', 'Dentistry Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28577-5', 'Dentistry Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28617-9', 'Dentistry Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28583-3', 'Dentistry Surgical Operation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28618-7', 'Dentistry Visit Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34128-9', 'Dentistry Outpatient Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34110-7', 'Diabetology Outpatient Evaluation And Management Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18748-4', 'Diagnostic Imaging Report', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34101-6', 'General Medicine Outpatient Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34115-6', 'Medical Student Hospital History and Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28621-1', 'Nurse Practitioner Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18764-1', 'Nurse Practitioner Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28622-9', 'Nursing Discharge Assessment Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('29753-1', 'Nursing Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28623-7', 'Nursing Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34139-6', 'Nursing Telephone Encounter Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28651-8', 'Nursing Transfer Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18734-4', 'Occupational Therapy Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11507-1', 'Occupational Therapy Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34122-2', 'Pathology Pathology Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34132-1', 'Pharmacy Outpatient Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18735-1', 'Physical Therapy Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11508-9', 'Physical Therapy Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28579-1', 'Physical Therapy Visit Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11490-0', 'Physician Discharge Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28626-0', 'Physician History and Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18736-9', 'Physician Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11505-5', 'Physician Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28573-4', 'Physician Surgical Operation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28616-1', 'Physician Transfer Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28568-4', 'Physician Emergency Department Visit Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34106-5', 'Physician Hospital Discharge Summarization Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34116-4', 'Physician Nursing Home History and Physical Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18737-7', 'Podiatry Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28625-2', 'Podiatry Procedure Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11509-7', 'Podiatry Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28624-5', 'Podiatry Surgical Operation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28635-1', 'Psychiatry Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28627-8', 'Psychiatry Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34102-4', 'Psychiatry Hospital Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18738-5', 'Psychology Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11510-5', 'Psychology Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('34103-2', 'Pulmonary Consultation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18739-3', 'Social Service Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28656-7', 'Social Service Subsequent Visit Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('28653-4', 'Social Service Visit Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('18740-1', 'Speech Therapy Initial Evaluation Note', 'LOINC');
INSERT INTO typeCode (code, display, codingScheme) VALUES('11512-1', 'Speech Therapy Subsequent Visit Evaluation Note', 'LOINC');

INSERT INTO USERS (LOGIN, PASSWORD) VALUES ('marisxds', 'xdSwGC7.aBWxk');
