PR=$(ps -eaf | ps aux | grep $(echo import:students  | sed "s/^\(.\)/[\1]/g") | awk '{print $2}')
COUNT=$(ps ax |grep artisan |wc -l)
#!/bin/bash
echo $PR
PREFIX='C'$(date +%M%S)
echo $COUNT
if [ $COUNT -gt 4 ]; then
    date
    echo "Process is running."
else
    date
    echo "Process is not running."
    /opt/bitnami/php/bin/php /app/artisan import:students $PREFIX   1>> /app/stdout 2>> /app/error &
fi
