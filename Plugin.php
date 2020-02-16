<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * 自动下载保存远程图片
 * 
 * @package AutoSaveImage 
 * @author dream
 * @version 1.0.0
 * @link https://www.moxui.com
 */
class AutoSaveImage_Plugin implements Typecho_Plugin_Interface
{
    //上传文件目录(统一目录，不再按日期新建目录，查询到文件已存在时则不再处理)
    const UPLOAD_DIR = '/usr/uploads/auto_save_image/';
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        //下载保存远程图片
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->write = array('AutoSaveImage_Plugin', 'saveFile');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->write = array('AutoSaveImage_Plugin', 'saveFile');
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
    }
    
    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}
    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}
    
    /**
     * 下载保存远程图片
     * 
     * @access public
     * @param array $post 数据结构体
     * @return array
     */
    public static function saveFile($post)
    {
        $text = $post['text'];      
        $urls = self::getImageFromText($text);
        if(!isset($urls) || count($urls) == 0) return $post;

        $date = new Typecho_Date();
        $save_dir = self::UPLOAD_DIR;
        $save_dir = parse_url($save_dir,PHP_URL_PATH);
        $uploadDir = Typecho_Common::url($save_dir, __TYPECHO_ROOT_DIR__);
        //创建上传目录
        if (!is_dir($uploadDir)) {              
          if (!@mkdir($uploadDir, 0644)) {
              throw new Typecho_Widget_Exception(_t('上传目录无法创建, 请手动创建安装目录下的 %s 目录, 并将它的权限设置为可写然后继续尝试', $uploadDir));
          }
        }
        //获取相对路径
        $domain = trim($_SERVER['HTTP_HOST']);
        foreach ($urls as $url) {
          $url = strtolower(trim($url));
          if(strpos($url,'://')===false || strpos($url,$domain)!==false){
            continue;
          }
          $hash = md5($url);
          $array = pathinfo($url); 
          $ext = $array['extension'];
          $filename = $hash.'.'.$ext;//生成要保存在本地的文件名
          $dst_path = $uploadDir.$filename;//本地保存绝对路径
          $dst_file = $save_dir.$filename;//相对保存路径
          $dst_url  = '//'.$domain.$dst_file;//生成的本地文件url
          if(!file_exists($dst_path)){
            //不存在文件则下载
            self::get_remote_file($filename,$url,$save_dir);
          }
          if(file_exists($dst_path)){
            //有文件了，替换内容
            $text = self::replace($text,$url,$dst_url);
          }
        }
        $post['text'] = $text;
        return $post;
    }

    static function getImageFromText($text){
        // $patten = '!(http|https)://[a-z0-9\-\.\/]+\.(?:jpe?g|png|gif)!Ui';
        $patten = '!(https?:\/\/.*\.(?:png|jpg|bmp|jpeg|gif|webp))!Ui';
        preg_match_all($patten, $text, $arr);
        $result = [];
        if(isset($arr) && count($arr) > 0){
            foreach ($arr as $key => $value) {
              if(count($value) > 0){
                foreach ($value as $v) {
                  if(!in_array($v,$result))
                    $result[] = $v;
                }
              }
            }
        }
        return $result;
    }

    static function replace($content,$src,$dst){
      $text = str_ireplace($src,$dst,$content);
      return $text;
      $text = preg_replace_callback('/\b'.$src.'\b/i', function($matches) use ($dst)
        {
           $i=0;
           return join('', array_map(function($char) use ($matches, &$i)
           {
              return ctype_lower($matches[0][$i++])?strtolower($char):strtoupper($char);
           }, str_split($dst)));
        }, $content);
      return $text;
    }
    /**
    * 下载远程文件
    * 
    */
    static function get_remote_file($filename,$url,$save_dir){
         $ch=curl_init();  
         $timeout=3;  

         if(trim($save_dir)=='' || trim($filename)=='' || trim($url)==''){  
            return false;  
         } else {
           
         $base_dir = parse_url($save_dir,PHP_URL_PATH);
         set_time_limit (10);  
         $file = fopen($url, "rb"); 
         if ($file) {   
           $newf = fopen ($_SERVER['DOCUMENT_ROOT'].$base_dir.$filename, "wb");
           if ($newf) {
             while (!feof($file)) {  fwrite($newf, fread($file, 1024 * 8), 1024 * 8);  }  
           }
         }  
         if ($file) {  fclose($file); }  
         if ($newf) {  fclose($newf); }  
           
         if (true){   return $save_dir.$filename; } else{   return false; }   
         }
    }
}
