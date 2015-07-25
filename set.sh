#!/bin/bash

echo -n "Image directory[.images]        : "
read images
echo -n "Link directory[images]          : "
read link
echo -n "Cookie file path[cookie.txt]    : "
read cookie
echo -n "Userlist file path[userlist.csv]: "
read userlist

if [ -z $images ]; then
  images='.images'
elif [ ! -d $images ]; then
  echo "Error: the $images is not directory!"
  exit 1;
fi

if [ -z $link ]; then
  link='images'
elif [ ! -d $link ]; then
  echo "Error: the $link is not directory!"
  exit 1;
fi

if [ -z $cookie ]; then
  cookie='cookie.txt'
elif [ ! -f $cookie ]; then
  echo "Error: the $cookie is not file!"
  exit 1;
fi

if [ -z $userlist ]; then
  userlist='userlist.csv'
elif [ ! -f $userlist ]; then
  echo "Error: the $userlist is not file!"
  exit 1;
fi

# Write setting
echo $images   >  .setting
echo $link     >> .setting
echo $cookie   >> .setting
echo $userlist >> .setting


echo
echo "Images directory is   : "$images >&2
echo "Link directory is     : "$link >&2
echo "Cookie file path is   : "$cookie >&2
echo "Userlist file path is : "$userlist >&2

exit 0;
