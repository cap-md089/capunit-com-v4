use EventManagement;
# Bits of code for use:
# Simulates two primary keys with auto increment, kind of?
#select (select MAX(EventNumber) as NEventNumber from TEventInformation where AccountID='md089')+1;
# Selects members present in one table but not the other
#select * from Import_Member where CAPID not in (SELECT CAPID from Data_Member) order by NameLast;
# Gets file sizes for an account
#select SUM(LENGTH(Data)) as Size from FileData where AccountID = 'md089';
# Gets the 12-24th files, not 12-12 (Starts at 12, gets 12 count)
#select * from FileData limit 12, 12;
# Selects people who have not been assigned to a flight
#(select CAPID from Member where CAPID not in (SELECT capid as CAPID from Flights));
# Selects unique errors based on name
#select * from ErrorMessages where id in (select min(id) from ErrorMessages where resolved = 0 group by message);
# Selects all people from the browser analytics and orders by hits
#select M.CAPID, M.NameFirst, M.NameLast, (SELECT SUM(Hits) from BrowserAnalytics where CAPID = B.CAPID) AS Hits from Data_Member as M inner join BrowserAnalytics as B on M.CAPID = B.CAPID group by CAPID order by Hits desc;
# Selects a prefered browser for a given user
#select Type, Version, Hits from BrowserAnalytics where CAPID = 542488 and Hits in ((select Max(Hits) from BrowserAnalytics where CAPID = 542488));
