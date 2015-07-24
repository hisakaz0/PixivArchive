# なにこれ?(パリコレ)

pixivの画像をだだっとdlしてくるやつです.

# How to use?

1. `userlist.csv`を自分好みに設定する.
2. `login.php`でpixivにログインする.
3. `dl.php`でdlが始まります.
4. `link.sh`で綺麗に飾ります.

__login.php__
```bash
./login.php <username|email> <password> [cookie_file]
```

__dl.php__
```bash
./dl.php <cookie_file> <userlist.csv>
```

__link.sh__
```bash
./link.sh <image_dir> <link_dir> <userlist>
```

user_idで管理しているため, 見やすいように`link.sh`でシンボリックリンクを貼ります(ぺたっ).

# 宝箱のかくれが

```bash
<image_dir> .images
<link_dir>   images
```

# Welcome your opinions!!!

意見や報告,指摘など募集中です.

### おわりに

`image_dir`や`link_dir`を任意の場所に変更したい(だれか).
