# Jerrybendy/get-favicon

获取网站的Favicon图标并显示在你的网页上.

## 使用方法

1.添加`jerrybendy/get-favicon`到你的`composer.json`文件中, 并执行`composer install`

```json
"require": {
    "jerrybendy/get-favicon": "*"
}
```


2.使用以下方式获取网站图标

```php
<?php
require "vendor/autoload.php";


$favicon = new \Jerrybendy\Favicon\Favicon;

/**
 * 获取图标并显示在浏览器上
 */
$favicon->get_favicon('http://blog.icewingcc.com', false);

```


## 其它用法

`get_favicon`也可以接收可选的第二个参数,默认值是`FALSE`表示直接输出获取到的图标到浏览器. 设置成`TRUE`可以强制返回二进制数据而不显示,以方便在外部做缓存之类的操作.

```php
$favicon = new \Jerrybendy\Favicon\Favicon;

$icon = $favicon->get_favicon('http://blog.icewingcc.com', true);

// 设置输出header
foreach ($favicon->getHeader() as $header) {
    @header($header);
}

echo $icon;
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

