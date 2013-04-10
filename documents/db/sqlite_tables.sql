-- Admin table

CREATE TABLE "admin" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , 
"allowed_section_ids" VARCHAR NOT NULL , 
"username" VARCHAR NOT NULL UNIQUE , 
"password" VARCHAR NOT NULL , 
"salt" VARCHAR NOT NULL, 
"user_type" VARCHAR NOT NULL, 
"created_time" DATETIME NOT NULL , 
"modified_time" DATETIME NOT NULL , 
"last_login_time" DATETIME NULL , 
"no_of_logins" INTEGER NOT NULL  DEFAULT 0, 
"is_active" INTEGER NOT NULL  DEFAULT 0)

--admin record
INSERT INTO "admin" VALUES(1,'1,2,3,4,5,6,7,8','admin','7c9bd4bee280fb112616b67a3628db9d','@ngrt#','admin','02-01-2012 12:15:08','02-01-2012 12:15:08','2012-02-23 13:31:44',8,0);


DROP TABLE IF EXISTS "sections";
CREATE TABLE "sections" ("id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL  UNIQUE , "caption" VARCHAR);
INSERT INTO "sections" VALUES(1,'ads');
INSERT INTO "sections" VALUES(2,'users');
INSERT INTO "sections" VALUES(3,'reply');
INSERT INTO "sections" VALUES(4,'alerts');
INSERT INTO "sections" VALUES(5,'search');
INSERT INTO "sections" VALUES(6,'premiumads');
INSERT INTO "sections" VALUES(7,'premiumpacks');
INSERT INTO "sections" VALUES(8,'settings');

DROP TABLE IF EXISTS "data_history";
CREATE TABLE "data_history" 
("id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL UNIQUE ,
"core" VARCHAR NOT NULL ,
"indexing_script" VARCHAR NOT NULL ,
"sql_query"  VARCHAR NOT NULL ,
"indexing_flag" VARCHAR NOT NULL ,
"for_no_of_days" INTEGER NOT NULL DEFAULT 0,
"db_count" INTEGER NOT NULL  DEFAULT 0,
"solr_query"  VARCHAR NULL,
"solr_count" INTEGER NOT NULL DEFAULT 0,
"indexing_day" DATETIME NOT NULL, 
"insert_time_db" DATETIME ,
"insert_time_solr" DATETIME, 
"log_file" VARCHAR NULL );

DROP TABLE IF EXISTS "ro_settings";
CREATE TABLE ro_settings (
    ro_user_id INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL,
    lft INTEGER NULL,
    rgt INTEGER NULL,
    "created_time" DATETIME NOT NULL, 
    "modified_time" DATETIME NOT NULL 
);

--root user
INSERT INTO "admin" VALUES(15,'7','Dhirendra','f79cf19404ae5db5f64ea428d0edaf93','@ngrt#','ro','2013-03-12 00:00:00','2013-03-12 00:00:00','2013-03-12 00:00:00',1,0); 
INSERT INTO "ro_settings" VALUES(15,1,2,'2013-03-12 00:00:00','2013-03-12 00:00:00'); 


--solr query analyzer
DROP TABLE IF EXISTS "solr_query_log";
CREATE TABLE "solr_query_log" (
    "id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL , 
    "severity_level" VARCHAR, 
    "calling_script" TEXT, 
    "stack_trace" TEXT,
    "php_mem_usage" FLOAT, 
    "php_peak_usage" FLOAT, 
    "entry_time" DATETIME, 
    "complete_time" DATETIME, 
    "url" TEXT, 
    "http_code" INTEGER, 
    "total_time" FLOAT, 
    "size_download" FLOAT, 
    "speed_download" FLOAT, 
    "size_upload" FLOAT, 
    "speed_upload" FLOAT, 
    "created" DATETIME DEFAULT CURRENT_TIMESTAMP 
);
