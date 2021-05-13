
// 图片压缩



//图片压缩
// flie 压缩的倍数(0-1之间,不能选0);
// 回显和压缩写在了一起
// function uploadImageHandle(File_domain, Multiple) {
//     console.log(e)
//         // 创建实例
//         var reader = new FileReader();
//          var img = document.createElement('img');
//         // 读取上传的图片的信息(lastModified, lastModifiedDate, name, size, type等)
//         var file = File_domain.files[0];
//          // 记下上传的图片的name, 后面会用到
//         var flieName= File_domain.files[0].name;
//         console.log(flieName);
//         // 记下上传的图片的类型, 后面会用到
//         var fileType = file.type;
//         // 生成canvas画布
//         var canvas = document.createElement('canvas');
//         var context = canvas.getContext('2d');
//         // MDN: 该方法会读取指定的 Blob 或 File 对象。读取操作完成的时候，
//         // readyState 会变成已完成（DONE），并触发 loadend 事件，
//         // 同时 result 属性将包含一个data:URL格式的字符串（base64编码）以表示所读取文件的内容。
//         // 也就是说, 将File对象转化为base64位字符串
//         reader.readAsDataURL(file);
//         // 上一步是异步操作, 读取完成后会执行onload事件, 而base64的字符串在e.target.rusult中
//         reader.onload = function (e) {
//             // 获得图片dom
//             var base64 = e.target.result
//             img.src = base64;
//             //  console.log(base64.length+ base64);
//             //   create_content(789, '的暑假', base64);
//         };

//         img.onload = function () {
//             // 图片原始尺寸
//             var originWidth = this.width;
//             var originHeight = this.height;

//             console.log('width'+ originWidth);
//             console.log('width' + originHeight);
//             // 最大尺寸限制
//             var maxWidth = this.width*Multiple,
//                 maxHeight = this.height*Multiple;
//                 console.log('maxWidth'+ maxWidth)
//                  console.log('maxHeight' + maxHeight)
//             // 目标尺寸
//             var targetWidth = originWidth,
//                 targetHeight = originHeight;
//             // 图片尺寸超过800x800的限制
//             if (originWidth > maxWidth || originHeight > maxHeight) {
//                 if (originWidth / originHeight > maxWidth / maxHeight) {
//                     // 更宽，按照宽度限定尺寸
//                     targetWidth = maxWidth;
//                     targetHeight = Math.round(
//                         maxWidth * (originHeight / originWidth)
//                     );
//                 } else {
//                     targetHeight = maxHeight;
//                     targetWidth = Math.round(
//                         maxHeight * (originWidth / originHeight)
//                     );
//                 }
//             }
//             // canvas对图片进行缩放
//             canvas.width = targetWidth;
//             canvas.height = targetHeight;
//             // 清除画布
//             context.clearRect(0, 0, targetWidth, targetHeight);
//             // 将图片划到canvas上
//             context.drawImage(img, 0, 0, targetWidth, targetHeight);
//             // 把canvas转成base64格式并去除头
//             var base64 = canvas
//                 .toDataURL(fileType)
//                 // .replace(/^data:image\/(jpeg|jpg|png|gif);base64,/, '');
//             // console.log(base64.length+ base64);
//             // console.log(base64)
//             create_content(78,'的暑假', base64);
//             // 上传base64, 如果需要上传图片只需要参照上面的操作, 重复将base64设置为图片上的src就行了. 当然, 需要在去处头之前
//             //					axios.post('https://yourdomain.com/api/xxx', {
//             //						imageSrc: base64
//             //					}).then(res => {
//             //						console.log(res)
//             //					}).catch(err => {
//             //						console.log(err)
//             //					})
//            var kl;
//            kl= base_flie(base64, flieName);
//            console.log(kl);
//             return [base64,kl]
//         };
//     }


