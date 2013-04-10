--sql to create stired procedure getGlobalDataUsingSubcatId

DELIMITER // 
CREATE PROCEDURE `proc_getGlobalDataUsingSubcatId`( 
IN in_subcatId BIGINT) 
BEGIN 

DECLARE subcatId BIGINT; 
DECLARE globalSubcatId BIGINT; 
DECLARE subcatName VARCHAR(255); 
DECLARE metacatId BIGINT; 
DECLARE globalMetacatId BIGINT; 
DECLARE metacatName VARCHAR(255); 
SELECT 
      a.node_id, a.nod_globalId,a.nod_name,a.nod_pid, (SELECT b.nod_globalId FROM babel_node as b WHERE b.node_id = a.nod_pid LIMIT 1), (SELECT b.nod_name FROM babel_node as b WHERE b.node_id = a.nod_pid AND a.nod_title != "" LIMIT 1) INTO subcatId, globalSubcatId, subcatName, metacatId, globalMetacatId, metacatName 
      FROM 
      babel_node as a 
      WHERE 
      a.node_id = in_subcatId AND a.nod_title != "" 
      LIMIT 1; 
SELECT subcatId, globalSubcatId, subcatName, metacatId, globalMetacatId, metacatName; 
END// 
DELIMITER ;


--for DB administrator

 GRANT execute on procedure kijiji_presentation.proc_getGlobalDataUsingSubcatId to 'qkr'@'172.16.1.50'