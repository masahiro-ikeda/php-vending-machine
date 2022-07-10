# php-vending-machine

## 説明

簡単な自動販売機プログラムです。
お金を入れて好きなドリンクを購入できます。

## 起動方法

dockerを使って起動します。
事前にローカルPCにdockerをインストールしてください。

https://docs.docker.com/get-docker/

- [Windowsの場合](https://docs.docker.jp/docker-for-windows/index.html)
- [Macの場合](https://docs.docker.jp/docker-for-mac/index.html)

ルートディレクトリに移動して `docker-compose up -d` コマンドを実行してください。

dockerが起動できたらブラウザから下記のURLにアクセスしてください。

http://localhost/vending-machine.php?pay10=2&pay100=1&drink=coffee


## 動かし方

クエリパラメータで支払うお金と購入するドリンクを指定します。

- お金

```
10円: pay10
50円: pay50
100円: pay100
500円: pay500
1000円: pay1000
```

- ドリンク

1. `drink=ドリンク名`で指定します。
2. `./data/drink.csv`ファイルに、`ドリンク名,値段,在庫数`が記載してあります。

↑の起動確認用のURLは`100円1枚と10円2枚を入れて、coffeeを購入する`というリクエストになります。

## 課題

### (1) バグの解消

- AsIs

お釣りの硬貨が足りないときは、返せる分のお釣りだけ返却します。<br>
例えばお釣りが80円あるけど、50円玉1枚と10円玉2枚しかなければそれだけ返却して終わりです。<br>
僕が利用者ならキレて二度と使いません。<br>

現金残高のデータは`./data/cash.csv`で管理しています。

- ToBe

お釣りの硬貨が足りないときは購入処理をストップさせる。

1. 画面表示は下記のようになるようにしてください。
```
200円のお金を入れました
おつりのお金が足りません
```

2. ドリンク在庫、現金残高は購入処理前の状態になるようにしてください。

### (2) リファクタリング

1. 問題点

課題(1)をクリアしたらわかると思いますが`可読性`が非常に悪いコードです。
理由は1つのファイルにすべての処理が書かれているため見通しが悪いことです。
見通しが悪い理由は、`処理の流れを把握するためにすべての処理を読む必要がある`ことに尽きます。

実際、課題(1)は`おつりの枚数計算`と`永続化`が処理されてる場所がわかればすぐに修正できます。

そこで処理内の責務を分けて、いくつかのクラスやメソッドに分割して見通しを良くしましょう。
これが`責務分割`が重要になる理由の一つになります。

2. リファクタリングの観点

- 業務ロジックを分割

まずは`アプリケーションを動かすための処理`と`業務処理`を分割しましょう。
前者はWebアプリ特有の処理です。すなわちクエリパラメータの受け取りと画面描画です。
そして後者は、①お金を払う、②ドリンクを買う、③おつりを計算する、④永続化の処理です。
`Service`クラスを作り、後者の業務ロジックに相当する処理を分割しましょう。

- 業務ロジック内の分割(1)

今度は業務ロジック内もリファクタリングしていきましょう。
業務ロジック内も`業務要件に関係する処理`と`システム要件に関する処理`に分けることができます。
①〜④のうち、①②③は`ドリンクを売るという業務`という業務要件に関係する処理です。
一方で④は、`データはcsvファイルに保存しましょう`というシステム要件に関係する処理です。

この2つが混ざった状態だと業務ロジックの見通しが悪くなります。

そこで`XxxDao`クラスを作成し、④に関わる処理を業務ロジックから切り出しましょう。
また`XxxDao`を作ると、他の業務ロジックから共通で呼び出せて`ソースの重複が防げる`というメリットも享受できます。

- 業務ロジック内の分割(2)

まだまだ業務ロジックはリファクタリングできます。
最後は`業務処理の流れ`と`業務処理の細かいロジック`をさらに分割します。

前者は文字通り`業務処理を行う順番`です。ドリンクを買う業務だと、支払金額を計算して、お金が足りていることを確認してから、
ドリンクを購入して、結果を永続化する、という順番に処理を行う必要があります。
お金が足りないのにジュースが買えたら大変なので、ちゃんと順番を考えて制御することは非常に重要です。

後者は`どうやって処理を実現するか？`です。
例えばお釣りの計算ロジックは、現金残高を見て返却する硬貨を決める、枚数が最小になるために額の大きな硬貨を優先して返すなど、
非常に`細かい業務ルール`を実現するために複雑な処理を行っています。

実はこの２つを分離することでさらに可読性を高めることができます。
`細かい業務ルール`を`XxxxLogic`クラスに切り出し、Serviceクラスは処理の順番を、Logicクラスは個々の処理の詳細を制御するように分けましょう。
この自動販売機プログラムの場合は、おつりの計算処理を`CaluculateChangeCountLogic`に分けると良いでしょう。

このように分割できると、例えば「お釣りの金額と枚数が合わない」という不具合が出たときに、どのクラスの処理に問題がありそうか予想ができるようになり、不具合修正にかかる時間を大幅に短縮できます。

実際の現場では上述のようにキレイに責務分割を行うことは難しいですが、上記の原則を理解しておくと読みやすいコードを書いたり、既存の処理内容の問題点に早く気付けるようになると思います。


## tips

- URL設計が[REST API](https://www.redhat.com/ja/topics/api/what-is-a-rest-api)に沿ってないですが、動かしやすくするためなのでご容赦ください。
- DB使うのは面倒だったので永続化にcsvファイルを利用しました。トランザクション制御は全くできないですが勘弁してください。
