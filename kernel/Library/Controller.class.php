<?php
namespace Library;

class Controller{
	protected $view=null; //视图对象
	protected static $_m=''; //当前被调用的模块
	protected static $_c=''; //当前被调用的控制器
	protected static $_a=''; //当前被调用的控制器方法
	
	protected function middleware($name,$excepts=[]){
		Route::setMiddleware($name,$excepts);
	}

	/**
	 * 模板显示 调用内置的模板引擎显示方法，
	 *
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件 默认为空则由系统自动定位模板文件
	 * @param string $charset 输出编码
	 * @param string $contentType 输出类型
	 * @return void
	 */
	protected function display($templateFile='',$charset='',$contentType=''){
		$this->view()->setMca(self::$_m, self::$_c, self::$_a);
		$this->view()->display($templateFile, $charset, $contentType);
	}

	/**
	 * 输出内容文本可以包括Html 并支持内容解析
	 *
	 * @access protected
	 * @param string $content 输出内容
	 * @param string $charset 模板输出字符集
	 * @param string $contentType 输出类型
	 * @return mixed
	 */
	protected function show($content='',$charset='',$contentType=''){
		$this->view()->setMca(self::$_m, self::$_c, self::$_a);
		$this->view()->display('', $charset, $contentType, $content);
	}

	/**
	 * 获取输出页面内容 调用内置的模板引擎fetch方法，
	 *
	 * @access protected
	 * @param string $templateFile 指定要调用的模板文件 默认为空 由系统自动定位模板文件
	 * @param string $content 模板输出内容
	 * @return string
	 */
	protected function fetch($templateFile='',$content=''){
		$this->view()->setMca(self::$_m, self::$_c, self::$_a);
		return $this->view()->fetch($templateFile, $content);
	}

	/**
	 * 创建静态页面
	 *
	 * @access protected @htmlfile 生成的静态文件名称 @htmlpath 生成的静态文件路径，默认生成到系统根目录
	 * @param string $templateFile 指定要调用的模板文件 默认为空 由系统自动定位模板文件
	 * @return string
	 */
	protected function buildHtml($htmlfile='',$htmlpath='',$templateFile=''){
		$content=$this->fetch($templateFile);
		$htmlpath=!empty($htmlpath) ? $htmlpath : ROOT_PATH;
		$htmlfile=$htmlpath . $htmlfile . C('HTML_FILE_SUFFIX', '.html');
		$fdir=dirname($htmlfile);
		!is_dir($fdir) && mkdir($fdir, 0777, true);
		file_put_contents($htmlfile, $content);
		return $content;
	}

	/**
	 * 模板变量赋值
	 *
	 * @access protected
	 * @param mixed $name 要显示的模板变量
	 * @param mixed $value 变量的值
	 * @return Controller
	 */
	protected function assign($name,$value=''){
		$this->view()->assign($name, $value);
		return $this;
	}

	/**
	 * 取得模板显示变量的值
	 *
	 * @access protected
	 * @param string $name 模板显示变量
	 * @return mixed
	 */
	public function get($name=''){
		return $this->view()->get($name);
	}

	/**
	 * 操作错误跳转的快捷方法
	 *
	 * @access protected
	 * @param string $message 错误信息
	 * @param int|string|bool $code 错误码
	 * @param string|bool|int $jumpUrl 页面跳转地址
	 * @param bool|int $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @return void
	 * 调用方式：
	 * 1. error($message,$code,$jumpUrl,ajax)
	 * 2. error($message,$jumpUrl,ajax)
	 * 3. error($message,ajax)
	 * 4. error($message)
	 */
	protected function error($message='',$code=1,$jumpUrl='',$ajax=false){
		if(is_string($code)){
			if(is_bool($jumpUrl) || is_int($jumpUrl)){
				$ajax=$jumpUrl;
			}
			$jumpUrl=$code;
			$code=1;
		}elseif(is_bool($code)){
			$ajax=$code;
		}
		$this->dispatchJump($message, $code, $jumpUrl, $ajax);
	}

	/**
	 * 操作成功跳转的快捷方法
	 *
	 * @access protected
	 * @param string $message 提示信息
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @return void
	 */
	protected function success($message='',$jumpUrl='',$ajax=false){
		$this->dispatchJump($message, 0, $jumpUrl, $ajax);
	}

