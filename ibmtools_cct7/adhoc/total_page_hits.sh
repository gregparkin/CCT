#!/bin/ksh
#

cd /ibm/cct6/counters

total=0

for a in `ls`
do
	count=`cat $a`
	total=`expr $total + $count`
	echo -e "$a = $count"
done	

echo -e "\nTotal Page Hits: $total"
