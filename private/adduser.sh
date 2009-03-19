#!/bin/sh
if [ -z "$1" -o ! -f "$1" ]; then
    printf "Usage: %s dbfile\n" `basename "$0"`
    exit 1
elif [ ! -w "$1" ]; then
    printf "Error: You need write permissions for %s\n" "$1" 1>&2
    exit 2
else
    dbfile=$1
fi
for util in md5sum sqlite; do
    if [ ! -x "`which $util 2>/dev/null`" ]; then
        echo "We need the $util utility"
        exit 3
    fi
done

echo "Add a user to the admin table"
echo ""

printf "Username: "
read user
printf "Password: "
read pass
md5pass=`printf "$pass"|md5sum|cut -c 1-32`
sql=`printf "INSERT INTO admins (name, pass) VALUES ('%s', '%s')" "$user" "$md5pass"`
sqlite "$dbfile" "$sql"
