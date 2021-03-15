<?php
/**
 * @package Include/help/ja
 */
?>
<h1><?php echo get_product_name(); ?>における不明モジュール</h1>
<p>
さまざまな理由で不明モジュールが発生することがあります。不明モジュールは、モジュールにおける特別な状態で、"直近で監視結果があるべきなのに、それが無い" ということを意味します。監視間隔の 2倍を超えて監視データを受信できない場合に不明になります。例えば、5分間隔の監視であれば、10分データを受信できないと不明になります。
</p>
<p>
不明モジュールの発生にはいくつかのケースがあります。
</p>
<ul class="list-type-disc mrgn_lft_30px">
    <li><?php echo get_product_name(); ?> サーバがダウンしている場合。それを再起動してくください。なぜダウンしたかを確認するために /var/log/pandora/pandora_server.log をチェックするのを忘れないようにしてください。</li>
    <li>tentacle サーバがダウンしている場合で、リモートサーバにインストールしている <?php echo get_product_name(); ?> からデータを取得できない場合。</li>
    <li>エージェントとサーバの間でネットワークの問題が発生している場合。</li>
    <li><?php echo get_product_name(); ?> エージェントが停止していて、サーバに情報を送信していない場合。</li>
    <li>ネットワークがダウンしているか、監視対象のリモートデバイスがダウンしているか IP アドレスが変わった場合(例えば、SNMP のクエリなど)。</li>
    <li>エージェントが間違った日時を報告していたり、過去の日時でデータを送っている場合。</li>
    <li>スクリプトやモジュールが動作する前に、エージェント自体で何らかの問題が発生している場合。この場合はエージェントを確認してください。</li>
</ul>
<p>
不明状態は、時には監視することが有用です。上記のような状態を警告するために、不明状態にアラートを設定することができます。
</p>
