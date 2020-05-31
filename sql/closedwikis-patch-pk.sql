-- Convert unique index to primary key
-- See T243986
ALTER TABLE /*_*/closedwikis
DROP INDEX /*i*/cw_wiki,
ADD PRIMARY KEY (cw_wiki);
