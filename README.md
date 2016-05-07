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

### 使用缓存

`formatUrl`方法返回一个格式化后的完整的URL字符串, 可以被用作缓存的键名. `Favicon`类中没有内置任何缓存的实现, 所以需要自己根据实际情况选择不同的缓存方式.

`getFavicon`也可以接收可选的第二个参数,默认值是`FALSE`表示直接输出获取到的图标到浏览器. 设置成`TRUE`可以强制返回二进制数据而不显示,以方便在外部做缓存之类的操作.

[demo/use-cache.php](demo/use-cache.php)中的示例代码演示了如何添加和使用缓存.



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

