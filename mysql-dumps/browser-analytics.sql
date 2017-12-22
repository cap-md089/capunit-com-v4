select * from EventManagement.BrowserAnalytics order By Hits Desc;

SELECT CAPID FROM BrowserAnalytics group by CAPID;

select Type, Version, Hits from BrowserAnalytics where CAPID = 542488 and Hits in ((select Max(Hits) from
BrowserAnalytics where CAPID = 542488));

select M.CAPID, M.NameFirst, M.NameLast, (SELECT SUM(Hits) from BrowserAnalytics where CAPID = B.CAPID) AS
Hits from Data_Member as M inner join BrowserAnalytics as B on M.CAPID = B.CAPID group by CAPID order by
Hits desc;
	
select sum(Hits) as Hits from BrowserAnalytics where CAPID = 'www';

select B.Type, B.Version, (select sum(Hits) from BrowserAnalytics as A where A.Type = B.Type AND A.Version = B.Version) as Hits from BrowserAnalytics as B;

Select Type as B, (select SUM(Hits) from BrowserAnalytics where Type = B) as Hits from BrowserAnalytics group by B order by Hits desc;