<?php
namespace Flex\Banana\Classes;

final class Log
{
    public const __version = '1.2.2';
    const MESSAGE_FILE   = 3; # 사용자 지정 파일에 저장
    const MESSAGE_ECHO   = 2; # 화면에만 출력
    const MESSAGE_SYSTEM = 0; # syslog 시스템 로그파일에 저장

    public static $message_type = 3;
    public static $logfile = 'log.txt';

    public static $debugs = ['d','v','i','w','e'];
    public static $options = [
        'datetime'   => true,   # 날짜 시간 출력
        'debug_type' => true,   # 디버그 타임 출력
        'newline'    => true    # 한줄내리기 출력
    ];

    # init
    public static function init(int $message_type = -1, string $logfile = null){
        Log::$message_type = ($message_type > -1) ? $message_type : Log::MESSAGE_ECHO;
        Log::$logfile = $logfile ?? 'log.txt';
    }

    # 출력 옵션 설정
    public static function options (array $options=[], bool $datetime=true, bool $debug_type=true, bool $newline=true) : void
    {
        $_options = [];
        if(is_array($options) && count($options)){
            $_options = $options;
        }else{
            $_options = [
                'datetime' => $datetime,  'debug_type' => $debug_type, 'newline' => $newline
            ];
        }

        Log::$options = array_merge(Log::$options, $_options);
    }

    # 출력하고자 하는 옵션 선택
    public static function setDebugs(string|array $m1, ...$mores): void
    {
        $debug_modes = [];
        $debug_modes[] = $m1;
        if(is_array($mores)){
            foreach($mores as $debug_type){
                $debug_modes[] = $debug_type;
            }
        }

        Log::$debugs = $debug_modes;
    }

    # debug
    public static function d (mixed $message, ... $message2) : void
    {
        if(in_array('d', Log::$debugs)){
            $output = Log::filterMessage($message).' | '.implode(' | ',array_map([Log::class, 'filterMessage'],$message2));
            Log::print_('D', $output);
        }
    }

    # success
    public static function v (mixed $message, ... $message2) : void
    {
        if(in_array('v', Log::$debugs)){
            $output = Log::filterMessage($message).' | '.implode(' | ',array_map([Log::class, 'filterMessage'],$message2));
            Log::print_('V', $output);
        }
    }

    # info
    public static function i (mixed $message, ... $message2) : void
    {
        if(in_array('i', Log::$debugs)){
            $output = Log::filterMessage($message).' | '.implode(' | ',array_map([Log::class, 'filterMessage'],$message2));
            Log::print_('I', $output);
        }
    }

    # warning
    public static function w (mixed $message, ... $message2) : void
    {
        if(in_array('w', Log::$debugs)){
            $output = Log::filterMessage($message).' | '.implode(' | ',array_map([Log::class, 'filterMessage'],$message2));
            Log::print_('W', $output);
        }
    }

    # error
    public static function e (mixed $message, ... $message2) : void
    {
        if(in_array('e', Log::$debugs)){
            $output = Log::filterMessage($message).' | '.implode(' | ',array_map([Log::class, 'filterMessage'],$message2));
            Log::print_('E', $output);
        }
    }

    private static function filterMessage ( mixed $message) : mixed
    {
        $result = $message;
        $typeof = gettype($message);
        if($typeof == 'array' || $typeof == 'object'){
            $result = print_r($message,true);
        }
    return $result;
    }

    # print
    private static function print_ (string $debug_type, string $message) : void
    {
        $logfile = (Log::$message_type == Log::MESSAGE_FILE ) ? Log::$logfile : null;
        $out_datetime   = (Log::$options['datetime']) ? date('Y-m-d H:i:s').' ' : '';
        $out_debug_type = (Log::$options['debug_type']) ? '>> '.$debug_type.' : ' : '';
        $out_newline    = (Log::$options['newline']) ? PHP_EOL : '';

        if(Log::$message_type == Log::MESSAGE_ECHO){
            echo sprintf("%s%s%s%s", $out_datetime, $out_debug_type, addslashes($message), $out_newline);
        }else{
            error_log (
                sprintf("%s%s%s%s", $out_datetime, $out_debug_type, addslashes($message), $out_newline),
                    Log::$message_type,
                        $logfile
            );
        }
    }
}