#!/bin/bash

IMAGE_DIR=$1
LINK_DIR=$2
USERLIST_FILE=$3

if [ -z $USERLIST_FILE]; then # 引数足りない
  echo "usage: $0 <IMAGE_DIR> <LINK_DIR> <USERLIST_FILE>"
  exit 1;
fi

if [  -a $IMAGE_DIR ]; then #フォルダが無い場合エラー
  echo "The image_dir is '$IMAGE_DIR'."
else
  echo "The directory '$IMAGE_DIR' is not exist!"
  exit 1;
fi

if [ -a $LINK_DIR ]; then #  ファイルやディレクトリがあるか
  if [ -d $LINK_DIR ]; then # 指定したのがフォルダの場合はほうこくだけ
    echo "The link_dir is '$LINK_DIR'."
  else # ファイルだよ
    echo "The '$LINK_DIR' is file!"
    exit 1;
  fi
else #無い場合作る
  mkdir -p $LINK_DIR
  echo "Make the directory '$LINK_FILE'."
fi

IMAGE_DIR_ABS=$(cd $(dirname $IMAGE_DIR) && pwd)/$(basename $IMAGE_DIR)
LINE=`wc -l $USERLIST_FILE | grep -Eo ' *[0-9]+ *' | grep -Eo '[0-9]+'` #数字だけ
rm -rf $LINK_DIR/* # ゴミ削除
IFS=$'\n'
for ROW in `tail -n $((LINE - 1)) $USERLIST_FILE`
do
  ID=`echo $ROW | cut -d ',' -f1`
  NAME=`echo $ROW | cut -d ',' -f3`
  if [ -n $NAME ]; then
    ln -sf "$IMAGE_DIR_ABS/$ID" "$LINK_DIR/$NAME"
    echo ln -sf "$IMAGE_DIR_ABS/$ID" "$LINK_DIR/$NAME"
  fi
done

exit 0;