	/**
	 * Ajax方式返回数据到客户端
	 *
	 * @access protected
	 * @param mixed $data 要返回的数据
	 * @param String $type AJAX返回数据格式
	 * @param int $json_option 传递给json_encode的option参数
	 * @return void
	 */
	protected function ajaxReturn($data,$type='',$json_option=null){
		if(empty($type)){
			$type=C('DEFAULT_AJAX_RETURN', 'JSON');
		}
		if(is_null($json_option)){
			$json_option=defined('JSON_UNESCAPED_UNICODE') ? JSON_UNESCAPED_UNICODE : 0;
		}
		$response=make('\Library\Response');
		switch(strtoupper($type)){
			case 'JSON':
				// 返回JSON数据格式到客户端 包含状态信息
				$response->header('Content-Type','text/html; charset=utf-8');
				$response->end(json_encode($data, $json_option));
				break;
			case 'JSONP':
				// 返回JSON数据格式到客户端 包含状态信息
				$response->header('Content-Type','text/html; charset=utf-8');
				$var_hdl=C('VAR_JSONP_HANDLER', 'callback');
				$handler=isset($_GET[$var_hdl]) ? $_GET[$var_hdl] : C('DEFAULT_JSONP_HANDLER', 'jsonpReturn');
				$response->end($handler . '(' . json_encode($data, $json_option) . ');');
				break;
			case 'EVAL':
				// 返回可执行的js脚本
				$response->header('Content-Type','text/javascript; charset=utf-8');
				$response->end($data);
				break;
			default:
				$response->header('Content-Type','text/html; charset=utf-8');
				$response->end(var_export($data, true));
				break;
		}
	}

	/**
	 * Action跳转(URL重定向） 支持指定模块和延时跳转
	 *
	 * @access protected
	 * @param string $url 跳转的URL表达式
	 * @param array $params 其它URL参数
	 * @param integer $delay 延时跳转的时间 单位为秒
	 * @param string $msg 跳转提示信息
	 * @return void
	 */
	protected function redirect($url,$params=array(),$delay=0,$msg=''){
		$url=U($url, $params);
		redirect($url, $delay, $msg);
	}

	/**
	 * 默认跳转操作 支持错误导向和正确跳转 调用模板显示 默认为public目录下面的success页面 提示页面为可配置 支持模板标签
	 *
	 * @param string $message 提示信息
	 * @param Boolean $code 状态
	 * @param string $jumpUrl 页面跳转地址
	 * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
	 * @access private
	 * @return void
	 */
	private function dispatchJump($message,$code=0,$jumpUrl='',$ajax=false){
		if(true === $ajax || env('IS_AJAX')){ // AJAX提交
			$data=is_array($ajax) ? $ajax : array();
			$data['message']=$message;
			$data['code']=$code;
			$data['url']=$jumpUrl;
			make('\Library\Response')->end($data);
		}else{
			is_int($ajax) && $this->assign('waitSecond', $ajax*1000);
			!empty($jumpUrl) && $this->assign('jumpUrl', $jumpUrl);
			$this->assign('msgTitle', !$code ? '操作成功！' : '操作失败！');
			$this->get('closeWin') && $this->assign('jumpUrl', 'close');
			$this->assign('code', $code); // 状态
			$this->assign('message', $message); // 提示信息
			if($code){
				!isset($this->waitSecond) && $this->assign('waitSecond', 1000);
				!isset($this->jumpUrl) && $this->assign('jumpUrl', 'auto');
				$this->display(C('TMPL_ACTION_SUCCESS', '/message'));
			}else{
				$this->assign('error', $message);
				!isset($this->waitSecond) && $this->assign('waitSecond', 3000);
				!isset($this->jumpUrl) && $this->assign('jumpUrl', 'auto');
				$this->display(C('TMPL_ACTION_ERROR', '/message'));
			}
		}
	}

	/**
	 * 获取视图对象
	 *
	 * @return View
	 */
	private function view(){
		if(is_null($this->view)){
			$this->view=make('\Library\View');
		}
		return $this->view;
	}

	/**
	 * 运行控制器方法
	 *
	 * @param string|object $concrete 控制器对象或类型
	 * @param string $method 方法名称
	 * @param array $parameters 参数
	 * @param array $isInCalled 是否在模板内部调用 return mixed 说明：增加对调用控制器类和方法的感知
	 */
	public static function run($concrete,$method,array $parameters=[],$isInCalled=false){
		static $classStacks=[];
		
		// 获取控制器类名
		$classname=is_object($concrete) ? get_class($concrete) : $concrete;
		
		// 入栈操作
		array_push($classStacks, $classname);
		
		// 记录控制器的调用信息
		$classes=explode('\\', $classname);
		array_shift($classes);
		$cm=array_shift($classes);
		if($cm!='Controller'){
			self::$_m=strtolower($cm);
			array_shift($classes);
		}else{
			self::$_m='';
		}
		self::$_c=implode('/',$classes);
		self::$_a=$method;
		// 设置内部调用标识
		View::setInCalled($isInCalled);
		$container=Container::getInstance();
		$result=$container->callMethod($concrete, $method, $parameters);
		
		// 出栈操作
		$container->forgetInstance(array_pop($classStacks));
		
		// 重置内部调用标识
		View::setInCalled(false);
		return $result;
	}
	
}