//      function base_flie(dataurl, filename) {//将base64转换为文件
//             var arr = dataurl.split(',');
//             var mime = arr[0].match(/:(.*?);/)[1];
//             var bstr = atob(arr[1]);
//             var n = bstr.length;
//             var u8arr = new Uint8Array(n);
//             while (n--) {
//                 u8arr[n] = bstr.charCodeAt(n);
//             }
//             return new File([u8arr], filename, { type: mime});
//         }



// 图片回显照片中的base64编码,图片的类型,图片的名称,需要原来宽高压缩的等比(0-1之间,0除外)
// function uploadImageHandle(imgbase64, fileType, flieName, Multiple) {
//     console.log(imgbase64);
//     // 创建图片
//     var img = document.createElement('img');
//     img.src = imgbase64;
//     // 生成canvas画布
//     var canvas = document.createElement('canvas');
//     var context = canvas.getContext('2d');
//     // 图片原始尺寸
//     var originWidth = img.width;
//     var originHeight = img.height;
//     // console.log('width' + originWidth);
//     // console.log('width' + originHeight);
//     // 最大尺寸限制
//     var maxWidth = img.width * Multiple;
//     var maxHeight = img.height * Multiple;
//     // console.log('maxWidth' + maxWidth)
//     // console.log('maxHeight' + maxHeight)
//     // 目标尺寸
//     var targetWidth = originWidth;
//     var targetHeight = originHeight;
//     // 图片尺寸超过800x800的限制
//     if (originWidth > maxWidth || originHeight > maxHeight) {
//         if (originWidth / originHeight > maxWidth / maxHeight) {
//             // 更宽，按照宽度限定尺寸
//             targetWidth = maxWidth;
//             targetHeight = Math.round(
//                 maxWidth * (originHeight / originWidth)
//             );
//         } else {
//             targetHeight = maxHeight;
//             targetWidth = Math.round(
//                 maxHeight * (originWidth / originHeight)
//             );
//         }
//     }
//     // canvas对图片进行缩放
//     canvas.width = targetWidth;
//     canvas.height = targetHeight;
//     // 清除画布
//     context.clearRect(0, 0, targetWidth, targetHeight);
//     // 将图片划到canvas上
//     context.drawImage(img, 0, 0, targetWidth, targetHeight);
//     // 把canvas转成base64
//     var base64 = canvas.toDataURL(fileType);
//     console.log(base64);
//     // base64转换file文件
//     var kl = base_flie(base64, flieName);
//     // 将转换的base64和file文件返回
//     // return [base64, kl]
// };

// //将base64转换为flie文件
// function base_flie(dataurl, filename) {
//     var arr = dataurl.split(',');
//     var mime = arr[0].match(/:(.*?);/)[1];
//     var bstr = atob(arr[1]);
//     var n = bstr.length;
//     var u8arr = new Uint8Array(n);
//     while (n--) {
//         u8arr[n] = bstr.charCodeAt(n);
//     }
//     return new File([u8arr], filename, { type: mime });
// }



