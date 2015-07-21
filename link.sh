#!/bin/bash

CMD=$1
if [ -z $CMD ]; then
  echo "usage: $0 <list|link> <arg..>"
  echo "list <USERLIST_FILE> [LINK_FILE]"
  echo "link <IMAGE_DIR> <LINK_DIR> <LINK_FILE>"
  exit 1;
fi

if [ $CMD = 'list' ]; then # リンクを作るための一覧を作成

  USERLIST_FILE=$2
  LINK_FILE=$3

  if [ -z $USERLIST_FILE ]; then # 引数たりない
    echo "usage: $0 list <USERLIST_FILE> [LINK_FILE]"
    exit 1
  fi

  if [ -a $USERLIST_FILE ]; then # ユーザリストファイルがない
    echo "Use the userlist file '$USERLIST_FILE'"
  else
    echo "The userlist file '$USERLIST_FILE' is not exist!"
    exit 1
  fi

  if [ -z $LINK_FILE ]; then # リンクファイルに指定がない
    LINK_FILE='linklist.csv' # デフォルトネーム
  fi


  if [ -a $LINK_FILE ]; then  # リンクファイルがある
    # リンクファイルに無い分だけ取得
    L_LINE=`wc -l $LINK_FILE | grep -Eo ' *\d+ *' | grep -Eo '\d+'` #数字だけ
    U_LINE=`wc -l $USERLIST_FILE | grep -Eo ' *\d+ *' | grep -Eo '\d+'` #数字だけ
    LINE=$((U_LINE - L_LINE))
    for ROW in `tail -n $LINE $USERLIST_FILE` # user_idを無い分だけ書き込み
    do
      ID=`echo $ROW | cut -d ',' -f1`
      echo "$ID," >> $LINK_FILE
    done

    echo "Update the link_file '$LINK_FILE' is done."
  else #リンクファイルがない
    U_LINE=`wc -l $USERLIST_FILE | grep -Eo ' *\d+ *' | grep -Eo '\d+'` #数字だけ
    echo "user_id,display_name" > $LINK_FILE
    for ROW in `tail -n $((U_LINE - 1)) $USERLIST_FILE` # user_idを全て書き込み 
    do
      ID=`echo $ROW | cut -d ',' -f1`
      echo "$ID," >> $LINK_FILE
    done
    echo "Write the link_file '$LINK_FILE'"
  fi

elif [ $CMD = 'link' ]; then # リンクをはる

  IMAGE_DIR=$2
  LINK_DIR=$3
  LINK_FILE=$4

  if [ -z $LINK_FILE ]; then # 引数足りない
    echo "usage: $0 link <IMAGE_DIR> <LINK_DIR> <LINK_FILE>"
    exit 1;
  fi

  if [  -a $IMAGE_DIR ]; then #フォルダが無い場合作る
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
  L_LINE=`wc -l $LINK_FILE | grep -Eo ' *\d+ *' | grep -Eo '\d+'` #数字だけ
  for ROW in `tail -n $((L_LINE - 1)) $LINK_FILE`
  do
    ID=`echo $ROW | cut -d ',' -f1`
    NAME=`echo $ROW | cut -d ',' -f2`
    if [ -n $NAME ]; then
      ln -sf "$IMAGE_DIR_ABS/$ID" "$LINK_DIR/$NAME"
      echo ln -sf "$IMAGE_DIR_ABS/$ID" "$LINK_DIR/$NAME"
    fi
  done
else
  echo "usage: $0 <list|link> <arg..>"
  echo "list <USERLIST_FILE> [LINK_FILE]"
  echo "link <IMAGE_DIR> <LINK_DIR> <LINK_FILE>"
  exit 1;
fi







