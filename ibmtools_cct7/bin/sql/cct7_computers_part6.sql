set echo on

REM
REM cct7_computers_part6.sql
REM

REM
REM complex_partitions
REM
DECLARE
	v_this_lastid        NUMBER  := 0;
	v_this_lastid_parent NUMBER  := 0;
	v_save_lastid        NUMBER  := 0;
	v_save_lastid_parent NUMBER  := 0;
	v_partitions         NUMBER  := 0;
	v_par_count          NUMBER  := 0;
	v_mac_count          NUMBER  := 0;

CURSOR c_asset IS
	SELECT lastid, complex_lastid from new_cct7_computers 
		where complex_lastid > 0 order by complex_lastid;

BEGIN
	OPEN c_asset;
	LOOP
		v_partitions := v_partitions + 1;

		FETCH c_asset INTO v_this_lastid, v_this_lastid_parent;

		EXIT WHEN c_asset%NOTFOUND;

		v_par_count := v_par_count + 1;

		IF v_save_lastid_parent = 0 THEN

			v_save_lastid := v_this_lastid;
			v_save_lastid_parent := v_this_lastid_parent;
			v_partitions := 0;

		ELSIF v_save_lastid_parent != v_this_lastid_parent THEN

			v_mac_count := v_mac_count + 1;
			update new_cct7_computers set 
				complex_partitions = v_partitions where lastid = v_save_lastid_parent;

			DBMS_OUTPUT.PUT_LINE(CONCAT('V_PARITIONS=', v_partitions));
			v_partitions := 0;
			v_save_lastid := v_this_lastid;
			v_save_lastid_parent := v_this_lastid_parent;

		END IF;

		DBMS_OUTPUT.PUT_LINE(CONCAT('V_LASTID_PARENT=', v_this_lastid_parent));
	END LOOP;

	IF v_partitions > 0 AND v_save_lastid_parent > 0 THEN
		v_mac_count := v_mac_count + 1;
		update new_cct7_computers set 
			complex_partitions = v_partitions where lastid = v_save_lastid_parent;
		DBMS_OUTPUT.PUT_LINE(CONCAT('V_PARITIONS=', v_partitions));
	END IF;

	CLOSE c_asset;
	DBMS_OUTPUT.PUT_LINE(CONCAT('V_PAR_COUNT=', v_par_count));
	DBMS_OUTPUT.PUT_LINE(CONCAT('V_MAC_COUNT=', v_mac_count));
END;
/

select hostname, is_complex, complex_partitions from new_cct7_computers where complex_partitions > 0;

commit;

REM
REM timezone
REM
update new_cct7_computers set timezone = 'IST' where clli_fullname like '/IND/%';
update new_cct7_computers set timezone = 'IST' where clli_fullname like '/INDIA/%';
update new_cct7_computers set timezone = 'IST' where clli_fullname like '/USA/NO/CHENNAI%';
update new_cct7_computers set timezone = 'MST' where clli_fullname like '/USA/NO/DENVER%';
update new_cct7_computers set timezone = 'MST' where clli_fullname like '/USA/CO/%';
update new_cct7_computers set timezone = 'MST' where clli_fullname like '/USA/NO/LITTLETON%';
update new_cct7_computers set timezone = 'EST' where clli_fullname like '/USA/DC/%';

