<?php

namespace glue;

use Glue;

class ErrorHandler extends Component
{

    public $emails = array();
    public $action = 'error';

    public $log=false;
    public $logger=null;

    public function getName($code)
    {
        $names = array (
                E_ERROR              => 'ERROR',
                E_WARNING            => 'WARNING',
                E_PARSE              => 'PARSING ERROR',
                E_NOTICE             => 'NOTICE',
                E_CORE_ERROR         => 'CORE ERROR',
                E_CORE_WARNING       => 'CORE WARNING',
                E_COMPILE_ERROR      => 'COMPILE ERROR',
                E_COMPILE_WARNING    => 'COMPILE WARNING',
                E_USER_ERROR         => 'USER ERROR',
                E_USER_WARNING       => 'USER WARNING',
                E_USER_NOTICE        => 'USER NOTICE',
                E_STRICT             => 'STRICT NOTICE',
                E_RECOVERABLE_ERROR  => 'RECOVERABLE ERROR'
        );
        return isset($names[$code]) ? $names[$code] : 'Caught Exception';
    }

    function handle($errno, $errstr='', $errfile='', $errline='')
    {
        $handlers = ob_list_handlers();
        while(!empty($handlers)){
            ob_end_clean();
            $handlers = ob_list_handlers();
        }

        if(error_reporting() == 0){
            /** Error has been surpressed via an @ **/
            return;
        }

        /**
         * Was this function called by an exception?
         * Shouldn't be! Exceptions are costly!
         */
        if(func_num_args() == 4){

            // called by trigger_error()
            $exception = null;
            list($errno, $errstr, $errfile, $errline) = func_get_args();
            $backtrace = array_reverse(debug_backtrace());
        }else{

            // caught exception
            $exc = func_get_arg(0);
            $errno = $exc->getCode();
            $errstr = $exc->getMessage();
            $errfile = $exc->getFile();
            $errline = $exc->getLine();
            $backtrace = $exc->getTrace();
        }
        
        $err = $this->getName($errno);

        /** Create Error Message **/
        $errMsg = "$err: $errstr in $errfile on line $errline";

        foreach($backtrace as $v){
            if(isset($v['class'])){
                $trace = 'in class '.$v['class'].'::'.$v['function'].'(' . $this->getArguments() . ')';
            }elseif(isset($v['function']) && empty($trace)){
                $trace = 'in function '.$v['function'].'(' . $this->getArguments() . ')';
            }
            break;
        }

        /** Now lets form the message **/
        $errorText = '<h2>Debug Msg</h2>
            <p>'.nl2br($errMsg).'</p>
            <p>Trace: '.nl2br($trace).'</p>
            <p>Back Trace:</p><p>'.$this->printBacktrace().'</p>
            <p>On: '.(php_sapi_name() != 'cli' ? Glue::http()->url('SELF') : $_SERVER['PHP_SELF']).'</p>';
        
        switch($errno){
            case E_NOTICE:
            case E_USER_NOTICE:
            default:

                if(!glue::$debug){

                    if(is_array($this->emails) && $this->emails!==array()){
                        foreach($this->emails as $email)
                            mail($email, 'Critical error of type '.$err,
                                    $errorText,
                                    'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset=iso-8859-1'."\r\n"
                            );
                    }
                    
                    // We don't use route since that can result in never ending cycle of pain
                    //echo $errorText; 
                    if(!glue::runAction($this->action)){
                    	$this->renderError();
                    }
                    if($this->log && ($this->logger instanceof \Closure || is_callable($this->logger)))
                        $this->logger();
                }else{
                    if(glue::http()->isAjax()){
                        header("HTTP/1.1 500 Internal Server Error");
                        echo $errorText;
                    }else{
                        echo $errorText;
                    }
                }
                exit();
                break;
        }

    } // end of errorHandler()

    public function handleFatal()
    {
        $error = error_get_last();

        $text = "<h1>Fatal Error</h1>
            <p>File: ".$error['file']."</p>
            <p>On Line: ".$error['line']."</p>
            <p>Output Message: ".$error['message']."</p>
            <p>On: ".(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI')."</p>
            <h1>Backtrace</h1>".$this->printBacktrace();

        if(!glue::$debug){

            if(is_array($this->emails) && $this->emails!==array()){
                foreach($this->emails as $email)
                    mail(
                        $email, 
                        'Critical error of type Fatal Error',
                        $text,
                        'MIME-Version: 1.0'."\r\n".'Content-type: text/html; charset=iso-8859-1'."\r\n"
                    );
            }

            // We try to produce as little action as possible in the event of a fatal error
            $this->renderError();
        }else{
            echo $text;
        }
        exit(1);
    }

    public function getArguments()
    {
        $trace = '';
        if(isset($v['args'])){
            $separator = '';
        
            foreach($v['args'] as $arg ){
                if(is_array($arg)){
                    $arg = 'Array';
                }
                $trace .= "$separator".$this->getArgument($arg);
                $separator = ', ';
            }
        }
        return $trace;
    }
    
    public function getArgument($arg)
    {
        switch(strtolower(gettype($arg))){
            case 'string':
                return( '"'.str_replace( array("\n"), array(''), $arg ).'"' );
            case 'boolean':
                return (bool)$arg;
            case 'object':
                return 'object('.get_class($arg).')';
            case 'array':
                return $arg;
            case 'resource':
                return 'resource('.get_resource_type($arg).')';
            default:
                return var_export($arg, true);
        }
    }

    public function printBacktrace()
    {
        $backtracel = '';
        foreach(debug_backtrace() as $k=>$v){
            if($v['function'] == "include" || $v['function'] == "include_once" || $v['function'] == "require_once" || $v['function'] == "require"){
                $backtracel .= "#".$k." ".$v['function']."(".$v['args'][0].") called at [".$v['file'].":".$v['line']."]<br />";
            }else{
                $backtracel .= "#".$k." ".(isset($v['function']) ? $v['function'] : null)."() called at [".(isset($v['file']) ? $v['file'] : null).":".
                    (isset($v['line']) ? $v['line'] : null)."]<br />";
            }
        }
        return $backtracel;
    }
    
    public function renderError()
	{
		?>
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
				<meta http-equiv="content-language" content="en"/>
		
				<link rel="shortcut icon" href="/images/favicon.ico" />
		
				<title>OH NOES IT'S A FATAL ERROR!!! - StageX</title>
				
				<style type="text/css">
				h1{
					color: inherit;
					font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
					color:#333333;
					font-weight: 500;
					line-height: 1.1;
					font-size: 2em;
					margin: 0.67em 0;
				}
				
				.error{
					width:600px;
					margin:45px auto;
				}
				
				p{
				    color: #333333;
				    font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
				    font-size: 14px;
				    line-height: 1.42857;
				}
				</style>
			</head>
			<body>
				<div class="error">
				<h1>Boom!!! StageX just blew up!</h1>
				<p>Normally this would show you some video goodness on StageX however an error we that just could not be fixed rose up to steal the day!</p> 
				<p>This might have been temporary and you are encouraged to try and refresh this page.</p>
				</div>
			</body>
		</html>
		<?php 
	}
}