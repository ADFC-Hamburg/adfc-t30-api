ALTER TABLE demandedstreetsection MODIFY house_no_from VARCHAR(255);
ALTER TABLE demandedstreetsection MODIFY house_no_to VARCHAR(255);
ALTER TABLE demandedstreetsection DROP other_streets_checked;
INSERT INTO crudpermission (role,entity,`read`) VALUES ("guest","email",1);
DELETE FROM crudpermission WHERE role="admin" and entity="email" and `create`=0;
ALTER TABLE demandedstreetsection ADD company int(11) NOT NULL DEFAULT 0;