update new_cct7_computers set timezone = 'CST' where state = 'AL';
update new_cct7_computers set timezone = 'AKST' where state = 'AK';
update new_cct7_computers set timezone = 'MST' where state = 'AZ';
update new_cct7_computers set timezone = 'CST' where state = 'AR';
update new_cct7_computers set timezone = 'PST' where state = 'CA';
update new_cct7_computers set timezone = 'MST' where state = 'CO';
update new_cct7_computers set timezone = 'EST' where state = 'CT';
update new_cct7_computers set timezone = 'EST' where state = 'DE';
update new_cct7_computers set timezone = 'EST' where state = 'FL';
update new_cct7_computers set timezone = 'EST' where state = 'GA';
update new_cct7_computers set timezone = 'HST' where state = 'HI';
update new_cct7_computers set timezone = 'MST' where state = 'ID';
update new_cct7_computers set timezone = 'CST' where state = 'IL';
update new_cct7_computers set timezone = 'EST' where state = 'IN';
update new_cct7_computers set timezone = 'CST' where state = 'IA';
update new_cct7_computers set timezone = 'CST' where state = 'KS';
update new_cct7_computers set timezone = 'CST' where state = 'KY';
update new_cct7_computers set timezone = 'CST' where state = 'LA';
update new_cct7_computers set timezone = 'EST' where state = 'ME';
update new_cct7_computers set timezone = 'EST' where state = 'MD';
update new_cct7_computers set timezone = 'EST' where state = 'MA';
update new_cct7_computers set timezone = 'EST' where state = 'MI';
update new_cct7_computers set timezone = 'CST' where state = 'MN';
update new_cct7_computers set timezone = 'CST' where state = 'MS';
update new_cct7_computers set timezone = 'CST' where state = 'MO';
update new_cct7_computers set timezone = 'MST' where state = 'MT';
update new_cct7_computers set timezone = 'CST' where state = 'NE';
update new_cct7_computers set timezone = 'PST' where state = 'NV';
update new_cct7_computers set timezone = 'EST' where state = 'NH';
update new_cct7_computers set timezone = 'EST' where state = 'NJ';
update new_cct7_computers set timezone = 'MST' where state = 'NM';
update new_cct7_computers set timezone = 'EST' where state = 'NY';
update new_cct7_computers set timezone = 'EST' where state = 'NC';
update new_cct7_computers set timezone = 'CST' where state = 'ND';
update new_cct7_computers set timezone = 'EST' where state = 'OH';
update new_cct7_computers set timezone = 'CST' where state = 'OK';
update new_cct7_computers set timezone = 'PST' where state = 'OR';
update new_cct7_computers set timezone = 'EST' where state = 'PA';
update new_cct7_computers set timezone = 'EST' where state = 'RI';
update new_cct7_computers set timezone = 'EST' where state = 'SC';
update new_cct7_computers set timezone = 'CST' where state = 'SD';
update new_cct7_computers set timezone = 'CST' where state = 'TN';
update new_cct7_computers set timezone = 'CST' where state = 'TX';
update new_cct7_computers set timezone = 'MST' where state = 'UT';
update new_cct7_computers set timezone = 'EST' where state = 'VT';
update new_cct7_computers set timezone = 'EST' where state = 'VA';
update new_cct7_computers set timezone = 'PST' where state = 'WA';
update new_cct7_computers set timezone = 'EST' where state = 'WV';
update new_cct7_computers set timezone = 'CST' where state = 'WI';
update new_cct7_computers set timezone = 'MST' where state = 'WY';

