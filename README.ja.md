# MigrationManager plugin for CakePHP 3

<p align="center">
    <a href="LICENSE.txt" target="_blank">
        <img alt="Software License" src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square">
    </a>
    <a href="https://github.com/elstc/cakephp-migration-manager/actions" target="_blank">
        <img alt="Build Status" src="https://img.shields.io/github/workflow/status/elstc/cakephp-migration-manager/CakePHP%20Plugin%20CI?style=flat-square">
    </a>
    <a href="https://codecov.io/gh/elstc/cakephp-migration-manager" target="_blank">
        <img alt="Codecov" src="https://img.shields.io/codecov/c/github/elstc/cakephp-migration-manager.svg?style=flat-square">
    </a>
    <a href="https://packagist.org/packages/elstc/cakephp-migration-manager" target="_blank">
        <img alt="Latest Stable Version" src="https://img.shields.io/packagist/v/elstc/cakephp-migration-manager.svg?style=flat-square">
    </a>
</p>

このプラグインは、マイグレーションをブラウザー経由で実行するGUIを提供します。

## 注意事項

データベースへのマイグレーション処理をブラウザーから直接実行できるため、操作によりデータを削除または破壊する可能性があります。
サーバーの制約によりCUIが直接利用できない場合にのみ導入してください。
また、設置時は認証・認可を適切に設定して、許可のないユーザーが実行できないようにしてください。

## 機能

- アプリケーション/プラグインのマイグレーション適用状況の一覧表示
- マイグレーションの適用/ロールバック
- マイグレーションファイルの表示

## インストール

[composer](http://getcomposer.org) を使用してインストールできます。

以下のようにして、Composer経由でプラグインをCakePHPアプリケーションへ追加します:

```
composer require elstc/cakephp-migration-manager
```

(CakePHP >= 3.6.0) アプリケーションの `src/Application.php` ファイルへ、次の行を追加します:

```
\Cake\Lib\Configure::write('Elastic/MigrationManager.baseController', \App\Controller\AppController::class);
$this->addPlugin('Elastic/MigrationManager', ['bootstrap' => true, 'routes' => true]);
```

(CakePHP <= 3.5.x) アプリケーションの `config/bootstrap.php` ファイルへ、次の行を追加します:

```
Configure::write('Elastic/MigrationManager.baseController', \App\Controller\AppController::class);
Plugin::load('Elastic/MigrationManager', ['bootstrap' => true, 'routes' => true]);
```

NOTE: マイグレーションの必要がないときは、`Plugin::load('Elastic/MigrationManager')` をコメントアウトしてプラグインを無効化しておくべきです。

## Configure key: Elastic/MigrationManager.baseController

MigrationManagerのコントローラーの基底となるクラスを指定します。デフォルトは `\Cake\Controller\Controller` です。
プラグインの bootstrap で使用するので、必ずプラグインのロード前にセットしてください。

例） `App\Controller\Admin\BaseController` を基底クラスにする場合:

```php
Configure::write('Elastic/MigrationManager.baseController', \App\Controller\Admin\BaseController::class);
```

## 使用方法

ブラウザから `https://{your-app-host}/migration-manager/migrations` へアクセスすることで使用できます。

### ロールバックを許可する

デフォルトではロールバック操作は行えないようになっています。
ロールバックを許可する場合は、アプリケーションの `config/bootstrap.php` ファイルへ、次の行を追加します:

```php
Configure::write('Elastic/MigrationManager.canRollback', true);
```