var file_File = ""; //接收file变量
var base64_YS = ""; //接收压缩的base64编码
var conver_size=""; //接受图片的真是大小
var size_array = []; //接受总图片的真是大小
function uploadImageHandle(File_domain, Multiple) {
    // 创建实例
    var reader = new FileReader();
    var img = document.createElement('img');
    // 读取上传的图片的信息(lastModified, lastModifiedDate, name, size, type等)
    var file = File_domain.files[0];
    // 记下上传的图片的name, 后面会用到
    var flieName = File_domain.files[0].name;
    // console.log(flieName);
    // 记下上传的图片的类型, 后面会用到
    var fileType = file.type;
    // 生成canvas画布
    var canvas = document.createElement('canvas');
    var context = canvas.getContext('2d');
    // MDN: 该方法会读取指定的 Blob 或 File 对象。读取操作完成的时候，
    // readyState 会变成已完成（DONE），并触发 loadend 事件，
    // 同时 result 属性将包含一个data:URL格式的字符串（base64编码）以表示所读取文件的内容。
    // 也就是说, 将File对象转化为base64位字符串
    reader.readAsDataURL(file);
   
    // 上一步是异步操作, 读取完成后会执行onload事件, 而base64的字符串在e.target.rusult中
    reader.onload = function (e) {
        // 获得图片dom
        var base64 = e.target.result
        img.src = base64;
        //  console.log(base64.length+ base64);
        //   create_content(789, '的暑假', base64);
    };

    img.onload = function () {
        // 图片原始尺寸
        var originWidth = this.width;
        var originHeight = this.height;

        // console.log('width' + originWidth);
        // console.log('width' + originHeight);
        // 最大尺寸限制
        var maxWidth = this.width * Multiple,
            maxHeight = this.height * Multiple;
        // console.log('maxWidth' + maxWidth)
        // console.log('maxHeight' + maxHeight)
        // 目标尺寸
        var targetWidth = originWidth,
            targetHeight = originHeight;
        // 图片尺寸超过800x800的限制
        if (originWidth > maxWidth || originHeight > maxHeight) {
            if (originWidth / originHeight > maxWidth / maxHeight) {
                // 更宽，按照宽度限定尺寸
                targetWidth = maxWidth;
                targetHeight = Math.round(
                    maxWidth * (originHeight / originWidth)
                );
            } else {
                targetHeight = maxHeight;
                targetWidth = Math.round(
                    maxHeight * (originWidth / originHeight)
                );
            }
        }
        // canvas对图片进行缩放
        canvas.width = targetWidth;
        canvas.height = targetHeight;
        // 清除画布
        context.clearRect(0, 0, targetWidth, targetHeight);
        // 将图片划到canvas上
        context.drawImage(img, 0, 0, targetWidth, targetHeight);
        // 把canvas转成base64格式并去除头
        var base64 = canvas
            .toDataURL(fileType)
        // .replace(/^data:image\/(jpeg|jpg|png|gif);base64,/, '');
        base64_YS = base64;
        file_File = base_flie(base64, flieName);
        conver_size = file_File.size;
    };
}


function base_flie(dataurl, filename) {//将base64转换为文件
    var arr = dataurl.split(',');
    var mime = arr[0].match(/:(.*?);/)[1];
    var bstr = atob(arr[1]);
    var n = bstr.length;
    var u8arr = new Uint8Array(n);
    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new File([u8arr], filename, { type: mime });
}



// 转化B,kB,MB,GB
function conver(limit) {

    let size = "";

    if (limit < 0.1 * 1024) {    //如果小于0.1KB转化成B

        size = limit.toFixed(2) + "B";

    } else if (limit < 0.1 * 1024 * 1024) {   //如果小于0.1MB转化成KB

        size = (limit / 1024).toFixed(2) + "KB";

    } else if (limit < 0.1 * 1024 * 1024 * 1024) {    //如果小于0.1GB转化成MB

        size = (limit / (1024 * 1024)).toFixed(2) + "MB";

    } else {   //其他转化成GB

        size = (limit / (1024 * 1024 * 1024)).toFixed(2) + "GB";

    }

    var sizestr = size + "";

    var len = sizestr.indexOf("\.");

    var dec = sizestr.substr(len + 1, 2);

    if (dec == "00") {   //当小数点后为00时 去掉小数部分
        return sizestr.substring(0, len) + sizestr.substr(len + 3, 2);
    }

    return sizestr;

}
// 删除相同大小
function delete_array(size){
    // console.log(size);
    for(let i=0;i<size_array.length;i++){
        if (size_array[i] == size) {
            size_array.splice(i, 1);
            console.log(size_array);
            break;
        }
    }
    // console.log(size_array);
}
// 计算所有图片总大小
function total_size(){
    let total=0;
    for (let i = 0; i < size_array.length; i++) {
        total+= parseInt(size_array[i]);
    }
    return parseInt(total);
}