update new_cct7_computers set timezone = 'MST' where clli = 'ABRDSDCO';
update new_cct7_computers set timezone = 'MST' where clli = 'ALBQNMPC';
update new_cct7_computers set timezone = 'CST' where clli = 'ANOKMNAN';
update new_cct7_computers set timezone = 'EST' where clli = 'ARTNVAOI';
update new_cct7_computers set timezone = 'EST' where clli = 'ATLNGAMA';
update new_cct7_computers set timezone = 'CST' where clli = 'BELNNMAG';
update new_cct7_computers set timezone = 'PST' where clli = 'BENDOR24';
update new_cct7_computers set timezone = 'MST' where clli = 'BLDRCOFI';
update new_cct7_computers set timezone = 'PST' where clli = 'BLHMWA01';
update new_cct7_computers set timezone = 'MST' where clli = 'BLNGMTSH';
update new_cct7_computers set timezone = 'PST' where clli = 'BMTNWA01';
update new_cct7_computers set timezone = 'MST' where clli = 'BOISIDMA';
update new_cct7_computers set timezone = 'MST' where clli = 'BOISIDSA';
update new_cct7_computers set timezone = 'CST' where clli = 'BTNDIAAW';
update new_cct7_computers set timezone = 'MST' where clli = 'BUTTMT20';
update new_cct7_computers set timezone = 'MST' where clli = 'BZMNMTSH';
update new_cct7_computers set timezone = 'MST' where clli = 'CDCYUTMA';
update new_cct7_computers set timezone = 'CST' where clli = 'CDRRIADT';
update new_cct7_computers set timezone = 'MST' where clli = 'CHYNWYCB';
update new_cct7_computers set timezone = 'MST' where clli = 'CLSPCOMA';
update new_cct7_computers set timezone = 'MST' where clli = 'CLSQCODE';
update new_cct7_computers set timezone = 'MST' where clli = 'CLWLIDSE';
update new_cct7_computers set timezone = 'MST' where clli = 'CODYWYSG';
update new_cct7_computers set timezone = 'MST' where clli = 'COTTONWOOD';
update new_cct7_computers set timezone = 'MST' where clli = 'CSGRAZ05';
update new_cct7_computers set timezone = 'MST' where clli = 'CSPRWYMA';
update new_cct7_computers set timezone = 'EST' where clli = 'DBLNOHIB';
update new_cct7_computers set timezone = 'EST' where clli = 'DELTOHLC';
update new_cct7_computers set timezone = 'CST' where clli = 'DESMIAHS';
update new_cct7_computers set timezone = 'MST' where clli = 'DNVRCOAR';
update new_cct7_computers set timezone = 'MST' where clli = 'DNVRCODP';
update new_cct7_computers set timezone = 'MST' where clli = 'DNVRCOMA';
update new_cct7_computers set timezone = 'MST' where clli = 'DNVRCOMC';
update new_cct7_computers set timezone = 'MST' where clli = 'DNVRCOMD';
update new_cct7_computers set timezone = 'MST' where clli = 'DNVRCOSC';
update new_cct7_computers set timezone = 'CST' where clli = 'DTLKMNDO';
update new_cct7_computers set timezone = 'MST' where clli = 'DURNCOGA';
update new_cct7_computers set timezone = 'MST' where clli = 'EDWDNM01';
update new_cct7_computers set timezone = 'CST' where clli = 'ELZBNJVG';
update new_cct7_computers set timezone = 'MST' where clli = 'ENWDCOEC';
update new_cct7_computers set timezone = 'PST' where clli = 'EUGNOR53';
update new_cct7_computers set timezone = 'MST' where clli = 'FLGSAZBG';
update new_cct7_computers set timezone = 'MST' where clli = 'FRTNNMMZ';
update new_cct7_computers set timezone = 'MST' where clli = 'FTCLCOBK';
update new_cct7_computers set timezone = 'CST' where clli = 'GDISNENW';
update new_cct7_computers set timezone = 'MST' where clli = 'GDJTCOER';
update new_cct7_computers set timezone = 'MST' where clli = 'GLOBAZ05';
update new_cct7_computers set timezone = 'MST' where clli = 'GLSPCOBX';
update new_cct7_computers set timezone = 'MST' where clli = 'GLTTWYSA';
update new_cct7_computers set timezone = 'MST' where clli = 'GRELCOCR';
update new_cct7_computers set timezone = 'MST' where clli = 'GRFLMTMA';
update new_cct7_computers set timezone = 'MST' where clli = 'GRNBCOMA';
update new_cct7_computers set timezone = 'MST' where clli = 'GRNTNMBA';
update new_cct7_computers set timezone = 'MST' where clli = 'HLNAMTGX';
update new_cct7_computers set timezone = 'PST' where clli = 'HMTNOR70';
update new_cct7_computers set timezone = 'PST' where clli = 'ISQHWA60';
update new_cct7_computers set timezone = 'MST' where clli = 'JCSNMSBI';
update new_cct7_computers set timezone = 'EST' where clli = 'JRCYNJ67';
update new_cct7_computers set timezone = 'MST' where clli = 'LARMWYBS';
update new_cct7_computers set timezone = 'PST' where clli = 'LGVWWA02';
update new_cct7_computers set timezone = 'MST' where clli = 'LKWDCO27';
update new_cct7_computers set timezone = 'MST' where clli = 'LKWDCOTC';
update new_cct7_computers set timezone = 'EST' where clli = 'LSANCARC';
update new_cct7_computers set timezone = 'MST' where clli = 'LSCRNMMA';
update new_cct7_computers set timezone = 'PST' where clli = 'LSTNIDSH';
update new_cct7_computers set timezone = 'MST' where clli = 'LTTNCOML';
update new_cct7_computers set timezone = 'EST' where clli = 'LWCTOHAP';
update new_cct7_computers set timezone = 'CST' where clli = 'LXTNNENW';
update new_cct7_computers set timezone = 'PST' where clli = 'MDFDOR74';
update new_cct7_computers set timezone = 'CST' where clli = 'MNDNNDSA';
update new_cct7_computers set timezone = 'CST' where clli = 'MPLSMN13';
update new_cct7_computers set timezone = 'CST' where clli = 'MPLSMNDT';
update new_cct7_computers set timezone = 'PST' where clli = 'MSLKWA60';
update new_cct7_computers set timezone = 'MST' where clli = 'MSSLMTBS';
update new_cct7_computers set timezone = 'MST' where clli = 'MTRSCOAP';
update new_cct7_computers set timezone = 'MST' where clli = 'OGDNUTMA';
update new_cct7_computers set timezone = 'PST' where clli = 'OLYMWACT';
update new_cct7_computers set timezone = 'CST' where clli = 'OMAHNE78';
update new_cct7_computers set timezone = 'CST' where clli = 'OMAHNEKG';
update new_cct7_computers set timezone = 'MST' where clli = 'PCTLIDCP';
update new_cct7_computers set timezone = 'MST' where clli = 'PHNXAZ30';
update new_cct7_computers set timezone = 'MST-AZ' where clli = 'PHNXAZ30';
update new_cct7_computers set timezone = 'MST' where clli = 'PHNXAZFP';
update new_cct7_computers set timezone = 'MST' where clli = 'PHNXAZFT';
update new_cct7_computers set timezone = 'CST' where clli = 'PLMOMNAC';
update new_cct7_computers set timezone = 'MST' where clli = 'PROVUTMA';
update new_cct7_computers set timezone = 'MST' where clli = 'PRSCAZEW';
update new_cct7_computers set timezone = 'PST' where clli = 'PTLDOR69';
update new_cct7_computers set timezone = 'PST' where clli = 'PTLDOR74';
update new_cct7_computers set timezone = 'PST' where clli = 'PTLDORCS';
update new_cct7_computers set timezone = 'MST' where clli = 'PUBLCOMZ';
update new_cct7_computers set timezone = 'CST' where clli = 'RCFDMN03';
update new_cct7_computers set timezone = 'MST' where clli = 'RCSPWYCB';
update new_cct7_computers set timezone = 'MST' where clli = 'RPCYSDCO';
update new_cct7_computers set timezone = 'PST' where clli = 'RSBGOR70';
update new_cct7_computers set timezone = 'MST' where clli = 'RSWLNMMA';
update new_cct7_computers set timezone = 'MST' where clli = 'RVTNWYSA';
update new_cct7_computers set timezone = 'PST' where clli = 'SALMOR58';
update new_cct7_computers set timezone = 'MST' where clli = 'SLKCUT75';
update new_cct7_computers set timezone = 'MST' where clli = 'SLKCUTCB';
update new_cct7_computers set timezone = 'MST' where clli = 'SLTHCOAF';
update new_cct7_computers set timezone = 'MST' where clli = 'SNFENMSC';
update new_cct7_computers set timezone = 'PST' where clli = 'SPKNWA28';
update new_cct7_computers set timezone = 'MST' where clli = 'SRVSAZ03';
update new_cct7_computers set timezone = 'CST' where clli = 'STCDMNHP';
update new_cct7_computers set timezone = 'MST' where clli = 'STGRUTIG';
update new_cct7_computers set timezone = 'CST' where clli = 'STPLMNMK';
update new_cct7_computers set timezone = 'MST' where clli = 'STSPCOMA';
update new_cct7_computers set timezone = 'PST' where clli = 'STTLWA60';
update new_cct7_computers set timezone = 'PST' where clli = 'STTLWA72';
update new_cct7_computers set timezone = 'PST' where clli = 'STTLWABP';
update new_cct7_computers set timezone = 'PST' where clli = 'STTLWAEL';
update new_cct7_computers set timezone = 'CST' where clli = 'SXCYIAJM';
update new_cct7_computers set timezone = 'CST' where clli = 'SXFLSDCO';
update new_cct7_computers set timezone = 'PST' where clli = 'TACMWASO';
update new_cct7_computers set timezone = 'MST' where clli = 'TAOSNMAE';
update new_cct7_computers set timezone = 'MST' where clli = 'TAOSNMMZ';
update new_cct7_computers set timezone = 'MST' where clli = 'TCSNAZLF';
update new_cct7_computers set timezone = 'MST' where clli = 'TEMPAZCC';
update new_cct7_computers set timezone = 'MST' where clli = 'TEMPAZEV';
update new_cct7_computers set timezone = 'MST' where clli = 'THTNCO18';
update new_cct7_computers set timezone = 'MST' where clli = 'THTNCOAI';
update new_cct7_computers set timezone = 'MST' where clli = 'TWFLIDSH';
update new_cct7_computers set timezone = 'PST' where clli = 'VANCWAQB';
update new_cct7_computers set timezone = 'PST' where clli = 'WLWLWA01';
update new_cct7_computers set timezone = 'PST' where clli = 'YAKMWA02';
update new_cct7_computers set timezone = 'MST' where clli = 'YUMAAZ05';

