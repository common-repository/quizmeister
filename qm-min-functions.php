<?php
/**
 * QuizMeister. Developed by Chris Dennett (dessimat0r@gmail.com)
 * Donate by PayPal to dessimat0r@gmail.com.
 * Bitcoin: 1JrHT9F96GjHYHHNFmBN2oRt79DDk5kzHq
 */

function quizmeister_intcmp($a,$b) {
    return ($a-$b) ? ($a-$b)/abs($a-$b) : 0;
}

function quizmeister_usortarr(&$array, $key, $callback = 'strnatcasecmp') {
    uasort($array, function($a, $b) use($key, $callback) {
        return call_user_func($callback, $a[$key], $b[$key]);
    });
}

// Checks if string starts with defined text
function quizmeister_starts_with( $string, $starts ) {
    strncmp($string, $starts, strlen($starts)) == 0;
}

// return string padded with specified number of non-breaking spaces
function quizmeister_get_spaces($spaces) {
    $str = '';
    for ($i = 0; $i < $spaces; $i++) {
        $str .= '&nbsp;';
    }
    unset($i);
    return $str;
}

// filters tags field to only contain text and commas
function quizmeister_clean_tags( $string ) {
    $string = preg_replace( '/\s*,\s*/', ',', rtrim( trim( $string ), ' ,' ) );
    return $string;
}

function quizmeister_log( $msg, $type = 'N/A' ) {
    if ( WP_DEBUG == true ) {
        $msg = sprintf( "[%s][%s] %s\n", date( 'Y-m-d H:i:s' ), $type, $msg );
        error_log( $msg, 3, dirname( __FILE__ ) . '/log.txt' );
    }
}

?>
