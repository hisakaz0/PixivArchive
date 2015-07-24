# なにこれ?(パリコレ)

pixivの画像をだだっとdlしてくるやつです.
作者ごとにpixivに登録されている全ての作品をdlします.
うごいら,普通のイラスト,マンガに対応してます.
`userlist.csv`に`user_id`を指定するだけです.
更新があれば前回の分から新しく登録された作品をdlします.

# How to use?

### 1. `userlist.csv`を自分好みに設定する

1行目に`user_id,last_artwork_id,display_nameに`と書きます.
あとは,dlしたいuser_idを2行目以降に書いて下さい(下のようになります).
user_idはpixivの各ユーザページを見て下さい(URLのmember.php?id=なんちゃらのトコロです).
```csv
user_id,last_artwork_id,display_name
235127
3302692
```

### 2. `login.php`でpixivにログインする.

`cookie_file`が指定されない場合は`cookie.txt`で保存されます.
```bash
$ ./login.php <username|email> <password> [cookie_file]
```
### 3. `dl.php`でdlが始まります.

ユーザ毎に作品をdlします.

```bash
$ ./dl.php <cookie_file> <userlist.csv>
```

dlが終わると`userlist.csv`のlast_artwork_idとdisplay_nameが書き加えられます.
display_nameは任意に変更して頂いても構いません
(`link.sh`でシンボリックリンクを貼るときに表示されるディレクトリ名になります).
### 4. `link.sh`で綺麗にします.

作品はuser_id毎に管理しています.しかし,それは人が識別するのは難しいです.
そのため, `link.sh`で作者名のシンボリックリンクを貼ります(ぺたっ).
```bash
$ ./link.sh <image_dir> <link_dir> <userlist>
```


# 宝箱のかくれが

`.images(ドットあり)`が画像が実際に保存されている場所です.`images(ドットなし)`は作者名ごとにシンボリックリンクが貼られているディレクトです.
```bash
<image_dir> .images
<link_dir>   images
```

# Welcome your opinions!!!

意見や報告,指摘など募集中です(プログラムきたないとの罵声も歓迎です).
改善点もウェルカムです.

## ScreenShot
dlしている様子

![dl_scene](https://41.media.tumblr.com/390a8b72574a56da827f22be3df6ad20/tumblr_nrzzcmWPDm1ut129uo1_1280.jpg)

`userlist.csv`のサンプル(`dl.php`実行後)

![userlist.csv](https://41.media.tumblr.com/8ed0e7a3bf2973b01eb918390e05bbad/tumblr_nrzzcmWPDm1ut129uo2_500.jpg)

### おわりに

`image_dir`や`link_dir`を任意の場所に変更したい(だれか).