COMMIT;

REM
REM Print Duplicates
REM
select
  lastid,
  asset_tag
from
  new_cct7_computers A
where
  rowid > (select min(rowid) from new_cct7_computers B WHERE B.lastid = A.lastid and B.asset_tag = A.asset_tag);

REM
REM Remove Duplicates
REM
delete from
	new_cct7_computers a
where
	a.rowid > any (select b.rowid from new_cct7_computers b where a.lastid = b.lastid and a.asset_tag = b.asset_tag);

commit;

REM
REM os_lite
REM
update new_cct7_computers set os_lite='AIX' where upper(operating_system) like '%AIX%';
update new_cct7_computers set os_lite='BSD' where upper(operating_system) like '%BSD%';
update new_cct7_computers set os_lite='CISCO' where upper(operating_system) like '%CISCO%';
update new_cct7_computers set os_lite='DYNIXptx' where upper(operating_system) like '%DYNIX%';
update new_cct7_computers set os_lite='HPUX' where upper(operating_system) like '%HP%';
update new_cct7_computers set os_lite='HPUX' where upper(operating_system) like '%STRATUS%';
update new_cct7_computers set os_lite='Linux' where upper(operating_system) like '%LINUX%';
update new_cct7_computers set os_lite='OPENVMS' where upper(operating_system) like '%OPENVMS%';
update new_cct7_computers set os_lite='RADWARE' where upper(operating_system) like '%RADWARE%';
update new_cct7_computers set os_lite='SCO' where upper(operating_system) like '%SCO%';
update new_cct7_computers set os_lite='SMPDCOSx' where upper(operating_system) like '%DCOS%';
update new_cct7_computers set os_lite='SunOS' where upper(operating_system) like '%SOLARIS%';
update new_cct7_computers set os_lite='SunOS' where upper(operating_system) like '%SUN%';
update new_cct7_computers set os_lite='SunOS' where upper(operating_system) like '%SUN%';
update new_cct7_computers set os_lite='UNISYS' where upper(operating_system) like '%UNISYS%';
update new_cct7_computers set os_lite='UNIXWARE' where upper(operating_system) like '%UNIXWARE%';
update new_cct7_computers set os_lite='UNKNOWN' where upper(operating_system) like '%UNKNOWN%';
update new_cct7_computers set os_lite='VAX' where upper(operating_system) like '%VAX%';
update new_cct7_computers set os_lite='VMWARE' where upper(operating_system) like '%VMWARE%';
update new_cct7_computers set os_lite='VOS' where upper(operating_system) like '%VOS%';
update new_cct7_computers set os_lite='WindowsNT' where upper(operating_system) like '%WINDOWS%';
REM update new_cct7_computers set os_lite='WindowsNT' where upper(operating_system) like '%NT%';  E(NT)ERPRISE 
update new_cct7_computers set os_lite='WindowsXP' where upper(operating_system) like '%XP%';
update new_cct7_computers set os_lite='Windows2000' where upper(operating_system) like '%2000%';
update new_cct7_computers set os_lite='Windows2003' where upper(operating_system) like '%2003%';
update new_cct7_computers set os_lite='Windows2008' where upper(operating_system) like '%2008%';
update new_cct7_computers set os_lite='Windows2000' where upper(operating_system) like '%WIN 2K%';

update new_cct7_computers set os_lite='AIX_COMPLEX' where upper(operating_system) = 'NO OS - THIS COMPLEX HOSTS AIX NPARS';
update new_cct7_computers set os_lite='HP_COMPLEX'  where upper(operating_system) = 'NO OS - THIS COMPLEX HOSTS HP-UX NPARS';
update new_cct7_computers set os_lite='SUN_COMPLEX' where upper(operating_system) = 'NO OS - THIS COMPLEX HOSTS SOLARIS NPARS';

update new_cct7_computers set operating_system='HPUX', os_lite='HPUX' where operating_system is null and hostname like 'HP%';
update new_cct7_computers set operating_system='CISCO', os_lite='CISCO' where operating_system is null and hostname like 'LTX%';
update new_cct7_computers set operating_system='Linux', os_lite='Linux' where operating_system is null and hostname like 'LX%';
update new_cct7_computers set operating_system='SunOS', os_lite='SunOS' where operating_system is null and hostname like 'SU%';
update new_cct7_computers set operating_system='VMWARE', os_lite='VMWARE' where operating_system is null and hostname like 'QT%';
update new_cct7_computers set operating_system='VOS', os_lite='VOS' where operating_system is null and hostname like 'NMA%';

COMMIT;
quit;
