# bash ftptest.sh lp0164d adc
ftp -i -n -v $1 << ftp_end
user $2

prompt off
quote namefmt 1
bin

cd /www/zendsvr/htdocs/Samples/Toolkit/RAW
prompt
mput *

quit

ftp_end

