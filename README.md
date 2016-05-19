# Jerrybendy/get-favicon

获取网站的Favicon图标并显示在你的网页上.

## 使用方法

1.添加`jerrybendy/get-favicon`到你的`composer.json`文件中

在命令行中执行:

```
composer require jerrybendy/get-favicon
```


2.使用以下方式获取网站图标

```php
<?php
require "vendor/autoload.php";

$favicon = new \Jerrybendy\Favicon\Favicon;

/**
 * 获取图标并显示在浏览器上
 */
$favicon->getFavicon('http://blog.icewingcc.com', false);

```

示例代码参见 [demo/basic-usage.php](demo/basic-usage.php)

## 其它用法

### 默认图标
默认图标用于在线获取图标失败时返回一个指定的图标文件作为代替。如果没有指定默认图标系统将会在获取失败时返回一个JSON字符串。

```php
$favicon = new \Jerrybendy\Favicon\Favicon;

$favicon->setDefaultIcon('default-icon.png');
```

### 文件映射
文件映射主要用于针对某些网站特殊处理（例如打不开的网站），如果网址能够匹配某个预设的规则，就返回指定的图标资源。

文件映射`setFileMap`函数接收一个数组作为参数，数组的键必须是正则表达式，值则可以对应到一个本地文件或网络文件的路径，你需要保证这个文件是可以正常读取的。

多个匹配规则会按顺序从上到下依次尝试匹配，并在第一次匹配成功后返回。

```php
$favicon = new \Jerrybendy\Favicon\Favicon;

$favicon->debug_mode = TRUE;

$favicon->setFileMap(array(
    '/www\.google\.com/i'   => 'http://www.baidu.com/favicon.ico',
    '/www\.facebook\.com/i' => 'cache/facebook.png',
));

$favicon->getFavicon('https://www.google.com', false);
```

在上面的例子中，如果输入的网址匹配了`/www\.google\.com/i`规则，将会返回百度的图标；如果匹配了`/www\.facebook\.com/i`规则将会读取本地已经存在的`facebook.png`文件。

### 使用缓存

`formatUrl`方法返回一个格式化后的完整的URL字符串, 可以被用作缓存的键名. `Favicon`类中没有内置任何缓存的实现, 所以需要自己根据实际情况选择不同的缓存方式.

`getFavicon`也可以接收可选的第二个参数,默认值是`FALSE`表示直接输出获取到的图标到浏览器. 设置成`TRUE`可以强制返回二进制数据而不显示,以方便在外部做缓存之类的操作.

[demo/use-cache.php](demo/use-cache.php)中的示例代码演示了如何添加和使用缓存.



### 调试模式
打开调试模式将会在系统的错误日志中输出一些信息，可以用来查看程序的运行时间、资源占用以及读取图标的来源等信息。因为输出是通过PHP内置的`error_log`函数实现的，所以可能需要通过PHP系统的错误日志去查看，或者`php_error.log`中。如果是用命令行`php -S`参数启动可以直接在控制台中看到日志内容。

打开调试模式：

```php
$favicon = new \Jerrybendy\Favicon\Favicon;

$favicon->debug_mode = TRUE;
```

## LICENSE

The MIT License (MIT)

Copyright (c) 2015 Jerry Bendy

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

