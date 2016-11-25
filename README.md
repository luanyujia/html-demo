# webpagemodule
一些模板页面和效果

* [蓝色通用企业首页模板](https://fairyly.github.io/html-demo/蓝色通用企业首页模板/index.html) (IE9+，chrome,FF等)
* [列表信息滚动](https://fairyly.github.io/html-demo/列表信息滚动/demo.html)
* [js图片裁剪效果](https://fairyly.github.io/html-demo/JavaScriptImageClip/demo.html)
* [响应式两栏资讯博客主题页面](https://fairyly.github.io/html-demo/autoblog/demo.html) (IE9+，chrome,FF等)
* [登陆界面](https://fairyly.github.io/html-demo/login/demo.html)(IE9+，chrome,FF等)
* 一些CSS3效果(ie10+/chrome等)：
      * [CSS3实现的导航](https://fairyly.github.io/html-demo/css3_effects/nav/nav.html)
      * [CSS3 Tips效果](https://fairyly.github.io/html-demo/css3_effects/button/demo.html)
      * [CSS3 3D效果](https://fairyly.github.io/html-demo/css3_effects/css3d-fz/demo.html)   
      * [CSS3 翻转](https://fairyly.github.io/html-demo/css3_effects/css3d-fz/fz.html)
      * [CSS3 按钮效果](https://fairyly.github.io/html-demo/css3_effects/button2/demo.html)
      * [CSS3 照片墙](https://fairyly.github.io/html-demo/css3_effects/photowall/demo.html)
      * [](https://fairyly.github.io/css3_effects/nav-slide/nav-slide.html/CSS3侧滑导航)
      * [按钮波纹](https://fairyly.github.io/html-demo/按钮波纹/demo.html)
      
* [地址选择](https://fairyly.github.io/html-demo/address/newAddress.html)

* [多文件上传](https://fairyly.github.io/html-demo/multupload/index.html)  
     (使用jquery.jquery.fileupload.js)本地环境测试图片的url存在了但是出现500错误
     ```
          <!DOCTYPE HTML>
          <html>
          <head>
          <meta charset="utf-8">
          <title>jQuery File Upload Example</title>
          </head>
          <body>
          <input id="fileupload" type="file" name="files[]" data-url="php/" multiple>
          <script src="js/jquery-2.2.3.min.js"></script>
          <script src="js/jquery.ui.widget.js"></script>
          <script src="js/jquery.iframe-transport.js"></script>
          <script src="js/jquery.fileupload.js"></script>
          <script>
          $(function () {
              $('#fileupload').fileupload({
                  dataType: 'json',
                  done: function (e, data) {
                    console.log(data);
                      $.each(data.result.files, function (index, file) {
                          $('<p/>').text(file.name).appendTo(document.body);
                          console.log(file.url)
                          $('<img src='+file.url+'/>').text(file.name).appendTo(document.body);
                      });
                  }
              });
          });
          </script>
          </body> 
          </html>
     ```


